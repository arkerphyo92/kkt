<?php
/**
 * WP Courseware DB Certificates.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Certificates.
 *
 * @since 4.3.0
 */
class DB_Certificates extends DB {

	/**
	 * Certificates Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'certificates' );
		$this->primary_key = 'cert_user_id';
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
			'cert_user_id'    => '%d',
			'cert_course_id'  => '%d',
			'cert_access_key' => '%s',
			'cert_generated'  => '%s',
		);
	}
}