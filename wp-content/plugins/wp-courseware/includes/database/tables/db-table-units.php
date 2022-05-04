<?php
/**
 * WP Courseware Database Table Units.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.6.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Units.
 *
 * @since 4.6.0
 */
final class DB_Table_Units extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_units_meta';

	/**
	 * @var int Database Table version
	 * @since 4.3.0
	 */
	protected $version = 460;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.3.0
	 */
	protected function set_schema() {
		$this->schema = "unit_id int(11) unsigned NOT NULL,
			             parent_module_id int(11) unsigned NOT NULL DEFAULT '0',
			             parent_course_id int(11) unsigned NOT NULL DEFAULT '0',
			             unit_author bigint(20) unsigned NOT NULL default '0',
			             unit_order int(11) unsigned NOT NULL DEFAULT '0',
			             unit_number int(11) unsigned NOT NULL DEFAULT '0',
			             unit_drip_type varchar(50) NOT NULL DEFAULT '',
			             unit_drip_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			             unit_drip_interval int(11) NOT NULL DEFAULT '432000',
			             unit_drip_interval_type varchar(15) NOT NULL DEFAULT 'interval_days',
			             unit_teaser int(11) unsigned NOT NULL DEFAULT '0',
			             PRIMARY KEY  (unit_id)";
	}

	/**
	 * Get Upgrades.
	 *
	 * @since 4.5.0
	 */
	protected function get_upgrades() {
		return array(
			'450' => 'upgrade_to_450',
			'460' => 'upgrade_to_460',
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
	 * Upgrade to version 4.6.0
	 *
	 * @since 4.6.0
	 */
	protected function upgrade_to_460() {
		global $wpdb;

		$unit_teaser = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'unit_teaser'" );

		if ( ! $unit_teaser ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `unit_teaser` int(11) NOT NULL DEFAULT '0'" );
		}
	}
}
