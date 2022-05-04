<?php
/**
 * WP Courseware Admin Ajax Functions.
 *
 * @since 1.0.0
 * @package WPCW
 */

/**
 * Function called when adding a question to a quiz from the thickbox.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_handleThickboxAction_QuestionPool_addQuestion() {
	$questionID      = WPCW_arrays_getValue( $_POST, 'questionnum' );
	$questionDetails = WPCW_questions_getQuestionDetails( $questionID );

	if ( $questionDetails ) {
		switch ( $questionDetails->question_type ) {
			case 'multi':
				$quizObj = new WPCW_quiz_MultipleChoice( $questionDetails );
				break;
			case 'truefalse':
				$quizObj = new WPCW_quiz_TrueFalse( $questionDetails );
				break;
			case 'open':
				$quizObj = new WPCW_quiz_OpenEntry( $questionDetails );
				break;
			case 'upload':
				$quizObj = new WPCW_quiz_FileUpload( $questionDetails );
				break;
			case 'random_selection':
				$quizObj = new WPCW_quiz_RandomSelection( $questionDetails );
				break;
			default:
				die( __( 'Unknown quiz type: ', 'wp-courseware' ) . $questionDetails->question_type );
				break;
		}

		$quizObj->showErrors         = true;
		$quizObj->needCorrectAnswers = true;

		echo $quizObj->editForm_toString();
	}

	die();
}

/**
 * Function called when any filtering occurs
 * within the thickbox window for the Question Pool.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_handleThickboxAction_QuestionPool() {
	$args = wp_parse_args(
		$_POST,
		array(
			'pagenum' => 1,
		)
	);

	// Create URL from parameters to use for building the question pool table
	echo WPCW_questionPool_showPoolTable( 20, $args, 'ajax' );

	die();
}

/**
 * Handle Question Remove Tag.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_handleQuestionRemoveTag() {
	$ajaxResults = array(
		'success' => true,
	);

	$tagID      = intval( WPCW_arrays_getValue( $_POST, 'tagid' ) );
	$questionID = intval( WPCW_arrays_getValue( $_POST, 'questionid' ) );

	WPCW_questions_tags_removeTag( $questionID, $tagID );

	header( 'Content-Type: application/json' );
	echo json_encode( $ajaxResults );

	die();
}

/**
 * Handle Question New Tag.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_handleQuestionNewTag() {
	$ajaxResults = array(
		'success'  => true,
		'errormsg' => __( 'Unfortunately there was a problem adding the tag.', 'wp-courseware' ),
		'html'     => false,
	);

	// Assume that we may have multiple tags, separated by commas.
	$potentialTagList = explode( ',', WPCW_arrays_getValue( $_POST, 'tagtext' ) );
	$cleanTagList     = array();

	// Check if question is expected to have been saved.
	$hasQuestionBeenSaved = 'yes' == WPCW_arrays_getValue( $_POST, 'isquestionsaved' );

	// Got potential tags
	if ( ! empty( $potentialTagList ) ) {
		// Clean up each tag, and add to a list.
		foreach ( $potentialTagList as $potentialTag ) {
			$cleanTagList[] = sanitize_text_field( stripslashes( $potentialTag ) );
		}

		// Check that cleaned tags are ok too
		if ( ! empty( $cleanTagList ) ) {
			// Do this if the question exists and we're adding tags.
			if ( $hasQuestionBeenSaved ) {
				// Get the ID of the question we're adding this tag to.
				$questionID = intval( WPCW_arrays_getValue( $_POST, 'questionid' ) );

				// Validate that the question exists before we tag it.
				$questionDetails = WPCW_questions_getQuestionDetails( $questionID );
				if ( ! $questionDetails ) {
					$ajaxResults['errormsg'] = __( 'Unfortunately that question could not be found, so the tag could not be added.', 'wp-courseware' );
					$ajaxResults['success']  = false;
				} // Question Found - carry on
				else {
					// Add the tag to the database, get a list of the tag details now that they have been added.
					$tagDetailList = WPCW_questions_tags_addTags( $questionID, $cleanTagList );
					foreach ( $tagDetailList as $tagAddedID => $tagAddedText ) {
						// Create the HTML to show the new tag.
						$ajaxResults['html'] .= sprintf(
							'<span><a data-questionid="%d" data-tagid="%d" class="ntdelbutton">X</a>&nbsp;%s</span>',
							$questionID,
							$tagAddedID,
							$tagAddedText
						);
					}
				}
			} else { // We expect the question not to exist, hence we don't try to add to a question.
				$tagDetailList = WPCW_questions_tags_addTags_withoutQuestion( $cleanTagList );

				// For a new question, the ID is a string, not a value.
				$questionIDStr = WPCW_arrays_getValue( $_POST, 'questionid' );

				// Create a hidden form entry plus the little tag, so that we can add the tag to the question when we save.
				foreach ( $tagDetailList as $tagAddedID => $tagAddedText ) {
					// Create the HTML to show the new tag. We'll add the full string to the hidden field so that we can
					// add the tags later.
					$ajaxResults['html'] .= sprintf(
						'<span>
							<a data-questionid="%d" data-tagid="%d" class="ntdelbutton">X</a>&nbsp;%s
							<input type="hidden" name="tags_to_add%s[]" value="%s" />
						</span>',
						0,
						$tagAddedID,
						$tagAddedText,
						$questionIDStr,
						addslashes( $tagAddedText )
					);
				}
			}
		}
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $ajaxResults );

	die();
}

/**
 * Handle Uniit Duplication.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_handleUnitDuplication() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'security_id' ), 'wpcw_ajax_unit_change' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	global $wpdb, $wpcwdb;

	// See if we can get the post that we've asked to duplicate
	$sourcePostID = WPCW_arrays_getValue( $_POST, 'source_id', false );
	$newUnit      = get_post( $sourcePostID, 'ARRAY_A' );

	$ajaxResults = array(
		'success'  => true,
		'errormsg' => false,
	);

	// Got the new unit
	if ( $newUnit ) {
		// Modify the post title to add ' Copy' to the end.
		$newUnit['post_title'] .= ' ' . __( 'Copy', 'wp-courseware' );

		// Adjust date to today
		$newUnit['post_date'] = current_time( 'mysql' );

		// Remove some of the keys relevant to the other post so that they are generated
		// automatically.
		unset( $newUnit['ID'] );
		unset( $newUnit['guid'] );
		unset( $newUnit['comment_count'] );
		unset( $newUnit['post_name'] );
		unset( $newUnit['post_date_gmt'] );

		// Insert the post into the database
		$newUnitID = wp_insert_post( $newUnit );

		// Duplicate all the taxonomies/terms
		$taxonomies = get_object_taxonomies( $newUnit['post_type'] );
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $sourcePostID, $taxonomy, array( 'fields' => 'names' ) );
				wp_set_object_terms( $newUnitID, $terms, $taxonomy );
			}
		}

		// Duplicate all the custom fields
		$custom_fields = get_post_custom( $sourcePostID );
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $key => $value ) {
				add_post_meta( $newUnitID, $key, maybe_unserialize( $value[0] ) );
			}
		}

		$SQL = $wpdb->prepare(
			"
			SELECT *
			FROM $wpcwdb->units_meta
			WHERE unit_id = %d
		",
			$sourcePostID
		);

		$get_current_unit = $wpdb->get_row( $SQL, 'ARRAY_A' );

		// See if there's an entry in the courseware table
		$SQL = $wpdb->prepare(
			"
			SELECT *
			FROM $wpcwdb->units_meta
			WHERE unit_id = %d
		",
			$newUnitID
		);

		// Ensure there's a blank entry in the database for this post.
		if ( ! $wpdb->get_row( $SQL ) && $get_current_unit ) {
			$SQL = $wpdb->prepare(
				"
				INSERT INTO $wpcwdb->units_meta (unit_id, parent_module_id, unit_author, unit_drip_type, unit_drip_date, unit_drip_interval, unit_drip_interval_type)
				VALUES (%d, 0, %d, %s, %s, %d, %s)
			",
				$newUnitID,
				get_current_user_id(),
				$get_current_unit['unit_drip_type'],
				$get_current_unit['unit_drip_date'],
				$get_current_unit['unit_drip_interval'],
				$get_current_unit['unit_drip_interval_type']
			);

			$wpdb->query( $SQL );
		}
	} else { // Post not found, show relevant error
		$ajaxResults['success']  = false;
		$ajaxResults['errormsg'] = __( 'Post could not be found.', 'wp-courseware' );
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $ajaxResults );

	die();
}

/**
 * Handle quizzes Duplication.
 *
 * @since 4.8.0
 */
