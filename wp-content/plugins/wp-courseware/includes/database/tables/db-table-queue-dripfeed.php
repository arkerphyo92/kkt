<?php
/**
 * WP Courseware Database Table Queue Dripfeed.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Queue_Dripfeed.
 *
 * @since 4.3.0
 */
final class DB_Table_Queue_Dripfeed extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_queue_dripfeed';

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
		$this->schema = "queue_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  				         queue_unit_id int(11) unsigned NOT NULL,
  				         queue_course_id int(11) unsigned NOT NULL,
  				         queue_user_id int(11) NOT NULL,
  				         queue_trigger_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  				         queue_enqueued_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  				         PRIMARY KEY (queue_id)";
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
