<?php
/**
 * WP Courseware Frontend.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Check to see if there is a quiz completed sitting on top of an uncompleted unit.
 *
 * @since 1.0.0
 *
 * @return void
 */
function WPCW_quiz_complete_unit_incomplete_fix() {
	global $post;

	$user_id         = get_current_user_id();
	$parentData      = WPCW_units_getAssociatedParentData( $post->ID );
	$userProgress    = new WPCW_UserProgress( $parentData->course_id, $user_id );
	$unitQuizDetails = WPCW_quizzes_getAssociatedQuizForUnit( $post->ID, $user_id, false );

	if ( $unitQuizDetails && ! $userProgress->isUnitCompleted( $post->ID ) ) {
		$unitQuizProgress = WPCW_quizzes_getUserResultsForQuiz( $user_id, $post->ID, $unitQuizDetails->quiz_id );

		if ( ! $unitQuizProgress ) {
			return;
		}

		if ( 'quiz_block' == $unitQuizDetails->quiz_type && ( $unitQuizProgress->quiz_grade >= $unitQuizDetails->quiz_pass_mark ) ) {
			WPCW_units_saveUserProgress_Complete( $user_id, $post->ID );
		}

		if ( 'quiz_noblock' == $unitQuizDetails->quiz_type && $unitQuizProgress->quiz_paging_status == 'complete' ) {
			WPCW_units_saveUserProgress_Complete( $user_id, $post->ID );
		}

		if ( 'survey' == $unitQuizDetails->quiz_type && $unitQuizProgress->quiz_paging_status == 'complete' ) {
			WPCW_units_saveUserProgress_Complete( $user_id, $post->ID );
		}
	}

	return;
}

/**
 * Course Expiration Checking
 *
 * @since 4.8.4
 *
 * @return boolean
 */
function WPCW_check_course_expiration( $post_id ) {

	if ( get_post_type() === 'course_unit' ){
		$parentData        = WPCW_units_getAssociatedParentData( $post_id );
		$post_id = $parentData->course_id;
	}

	$is_course_expired = get_post_meta( $post_id , 'is_course_expired', true );
	$course            = wpcw_get_course( $post_id );

	if ( isset( $_COOKIE['wpcw_timezone'] ) ) {
		$current_date = new DateTime( null, new DateTimeZone( $_COOKIE['wpcw_timezone'] ) );
		$today        = $current_date->format( 'Y-m-d H:i:s' );
	} else {
		$today = gmdate( 'Y-m-d H:i:s' );
	}

	if ( 'specific_interval' === $course->get_course_access_expiration() ) {
		$enrollment_date          = WPCW_users_getCourseEnrolmentActualDate( get_current_user_id(), $course->get_id() );
		$enrolment_date_interval = gmdate( 'Y-m-d H:i:s', strtotime( $enrollment_date . '+' . $course->get_cae_interval_date_int_expire() . ' ' . $course->get_cae_interval_date_string_expire() . '' ) );
	}
	
	if ( 'true' === $is_course_expired ||
		'specific_date' === $course->get_course_access_expiration() && ! empty( $course->get_cae_specific_date() ) && $today > $course->get_cae_specific_date() ||
		'specific_interval' === $course->get_course_access_expiration() && $enrollment_date && $today > $enrolment_date_interval
	) {

		//reset student progress
		if ( 'yes' === $course->get_cae_reset_on_course_expire() ) {
			
			$coursemap = new \WPCW_CourseMap();
			$coursemap->loadDetails_byCourseID( $course->get_course_id() );
			$units = $coursemap->getUnitIDList_forCourse();
			wpcw_reset_progress( $course->get_course_id(), array( get_current_user_id() ), $units );

		}

		return true;
	}

	return false;
}



/**
 * Handle showing the box that allows a user to mark a unit as completed.
 *
 * @since 1.0.0
 *
 * @return string
 */
