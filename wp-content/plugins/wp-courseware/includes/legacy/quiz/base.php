<?php
/**
 * WP Courseware Quiz Base Class.
 *
 * @package WPCW
 * @since 1.0.0
 */

if ( ! class_exists( 'WPCW_quiz_base' ) ) {
	/**
	 * Class WPCW_quiz_base.
	 *
	 * @since 1.0.0
	 */
	class WPCW_quiz_base {

		/**
		 * Contains the raw information to render the quiz item.
		 *
		 * @var Object
		 */
		protected $quizItem;

		/**
		 * If true, then show errors on the form (effects frontend or backend).
		 *
		 * @var Boolean
		 */
		public $showErrors;

		/**
		 * If true, then show the user the correct answers.
		 *
		 * @var Boolean
		 */
		public $needCorrectAnswers;

		/**
		 * Contains any CSS classes to add to the rendered form.
		 *
		 * @var String
		 */
		public $cssClasses;

		/**
		 * If true, then the form contains an error.
		 *
		 * @var Boolean
		 */
		public $gotError;

		/**
		 * Any extra quiz HTML to add before the closing section for the quiz.
		 *
		 * @var String
		 */
		public $extraQuizHTML;

		/**
		 * Any extra quiz HTML to add after the selectable answers for the quiz.
		 *
		 * @var String
		 */
		public $extraQuizHTMLAfter;

		/**
		 * Stores the type of question as a simple string.
		 *
		 * @var String
		 */
		public $questionType;

		/**
		 * If true, then hide the tags form on the edit form.
		 *
		 * @var Boolean
		 */
		public $editForm_questionNotSavedYet;

		/**
		 * If true, then hide the question usage count.
		 *
		 * @var Boolean
		 */
		public $hideQuestionUsageCount;

		/**
		 * Stores the hint message.
		 *
		 * @var String
		 */
		public $hint;

		/**
		 * If true, hide the dragging actions in the footer of the control.
		 *
		 * @var Boolean
		 */
		public $hideDragActions;

		/**
		 * @var WP_User The current user.
		 * @since 4.5.2
		 */
		public $currentUser;

		/**
		 * @var bool Is question disabled?
		 * @since 4.5.2
		 */
		public $disabled = false;

		/**
		 * Default constructors.
		 *
		 * @param Object $quizItem The quiz item details.
		 */
		public function __construct( $quizItem ) {
			$this->quizItem                     = $quizItem;
			$this->showErrors                   = false;
			$this->needCorrectAnswers           = false;
			$this->cssClasses                   = false;
			$this->gotError                     = false;
			$this->answerList                   = false;
			$this->answerImageList              = false;
			$this->extraQuizHTML                = false;
			$this->extraQuizHTMLAfter           = false;
			$this->hideDragActions              = false;
			$this->hideQuestionUsageCount       = false;
			$this->editForm_questionNotSavedYet = false;
			$this->currentUser                  = false;

			// Ensure we have a quiz order field, if field doesn't exist.
			if ( ! isset( $this->quizItem->question_order ) ) {
				$this->quizItem->question_order = 0;
			}

			// Ensure we have a quiz author field, if field doesn't exist.
			if ( ! isset( $this->quizItem->question_author ) ) {
				$this->quizItem->question_author = get_current_user_id();
			}

			if ( is_user_logged_in() ) {
				$this->currentUser = wp_get_current_user();
			}

			if ( $this->currentUser && ! user_can( $this->currentUser, 'manage_wpcw_settings' ) && $this->currentUser->ID != $this->quizItem->question_author ) {
				$this->disabled = true;
			}
		}

		/**
		 * Returns the buttons that can be used to control the quiz item.
		 */
		public function getSection_actionButtons( $columnCount ) {
			// Don't want the actions shown.
			if ( $this->hideDragActions ) {
				return;
			}

			return sprintf(
				'<tr class="wpcw_quiz_row_footer">
				<td colspan="%d" class="wpcw_question_actions">
					<a href="#" class="wpcw_delete_icon" rel="%s">%s</a>
					<a href="#" class="wpcw_move_icon">%s</a>
				</td>
			</tr>',
				$columnCount,
				__( 'Are you sure you wish to delete this question?', 'wp-courseware' ),
				__( 'Delete', 'wp-courseware' ),
				__( 'Move', 'wp-courseware' )
			);
		}

		/**
		 * Shows a the header at the top of the question.
		 *
		 * @param Integer $columnCount The number of columns that are being rendered to show the question.
		 *
		 * @return String The HTML for rendering the header section.
		 */
		public function getSection_processHeader( $columnCount ) {
			$html = false;

			// Show the quiz type
			$html .= sprintf( '<tr class="wpcw_quiz_row_header">' );

			// Quiz type
			$html .= sprintf(
				'<td colspan="%d">%s: <b>%s</b>', $columnCount, __( 'Type', 'wp-courseware' ), WPCW_quizzes_getQuestionTypeName( $this->questionType )
			);

			// Usage count
			if ( ! $this->hideQuestionUsageCount ) {
				$html .= sprintf(
					'<span class="wpcw_quiz_row_header_usage_count">(%s)</span>', sprintf( _n( 'In use by 1 quiz', 'In use by %d quizzes', $this->quizItem->question_usage_count, 'wp-courseware' ), $this->quizItem->question_usage_count )
				);
			}

			$html .= '</td>';
			$html .= '</tr>';

			return $html;
		}

		/**
		 * Shows a notice that the question is disabled.
		 *
		 * @since 4.5.2
		 *
		 * @param int $columnCount The number of columns that are being rendered to show the question.
		 *
		 * @return string $html The HTML for rendering the header section.
		 */
		public function getSection_disabledQuestionNotice( $columnCount ) {
			$html = false;

			// Show the quiz type
			$html .= sprintf( '<tr class="wpcw_quiz_row_disable_notice wpcw_msg_error">' );

			// Quiz type
			$html .= sprintf( '<td colspan="%d">%s</td>', $columnCount, __( 'This question is authored by another instructor and can only be moved or deleted from this quiz.', 'wp-courseware' ) );

			$html .= '</tr>';

			return $html;
		}

		/**
		 * Shows a the footer at the bottom of the question.
		 *
		 * @param Integer $columnCount The number of columns that are being rendered to show the question.
		 *
		 * @return String The HTML for rendering the footer section.
		 */
		public function getSection_processFooter( $columnCount ) {
			$html = false;

			// Add optional field for the hint
			$html .= $this->getSection_Hint( $columnCount, $this->hint );

			// Add optional field for showing an explanation.
			$html .= $this->getSection_Explanation( $columnCount );

			// Always show tags, but change how they are rendered for the AJAX, as we can't
			// associate a tag for a question that's not yet been saved.
			$html .= $this->getSection_questionTags( $columnCount, $this->editForm_questionNotSavedYet );

			// Question Author
			$html .= $this->getSection_showQuestionAuthorField();

			// Add icons for adding or removing a question.
			$html .= $this->getSection_actionButtons( $columnCount );

			return $html;
		}

		/**
		 * Shows the row where you can add tags for this question.
		 *
		 * @param Integer $columnCount The number of columns that are being rendered to show the question.
		 * @param Boolean $questionNotSavedYet If true, then there's limited save capability as the question is not saved yet.
		 *
		 * @return String The HTML to show the tags.
		 */
		public function getSection_questionTags( $columnCount, $questionNotSavedYet = false ) {
			$html = sprintf( '<tr data-questionsaved="%s">', ( $questionNotSavedYet ? 'no' : 'yes' ) );
			$html .= sprintf(
				'<th>%s<span class="wpcw_inner_hint">%s</span></th>', __( 'Question Tags', 'wp-courseware' ), __( '(Optional) Add tags to this question to add it to your pool of questions to reuse in other quizzes.', 'wp-courseware' )
			);

			// For new questions, to ensure they get replaced with IDs, we need to add an _ to the question ID, but without
			// changing the field for normal numbers.
			$extraPrefix = false;
			if ( 'new' == substr( $this->quizItem->question_id, 0, 3 ) ) {
				$extraPrefix = '_';
			}

			$html .= sprintf(
				'<td class="wpcw_quiz_details_question_tags" colspan="%s" data-questionid="%s%s" id="wpcw_quiz_details_question_tags_%s">', $columnCount - 1, $extraPrefix, $this->quizItem->question_id, $this->quizItem->question_id
			);

			// The input box and add button.
			$html .= sprintf( '<input class="wpcw_question_add_tag_input" name="wpcw_question_add_tag_input" type="text" value="">' );
			$html .= sprintf( '<input class="button-secondary wpcw_question_add_tag_btn" type="submit" value="%s" >', __( 'Add', 'wp-courseware' ) );

			// Now need to have a list of the tags currently being used, all with X to allow them to be removed.]
			$tagsToShow = false;
			if ( ! empty( $this->quizItem->tags ) ) {
				$tagsToShow = $this->quizItem->tags;
			}
			// Use this so we always create a tag wrapper.
			$html .= WPCW_questions_tags_render( $this->quizItem->question_id, $tagsToShow );

			$html .= '</td>';

			$html .= '</tr>';

			return $html;
		}

		/**
		 * Shows a field where the user can enter an explanation for the question (if they want to).
		 *
		 * @param Integer $columnCount The number of columns that are being rendered to show the question.
		 *
		 * @return String The HTML for rendering the explanation button.
		 */
		public function getSection_Explanation( $columnCount ) {
			$html = '<tr>';
			$html .= sprintf(
				'<th>%s<span class="wpcw_inner_hint">%s</span></th>', __( 'Explanation', 'wp-courseware' ), __( '(Optional) Displayed after the quiz is submitted to offer information on the correct answer.', 'wp-courseware' )
			);

			$html .= '<td class="wpcw_quiz_details_question_explanation">';
			$html .= sprintf( '<textarea name="question_answer_explanation_%s">%s</textarea>', $this->quizItem->question_id, $this->quizItem->question_answer_explanation );
			$html .= '</td>';

			// Works out the space after the text area.
			$columnCount -= 2;
			if ( $columnCount > 0 ) {
				$html .= sprintf( '<td colspan="%d">&nbsp;</td>', $columnCount );
			}

			$html .= '</tr>';

			return $html;
		}

		/**
		 * Shows a field where the user can enter a hint for the question (if they want to).
		 *
		 * @param Integer $columnCount The number of columns that are being rendered to show the question.
		 * @param String  $messageToShow The message to show for the hint.
		 *
		 * @return String The HTML for rendering the explanation button.
		 */
		public function getSection_Hint( $columnCount, $messageToShow ) {
			$html = '<tr>';
			$html .= sprintf(
				'<th>%s<span class="wpcw_inner_hint">%s</span></th>', __( 'Hint', 'wp-courseware' ), $messageToShow
			);

			$html .= '<td class="wpcw_quiz_details_question_hint">';
			$html .= sprintf( '<textarea name="question_answer_hint_%s">%s</textarea>', $this->quizItem->question_id, $this->quizItem->question_answer_hint );
			$html .= '</td>';

			// Works out the space after the text area.
			$columnCount -= 2;
			if ( $columnCount > 0 ) {
				$html .= sprintf( '<td colspan="%d">&nbsp;</td>', $columnCount );
			}

			$html .= '</tr>';

			return $html;
		}

		/**
		 * Shows the field where the user can choose an image for the question (that's used as part of the question).
		 *
		 * @param Integer $columnCount The number of columns that are being rendered to show the question.
		 *
		 * @return String The HTML for rendering the image field.
		 */
		public function getSection_showImageField( $columnCount ) {
			$html = '<tr>';
			$html .= sprintf(
				'<th>%s<span class="wpcw_inner_hint">%s</span></th>', __( 'Question Image URL', 'wp-courseware' ), __( '(Optional) An image to show with the question.', 'wp-courseware' )
			);

			$html .= '<td class="wpcw_quiz_details_question_image">';
			$html .= sprintf(
				'<input name="question_image_%s" id="wpcw_insert_image_holder_%s" class="wpcw_insert_image_holder" type="text" value="%s">', $this->quizItem->question_id, $this->quizItem->question_id, $this->quizItem->question_image
			);

			// The add media button
			$html .= sprintf(
				'<span class="wpcw_insert_image_wrap"><a href="#" class="button wpcw_insert_image" data-uploader_title="%s" data-uploader_btn_text="%s" data-target="wpcw_insert_image_holder_%s" title="%s"><span class="wpcw_insert_image_img"></span> %s</a></span>', __( 'Choose an image for your question...', 'wp-courseware' ), __( 'Select Image...', 'wp-courseware' ), $this->quizItem->question_id, __( 'Select Image', 'wp-courseware' ), __( 'Select Image', 'wp-courseware' )
			);

			$html .= '</td>';

			// Works out the space after the text area.
			$columnCount -= 2;
			if ( $columnCount > 0 ) {
				$html .= sprintf( '<td colspan="%d">&nbsp;</td>', $columnCount );
			}

			$html .= '</tr>';

			return $html;
		}

		/**
		 * Shows the field where the question author is set.
		 */
		public function getSection_showQuestionAuthorField() {
			return sprintf( '<input type="hidden" name="question_author_%s" value="%s" class="wpcw_question_hidden_author" />', $this->quizItem->question_id, $this->quizItem->question_author );
		}

		/**
		 * Output the form that allows questions to be configured.
		 */
		public function editForm_toString() {
			return false;
		}

		/**
		 * Create the form that the user can complete when completing their answers.
		 *
		 * @param Object  $parentQuiz The parent quiz object.
		 * @param Integer $questionNum The current question number.
		 * @param String  $selectedAnswer If an answer is selected already, this is what's been selected.
		 * @param Boolean $showAsError If set to 'missing', field is missing. If set to 'wrong', then the answer is wrong.
		 * @param String  $errorToShow Optional parameter which contains the error message if something went wrong.
		 */
		public function renderForm_toString( $parentQuiz, $questionNum, $selectedAnswer, $showAsError, $errorToShow = false ) {
			return $this->renderForm_toString_withClass( $parentQuiz, $questionNum, $selectedAnswer, $showAsError, false, $errorToShow );
		}

		/**
		 * Create the form that the user can complete when completing their answers.
		 *
		 * @param Object  $parentQuiz The parent quiz object.
		 * @param Integer $questionNum The current question number.
		 * @param String  $selectedAnswer If an answer is selected already, this is what's been selected.
		 * @param Boolean $showAsError If set to 'missing', field is missing. If set to 'wrong', then the answer is wrong.
		 * @param String  $cssClass Extra CSS Classes to add to the wrapper
		 * @param String  $errorToShow Optional parameter which contains the error message if something went wrong.
		 */
		protected function renderForm_toString_withClass( $parentQuiz, $questionNum, $selectedAnswer, $showAsError, $cssClass, $errorToShow = false ) {
			$fieldID = sprintf( 'wpcw_fe_wrap_question_%d_%s_%d', $parentQuiz->quiz_id, $this->questionType, $this->quizItem->question_id );

			$html = false;
			$html .= sprintf( '<div class="wpcw_fe_quiz_q_single %s %s%s" id="%s"><fieldset>', $this->cssClasses, $cssClass, ( $showAsError ? ' wpcw_fe_quiz_q_error' : '' ), $fieldID );

			// Is the answer wrong?
			$wrongAnswerState = ( 'wrong' == $showAsError );

			// Question title
			$html .= sprintf(
				'<div class="wpcw_fe_quiz_q_title"><legend>%s #%d: %s%s</legend></div>', __( 'Question', 'wp-courseware' ), $questionNum, nl2br( htmlspecialchars( $this->quizItem->question_question ) ), ( $wrongAnswerState ? '<span class="wpcw_fe_quiz_status">(' . __( 'Incorrect', 'wp-courseware' ) . ')</span>' : '' )
			);

			// Got an error? Show the error just beneath the question, before the entry section.
			if ( $showAsError && $errorToShow ) {
				$html .= sprintf( '<div class="wpcw_fe_quiz_q_single_error">%s</div>', $errorToShow );
			}

			// If there's an image for this quiz, then render it.
			if ( $this->quizItem->question_image ) {
				$html .= sprintf( '<div class="wpcw_fe_quiz_q_image"><img src="%s" /></div>', $this->quizItem->question_image );
			}

			// Got any extra HTML to add?
			if ( $this->extraQuizHTML ) {
				$html .= $this->extraQuizHTML;
			}

			// Render the list of answers if we have any as radio items.
			$html .= $this->renderForm_toString_answerList( $parentQuiz, $questionNum, $selectedAnswer, $showAsError, $cssClass );

			// Got any extra HTML to add?
			if ( $this->extraQuizHTMLAfter ) {
				$html .= $this->extraQuizHTMLAfter;
			}

			$html .= '</fieldset></div>';

			return $html;
		}

		/**
		 * Handle the rendering of the list of answers to choose from (used by T/F questions and multiple answers).
		 *
		 * @param Object  $parentQuiz The parent quiz object.
		 * @param Integer $questionNum The current question number.
		 * @param String  $selectedAnswer If an answer is selected already, this is what's been selected.
		 * @param Boolean $showAsError If set to 'missing', field is missing. If set to 'wrong', then the answer is wrong.
		 * @param String  $cssClass Extra CSS Classes to add to the wrapper
		 */
		protected function renderForm_toString_answerList( $parentQuiz, $questionNum, $selectedAnswer, $showAsError, $cssClass ) {
			$html = false;

			// This is done for T/F and Multiple Choice Questions
			if ( $this->answerList ) {
				// Creating a list using <UL> rather than tables for simplicity. Should render
				// fine on nearly all browsers/themes.
				$html .= '<ul class="wpcw_fe_quiz_q_answers">';
				foreach ( $this->answerList as $answerItem => $answerValue ) {
					$imageCode = false;

					if ( ! empty( $this->answerImageList ) && isset( $this->answerImageList[ $answerValue ] ) ) {
						$imageCode = sprintf( '<div class="wpcw_fe_quiz_a_image"><img src="%s" /></div>', $this->answerImageList[ $answerValue ] );
					}

					// Generate the ID of the field, also used for the CSS ID
					$fieldID = sprintf( 'question_%d_%s_%d', $parentQuiz->quiz_id, $this->questionType, $this->quizItem->question_id );


					// Generate the ID for the answer field.
					$hiddenFieldID = sprintf( 'answer_%d_%s_%d', $parentQuiz->quiz_id, $this->questionType, $this->quizItem->question_id );

					// Using the value as part of the CSS ID and the label for, to allow clicking on the label to select the parent
					// radio item.
					/*
					 * Get the current question object to check that if the question has multiple answers or not.
					 *
					 */
					$qObj = $parentQuiz->questions[ $this->quizItem->question_id ];
					/*
					 * Display the checkbox if the qution has multipal answers and radio button if the qution has single answer.
					 */

					$answers     = $qObj->question_correct_answer;
					$ansArray    = @unserialize( $answers );
					$flagMultAns = false;
					if ( ( is_array( $ansArray ) && count( $ansArray ) > 1 ) || $qObj->question_multi_checkboxes_enable ) {
						$flagMultAns = true;
					}
					if ( $flagMultAns || $parentQuiz->quiz_type == 'survey' && $this->questionType != 'truefalse' ) {
						if ( is_array( $selectedAnswer ) ) {
							$checked = in_array( $answerValue, $selectedAnswer ) ? 'checked="checked"' : false;
						} else {
							$checked = $selectedAnswer == $answerValue ? 'checked="checked"' : false;
						}
						$html .= sprintf(
							'<li><input type="checkbox" name="%s[]" id="%s_%s" value="%s" %s> <label for="%s_%s">%s</label> %s', $fieldID, // Name
							$fieldID, sanitize_title( $answerValue ), // CSS ID
							$answerValue, // Value
							$checked, // Mark the correct item as checked
							$fieldID, sanitize_title( $answerValue ), // Label for=""
							htmlspecialchars( $answerItem ), // Label value. Ensure encoded in case HTML is used for answers.
							$imageCode                                    // Optional image
						);


						$html .= sprintf(
							'<input type="hidden" name="%s[]" value="%s"></li>',
							$hiddenFieldID, //Answer ID
							$answerValue //Answer Value
						);
					} else {
						if ( is_array( $selectedAnswer ) ) {
							$checked = in_array( $answerValue, $selectedAnswer ) ? 'checked="checked"' : false;
						} else {
							$checked = $selectedAnswer == $answerValue ? 'checked="checked"' : false;
						}
						$html .= sprintf(
							'<li><input type="radio" name="%s" id="%s_%s" value="%s" %s> <label for="%s_%s">%s</label> %s', $fieldID, // Name
							$fieldID, sanitize_title( $answerValue ), // CSS ID
							$answerValue, // Value
							$checked, // Mark the correct item as checked
							$fieldID, sanitize_title( $answerValue ), // Label for=""
							htmlspecialchars( $answerItem ), // Label value. Ensure encoded in case HTML is used for answers.
							$imageCode                                    // Optional image
						);

						$html .= sprintf(
							'<input type="hidden" name="%s[]" value="%s"></li>',
							$hiddenFieldID, //Answer ID
							$answerValue //Answer Value
						);
					}
				}

				$html .= '</ul>';
			}

			return $html;
		}

		/**
		 * Clean the answer data and return it to the user.
		 * Designed to be overridden by child classes to add class-specific functionality.
		 *
		 * @param String $rawData The data that's being cleaned.
		 *
		 * @return String The cleaned data.
		 */
		public static function sanitizeAnswerData( $rawData ) {
			return false;
		}
	}
}
