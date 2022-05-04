<?php
/**
 * WP Courseware Database Table Orders.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Orders.
 *
 * @since 4.3.0
 */
final class DB_Table_Orders extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_orders';

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
		$this->schema = "order_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
						 order_key varchar(100) NOT NULL,
						 student_id BIGINT UNSIGNED NOT NULL,
						 student_email varchar(200) NOT NULL,
						 student_first_name varchar(100) NOT NULL,
						 student_last_name varchar(100) NOT NULL,
						 billing_address_1 varchar(200) NOT NULL,
						 billing_address_2 varchar(200) NOT NULL,
						 billing_city varchar(100) NOT NULL,
						 billing_state varchar(100) NOT NULL,
						 billing_postcode varchar(100) NOT NULL,
						 billing_country varchar(100) NOT NULL,
						 order_type varchar(100) NOT NULL,
						 order_status varchar(20) NOT NULL DEFAULT 'pending',
						 order_parent_id BIGINT UNSIGNED NOT NULL,
						 subscription_id BIGINT UNSIGNED NOT NULL,
						 payment_method varchar(100) NOT NULL,
						 payment_method_title varchar(100) NOT NULL,
						 discounts varchar(100) NOT NULL DEFAULT 0,
						 subtotal varchar(100) NOT NULL DEFAULT 0,
						 tax varchar(100) NOT NULL DEFAULT 0,
						 total varchar(100) NOT NULL DEFAULT 0,
						 currency varchar(3) NOT NULL,
						 transaction_id varchar(200) NOT NULL,
						 student_ip_address varchar(40) NOT NULL,
						 student_user_agent varchar(200) NOT NULL,
						 created_via varchar(200) NOT NULL,
						 date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 date_completed datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 date_paid datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 cart_hash varchar(32) NOT NULL,
						 PRIMARY KEY  (order_id),
						 UNIQUE KEY order_key (order_key),
						 KEY student_id (student_id),
						 KEY order_type (order_type),
						 KEY order_parent_id (order_parent_id),
						 KEY subscription_id (subscription_id),
						 KEY order_total (total)";
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
