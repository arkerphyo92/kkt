<?php
/**
 * WP Courseware Logs.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WPCW\Core\Api;
use WPCW\Database\DB_Logs;
use WPCW\Models\Log;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Logs.
 *
 * @since 4.3.0
 */
class Logs extends Controller {

	/**
	 * @var DB_Logs The logs database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var string The log filename.
	 * @since 4.3.0
	 */
	protected $filename;

	/**
	 * @var string The log file.
	 * @since 4.3.0
	 */
	protected $file;

	/**
	 * @var bool Is the directory writable.
	 * @since 4.3.0
	 */
	protected $is_writable = true;

	/**
	 * Logs Constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Logs();
	}

	/**
	 * Load Logs.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_loaded', array( $this, 'setup_log_file' ), 0 );
		add_action( 'wpcw_api_actions', array( $this, 'register_api_actions' ), 10, 2 );
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
		add_action( 'wpcw_daily_cron', array( $this, 'cron_clear_logs' ) );
	}

	/**
	 * Add Log.
	 *
	 * @since 4.3.0
	 *
	 * @param array  $data The log data.
	 * @param string $method The insert method.
	 *
	 * @return bool True on succesfful addition, False on failure.
	 */
	public function add_log( $data = array(), $method = 'file' ) {
		$defaults = array(
			'object_id'   => 0,
			'object_type' => '',
			'type'        => 'debug',
			'title'       => '',
			'message'     => '',
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['message'] ) ) {
			return false;
		}

		if ( 'file' === $method ) {
			return $this->add_log_entry_to_file( $data );
		}

