<?php
/**
 * WP Courseware DB Question Locks.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Question_Locks.
 *
 * @since 4.3.0
 */
class DB_Question_Locks extends DB {

	/**
	 * Question Locks Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'question_random_lock' );
		$this->primary_key = 'question_user_id';
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
			'question_user_id'        => '%d',
			'rand_question_id'        => '%d',
			'parent_unit_id'          => '%d',
			'question_selection_list' => '%s',
		);
	}
}