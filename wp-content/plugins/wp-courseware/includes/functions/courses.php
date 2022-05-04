<?php
/**
 * WP Courseware Course Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Course.
 *
 * @since 4.3.0
 *
 * @param mixed $course The Post object or ID of the course.
 *
 * @return \WPCW\Models\Course|bool An course object or false.
 */
function wpcw_get_course( $course = false ) {
	return new \WPCW\Models\Course( $course );
}

/**
 * Get Courses.
 *
 * @since 4.3.0
 *
 * @param array $args The courses query args.
 *
 * @return array The array of Course objects.
 */
function wpcw_get_courses( $args = array() ) {
	if ( isset( $args['student_id'] ) ) {
		return wpcw()->courses->get_courses_by_student( absint( $args['student_id'] ) );
	}

	return wpcw()->courses->get_courses( $args );
}

/**
 * Get Test Course.
 *
 * @since 4.3.0
 *
 * @return \WPCW\Models\Course $test_course The test course object.
 */
function wpcw_get_test_course() {
	$test_course = new \WPCW\Models\Course();

	$test_data = array(
		'course_id'                                     => rand(),
		'course_title'                                  => esc_html__( 'Free Course', 'wp-courseware' ),
		'course_desc'                                   => esc_html__( 'This is a free course.', 'wp-coursewar' ),
		'course_author'                                 => 1,
		'course_opt_completion_wall'                    => 'completion_wall',
		'course_opt_use_certificate'                    => 'use_certs',
		'course_opt_user_access'                        => 'default_show',
		'course_unit_count'                             => '1',
		'course_from_name'                              => esc_html__( 'WP Courseware', 'wp-courseware' ),
		'course_from_email'                             => 'wpcw.test.admin@wpcwtest.com',
		'course_to_email'                               => 'wpcw.test.admin@wpcwtest.com',
		'course_opt_prerequisites'                      => 'a:0:{}',
		'course_message_unit_complete'                  => 'You have now completed this unit.',
		'course_message_course_complete'                => 'You have now completed the whole course. Congratulations!',
		'course_message_unit_not_logged_in'             => 'You cannot view this unit as you\'re not logged in yet.',
		'course_message_unit_pending'                   => 'Have you completed this unit? Then mark this unit as completed.',
		'course_message_unit_no_access'                 => 'Sorry, but you\'re not allowed to access this course.',
		'course_message_prerequisite_not_met'           => 'This course can not be accessed until the prerequisites for this course are complete.',
		'course_message_unit_not_yet'                   => 'You need to complete the previous unit first.',
		'course_message_unit_not_yet_dripfeed'          => 'This unit isn\'t available just yet. Please check back in about {UNIT_UNLOCKED_TIME}.',
		'course_message_quiz_open_grading_blocking'     => 'Your quiz has been submitted for grading by the course instructor. Once your grade has been entered, you will be able to access the next unit.',
		'course_message_quiz_open_grading_non_blocking' => 'Your quiz has been submitted for grading by the course instructor. You have now completed this unit.',
		'email_complete_module_option_admin'            => 'send_email',
		'email_complete_module_option'                  => 'send_email',
		'email_complete_module_subject'                 => 'Module {MODULE_TITLE} - Complete.',
		'email_complete_module_body'                    => 'Hi {USER_NAME}

Great work for completing the "{MODULE_TITLE}" module!

{SITE_NAME}
{SITE_URL}',
		'email_complete_course_option_admin'            => 'send_email',
		'email_complete_course_option'                  => 'send_email',
		'email_complete_course_subject'                 => 'Course {COURSE_TITLE} - Complete',
		'email_complete_course_body'                    => 'Hi {USER_NAME}

Great work for completing the "{COURSE_TITLE}" training course! Fantastic!

{SITE_NAME}
{SITE_URL}',
		'email_quiz_grade_option'                       => 'send_email',
		'email_quiz_grade_subject'                      => '{COURSE_TITLE} - Your Quiz Grade - For "{QUIZ_TITLE}"',
		'email_quiz_grade_body'                         => 'Hi {USER_NAME}

Your grade for the "{QUIZ_TITLE}" quiz is:
{QUIZ_GRADE}

This was for the quiz at the end of this unit:
{UNIT_URL}

{QUIZ_RESULT_DETAIL}

{SITE_NAME}
{SITE_URL}',
		'email_complete_course_grade_summary_subject'   => 'Your final grade summary for "{COURSE_TITLE}"',
		'email_complete_course_grade_summary_body'      => 'Hi {USER_NAME}

Congratulations on completing the "{COURSE_TITLE}" training course! Fantastic!

Your final grade is: {CUMULATIVE_GRADE}

Here is a summary of your quiz results:
{QUIZ_SUMMARY}

You can download your certificate here:
{CERTIFICATE_LINK}

I hope you enjoyed the course!

{SITE_NAME}
{SITE_URL}',
		'email_unit_unlocked_subject'                   => 'Your next unit ({UNIT_TITLE}) is now available!',
		'email_unit_unlocked_body'                      => 'Hi {USER_NAME}

Good news! You can now access the next unit!

The unit called "{UNIT_TITLE}" from "{COURSE_TITLE}" is now available for you to access.

To access the unit, just click on this link:
{UNIT_URL}

Thanks!

{SITE_NAME}
{SITE_URL}',
		'cert_signature_type'                           => 'text',
		'cert_sig_text'                                 => 'WP Courseware',
		'cert_sig_image_url'                            => '',
		'cert_logo_enabled'                             => 'no_cert_logo',
		'cert_logo_url'                                 => '',
		'cert_background_type'                          => 'use_default',
		'cert_background_custom_url'                    => '',
		'payments_type'                                 => 'free',
		'payments_price'                                => '5.00',
		'payments_interval'                             => 'month',
		'course_access_expiration'                      => 'none',
		'cae_specific_date'                             => '0000-00-00 00:00:00',
		'cae_interval_date_int_expire'                  => 1,
		'cae_interval_date_string_expire'               => 'hour',
		'cae_reset_on_course_expire'                    => 'no',
	);

	$test_course->set_data( $test_data );

	return $test_course;
}

