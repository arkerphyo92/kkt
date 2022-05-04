<?php
/**
 * WP Courseware Database Table Question Random Lock.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Question_Random_Lock.
 *
 * @since 4.3.0
 */
final class DB_Table_Question_Random_Lock extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_question_random_lock';

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
		$this->schema = "question_user_id int(11) unsigned NOT NULL,
						 rand_question_id int(11) unsigned NOT NULL,
						 parent_unit_id int(11) unsigned NOT NULL,
						 question_selection_list text NOT NULL,
						 UNIQUE KEY wpcw_question_rand_lock (question_user_id,rand_question_id)";
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
