<?php
/**
 * WP Courseware Api.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WP_Error;
use WP_REST_Request;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Api.
 *
 * @since 4.1.0
 */
final class Api {

	/**
	 * @var string The api namespace.
	 * @since 4.1.0
	 */
	protected $namespace = 'wpcw/v1';

	/**
	 * @var string The api route base.
	 * @since 4.1.0
	 */
	protected $base = '/api/';

	/**
	 * @var string The api endpoint.
	 * @since 3.0.0
	 */
	protected $endpoint = 'wpcw-api';

	/**
	 * @var string The actions api endpoint.
	 * @since 4.3.0
	 */
	protected $action_api_prefix = 'wpcw_api';

	/**
	 * Load Api.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		// Standard Endpoint Api.
		add_filter( 'query_vars', array( $this, 'add_api_query_vars' ), 0 );
		add_action( 'init', array( $this, 'add_api_endpoint' ), 0 );
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );

		// Actions API: Legacy
		add_action( 'init', array( $this, 'register_actions_api' ), 5 );
		add_action( 'init', array( $this, 'process_actions_api' ), 10 );

		// REST Api
		add_action( 'rest_api_init', array( $this, 'register_rest_api_endpoints' ) );
	}

	/**
	 * API: Add Query Vars.
	 *
	 * @since 4.3.0
	 *
	 * @param array $vars The query vars.
	 *
	 * @return array $vars The modified query vars.
	 */
	public function add_api_query_vars( $vars ) {
		$vars[] = $this->endpoint;

		return $vars;
	}

	/**
	 * API: Add Endpoint.
	 *
	 * @since 4.3.0
	 */
	public function add_api_endpoint() {
		add_rewrite_endpoint( $this->endpoint, EP_ALL );
	}

	/**
	 * API: Handle Requests.
	 *
	 * @since 4.3.0
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET[ $this->endpoint ] ) ) {
			$wp->query_vars[ $this->endpoint ] = sanitize_key( wp_unslash( $_GET[ $this->endpoint ] ) );
		}

		if ( ! empty( $wp->query_vars[ $this->endpoint ] ) ) {
			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			wpcw_nocache_headers();

			// Clean the API request.
			$api_request = strtolower( wpcw_clean( $wp->query_vars[ $this->endpoint ] ) );

			// Trigger generic action before request hook.
			do_action( 'wpcw_api_request', $api_request );

			// Status Header.
			$status_header = has_action( 'wpcw_api_' . $api_request ) ? 200 : 400;

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
			status_header( $status_header );

			// Trigger an action which plugins can hook into to fulfill the request.
			do_action( 'wpcw_api_' . $api_request );

			// Done, clear buffer and exit.
			ob_end_clean();

			// Display a die message.
			wp_die(
				esc_html__( 'This action for this api endpoint does not exist. Please try again.', 'wp-courseware' ),
				esc_html__( 'Api Error', 'wp-courseware' ),
				array( 'response' => $status_header, 'back_link' => true )
			);
		}
	}

	/**
	 * API: Get Api URL.
	 *
	 * @since 4.3.0
	 *
	 * @param string $request The api request.
	 * @param string $ssl The ssl flag.
	 *
	 * @return string The api request url.
	 */
	public function get_api_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$api_request_url = trailingslashit( home_url( '/index.php/wpcw-api/' . $request, $scheme ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$api_request_url = trailingslashit( home_url( '/wpcw-api/' . $request, $scheme ) );
		} else {
			$api_request_url = add_query_arg( 'wpcw-api', $request, trailingslashit( home_url( '', $scheme ) ) );
		}

		$api_request_url = wpcw_maybe_change_home_url( $api_request_url );

		return esc_url_raw( apply_filters( 'wpcw_api_request_url', $api_request_url, $request, $ssl ) );
	}

