<?php
/**
 * WP Courseware Database Logs Table.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Logs.
 *
 * @since 4.3.0
 */
final class DB_Table_Logs extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_logs';

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
		$this->schema = "log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						 object_id bigint(20) unsigned NOT NULL DEFAULT '0',
						 object_type varchar(20) DEFAULT NULL,
						 type varchar(30) DEFAULT NULL,
						 title text DEFAULT NULL,
						 message longtext,
						 date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 PRIMARY KEY (log_id),
						 KEY object_id_type (object_id, object_type),
						 KEY type (type),
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
			'467' => 'upgrade_to_467',
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
	 * Upgrade to version 4.6.7
	 *
	 * @since 4.6.7
	 */
	protected function upgrade_to_467() {
		global $wpdb;

		$title = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'title'" );

		if ( $title ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `title` text DEFAULT NULL" );
		}
	}
}
