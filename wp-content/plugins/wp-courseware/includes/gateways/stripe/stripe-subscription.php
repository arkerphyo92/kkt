<?php
/**
 * WP Courseware Gateway Stripe - Subscription.
 *
 * @since 4.3.0
 * @subpackage Gateways\Stripe
 * @package WPCW
 */

namespace WPCW\Gateways\Stripe;

use Stripe\ApiResource;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\Plan;
use Stripe\Product;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\Source;
use Stripe\Subscription;
use WPCW\Gateways\Gateway_Stripe;
use WPCW\Models\Course;
use WPCW\Models\Order;
use WPCW\Models\Order_Item;
use WPCW\Models\Subscription as WPCW_Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Stripe_Subscription
 *
 * @since 4.3.0
 */
class Stripe_Subscription {

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	protected $order;

	/**
	 * @var Order_Item The order item object.
	 * @since 4.3.0
	 */
	protected $order_item;

	/**
	 * @var Order The order payment.
	 * @since 4.6.3
	 */
	protected $payment;

	/**
	 * @var PaymentIntent The payment intent object.
	 * @since 4.6.3
	 */
	protected $payment_intent;

	/**
	 * @var Course The course object.
	 * @since 4.3.0
	 */
	protected $course;

	/**
	 * @var Product The stripe product object.
	 * @since 4.3.0
	 */
	protected $product;

	/**
	 * @var Plan The stripe plan object.
	 * @since 4.3.0
	 */
	protected $plan;

	/**
	 * @var Subscription The stripe subscription object.
	 * @since 4.3.0
	 */
	protected $subscription;

	/**
	 * @var Invoice The stripe invoice object.
	 * @since 4.3.0
	 */
	protected $invoice;

	/**
	 * @var Gateway_Stripe The stripe payment gateway.
	 * @since 4.3.0
	 */
	protected $gateway;