	/**
	 * ACTIONS Api: Register.
	 *
	 * @since 4.1.0
	 */
	public function register_actions_api() {
		/**
		 * Register Api Actions Hook.
		 *
		 * @since 4.1.0
		 *
		 * @param string $action_api_prefix
		 * @param object Api The api object.
		 */
		do_action( 'wpcw_api_actions', $this->action_api_prefix, $this );
	}

	/**
	 * ACTIONS Api: Process.
	 *
	 * @since 4.1.0
	 */
	public function process_actions_api() {
		if ( $post_action = wpcw_post_var( "{$this->action_api_prefix}_action" ) ) {
			do_action( "{$this->action_api_prefix}_{$post_action}", $_POST );
		}

		if ( $get_action = wpcw_get_var( "{$this->action_api_prefix}_action" ) ) {
			do_action( "{$this->action_api_prefix}_{$get_action}", $_GET );
		}
	}

	/**
	 * ACTIONS Api: Get Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_actions_api_url() {
		return esc_url( site_url( '/' ) );
	}

	/**
	 * ACTIONS Api: Get Nonce.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_actions_api_nonce() {
		return wp_create_nonce( 'wpcw-api-actions' );
	}

	/**
	 * REST Api: Get Namespace.
	 *
	 * @since 4.1.0
	 *
	 * @return string The namespace.
	 */
	public function get_rest_api_namespace() {
		return $this->namespace;
	}

	/**
	 * REST Api: Get Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The api url.
	 */
	public function get_rest_api_url() {
		return esc_url_raw( rest_url( $this->namespace . '/api/' ) );
	}

	/**
	 * REST Api: Get Nonce
	 *
	 * @since 4.1.0
	 *
	 * @return string The api nonce.
	 */
	public function get_rest_api_nonce() {
		return wp_create_nonce( 'wp_rest' );
	}

	/**
	 * REST Api: Get Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @return array The api endpoints.
	 */
	public function get_rest_api_endpoints() {
		/**
		 * Register Api Endpoints.
		 *
		 * @since 4.1.0
		 *
		 * @param array The API Endpoints.
		 *
		 * array(
		 *     'endpoint' => 'endpoint',
		 *     'method'   => 'GET|POST'
		 *     'callback' => 'some_callback_method'
		 * )
		 */
		return apply_filters( 'wpcw_api_endoints', array(), $this );
	}

	/**
	 * REST Api: Register Endpoints
	 *
	 * @since 4.1.0
	 */
	public function register_rest_api_endpoints() {
		foreach ( $this->get_rest_api_endpoints() as $endpoint ) {
			$this->register_rest_api_endpoint(
				$endpoint['endpoint'],
				$endpoint['callback'],
				$endpoint['method'],
				isset( $endpoint['permission_callback'] ) ? $endpoint['permission_callback'] : '',
				isset( $endpoint['frontend'] ) ? $endpoint['frontend'] : false
			);
		}
	}

	/**
	 * REST Api: Register Endpoint.
	 *
	 * @since 4.1.0
	 *
	 * @param string $endpoint
	 * @param string $callback
	 * @param string $method
	 */
	public function register_rest_api_endpoint( $endpoint, $callback, $method = 'GET', $permission_callback = '', $frontend = false ) {
		if ( empty( $permission_callback ) && ! $frontend ) {
			$permission_callback = array( $this, 'rest_api_auth' );
		} elseif ( empty( $permission_callback ) && $frontend ) {
			$permission_callback = array( $this, 'rest_api_auth_frontend' );
		}

		register_rest_route( $this->namespace, '/api/' . $endpoint, array(
			'methods'             => $method,
			'callback'            => $callback,
			'permission_callback' => $permission_callback,
		) );
	}

	/**
	 * REST Api: Permissions Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function rest_api_auth( WP_REST_Request $request ) {
		if ( ! current_user_can( 'view_wpcw_courses' ) ) {
			return new WP_Error(
				'api_access_forbidden',
				esc_html__( 'You are not able to access this api endpoint without proper permission.', 'wp-courseware' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * REST Api: Frontend Request Auth.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function rest_api_auth_frontend( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_wp_nonce' );

		return wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
