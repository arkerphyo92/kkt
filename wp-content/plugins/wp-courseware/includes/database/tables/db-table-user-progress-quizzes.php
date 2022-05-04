<?php
/**
 * WP Courseware Database Table User Progress Quizzes.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_User_Progress_Quizzes.
 *
 * @since 4.3.0
 */
final class DB_Table_User_Progress_Quizzes extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_user_progress_quizzes';

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
		$this->schema = "user_id int(11) NOT NULL,
			             unit_id int(11) NOT NULL,
			             quiz_id bigint(20) NOT NULL,
			             quiz_attempt_id int(11) NOT NULL DEFAULT '0',
			             quiz_completed_date datetime NOT NULL,
			             quiz_started_date datetime NOT NULL,
			             quiz_correct_questions int(11) unsigned NOT NULL,
			             quiz_grade FLOAT(8,2) NOT NULL DEFAULT '-1',
			             quiz_question_total int(11) unsigned NOT NULL,
			             quiz_data longtext NULL,
			             quiz_is_latest VARCHAR(50) DEFAULT 'latest',
			             quiz_needs_marking int(11) unsigned NOT NULL DEFAULT '0',
			             quiz_needs_marking_list TEXT NULL,
			             quiz_next_step_type VARCHAR(50) DEFAULT '',
			             quiz_next_step_msg TEXT DEFAULT '',
			             quiz_paging_status VARCHAR(20) NOT NULL DEFAULT 'complete',
			             quiz_paging_next_q int(11) NOT NULL DEFAULT 0,
			             quiz_paging_incomplete int(11) NOT NULL DEFAULT 0,
			             quiz_completion_time_seconds BIGINT NOT NULL DEFAULT 0,
			             UNIQUE KEY unique_progress_item (user_id,unit_id,quiz_id,quiz_attempt_id)";
	}

	/**
	 * Handle Upgrades.
	 *
	 * @since 4.4.0
	 */
	protected function get_upgrades() {
		return array(
			'450' => 'upgrade_to_450',
		);
	}

	/**
	 * Upgrade to version 4.4.0
	 *
	 * @since 4.4.0
	 */
	protected function upgrade_to_440() {
		global $wpdb;

		$wpdb->query( "ALTER TABLE $this->table_name MODIFY `quiz_data` longtext NULL" );
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
