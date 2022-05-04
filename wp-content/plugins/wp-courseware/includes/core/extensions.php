<?php
/**
 * WP Courseware Extensions.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Extensions.
 *
 * @since 4.3.0
 */
final class Extensions {

	/**
	 * Load Extensions.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_init', array( $this, 'load_extensions' ), 0 );
	}

	/**
	 * Load External Extensions.
	 *
	 * @since 4.3.0
	 */
	public function load_extensions() {
		/**
		 * Hook: Load Extensions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_extensions_load' );
	}
}