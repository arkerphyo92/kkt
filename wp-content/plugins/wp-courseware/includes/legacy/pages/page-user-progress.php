<?php
/**
 * WP Courseware Page User Progress.
 *
 * Functions relating to showing the process for a specific user.
 *
 * @package WPCW
 * @since 1.0.0
 */

use WPCW\Models\Student;

/**
 * Shows a detailed summary of the user progress.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess_load() {
	// globals
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Vars
	$page         = new PageBuilder( false );
	$current_user = wp_get_current_user();

	$student_progress_title = __( 'Detailed Student Progress Report', 'wp-courseware' );

	// Check permisssions
	if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
		$page->showPageHeader( $student_progress_title, '75%' );
		$page->showMessage( esc_attr__( 'Sorry, but you do not have access to see details user progress reports.', 'wp-courseware' ), true );
		$page->showPageFooter();

		return false;
	}

	// Check passed user ID is valid
	$userID      = WPCW_arrays_getValue( $_GET, 'user_id' );
	$userDetails = get_userdata( $userID );

	if ( ! $userDetails ) {
		$page->showPageHeader( $student_progress_title, '75%' );
		$page->showMessage( __( 'Sorry, but that user could not be found.', 'wp-courseware' ), true );
		$page->showPageFooter();

		return false;
	}

	$student_url  = add_query_arg( array( 'page' => 'wpcw-student', 'id' => $userID ), admin_url( 'admin.php' ) );
	$students_url = add_query_arg( array( 'page' => 'wpcw-students' ), admin_url( 'admin.php' ) );

	$student_progress_title .= sprintf( ' <a class="page-title-action" href="%s">%s</a>', $student_url, esc_html__( 'Back to Student', 'wp-courseware' ) );
	$student_progress_title .= sprintf( ' <a class="page-title-action" href="%s">%s</a>', $students_url, esc_html__( 'Back to Students', 'wp-courseware' ) );

	$page->showPageHeader( $student_progress_title, '75%' );

	printf( __( '<p>Here you can see how well <b>%s</b> (Username: <b>%s</b>) is doing with your training courses.</p>', 'wp-courseware' ), $userDetails->data->display_name, $userDetails->data->user_login );

	// Add sql conditions
	$SQL_WHERE = false;
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		$SQL_WHERE = $wpdb->prepare( ' WHERE course_author = %d', $current_user->ID );
	}

	// 1 - Show a list of all training courses, and then list the units associated with that course.
	$SQL = "SELECT *
			FROM $wpcwdb->courses
			$SQL_WHERE
			ORDER BY course_title ASC
			";

	$courseCount = 0;

	$courses = $wpdb->get_results( $SQL );
	if ( $courses ) {
		foreach ( $courses as $course ) {
			$up = new WPCW_UserProgress( $course->course_id, $userID );

			// Skip if user is not allowed to access the training course.
			if ( ! WPCW_courses_canUserAccessCourse( $course->course_id, $userID ) ) {
				continue;
			}

			//Fetch course data to obtain certificate
			$certificate       = false;
			$courseDetails     = WPCW_courses_getCourseDetails( $course->course_id );
			$usingCertificates = ( 'use_certs' == $courseDetails->course_opt_use_certificate );

			// Generate certificate button if enabled and a certificate exists for this user.
			if ( $usingCertificates && $certificateDetails = WPCW_certificate_getCertificateDetails( $userID, $course->course_id, false ) ) {
				//Prepare certificate button.
				$certificate = sprintf( '<a href="%s" class="button-primary" target="_blank">%s</a>',
					WPCW_certificate_generateLink( $certificateDetails->cert_access_key ), __( 'View Certificate', 'wp-courseware' ) );
			}

			printf(
				'<h3 class="wpcw_tbl_progress_course"><a href="%1$s">%2$s</a>&nbsp&nbsp%3$s</h3> ',
				add_query_arg( array( 'post' => $course->course_post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ),
				$course->course_title,
				$certificate
			);
			printf( '<table class="widefat wpcw_tbl wpcw_tbl_progress">' );

			printf( '<thead>' );
			printf( '<th>%s</th>', __( 'Unit', 'wp-courseware' ) );
			printf( '<th class="wpcw_left">%s</th>', __( 'Completed', 'wp-courseware' ) );
			printf( '<th class="wpcw_left wpcw_tbl_progress_quiz_name">%s</th>', __( 'Quiz Name', 'wp-courseware' ) );
			printf( '<th class="wpcw_left">%s</th>', __( 'Quiz Status', 'wp-courseware' ) );
			printf( '<th class="wpcw_left">%s</th>', __( 'Actions', 'wp-courseware' ) );
			printf( '</thead><tbody>' );

			// 2 - Fetch all associated modules
			$modules = WPCW_courses_getModuleDetailsList( $course->course_id );
			if ( $modules ) {
				foreach ( $modules as $module ) {
					// 3 - Render Modules as a heading.
					printf( '<tr class="wpcw_tbl_progress_module">' );
					printf( '<td colspan="3"><a href="%1$s">%2$s %3$d - %4$s</a></td>',
						add_query_arg( array( 'page' => 'WPCW_showPage_ModifyModule', 'module_id' => $module->module_id ), admin_url( 'admin.php' ) ),
						__( 'Module', 'wp-courseware' ),
						$module->module_number,
						$module->module_title
					);

					// Blanks for Quiz Name and Actions.
					printf( '<td>&nbsp;</td>' );
					printf( '<td>&nbsp;</td>' );
					printf( '</tr>' );

					// 4. - Render the units for this module
					$units = WPCW_units_getListOfUnits( $module->module_id );
					if ( $units ) {
						foreach ( $units as $unit ) {
							$showDetailLink = false;

							printf( '<tr class="wpcw_tbl_progress_unit">' );

							printf( '<td class="wpcw_tbl_progress_unit_name"><a href="%1$s">%2$s %3$d - %4$s</a></td>',
								add_query_arg( array( 'post' => $unit->ID, 'action' => 'edit' ), admin_url( 'post.php' ) ),
								__( 'Unit', 'wp-courseware' ),
								$unit->unit_meta->unit_number,
								$unit->post_title
							);

							// Has the unit been completed yet?
							printf( '<td class="wpcw_tbl_progress_completed">%s</td>', $up->isUnitCompleted( $unit->ID ) ? __( 'Completed', 'wp-courseware' ) : '' );

							// See if there's a quiz for this unit?
							$quizDetails = WPCW_quizzes_getAssociatedQuizForUnit( $unit->ID, $userID, false );

							// Render the quiz details.
							if ( $quizDetails ) {
								// Title of quiz
								printf(
									'<td class="wpcw_tbl_progress_quiz_name"><a href="%1$s">%2$s</td>',
									add_query_arg( array( 'page' => 'WPCW_showPage_ModifyQuiz', 'quiz_id' => $quizDetails->quiz_id ), admin_url( 'admin.php' ) ),
									$quizDetails->quiz_title
								);

								// No correct answers, so mark as complete.
								if ( 'survey' == $quizDetails->quiz_type ) {
									$quizResults = WPCW_quizzes_getUserResultsForQuiz( $userID, $unit->ID, $quizDetails->quiz_id );

									if ( $quizResults ) {
										printf( '<td class="wpcw_tbl_progress_completed">%s</td>', __( 'Completed', 'wp-courseware' ) );

										// Showing a link to view details
										$showDetailLink = true;
										printf( '<td><a href="%s&user_id=%d&quiz_id=%d&unit_id=%d" class="button-secondary">%s</a></td>',
											admin_url( 'admin.php?page=WPCW_showPage_UserProgess_quizAnswers' ),
											$userID, $quizDetails->quiz_id, $unit->ID,
											__( 'View Survey Details', 'wp-courseware' )
										);
									} // Survey not taken yet
									else {
										printf( '<td class="wpcw_left">%s</td>', __( 'Pending', 'wp-courseware' ) );
									}
								} // Quiz - show correct answers.
								else {
									$quizResults = WPCW_quizzes_getUserResultsForQuiz( $userID, $unit->ID, $quizDetails->quiz_id );

									// Show the admin how many questions were right.
									if ( $quizResults ) {
										// -1% means that the quiz is needing grading.
										if ( $quizResults->quiz_grade < 0 ) {
											printf( '<td class="wpcw_left">%s</td>', __( 'Awaiting Final Grading', 'wp-courseware' ) );
										} else {
											// Changed specifier to string to show actual grade with decimal 2/5/2016 --Benito--
											printf( '<td class="wpcw_tbl_progress_completed">%s%%</td>', $quizResults->quiz_grade );
										}

										// Showing a link to view details
										$showDetailLink = true;

										printf( '<td><a href="%s&user_id=%d&quiz_id=%d&unit_id=%d" class="button-secondary">%s</a>&nbsp;&nbsp;',
											admin_url( 'admin.php?page=WPCW_showPage_UserProgess_quizAnswers' ),
											$userID, $quizDetails->quiz_id, $unit->ID,
											__( 'View Quiz Details', 'wp-courseware' )
										);

										$quizResultsURL = add_query_arg( array(
											'page'   => 'wpcw_pdf_create_results',
											'unitid' => $unit->ID,
											'quizid' => $quizDetails->quiz_id,
											'userid' => $userID,
										), esc_url( site_url( '/' ) ) );

										printf( '<a href="%s" class="button-primary" download><i class="wpcw-fas wpcw-fa-download left" aria-hidden="true"></i>%s</a></td>',
											$quizResultsURL,
											__( "Quiz Results", 'wp-courseware' )
										);
									} // end of if  printf('<td class="wpcw_tbl_progress_completed">%s</td>'

									// Quiz not taken yet
									else {
										printf( '<td class="wpcw_left">%s</td>', __( 'Pending', 'wp-courseware' ) );
									}
								} // end of if survey
							} // end of if $quizDetails

							// No quiz for this unit
							else {
								printf( '<td class="wpcw_left">-</td>' );
								printf( '<td class="wpcw_left">-</td>' );
							}

							// Quiz detail link
							if ( ! $showDetailLink ) {
								printf( '<td>&nbsp;</td>' );
							}

							printf( '</tr>' );
						}
					}
				}
			}

			printf( '</tbody></table>' );

			// Track number of courses user can actually access
			$courseCount ++;
		}

		// User is not allowed to access any courses. So show a meaningful message.
		if ( $courseCount == 0 ) {
			$page->showMessage( sprintf( __( 'User <b>%s</b> is not currently allowed to access any training courses.', 'wp-courseware' ), $userDetails->data->display_name ), true );
		}
	} else {
		printf( '<p>%s</p>', __( 'There are currently no courses to show. Why not create one?', 'wp-courseware' ) );
	}

	$page->showPageFooter();
}

/**
 * Shows a detailed summary of the user's quiz or survey answers.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess_quizAnswers_load() {
	// Globals
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Variables
	$page         = new PageBuilder( false );
	$current_user = wp_get_current_user();

	// Header
	$page->showPageHeader( __( 'Detailed Student Quiz / Survey Results', 'wp-courseware' ), '75%' );

	$userID = absint( WPCW_arrays_getValue( $_GET, 'user_id' ) );
	$unitID = absint( WPCW_arrays_getValue( $_GET, 'unit_id' ) );
	$quizID = absint( WPCW_arrays_getValue( $_GET, 'quiz_id' ) );

	// check permissions
	if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
		$page->showMessage( __( 'Sorry, but you cannot access this user quiz/survey results.', 'wp-courseware' ), true );
		$page->showPageFooter();

		return;
	}

	// check additional permissions
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		// Get the author of the quiz.
		$quiz_author_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT quiz_author
			FROM $wpcwdb->quiz
			WHERE quiz_id = %d;
		", $quizID ) );

		// Check the author
		if ( $current_user->ID != $quiz_author_id ) {
			$page->showMessage( __( 'Sorry, but you cannot access this user quiz/survey results.', 'wp-courseware' ), true );
			$page->showPageFooter();

			return;
		}
	}

	// Create a link back to the detailed user progress, and back to all users.
	printf( '<div class="wpcw_button_group">' );

	// Link back to all user summary
	printf( '<a href="%s" class="button-secondary">%s</a>&nbsp;&nbsp;',
		add_query_arg( array( 'page' => 'wpcw-students' ), admin_url( 'admin.php' ) ),
		__( '&laquo; Return to Students', 'wp-courseware' )
	);

	if ( $userDetails = get_userdata( $userID ) ) {
		// Link back to user's personal summary
		printf( '<a href="%s&user_id=%d" class="button-secondary">%s</a>&nbsp;&nbsp;',
			admin_url( 'admin.php?page=WPCW_showPage_UserProgess' ),
			$userDetails->ID,
			sprintf( __( '&laquo; Return to <b>%s\'s</b> Progress Report', 'wp-courseware' ), $userDetails->display_name )
		);
	}

	// Try to get the full detailed results.
	$results = WPCW_quizzes_getUserResultsForQuiz( $userID, $unitID, $quizID );

	// No results, so abort.
	if ( ! $results ) {
		printf( '</div>' );

		$page->showMessage( __( 'Sorry, but no results could be found.', 'wp-courseware' ), true );
		$page->showPageFooter();

		return;
	}

	// Could potentially have an issue where the quiz has been deleted
	// but the data exists.. small chance though.
	$quizDetails = WPCW_quizzes_getQuizDetails( $quizID, $userID, true, true );

	// Extra button - return to gradebook
	printf( '<a href="%s&course_id=%d" class="button-secondary">%s</a>&nbsp;&nbsp;',
		admin_url( 'admin.php?page=WPCW_showPage_GradeBook' ), $quizDetails->parent_course_id,
		__( "&laquo; Return to Gradebook", 'wp-courseware' )
	);

	$student = new Student( $userID );
	echo wpcw_admin_get_view( 'common/modal-notices' );
	echo wpcw_admin_get_view( 'common/form-field' );
	echo wpcw_admin_get_view( 'gradebook/gradebook-send-email' );

	$quizResultsURL = add_query_arg( array(
						'page'   => 'wpcw_pdf_create_results',
						'unitid' => $unitID,
						'quizid' => $quizID,
						'userid' => $userID,
					), esc_url( site_url( '/' ) ) );

	printf( '<a href="%s" class="button-primary" download><i class="wpcw-fas wpcw-fa-download left" aria-hidden="true"></i>%s</a>&nbsp;&nbsp;',
		$quizResultsURL,
		__( "Quiz Results", 'wp-courseware' )
	);


	?>
	<div id="wpcw-hidden-wp-email-editor" style="display: none;"><?php wp_editor( '', 'wpcw_email_content', array( 'media_buttons' => true ) ); ?></div>
	<a class="button button-primary gradebook-action-email" data-student="<?php echo $student->get_json(); ?>" data-subject="<?php echo $quizDetails->quiz_title; ?>" href="#">
		<i class="wpcw-fas wpcw-fa-envelope left" aria-hidden="true"></i>
		<?php esc_html_e( 'Email Student', 'wp-courseware' ); ?>
	</a>
	<div id="wpcw-send-gradebook-email-instance">
		<wpcw-gradebook-send-email v-once></wpcw-gradebook-send-email>
	</div>
	<?php

	printf( '</div>' ); // .wpcw_button_group

	// 1 - Handle grades being updated
	$results = WPCW_showPage_UserProgess_quizAnswers_handingGrading( $quizDetails, $results, $page, $userID, $unitID );

	// 2A - Check if next action for user has been triggered by the admin.
	$results = WPCW_showPage_UserProgess_quizAnswers_whatsNext_savePreferences( $quizDetails, $results, $page, $userID, $unitID );

	// 2B - Handle telling admin what's next
	WPCW_showPage_UserProgess_quizAnswers_whatsNext( $quizDetails, $results, $page, $userID, $unitID );

	// 3 - Handle sending emails if something has changed.
	if ( isset( $results->sendOutEmails ) && $results->sendOutEmails ) {
		$extraDetail = ( isset( $results->extraEmailDetail ) ? $results->extraEmailDetail : '' );

		// Only called if the quiz was graded.
		if ( isset( $results->quiz_has_just_been_graded ) && $results->quiz_has_just_been_graded ) {
			// Need to call the action anyway, but any functions hanging off this
			// should check if the admin wants users to have notifications or not.
			do_action( 'wpcw_quiz_graded', $userID, $quizDetails, number_format( $results->quiz_grade, 1 ), $extraDetail );
		}

		$courseDetails = WPCW_courses_getCourseDetails( $quizDetails->parent_course_id );
		if ( $courseDetails->email_quiz_grade_option == 'send_email' ) {
			// Message is only if quiz has been graded.
			if ( isset( $results->quiz_has_just_been_graded ) && $results->quiz_has_just_been_graded ) {
				$page->showMessage( __( 'The user has been sent an email with their grade for this course.', 'wp-courseware' ) );
			}
		}
	}

	// Table 1 - Overview
	printf( '<h3>%s</h3>', __( 'Quiz/Survey Overview', 'wp-courseware' ) );

	$tbl             = new TableBuilder();
	$tbl->attributes = array(
		'id'    => 'wpcw_tbl_progress_quiz_info',
		'class' => 'widefat wpcw_tbl',
	);

	$tblCol            = new TableColumn( false, 'quiz_label' );
	$tblCol->cellClass = 'wpcw_tbl_label';
	$tbl->addColumn( $tblCol );

	$tblCol = new TableColumn( false, 'quiz_detail' );
	$tbl->addColumn( $tblCol );
	$date = get_date_from_gmt( date( 'Y-m-d H:i:s', $results->quiz_completed_date_ts ), 'D F jS Y \a\t H:i:s' );

	// These are the base details for the quiz to show.
	$summaryData = array(
		__( 'Quiz Title', 'wp-courseware' )       => $quizDetails->quiz_title,
		__( 'Quiz Description', 'wp-courseware' ) => $quizDetails->quiz_desc,
		__( 'Quiz Type', 'wp-courseware' )        => WPCW_quizzes_getQuizTypeName( $quizDetails->quiz_type ),
		__( 'No. of Questions', 'wp-courseware' ) => $results->quiz_question_total,

		__( 'Completed Date', 'wp-courseware' ) =>
			__( 'About', 'wp-courseware' ) . ' ' . human_time_diff( $results->quiz_completed_date_ts, current_time( 'timestamp', true ) ) . ' ' . __( 'ago', 'wp-courseware' ) .
			'<br/><small>(' . $date . ')</small>',

		__( 'Number of Quiz Attempts', 'wp-courseware' ) => $results->attempt_count,
		__( 'Permitted Quiz Attempts', 'wp-courseware' ) => ( - 1 == $quizDetails->quiz_attempts_allowed ? __( 'Unlimited', 'wp-courseware' ) : $quizDetails->quiz_attempts_allowed ),
	);

	// Quiz details relating to score, etc.
	if ( 'survey' != $quizDetails->quiz_type ) {
		$pass_mark_label             = __( 'Pass Mark', 'wp-courseware' );
		$recommended_pass_mark_label = __( 'Recommended Pass Mark', 'wp-courseware' );
		$num_questions_grade_label   = __( 'No. of Questions to Grade', 'wp-courseware' );
		$overall_grade_label         = __( 'Overall Grade', 'wp-courseware' );

		// Display correct label according to quiz type
		if ( 'quiz_block' == $quizDetails->quiz_type ) {
			$summaryData[ $pass_mark_label ] = $quizDetails->quiz_pass_mark . '%';
		} else {
			$summaryData[ $recommended_pass_mark_label ] = $quizDetails->show_recommended_percentage . '%';
		}

		// Still got items to grade
		if ( $results->quiz_needs_marking > 0 ) {
			$summaryData[ $num_questions_grade_label ] = '<span class="wpcw_status_info wpcw_icon_pending">' . $results->quiz_needs_marking . '</span>';
			$summaryData[ $overall_grade_label ]       = '<span class="wpcw_status_info wpcw_icon_pending">' . __( 'Awaiting Final Grading', 'wp-courseware' ) . '</span>';
		} else {
			$summaryData[ $num_questions_grade_label ] = '-';

			// Show if PASSED or FAILED with the overall grade.
			$gradeData = false;
			if ( 'quiz_noblock' == $quizDetails->quiz_type ) {
				$gradeData = sprintf( '<span class="wpcw_tbl_progress_quiz_overall  ">%s%%</span>', $results->quiz_grade );
			} else if ( $results->quiz_grade >= $quizDetails->quiz_pass_mark ) {
				$gradeData = sprintf( '<span class="wpcw_tbl_progress_quiz_overall wpcw_question_yesno_status wpcw_question_yes">%s%% %s</span>', $results->quiz_grade, __( 'Passed', 'wp-courseware' ) );
			} else {
				$gradeData = sprintf( '<span class="wpcw_tbl_progress_quiz_overall wpcw_question_yesno_status wpcw_question_no">%s%% %s</span>', $results->quiz_grade, __( 'Failed', 'wp-courseware' ) );
			}

			$summaryData[ $overall_grade_label ] = $gradeData;
		}
	}

	foreach ( $summaryData as $label => $data ) {
		$tbl->addRow( array(
			'quiz_label'  => $label . ':',
			'quiz_detail' => $data,
		) );
	}

	echo $tbl->toString();

	// 4 - Form Code - to allow instructor to send data back to
	printf( '<form method="POST" id="wpcw_tbl_progress_quiz_grading_form">' );
	printf( '<input type="hidden" name="grade_answers_submitted" value="true">' );

	// 5 - Table 2 - Each Specific Quiz
	$questionNumber = 0;
	if ( $results->quiz_data && count( $results->quiz_data ) > 0 ) {
		foreach ( $results->quiz_data as $questionID => $answer ) {
			$data = $answer;

			// Get the question type
			if ( isset( $quizDetails->questions[ $questionID ] ) ) {
				// Store as object for easy reference.
				$quObj = $quizDetails->questions[ $questionID ];

				// Render the question as a table.
				printf( '<h3>%s #%d - %s</h3>', __( 'Question', 'wp-courseware' ), ++ $questionNumber, $quObj->question_question );

				if ( isset( $quObj->question_image ) && $quObj->question_image !== '' ) {
					printf( '<img src="%s" class="wpcw-student-results-quiz-question-image" />', $quObj->question_image );
				}

				$tbl             = new TableBuilder();
				$tbl->attributes = array(
					'id'    => 'wpcw_tbl_progress_quiz_info',
					'class' => 'widefat wpcw_tbl wpcw_tbl_progress_quiz_answers_' . $quObj->question_type // Add question type to table class, for good measure!
				);

				$tblCol            = new TableColumn( false, 'quiz_label' );
				$tblCol->cellClass = 'wpcw_tbl_label';
				$tbl->addColumn( $tblCol );

				$tblCol = new TableColumn( false, 'quiz_detail' );
				$tbl->addColumn( $tblCol );

				$theirAnswer = false;
				switch ( $quObj->question_type ) {
					case 'truefalse':
					case 'multi':
						$theirAnswer = $answer['their_answer'];
						break;

					// File Upload - create a download link
					case 'upload':
						$theirAnswer = sprintf( '<a href="%s%s" target="_blank" class="button-primary">%s .%s %s (%s)</a>',
							WP_CONTENT_URL, $answer['their_answer'],
							__( 'Open', 'wp-courseware' ),
							pathinfo( $answer['their_answer'], PATHINFO_EXTENSION ),
							__( 'File', 'wp-courseware' ),
							WPCW_files_getFileSize_human( $answer['their_answer'] )
						);
						break;

					// Open Ended - Wrap in span tags, to cap the size of the field, and format new lines.
					case 'open':
						$theirAnswer = '<span class="wpcw_q_answer_open_wrap"><textarea readonly>' . $data['their_answer'] . '</textarea></span>';
						break;
				} // end of $theirAnswer check

				$summaryData = array(
					// Quiz Type - Work out the label for the quiz type
					__( 'Type', 'wp-courseware' ) => array(
						'data'     => WPCW_quizzes_getQuestionTypeName( $quObj->question_type ),
						'cssclass' => '',
					),

					__( 'Their Answer', 'wp-courseware' ) => array(
						'data'     => $theirAnswer,
						'cssclass' => '',
					),
				);

				// Just for quizzes - show answers/grade
				if ( 'survey' != $quizDetails->quiz_type ) {
					switch ( $quObj->question_type ) {
						case 'truefalse':
						case 'multi':
							$correct_answer_label = __( 'Correct Answer', 'wp-courseware' );
							// The right answer...
							$summaryData[ $correct_answer_label ] = array(
								'data'     => $answer['correct'],
								'cssclass' => '',
							);

							// Did they get it right?
							$getItRight = sprintf( '<span class="wpcw_question_yesno_status wpcw_question_%s">%s</span>', $answer['got_right'],
								( 'yes' == $answer['got_right'] ? __( 'Yes', 'wp-courseware' ) : __( 'No', 'wp-courseware' ) )
							);

							$did_the_get_it_right_label = __( 'Did they get it right?', 'wp-courseware' );

							$summaryData[ $did_the_get_it_right_label ] = array(
								'data'     => $getItRight,
								'cssclass' => '',
							);
							break;

						case 'upload':
						case 'open':
							$gradeHTML  = false;
							$theirGrade = WPCW_arrays_getValue( $answer, 'their_grade' );

							// Not graded - show select box.
							if ( $theirGrade == 0 ) {
								$cssClass = 'wpcw_grade_needs_grading';
							} // Graded - Show click-to-edit link
							else {
								$cssClass  = 'wpcw_grade_already_graded';
								$gradeHTML = sprintf( '<span class="wpcw_grade_view">%d%% <a href="#">(%s)</a></span>', $theirGrade, __( 'Click to edit', 'wp-courseware' ) );
							}

							// Not graded yet, allow admin to grade the quiz, or change
							// the grading later if they want to.
							$gradeHTML .= WPCW_forms_createDropdown(
								'grade_quiz_' . $quObj->question_id,
								WPCW_quizzes_getPercentageList( __( '-- Select a grade --', 'wp-courseware' ) ),
								$theirGrade,
								false,
								'wpcw_tbl_progress_quiz_answers_grade'
							);

							$their_grade_label = __( 'Their Grade', 'wp-courseware' );

							$summaryData[ $their_grade_label ] = array(
								'data'     => $gradeHTML,
								'cssclass' => $cssClass,
							);
							break;
					}

					// Provide Feedback radio.
					$provide_feedback_radio_quiz = WPCW_arrays_getValue( $answer, 'provide_feedback_radio_quiz' );

					$provide_feedback_radio = __( 'Provide Feedback?', 'wp-courseware' );
					$css_class = 'wpcw_provide_feedback_radio';

					$provide_feedback_radio_html = sprintf( '<label><input class="wpcw_provide_feedback_radio_quiz" type="radio" name="provide_feedback_radio_quiz_%s" value="yes" %s /> %s</label>',
						$quObj->question_id, ( 'yes' === $provide_feedback_radio_quiz ? 'checked="checked"' : false ),
						__( 'Yes', 'wp-courseware' )
					);

					$provide_feedback_radio_html .= sprintf( '<label><input class="wpcw_provide_feedback_radio_quiz" type="radio" name="provide_feedback_radio_quiz_%s" value="no" %s /> %s</label>',
						$quObj->question_id, ( 'yes' !== $provide_feedback_radio_quiz ? 'checked="checked"' : false ),
						__( 'No', 'wp-courseware' )
					);

					$summaryData[ $provide_feedback_radio ] = array(
						'data'     => $provide_feedback_radio_html,
						'cssclass' => $css_class,
					);

					// Provide Feedback Textarea.
					$provide_feedback_textarea_quiz = WPCW_arrays_getValue( $answer, 'provide_feedback_textarea_quiz' );

					$provide_feedback_textarea = __( 'Feedback', 'wp-courseware' );
					$css_class = 'yes' !== $provide_feedback_radio_quiz ? 'wpcw_provide_feedback_textarea_hide' : 'wpcw_provide_feedback_textarea';

					$provide_feedback_textarea_html = sprintf( '<textarea class="wpcw_provide_feedback_textarea_quiz" name="provide_feedback_textarea_quiz_%s">%s</textarea>',
						$quObj->question_id, $provide_feedback_textarea_quiz
					);

					$summaryData[ $provide_feedback_textarea ] = array(
						'data'     => $provide_feedback_textarea_html,
						'cssclass' => $css_class,
					);

				} // Check of showing the right answer.

				foreach ( $summaryData as $label => $data ) {
					$tbl->addRow( array(
						'quiz_label'  => $label . ':',
						'quiz_detail' => $data['data'],
					), $data['cssclass'] );
				}

				echo $tbl->toString();
			} // end if (isset($quizDetails->questions[$questionID]))
		} // foreach ($results->quiz_data as $questionID => $answer)
	}

	printf( '</form>' );

	// Shows a bar that pops up, allowing the user to easily save all grades that have changed.
	?>
	<div id="wpcw_sticky_bar" style="display: none">
		<div id="wpcw_sticky_bar_inner">
			<a href="#" id="wpcw_tbl_progress_quiz_grading_updated" class="button-primary"><?php _e( 'Save Changes', 'wp-courseware' ); ?></a>
			<span id="wpcw_sticky_bar_status" title="<?php _e( 'Ready to save changes?', 'wp-courseware' ); ?>"></span>
		</div>
	</div>
	<br/><br/><br/><br/>
	<?php

	$page->showPageFooter();
}

/**
 * Handles the grading of the quiz questions.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess_quizAnswers_handingGrading( $quizDetails, $results, $page, $userID, $unitID ) {
	if ( isset( $_POST['grade_answers_submitted'] ) && 'true' == $_POST['grade_answers_submitted'] ) {
		$listOfQuestionsToMark = $results->quiz_needs_marking_list;

		// Switch array so values become keys.
		if ( ! empty( $listOfQuestionsToMark ) ) {
			$listOfQuestionsToMark = array_flip( $listOfQuestionsToMark );
		} // Ensure we always have a valid array
		else {
			$listOfQuestionsToMark = array();
		}

		// Course Id.
		$course_id = $quizDetails->parent_course_id;

		// Check $_POST keys for the graded results.
		foreach ( $_POST as $key => $value ) {
			// Check that we have a question ID and a matching grade for the quiz. Only want grades that are greater than 0.
			if ( preg_match( '/^grade_quiz_([0-9]+)$/', $key, $keyMatches ) && preg_match( '/^[0-9]+$/', $value ) && $value > 0 ) {
				$questionID = $keyMatches[1];

				// Remove from list to be marked, if found
				unset( $listOfQuestionsToMark[ $questionID ] );

				// Add the grade information to the quiz
				if ( isset( $results->quiz_data[ $questionID ] ) ) {
					$results->quiz_data[ $questionID ]['their_grade'] = $value;
				}
			}

			// Check that we have a question ID and a matching feedback radio for the quiz.
			if ( preg_match( '/^provide_feedback_radio_quiz_([0-9]+)$/', $key, $keyMatches ) ) {
				$questionID = $keyMatches[1];

				// Remove from list to be marked, if found
				//unset( $listOfQuestionsToMark[ $questionID ] );

				// Add the grade information to the quiz
				if ( isset( $results->quiz_data[ $questionID ] ) ) {
					$results->quiz_data[ $questionID ]['provide_feedback_radio_quiz'] = $value;
				}
			}

			// Check that we have a question ID and a matching feedback textarea for the quiz.
			if ( preg_match( '/^provide_feedback_textarea_quiz_([0-9]+)$/', $key, $keyMatches ) ) {
				$questionID = $keyMatches[1];

				// Remove from list to be marked, if found
				//unset( $listOfQuestionsToMark[ $questionID ] );

				// Add the grade information to the quiz
				if ( isset( $results->quiz_data[ $questionID ] ) ) {
					$results->quiz_data[ $questionID ]['provide_feedback_textarea_quiz'] = $value;
				}
			}
		}

		// Update the database with the list of questions to mark, plus the updated quiz grading information.
		// Return to a simple list again, hence using array flip (ID => index) becomes (index => ID)
		$results->quiz_needs_marking_list = array_flip( $listOfQuestionsToMark );

		// Update the results in the database.
		WPCW_quizzes_updateQuizResults( $results );

		// Success message
		$page->showMessage( __( 'Grades have been successfully updated for this user.', 'wp-courseware' ) );

		// Refresh the results - now that we've made changes
		$results = WPCW_quizzes_getUserResultsForQuiz( $userID, $unitID, $quizDetails->quiz_id );

		// All items are marked, so email user, and tell admin that user has been notified.
		if ( $results->quiz_needs_marking == 0 ) {
			// Send out email only if not a blocking test, or blocking and passed.
			if ( 'quiz_block' == $quizDetails->quiz_type && $results->quiz_grade < $quizDetails->quiz_pass_mark ) {
				$results->sendOutEmails = false;
			} else {
				$results->sendOutEmails = true;
			}

			// Check if the user has passed or not to indicate what to do next.
			if ( $results->quiz_grade >= $quizDetails->quiz_pass_mark ) {
				// Just a little note to mark as complete
				$results->extraEmailDetail = __( 'You have passed the quiz.', 'wp-courseware' );

				printf( '<div id="message" class="wpcw_msg wpcw_msg_success">%s</span></div>',
					__( 'The user has <b>PASSED</b> this quiz, and the associated unit has been marked as complete.', 'wp-courseware' )
				);

				WPCW_units_saveUserProgress_Complete( $userID, $unitID );

				// Unit complete, check if course/module is complete too.
				do_action( 'wpcw_user_completed_unit', $userID, $unitID, WPCW_units_getAssociatedParentData( $unitID ) );
			}
		}

		// Delete Course Meta.
		if ( $course_id ) {
			wpcw_delete_course_meta( $course_id, 'quizzes_need_grading' );
		}

		// Delete Primary Transient.
		delete_transient( 'wpcw_quizzes_need_grading' );

		// Set flag that the quiz has just literally been graded for use in code around this.
		// Doing this after the results have been updated above.
		$results->quiz_has_just_been_graded = true;
	}

	return $results;
}

/**
 * Function that shows details to the admin telling them what to do next.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess_quizAnswers_whatsNext( $quizDetails, $results, $page, $userID, $unitID ) {
	// Tell admin still questions that need marking
	if ( $results->quiz_needs_marking > 0 ) {
		printf( '<div id="message" class="wpcw_msg wpcw_msg_info"><span class="wpcw_icon_pending"><b>%s</b></span></div>',
			__( 'This quiz has questions that need grading.', 'wp-courseware' )
		);
	} else {
		// Show the form only if the quiz is blocking and they've failed.
		if ( 'quiz_block' == $quizDetails->quiz_type && $results->quiz_grade < $quizDetails->quiz_pass_mark ) {
			$showAdminProgressForm  = true;
			$showAdminMessageCustom = false;

			// Show admin which method was selected.
			if ( $results->quiz_next_step_type ) {
				switch ( $results->quiz_next_step_type ) {
					case 'progress_anyway':
						printf( '<div id="message" class="wpcw_msg wpcw_msg_info">%s</span></div>',
							__( 'You have allowed the user to <b>progress anyway</b>, despite failing the quiz.', 'wp-courseware' )
						);
						$showAdminProgressForm = false;
						break;

					case 'retake_quiz':
						printf( '<div id="message" class="wpcw_msg wpcw_msg_info">%s</span></div>',
							__( 'You have requested that the user <b>re-takes the quiz</b>.', 'wp-courseware' )
						);
						$showAdminProgressForm = false;
						break;

					case 'retake_waiting':
						printf( '<div id="message" class="wpcw_msg wpcw_msg_info">%s</span></div>',
							__( 'The user has requested a retake, but they have not yet completed the quiz.', 'wp-courseware' )
						);
						$showAdminProgressForm = false;
						break;

					case 'quiz_fail_no_retakes':
						$showAdminMessageCustom = __( 'The user has <b>exhausted all of their retakes</b>.', 'wp-courseware' );
						$showAdminProgressForm  = true;
						break;
				}
			}

			// No need to show progress form if there are attempts left. Also no need to show for unlimited attemps.
			// if ($quizDetails->quiz_attempts_allowed > $results->attempt_count || $quizDetails->quiz_attempts_allowed == -1){
			// 	$showAdminProgressForm = false;
			// }

			// Next step has not been specified, allow the admin to choose one.
			if ( $showAdminProgressForm ) {
				$attempts_taken  = WPCW_quizzes_getUserResultsForQuiz( $userID, $unitID, $quizDetails->quiz_id );
				$unitQuizDetails = WPCW_quizzes_getAssociatedQuizForUnit( $unitID, $userID, true );

				printf( '<div class="wpcw_user_progress_failed"><form method="POST">' );

				// Show the main message or a custom message from above.
				printf( '<div id="message" class="wpcw_msg wpcw_msg_error">%s %s</span></div>',
					$showAdminMessageCustom, __( 'Since this is a <b>blocking quiz</b>, and the user has <b>failed</b>, what would you like to do?', 'wp-courseware' )
				);

				printf( '
					<div class="wpcw_user_progress_failed_next_action">
						<label><input type="radio" name="wpcw_user_continue_action" class="wpcw_next_action_progress_anyway" value="progress_anyway" checked="checked" /> <span><b>%s</b> %s</span></label><br/>
					',
					__( 'Allow the user to continue anyway.', 'wp-courseware' ),
					__( ' (User is emailed saying they can continue)', 'wp-courseware' )
				);

				//if ($results->quiz_next_step_type == 'quiz_fail_no_retakes'){
				if ( $attempts_taken->attempt_count >= $unitQuizDetails->quiz_attempts_allowed && $unitQuizDetails->quiz_attempts_allowed != - 1 ) {
					printf( '
							<label><input type="radio" name="wpcw_user_continue_action" class="wpcw_next_action_retake_quiz" value="retake_quiz" /> <span><b>%s</b> %s</span></label>
						',
						__( 'Allow the user one more attempt.', 'wp-courseware' ),
						__( ' (User is emailed saying they need to re-take the quiz)', 'wp-courseware' )
					);
				} else {
					printf( '
						<label><input type="radio" name="wpcw_user_continue_action" class="wpcw_next_action_retake_quiz" value="retake_quiz" /> <span><b>%s</b> %s</span></label>
					',
						__( 'Require the user to re-take the quiz.', 'wp-courseware' ),
						__( ' (User is emailed saying they need to re-take the quiz)', 'wp-courseware' )
					);
				}

				printf( '
					</div>

					<div class="wpcw_user_progress_failed_reason" style="display: none;">
						<label><b>%s</b></label><br/>
						<textarea name="wpcw_user_progress_failed_reason"></textarea><br/>
						<small>%s</small>
					</div>

					<div class="wpcw_user_progress_failed_btns">
						<input type="submit" name="failed_quiz_next_action" value="%s" class="button-primary" />
					</div>
				',
					__( 'Require the user to re-take the quiz.', 'wp-courseware' ),
					__( ' (User is emailed saying they need to re-take the quiz)', 'wp-courseware' ),
					__( 'Custom Message:', 'wp-courseware' ),
					__( 'Custom message for the user that\'s sent to the user when asking them to retake the quiz.', 'wp-courseware' ),
					__( 'Save Preference', 'wp-courseware' )
				);

				printf( '</form></div>' );
			}
		}
	}
}

/**
 * Handles saving what the admin wants to do for the user next.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess_quizAnswers_whatsNext_savePreferences( $quizDetails, $results, $page, $userID, $unitID ) {
	// Admin wants to save the next action to this progress.
	if ( isset( $_POST['failed_quiz_next_action'] ) && $_POST['failed_quiz_next_action'] ) {
		global $wpdb, $wpcwdb;
		$wpdb->show_errors();

		$userNextAction = WPCW_arrays_getValue( $_POST, 'wpcw_user_continue_action' );
		$userRetakeMsg  = filter_var( WPCW_arrays_getValue( $_POST, 'wpcw_user_progress_failed_reason' ), FILTER_SANITIZE_STRING );

		// Check action is valid. Abort if not
		if ( ! in_array( $userNextAction, array( 'retake_quiz', 'progress_anyway' ) ) ) {
			return $results;
		}

		// Update the progress item
		$SQL = $wpdb->prepare( "
		    	UPDATE $wpcwdb->user_progress_quiz
		    	  SET quiz_next_step_type = '%s',
		    	      quiz_next_step_msg = %s
		    	WHERE user_id = %d
		    	  AND unit_id = %d
		    	  AND quiz_id = %d
		    	ORDER BY quiz_attempt_id DESC
		    	LIMIT 1
	   		",
			$userNextAction, $userRetakeMsg,
			$userID, $unitID, $quizDetails->quiz_id
		);

		$wpdb->query( $SQL );

		// Need to update the results object for use later.
		$results->quiz_next_step_type = $userNextAction;
		$results->quiz_next_step_msg  = $userRetakeMsg;

		switch ( $userNextAction ) {
			// User needs to retake the course.
			case 'retake_quiz':
				$results->extraEmailDetail = __( 'Since you didn\'t pass the quiz, the instructor has asked that you re-take this quiz.', 'wp-courseware' );
				if ( $userRetakeMsg ) {
					$results->extraEmailDetail .= "\n\n" . $userRetakeMsg;
				}
				break;

			// User is allowed to progress
			case 'progress_anyway':
				$results->extraEmailDetail = __( 'Although you didn\'t pass the quiz, the instructor is allowing you to continue.', 'wp-courseware' );

				// Mark the unit as completed.
				WPCW_units_saveUserProgress_Complete( $userID, $unitID );

				// Unit complete, check if course/module is complete too.
				do_action( 'wpcw_user_completed_unit', $userID, $unitID, WPCW_units_getAssociatedParentData( $unitID ) );
				break;
		}

		// Tell code to send out emails
		$results->sendOutEmails = true;
	}
	$results->quiz_has_just_been_graded = true;

	return $results;
}
