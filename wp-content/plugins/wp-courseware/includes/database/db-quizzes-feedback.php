<?php
/**
 * WP Courseware DB Quizzes Feedback.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Quizzes_Feedback.
 *
 * @since 4.3.0
 */
class DB_Quizzes_Feedback extends DB {

	/**
	 * Quizzes Feedback Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'quizzes_feedback' );
		$this->primary_key = 'qfeedback_id';
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
			'qfeedback_id'          => '%d',
			'qfeedback_tag_id'      => '%d',
			'qfeedback_quiz_id'     => '%d',
			'qfeedback_summary'     => '%s',
			'qfeedback_score_type'  => '%s',
			'qfeedback_score_grade' => '%d',
			'qfeedback_message'     => '%s',
		);
	}
}