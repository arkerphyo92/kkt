<?php
/**
 * WP Courseware Cron Handler.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Cron.
 *
 * @since 4.3.0
 */
final class Cron {

	/**
	 * Load Cron Handler.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
		add_action( 'wp', array( $this, 'schedule_crons' ) );
		add_filter( 'cron_request', array( $this, 'disable_cron_ssl' ) );
	}

	/**
	 * Add Cron Schedules.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function add_cron_schedules( $schedules = array() ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => esc_html__( 'Once Weekly', 'wp-courseware' ),
		);

		return $schedules;
	}

	/**
	 * Schedule Cron Jobs.
	 *
	 * @since 4.3.0
	 */
	public function schedule_crons() {
		$this->weekly_cron();
		$this->daily_cron();
	}

	/**
	 * Schedule Weekly Cron Event.
	 *
	 * @since 4.3.0
	 */
	private function weekly_cron() {
		if ( ! wp_next_scheduled( 'wpcw_weekly_cron' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'weekly', 'wpcw_weekly_cron' );
		}
	}

	/**
	 * Schedule Daily Cron Event.
	 *
	 * @since 4.3.0
	 */
	private function daily_cron() {
		if ( ! wp_next_scheduled( 'wpcw_daily_cron' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'daily', 'wpcw_daily_cron' );
		}
	}

	/**
	 * Disable Cron SSL.
	 *
	 * Used for local debugging.
	 * This only happens and works when:
	 *  #1: WP_DEBUG is defined and set to true.
	 *  #2: WPCW_CRON_DISABLE_SSL is defined and set to true.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The cron request args.
	 *
	 * @return array $args The cron request args.
	 */
	public function disable_cron_ssl( $args ) {
		if ( defined( 'WPCW_CRON_DISABLE_SSL' ) && true === WPCW_CRON_DISABLE_SSL && defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$args['url'] = set_url_scheme( $args['url'], 'http' );
		}

		return $args;
	}
}