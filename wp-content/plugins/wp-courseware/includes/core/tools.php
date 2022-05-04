<?php
/**
 * WP Courseware Tools Information.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.1.0
 */
namespace WPCW\Core;

use WP_REST_Request;
use WP_REST_Response;
use WPCW\Library\Browser;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class System.
 *
 * @since 4.1.0
 */
final class Tools {

	/**
	 * Load System.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		add_action( 'wpcw_api_actions', array( $this, 'register_api_actions' ), 10, 2 );
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Register Api Actions.
	 *
	 * @since 4.1.0
	 *
	 * @param string $prefix The Action prefix.
	 * @param Api    $api
	 *
	 * @return void
	 */
	public function register_api_actions( $prefix, $api ) {
		add_action( "{$prefix}_download_system_report", array( $this, 'api_action_download_report' ) );
	}

	/**
	 * Register Settings Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api endpoints.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'systeminfo', 'method' => 'GET', 'callback' => array( $this, 'api_get_systeminfo' ) );
		$endpoints[] = array( 'endpoint' => 'utility-clear-transients', 'method' => 'POST', 'callback' => array( $this, 'api_clear_transients' ) );
		$endpoints[] = array( 'endpoint' => 'utility-delete-orphaned-question-tags', 'method' => 'POST', 'callback' => array( $this, 'api_delete_orphaned_question_tags' ) );
		$endpoints[] = array( 'endpoint' => 'utility-delete-orphaned-units', 'method' => 'POST', 'callback' => array( $this, 'api_delete_orphaned_units' ) );
		$endpoints[] = array( 'endpoint' => 'utility-manually-upgrade-courses', 'method' => 'POST', 'callback' => array( $this, 'api_manually_upgrade_courses' ) );
		$endpoints[] = array( 'endpoint' => 'utility-reset-roles', 'method' => 'POST', 'callback' => array( $this, 'api_reset_roles_and_caps' ) );
		$endpoints[] = array( 'endpoint' => 'utility-reset-tracking', 'method' => 'POST', 'callback' => array( $this, 'api_reset_tracking_optin' ) );
		$endpoints[] = array( 'endpoint' => 'utility-send-tracking', 'method' => 'POST', 'callback' => array( $this, 'api_send_tracking_data' ) );
		$endpoints[] = array( 'endpoint' => 'utility-run-updater', 'method' => 'POST', 'callback' => array( $this, 'api_run_updater' ) );
		$endpoints[] = array( 'endpoint' => 'utility-kill-updater', 'method' => 'POST', 'callback' => array( $this, 'api_kill_updater' ) );
		$endpoints[] = array( 'endpoint' => 'utility-fix-database', 'method' => 'POST', 'callback' => array( $this, 'api_fix_database' ) );
		$endpoints[] = array( 'endpoint' => 'utility-fix-discounts', 'method' => 'POST', 'callback' => array( $this, 'api_fix_discounts' ) );

		return $endpoints;
	}

	/**
	 * Api Download Report Action Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param array $data The GET/POST data.
	 */
	public function api_action_download_report( $data ) {
		if ( ! wp_verify_nonce( $data['api_action_nonce'], 'wpcw-api-actions' ) ) {
			wp_die( __( '<strong>Error:</strong> Sorry! You are not able to perform this action.', 'wp-courseware' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( __( '<strong>Error:</strong> Sorry! You are not able to perform this action.', 'wp-courseware' ) );
		}

		$system_report = $this->get_report();

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="wp-courseware-system-info-report.txt"' );

		echo wp_strip_all_tags( $system_report );

		die();
	}

	/**
	 * Api System Info Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function api_get_systeminfo( WP_REST_Request $request ) {
		$system_report = $this->get_report();

		return rest_ensure_response( array( 'systemreport' => $system_report ) );
	}

	/**
	 * Get System Report.
	 *
	 * @since 4.1.0
	 *
	 * @return string $system_info The system info string that will be displayed on the page.
	 */
	public function get_report() {
		global $wpdb;

		if ( ! function_exists( 'get_plugin_updates' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/update.php' );
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// Browser Helper.
		$browser = new Browser();

		// Theme Info.
		$theme_data   = wp_get_theme();
		$theme        = $theme_data->Name . ' ' . $theme_data->Version;
		$parent_theme = $theme_data->Template;
		if ( ! empty( $parent_theme ) ) {
			$parent_theme_data = wp_get_theme( $parent_theme );
			$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
		}

		$system_info = '### Begin System Info ###' . "\n\n";

		// Site Info.
		$system_info .= '-- Site Info' . "\n\n";
		$system_info .= 'Site URL:                 ' . site_url() . "\n";
		$system_info .= 'Home URL:                 ' . home_url() . "\n";
		$system_info .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

		$system_info = apply_filters( 'wpcw_system_info_report_after_site_info', $system_info );

		// Hosting Providers
		$host = wpcw_get_host();
		if ( $host ) {
			$system_info .= "\n" . '-- Hosting Provider' . "\n\n";
			$system_info .= 'Host:                     ' . $host . "\n";

			$system_info = apply_filters( 'wpcw_system_info_report_after_host_info', $system_info );
		}

		// User Browser Information.
		$system_info .= "\n" . '-- User Browser' . "\n\n";
		$system_info .= $browser;

		$system_info = apply_filters( 'wpcw_system_info_report_after_user_browser', $system_info );

		$locale = get_locale();

		// WordPress.
		$system_info .= "\n" . '-- WordPress Configuration' . "\n\n";
		$system_info .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$system_info .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
		$system_info .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
		$system_info .= 'Active Theme:             ' . $theme . "\n";
		if ( $parent_theme !== $theme ) {
			$system_info .= 'Parent Theme:             ' . $parent_theme . "\n";
		}
		$system_info .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

		// Only show page specs if frontpage is set to 'page'
		if ( get_option( 'show_on_front' ) == 'page' ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id  = get_option( 'page_for_posts' );

			$system_info .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
			$system_info .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
		}

		$system_info .= 'ABSPATH:                  ' . ABSPATH . "\n";

		// Make sure wp_remote_post() is working
		$request['cmd'] = '_notify-validate';

		$params = array(
			'sslverify'  => false,
			'timeout'    => 60,
			'user-agent' => 'WPCW/' . WPCW_VERSION,
			'body'       => $request,
		);

		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$WP_REMOTE_POST = 'wp_remote_post() works';
		} else {
			$WP_REMOTE_POST = 'wp_remote_post() does not work';
		}

		$system_info .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
		$system_info .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
		$system_info .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$system_info .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
		$system_info .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

		$system_info = apply_filters( 'wpcw_system_info_report_after_wordpress_config', $system_info );

		// Churly.
		$system_info .= "\n" . '-- WP Courseware Configuration' . "\n\n";
		$system_info .= 'Version:                  ' . WPCW_VERSION . "\n";
		$system_info .= 'DB Version:               ' . WPCW_DB_VERSION . "\n";

		$system_info = apply_filters( 'wpcw_system_info_report_after_edd_config', $system_info );

		// WordPress plugins with updates.
		$updates = get_plugin_updates();

		// Must-use plugins.
		// NOTE: MU plugins can't show updates!
		$muplugins = get_mu_plugins();
		if ( count( $muplugins ) > 0 ) {
			$system_info .= "\n" . '-- Must-Use Plugins' . "\n\n";

			foreach ( $muplugins as $plugin => $plugin_data ) {
				$system_info .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
			}

			$system_info = apply_filters( 'wpcw_system_info_report_after_wordpress_mu_plugins', $system_info );
		}

		// WordPress active plugins.
		$system_info .= "\n" . '-- WordPress Active Plugins' . "\n\n";

		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$update      = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$system_info .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$system_info = apply_filters( 'wpcw_system_info_report_after_wordpress_plugins', $system_info );

		// WordPress inactive plugins.
		$system_info .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( in_array( $plugin_path, $active_plugins ) ) {
				continue;
			}

			$update      = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$system_info .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$system_info = apply_filters( 'wpcw_system_info_report_after_wordpress_plugins_inactive', $system_info );

		// Multisite info.
		if ( is_multisite() ) {
			// WordPress Multisite active plugins
			$system_info .= "\n" . '-- Network Active Plugins' . "\n\n";

			$plugins        = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach ( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
					continue;
				}

				$update      = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
				$plugin      = get_plugin_data( $plugin_path );
				$system_info .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}

			$system_info = apply_filters( 'wpcw_system_info_report_after_wordpress_ms_plugins', $system_info );
		}

		// Server.
		$system_info .= "\n" . '-- Webserver Configuration' . "\n\n";
		$system_info .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$system_info .= 'Curl Version:             ' . curl_version()['version'] . "\n";
		$system_info .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$system_info .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

		$system_info = apply_filters( 'wpcw_system_info_report_after_webserver_config', $system_info );

		// PHP Configs.
		$system_info .= "\n" . '-- PHP Configuration' . "\n\n";
		$system_info .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$system_info .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$system_info .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$system_info .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$system_info .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$system_info .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$system_info .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
		$system_info .= 'PHP Arg Separator:        ' . ini_get( 'arg_separator.output' ) . "\n";

		$system_info = apply_filters( 'wpcw_system_info_report_after_php_config', $system_info );

		// PHP Extensions.
		$system_info .= "\n" . '-- PHP Extensions' . "\n\n";
		$system_info .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$system_info .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$system_info .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
		$system_info .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

		$system_info = apply_filters( 'wpcw_system_info_report_after_php_ext', $system_info );

		// Sessions.
		$system_info .= "\n" . '-- Session Configuration' . "\n\n";
		$system_info .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

		// If Sessions are enabled.
		if ( isset( $_SESSION ) ) {
			$system_info .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
			$system_info .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
			$system_info .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
			$system_info .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
			$system_info .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
		}

		$system_info = apply_filters( 'wpcw_system_info_report_after_session_config', $system_info );

		$system_info .= "\n" . '### End System Info ###';

		return wp_strip_all_tags( $system_info );
	}

	/**
	 * Rest Api: Clear Transients.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_clear_transients( WP_REST_Request $request ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", '%' . $wpdb->esc_like( 'wpcw_sl' ) . '%' ) );

		$transients_to_delete = array(
			'update_plugins',
			'plugin_slugs',
			'update_core',
			'update_themes',
			'theme_roots',
			'wpcw_license_status',
			'wpcw_quizzes_need_grading',
		);

		foreach ( $transients_to_delete as $transient ) {
			delete_site_transient( $transient );
			delete_transient( $transient );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Transients cleared successfully!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Delete Orphaned Question Tags.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_delete_orphaned_question_tags( WP_REST_Request $request ) {
		$delete_tags = wpcw()->questions->delete_orphaned_question_tags();

		if ( ! $delete_tags ) {
			return rest_ensure_response( array( 'success' => false, 'message' => esc_html__( 'There was an error deleting the orphaned tags. Please try again.', 'wp-courseware' ) ) );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Orphaned question tags deleted successfully!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Delete Orphaned Units.
	 *
	 * @since 4.5.1
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_delete_orphaned_units( WP_REST_Request $request ) {
		$delete_orphaned_units = wpcw()->units->delete_orphaned_units();

		if ( ! $delete_orphaned_units ) {
			return rest_ensure_response( array( 'success' => false, 'message' => esc_html__( 'There was an error deleting the orphaned units. Please try again.', 'wp-courseware' ) ) );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Orphaned units deleted successfully!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Manually Upgrade Courses.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_manually_upgrade_courses( WP_REST_Request $request ) {
		wpcw()->courses->maybe_upgrade_courses();
		wpcw()->courses->maybe_fix_duplicate_courses();

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Successfully force upgraded all courses!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Reset Roles & Capabilites
	 *
	 * @since 4.4.1
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_reset_roles_and_caps( WP_REST_Request $request ) {
		wpcw()->roles->reset_roles_caps();

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Successfully reset roles and capabilities!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Reset Tracking Optin
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_reset_tracking_optin( WP_REST_Request $request ) {
		wpcw()->tracker->reset_tracking_opt_in();

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Successfully reset tracking opt-in!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Send Tracking Data
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_send_tracking_data( WP_REST_Request $request ) {
		$checkin = wpcw()->tracker->manually_send_checkin();

		if ( is_wp_error( $checkin ) ) {
			return rest_ensure_response( array( 'success' => false, 'message' => esc_html__( 'There was an error sending tracking data to the server!', 'wp-courseware' ) ) );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Successfully sent tracking data to server!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Manually Run Updater
	 *
	 * @since 4.4.3
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_run_updater( WP_REST_Request $request ) {
		wpcw()->install->manually_run_updates();

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Successfully executed updates and WP Courseware is now on the latest version!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Manually Kill Updater
	 *
	 * @since 4.4.3
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_kill_updater( WP_REST_Request $request ) {
		wpcw()->install->manually_kill_updater();

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Successfully stopped updater!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Fix Database
	 *
	 * @since 4.4.4
	 *
	 * @param WP_REST_Request $request The rest api request.
	 *
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_fix_database( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wpcw()->database->fix_tables();
		wpcw()->database->fix_email_backslashes();

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( 'Database fixed!', 'wp-courseware' ) ) );
	}

	/**
	 * Rest Api: Fix Order Dicounts
	 *
	 * @since 4.6.8
	 *
	 * @param WP_REST_Request $request The rest api request.
	 * @return WP_REST_Response $response The rest api response.
	 */
	public function api_fix_discounts( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Validate the incoming nonce
		$headers = $request->get_headers();
		if( $headers['x_wp_nonce'] && is_array( $headers['x_wp_nonce'] ) && $headers['x_wp_nonce'][0] ) {
			$nonce = $headers['x_wp_nonce'][0];

			// The incoming nonce is invalid
			if( false == wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return rest_ensure_response( array( 'success' => false, 'message' => esc_html__( 'Your request could not be authenticated. Please refresh the page and try again.', 'wp-courseware' ) ) );
			}
		} else { // A nonce was not received
			return rest_ensure_response( array( 'success' => false, 'message' => esc_html__( 'Your request could not be authenticated. Please refresh the page and try again.', 'wp-courseware' ) ) );
		}

		// Request was validated, fix the data
		$order_count = wpcw()->database->fix_discounts();
		// Set word plurality for the outgoing message
		$order_words = array( "order" => "orders", "were" => "were", "count" => $order_count );
		if( $order_count == 1 ) {
			$order_words["order"] = "order";
			$order_words["were"] = "was";
		} if( $order_count < 1 ) {
			$order_words["count"] = "No";
			$order_words["were"] = "needed to be";
		}
		$message = $order_words["count"] . " " . $order_words["order"] . " " . $order_words["were"] . " fixed!";

		return rest_ensure_response( array( 'success' => true, 'message' => esc_html__( $message, 'wp-courseware' ) ) );
	}

}
