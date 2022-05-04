<?php
/**
 * WP Courseware Questions Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use Exception;
use WPCW\Database\DB_Questions;
use WPCW\Models\Question;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Questions.
 *
 * @since 4.2.0
 */
class Questions extends Controller {

	/**
	 * @var DB_Questions The questions db object.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Questions constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Questions();
	}

	/**
	 * Questions Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
	}

	/**
	 * Get Question by Id.
	 *
	 * @since 4.2.0
	 *
	 * @param int $question_id The question id.
	 *
	 * @return bool|Question The quiz object.
	 */
	public function get_question( $question_id ) {
		if ( 0 === absint( $question_id ) ) {
			return false;
		}

		$result = $this->db->get( $question_id );

		if ( ! $result ) {
			return false;
		}

		return new Question( $result );
	}

	/**
	 * Get Quesitons.
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return array Array of question objects.
	 */
	public function get_questions( $args = array(), $raw = false ) {
		$questions = array();
		$results   = $this->db->get_questions( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$questions[] = new Question( $result );
		}

		return $questions;
	}

	/**
	 * Get Number of Questions.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of quesitons.
	 */
	public function get_questions_count( $args = array() ) {
		return $this->db->get_questions( $args, true );
	}

	/**
	 * Get Question Tags.
	 *
	 * @since 4.2.0
	 *
	 * @param int $id The Question Id.
	 *
	 * @return array An array of question tabgs.
	 */
	public function get_question_tags( $id ) {
		return $this->db->get_question_tags( $id );
	}

