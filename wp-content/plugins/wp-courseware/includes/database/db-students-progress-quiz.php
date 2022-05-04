<?php
/**
 * WP Courseware DB Students Progress Quiz.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Students_Progress_Quiz.
 *
 * @since 4.3.0
 */
class DB_Students_Progress_Quiz extends DB {

	/**
	 * Students Progress Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'user_progress_quizzes' );
		$this->primary_key = 'user_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'user_id'                      => '%d',
			'unit_id'                      => '%d',
			'quiz_id'                      => '%d',
			'quiz_attempt_id'              => '%d',
			'quiz_completed_date'          => '%s',
			'quiz_started_date'            => '%s',
			'quiz_correct_questions'       => '%s',
			'quiz_grade'                   => '%d',
			'quiz_question_total'          => '%d',
			'quiz_data'                    => '%s',
			'quiz_is_latest'               => '%s',
			'quiz_needs_marking'           => '%d',
			'quiz_needs_marking_list'      => '%s',
			'quiz_next_step_type'          => '%s',
			'quiz_next_step_msg'           => '%s',
			'quiz_paging_status'           => '%s',
			'quiz_paging_next_q'           => '%d',
			'quiz_paging_incomplete'       => '%d',
			'quiz_completion_time_seconds' => '%d',
		);
	}
}