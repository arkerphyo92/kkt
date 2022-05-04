<?php
/**
 * WP Courseware Shortcodes.
 *
 * @package WPCW
 * @since 1.0.0
 */

use WPCW\Models\Course;

/**
 * Creates widget that shows off a training course, its modules, and its units.
 *
 *  e.g. [wpcourse course="2" showunits="true" /]
 *
 * @since 1.0.0
 *
 * @param array $atts The attributes of the shortcode.
 * @param string $content The content of the shortcode.
 *
 * @return string
 */
function WPCW_shortcodes_showTrainingCourse( $atts, $content = '' ) {
	extract( shortcode_atts( array(
		'course' => 0,
	), $atts ) );

	// Just pass arguments straight on
	return WPCW_courses_renderCourseList( $course, $atts );
}

/**
 * Function that renders a course list.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course to show.
 * @param array $options The list of options to show.
 *
 * @return string
 */
function WPCW_courses_renderCourseList( $courseID, $options ) {
	extract( shortcode_atts( array(
		'module'                => 0,
		'module_desc'           => false,
		'show_title'            => false,
		'show_desc'             => false,

		// Hide the credit link if requested
		'hide_credit_link'      => false,

		// Widget Only: Easy way to determine if in widget mode.
		'widget_mode'           => false,

		// Widget Only
		'show_toggle_col'       => false,

		// Widget Only: Handle widget showing / hiding capability.
		'show_modules_previous' => 'all',
		'show_modules_next'     => 'all',
		'toggle_modules'        => 'expand_all',

		// Handle quiz grade on unit
		'user_quiz_grade'       => false,
		'reset_progress'        => false,
	), $options ) );

	// Check settings to to see if they are true
	$module_desc      = ( $module_desc == 'true' );
	$show_title       = ( $show_title == 'true' );
	$show_desc        = ( $show_desc == 'true' );
	$hide_credit_link = ( $hide_credit_link == 'true' );
	$widget_mode      = ( $widget_mode == 'true' );
	$show_toggle_col  = ( $show_toggle_col == 'true' );
	$user_quiz_grade  = ( $user_quiz_grade == 'true' );
	$reset_progress   = ( $reset_progress == 'true' );

	$courseDetails = false;
	$parentData    = false;

	global $post;

	// Show course based on current location for user. Use the currently shown post
	// to work out which course to show using the associated parent data.
	if ( 'current' == $courseID && $post ) {
		$parentData = WPCW_units_getAssociatedParentData( $post->ID );
		if ( $parentData ) {
			$courseDetails = WPCW_courses_getCourseDetails( $parentData->parent_course_id );
			$courseID      = $parentData->parent_course_id;
		} else {
			return false;
		}
	} else {
		// Check course ID is valid
		$courseDetails = WPCW_courses_getCourseDetails( $courseID );
		if ( ! $courseDetails ) {
			return __( 'Unrecognised course ID.', 'wp-courseware' );
		}

		// Course ID is fine, get associated parent data for
		// hiding aspects of the widget
		if ( $post ) {
			$parentData = WPCW_units_getAssociatedParentData( $post->ID );
		}
	}

	$moduleList = false;

	// Do we just want a single module?
	if ( $module > 0 ) {
		// Get module by module number within course (not the module ID)
		$moduleDetailsSingle = WPCW_modules_getModuleDetails_byModuleNumber( $courseDetails->course_id, $module );

		if ( ! $moduleDetailsSingle ) {
			return __( 'Could not find module.', 'wp-courseware' );
		}

		// Create module list of 1 using single module
		$moduleList[ $moduleDetailsSingle->module_id ] = $moduleDetailsSingle;
	} else {
		// Check there are modules
		$moduleList = WPCW_courses_getModuleDetailsList( $courseID );
		if ( ! $moduleList ) {
			return __( 'There are no modules in this training course.', 'wp-courseware' );
		}
	}

	$html = false;

	// Show course title.
	if ( $show_title ) {
		$html .= sprintf( __( '<div class="wpcw_fe_course_title">%s</div>', 'wp-courseware' ), $courseDetails->course_title );
	}

	// Show course description.
	if ( $show_desc ) {
		$html .= sprintf( __( '<div class="wpcw_fe_course_desc">%s</div>', 'wp-courseware' ), $courseDetails->course_desc );
	}

	// Start Table.
	$html .= '<table id="wpcw_fe_course" class="wpcw_fe_table" cellspacing="0" cellborder="0">';

	$showUnitLinks = false;
	$colCount      = 2;
	$userProgress  = false;
	$user_id       = get_current_user_id();

	// User Progress Data.
	if ( is_user_logged_in() && 0 !== $user_id ) {
		$userProgress = new WPCW_UserProgress( $courseID, $user_id, $courseDetails );

		// Show links for user if they are allowed to access this course.
		if ( $userProgress->canUserAccessCourse() ) {
			// User is logged in and can do course, so show the stuff they can do.
			$showUnitLinks = true;

			// Got an extra column to show progress
			$colCount = 3;
		}
	}

	// If we're showing a widget, and we have the parent data based on the
	// currently viewed unit, then change what's in the widget in terms
	// of previous / next units.
	$hideList = array();

	// Widget Logic.
	if ( $widget_mode && $module == 0 && $parentData ) {
		// Build a list of the modules before and after the current
		// module, so that we can more easily control what's visible,
		// and what's not.
		$modulesBefore = array();
		$modulesAfter  = array();
		$currentList   = &$modulesBefore;

		foreach ( $moduleList as $moduleID => $moduleObj ) {
			// Switch lists, we've found the current module
			if ( $moduleID == $parentData->parent_module_id ) {
				$currentList = &$modulesAfter;
			} else {
				// Any other module, just add to the list (which is either the before or after).
				$currentList[] = $moduleID;
			}
		}

		// Handle showing previous modules
		switch ( $show_modules_previous ) {
			// All items in the before list to be hidden
			case 'none':
				$hideList = array_merge( $hideList, $modulesBefore );
				break;

			case 'all':
				break;

			// Keep a specific number of modules to show.
			default:
				$show_modules_previous += 0;
				$modulesToPickFrom     = count( $modulesBefore );

				// Remove the modules at the start of the list, leaving the right number of
				// $show_modules_previous modules in the list.
				if ( $show_modules_previous > 0 && $modulesToPickFrom > $show_modules_previous ) {
					$hideList = array_merge( $hideList, ( array_slice( $modulesBefore, 0, ( $modulesToPickFrom - $show_modules_previous ) ) ) );
				}
				break;
		}

		// Handle showing the next modules.
		switch ( $show_modules_next ) {
			// All all items in the after list to be hidden
			case 'none':
				$hideList = array_merge( $hideList, $modulesAfter );
				break;

			case 'all':
				break;

			// Keep a specific number of modules to show.
			default:
				$show_modules_next += 0;
				$modulesToPickFrom = count( $modulesAfter );

				// Remove the modules at the start of the list, leaving the right number of
				// $show_modules_previous modules in the list.
				if ( $show_modules_next > 0 && $modulesToPickFrom > $show_modules_next ) {
					$hideList = array_merge( $hideList, ( array_slice( $modulesAfter, $show_modules_next ) ) );
				}
				break;
		}
	}

	// Columns for marking item as being pending or complete.
	$progress_Complete = '<td class="wpcw_fe_unit_progress wpcw_fe_unit_progress_complete"><span aria-label="complete" class="wpcw_checkmark" role="img"></span></td>';
	$progress_Pending  = '<td class="wpcw_fe_unit_progress wpcw_fe_unit_progress_incomplete"><span class="wpcw_circle"></span></td>';
	$progress_Blank    = '<td class="wpcw_fe_unit_progress"><span class="wpcw_circle"></span></td>';

	// Show Modules
	foreach ( $moduleList as $moduleID => $moduleObj ) {
		if ( in_array( $moduleID, $hideList ) ) {
			continue;
		}

		// If $collapseTitleArea is set to true, then the module will be collapsed. So just check what to hide
		// based on the contents of $toggle_modules
		$collapseTitleArea = false;

		// Widget Only Logic.
		if ( $widget_mode ) {
			switch ( $toggle_modules ) {
				case 'contract_all':
					$collapseTitleArea = true;
					break;

				// See if the currently visible unit module is the one being rendered.
				case 'contract_all_but_current':
					$collapseTitleArea = true; // Contract all by default.

					// We're showing the current module.
					if ( $parentData && $moduleID == $parentData->parent_module_id ) {
						$collapseTitleArea = false;
					}
					break;
			}
		}

		$quizGradeArea     = false;
		$resetProgressArea = false;
		$unitProgressArea  = false;

		// Widget Logic
		if ( $widget_mode && $show_toggle_col ) {
			$moduleTitleArea = sprintf( __( '<th>%s</th><th class="wpcw_fe_toggle">%s</th>', 'wp-courseware' ), $moduleObj->module_title, ( $collapseTitleArea ? '+' : '-' ) );
		} else {
			$moduleTitleArea = sprintf( '<th class="wpcw_fe_module_desc_header">%s</th>', $moduleObj->module_title );

			// Add Extra Column for user progress.
			if ( is_user_logged_in() && $userProgress->canUserAccessCourse() ) {
				if ( $user_quiz_grade ) {
					$moduleTitleArea = sprintf( __( '<th class="wpcw_fe_unit_header">%s</th>', 'wp-courseware' ), $moduleObj->module_title );
					$quizGradeArea   = sprintf( '<th class="wpcw_fe_quiz_header">%s</th>', __( 'Quiz Grade', 'wp-courseware' ) );
				}

				$unitProgressArea = '<th class="wpcw_fe_unit_progress_header">&nbsp;</th>';

				if ( $reset_progress ) {
					$resetProgressArea = '<th class="wpcw_fe_unit_progress_reset_header">&nbsp;</th>';
				}
			}
		}

		// Render final title bit
		$html .= sprintf(
			'<tr class="wpcw_fe_module%s%s" id="wpcw_fe_module_group_%d">
				<th class="wpcw_fe_module_title_header">%s %d</th>
				' . $moduleTitleArea . '
				' . $quizGradeArea . '
				' . $unitProgressArea . '
				' . $resetProgressArea . '
			</tr>',
			( $collapseTitleArea ? ' wpcw_fe_module_toggle_hide' : '' ),
			( $parentData && $moduleID == $parentData->parent_module_id ? ' active' : '' ),
			$moduleObj->module_number, __( 'Module', 'wp-courseware' ),
			$moduleObj->module_number,
			$moduleTitleArea
		);

		// Render module description
		if ( $module_desc ) {
			if ( $widget_mode && $show_toggle_col ) {
				$colCount ++;
			}

			if ( $user_quiz_grade && is_user_logged_in() && $userProgress->canUserAccessCourse() ) {
				$html .= sprintf( __( '<tr class="wpcw_fe_module_des"><td colspan="%d">%s</td></tr>', 'wp-courseware' ), $colCount + 1, $moduleObj->module_desc );
			} elseif ( is_user_logged_in() && $userProgress->canUserAccessCourse() ) {
				if ( $widget_mode && $show_toggle_col ) {
					$colCount --;
				}
				$html .= sprintf( __( '<tr class="wpcw_fe_module_des"><td colspan="%d">%s</td></tr>', 'wp-courseware' ), $colCount, $moduleObj->module_desc );
			} else {
				$html .= sprintf( __( '<tr class="wpcw_fe_module_des"><td colspan="%d">%s</td></tr>', 'wp-courseware' ), $colCount, $moduleObj->module_desc );
			}
		}

		// Add the class for the row that matches the parent module ID.
		$moduleRowClass = ' wpcw_fe_module_group_' . $moduleObj->module_number;

		// Get Units
		$units = WPCW_units_getListOfUnits( $moduleID );

		// Render Units
		if ( ! $units ) {
			$html .= sprintf(
				'<tr class="wpcw_fe_unit wpcw_fe_unit_none %s">
					<td colspan="%s">%s</td>
				</tr>',
				$moduleRowClass,
				$colCount,
				__( 'There are no units in this module.', 'wp-courseware' )
			);
		} else {
			foreach ( $units as $unit ) {
				$progressRow      = false;
				$progressCol      = false;
				$quizGradeCol     = false;
				$resetProgressCol = false;

				// Show links for units
				if ( $showUnitLinks ) {
					// Yes we are showing progress data... see what state we're at.
					if ( $userProgress ) {
						if ( $userProgress->isUnitCompleted( $unit->ID ) ) {
							$progressCol = $progress_Complete;
							$progressRow = 'wpcw_fe_unit_complete';

							if ( $reset_progress ) {
								$resetProgressCol = '<td class="wpcw_fe_reset_progress">
									<a class="wpcw_reset_unit_progress"
									   href="#"
									   data-unit-id="' . $unit->ID . '"
									   data-student-id="' . get_current_user_id() . '"
									   data-reset-type="' . ( 'completion_wall' === $courseDetails->course_opt_completion_wall ? 'bulk' : 'single' ) . '"><i class="wpcw-fas wpcw-fa-redo"></i></a>
								</td>';
							}
						} else {
							$progressCol = $progress_Pending;
							$progressRow = 'wpcw_fe_unit_pending';
						}
					}

					if ( is_user_logged_in() && $user_quiz_grade && ! $widget_mode ) {
						$quizGradeCol = '<td class="wpcw_fe_quiz">' . esc_html__( '-', 'wp-courseware' ) . '</td>';
					}

					if ( is_user_logged_in() && ! $resetProgressCol && $reset_progress ) {
						$resetProgressCol = '<td class="wpcw_fe_reset_progress">&nbsp;</td>';
					}

					// See if the user is allowed to access this unit or not.
					if ( $userProgress->canUserAccessUnit( $unit->ID ) ) {
						// Only render quiz grade if shortcode param is true and isn't a widget
						if ( $user_quiz_grade && ! $widget_mode ) {
							// If quiz data exists for unit, get it.
							if ( $quizDetails = WPCW_quizzes_getAssociatedQuizForUnit( $unit->ID, $user_id, false ) ) {
								// Fetch quiz results for user.
								$quizResults = WPCW_quizzes_getUserResultsForQuiz( $user_id, $unit->ID, $quizDetails->quiz_id );
								if ( $quizResults ) {
									// Check to see if quiz has been graded.
									if ( $quizResults->quiz_grade > 0 ) {
										// Prepare grade to be displayed on course outline.
										$quizGrade    = number_format( $quizResults->quiz_grade, 2 ) . '%%';
										$quizGradeCol = sprintf( '<td class="wpcw_fe_quiz">%s</td>', $quizGrade );
									} else {
										$quizGradeCol = sprintf( '<td class="wpcw_fe_quiz">' . esc_html__( '-', 'wp-courseware' ) . '</td>' );
									}
								}
							}
						}

						// Main unit title, link and unit number
						$html .= sprintf(
							'<tr class="wpcw_fe_unit %s %s %s">
								<td class="wpcw_fe_unit_title">%s %d</td>
								<td class="wpcw_fe_unit"><a href="%s">%s</a></td>
								' . $quizGradeCol . '
								' . $progressCol . '
								' . $resetProgressCol . '
							</tr>',
							$progressRow,
							$moduleRowClass,
							( $post && ( $post->ID == $unit->ID ) ? 'active' : '' ),
							wpcw_get_unit_label(),
							$unit->unit_meta->unit_number,
							get_permalink( $unit->ID ),
							$unit->post_title );
					} else {
						// If we're not allowed to access the unit, then it's always marked as pending.
						$html .= sprintf(
							'<tr class="wpcw_fe_unit %s%s">
								<td class="wpcw_fe_unit_title">%s %d</td>
								<td class="wpcw_fe_unit">%s</td>
								' . $quizGradeCol . '
								' . $progress_Pending . '
								' . $resetProgressCol . '
							</tr>',
							$progressRow,
							$moduleRowClass,
							wpcw_get_unit_label(),
							$unit->unit_meta->unit_number,
							$unit->post_title
						);
					}
				} else {
					if ( $unit->unit_meta->unit_teaser ) {
						// Main unit title, link and unit number
						$html .= sprintf(
							'<tr class="wpcw_fe_unit %s %s %s">
								<td class="wpcw_fe_unit_title">%s %d</td>
								<td class="wpcw_fe_unit"><a href="%s">%s%s</a></td>
							</tr>',
							$progressRow,
							$moduleRowClass,
							( $post && ( $post->ID == $unit->ID ) ? 'active' : '' ),
							wpcw_get_unit_label(),
							$unit->unit_meta->unit_number,
							get_permalink( $unit->ID ),
							$unit->post_title,
							$unit->unit_meta->unit_teaser ? apply_filters( 'wpcw_unit_preview_text', '&nbsp; - <em>' . esc_html__( 'Preview', 'wp-courseware' ) . '</em>', $unit ) : ''
						);
					} else {
						$html .= sprintf(
							'<tr class="wpcw_fe_unit ' . $progressRow . $moduleRowClass . '">
								<td class="wpcw_fe_unit_title">%s %d</td>
								<td class="wpcw_fe_unit" %s>%s</td>
							</tr>',
							wpcw_get_unit_label(),
							$unit->unit_meta->unit_number,
							( $widget_mode && $show_toggle_col ) ? 'colspan="2"' : '',
							$unit->post_title
						);
					}
				}
			}
		}
	}

	// End Table.
	$html .= '</table>';

	// Credit Link with manual override.
	if ( ! $hide_credit_link ) {
		$html .= WPCW_generatedPoweredByLink();
	}

	return $html;
}

