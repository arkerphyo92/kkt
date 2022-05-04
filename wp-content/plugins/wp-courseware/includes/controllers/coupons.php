<?php
/**
 * WP Courseware Coupons Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.5.0
 */
namespace WPCW\Controllers;

use WPCW\Database\DB_Coupons;
use WPCW\Models\Coupon;
use WPCW\Models\Order;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Coupons.
 *
 * @since 4.5.0
 */
class Coupons extends Controller {

	/**
	 * @var DB_Coupons The coupons database.
	 * @since 4.5.0
	 */
	protected $db;

	/**
	 * Coupons constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {
		$this->db = new DB_Coupons();
	}

	/**
	 * Load Coupons Controller.
	 *
	 * @since 4.5.0
	 */
	public function load() {
		add_action( 'wpcw_ajax_api_events', array( $this, 'register_coupon_ajax_events' ) );
		add_action( 'wpcw_cart_after_display', array( $this, 'display_coupon' ) );

		// Increase / Decrease Usage Counts.
		add_action( 'wpcw_order_status_pending', array( $this, 'maybe_update_coupon_usage_counts' ), 10, 2 );
		add_action( 'wpcw_order_status_processing', array( $this, 'maybe_update_coupon_usage_counts' ), 10, 2 );
		add_action( 'wpcw_order_status_on-hold', array( $this, 'maybe_update_coupon_usage_counts' ), 10, 2 );
		add_action( 'wpcw_order_status_completed', array( $this, 'maybe_update_coupon_usage_counts' ), 10, 2 );
		add_action( 'wpcw_order_status_cancelled', array( $this, 'maybe_update_coupon_usage_counts' ), 10, 2 );
	}

	/** Ajax Methods ------------------------------------------------------- */

	/**
	 * Register Ajax Coupon Endpoints.
	 *
	 * @since 4.5.0
	 *
	 * @return array $ajax_events The array of ajax events.
	 */
	public function register_coupon_ajax_events( $ajax_events ) {
		$coupon_ajax_events = array(
			'apply-coupon'  => array( $this, 'ajax_apply_coupon' ),
			'remove-coupon' => array( $this, 'ajax_remove_coupon' ),
		);

		return array_merge( $ajax_events, $coupon_ajax_events );
	}

	/**
	 * AJAX: Apply Coupon
	 *
	 * @since 4.5.0
	 */
	public function ajax_apply_coupon() {
		wpcw()->ajax->verify_nonce();

		if ( ! empty( $_POST['coupon_code'] ) ) {
			wpcw()->cart->apply_coupon( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) );
		} else {
			$coupon = new Coupon();
			$coupon->add_coupon_error( Coupon::E_COUPON_MISSING );
		}