function WPCW_AJAX_handleQuizzesDuplication() {

	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'security_id' ), 'quiz-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	global $wpdb, $wpcwdb;

	// See if we can get the post that we've asked to duplicate
	$sourcePostID = WPCW_arrays_getValue( $_POST, 'source_id', false );

	$ajaxResults = array(
		'success'  => true,
		'errormsg' => false,
	);

	if ( $sourcePostID ) {

		$SQL = $wpdb->prepare(
			"
			INSERT INTO $wpcwdb->quiz SELECT NULL, CONCAT(quiz_title, ' copy') `quiz_title`, `quiz_desc`, `quiz_author`, `parent_unit_id`, `parent_course_id`, `quiz_type`, `quiz_pass_mark`, `quiz_show_answers`, `quiz_show_survey_responses`, `quiz_attempts_allowed`, `show_answers_settings`, `quiz_paginate_questions`, `quiz_paginate_questions_settings`, `quiz_timer_mode`, `quiz_timer_mode_limit`, `quiz_results_downloadable`, `quiz_results_by_tag`, `quiz_results_by_timer`, `quiz_recommended_score`, `show_recommended_percentage`
			FROM $wpcwdb->quiz
			WHERE quiz_id=%d
		",
			$sourcePostID
		);

		$wpdb->query( $SQL );
		$new_quiz_id    = $wpdb->insert_id;
		$parent_unit_id = 0;
		$new_course_id  = 0;
		$SQL            = $wpdb->prepare(
			"UPDATE $wpcwdb->quiz SET parent_unit_id = %d, parent_course_id = %d WHERE quiz_id = %d",
			$parent_unit_id,
			$new_course_id,
			$new_quiz_id
		);
		$wpdb->query( $SQL );

		$SQL = $wpdb->prepare(
			"
			INSERT INTO $wpcwdb->quiz_qs_mapping SELECT $new_quiz_id `parent_quiz_id`, `question_id`, `question_order`
			FROM $wpcwdb->quiz_qs_mapping
			WHERE parent_quiz_id=%d
		",
			$sourcePostID
		);

		$wpdb->query( $SQL );

		$SQL = $wpdb->prepare(
			"
			INSERT INTO $wpcwdb->quiz_feedback SELECT NULL, `qfeedback_tag_id`, $new_quiz_id `qfeedback_quiz_id`, `qfeedback_summary`, `qfeedback_score_type`, `qfeedback_score_grade`, `qfeedback_message`
			FROM $wpcwdb->quiz_feedback
			WHERE qfeedback_quiz_id=%d
		",
			$sourcePostID
		);

		$wpdb->query( $SQL );

	} else { // Post not found, show relevant error
		$ajaxResults['success']  = false;
		$ajaxResults['errormsg'] = __( 'Post could not be found.', 'wp-courseware' );
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $ajaxResults );

	die();
}

/**
 * Handle modules Duplication.
 *
 * @since 4.8.0
 */
