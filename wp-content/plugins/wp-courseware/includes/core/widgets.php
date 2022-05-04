<?php
/**
 * WP Courseware Widgets.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Widgets.
 *
 * @since 4.3.0
 */
final class Widgets {

	/**
	 * Load Widgets.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register Widgets.
	 *
	 * @since 4.3.0
	 */
	public function register_widgets() {
		register_widget( '\\WPCW\\Widgets\\Widget_Course_Progress' );
		register_widget( '\\WPCW\\Widgets\\Widget_Course_Progress_Bar' );
	}
}
