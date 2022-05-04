<?php
/**
 * WP Courseware Progress Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.6.4
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Reset Progress.
 *
 * @since 4.6.4
 *
 * @param array $students The list of students to reset.
 * @param array $units The list of units to evaluate.
 * @param \WPCW\Models\Course|object $course The course object.
 */
function wpcw_reset_progress( $course, $students = array(), $units = array() ) {
	global $wpcwdb, $wpdb;

	// Get Out.
	if ( empty( $students ) || empty( $units ) ) {
		return;
	}

	// Check the course instance.
	if ( ! $course instanceof \WPCW\Models\Course ) {
		$course = wpcw_get_course( $course );
	}

	$sql_units    = '(' . implode( ',', $units ) . ')';
	$sql_students = '(' . implode( ',', $students ) . ')';

	// Delete User Progress.
	$wpdb->query(
		"DELETE FROM {$wpcwdb->user_progress}
		 WHERE user_id IN {$sql_students}
		 AND unit_id IN {$sql_units}"
	);

	// Delete Quiz Data.
	$wpdb->query(
		"DELETE FROM {$wpcwdb->user_progress_quiz}
		 WHERE user_id IN {$sql_students}
		 AND unit_id IN {$sql_units}"
	);

	// Delete User Locks.
	$wpdb->query(
		"DELETE FROM {$wpcwdb->question_rand_lock}
		 WHERE question_user_id IN {$sql_students}
		 AND parent_unit_id IN {$sql_units}"
	);

	// Update User Progress.
	foreach ( $students as $student ) {
		$progress_exists = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpcwdb->user_courses}
			 WHERE user_id = %d
			 AND course_id = %d",
			$student,
			$course->get_course_id()
		) );

		if ( $progress_exists ) {
			// DJH 2015-09-09
			// Fixed reset grade sent flag.
			// Going to assume that if we're resetting any progress, then we're undoing the course completion.
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpcwdb->user_courses}
				 SET course_final_grade_sent = ''
		    	 WHERE user_id = %d
		    	 AND course_id = %d",
				$student,
				$course->get_course_id()
			) );
		}

		// DJH 2015-09-09
		// Try to delete the certificate, if we've already created one.
		// Going to assume that if we're resetting any progress, then we're undoing the course completion.
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$wpcwdb->certificates}
			WHERE cert_user_id = %d
			AND cert_course_id = %d",
			$student,
			$course->get_course_id()
		) );

		// DJH 2015-11-01
		// Ensure that the progress status matches the actual progress, rather than always resetting to 0
		// like this code did before.
		WPCW_users_updateUserUnitProgress( $course->get_course_id(), $student, $course->get_course_unit_count() );
	}
}
