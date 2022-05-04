<?php
/**
 * WP Courseware Database Table Question Tags.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Question_Tags.
 *
 * @since 4.3.0
 */
final class DB_Table_Question_Tags extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_question_tags';

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
		$this->schema = "question_tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						 question_tag_name varchar(150) NOT NULL,
						 question_tag_usage int(11) unsigned NOT NULL,
						 question_tag_author bigint(20) unsigned NOT NULL default '0',
						 PRIMARY KEY  (question_tag_id)";
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
