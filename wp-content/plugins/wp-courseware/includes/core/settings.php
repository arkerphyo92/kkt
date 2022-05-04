<?php
/**
 * WP Courseware Settings.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.1.0
 */
namespace WPCW\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPCW\Common\Settings_Api;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 *
 * Class for all settings functionality throughout the plugin.
 *
 * @since 4.3.0
 */
final class Settings extends Settings_Api {

	/**
	 * Load Settings.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		add_action( 'wpcw_loaded', array( $this, 'register_settings' ), 0 );
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Register Settings.
	 *
	 * @since 4.3.0
	 */
	public function register_settings() {
		// Set Settings Fields.
		$this->set_fields();

		// Load Settings.
		$this->load_settings();

		// Perform Upgrade Routine.
		$this->maybe_upgrade();
	}

	/**
	 * Set Settings Fields.
	 *
	 * @since 4.3.0
	 */
	public function set_fields() {
		$license_fields      = wpcw()->license->get_settings_fields();
		$courses_fields      = wpcw()->courses->get_settings_fields();
		$units_fields        = wpcw()->units->get_settings_fields();
		$students_fields     = wpcw()->students->get_settings_fields();
		$certificates_fields = wpcw()->certificates->get_settings_fields();
		$styles_fields       = wpcw()->styles->get_settings_fields();
		$checkout_fields     = wpcw()->checkout->get_settings_fields();
		$emails_fields       = wpcw()->emails->get_settings_fields();
		$tracker_fields      = wpcw()->tracker->get_settings_fields();

		$this->fields = array_merge(
			$license_fields,
			$courses_fields,
			$units_fields,
			$students_fields,
			$certificates_fields,
			$styles_fields,
			$checkout_fields,
			$emails_fields,
			$tracker_fields
		);
	}

	/**
	 * Maybe Upgrade Settings.
	 *
	 * @since 4.3.0
	 */
	protected function maybe_upgrade() {
		if ( get_option( 'wpcw_upgrade_to_4_1_0' ) ) {
			return;
		}

		if ( ! $wpcw_old_settings = get_option( 'WPCW_Settings' ) ) {
			update_option( 'wpcw_upgrade_to_4_1_0', true );

			return;
		}

		$wpcw_old_settings = maybe_unserialize( $wpcw_old_settings );

		$settings_map = array(
			'licence_key'                => 'licensekey',
			'enable_unit_comments'       => 'enable_unit_comments',
			'use_default_css'            => 'use_default_css',
			'cron_time_dripfeed'         => 'cron_time_dripfeed',
			'show_powered_by'            => 'show_powered_by',
			'affiliate_id'               => 'affiliate_id',
			'cert_signature_type'        => 'cert_signature_type',
			'cert_sig_text'              => 'cert_sig_text',
			'cert_sig_image_url'         => 'cert_sig_image_url',
			'cert_logo_enabled'          => 'cert_logo_enabled',
			'cert_logo_url'              => 'cert_logo_url',
			'cert_background_type'       => 'cert_background_type',
			'cert_background_custom_url' => 'cert_background_custom_url',
			'certificate_encoding'       => 'certificate_encoding',
		);

		foreach ( $settings_map as $old_setting_key => $new_setting_key ) {
			$old_setting = isset( $wpcw_old_settings[ $old_setting_key ] ) ? $wpcw_old_settings[ $old_setting_key ] : false;

			if ( ! $old_setting ) {
				continue;
			}

			switch ( $old_setting_key ) {
				case 'show_powered_by' :
					if ( $old_setting === 'show_link' ) {
						$this->set_setting( $new_setting_key, 'yes' );
					} else {
						$this->set_setting( $new_setting_key, 'no' );
					}
					break;
				case 'use_default_css' :
					if ( $old_setting === 'show_css' ) {
						$this->set_setting( $new_setting_key, 'yes' );
					} else {
						$this->set_setting( $new_setting_key, 'no' );
					}
					break;
				case 'enable_unit_comments' :
					if ( $old_setting === 'enable_comments' ) {
						$this->set_setting( $new_setting_key, 'yes' );
					} else {
						$this->set_setting( $new_setting_key, 'no' );
					}
					break;
				default:
					$this->set_setting( $new_setting_key, $old_setting );
					break;
			}
		}

		// Save Settings.
		$this->save_settings();

		// Activate license.
		wpcw()->license->activate();

		// Update Options.
		delete_option( 'WPCW_Version' );
		update_option( 'wpcw_plugin_version', '4.1.3' );
		update_option( 'wpcw_db_version', '4.1.3' );
		update_option( 'wpcw_upgrade_to_4_1_0', true );
	}

