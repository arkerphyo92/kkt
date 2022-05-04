<?php
/**
 * WP Courseware Order Model.
 *
 * @since 4.3.0
 * @subpackage Models
 * @package WPCW
 */

namespace WPCW\Models;

use WPCW\Database\DB_Order_Meta;
use WPCW\Database\DB_Orders;
use Exception;
use WPCW\Gateways\Gateway;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Order.
 *
 * @since 4.3.0
 *
 * @property int $order_id
 * @property string $order_key
 * @property string $order_type
 * @property int $student_id
 * @property Student $student
 * @property stirng $student_email
 * @property stirng $student_first_name
 * @property stirng $student_last_name
 * @property stirng $billing_address_1
 * @property stirng $billing_address_2
 * @property stirng $billing_city
 * @property stirng $billing_state
 * @property stirng $billing_postcode
 * @property stirng $billing_country
 * @property string $order_status
 * @property int $order_parent_id
 * @property Order $order_parent
 * @property int $subscription_id
 * @property Subscription $subscription
 * @property string $payment_method
 * @property string $payment_method_title
 * @property string $discounts
 * @property string $subtotal
 * @property string $tax
 * @property string $total
 * @property string $currency
 * @property string $transaction_id
 * @property string $student_ip_address
 * @property string $student_user_agent
 * @property string $created_via
 * @property string $date_created
 * @property string $date_completed
 * @property string $date_paid
 * @property string $cart_hash
 */
class Order extends Model {

	/**
	 * @var DB_Orders The orders database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var DB_Order_Meta The order meta database.
	 * @since 4.3.0
	 */
	protected $meta_db;

	/**
	 * @var int The Order Id.
	 * @since 4.3.0
	 */
	public $order_id;

	/**
	 * @var string The Order Key.
	 * @since 4.3.0
	 */
	public $order_key;

	/**
	 * @var string The Order Type.
	 * @since 4.3.0
	 */
	public $order_type;

	/**
	 * @var int The Student Id.
	 * @since 4.3.0
	 */
	public $student_id;

	/**
	 * @var Student The student object.
	 * @since 4.3.0
	 */
	public $student;

	/**
	 * @var string The Student Email.
	 * @since 4.3.0
	 */
	public $student_email;

	/**
	 * @var string The Student First Name.
	 * @since 4.3.0
	 */
	public $student_first_name;

	/**
	 * @var string The Student Last Name.
	 * @since 4.3.0
	 */
	public $student_last_name;

	/**
	 * @var string The Billing Address 1.
	 * @since 4.3.0
	 */
	public $billing_address_1;

	/**
	 * @var string The Billing Address 2.
	 * @since 4.3.0
	 */
	public $billing_address_2;

	/**
	 * @var string The Billing City.
	 * @since 4.3.0
	 */
	public $billing_city;

	/**
	 * @var string The Billing State.
	 * @since 4.3.0
	 */
	public $billing_state;

	/**
	 * @var string The Billing Postcode.
	 * @since 4.3.0
	 */
	public $billing_postcode;

	/**
	 * @var string The Billing Country.
	 * @since 4.3.0
	 */
	public $billing_country;

	/**
	 * @var string The Order Status.
	 * @since 4.3.0
	 */
	public $order_status;

	/**
	 * @var int The Order Parent Id.
	 * @since 4.3.0
	 */
	public $order_parent_id;

	/**
	 * @var Order The parent order object.
	 * @since 4.3.0
	 */
	public $order_parent;

	/**
	 * @var int The Order Subscription Id.
	 * @since 4.3.0
	 */
	public $subscription_id;

	/**
	 * @var Subscription The Order Subscription Object.
	 * @since 4.3.0
	 */
	public $subscription;

	/**
	 * @var string The Payment Method.
	 * @since 4.3.0
	 */
	public $payment_method;

	/**
	 * @var string The Payment Method Title.
	 * @since 4.3.0
	 */
	public $payment_method_title;

	/**
	 * @var string The Order Discounts.
	 * @since 4.3.0
	 */
	public $discounts;

	/**
	 * @var string The Order Subtotal.
	 * @since 4.3.0
	 */
	public $subtotal;

	/**
	 * @var string The Order Tax.
	 * @since 4.3.0
	 */
	public $tax;

	/**
	 * @var string The Order Total.
	 * @since 4.3.0
	 */
	public $total;

	/**
	 * @var string The Order Currency.
	 * @since 4.3.0
	 */
	public $currency;

	/**
	 * @var string The Order Transaction Id.
	 * @since 4.3.0
	 */
	public $transaction_id;

	/**
	 * @var string The Order Student IP Address.
	 * @since 4.3.0
	 */
	public $student_ip_address;

	/**
	 * @var string The Order Student User Agent.
	 * @since 4.3.0
	 */
	public $student_user_agent;

	/**
	 * @var string Created via 'Admin' or 'System'
	 * @since 4.3.0
	 */
	public $created_via;

	/**
	 * @var string Order Date Created
	 * @since 4.3.0
	 */
	public $date_created;

	/**
	 * @var string Order Date Completed
	 * @since 4.3.0
	 */
	public $date_completed;

	/**
	 * @var string Order Date Paid.
	 * @since 4.3.0
	 */
	public $date_paid;

	/**
	 * @var string Order Cart Hash.
	 * @since 4.3.0
	 */
	public $cart_hash;

	/**
	 * @var array Order Items.
	 * @since 4.3.0
	 */
	public $order_items = array();

	/**
	 * @var bool|array Status transition.
	 * @since 4.3.0
	 */
	protected $status_transition = false;

	/**
	 * @var array Related Orders.
	 * @since 4.3.0
	 */
	public $related_orders = array();

	/**
	 * @var array Subscriptions.
	 * @since 4.3.0
	 */
	public $subscriptions = array();

	/**
	 * Order Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db      = new DB_Orders();
		$this->meta_db = new DB_Order_Meta();
		parent::__construct( $data );
	}

	/**
	 * Order Setup.
	 *
	 * @since 4.3.0
	 *
	 * @param int $data The order data Id.
	 */
	public function setup( $data ) {
		if ( 0 === absint( $data ) ) {
			return;
		}

		$data_object = $this->db->get( $data );

		if ( ! $data_object ) {
			return;
		}

		if ( $data_object && is_object( $data_object ) ) {
			$this->set_data( $data_object );
		}

		if ( $this->get_order_id() ) {
			$this->set_order_items();
		}
	}

	/**
	 * Refresh Order Object.
	 *
	 * @since 4.3.0
	 */
	public function refresh() {
		if ( ! $this->get_order_id() ) {
			return;
		}

		$this->setup( $this->get_order_id() );
	}

	/**
	 * Get Order Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The order id.
	 */
	public function get_id() {
		return absint( $this->get_order_id() );
	}

