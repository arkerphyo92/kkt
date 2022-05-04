<?php
/**
 * WP Courseware Gateway Stripe - Api.
 *
 * @package WPCW
 * @subpackage Gateways\Stripe
 * @since 4.3.0
 */

namespace WPCW\Gateways\Stripe;

use Stripe\Charge;
use Stripe\Event;
use WPCW\Gateways\Gateway_Stripe;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Stripe_Webhooks
 *
 * @since 4.3.0
 */
class Stripe_Webhooks {

	/**
	 * @since 4.3.0
	 * @var Gateway_Stripe
	 */
	protected $gateway;

	/**
	 * @since 4.3.0
	 * @var int Retry Interval.
	 */
	protected $retry_interval = 2;

	/**
	 * Stripe_Webhooks constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param Gateway_Stripe The Stripe Gateway parent object.
	 */
	public function __construct( Gateway_Stripe $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Load Stripe Webhooks.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_api_gateway-stripe', array( $this, 'webhook_handler' ) );
	}

	/**
	 * Webhook Handler.
	 *
	 * @since 4.3.0
	 */
	public function webhook_handler() {
		$request_body    = file_get_contents( 'php://input' );
		$request_headers = array_change_key_case( $this->get_request_headers(), CASE_UPPER );

		if ( $this->is_valid_request( $request_headers, $request_body ) ) {
			$this->process_webhook( $request_body );
			status_header( 200 );
			http_response_code( 200 );
			ob_end_clean();
			exit;
		} else {
			$this->gateway->setup();
			$this->log( sprintf( 'Incoming webhook failed validation: Headers: %1$s - Body: %2$s', wpcw_print_r( $request_headers, true ), wpcw_print_r( $request_body, true ) ) );
			status_header( 400 );
			http_response_code( 400 );
			ob_end_clean();
			exit;
		}
	}

	/**
	 * Get Request Headers.
	 *
	 * @since 4.3.0
	 *
	 * @return array Requested headers.
	 */
	protected function get_request_headers() {
		if ( ! function_exists( 'getallheaders' ) ) {
			$headers = [];
			foreach ( $_SERVER as $name => $value ) {
				if ( 'HTTP_' === substr( $name, 0, 5 ) ) {
					$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
				}
			}

			return $headers;
		} else {
			return getallheaders();
		}
	}

