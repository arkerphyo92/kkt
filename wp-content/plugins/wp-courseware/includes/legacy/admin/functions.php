<?php
/**
 * WP Courseware Admin Only Functions.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Generates the tab header for the page.
 *
 * @since 1.0.0
 *
 * @param array $tabList The list of tabs to create (name => label)
 * @param string $tabID The CSS ID of the tab wrapper for the page.
 * @param string $currentTab The current tab to show (using name)
 *
 * @return string The rendered tab header for the page. A closing </div> is needed to complete this wrapper.
 */
function WPCW_tabs_generateTabHeader( $tabList, $tabID, $currentTab = false ) {
	$html = false;

	// Generate the tabs
	$html .= sprintf( '<div class="wpcw_tab_wrapper" id="%s"><div class="wpcw_tab_wrapper_tabs">', $tabID );

	// Select the first tab if no tab has been selected
	if ( ! $currentTab ) {
		$currentTab = current( array_keys( $tabList ) );
	}

	// Now render each of the tabs on the page.
	foreach ( $tabList as $tabName => $tabDetails ) {
		// Work out the CSS class if selected
		$class = ( $tabName == $currentTab ) ? ' wpcw_tab_active' : '';

		// Any extra classes
		if ( isset( $tabDetails['cssclass'] ) ) {
			$class .= ' ' . $tabDetails['cssclass'];
		}

		$html .= sprintf( '<a class="wpcw_tab%s" href="#" data-tab="%s" id="wpcw_tab_%s">%s</a>', $class, $tabName, $tabName, $tabDetails['label'] );
	}
	$html .= '</div>'; // .wpcw_tab_wrapper_tabs

	return $html;
}

/**
 * Safe method to find a subitem on the menu and remove it.
 *
 * @since 1.0.0
 *
 * @param string $submenuName The name of the submenu to search.
 * @param stirng $menuItemID The id of the menu item to be removed.
 */
function WPCW_menu_removeSubmenuItem( $submenuName, $menuItemID ) {
	global $submenu;

	// Not found
	if ( ! isset( $submenu[ $submenuName ] ) ) {
		return false;
	}

	// Search each item of the submenu
	foreach ( $submenu[ $submenuName ] as $index => $details ) {
		// Found a matching subitem title
		if ( $details[2] == $menuItemID ) {
			unset( $submenu[ $submenuName ][ $index ] );

			// No need to continue searching
			return;
		}
	}
}

/**
 * Shows the question pool page.
 *
 * @since 1.0.0
 */
function WPCW_showPage_QuestionPool() {
	WPCW_showPage_QuestionPool_load();
}

/**
 * Shows the quiz summary page.
 *
 * @since 1.0.0
 */
function WPCW_showPage_QuizSummary() {
	WPCW_showPage_QuizSummary_load();
}

/**
 * Function that allows a quiz to be created or edited.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ModifyQuiz() {
	WPCW_showPage_ModifyQuiz_load();
}

/**
 * Function that allows a quiz to be created or edited.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ModifyQuestion() {
	WPCW_showPage_ModifyQuestion_load();
}

/**
 * Function that allows a module to be created or edited.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ModifyModule() {
	WPCW_showPage_ModifyModule_load();
}

/**
 * Shows the page to do with importing/exporting training courses.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ImportExport() {
	WPCW_showPage_ImportExport_load();
}

/**
 * Shows the documentation page for the plugin.
 *
 * @since 1.0.0
 */
function WPCW_showPage_GradeBook() {
	WPCW_showPage_GradeBook_load();
}

/**
 * Page where the modules of a course can be ordered.
 *
 * @since 1.0.0
 */
function WPCW_showPage_CourseOrdering() {
	WPCW_showPage_CourseOrdering_load();
}

/**
 * Function that show a summary of the training courses.
 *
 * @since 1.0.0
 */
function WPCW_showPage_Dashboard() {
	WPCW_showPage_Dashboard_load();
}

/**
 * Function that allows a course to be created or edited.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ModifyCourse() {
	WPCW_showPage_ModifyCourse_load();
}

/**
 * Shows the settings page for the plugin.
 *
 * @since 1.0.0
 */
function WPCW_showPage_Settings_Network() {
	WPCW_showPage_Settings_Network_load();
}

/**
 * Shows a detailed summary of the user progress.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess() {
	WPCW_showPage_UserProgess_load();
}

/**
 * Shows a detailed summary of the user's quiz or survey answers.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserProgess_quizAnswers() {
	WPCW_showPage_UserProgess_quizAnswers_load();
}

/**
 * Page where the site owner can choose which courses a user is allowed to access.
 *
 * @since 1.0.0
 */
function WPCW_showPage_UserCourseAccess() {
	WPCW_showPage_UserCourseAccess_load();
}

/**
 * Convert page/post to a course unit.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ConvertPage() {
	WPCW_showPage_ConvertPage_load();
}

/**
 * Handle saving questions to the database.
 *
 * @since 1.0.0
 *
 * @param integer $quizID The quiz for which the questions apply to.
 * @param boolean $singleQuestionMode If true, then we're updating a single question, and we do things slightly differently.
 */
