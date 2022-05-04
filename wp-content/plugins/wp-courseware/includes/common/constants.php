<?php
/**
 * WP Courseware Constants.
 *
 * All common constants that are used throughout WP Courseware.
 *
 * @package WPCW
 * @subpackage Common
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

// Database Version
define( 'WPCW_DB_VERSION', WPCW_VERSION );

// Minimum PHP Version
define( 'WPCW_MIN_PHP_VERSION', '5.6.0' );

// Minimum WP Version
define( 'WPCW_MIN_WP_VERSION', '4.8.0' );

// Tested WP Version.
define( 'WPCW_TESTED_WP_VERSION', '5.3.2' );

// Includes Folder Path
define( 'WPCW_INC_PATH', trailingslashit( WPCW_PATH . 'includes' ) );

// Languages Folder Path
define( 'WPCW_LANG_PATH', trailingslashit( WPCW_PATH . 'languages' ) );

// Templates Folder Path
define( 'WPCW_TEMPLATES_PATH', trailingslashit( WPCW_PATH . 'templates' ) );

// Vendor Folder Path
define( 'WPCW_VENDOR_PATH', trailingslashit( WPCW_PATH . 'vendor' ) );

// Assets Folder Path
define( 'WPCW_ASSETS_PATH', trailingslashit( WPCW_PATH . 'assets' ) );

// Admin Folder Path
define( 'WPCW_ADMIN_PATH', trailingslashit( WPCW_INC_PATH . 'admin' ) );

// Common Folder Path
define( 'WPCW_COMMON_PATH', trailingslashit( WPCW_INC_PATH . 'common' ) );

// Common Folder Path
define( 'WPCW_CORE_PATH', trailingslashit( WPCW_INC_PATH . 'core' ) );

// Database Folder Path
define( 'WPCW_DATABASE_PATH', trailingslashit( WPCW_INC_PATH . 'database' ) );

// Legacy Folder Path
define( 'WPCW_EXTEND_PATH', trailingslashit( WPCW_INC_PATH . 'extend' ) );

// Legacy Folder Path
define( 'WPCW_LEGACY_PATH', trailingslashit( WPCW_INC_PATH . 'legacy' ) );

// Libraray Folder Path
define( 'WPCW_LIB_PATH', trailingslashit( WPCW_INC_PATH . 'library' ) );

// Assets Folder Url
define( 'WPCW_ASSETS_URL', trailingslashit( WPCW_URL . 'assets' ) );

// Images Folder Url
define( 'WPCW_IMG_URL', trailingslashit( WPCW_ASSETS_URL . 'img' ) );

// CSS Folder Url
define( 'WPCW_CSS_URL', trailingslashit( WPCW_ASSETS_URL . 'css' ) );

// JS Folder Url
define( 'WPCW_JS_URL', trailingslashit( WPCW_ASSETS_URL . 'js' ) );

// Session Cache Group.
define( 'WPCW_SESSION_CACHE_GROUP', 'wpcw_session_id' );

// Legacy: Plugin Version
define( 'WPCW_PLUGIN_VERSION', WPCW_VERSION );

// Legacy: Database Version
define( 'WPCW_DATABASE_VERSION', WPCW_VERSION );

// Legacy: Database Key
if ( version_compare( WPCW_DB_VERSION, '4.1.0', '>=' ) ) {
	define( 'WPCW_DATABASE_KEY', 'wpcw_db_version' );
} else {
	define( 'WPCW_DATABASE_KEY', 'WPCW_Version' );
}

// Legacy: Database Settings Key
if ( version_compare( WPCW_VERSION, '4.1.0', '>=' ) ) {
	define( 'WPCW_DATABASE_SETTINGS_KEY', 'wpcw' );
} else {
	define( 'WPCW_DATABASE_SETTINGS_KEY', 'WPCW_Settings' );
}

// Legacy: Plugin ID
define( 'WPCW_PLUGIN_ID', 'WPCW_wp_courseware' );

// Legacy: Plugin Update ID
define( 'WPCW_PLUGIN_UPDATE_ID', 'wp-courseware/wp-courseware.php' );

// Legacy: Menu Position ID
define( 'WPCW_MENU_POSITION', 384289 );

// Legacy: Template Meta ID
define( 'WPCW_TEMPLATE_META_ID', '_wpcw_template_to_use' );

// Legacy: HTML used to show that a field is optional
define( 'WPCW_HTML_OPTIONAL', sprintf( '<em class="wpcw_optional">(%s)</em>', esc_html__( 'Optional', 'wp-courseware' ) ) );

// Legacy: HTML used to show that a field is optional but recommended
define( 'WPCW_HTML_OPTIONAL_RECOMMENDED', sprintf( '<em class="wpcw_optional">(%s)</em>', esc_html__( 'Optional, but Recommended', 'wp-courseware' ) ) );

// Legacy: The width of the signature image in pixels
define( 'WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX', '170' );

// Legacy: The height of the signature image in pixels
define( 'WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX', '40' );

// Legacy: The width of the signature image in pixels
define( 'WPCW_CERTIFICATE_LOGO_WIDTH_PX', '160' );

// Legacy: The height of the signature image in pixels
define( 'WPCW_CERTIFICATE_LOGO_HEIGHT_PX', '120' );

// Legacy: The width of the signature image in pixels
define( 'WPCW_CERTIFICATE_BG_WIDTH_PX', '3508' );

// Legacy: The height of the signature image in pixels
define( 'WPCW_CERTIFICATE_BG_HEIGHT_PX', '2480' );

// Legacy: The ID of the cron task that sends out notifications to trainees
define( 'WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID', 'wpcw-cron-notifications-dripfeed' );

// Legacy: Time minute in seconds
define( 'WPCW_TIME_MIN_IN_SECS', 60 );

// Legacy: Time hour in seconds
define( 'WPCW_TIME_HR_IN_SECS', 60 * WPCW_TIME_MIN_IN_SECS );

// Legacy: Time day in seconds
define( 'WPCW_TIME_DAY_IN_SECS', 24 * WPCW_TIME_HR_IN_SECS );

// Legacy: Time week in seconds
define( 'WPCW_TIME_WEEK_IN_SECS', 7 * WPCW_TIME_DAY_IN_SECS );

// Legacy: Time year in seconds
define( 'WPCW_TIME_YEAR_IN_SECS', 365 * WPCW_TIME_DAY_IN_SECS );

// Legacy: Time month in seconds
define( 'WPCW_TIME_MONTH_IN_SECS', WPCW_TIME_YEAR_IN_SECS / 12 );

// Currency rounding precision.
define( 'WPCW_ROUNDING_PRECISION', 2 );

// Cal Gregorian.
defined( 'CAL_GREGORIAN' ) || define( 'CAL_GREGORIAN', 1 );

// PayPal NGROK URL
defined( 'WPCW_LOCAL_TESTING' ) || define( 'WPCW_LOCAL_TESTING', false );
defined( 'WPCW_LOCAL_URL' ) || define( 'WPCW_LOCAL_URL', '' );

// Cron
defined( 'WPCW_CRON_DISABLE_SSL' ) || define( 'WPCW_CRON_DISABLE_SSL', false );

// Templates
defined( 'WPCW_TEMPLATE_DEBUG_MODE' ) || define( 'WPCW_TEMPLATE_DEBUG_MODE', false );
