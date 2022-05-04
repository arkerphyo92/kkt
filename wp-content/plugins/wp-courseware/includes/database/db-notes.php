<?php
/**
 * WP Courseware DB Notes.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Notes.
 *
 * @since 4.3.0
 */
class DB_Notes extends DB {

	/**
	 * Notes Database Constructor
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'notes' );
		$this->primary_key = 'id';
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
			'id'           => '%d',
			'object_id'    => '%d',
			'object_type'  => '%s',
			'user_id'      => '%d',
			'content'      => '%s',
			'date_created' => '%s',
			'is_public'    => '%d',
		);
	}

	/**
	 * Get Column Defaults.
	 *
	 * @since 4.3.0
	 *
	 * @return array The column default values.
	 */
	public function get_column_defaults() {
		return array(
			'object_id'    => 0,
			'object_type'  => '',
			'content'      => '',
			'user_id'      => 0,
			'date_created' => date( 'Y-m-d H:i:s' ),
			'is_public'    => 0,
		);
	}

	/**
	 * Insert Note.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The note data.
	 *
	 * @return int|bool The note id or false if an error occurred.
	 */
	public function insert_note( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		return $this->insert( $data, 'note' );
	}

	/**
	 * Update Note.
	 *
	 * @since 4.3.0
	 *
	 * @param int $note_id The note id.
	 * @param array $data The note data.
	 *
	 * @return bool True on successful update, False on failure.
	 */
	public function update_note( $note_id, $data = array() ) {
		if ( empty( $data ) ) {
			return;
		}

		$data = $this->sanitize_columns( $data );

		return $this->update( $note_id, $data );
	}

	/**
	 * Get Notes.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 *
	 * @return array Array of courses.
	 */
	public function get_notes( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'      => 20,
			'offset'      => 0,
			'id'          => 0,
			'object_id'   => 0,
			'object_type' => '',
			'user_id'     => 0,
			'is_public'   => 0,
			'order'       => 'DESC',
			'orderby'     => 'title',
			'search'      => '',
			'fields'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$courses = array();

		$fields  = '';
		$join    = '';
		$where   = '';
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] );
		$wild    = '%';

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'] );
		}

		if ( ! empty( $args['id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['id'] ) ) {
				$note_ids = implode( ',', array_map( 'intval', $args['id'] ) );
			} else {
				$note_ids = intval( $args['id'] );
			}

			$where .= "id IN( {$note_ids} )";
		}

		if ( ! empty( $args['object_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['object_id'] ) ) {
				$object_ids = implode( ',', array_map( 'intval', $args['object_id'] ) );
			} else {
				$object_ids = intval( $args['object_id'] );
			}

			$where .= "object_id IN( {$object_ids} )";
		}

		if ( ! empty( $args['object_type'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$object_type = esc_attr( $args['object_type'] );

			$where .= "object_type = '{$object_type}'";
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$user_id = intval( $args['user_id'] );

			$where .= "user_id = {$user_id}";
		}

		if ( ! empty( $args['is_public'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$is_public = intval( $args['is_public'] );

			$where .= "is_public = {$is_public}";
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "content LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = 'id';
				break;

			default :
				$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? $args['orderby'] : $this->primary_key;
				break;
		}

		$args['orderby'] = $orderby;
		$args['order']   = $order;

		$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

		$results = $this->get_results( $clauses, $args );

		return $results;
	}

	/**
	 * Delete Notes by Object Id.
	 *
	 * @since 4.3.0
	 *
	 * @param array $object_id The object id.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_notes_by_object_id( $object_id = 0 ) {
		global $wpdb;

		if ( empty( $object_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE object_id = %d", $object_id ) ) ) {
			return false;
		}

		return true;
	}
}