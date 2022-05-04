<?php
/**
 * WP Courseware Database Table Order Items.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Order_Items.
 *
 * @since 4.3.0
 */
final class DB_Table_Order_Items extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_order_items';

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
		$this->schema = "order_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						 order_id bigint(20) unsigned NOT NULL DEFAULT '0',
						 course_id bigint(20) NOT NULL DEFAULT '0',
						 order_item_title text NOT NULL DEFAULT '',
						 order_item_index bigint(20) unsigned NOT NULL DEFAULT '0',
						 type varchar(20) NOT NULL DEFAULT 'course',
						 qty bigint(20) unsigned NOT NULL DEFAULT '0',
						 amount varchar(100) NOT NULL DEFAULT '0',
						 subtotal varchar(100) NOT NULL DEFAULT '0',
						 discount varchar(100) NOT NULL DEFAULT '0',
						 tax varchar(100) NOT NULL DEFAULT '0',
						 total varchar(100) NOT NULL DEFAULT '0',
						 use_installments tinyint(1) NOT NULL DEFAULT '0',
						 is_recurring tinyint(1) NOT NULL DEFAULT '0',
						 PRIMARY KEY (order_item_id),
						 KEY order_id (order_id),
						 KEY course_id (course_id),
						 KEY type (type),
						 KEY order_course_id (order_id, course_id)";
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

		// Installment Plan
		$use_installments = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'use_installments'" );

		if ( ! $use_installments ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `use_installments` tinyint(1) NOT NULL DEFAULT '0'" );
		}
	}
}