		wpcw_print_notices();
		wp_die();
	}

	/**
	 * AJAX: Remove Coupon
	 *
	 * @since 4.5.0
	 */
	public function ajax_remove_coupon() {
		wpcw()->ajax->verify_nonce();

		$coupon = isset( $_POST['coupon'] ) ? wpcw_clean( $_POST['coupon'] ) : false;

		if ( empty( $coupon ) ) {
			wpcw_add_notice( esc_html__( 'Sorry there was a problem removing this coupon.', 'wp-courseware' ), 'error' );
		} else {
			wpcw()->cart->remove_coupon( $coupon );
			wpcw_add_notice( esc_html__( 'Coupon has been removed.', 'wp-courseware' ) );
		}

		wpcw_print_notices();
		wp_die();
	}

	/** Cart Methods ------------------------------------------------------- */

	/**
	 * Display Coupon.
	 *
	 * @since 4.5.0
	 */
	public function display_coupon() {
		do_action( 'wpcw_coupon_before_display', $this );

		wpcw_get_template( 'checkout/coupon.php', array( 'coupon' => $this ) );

		do_action( 'wpcw_coupon_after_display', $this );
	}

	/** Getter Methods ----------------------------------------------------- */

	/**
	 * Get Coupon Types.
	 *
	 * @since 4.5.0
	 *
	 * @param bool $include_desc Include description of each coupon.
	 *
	 * @return array The array of global coupon types.
	 */
	public function get_types( $include_desc = false ) {
		$types = array(
			'percentage'   => esc_html__( 'Percentage Discount', 'wp-courseware' ),
			'fixed_cart'   => esc_html__( 'Fixed Cart Discount', 'wp-courseware' ),
			'fixed_course' => esc_html__( 'Fixed Course Discount', 'wp-courseware' ),
		);

		if ( $include_desc ) {
			$types['percentage']   = sprintf( '<strong>%s</strong>: %s', $types['percentage'], esc_html__( 'A percentage discount for the entire cart subtotal by default (if specific courses are not specified)', 'wp-courseware' ) );
			$types['fixed_cart']   = sprintf( '<strong>%s</strong>: %s', $types['fixed_cart'], esc_html__( 'A fixed discount for the entire cart subtotal by default (if specific courses are not specified)', 'wp-courseware' ) );
			$types['fixed_course'] = sprintf( '<strong>%s</strong>: %s', $types['fixed_course'], esc_html__( 'A fixed discount for each course by default (if specific courses are not specified)', 'wp-courseware' ) );
		}

		return apply_filters( 'wpcw_coupon_types', $types, $include_desc );
	}

	/**
	 * Get Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 *
	 * @return array Array of Coupon objects.
	 */
	public function get_coupons( $args = array(), $raw = false ) {
		$coupons = array();
		$results = $this->db->get_coupons( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$coupons[] = new Coupon( $result );
		}

		return $coupons;
	}

	/**
	 * Get Number of Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of coupons.
	 */
	public function get_coupons_count( $args = array() ) {
		return $this->db->get_coupons( $args, true );
	}

	/**
	 * Get Coupon Ids by Code.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code The coupon code.
	 *
	 * @return array The array of coupon ids.
	 */
	public function get_coupon_ids_by_code( $code ) {
		return $this->db->get_coupon_ids_by_code( $code );
	}

	/**
	 * Get Coupon by Code.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code The coupon code.
	 *
	 * @return Coupon|false The coupon object or false on failure.
	 */
	public function get_coupon_by_code( $code ) {
		$result = $this->db->get_coupon_by_code( $code );

		return new Coupon( $result );
	}

	/** Crud Methods ----------------------------------------------------- */

	/**
	 * Delete Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @param array $coupon_ids The Coupon Id's to delete.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function delete_coupons( $coupon_ids = array() ) {
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return false;
		}

		if ( empty( $coupon_ids ) ) {
			return false;
		}

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new Coupon( absint( $coupon_id ) );
			if ( $coupon->get_id() ) {
				$coupon->delete();
			}
		}

		return true;
	}

	/** Usage Count Methods ----------------------------------------------- */

	/**
	 * Update Coupon Usage Counts.
	 *
	 * @since 4.5.0
	 *
	 * @param int   $order_id The order id.
	 * @param Order $order The order object.
	 */
	public function maybe_update_coupon_usage_counts( $order_id, $order ) {
		if ( 'order' !== $order->get_order_type() ) {
			return;
		}

		$needs_update = false;
		$has_recorded = $order->get_recorded_coupon_usage_count();

		if ( $order->has_status( 'cancelled' ) && $has_recorded ) {
			$action = 'reduce';
			$order->set_recorded_coupon_usage_count( false );
		} elseif ( ! $order->has_status( 'cancelled' ) && ! $has_recorded ) {
			$action = 'increase';
			$order->set_recorded_coupon_usage_count( true );
		} else {
			return;
		}

		$applied_coupons = $order->get_applied_coupons();

		if ( ! empty( $applied_coupons ) ) {
			foreach ( $applied_coupons as $applied_coupon ) {
				$coupon = new Coupon( $applied_coupon );

				if ( ! $coupon->get_id() ) {
					continue;
				}

				$used_by = $order->get_student_id();

				if ( ! $used_by ) {
					$used_by = $order->get_student_email();
				}

				switch ( $action ) {
					case 'reduce':
						$needs_update = true;
						$coupon->decrease_usage_count( $used_by );
						break;
					case 'increase':
						$needs_update = true;
						$coupon->increase_usage_count( $used_by );
						break;
				}
			}
		}

		// Needs Update.
		if ( $needs_update ) {
			$coupon->save();
		}
	}
}