function WPCW_AJAX_handleModulesDuplication() {

	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'security_id' ), 'module-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	global $wpdb, $wpcwdb;

	// See if we can get the post that we've asked to duplicate
	$sourcePostID = WPCW_arrays_getValue( $_POST, 'source_id', false );

	$ajaxResults = array(
		'success'  => true,
		'errormsg' => false,
	);

	if ( $sourcePostID ) {

		$SQL = $wpdb->prepare(
			"
			SELECT *
			FROM $wpcwdb->modules
			WHERE module_id = %d
		",
			$sourcePostID
		);

		$get_current_module = $wpdb->get_row( $SQL );

		// Ensure there's a blank entry in the database for this post.
		if ( $get_current_module ) {

			$SQL = $wpdb->prepare(
				"
				INSERT INTO $wpcwdb->modules SELECT NULL, `parent_course_id`, `module_author`, CONCAT(module_title, ' copy') `module_title`, `module_desc`, `module_order`, `module_number`
				FROM $wpcwdb->modules
				WHERE module_id=%d
			",
				$sourcePostID
			);

			$wpdb->query( $SQL );

			$new_module_id = $wpdb->insert_id;

			// Unit List.
			$unitList = WPCW_units_getListOfUnits( $sourcePostID );

			if ( $unitList ) {
				foreach ( $unitList as $unitID => $unitObj ) {

					$newUnit = get_post( $unitID, 'ARRAY_A' );

					// Got the new unit
					if ( $newUnit ) {

						// Adjust date to today
						$newUnit['post_date'] = current_time( 'mysql' );

						// Remove some of the keys relevant to the other post so that they are generated
						// automatically.
						unset( $newUnit['ID'] );
						unset( $newUnit['guid'] );
						unset( $newUnit['comment_count'] );
						unset( $newUnit['post_name'] );
						unset( $newUnit['post_date_gmt'] );

						// Insert the post into the database
						$newUnitID = wp_insert_post( $newUnit );

						// Duplicate all the taxonomies/terms
						$taxonomies = get_object_taxonomies( $newUnit['post_type'] );
						if ( ! empty( $taxonomies ) ) {
							foreach ( $taxonomies as $taxonomy ) {
								$terms = wp_get_post_terms( $unitID, $taxonomy, array( 'fields' => 'names' ) );
								wp_set_object_terms( $newUnitID, $terms, $taxonomy );
							}
						}

						// Duplicate all the custom fields
						$custom_fields = get_post_custom( $unitID );
						if ( ! empty( $custom_fields ) ) {
							foreach ( $custom_fields as $key => $value ) {
								add_post_meta( $newUnitID, $key, maybe_unserialize( $value[0] ) );
							}
						}

						// See if there's an entry in the courseware table
						$SQL = $wpdb->prepare(
							"
							SELECT *
							FROM $wpcwdb->units_meta
							WHERE unit_id = %d
						",
							$newUnitID
						);

						// Ensure there's a blank entry in the database for this post.
						if ( ! $wpdb->get_row( $SQL ) ) {
							$SQL = $wpdb->prepare(
								"
								INSERT INTO $wpcwdb->units_meta (unit_id, parent_module_id, unit_author)
								VALUES (%d, %d, %d)
							",
								$newUnitID,
								$new_module_id,
								get_current_user_id()
							);

							$wpdb->query( $SQL );
						}

						$quizData = WPCW_quizzes_getListOfQuizzes( $unitID );
						if ( $quizData ) {
							foreach ( $quizData as $quizID => $quizObj ) {

								$SQL = $wpdb->prepare(
									"
									INSERT INTO $wpcwdb->quiz SELECT NULL, `quiz_title`, `quiz_desc`, `quiz_author`, `parent_unit_id`, `parent_course_id`, `quiz_type`, `quiz_pass_mark`, `quiz_show_answers`, `quiz_show_survey_responses`, `quiz_attempts_allowed`, `show_answers_settings`, `quiz_paginate_questions`, `quiz_paginate_questions_settings`, `quiz_timer_mode`, `quiz_timer_mode_limit`, `quiz_results_downloadable`, `quiz_results_by_tag`, `quiz_results_by_timer`, `quiz_recommended_score`, `show_recommended_percentage`
									FROM $wpcwdb->quiz
									WHERE quiz_id=%d
								",
									$quizID
								);

								$wpdb->query( $SQL );

								$new_quiz_id = $wpdb->insert_id;

								$SQL = $wpdb->prepare(
									"
								UPDATE $wpcwdb->quiz
								SET parent_unit_id = %d
								WHERE quiz_id = %d
								",
									$newUnitID,
									$new_quiz_id
								);

								$wpdb->query( $SQL );

								$SQL = $wpdb->prepare(
									"
									INSERT INTO $wpcwdb->quiz_qs_mapping SELECT $new_quiz_id `parent_quiz_id`, `question_id`, `question_order`
									FROM $wpcwdb->quiz_qs_mapping
									WHERE parent_quiz_id=%d
								",
									$quizID
								);

								$wpdb->query( $SQL );

								$SQL = $wpdb->prepare(
									"
									INSERT INTO $wpcwdb->quiz_feedback SELECT NULL, `qfeedback_tag_id`, $new_quiz_id `qfeedback_quiz_id`, `qfeedback_summary`, `qfeedback_score_type`, `qfeedback_score_grade`, `qfeedback_message`
									FROM $wpcwdb->quiz_feedback
									WHERE qfeedback_quiz_id=%d
								",
									$quizID
								);
							}
						}
					}
				}
			}
		}
		$ajaxResults['new_module_id'] = $new_module_id;
	} else { // Post not found, show relevant error

		$ajaxResults['success']  = false;
		$ajaxResults['errormsg'] = __( 'Post could not be found.', 'wp-courseware' );
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $ajaxResults );

	die();
}

/**
 * Handle Course Duplication.
 *
 * @since 4.8.0
 */
