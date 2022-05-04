<?php
/**
 * WP Courseware Query.
 *
 * @since 4.3.0
 * @subpackage Core
 * @package WPCW
 */

namespace WPCW\Core;

use WPCW\Models\Order;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Query.
 *
 * @since 4.3.0
 */
final class Query {

	/**
	 * array The query vars needed to add to WP.
	 *
	 * @since 4.3.0
	 */
	protected $query_vars = array();

	/**
	 * Load Query Class.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_init', array( $this, 'register_query_vars' ) );
		add_action( 'wpcw_init', array( $this, 'register_endpoints' ) );

		if ( ! is_admin() ) {
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}
	}

	/**
	 * Register Query Vars.
	 *
	 * @since 4.3.0
	 */
	public function register_query_vars() {
		$this->query_vars = apply_filters( 'wpcw_query_vars', array(
			'order-received'    => wpcw_get_setting( 'order_received_endpoint' ),
			'order-failed'      => wpcw_get_setting( 'order_failed_endpoint' ),
			'courses'           => wpcw_get_setting( 'student_courses_endpoint' ),
			'orders'            => wpcw_get_setting( 'student_orders_endpoint' ),
			'view-order'        => wpcw_get_setting( 'student_view_order_endpoint' ),
			'subscriptions'     => wpcw_get_setting( 'student_subscriptions_endpoint' ),
			'view-subscription' => wpcw_get_setting( 'student_view_subscription_endpoint' ),
			'register'          => wpcw_get_setting( 'student_register_endpoint' ),
			'edit-account'      => wpcw_get_setting( 'student_edit_account_endpoint' ),
			'lost-password'     => wpcw_get_setting( 'student_lost_password_endpoint' ),
			'student-logout'    => wpcw_get_setting( 'student_logout_endpoint' ),
		) );
	}

	/**
	 * Register Endpoints.
	 *
	 * @since 4.3.0
	 */
	public function register_endpoints() {
		$mask = $this->get_endpoints_mask();
		foreach ( $this->get_query_vars() as $key => $var ) {
			if ( ! empty( $var ) ) {
				add_rewrite_endpoint( $var, $mask );
			}
		}
	}

	/**
	 * Endpoint mask describing the places the endpoint should be added.
	 *
	 * @since 4.3.0
	 *
	 * @return string The endpoint mask.
	 */
	public function get_endpoints_mask() {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$page_on_front    = get_option( 'page_on_front' );
			$account_page_id  = wpcw_get_page_id( 'account' );
			$checkout_page_id = wpcw_get_page_id( 'checkout' );

			if ( in_array( absint( $page_on_front ), array( $account_page_id, $checkout_page_id ), true ) ) {
				return EP_ROOT | EP_PAGES;
			}
		}

