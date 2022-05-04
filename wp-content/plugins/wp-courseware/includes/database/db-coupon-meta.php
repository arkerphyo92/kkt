<?php
/**
 * WP Couponware DB Coupon Meta.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.5.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Coupon_Meta.
 *
 * @since 4.5.0
 */
class DB_Coupon_Meta extends DB {

	/**
	 * Coupons Meta Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'couponmeta' );
		$this->primary_key = 'meta_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.5.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'meta_id'        => '%d',
			'wpcw_coupon_id' => '%d',
			'meta_key'       => '%s',
			'meta_value'     => '%s',
		);
	}

	/**
	 * Retrieve Coupon Meta Field.
	 *
	 * @since 4.5.0
	 *
	 * @param int    $coupon_id The Coupon ID.
	 * @param string $meta_key The Coupon Meta Key.
	 * @param bool   $single Whether to return a single value.
	 *
	 * @return mixed|array The Coupon Meta Value. Array if $single is false.
	 */
	public function get_meta( $coupon_id = 0, $meta_key = '', $single = false ) {
		$coupon_id = $this->sanitize_coupon_id( $coupon_id );

		if ( false === $coupon_id ) {
			return false;
		}

		return get_metadata( 'wpcw_coupon', absint( $coupon_id ), $meta_key, $single );
	}

	/**
	 * Add Meta Field to Coupon.
	 *
	 * @since 4.5.0
	 *
	 * @param int    $coupon_id The Coupon ID.
	 * @param string $meta_key The Coupon Meta Key.
	 * @param mixed  $meta_value The Coupon Meta Value.
	 * @param bool   $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function add_meta( $coupon_id = 0, $meta_key = '', $meta_value, $unique = false ) {
		$coupon_id = $this->sanitize_coupon_id( $coupon_id );

		if ( false === $coupon_id ) {
			return false;
		}

		return add_metadata( 'wpcw_coupon', $coupon_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Coupon Meta Field.
	 *
	 * Use the $prev_value parameter to differentiate between
	 * meta fields with the same key and Coupon ID. If the meta
	 * field for the customer does not exist, it will be added.
	 *
	 * @since 4.5.0
	 *
	 * @param int    $coupon_id The Coupon ID.
	 * @param string $meta_key The Coupon Meta Key.
	 * @param mixed  $meta_value The Coupon Meta Value.
	 * @param mixed  $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function update_meta( $coupon_id = 0, $meta_key = '', $meta_value, $prev_value = '' ) {
		$coupon_id = $this->sanitize_coupon_id( $coupon_id );

		if ( false === $coupon_id ) {
			return false;
		}

		return update_metadata( 'wpcw_coupon', $coupon_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Coupon Meta Field.
	 *
	 * You can match based on the key, or key and value. Removing
	 * based on key and value, will keep from removing duplicate
	 * metadata with the same key. It also allows removing all
	 * metadata matching key, if needed.
	 *
	 * @since 4.5.0
	 *
	 * @param int    $coupon_id The Coupon ID.
	 * @param string $meta_key The Coupon Meta Key.
	 * @param mixed  $meta_value Optional. The Coupon Meta Value.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function delete_meta( $coupon_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'wpcw_coupon', $coupon_id, $meta_key, $meta_value );
	}

	/**
	 * Delete All Meta.
	 *
	 * @since 4.4.0
	 *
	 * @param int $coupon_id The course id.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function delete_all_meta( $coupon_id = 0 ) {
		global $wpdb;

		if ( empty( $coupon_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE wpcw_coupon_id = %d", $coupon_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize Coupon ID.
	 *
	 * Make sure it's a positive number and greater than zero.
	 *
	 * @since 4.5.0
	 *
	 * @param int|stirng $coupon_id The Coupon ID.
	 *
	 * @return int|bool The Coupon ID or False if not valid.
	 */
	private function sanitize_coupon_id( $coupon_id ) {
		if ( ! is_numeric( $coupon_id ) ) {
			return false;
		}

		$coupon_id = (int) $coupon_id;

		// We were given a non positive number
		if ( absint( $coupon_id ) !== $coupon_id ) {
			return false;
		}

		if ( empty( $coupon_id ) ) {
			return false;
		}

		return absint( $coupon_id );
	}
}
