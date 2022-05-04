<?php
/**
 * WP Courseware Database Table Quizzes Questions Map.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Quizzes_Questions_Map.
 *
 * @since 4.3.0
 */
final class DB_Table_Quizzes_Questions_Map extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_quizzes_questions_map';

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
		$this->schema = "parent_quiz_id bigint(20) unsigned NULL,
						 question_id bigint(20) unsigned NOT NULL,
						 question_order int(11) unsigned NOT NULL DEFAULT '0',
						 UNIQUE KEY question_assoc_id (parent_quiz_id,question_id)";
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
