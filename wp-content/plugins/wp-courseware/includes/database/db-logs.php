<?php
/**
 * WP Courseware DB Logs.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Logs.
 *
 * @since 4.3.0
 */
class DB_Logs extends DB {

	/**
	 * Logs Database Constructor
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'logs' );
		$this->primary_key = 'log_id';
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
			'log_id'       => '%d',
			'object_id'    => '%d',
			'object_type'  => '%s',
			'type'         => '%s',
			'title'        => '%s',
			'message'      => '%s',
			'date_created' => '%s',
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
			'type'         => 'debug',
			'title'        => '',
			'message'      => '',
			'date_created' => date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Get Logs.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 *
	 * @return array Array of courses.
	 */
	public function get_logs( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'      => 20,
			'offset'      => 0,
			'log_id'      => 0,
			'object_id'   => 0,
			'object_type' => '',
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

		if ( ! empty( $args['log_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['log_id'] ) ) {
				$log_ids = implode( ',', array_map( 'intval', $args['log_id'] ) );
			} else {
				$log_ids = intval( $args['log_id'] );
			}

			$where .= "log_id IN( {$log_ids} )";
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

			$object_type = intval( $args['object_type'] );

			$where .= "object_type = {$object_type}";
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "log_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "title LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = 'log_id';
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
}