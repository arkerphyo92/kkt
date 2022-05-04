<?php
/**
 * WP Courseware Orders Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WP_Error;
use WP_REST_Request;
use WPCW\Core\Api;
use WPCW\Database\DB_Orders;
use WPCW\Models\Order;
use WPCW\Models\Order_Item;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Checkout.
 *
 * @since 4.3.0
 */
class Orders extends Controller {

	/**
	 * @var DB_Orders The order db object.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Orders constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Orders();
	}

	/**
	 * Load Orders Controller.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
		add_filter( 'wpcw_order_item_title', array( $this, 'display_order_item_subscription_renewal_message' ), 10, 2 );
		add_filter( 'wpcw_order_item_price', array( $this, 'display_order_item_subscription_interval' ), 10, 2 );
		add_filter( 'wpcw_order_item_title', array( $this, 'display_order_item_installments_message' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'process_order_cancellation' ), 20 );
	}

	/**
	 * Get Order Statuses.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of global order statuses.
	 */
	public function get_order_statuses() {
		return apply_filters( 'wpcw_order_statuses', array(
			'pending'    => esc_html__( 'Pending', 'wp-courseware' ),
			'processing' => esc_html__( 'Processing', 'wp-courseware' ),
			'on-hold'    => esc_html__( 'On-Hold', 'wp-courseware' ),
			'completed'  => esc_html__( 'Completed', 'wp-courseware' ),
			'refunded'   => esc_html__( 'Refunded', 'wp-courseware' ),
			'cancelled'  => esc_html__( 'Cancelled', 'wp-courseware' ),
			'failed'     => esc_html__( 'Failed', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Order Types.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of global order types.
	 */
	public function get_order_types() {
		return apply_filters( 'wpcw_order_types', array(
			'order'   => esc_html__( 'Order', 'wp-courseware' ),
			'payment' => esc_html__( 'Payment', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 * @param bool  $scrub_followup Remove 'apparently' duplicate follow-up entries
	 *
	 * @return array Array of course objects.
	 */
	public function get_orders( $args = array(), $raw = false, $scrub_followup = false ) {
		$orders  = array();
		$results = $this->db->get_orders( $args );

		// Short circuit the query when there are no connected order IDs and this isn't a list of all orders
		if(	isset( $args['order_id'] ) && 
			(!is_array( $args['order_id'] ) || count( $args['order_id'] ) < 1) )
		{
			return $orders;
		}

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$orders[] = new Order( $result );
		}

		// Do not display the 'apparently duplicate' follow-up transaction for subscription/installment orders
		if( $scrub_followup && count( $orders ) > 0 && isset( $orders[0] ) ) {
			// Make sure we're dealing with an Order object
            if ($orders[0] instanceof \WPCW\Models\Order) {
                $order_parent = new Order($orders[0]->get_order_parent_id());
                $parent_date = strtotime($order_parent->date_completed);
                $payment_date = strtotime($orders[0]->date_completed);
                // Parent order completion and payment order completion should have the same timestamp, but we're going to
                //   allow a 30 second difference in case of latency
                if (absint(($payment_date - $parent_date)) <= 30) {
                    array_shift($orders);
                }
            } // if ($orders[0] instanceof \WPCW\Models\Order)
		}

		return $orders;
	}

	/**
	 * Get Number of Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of courses.
	 */
	public function get_orders_count( $args = array() ) {
		return $this->db->get_orders( $args, true );
	}

	/**
	 * Get Order by Transaction Id.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction_id The transaction id.
	 *
	 * @return Order|bool The order object or false on failure.
	 */
	public function get_order_by_transaction_id( $transaction_id ) {
		$result = $this->db->get_by( 'transaction_id', $transaction_id );

		return $result ? new Order( $result ) : false;
	}

	/**
	 * Delete Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param array $order_ids The Order Id's to delete.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function delete_orders( $order_ids = array() ) {
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return false;
		}

		if ( empty( $order_ids ) ) {
			return false;
		}

		foreach ( $order_ids as $order_id ) {
			$order = new Order( absint( $order_id ) );
			if ( $order->get_order_id() ) {
				$order->delete();
			}
		}

		return true;
	}

	/**
	 * Get Order by Order Key.
	 *
	 * @since 4.3.0
	 *
	 * @param string $order_key The order key.
	 *
	 * @return bool|Order The ordeer of if it doesn't exist false.
	 */
	public function get_order_by_order_key( $order_key ) {
		$result = $this->db->get_by( 'order_key', 'wpcw_order_' . $order_key );

		if ( ! $result ) {
			return false;
		}

		return new Order( $result );
	}

	/**
	 * Process Order.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The order post data.
	 *
	 * @return bool True on successful save, False on failure
	 */
	public function process_order( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		// Order Id.
		if ( ! ( $order_id = wpcw_array_var( $data, 'order_id' ) ) ) {
			return false;
		}

		// Setup Order.
		$order = new Order( absint( $order_id ) );

		// Create order if doesn't exist.
		if ( ! $order->get_order_id() ) {
			$order->create();
		}

		// Sanitize & Save Order Data.
		foreach ( $order->get_data( true ) as $key => $value ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			// Specific Sanitization
			switch ( $key ) {
				case 'date_created' :
					if ( ! ( $date_created = wpcw_array_var( $data, 'date_created' ) ) ) {
						$value = current_time( 'timestamp', true );
					} else {
						$date_created_hour   = wpcw_array_var( $data, 'date_created_hour' );
						$date_created_minute = wpcw_array_var( $data, 'date_created_minute' );
						$date_created_second = wpcw_array_var( $data, 'date_created_second' );
						if ( $date_created && $date_created_hour && $date_created_minute && $date_created_second ) {
							$value = gmdate( 'Y-m-d H:i:s', strtotime( $date_created . ' ' . (int) $date_created_hour . ':' . (int) $date_created_minute . ':' . (int) $date_created_second ) );
						}
					}
					break;
				case 'payment_method' :
					$value = wpcw_clean( $data[ $key ] );
					if ( ! $value ) {
						$order->set_prop( 'payment_method_title', '' );
					} else {
						$payment_methods = wpcw()->checkout->get_payment_methods();
						if ( array_key_exists( $value, $payment_methods ) ) {
							$order->set_prop( 'payment_method_title', wpcw_clean( $payment_methods[ $value ] ) );
						}
					}
					break;
				case 'student_id' :
					$value = absint( $data[ $key ] );
					if ( $value && ( $course_ids = wp_list_pluck( $order->get_order_items_data(), 'course_id' ) ) ) {
						wpcw()->enrollment->enroll_student( $value, $course_ids );
					}
					break;
				case 'order_status' :
					$value = wpcw_clean( $data[ $key ] );
					if ( $value ) {
						$order->set_order_status( $value );
					}
					break;
				default:
					$value = wpcw_clean( $data[ $key ] );
					break;
			}

			// Set Order Property.
			$order->set_prop( $key, $value );
		}

		// Save Order.
		return $order->save();
	}

	/**
	 * Display Order Item Subscription Renewal Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $$order_item_title The order item title.
	 * @param Order_Item $order_item The order item object.
	 *
	 * @return string The course subscription renewal message.
	 */
	public function display_order_item_subscription_renewal_message( $order_item_title, $order_item ) {
		$course = $order_item->get_course();

		if ( ! $course || ! $course->is_purchasable() || ! $course->is_subscription() || ( $course->is_subscription() && $course->charge_installments() ) ) {
			return $order_item_title;
		}

		$order_item_title .= "\n" . sprintf( __( '<p><em>Billed %s until cancelled.</em></p>', 'wp-courseware' ), strtolower( $course->get_subscription_interval() ) );

		return $order_item_title;
	}

	/**
	 * Display Order Item Subscription Interval.
	 *
	 * @since 4.3.0
	 *
	 * @param string     $order_item_price The order item price.
	 * @param Order_Item $order_item The order item object.
	 *
	 * @return string $order_item_price The order item price with the interval
	 */
	public function display_order_item_subscription_interval( $order_item_price, $order_item ) {
		$course = $order_item->get_course();

		if ( ! $course || ! $course->is_purchasable() || ! $course->is_subscription() || ( $course->is_subscription() && $course->charge_installments() ) ) {
			return $order_item_price;
		}

		$order_item_price = sprintf( '%s / %s', $order_item_price, $course->get_subscription_interval() );

		return $order_item_price;
	}

	/**
	 * Display Order Item Installments Message.
	 *
	 * @since 4.6.0
	 *
	 * @param string $$order_item_title The order item title.
	 * @param Order_Item $order_item The order item object.
	 *
	 * @return string The course subscription renewal message.
	 */
	public function display_order_item_installments_message( $order_item_title, $order_item ) {
		$course = $order_item->get_course();

		if ( ! $course || ! $course->is_purchasable() || ! $course->charge_installments() ) {
			return $order_item_title;
		}

		$order_item_title .= "\n" . sprintf( __( '<div class="wpcw-cart-item-message"><em>%s</em></div>', 'wp-courseware' ), $course->get_installments_label() );

		return $order_item_title;
	}

	/**
	 * Process Order Cancellation.
	 *
	 * @since 4.3.0
	 */
	public function process_order_cancellation() {
		if ( isset( $_GET['cancel_order'] ) && isset( $_GET['order'] ) && isset( $_GET['order_id'] ) && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpcw-cancel_order' ) ) ) {
			wpcw_nocache_headers();

			$order_key        = $_GET['order'];
			$order_id         = absint( $_GET['order_id'] );
			$order            = wpcw_get_order( $order_id );
			$user_can_cancel  = current_user_can( 'read', $order_id );
			$order_can_cancel = $order->has_order_status( apply_filters( 'wpcw_order_statuses_for_cancel', array( 'pending', 'failed' ) ) );
			$redirect         = isset( $_GET['redirect'] ) ? $_GET['redirect'] : '';

			if ( ! $order->has_order_status( 'cancelled' ) && $user_can_cancel && $order_can_cancel && absint( $order->get_order_id() ) === absint( $order_id ) && $order->get_order_key() === $order_key ) {
				// Cancel the order.
				wpcw()->session->set( 'order_awaiting_payment', false );

				// Update the Status.
				$order->update_status( 'cancelled', __( 'Order cancelled by customer.', 'wp-courseware' ) );

				// Display Message.
				wpcw_add_notice( apply_filters( 'wpcw_order_cancelled_notice', __( 'Your order was cancelled.', 'wp-courseware' ) ), apply_filters( 'wpcw_order_cancelled_notice_type', 'info' ) );

				/**
				 * Action: Order Cancelled.
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 */
				do_action( 'wpcw_order_cancelled', $order->get_order_id() );
			} elseif ( $user_can_cancel && ! $order_can_cancel ) {
				wpcw_add_notice( __( 'Your order can no longer be cancelled. Please contact us if you need assistance.', 'wp-courseware' ), 'error' );
			} else {
				wpcw_add_notice( __( 'Invalid order.', 'wp-courseware' ), 'error' );
			}

			if ( $redirect ) {
				wp_safe_redirect( $redirect );
				exit;
			} else {
				wp_safe_redirect( $order->get_view_order_url() );
				exit;
			}
		}
	}

	/** API Methods -------------------------------------------------- */

	/**
	 * Register Orders Api Endpoints.
	 *
	 * @since 4.3.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'orders', 'method' => 'GET', 'callback' => array( $this, 'api_get_orders' ) );
		$endpoints[] = array( 'endpoint' => 'save-order-items', 'method' => 'POST', 'callback' => array( $this, 'api_save_order_items' ) );
		$endpoints[] = array( 'endpoint' => 'delete-order-items', 'method' => 'POST', 'callback' => array( $this, 'api_delete_order_items' ) );
		$endpoints[] = array( 'endpoint' => 'order-refund', 'method' => 'POST', 'callback' => array( $this, 'api_order_refund' ) );

		return $endpoints;
	}

	/**
	 * Api: Get Orders.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_orders( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$test   = $request->get_param( 'test' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = 1000;
		}

		$results    = array();
		$query_args = array( 'search' => $search, 'number' => $number );

		$orders = $this->get_orders( $query_args );
		$count  = $this->get_orders_count( $query_args );

		foreach ( $orders as $order ) {
			if ( ! $order instanceof Order ) {
				continue;
			}

			$results[] = array(
				'id'    => $order->get_order_id(),
				'title' => sprintf( __( 'Order #%s', 'wp-courseware' ), $order->get_order_id() ),
			);
		}

		if ( $test ) {
			$test_order = wpcw_get_test_order();
			$results[]  = array(
				'id'    => 'test',
				'title' => esc_html__( 'Test Order', 'wp-courseware' ),
			);
		}

		return rest_ensure_response( array( 'orders' => $results ) );
	}

	/**
	 * Api: Save Order Items
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_save_order_items( WP_REST_Request $request ) {
		$order_id    = $request->get_param( 'order_id' );
		$order_items = $request->get_param( 'order_items' );

		if ( empty( $order_id ) ) {
			return new WP_Error( 'order-id-missing', esc_html__( 'There was no Order ID provided. Please specifiy an Order ID.', 'wp-courseware' ) );
		}

		if ( empty( $order_items ) ) {
			return new WP_Error( 'order-items-missing', esc_html__( 'There were no order items provided. Please add some order items and try again.', 'wp-courseware' ) );
		}

		$order = new Order( $order_id );
		$order->insert_order_items( $order_items );
		$order->update_totals();
		$order->save();

		return rest_ensure_response( array(
			'items'    => $order->get_order_items_data( false ),
			'subtotal' => $order->get_subtotal( true ),
			'tax'      => $order->get_tax( true ),
			'total'    => $order->get_total( true ),
		) );
	}

	/**
	 * Api: Delete Order Items
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_delete_order_items( WP_REST_Request $request ) {
		$order_id      = $request->get_param( 'order_id' );
		$order_item_id = $request->get_param( 'order_item_id' );

		if ( empty( $order_id ) ) {
			return new WP_Error( 'order-id-missing', esc_html__( 'There was no Order ID provided. Please specifiy an Order ID.', 'wp-courseware' ) );
		}

		if ( empty( $order_item_id ) ) {
			return new WP_Error( 'order-item-id-missing', esc_html__( 'There was no Order Item ID provided.', 'wp-courseware' ) );
		}

		$order = new Order( $order_id );
		$order->delete_order_items( array( $order_item_id ) );
		$order->update_totals();
		$order->save();

		return rest_ensure_response( array(
			'items'    => $order->get_order_items_data( false ),
			'subtotal' => $order->get_subtotal( true ),
			'tax'      => $order->get_tax( true ),
			'total'    => $order->get_total( true ),
		) );
	}

	/**
	 * Api: Order Refund.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_order_refund( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );

		if ( empty( $order_id ) ) {
			return new WP_Error( 'order-id-missing', esc_html__( 'There was no Order ID provided. Please specifiy an Order ID.', 'wp-courseware' ) );
		}

		$status = 'success';

		$order    = new Order( $order_id );
		$refunded = $order->refund();

		if ( ! $refunded ) {
			$status = 'failure';
		}

		return rest_ensure_response( array( 'status' => $status ) );
	}
}
