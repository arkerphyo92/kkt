<?php
/**
 * WP Courseware License.
 *
 * @packcage WPCW
 * @subpackage Core
 * @since 4.1.0
 */
namespace WPCW\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class License.
 *
 * @since 4.3.0
 */
final class License {

	/**
	 * @var array The License Cache Keys.
	 * @since 4.1.0
	 */
	protected $cache = array( 'status' => 'wpcw_license_status' );

	/**
	 * Load License.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		add_action( 'admin_init', array( $this, 'init_license' ), 0 );
		add_action( 'admin_init', array( $this, 'check_status' ), 10 );
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Get Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The license settings fields.
	 */
	public function get_settings_fields() {
		return apply_filters( 'wpcw_license_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'license_section_settings_heading',
				'title' => esc_html__( 'License', 'wp-courseware' ),
				'desc'  => esc_html__( 'Your license, when activated, will provides access to updates and support.', 'wp-courseware' ),
			),
			array(
				'type'      => 'license',
				'key'       => 'license',
				'component' => true,
				'views'     => array( 'settings/settings-field-license' ),
				'settings'  => array(
					array(
						'key'     => 'licensekey',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'key'     => 'licensestatus',
						'type'    => 'hidden',
						'default' => 'invalid',
					),
				),
			),
		) );
	}

	/**
	 * Get License Id.
	 *
	 * @since 4.1.0
	 *
	 * @return int $license_id The license id.
	 */
	public function get_id() {
		return absint( apply_filters( 'wpcw_license_product_id', 42 ) );
	}

	/**
	 * Get License Name.
	 *
	 * @since 4.1.0
	 *
	 * @return string $license_name The license name.
	 */
	public function get_name() {
		return urlencode( esc_attr( apply_filters( 'wpcw_license_name', wpcw()->get_name() ) ) );
	}

	/**
	 * Get Author.
	 *
	 * @since 4.1.0
	 *
	 * @return string $license_author The license author.
	 */
	public function get_author() {
		return esc_attr( apply_filters( 'wpcw_license_author', wpcw()->get_company_name() ) );
	}

	/**
	 * Get Beta.
	 *
	 * @since 4.1.0
	 *
	 * @return bool $beta If the license is a beta version.
	 */
	public function get_beta() {
		return (bool) apply_filters( 'wpcw_license_beta', false );
	}

	/**
	 * Get License Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string $license_url The license url.
	 */
	public function get_url() {
		return esc_url_raw( trailingslashit( apply_filters( 'wpcw_license_url', wpcw()->get_company_url() ) . 'license-updater' ) );
	}

	/**
	 * Get License Keys Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string $license_keys_url The url to the member portal license keys.
	 */
	public function get_keys_url() {
		return esc_url_raw( apply_filters( 'wpcw_license_keys_url', wpcw()->get_member_portal_license_url() ) );
	}

	/**
	 * Get License Key.
	 *
	 * @since 4.1.0
	 *
	 * @return string|bool The license key string if exists. False if empty.
	 */
	public function get_key() {
		return trim( apply_filters( 'wpcw_license_key', wpcw_get_setting( 'licensekey' ) ) );
	}

	/**
	 * Update License Key.
	 *
	 * @since 4.1.0
	 *
	 * @param string $key The new license key.
	 *
	 * @return bool True on update, false otherwise.
	 */
	public function update_key( $key ) {
		return wpcw_update_setting( 'licensekey', trim( $key ) );
	}

	/**
	 * Delete License Key.
	 *
	 * @since 4.1.0
	 *
	 * @return bool True if it was deleted, false otherwise.
	 */
	public function delete_key() {
		return wpcw_update_setting( 'licensekey', '' );
	}

	/**
	 * Get License Status.
	 *
	 * @since 4.1.0
	 *
	 * @return stirng|bool The license status string if exists. False if empty.
	 */
	public function get_status() {
		return wpcw_get_setting( 'licensestatus' );
	}

	/**
	 * Update License Status.
	 *
	 * @since 4.1.0
	 *
	 * @param string $status The new status of the license.
	 *
	 * @return bool True if it is correctly updated, false otherwise.
	 */
	public function update_status( $status ) {
		return wpcw_update_setting( 'licensestatus', $status );
	}

	/**
	 * Get License Details
	 *
	 * @since 4.1.0
	 *
	 * @return array $license_details An array of all the details needed.
	 */
	protected function get_details() {
		return apply_filters( 'wpcw_license_details', array(
			'version'   => WPCW_VERSION,
			'license'   => $this->get_key(),
			'item_id'   => $this->get_id(),
			'item_name' => $this->get_name(),
			'author'    => $this->get_author(),
			'beta'      => $this->get_beta(),
		) );
	}

	/**
	 * Init License.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function init_license() {
		new Updater( $this->get_url(), WPCW_FILE, $this->get_details() );
	}

	/**
	 * Activate License
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function activate() {
		$activate = $this->api_request( 'activate_license' );

		if ( is_wp_error( $activate ) ) {
			$this->delete_key();
			$this->clear_cache();
			$this->cache_status( 'invalid' );

			return $activate;
		}

		$this->clear_cache();
		$this->cache_status( $activate->license );

		return $this->update_status( $activate->license );
	}

	/**
	 * Deactivate License
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function deactivate() {
		$message    = '';
		$deactivate = $this->api_request( 'deactivate_license' );

		if ( is_wp_error( $deactivate ) ) {
			$message = esc_html__( 'License successfully deactivated!', 'wp-courseware' );
		} else {
			$message = $deactivate->license;
		}

		$this->delete_key();
		$this->clear_cache();
		$this->cache_status( 'invalid' );

		return $this->update_status( $message );
	}

	/**
	 * Check License Status.
	 *
	 * @since 4.1.0
	 *
	 * @return string $status The status of the license.
	 */
	public function check_status() {
		global $pagenow;

		if ( false === ( $status = get_transient( $this->cache['status'] ) ) ) {
			$license_status = $this->api_request( 'check_license' );

			if ( is_wp_error( $license_status ) ) {
				$status = 'invalid';
			} else {
				$status = $license_status->license;
			}

			$this->cache_status( $status );
		}

		if ( 'invalid' !== $status && ! $this->get_key() ) {
			$this->clear_cache();
		}

		if ( 'invalid' === $status ) {
			/* translators: %1$s - License page url */
			$error = sprintf( __( 'Your <strong>WP Courseware</strong> license key is missing or invalid. <a href="%1$s">Please enter your license key.</a>', 'wp-courseware' ), $this->get_license_page() );
			wpcw_display_admin_notice( $error, 'error' );
		}
	}

	/**
	 * Cache Status.
	 *
	 * @since 4.1.0
	 *
	 * @param string $status The status of the plugin.
	 */
	public function cache_status( $status ) {
		set_transient( $this->cache['status'], $status, DAY_IN_SECONDS );
	}

	/**
	 * Clear Cache.
	 *
	 * @since 4.1.0
	 */
	protected function clear_cache() {
		foreach ( $this->cache as $cache_item ) {
			delete_transient( $cache_item );
		}
	}

	/**
	 * License Api Request.
	 *
	 * @since 4.1.0
	 *
	 * @param string $action The request action. Default is 'check_license'
	 *
	 * @return object|WP_Error Object of values or a WP_Error.
	 */
	protected function api_request( $action = 'check_license' ) {
		if ( empty( $action ) ) {
			return new WP_Error( 'wpcw_request_action_error', esc_html__( 'License API request action invalid.', 'wp-courseware' ) );
		}

		$api_params = array(
			'edd_action' => $action,
			'license'    => $this->get_key(),
			'item_name'  => $this->get_name(),
			'item_id'    => $this->get_id(),
			'url'        => home_url(),
		);

		$request = wp_remote_post( $this->get_url(), array(
			'timeout'   => 15,
			'sslverify' => true,
			'body'      => $api_params,
		) );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return new WP_Error( 'wpcw_license_error', esc_html__( 'An error occurred, please try again.', 'wp-courseware' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $request ) );

		if ( false === $data->success ) {
			$error = isset( $data->error ) ? $data->error : $data->license;

			return new WP_Error( 'wpcw_license_error', $this->api_get_error( $error, $data ) );
		}

		return $data;
	}

	/**
	 * Get License API Error.
	 *
	 * @since 4.1.0
	 *
	 * @param string $error The error to look up.
	 * @param object $data The license data.
	 *
	 * @return string $error_message The license error message.
	 */
	protected function api_get_error( $error, $data ) {
		$error_message = '';

		switch ( $error ) {
			case 'expired' :
				/* translators: %s - Date the license expired */
				$error_message = sprintf(
					esc_html__( 'Your license key expired on %s.', 'wp-courseware' ),
					date_i18n( get_option( 'date_format' ), strtotime( $data->expires, current_time( 'timestamp' ) ) )
				);
				break;
			case 'revoked' :
				$error_message = esc_html__( 'Your license key has been disabled.', 'wp-courseware' );
				break;
			case 'invalid' :
			case 'missing' :
				$error_message = esc_html__( 'Invalid license key. Please enter a valid license key.', 'wp-courseware' );
				break;
			case 'site_inactive' :
				$error_message = esc_html__( 'Your license is not active for this URL.', 'wp-courseware' );
				break;
			case 'item_name_mismatch' :
				/* translators: %s - Product Name */
				$error_message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wp-courseware' ), $this->get_name() );
				break;
			case 'no_activations_left':
				$error_message = esc_html__( 'Your license key has reached its activation limit.', 'wp-courseware' );
				break;
			default :
				$error_message = esc_html__( 'An error occurred, please try again.', 'wp-courseware' );
				break;
		}

		if ( $error_message ) {
			$error_message = sprintf( '<strong>%s:</strong> %s', esc_html__( 'Error', 'wp-courseware' ), $error_message );
		}

		return apply_filters( 'wpcw_license_error', $error_message, $error );
	}

	/**
	 * Get License Page.
	 *
	 * @since 4.1.0
	 *
	 * @return string $license_page_url The license page url.
	 */
	public function get_license_page() {
		return esc_url_raw( add_query_arg( array( 'page' => 'wpcw-settings', 'tab' => 'license' ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Register License Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The existing endpoints.
	 * @param Api The api reference object.
	 *
	 * @return array $endpoints The modified endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array(
			'endpoint' => 'license',
			'method'   => 'POST',
			'callback' => array( $this, 'api_license' ),
		);

		$endpoints[] = array(
			'endpoint' => 'license-clear-cache',
			'method'   => 'POST',
			'callback' => array( $this, 'api_license_clear_cache' ),
		);

		return $endpoints;
	}

	/**
	 * Api License Callback
	 *
	 * @since 4.1.0
	 *
	 * @param object WP_REST_Request $request The api request.
	 *
	 * @return WP_REST_Response|WP_Error The api response.
	 */
	public function api_license( WP_REST_Request $request ) {
		$notice = '';

		$license = $request->get_param( 'license' );
		$action  = $request->get_param( 'action' );

		if ( empty( $license ) || empty( $action ) ) {
			return new WP_Error(
				'wpcw-api-license-error',
				esc_html__( 'There was an error updating your license. Please try again.', 'wp-courseware' ),
				array( 'status' => 400 )
			);
		}

		if ( 'activate' === $action ) {
			$this->update_key( $license );

			$activate = $this->activate();

			if ( is_wp_error( $activate ) ) {
				return $activate;
			}

			$notice = esc_html__( 'License successfully activated!', 'wp-courseware' );
		}

		if ( 'deactivate' === $action ) {
			$deactivate = $this->deactivate();

			if ( is_wp_error( $deactivate ) ) {
				return $deactivate;
			}

			$notice = esc_html__( 'License successfully deactivated!', 'wp-courseware' );
		}

		$response_data = array(
			'license' => $this->get_key(),
			'status'  => $this->get_status(),
			'notice'  => $notice,
		);

		return rest_ensure_response( $response_data );
	}

	/**
	 * Api License Clear Cache Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param object WP_REST_Request $request The api request.
	 *
	 * @return WP_REST_Response|WP_Error The api response.
	 */
	public function api_license_clear_cache( WP_REST_Request $request ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", '%' . $wpdb->esc_like( 'wpcw_sl' ) . '%' ) );

		$transients_to_delete = array(
			'update_plugins',
			'plugin_slugs',
			'update_core',
			'update_themes',
			'theme_roots',
			'wpcw_license_status',
		);

		foreach ( $transients_to_delete as $transient ) {
			delete_site_transient( $transient );
			delete_transient( $transient );
		}

		$message = esc_html__( 'Plugin update cache cleared successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'message' => $message ) );
	}
}
