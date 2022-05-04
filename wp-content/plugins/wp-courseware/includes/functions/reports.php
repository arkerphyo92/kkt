<?php
/**
 * WP Courseware Reports Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Calender Days in a Month.
 *
 * @since 4.3.0
 *
 * @return int The number of days in a given calendar month.
 */
if ( ! function_exists( 'wpcw_cal_days_in_month' ) ) {
	function wpcw_cal_days_in_month( $calendar, $month, $year ) {
		// Preferred but if calendar extension not loaded, use below.
		if ( function_exists( 'cal_days_in_month' ) ) {
			return cal_days_in_month( $calendar, $month, $year );
		}

		return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}
}