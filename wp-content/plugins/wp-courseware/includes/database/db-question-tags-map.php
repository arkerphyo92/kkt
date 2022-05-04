<?php
/**
 * WP Courseware DB Question Tags Map.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Question_Tags_Map.
 *
 * @since 4.3.0
 */
class DB_Question_Tags_Map extends DB {

	/**
	 * Certificates Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'question_tags_map' );
		$this->primary_key = 'question_id';
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
			'question_id' => '%d',
			'tag_id'      => '%d',
		);
	}
}