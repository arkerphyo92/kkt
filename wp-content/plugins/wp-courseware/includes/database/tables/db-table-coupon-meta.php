<?php
/**
 * WP Courseware Database Table Coupon Meta.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.5.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Coupon_Meta.
 *
 * @since 4.5.0
 */
final class DB_Table_Coupon_Meta extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.5.0
	 */
	protected $name = 'wpcw_couponmeta';

	/**
	 * @var int Database Table version
	 * @since 4.5.0
	 */
	protected $version = 450;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.5.0
	 */
	protected function set_schema() {
		$this->schema = "meta_id bigint(20) unsigned NOT NULL auto_increment,
						 wpcw_coupon_id bigint(20) unsigned NOT NULL default '0',
						 meta_key varchar(255) DEFAULT NULL,
						 meta_value longtext DEFAULT NULL,
						 PRIMARY KEY (meta_id),
						 KEY wpcw_coupon_id (wpcw_coupon_id),
						 KEY meta_key (meta_key(191))";
	}
}
