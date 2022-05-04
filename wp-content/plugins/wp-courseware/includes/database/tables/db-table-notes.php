<?php
/**
 * WP Courseware Database Notes Table.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Notes.
 *
 * @since 4.3.0
 */
final class DB_Table_Notes extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_notes';

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
		$this->schema = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						 object_id bigint(20) unsigned NOT NULL DEFAULT '0',
						 object_type varchar(20) DEFAULT NULL,
						 user_id bigint(20) unsigned NOT NULL DEFAULT '0',
						 content longtext NOT NULL,
						 date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 is_public tinyint(1) NOT NULL DEFAULT '0',
						 PRIMARY KEY (id),
						 KEY object_id_type (object_id, object_type),
						 KEY user_id (user_id),
						 KEY date_created (date_created)";
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
