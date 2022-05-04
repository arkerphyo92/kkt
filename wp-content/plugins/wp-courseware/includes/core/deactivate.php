<?php
/**
 * WP Courseware De-Activation.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivate.
 *
 * @since 4.3.0
 */
final class Deactivate {

	/**
	 * Load Deactivate Class.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		register_deactivation_hook( WPCW_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Deactivate Plugin.
	 *
	 * @since 4.3.0
	 */
	public function deactivate() {
		// Remove any cron-associated tasks from the system.
		wp_clear_scheduled_hook( WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID );

		// Resets Permalinks nag if plugin is deactivated
		$current_user = wp_get_current_user();

		// Removes user meta that keeps nag notices away
		delete_user_meta( $current_user->ID, 'ignore_permalinks_notice', 'yes' );
		delete_user_meta( $current_user->ID, 'ignore_cancelled_license_notice', 'yes' );
		delete_user_meta( $current_user->ID, 'ignore_expired_license_notice', 'yes' );
	}
}