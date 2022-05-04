<?php
/**
 * WP Courseware DB Quizzzes Question Map.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.2.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Questions_Map.
 *
 * @since 4.2.0
 */
class DB_Quizzes_Question_Map extends DB {

	/**
	 * Questions Map Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.2.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'quizzes_questions_map' );
		$this->primary_key = 'parent_quiz_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'parent_quiz_id' => '%d',
			'question_id'    => '%d',
			'question_order' => '%d',
		);
	}
}