function WPCW_units_processUnitContent( $content ) {
	global $post;

	// Ensure we're only showing a course unit, a single item
	if ( ! is_single() || 'course_unit' != get_post_type() || ! WPCW_units_getAssociatedParentData( $post->ID ) ) {
		return $content;
	}

	// Run completed quiz/incomplete unit check
	WPCW_quiz_complete_unit_incomplete_fix();

	$fe = new WPCW_UnitFrontend( $post );

	if ( WPCW_check_course_expiration( $post->ID ) ){
		return $expired_course_message = sprintf( '<p class="wpcw_expired_notification"> %s</p>', __( 'This course is expired.', 'wp-courseware' ) );
	}

	// Get associated data for this unit. No course/module data, then it's not a unit
	if ( ! $fe->check_unit_doesUnitHaveParentData() ) {
		return $content;
	}

	// Don't show any course content if password protected.
	if ( post_password_required() ) {
		return $content;
	}

	// If user is not logged in and is unit teaser.
	if ( ! $fe->check_user_isUserLoggedIn() && $fe->check_is_unit_teaser() ) {
		return $fe->render_detailsForUnit( $content );
	}

	// Ensure we're logged in
	if ( ! $fe->check_user_isUserLoggedIn() ) {
		return $fe->message_user_notLoggedIn();
	}

	// Check if user is admin or teacher.
	if ( $fe->check_is_admin_or_teacher() ) {
		return $fe->render_detailsForUnit( $content );
	}

	// If user is not logged in and is unit teaser.
	if ( ! $fe->check_user_canUserAccessCourse() && $fe->check_is_unit_teaser() ) {
		return $fe->render_detailsForUnit( $content );
	}

	// User not allowed access to content, so certainly can't say they've done this unit.
	if ( ! $fe->check_user_canUserAccessCourse() ) {
		return $fe->message_user_cannotAccessCourse();
	}

	// Is user allowed to access this unit yet?
	if ( ! $fe->check_user_canUserAccessUnit() ) {
		// DJH 2015-08-18 - Added capability for a previous button if we've stumbled
		// on a unit that we're not able to complete just yet.
		$navigationBox = $fe->render_navigation_getNavigationBox();

		// Show the navigation box AFTErR the cannot progress message.
		return $fe->message_user_cannotAccessUnit() . $navigationBox;
	}

	// Has user completed course prerequisites
	if ( ! $fe->check_user_hasCompletedCoursePrerequisites() ) {
		// on a unit that we're not able to complete just yet.
		$navigationBox = $fe->render_navigation_getNavigationBox();

		// Show navigation box after the cannot process message.
		return $fe->message_user_hasNotCompletedCoursePrerequisites() . $navigationBox;
	}

	// Do the remaining rendering...
	return $fe->render_detailsForUnit( $content );
}

/**
 * If the settings permit, generate the powered by link for WP Courseware.
 *
 * @since 1.0.0
 *
 * @return string The HTML for rendering the powered by link.
 */
function WPCW_generatedPoweredByLink() {
	$show_powered_by = wpcw()->settings->get_setting( 'show_powered_by' );

	// Show the credit link by default.
	if ( $show_powered_by == 'hide_link' || $show_powered_by == 'no' || ! $show_powered_by ) {
		return false;
	}

	$url          = 'https://flyplugins.com/?ref=1';
	$nofollow     = false;
	$affiliate_id = wpcw()->settings->get_setting( 'affiliate_id' );

	if ( $affiliate_id ) {
		$url      = str_replace( 'XXX', $affiliate_id, 'https://flyplugins.com/?ref=XXX' );
		$nofollow = 'rel="nofollow"';
	}

	return sprintf( '<div class="wpcw_powered_by">%s <a href="%s" %s target="_blank">%s</a></div>',
		__( 'Powered By', 'wp-courseware' ),
		$url,
		$nofollow,
		__( 'WP Courseware', 'wp-courseware' )
	);
}

/**
 * Get the time difference using days, hours and minutes.
 *
 * @since 1.0.0
 *
 * @param integer $futureTime The timestamp of a time in the future.
 *
 * @return string The human time in days, hours and minutes.
 */
function WPCW_date_getHumanTimeDiff( $futureTime ) {
	$humanTime = false;

	// Work out seconds between now and future time.
	$secondsLeft = $futureTime - current_time( 'timestamp' );

	$days        = floor( $secondsLeft / 86400 );
	$secondsLeft = $secondsLeft - ( $days * 86400 );

	$hours       = floor( $secondsLeft / 3600 );
	$secondsLeft = $secondsLeft - ( $hours * 3600 );

	$minutes     = floor( $secondsLeft / 60 );
	$secondsLeft = $secondsLeft - ( $minutes * 60 );

	if ( $minutes > 0 ) {
		// Now create a time based on what we've got.
		$humanTime = sprintf( _n( '%d minute', '%d minutes', $minutes, 'wp-courseware' ), $minutes );

		if ( $days > 0 || $hours > 0 ) {
			// Must add hours, because we've got days
			$humanTime = sprintf( _n( '%d hour', '%d hours', $hours, 'wp-courseware' ), $hours )
			             . ' ' . _x( 'and', 'Used in context of 4 days 7 hours and 14 minutes', 'wp-courseware' ) . ' ' . $humanTime;

			// Now add days
			if ( $days > 0 ) {
				$humanTime = sprintf( _n( '%d day', '%d days', $days, 'wp-courseware' ), $days ) . ' ' . $humanTime;
			}
		}
	} else { // Less than 1 minute remaining...
		$humanTime = sprintf( _n( '%d second', '%d seconds', $secondsLeft, 'wp-courseware' ), $secondsLeft );
	}

	return $humanTime;
}
