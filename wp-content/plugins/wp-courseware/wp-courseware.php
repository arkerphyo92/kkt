<?php
/**
 * Plugin Name: WP Courseware
 * Plugin URI:  https://flyplugins.com/wp-courseware
 * Description: WordPress's leading Learning Management System (L.M.S.) plugin and is so simple you can create an online course in minutes.
 * Version:     4.8.19
 * Author:      Fly Plugins
 * Author URI:  https://flyplugins.com
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-courseware
 * Domain Path: /languages
 *
 * Copyright (c) 2022 Fly Plugins - Lighthouse Media, LLC (email : questions@flyplugins.com)
 *
 * @package WPCW
 * @version 4.8.19
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Plugin Version
define( 'WPCW_VERSION', '4.8.19' );

// Plugin File
define( 'WPCW_FILE', __FILE__ );

// Plugin Path
defined( 'WPCW_PATH' ) || define( 'WPCW_PATH', plugin_dir_path( WPCW_FILE ) );

// Plugin Url
defined( 'WPCW_URL' ) || define( 'WPCW_URL', plugin_dir_url( WPCW_FILE ) );

// TCPDF font folder file path.
$tcpdf_font_path = WP_CONTENT_DIR . '/wpcourseware_uploads/tcpdf-fonts/';
if ( ! file_exists( $tcpdf_font_path ) ) {
	@mkdir( $tcpdf_font_path, 0777, true );
	$font_array = array( 'architectsdaughter.ctg.z', 'architectsdaughter.php', 'architectsdaughter.z', 'helvetica.php', 'helveticab.php', 'helveticabi.php', 'helveticai.php', 'dejavusans.php', 'dejavusans.z', 'dejavusans.ctg.z' , 'dejavusansb.ctg.z', 'dejavusansb.php', 'dejavusansb.z' );
	foreach ( $font_array as $font ) {
		$current_file_path = WPCW_PATH . 'includes/library/tcpdf/fonts/' . $font;
		$new_file_path     = WP_CONTENT_DIR . '/wpcourseware_uploads/tcpdf-fonts/' . $font;
		$fileMoved         = copy( $current_file_path, $new_file_path );
	}
}
define( 'WPCW_PDF_FONTS', 'https://dlp9jhkr9nqwv.cloudfront.net/pdf/' );
define( 'WPCW_WEB_FONTS', 'https://dlp9jhkr9nqwv.cloudfront.net/webkit/' );
define( 'K_PATH_FONTS', WP_CONTENT_DIR . '/wpcourseware_uploads/tcpdf-fonts/' );

// Requirements to run plugin
require_once WPCW_PATH . 'includes/common/requirements.php';

// Requirements check
if ( WPCW_Requirements::check() ) {
	require_once WPCW_PATH . 'includes/plugin.php';

	/**
	 * Main WP Courseware Function.
	 *
	 * The main function responsible for returning
	 * the singleton instance of \WPCW\Plugin.
	 *
	 * Example: <?php $wpcw = wpcw(); ?>
	 *
	 * @since 4.3.0
	 *
	 * @return WPCW_Plugin The WP Courseware plugin singleton instance.
	 */
	function wpcw() {
		return WPCW_Plugin::instance();
	}

	/**
	 * Start WP Courseware.
	 *
	 * Instead of hooking into the 'plugins_loaded' action
	 * we load the singleton instance immediately to load
	 * the necesary objects into memory and fire hooks
	 * at the appropriate time.
	 *
	 * @since 4.3.0
	 */
	wpcw();
}
