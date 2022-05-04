<?php
/**
 * WP Courseware Subscriptions Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WP_REST_Request;
use WPCW\Core\Api;
use WPCW\Database\DB_Subscriptions;
use WPCW\Models\Course;
use WPCW\Models\Order;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Subscriptions.
 *
 * @since 4.3.0
 */
class Subscriptions extends Controller {

	/**
	 * @var DB_Subscriptions The subscriptions database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Subscriptions constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Subscriptions();
	}

	/**
	 * Subscriptions Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'process_subscription_cancelation' ), 20 );
		add_action( 'wp_loaded', array( $this, 'process_admin_subscription_cancelation' ), 20 );
		add_action( 'wpcw_init', array( $this, 'check_for_expired_subscriptions' ), 20 );
		add_filter( 'wpcw_cart_needs_payment', array( $this, 'check_cart_needs_payment_for_zero_based_subscriptions' ), 10, 2 );
	}

	/**
	 * Get Subscription Statuses.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of global subscription statuses.
	 */
	public function get_statuses() {
		return apply_filters( 'wpcw_subscription_statuses', array(
			'pending'        => esc_html__( 'Pending', 'wp-courseware' ),
			'active'         => esc_html__( 'Active', 'wp-courseware' ),
			'on-hold'        => esc_html__( 'On-Hold', 'wp-courseware' ),
			'suspended'      => esc_html__( 'Suspended', 'wp-courseware' ),
			'pending-cancel' => esc_html__( 'Pending Cancellation', 'wp-courseware' ),
			'cancelled'      => esc_html__( 'Cancelled', 'wp-courseware' ),
			'completed'      => esc_html__( 'Completed', 'wp-courseware' ),
			'expired'        => esc_html__( 'Expired', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Subscription Periods.
	 *
	 * @since 4.3.0
	 *
	 * @return array The subscripiton periods.
	 */
	public function get_periods() {
		return apply_filters( 'wpcw_subscription_periods', array(
			'day'       => esc_html__( 'Daily', 'wp-courseware' ),
			'week'      => esc_html__( 'Weekly', 'wp-courseware' ),
			'month'     => esc_html__( 'Monthly', 'wp-courseware' ),
			'quarter'   => esc_html__( 'Quarterly', 'wp-courseware' ),
			'semi-year' => esc_html__( 'Semi-Yearly', 'wp-courseware' ),
			'year'      => esc_html__( 'Yearly', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 *
	 * @return array Array of Subscription objects.
	 */
	public function get_subscriptions( $args = array(), $raw = false ) {
		$subscriptions = array();
		$results       = $this->db->get_subscriptions( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$subscriptions[] = new Subscription( $result );
		}

		return $subscriptions;
	}

	/**
	 * Get Number of Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of subscriptions.
	 */
	public function get_subscriptions_count( $args = array() ) {
		return $this->db->get_subscriptions( $args, true );
	}

	/**
	 * Get Subscription by Profile Id.
	 *
	 * @since 4.3.0
	 *
	 * @param string $profile_id The profile id.
	 *
	 * @return Subscription The subscription or false if it doens't exist.
	 */
	public function get_subscription_by_profile_id( $profile_id ) {
		$result = $this->db->get_by( 'profile_id', $profile_id );

		if ( ! $result ) {
			return false;
		}

		return new Subscription( $result );
	}

	/**
	 * Get Last Subscription.
	 *
	 * @since 4.6.0
	 *
	 * @return Subscription|bool The subscription object or false.
	 */
	public function get_last_subscription() {
		$last_subscription = $this->get_subscriptions( array( 'number' => 1 ) );

		return $last_subscription[0] ?: false;
	}

	/**
	 * Get Subscription by Transaction Id.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction_id The transaction id.
	 *
	 * @return bool|Subscription The subscription or false if it doens't exist.
	 */
	public function get_subscription_by_transaction_id( $transaction_id ) {
		$result = $this->db->get_by( 'transaction_id', $transaction_id );

		if ( ! $result ) {
			return false;
		}

		return new Subscription( $result );
	}

	/**
	 * Delete Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param array $subscription_ids The Subscription Id's to delete.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function delete_subscriptions( $subscription_ids = array() ) {
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return false;
		}

		if ( empty( $subscription_ids ) ) {
			return false;
		}

		foreach ( $subscription_ids as $subscription_ids ) {
			$subscription = new Subscription( absint( $subscription_ids ) );
			if ( $subscription->get_id() ) {
				$subscription->delete();
			}
		}

		return true;
	}

	/**
	 * Process Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The subscription post data.
	 *
	 * @return bool True on successful save, False on failure
	 */
	public function process_subscription( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		// Subscription Id.
		if ( ! ( $subscription_id = wpcw_array_var( $data, 'subscription_id' ) ) ) {
			return false;
		}

		// Setup Subscription.
		$subscription = new Subscription( absint( $subscription_id ) );

		// Create subscription if doesn't exist.
		if ( ! $subscription->get_id() ) {
			$subscription->create();
		}

		// Sanitize & Save Subscription Data.
		foreach ( $subscription->get_data( true ) as $key => $value ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$dont_set_prop = false;

			// Specific Sanitization
			switch ( $key ) {
				case 'created' :
					if ( ! ( $created = wpcw_array_var( $data, 'created' ) ) ) {
						$value = current_time( 'timestamp', true );
					} else {
						$created_hour   = wpcw_array_var( $data, 'created_hour' );
						$created_minute = wpcw_array_var( $data, 'created_minute' );
						$created_second = wpcw_array_var( $data, 'created_second' );
						if ( $created && $created_hour && $created_minute && $created_second ) {
							$value = gmdate( 'Y-m-d H:i:s', strtotime( $created . ' ' . (int) $created_hour . ':' . (int) $created_minute . ':' . (int) $created_second ) );
						}
					}
					break;
				case 'expiration' :
					if ( ! ( $expiration = wpcw_array_var( $data, 'expiration' ) ) ) {
						$value = current_time( 'timestamp', true );
					} else {
						$expiration_hour   = wpcw_array_var( $data, 'expiration_hour' );
						$expiration_minute = wpcw_array_var( $data, 'expiration_minute' );
						$expiration_second = wpcw_array_var( $data, 'expiration_second' );
						if ( $expiration && $expiration_hour && $expiration_minute && $expiration_second ) {
							$value = gmdate( 'Y-m-d H:i:s', strtotime( $expiration . ' ' . (int) $expiration_hour . ':' . (int) $expiration_minute . ':' . (int) $expiration_second ) );
						}
					}
					break;
				case 'order_id':
					$value = absint( $data[ $key ] );
					if ( absint( $subscription->get_order_id() ) !== absint( $value ) ) {
						$order = new Order( $value );
						if ( $order->get_order_id() ) {
							$order->update_meta( 'subscription_id', $value, $subscription->get_order_id() );
						}
					}
					break;
				case 'course_id':
					$value = absint( $data[ $key ] );
					break;
				case 'student_id' :
					$value = absint( $data[ $key ] );
					break;
				case 'status' :
					$subscription->set_status( wpcw_clean( $data[ $key ] ) );
					$dont_set_prop = true;
					break;
				default:
					$value = wpcw_clean( $data[ $key ] );
					break;
			}

			// Set Subscription Property.
			if ( ! $dont_set_prop ) {
				$subscription->set_prop( $key, $value );
			}
		}

		// Check for Student Id.
		if ( ! $subscription->get_student_id() ) {
			wp_die( esc_html__( 'Student is required.', 'wp-courseware' ) );
		}

		// Check for Course Id.
		if ( ! $subscription->get_course_id() ) {
			wp_die( esc_html__( 'Course is required.', 'wp-courseware' ) );
		}

		// Check for Payment Method.
		if ( ! $subscription->get_method() ) {
			wp_die( esc_html__( 'Payment Method is required.', 'wp-courseware' ) );
		}

		// Add new order.
		if ( ( $order_action = wpcw_array_var( $data, 'order_action' ) ) && 'new' === $order_action ) {
			// Create Order.
			$order = new Order();
			$order->create();

			// Get Course.
			$course  = $subscription->get_course();
			$student = $subscription->get_student();

			// Add Order Items.
			$order->insert_order_items( array(
				array(
					'id'     => $course->get_course_id(),
					'title'  => $course->get_course_title(),
					'qty'    => 1,
					'amount' => $course->get_payments_price(),
				),
			) );

			// Set Course Id.
			$subscription->set_prop( 'course_title', $course->get_course_title() );

			// Set Student Id.
			if ( $subscription->get_student_id() ) {
				$order->set_prop( 'student_id', $student->get_ID() );
				$order->set_prop( 'student_name', $student->get_full_name() );
				$order->set_prop( 'student_email', $student->get_email() );
			}

			// Set Transaction Id.
			if ( $subscription->get_transaction_id() ) {
				$order->set_prop( 'transaction_id', $subscription->get_transaction_id() );
			}

			// Set Payment Method.
			if ( $subscription->get_method() ) {
				$order->set_prop( 'payment_method', $subscription->get_method() );
			}

			// Set Order Completed.
			if ( 'completed' !== $order->get_order_status() ) {
				$order->set_order_status( 'completed' );
			}

			// Update Totals & Save.
			$order->update_totals();
			$order->save();

			// Set Subscription Id to Order.
			$order->add_meta( 'subscription_id', $subscription->get_id(), true );

			// Set Order Id for Subscription.
			$subscription->set_prop( 'order_id', $order->get_order_id() );
		}

		// Save Subscription.
		return $subscription->save();
	}

	/**
	 * Process Subscription Cancellation.
	 *
	 * @since 4.3.0
	 */
	public function process_subscription_cancelation() {
		if ( isset( $_GET['cancel_subscription'] ) && isset( $_GET['subscription_id'] ) && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpcw-cancel-subscription' ) ) ) {
			wpcw_nocache_headers();

			$subscription_id         = absint( $_GET['subscription_id'] );
			$subscription            = wpcw_get_subscription( $subscription_id );
			$user_can_cancel         = current_user_can( 'read', $subscription_id );
			$subscription_can_cancel = $subscription->has_status( apply_filters( 'wpcw_subscription_statuses_for_cancel', array( 'active', 'pending', 'failed' ) ) );
			$redirect                = $_GET['redirect'];

			if ( ! $subscription->has_status( 'cancelled' ) && $user_can_cancel && $subscription_can_cancel && absint( $subscription->get_id() ) === absint( $subscription_id ) ) {
				$gateway = wpcw()->gateways->get_gateway( $subscription->get_method() );

				if ( ! $gateway ) {
					wpcw_add_notice( esc_html__( 'There was a error cancelling your subscription. Please try again.', 'wp-courseware' ), 'error' );

					return;
				}

				if ( ! method_exists( $gateway, 'process_subscription_cancellation' ) ) {
					wpcw_add_notice( sprintf( __( 'The payment gateway %s does not support cancelling your subscription.', 'wp-courseware' ), $gateway->get_title() ), 'error' );

					return;
				}

				// Process Subscription Cancellation.
				$cancelled = $gateway->process_subscription_cancellation( $subscription );

				if ( $cancelled ) {
					wpcw_add_notice( apply_filters( 'wpcw_subscription_cancelled_notice', esc_html__( 'Your subscription has been cancelled.', 'wp-courseware' ) ), apply_filters( 'wpcw_subscription_cancelled_notice_type', 'success' ) );
				} else {
					wpcw_add_notice( esc_html__( 'There was an error cancelling your subscription. Please try again.', 'wp-courseware' ), 'error' );
				}
			} elseif ( $user_can_cancel && ! $subscription_id ) {
				wpcw_add_notice( esc_html__( 'Your subscription can no longer be cancelled. Please contact us if you need assistance.', 'wp-courseware' ), 'error' );
			} else {
				wpcw_add_notice( esc_html__( 'Invalid subscription.', 'wp-courseware' ), 'error' );
			}

			if ( $redirect ) {
				wp_safe_redirect( $redirect );
				exit;
			} else {
				wp_safe_redirect( $subscription->get_view_url() );
				exit;
			}
		}
	}

	/**
	 * Process Admin Subscription Cancellation.
	 *
	 * @since 4.3.0
	 */
	public function process_admin_subscription_cancelation() {
		if ( ! is_admin() ) {
			return;
		}

		if ( isset( $_GET['cancel_subscription'] ) && isset( $_GET['subscription_id'] ) && ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpcw-admin-cancel-subscription' ) ) ) {
			wpcw_nocache_headers();

			$subscription_id         = absint( $_GET['subscription_id'] );
			$subscription            = wpcw_get_subscription( $subscription_id );
			$user_can_cancel         = current_user_can( 'read', $subscription_id );
			$subscription_can_cancel = $subscription->has_status( apply_filters( 'wpcw_subscription_statuses_for_cancel', array( 'active', 'pending', 'pending-cancel', 'expired', 'failed' ) ) );
			$redirect                = $subscription->get_edit_url();

			if ( ! $subscription->has_status( array(
					'pending-cancel',
					'cancelled',
				) ) && $user_can_cancel && $subscription_can_cancel && absint( $subscription->get_id() ) === absint( $subscription_id ) ) {
				$gateway = wpcw()->gateways->get_gateway( $subscription->get_method() );

				if ( ! $gateway ) {
					wpcw_add_admin_notice_error( esc_html__( 'There was a error cancelling the subscription. Please try again.', 'wp-courseware' ) );
					wp_safe_redirect( $redirect );
					exit;
				}

				if ( ! method_exists( $gateway, 'process_subscription_cancellation' ) ) {
					wpcw_add_admin_notice_error( sprintf( __( 'The payment gateway %s does not support cancelling your subscription.', 'wp-courseware' ), $gateway->get_title() ) );
					wp_safe_redirect( $redirect );
					exit;
				}

				// Process Subscription Cancellation.
				$cancelled = $gateway->process_subscription_cancellation( $subscription );

				if ( $cancelled ) {
					wpcw_add_admin_notice_success( apply_filters( 'wpcw_subscription_cancelled_admin_notice', esc_html__( 'This subscription has been cancelled.', 'wp-courseware' ) ) );
				} else {
					wpcw_add_admin_notice_error( esc_html__( 'There was an error cancelling this subscription. Please try again.', 'wp-courseware' ), 'error' );
				}
			} elseif ( $user_can_cancel && ! $subscription_id ) {
				wpcw_add_admin_notice_error( esc_html__( 'This subscription can no longer be cancelled.', 'wp-courseware' ), 'error' );
			} elseif ( $subscription_can_cancel && $subscription->has_status( 'pending-cancel' ) ) {
				$subscription->cancel();
				wpcw_add_admin_notice_success( esc_html__( 'This subscription has been cancelled', 'wp-courseware' ) );
			} else {
				wpcw_add_admin_notice_error( esc_html__( 'Invalid subscription.', 'wp-courseware' ), 'error' );
			}

			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Check for Expired Subscriptions.
	 *
	 * @since 4.3.0
	 */
	public function check_for_expired_subscriptions() {
		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		$student_id    = get_current_user_id();
		$check_key     = '_wpcw_subs_expired_check';
		$check_expires = time() + 12 * HOUR_IN_SECONDS;
		$check_data    = array( 'checked' => true, 'expires' => $check_expires );
		$check_cache   = get_user_meta( $student_id, $check_key, true );

		if ( empty( $check_cache ) || $check_cache['expires'] < time() ) {
			$student = wpcw_get_student( get_current_user_id() );

			if ( ! $student || ( $student && ! $student->get_ID() ) ) {
				delete_user_meta( $student_id, $check_key );

				return;
			}

			// Get Active Subscriptions
			if ( ! $student_subscriptions = $student->get_subscriptions( array( 'status' => array( 'active', 'pending-cancel' ) ) ) ) {
				update_user_meta( $student_id, $check_key, $check_data );

				return;
			}

			/** @var Subscription $student_subscription */
			foreach ( $student_subscriptions as $student_subscription ) {
				if ( $student_subscription->is_expired() ) {
					$student_subscription->expire();
				}
			}

			update_user_meta( $student_id, $check_key, $check_data );
		}
	}

	/**
	 * Does the cart contain a subscription.
	 *
	 * @since 4.5.0
	 *
	 * @return bool $contains_subscription True if cart contains subscription.
	 */
	public function cart_contains_subscription() {
		$contains_subscription = false;

		$cart = wpcw()->cart->get_cart();

		if ( empty( $cart ) ) {
			return $contains_subscription;
		}

		foreach ( $cart as $cart_item_key => $cart_item ) {
			if ( ! isset( $cart_item['id'] ) || empty( $cart_item['data'] ) ) {
				continue;
			}

			$course = new Course( $cart_item['data'] );

			if ( $course->is_subscription() ) {
				$contains_subscription = true;
				break;
			}
		}

		return $contains_subscription;
	}

	/**
	 * Check Cart Needs Payment for Zero Based Subscriptions.
	 *
	 * @since 4.5.0
	 *
	 * @param bool $needs_payment If the cart needs payment.
	 * @param Cart $cart The cart controller.
	 *
	 * @return bool True if cart needs payment.
	 */
	public function check_cart_needs_payment_for_zero_based_subscriptions( $needs_payment, $cart ) {
		if ( ( false === (bool) $needs_payment ) && $this->cart_contains_subscription() && ( 0 === absint( $cart->get_total() ) ) ) {
			$needs_payment = true;
		}

		return $needs_payment;
	}

	/** API Methods -------------------------------------------------- */

	/**
	 * Register Subscriptions Api Endpoints.
	 *
	 * @since 4.3.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'subscriptions', 'method' => 'GET', 'callback' => array( $this, 'api_get_subscriptions' ) );

		return $endpoints;
	}

	/**
	 * Api: Get Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_subscriptions( WP_REST_Request $request ) {
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

		$subscriptions = $this->get_subscriptions( $query_args );
		$count         = $this->get_subscriptions_count( $query_args );

		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription instanceof Subscription ) {
				continue;
			}

			$results[] = array(
				'id'    => $subscription->get_id(),
				'title' => sprintf( __( 'Subscription #%s', 'wp-courseware' ), $subscription->get_id() ),
			);
		}

		if ( $test ) {
			$results[] = array(
				'id'    => 'test',
				'title' => esc_html__( 'Test Subscription', 'wp-courseware' ),
			);
		}

		return rest_ensure_response( array( 'subscriptions' => $results ) );
	}
}