	/**
	 * Stripe_Subscription constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param Gateway_Stripe The stripe gateway.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Create Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param Order_Item $order_item The order item object.
	 * @param Order $payment The payment object.
	 * @param SetupIntent|PaymentIntent $payment_intent The stripe payment intent object.
	 *
	 * @return string The subscription id.
	 */
	public function create_subscription( Order &$order, Order_Item $order_item, Order &$payment, ApiResource $payment_intent ) {
		// Set Vars.
		$this->order          = $order;
		$this->order_item     = $order_item;
		$this->payment        = $payment;
		$this->payment_intent = $payment_intent;

		// Get Course.
		$this->course = $this->order_item->get_course();

		// Check the course to see if its a subscription.
		if ( ! $this->course->is_subscription() ) {
			throw new Stripe_Exception( 'course-is-not-a-subscription', sprintf( __( '%1$s ( Course ID: %2$s ) - is not a subscription. Subscription not created.', 'wp-courseware' ), $this->course->get_course_title(), $this->course->get_course_id() ) );
		}

		// Check for customer.
		if ( empty( $this->payment_intent->customer ) ) {
			throw new Stripe_Exception( 'customer-is-not-set', esc_html__( 'The customer needed to process a subscription is empty. Subscription not created.', 'wp-courseware' ) );
		}

		// Create Stripe Plan.
		$this->maybe_create_plan();

		// Create Subscription.
		$subscription = new WPCW_Subscription();
		$subscription->create();

		// Additional check.
		if ( ! $subscription || ! $subscription->get_id() ) {
			throw new Stripe_Exception( 'subscription-setup-error', sprintf( __( 'Subscription Setup Error for stripe subscription Id: %s', 'wp-courseware' ), $stripe_sub_id ) );
		}

		// Item Discount.
		// Discounts only apply to the initial payment, so it should always be 0 for subscriptions
		$item_discount   = 0; //$this->order_item->get_discount();
		$discount_coupon = '';

		// Populate Item Discount.
		if ( $item_discount > 0 ) {
			$discount_coupon = $this->gateway->get_api()->create_coupon( array(
				'id'              => "ONE_TIME_DISCOUNT_{$this->order_item->get_id()}_{$this->order->get_order_id()}",
				'duration'        => 'once',
				'amount_off'      => wpcw_round( $item_discount * 100 ),
				'currency'        => wpcw_get_currency(),
				'max_redemptions' => 1,
			) );

			// Check for error.
			if ( ! empty( $discount_coupon->error ) ) {
				throw new Stripe_Exception( $discount_coupon->error->type, $discount_coupon->error->message );
			}
		}

		// Create Stripe Subscription.
		$subscription_args = array(
			'customer'               => $this->payment_intent->customer,
			'default_source'         => $this->payment_intent->payment_method,
			'default_payment_method' => $this->payment_intent->payment_method,
			'items'                  => array(
				array(
					'plan'     => $this->get_plan()->id,
					'quantity' => 1,
				),
			),
			'off_session'            => true,
			'metadata'               => array(
				'subscription_id'  => $subscription->get_id(),
				'order_id'         => $this->order->get_order_id(),
				'student_id'       => $this->order->get_student_id(),
				'student_email'    => $this->order->get_student_email(),
				'installment_plan' => $this->course->charge_installments(),
			),
		);

		// Apply coupon if exists.
		if ( ! empty( $discount_coupon ) ) {
			$subscription_args['coupon'] = $discount_coupon->id;
		}

		// Subscription Taxes
		if ( wpcw_taxes_enabled() ) {
			$subscription_args['tax_percent'] = wpcw_get_tax_percentage();
		}

		// Get the inverval and create the trial end when creating.
		if ( ( $subscription_interval = $this->get_interval() ) ) {
			if ( 'quarter' === $subscription_interval ) {
				$subscription_args['trial_end'] = strtotime( '+3 months' );
			} elseif ( 'semi-year' === $subscription_interval ) {
				$subscription_args['trial_end'] = strtotime( '+6 months' );
			} else {
				$subscription_args['trial_end'] = strtotime( '+1 ' . $subscription_interval );
			}
		}

		/**
		 * Filter: Stripe Subscription Args.
		 *
		 * @since 4.3.0
		 *
		 * @param string The customer id.
		 * @param string The payment method id.
		 * @param Plan The stripe source object.
		 * @param Product The stripe product object.
		 * @param array $subscription_args The subscription args.
		 *
		 * @return array $subscription_args The modified subscription arguments.
		 */
		$subscription_args = apply_filters(
			'wpcw_stripe_subscription_args',
			$subscription_args,
			$this->payment_intent->customer,
			$this->payment_intent->payment_method,
			$this->plan,
			$this->product
		);

		// Create a Subscription.
		$this->subscription = $this->gateway->get_api()->create_subscription( $subscription_args );

		// Check for error.
		if ( ! empty( $this->subscription->error ) ) {
			throw new Stripe_Exception( $this->subscription->error->type, $this->subscription->error->message );
		}

		// Log the subscription.
		$this->log( sprintf( 'Subscription created successfully. Subscription ID: %s', $this->subscription->id ) );

		// Set Properties.
		$subscription->set_props( array(
			'profile_id'       => $this->get_id(),
			'student_id'       => $this->order->get_student_id(),
			'student_name'     => $this->order->get_student_full_name(),
			'student_email'    => $this->order->get_student_email(),
			'order_id'         => $this->order->get_order_id(),
			'method'           => $this->order->get_payment_method(),
			'created'          => date( 'Y-m-d H:i:s', $this->subscription->current_period_start ),
			'expiration'       => date( 'Y-m-d H:i:s', $this->subscription->current_period_end ),
			'installment_plan' => $this->course->charge_installments(),
		) );

		// Set Course Id.
		$subscription->set_prop( 'course_id', $this->order_item->get_course_id() );
		$subscription->set_prop( 'course_title', $this->order_item->get_order_item_title() );

		// Set Amounts and Period.
		$subscription->set_props( array(
			'initial_amount'   => ( $this->get_amount() ) ? $this->gateway->format_stripe_amount( $this->get_amount() ) : $this->order_item->get_amount(),
			'recurring_amount' => ( $this->get_amount() ) ? $this->gateway->format_stripe_amount( $this->get_amount() ) : $this->order_item->get_amount(),
			'period'           => ( $this->get_interval() ) ? $this->get_interval() : $this->get_course()->get_payments_interval(),
		) );

		// Create Subscription Payment.
		$this->payment = $subscription->create_payment( true, $this->payment );

		// Set Subscription Id.
		$this->payment->set_prop( 'subscription_id', $subscription->get_id() );

		// Subscription Transaction Id.
		$subscription_transaction_id = $this->payment_intent->id;

		// Set Transaction Id.
		$subscription->set_prop( 'transaction_id', $subscription_transaction_id );

		// Set Status.
		if ( in_array( $this->get_status(), array( 'active', 'trialing' ) ) ) {
			$activated_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Subscription Plan #%1$s activated. Stripe Installment Subscription Plan ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $this->get_id() )
				: sprintf( __( 'Subscription #%1$s activated. Stripe Subscription Plan ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $this->get_id() );

			$subscription->set_status( 'active', $activated_message );
		} else {
			$on_hold_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Subscription Plan #%1$s pending payment. Stripe Installment Subscription Plan ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $this->get_id() )
				: sprintf( __( 'Subscription #%1$s pending payment. Stripe Subscription Plan ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $this->get_id() );

			$subscription->set_status( 'on-hold', $on_hold_message );
		}

		$bill_times = ((int)$subscription->get_bill_times() + 1);
		$subscription->set_prop( 'bill_times', absint( $bill_times ) );

		// Add Meta to Order.
		if ( $subscription->is_installment_plan() ) {
			$this->payment->update_meta( '_installment_payment', true );
			$this->payment->update_meta( '_installment_payment_number', $bill_times );
		}

		// Save Subscription.
		if ( $subscription->save() ) {
			$saved_message_log = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Subscription #%1$s saved successfully. Installment Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) )
				: sprintf( __( 'Subscription #%1$s saved successfully. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) );

			$this->log( $saved_message_log );
		} else {
			$error_message_log = $subscription->is_installment_plan()
				? sprintf( __( 'Subscription #%1$s save error. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) )
				: sprintf( __( 'Subscription #%1$s save error. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) );

			$this->log( $error_message_log );
		}

		// Set Status.
		$payment_processing_message = $subscription->is_installment_plan()
			? sprintf( __( 'Installment Payment #%s is processing. `PaymentIntent` Id: %s', 'wp-courseware' ), $this->payment->get_order_number(), $subscription_transaction_id )
			: sprintf( __( 'Subscription Payment #%s is processing. `PaymentIntent` Id: %s', 'wp-courseware' ), $this->payment->get_order_number(), $subscription_transaction_id );

		$this->payment->add_order_note( $payment_processing_message );

		return $this->get_id();
	}

	/**
	 * Get Stripe Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|Subscription The subscription object or false on failure.
	 */
	public function get_subscription() {
		if ( empty( $this->subscription->id ) ) {
			return false;
		}

		return $this->subscription;
	}

	/**
	 * Get Plan Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The plan id.
	 */
	protected function get_plan_id() {
		if ( $this->order_item->use_installments() ) {
			$plan_id = sprintf(
				'%s-%s-installments-of-%s-plan',
				sanitize_title( $this->order_item->get_order_item_title() ),
				strtolower( $this->course->get_installments_interval_label() ),
				$this->course->get_installments_amount()
			);
		} else {
			$plan_id = sprintf(
				'%s-%s-%s-plan',
				sanitize_title( $this->order_item->get_order_item_title() ),
				$this->course->get_payments_price(),
				$this->course->get_payments_interval()
			);
		}

		return sanitize_key( $plan_id );
	}

	/**
	 * Get Plan Args.
	 *
	 * @since 4.3.0
	 *
	 * @return array The plan arguments.
	 */
	protected function get_plan_args() {
		// Intervals.
		$interval       = $this->course->get_payments_interval();
		$interval_count = 1;

		// Modify for quarter and semi-year.
		switch ( $interval ) {
			case 'quarter' :
				$interval       = 'month';
				$interval_count = 3;
				break;
			case 'semi-year' :
				$interval       = 'month';
				$interval_count = 6;
				break;
		}

		$desc = $this->gateway->get_statement_desc();

		if ( $this->order_item->use_installments() ) {
			$name = sprintf(
				esc_html__( '%s - [%s Installments of %s]', 'wp-courseware' ),
				$this->order_item->get_order_item_title(),
				$this->course->get_installments_interval_label(),
				html_entity_decode( wpcw_price( $this->course->get_installments_amount() ), ENT_QUOTES, get_bloginfo( 'charset' ) )
			);
		} else {
			$name = sprintf(
				esc_html__( '%s - [%s %s]', 'wp-courseware' ),
				$this->order_item->get_order_item_title(),
				html_entity_decode( wpcw_price( $this->course->get_payments_price() ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				$this->course->get_subscription_interval()
			);
		}

		$amount = $this->gateway->get_total( $this->course->get_payments_price() );

		if ( empty( $desc ) ) {
			$desc = $this->gateway->format_statement_desc( strtoupper( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) );
		}

		return apply_filters( 'wpcw_stripe_plan_args', array(
			'id'                   => $this->get_plan_id(),
			'name'                 => $name,
			'amount'               => $amount,
			'currency'             => wpcw_get_currency(),
			'interval'             => $interval,
			'interval_count'       => $interval_count,
			'statement_descriptor' => $desc,
			'metadata'             => array(
				'course_title'     => $this->course->get_course_title(),
				'course_id'        => $this->course->get_course_id(),
				'use_installments' => $this->course->charge_installments(),
			),
		) );
	}

	/**
	 * Maybe Create Plan.
	 *
	 * @since 4.3.0
	 *
	 * @return Plan|object The stripe plan object or an error object on failure.
	 */
	protected function maybe_create_plan() {
		if ( empty( $this->order_item ) ) {
			return;
		}

		if ( ! empty( $this->plan ) && $this->plan instanceof Plan ) {
			return $this->plan;
		}

		try {
			$this->plan = $this->gateway->get_api()->get_plan( $this->get_plan_id() );
			$currency   = strtolower( wpcw_get_currency() );

			if ( ! empty( $this->plan->error ) ) {
				throw new Stripe_Exception( $this->plan->error->type, $this->plan->error->message );
			}

			if ( strtolower( $this->plan->currency ) !== $currency ) {
				try {
					$this->plan = $this->gateway->get_api()->get_plan( $this->get_plan_id() );

					if ( ! empty( $this->plan->error ) ) {
						throw new Stripe_Exception( $this->plan->error->type, $this->plan->error->message );
					}
				} catch ( Stripe_Exception $exception ) {
					$plan_args       = $this->get_plan_args();
					$plan_args['id'] = "{$this->get_plan_id()}_{$currency}";

					$this->plan = $this->create_plan( $plan_args );

					if ( ! empty( $this->plan->error ) ) {
						throw new Stripe_Exception( $this->plan->error->type, $this->plan->error->message );
					}
				}
			}
		} catch ( Stripe_Exception $exception ) {
			$this->plan = $this->create_plan();

			if ( ! empty( $this->plan->error ) ) {
				throw new Stripe_Exception( $this->plan->error->type, $this->plan->error->message );
			}
		}
	}

	/**
	 * Create Plan.
	 *
	 * @since 4.3.0
	 *
	 * @return Plan The stripe subscription plan.
	 */
	protected function create_plan( $args = array() ) {
		$args = wp_parse_args( $args, $this->get_plan_args() );
		$id   = md5( serialize( $args ) );

		try {
			$this->product = $this->gateway->get_api()->get_product( $id );

			if ( ! empty( $this->product->error ) ) {
				throw new Stripe_Exception( $this->product->error->type, $this->product->error->message );
			}
		} catch ( Stripe_Exception $exception ) {
			$product_args = array(
				'id'   => $id,
				'name' => $args['name'],
				'type' => 'service',
			);

			if ( ! empty( $args['statement_descriptor'] ) ) {
				$product_args['statement_descriptor'] = $args['statement_descriptor'];
			}

			if ( ! empty( $args['metadata'] ) ) {
				$product_args['metadata'] = $args['metadata'];
			}

			$this->product = $this->gateway->get_api()->create_product( $product_args );

			if ( ! empty( $this->product->error ) ) {
				return $this->product;
			}
		}

		if ( ! empty( $this->product ) ) {
			$args['product'] = $this->product->id;

			unset( $args['name'], $args['statement_descriptor'] );

			$this->plan = $this->gateway->get_api()->create_plan( $args );

			if ( ! empty( $this->plan->error ) ) {
				return $this->plan;
			}
		}

		return $this->plan;
	}

	/**
	 * Get Plan.
	 *
	 * @since 4.3.0
	 *
	 * @return Plan The plan object.
	 */
	public function get_plan() {
		if ( empty( $this->plan ) ) {
			$this->maybe_create_plan();
		}

		return $this->plan;
	}

	/**
	 * Get Subscription Id.
	 *
	 * @return bool|string The subscription id if set, false otherwise.
	 */
	public function get_id() {
		if ( empty( $this->subscription ) || ! $this->subscription instanceof Subscription ) {
			return false;
		}

		return $this->subscription->id;
	}

	/**
	 * Get Subscription Plan Amount.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|string The subscription plan amount or false on failure.
	 */
	public function get_amount() {
		if ( empty( $this->plan->id ) ) {
			return false;
		}

		return $this->plan->amount;
	}

	/**
	 * Get Subscription Plan Interval.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|string The subscription plan interval or false on failure.
	 */
	public function get_interval() {
		if ( empty( $this->plan->interval ) ) {
			return false;
		}

		// Intervals
		$interval       = $this->plan->interval;
		$interval_count = $this->plan->interval_count;

		// Adjustment for quarter
		if ( 'month' === $interval && 3 === absint( $interval_count ) ) {
			$interval = 'quarter';
		}

		// Adjustment for semi-year
		if ( 'month' === $interval && 6 === absint( $interval_count ) ) {
			$interval = 'semi-year';
		}

		return $interval;
	}

	/**
	 * Get Subscription Status.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|string $status The subscription status or false on failure.
	 */
	public function get_status() {
		if ( empty( $this->subscription->id ) ) {
			return false;
		}

		return $this->subscription->status;
	}

	/**
	 * Get Subscription Course.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|Course The course object or false on failure.
	 */
	public function get_course() {
		if ( ! $this->course->get_course_id() ) {
			return false;
		}

		return $this->course;
	}

	/**
	 * Get Payment.
	 *
	 * @since 4.3.0
	 *
	 * @return Order $payment The payment order.
	 */
	public function get_payment() {
		if ( empty( $this->payment ) ) {
			return false;
		}

		return $this->payment;
	}

	/**
	 * Log Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The message text.
	 */
	protected function log( $message = '' ) {
		$this->gateway->log( $message );
	}
}
