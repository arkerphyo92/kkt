<?php
/**
 * WP Courseware Frontend.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */

namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend.
 *
 * @package WPCW
 * @since 4.1.0
 */
final class Frontend {

	/**
	 * Load Frontend.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_head', array( $this, 'styles' ) );
	}

	/**
	 * Frontend Assets.
	 *
	 * @since 4.3.0
	 *
	 * @param string $hook The page hook.
	 */
	public function assets( $hook ) {
		$use_default_css = wpcw_get_setting( 'use_default_css' );

		if ( 'yes' === $use_default_css ) {
			wp_enqueue_style( 'wpcw-frontend', wpcw_css_file( 'frontend.css' ), false, WPCW_VERSION );
		}

		$ssl = is_ssl() ? 'https' : 'http';

		wp_enqueue_script( 'wpcw-countdown', wpcw_js_file( 'countdown.js' ), array( 'jquery' ), WPCW_VERSION, true );
		wp_enqueue_script( 'wpcw-jquery-form', wpcw_js_file( 'form.js' ), array( 'jquery' ), WPCW_VERSION, true );
		wp_enqueue_script( 'wpcw-frontend', wpcw_js_file( 'frontend.js' ), array( 'jquery', 'wpcw-countdown', 'wpcw-jquery-form' ), WPCW_VERSION, true );
		wp_localize_script( 'wpcw-frontend', 'wpcw_frontend_params', array(
			'api_url'               => wpcw()->api->get_rest_api_url(),
			'api_nonce'             => wpcw()->api->get_rest_api_nonce(),
			'ajax_api_url'          => wpcw()->ajax->get_url( '%%endpoint%%' ),
			'ajax_api_nonce'        => wpcw()->ajax->get_nonce(),
			'ajaxurl'               => admin_url( 'admin-ajax.php', $ssl ),
			'enrollment_nonce'      => wp_create_nonce( 'wpcw-enrollment-nonce' ),
			'progress_nonce'        => wp_create_nonce( 'wpcw-progress-nonce' ),
			'str_uploading'         => esc_html__( 'Uploading:', 'wp-courseware' ),
			'str_quiz_all_fields'   => esc_html__( 'Please provide an answer for all of the questions on this page.', 'wp-courseware' ),
			'timer_units_hrs'       => esc_html__( 'hrs', 'wp-courseware' ),
			'timer_units_mins'      => esc_html__( 'mins', 'wp-courseware' ),
			'timer_units_secs'      => esc_html__( 'secs', 'wp-courseware' ),
			'min_password_strength' => apply_filters( 'wpcw_min_password_strength', 3 ),
			'i18n_password_error'   => esc_attr__( 'Please enter a stronger password.', 'wp-courseware' ),
			'i18n_password_hint'    => esc_attr( wp_get_password_hint() ),
			'course_progress'       => array(
				'i18n_confirm_reset_unit'  => esc_html__( 'Are you sure you wish to reset your progress of this Unit? This CANNOT be undone.', 'wp-courseware' ),
				'i18n_confirm_reset_units' => esc_html__( 'Are you sure you wish to reset your progress up to this Unit? This CANNOT be undone.', 'wp-courseware' )
			)
		) );

		wp_register_style( 'wpcw-checkout', wpcw_css_file( 'checkout.css' ), false, WPCW_VERSION );
		wp_register_script( 'wpcw-checkout', wpcw_js_file( 'checkout.js' ), array( 'jquery', 'password-strength-meter' ), WPCW_VERSION, true );
		wp_localize_script( 'wpcw-checkout', 'wpcw_checkout_params', array(
			'api_url'                   => wpcw()->api->get_rest_api_url(),
			'api_nonce'                 => wpcw()->api->get_rest_api_nonce(),
			'ajax_api_url'              => wpcw()->ajax->get_url( '%%endpoint%%' ),
			'ajax_api_nonce'            => wpcw()->ajax->get_nonce(),
			'countries'                 => json_encode( wpcw()->countries->get_allowed_country_states() ),
			'i18n_checkout_error'       => esc_attr__( 'Error processing checkout. Please try again.', 'wp-courseware' ),
			'i18n_select_state_text'    => esc_attr__( 'Select an option&hellip;', 'wp-courseware' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'wp-courseware' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'wp-courseware' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'wp-courseware' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'wp-courseware' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'wp-courseware' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'wp-courseware' ),
		) );

		wp_register_script( 'wpcw-account', wpcw_js_file( 'account.js' ), array( 'jquery' ), WPCW_VERSION, true );
		wp_localize_script( 'wpcw-account', 'wpcw_account_params', array(
			'api_url'                   => wpcw()->api->get_rest_api_url(),
			'api_nonce'                 => wpcw()->api->get_rest_api_nonce(),
			'ajax_api_url'              => wpcw()->ajax->get_url( '%%endpoint%%' ),
			'ajax_api_nonce'            => wpcw()->ajax->get_nonce(),
			'countries'                 => json_encode( wpcw()->countries->get_allowed_country_states() ),
			'i18n_select_state_text'    => esc_attr__( 'Select an option&hellip;', 'wp-courseware' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'wp-courseware' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'wp-courseware' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'wp-courseware' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'wp-courseware' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'wp-courseware' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'wp-courseware' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'wp-courseware' ),
		) );

		if ( wpcw_is_checkout() && ! apply_filters( 'wpcw_checkout_disable_scripts', false ) ) {
			wp_enqueue_style( 'wpcw-checkout' );
			wp_enqueue_script( 'wpcw-checkout' );
		}

		if ( wpcw_is_edit_account_page() ) {
			wp_enqueue_script( 'wpcw-account' );
		}

		/**
		 * Action: Enqueue Frontend Scripts.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_frontend_enqueue_scripts' );
	}

	/**
	 * Front End Stles.
	 *
	 * @since 4.2.0
	 */
	public function styles() {
		$use_stylesheet = wpcw()->settings->get_setting( 'use_default_css', 'yes' );

		if ( 'no' === $use_stylesheet ) {
			return;
		}

		$styles = '';

		$customize_colors = wpcw()->settings->get_setting( 'customize_colors', 'yes' );

		if ( 'yes' === $customize_colors ) {
			$color_settings = wpcw()->styles->get_colors();

			if ( ! empty( $color_settings ) ) {
				foreach ( $color_settings as $id => $color_setting ) {
					if ( ! empty( $color_setting['settings'] ) ) {
						foreach ( $color_setting['settings'] as $setting => $args ) {
							$default   = ! empty( $args['default'] ) ? $args['default'] : '';
							$value     = wpcw()->settings->get_setting( $setting, $default );
							$selector  = ! empty( $args['selector'] ) ? esc_attr( $args['selector'] ) : '';
							$attribute = ! empty( $args['attribute'] ) ? esc_attr( $args['attribute'] ) : '';
							$important = isset( $args['important'] ) && $args['important'] ? ' !important' : '';
							$hover     = isset( $args['hover'] ) && $args['hover'] ? true : false;

							if ( $default === $value || ! $selector || ! $attribute ) {
								continue;
							}
							if ( $hover ) {
								$selector = explode( ',', $selector );
								if ( is_array( $selector ) ) {
									foreach ( $selector as $selector_item ) {
										$hselector = "{$selector_item}:hover,{$selector_item}:focus,{$selector_item}:active";
										$styles    .= sprintf( '%s{%s:%s%s;}', $hselector, $attribute, $value, $important );
									}
								} else {
									$hselector = "{$selector}:hover,{$selector}:focus,{$selector}:active";
									$styles    .= sprintf( '%s{%s:%s%s;}', $hselector, $attribute, $value, $important );
								}
							} else {
								$styles .= sprintf( '%s{%s:%s%s;}', $selector, $attribute, $value, $important );
							}
						}
					}
				}
			}
		}

		if ( ! empty( $styles ) ) {
			printf( '<style type="text/css">%s</style>', wpcw_strip( $styles ) );
		}
	}
}
