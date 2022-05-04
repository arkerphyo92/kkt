<?php
/**
 * WP Courseware DB Students Progress.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.1.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Students_Progress.
 *
 * @since 4.1.0
 */
class DB_Students_Progress extends DB {

	/**
	 * Students Progress Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.1.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'user_progress' );
		$this->primary_key = 'user_id';
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
			'user_id'               => '%d',
			'unit_id'               => '%d',
			'unit_completed_date'   => '%s',
			'unit_completed_status' => '%s',
		);
	}
}