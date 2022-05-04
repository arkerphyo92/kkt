<?php
/**
 * WP Courseware Ajax Handler.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax.
 *
 * @since 4.3.0
 */
final class Ajax {

	/**
	 * Load Ajax Handler.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_init', array( $this, 'define_ajax' ), 0 );
		add_action( 'wpcw_init', array( $this, 'define_ajax_events' ), 1 );
		add_action( 'template_redirect', array( $this, 'do_ajax' ), 0 );
	}

	/**
	 * Set AJAX constant and headers.
	 *
	 * @since 4.3.0
	 */
	public function define_ajax() {
		if ( ! empty( $_GET['wpcw-ajax'] ) ) {
			wpcw_maybe_define_constant( 'DOING_AJAX', true );
			wpcw_maybe_define_constant( 'WPCW_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Ajax Events.
	 *
	 * @since 4.3.0
	 */
	public function define_ajax_events() {
		// Ajax Event => Execute on Frontend.
		$ajax_events = apply_filters( 'wpcw_ajax_api_events', array() );

		if ( empty( $ajax_events ) ) {
			return;
		}

		foreach ( $ajax_events as $ajax_event => $callback ) {
			if ( is_callable( $callback ) ) {
				add_action( 'wpcw_ajax_' . $ajax_event, $callback );
			}
		}
	}

	/**
	 * Send headers for Ajax Requests.
	 *
	 * @since 4.3.0
	 */
	private function ajax_headers() {
		send_origin_headers();
		send_nosniff_header();
		wpcw_nocache_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		status_header( 200 );
	}

	/**
	 * Check for Ajax Request and Dispatch Action.
	 *
	 * @since 4.3.0
	 */
	public function do_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['wpcw-ajax'] ) ) {
			$wp_query->set( 'wpcw-ajax', sanitize_text_field( $_GET['wpcw-ajax'] ) );
		}

		if ( $action = $wp_query->get( 'wpcw-ajax' ) ) {
			$this->ajax_headers();
			do_action( 'wpcw_ajax_' . sanitize_text_field( $action ) );
			wp_die();
		}
	}

	/**
	 * Get Ajax Api Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $request Optional
	 *
	 * @return string The ajax endpoint.
	 */
	public function get_url( $request = '' ) {
		return esc_url_raw( apply_filters( 'wpcw_ajax_get_url', add_query_arg( 'wpcw-ajax', $request, home_url( '/', 'relative' ) ), $request ) );
	}

	/**
	 * Get Ajax Api Nonce.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_nonce() {
		return wp_create_nonce( 'ajax-api-nonce' );
	}

	/**
	 * Verify Ajax Api Nonce.
	 *
	 * @since 4.3.0
	 *
	 * @param bool Whether to return the response or die. Default is false.
	 *
	 * @return false|int
	 */
	public function verify_nonce( $return = false ) {
		$die = $return ? false : true;
		return check_ajax_referer( 'ajax-api-nonce', 'security', $die );
	}
}