	/**
	 * Get Order Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Get Order Number for Display.
	 *
	 * @since 4.3.0
	 *
	 * @return string $order_number The order number for display. Default is Order Id.
	 */
	public function get_order_number() {
		/**
		 * Filter: Order Number for Display.
		 *
		 * @since 4.3.0
		 *
		 * @param int The order id.
		 * @param Order The order object.
		 *
		 * @return string $order_id The order id.
		 */
		return (string) apply_filters( 'wpcw_order_number', $this->get_order_id(), $this );
	}

	/**
	 * Get Order Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string The order title.
	 */
	public function get_order_title() {
		return sprintf( __( 'Order #%s', 'wp-courseware' ), $this->get_order_id() );
	}

	/**
	 * Create Order Key.
	 *
	 * @since 4.3.0
	 *
	 * @return string The order key string.
	 */
	public function create_order_key() {
		$order_key = apply_filters( 'wpcw_order_create_key', uniqid( 'order_' ) );

		return sprintf( 'wpcw_%s', $order_key );
	}

	/**
	 * Get Order Key.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_order_key() {
		return str_replace( 'wpcw_order_', '', $this->order_key );
	}

	/**
	 * Get Order Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_order_type() {
		return $this->order_type;
	}

	/**
	 * Get Student Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int
	 */
	public function get_student_id() {
		return absint( $this->student_id );
	}

	/**
	 * Get Student.
	 *
	 * @since 4.3.0
	 *
	 * @return Student|bool Student object if exists, false otherwise.
	 */
	public function get_student() {
		if ( ! $this->get_student_id() ) {
			return false;
		}

		if ( empty( $this->student ) ) {
			$this->student = new Student( $this->get_student_id() );
		}

		return $this->student;
	}

	/**
	 * Get Student Email.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_student_email() {
		if ( empty( $this->student_email ) ) {
			if ( $student = $this->get_student() ) {
				$this->student_email = $student->get_email();
			}
		}

		return esc_attr( $this->student_email );
	}

	/**
	 * Get Student First Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_student_first_name() {
		if ( empty( $this->student_first_name ) ) {
			if ( $student = $this->get_student() ) {
				$this->student_first_name = $student->get_first_name();
			}
		}

		return esc_attr( $this->student_first_name );
	}

	/**
	 * Get Student Last Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_student_last_name() {
		if ( empty( $this->student_last_name ) ) {
			if ( $student = $this->get_student() ) {
				$this->student_last_name = $student->get_last_name();
			}
		}

		return esc_attr( $this->student_last_name );
	}

	/**
	 * Get Student Full Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string The student full name.
	 */
	public function get_student_full_name() {
		$first_name = $this->get_student_first_name();
		$last_name  = $this->get_student_last_name();

		return ( $first_name && $last_name ) ? sprintf( '%s %s', $first_name, $last_name ) : '';
	}

	/**
	 * Get Billing Address 1.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_billing_address_1() {
		return esc_attr( $this->billing_address_1 );
	}

	/**
	 * Get Billing Address 2.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_billing_address_2() {
		return esc_attr( $this->billing_address_2 );
	}

	/**
	 * Get Billing City.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_billing_city() {
		return esc_attr( $this->billing_city );
	}

	/**
	 * Get Billing State.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_billing_state() {
		return esc_attr( $this->billing_state );
	}

	/**
	 * Get Billing Postcode.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_billing_postcode() {
		return esc_attr( $this->billing_postcode );
	}

	/**
	 * Get Billing Country.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_billing_country() {
		return esc_attr( $this->billing_country );
	}

	/**
	 * Get Order Status.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_order_status() {
		return esc_attr( $this->order_status );
	}

	/**
	 * Get Order Status - Backwards compat.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->get_order_status();
	}

	/**
	 * Checks the order status against a passed in status.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status The order status.
	 *
	 * @return mixed|void
	 */
	public function has_order_status( $status ) {
		$has_status = ( ( is_array( $status ) && in_array( $this->get_order_status(), $status ) ) || $this->get_order_status() === $status ) ? true : false;

		return apply_filters( 'wpcw_order_has_status', $has_status, $this, $status );
	}

	/**
	 * Backwards Compatible: Check Order Status.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status the order status.
	 *
	 * @reutrn mixed|void
	 */
	public function has_status( $status ) {
		return $this->has_order_status( $status );
	}

	/**
	 * Set Order Status.
	 *
	 * @since 4.3.0
	 *
	 * @param string $new_status The new order status.
	 * @param string $note Optional. Note to add.
	 * @param bool   $manual_update Is this a manual order status change.
	 */
	public function set_order_status( $new_status, $note = '', $manual_update = false ) {
		$old_status = $this->get_order_status();

		// Only allow valid new status
		if ( ! array_key_exists( $new_status, wpcw()->orders->get_order_statuses() ) && 'trash' !== $new_status ) {
			$new_status = 'pending';
		}

		// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
		if ( $old_status && ! array_key_exists( $old_status, wpcw()->orders->get_order_statuses() ) && 'trash' !== $old_status ) {
			$old_status = 'pending';
		}

		// Set Status Property.
		$this->set_prop( 'order_status', $new_status );

		// Set Status Transition.
		$this->status_transition = array(
			'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $old_status,
			'to'     => $new_status,
			'note'   => $note,
			'manual' => (bool) $manual_update,
		);
	}

	/**
	 * Get Order Parent Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int
	 */
	public function get_order_parent_id() {
		return absint( $this->order_parent_id );
	}

	/**
	 * Get Order Parent.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|Order The order parent object.
	 */
	public function get_order_parent() {
		if ( ! $this->get_order_parent_id() ) {
			return false;
		}

		if ( empty( $this->order_parent ) ) {
			$this->order_parent = new Order( $this->get_order_parent_id() );
		}

		return $this->order_parent;
	}

	/**
	 * Get Subscription Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The subscription id.
	 */
	public function get_subscription_id() {
		return absint( $this->subscription_id );
	}

	/**
	 * Get Subscription Object.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|Subscription
	 */
	public function get_subscription() {
		if ( ! $this->get_subscription_id() ) {
			return false;
		}

		if ( empty( $this->subscription ) ) {
			$this->subscription = new Subscription( $this->get_subscription_id() );
		}

		return $this->subscription;
	}

	/**
	 * Get Payment Method.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return esc_attr( $this->payment_method );
	}

	/**
	 * Get Payment Method Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_payment_method_title() {
		return $this->payment_method_title;
	}

	/**
	 * Get Discounts.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_discounts( $format = false ) {
		return ( $format ) ? sprintf( '-%s', wpcw_price( $this->discounts ) ) : $this->discounts;
	}

	/**
	 * Get Subtotal.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format If to format the subtotal.
	 *
	 * @return string
	 */
	public function get_subtotal( $format = false ) {
		$subtotal = $this->subtotal;

		if ( empty( $subtotal ) || 0 === absint( $subtotal ) ) {
			$subtotal = $this->get_total();
		}

		return ( $format ) ? wpcw_price( $subtotal ) : $subtotal;
	}