function WPCW_handler_questions_processSave( $quizID, $singleQuestionMode = false ) {
	global $wpdb, $wpcwdb;

	$wpdb->show_errors();

	$questionsToSave     = array();
	$questionsToSave_New = array();

	// Check $_POST data for the
	foreach ( $_POST as $key => $value ) {
		// 1 - Check if we're deleting a question from this quiz
		// We're not just deleting the question, just the association. This is because questions remain in the
		// pool now.
		if ( preg_match( '/^delete_wpcw_quiz_details_([0-9]+)$/', $key, $matches ) ) {
			// Remove mapping from the mapping table.
			$SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->quiz_qs_mapping
				WHERE question_id = %d
				  AND parent_quiz_id = %d
			", $matches[1], $quizID );

			$wpdb->query( $SQL );

			// Update usage counts
			WPCW_questions_updateUsageCount( $matches[1] );

			// Just a deletion - move on to next array item to save processing time.
			continue;
		}

		// 2 - See if we have a question to check for.
		if ( preg_match( '/^question_question_(new_question_)?([0-9]+)$/', $key, $matches ) ) {
			// Got the ID of the question, now get answers and correct answer.
			$questionID = $matches[2];

			// Store the extra string if we're adding a new question.
			$newQuestionPrefix = $matches[1];

			$fieldName_Answers     = 'question_answer_' . $newQuestionPrefix . $questionID;
			$fieldName_Answers_Img = 'question_answer_image_' . $newQuestionPrefix . $questionID;
			$fieldName_Correct     = 'question_answer_sel_' . $newQuestionPrefix . $questionID;
			$fieldName_Type        = 'question_type_' . $newQuestionPrefix . $questionID;
			$fieldName_Order       = 'question_order_' . $newQuestionPrefix . $questionID;
			$fieldName_AnswerType  = 'question_answer_type_' . $newQuestionPrefix . $questionID;
			$fieldName_AnswerHint  = 'question_answer_hint_' . $newQuestionPrefix . $questionID;
			$fieldName_Explanation = 'question_answer_explanation_' . $newQuestionPrefix . $questionID;
			$fieldName_Image       = 'question_image_' . $newQuestionPrefix . $questionID;
			$fieldName_Author      = 'question_author_' . $newQuestionPrefix . $questionID;

			// For Multi-Choice - Answer randomization
			$fieldName_Multi_Random_Enable     = 'question_multi_random_enable_' . $newQuestionPrefix . $questionID;
			$fieldName_Multi_Random_Count      = 'question_multi_random_count_' . $newQuestionPrefix . $questionID;
			$fieldname_Multi_Checkboxes_Enable = 'question_multi_checkboxes_enable_' . $newQuestionPrefix . $questionID;

			// Order should be a number
			$questionOrder = 0;
			if ( isset( $_POST[ $fieldName_Order ] ) ) {
				$questionOrder = absint( $_POST[ $fieldName_Order ] );
			}

			// Default types
			$qAns          = false;
			$qAnsCor       = false;
			$qAnsType      = false; // Just used for open question types.
			$qAnsFileTypes = false; // Just used for upload file types.

			// Get the hint - Just used for open and upload types. Allow HTML.
			$qAnsHint = trim( WPCW_arrays_getValue( $_POST, $fieldName_AnswerHint ) );

			// Get the explanation - All questions. Allow HTML.
			$qAnsExplain = trim( WPCW_arrays_getValue( $_POST, $fieldName_Explanation ) );

			// The image URL to use. No HTML. Table record is 300 chars, hence cropping.
			$qQuesImage = trim( substr( strip_tags( WPCW_arrays_getValue( $_POST, $fieldName_Image ) ), 0, 300 ) );

			// The Question Author
			$qQuesAuthor = trim( WPCW_arrays_getValue( $_POST, $fieldName_Author ) );

			// How many questions are there is this selection? 1 by default for non-random questions.
			$expandedQuestionCount = 1;

			// For Multi-Choice - Answer randomization
			$qMultiRandomEnable     = false;
			$qMultiRandomCount      = 5;
			$qMultiCheckboxesEnable = false;

			// What type of question do we have?
			$questionType = WPCW_arrays_getValue( $_POST, $fieldName_Type );
			switch ( $questionType ) {
				case 'multi':
					$qAns    = WPCW_quiz_MultipleChoice::editSave_extractAnswerList( $fieldName_Answers, $fieldName_Answers_Img );
					$qAnsCor = WPCW_quiz_MultipleChoice::editSave_extractCorrectAnswer( $qAns, $fieldName_Correct );

					// Provide the UI with at least once slot for an answer.
					if ( ! $qAns ) {
						$qAns = array( '1' => array( 'answer' => '' ), '2' => array( 'answer' => '' ) );
					}

					// Check randomization values (boolean will be 'on' to enable, as it's a checkbox)
					$qMultiRandomEnable     = 'on' == WPCW_arrays_getValue( $_POST, $fieldName_Multi_Random_Enable );
					$qMultiRandomCount      = intval( WPCW_arrays_getValue( $_POST, $fieldName_Multi_Random_Count ) );
					$qMultiCheckboxesEnable = 'on' == WPCW_arrays_getValue( $_POST, $fieldname_Multi_Checkboxes_Enable );
					break;

				case 'open':
					// See if there's a question type that's been sent back to the server.
					$answerTypes    = WPCW_quiz_OpenEntry::getValidAnswerTypes();
					$thisAnswerType = WPCW_arrays_getValue( $_POST, $fieldName_AnswerType );

					// Validate the answer type is in the list. Don't create a default so that user must choose.
					if ( isset( $answerTypes[ $thisAnswerType ] ) ) {
						$qAnsType = $thisAnswerType;
					}

					// There's no correct answer for an open question.
					$qAnsCor = false;
					break;

				case 'upload':
					$fieldName_FileType = 'question_answer_file_types_' . $newQuestionPrefix . $questionID;

					// Check new file extension types, parsing them.
					$qAnsFileTypesRaw = WPCW_files_cleanFileExtensionList( WPCW_arrays_getValue( $_POST, $fieldName_FileType ) );
					$qAnsFileTypes    = implode( ',', $qAnsFileTypesRaw );
					break;

				case 'truefalse':
					$qAnsCor = WPCW_quiz_TrueFalse::editSave_extractCorrectAnswer( $fieldName_Correct );
					break;

				// Validate the the JSON data here... ensure all the tags are valid (not worried about the counts).
				// Then save back to database.
				case 'random_selection':
					// Reset to zero for counting below.
					$expandedQuestionCount = 0;

					$decodedTags = WPCW_quiz_RandomSelection::decodeTagSelection( stripslashes( $value ) );

					// Capture just ID and count and resave back to database.
					$toSaveList = false;
					if ( ! empty( $decodedTags ) ) {
						$toSaveList = array();
						foreach ( $decodedTags as $decodedKey => $decodedDetails ) {
							$toSaveList[ $decodedKey ] = $decodedDetails['count'];

							// Track requested questions
							$expandedQuestionCount += $decodedDetails['count'];
						}
					}

					// Overwrite $value to use cleaned question
					$value = json_encode( $toSaveList );
					break;

				// Not expecting anything here... so not handling the error case.
				default:
					break;
			}

			// 4a - Encode the answer data
			$encodedqAns = $qAns;
			if ( ! empty( $qAns ) ) {
				foreach ( $encodedqAns as $idx => $data ) {
					$encodedqAns[ $idx ]['answer'] = base64_encode( $data['answer'] );
				}
			}

			// 4b - Save new question data as a list ready for saving to the database.
			$quDataToSave = array(
				'question_answers'                 => false, // Not needed, legacy column.
				'question_question'                => stripslashes( $value ),    // Clean up each answer if slashes used for escape characters.
				'question_data_answers'            => serialize( $encodedqAns ), // Answers need to be serialised.
				'question_correct_answer'          => $qAnsCor,
				'question_type'                    => $questionType,
				'question_order'                   => $questionOrder,
				'question_answer_type'             => $qAnsType,
				'question_answer_hint'             => stripslashes( $qAnsHint ),
				'question_answer_explanation'      => stripslashes( $qAnsExplain ),
				'question_answer_file_types'       => $qAnsFileTypes,
				'question_image'                   => $qQuesImage,
				'question_expanded_count'          => $expandedQuestionCount,
				'question_author'                  => $qQuesAuthor,

				// Multi only
				'question_multi_random_enable'     => $qMultiRandomEnable,
				'question_multi_random_count'      => $qMultiRandomCount,
				'question_multi_checkboxes_enable' => $qMultiCheckboxesEnable,

				// Default placeholder of tags to save - if any.
				'taglist'                          => array(),
			);

			// 5 - Check if there are any tags to save. Only happens for questions that
			// haven't been saved, so that we can save when we do a $_POST save.
			$tagFieldForNewQuestions = 'tags_to_add_' . $newQuestionPrefix . $questionID;
			if ( isset( $_POST[ $tagFieldForNewQuestions ] ) ) {
				if ( ! empty( $_POST[ $tagFieldForNewQuestions ] ) ) {
					// Validate each tag ID we have, add to list to be stored for this question later.
					foreach ( $_POST[ $tagFieldForNewQuestions ] as $idx => $tagText ) {
						$tagText = trim( stripslashes( $tagText ) );
						if ( $tagText ) {
							$quDataToSave['taglist'][] = $tagText;
						}
					}
				}
			}

			// Not a new question - so not got question ID as yet
			if ( $newQuestionPrefix ) {
				$questionsToSave_New[] = $quDataToSave;
			} else {
				$quDataToSave['question_id']    = $questionID;
				$questionsToSave[ $questionID ] = $quDataToSave;
			}
		}
	}

	// Only need to adjust quiz settings when editing a quiz and not a single question.
	if ( ! $singleQuestionMode ) {
		// 6 - Remove association of all questions for this quiz as we're going to re-add them.
		$wpdb->query( $wpdb->prepare( "
					DELETE FROM $wpcwdb->quiz_qs_mapping
					WHERE parent_quiz_id = %d
				", $quizID ) );
	}

	// 7 - Check we have existing questions to save
	if ( count( $questionsToSave ) ) {
		// Now save all data back to the database.
		foreach ( $questionsToSave as $questionID => $questionDetails ) {
			// Extract the question order, as can't save order with question in DB
			$questionOrder = $questionDetails['question_order'];
			unset( $questionDetails['question_order'] );

			// Tag list only used for new questions, so remove this field
			unset( $questionDetails['taglist'] );

			// Save question details back to database.
			$wpdb->query( arrayToSQLUpdate( $wpcwdb->quiz_qs, $questionDetails, 'question_id' ) );

			// No need to update counts/associations when editing a single lone question
			if ( ! $singleQuestionMode ) {
				// Create the association for this quiz/question.
				$wpdb->query( $wpdb->prepare( "
					INSERT INTO $wpcwdb->quiz_qs_mapping
					(question_id, parent_quiz_id, question_order)
					VALUES (%d, %d, %d)
				", $questionID, $quizID, $questionOrder ) );

				// Update usage count for question.
				WPCW_questions_updateUsageCount( $questionID );
			}
		}
	}

	// 8 - Save the new questions we have
	if ( count( $questionsToSave_New ) ) {
		// Now save all data back to the database.
		foreach ( $questionsToSave_New as $questionDetails ) {
			// Extract the question order, as can't save order with question in DB
			$questionOrder = $questionDetails['question_order'];
			unset( $questionDetails['question_order'] );

			// Extract the tags added for this question - we'll save manually.
			$tagsToAddList = $questionDetails['taglist'];
			unset( $questionDetails['taglist'] );

			// Create question in database
			$wpdb->query( arrayToSQLInsert( $wpcwdb->quiz_qs, $questionDetails ) );
			$newQuestionID = $wpdb->insert_id;

			// No need to update counts/associations when editing a single lone question
			if ( ! $singleQuestionMode ) {
				// Create the association for this quiz/question.
				$wpdb->query( $wpdb->prepare( "
					INSERT INTO $wpcwdb->quiz_qs_mapping
					(question_id, parent_quiz_id, question_order)
					VALUES (%d, %d, %d)
				", $newQuestionID, $quizID, $questionOrder ) );

				// Update usage
				WPCW_questions_updateUsageCount( $newQuestionID );
			}

			// Add associations for tags for this unsaved question now we finally have a question ID.
			if ( ! empty( $tagsToAddList ) ) {
				WPCW_questions_tags_addTags( $newQuestionID, $tagsToAddList );
			}

			if ( $singleQuestionMode ) {
				return $newQuestionID;
			}
		}
	}
}

/**
 * Handle saving questions to the database.
 *
 * @since 1.0.0
 *
 * @param array $questionData The question data that we wish to save.
 *
 * @return boolean If true, then it was successfully updated.
 */
function WPCW_handler_Save_Question( $questionData = array() ) {
	if ( empty ( $questionData ) ) {
		return false;
	}

	// Global
	global $wpdb, $wpcwdb;

	// Array Details
	$defaultQuestionData = array(
		'question_id'                      => '',
		'question_type'                    => '',
		'question_question'                => '',
		'question_answers'                 => '',
		'question_data_answers'            => '',
		'question_correct_answer'          => '',
		'question_answer_type'             => '',
		'question_answer_hint'             => '',
		'question_answer_explanation'      => '',
		'question_image'                   => '',
		'question_answer_file_types'       => '',
		'question_usage_count'             => '',
		'question_expanded_count'          => '',
		'question_multi_random_enable'     => '',
		'question_multi_random_count'      => '',
		'question_multi_checkboxes_enable' => '',
	);

	// Merge
	$questionData = wp_parse_args( $questionData, $defaultQuestionData );

	// Question Data
	$wpdb->query( arrayToSQLInsert( $wpcwdb->quiz_qs, $questionData ) );

	// Get Question Id
	$questionId = $wpdb->insert_id;

	// Return
	return $questionId;
}

/**
 * Show standard support information.
 *
 * @since 1.0.0
 *
 * @param object $page A reference to the page object showing information.
 */
function WPCW_docs_showSupportInfo( $page ) {
	$page->openPane( 'wpcw-docs-support', __( 'Need help?', 'wp-courseware' ) );

	echo '<p>' . __( "If you need assistance with WP Courseware, please visit the <a href='admin.php?page=WPCW_showPage_Documentation'>documentation section</a> first. We have lots of technical articles on our <a href='http://support.wpcourseware.com'>support docs site</a>. If you would like to submit a support request, please login to the <a href='http://flyplugins.com/member-portal/'>Member Portal</a> and click on the support tab.", 'wp-courseware' ) . '</p>';

	$page->closePane();
}

/**
 * Show information on being an affiliate.
 *
 * @since 1.0.0
 *
 * @param Object $page A reference to the page object showing information.
 */
function WPCW_docs_showSupportInfo_Affiliate( $page ) {
	$page->openPane( 'wpcw-docs-affiliate', __( 'Want to become an affiliate?', 'wp-courseware' ) );

	echo '<p>' . __( "If you're interested in making money by promoting WP Courseware, please login to the <a href='http://flyplugins.com/member-portal/'>Member Portal</a> and click on the affiliates tab.", 'wp-courseware' ) . '</p>';

	$page->closePane();
}

/**
 * Show the latest news.
 *
 * @since 1.0.0
 *
 * @param object $page A reference to the page object showing information.
 */
function WPCW_docs_showSupportInfo_News( $page ) {
	$page->openPane( 'wpcw-docs-support-news', __( 'Latest news from FlyPlugins.com', 'wp-courseware' ) );

	$rss = fetch_feed( 'http://feeds.feedburner.com/FlyPlugins' );

	// Got items, so show the news
	if ( ! is_wp_error( $rss ) ) {
		$rss_items = $rss->get_items( 0, $rss->get_item_quantity( 2 ) );

		$content = '<ul>';
		if ( ! $rss_items ) {
			$content .= '<li class="fly">' . __( 'No news items, feed might be broken...', 'wp-courseware' ) . '</li>';
		} else {
			foreach ( $rss_items as $item ) {
				$url     = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls = null, 'display' ) );
				$content .= '<li class="fly">';
				$content .= '<a class="rsswidget" href="' . $url . '">' . esc_html( $item->get_title() ) . '</a> ';
				$content .= '</li>';
			}
		}
		$content .= '</ul>';
	}

	$content .= '<ul class="wpcw_connect">';
	$content .= '<li class="facebook"><a href="http://facebook.com/flyplugins">' . __( 'Like Fly Plugins on Facebook', 'wp-courseware' ) . '</a></li>';
	$content .= '<li class="twitter"><a href="http://twitter.com/flyplugins">' . __( 'Follow Fly Plugins on Twitter', 'wp-courseware' ) . '</a></li>';
	$content .= '<li class="youtube"><a href="http://www.youtube.com/flyplugins">' . __( 'Watch Fly Plugins on YouTube', 'wp-courseware' ) . '</a></li>';
	$content .= '</ul>';

	echo '<div class="wpcw_fly_support_news">' . $content . '</div>';

	$page->closePane();
}

/**
 * Translation strings to use with each form.
 *
 * @since 1.0.0
 *
 * @return array The translated strings.
 */
function WPCW_forms_getTranslationStrings() {
	return array(
		"Please fill in the required '%s' field." => __( "Please fill in the required '%s' field.", 'wp-courseware' ),
		"There's a problem with value for '%s'."  => __( "There's a problem with value for '%s'.", 'wp-courseware' ),
		'required'                                => __( 'required', 'wp-courseware' ),
	);
}

/**
 * Create a dropdown box using the list of values provided and select a value if $selected is specified.
 *
 * @since 1.0.0
 *
 * @param string $name The name of the drop down box.
 * @param string $values The values to use for the drop down box.
 * @param string $selected If specified, the value of the drop down box to mark as selected.
 * @param string $cssid The CSS ID of the drop down list.
 * @param string $cssclass The CSS class for the drop down list.
 *
 * @return string The HTML for the select box.
 */
function WPCW_forms_createDropdown( $name, $values, $selected, $cssid = false, $cssclass = false ) {
	if ( ! $values ) {
		return false;
	}

	$selectedhtml = 'selected="selected" ';

	// CSS Attributes
	$css_attrib = false;
	if ( $cssid ) {
		$css_attrib = "id=\"$cssid\" ";
	}
	if ( $cssclass ) {
		$css_attrib .= "class=\"$cssclass\" ";
	}

	$html = sprintf( '<select name="%s" %s>', $name, $css_attrib );

	foreach ( $values as $key => $details ) {
		// Handle value => array('label' => '', 'data' => '', 'data2' => '')
		if ( is_array( $details ) ) {
			// This adds extra HTML5 data.
			$html .= sprintf( '<option value="%s" data-content="%s" data-content-two="%s" %s>%s&nbsp;&nbsp;</option>',
				$key, $details['data'], $details['data2'], ( $key == $selected ? $selectedhtml : '' ), $details['label']
			);
		} // Handle value => data
		else {
			$html .= sprintf( '<option value="%s" %s>%s&nbsp;&nbsp;</option>', $key, ( $key == $selected ? $selectedhtml : '' ), $details );
		}
	}

	return $html . '</select>';
}

/**
 * Create a break bar for the forms as a tab, with a save button too.
 *
 * @since 1.0.0
 *
 * @return String The HTML for the section break.
 */
function WPCW_forms_createBreakHTML_tab() {
	$html = false;
	$html .= '<div class="wpcw_form_break_tab"></div>';

	return $html;
}

/**
 * Create a break bar for the forms, with a save button too.
 *
 * @since 1.0.0
 *
 * @param string $title The title for the section.
 * @param string $buttonText The text for the button on the break section.
 * @param string $extraCSSClass Any extra CSS for styling the break.
 *
 * @return string The HTML for the section break.
 */
function WPCW_forms_createBreakHTML( $title, $buttonText = false, $hideButton = false, $extraCSSClass = false ) {
	if ( ! $hideButton ) {
		$buttonText = __( 'Save ALL Settings', 'wp-courseware' );
	}

	$btnHTML = false;
	if ( $buttonText && ! $hideButton ) {
		$btnHTML = sprintf( '<input type="submit" value="%s" name="Submit" class="button-primary">', $buttonText );
	}

	return sprintf( '
		<div class="wpcw_form_break %s">
			%s
			<h3>%s</h3>
			<div class="wpcw_cleared">&nbsp;</div>
		</div>
	',
		$extraCSSClass,
		$btnHTML,
		$title );
}

/**
 * Function to get the details of a question (to ensure it exists).
 *
 * @since 1.0.0
 *
 * @param integer $questionID The ID of the question to get the details for.
 * @param boolean $getTagsToo If true, then get the list of these tags too.
 *
 * @return object The details of the question as an object.
 */
function WPCW_questions_getQuestionDetails( $questionID, $getTagsToo = false ) {
	if ( ! $questionID ) {
		return false;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "SELECT *
			FROM $wpcwdb->quiz_qs
			WHERE question_id = %d
			", $questionID );

	$obj = $wpdb->get_row( $SQL );

	// Also grab any tags that this question has.
	if ( $obj && $getTagsToo ) {
		$obj->tags = $wpdb->get_results( $wpdb->prepare( "
			SELECT qt.*
			FROM $wpcwdb->question_tag_mapping qtm
				LEFT JOIN $wpcwdb->question_tags qt ON qtm.tag_id = qt.question_tag_id
			WHERE question_id = %d
			ORDER BY question_tag_name ASC
		", $obj->question_id ) );
	}

	return $obj;
}

/**
 * Update a question to track how many quizzes are using this question.
 *
 * @since 1.0.0
 *
 * @param integer $questionID The ID of the question to update with the count.
 *
 * @return integer The number of quizzes using a question.
 */
function WPCW_questions_updateUsageCount( $questionID ) {
	if ( ! intval( $questionID ) ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Update tag popularity
	$usageCountForQuestion = $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT(*)
		 FROM $wpcwdb->quiz_qs_mapping
		WHERE question_id = %d
	", $questionID ) );

	// Update the count in the tag field
	$wpdb->query( $wpdb->prepare( "
		UPDATE $wpcwdb->quiz_qs
		SET question_usage_count = %d
		WHERE question_id = %d
	", $usageCountForQuestion, $questionID ) );

	return $usageCountForQuestion;
}

/**
 * Update the popularity stats for a tag.
 *
 * @since 1.0.0
 *
 * @param integer $tagID The ID of the tag to update the popularity for.
 */
function WPCW_questions_tags_updatePopularity( $tagID ) {
	if ( ! intval( $tagID ) ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Update tag popularity
	$usageCountForTag = $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT(*)
		  FROM $wpcwdb->question_tag_mapping
		WHERE tag_id = %d
	", $tagID ) );

	// Update the count in the tag field
	$wpdb->query( $wpdb->prepare( "
		UPDATE $wpcwdb->question_tags
		SET question_tag_usage = %d
		WHERE question_tag_id = %d
	", $usageCountForTag, $tagID ) );
}

/**
 * Given the tag and question IDs, remove the association.
 *
 * @since 1.0.0
 *
 * @param integer $questionID The ID of the question to remove the tag from.
 * @param integer $tagID The ID of the tag to remove from the questio
 */
function WPCW_questions_tags_removeTag( $questionID, $tagID ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	if ( absint( $questionID ) === 0 ) {
		// Check that the tag exists first...
		$tagFound = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			  FROM $wpcwdb->question_tags
			  WHERE question_tag_id = %d
		", $tagID ) );

		if ( $tagFound ) {
			// Update the count in the tag field
			$wpdb->query( $wpdb->prepare( "
                UPDATE $wpcwdb->question_tags
                SET question_tag_usage = %d
                WHERE question_tag_id = %d
            ", 0, $tagID ) );
		}
	} else {
		// Check that the tag exists first...
		$tagFound = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			  FROM $wpcwdb->question_tag_mapping
			WHERE question_id = %d
			  AND tag_id = %d
		", $questionID, $tagID ) );

		if ( $tagFound ) {
			// Only remove if found...
			$wpdb->query( $wpdb->prepare( "
                DELETE FROM $wpcwdb->question_tag_mapping
                WHERE question_id = %d
                  AND tag_id = %d
            ", $questionID, $tagID ) );

			// Update tag usage count.
			WPCW_questions_tags_updatePopularity( $tagID );
		}
	}
}

/**
 * Given a list of tags, try to add them without adding them to a specific question.
 *
 * @since 1.0.0
 *
 * @param array $tagList The list of tags to add.
 *
 * @return array The list of tags to be rendered again.
 */
function WPCW_questions_tags_addTags_withoutQuestion( $tagList ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	$taglistToReturn = array();

	// Just get the IDs of tags (and add the missing ones)
	foreach ( $tagList as $tagToAdd ) {
		// Check permissions, so that users that are not administrators cannot use other peoples tags.
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$tagDetails = $wpdb->get_row( $wpdb->prepare( "
				SELECT question_tag_id
				  FROM $wpcwdb->question_tags
				WHERE question_tag_name = %s
				AND question_tag_author = %d
			", $tagToAdd, get_current_user_id() ) );
		} else {
			$tagDetails = $wpdb->get_row( $wpdb->prepare( "
				SELECT question_tag_id
				  FROM $wpcwdb->question_tags
				WHERE question_tag_name = %s
			", $tagToAdd ) );
		}

		// Got a tag already, so need the tag ID
		if ( $tagDetails ) {
			// Add to list that we're turning to AJAX.
			$taglistToReturn[ $tagDetails->question_tag_id ] = $tagToAdd;
		} // We need to insert the tag to the tag table.
		else {
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO $wpcwdb->question_tags
				(question_tag_name, question_tag_usage, question_tag_author) VALUES (%s, 1, %d)
				", $tagToAdd, get_current_user_id() ) );

			$taglistToReturn[ $wpdb->insert_id ] = $tagToAdd;
		}
	}

	return $taglistToReturn;
}

/**
 * Given a list of tags, try to add them to the specified question.
 *
 * @since 1.0.0
 *
 * @param integer $questionID The ID of the question that we're adding the tag for.
 * @param array $tagList The list of tags to add.
 *
 * @return array The list of tags to be rendered again.
 */
function WPCW_questions_tags_addTags( $questionID, $tagList ) {
	if ( empty( $tagList ) ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	$taglistToReturn = WPCW_questions_tags_addTags_withoutQuestion( $tagList );

	// Now we need to work through and associate each tag with the question
	foreach ( $taglistToReturn as $tagID => $tagText ) {
		$wpdb->query( $wpdb->prepare( "
			INSERT IGNORE INTO $wpcwdb->question_tag_mapping
			(question_id, tag_id)
			VALUES (%d, %d)
		", $questionID, $tagID ) );

		WPCW_questions_tags_updatePopularity( $tagID );
	}

	return $taglistToReturn;
}

/**
 * Given a list of tags, render them for admin control.
 *
 * @since 1.0.0
 *
 * @param integer $questionID The ID of the question that we're adding/removing the tag for.
 * @param array $tagList The list of tags to add (a list of tag objects.
 *
 * @return string The HTML for rendering the tags.
 */
function WPCW_questions_tags_render( $questionID, $tagList ) {
	$html = '<span class="wpcw_tag_list_wrapper tagchecklist">';

	// Nothing to do, but still return wrapper for adding via AJAX.
	if ( empty( $tagList ) ) {
		$html .= '</span>';

		return $html;
	}

	// Render list of tags
	foreach ( $tagList as $tagDetails ) {
		$html .= sprintf( '<span><a data-questionid="%d" data-tagid="%d" class="ntdelbutton">X</a>&nbsp;%s</span>',
			$questionID, $tagDetails->question_tag_id, stripslashes( $tagDetails->question_tag_name )
		);
	}

	$html .= '</span>';

	return $html;
}

/**
 * Shows a list of tags and a button to filter the question pool by tag.
 *
 * @since 1.0.0
 *
 * @param string $currentTag The current tag that has been selected.
 * @param string $pageForURL The name of the page to show this form on (where page=WPCW_showPage_QuestionPool)
 *
 * @return string The HTML to render the tag filtering code.
 */
function WPCW_questions_tags_createTagFilter( $currentTag, $pageForURL ) {
	$html = sprintf( '<div class="wpcw_questions_tag_filter_wrap"><form method="get" action="%s">', admin_url( 'admin.php?page=' . $pageForURL ) );

	// Page that this form is being shown on.
	$html .= sprintf( '<input type="hidden" name="page" value="%s" />', $pageForURL );

	// Select
	$html .= sprintf( '<label for="wpcw_questions_tag_filter">%s</label>', __( 'Filter By:', 'wp-courseware' ) );
	$html .= WPCW_questions_tags_getTagDropdown( 'filter', $currentTag, 'wpcw_questions_tag_filter', __( '-- View All Tags --', 'wp-courseware' ) );

	// CTA
	$html .= sprintf( '<input type="submit" class="button-secondary" value="%s" />', __( 'Filter', 'wp-courseware' ) );

	return $html . '</form></div>';
}

/**
 * Create a dropdown tag list.
 *
 * @since 1.0.0
 *
 * @param boolean $showBlank If true, add a blank to the start.
 * @param string $fieldName The name of the HTML field.
 * @param string $currentTag If specified, the current tag to mark as selected.
 * @param string $cssClassName The CSS name for the field.
 * @param boolean $showQuestionStr If true, show the string 'questions' after each tag.
 * @param boolean $showCountOfQuestions If true, then show the count of questions per tag.
 *
 * @return string The HTML for rendering the dropdown.
 */
function WPCW_questions_tags_getTagDropdown( $fieldName, $currentTag, $cssClassName, $showBlank = false, $showQuestionStr = false, $showCountOfQuestions = true ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$tagsToShow   = array();
	$current_user = wp_get_current_user();

	// Save the SQL query, used the cached variable if available.
	static $tagsToShow_cached;

	if ( ! $tagsToShow_cached ) {
		// Default Query
		// $TAG_SQL_QUERY = "SELECT * FROM $wpcwdb->question_tags WHERE question_tag_usage > 0 ORDER BY question_tag_name ASC";
		$TAG_SQL_QUERY = "SELECT * FROM $wpcwdb->question_tags ORDER BY question_tag_name ASC";

		// If a teacher or not admin
		if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
			$TAG_SQL_QUERY = $wpdb->prepare( "SELECT * FROM $wpcwdb->question_tags WHERE question_tag_author = %d ORDER BY question_tag_name ASC;", $current_user->ID );
		}

		$tagList = $wpdb->get_results( $TAG_SQL_QUERY );

		if ( ! empty( $tagList ) ) {
			foreach ( $tagList as $singleTag ) {
				// Create downdown with tag selection and number of questions that exist for that tag).
				// The HTML5 data tag is useful for setting the max on spinners.
				$tagsToShow[ $singleTag->question_tag_id ] = array(
					'label' => $singleTag->question_tag_name,
					'data'  => $singleTag->question_tag_usage,
					'data2' => $singleTag->question_tag_name,
				);

				// Add Label to those tags that are the same, but have different authors.
				if ( is_admin() && user_can( $current_user, 'manage_wpcw_settings' ) && $singleTag->question_tag_author != $current_user->ID ) {
					foreach ( $tagList as $checkTag ) {
						if ( $checkTag->question_tag_author == $current_user->ID && $checkTag->question_tag_name == $singleTag->question_tag_name ) {
							if ( $tagAuthor = get_user_by( 'id', $singleTag->question_tag_author ) ) {
								$tagsToShow[ $singleTag->question_tag_id ]['label'] .= sprintf( ' [%s] ', $tagAuthor->display_name );
							}
						}
					}
				}

				// Add count if requested.
				if ( $showCountOfQuestions ) {
					$tagsToShow[ $singleTag->question_tag_id ]['label'] .= ' (' . $singleTag->question_tag_usage . ( $showQuestionStr ? ' ' . __( 'Questions', 'wp-courseware' ) : '' ) . ')';
				}
			}
		}

		$tagsToShow_cached = $tagsToShow;
	} else {
		$tagsToShow = $tagsToShow_cached;
	}

	// Create the blank item to use, added to the front, but not cached.
	if ( $showBlank ) {
		$tagsToShow = array( '' => $showBlank ) + $tagsToShow;
	}

	// Save to static variable to save execution again in same page load.
	return WPCW_forms_createDropdown( $fieldName, $tagsToShow, $currentTag, false, $cssClassName );
}

/**
 * Shows a list of quesiton types for addition.
 *
 * @since 1.0.0
 *
 * @return string The HTML to render the action code.
 */
function WPCW_questions_questionType_Actions() {
	$question_types = apply_filters( 'wpcw_question_types', array(
		'multi'     => __( 'Multiple Choice', 'wp-courseware' ),
		'truefalse' => __( 'True / False', 'wp-courseware' ),
		'open'      => __( 'Open Ended', 'wp-courseware' ),
		'upload'    => __( 'File Upload', 'wp-courseware' ),
	) );

	$html = sprintf( '<div class="wpcw_questions_question_type_actions_wrap"><form method="POST" action="%s">', admin_url( 'admin.php?page=WPCW_showPage_ModifyQuestion' ) );

	// Select
	$html .= sprintf( '<label for="wpcw_questions_question_type_actions">%s</label>', __( 'Add Question:', 'wp-courseware' ) );
	$html .= '<select name="question_type" class="wpcw_question_type_action">';
	$html .= sprintf( '<option value="" selected="selected">%s</option>', __( 'Select Question Type', 'wp-courseware' ) );
	foreach ( $question_types as $question_id => $question_label ) {
		$html .= sprintf( '<option value="%s">%s</option>', $question_id, $question_label );
	}
	$html .= '</select>';

	// CTA
	$html .= sprintf( '<input type="submit" class="button-secondary" value="%s" />', __( 'Add', 'wp-courseware' ) );

	return $html . '</form></div>';
}

/**
 * Function to show a list of questions in the question pool for use by a standard page or the AJAX thickbox.
 *
 * @since 1.0.0
 *
 * @param integer $itemsPerPage The number of items to show on each table page.
 * @param array $paramSrc The array of parameters to use for filtering/searching the question pool.
 * @param string $actionMode The type of mode we're in (ajax or std).
 * @param PageBuilder $page The current page object (optional).
 */
function WPCW_questionPool_showPoolTable( $itemsPerPage, $paramSrc, $actionMode = 'std', $page = false ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();
	$user_id = get_current_user_id();

	// AJAX loader
	if ( 'ajax' == $actionMode ) {
		printf( '<img src="%sajax_loader.gif" class="wpcw_loader" style="display: none;" />', WPCW_IMG_URL );
	}

	// Check to see if we've got questions to process
	if ( 'std' == $actionMode ) {
		WPCW_showPage_QuestionPool_processActionForm( $page );
	}

	$paging_pageWanted = absint( WPCW_arrays_getValue( $paramSrc, 'pagenum' ) );
	if ( $paging_pageWanted == 0 ) {
		$paging_pageWanted = 1;
	}

	// Handle the sorting and filtering
	$orderBy  = WPCW_arrays_getValue( $paramSrc, 'orderby' );
	$ordering = WPCW_arrays_getValue( $paramSrc, 'order' );

	// Validate ordering
	switch ( $orderBy ) {
		case 'question_question':
		case 'question_type':
			break;
		default:
			$orderBy = 'qs.question_id';
			break;
	}

	// Create opposite ordering for reversing it.
	$ordering_opposite = false;
	switch ( $ordering ) {
		case 'desc':
			$ordering_opposite = 'asc';
			break;
		case 'asc':
			$ordering_opposite = 'desc';
			break;
		default:
			$ordering          = 'desc';
			$ordering_opposite = 'asc';
			break;
	}

	// Was a search string specified? Or a specific item?
	$searchString = esc_html( WPCW_arrays_getValue( $paramSrc, 's' ) );

	$summaryPageURL = admin_url( 'admin.php?page=WPCW_showPage_QuestionPool' );

	// Show the form for searching
	?>
	<form id="wpcw_questions_search_box" method="get" action="<?php echo $summaryPageURL; ?>">
		<p class="search-box">
			<label class="screen-reader-text" for="wpcw_questions_search_input"><?php _e( 'Search Questions', 'wp-courseware' ); ?></label>
			<input id="wpcw_questions_search_input" type="text" value="<?php echo $searchString ?>" name="s"/>
			<input class="button" type="submit" value="<?php _e( 'Search Questions', 'wp-courseware' ); ?>"/>
			<input type="hidden" name="page" value="WPCW_showPage_QuestionPool"/>
		</p>
	</form>
	<?php

	// Create WHERE string based search - Title or Description of Quiz
	$SQL_WHERE_CONDITIONS = array();
	$SQL_WHERE            = "WHERE question_type <> 'random_selection'";
	if ( $searchString ) {
		$SQL_WHERE_CONDITIONS[] = $wpdb->prepare( 'question_question LIKE %s', '%' . $searchString . '%' );
	}

	// Check if admin
	if ( ! user_can( $user_id, 'manage_wpcw_settings' ) ) {
		$SQL_WHERE_CONDITIONS[] = $wpdb->prepare( 'question_author = %d', $user_id );
	}

	$SQL_TAG_FILTER = false;
	$tagFilter      = intval( WPCW_arrays_getValue( $paramSrc, 'filter', false ) );

	// See if we have any tag filtering to do.
	if ( $tagFilter > 0 ) {
		// Ensure we add the tag mapping table to the query
		$SQL_TAG_FILTER = "
			LEFT JOIN $wpcwdb->question_tag_mapping qtm ON qtm.question_id = qs.question_id
		";

		$SQL_WHERE_CONDITIONS[] = $wpdb->prepare( "qtm.tag_id = %d", $tagFilter );
		$SQL_WHERE_CONDITIONS[] = 'qs.question_question IS NOT NULL';
	}

	// Build Where Query
	if ( is_array( $SQL_WHERE_CONDITIONS ) && ! empty( $SQL_WHERE_CONDITIONS ) ) {
		$SQL_WHERE       .= ' AND ';
		$counter         = 1;
		$condition_count = count( $SQL_WHERE_CONDITIONS );
		foreach ( $SQL_WHERE_CONDITIONS as $SQL_CONDITION ) {
			$SQL_WHERE .= ( $counter !== $condition_count ) ? $SQL_CONDITION . ' AND ' : $SQL_CONDITION;
			$counter ++;
		}
	}

	$SQL_PAGING = "
		SELECT COUNT(*) as question_count
		FROM $wpcwdb->quiz_qs qs
		$SQL_TAG_FILTER
		$SQL_WHERE
	";

	$paging_resultsPerPage = $itemsPerPage;
	$paging_totalCount     = $wpdb->get_var( $SQL_PAGING );
	$paging_recordStart    = ( ( $paging_pageWanted - 1 ) * $paging_resultsPerPage ) + 1;
	$paging_recordEnd      = ( $paging_pageWanted * $paging_resultsPerPage );
	$paging_pageCount      = ceil( $paging_totalCount / $paging_resultsPerPage );
	$paging_sqlStart       = $paging_recordStart - 1;

	// Show search message - that a search has been tried.
	if ( $searchString ) {
		printf( '<div class="wpcw_search_count">%s "%s" (%s %s) (<a href="%s">%s</a>)</div>',
			__( 'Search results for', 'wp-courseware' ),
			htmlentities( $searchString ),
			$paging_totalCount,
			_n( 'result', 'results', $paging_totalCount, 'wp-courseware' ),
			$summaryPageURL,
			__( 'reset', 'wp-courseware' )
		);
	}

	// Do main query
	$SQL = "SELECT *
			FROM $wpcwdb->quiz_qs qs
			$SQL_TAG_FILTER
			$SQL_WHERE
			ORDER BY $orderBy $ordering
			LIMIT $paging_sqlStart, $paging_resultsPerPage
			"; // These are already checked, so they are safe, hence no prepare()

	// Generate paging code
	$baseURL   = WPCW_urls_getURLWithParams( $summaryPageURL ) . "&pagenum=";
	$questions = $wpdb->get_results( $SQL );

	$tbl             = new TableBuilder();
	$tbl->attributes = array(
		'id'    => 'wpcw_tbl_question_pool',
		'class' => 'widefat wpcw_tbl',
	);

	// Wanting sorting links... in standard mode
	if ( 'std' == $actionMode ) {
		// Checkbox field (no name, as we'll use jQuery to do a check all)
		$tblCol              = new TableColumn( '<input type="checkbox" />', 'question_id_cb' );
		$tblCol->cellClass   = "wpcw_center wpcw_select_cb";
		$tblCol->headerClass = "wpcw_center wpcw_select_cb";
		$tbl->addColumn( $tblCol );

		// ID - sortable
		$sortableLink = sprintf( '<a href="%s&order=%s&orderby=question_id"><span>%s</span><span class="sorting-indicator"></span></a>',
			$baseURL,
			( 'question_id' == $orderBy ? $ordering_opposite : 'asc' ),
			__( 'ID', 'wp-courseware' )
		);

		// ID - render
		$tblCol              = new TableColumn( $sortableLink, 'question_id' );
		$tblCol->headerClass = ( 'question_id' == $orderBy ? 'sorted ' . $ordering : 'sortable' );
		$tblCol->cellClass   = "question_id";
		$tbl->addColumn( $tblCol );

		// Question - sortable
		$sortableLink = sprintf( '<a href="%s&order=%s&orderby=question_question"><span>%s</span><span class="sorting-indicator"></span></a>',
			$baseURL,
			( 'question_question' == $orderBy ? $ordering_opposite : 'asc' ),
			__( 'Question', 'wp-courseware' )
		);

		// Question - render
		$tblCol              = new TableColumn( $sortableLink, 'question_question' );
		$tblCol->headerClass = ( 'question_question' == $orderBy ? 'sorted ' . $ordering : 'sortable' );
		$tblCol->cellClass   = "question_question";
		$tbl->addColumn( $tblCol );

		// Question Type - sortable
		$sortableLink = sprintf( '<a href="%s&order=%s&orderby=question_type"><span>%s</span><span class="sorting-indicator"></span></a>',
			$baseURL,
			( 'question_type' == $orderBy ? $ordering_opposite : 'asc' ),
			__( 'Question Type', 'wp-courseware' )
		);

		// Question Type - render
		$tblCol              = new TableColumn( $sortableLink, 'question_type' );
		$tblCol->headerClass = ( 'question_type' == $orderBy ? 'sorted ' . $ordering : 'sortable' ) . ' wpcw_center';
		$tblCol->cellClass   = "question_type";
		$tbl->addColumn( $tblCol );
	} else {
		$tblCol            = new TableColumn( __( 'ID', 'wp-courseware' ), 'question_id' );
		$tblCol->cellClass = "question_id";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Question', 'wp-courseware' ), 'question_question' );
		$tblCol->cellClass = "question_question";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Question Type', 'wp-courseware' ), 'question_type' );
		$tblCol->cellClass = "question_type";
		$tbl->addColumn( $tblCol );
	}

	$tblCol              = new TableColumn( __( 'Associated Quizzes', 'wp-courseware' ), 'associated_quizzes' );
	$tblCol->headerClass = "wpcw_center";
	$tblCol->cellClass   = "associated_quizzes wpcw_center";
	$tbl->addColumn( $tblCol );

	$tblCol              = new TableColumn( __( 'Tags', 'wp-courseware' ), 'question_tags' );
	$tblCol->headerClass = "wpcw_center";
	$tblCol->cellClass   = "question_tags wpcw_center";
	$tbl->addColumn( $tblCol );

	// Actions
	$tblCol              = new TableColumn( __( 'Actions', 'wp-courseware' ), 'actions' );
	$tblCol->cellClass   = "actions actions_right";
	$tblCol->headerClass = "actions_right";
	$tbl->addColumn( $tblCol );

	// Stores course details in a mini cache to save lots of MySQL lookups.
	$miniCourseDetailCache = array();

	// Format row data and show it.
	if ( $questions ) {
		$odd = false;
		foreach ( $questions as $singleQuestion ) {
			$data = array();

			// URLs
			$editURL = admin_url( 'admin.php?page=WPCW_showPage_ModifyQuestion&question_id=' . $singleQuestion->question_id );

			// Maintain paging where possible.
			$deleteURL = $baseURL . '&action=delete&question_id=' . $singleQuestion->question_id;

			// Basic Details
			$data['question_id']    = $singleQuestion->question_id;
			$data['question_type']  = WPCW_quizzes_getQuestionTypeName( $singleQuestion->question_type );
			$data['question_id_cb'] = sprintf( '<input type="checkbox" name="question_%d" />', $singleQuestion->question_id );

			// Association Count
			$data['associated_quizzes'] = $singleQuestion->question_usage_count;

			// Actions - Std mode
			if ( 'std' == $actionMode ) {
				// Edit by clicking
				$data['question_question'] = sprintf( '<a href="%s">%s</a>', $editURL, $singleQuestion->question_question );

				$data['actions'] = '<ul class="wpcw_action_link_list">';

				$data['actions'] .= sprintf( '<li><a href="%s" class="button-primary">%s</a></li>', $editURL, __( 'Edit', 'wp-courseware' ) );
				$data['actions'] .= sprintf( '<li><a href="%s" class="button-secondary wpcw_action_link_delete_question wpcw_action_link_delete" rel="%s">%s</a></li>',
					$deleteURL,
					__( 'Are you sure you wish to delete this question? This cannot be undone.', 'wp-courseware' ),
					__( 'Delete', 'wp-courseware' ) );

				$data['actions'] .= '</ul>';
			} elseif ( 'ajax' == $actionMode ) {
				// No Edit by clicking
				$data['question_question'] = $singleQuestion->question_question . sprintf( '<span class="wpcw_action_status wpcw_action_status_added">%s</span>', __( 'Added', 'wp-courseware' ) );

				$data['actions'] = '<ul class="wpcw_action_link_list">';
				$data['actions'] .= sprintf( '<li><a href="#" class="button-primary wpcw_tb_action_add" data-questionnum="%d">%s</a></li>',
					$singleQuestion->question_id,
					__( 'Add To Quiz', 'wp-courseware' )
				);
				$data['actions'] .= '</ul>';
			}

			// Tags
			$data['question_tags'] = sprintf( '<span class="wpcw_quiz_details_question_tags" data-questionid="%d" id="wpcw_quiz_details_question_tags_%d">',
				$singleQuestion->question_id,
				$singleQuestion->question_id
			);
			$data['question_tags'] .= WPCW_questions_tags_render( $singleQuestion->question_id, WPCW_questions_tags_getTagsForQuestion( $singleQuestion->question_id ) );
			$data['question_tags'] .= '</span>';

			// Odd/Even row colouring.
			$odd = ! $odd;
			$tbl->addRow( $data, ( $odd ? 'alternate' : '' ) );
		}
	} else {
		// No questions - show error in table.
		$tbl->addRowObj( new RowDataSimple( 'wpcw_center wpcw_none_found', __( 'There are currently no questions to show.', 'wp-courseware' ), 7 ) );
	}

	// Add the form for the start of the multiple-add
	$formWrapper_start = false;
	if ( 'std' == $actionMode ) {
		// Set the action URL to preserve parameters that we have.
		$formWrapper_start = sprintf( '<form method="POST" action="%s">', WPCW_urls_getURLWithParams( $summaryPageURL, 'pagenum' ) );
	}

	// Create tag filter (uses a form)
	$tagFilter = WPCW_questions_tags_createTagFilter( $tagFilter, 'WPCW_showPage_QuestionPool' );

	// Question Action
	if ( $actionMode !== 'ajax' ) {
		$questionActions = WPCW_questions_questionType_Actions();
	} else {
		$questionActions = '';
	}

	// Work out paging and filtering
	$paging = WPCW_tables_showPagination( $baseURL, $paging_pageWanted, $paging_pageCount, $paging_totalCount, $paging_recordStart, $paging_recordEnd, $tagFilter, $questionActions );

	// Show the actions
	$formWrapper_end = false;
	if ( 'std' == $actionMode ) {
		$formWrapper_end = WPCW_showPage_QuestionPool_actionForm();

		// Form tag - needed for processing
		$formWrapper_end .= '</form>';
	}

	// Finally show table
	return $paging . $questionActions . $formWrapper_start . $tbl->toString() . $formWrapper_end . $paging;
}

/**
 * Update the user summary columns to show our custom fields, and hide cluttering ones.
 *
 * @since 1.0.0
 *
 * @param Array $column_headers The list of columns to show (before showing them).
 *
 * @return Array The actual list of columns to show.
 */
function WPCW_users_manageColumns( $column_headers ) {
	// Remove list of posts
	unset( $column_headers['posts'] );

	// Remove name and email address (so that we can combine it)
	unset( $column_headers['name'] );
	unset( $column_headers['email'] );
	unset( $column_headers['role'] );

	// Add new name column
	$column_headers['wpcw_col_user_details'] = __( 'Details', 'wp-courseware' );

	// Training Course Allocations
	$column_headers['wpcw_col_training_courses']        = __( 'Training Course Progress', 'wp-courseware' );
	$column_headers['wpcw_col_training_courses_access'] = __( 'Actions', 'wp-courseware' );

	return $column_headers;
}

/**
 * Creates the column columns of data.
 *
 * @since 1.0.0
 *
 * @param string $colContent The content of the column.
 * @param string $column_name The name of the column we're changing.
 * @param integer $user_id The ID of the user we're rendering.
 *
 * @return string The formatted HTML code for the table.
 */
function WPCW_users_addCustomColumnContent( $colContent, $column_name, $user_id ) {
	switch ( $column_name ) {
		// Basically condense user details.
		case 'wpcw_col_user_details':
			global $wp_roles;
			// Format nice details of name, email and role to save space.
			$userDetails = get_userdata( $user_id );

			// Ensure role is valid and it exists.
			$roleName = false;
			if ( ! empty( $userDetails->roles ) ) {
				$roleNameId = $userDetails->roles[0];
				$roleName   = $wp_roles->roles[ $roleNameId ]['name'];
			}

			$colContent = sprintf( '<span class="wpcw_col_cell_name">%s</span>', $userDetails->data->display_name );
			$colContent .= sprintf( '<span class="wpcw_col_cell_email"><a href="mailto:%s" target="_blank">%s</a></span>', $userDetails->data->user_email, $userDetails->data->user_email );
			$colContent .= sprintf( '<span class="wpcw_col_cell_role">%s</span>', ucwords( $roleName ) );
			break;

		// The training course statuses.
		case 'wpcw_col_training_courses':
			// Got some associated courses, so render progress.
			$courseData = WPCW_users_getUserCourseListAdmin( $user_id );
			if ( $courseData ) {
				foreach ( $courseData as $courseDataItem ) {
					$colContent .= WPCW_stats_convertPercentageToBar( $courseDataItem->course_progress, $courseDataItem->course_title );
				}
			} // No courses
			else {
				$colContent = __( 'No associated courses', 'wp-courseware' );
			}
			break;

		// Links to change user access for courses.
		case 'wpcw_col_training_courses_access':
			$colContent = sprintf( '<span><a href="%s&user_id=%d" class="button-primary">%s</a></span>',
				admin_url( 'admin.php?page=WPCW_showPage_UserProgess' ),
				$user_id,
				__( 'View Detailed Progress', 'wp-courseware' )
			);

			// View the full progress of the user.
			$colContent .= sprintf( '<span><a href="%s&user_id=%d" class="button-secondary">%s</a></span>',
				admin_url( 'admin.php?page=WPCW_showPage_UserCourseAccess' ),
				$user_id,
				__( 'Update Course Access Permissions', 'wp-courseware' )
			);

			// Allow the user progress to be reset
			$courseData   = WPCW_users_getUserCourseListAdmin( $user_id );
			$courseIDList = array();
			if ( ! empty( $courseData ) ) {
				// Construct a simple list of IDs that we can use for filtering.
				foreach ( $courseData as $courseDetails ) {
					$courseIDList[] = $courseDetails->course_id;
				}
			}

			// Construct the mini form for resetting the user progress.
			$colContent .= '<span>';

			$colContent .= '<form method="get">';

			// Using this method of the user ID automaticallyed added the first user to any bulk action, which is clearly a bug.
			// So the field had to be renamed.
			//$colContent .= sprintf('<input type="hidden" name="users[]" value="%d" >', $user_id);
			$colContent .= sprintf( '<input type="hidden" name="wpcw_users_single" value="%d" >', $user_id );

			// The dropdown for this.
			$colContent .= WPCW_courses_getCourseResetDropdown(
				'wpcw_user_progress_reset_point_single',
				__( 'No associated courses.', 'wp-courseware' ),
				__( 'Reset this user to beginning of...', 'wp-courseware' ),
				'',
				'wpcw_user_progress_reset_select wpcw_user_progress_reset_point_single',
				$courseIDList
			);

			$colContent .= '</form>';
			$colContent .= '</span>';
			break;
	}

	return $colContent;
}

/**
 * Creates the dropdown form and button that allows the bulk-reset of users on their respective courses.
 *
 * @since 1.0.0
 *
 * @return string
 */
function WPCW_users_showUserResetAbility() {
	global $wpcw_bulk_reset_index;

	// We don't handle multiple instances so don't render another.
	if ( ! isset( $wpcw_bulk_reset_index ) ) {
		$wpcw_bulk_reset_index = 0;
	} else {
		return '';
	}

	$html = '<div class="wpcw_user_bulk_progress_reset">';
	$html .= WPCW_courses_getCourseResetDropdown(
		'wpcw_user_progress_reset_point_bulk',
		__( 'No courses yet.', 'wp-courseware' ),
		__( 'Reset User Progress to beginning of...', 'wp-courseware' ),
		'wpcw_user_progress_reset_point_bulk',
		'wpcw_user_progress_reset_select',
		false
	);
	$html .= sprintf( '<input id="wpcw_user_progress_reset_point_bulk_btn" name="wpcw_user_bulk_progress_reset" type="submit" class="button" value="Reset">' );
	$html .= '</div>';

	echo $html;
}

/**
 * Shows reset success message in right place in HTML to not trigger errors.
 *
 * @since 1.0.0
 */
function WPCW_users_processUserResetAbility_showSuccess() {
	if ( isset( $_GET['wpcw_reset'] ) ) {
		wpcw_add_admin_notice_success( esc_html__( 'Student(s) progess has been reset.', 'wp-courseware' ) );
		wp_safe_redirect( remove_query_arg( 'wpcw_reset', $_SERVER['REQUEST_URI'] ) );
		exit;
	}
	if ( isset( $_GET['wpcw_reset_classroom'] ) ) {
		wpcw_add_admin_notice_success( esc_html__( 'Course classroom has been reset.', 'wp-courseware' ) );
		wp_safe_redirect( remove_query_arg( 'wpcw_reset_classroom', $_SERVER['REQUEST_URI'] ) );
		exit;
	}
}

/**
 * This function filters the users by course and author id.
 *
 * Currently this does not work properly.
 *
 * @since 1.0.0
 *
 * @param object $query The WP_User_Query object for modification.
 */
function WPCW_users_permissionUsersFilter( $query ) {
	// Check
	if ( ! is_admin() ) {
		return;
	}

	// Globals
	global $wpdb, $wpcwdb;

	// Vars
	$current_user = wp_get_current_user();

	// If admin, return.
	if ( user_can( $current_user, 'manage_wpcw_settings' ) ) {
		return;
	}

	// Users array
	$search_users = array();

	// Get users of course this author has created.
	$course_users = $wpdb->get_results( $wpdb->prepare( "
    	SELECT *
    	FROM $wpcwdb->user_courses uc
    	LEFT JOIN  $wpcwdb->courses c ON c.course_id = uc.course_id
   		WHERE course_author = %d
   		ORDER BY course_title ASC
    ", $current_user->ID ) );

	// Check returned data.
	if ( $course_users ) {
		foreach ( $course_users as $course_user ) {
			if ( isset( $course_user->user_id ) ) {
				$search_users[] = $course_user->user_id;
			}
		}
	}

	// Set the query paramater
	$query->set( 'exclude', array_unique( $search_users ) );
}

/**
 * Code that checks if we're resetting the user progress.
 *
 * @since 1.0.0
 */
function WPCW_users_processUserResetAbility() {
	// Check bulk by default, otherwise check the single user change.
	$resetTypeCommand = WPCW_arrays_getValue( $_GET, 'wpcw_user_progress_reset_point_bulk' );
	if ( ! $resetTypeCommand ) {
		$resetTypeCommand = WPCW_arrays_getValue( $_GET, 'wpcw_user_progress_reset_point_single' );
	}

	// Detect the reset command.
	if ( $resetTypeCommand ) {
		// Check for a specific module/unit/course to reset. If none found, then refresh.
		if ( ! preg_match( '/^(course|module|unit)_([0-9]+)$/', $resetTypeCommand, $matches ) ) {
			// No parameter found, reset.
			wp_redirect( add_query_arg( array( 'page' => 'wpcw-students', 'wpcw_reset' => false ), admin_url( 'admin.php' ) ) );
			die();
		}

		$userList        = array();
		$classroom_reset = false;

		// Check array of users first (as not triggered for a single update)
		if ( isset( $_GET['users'] ) ) {
			// Check if we've chosen any users to reset. If not, reset.
			$userList = array_map( 'intval', (array) $_GET['users'] );
		} elseif ( isset( $_GET['student_id'] ) && isset( $_GET['bulk_course_id'] ) && isset( $_GET['wpcw_user_bulk_progress_reset'] ) ) {
			$userList = array_map( 'intval', (array) $_GET['student_id'] );
		} elseif ( isset( $_GET['bulk_course_id'] ) && isset( $_GET['wpcw_classroom_bulk_progress_reset'] ) ) {
			$classroom_reset = true;
		} elseif ( isset( $_GET['wpcw_users_single'] ) && isset( $_GET['wpcw_user_progress_reset_point_single'] ) && $_GET['wpcw_user_progress_reset_point_single'] ) {
			// Add a single user ID.
			$userList[] = intval( $_GET['wpcw_users_single'] );
		}

		// No users at all.
		if ( empty( $userList ) && ! $classroom_reset ) {
			if ( isset( $_GET['course_id'] ) || isset( $_GET['bulk_course_id'] ) ) {
				$course_id = isset( $_GET['course_id'] ) ? $_GET['course_id'] : $_GET['bulk_course_id'];
				wp_redirect( add_query_arg( array( 'page' => 'wpcw-course-classroom', 'course_id' => absint( $course_id ), 'wpcw_reset' => false ), admin_url( 'admin.php' ) ) );
			} elseif ( isset( $_GET['wpcw_student_reset_page'] ) && isset( $_GET['wpcw_student'] ) ) {
				wp_redirect( add_query_arg( array(
					'page'       => esc_attr( $_GET['wpcw_student_reset_page'] ),
					'id'         => absint( $_GET['wpcw_student'] ),
					'wpcw_reset' => false,
				), admin_url( 'admin.php' ) ) );
			} else {
				wp_redirect( add_query_arg( array( 'page' => 'wpcw-students', 'wpcw_reset' => false ), admin_url( 'admin.php' ) ) );
			}
			die();
		}

		// See what we tried to reset to.
		$unitList  = false;
		$courseMap = new WPCW_CourseMap();
		switch ( $matches[1] ) {
			case 'unit':
				$courseMap->loadDetails_byUnitID( $matches[2] );
				$unitList = $courseMap->getUnitIDList_afterUnit( $matches[2] );
				break;

			case 'module':
				$courseMap->loadDetails_byModuleID( $matches[2] );
				$unitList = $courseMap->getUnitIDList_afterModule( $matches[2] );
				break;

			case 'course':
				$courseMap->loadDetails_byCourseID( $matches[2] );
				$unitList = $courseMap->getUnitIDList_forCourse();
				break;

			default:
				// No parameter found, reset.
				wp_redirect( add_query_arg( array( 'page' => 'wpcw-students', 'wpcw_reset' => false ), admin_url( 'admin.php' ) ) );
				die();
				break;
		}

		// Classroom Reset.
		if ( empty( $userList ) && $classroom_reset ) {
			if ( isset( $_GET['course_id'] ) || isset( $_GET['bulk_course_id'] ) ) {
				$course_id = isset( $_GET['course_id'] ) ? $_GET['course_id'] : $_GET['bulk_course_id'];
				$userList  = wpcw()->students->get_students( array( 'course_id' => absint( $course_id ), 'number' => - 1, 'fields' => 'ids' ) );
			}
		}

		// Now do the reset of the progress.
		wpcw_reset_progress( $courseMap->getCourseDetails(), $userList, $unitList );

		// Classroom Reset.
		if ( $classroom_reset ) {
			wp_redirect( add_query_arg( array( 'page' => 'wpcw-course-classroom', 'course_id' => absint( $course_id ), 'wpcw_reset_classroom' => true ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Redirect to remove the GET flags from the URL.
		if ( isset( $_GET['course_id'] ) || isset( $_GET['bulk_course_id'] ) ) {
			$course_id = isset( $_GET['course_id'] ) ? $_GET['course_id'] : $_GET['bulk_course_id'];
			wp_redirect( add_query_arg( array( 'page' => 'wpcw-course-classroom', 'course_id' => absint( $course_id ), 'wpcw_reset' => 'true' ), admin_url( 'admin.php' ) ) );
		} elseif ( isset( $_GET['wpcw_student_reset_page'] ) && isset( $_GET['wpcw_student'] ) ) {
			wp_redirect( add_query_arg( array(
				'page'       => esc_attr( $_GET['wpcw_student_reset_page'] ),
				'id'         => absint( $_GET['wpcw_student'] ),
				'wpcw_reset' => true,
			), admin_url( 'admin.php' ) ) );
		} else {
			wp_redirect( add_query_arg( array( 'page' => 'wpcw-students', 'wpcw_reset' => 'true' ), admin_url( 'admin.php' ) ) );
		}
		die();
	}
}

/**
 * Generate a list of filters for a table, that ultimately is used to trigger an SQL filter on the view
 * of items in a table.
 *
 * @since 1.0.0
 *
 * @param array $filterList The list of items to use in the filter.
 * @param string $baseURL The string to use at the start of the URL to ensure it works correctly.
 * @param string $activeItem The key that matches the item that's currently selected.
 *
 * @return string The HTML to render the filter.
 */
function WPCW_table_showFilters( $filterList, $baseURL, $activeItem ) {
	$html = '<div class="subsubsub wpcw_table_filter">';
	foreach ( $filterList as $filterKey => $filterLabel ) {
		$html .= sprintf( '<a href="%s%s" class="%s">%s</a>',
			$baseURL, $filterKey,
			( $activeItem == $filterKey ? 'wpcw_table_filter_active' : '' ),
			$filterLabel
		);
	}

	return $html . '</div>';
}

/**
 * Show the section that deals with pagination.
 *
 * @since 1.0.0
 *
 * @param string $baseURL The URL to use that starts of the paging.
 * @param integer $pageNumber The current page.
 * @param integer $pageCount The number of pages.
 * @param integer $dataCount The number of data rows.
 * @param integer $recordStart The current record number.
 * @param integer $recordEnd The ending record number.
 * @param string $leftControls The HTML for controls shown on the left.
 */
function WPCW_tables_showPagination( $baseURL, $pageNumber, $pageCount, $dataCount, $recordStart, $recordEnd, $leftControls = false ) {
	$html = '<div class="tablenav wpcw_tbl_paging">';

	$html .= '<div class="wpbs_paging tablenav-pages">';
	$html .= sprintf( '<span class="displaying-num">Displaying %s &ndash; %s of %s</span>',
		$recordStart,
		( $dataCount < $recordEnd ? $dataCount : $recordEnd ), // ensure that the upper number of the record matches how many are left.
		$dataCount
	);

	// Got more than 1 page?
	if ( $pageCount > 1 ) {
		if ( $pageNumber > 1 ) {
			$html .= sprintf( '<a href="%s%d" class="prev page-numbers" data-pagenum="%d">&laquo;</a>' . "\n",
				$baseURL,
				$pageNumber - 1,
				$pageNumber - 1
			);
		}

		$pageList = array();

		// Always have first and last page linked
		$pageList[] = 1;
		$pageList[] = $pageCount;

		// Have 3 pages either side of page we're on
		if ( $pageNumber - 3 > 1 ) {
			$pageList[] = $pageNumber - 3;
		}

		if ( $pageNumber - 2 > 1 ) {
			$pageList[] = $pageNumber - 2;
		}
		if ( $pageNumber - 1 > 1 ) {
			$pageList[] = $pageNumber - 1;
		}
		if ( $pageNumber + 1 < $pageCount ) {
			$pageList[] = $pageNumber + 1;
		}
		if ( $pageNumber + 2 < $pageCount ) {
			$pageList[] = $pageNumber + 2;
		}
		if ( $pageNumber + 3 < $pageCount ) {
			$pageList[] = $pageNumber + 3;
		}

		// Plus we want the current page
		if ( $pageNumber != $pageCount && $pageNumber != 1 ) {
			$pageList[] = $pageNumber;
		}

		// Sort pages in order and then render them
		sort( $pageList );
		$previous = 0;
		foreach ( $pageList as $pageLink ) {
			// Add dots if a large gap between numbers
			if ( $previous > 0 && ( $pageLink - $previous ) > 1 ) {
				$html .= '<span class="page-numbers dots">...</span>';
			}

			$html .= sprintf( '<a href="%s%d" class="page-numbers %s" data-pagenum="%d">%s</a>',
				$baseURL,
				$pageLink,
				( $pageNumber == $pageLink ? 'current' : '' ),
				$pageLink,
				$pageLink
			);

			// Want to check what the previous one is
			$previous = $pageLink;
		}

		// Got pages left at the end
		if ( $pageCount > $pageNumber ) {
			$html .= sprintf( '<a href="%s%s" class="next page-numbers" data-pagenum="%d">&raquo;</a>',
				$baseURL,
				$pageNumber + 1,
				$pageNumber + 1
			);
		}
	} // end of it pageCount > 1
	$html .= '</div>'; // end of tablenav-pages

	$html .= '</div>'; // end of tablenav
	$html .= $leftControls;

	return $html;
}

/**
 * Get the URL for the desired page, preserving any parameters.
 *
 * @since 1.0.0
 *
 * @param string $pageBase The based page to fetch.
 * @param mixed $ignoreFields The array or string of parameters not to include.
 *
 * @return string The newly formed URL.
 */
function WPCW_urls_getURLWithParams( $pageBase, $ignoreFields = false ) {
	// Parameters to extract from URL to keep in the URL.
	$params = array(
		's'       => false,
		'pagenum' => false,
		'filter'  => false,
		'order'   => false,
		'orderby' => false,
	);

	// Got fields we don't want in the URL? Handle both a string and
	// arrays
	if ( $ignoreFields ) {
		if ( is_array( $ignoreFields ) ) {
			foreach ( $ignoreFields as $field ) {
				unset( $params[ $field ] );
			}
		} else {
			unset( $params[ $ignoreFields ] );
		}
	}

	foreach ( $params as $paramName => $notused ) {
		$value = WPCW_arrays_getValue( $_GET, $paramName );
		if ( $value ) {
			$pageBase .= '&' . $paramName . '=' . $value;
		}
	}

	return $pageBase;
}

/**
 * Method called whenever a post is saved, which will check that any course units
 * save their meta data.
 *
 * @since 1.0.0
 *
 * @param integer $post_id The ID of the post being saved.
 */
function WPCW_units_saveUnitPostMetaData( $post_id, $post ) {

	// Handle WPCW-related page and post metadata
	if( in_array( $post->post_type, array( 'page', 'post' ) ) ) {
		$new_restrictions = array();
		if( array_key_exists( 'wpcw_course_restriction', $_POST ) ) {
			$new_restrictions = $_POST['wpcw_course_restriction'];
		}
		update_post_meta( $post->ID, 'wpcw_course_restriction', $new_restrictions );

		return; // There's nothing left to do for pages or posts
	}

	// Check we have a course unit, not any other type (including revisions).
	if ( 'course_unit' != $post->post_type ) {
		return;
	}

	// Check user is allowed to edit the post.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Current User
	$current_user = wp_get_current_user();

	// See if there's an entry in the courseware table
	$SQL = $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->units_meta
		WHERE unit_id = %d
	", $post_id );

	// Ensure there's a blank entry in the database for this post.
	if ( ! $unitDetails = $wpdb->get_row( $SQL ) ) {
		$SQL = $wpdb->prepare( "
			INSERT INTO $wpcwdb->units_meta (unit_id, parent_module_id, unit_author)
			VALUES (%d, 0, %d)
		", $post_id, $current_user->ID );

		$wpdb->query( $SQL );
	}

	// Update the selection for the unit template to standard WP meta table
	if ( $template = WPCW_arrays_getValue( $_POST, 'wpcw_units_choose_template_list' ) ) {
		update_post_meta( $post_id, WPCW_TEMPLATE_META_ID, $template );
	}

	$convertedTimestamp = absint( WPCW_arrays_getValue( $_POST, 'wpcw_units_drip_interval' ) );
	$interval_type      = WPCW_arrays_getValue( $_POST, 'wpcw_units_drip_interval_type' );

	switch ( $interval_type ) {
		case 'interval_hours':
			$convertedTimestamp = $convertedTimestamp * WPCW_TIME_HR_IN_SECS;
			break;

		case 'interval_days':
			$convertedTimestamp = $convertedTimestamp * WPCW_TIME_DAY_IN_SECS;
			break;

		case 'interval_weeks':
			$convertedTimestamp = $convertedTimestamp * WPCW_TIME_WEEK_IN_SECS;
			break;

		case 'interval_months':
			$convertedTimestamp = $convertedTimestamp * WPCW_TIME_MONTH_IN_SECS;
			break;

		case 'interval_years':
			$convertedTimestamp = $convertedTimestamp * WPCW_TIME_YEAR_IN_SECS;
			break;
	}

	// For the drip-feed interval, we need to add to the post meta table.
	$metaTableUpdate = array(
		'unit_id'                 => $post_id,
		'unit_drip_type'          => WPCW_arrays_getValue( $_POST, 'wpcw_units_drip_type' ),
		'unit_drip_interval'      => $convertedTimestamp,
		'unit_drip_interval_type' => $interval_type,

		// Actual date is converted using the hidden field that has a reverse-date notation
		// to bypass the translation aspects.
		'unit_drip_date'          => date( 'Y-m-d H:i:s', strtotime( WPCW_arrays_getValue( $_POST, 'wpcw_units_drip_date' ) ) ),
		'unit_teaser'             => WPCW_arrays_getValue( $_POST, 'wpcw_unit_teaser' ) ?: 0
	);

	$wpdb->query( arrayToSQLUpdate( $wpcwdb->units_meta, $metaTableUpdate, 'unit_id' ) );

	if ( $unitDetails && $unitDetails->parent_course_id > 0 ) {
		wpcw()->courses->invalidate_builder_cache( $unitDetails->parent_course_id );
	}

	// Let the queuing system know that a change has happened.
	WPCW_queue_dripfeed::updateQueueItems_unitUpdated( $post_id );
}

/**
 * Function called when a post is being deleted by WordPress. Want to check
 * if this relates to a unit, and if so, remove it from our tables.
 *
 * @since 1.0.0
 *
 * @param integer $post_id The ID of the post being deleted.
 */
function WPCW_units_deleteUnitHandler( $post_id ) {
	// Only process course units.
	if ( 'course_unit' != get_post_type( $post_id ) ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// See if we've got data on this unit in the meta table
	$SQL = $wpdb->prepare( "SELECT * FROM $wpcwdb->units_meta WHERE unit_id = %d", $post_id );
	if ( $unitDetails = $wpdb->get_row( $SQL ) ) {
		// Associated with a course? Get course details
		$parentData = WPCW_units_getAssociatedParentData( $post_id );

		// Right, it's one of our units, so need to delete the meta data
		$SQL = $wpdb->prepare( "DELETE FROM $wpcwdb->units_meta WHERE unit_id = %d", $post_id );
		$wpdb->query( $SQL );

		// Now update the course data.
		if ( $unitDetails->parent_course_id > 0 ) {
			WPCW_courses_reorderUnitNumbers( $unitDetails->parent_course_id );

			$parentCourseDetails = WPCW_courses_getCourseDetails( $unitDetails->parent_course_id );

			// Need to update the course unit count and progresses
			do_action( 'wpcw_course_details_updated', $parentCourseDetails );

			wpcw()->courses->invalidate_builder_cache( $unitDetails->parent_course_id );
		}
	}

	// Delete it from the user progress too
	$SQL = $wpdb->prepare( "DELETE FROM $wpcwdb->user_progress WHERE unit_id = %d", $post_id );
	$wpdb->query( $SQL );

	// Quiz - Unconnect it from the quiz that it's associated with.
	$SQL = $wpdb->prepare( "UPDATE $wpcwdb->quiz SET parent_unit_id = 0, parent_course_id = 0 WHERE parent_unit_id = %d", $post_id );
	$wpdb->query( $SQL );

	// Quiz Progress - Remove user progress for this quiz/unit
	$SQL = $wpdb->prepare( "UPDATE $wpcwdb->user_progress_quiz SET unit_id = 0 WHERE unit_id = %d", $post_id );
	$wpdb->query( $SQL );

	// Remove any queue data so that users aren't notified about deleted items.
	WPCW_queue_dripfeed::updateQueueItems_unitDeleted( $post_id );
}

/**
 * Function called when a unit has been moved to the trash, but not expunged.
 *
 * @since 1.0.0
 *
 * @param integer $post_id The ID of the post being deleted.
 */
function WPCW_units_deleteUnitHandler_inTrash( $post_id ) {
	// Only process course units.
	if ( 'course_unit' != get_post_type( $post_id ) ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// See if we've got data on this unit in the meta table
	$SQL = $wpdb->prepare( "SELECT * FROM $wpcwdb->units_meta WHERE unit_id = %d", $post_id );
	if ( $unitDetails = $wpdb->get_row( $SQL ) ) {
		// Associated with a course? Get the course details
		$parentData = WPCW_units_getAssociatedParentData( $post_id );

		// Now remove this unit from the parent course without deleting the meta data.
		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpcwdb->units_meta
			SET parent_module_id = 0,
			    parent_course_id = 0,
			    unit_order = 0,
			    unit_number = 0
	      WHERE unit_id = %d
		", $post_id ) );

		// Now update the course data.
		if ( $unitDetails->parent_course_id > 0 ) {
			WPCW_courses_reorderUnitNumbers( $unitDetails->parent_course_id );

			$parentCourseDetails = WPCW_courses_getCourseDetails( $unitDetails->parent_course_id );

			// Need to update the course unit count and progresses
			do_action( 'wpcw_course_details_updated', $parentCourseDetails );

			wpcw()->courses->invalidate_builder_cache( $unitDetails->parent_course_id );
		}
	}

	// Quiz - Unconnect it from the quiz that it's associated with.
	$SQL = $wpdb->prepare( "UPDATE $wpcwdb->quiz SET parent_unit_id = 0, parent_course_id = 0 WHERE parent_unit_id = %d", $post_id );
	$wpdb->query( $SQL );

	// Remove any queue data so that users aren't notified about deleted items.
	WPCW_queue_dripfeed::updateQueueItems_unitDeleted( $post_id );
}

/**
 * Function that completely re-orders the units in a course to clear out orphans.
 *
 * @since 1.0.0
 *
 * @param integer $course_id The ID of the course to update.
 */
function WPCW_courses_reorderUnitNumbers( $course_id ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Get a list of all units for this course in absolute order
	$unitList = $wpdb->get_results( $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->units_meta
		WHERE parent_course_id = %d
		ORDER BY unit_order ASC
	", $course_id ) );

	// Just update unit numbers, don't worry about ordering, as that should be preserved.
	$lastModule       = - 1;
	$targetUnitNumber = 0;

	if ( ! empty( $unitList ) ) {
		foreach ( $unitList as $singleUnit ) {
			// New module, so reset the unit number.
			if ( $singleUnit->parent_module_id != $lastModule ) {
				$lastModule       = $singleUnit->parent_module_id;
				$targetUnitNumber = 1;
			} // Same module, so increase unit number.
			else {
				$targetUnitNumber ++;
			}

			// Only update units that need fixing.
			if ( $targetUnitNumber != $singleUnit->unit_number ) {
				$wpdb->query( $wpdb->prepare( "
					UPDATE $wpcwdb->units_meta
					   SET unit_number = %d
					WHERE parent_course_id = %d
					  AND unit_id = %d
				", $targetUnitNumber, $course_id, $singleUnit->unit_id ) );
			}
		}
	}
}

/**
 * Change Course Unit Counts in the Table Filter View.
 *
 * @since 1.0.0
 */
function WPCW_course_unit_permissions_list_table_views_filter( $view ) {
	// Global
	global $wpdb;

	// Get Current User
	$current_user = wp_get_current_user();
	$post_type    = 'course_unit';

	/*
     * Change the counts
	 *
	 * [all] => <a href="edit.php?post_type=course_unit&#038;all_posts=1" class="current">All <span class="count">(6)</span></a>
     * [mine] => <a href="edit.php?post_type=course_unit&#038;author=2">Mine <span class="count">(1)</span></a>
     * [publish] => <a href="edit.php?post_status=publish&#038;post_type=course_unit">Published <span class="count">(5)</span></a>
     * [draft] => <a href="edit.php?post_status=draft&#038;post_type=course_unit">Draft <span class="count">(1)</span></a>
     */
	$all = $wpdb->get_var( "SELECT COUNT(*)
		 FROM $wpdb->posts
		 WHERE ( post_status = 'publish' OR post_status = 'draft' )
		 AND ( post_author = '$current_user->ID' AND post_type = '$post_type' )"
	);
	if ( isset( $view['mine'] ) ) {
		$mine = $wpdb->get_var( "SELECT COUNT(*)
			 FROM $wpdb->posts
			 WHERE post_status = 'publish'
			 AND post_author = '$current_user->ID'
			 AND post_type = '$post_type'"
		);
	}
	$publish = $wpdb->get_var( "SELECT COUNT(*)
		 FROM $wpdb->posts
		 WHERE post_status = 'publish'
		 AND post_author = '$current_user->ID'
		 AND post_type = '$post_type' "
	);
	if ( isset( $view['draft'] ) ) {
		$draft = $wpdb->get_var( "SELECT COUNT(*)
			 FROM $wpdb->posts
			 WHERE post_status = 'draft'
			 AND post_author = '$current_user->ID'
			 AND post_type = '$post_type'"
		);
	}

	// All
	if ( isset( $view['all'] ) ) {
		$view['all'] = preg_replace( '/\(.+\)/U', '(' . $all . ')', $view['all'] );
	}

	// Mine
	if ( isset( $view['mine'] ) ) {
		$view['mine'] = preg_replace( '/\(.+\)/U', '(' . $mine . ')', $view['mine'] );
	}

	// Publish
	if ( isset( $view['publish'] ) ) {
		$view['publish'] = preg_replace( '/\(.+\)/U', '(' . $publish . ')', $view['publish'] );
	}

	// Draft
	if ( isset( $view['draft'] ) ) {
		$view['draft'] = preg_replace( '/\(.+\)/U', '(' . $draft . ')', $view['draft'] );
	}

	return $view;
}

/**
 * Add a duplicate post link.
 *
 * @since 1.0.0
 */
function WPCW_units_admin_addActionRows( $actions, $post ) {
	// Only add duplicate for units.
	if ( 'course_unit' == $post->post_type ) {
		// Create a nonce & add an action to duplicate this unit.
		$actions['duplicate_post'] = sprintf( '<a class="wpcw_units_admin_duplicate" data-nonce="%s" data-postid="%d" href="#">%s</a>',
			wp_create_nonce( 'wpcw_ajax_unit_change' ),
			$post->ID,
			__( 'Duplicate Unit', 'wp-courseware' )
		);
	}

	return $actions;
}


/**
 * Generate a course list for resetting the progress for a user.
 *
 * @since 1.0.0
 *
 * @param string $fieldName The name to use for the name attribute for this dropdown.
 * @param array $courseIDList If specified, this is a list of IDs to determine which courses to use in the reset box.
 * @param string $blankMessage the message to show if there are no courses.
 * @param string $addBlank Use this string as the first item in the dropdown.
 * @param string $cssID The CSS ID to use for the select box.
 * @param string $cssClass The CSS class to use for the select box.
 *
 * @return string The course reset dropdown box.
 */
function WPCW_courses_getCourseResetDropdown( $fieldName, $blankMessage, $addBlank, $cssID, $cssClass, $courseIDList = false ) {
	$selectDetails = array( '' => $addBlank );

	// Need all courses
	$courseList = WPCW_courses_getCourseList();
	if ( ! empty( $courseList ) ) {
		$blankCount = 2;
		foreach ( $courseList as $courseID => $aCourse ) {
			// Filter out unwanted courses.
			if ( is_array( $courseIDList ) && ! in_array( $courseID, $courseIDList ) ) {
				continue;
			}

			// Have sentinel of course_ to identify a course.
			$selectDetails[ 'course_' . $courseID ] = $aCourse;

			// Now we add the modules for this course
			$moduleList = WPCW_courses_getModuleDetailsList( $courseID );
			if ( ! empty( $moduleList ) ) {
				foreach ( $moduleList as $moduleID => $moduleDetails ) {
					// Now we add the units for this course
					$units = WPCW_units_getListOfUnits( $moduleID );
					if ( ! empty( $units ) ) {
						// Only add a module if it has units, to make resetting easier.
						$selectDetails[ 'module_' . $moduleID ] = sprintf( '&nbsp;&nbsp;- %s %d: %s',
							__( 'Module', 'wp-courseware' ),
							$moduleDetails->module_number,
							$moduleDetails->module_title
						);

						foreach ( $units as $unitID => $unitDetails ) {
							$selectDetails[ 'unit_' . $unitID ] = sprintf( '&nbsp;&nbsp;-- %s %d: %s',
								__( 'Unit', 'wp-courseware' ),
								$unitDetails->unit_meta->unit_number,
								$unitDetails->post_title
							);
						}
					}
				}
			}

			// Add a blank sentinel to space out courses.
			$paddingKey                   = str_pad( false, $blankCount ++, ' ' );
			$selectDetails[ $paddingKey ] = '&nbsp';
		}
	}

	// No courses... show meaningful message to the trainer.
	if ( count( $selectDetails ) == 1 ) {
		$selectDetails[' '] = $blankMessage;
	}

	// Generate the select box. Use the $cssID as the name of the field too.
	return WPCW_forms_createDropdown( $fieldName, $selectDetails, false, $cssID, $cssClass );
}

/**
 * Calculate the actual number of questions in a quiz - supporting random questions.
 *
 * @since 1.0.0
 *
 * @param integer $quizID The ID of the quiz to get a count for.
 *
 * @return integer The actual number of questions in the quiz.
 */
function WPCW_quizzes_calculateActualQuestionCount( $quizID ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	return $wpdb->get_var( $wpdb->prepare( "
		SELECT SUM(q.question_expanded_count) as total_questions
		FROM $wpcwdb->quiz_qs_mapping qm
			LEFT JOIN $wpcwdb->quiz_qs q ON q.question_id = qm.question_id
		WHERE qm.parent_quiz_id = %d
	", $quizID ) );
}

/**
 * Get a list of all quizzes and surveys for a training course, in the order that they are used.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course to get the quizzes for.
 *
 * @return array A list of the quizzes in order.
 */
function WPCW_quizzes_getAllQuizzesAndSurveysForCourse( $courseID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	return $wpdb->get_results( $wpdb->prepare( "
    	SELECT *
    	FROM $wpcwdb->quiz q
    		LEFT JOIN $wpcwdb->units_meta um ON um.unit_id = q.parent_unit_id
    	WHERE q.parent_course_id = %d
    	ORDER BY unit_order
   	", $courseID ) );
}

/**
 * Generate an array of pass marks for a select box.
 *
 * @since 1.0.0
 *
 * @param string $addBlank If specified, add a blank entry to the top of the list
 *
 * @return array A list of pass marks.
 */
function WPCW_quizzes_getPercentageList( $addBlank = false ) {
	$list = array();

	if ( $addBlank ) {
		$list[] = $addBlank;
	}

	for ( $i = 100; $i > 0; $i -- ) {
		$list[ $i ] = $i . '%';
	}

	return $list;
}

/**
 * Return the number of quizzes that are pending grading or need unblocking for a user.
 *
 * @since 1.0.0
 *
 * @return integer The total number of quizzes that need attention.
 */
function WPCW_quizzes_getCoursesNeedingAttentionCount() {
	// Globals
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Current User
	$current_user = wp_get_current_user();

	// Check permissions and only show notifications from the authors quizes
	if ( is_admin() && ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		return $wpdb->get_var( "SELECT COUNT(*) FROM $wpcwdb->user_progress_quiz upq
			LEFT JOIN $wpcwdb->quiz q ON q.quiz_id = upq.quiz_id
			WHERE quiz_is_latest = 'latest'
			AND (quiz_needs_marking > 0 OR quiz_next_step_type = 'quiz_fail_no_retakes')
			AND quiz_author = $current_user->ID;" );
	}

	return $wpdb->get_var( "
		SELECT COUNT(*)
		FROM $wpcwdb->user_progress_quiz
		WHERE quiz_is_latest = 'latest'
		AND (quiz_needs_marking > 0 OR quiz_next_step_type = 'quiz_fail_no_retakes');" );
}

/**
 * Translates a question type into its proper name.
 *
 * @since 1.0.0
 *
 * @param string $questionType The type of the quiz question.
 *
 * @return string The question type as a label.
 */
function WPCW_quizzes_getQuestionTypeName( $questionType ) {
	$questionTypeStr = __( 'N/A', 'wp-courseware' );
	switch ( $questionType ) {
		case 'truefalse':
			$questionTypeStr = __( 'True/False', 'wp-courseware' );
			break;

		case 'multi':
			$questionTypeStr = __( 'Multiple Choice', 'wp-courseware' );
			break;

		case 'upload':
			$questionTypeStr = __( 'File Upload', 'wp-courseware' );
			break;

		case 'open':
			$questionTypeStr = __( 'Open Ended', 'wp-courseware' );
			break;

		case 'random_selection':
			$questionTypeStr = __( 'Random Selection', 'wp-courseware' );
			break;
	}

	return $questionTypeStr;
}

/**
 * Determine if any of the specified list of questions require manual grading.
 *
 * @since 1.0.0
 *
 * @param array $quizItems The items to check
 *
 * @return boolean True if the items need manual grading, false otherwise.
 */
function WPCW_quizzes_containsQuestionsNeedingManualGrading( $quizItems ) {
	if ( ! $quizItems ) {
		return false;
	}

	foreach ( $quizItems as $quizItem ) {
		// Open or upload questions
		if ( 'open' == $quizItem->question_type || 'upload' == $quizItem->question_type ) {
			return true;
		}
	}

	return false;
}

/**
 * Sends the HTTP headers for CSV content that forces a download.
 *
 * @since 1.0.0
 *
 * @param string $filenameToUse The filename to use.
 */
function WPCW_data_export_sendHeaders_CSV( $filenameToUse ) {
	$debugMode = false;

	if ( ! $debugMode ) {
		// Force the file to download
		header( 'Content-Type: application/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filenameToUse . '"' );
		header( "Cache-Control: no-store, no-cache" );
	} else {
		// Enable below and disable header() calls above for debugging purposes.
		header( 'Content-Type: text/plain' );
	}
}

/**
 * Function that checks to see if a data export has been triggered.
 *
 * @since 1.0.0
 */
function WPCW_data_handleDataExport() {
	// Check for a generic trigger for an export.
	if ( isset( $_GET['wpcw_export'] ) && $exportType = $_GET['wpcw_export'] ) {
		// Check logged in status
		if ( ! is_user_logged_in() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-courseware' ) );
		}

		// Contains the data type => the function that generates it.
		// Permissions are checked in each individual function
		$exportTypeList = array(
			'csv_import_user_sample'      => 'WPCW_data_export_userImportSample',
			'csv_import_questions_sample' => 'WPCW_data_export_questionImportSample',
			'csv_export_survey_data'      => 'WPCW_data_export_quizSurveyData',
			'gradebook_csv'               => 'WPCW_data_export_gradebookData',
		);

		// Check the export type matches the only types of export that we handle.
		if ( ! in_array( $exportType, array_keys( $exportTypeList ) ) ) {
			return;
		}

		// Trigger the function that will export this type of file.
		call_user_func( $exportTypeList[ $exportType ] );
	}
}

/**
 * Function that handles the export of the survey responses for a specified survey.
 *
 * @since 1.0.0
 */
function WPCW_data_export_quizSurveyData() {
	// Globals
	global $wpcwdb, $wpdb;

	// Vars
	$quizID       = absint( trim( WPCW_arrays_getValue( $_GET, 'quiz_id' ) ) );
	$quizDetails  = WPCW_quizzes_getQuizDetails( $quizID, false, true, false );
	$current_user = wp_get_current_user();

	// Check that we can find the survey.
	if ( ! $quizDetails ) {
		wp_die( __( 'Sorry, could not find that survey to export the response data.', 'wp-courseware' ) );
	}

	// Ensure it's a survey
	if ( 'survey' != $quizDetails->quiz_type ) {
		wp_die( __( 'Sorry, but the selected item is not a survey, it\'s a quiz.', 'wp-courseware' ) );
	}

	// If user is not allowed to view courses, then redirect to home page
	if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
		wp_die( __( 'You do not have sufficient permissions to export this survey data.', 'wp-courseware' ) );
	}

	// Check permissions
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) && $current_user->ID != $quizDetails->quiz_author ) {
		wp_die( __( 'You do not have sufficient permissions to export this survey data.', 'wp-courseware' ) );
	}

	// Does this survey contain random questions? If so, then we need to get the full question data
	// of all possible questions
	if ( WPCW_quizzes_doesQuizContainRandomQuestions( $quizDetails ) ) {
		$quizDetails->questions = WPCW_quizzes_randomQuestions_fullyExpand( $quizDetails );
	}

	// Create a URL-safe version of the filename.
	$csvFileName = WPCW_urls_createCleanURL( 'survey-' . $quizDetails->quiz_id . '-' . $quizDetails->quiz_title ) . '.csv';
	WPCW_data_export_sendHeaders_CSV( $csvFileName );

	// The headings
	$headings = array(
		__( 'Trainee WP ID', 'wp-courseware' ),
		__( 'Trainee Name', 'wp-courseware' ),
		__( 'Trainee Email Address', 'wp-courseware' ),
	);

	// Extract the questions to use as headings.
	$questionListForColumns = array();

	// See if we have any questions in the list.
	if ( ! empty( $quizDetails->questions ) ) {
		foreach ( $quizDetails->questions as $questionID => $questionDetails ) {
			$questionListForColumns[ $questionID ] = $questionDetails->question_question;

			// Add this question to the headings.
			$headings[] = $questionDetails->question_question;
		}
	}

	// Start CSV
	$out = fopen( 'php://output', 'w' );

	// Push out the question headings.
	fputcsv( $out, $headings );

	// The responses to the questions
	$answers = $wpdb->get_results( $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->user_progress_quiz
		WHERE quiz_id = %d
	", $quizDetails->quiz_id ) );

	// Process eacy response from the user, extracting their details too.
	if ( ! empty( $answers ) ) {
		foreach ( $answers as $answerDetails ) {
			$resultData = array();

			// We've definitely got the ID
			$resultData[] = $answerDetails->user_id;

			// See if we can get the name and email address.
			$userDetails = get_userdata( $answerDetails->user_id );
			if ( $userDetails ) {
				$resultData[] = $userDetails->display_name;
				$resultData[] = $userDetails->user_email;
			} // User has been deleted.
			else {
				$resultData[] = __( 'User no longer on system.', 'wp-courseware' );
				$resultData[] = __( 'N/A', 'wp-courseware' );
			}

			// Extract their responses into an array
			$theirResponses = maybe_unserialize( $answerDetails->quiz_data );

			// Go through answers logically now
			if ( ! empty( $questionListForColumns ) ) {
				foreach ( $questionListForColumns as $questionID => $questionTitle ) {
					if ( isset( $theirResponses[ $questionID ] ) && isset( $theirResponses[ $questionID ]['their_answer'] ) ) {
						if ( is_array( $theirResponses[ $questionID ]['their_answer'] ) ) {
							$resultData[] = implode( ",", $theirResponses[ $questionID ]['their_answer'] );
						} else {
							//$resultData[] = $theirResponses[ $questionID ]['their_answer'];
							$resultData[] = html_entity_decode( $theirResponses[ $questionID ]['their_answer'], ENT_QUOTES );
						}
					} // Put something in the column, even if there is no answer.
					else {
						$resultData[] = __( 'No answer for this question.', 'wp-courseware' );
					}
				}
			}

			fputcsv( $out, $resultData );
		}
	}

	fclose( $out );

	die();
}

/**
 * Takes a string and makes it safe for a URL.
 *
 * @since 1.0.0
 *
 * @param string $urlString The string to make safe.
 *
 * @return string A string safe enough to use as a URL.
 */
function WPCW_urls_createCleanURL( $urlString ) {
	$urlString = trim( strtolower( $urlString ) );

	// Remove brackets completely
	$urlString = preg_replace( '%[\(\[\]\)]%', '', $urlString );

	// Remove non-alpha characters
	$urlString = preg_replace( '%[^0-9a-z\-]%', '-', $urlString );

	// Replace long sequences of hypens with a single hyphen
	$urlString = preg_replace( '%[\-]+%', '-', $urlString );

	// Remove the last hypen (if there is one)
	$urlString = rtrim( $urlString, '-' );

	return $urlString;
}

/**
 * Function that generates a sample CSV file from the database using the relevant course IDs.
 *
 * @since 1.0.0
 */
function WPCW_data_export_userImportSample() {
	$current_user = wp_get_current_user();

	// Check permissions
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		wp_die( __( 'You do not have sufficient permissions to import users.', 'wp-courseware' ) );
	}

	WPCW_data_export_sendHeaders_CSV( 'wpcw-import-users-sample.csv' );

	// Start CSV
	$out = fopen( 'php://output', 'w' );

	// The headings
	$headings = array( 'first_name', 'last_name', 'email_address', 'courses_to_add_to' );
	fputcsv( $out, $headings );

	// Use existing course IDs to make it more useful. If there are no courses
	// Create some dummy courses to add.
	$courseList    = array();
	$courseList[1] = __( 'Test Course', 'wp-courseware' ) . ' A';
	$courseList[2] = __( 'Test Course', 'wp-courseware' ) . ' B';

	$courseListOfIDs = 0;
	foreach ( $courseList as $courseID => $courseName ) {
		$data   = array();
		$data[] = 'John';
		$data[] = 'Smith';
		$data[] = get_bloginfo( 'admin_email' );

		// Sequentially add each ID to the list
		if ( $courseListOfIDs ) {
			$courseListOfIDs .= ',' . $courseID;
		} else {
			$courseListOfIDs = $courseID;
		}
		$data[] = $courseListOfIDs;

		// Not removing any courses
		$data[] = false;

		fputcsv( $out, $data );
	}

	fclose( $out );

	die();
}

/**
 * Function that generates a sample CSV file from the database using the relevant course IDs.
 *
 * @since 1.0.0
 */
function WPCW_data_export_questionImportSample() {
	$current_user = wp_get_current_user();

	// Check permissions
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		wp_die( __( 'You do not have sufficient permissions to import questions.', 'wp-courseware' ) );
	}

	WPCW_data_export_sendHeaders_CSV( 'wpcw-import-quiz-questions-sample.csv' );

	// Start CSV
	$out = fopen( 'php://output', 'w' );

	// The headings
	$headings = array(
		'quiz_question',
		'question_type',
		'possible_answers',
		'correct_answer',
		'answer_type',
		'hint',
		'explanation',
		'tags',
		'file_extensions',
	);

	// Output Headings
	fputcsv( $out, $headings );

	// Row Data
	$data = array(
		array(
			'Which of the following are blue?',
			'multi',
			'sky | sea | dirt | trees | grass, and other grass like items',
			'sky | grass, and other grass like items',
			'',
			'Above the clouds and below sea level',
			'The sea and sky are blue.',
			'blue',
			'',
		),
		array(
			'Explain the color of the moon.',
			'open',
			'',
			'',
			'single_line',
			'The moon has bright and dark colors',
			'The moon is bright when lit.',
			'yellow,gray',
			'',
		),
		array(
			'Is the sun purple?',
			'truefalse',
			'',
			'FALSE',
			'',
			'The sun is a bright color.',
			'The sun is orange.',
			'orange',
			'',
		),
		array(
			'Upload a picture of a tree',
			'upload',
			'',
			'',
			'',
			'Any kind of tree',
			'Tree’s are green',
			'green,brown',
			'png,jpg,gif',
		),
	);

	// Insert Data
	foreach ( $data as $rowData ) {
		if ( ! empty( $rowData ) ) {
			fputcsv( $out, $rowData );
		}
	}

	fclose( $out );

	die();
}

/**
 * Generates a verbose output of the gradebook data for a specific course.
 *
 * @since 1.0.0
 */
function WPCW_data_export_gradebookData() {
	// Globals
	global $wpcwdb, $wpdb;

	// Current User
	$current_user = wp_get_current_user();

	// If user is not allowed to view courses, then redirect to home page
	if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-courseware' ) );
	}

	// 1 - See if the course exists first.
	$courseDetails = false;
	if ( isset( $_GET['course_id'] ) && $courseID = $_GET['course_id'] ) {
		$courseDetails = WPCW_courses_getCourseDetails( $courseID );
	}

	// Course does not exist, simply output an error using plain text.
	if ( ! $courseDetails ) {
		wp_die( __( 'Sorry, but that course could not be found.', 'wp-courseware' ) );
	}

	// Check permissions on course
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) && $current_user->ID != $courseDetails->course_author ) {
		wp_die( __( 'You do not have sufficient permissions to access this course gradebook data.', 'wp-courseware' ) );
	}

	// 2 - Need a list of all quizzes for this course, excluding surveys.
	$quizzesForCourse = WPCW_quizzes_getAllQuizzesForCourse( $courseDetails->course_id );

	// Handle situation when there are no quizzes.
	if ( ! $quizzesForCourse ) {
		header( 'Content-Type: text/plain' );
		_e( 'There are no quizzes for this course, therefore no grade information to show.', 'wp-courseware' );

		return;
	}

	// Do we want certificates?
	$usingCertificates = ( 'use_certs' == $courseDetails->course_opt_use_certificate );

	// Create a simple list of IDs to use in SQL queries
	$quizIDList = array();
	foreach ( $quizzesForCourse as $singleQuiz ) {
		$quizIDList[] = $singleQuiz->quiz_id;
	}

	// Convert list of IDs into an SQL list
	$quizIDListForSQL = '(' . implode( ',', $quizIDList ) . ')';

	// Course does exist, so now we really output the data
	$csvFilename = sanitize_title( $courseDetails->course_title ) . "-gradebook-" . date( "Y-m-d" ) . ".csv";
	WPCW_data_export_sendHeaders_CSV( $csvFilename );

	// Start CSV
	$out = fopen( 'php://output', 'w' );

	// 3 - The headings for the CSV data
	$headings = array(
		__( 'Name', 'wp-courseware' ),
		__( 'Username', 'wp-courseware' ),
		__( 'Email Address', 'wp-courseware' ),
		__( 'Course Progress', 'wp-courseware' ),
		__( 'Cumulative Grade', 'wp-courseware' ),
		__( 'Has Grade Been Sent?', 'wp-courseware' ),
		__( 'Course Completion Date', 'wp-courseware' ),
	);

	// Check if we're using certificates or not.
	if ( $usingCertificates ) {
		$headings[] = __( 'Is Certificate Available?', 'wp-courseware' );
	}

	// 4 - Add the headings for the quiz titles.
	foreach ( $quizzesForCourse as $singleQuiz ) {
		$headings[] = sprintf( '%s (quiz_%d)', $singleQuiz->quiz_title, $singleQuiz->quiz_id );
	}

	// 6 - Render the headings
	fputcsv( $out, $headings );

	// 7 - Select all users that exist for this course
	$SQL = $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->user_courses uc
		LEFT JOIN $wpdb->users u ON u.ID = uc.user_id
		WHERE uc.course_id = %d
		  AND u.ID IS NOT NULL
		", $courseDetails->course_id );

	$userData = $wpdb->get_results( $SQL );
	if ( ! $userData ) {
		fclose( $out );

		return;
	}

	// 8 - Render the specific user details.
	foreach ( $userData as $userObj ) {
		$quizResults = WPCW_quizzes_getQuizResultsForUser( $userObj->ID, $quizIDListForSQL );

		// Track cumulative data
		$quizScoresSoFar       = 0;
		$quizScoresSoFar_count = 0;

		// Track the quiz scores in order
		$thisUsersQuizData = array();

		// Now render results for each quiz
		foreach ( $quizIDList as $aQuizID ) {
			// Got progress data, process the result
			if ( isset( $quizResults[ $aQuizID ] ) ) {
				// Extract results and unserialise the data array.
				$theResults            = $quizResults[ $aQuizID ];
				$theResults->quiz_data = maybe_unserialize( $theResults->quiz_data );

				// We've got something that needs grading.
				if ( $theResults->quiz_needs_marking > 0 ) {
					$thisUsersQuizData[ 'quiz_' . $aQuizID ] = __( 'Manual Grade Required', 'wp-courseware' );
				} // User is blocked - they've failed and are blocked
				elseif ( 'quiz_fail_no_retakes' == $theResults->quiz_next_step_type ) {
					$thisUsersQuizData[ 'quiz_' . $aQuizID ] = __( 'Quiz Retakes Exhausted', 'wp-courseware' );
				} // Quiz not yet complete...
				elseif ( 'incomplete' == $theResults->quiz_paging_status ) {
					$thisUsersQuizData[ 'quiz_' . $aQuizID ] = __( 'In Progress', 'wp-courseware' );
				} else { // No quizzes need marking, so show the scores as usual.
					// Calculate score, and use for cumulative.
					$score           = number_format( $theResults->quiz_grade );
					$quizScoresSoFar += $score;

					$thisUsersQuizData[ 'quiz_' . $aQuizID ] = $score . '%';

					$quizScoresSoFar_count ++;
				}
			} else { // No progress data - quiz not completed yet
				$thisUsersQuizData[ 'quiz_' . $aQuizID ] = __( 'Not Taken', 'wp-courseware' );
			}
		}

		$dataToOutput = array();

		// These must be in the order of the columns specified above for it all to match up.
		$dataToOutput['name']          = $userObj->display_name;
		$dataToOutput['username']      = $userObj->user_login;
		$dataToOutput['email_address'] = $userObj->user_email;

		// Progress Details
		$dataToOutput['course_progress']     = $userObj->course_progress . '%';
		$dataToOutput['cumulative_grade']    = ( $quizScoresSoFar_count > 0 ? number_format( ( $quizScoresSoFar / $quizScoresSoFar_count ), 1 ) . '%' : __( '-', 'wp-courseware' ) );
		$dataToOutput['has_grade_been_sent'] = ( 'sent' == $userObj->course_final_grade_sent ? __( 'Yes', 'wp-courseware' ) : __( 'No', 'wp-courseware' ) );

		// Course completion date
		$dataToOutput['completion_date'] = __( 'N/A', 'wp-courseware' );
		$userProgress                    = new WPCW_UserProgress( $courseDetails->course_id, $userObj->ID );
		$courseCompDate                  = $userProgress->courseCompletedDate();

		// Do we have a course completion date?
		if ( $courseCompDate ) {
			// Make the date look pretty
			$date_localFormat                = get_option( 'date_format' );
			$date_str                        = date_i18n( $date_localFormat, strtotime( $courseCompDate ) );
			$dataToOutput['completion_date'] = $date_str;
		}

		// Show if there's a certificate that can be downloaded.
		if ( $usingCertificates ) {
			$dataToOutput['is_certificate_available'] = __( 'No', 'wp-courseware' );
			if ( WPCW_certificate_getCertificateDetails( $userObj->ID, $courseDetails->course_id, false ) ) {
				$dataToOutput['is_certificate_available'] = __( 'Yes', 'wp-courseware' );
			}
		}

		// Output the quiz summary here..
		$dataToOutput += $thisUsersQuizData;

		fputcsv( $out, $dataToOutput );
	}

	fclose( $out );

	die();
}

/**
 * Does this quiz contain any random questions.
 *
 * @since 1.0.0
 *
 * @param object $quizDetails The quiz details to check.
 *
 * @return boolean True if there are random questions, false otherwise.
 */
function WPCW_quizzes_doesQuizContainRandomQuestions( $quizDetails ) {
	if ( empty( $quizDetails->questions ) ) {
		return false;
	}

	// Just need the first question to confirm if this is the case.
	foreach ( $quizDetails->questions as $singleQuestion ) {
		if ( 'random_selection' == $singleQuestion->question_type ) {
			return true;
		}
	}

	return false;
}

/**
 * Does this quiz contain any random questions.
 *
 * @since 1.0.0
 *
 * @param object $quizDetails The quiz details to check.
 *
 * @return Boolean True if there are random questions, false otherwise.
 */
function WPCW_quizzes_randomQuestions_fullyExpand( $quizDetails ) {
	if ( empty( $quizDetails->questions ) ) {
		return $quizDetails->questions;
	}

	// Just need the first question to confirm if this is the case.
	$newQuizList = array();
	foreach ( $quizDetails->questions as $questionID => $singleQuestion ) {
		// Got a random selection, so we need to get all question variations.
		if ( 'random_selection' == $singleQuestion->question_type ) {
			// Need tags for this question
			$tagDetails = WPCW_quiz_RandomSelection::decodeTagSelection( $singleQuestion->question_question );

			// Ignore limits, just get all questions
			$expandedQuestions = WPCW_quiz_RandomSelection::questionSelection_getRandomQuestionsFromTags( $tagDetails );
			if ( ! empty( $expandedQuestions ) ) {
				$newQuizList += $expandedQuestions;
			}
		} else { // Normal question - just return it.
			$newQuizList[ $questionID ] = $singleQuestion;
		}
	}

	return $newQuizList;
}
