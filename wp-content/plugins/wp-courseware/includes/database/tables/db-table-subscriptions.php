<?php
/**
 * WP Courseware Database Table Subscriptions.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Subscriptions.
 *
 * @since 4.3.0
 */
final class DB_Table_Subscriptions extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_subscriptions';

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
		$this->schema = "id bigint(20) NOT NULL AUTO_INCREMENT,
						 student_id bigint(20) NOT NULL,
						 order_id bigint(20) NOT NULL,
						 course_id bigint(20) NOT NULL,
						 student_name varchar(200) NOT NULL,
						 student_email varchar(200) NOT NULL,
						 course_title text NOT NULL,
						 period varchar(20) NOT NULL,
						 initial_amount mediumtext NOT NULL,
						 recurring_amount mediumtext NOT NULL,
						 bill_times bigint(20) NOT NULL,
						 transaction_id varchar(60) NOT NULL,
						 method varchar(100) NOT NULL,
						 created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 expiration datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
						 status varchar(20) NOT NULL,
						 profile_id varchar(60) NOT NULL,
						 installment_plan tinyint(1) NOT NULL DEFAULT '0',
						 PRIMARY KEY  (id),
						 KEY profile_id (profile_id),
						 KEY student_id (student_id),
						 KEY transaction_id (transaction_id),
						 KEY student_and_status (student_id, status)";
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
	 * Upgrade to version 4.6.0
	 *
	 * @since 4.6.0
	 */
	protected function upgrade_to_460() {
		global $wpdb;

		// Installment Plan
		$installment_plan = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'installment_plan'" );

		if ( ! $installment_plan ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `installment_plan` tinyint(1) NOT NULL DEFAULT '0'" );
		}
	}

	/**
	 * Upgrade to version 4.6.7
	 *
	 * @since 4.6.7
	 */
	protected function upgrade_to_467() {
		global $wpdb;

		$course_title = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_title'" );

		if ( $course_title ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `course_title` text NOT NULL" );
		}
	}
}
