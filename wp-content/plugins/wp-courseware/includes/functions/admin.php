<?php
/**
 * WP Courseware Admin Only Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Exit if not is admin
if ( ! is_admin() ) {
	return;
}

/**
 * Is Admin Settings Page?
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_admin_settings_page() {
	global $pagenow;

	if ( is_admin() && $pagenow === 'admin.php' && wpcw_get_var( 'page' ) === 'wpcw-settings' ) {
		return true;
	}

	return false;
}

/**
 * Get Admin View.
 *
 * @since 4.3.0
 *
 * @param string $template The template name.
 *
 * @return string The template contents.
 */
function wpcw_admin_get_view( $view ) {
	if ( ! file_exists( $view ) ) {
		$view = WPCW_ADMIN_PATH . "views/{$view}.php";
		if ( ! file_exists( $view ) ) {
			return '';
		}
	}

	ob_start();

	include $view;

	return ob_get_clean();
}

/**
 * Add Admin Notice.
 *
 * @since 4.3.0
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success info or warning.
 */
function wpcw_add_admin_notice( $message, $notice_type = 'info' ) {
	wpcw()->admin->notices->add( $message, $notice_type );
}

/**
 * Clear Admin Notices.
 *
 * @since 4.3.0
 */
function wpcw_clear_admin_notices() {
	wpcw()->admin->notices->delete();
}

/**
 * Get Admin Notices.
 *
 * @since 4.3.0
 *
 * @return array|mixed
 */
function wpcw_get_admin_notices() {
	return wpcw()->admin->notices->get();
}

/**
 * Admin Notice.
 *
 * @since 4.3.0
 *
 * @param string $message The message that should be displayed.
 * @param string $type The type of error that should be displayed. Default is 'info'.
 * @param bool $dismissable If the error should be allowed to be dismissed. Default is 'true'.
 * @param bool $echo If to echo the text or return it.
 *
 * @return string|void
 */
function wpcw_admin_notice( $message, $type = 'info', $dismissable = true, $echo = true, $inline = false ) {
	$notice = sprintf(
		'<div class="wpcw-admin-notice notice notice-%1$s%2$s%3$s"><p>%4$s</p></div>',
		esc_attr( $type ),
		( $dismissable ) ? ' is-dismissible' : '',
		( $inline ) ? ' inline' : '',
		$message
	);

	if ( $echo ) {
		echo $notice;
	} else {
		return $notice;
	}
}

/**
 * Display One Off Admin Notice.
 *
 * @since 4.3.0
 *
 * @param string $message The notice message.
 * @param string $type The notice type.
 * @param bool $dismissable Is the notice dismissable.
 */
function wpcw_display_admin_notice( $message, $type = 'warning', $dismissable = true ) {
	add_action( 'admin_notices', function () use ( $message, $type, $dismissable ) {
		wpcw_admin_notice( $message, $type, $dismissable );
	} );
}

/**
 * Notice - Error.
 *
 * @since 4.3.0
 *
 * @param string $message The message that should be disaplyed.
 * @param bool $echo If to echo the text or return it.
 * @param bool $dismissable If the error should be allowed to be dismissed.
 *
 * @return string|void
 */
function wpcw_admin_notice_error( $message, $echo = true, $dismissable = true ) {
	if ( $echo ) {
		wpcw_admin_notice( $message, 'error', $dismissable );
	} else {
		return wpcw_admin_notice( $message, 'error', $dismissable, false );
	}
}

/**
 * Add Admin Notice - Error.
 *
 * @since 4.3.0
 *
 * @param string $message The error message.
 */
function wpcw_add_admin_notice_error( $message ) {
	wpcw_add_admin_notice( $message, 'error' );
}

/**
 * Admin Notice - Success.
 *
 * @since 4.3.0
 *
 * @param string $message The message that should be disaplyed.
 * @param bool $echo If to echo the text or return it.
 * @param bool $dismissable If the error should be allowed to be dismissed.
 *
 * @return string|void
 */
function wpcw_admin_notice_success( $message, $echo = true, $dismissable = true ) {
	if ( $echo ) {
		wpcw_admin_notice( $message, 'success', $dismissable );
	} else {
		return wpcw_admin_notice( $message, 'success', $dismissable, false );
	}
}

/**
 * Add Admin Notice - Success.
 *
 * @since 4.3.0
 *
 * @param string $message The error message.
 */
function wpcw_add_admin_notice_success( $message ) {
	wpcw_add_admin_notice( $message, 'success' );
}

/**
 * Admin Notice - Info.
 *
 * @since 4.3.0
 *
 * @param string $message The message that should be disaplyed.
 * @param bool $echo If to echo the text or return it.
 * @param bool $dismissable If the error should be allowed to be dismissed.
 *
 * @return string|void
 */
function wpcw_admin_notice_info( $message, $echo = true, $dismissable = true ) {
	if ( $echo ) {
		wpcw_admin_notice( $message, 'info', $dismissable );
	} else {
		return wpcw_admin_notice( $message, 'info', $dismissable, false );
	}
}

/**
 * Add Admin Notice - Info.
 *
 * @since 4.3.0
 *
 * @param string $message The error message.
 */
function wpcw_add_admin_notice_info( $message ) {
	wpcw_add_admin_notice( $message, 'info' );
}

/**
 * Admin Notice - Warning.
 *
 * @since 4.3.0
 *
 * @param string $message The message that should be disaplyed.
 * @param bool $echo If to echo the text or return it.
 * @param bool $dismissable If the error should be allowed to be dismissed.
 *
 * @return string|void
 */
function wpcw_admin_notice_warning( $message, $echo = true, $dismissable = true ) {
	if ( $echo ) {
		wpcw_admin_notice( $message, 'warning', $dismissable );
	} else {
		return wpcw_admin_notice( $message, 'warning', $dismissable, false );
	}
}

/**
 * Add Admin Notice - Warning.
 *
 * @since 4.3.0
 *
 * @param string $message The error message.
 */
function wpcw_add_admin_notice_warning( $message ) {
	wpcw_add_admin_notice( $message, 'warning' );
}