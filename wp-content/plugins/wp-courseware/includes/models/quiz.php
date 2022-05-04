<?php
/**
 * WP Courseware Quiz Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.2.0
 */

namespace WPCW\Models;

use WPCW\Database\DB_Quizzes;
use WPCW\Database\DB_Students_Progress_Quiz;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Quiz.
 *
 * @since 4.2.0
 *
 * @property int    $quiz_id
 * @property string $quiz_title
 * @property string $quiz_desc
 * @property int    $quiz_author
 * @property int    $parent_unit_id
 * @property int    $parent_course_id
 * @property string $quiz_type
 * @property int    $quiz_pass_mark
 * @property string $quiz_show_answers
 * @property string $quiz_show_survey_responses
 * @property int    $quiz_attempts_allowed
 * @property string $show_answers_settings
 * @property string $quiz_paginate_questions
 * @property string $quiz_paginate_questions_settings
 * @property string $quiz_timer_mode
 * @property int    $quiz_timer_mode_limit
 * @property string $quiz_results_downloadable
 * @property string $quiz_results_by_tag
 * @property string $quiz_results_by_timer
 * @property string $quiz_recommended_score
 * @property int    $show_recommended_percentage
 */
class Quiz extends Model {

	/**
	 * @var DB_Quizzes The quizzes database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var DB_Students_Progress_Quiz The quizzes student progress.
	 * @since 4.4.0
	 */
	protected $db_spq;

	/**
	 * @var int Quiz Id.
	 * @since 4.2.0
	 */
	protected $quiz_id;

	/**
	 * @var string Quiz Title.
	 * @since 4.2.0
	 */
	protected $quiz_title = '';

	/**
	 * @var string Quiz Description.
	 * @since 4.2.0
	 */
	protected $quiz_desc = '';

	/**
	 * @var int Quiz Author.
	 * @since 4.2.0
	 */
	protected $quiz_author = 0;

	/**
	 * @var int Quiz Unit Id.
	 * @since 4.2.0
	 */
	protected $parent_unit_id = 0;

	/**
	 * @var int Quiz Course Id.
	 * @since 4.2.0
	 */
	protected $parent_course_id = 0;

	/**
	 * @var string Quiz Type.
	 * @since 4.2.0
	 */
	protected $quiz_type = '';

	/**
	 * @var int Quiz Pass Mark.
	 * @since 4.2.0
	 */
	protected $quiz_pass_mark = 0;

	/**
	 * @var string Quiz show answers.
	 * @since 4.2.0
	 */
	protected $quiz_show_answers = 'no_answers';

	/**
	 * @var string Quiz show survey responses.
	 * @since 4.2.0
	 */
	protected $quiz_show_survey_responses = 'no_responses';

	/**
	 * @var int Quiz attempts allowed.
	 * @since 4.2.0
	 */
	protected $quiz_attempts_allowed = - 1;

	/**
	 * @var string Quiz show answer settings.
	 * @since 4.2.0
	 */
	protected $show_answers_settings = '';

	/**
	 * @var string Quiz paginate quesitons.
	 * @since 4.2.0
	 */
	protected $quiz_paginate_questions = 'no_paging';

	/**
	 * @var string Quiz paginate questions settings.
	 * @since 4.2.0
	 */
	protected $quiz_paginate_questions_settings = '';

	/**
	 * @var string Quiz timer mode.
	 * @since 4.2.0
	 */
	protected $quiz_timer_mode = 'no_timer';

	/**
	 * @var int Quiz timer mode limit.
	 * @since 4.2.0
	 */
	protected $quiz_timer_mode_limit = 15;

	/**
	 * @var string Quiz results downloadable.
	 * @since 4.2.0
	 */
	protected $quiz_results_downloadable = 'on';

	/**
	 * @var string Quiz results by tag.
	 * @since 4.2.0
	 */
	protected $quiz_results_by_tag = 'on';

	/**
	 * @var string Quiz results by timer.
	 * @since 4.2.0
	 */
	protected $quiz_results_by_timer = 'on';

