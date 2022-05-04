<?php
/**
 * WP Courseware DB Course Meta.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Course_Meta.
 *
 * @since 4.3.0
 */
class DB_Course_Meta extends DB {

	/**
	 * Courses Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'coursemeta' );
		$this->primary_key = 'meta_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'meta_id'        => '%d',
			'wpcw_course_id' => '%d',
			'meta_key'       => '%s',
			'meta_value'     => '%s',
		);
	}

	/**
	 * Retrieve Course Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id The Course ID.
	 * @param string $meta_key The Course Meta Key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed|array The Course Meta Value. Array if $single is false.
	 */
	public function get_meta( $course_id = 0, $meta_key = '', $single = false ) {
		$course_id = $this->sanitize_course_id( $course_id );

		if ( false === $course_id ) {
			return false;
		}

		return get_metadata( 'wpcw_course', absint( $course_id ), $meta_key, $single );
	}

	/**
	 * Add Meta Field to Course.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id The Course ID.
	 * @param string $meta_key The Course Meta Key.
	 * @param mixed $meta_value The Course Meta Value.
	 * @param bool $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function add_meta( $course_id = 0, $meta_key = '', $meta_value = null, $unique = false ) {
		$course_id = $this->sanitize_course_id( $course_id );

		if ( false === $course_id ) {
			return false;
		}

		return add_metadata( 'wpcw_course', $course_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Course Meta Field.
	 *
	 * Use the $prev_value parameter to differentiate between
	 * meta fields with the same key and Course ID. If the meta
	 * field for the customer does not exist, it will be added.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id The Course ID.
	 * @param string $meta_key The Course Meta Key.
	 * @param mixed $meta_value The Course Meta Value.
	 * @param mixed $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function update_meta( $course_id = 0, $meta_key = '', $meta_value = null, $prev_value = '' ) {
		$course_id = $this->sanitize_course_id( $course_id );

		if ( false === $course_id ) {
			return false;
		}

		return update_metadata( 'wpcw_course', $course_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Course Meta Field.
	 *
	 * You can match based on the key, or key and value. Removing
	 * based on key and value, will keep from removing duplicate
	 * metadata with the same key. It also allows removing all
	 * metadata matching key, if needed.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id The Course ID.
	 * @param string $meta_key The Course Meta Key.
	 * @param mixed $meta_value Optional. The Course Meta Value.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function delete_meta( $course_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'wpcw_course', $course_id, $meta_key, $meta_value );
	}

	/**
	 * Delete All Meta.
	 *
	 * @since 4.4.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return bool False on failure, True if successful.
	 */
	public function delete_all_meta( $course_id = 0 ) {
		global $wpdb;

		if ( empty( $course_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE wpcw_course_id = %d", $course_id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize Course ID.
	 *
	 * Make sure it's a positive number and greater than zero.
	 *
	 * @since 4.3.0
	 *
	 * @param int|stirng $course_id The Course ID.
	 *
	 * @return int|bool The Course ID or False if not valid.
	 */
	private function sanitize_course_id( $course_id ) {
		if ( ! is_numeric( $course_id ) ) {
			return false;
		}

		$course_id = (int) $course_id;

		// We were given a non positive number
		if ( absint( $course_id ) !== $course_id ) {
			return false;
		}

		if ( empty( $course_id ) ) {
			return false;
		}

		return absint( $course_id );
	}
}