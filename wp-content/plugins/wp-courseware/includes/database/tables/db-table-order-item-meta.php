<?php
/**
 * WP Courseware Database Table Order Item Meta.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Order_Item_Meta.
 *
 * @since 4.3.0
 */
final class DB_Table_Order_Item_Meta extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_order_itemmeta';

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
		$this->schema = "meta_id bigint(20) unsigned NOT NULL auto_increment,
						 wpcw_order_item_id bigint(20) unsigned NOT NULL default '0',
						 meta_key varchar(255) DEFAULT NULL,
						 meta_value longtext DEFAULT NULL,
						 PRIMARY KEY (meta_id),
						 KEY wpcw_order_item_id (wpcw_order_item_id),
						 KEY meta_key (meta_key(191))";
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