/**
 * Creates widget that shows off the user's progress on their respective courses.
 *
 *  e.g. [wpcourse_progress courses="2" user_progress="true" user_grade="true" /]
 *
 * @since 1.0.0
 *
 * @param array $atts The attributes of the shortcode.
 * @param string $content The content of the shortcode.
 *
 * @return string
 */
function WPCW_shortcodes_showTrainingCourseProgress( $atts, $content ) {
	extract( shortcode_atts( array(
		'courses'              => 'all',
		'course_desc'          => false,
		'course_prerequisites' => false,
		'user_progress'        => true,
		'user_grade'           => true,
		'user_quiz_grade'      => false,
		'certificate'          => true,
		'hide_credit_link'     => false,
		'hide_completed'       => false,
		'reset_progress'       => false,
	), $atts ) );

	// Check flags to see what we're showing
	$showCourseDesc          = ( 'true' == strtolower( $course_desc ) );
	$showCoursePrerequisites = ( 'true' == strtolower( $course_prerequisites ) );
	$showUserProgress        = ( 'true' == strtolower( $user_progress ) );
	$showUserGrade           = ( 'true' == strtolower( $user_grade ) );
	$showCertificate         = ( 'true' == strtolower( $certificate ) );
	$hideCompleted           = ( 'true' == strtolower( $hide_completed ) );

	// Show a message to the user if they are not logged in.
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return sprintf( __( '<div class="wpcw_fe_progress_box_wrap"><div class="wpcw_fe_progress_box wpcw_fe_progress_box_error">%s</div></div>', 'wp-courseware' ),
			apply_filters( 'wpcw_front_shortcode_wpcourse_progress_notloggedin', __( 'You need to be logged in to see your course progress.', 'wp-courseware' ) )
		);
	}

	// Get a list of all of the courses that the user is subscribed to.
	$courseList         = WPCW_users_getUserCourseList( $user_id );
	$selectedCourseList = array();

	// Check if courses are published
	if ( $courseList ){
		foreach( $courseList as $key => $course ){
			$course = new Course( $course->course_id );
			if ( WPCW_check_course_expiration( $course->course_post_id ) ) {
				unset( $courseList[ $key ] );
			}
			$status = array( 'draft', 'private', 'trash', 'auto-draft', 'pending' );
			if ( in_array( $course->get_course_post_status(), $status ) ){
				unset( $courseList[ $key ] );
			}
		}
	}

	// Remove courses that are completed.
	if ( $hideCompleted ) {
		foreach ( $courseList as $key => $course ) {
			$userProgress = new WPCW_UserProgress( $course->course_id, $user_id );

			if ( $userProgress->isCourseCompleted() ) {
				unset( $courseList[ $key ] );
			}
		}
	}

	// Filter the list of courses to remove the ones that the trainer doesn't
	// want the user to see. 'all' means show all courses with no filtering.
	// Only do this check if we have any courses to check, to save time.
	if ( ! empty( $courseList ) && 'all' != strtolower( $courses ) ) {
		$selectedCourseList = explode( ',', $courses );

		// This is the list of courses we'll actually use.
		$chosenListOfCourses = array();

		// We've got courses that have been specified, so we need to go through them now.
		if ( ! empty( $selectedCourseList ) ) {
			foreach ( $selectedCourseList as $potentialItem ) {
				$potentialItem = trim( $potentialItem );

				// Got a potential ID here.
				if ( preg_match( '/^([0-9]+)$/', $potentialItem ) ) {
					// Check each course we still have to see if the ID matches.
					// I know it's O(N), but it's simple at least.
					foreach ( $courseList as $idx => $aSingleCourse ) {
						// Got a match...
						if ( $potentialItem == $aSingleCourse->course_id ) {
							// Move the chosen course to the selected list. Doing
							// so makes subsequent searches faster.
							$chosenListOfCourses[] = $aSingleCourse;
							unset( $courseList[ $idx ] );
							break;
						}
					}
				}
			}
		}

		// Overwrite the list of courses to use.
		$courseList = $chosenListOfCourses;
	}

	// Handle when the list is empty
	if ( empty( $courseList ) ) {
		// Change message slightly based on how many courses are selected.
		$messageToShow = __( 'You are not enrolled in any courses.', 'wp-courseware' );
		if ( ! empty( $selectedCourseList ) ) {
			$messageToShow = __( 'You are not enrolled in any of these courses.', 'wp-courseware' );
		}

		return sprintf( __( '<div class="wpcw_fe_progress_box_wrap"><div class="wpcw_fe_progress_box wpcw_fe_progress_box_error">%s</div></div>', 'wp-courseware' ),
			apply_filters( 'wpcw_front_shortcode_wpcourse_progress_no_courses', $messageToShow, count( $courseList ) )
		);
	}

	// Used to determine how many columns we have in the table for showing the course details.
	$columnCount = 1;

	// Show the list of courses
	$html = '<table id="wpcw_fe_course_progress" class="wpcw_fe_table wpcw_fe_summary_course_progress">';

	// The title bar for the course.
	$html .= '<thead><tr>';

	// Course name
	$html .= sprintf( '<th scope="col" class="wpcw_fe_course_progress_course">%s</th>', __( 'Course', 'wp-courseware' ) );

	// Course progress
	if ( $showUserProgress ) {
		$columnCount ++;
		$html .= sprintf( '<th scope="col" class="wpcw_fe_course_progress_pc">%s</th>', __( 'Progress', 'wp-courseware' ) );
	}

	// Overall grade so far
	if ( $showUserGrade ) {
		$columnCount ++;
		$html .= sprintf( '<th scope="col" class="wpcw_fe_course_progress_grade">%s</th>', __( 'Overall Grade', 'wp-courseware' ) );
	}

	// Overall grade so far
	if ( $showCertificate ) {
		$columnCount ++;
		$html .= sprintf( '<th scope="col" class="wpcw_fe_course_progress_certificate">%s</th>', __( 'Certificate', 'wp-courseware' ) );
	}

	$html .= '</tr></thead><tbody>';

	// The main body of the course information.
	foreach ( $courseList as $aSingleCourse ) {
		$html .= '<tr class="wpcw_fe_course_progress_row">';

		// Course name
		$html .= sprintf( __( '<td class="wpcw_fe_course_progress_course"><a href="#" data-toggle="wpcw_fe_course_progress_detail_%d">%s</a></td>', 'wp-courseware' ), $aSingleCourse->course_id, $aSingleCourse->course_title );

		// Course progress
		if ( $showUserProgress ) {
			$html .= sprintf( '<td class="wpcw_fe_course_progress_pc">%s</td>', WPCW_content_progressBar( $aSingleCourse->course_progress ) );
		}

		// Show the Overall grade so far
		if ( $showUserGrade ) {
			$overallGrade = WPCW_courses_getCourseCumulativeGrade( $aSingleCourse->course_id, $user_id );
			if ( $overallGrade === '-' || $overallGrade === 'N/A' ) {
				$html .= sprintf( '<td class="wpcw_fe_course_progress_grade">%s</td>', $overallGrade );
			} else {
				$html .= sprintf( '<td class="wpcw_fe_course_progress_grade">%s%%</td>', $overallGrade );
			}
		}

		// Show certificate for course if available
		if ( $showCertificate ) {
			$courseDetails     = WPCW_courses_getCourseDetails( $aSingleCourse->course_id );
			$usingCertificates = ( 'use_certs' == $courseDetails->course_opt_use_certificate );

			// Generate certificate button if enabled and a certificate exists for this user.
			if ( $usingCertificates && $certificateDetails = WPCW_certificate_getCertificateDetails( $user_id, $aSingleCourse->course_id, false ) ) {
				$html .= sprintf( '<td class="wpcw_fe_course_progress_certificate"><a href="%s" class="fe_btn fe_btn_download" target="_blank">%s</a></td>',
					WPCW_certificate_generateLink( $certificateDetails->cert_access_key ), __( 'Download Certificate', 'wp-courseware' ) );
			} else {
				$html .= sprintf( '<td class="wpcw_fe_course_progress_certificate">%s</td>', __( 'Not available', 'wp-courseware' ) );
			}
		}

		$html .= '</tr>';

		// Show full course details. This might be a setting at some point.
		$html .= sprintf( '<tr><td class="wpcw_fe_course_progress_detail" id="wpcw_fe_course_progress_detail_%d" colspan="%d">',
			$aSingleCourse->course_id, $columnCount
		);

		if ( $showCourseDesc ) {
			$html .= sprintf( '<div class="wpcw_fe_course_progress_desc" id="wpcw_fe_course_progress_desc_%d">%s</div>', $aSingleCourse->course_id, $aSingleCourse->course_desc );
		}

		if ( $showCoursePrerequisites ) {
			$coursePrerequisites = WPCW_users_getCoursePrerequisites( $aSingleCourse->course_id );

			if ( $coursePrerequisites ) {
				$html .= sprintf( '<div class="wpcw_fe_course_progress_prerequisites" id="wpcw_fe_course_progress_prerequisites_%d">', $aSingleCourse->course_id );
				$html .= '<table class="wpcw_fe_course_prerequisites_table wpcw_fe_table" cellspacing="0" cellpadding="0">';
				$html .= '<tbody>';
				$html .= '<tr class="wpcw_fe_course_prerequisites_header">';
				$html .= sprintf( '<td class="wpcw_fe_course_prerequisites_header_column" colspan="2">%s</td>', esc_html__( 'Course Prerequisites', 'wp-courseware' ) );
				$html .= '</tr>';
				foreach ( $coursePrerequisites as $coursePrerequisite ) {
					$course = new Course( $coursePrerequisite );

					$html .= '<tr class="wpcw_fe_course_prerequisites_row">';
					$html .= sprintf( '<td class="wpcw_fe_course_prerequisites_column_title"><a target="_blank" href="%s">%s</a></td>', $course->get_permalink(), $course->get_course_title() );
					$html .= '<td class="wpcw_fe_course_prerequisites_column_complete">';
					if ( wpcw_has_student_completed_course( $user_id, $course->get_id() ) ) {
						$html .= '<span aria-label="complete" class="wpcw_checkmark" role="img"></span>';
					} else {
						$html .= '<span class="wpcw_circle"></span>';
					}
					$html .= '</td>';
					$html .= '</tr>';
				}
				$html .= '</tbody>';
				$html .= '</table>';
				$html .= '</div>';
			}
		}

		$html .= WPCW_courses_renderCourseList( $aSingleCourse->course_id, array( 'hide_credit_link' => true, 'user_quiz_grade' => $user_quiz_grade, 'reset_progress' => $reset_progress ) );
		$html .= '</td></tr>';
	}

	$html .= '</tbody></table>'; // end .wpcw_fe_summary_course_progress

	if ( ! $hide_credit_link ) {
		$html .= WPCW_generatedPoweredByLink();
	}

	return $html;
}
