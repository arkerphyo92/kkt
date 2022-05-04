<?php
/**
 * WP Courseware DB Member Levels.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Member_Levels.
 *
 * @since 4.3.0
 */
class DB_Member_Levels extends DB {

	/**
	 * Member Levels Database Constructor.
	 *
	 * Intiate the table name, version, and primary key
	 * and any other associated database table.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'member_levels' );
		$this->primary_key = 'course_id';
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
			'course_id'       => '%d',
			'member_level_id' => '%s',
		);
	}
}