<?php
/**
 * WP Courseware Database Table Modules.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Modules.
 *
 * @since 4.3.0
 */
final class DB_Table_Modules extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_modules';

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
		$this->schema = "module_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			             parent_course_id int(11) unsigned NOT NULL DEFAULT '0',
			             module_author bigint(20) unsigned NOT NULL default '0',
			             module_title text NOT NULL,
			             module_desc longtext NOT NULL,
			             module_order int(11) unsigned NOT NULL DEFAULT '10000',
			             module_number int(11) unsigned NOT NULL DEFAULT '0',
			             PRIMARY KEY  (module_id)";
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

		$module_desc = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'module_desc'" );

		if ( $module_desc ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `module_desc` longtext NOT NULL" );
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

		$module_title = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'module_title'" );

		if ( $module_title ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `module_title` text NOT NULL" );
		}
	}
}