function WPCW_AJAX_handleCourseDuplication() {

	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'security_id' ), 'course-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	global $wpdb, $wpcwdb;

	// See if we can get the post that we've asked to duplicate
	$sourcePostID = WPCW_arrays_getValue( $_POST, 'source_id', false );

	$ajaxResults = array(
		'success'  => true,
		'errormsg' => false,
	);

	$new_course_post = get_post( $sourcePostID, 'ARRAY_A' );

	// Got the new course
	if ( $new_course_post ) {

		// Modify the post title to add ' Copy' to the end.
		$new_course_post['post_title'] .= ' ' . __( 'Copy', 'wp-courseware' );

		// Adjust date to today
		$new_course_post['post_date'] = current_time( 'mysql' );

		// Remove some of the keys relevant to the other post so that they are generated
		// automatically.
		unset( $new_course_post['ID'] );
		unset( $new_course_post['guid'] );
		unset( $new_course_post['comment_count'] );
		unset( $new_course_post['post_name'] );
		unset( $new_course_post['post_date_gmt'] );

		// Insert the post into the database
		$new_course_post_id = wp_insert_post( $new_course_post );

		// Duplicate all the taxonomies/terms
		$taxonomies = get_object_taxonomies( $new_course_post['post_type'] );
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $sourcePostID, $taxonomy, array( 'fields' => 'names' ) );
				wp_set_object_terms( $new_course_post_id, $terms, $taxonomy );
			}
		}

		// Duplicate all the custom fields
		$custom_fields = get_post_custom( $sourcePostID );
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $key => $value ) {
				add_post_meta( $new_course_post_id, $key, maybe_unserialize( $value[0] ) );
			}
		}

		$SQL = $wpdb->prepare(
			"
			SELECT course_id
			FROM $wpcwdb->courses
			WHERE course_post_id = %d
		",
			$new_course_post_id
		);

		$get_new_course = $wpdb->get_row( $SQL, 'ARRAY_A' );
		$new_course_id  = $get_new_course['course_id'];

		if ( $new_course_id ) {
			$SQL = $wpdb->prepare(
				"
				SELECT *
				FROM $wpcwdb->courses
				WHERE course_post_id = %d
			",
				$sourcePostID
			);

			$get_current_course = $wpdb->get_row( $SQL, 'ARRAY_A' );

			if ( $get_current_course ) {
				$SQL = $wpdb->prepare(
					"UPDATE $wpcwdb->courses
				SET course_title = %s,
				course_desc = %s,
				course_opt_completion_wall = %s,
				course_opt_use_certificate = %s,
				course_opt_user_access = %s,
				course_unit_count = %d,
				course_from_name = %s,
				course_opt_prerequisites = %s,
				course_message_unit_complete = %s,
				course_message_course_complete = %s,
				course_message_unit_not_logged_in = %s,
				course_message_unit_pending = %s,
				course_message_unit_no_access = %s,
				course_message_prerequisite_not_met = %s,
				course_message_unit_not_yet = %s,
				course_message_unit_not_yet_dripfeed = %s,
				course_message_quiz_open_grading_blocking = %s,
				course_message_quiz_open_grading_non_blocking = %s,
				email_complete_module_option_admin = %s,
				email_complete_module_option = %s,
				email_complete_module_subject = %s,
				email_complete_module_body = %s,
				email_complete_course_option_admin = %s,
				email_complete_course_option = %s,
				email_complete_course_subject = %s,
				email_complete_course_body = %s,
				email_quiz_grade_option = %s,
				email_quiz_grade_subject = %s,
				email_quiz_grade_body = %s,
				email_complete_course_grade_summary_subject = %s,
				email_complete_course_grade_summary_body = %s,
				email_complete_unit_option_admin = %s,
				email_complete_unit_option = %s,
				email_complete_unit_subject = %s,
				email_complete_unit_body = %s,
				email_unit_unlocked_subject = %s,
				email_unit_unlocked_body = %s,
				cert_signature_type = %s,
				cert_sig_text = %s,
				cert_sig_image_url = %s,
				cert_logo_enabled = %s,
				cert_logo_url = %s,
				cert_background_type = %s,
				cert_background_custom_url = %s,
				payments_type = %s,
				payments_price = %s,
				payments_interval = %s,
				course_bundles = %s,
				installments_enabled = %s,
				installments_number = %d,
				installments_amount = %s,
				installments_interval = %s,
				unit_advancement = %s,
				course_access_expiration = %s,
				cae_interval_date_int_expire = %d,
				cae_interval_date_string_expire = %s,
				cae_reset_on_course_expire = %s,
				cae_specific_date = %s,
				certificate_template = %d
				WHERE course_post_id = %d
				",
					$get_current_course['course_title'] . ' Copy',
					$get_current_course['course_desc'],
					$get_current_course['course_opt_completion_wall'],
					$get_current_course['course_opt_use_certificate'],
					$get_current_course['course_opt_user_access'],
					$get_current_course['course_unit_count'],
					$get_current_course['course_from_name'],
					$get_current_course['course_opt_prerequisites'],
					$get_current_course['course_message_unit_complete'],
					$get_current_course['course_message_course_complete'],
					$get_current_course['course_message_unit_not_logged_in'],
					$get_current_course['course_message_unit_pending'],
					$get_current_course['course_message_unit_no_access'],
					$get_current_course['course_message_prerequisite_not_met'],
					$get_current_course['course_message_unit_not_yet'],
					$get_current_course['course_message_unit_not_yet_dripfeed'],
					$get_current_course['course_message_quiz_open_grading_blocking'],
					$get_current_course['course_message_quiz_open_grading_non_blocking'],
					$get_current_course['email_complete_module_option_admin'],
					$get_current_course['email_complete_module_option'],
					$get_current_course['email_complete_module_subject'],
					$get_current_course['email_complete_module_body'],
					$get_current_course['email_complete_course_option_admin'],
					$get_current_course['email_complete_course_option'],
					$get_current_course['email_complete_course_subject'],
					$get_current_course['email_complete_course_body'],
					$get_current_course['email_quiz_grade_option'],
					$get_current_course['email_quiz_grade_subject'],
					$get_current_course['email_quiz_grade_body'],
					$get_current_course['email_complete_course_grade_summary_subject'],
					$get_current_course['email_complete_course_grade_summary_body'],
					$get_current_course['email_complete_unit_option_admin'],
					$get_current_course['email_complete_unit_option'],
					$get_current_course['email_complete_unit_subject'],
					$get_current_course['email_complete_unit_body'],
					$get_current_course['email_unit_unlocked_subject'],
					$get_current_course['email_unit_unlocked_body'],
					$get_current_course['cert_signature_type'],
					$get_current_course['cert_sig_text'],
					$get_current_course['cert_sig_image_url'],
					$get_current_course['cert_logo_enabled'],
					$get_current_course['cert_logo_url'],
					$get_current_course['cert_background_type'],
					$get_current_course['cert_background_custom_url'],
					$get_current_course['payments_type'],
					$get_current_course['payments_price'],
					$get_current_course['payments_interval'],
					$get_current_course['course_bundles'],
					$get_current_course['installments_enabled'],
					$get_current_course['installments_number'],
					$get_current_course['installments_amount'],
					$get_current_course['installments_interval'],
					$get_current_course['unit_advancement'],
					$get_current_course['course_access_expiration'],
					$get_current_course['cae_interval_date_int_expire'],
					$get_current_course['cae_interval_date_string_expire'],
					$get_current_course['cae_reset_on_course_expire'],
					$get_current_course['cae_specific_date'],
					$get_current_course['certificate_template'],
					$new_course_post_id
				);

				$wpdb->query( $SQL );

				$moduleList = WPCW_courses_getModuleDetailsList( $get_current_course['course_id'] );
				if ( ! empty( $moduleList ) ) {
					foreach ( $moduleList as $moduleID => $moduleDetails ) {
						$SQL = $wpdb->prepare(
							"
							SELECT *
							FROM $wpcwdb->modules
							WHERE module_id = %d
						",
							$moduleID
						);

						$get_current_module = $wpdb->get_row( $SQL );

						// Ensure there's a blank entry in the database for this post.
						if ( $get_current_module ) {

							$SQL = $wpdb->prepare(
								"
								INSERT INTO $wpcwdb->modules SELECT NULL, %d `parent_course_id`, `module_author`, `module_title`, `module_desc`, `module_order`, `module_number`
								FROM $wpcwdb->modules
								WHERE module_id=%d
							",
								$new_course_id,
								$moduleID
							);

							$wpdb->query( $SQL );

							$new_module_id = $wpdb->insert_id;

							// Unit List.
							$unitList = WPCW_units_getListOfUnits( $moduleID );

							if ( $unitList ) {
								foreach ( $unitList as $unitID => $unitObj ) {

									$newUnit = get_post( $unitID, 'ARRAY_A' );

									// Got the new unit
									if ( $newUnit ) {

										// Adjust date to today
										$newUnit['post_date'] = current_time( 'mysql' );

										// Remove some of the keys relevant to the other post so that they are generated
										// automatically.
										unset( $newUnit['ID'] );
										unset( $newUnit['guid'] );
										unset( $newUnit['comment_count'] );
										unset( $newUnit['post_name'] );
										unset( $newUnit['post_date_gmt'] );

										// Insert the post into the database
										$newUnitID = wp_insert_post( $newUnit );

										// Duplicate all the taxonomies/terms
										$taxonomies = get_object_taxonomies( $newUnit['post_type'] );
										if ( ! empty( $taxonomies ) ) {
											foreach ( $taxonomies as $taxonomy ) {
												$terms = wp_get_post_terms( $unitID, $taxonomy, array( 'fields' => 'names' ) );
												wp_set_object_terms( $newUnitID, $terms, $taxonomy );
											}
										}

										// Duplicate all the custom fields
										$custom_fields = get_post_custom( $unitID );
										if ( ! empty( $custom_fields ) ) {
											foreach ( $custom_fields as $key => $value ) {
												add_post_meta( $newUnitID, $key, maybe_unserialize( $value[0] ) );
											}
										}

										// See if there's an entry in the courseware table
										$SQL = $wpdb->prepare(
											"
											SELECT *
											FROM $wpcwdb->units_meta
											WHERE unit_id = %d
										",
											$newUnitID
										);

										// Ensure there's a blank entry in the database for this post.
										if ( ! $wpdb->get_row( $SQL ) ) {
											$SQL = $wpdb->prepare(
												"
												INSERT INTO $wpcwdb->units_meta (unit_id, parent_module_id, parent_course_id, unit_author)
												VALUES (%d, %d, %d, %d)
											",
												$newUnitID,
												$new_module_id,
												$new_course_id,
												get_current_user_id()
											);

											$wpdb->query( $SQL );
										}

										$quizData = WPCW_quizzes_getListOfQuizzes( $unitID );
										if ( $quizData ) {
											foreach ( $quizData as $quizID => $quizObj ) {

												$SQL = $wpdb->prepare(
													"
													INSERT INTO $wpcwdb->quiz SELECT NULL, `quiz_title`, `quiz_desc`, `quiz_author`, `parent_unit_id`, `parent_course_id`, `quiz_type`, `quiz_pass_mark`, `quiz_show_answers`, `quiz_show_survey_responses`, `quiz_attempts_allowed`, `show_answers_settings`, `quiz_paginate_questions`, `quiz_paginate_questions_settings`, `quiz_timer_mode`, `quiz_timer_mode_limit`, `quiz_results_downloadable`, `quiz_results_by_tag`, `quiz_results_by_timer`, `quiz_recommended_score`, `show_recommended_percentage`
													FROM $wpcwdb->quiz
													WHERE quiz_id=%d
												",
													$quizID
												);

												$wpdb->query( $SQL );

												$new_quiz_id = $wpdb->insert_id;

												$SQL = $wpdb->prepare(
													"
												UPDATE $wpcwdb->quiz
												SET parent_unit_id = %d,
												parent_course_id = %d
												WHERE quiz_id = %d
												",
													$newUnitID,
													$new_course_id,
													$new_quiz_id
												);

												$wpdb->query( $SQL );

												$SQL = $wpdb->prepare(
													"
													INSERT INTO $wpcwdb->quiz_qs_mapping SELECT $new_quiz_id `parent_quiz_id`, `question_id`, `question_order`
													FROM $wpcwdb->quiz_qs_mapping
													WHERE parent_quiz_id=%d
												",
													$quizID
												);

												$wpdb->query( $SQL );

												$SQL = $wpdb->prepare(
													"
													INSERT INTO $wpcwdb->quiz_feedback SELECT NULL, `qfeedback_tag_id`, $new_quiz_id `qfeedback_quiz_id`, `qfeedback_summary`, `qfeedback_score_type`, `qfeedback_score_grade`, `qfeedback_message`
													FROM $wpcwdb->quiz_feedback
													WHERE qfeedback_quiz_id=%d
												",
													$quizID
												);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	} else { // Post not found, show relevant error
		$ajaxResults['success']  = false;
		$ajaxResults['errormsg'] = __( 'Post could not be found.', 'wp-courseware' );
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $ajaxResults );

	die();
}

/**
 * Handle Unit Ordering Saving.
 *
 * This function will save the order of the modules, units and any unassigned units.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_handleUnitOrderingSaving() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'order_nonce' ), 'wpcw-order-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	// Get list of modules to save, check IDs are what we expect, and abort if nothing to do.
	$moduleList = WPCW_arrays_getValue( $_POST, 'moduleList' );
	if ( ! $moduleList || count( $moduleList ) < 1 ) {
		die();
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	$parentCourseID = 0;

	// Save new module ordering to database
	$moduleOrderCount = 0;

	// Ordering of units is absolute to the whole course
	$unitOrderCount = 0;

	// Need a course ID for resetting the ordering.
	foreach ( $moduleList as $moduleID ) {
		// Validate we have an actual module
		if ( preg_match( '/^wpcw_mod_(\d+)$/', $moduleID, $matches ) ) {
			// Get course ID from module
			$moduleDetails = WPCW_modules_getModuleDetails( $matches[1] );
			if ( $moduleDetails ) {
				$parentCourseID = $moduleDetails->parent_course_id;
				break;
			}
		}
	}

	// If there's no associated parent course, there's an issue.
	if ( ! $parentCourseID ) {
		error_log( 'WPCW_AJAX_handleUnitOrderingSaving(). No associated parent course ID, so aborting.' );
		die();
	}

	// 2013-05-01 - Bug with orphan modules being left in the units_meta
	// Fix - Clean out existing units in this course, resetting them.
	// Then update the ordering using the loops below.
	$SQL = $wpdb->prepare(
		"
		UPDATE $wpcwdb->units_meta
		   SET unit_order = 0, parent_module_id = 0,
		   	   parent_course_id = 0, unit_number = 0
		WHERE parent_course_id = %d
	",
		$parentCourseID
	);

	$wpdb->query( $SQL );

	foreach ( $moduleList as $moduleID ) {
		// Check module name matches expected format.
		if ( preg_match( '/^wpcw_mod_(\d+)$/', $moduleID, $matches ) ) {
			$moduleOrderCount ++;
			$moduleIDClean = $matches[1];

			// Update module list with new ordering
			$SQL = $wpdb->prepare(
				"
				UPDATE $wpcwdb->modules
				   SET module_order = %d, module_number = %d
				WHERE module_id = %d
			",
				$moduleOrderCount,
				$moduleOrderCount,
				$moduleIDClean
			);

			$wpdb->query( $SQL );

			// Check units associated with this module
			$unitList = WPCW_arrays_getValue( $_POST, $moduleID );
			if ( $unitList && count( $unitList ) > 0 ) {
				$unitNumber = 0;
				foreach ( $unitList as $unitID ) {
					$unitNumber ++;

					// Check unit name matches expected format.
					if ( preg_match( '/^wpcw_unit_(\d+)$/', $unitID, $matches ) ) {
						$unitOrderCount += 10;
						$unitIDClean     = $matches[1];

						// Update database with new association and ordering.
						$SQL = $wpdb->prepare(
							"
							UPDATE $wpcwdb->units_meta
							   SET unit_order = %d, parent_module_id = %d,
							   	   parent_course_id = %d, unit_number = %d
							WHERE unit_id = %d
						",
							$unitOrderCount,
							$moduleIDClean,
							$parentCourseID,
							$unitNumber,
							$unitIDClean
						);

						$wpdb->query( $SQL );

						// 2013-05-01 - Updated to use the module ID, rather than the module order.
						update_post_meta( $unitIDClean, 'wpcw_associated_module', $moduleIDClean );
					}
				}// end foreach
			} // end of $unitList check
		}
	}

	// Check for any units that have associated quizzes
	foreach ( $_POST as $key => $value ) {
		// Check any post value that has a unit in it
		if ( preg_match( '/^wpcw_unit_(\d+)$/', $key, $matches ) ) {
			$unitIDClean = $matches[1];

			// Try to extract the unit ID
			// [wpcw_unit_71] => Array
			// (
			// [0] => wpcw_quiz_2
			// )
			$quizIDRaw = false;
			if ( $value && is_array( $value ) ) {
				$quizIDRaw = $value[0];
			}

			// Got a matching quiz ID
			if ( preg_match( '/^wpcw_quiz_(\d+)$/', $quizIDRaw, $matches ) ) {
				$quizIDClean = $matches[1];

				// Grab parent course ID from unit. Can't assume all units are in same course.
				$parentData     = WPCW_units_getAssociatedParentData( $unitIDClean );
				$parentCourseID = $parentData->parent_course_id;

				// Update database with new association and ordering.
				$SQL = $wpdb->prepare(
					"
					UPDATE $wpcwdb->quiz
					   SET parent_unit_id = %d, parent_course_id = %d
					WHERE quiz_id = %d
				",
					$unitIDClean,
					$parentCourseID,
					$quizIDClean
				);

				$wpdb->query( $SQL );

				// Add new associated unit information to the user quiz progress,
				// keeping any existing quiz results.
				$SQL = $wpdb->prepare(
					"
					UPDATE $wpcwdb->user_progress_quiz
					   SET unit_id = %d
					WHERE quiz_id = %d
				",
					$unitIDClean,
					$quizIDClean
				);

				$wpdb->query( $SQL );
			}
		}
	}

	// Check for any unassigned units, and ensure they're de-associated from modules.
	$unitList = WPCW_arrays_getValue( $_POST, 'unassunits' );
	if ( $unitList && count( $unitList ) > 0 ) {
		foreach ( $unitList as $unitID ) {
			// Check unit name matches expected format.
			if ( preg_match( '/^wpcw_unit_(\d+)$/', $unitID, $matches ) ) {
				$unitIDClean = $matches[1];

				// Remove notifications
				WPCW_queue_dripfeed::updateQueueItems_unitRemovedFromCourse( $unitIDClean );

				// Update database with new association and ordering.
				$SQL = $wpdb->prepare(
					"
					UPDATE $wpcwdb->units_meta
					   SET unit_order = 0, parent_module_id = 0, parent_course_id = 0, unit_number = 0
					WHERE unit_id = %d
				",
					$unitIDClean
				);

				$wpdb->query( $SQL );

				// Update post meta to remove associated module detail
				update_post_meta( $unitIDClean, 'wpcw_associated_module', 0 );

				// Remove progress for this unit, as likely to be associated with something else.
				$SQL = $wpdb->prepare(
					"
					DELETE FROM $wpcwdb->user_progress
					WHERE unit_id = %d
				",
					$unitIDClean
				);

				$wpdb->query( $SQL );
			}
		}
	}

	// Check for any unassigned quizzes, and ensure they're de-associated from units.
	$quizList = WPCW_arrays_getValue( $_POST, 'unassquizzes' );
	if ( $quizList && count( $quizList ) > 0 ) {
		foreach ( $quizList as $quizID ) {
			// Check unit name matches expected format.
			if ( preg_match( '/^wpcw_quiz_(\d+)$/', $quizID, $matches ) ) {
				$quizIDClean = $matches[1];

				// Update database with new association and ordering.
				$SQL = $wpdb->prepare(
					"
					UPDATE $wpcwdb->quiz
					   SET parent_unit_id = 0, parent_course_id = 0
					WHERE quiz_id = %d
				",
					$quizIDClean
				);

				$wpdb->query( $SQL );

				// Remove the associated unit information from the user quiz progress.
				// But keep the quiz results for now.
				$SQL = $wpdb->prepare(
					"
					UPDATE $wpcwdb->user_progress_quiz
					   SET unit_id = 0
					WHERE quiz_id = %d
				",
					$quizIDClean
				);

				$wpdb->query( $SQL );
			}
		} // end foreach ($quizList as $quizID)
	}

	// Update course details
	$courseDetails = WPCW_courses_getCourseDetails( $parentCourseID );
	if ( $courseDetails ) {
		do_action( 'wpcw_course_details_updated', $courseDetails );
	}

	die();
}

/**
 * Handle Quiz Retake.
 *
 * Lots of checking needs to go on here for security reasons
 * to ensure that they don't manipulate their own progress (or somebody elses).
 *
 * @since 1.0.0
 */
function WPCW_AJAX_units_handleQuizRetakeRequest() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'progress_nonce' ), 'wpcw-progress-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	// Get unit and quiz ID
	$unitID = intval( WPCW_arrays_getValue( $_POST, 'unitid' ) );
	$quizID = intval( WPCW_arrays_getValue( $_POST, 'quizid' ) );

	// Get the post object for this quiz item.
	$post = get_post( $unitID );
	if ( ! $post ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not request a retake for the quiz.', 'wp-courseware' ) . ' ' . __( 'Could not find training unit.', 'wp-courseware' ) );
		die();
	}

	// Initalise the unit details
	$fe = new WPCW_UnitFrontend( $post );

	// Get associated data for this unit. No course/module data, then it's not a unit
	if ( ! $fe->check_unit_doesUnitHaveParentData() ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not request a retake for the quiz.', 'wp-courseware' ) . ' ' . __( 'Could not find course details for unit.', 'wp-courseware' ) );
		die();
	}

	// User not allowed access to content
	if ( ! $fe->check_user_canUserAccessCourse() ) {
		echo $fe->message_user_cannotAccessCourse();
		die();
	}

	// See if we're in a position to retake this quiz?
	if ( ! $fe->check_quizzes_canUserRequestRetake() ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not request a retake for the quiz.', 'wp-courseware' ) . ' ' . __( 'You are not permitted to retake this quiz.', 'wp-courseware' ) );
		die();
	}

	// Trigger the upgrade to progress so that we're allowed to retake this quiz.
	$fe->update_quizzes_requestQuizRetake();

	// Only complete if allowed to continue.
	echo apply_filters( 'the_content', $fe->render_detailsForUnit( false, true ) );

	die();
}

/**
 * Handle Unit User Progress.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_units_handleUserProgress() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'progress_nonce' ), 'wpcw-progress-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	$unitID = WPCW_arrays_getValue( $_POST, 'id' );

	// Validate the course ID
	if ( ! preg_match( '/unit_complete_(\d+)/', $unitID, $matches ) ) {
		echo WPCW_UnitFrontend::message_error_getCompletionBox_error();
		die();
	}
	$unitID = $matches[1];

	// Get the post object for this quiz item.
	$post = get_post( $unitID );
	if ( ! $post ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not save your progress.', 'wp-courseware' ) . ' ' . __( 'Could not find training unit.', 'wp-courseware' ) );
		die();
	}

	// Initalise the unit details
	$fe = new WPCW_UnitFrontend( $post );

	// Get associated data for this unit. No course/module data, then it's not a unit
	if ( ! $fe->check_unit_doesUnitHaveParentData() ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not save your progress.', 'wp-courseware' ) . ' ' . __( 'Could not find course details for unit.', 'wp-courseware' ) );
		die();
	}

	// User not allowed access to content
	if ( ! $fe->check_user_canUserAccessCourse() ) {
		echo $fe->message_user_cannotAccessCourse();
		die();
	}

	// Get Parent Data.
	$unit_parent_data = $fe->fetch_getUnitParentData();

	WPCW_units_saveUserProgress_Complete( $fe->fetch_getUserID(), $fe->fetch_getUnitID(), 'complete' );

	// Unit complete, check if course/module is complete too.
	do_action( 'wpcw_user_completed_unit', $fe->fetch_getUserID(), $fe->fetch_getUnitID(), $fe->fetch_getUnitParentData() );

	// Check for automatic advancement.
	if ( $unit_parent_data && isset( $unit_parent_data->unit_advancement ) && 'automatic' === $unit_parent_data->unit_advancement ) {
		/** @var Unit $next_course_unit */
		$next_course_unit = wpcw_get_student_progress_next_course_unit( $fe->fetch_getUserID(), $unit_parent_data->parent_course_id, 'object' );

		/**
		 * Filter: Disable Course Unit Advancement.
		 *
		 * @since 4.6.2
		 *
		 * @param int The unit parent course id.
		 * @param int The unit id.
		 *
		 * @return bool True to disable unit davancement. Default is false.
		 */
		$disable_unit_advancement = apply_filters( 'wpcw_course_unit_advancement_disable', false, $unit_parent_data->parent_course_id, $fe->fetch_getUnitID() );

		if ( $next_course_unit && ! $disable_unit_advancement ) {
			wp_send_json_success(
				array(
					'url' => $next_course_unit->get_view_url(),
				)
			);
		}
	}

	echo apply_filters( 'the_content', $fe->render_detailsForUnit( false, false ) );

	die();
}

/**
 * Handle Quiz Response.
 *
 * Called when a user is submitting quiz answers via the frontend form.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_units_handleQuizResponse() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'progress_nonce' ), 'wpcw-progress-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	// Quiz ID and Unit ID are combined in the single CSS ID for validation.
	// So validate both are correct and that user is allowed to access quiz.
	$quizAndUnitID = WPCW_arrays_getValue( $_POST, 'id' );

	// e.g. quiz_complete_69_1 or quiz_complete_17_2 (first ID is unit, 2nd ID is quiz)
	if ( ! preg_match( '/quiz_complete_(\d+)_(\d+)/', $quizAndUnitID, $matches ) ) {
		echo WPCW_UnitFrontend::message_error_getCompletionBox_error();
		die();
	}

	// Use the extracted data for further validation
	$unitID = $matches[1];
	$quizID = $matches[2];

	// Get the post object for this quiz item.
	$post = get_post( $unitID );
	if ( ! $post ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not save your quiz results.', 'wp-courseware' ) . ' ' . __( 'Could not find training unit.', 'wp-courseware' ) );
		die();
	}

	// Initalise the unit details
	$fe = new WPCW_UnitFrontend( $post );
	$fe->setTriggeredAfterAJAXRequest();

	// Get associated data for this unit. No course/module data, then it's not a unit
	if ( ! $fe->check_unit_doesUnitHaveParentData() ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not save your quiz results.', 'wp-courseware' ) . ' ' . __( 'Could not find course details for unit.', 'wp-courseware' ) );
		die();
	}

	// User not allowed access to content
	if ( ! $fe->check_user_canUserAccessCourse() ) {
		echo $fe->message_user_cannotAccessCourse();
		die();
	}

	// Check that the quiz is valid and belongs to this unit
	if ( ! $fe->check_quizzes_isQuizValidForUnit( $quizID ) ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not save your quiz results.', 'wp-courseware' ) . ' ' . __( 'Quiz data does not match quiz for this unit.', 'wp-courseware' ) );
		die();
	}

	$canContinue = false;

	// Do we have all the answers that we need so that we can grade the quiz?
	// Answer Check Variation A - Paging
	if ( $fe->check_paging_areWePagingQuestions() ) {
		// If this is false, then we keep checking for more answers.
		$readyForMarking = $fe->check_quizzes_canWeContinue_checkAnswersFromPaging( $_POST );
	} else { // Answer Check Variation B - All at once (no paging)
		// If this is false, then the form is represented asking for fixes.
		$readyForMarking = $fe->check_quizzes_canWeContinue_checkAnswersFromOnePageQuiz( $_POST );
	}

	// Now checks are done, $this->unitQuizProgress contains the latest questions so that we can mark them.
	if ( $readyForMarking || $fe->check_timers_doWeHaveAnActiveTimer_thatHasExpired() ) {
		$canContinue = $fe->check_quizzes_gradeQuestionsForQuiz();
	}

	// Validate the answers that we have, which determines if we can carry on to the next
	// unit, or if the user needs to do something else.
	if ( $canContinue ) {
		WPCW_units_saveUserProgress_Complete( $fe->fetch_getUserID(), $fe->fetch_getUnitID(), 'complete' );

		// Unit complete, check if course/module is complete too.
		do_action( 'wpcw_user_completed_unit', $fe->fetch_getUserID(), $fe->fetch_getUnitID(), $fe->fetch_getUnitParentData() );
	}

	// Show the appropriate messages/forms for the user to look at. This is common for all execution
	// paths.
	// DJH 2015-09-09 - Added capability for next button to show when a user completes a quiz.
	echo apply_filters( 'the_content', $fe->render_detailsForUnit( false, ! $canContinue ) );

	die();
}

/**
 * Handle a user wanting to go to the previous question or
 * jump a question without saving the question details.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_units_handleQuizJumpQuestion() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'progress_nonce' ), 'wpcw-progress-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	// Get unit and quiz ID
	$unitID = intval( WPCW_arrays_getValue( $_POST, 'unitid' ) );
	$quizID = intval( WPCW_arrays_getValue( $_POST, 'quizid' ) );

	$jumpMode  = 'previous';
	$msgPrefix = __( 'Error - could not load the previous question.', 'wp-courseware' ) . ' ';

	// We're skipping ahead.
	if ( 'next' == WPCW_arrays_getValue( $_POST, 'qu_direction' ) ) {
		$jumpMode  = 'next';
		$msgPrefix = __( 'Error - could not load the next question.', 'wp-courseware' ) . ' ';
	}

	// Get the post object for this quiz item.
	$post = get_post( $unitID );
	if ( ! $post ) {
		echo WPCW_UnitFrontend::message_createMessage_error( $msgPrefix . __( 'Could not find training unit.', 'wp-courseware' ) );
		die();
	}

	// Initalise the unit details
	$fe = new WPCW_UnitFrontend( $post );

	// Get associated data for this unit. No course/module data, then it's not a unit
	if ( ! $fe->check_unit_doesUnitHaveParentData() ) {
		echo WPCW_UnitFrontend::message_createMessage_error( $msgPrefix . __( 'Could not find course details for unit.', 'wp-courseware' ) );
		die();
	}

	// User not allowed access to content
	if ( ! $fe->check_user_canUserAccessCourse() ) {
		echo $fe->message_user_cannotAccessCourse();
		die();
	}

	// Check that the quiz is valid and belongs to this unit
	if ( ! $fe->check_quizzes_isQuizValidForUnit( $quizID ) ) {
		echo WPCW_UnitFrontend::message_createMessage_error( $msgPrefix . __( 'Quiz data does not match quiz for this unit.', 'wp-courseware' ) );
		die();
	}

	$canContinue = false;

	// If we're paging, then do what we need next.
	if ( $fe->check_paging_areWePagingQuestions() ) {
		$fe->fetch_paging_getQuestion_moveQuestionMarker( $jumpMode );
	}

	echo apply_filters( 'the_content', $fe->render_detailsForUnit( false, true ) );

	die();
}

/**
 * Function called when user starting a quiz
 * and needs to kick off the timer.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_units_handleQuizTimerBegin() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'progress_nonce' ), 'wpcw-progress-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	// Get unit and quiz ID
	$unitID = intval( WPCW_arrays_getValue( $_POST, 'unitid' ) );
	$quizID = intval( WPCW_arrays_getValue( $_POST, 'quizid' ) );

	// Get the post object for this quiz item.
	$post = get_post( $unitID );
	if ( ! $post ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not start the timer for the quiz.', 'wp-courseware' ) . ' ' . __( 'Could not find training unit.', 'wp-courseware' ) );
		die();
	}

	// Initalise the unit details
	$fe = new WPCW_UnitFrontend( $post );

	// Get associated data for this unit. No course/module data, then it's not a unit
	if ( ! $fe->check_unit_doesUnitHaveParentData() ) {
		echo WPCW_UnitFrontend::message_createMessage_error( __( 'Error - could not start the timer for the quiz.', 'wp-courseware' ) . ' ' . __( 'Could not find course details for unit.', 'wp-courseware' ) );
		die();
	}

	// User not allowed access to content
	if ( ! $fe->check_user_canUserAccessCourse() ) {
		echo $fe->message_user_cannotAccessCourse();
		die();
	}

	// See if we're in a position to retake this quiz?
	// if (!$fe->check_quizzes_canUserRequestRetake())
	// {
	// echo WPCW_UnitFrontend::message_createMessage_error(__('Error - could not start the timer for the quiz.', 'wp-courseware') . ' ' . __('You are not permitted to retake this quiz.', 'wp-courseware'));
	// die();
	// }

	// Trigger the upgrade to progress so that we can start the quiz, and trigger the timer.
	$fe->update_quizzes_beginQuiz();

	// Only complete if allowed to continue.
	echo apply_filters( 'the_content', $fe->render_detailsForUnit( false, true ) );

	die();
}

/**
 * Function called when the user is
 * enrolling via enrollment shortcode.
 *
 * @since 1.0.0
 */
function WPCW_AJAX_course_handleEnrollment_button() {
	if ( ! wp_verify_nonce( WPCW_arrays_getValue( $_POST, 'enrollment_nonce' ), 'wpcw-enrollment-nonce' ) ) {
		die( __( 'Security check failed!', 'wp-courseware' ) );
	}

	$courseList = WPCW_arrays_getValue( $_POST, 'id' );
	$redirect   = WPCW_arrays_getValue( $_POST, 'redirect' );

	// Validate the course ID
	if ( ! preg_match( '/(\d+)(_\s*\d+)*/', $courseList, $matches ) ) {
		echo WPCW_UnitFrontend::message_error_getCompletionBox_error();
		die();
	}

	$courseIDs = explode( '_', $matches[0] );
	$user_id   = get_current_user_id();

	// enroll user into course(s)
	WPCW_courses_syncUserAccess( $user_id, $courseIDs, 'add' );

	// Check redirect.
	if ( $redirect ) {
		foreach ( $courseIDs as $course_id ) {
			if ( ( $course = wpcw_get_course( $course_id ) ) ) {
				if ( ( $units = $course->get_units( array( 'number' => 1 ) ) ) ) {
					/** @var \WPCW\Models\Unit $unit */
					$unit = $units[0];
					if ( $unit instanceof \WPCW\Models\Unit ) {
						wp_send_json_success( array( 'redirect' => $unit->get_view_url() ) );
					}
				}
			}
		}
	}

	// verify enrollment
	foreach ( $courseIDs as $courseID ) {
		// Back Compat
		$course_id = $courseID;

		// Get course details to fetch course title
		$course = WPCW_courses_getCourseDetails( $courseID );

		// Can the student access the course now?
		$userCourses = WPCW_courses_canUserAccessCourse( $courseID, $user_id );

		if ( $userCourses ) {
			$course_title = sprintf( __( '%s', 'wp-courseware' ), $course->course_title );
			/**
			 * Filter: Course Enrollment Success Message.
			 *
			 * @since 4.3.0
			 *
			 * @param string $message The success message.
			 * @param int $course_id The course id.
			 * @param int $user_id The user id.
			 *
			 * @return string $message The success message modified.
			 */
			$success_message = apply_filters( 'wpcw_course_enrollment_success_message', sprintf( __( 'Success! You have been enrolled into %s', 'wp-courseware' ), $course_title ), $course_id, $user_id );
			echo WPCW_UnitFrontend::message_createMessage_success( $success_message );
		} else {
			/**
			 * Filter: Course Enrollment Error Message.
			 *
			 * @since 4.3.0
			 *
			 * @param string $message The success message.
			 * @param int $course_id The course id.
			 * @param int $user_id The user id.
			 *
			 * @return string $message The success message modified.
			 */
			$error_message = apply_filters( 'wpcw_course_enrollment_error_message', __( 'Oops! Something went wrong. Please contact the course instructor for more information.', 'wp-courseware' ), $course_id, $user_id );
			echo WPCW_UnitFrontend::message_createMessage_error( $error_message );
		}
	}

	die();
}