/**
 * Get Course Title.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Course The course object.
 *
 * @return string The course title.
 */
function wpcw_course_get_title( $course ) {
	if ( ! $course instanceof \WPCW\Models\Course ) {
		return;
	}

	return esc_html( $course->get_course_title() );
}

/**
 * Get Course Description.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Course The course description.
 *
 * @return string The course description.
 */
function wpcw_course_get_desc( $course ) {
	if ( ! $course instanceof \WPCW\Models\Course ) {
		return;
	}

	return wp_kses_post( $course->get_course_desc() );
}

/**
 * Get Course Post Id.
 *
 * @since 4.4.0
 *
 * @param int $course_id The course id.
 *
 * @return int|bool The course post id or false.
 */
function wpcw_course_get_post_id( $course_id ) {
	return wpcw()->courses->get_course_post_id( $course_id );
}

/**
 * Get Course Thumbnail.
 *
 * @since 4.4.0
 *
 * @param \WPCW\Models\Course The course object.
 * @param string $size The thumbnail size.
 *
 * @return string The course thumbnail image.
 */
function wpcw_course_get_thumbnail( $course, $size = 'post-thumbnail' ) {
	if ( ! $course instanceof \WPCW\Models\Course ) {
		return;
	}

	return wp_kses_post( $course->get_thumbnail_image( $size ) );
}

/**
 * Get Purchase Type.
 *
 * @since 4.3.0
 *
 * @param int $course_id The course id.
 */
function wpcw_course_get_payments_type( $course_id ) {
	return wpcw()->courses->get_course_payments_type( $course_id );
}

/**
 * Get Course Enroll / Purchase button.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Course The course description.
 *
 * @return string The course enroll / purchase button.
 */
function wpcw_course_get_enroll_purchase_button( $course ) {
	if ( ! $course instanceof \WPCW\Models\Course ) {
		return;
	}

	return ( $course->is_purchasable() ) ? wpcw_add_to_cart_link( $course, array(), false ) : do_shortcode( '[wpcw_course_enroll courses=' . $course->get_course_id() . ']' );
}

/**
 * Get Default Email From Name.
 *
 * @since 4.4.0
 *
 * @return string The email from name.
 */
function wpcw_course_get_default_email_from_name() {
	return apply_filters( 'wpcw_course_default_email_from_name', get_bloginfo( 'name' ) );
}

/**
 * Get Default Email From Email.
 *
 * @since 4.4.0
 *
 * @return string The default from email.
 */
function wpcw_course_get_default_email_from_email() {
	$current_user = wp_get_current_user();

	$from_email = ( $current_user->user_email != get_bloginfo( 'admin_email' ) ) ? $current_user->user_email : get_bloginfo( 'admin_email' );

	return apply_filters( 'wpcw_course_default_from_email', $from_email );
}

/**
 * Get Default Email To Email.
 *
 * @since 4.4.0
 *
 * @return string The default from email.
 */
function wpcw_course_get_default_email_to_email() {
	$current_user = wp_get_current_user();

	$from_email = ( $current_user->user_email != get_bloginfo( 'admin_email' ) ) ? $current_user->user_email : get_bloginfo( 'admin_email' );

	return apply_filters( 'wpcw_course_default_to_email', $from_email );
}

/**
 * Sanitize Course ID.
 *
 * Make sure it's a positive number and greater than zero.
 *
 * @since 4.4.5
 *
 * @param int|stirng $course_id The Course ID.
 *
 * @return int|bool The Course ID or False if not valid.
 */