	/**
	 * @var string Quiz recommended score.
	 * @since 4.2.0
	 */
	protected $quiz_recommended_score = 'no_recommended';

	/**
	 * @var int Quiz recommended percentage.
	 * @since 4.2.0
	 */
	protected $show_recommended_percentage = 50;

	/**
	 * Quiz Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db     = new DB_Quizzes();
		$this->db_spq = new DB_Students_Progress_Quiz();
		parent::__construct( $data );
	}

	/**
	 * Get Quiz Defaults
	 *
	 * @since 4.4.0
	 *
	 * @return array The quiz model defaults
	 */
	public function get_defaults() {
		return array(
			'quiz_title'                       => '',
			'quiz_desc'                        => '',
			'quiz_author'                      => get_current_user_id(),
			'parent_unit_id'                   => 0,
			'parent_course_id'                 => 0,
			'quiz_type'                        => 'quiz_noblock',
			'quiz_pass_mark'                   => 50,
			'quiz_show_answers'                => 'show_answers',
			'quiz_show_survey_responses'       => 'no_responses',
			'quiz_attempts_allowed'            => - 1,
			'show_answers_settings'            => array(
				'show_correct_answer'         => 'on',
				'show_user_answer'            => 'on',
				'show_explanation'            => 'on',
				'mark_answers'                => 'on',
				'show_results_later'          => 'on',
				'show_other_possible_answers' => 'off',
			),
			'quiz_paginate_questions'          => 'no_paging',
			'quiz_paginate_questions_settings' => array(
				'allow_review_before_submission' => 'on',
				'allow_students_to_answer_later' => 'on',
				'allow_nav_previous_questions'   => 'on',
			),
			'quiz_timer_mode'                  => 'no_timer',
			'quiz_timer_mode_limit'            => 15,
			'quiz_results_downloadable'        => 'on',
			'quiz_results_by_tag'              => 'on',
			'quiz_results_by_timer'            => 'on',
			'quiz_recommended_score'           => 'no_recommended',
			'show_recommended_percentage'      => 50,
		);
	}

	/**
	 * Get Quiz Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int|void
	 */
	public function get_id() {
		return absint( $this->get_quiz_id() );
	}

