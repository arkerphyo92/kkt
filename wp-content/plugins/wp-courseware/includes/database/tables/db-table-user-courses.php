<?php
/**
 * WP Courseware Database Table User Courses.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_User_Courses.
 *
 * @since 4.3.0
 */
final class DB_Table_User_Courses extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_user_courses';

	/**
	 * @var int Database Table version
	 * @since 4.3.0
	 */
	protected $version = 450;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.3.0
	 */
	protected function set_schema() {
		$this->schema = "user_id int(11) unsigned NOT NULL,
			             course_id int(11) unsigned NOT NULL,
			             course_progress int(11) NOT NULL DEFAULT '0',
			             course_final_grade_sent VARCHAR(30) NOT NULL DEFAULT '',
			             course_enrolment_date datetime DEFAULT '0000-00-00 00:00:00',
			             UNIQUE KEY user_id (user_id,course_id)";
	}

	/**
	 * Get Upgrades.
	 *
	 * @since 4.5.0
	 */
	protected function get_upgrades() {
		return array(
			'450' => 'upgrade_to_450',
		);
	}

	/**
	 * Upgrade to version 4.5.0
	 *
	 * @since 4.5.0
	 */
	protected function upgrade_to_450() {
		maybe_convert_table_to_utf8mb4( $this->table_name );
	}
}
