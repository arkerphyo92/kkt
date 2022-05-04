<?php
/**
 * WP Courseware DB Question Tags.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Question_Tags.
 *
 * @since 4.3.0
 */
class DB_Question_Tags extends DB {

	/**
	 * Certificates Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'question_tags' );
		$this->primary_key = 'question_tag_id';
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
			'question_tag_id'     => '%d',
			'question_tag_name'   => '%s',
			'question_tag_usage'  => '%d',
			'question_tag_author' => '%d',
		);
	}
}