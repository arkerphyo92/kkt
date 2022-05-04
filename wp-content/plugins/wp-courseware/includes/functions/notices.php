<?php
/**
 * WP Courseware Notices.
 *
 * @since 4.3.0
 * @subpackage Functions
 * @package WPCW
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Notice Count.
 *
 * @since 4.3.0
 *
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 *
 * @return int The number of notices.
 */
function wpcw_notice_count( $notice_type = '' ) {
	$notice_count = 0;
	$all_notices  = wpcw()->session->get( 'wpcw_notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {
		$notice_count = count( $all_notices[ $notice_type ] );
	} elseif ( empty( $notice_type ) ) {
		foreach ( $all_notices as $notices ) {
			$notice_count += count( $notices );
		}
	}

	return $notice_count;
}

/**
 * Check if a notice has already been added.
 *
 * @since 4.3.0
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 *
 * @return bool True if the notice is found.
 */
function wpcw_has_notice( $message, $notice_type = 'success' ) {
	$notices = wpcw()->session->get( 'wpcw_notices', array() );
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();

	return array_search( $message, $notices, true ) !== false;
}

/**
 * Add Notice.
 *
 * @since 4.3.0
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 */
function wpcw_add_notice( $message, $notice_type = 'success' ) {
	$notices = wpcw()->session->get( 'wpcw_notices', array() );

	$notices[ $notice_type ][] = apply_filters( 'wpcw_notices_add_' . $notice_type, $message );

	wpcw()->session->set( 'wpcw_notices', $notices );
}

/**
 * Set All Notices.
 *
 * @since 4.3.0
 *
 * @param mixed $notices Array of notices.
 */
function wpcw_set_notices( $notices ) {
	wpcw()->session->set( 'wpcw_notices', $notices );
}

/**
 * Clear All Notices.
 *
 * @since 4.3.0
 */
function wpcw_clear_notices() {
	wpcw()->session->set( 'wpcw_notices', null );
}

/**
 * Print All Notices.
 *
 * Prints messages and errors which are
 * stored in the session, then clears them.
 *
 * @since 4.3.0
 */
function wpcw_print_notices() {
	$all_notices  = wpcw()->session->get( 'wpcw_notices', array() );
	$notice_types = apply_filters( 'wpcw_notice_types', array( 'error', 'success', 'info' ) );

	foreach ( $notice_types as $notice_type ) {
		if ( wpcw_notice_count( $notice_type ) > 0 ) {
			wpcw_get_template( "notices/{$notice_type}.php", array(
				'messages' => array_filter( $all_notices[ $notice_type ] ),
			) );
		}
	}

	wpcw_clear_notices();
}

/**
 * Print a Notice.
 *
 * @since 4.3.0
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The singular name of the notice type - either error, success or notice.
 */
function wpcw_print_notice( $message, $notice_type = 'success' ) {
	wpcw_get_template( "notices/{$notice_type}.php", array(
		'messages' => array( apply_filters( 'wpcw_add_' . $notice_type, $message ) ),
	) );
}

/**
 * Get a Notice.
 *
 * @since 4.3.0
 *
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The singular name of the notice type - either error, success or notice.
 *
 * @return string The notice.
 */
function wpcw_get_notice( $message, $notice_type = 'success' ) {
	ob_start();

	wpcw_print_notice( $message, $notice_type );

	return ob_get_clean();
}

/**
 * Get All Notices.
 *
 * @since 4.3.0
 *
 * @return array|mixed
 */
function wpcw_get_notices() {
	ob_start();

	wpcw_print_notices();

	return ob_get_clean();
}

/**
 * Add notices for WP Errors.
 *
 * @since 4.3.0
 *
 * @param WP_Error $errors Errors.
 */
function wpcw_add_wp_error_notices( $errors ) {
	if ( is_wp_error( $errors ) && $errors->get_error_messages() ) {
		foreach ( $errors->get_error_messages() as $error ) {
			wpcw_add_notice( $error, 'error' );
		}
	}
}
