<?php
/**
 * WP Orderware DB Order Meta.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Order_Meta.
 *
 * @since 4.3.0
 */
class DB_Order_Meta extends DB {

	/**
	 * Order Meta Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'ordermeta' );
		$this->primary_key = 'meta_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'meta_id'       => '%d',
			'wpcw_order_id' => '%d',
			'meta_key'      => '%s',
			'meta_value'    => '%s',
		);
	}

	/**
	 * Retrieve Order Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The Order ID.
	 * @param string $meta_key The Order Meta Key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed|array The Order Meta Value. Array if $single is false.
	 */
	public function get_meta( $order_id = 0, $meta_key = '', $single = false ) {
		$order_id = $this->sanitize_order_id( $order_id );

		if ( false === $order_id ) {
			return false;
		}

		return get_metadata( 'wpcw_order', absint( $order_id ), $meta_key, $single );
	}

	/**
	 * Add Meta Field to Order.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The Order ID.
	 * @param string $meta_key The Order Meta Key.
	 * @param mixed $meta_value The Order Meta Value.
	 * @param bool $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function add_meta( $order_id = 0, $meta_key = '', $meta_value, $unique = false ) {
		$order_id = $this->sanitize_order_id( $order_id );

		if ( false === $order_id ) {
			return false;
		}

		return add_metadata( 'wpcw_order', $order_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Order Meta Field.
	 *
	 * Use the $prev_value parameter to differentiate between
	 * meta fields with the same key and Order ID. If the meta
	 * field for the customer does not exist, it will be added.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The Order ID.
	 * @param string $meta_key The Order Meta Key.
	 * @param mixed $meta_value The Order Meta Value.
	 * @param mixed $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function update_meta( $order_id = 0, $meta_key = '', $meta_value, $prev_value = '' ) {
		$order_id = $this->sanitize_order_id( $order_id );

		if ( false === $order_id ) {
			return false;
		}

		return update_metadata( 'wpcw_order', $order_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Order Meta Field.
	 *
	 * You can match based on the key, or key and value. Removing
	 * based on key and value, will keep from removing duplicate
	 * metadata with the same key. It also allows removing all
	 * metadata matching key, if needed.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The Order ID.
	 * @param string $meta_key The Order Meta Key.
	 * @param mixed $meta_value Optional. The Order Meta Value.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function delete_meta( $order_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'wpcw_order', $order_id, $meta_key, $meta_value );
	}

	/**
	 * Delete All Meta.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The order id.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function delete_all_meta( $order_id = 0 ) {
		global $wpdb;

		if ( empty( $order_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE wpcw_order_id = %d", $order_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize Order ID.
	 *
	 * Make sure it's a positive number and greater than zero.
	 *
	 * @since 4.3.0
	 *
	 * @param int|stirng $order_id The Order ID.
	 *
	 * @return int|bool The Order ID or False if not valid.
	 */
	private function sanitize_order_id( $order_id ) {
		if ( ! is_numeric( $order_id ) ) {
			return false;
		}

		$order_id = (int) $order_id;

		// We were given a non positive number
		if ( absint( $order_id ) !== $order_id ) {
			return false;
		}

		if ( empty( $order_id ) ) {
			return false;
		}

		return absint( $order_id );
	}
}