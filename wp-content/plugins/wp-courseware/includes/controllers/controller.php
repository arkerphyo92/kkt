<?php
/**
 * WP Courseware Controller.
 *
 * The base class controller for all others classes to inherit.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Controller.
 *
 * @since 4.3.0
 */
abstract class Controller {

	/**
	 * Controller Load.
	 *
	 * @since 4.3.0
	 */
	public abstract function load();

	/**
	 * Log Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The log message.
	 * @param bool   $log_to_file Log the message to the file log.
	 */
	public function log( $message = '', $log_to_file = true ) {
		if ( empty( $message ) || ! defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {
			return;
		}

		$log_entry = "\n" . '====Start ' . get_called_class() . ' Log====' . "\n" . $message . "\n" . '====End ' . get_called_class() . ' Log====' . "\n";

		wpcw_log( $log_entry );

		if ( $log_to_file ) {
			wpcw_file_log( array( 'message' => $log_entry ) );
		}
	}
}
