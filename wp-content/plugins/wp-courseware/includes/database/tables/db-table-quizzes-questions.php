<?php
/**
 * WP Courseware Database Table Quizzes Questions.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */

namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Quizzes_Questions.
 *
 * @since 4.3.0
 */
final class DB_Table_Quizzes_Questions extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_quizzes_questions';

	/**
	 * @var int Database Table version
	 * @since 4.3.0
	 */
	protected $version = 464;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.3.0
	 */
	protected function set_schema() {
		$this->schema = "question_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			             question_author bigint(20) unsigned NOT NULL default '0',
			             question_type VARCHAR(20) NOT NULL DEFAULT 'multi',
			             question_question text NULL,
			             question_answers text NULL,
			             question_data_answers text NULL,
			             question_correct_answer VARCHAR(300) NOT NULL,
			             question_answer_type VARCHAR(50) NOT NULL DEFAULT '',
			             question_answer_hint text NULL,
			             question_answer_explanation text NULL,
			             question_image VARCHAR(300) NOT NULL DEFAULT '',
			             question_answer_file_types VARCHAR(300) NOT NULL DEFAULT '',
			             question_usage_count int(11) UNSIGNED DEFAULT 0,
			             question_expanded_count int(11) UNSIGNED DEFAULT 1,
			             question_multi_random_enable int(2) UNSIGNED DEFAULT 0,
			             question_multi_random_count  int(4) UNSIGNED DEFAULT 5,
			             question_multi_checkboxes_enable int(2) UNSIGNED DEFAULT 0,
			             PRIMARY KEY  (question_id)";
	}

	/**
	 * Get Upgrades.
	 *
	 * @since 4.5.0
	 */
	protected function get_upgrades() {
		return array(
			'450' => 'upgrade_to_450',
			'464' => 'upgrade_to_464'
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

	/**
	 * Upgrade to version 4.6.4
	 *
	 * @since 4.6.4
	 */
	protected function upgrade_to_464() {
		global $wpdb;

		$checkboxes_enable = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'question_multi_checkboxes_enable'" );

		if ( ! $checkboxes_enable ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `question_multi_checkboxes_enable` int(2) UNSIGNED DEFAULT 0" );
		}
	}
}
