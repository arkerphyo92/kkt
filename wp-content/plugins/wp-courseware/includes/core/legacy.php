<?php
/**
 * WP Courseware Legacy Class.
 *
 * Handles all loading of legacy functionality and files.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WPCW_Export;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Legacy.
 *
 * @since 4.1.0
 */
final class Legacy {

	/**
	 * Load Legacy.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		include_once WPCW_LEGACY_PATH . 'hooks.php';

		add_action( 'wpcw_init', array( $this, 'init' ) );
	}

	/**
	 * Initialize Legacy.
	 *
	 * @since 4.1.0
	 */
	public function init() {
		if ( is_admin() ) {
			WPCW_Export::tryExportCourse();
		}
	}
}