	/**
	 * Register Settings Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object reference.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'settings', 'method' => 'GET', 'callback' => array( $this, 'api_get_settings' ) );
		$endpoints[] = array( 'endpoint' => 'settings', 'method' => 'POST', 'callback' => array( $this, 'api_save_settings' ) );
		$endpoints[] = array( 'endpoint' => 'settings-field-create-page', 'method' => 'POST', 'callback' => array( $this, 'api_settings_field_create_page' ) );

		return $endpoints;
	}

	/**
	 * Get Settings Api Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function api_get_settings( WP_REST_Request $request ) {
		$settings = $this->get_settings();

		return rest_ensure_response( array( 'settings' => $settings ) );
	}

	/**
	 * Save Settings Api Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function api_save_settings( WP_REST_Request $request ) {

		$settings = $request->get_param( 'settings' );

		if ( empty( $settings ) ) {
			return new WP_Error( 'api-settings-request-empty', esc_html__( 'Sorry, the request contained no settings to save.', 'wp-courseware' ), array( 'status' => 403 ) );
		}

		$this->set_settings( $settings );
		$this->save_settings();

		return rest_ensure_response( $saved_settings );
	}

	/**
	 * Save Settings Field Create Page.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function api_settings_field_create_page( WP_REST_Request $request ) {
		$name = $request->get_param( 'name' );

		if ( empty( $name ) ) {
			return new WP_Error( 'api-create-page-request', esc_html__( 'Sorry, the request was invalid', 'wp-courseware' ), array( 'status' => 403 ) );
		}

		$page_id = 0;

		$notice = esc_html__( 'Page created successfully!', 'wp-courseware' );

		switch ( $name ) {
			case 'courses_page' :
				$page_id = wpcw()->courses->create_courses_page();
				$notice  = esc_html__( 'Courses page created successfully!', 'wp-courseware' );
				break;
			case 'checkout_page' :
				$page_id = wpcw()->checkout->create_checkout_page();
				$notice  = esc_html__( 'Checkout page created successfully!', 'wp-courseware' );
				break;
			case 'order_received_page' :
				$page_id = wpcw()->checkout->create_checkout_order_received_page();
				$notice  = esc_html__( 'Order recieved page created successfully!', 'wp-courseware' );
				break;
			case 'order_failed_page' :
				$page_id = wpcw()->checkout->create_checkout_order_failed_page();
				$notice  = esc_html__( 'Order failed page created successfully!', 'wp-courseware' );
				break;
			case 'terms_page' :
				$page_id = wpcw()->checkout->create_checkout_terms_page();
				$notice  = esc_html__( 'Terms & Conditions page created successfully!', 'wp-courseware' );
				break;
			case 'privacy_page' :
				$page_id = wpcw()->checkout->create_checkout_privacy_page();
				$notice  = esc_html__( 'Privacy Policy page created successfully!', 'wp-courseware' );
				break;
			case 'account_page' :
				$page_id = wpcw()->students->create_students_account_page();
				$notice  = esc_html__( 'Students account page created successfully!', 'wp-courseware' );
				break;
			default :
				break;
		}

		// If the page id was created.
		if ( 0 !== $page_id ) {
			$this->set_setting( $name, absint( $page_id ) );
			$this->save_settings();
		}

		return rest_ensure_response( array(
			'id'     => absint( $page_id ),
			'title'  => htmlspecialchars_decode( get_the_title( $page_id ) ),
			'notice' => esc_html( $notice ),
		) );
	}
}
