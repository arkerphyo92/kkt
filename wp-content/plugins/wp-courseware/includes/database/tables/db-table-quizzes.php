<?php
/**
 * WP Courseware Database Table Quizzes.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Quizzes.
 *
 * @since 4.3.0
 */
final class DB_Table_Quizzes extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_quizzes';

	/**
	 * @var int Database Table version
	 * @since 4.3.0
	 */
	protected $version = 467;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.3.0
	 */
	protected function set_schema() {
		$this->schema = "quiz_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			             quiz_title text NOT NULL,
			             quiz_desc longtext NOT NULL,
			             quiz_author bigint(20) unsigned NOT NULL default '0',
			             parent_unit_id int(11) unsigned NOT NULL DEFAULT '0',
			             parent_course_id int(11) NOT NULL DEFAULT '0',
			             quiz_type varchar(15) NOT NULL,
			             quiz_pass_mark int(11) NOT NULL DEFAULT '0',
			             quiz_show_answers varchar(15) NOT NULL DEFAULT 'no_answers',
			             quiz_show_survey_responses varchar(15) NOT NULL DEFAULT 'no_responses',
			             quiz_attempts_allowed int(11) NOT NULL DEFAULT '-1',
			             show_answers_settings VARCHAR(500) NOT NULL DEFAULT '',
			             quiz_paginate_questions VARCHAR(15) NOT NULL DEFAULT 'no_paging',
			             quiz_paginate_questions_settings VARCHAR(500) NOT NULL DEFAULT '',
			             quiz_timer_mode varchar(25) NOT NULL DEFAULT 'no_timer',
			             quiz_timer_mode_limit int(11) unsigned NOT NULL DEFAULT '15',
			             quiz_results_downloadable varchar(10) NOT NULL DEFAULT 'on',
			             quiz_results_by_tag varchar(10) NOT NULL DEFAULT 'on',
			             quiz_results_by_timer varchar(10) NOT NULL DEFAULT 'on',
			             quiz_recommended_score varchar(20) NOT NULL DEFAULT 'no_recommended',
			             show_recommended_percentage int(10) unsigned NOT NULL DEFAULT 50,
			             PRIMARY KEY  (quiz_id)";
	}

	/**
	 * Get Upgrades.
	 *
	 * @since 4.4.0
	 */
	protected function get_upgrades() {
		return array(
			'440' => 'upgrade_to_440',
			'450' => 'upgrade_to_450',
			'467' => 'upgrade_to_467',
		);
	}

	/**
	 * Upgrade to version 4.4.0
	 *
	 * @since 4.4.0
	 */
	protected function upgrade_to_440() {
		global $wpdb;

		$quiz_desc = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'quiz_desc'" );

		if ( $quiz_desc ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `quiz_desc` longtext NOT NULL" );
		}
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
	 * Upgrade to version 4.6.7
	 *
	 * @since 4.6.7
	 */
	protected function upgrade_to_467() {
		global $wpdb;

		$quiz_title = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'quiz_title'" );

		if ( $quiz_title ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `quiz_title` text NOT NULL" );
		}
	}
}
