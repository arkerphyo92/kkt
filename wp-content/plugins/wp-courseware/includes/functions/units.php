<?php
/**
 * WP Courseware Units Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Unit.
 *
 * @since 4.4.0
 *
 * @param int|bool $unit_id The Unit Id.
 *
 * @return \WPCW\Models\Unit|bool An unit object or false.
 */
function wpcw_get_unit( $unit_id = false ) {
	return new \WPCW\Models\Unit( $unit_id );
}

/**
 * Insert Unit.
 *
 * @since 4.4.0
 *
 * @param array $data The unit data.
 *
 * @return \WPCW\Models\Unit|bool The unit object or false on failure.
 */
function wpcw_insert_unit( $data = array() ) {
	$unit    = new \WPCW\Models\Unit();
	$unit_id = $unit->create( $data );

	return $unit_id ? $unit : $unit_id;
}

/**
 * Get Units.
 *
 * @since 4.4.0
 *
 * @param array $args The courses query args.
 *
 * @return array The array of Course objects.
 */
function wpcw_get_units( $args = array() ) {
	return wpcw()->units->get_units( $args );
}

/**
 * Is Unit Admin or Teacher?
 *
 * @since 4.6.0
 *
 * @param int $unit_id The unit id.
 * @param int $user_id The user id.
 *
 * @return bool $is_admin_or_teacher True if the user is a unit admin or teacher.
 */
function wpcw_is_unit_admin_or_teacher( $unit_id_or_object, $user_id = 0 ) {
	$is_admin_or_teacher = false;

	if ( ! $unit_id_or_object ) {
		return $is_admin_or_teacher;
	}

	if ( ! $user_id && ! is_user_logged_in() ) {
		return $is_admin_or_teacher;
	}

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	/**
	 * Filter: Disable Admin or Teacher Check.
	 *
	 * @since 4.6.0
	 *
	 * @param bool True or False to disable the is admin or teacher check. Default is false.
	 *
	 * @return bool True or False to disable the is admin or teacher check.
	 */
	if ( apply_filters( 'wpcw_disable_is_admin_or_teacher_check', false ) ) {
		return $is_admin_or_teacher;
	}

	// Admin and Teachers Capability.
	$is_admin   = user_can( $user_id, apply_filters( 'wpcw_units_accessible_admin_capability', 'manage_wpcw_settings' ) );
	$is_teacher = user_can( $user_id, apply_filters( 'wpcw_units_accessible_minimum_capability', 'view_wpcw_courses' ) );

	// If is an admin or teacher.
	if ( $is_admin || $is_teacher ) {
		$is_admin_or_teacher = true;
	}

	// If teachers and not admins, we need to check for authorship.
	if ( ! $is_admin && $is_teacher ) {
		if ( is_object( $unit_id_or_object ) && ! empty( $unit_id_or_object->post_author ) ) {
			$unit_author = $unit_id_or_object->post_author;
		} else {
			$unit_post   = get_post( $unit_id_or_object );
			$unit_author = $unit_post->post_author;
		}

		if ( absint( $user_id ) !== absint( $unit_author ) ) {
			$is_admin_or_teacher = false;
		}
	}

	return $is_admin_or_teacher;
}

/**
 * Get Unit Label.
 *
 * @since 4.4.4
 *
 * @param bool $plural True if plural is needed.
 *
 * @return string The unit label.
 */
function wpcw_get_unit_label( $plural = false ) {
	$unit_label_setting = wpcw_get_setting( 'unit_label', 'unit' );

	$default   = esc_html__( 'Unit', 'wp-courseware' );
	$default_p = esc_html__( 'Units', 'wp-courseware' );

	$unit_label   = $default;
	$unit_label_p = $default_p;

	switch ( $unit_label_setting ) {
		case 'lesson':
			$unit_label   = esc_html__( 'Lesson', 'wp-courseware' );
			$unit_label_p = esc_html__( 'Lessons', 'wp-courseware' );
			break;
		case 'lecture':
			$unit_label   = esc_html__( 'Lecture', 'wp-courseware' );
			$unit_label_p = esc_html__( 'Lectures', 'wp-courseware' );
			break;
	}

	if ( 'custom' === $unit_label_setting ) {
		$unit_label   = wpcw_get_setting( 'unit_label_custom', $unit_label );
		$unit_label_p = wpcw_get_setting( 'unit_label_custom_plural', $unit_label_p );
	}

	return $plural ? $unit_label_p : $unit_label;
}

/**
 * Convert Unit Drip Interval.
 *
 * @since 4.6.0
 *
 * @param int    $interval
 * @param string $type
 *
 * @return int
 */
function wpcw_unit_convert_drip_interval( $interval, $type = 'interval_days' ) {
	$drip_interval = 0;

	switch ( $type ) {
		case 'interval_hours':
			$drip_interval = $interval * WPCW_TIME_HR_IN_SECS;
			break;

		case 'interval_days':
			$drip_interval = $interval * WPCW_TIME_DAY_IN_SECS;
			break;

		case 'interval_weeks':
			$drip_interval = $interval * WPCW_TIME_WEEK_IN_SECS;
			break;

		case 'interval_months':
			$drip_interval = $interval * WPCW_TIME_MONTH_IN_SECS;
			break;

		case 'interval_years':
			$drip_interval = $interval * WPCW_TIME_YEAR_IN_SECS;
			break;
	}

	return absint( $drip_interval );
}

/**
 * Unit Mark as Complete.
 *
 * @since 4.6.3
 *
 * @param int $unit_id The unit id.
 * @param int $user_id The user id.
 *
 * @return bool True upon succesfull completion, false otherwise.
 */
if ( ! function_exists( 'wpcw_unit_mark_as_complete' ) ) {
	function wpcw_unit_mark_as_complete( $unit_id, $user_id ) {
		// Get Unit Parent Data.
		$unit_parent_data = WPCW_units_getAssociatedParentData( $unit_id );

		// Check to see if it's associated.
		if ( is_null( $unit_parent_data ) || empty( $unit_parent_data ) ) {
			return false;
		}

		// Save User Progress.
		WPCW_units_saveUserProgress_Complete( $user_id, $unit_id, 'complete' );

		/**
		 * Action: WPCW User Completed Unit.
		 *
		 * @since 4.6.3
		 *
		 * @param int    $user_id The user id.
		 * @param int    $unit_id The unit id.
		 * @param object $unit_parent_data The unit parent data.
		 */
		do_action( 'wpcw_user_completed_unit', $user_id, $unit_id, $unit_parent_data );
	}
}
