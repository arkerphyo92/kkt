<?php
/**
 * WP Courseware Database Coupons Table.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.5.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Coupons.
 *
 * @since 4.5.0
 */
final class DB_Table_Coupons extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.4.0
	 */
	protected $name = 'wpcw_coupons';

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
		$this->schema = "coupon_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						 code varchar(50) DEFAULT NULL,
						 amount mediumtext DEFAULT NULL,
						 type varchar(30) DEFAULT NULL,
						 usage_count bigint(20) unsigned NOT NULL DEFAULT '0',
						 usage_limit bigint(20) unsigned NOT NULL DEFAULT '0',
						 usage_limit_per_user bigint(20) unsigned NOT NULL DEFAULT '0',
						 individual_use int(1) NOT NULL DEFAULT '0',
						 course_ids longtext NOT NULL,
						 exclude_course_ids longtext NOT NULL,
						 minimum_amount mediumtext DEFAULT NULL,
						 maximum_amount mediumtext DEFAULT NULL,
						 start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 end_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 PRIMARY KEY (coupon_id),
						 KEY type (type),
						 KEY date_created (date_created)";
	}
}
