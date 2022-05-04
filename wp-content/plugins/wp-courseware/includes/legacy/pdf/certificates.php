<?php
/**
 * WP Courseware Create PDF Certificates.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Create PDF Certificates.
 *
 * @since 1.0.0
 *
 * @param object $wp The WP Object.
 */
function WPCW_create_pdf_certificates( $wp ) {
	if ( ! array_key_exists( 'page', $wp->query_vars ) || $wp->query_vars['page'] != 'wpcw_pdf_create_certificate' ) {
		return;
	}

	// Grab the certificate from the parameter
	$certificateID           = WPCW_arrays_getValue( $_GET, 'certificate' );
	$certificate_template_id = ( isset( $_GET['certificate_template_id'] ) ) ? absint( $_GET['certificate_template_id'] ) : '';

	// Get Course Id
	$courseID = ( isset( $_GET['course_id'] ) ) ? absint( $_GET['course_id'] ) : false;

	if ( empty( $certificate_template_id ) && ! empty( $certificateID ) && 'preview' !== $certificateID ) {
		$certificateDetails      = WPCW_certificate_getCertificateDetails_byAccessKey( $certificateID );
		$courseDetails           = WPCW_courses_getCourseDetails( $certificateDetails->cert_course_id );
		$certificate_template_id = $courseDetails->certificate_template;
	}
	// Nothing to see.
	if ( ! $certificateID ) {
		WPCW_certificate_notFound();
	}

	if ( empty( $certificate_template_id ) || 0 === $certificate_template_id ) {
		// PREVIEW - Has a preview been requested? Is the user logged in and is permitted to preview.
		if ( 'preview' == $certificateID ) {
			if ( ! is_user_logged_in() ) {
				WPCW_certificate_notFound();
			}

			// See if the provided ID is a valid ID
			$current_user = wp_get_current_user();

			// Can't even view courses, get out.
			if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
				WPCW_certificate_notFound();
			}

			// Check course
			if ( ! $courseID ) {
				wp_die( __( 'No course was found to preview its certificate.', 'wp-courseware' ) );
			}

			// Get Course Details
			$courseDetails = WPCW_courses_getCourseDetails( $courseID );

			// Check course details
			if ( ! $courseDetails ) {
				wp_die( __( 'No certificate was found that is associated with this course.', 'wp-courseware' ) );
			}

			// Check permissions, this condition allows admins to view all modules even if they are not the author.
			if ( ! user_can( $current_user->ID, 'manage_wpcw_settings' ) && $courseDetails->course_author != $current_user->ID ) {
				wp_die( __( 'You are not allowed to preview certificates for this course.', 'wp-courseware' ) );
			}

			// Generate certificate
			$cert = new WPCW_Certificate( $courseDetails );
			$cert->generatePDF( WPCW_users_getUsersName( $current_user ), $courseDetails->course_title, false, 'browser', false, $courseID );

			die();
		} else {
			// Check database for the certificate by the ID
			$certificateDetails = WPCW_certificate_getCertificateDetails_byAccessKey( $certificateID );

			// Not a valid certificate, abort
			if ( ! $certificateDetails ) {
				WPCW_certificate_notFound();
			}

			$courseDetails = WPCW_courses_getCourseDetails( $certificateDetails->cert_course_id );
			$userInfo      = get_userdata( $certificateDetails->cert_user_id );

			// Not a valid course or user data
			if ( ! $certificateDetails || ! $userInfo ) {
				WPCW_certificate_notFound();
			}

			// Generate certificate to download
			$cert = new WPCW_Certificate( $courseDetails );
			$cert->generatePDF( WPCW_users_getUsersName( $userInfo ), $courseDetails->course_title, $certificateDetails, 'browser', false, $courseID );

			die();
		}
	} else {

		if ( empty( $certificate_template_id ) || 0 === $certificate_template_id ) {
			wp_die( __( 'No certificate template selected', 'wp-courseware' ) );
		}

		// PREVIEW - Has a preview been requested? Is the user logged in and is permitted to preview.
		if ( 'preview' == $certificateID ) {
			if ( ! is_user_logged_in() ) {
				WPCW_certificate_notFound();
			}

			// See if the provided ID is a valid ID
			$current_user = wp_get_current_user();

			// Can't even view courses, get out.
			if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
				WPCW_certificate_notFound();
			}

			// Get Course Id
			$courseID = ( isset( $_GET['course_id'] ) ) ? $_GET['course_id'] : false;

			// Check course
			if ( ! $courseID ) {
				wp_die( __( 'No course was found to preview its certificate.', 'wp-courseware' ) );
			}
			// get course details.
			//	$courseDetails = WPCW_courses_getCourseDetails( $courseID );

			if ( ! empty( $certificate_template_id ) || 0 !== $certificate_template_id ) {
				// Get Course Details

				$postdetail    = get_post( $certificate_template_id );
				$blocks        = parse_blocks( $postdetail->post_content );
				$courseDetails = $blocks[0];
				$courseTemplateDetails = $blocks[0];

			} else {
				wp_die( __( 'No certificate template selected', 'wp-courseware' ) );
			}

			// Check course details
			if ( ! $courseDetails ) {
				wp_die( __( 'No certificate was found that is associated with this course.', 'wp-courseware' ) );
			}

			// Check permissions, this condition allows admins to view all modules even if they are not the author.
			if ( ! user_can( $current_user->ID, 'manage_wpcw_settings' ) && $courseDetails->course_author != $current_user->ID ) {
				wp_die( __( 'You are not allowed to preview certificates for this course.', 'wp-courseware' ) );
			}
			$course_title = 'Course Title';
			// Generate certificate
			$cert = new WPCW_Certificate( $courseDetails );
			//$cert->generatePDF( WPCW_users_getUsersName( $current_user ), $courseDetails->course_title, $courseTemplateDetails, 'browser', true, $courseID, $certificateID );
			$cert->generatePDF( WPCW_users_getUsersName( $current_user ), $course_title, $courseTemplateDetails, 'browser', true, $courseID, $certificateID );

			die();
		} else {
			// Check database for the certificate by the ID
			$certificateDetails = WPCW_certificate_getCertificateDetails_byAccessKey( $certificateID );

			// Not a valid certificate, abort
			if ( ! $certificateDetails ) {
				WPCW_certificate_notFound();
			}

			if ( ! empty( $certificate_template_id ) || 0 !== $certificate_template_id ) {

				// Get Course Details
				$postdetail    = get_post( $certificate_template_id );
				$blocks        = parse_blocks( $postdetail->post_content );
				$courseTemplateDetails = $blocks[0];

			} else {
				wp_die( __( 'No certificate template selected.', 'wp-courseware' ) );
			}

			// $courseDetails = WPCW_courses_getCourseDetails( $certificateDetails->cert_course_id );
			$courseID = $certificateDetails->cert_course_id;
			$userInfo = get_userdata( $certificateDetails->cert_user_id );

			// Not a valid course or user data
			if ( ! $certificateDetails || ! $userInfo ) {
				WPCW_certificate_notFound();
			}

			// Generate certificate to download
			$cert = new WPCW_Certificate( $courseDetails );
			$cert->generatePDF( WPCW_users_getUsersName( $userInfo ), $courseDetails->course_title, $courseTemplateDetails, 'browser', true, $courseID, $certificateID );
			die();
		}
	}
}

/**
 * No Certificate Found Error.
 *
 * @since 1.0.0
 */
function WPCW_certificate_notFound() {
	wp_die( __( 'No certificate was found.', 'wp-courseware' ) );
}