	/**
	 * Get quiz id.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_quiz_id() {
		return absint( $this->quiz_id );
	}

	/**
	 * Get quiz title.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_title() {
		return $this->quiz_title;
	}

	/**
	 * Get quiz description.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_desc() {
		return wp_kses_post( $this->quiz_desc );
	}

	/**
	 * Get quiz author.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_quiz_author() {
		return absint( $this->quiz_author );
	}

	/**
	 * Get parent unit id.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_parent_unit_id() {
		return absint( $this->parent_unit_id );
	}

	/**
	 * Get parent course id.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_parent_course_id() {
		return absint( $this->parent_course_id );
	}

	/**
	 * Get quiz type.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_type() {
		return $this->quiz_type;
	}

	/**
	 * Get quiz pass mark.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_quiz_pass_mark() {
		return $this->quiz_pass_mark;
	}

	/**
	 * Get quiz show answers.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_show_answers() {
		return $this->quiz_show_answers;
	}

	/**
	 * Get quiz show survey responses.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_show_survey_responses() {
		return $this->quiz_show_survey_responses;
	}

	/**
	 * Get quiz attempts allowed.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_quiz_attempts_allowed() {
		return $this->quiz_attempts_allowed;
	}

	/**
	 * Get show answers settings.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_show_answers_settings() {
		return $this->show_answers_settings;
	}

	/**
	 * Get quiz paginate quesitons.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_paginate_questions() {
		return $this->quiz_paginate_questions;
	}

	/**
	 * Get quiz paginate settings.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_paginate_questions_settings() {
		return $this->quiz_paginate_questions_settings;
	}

	/**
	 * Get quiz timer mode.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_timer_mode() {
		return $this->quiz_timer_mode;
	}

	/**
	 * Get quiz timer mode limit.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_quiz_timer_mode_limit() {
		return $this->quiz_timer_mode_limit;
	}

	/**
	 * Get quiz results downloadable.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_results_downloadable() {
		return $this->quiz_results_downloadable;
	}

	/**
	 * Get quiz results by tag.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_results_by_tag() {
		return $this->quiz_results_by_tag;
	}

	/**
	 * Get quiz results by timer.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_results_by_timer() {
		return $this->quiz_results_by_timer;
	}

	/**
	 * Get quiz recommended score.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_quiz_recommended_score() {
		return $this->quiz_recommended_score;
	}

	/**
	 * Get show recommended percentage.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_show_recommended_percentage() {
		return $this->show_recommended_percentage;
	}

	/**
	 * Get Edit Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The edit quiz url.
	 */
	public function get_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_quiz_get_edit_url', add_query_arg( array(
			'page'    => 'WPCW_showPage_ModifyQuiz',
			'quiz_id' => $this->get_quiz_id(),
		), admin_url( 'admin.php' ) ), $this ) );
	}

	/**
	 * Update Student Progress Quiz Association.
	 *
	 * @since 4.4.0
	 *
	 * @bool True if update is successful. False otherwise.
	 */
	public function update_spq_association() {
		return $this->db_spq->update( array( 'unit_id' => $this->get_parent_unit_id(), 'quiz_id' => $this->get_id() ) );
	}

	/**
	 * Disconnect Quiz.
	 *
	 * This will make the quiz disconnected from any unit.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if it is successfully unassociated.
	 */
	public function disconnect() {
		global $wpdb, $wpcwdb;

		// Set Properties.
		$this->set_prop( 'parent_unit_id', 0 );
		$this->set_prop( 'parent_course_id', 0 );

		// Delete User Progress
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_progress_quiz WHERE quiz_id = %d", $this->get_id() ) );

		// Save Unit.
		$this->save();
	}

	/**
	 * Update Author.
	 *
	 * This will also update all associated questions author.
	 *
	 * @param int  $user_id The user id.
	 * @param bool $update_questions Update the questions author. Default is true.
	 * @param
	 */
	public function update_author( $author_id, $update_questions = true, $update_tags = true ) {
		global $wpdb, $wpcwdb;

		$this->set_prop( 'quiz_author', absint( $author_id ) );

		if ( $update_questions ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpcwdb->quiz_qs} qq 
			     LEFT JOIN {$wpcwdb->quiz_qs_mapping} qqm ON qqm.question_id = qq.question_id
			     SET qq.question_author = %d
			     WHERE qqm.parent_quiz_id = %d",
				absint( $author_id ),
				$this->get_id()
			) );

			if ( $update_tags ) {
				$question_ids = $wpdb->get_col( $wpdb->prepare(
					"SELECT qqm.question_id
				     FROM {$wpcwdb->quiz_qs_mapping} qqm
				     LEFT JOIN {$wpcwdb->quiz_qs} qq ON qq.question_id = qqm.question_id
				     WHERE qqm.parent_quiz_id = %d
				     ORDER BY qqm.question_order ASC",
					$this->get_id()
				) );

				if ( $question_ids ) {
					$wpdb->query( $wpdb->prepare(
						"UPDATE {$wpcwdb->question_tags} qt 
			             LEFT JOIN {$wpcwdb->question_tag_mapping} qtm ON qtm.tag_id = qt.question_tag_id
			             SET qt.question_tag_author = %d
			             WHERE qtm.question_id IN (%s)",
						absint( $author_id ),
						implode( ',', $question_ids )
					) );
				}
			}
		}

		$this->save();
	}

	/**
	 * Delete Quiz.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function delete() {
		global $wpdb, $wpcwdb;

		$deleted = $this->db->delete( $this->get_id() );

		if ( $deleted ) {
			// Delete Quiz Questions from question map.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->quiz_qs_mapping WHERE parent_quiz_id = %d", $this->get_id() ) );

			// Delete User Progress.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_progress_quiz WHERE quiz_id = %d", $this->get_id() ) );

			// Delete Quiz.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->quiz WHERE quiz_id = %d", $this->get_id() ) );
		}

		return $deleted;
	}
}