function wpcw_sanitize_course_id( $course_id ) {
	if ( ! is_numeric( $course_id ) ) {
		return false;
	}

	$course_id = (int) $course_id;

	// We were given a non positive number
	if ( absint( $course_id ) !== $course_id ) {
		return false;
	}

	if ( empty( $course_id ) ) {
		return false;
	}

	return absint( $course_id );
}

/**
 * Retrieve Course Meta Field.
 *
 * @since 4.4.5
 *
 * @param int $course_id The Course ID.
 * @param string $meta_key The Course Meta Key.
 * @param bool $single Whether to return a single value.
 *
 * @return mixed|array The Course Meta Value. Array if $single is false.
 */
function wpcw_get_course_meta( $course_id = 0, $meta_key = '', $single = false ) {
	$course_id = wpcw_sanitize_course_id( $course_id );

	if ( false === $course_id ) {
		return false;
	}

	return get_metadata( 'wpcw_course', absint( $course_id ), $meta_key, $single );
}

/**
 * Update Course Meta Field.
 *
 * Use the $prev_value parameter to differentiate between
 * meta fields with the same key and Course ID. If the meta
 * field for the customer does not exist, it will be added.
 *
 * @since 4.4.5
 *
 * @param int $course_id The Course ID.
 * @param string $meta_key The Course Meta Key.
 * @param mixed $meta_value The Course Meta Value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 *
 * @return bool False on failure, True if successful.
 */
function wpcw_update_course_meta( $meta_value, $course_id = 0, $meta_key = '', $prev_value = '' ) {
	$course_id = wpcw_sanitize_course_id( $course_id );

	if ( false === $course_id ) {
		return false;
	}

	return update_metadata( 'wpcw_course', $course_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Add Meta Field to Course.
 *
 * @since 4.4.5
 *
 * @param int $course_id The Course ID.
 * @param string $meta_key The Course Meta Key.
 * @param mixed $meta_value The Course Meta Value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 *
 * @return bool False on failure, True if successful.
 */
function wpcw_add_course_meta( $meta_value, $course_id = 0, $meta_key = '', $unique = false ) {
	$course_id = wpcw_sanitize_course_id( $course_id );

	if ( false === $course_id ) {
		return false;
	}

	return add_metadata( 'wpcw_course', $course_id, $meta_key, $meta_value, $unique );
}

/**
 * Delete Course Meta Field.
 *
 * You can match based on the key, or key and value. Removing
 * based on key and value, will keep from removing duplicate
 * metadata with the same key. It also allows removing all
 * metadata matching key, if needed.
 *
 * @since 4.4.5
 *
 * @param int $course_id The Course ID.
 * @param string $meta_key The Course Meta Key.
 * @param mixed $meta_value Optional. The Course Meta Value.
 *
 * @return bool False on failure, True if successful.
 */
function wpcw_delete_course_meta( $course_id = 0, $meta_key = '', $meta_value = '' ) {
	return delete_metadata( 'wpcw_course', $course_id, $meta_key, $meta_value );
}

/* ---------- Template Functions ---------- */

if ( ! function_exists( 'wpcw_get_course_title' ) ) {
	/**
	 * Get Course Title.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course title.
	 */
	function wpcw_get_course_title() {
		/** @var \WPCW\Models\Course $course */
		global $course;

		return $course->get_course_title();
	}
}

if ( ! function_exists( 'wpcw_get_course_desc' ) ) {
	/**
	 * Get Course Description.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course title.
	 */
	function wpcw_get_course_desc() {
		/** @var \WPCW\Models\Course $course */
		global $course;

		$desc = $course->get_course_desc();

		return wpautop( $desc );
	}
}

if ( ! function_exists( 'wpcw_get_course_thumbnail' ) ) {
	/**
	 * Get Course Description.
	 *
	 * @since 4.4.0
	 *
	 * @param string $size The course thumbnail size.
	 *
	 * @return string The course title.
	 */
	function wpcw_get_course_thumbnail( $size = 'post-thumbnail' ) {
		/** @var \WPCW\Models\Course $course */
		global $course;

		return $course->get_thumbnail_image( $size );
	}
}

if ( ! function_exists( 'wpcw_get_course_enrollment_button' ) ) {
	/**
	 * Get Course Enrollment Button.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course title.
	 */
	function wpcw_get_course_enrollment_button() {
		/** @var \WPCW\Models\Course $course */
		global $course;

		return $course->get_enrollment_button();
	}
}

if ( ! function_exists( 'wpcw_get_course_outline' ) ) {
	/**
	 * Get Course Outline.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course outline.
	 */
	function wpcw_get_course_outline() {
		/** @var \WPCW\Models\Course $course */
		global $course;

		return $course->get_outline();
	}
}
