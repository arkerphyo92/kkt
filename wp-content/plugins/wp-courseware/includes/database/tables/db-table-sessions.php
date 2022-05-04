<?php
/**
 * WP Courseware Database Sessions Table.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Sessions.
 *
 * @since 4.3.0
 */
final class DB_Table_Sessions extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_sessions';

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
		$this->schema = "session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
						 session_key char(32) NOT NULL,
						 session_value longtext NOT NULL,
						 session_expiry BIGINT UNSIGNED NOT NULL,
						 PRIMARY KEY (session_key),
						 UNIQUE KEY session_id (session_id)";
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
