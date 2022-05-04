<?php
/**
 * WP Courseware Enrollment Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Is Student Enrolled?
 *
 * @since 4.4.0
 *
 * @param int $student_id The student id.
 * @param int $course_id The course id.
 *
 * @return bool True if student is enrolled in course, false otherwise.
 */
function wpcw_is_student_enrolled( $student_id = 0, $course_id = 0 ) {
	if ( empty( $student_id ) || empty( $course_id ) ) {
		return false;
	}

	// Get the Course Object.
	$course = wpcw_get_course( absint( $course_id ) );

	// Check if course exists and return if user can access.
	return $course->exists() ? $course->can_user_access( absint( $student_id ) ) : false;
}