	/**
	 * Get Subtotal Refunded.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format If to format the subtotal.
	 *
	 * @return string
	 */
	public function get_subtotal_refunded( $format = false ) {
		$subtotal = $this->subtotal;

		if ( empty( $subtotal ) || 0 === absint( $subtotal ) ) {
			$subtotal = $this->get_total();
		}

		$subtotal = $subtotal * - 1;

		return ( $format ) ? wpcw_price( $subtotal ) : $subtotal;
	}

	/**
	 * Get Tax.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format Whether to format the amount.
	 *
	 * @return string
	 */
	public function get_tax( $format = false ) {
		return ( $format ) ? wpcw_price( $this->tax ) : $this->tax;
	}

	/**
	 * Get Tax Refunded.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format Whether to format the amount.
	 *
	 * @return string
	 */
	public function get_tax_refunded( $format = false ) {
		$tax = $this->tax * - 1;

		return ( $format ) ? wpcw_price( $tax ) : $tax;
	}

	/**
	 * Get Total.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format If to format the total.
	 *
	 * @return string
	 */
	public function get_total( $format = false ) {
		return ( $format ) ? wpcw_price( $this->total ) : $this->total;
	}

	/**
	 * Get Total Refunded.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format If to format the total.
	 *
	 * @return string
	 */
	public function get_total_refunded( $format = false ) {
		$total = $this->total * - 1;

		return ( $format ) ? wpcw_price( $total ) : $total;
	}

	/**
	 * Get Currency.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_currency() {
		return $this->currency;
	}

	/**
	 * Get Transaction Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_transaction_id() {
		return $this->transaction_id;
	}

	/**
	 * Get Student IP Address.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_student_ip_address() {
		return $this->student_ip_address;
	}

	/**
	 * Get Student User Agent.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_student_user_agent() {
		return $this->student_user_agent;
	}

	/**
	 * Get Created Via.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_created_via() {
		return $this->created_via;
	}

	/**
	 * Get Date Created.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format Format the Date into readable form.
	 *
	 * @return string The date raw or formatted.
	 */
	public function get_date_created( $format = false ) {
		return $format ? date_i18n( 'F j, Y', strtotime( $this->date_created ) ) : $this->date_created;
	}

	/**
	 * Get Date Completed.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format Format the Date into readable form.
	 *
	 * @return string The date raw or formatted.
	 */
	public function get_date_completed( $format = false ) {
		return $format ? date_i18n( 'F j, Y, g:i a', strtotime( $this->date_completed ) ) : $this->date_completed;
	}

	/**
	 * Get Date Paid.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format Format the Date into readable form.
	 *
	 * @return string The date raw or formatted.
	 */
	public function get_date_paid( $format = false ) {
		return $format ? date_i18n( 'F j, Y, g:i a', strtotime( $this->date_paid ) ) : $this->date_paid;
	}

	/**
	 * Get Cart Hash.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_cart_hash() {
		return ! empty( $this->cart_hash ) ? $this->cart_hash : '';
	}

	/**
	 * Has Cart Hash?
	 *
	 * See if the order matches the hash.
	 *
	 * @param string $cart_hash The cart hash.
	 *
	 * @return bool True if it matches, false otherwise.
	 */
	public function has_cart_hash( $cart_hash = '' ) {
		return hash_equals( $this->get_cart_hash(), $cart_hash );
	}

	/**
	 * Set Order Items.
	 *
	 * @since 4.3.0
	 *
	 * @param array $order_items Optional. The array of order item objects.
	 *
	 * @return array $order_items The array of Order_Item objects.
	 */
	public function set_order_items( $order_items = array() ) {
		if ( ! empty( $order_items ) ) {
			$this->order_items = $order_items;
		} else {
			$this->order_items = array();
			$order_items_data  = $this->db->get_order_items( $this->get_order_id() );

			if ( empty( $order_items_data ) ) {
				return $this->order_items;
			}

			foreach ( $order_items_data as $order_item_data ) {
				$this->order_items[] = new Order_Item( $order_item_data );
			}
		}

		return $this->order_items;
	}

	/**
	 * Get Order Items.
	 *
	 * @since 4.3.0
	 *
	 * @return array Array of Order Item Objects.
	 */
	public function get_order_items() {
		if ( empty( $this->order_items ) ) {
			$this->set_order_items();
		}

		return $this->order_items;
	}