	/**
	 * Delete Question.
	 *
	 * @since 4.2.0
	 *
	 * @param int $question_id The question id.
	 *
	 * @return Question|false The question object or false.
	 */
	public function delete_question( $question_id ) {
		if ( ! is_admin() || ! current_user_can( 'view_wpcw_courses' ) ) {
			return false;
		}

		$current_user   = wp_get_current_user();
		$has_permission = false;

		if ( $question = $this->get_question( $question_id ) ) {
			global $wpdb, $wpcwdb;

			// Check permissions, this condition allows admins to view all questions even if they are not the author.
			if ( user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
				$has_permission = true;
			}

			// Check Author
			if ( $current_user->ID == $question->get_question_author() ) {
				$has_permission = true;
			}

			// Back compat filter
			$has_permission = apply_filters( 'wpcw_back_permissions_user_can_delete_question', $has_permission, $current_user->ID, $question );

			// Add filters to override
			$has_permission = apply_filters( 'wpcw_permissions_user_can_edit_question', $has_permission, $current_user, $question );

			// Get out if no access.
			if ( ! $has_permission ) {
				return false;
			}

			// Question Id.
			$question_id = $question->get_question_id();

			// Get tags for questions
			$tags = $this->get_question_tags( $question_id );

			// Remove tags for each question
			foreach ( $tags as $tag ) {
				WPCW_questions_tags_removeTag( $question_id, $tag->question_tag_id );
			}

			// Delete question from question map
			$wpdb->query( $wpdb->prepare( "
				DELETE FROM $wpcwdb->quiz_qs_mapping
				WHERE question_id = %d
			", $question_id ) );

			// Finally delete question itself
			$wpdb->query( $wpdb->prepare( "
				DELETE FROM $wpcwdb->quiz_qs
				WHERE question_id = %d
			", $question_id ) );

			return $question;
		}

		return false;
	}

	/**
	 * Get Question Type Name.
	 *
	 * @since 4.2.0
	 *
	 * @param string $type The type of the quiz question.
	 *
	 * @return string The question type as a label.
	 */
	public function get_question_type_name( $type ) {
		$question_type = esc_html__( 'N/A', 'wp-courseware' );

		switch ( $type ) {
			case 'truefalse':
				$question_type = esc_html__( 'True/False', 'wp-courseware' );
				break;

			case 'multi':
				$question_type = esc_html__( 'Multiple Choice', 'wp-courseware' );
				break;

			case 'upload':
				$question_type = esc_html__( 'File Upload', 'wp-courseware' );
				break;

			case 'open':
				$question_type = esc_html__( 'Open Ended', 'wp-courseware' );
				break;

			case 'random_selection':
				$question_type = esc_html__( 'Random Selection', 'wp-courseware' );
				break;
		}

		return $question_type;
	}

	/**
	 * Get Question Types.
	 *
	 * @since 4.2.0
	 *
	 * @return array An array of question type slug => label.
	 */
	public function get_question_types() {
		return apply_filters( 'wpcw_question_types', array(
			'multi'     => esc_html__( 'Multiple Choice', 'wp-courseware' ),
			'truefalse' => esc_html__( 'True / False', 'wp-courseware' ),
			'open'      => esc_html__( 'Open Ended', 'wp-courseware' ),
			'upload'    => esc_html__( 'File Upload', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Add Question Dropdown.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args The dropdown args.
	 *
	 * @return string The question type dropdown.
	 */
	public function get_add_question_dropdown_form( array $args = array() ) {
		$question_types = $this->get_question_types();

		if ( empty( $question_types ) ) {
			return;
		}

		$defaults = array(
			'placeholder'  => esc_html__( 'Select Question Type', 'wp-courseware' ),
			'button_text'  => esc_html__( 'Add New', 'wp-courseware' ),
			'button_class' => 'page-title-action',
		);

		$args = wp_parse_args( $args, $defaults );

		$add_new_question_url = esc_url( add_query_arg( array( 'page' => 'WPCW_showPage_ModifyQuestion' ), admin_url( 'admin.php' ) ) );

		$html = sprintf( '<form class="questions-action-form" method="POST" action="%s">', $add_new_question_url );
		$html .= sprintf( '<select name="question_type" class="question-type-select" placeholder="%s">', $args['placeholder'] );
		$html .= sprintf( '<option value="multi" selected="selected">%s</option>', $args['placeholder'] );
		foreach ( $question_types as $key => $type ) {
			$html .= sprintf( '<option value="%s">%s</option>', $key, $type );
		}
		$html .= '</select>';
		$html .= sprintf( '<input type="submit" class="%s" value="%s" />', $args['button_class'], $args['button_text'] );
		$html .= '</form>';

		return $html;
	}

	/**
	 * Get Tags Filter Dropdown.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args The dropdown args. Can be used to customize the dropdown.
	 *
	 * @return string The HTML to render the tag filtering code.
	 */
	public function get_tags_filter_by_dropdown( array $args = array() ) {
		$defaults = array(
			'placeholder' => esc_html__( '-- View All Tags --', 'wp-courseware' ),
			'name'        => 'question_tag_id',
			'classes'     => array(),
			'selected'    => isset( $_GET['question_tag'] ) ? absint( $_GET['question_tag'] ) : '',
		);

		$args = wp_parse_args( $args, $defaults );

		$form = '';

		$form .= WPCW_questions_tags_getTagDropdown( 'question_tag', $args['selected'], 'wpcw_questions_tag_filter',  __( '-- View All Tags --', 'wp-courseware' ) );

		return $form;
	}

	/**
	 * Process Action Tags.
	 *
	 * @since 4.2.0
	 */
	public function process_action_tags() {
		global $wpdb, $wpcwdb;
		$wpdb->show_errors();

		if ( ! isset( $_GET['wpcw_bulk_action_actions'] ) ) {
			return false;
		}

		if ( empty( $_GET['question_id'] ) ) {
			return false;
		}

		// #### #1 - Get a list of the questions to update
		$questionListToUpdate = array();

		foreach ( $_GET['question_id'] as $key => $question_id ) {
			$questionListToUpdate[] = $question_id;
		}

		// Appears there's nothing to do.
		if ( empty( $questionListToUpdate ) ) {
			wpcw_add_admin_notice_error( __( 'Error. Please select some questions to update.', 'wp-courseware' ) );

			return false;
		}

		// #### #2 - Validate that the questions do indeed exist
		// Direct SQL is ok here, as IDs have been validated with the regex previously.
		$questionListStr    = implode( ',', $questionListToUpdate );
		$validatedQuestions = $wpdb->get_col( "
			SELECT *
			FROM $wpcwdb->quiz_qs
			WHERE question_id IN ($questionListStr)
		" );

		// Appears there's nothing to do, as questions do not validate.
		if ( empty( $questionListToUpdate ) ) {
			wpcw_add_admin_notice_error( __( 'Error. Those questions no longer exist. Please select some more questions to update.', 'wp-courseware' ) );

			return;
		}

		// #### #3 - Check that the action is what we're expecting.
		$actionToProcess = WPCW_arrays_getValue( $_GET, 'wpcw_bulk_action_actions' );

		switch ( $actionToProcess ) {
			case 'add_tag':
			case 'remove_tag':
			case 'replace_tag':
				break;

			default:
				wpcw_add_admin_notice_error( __( 'Error. Did not recognise action to apply to selected questions.', 'wp-courseware' ) );

				return;
				break;
		}

		// #### #4 - check permissions and unset what questions should be updated.
		$bulk_question_counter = 0;
		foreach ( $validatedQuestions as $questionId ) {
			// Vars
			$canBulkEditQuestion = false;
			$current_user        = wp_get_current_user();

			// Check permissions, this condition allows admins to view all modules even if they are not the author.
			if ( user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
				$canBulkEditQuestion = true;
			}

			// Question author
			$question_author_id = $wpdb->get_var( $wpdb->prepare( "SELECT question_author FROM $wpcwdb->quiz_qs WHERE question_id = %d", $questionId ) );

			// Check Author
			if ( $question_author_id == $current_user->ID ) {
				$canBulkEditQuestion = true;
			}

			// Back compat filter
			$canBulkEditQuestion = apply_filters( 'wpcw_back_permissions_user_can_bulkedit_question', $canBulkEditQuestion, $current_user->ID, $questionId );

			// Add filters to override
			$canBulkEditQuestion     = apply_filters( 'wpcw_permissions_user_can_bulkedit_question', $canBulkEditQuestion, $current_user, $questionId );
			$cantBulkEditQuestionMsg = apply_filters( 'wpcw_permissions_user_can_bulkedit_question_msg', sprintf( esc_attr__( 'You are not permitted to bulk edit Question ID: %s.', 'wp-courseware' ), $questionId ), $current_user, $questionId );

			// Display message if no access.
			if ( ! $canBulkEditQuestion ) {
				wpcw_add_admin_notice_error( $cantBulkEditQuestionMsg );
				unset( $validatedQuestions[ $bulk_question_counter ] );
			}

			// Increment Counter
			$bulk_question_counter ++;
		}

		// #### #4 - Check that we have the tags that we're expecting.
		$tagID_first  = WPCW_arrays_getValue( $_GET, 'wpcw_bulk_action_select_tag_a', 0 );
		$tagID_second = WPCW_arrays_getValue( $_GET, 'wpcw_bulk_action_select_tag_b', 0 );

		$tagDetails_first  = false;
		$tagDetails_second = false;

		if ( ! $tagDetails_first = WPCW_questions_tags_getTagDetails( $tagID_first ) ) {
			wpcw_add_admin_notice_error( __( 'Error. The first tag does not exist. Please select another tag.', 'wp-courseware' ) );

			return;
		}

		// Check replace tag requirements
		if ( 'replace_tag' == $actionToProcess ) {
			// No 2nd tag
			if ( ! $tagDetails_second = WPCW_questions_tags_getTagDetails( $tagID_second ) ) {
				wpcw_add_admin_notice_error( __( 'Error. The second tag does not exist. Please select another tag.', 'wp-courseware' ) );

				return;
			}

			// 1st and 2nd tags match
			if ( $tagDetails_first->question_tag_id == $tagDetails_second->question_tag_id ) {
				wpcw_add_admin_notice_error( __( 'Error. The first and second tag should be different.', 'wp-courseware' ) );

				return;
			}
		}

		// #### #5 - By this point, everything is validated, so just execute the SQL.
		foreach ( $validatedQuestions as $questionID ) {
			switch ( $actionToProcess ) {
				case 'add_tag':
					$wpdb->query( $wpdb->prepare( "
						INSERT IGNORE $wpcwdb->question_tag_mapping
						(question_id, tag_id)
						VALUES (%d, %d)
					", $questionID, $tagDetails_first->question_tag_id ) );
					break;

				case 'remove_tag':
					$wpdb->query( $wpdb->prepare( "
						DELETE FROM $wpcwdb->question_tag_mapping
						WHERE question_id = %d
					  	AND tag_id = %d
					", $questionID, $tagDetails_first->question_tag_id ) );
					break;

				case 'replace_tag':
					$wpdb->query( $wpdb->prepare( "
						UPDATE $wpcwdb->question_tag_mapping
					  	SET tag_id = %d
						WHERE question_id = %d
					  	AND tag_id = %d
					", $tagDetails_second->question_tag_id, $questionID, $tagDetails_first->question_tag_id ) );
					break;
			}
		}

		// Need to update tag counts
		WPCW_questions_tags_updatePopularity( $tagDetails_first->question_tag_id );

		// 2nd is optional, so just need to check it exists first before trying update to prevent
		// an error message.
		if ( $tagDetails_second ) {
			WPCW_questions_tags_updatePopularity( $tagDetails_second->question_tag_id );
		}

		// #### #6 Finally show message
		wpcw_add_admin_notice_success( __( 'Questions successfully updated.', 'wp-courseware' ) );
	}

	/**
	 * Delete Orphaned Question Tags.
	 *
	 * @since 4.3.0
	 */
	public function delete_orphaned_question_tags() {
		global $wpdb, $wpcwdb;

		try {
			$wpdb->query( "DELETE FROM $wpcwdb->question_tags WHERE question_tag_usage = 0" );
		} catch ( Exception $exception ) {
			$this->log( $exception->getMessage(), false );

			return false;
		}

		return true;
	}
}
