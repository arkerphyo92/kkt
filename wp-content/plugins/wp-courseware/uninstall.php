<?php
/**
 * WP Courseware Uninstall.
 *
 * Uninstalling WP Courseware deletes user roles, pages, tables, and options.
 *
 * @package WPCW
 * @since 4.4.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Include Plugin File.
include_once 'wp-courseware.php';

// Notifications Cron.
if ( defined( 'WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID' ) ) {
	wp_clear_scheduled_hook( WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID );
}

// Weekly & Daily Cron
wp_clear_scheduled_hook( 'wpcw_weekly_cron' );
wp_clear_scheduled_hook( 'wpcw_daily_cron' );
wp_clear_scheduled_hook( 'wpcw_tracker_send_initial_checkin' );

// Role Capabilities
wpcw()->roles->remove_caps();

/*
 * Only remove ALL course and page data if WPCW_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WPCW_REMOVE_ALL_DATA' ) && true === WPCW_REMOVE_ALL_DATA ) {
	// Remove Roles
	wpcw()->roles->remove_roles();

	// Pages
	foreach ( array( 'courses_page', 'checkout_page', 'order_received_page', 'order_failed_page', 'terms_page', 'account_page' ) as $page ) {
		wp_trash_post( wpcw_get_page_id( $page ) );
	}

	// Drop Database Tables.
	wpcw()->database->drop_tables();

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpcw\_%';" );

	// Delete usermeta.
	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'wpcw\_%';" );
}
