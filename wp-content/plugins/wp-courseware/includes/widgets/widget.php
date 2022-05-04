<?php
/**
 * WP Courseware Widget Base Class.
 *
 * The base class widget class for all others widget classes to inherit.
 *
 * @package WPCW
 * @subpackage Widgets
 * @since 4.6.0
 */
namespace WPCW\Widgets;

use WP_Widget;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Widget.
 *
 * @since 4.6.0
 */
abstract class Widget extends WP_Widget {

	/**
	 * Log Message.
	 *
	 * @since 4.6.0
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
