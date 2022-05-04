<?php
/**
 * WP Courseware DB Dripfeed.
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
class DB_Dripfeed extends DB {

	/**
	 * Dripfeed Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'queue_dripfeed' );
		$this->primary_key = 'queue_id';
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
			'queue_id'            => '%d',
			'queue_unit_id'       => '%d',
			'queue_course_id'     => '%d',
			'queue_user_id'       => '%d',
			'queue_trigger_date'  => '%s',
			'queue_enqueued_date' => '%s',
		);
	}
}