	/**
	 * Order has recurring items?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if contains recurring items, False otherwise.
	 */
	public function has_recurring_items() {
		$order_items = $this->get_order_items();

		/** @var Order_Item $order_item */
		foreach ( $order_items as $order_item ) {
			if ( $order_item->get_is_recurring() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get Order Recurring Items
	 *
	 * @since 4.3.0
	 *
	 * @return array $recurring_items The recurring items.
	 */
	public function get_recurring_items() {
		$order_items     = $this->get_order_items();
		$recurring_items = array();

		/** @var Order_Item $order_item */
		foreach ( $order_items as $order_item ) {
			if ( $order_item->get_is_recurring() ) {
				$recurring_items[ $order_item->get_order_item_id() ] = $order_item;
			}
		}

		return $recurring_items;
	}

	/**
	 * Get Order One Time Items.
	 *
	 * @since 4.3.0
	 *
	 * @return array $one_time_items The one time items.
	 */
	public function get_one_time_items() {
		$order_items    = $this->get_order_items();
		$one_time_items = array();

		/** @var Order_Item $order_item */
		foreach ( $order_items as $order_item ) {
			if ( ! $order_item->get_is_recurring() ) {
				$one_time_items[] = $order_item;
			}
		}

		return $one_time_items;
	}

	/**
	 * Get Order Items Data.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $json Format as JSON?
	 *
	 * @return array|string
	 */
	public function get_order_items_data( $json = false ) {
		$order_items = array();

		if ( $this->get_order_items() ) {
			foreach ( $this->get_order_items() as $order_item ) {
				if ( $order_item instanceof Order_Item ) {
					$order_items[] = array(
						'id'           => $order_item->get_order_item_id(),
						'order_id'     => $order_item->get_order_id(),
						'course_id'    => $order_item->get_course_id(),
						'course_url'   => $order_item->get_course_url(),
						'title'        => $order_item->get_order_item_title(),
						'index'        => $order_item->get_order_item_index(),
						'qty'          => $order_item->get_qty(),
						'amount'       => wpcw_price( $order_item->get_amount() ),
						'amount_tax'   => wpcw_price( $order_item->get_amount_tax() ),
						'discount'     => wpcw_price( $order_item->get_discount() ),
						'discount_tax' => wpcw_price( $order_item->get_discount_tax() ),
						'subtotal'     => wpcw_price( $order_item->get_subtotal() ),
						'subtotal_tax' => wpcw_price( $order_item->get_subtotal_tax() ),
						'tax'          => wpcw_price( $order_item->get_tax() ),
						'total'        => wpcw_price( $order_item->get_total() ),
					);
				}
			}
		}

		return ( $json ) ? wpcw_convert_array_to_json( $order_items ) : $order_items;
	}

	/**
	 * Insert Order Items.
	 *
	 * @since 4.3.0
	 *
	 * @param array $items The Order Items Data.
	 * @param bool  $include_discounts Include order item discounts.
	 */
	public function insert_order_items( $items = array(), $include_discounts = true ) {
		if ( empty( $items ) ) {
			return false;
		}

		$order_items = array();

		foreach ( $items as $item_index => $item ) {
			if ( $item instanceof Order_Item ) {
				$order_item_data = $item->get_data( true );

				if ( isset( $order_item_data['order_id'] ) ) {
					$order_item_data['order_id'] = absint( $this->get_order_id() );
				}

				if ( isset( $order_item_data['order_item_index'] ) ) {
					$order_item_data['order_item_index'] = absint( $item_index );
				}

				$order_item_data['discount']     = $include_discounts ? wpcw_round( $item->get_discount() ) : 0.00;
				$order_item_data['discount_tax'] = $include_discounts ? wpcw_round( $item->get_discount_tax() ) : 0.00;

				$order_item_data['subtotal']     = wpcw_round( $item->get_subtotal() );
				$order_item_data['subtotal_tax'] = wpcw_round( $item->get_subtotal_tax() );

				unset( $order_item_data['order_item_id'] );
			} else {
				$order_item_amount = isset( $item['amount'] ) ? esc_attr( wpcw_round( $item['amount'] ) ) : 0;

				$order_item_data = array(
					'order_id'         => absint( $this->get_order_id() ),
					'course_id'        => ! empty( $item['id'] ) ? absint( $item['id'] ) : 0,
					'order_item_title' => ! empty( $item['title'] ) ? esc_attr( $item['title'] ) : '',
					'order_item_index' => absint( $item_index ),
					'qty'              => isset( $item['qty'] ) ? absint( $item['qty'] ) : 1,
					'amount'           => $order_item_amount,
					'discount'         => isset( $item['discount'] ) && $include_discounts ? esc_attr( wpcw_round( $item['discount'] ) ) : 0.00,
					'discount_tax'     => isset( $item['discount_tax'] ) && $include_discounts ? esc_attr( wpcw_round( $item['discount_tax'] ) ) : 0.00,
					'subtotal'         => isset( $item['subtotal'] ) ? esc_attr( wpcw_round( $item['subtotal'] ) ) : $order_item_amount,
					'subtotal_tax'     => isset( $item['subtotal_tax'] ) ? esc_attr( wpcw_round( $item['subtotal_tax'] ) ) : wpcw_calculate_tax_amount( $order_item_amount ),
					'use_installments' => isset( $item['use_installments'] ) ? $item['use_installments'] : false,
					'is_recurring'     => isset( $item['is_recurring'] ) ? $item['is_recurring'] : false,
				);
			}

			$order_item_data['tax']   = $include_discounts ? esc_attr( wpcw_round( $order_item_data['subtotal_tax'] - $order_item_data['discount_tax'] ) ) : esc_attr( wpcw_round( $order_item_data['subtotal_tax'] ) );
			$order_item_data['total'] = $include_discounts ? esc_attr( wpcw_round( $order_item_data['subtotal'] - $order_item_data['discount'] ) ) : esc_attr( wpcw_round( $order_item_data['subtotal'] ) );

			if ( $order_item_data['total'] > 0 ) {
				$order_item_data['total'] += esc_attr( wpcw_round( $order_item_data['tax'] ) );
			}

			$order_item    = new Order_Item();
			$order_item_id = $order_item->create( $order_item_data );

			if ( $order_item_id ) {
				$order_items[] = $order_item;
			}
		}

		$this->set_order_items();
	}

	/**
	 * Delete Order Items.
	 *
	 * @since 4.3.0
	 *
	 * @param array $items The Order Items Data.
	 */
	public function delete_order_items( $items = array() ) {
		if ( empty( $items ) ) {
			return false;
		}

		foreach ( $items as $order_item ) {
			if ( $order_item instanceof Order_Item ) {
				$this->db->delete_order_item( $order_item->get_order_item_id() );
			} elseif ( is_numeric( $order_item ) ) {
				$this->db->delete_order_item( $order_item );
			}
		}

		$this->set_order_items();
	}

	/**
	 * Create Order.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The data to create the order with.
	 *
	 * @return int|bool The order_id or false otherwise.
	 */
	public function create( $data = array() ) {
		$defaults = array(
			'status'       => 'create',
			'order_key'    => $this->create_order_key(),
			'created_via'  => 'system',
			'date_created' => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( $order_id = $this->db->insert_order( $data ) ) {
			$this->set_prop( 'order_id', $order_id );
			$this->set_data( $data );
		}

		return $order_id;
	}

	/**
	 * Save Order
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if successfull, False on failure
	 */
	public function save() {
		$data = $this->get_data( true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		$this->db->update_order( $this->get_order_id(), $data );

		$this->status_transition();

		return $this->get_order_id();
	}

	/**
	 * Delete Order.
	 *
	 * @since 4.3.0
	 */
	public function delete() {
		$this->delete_order_items( $this->get_order_items() );
		$this->delete_order_notes();
		$this->delete_all_meta();

		// Delete Subscriptions.
		$this->delete_subscriptions();

		// Delete Related Orders.
		if ( ! $this->get_order_parent_id() ) {
			$this->delete_related_orders();
		}

		return $this->db->delete( $this->get_order_id() );
	}

	/**
	 * Delete Related Orders.
	 *
	 * @since 4.3.0
	 *
	 * @return array An array of deleted orders.
	 */
	public function delete_related_orders() {
		$deleted_orders = array();

		if ( $related_orders = $this->get_related_orders( array(), true ) ) {
			/** @var Order $related_order */
			foreach ( $related_orders as $related_order ) {
				if ( $related_order->get_id() ) {
					$deleted_orders[ $related_order->get_id() ] = $related_order->delete();
				}
			}
		}

		return $deleted_orders;
	}

	/**
	 * Delete Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @return array An array of deleted subscriptions.
	 */
	public function delete_subscriptions() {
		$deleted_subscriptions = array();

		if ( $subscriptions = $this->get_subscriptions( array(), true ) ) {
			/** @var Subscription $subscription */
			foreach ( $subscriptions as $subscription ) {
				if ( $subscription->get_id() ) {
					$deleted_subscriptions[ $subscription->get_id() ] = $subscription->delete();
				}
			}
		}

		return $deleted_subscriptions;
	}

	/**
	 * Get a Order Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool   $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return $this->meta_db->get_meta( $this->get_order_id(), $meta_key, $single );
	}

	/**
	 * Add Order Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed  $meta_value Metadata value.
	 * @param bool   $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return $this->meta_db->add_meta( $this->get_order_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Order Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata key.
	 * @param mixed  $meta_value Metadata value.
	 * @param mixed  $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, true if success.
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return $this->meta_db->update_meta( $this->get_order_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Order Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return $this->meta_db->delete_meta( $this->get_order_id(), $meta_key, $meta_value );
	}

	/**
	 * Delete All Meta Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function delete_all_meta() {
		return $this->meta_db->delete_all_meta( $this->get_order_id() );
	}

	/**
	 * Add Order Note.
	 *
	 * @since 4.3.0
	 *
	 * @param string $content The note content.
	 * @param bool   $is_public Is this note public. Shown to Student?
	 * @param bool   $added_by_user Was this added manually by a user.
	 *
	 * @return bool|int The note id or false if something goes wrong.
	 */
	public function add_order_note( $content, $is_public = false, $added_by_user = false ) {
		if ( ! $this->get_order_id() ) {
			return false;
		}

		$note = new Note();
		$note->create( $this->get_order_id(), 'order' );

		if ( is_user_logged_in() && current_user_can( 'manage_wpcw_settings' ) && $added_by_user ) {
			$note->set_prop( 'user_id', get_current_user_id() );
		}

		if ( $is_public ) {
			$note->set_prop( 'is_public', absint( $is_public ) );
		}

		$note->set_prop( 'content', wp_kses_post( $content ) );

		return $note->save();
	}

	/**
	 * Add Applied Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @param array $applied_coupons The applied coupons array.
	 */
	public function add_applied_coupons( $applied_coupons ) {
		$order_applied_coupons = array();
		if ( ! empty( $applied_coupons ) ) {
			foreach ( $applied_coupons as $applied_coupon ) {
				$applied_coupon = wpcw_get_coupon_by_code( $applied_coupon );
				if ( $applied_coupon->get_id() ) {
					$order_applied_coupons[ $applied_coupon->get_id() ] = $applied_coupon->get_data( true );
				}
			}
		}

		if ( ! empty( $order_applied_coupons ) ) {
			$this->update_meta( 'applied_coupons', $order_applied_coupons );
		}
	}

	/**
	 * Update Applied Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @param array $applied_coupons The array of applied coupons.
	 */
	public function update_applied_coupons( $applied_coupons ) {
		return $this->update_meta( 'applied_coupons', $applied_coupons );
	}

	/**
	 * Get Applied Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @return array $applied_coupons The array of applied coupons.
	 */
	public function get_applied_coupons() {
		$applied_coupons = $this->get_meta( 'applied_coupons', true );

		return ! empty( $applied_coupons ) ? (array) $applied_coupons : '';
	}

	/**
	 * Get Applied Coupons Data.
	 *
	 * @since 4.5.0
	 *
	 * @return string The applied coupons json string.
	 */
	public function get_applied_coupons_data( $json = false ) {
		$applied_coupons      = $this->get_applied_coupons();
		$applied_coupons_data = array();

		if ( ! empty( $applied_coupons ) ) {
			foreach ( $applied_coupons as $applied_coupon ) {
				$applied_coupon_object = new Coupon( $applied_coupon );
				if ( $applied_coupon_object instanceof Coupon ) {
					$amount = $applied_coupon_object->get_amount();
					if ( 0 > $amount ) {
						$amount = 0;
					}
					$amount = number_format( $amount, 2 );
					if ( 'percentage' === $applied_coupon_object->get_type() ) {
						$coupon_amount = $amount . '%';
					} else {
						$coupon_amount = '$' . $amount;
					}
					$applied_coupons_data[] = array(
						'id'            => $applied_coupon_object->get_id(),
						'code'          => $applied_coupon_object->get_code(),
						'amount'        => $applied_coupon_object->get_amount( true ),
						'ramount'       => $applied_coupon_object->get_amount(),
						'edit_url'      => $applied_coupon_object->get_edit_url(),
						'coupon_type'   => $applied_coupon_object->get_type(),
						'coupon_amount' => $coupon_amount,
					);
				}
			}
		}

		return ( $json ) ? wpcw_convert_array_to_json( $applied_coupons_data ) : $applied_coupons_data;
	}

	/**
	 * Add Record Coupon Usage Count.
	 *
	 * @since 4.5.0
	 *
	 * @param bool $set True of False.
	 *
	 * @retunr bool If it set or not.
	 */
	public function set_recorded_coupon_usage_count( $set = true ) {
		return $this->update_meta( '_recorded_coupon_usage_counts', wpcw_bool_to_string( $set ) );
	}

	/**
	 * Get Record Coupon Usage Count.
	 *
	 * @since 4.5.0
	 *
	 * @retunr bool If the usage count has been recorded.
	 */
	public function get_recorded_coupon_usage_count() {
		return wpcw_string_to_bool( $this->get_meta( '_recorded_coupon_usage_counts', true ) );
	}

	/**
	 * Get Order Notes.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of order note objects.
	 */
	public function get_notes( $args = array() ) {
		$notes = array();

		if ( ! $this->get_id() ) {
			return $notes;
		}

		$notes = wpcw()->notes->get_notes(
			array(
				'object_id'   => $this->get_order_id(),
				'object_type' => 'order',
				'order'       => 'ASC',
				'orderby'     => 'id',
			)
		);

		return $notes;
	}

	/**
	 * Delete Order Note.
	 *
	 * @since 4.3.0
	 *
	 * @param int $note_id The order note.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_order_note( $note_id = 0 ) {
		if ( ! $this->get_order_id() ) {
			return false;
		}

		if ( empty( $note_id ) ) {
			return false;
		}

		return wpcw()->notes->delete_notes( array( $note_id ) );
	}

	/**
	 * Delete Order Notes.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_order_notes() {
		if ( ! $this->get_order_id() ) {
			return false;
		}

		return wpcw()->notes->delete_notes_by_object_id( $this->get_order_id() );
	}

	/**
	 * Updates status of order immediately. Order must exist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $new_status Status to change the order to.
	 * @param string $note Optional note to add.
	 * @param bool   $manual Is this a manual order status change?
	 *
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		try {
			if ( ! $this->get_order_id() ) {
				throw new Exception( __( 'Attempting to update the order status but the Order Id is empty.', 'wp-courseware' ) );
			}

			$this->set_order_status( $new_status, $note, $manual );

			$this->save();
		} catch ( Exception $exception ) {
			wpcw_file_log( array( 'message' => sprintf( 'Update status of order #%d failed! Order Data: %s, Error: %s', $this->get_order_id(), wpcw_print_r( $this, true ), $exception->getMessage() ) ) );

			return false;
		}

		return true;
	}

	/**
	 * Handle Status Transition.
	 *
	 * @since 4.3.0
	 */
	protected function status_transition() {
		// Store Status Transition for local use.
		$status_transition = $this->status_transition;

		// Handle Status Transition.
		if ( $status_transition ) {
			// Log the transition.
			$this->log( sprintf( 'Order status transition: %s', wpcw_print_r( $status_transition, true ) ) );
			$this->log( sprintf( 'Order status transition order: %s', wpcw_print_r( $this->get_data(), true ) ) );

			/**
			 * Action: Order Status Transition To
			 *
			 * @since 4.3.0
			 *
			 * @param int $order_id The order id.
			 * @param Order The order object.
			 */
			do_action( 'wpcw_order_status_' . $status_transition['to'], $this->get_order_id(), $this );

			if ( ! empty( $status_transition['from'] ) ) {
				/* translators: 1: old order status 2: new order status */
				$transition_note = sprintf( __( 'Order status changed from "%1$s" to "%2$s".', 'wp-courseware' ), wpcw_get_order_status_name( $status_transition['from'] ), wpcw_get_order_status_name( $status_transition['to'] ) );

				/**
				 * Action: Order Status Transition From - To
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 * @param Order The order object.
				 */
				do_action( 'wpcw_order_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_order_id(), $this );

				/**
				 * Action: Order Status Changed
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 * @param string $status_transition ['from'] The old status or from status.
				 * @param string $status_transition ['to'] The new status or to status.
				 */
				do_action( 'wpcw_order_status_changed', $this->get_order_id(), $status_transition['from'], $status_transition['to'], $this );
			} else {
				/* translators: %s: new order status */
				$transition_note = sprintf( __( 'Order status set to "%s".', 'wp-courseware' ), wpcw_get_order_status_name( $status_transition['to'] ) );
			}

			// Add Note.
			$this->add_order_note( trim( $status_transition['note'] . ' ' . $transition_note ), false, $status_transition['manual'] );
		}

		// Reset status transition variable.
		$this->status_transition = false;
	}

	/**
	 * Update Order Totals.
	 *
	 * $order_item->get_discount() returns non-zero for INITIAL Stripe orders and ALL PayPal orders that have a coupon applied.
	 * PayPal must force $include_discounts to false for installation payments.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $include_discounts Whether or not discounts should be included with the order info
	 */
	public function update_totals( $include_discounts = true ) {
		$subtotal = 0;
		$discount = 0;
		$tax      = 0;
		$total    = 0;

		$order_items = $this->get_order_items();

		if ( ! empty( $order_items ) ) {
			foreach ( $order_items as $order_item ) {
				if ( $order_item instanceof Order_Item ) {
					$item_subtotal = $order_item->get_subtotal();
					$item_discount = ( $include_discounts ) ? $order_item->get_discount() : 0;
					$item_tax      = $order_item->get_tax();
					$item_total    = ( $include_discounts ) ? $order_item->get_total() : ( $order_item->get_subtotal() + $item_tax );

					$subtotal += $item_subtotal;
					$discount += $item_discount;
					$tax      += $item_tax;
					$total    += $item_total;
				}
			}
		}

		$this->set_prop( 'subtotal', $subtotal );
		$this->set_prop( 'discounts', $discount );
		$this->set_prop( 'tax', $tax );
		$this->set_prop( 'total', $total );
	}

	/**
	 * Get Order Recieved Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_order_received_url() {
		$order_received_url = wpcw_get_page_permalink( 'order-received' );

		if ( 'yes' === wpcw_get_setting( 'force_ssl' ) || is_ssl() ) {
			$order_received_url = set_url_scheme( $order_received_url, 'https' );
		}

		$order_key          = $this->get_order_key();
		$order_id           = $this->get_order_id();
		$order_received_url = add_query_arg(
			array(
				'key'      => $order_key,
				'order_id' => $order_id,
			),
			$order_received_url
		);

		return apply_filters( 'wpcw_order_get_order_received_url', $order_received_url, $this );
	}

	/**
	 * Get Order Failed Url.
	 *
	 * @since 4.6.3
	 *
	 * @return string
	 */
	public function get_order_failed_url() {
		$order_failed_url = wpcw_get_page_permalink( 'order-failed' );

		if ( 'yes' === wpcw_get_setting( 'force_ssl' ) || is_ssl() ) {
			$order_failed_url = set_url_scheme( $order_failed_url, 'https' );
		}

		$order_key        = $this->get_order_key();
		$order_id         = $this->get_order_id();
		$order_failed_url = add_query_arg(
			array(
				'key'      => $order_key,
				'order_id' => $order_id,
			),
			$order_failed_url
		);

		return apply_filters( 'wpcw_order_get_order_failed_url', $order_failed_url, $this );
	}

	/**
	 * Get View Order Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The view order url.
	 */
	public function get_view_order_url() {
		$view_order_url = sprintf( '%s/%s/', untrailingslashit( wpcw_get_student_account_endpoint_url( 'view-order' ) ), $this->get_order_id() );

		return apply_filters( 'wpcw_order_get_view_order_url', $view_order_url, $this );
	}

	/**
	 * Get Cancel Order Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $redirect Redirect URL.
	 *
	 * @return string The cancel url.
	 */
	public function get_order_cancel_url( $redirect = '' ) {
		$cancel_order_url_params = array(
			'cancel_order' => 'true',
			'order'        => $this->get_order_key(),
			'order_id'     => $this->get_order_id(),
			'redirect'     => $redirect,
		);

		if ( $redirect ) {
			$cancel_order_url_params['redirect'] = $redirect;
		}

		return apply_filters( 'wpwc_order_get_order_cancel_url', wp_nonce_url( add_query_arg( $cancel_order_url_params, $this->get_cancel_order_endpoint() ), 'wpcw-cancel_order' ) );
	}

	/**
	 * Get Cancel Order Url ( Unescaped ).
	 *
	 * @since 4.3.0
	 *
	 * @param string $redirect Redirect URL.
	 *
	 * @return string The unescaped cancel URL.
	 */
	public function get_cancel_order_url_raw( $redirect = '' ) {
		$cancel_order_url_params = array(
			'cancel_order' => 'true',
			'order'        => $this->get_order_key(),
			'order_id'     => $this->get_order_id(),
			'_wpnonce'     => wp_create_nonce( 'wpcw-cancel_order' ),
		);

		if ( ! empty( $redirect ) ) {
			$cancel_order_url_params['redirect'] = remove_query_arg( '_wpnonce', $redirect );
		}

		return apply_filters( 'wpwc_order_get_order_cancel_url_raw', add_query_arg( $cancel_order_url_params, $this->get_cancel_order_endpoint() ) );
	}

	/**
	 * Get Cancel Order Endpoint.
	 *
	 * @since 4.3.0
	 *
	 * @return string The cancel endpoint url.
	 */
	public function get_cancel_order_endpoint() {
		$cancel_endpoint = wpcw_get_page_permalink( 'checkout' );

		if ( ! $cancel_endpoint ) {
			$cancel_endpoint = home_url();
		}

		if ( false === strpos( $cancel_endpoint, '?' ) ) {
			$cancel_endpoint = trailingslashit( $cancel_endpoint );
		}

		return $cancel_endpoint;
	}

	/**
	 * Get Edit Order Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The edit order url
	 */
	public function get_order_edit_url() {
		return esc_url_raw(
			apply_filters(
				'wpcw_order_get_edit_url',
				add_query_arg(
					array(
						'page'     => 'wpcw-order',
						'order_id' => $this->get_order_id(),
					),
					admin_url( 'admin.php' )
				),
				$this
			)
		);
	}

	/**
	 * Order Payment Complete.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $note Note for when the payment completes.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function payment_complete( $transaction_id = '', $note = '' ) {
		try {
			if ( ! $this->get_order_id() ) {
				return false;
			}

			/**
			 * Action: Order Pre Payment Complete.
			 *
			 * @since 4.3.0
			 *
			 * @param int $order_id The order id.
			 */
			do_action( 'wpcw_order_pre_payment_complete', $this->get_order_id() );

			if ( wpcw()->session ) {
				wpcw()->session->set( 'order_awaiting_payment', false );
			}

			// Add Order Note.
			if ( ! empty( $note ) ) {
				$this->add_order_note( $note );
			}

			if ( $this->has_order_status(
				apply_filters(
					'wpcw_order_valid_order_statuses_for_payment_complete',
					array(
						'on-hold',
						'processing',
						'pending',
						'failed',
						'cancelled',
					),
					$this
				)
			) ) {
				// Transaction Id.
				$this->log( sprintf( 'Payment with Order #%1$s is about to be completed. Transaction Id: %2$s', $this->get_order_id(), $transaction_id ) );

				// Set Transaction Id.
				$this->set_prop( 'transaction_id', $transaction_id );

				// Set Timestamps.
				$this->set_prop( 'date_completed', date_i18n( 'Y-m-d H:i:s', time() ) );
				$this->set_prop( 'date_paid', date_i18n( 'Y-m-d H:i:s', time() ) );

				/**
				 * Filter: Order Payment Complete Status.
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 * @param Order The order object
				 *
				 * @return string The order status. Default is 'completed'
				 */
				$this->set_order_status( apply_filters( 'wpcw_order_payment_complete_status', 'completed', $this->get_order_id(), $this ), $note );

				// Save Order.
				$this->save();

				/**
				 * Action: Order Payment Complete.
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 */
				do_action( 'wpcw_order_payment_complete', $this->get_order_id() );
			} else {
				/**
				 * Action: Order Payment Complete Order Status.
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 */
				do_action( 'wpcw_order_payment_complete_order_status_' . $this->get_order_status(), $this->get_order_id() );
			}
		} catch ( Exception $e ) {
			wpcw_file_log(
				array(
					'message' => sprintf( 'Payment complete of order #%d failed!', $this->get_order_id() ),
					array(
						'order' => $this,
						'error' => $e,
					),
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Is this order editable.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_editable() {
		return apply_filters(
			'wpcw_order_is_editable',
			in_array(
				$this->get_order_status(),
				array(
					'pending',
					'on-hold',
				),
				true
			),
			$this
		);
	}

	/**
	 * Is Order Refundable?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if order is refundable, false otherwise.
	 */
	public function is_refundable() {
		$gateway = wpcw()->gateways->get_gateway( $this->get_payment_method() );

		if ( is_null( $gateway ) || ! $gateway instanceof Gateway ) {
			return false;
		}

		if ( 'multiple' === $this->get_transaction_id() ) {
			return false;
		}

		if ( $this->has_status( 'refunded' ) ) {
			return false;
		}

		return $gateway->supports( 'refunds' ) ? true : false;
	}

	/**
	 * Refund Order.
	 *
	 * @since 4.3.0
	 *
	 * @param string $reason The reason to refund the order.
	 */
	public function refund( $reason = '' ) {
		try {
			if ( ! $this->get_order_id() ) {
				throw new Exception( __( 'The order ID is not set. Refund not processed. Aborting...', 'wp-courseware' ) );
			}

			$gateway = wpcw()->gateways->get_gateway( $this->get_payment_method() );

			if ( is_null( $gateway ) ) {
				throw new Exception( __( 'The payment method used to process the payment is no longer enabled. Please try again', 'wp-courseware' ) );
			}

			if ( ! $gateway->supports( 'refunds' ) || ! method_exists( $gateway, 'process_refund' ) ) {
				throw new Exception( sprintf( __( 'The payment gateway %s does not support refunds.', 'wp-courseware' ), $this->get_payment_method_title() ) );
			}

			$refunded = $gateway->process_refund( $this, $this->get_total(), $reason );

			if ( $refunded ) {
				// Maybe cancel subscriptions.
				$this->maybe_cancel_subscriptions();

				return true;
			} else {
				return false;
			}
		} catch ( Exception $exception ) {
			$this->log( sprintf( 'An error occurred while trying to refund an order. Error Message: %s', $exception->getMessage() ) );

			return false;
		}

		return true;
	}

	/**
	 * Callback for array_map that needs to receive quoted strings
	 *
	 * Input is already assumed to be database-safe
	 *
	 * @param string $string        The input to quote
	 * @return array
	 * @since 4.6.8
	 */
	private function db_quote_string( $string ) {
		return "'" . $string . "'";
	}

	/**
	 * Turns an array of stdClass object values containing order_id to a flat array for database queries
	 *
	 * @param string $string        The input to quote
	 * @return array
	 * @since 4.6.8
	 */
	private function flatten_order_id_array( $input ) {
		$return_value = array();

		foreach ( $input as $object ) {
			if ( $object->order_id ) {
				$return_value[] = $object->order_id;
			}
		}

		if ( count( $return_value ) < 1 ) {
			$return_value = $input;
		}
		return $return_value;
	}

	/**
	 * Get Related Orders.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of related paymetn orders.
	 */
	public function get_related_orders( $args = array(), $refresh = false ) {
		if ( empty( $this->related_orders ) || $refresh ) {
			if ( ! $this->get_id() ) {
				return $this->related_orders;
			}

			global $wpdb, $wpcwdb;

			// Failsafe: Make sure we have a valid order_id
			$order_id = ( $this->get_id() ) ? $this->get_id() : -1;

			// Get the transaction_id from the database so that we do not show duplicates
			$transaction_results = $wpdb->get_results(
				'
				SELECT		transaction_id
				FROM 		' . $wpcwdb->orders . '
				WHERE		order_id = ' . $order_id
			);

			$transaction_ids = array();
			foreach ( $transaction_results as $transaction ) {
				$transaction_ids[] = $transaction->transaction_id;
			}

			// There were no transaciton_ids, fallback to the default query
			if ( count( $transaction_ids ) < 1 ) {

				$defaults = array(
					'order_parent_id' => $this->get_id(),
					'order'           => 'ASC',
				);
				$args     = wp_parse_args( $args, $defaults );

			} else { // Get related transactions that have a unique transaction_id

				// Handle PayPal transactions
				if ( count( $transaction_ids ) > 0 && isset( $transaction_ids[0] ) && 'multiple' === $transaction_ids[0] ) {

					$sql                   = '
					SELECT		transaction_id, order_id
					FROM 		' . $wpcwdb->orders . '
					WHERE		order_parent_id = ' . $order_id . " AND
								transaction_id != 'multiple'
					ORDER BY	order_id ASC
					LIMIT		1";
					$incoming_transactions = $wpdb->get_results( $sql );

					// Replace the 'multiple' transaction ID with the real one, but still don't display the follow-up as a related order
					if ( $incoming_transactions && isset( $incoming_transactions[0] )
						&& isset( $incoming_transactions[0]->transaction_id ) &&
						strlen( $incoming_transactions[0]->transaction_id ) > 0 ) {

							$transaction_ids[0] = $incoming_transactions[0]->transaction_id;
							// $order_id = $incoming_transactions[0]->order_id; // TODO: See if this is needed to re-key future installment/subscription payments for display

					} else {
						// return $this->related_orders = array(); // TODO: Need to return here?
					}
				} // End PayPal data mangling

				$in_clause = implode(
					',',
					array_map( array( $this, 'db_quote_string' ), $transaction_ids )
				);

				$sql = '
				SELECT		order_id
				FROM 		' . $wpcwdb->orders . '
				WHERE		order_parent_id = ' . $order_id . ' AND
							transaction_id NOT IN ( ' . $in_clause . ' )
				ORDER BY	order_id ASC';

				$order_ids = $wpdb->get_results( $sql );

				$args = array(
					'order_id' => $this->flatten_order_id_array( $order_ids ),
					'order'    => 'ASC',
				);

			}

			$this->related_orders = wpcw()->orders->get_orders( $args );
		}

		return $this->related_orders;
	}

	/**
	 * Get Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of subscriptions.
	 */
	public function get_subscriptions( $args = array(), $refresh = true ) {
		if ( empty( $this->subscriptions ) ) {
			if ( ! $this->get_id() ) {
				return $this->subscriptions;
			}

			if ( 'payment' === $this->get_order_type() && $this->get_subscription_id() ) {
				$subscription = $this->get_subscription();

				if ( $subscription->get_id() ) {
					$this->subscriptions = array( $subscription );
				}
			} else {
				$defaults = array(
					'order_id' => $this->get_id(),
					'order'    => 'ASC',
				);

				$args = wp_parse_args( $args, $defaults );

				$this->subscriptions = wpcw()->subscriptions->get_subscriptions( $args );
			}
		}

		return $this->subscriptions;
	}

	/**
	 * Maybe Cancel Subscriptions.
	 *
	 * @since 4.3.0
	 */
	public function maybe_cancel_subscriptions() {
		if ( $this->has_recurring_items() ) {
			if ( $subscriptions = $this->get_subscriptions( array(), true ) ) {
				/** @var Subscription $subscription */
				foreach ( $subscriptions as $subscription ) {
					if ( ! $subscription->can_cancel() ) {
						continue;
					}

					$gateway = wpcw()->gateways->get_gateway( $subscription->get_method() );

					if ( ! $gateway ) {
						continue;
					}

					if ( ! method_exists( $gateway, 'process_subscription_cancellation' ) ) {
						continue;
					}

					// Process Cancellation.
					$gateway->process_subscription_cancellation( $subscription );
				}
			}
		}
	}

	/**
	 * Can View Order.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if a user can view, false otherwise.
	 */
	public function can_view() {
		/**
		 * Filter: Can view order?
		 *
		 * @since 4.3.0
		 *
		 * @param bool $can_view The default value.
		 * @param int $order_id The order id.
		 * @param Order $order The order object.
		 */
		return apply_filters( 'wpcw_order_can_view', true, $this->get_order_id(), $this );
	}

	/**
	 * Can Cancel Order.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if a user can cancel the order, false otherwise.
	 */
	public function can_cancel() {
		$can_cancel = true;

		if ( ! in_array(
			$this->get_status(),
			apply_filters(
				'wpcw_order_valid_statuses_for_cancel',
				array(
					'pending',
					'failed',
				),
				$this
			)
		) ) {
			$can_cancel = false;
		}

		/**
		 * Filter: Can view order?
		 *
		 * @since 4.3.0
		 *
		 * @param bool $can_cancel The default value.
		 * @param int $order_id The order id.
		 * @param Order $order The order object.
		 */
		return apply_filters( 'wpcw_order_can_cancel', $can_cancel, $this->get_order_id(), $this );
	}

	/**
	 * Get Order Actions.
	 *
	 * @since 4.3.0
	 *
	 * @return array $actions The array of order actions.
	 */
	public function get_actions() {
		$actions = array();

		if ( $this->can_view() ) {
			$actions['view'] = array(
				'url'  => $this->get_view_order_url(),
				'name' => esc_html__( 'View', 'wp-courseware' ),
			);
		}

		if ( $this->can_cancel() ) {
			$actions['cancel'] = array(
				'url'     => $this->get_order_cancel_url(),
				'name'    => esc_html__( 'Cancel', 'wp-courseware' ),
				'confirm' => esc_html__( 'Are you sure you want to cancel this Order? This CANNOT be undone!', 'wp-courseware' ),
			);
		}

		/**
		 * Filter: Order Actions.
		 *
		 * @since 4.3.0
		 *
		 * @param array $actions The order actions.
		 * @param Order $order The order object.
		 *
		 * @return array $actions The order actions.
		 */
		return apply_filters( 'wpcw_order_actions', $actions, $this );
	}

	/**
	 * Is the order amount zeroed because of discount?
	 *
	 * @since 4.5.0
	 *
	 * @return bool $zero True if amount is zeroed, false otherwise.
	 */
	public function is_amount_zero_because_of_discount() {
		$zero = false;

		if ( $this->get_total() <= 0 ) {
			$discounts  = $this->get_discounts();
			$subtotal   = $this->get_subtotal();
			$difference = wpcw_round( ( $subtotal - $discounts ) );
			if ( $difference <= 0 ) {
				$zero = true;
			}
		}

		return $zero;
	}

	/**
	 * Is Payment?
	 *
	 * @since 4.6.0
	 *
	 * @return bool True if this order is a payment. Default is false.
	 */
	public function is_payment() {
		return 'payment' === $this->get_order_type() ? true : false;
	}

	/**
	 * Is Initial Payment?
	 *
	 * @since 4.6.0
	 *
	 * @return bool True if this order is the first payment. Default is false.
	 */
	public function is_initial_payment() {
		return $this->is_payment() && $this->get_meta( '_initial_payment', true ) ? true : false;
	}

	/**
	 * Is Subscription Payment.
	 *
	 * @since 4.6.0
	 *
	 * @return bool True if its a subscription payment.
	 */
	public function is_subscription_payment() {
		return $this->is_payment() && $this->get_subscription_id() ? true : false;
	}

	/**
	 * Is Installment Payment.
	 *
	 * @since 4.6.0
	 *
	 * @return bool True if its a subscription renewal payment.
	 */
	public function is_installment_payment() {
		return $this->is_subscription_payment() && ! $this->is_initial_payment() && $this->get_meta( '_installment_payment', true ) ? true : false;
	}

	/**
	 * Is Subscription Renewal Payment.
	 *
	 * @since 4.6.0
	 *
	 * @return bool True if its a subscription renewal payment.
	 */
	public function is_subscription_renewal_payment() {
		return $this->is_subscription_payment() && ! $this->is_initial_payment() && ! $this->is_installment_payment() ? true : false;
	}
}