		return EP_PAGES;
	}

	/**
	 * Get Endpoint Title.
	 *
	 * @since 4.3.0
	 *
	 * @param string $endpoint The endpoint slug.
	 *
	 * @return string The title for the endpoint
	 */
	public function get_endpoint_title( $endpoint ) {
		global $wp;

		switch ( $endpoint ) {
			case 'order-received':
				$title = esc_html__( 'Order Received', 'wp-courseware' );
				break;
			case 'order-failed':
				$title = esc_html__( 'Order Failed', 'wp-courseware' );
				break;
			case 'courses':
				$title = esc_html__( 'Student Courses', 'wp-courseware' );
				break;
			case 'orders':
				$title = esc_html__( 'Student Orders', 'wp-courseware' );
				break;
			case 'view-order':
				/** @var Order $order */
				$order = wpcw_get_order( $wp->query_vars['view-order'] );
				/* translators: %s: order number */
				$title = ( $order ) ? sprintf( __( 'Student Order #%s', 'wp-courseware' ), $order->get_order_number() ) : esc_html__( 'Student Order', 'wp-courseware' );
				break;
			case 'subscriptions':
				$title = esc_html__( 'Student Subscriptions', 'wp-courseware' );
				break;
			case 'view-subscription':
				/** @var Subscription $subscription */
				$subscription = wpcw_get_subscription( $wp->query_vars['view-subscription'] );
				/* translators: %s: subscription id */
				$title = ( $subscription ) ? sprintf( __( 'Student Subscription #%s', 'wp-courseware' ), $subscription->get_id() ) : esc_html__( 'Student Subscription', 'wp-courseware' );
				break;
			case 'register' :
				$title = esc_html__( 'Register for an Account', 'wp-courseware' );
				break;
			case 'edit-account' :
				$title = esc_html__( 'Edit Student Account', 'wp-courseware' );
				break;
			case 'lost-password' :
				$title = esc_html__( 'Recover Password', 'wp-courseware' );
				break;
			default:
				$title = '';
				break;
		}

		/**
		 * Filter: Endpoint Title.
		 *
		 * @since 4.3.0
		 *
		 * @param stirng $title The endpoint title.
		 * @param string $endpoint The endpoint slug.
		 *
		 * @return string The endpoint title.
		 */
		return apply_filters( 'wpcw_endpoint_' . $endpoint . '_title', $title, $endpoint );
	}

	/**
	 * Get query current active query var.
	 *
	 * @return string
	 */
	public function get_current_endpoint() {
		global $wp;

		foreach ( $this->get_query_vars() as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Get Query Vars.
	 *
	 * @since 4.3.0
	 *
	 * @return array The registered query vars.
	 */
	public function get_query_vars() {
		return apply_filters( 'wpcw_get_query_vars', $this->query_vars );
	}

	/**
	 * Add Query Vars to WP.
	 *
	 * @since 4.3.0
	 *
	 * @param array $vars The WP query vars.
	 *
	 * @return array The modified WP query vars.
	 */
	public function add_query_vars( $vars ) {
		foreach ( $this->get_query_vars() as $key => $var ) {
			if ( ! empty( $var ) ) {
				$vars[] = $key;
			}
		}

		return $vars;
	}

	/**
	 * Parse the request to look for query vars.
	 *
	 * @since 4.3.0
	 */
	public function parse_request() {
		global $wp;

		// Map query vars to their keys, or get them if endpoints are not supported.
		foreach ( $this->get_query_vars() as $key => $var ) {
			if ( isset( $_GET[ $var ] ) ) {
				$wp->query_vars[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $var ] ) );
			} elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}
	}

	/**
	 * Are we currently on the front page?
	 *
	 * @since 4.3.4
	 *
	 * @param \WP_Query $q The WP_Query instance.
	 *
	 * @return bool TRue if is home is show on front.
	 */
	private function is_showing_page_on_front( $q ) {
		return $q->is_home() && 'page' === get_option( 'show_on_front' );
	}

	/**
	 * Is the front page a page we define?
	 *
	 * @since 4.3.4
	 *
	 * @param int $page_id Page ID.
	 *
	 * @return bool
	 */
	private function page_on_front_is( $page_id ) {
		return absint( get_option( 'page_on_front' ) ) === absint( $page_id );
	}

	/**
	 * Hook into pre get posts to fix endpoints on the home page.
	 *
	 * @since 4.3.4
	 *
	 * @param \WP_Query $q The main WP_Query instance.
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query.
		if ( ! $q->is_main_query() ) {
			return;
		}

		// Fix for endpoints on the homepage.
		if ( $this->is_showing_page_on_front( $q ) && ! $this->page_on_front_is( $q->get( 'page_id' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->get_query_vars() ) ) ) {
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}
	}

	/**
	 * Replace the title for the specific endpoint.
	 *
	 * @since 4.3.0
	 *
	 * @param string $title The title of the endpoint.
	 */
	public function endpoint_title( $title ) {
		global $wp_query;

		if ( ! is_null( $wp_query ) && ! is_admin() && is_main_query() && in_the_loop() && is_page() && wpcw_is_endpoint_url() ) {
			$endpoint       = $this->get_current_endpoint();
			$endpoint_title = $this->get_endpoint_title( $endpoint );
			$title          = $endpoint_title ? $endpoint_title : $title;

			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}

		return $title;
	}
}