	/**
	 * Is Valid Request?
	 *
	 * @since 4.6.3
	 *
	 * @param string $request_headers The request headers from Stripe.
	 * @param string $request_body The request body from Stripe.
	 *
	 * @return bool
	 */
	protected function is_valid_request( $request_headers = null, $request_body = null ) {
		if ( null === $request_headers || null === $request_body ) {
			return false;
		}

		if ( ! empty( $request_headers['USER-AGENT'] ) && ! preg_match( '/Stripe/', $request_headers['USER-AGENT'] ) ) {
			return false;
		}

		// Setup Gateway.
		$this->gateway->setup();

		// Webhook Secret.
		$webhook_secret = $this->gateway->get_webhook_secret();

		if ( ! empty( $webhook_secret ) ) {
			// Check for a valid signature.
			$signature_format = '/^t=(?P<timestamp>\d+)(?P<signatures>(,v\d+=[a-z0-9]+){1,2})$/';
			if ( empty( $request_headers['STRIPE-SIGNATURE'] ) || ! preg_match( $signature_format, $request_headers['STRIPE-SIGNATURE'], $matches ) ) {
				return false;
			}

			// Verify the timestamp.
			$timestamp = intval( $matches['timestamp'] );
			if ( abs( $timestamp - time() ) > MINUTE_IN_SECONDS ) {
				return;
			}

			// Generate the expected signature.
			$signed_payload     = $timestamp . '.' . $request_body;
			$expected_signature = hash_hmac( 'sha256', $signed_payload, $webhook_secret );

			// Check if the expected signature is present.
			if ( ! preg_match( '/,v\d+=' . preg_quote( $expected_signature, '/' ) . '/', $matches['signatures'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get Stripe Api.
	 *
	 * @since 4.3.0
	 *
	 * @throws Stripe_Exception
	 */
	protected function get_api() {
		$api = $this->gateway->get_api();

		if ( ! empty( $api->error ) ) {
			throw new Stripe_Exception( $api->error->message, $api->error->localized );
		}

		return $api->is_available() ? $api : false;
	}

	/**
	 * Log Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The message to log.
	 */
	protected function log( $message = '' ) {
		$this->gateway->log( $message );
	}

	/**
	 * Process Webhook.
	 *
	 * @version 4.0.0
	 *
	 * @since 4.0.0
	 *
	 * @param string $request_body
	 *
	 */
	protected function process_webhook( $request_body ) {
		$webhook = json_decode( $request_body );
		$this->gateway->setup();

		$webhook_test = false;

		try {
			$api = $this->get_api();

			if ( ! isset( $webhook->id ) ) {
				throw new Stripe_Exception( 'stripe-api-webhook-no-id', esc_html__( 'Stripe API Error: Webhook does not have an ID.', 'wp-courseware' ) );
			}

			$webhook_test = $webhook->livemode ?: true;

			if ( ! $webhook_test ) {
				$webhook = $api->get_event( $webhook->id );
			}

			if ( ! empty( $webhook->error ) ) {
				throw new Stripe_Exception( $webhook->error->message, $webhook->error->localized );
			}
		} catch ( Stripe_Exception $exception ) {
			$this->log( $exception->getMessage() );

			return;
		}

		$this->log( sprintf( 'About to process webhook type: %s', $webhook->type ) );

		// Define Webhook Data.
		$object = $webhook->data->object;

		// Check to see if there is any data.
		if ( empty( $object ) ) {
			$this->log( sprintf( 'Webhook data object is empty. Webhook Object: %s', wpcw_print_r( $webhook, true ) ) );

			return;
		}

		/** @var Event The webook object. */
		switch ( $webhook->type ) {
			case 'charge.refunded':
				$this->webhook_charge_refunded( $object, $webhook, $webhook_test );
				break;
			case 'review.opened':
				$this->webhook_review_opened( $object, $webhook, $webhook_test );
				break;
			case 'review.closed':
				$this->webhook_review_closed( $object, $webhook, $webhook_test );
				break;
			case 'invoice.payment_failed' :
				$this->webhook_invoice_payment_failed( $object, $webhook, $webhook_test );
				break;
			case 'invoice.payment_succeeded' :
				$this->webhook_invoice_payment_succeeded( $object, $webhook, $webhook_test );
				break;
			case 'customer.subscription.deleted' :
				$this->webhook_customer_subscription_deleted( $object, $webhook, $webhook_test );
				break;
			case 'customer.subscription.updated' :
				$this->webhook_customer_subscription_updated( $object, $webhook, $webhook_test );
				break;
		}

		/**
		 * Action: Stripe Process Webhook.
		 *
		 * @since 4.3.0
		 *
		 * @param object The webhook data object.
		 * @param string The webhook type.
		 * @param Event The stripe event.
		 * @param Stripe_Webhooks The stripe webhooks object.
		 */
		do_action( 'wpcw_stripe_process_webhook', $object, $webhook->type, $webhook, $this );
	}

	/** Stripe Webhooks -------------------------------------------------- */

	/**
	 * Stripe Webhook: Charge Refunded.
	 *
	 * Occurs whenever a charge is refunded, including partial refunds.
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 * @param bool $test Is webhook a test webhook? Default is false.
	 */
	protected function webhook_charge_refunded( $object, $webhook, $test = false ) {
		$order = wpcw()->orders->get_order_by_transaction_id( $object->id );

		if ( ! $order ) {
			$this->log( sprintf( 'Could not find order via transaction id: %s', $object->id ) );

			return;
		}

		if ( 'stripe' !== $order->get_payment_method() || $order->has_status( 'refunded' ) ) {
			$this->log( sprintf( 'Order #%1$s cannot or has already been refunded: Refund Id: %2$s', $order->get_order_id(), $object->id ) );

			return;
		}

		$refund        = $object->refunds->data[0];
		$refund_id     = $refund->id;
		$refund_amount = $this->gateway->format_stripe_amount( $refund->amount );
		$refund_reason = esc_html__( 'Refunded via Stripe Dashboard', 'wp-courseware' );

		$order->add_meta( '_stripe_refund_id', $refund_id );
		$order->add_meta( '_stripe_refund_amount', $refund_amount );

		$order->update_status( 'refunded', sprintf( __( 'Refunded %1$s - Refund ID: %2$s - %3$s', 'wp-courseware' ), wpcw_price( $refund_amount ), $refund_id, $refund_reason ) );
	}

	/**
	 * Stripe Webhook: Review Opened.
	 *
	 * Occurs whenever a review is opened.
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 * @param bool $test Is webhook a test webhook? Default is false.
	 */
	protected function webhook_review_opened( $object, $webhook, $test = false ) {
		$order = wpcw()->orders->get_order_by_transaction_id( $object->charge );

		if ( ! $order ) {
			$this->log( sprintf( 'Could not find order via transaction id: %s', $object->charge ) );

			return;
		}

		/* translators: %1$s The stripe transaction url %2$s The stripe review reason. */
		$message = sprintf(
			__( 'A review has been opened for this order. Action is needed. Please go to your <a href="%1$s" title="Stripe Dashboard" target="_blank">Stripe Dashboard</a> to review the issue. Reason: (%2$s)', 'wp-courseware' ),
			$this->get_transaction_url( $order ),
			$object->reason
		);

		if ( apply_filters( 'wpcw_stripe_webhook_review_change_order_status', true, $order, $object, $webhook ) ) {
			$order->update_status( 'on-hold', $message );
		} else {
			$order->add_order_note( $message );
		}
	}

	/**
	 * Stripe Webhook: Review Closed.
	 *
	 * Occurs whenever a review is closed. The review's reason field indicates why:
	 * - approved
	 * - disputed
	 * - refunded
	 * - refunded_as_fraud
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 * @param bool $test Is webhook a test webhook? Default is false.
	 */
	protected function webhook_review_closed( $object, $webhook, $test = false ) {
		$order = wpcw()->orders->get_order_by_transaction_id( $object->charge );

		if ( ! $order ) {
			$this->log( sprintf( 'Could not find order via transaction id: %s', $object->charge ) );

			return;
		}

		/* translators: %s: The reason type. */
		$message = sprintf( __( 'The opened review for this order is now closed. Reason: (%s)', 'wp-courseware' ), $object->reason );

		if ( $order->has_order_status( 'on-hold' ) ) {
			if ( apply_filters( 'wpcw_stripe_webhook_review_change_order_status', true, $order, $object, $webhook ) ) {
				$order->update_status( 'processing', $message );
			} else {
				$order->add_order_note( $message );
			}
		} else {
			$order->add_order_note( $message );
		}
	}

	/**
	 * Stripe Webhook: Invoice Payment Failed.
	 *
	 * Occurs whenever an invoice payment fails.
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 * @param bool $test Is webhook a test webhook? Default is false.
	 */
	protected function webhook_invoice_payment_failed( $object, $webhook, $test = false ) {
		$subscription_id      = $object->subscription;
		$transaction_id       = $object->charge;
		$test_webhook_request = false;

		if ( strpos( $subscription_id, 'sub_00000000000000' ) !== false ) {
			$test_webhook_request = true;
		}

		if ( ! $test_webhook_request ) {
			// Stripe Subscription.
			$stripe_subscription = $this->gateway->get_api()->get_subscription( $subscription_id );

			// Record Error.
			if ( ! empty( $stripe_subscription->error ) ) {
				$this->log( $stripe_subscription->error->message );

				return;
			}

			// See if trial is still in place before trying to create another payment.
			if ( 0 === (int) $object->total && ( $stripe_subscription && current_time( 'timestamp' ) < $stripe_subscription->trial_end ) ) {
				$this->log( sprintf( 'Subscription [%s] trial payment still in place. Exiting...', $subscription_id ) );

				return;
			}
		}

		$subscription = wpcw()->subscriptions->get_subscription_by_profile_id( $subscription_id );

		// Check to see if there is no subscription, if test mode, get last description.
		if ( ! $subscription && $test_webhook_request && $test ) {
			$subscription = wpcw()->subscriptions->get_last_subscription();
		}

		if ( ! $subscription ) {
			$this->log( sprintf( 'Could not find subscription via subscription id: %s', $subscription_id ) );

			return;
		}

		// Append a unique id to it if in test mode.
		if ( $test_webhook_request && $test ) {
			$transaction_id = 'ch_' . uniqid();
		}

		$subscription_payment_failed_note = $subscription->is_installment_plan()
			? sprintf( __( 'Stripe Installment Subscription Payment Failed. Transaction Id: %s', 'wp-courseware' ), $transaction_id )
			: sprintf( __( 'Stripe Subscription Payment Failed. Transaction Id: %s', 'wp-courseware' ), $transaction_id );

		$subscription->add_note( $subscription_payment_failed_note );

		$subscription->payment_failed();
	}

	/**
	 * Stripe Webhook: Invoice Payment Succeeded.
	 *
	 * Occurs whenever an invoice payment succeeds.
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 * @param bool $test Is webhook a test webhook? Default is false.
	 */
	protected function webhook_invoice_payment_succeeded( $object, $webhook, $test = false ) {
		// Data.
		$subscription_id      = $object->subscription;
		$transaction_id       = $object->charge;
		$allow_stripe_api     = true;
		$test_webhook_request = false;

		if ( strpos( $subscription_id, 'sub_00000000000000' ) !== false ) {
			$test_webhook_request = true;
		}

		if ( ! $test_webhook_request ) {
			// Stripe Subscription.
			$stripe_subscription = $this->gateway->get_api()->get_subscription( $subscription_id );

			// Record Error.
			if ( ! empty( $stripe_subscription->error ) ) {
				$this->log( $stripe_subscription->error->message );

				return;
			}

			// See if trial is still in place before trying to create another payment.
			if ( 0 === (int) $object->total && ( $stripe_subscription && current_time( 'timestamp' ) < $stripe_subscription->trial_end ) ) {
				$this->log( sprintf( 'Subscription [%s] trial payment still in place. Exiting...', $subscription_id ) );

				return;
			}
		}

		// Get Subscription.
		$subscription = wpcw()->subscriptions->get_subscription_by_profile_id( $subscription_id );

		// Check to see if there is no subscription, if test mode, get last subscription.
		if ( ! $subscription && $test_webhook_request && $test ) {
			$allow_stripe_api = false;
			$subscription     = wpcw()->subscriptions->get_last_subscription();
		}

		// Check to see if it exists.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Could not find subscription via ID: %s', $subscription_id ) );

			return;
		}

		// Append a unique id to it if in test mode.
		if ( $test_webhook_request && $test ) {
			$transaction_id = str_replace( '_00000000000000', 'ch_' . uniqid(), $transaction_id );
		}

		// Check for Payments
		$payments = $subscription->get_payments( array( 'transaction_id' => $transaction_id ) );

		// Bail if there are existing payments with the same transaction id.
		if ( ! empty( $payments ) ) {
			$this->log( sprintf( 'Payment already recorded for Subscription %s and Transaction Id %s. Aborting...', $subscription->get_id(), $transaction_id ) );

			return;
		}

		// Log that nothing has been recorded.
		$new_payment_order_log = $subscription->is_installment_plan()
			? sprintf( 'No payments recorded for Installment Plan %s and Transaction Id %s. Creating New Payment...', $subscription->get_id(), $transaction_id )
			: sprintf( 'No payments recorded for Subscription %s and Transaction Id %s. Creating New Payment...', $subscription->get_id(), $transaction_id );

		$this->log( $new_payment_order_log );

		// Sub Data.
		$subtotal   = wpcw_round( $object->subtotal / 100 );
		$tax        = wpcw_round( $object->tax / 100 );
		$total      = wpcw_round( $object->total / 100 );
		$period_end = $object->period_end;
		$paid       = $object->paid;
		$bill_times = $subscription->get_bill_times();

		if ( ! $test || $allow_stripe_api ) {
			// Check Status.
			if ( in_array( $stripe_subscription->status, array( 'past_due', 'canceled', 'unpaid' ) ) ) {
				switch ( $stripe_subscription->status ) {
					case 'canceled' :
						$subscription->cancel();
						break;
					case 'past_due' :
						$past_due_status_message = $subscription->is_installment_plan()
							? sprintf( __( 'Installment Subscription Plan #%1$s is on hold as Stripe has reported that the installment subscription plan payment is past due. Stripe Installment Subscription Plan ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id )
							: sprintf( __( 'Subscription #%1$s is on hold as Stripe has reported that the subscription payment is past due. Stripe Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id );

						$subscription->update_status( 'on-hold', $past_due_status_message );
						break;
					case 'unpaid' :
						$unpaid_status_message = $subscription->is_installment_plan()
							? sprintf( __( 'Installment Subscription Plan #%1$s is on hold as Stripe has reported that the installment subscription plan is unpaid. Stripe Installment Subscription Plan ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id )
							: sprintf( __( 'Subscription #%1$s is on hold as Stripe has reported that the subscription is unpaid. Stripe Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id );

						$subscription->update_status( 'on-hold', $unpaid_status_message );
						break;
				}

				return;
			}
		}

		// Create Payment.
		$payment = $subscription->create_payment();
		$payment->set_props( array(
			'subscription_id' => $subscription->get_id(),
			'transaction_id'  => $transaction_id,
		) );

		if ( ! $test || $allow_stripe_api ) {
			// Get Subscription Payment.
			$stripe_charge = $this->gateway->get_api()->get_charge( $transaction_id );

			// Check for the payment and make sure its paid, if not handle it in the webhook.
			if ( empty( $stripe_charge->error ) && false !== $stripe_charge && $stripe_charge instanceof Charge && $stripe_charge->paid ) {
				// Store Fees.
				if ( isset( $stripe_charge->balance_transaction ) && isset( $stripe_charge->balance_transaction->fee ) ) {
					$amount = ! empty( $stripe_charge->balance_transaction->amount ) ? $this->gateway->format_balance_fee( $stripe_charge->balance_transaction, 'amount' ) : 0.00;
					$payment->update_meta( '_stripe_amount', $amount );

					$fee = ! empty( $stripe_charge->balance_transaction->fee ) ? $this->gateway->format_balance_fee( $stripe_charge->balance_transaction, 'fee' ) : 0.00;
					$payment->update_meta( '_stripe_fee', $fee );

					$net = ! empty( $stripe_charge->balance_transaction->net ) ? $this->gateway->format_balance_fee( $stripe_charge->balance_transaction, 'net' ) : 0.00;
					$payment->update_meta( '_stripe_net', $net );

					$currency = ! empty( $stripe_charge->balance_transaction->currency ) ? strtoupper( $stripe_charge->balance_transaction->currency ) : null;
					$payment->update_meta( '_stripe_currency', $currency );

					// Customer Id.
					if ( ! empty( $stripe_charge->customer ) ) {
						$payment->update_meta( '_stripe_customer_id', $stripe_charge->customer );
					}

					// Source Id.
					if ( ! empty( $stripe_charge->source->id ) ) {
						$payment->update_meta( '_stripe_source_id', $stripe_charge->source->id );
					}

					// Add Note.
					$payment->add_order_note( sprintf( __( 'Recorded Stripe Fees. Total Amount: %1$s, Fee: %2$s, Net Amount: %3$s, Currency: %4$s', 'wp-courseware' ), wpcw_price( $amount ), wpcw_price( $fee ), wpcw_price( $net ), $currency ) );
				}
			}

			// Set Properties.
			$subscription->set_prop( 'transaction_id', $transaction_id );
			$subscription->set_prop( 'expiration', date( 'Y-m-d H:i:s', $stripe_subscription->current_period_end ) );
		}

		// Increment Bill times if paid.
		if ( $paid ) {
			$bill_times = $bill_times + 1;
			$subscription->set_prop( 'bill_times', $bill_times );

			// Add Meta to Order.
			if ( $subscription->is_installment_plan() ) {
				$payment->update_meta( '_installment_payment', true );
				$payment->update_meta( '_installment_payment_number', $bill_times );
			}
		}

		if ( ! $test || $allow_stripe_api ) {
			if ( in_array( $stripe_subscription->status, array( 'active', 'trialing' ) ) ) {
				$subscription_active_status_message = $subscription->is_installment_plan()
					? sprintf( __( 'Installment for Subscription #%1$s paid. Stripe Installment Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id )
					: sprintf( __( 'Subscription #%1$s renewed. Stripe Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id );

				$subscription->set_status( 'active', $subscription_active_status_message );
			} else {
				$subscription_on_hold_status_message = $subscription->is_installment_plan()
					? sprintf( __( 'Installment for Subscription #%1$s on hold. Stripe Installment Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id )
					: sprintf( __( 'Subscription #%1$s on hold. Stripe Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id );

				$subscription->set_status( 'on-hold', $subscription_on_hold_status_message );
			}
		} else {
			$subscription_active_status_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment for Subscription #%1$s paid. Stripe Installment Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id )
				: sprintf( __( 'Subscription #%1$s renewed. Stripe Subscription ID: %2$s', 'wp-courseware' ), $subscription->get_id(), $subscription_id );

			$subscription->set_status( 'active', $subscription_active_status_message );
		}

		// Save Subscription.
		if ( $subscription->save() ) {
			$subscription_save_log_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Subscription #%1$s saved successfully. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) )
				: sprintf( __( 'Subscription #%1$s saved successfully. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) );

			$this->log( $subscription_save_log_message );
		} else {
			$subscription_save_error_log_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Subscription #%1$s save error. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) )
				: sprintf( __( 'Subscription #%1$s save error. Subscription Data: %2$s', 'wp-courseware' ), $subscription->get_id(), wpcw_print_r( $subscription->get_data(), true ) );

			$this->log( $subscription_save_error_log_message );
		}

		// Set Status.
		if ( $subscription->has_status( 'active' ) ) {
			$subscription_payment_complete_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Payment Order #%s is complete. Stripe Txn Id: %s', 'wp-courseware' ), $payment->get_order_number(), $transaction_id )
				: sprintf( __( 'Payment Order #%s is complete. Stripe Txn Id: %s', 'wp-courseware' ), $payment->get_order_number(), $transaction_id );

			$payment->payment_complete( $transaction_id, $subscription_payment_complete_message );
		} else {
			$subscription_payment_on_hold_message = $subscription->is_installment_plan()
				? sprintf( __( 'Installment Payment Order #%s is pending payment. Stripe Txn Id: %s', 'wp-courseware' ), $payment->get_order_number(), $transaction_id )
				: sprintf( __( 'Payment Order #%s is on-hold awaiting confirmation of funds. Stripe Txn Id: %s', 'wp-courseware' ), $payment->get_order_number(), $transaction_id );

			$payment->update_status( 'on-hold', $subscription_payment_on_hold_message );
		}

		// Lastly - Check for Insallment Plan logic.
		if ( $paid && $subscription->is_installment_plan() ) {
			// Get installments number.
			$instllments_number = $subscription->get_course()->get_installments_number();

			// Check to see if bill times equals the number of installments needed.
			if ( absint( $bill_times ) === absint( $instllments_number ) ) {
				$this->log( sprintf( 'Subscription #%1$s has processed all %2$s installments. Completing Subscription...', $subscription->get_id(), $bill_times ) );

				$this->gateway->process_subscription_completion( $subscription );
			}
		}
	}

	/**
	 * Stripe Webhook: Customer Subscription Deleted
	 *
	 * Occurs whenever a subscription is deleted.
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 * @param bool $test Is webhook a test webhook? Default is false.
	 */
	protected function webhook_customer_subscription_deleted( $object, $webhook, $test = false ) {
		$subscription = wpcw()->subscriptions->get_subscription_by_profile_id( $object->id );

		if ( ! $subscription ) {
			$this->log( sprintf( 'Could not find subscription via subscription id: %s', $object->id ) );

			return;
		}

		if ( $subscription->is_installment_plan() ) {
			// Get Bill Times.
			$bill_times = $subscription->get_bill_times();

			// Get installments number.
			$instllments_number = $subscription->get_course()->get_installments_number();

			// Check to see if bill times equals the number of installments needed. If so, its already been completed.
			if ( absint( $bill_times ) === absint( $instllments_number ) ) {
				return;
			}
		}

		// Cancel the subscription.
		$subscription->cancel();
	}

	/**
	 * Stripe Webhook: Customer Subscription Updated
	 *
	 * Occurs whenever a subscription is updated.
	 *
	 * @since 4.3.0
	 *
	 * @param object $object The stripe webhook data object.
	 * @param Event $webhook The stripe webhook event object.
	 */
	protected function webhook_customer_subscription_updated( $object, $webhook, $test = false ) {
		$subscription = wpcw()->subscriptions->get_subscription_by_profile_id( $object->id );

		if ( ! $subscription ) {
			$this->log( sprintf( 'Could not find subscription via subscription id: %s', $object->id ) );

			return;
		}

		$old_amount = $subscription->get_recurring_amount();
		$new_amount = $this->gateway->format_stripe_amount( $object->plan->amount );

		if ( $old_amount !== $new_amount ) {
			$subscription->set_prop( 'recurring_amount', $new_amount );
			$subscription->update_status( sprintf( __( 'Subscription recurring amount changed from %1$s to %2$s in Stripe', 'wp-courseware' ), wpcw_price( $old_amount ), wpcw_price( $new_amount ) ) );
		}
	}
}
