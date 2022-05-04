<?php
/**
 * WP Courseware Tracker.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.4.0
 */
namespace WPCW\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPCW\Gateways\Gateway;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Analytics.
 *
 * @since 4.4.0
 */
final class Tracker {

	/**
	 * @var string Api Url.
	 * @since 4.4.0
	 */
	private $api_url = 'https://analytics.flyplugins.com/api/v1/data';

	/**
	 * Load Tracker.
	 *
	 * @since 4.4.0
	 */
	public function load() {
		add_action( 'wpcw_tracker_send_initial_checkin', array( $this, 'maybe_send_initial_checkin' ) );
		add_action( 'wpcw_weekly_cron', array( $this, 'maybe_send_checkin' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
		add_filter( 'http_request_host_is_external', array( $this, 'http_request_host_is_external' ), 10, 3 );
	}

	/** Settings Methods ---------------------------------------------- */

	/**
	 * Get Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The license settings fields.
	 */
	public function get_settings_fields() {
		return apply_filters( 'wpcw_tracker_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'tracker_section_settings_heading',
				'title' => esc_html__( 'Tracking', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to anonymouse usage tracking of plugin data.', 'wp-courseware' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'tracking',
				'title'    => esc_html__( 'Allow Usage Tracking?', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Allow WP Courseware to anonymously track how this plugin is used and help us make the plugin better.', 'wp-courseware' ),
				'label'    => esc_html__( 'Allow anonymous usage tracking to help us make the plugin better.', 'wp-courseware' ),
				'default'  => 'no',
			),
		) );
	}

	/** Cron Methods --------------------------------------------------- */

	/**
	 * Maybe Send Initial Checkin.
	 *
	 * @since 4.4.0
	 */
	public function maybe_send_initial_checkin() {
		// Define Sensitive Data Keys.
		$sensitive_data_keys = array(
			'email',
		);

		// Get Tracking Data.
		$data = $this->get_tracking_data();

		// Remove Sensitive Data.
		foreach ( $sensitive_data_keys as $sensitive_data_key ) {
			if ( isset( $data[ $sensitive_data_key ] ) ) {
				unset( $data[ $sensitive_data_key ] );
			}
		}

		// Send Checkin
		$this->send_checkin( $data );
	}

	/**
	 * Manually Send Checkin.
	 *
	 * @since 4.4.0
	 */
	public function manually_send_checkin() {
		// Define Sensitive Data Keys.
		$sensitive_data_keys = array(
			'email',
		);

		// Get Tracking Data.
		$data = $this->get_tracking_data();

		// Remove Sensitive Data.
		foreach ( $sensitive_data_keys as $sensitive_data_key ) {
			if ( isset( $data[ $sensitive_data_key ] ) ) {
				unset( $data[ $sensitive_data_key ] );
			}
		}

		// Send Checkin
		return $this->send_checkin( $data );
	}

	/**
	 * Maybe Send Checkin.
	 *
	 * @since 4.4.0
	 *
	 * @param boolean $override Should override?.
	 */
	public function maybe_send_checkin( $override = false ) {
		// Don't trigger this on AJAX Requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// If not doing cron, get out.
		if ( ! wpcw_is_doing_cron() ) {
			return;
		}

		// Is tracking allowed?
		if ( ! $this->is_tracking_allowed() ) {
			return;
		}

		/**
		 * Filter: Tracker Disabled Send.
		 *
		 * @since 4.4.0
		 *
		 * @param bool True to disabled tracker send. Default is false.
		 *
		 * @return bool True of false to disable tracker send. Default is false.
		 */
		$disable_send = apply_filters( 'wpcw_tracker_disable_send', false );

		// Allows us to stop our own site from checking in, and a filter for our additional sites
		if ( trailingslashit( home_url() ) === 'https://flyplugins.com/' || $disable_send ) {
			return;
		}

		/**
		 * Filter: Tracker Override Send.
		 *
		 * @since 4.4.0
		 *
		 * @param bool $override Override the tracker send. Default is false.
		 *
		 * @return bool $override Override the tracker send. Default is false.
		 */
		$override_send = apply_filters( 'wpcw_tracker_override_send', $override );

		if ( ! $override_send ) {
			// Send a maximum of once per week by default.
			$last_send = $this->get_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'wpcw_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
			$last_send = $this->get_last_send_time();
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		// Update time first before sending to ensure it is set.
		update_option( 'wpcw_tracker_last_send', time() );

		// Get Tracking Data.
		$data = $this->get_tracking_data();

		// Send Checkin
		$this->send_checkin( $data );
	}

	/**
	 * Send Checkin
	 *
	 * @since 4.4.0
	 *
	 * @param array $data The data to send.
	 */
	private function send_checkin( $data ) {
		// Override if necessary.
		if ( defined( 'WPCW_LOCAL_TRACKER_API_URL' ) && WPCW_LOCAL_TRACKER_API_URL ) {
			$this->api_url = esc_url_raw( WPCW_LOCAL_TRACKER_API_URL );
		}

		// Remote Send.
		return wp_safe_remote_post( $this->api_url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => false,
			'headers'     => array( 'user-agent' => 'WPCWTracker/' . md5( esc_url_raw( home_url( '/' ) ) ) . ';' ),
			'body'        => array( 'data' => wp_json_encode( $data ) ),
			'cookies'     => array(),
		) );
	}

	/**
	 * Get Last Send Time.
	 *
	 * @since 4.4.0
	 *
	 * @return int|bool
	 */
	private function get_last_send_time() {
		return apply_filters( 'wpcw_tracker_last_send_time', get_option( 'wpcw_tracker_last_send', false ) );
	}

	/** Conditional Methods -------------------------------------------- */

	/**
	 * Is Tracking Allowed?
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function is_tracking_allowed() {
		return 'yes' === wpcw_get_setting( 'tracking', 'no' ) ? true : false;
	}

	/**
	 * Is Notice Hidden?
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if notice is hidden. False otherwise.
	 */
	public function is_notice_hidden() {
		return (bool) get_option( 'wpcw_tracker_notice', false );
	}

	/** Notice Methods ------------------------------------------------ */

	/**
	 * Tracker Admin Notices.
	 *
	 * @since 4.4.0
	 */
	public function admin_notices() {
		// Has notice already been dismissed?
		if ( $this->is_notice_hidden() ) {
			return;
		}

		// Is tracking already allowed?
		if ( $this->is_tracking_allowed() ) {
			return;
		}

		// Check Admin.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check for Allowed Page
		if ( ! wpcw()->admin->is_allowed_page() ) {
			return;
		}

		// Tracker Notice
		echo wpcw_admin_get_view( 'tracker/tracker-notice' );
		echo '<div id="wpcw-tracker">';
		echo '<wpcw-tracker-notice></wpcw-tracker-notice>';
		echo '</div>';
	}

	/**
	 * Hide Admin Tracker Notice.
	 *
	 * @since 4.4.0
	 */
	public function hide_notice() {
		return update_option( 'wpcw_tracker_notice', true );
	}

	/** API Methods -------------------------------------------------- */

	/**
	 * Register Tracker Api Endpoints.
	 *
	 * @since 4.4.0
	 *
	 * @param array $endpoints The existing endpoints.
	 * @param Api The api reference object.
	 *
	 * @return array $endpoints The modified endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'tracker', 'method' => 'POST', 'callback' => array( $this, 'api_tracker' ) );

		return $endpoints;
	}

	/**
	 * Api Tracker Callback
	 *
	 * @since 4.4.0
	 *
	 * @param object WP_REST_Request $request The api request.
	 *
	 * @return WP_REST_Response|WP_Error The api response.
	 */
	public function api_tracker( WP_REST_Request $request ) {
		$action = $request->get_param( 'action' );

		if ( ! in_array( $action, array( 'allow', 'disallow' ) ) ) {
			$notice = sprintf( '<div class="wpcw-tracker-error">%s</div>', esc_html__( 'Whoops! Something wen\'t wrong. Please refresh your page and try again.', 'wp-courseware' ) );

			return rest_ensure_response( array( 'success' => false, 'error' => $notice ) );
		}

		switch ( $action ) {
			case 'allow' :
				wpcw_update_setting( 'tracking', 'yes' );
				break;
			case 'disallow' :
				wpcw_update_setting( 'tracking', 'no' );
				break;
		}

		$this->hide_notice();

		return rest_ensure_response( array( 'success' => true ) );
	}

	/** Data Methods -------------------------------------------------- */

	/**
	 * Get Tracking Data.
	 *
	 * @since 4.4.0
	 *
	 * @return array $data The tracking data as an array.
	 */
	private function get_tracking_data() {
		$data = array();

		// General Data.
		$data['url']   = home_url();
		$data['email'] = apply_filters( 'wpcw_tracker_admin_email', get_option( 'admin_email' ) );

		// Theme Data.
		$data['theme'] = $this->get_theme_data();

		// WordPress Data.
		$data['wp'] = $this->get_wordpress_data();

		// Server Data.
		$data['server'] = $this->get_server_data();

		// Plugin Data.
		$all_plugins              = $this->get_all_plugins();
		$data['active_plugins']   = $all_plugins['active_plugins'];
		$data['inactive_plugins'] = $all_plugins['inactive_plugins'];

		// Users
		$data['users'] = $this->get_users_count();

		// Core Object Counts
		$data['courses']   = $this->get_courses_count();
		$data['modules']   = $this->get_modules_count();
		$data['units']     = $this->get_units_count();
		$data['quizzes']   = $this->get_quizzes_count();
		$data['questions'] = $this->get_questions_count();

		// Reports Data.
		$reports_data          = $this->get_reports_data();
		$data['students']      = ! empty( $reports_data['students'] ) ? $reports_data['students'] : array();
		$data['orders']        = ! empty( $reports_data['orders'] ) ? $reports_data['orders'] : array();
		$data['subscriptions'] = ! empty( $reports_data['subscriptions'] ) ? $reports_data['subscriptions'] : array();
		$data['sales']         = ! empty( $reports_data['sales'] ) ? $reports_data['sales'] : array();

		// Payment Gateways
		$data['gateways'] = $this->get_active_payment_gateways();

		// Settings
		$data['settings'] = $this->get_settings();

		// License
		$data['license'] = $this->get_license_key();

		// Sold by
		$data['soldby'] = $this->get_sold_by();

		/**
		 * Filter: Tracker Data.
		 *
		 * @since 4.4.0
		 *
		 * @param array   $data The tracker data.
		 * @param Tracker $this The tracker object.
		 *
		 * @return array $data The tracker data.
		 */
		return apply_filters( 'wpcw_tracker_data', $data, $this );
	}

	/**
	 * Get Theme Data.
	 *
	 * @since 4.4.0
	 *
	 * @return array The theme data.
	 */
	private function get_theme_data() {
		$theme_data        = wp_get_theme();
		$theme_child_theme = wpcw_bool_to_string( is_child_theme() );

		return array(
			'name'        => $theme_data->Name,
			'version'     => $theme_data->Version,
			'child_theme' => $theme_child_theme,
		);
	}

	/**
	 * Get WordPress Data.
	 *
	 * @since 4.4.0
	 *
	 * @return array The WordPress data.
	 */
	private function get_wordpress_data() {
		$wp_data = array();

		$memory = wpcw_let_to_num( WP_MEMORY_LIMIT );

		if ( function_exists( 'memory_get_usage' ) ) {
			$system_memory = wpcw_let_to_num( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}

		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['version']      = get_bloginfo( 'version' );
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}

	/**
	 * Get Server Data.
	 *
	 * @since 4.4.0
	 *
	 * @return array The server data.
	 */
	private function get_server_data() {
		$server_data = array();

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE'];
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data['php_version'] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) {
			$server_data['php_post_max_size']  = size_format( wpcw_let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt']      = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin']        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		$database_version             = wpcw_get_server_database_version();
		$server_data['mysql_version'] = $database_version['number'];

		$server_data['php_max_upload_size']  = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap']             = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data['php_fsockopen']        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl']             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Get All Plugins.
	 *
	 * Group into 'active' and 'not active'
	 *
	 * @since 4.4.0
	 *
	 * @return array The plugins data.
	 */
	private function get_all_plugins() {
		// Ensure get_plugins function is loaded.
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins             = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins      = array();

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted         = array();
			$formatted['name'] = strip_tags( $v['Name'] );
			if ( isset( $v['Version'] ) ) {
				$formatted['version'] = strip_tags( $v['Version'] );
			}
			if ( isset( $v['Author'] ) ) {
				$formatted['author'] = strip_tags( $v['Author'] );
			}
			if ( isset( $v['Network'] ) ) {
				$formatted['network'] = strip_tags( $v['Network'] );
			}
			if ( isset( $v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = strip_tags( $v['PluginURI'] );
			}
			if ( in_array( $k, $active_plugins_keys ) ) {
				// Remove active plugins from list so we can show active and inactive separately.
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}

		return array(
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => $plugins,
		);
	}

	/**
	 * Get Users Count
	 *
	 * @since 4.4.0
	 *
	 * @return array The users count.
	 */
	private function get_users_count() {
		$user_count          = array();
		$user_count_data     = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role.
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}

		return $user_count;
	}

	/**
	 * Get Courses Count.
	 *
	 * @since 4.4.0
	 *
	 * @return int The courses count as an interger.
	 */
	private function get_courses_count() {
		return wpcw()->courses->get_courses_count( array( 'number' => - 1 ) );
	}

	/**
	 * Get Modules Count.
	 *
	 * @since 4.4.0
	 *
	 * @return int The modules count as an interger.
	 */
	private function get_modules_count() {
		return wpcw()->modules->get_modules_count( array( 'number' => - 1 ) );
	}

	/**
	 * Get Units Count.
	 *
	 * @since 4.4.0
	 *
	 * @return int The units count as an interger.
	 */
	private function get_units_count() {
		return wpcw()->units->get_units_count( array( 'number' => - 1 ) );
	}

	/**
	 * Get Quiz Count.
	 *
	 * @since 4.4.0
	 *
	 * @return int The quiz count as an interger.
	 */
	private function get_quizzes_count() {
		return wpcw()->quizzes->get_quizzes_count( array( 'number' => - 1 ) );
	}

	/**
	 * Get Questions Count.
	 *
	 * @since 4.4.0
	 *
	 * @return int The questions count as an interger.
	 */
	private function get_questions_count() {
		return wpcw()->questions->get_questions_count( array( 'number' => - 1 ) );
	}

	/**
	 * Get Reports Data.
	 *
	 * @since 4.4.0
	 *
	 * @return array The reports Data.
	 */
	private function get_reports_data() {
		return wpcw()->reports->get_reports_data( true );
	}

	/**
	 * Get Active Payment Gateways.
	 *
	 * @since 4.4.0
	 *
	 * @return array $active_gateways The active payment gateways.
	 */
	private function get_active_payment_gateways() {
		$active_gateways = array();

		$gateways = wpcw()->gateways->get_available_gateways();

		if ( $gateways ) {
			/** @var Gateway $gateway */
			foreach ( $gateways as $id => $gateway ) {
				$active_gateways[ $id ] = array(
					'slug'     => $gateway->get_slug(),
					'title'    => $gateway->get_title(),
					'supports' => $gateway->get_supported_features(),
				);
			}
		}

		return $active_gateways;
	}

	/**
	 * Get All Settings.
	 *
	 * @since 4.4.0
	 *
	 * @return array $settings All the plugin settings.
	 */
	private function get_settings() {
		return array(
			'version'              => WPCW_VERSION,
			'db_version'           => WPCW_DB_VERSION,
			'currency'             => wpcw_get_currency(),
			'taxes_enabled'        => wpcw_taxes_enabled() ? 'Yes' : 'No',
			'tax_percent'          => wpcw_get_tax_percentage(),
			'secure_checkout'      => wpcw_get_setting( 'force_ssl' ),
			'privacy_enabled'      => wpcw_get_setting( 'privacy_policy' ),
			'show_powered_by'      => wpcw_get_setting( 'show_powered_by' ),
			'use_default_css'      => wpcw_get_setting( 'use_default_css' ),
			'customize_colors'     => wpcw_get_setting( 'customize_colors' ),
			'enable_unit_comments' => wpcw_get_setting( 'enable_unit_comments' ),
			'cron_time_dripfeed'   => wpcw_get_setting( 'cron_time_dripfeed' ),
			'permalinks'           => wpcw_get_permalink_structure(),
		);
	}

	/**
	 * Get License Key.
	 *
	 * @since 4.4.0
	 * @return bool|string
	 */
	private function get_license_key() {
		return wpcw()->license->get_key();
	}

	/**
	 * Get Sold By.
	 *
	 * @since 4.4.0
	 *
	 * @return string The sold by name.
	 */
	private function get_sold_by() {
		return wpcw()->get_company_name();
	}

	/** Misc Methods -------------------------------------------------- */

	/**
	 * Reset Tracking Optin.
	 *
	 * @since 4.4.0
	 */
	public function reset_tracking_opt_in() {
		wpcw_update_setting( 'tracking', 'no' );
		delete_option( 'wpcw_tracker_last_send' );
		delete_option( 'wpcw_tracker_notice' );
	}

	/**
	 * Is HTTP Request External.
	 *
	 * @since 4.4.0
	 *
	 * @param bool   $external Whether HTTP request is external or not. Default is false.
	 * @param string $host IP of the requested host.
	 * @param string $url URL of the requested host.
	 *
	 * @return bool Force true for local testing. Default is false.
	 */
	public function http_request_host_is_external( $external, $host, $url ) {
		return ( defined( 'WPCW_LOCAL_TESTING' ) && WPCW_LOCAL_TESTING ) ? true : $external;
	}
}