		return $this->add_log_entry_to_db( $data );
	}

	/**
	 * Get Log by Id.
	 *
	 * @since 4.1.0
	 *
	 * @param int $log_id The log id.
	 *
	 * @return bool|Log The log object.
	 */
	public function get_log( $log_id ) {
		if ( 0 === absint( $log_id ) ) {
			return false;
		}

		$result = $this->db->get( $log_id );

		if ( ! $result ) {
			return false;
		}

		return new Log( $result );
	}

	/**
	 * Get Logs.
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 *
	 * @return array Array of log objects.
	 */
	public function get_logs( $args = array(), $raw = false ) {
		$logs    = array();
		$results = $this->db->get_logs( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$logs[] = new Log( $result );
		}

		return $logs;
	}

	/**
	 * Add Log Entry to DB.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The log entry data.
	 */
	public function add_log_entry_to_db( $data = array() ) {
		$defaults = array(
			'object_id'    => 0,
			'object_type'  => '',
			'type'         => 'debug',
			'title'        => '',
			'message'      => '',
			'date_created' => date_i18n( 'Y-m-d H:i:s' ),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['message'] ) ) {
			return;
		}

		if ( is_array( $data['message'] ) ) {
			$data['message'] = print_r( $data['message'], true );
		}

		return $this->db->insert( $data, 'log' );
	}

	/**
	 * Add Log Entry to File.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The log entry data.
	 *
	 * @return string|bool Number of bytes if inserted or false on failure.
	 */
	public function add_log_entry_to_file( $data = array() ) {
		$defaults = array(
			'date_created' => date_i18n( 'm-d-Y @ H:i:s' ),
			'message'      => '',
			'type'         => 'debug',
		);

		$data = wp_parse_args( $data, $defaults );

		if ( empty( $data['message'] ) ) {
			return;
		}

		if ( is_array( $data['message'] ) ) {
			$data['message'] = wpcw_print_r( $data['message'], true );
		}

		$message = '[' . esc_attr( $data['date_created'] ) . '] ' . $data['message'] . "\r\n";

		$log_file_contents = $this->get_log_file_contents();
		$log_file_contents .= $message;

		return @file_put_contents( $this->file, $log_file_contents );
	}

	/**
	 * Maybe Create Log Directory.
	 *
	 * @since 4.3.0
	 */
	protected function maybe_create_log_dir() {
		$path = $this->get_log_dir();

		if ( ! file_exists( $path ) ) {
			@mkdir( $path, 0777, true );
		}

		// Create an empty index page to stop directory listings.
		if ( file_exists( $path ) ) {
			touch( $path . 'index.php' );
		}
	}

	/**
	 * Get Log Directory.
	 *
	 * @since 4.3.0
	 *
	 * @return string The log directory path.
	 */
	public function get_log_dir() {
		$upload_dir = wpcw_get_upload_directory_path();

		return apply_filters( 'wpcw_log_dir_path', trailingslashit( $upload_dir . 'logs' ) );
	}

	/**
	 * Setup Log File.
	 *
	 * @since 4.3.0
	 */
	public function setup_log_file() {
		$this->maybe_create_log_dir();

		$log_dir        = $this->get_log_dir();
		$this->filename = wp_hash( get_bloginfo( 'name' ) ) . '-wpcw-log.log';
		$this->file     = $log_dir . $this->filename;

		if ( ! is_writeable( $log_dir ) ) {
			$this->is_writable = false;
		}
	}

	/**
	 * Get Log File Contents.
	 *
	 * @since 4.3.0
	 *
	 * @return string The log file contents.
	 */
	protected function get_log_file_contents() {
		$log_file_contents = '';

		if ( @file_exists( $this->file ) ) {
			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}
			$log_file_contents = @file_get_contents( $this->file );
		} else {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}

		return $log_file_contents;
	}

	/**
	 * Clear Log.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_log() {
		@unlink( $this->file );

		// It's still there, so maybe server doesn't have delete rights
		// Try to give the server delete rights
		if ( file_exists( $this->file ) ) {
			chmod( $this->file, 0664 );
			@unlink( $this->file );
			if ( @file_exists( $this->file ) ) {
				if ( is_writeable( $this->file ) ) {
					file_put_contents( $this->file, '' );
				} else {
					return false;
				}
			}
		}

		$this->file = '';

		return true;
	}

	/**
	 * Log Types.
	 *
	 * @since 4.3.0
	 *
	 * @return array The valid log types.
	 */
	public function get_log_types() {
		$types = array(
			'emergency',
			'alert',
			'critical',
			'error',
			'warning',
			'notice',
			'info',
			'debug',
			'gateway',
		);

		return apply_filters( 'wpcw_log_types', $types );
	}

	/**
	 * Is Valid Log Type?
	 *
	 * @since 4.3.0
	 *
	 * @param string $type The type of log.
	 *
	 * @return bool True if is valid, False otherwise.
	 */
	public function is_valid_log_type( $type ) {
		return in_array( $type, $this->get_log_types() );
	}

	/**
	 * Cron Clear Logs.
	 *
	 * @since 4.3.0
	 */
	public function cron_clear_logs() {
		if ( ! wpcw_is_doing_cron() ) {
			return;
		}

		$this->clear_log();
	}

	/**
	 * Register Logs Api Actions.
	 *
	 * @since 4.3.0
	 *
	 * @param string $prefix The Action prefix.
	 * @param Api    $api The api object.
	 *
	 * @return void
	 */
	public function register_api_actions( $prefix, $api ) {
		add_action( "{$prefix}_download_system_log", array( $this, 'api_action_download_log' ) );
	}

	/**
	 * Api Download Log Action Callback.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The GET/POST data.
	 */
	public function api_action_download_log( $data ) {
		if ( ! wp_verify_nonce( $data['api_action_nonce'], 'wpcw-api-actions' ) ) {
			wp_die( __( '<strong>Error:</strong> Sorry! You are not able to perform this action.', 'wp-courseware' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( __( '<strong>Error:</strong> Sorry! You are not able to perform this action.', 'wp-courseware' ) );
		}

		$log = $this->get_log_file_contents();

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . $this->filename . '"' );

		echo wp_strip_all_tags( $log );

		die();
	}

	/**
	 * Register Logs Api Endpoints.
	 *
	 * @since 4.3.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The API Object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array(
			'endpoint' => 'systemlog',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_log' ),
		);

		$endpoints[] = array(
			'endpoint' => 'deletesystemlog',
			'method'   => 'POST',
			'callback' => array( $this, 'api_delete_log' ),
		);

		return $endpoints;
	}

	/**
	 * Api: Get Log Callback.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function api_get_log( WP_REST_Request $request ) {
		$systemlog = $this->get_log_file_contents();

		if ( empty( $systemlog ) ) {
			$systemlog = esc_html__( 'There are currently no log items.', 'wp-courseware' );
		}

		return rest_ensure_response( array( 'systemlog' => $systemlog ) );
	}

	/**
	 * Api: Get Log Callback.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function api_delete_log( WP_REST_Request $request ) {
		$notice    = sprintf( __( 'The log <strong>%s</strong> was deleted successfully.', 'wp-courseware' ), esc_attr( $this->filename ) );
		$systemlog = esc_html__( 'There are currently no log items.', 'wp-courseware' );

		if ( ! $this->clear_log() ) {
			$notice    = sprintf( __( 'The log <strong>%s</strong> was not deleted successfully.', 'wp-courseware' ), esc_attr( $this->filename ) );
			$systemlog = $this->get_log_file_contents();
		}

		return rest_ensure_response( array( 'systemlog' => $systemlog ) );
	}
}
