<?php
/**
 * WP Courseware DB Orders Items.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Order_Items.
 *
 * @since 4.3.0
 */
class DB_Order_Items extends DB {

	/**
	 * Order Items Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'order_items' );
		$this->primary_key = 'order_item_id';
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
			'order_item_id'    => '%d',
			'order_id'         => '%d',
			'course_id'        => '%d',
			'order_item_title' => '%s',
			'order_item_index' => '%d',
			'type'             => '%s',
			'qty'              => '%d',
			'amount'           => '%s',
			'subtotal'         => '%s',
			'discount'         => '%s',
			'tax'              => '%s',
			'total'            => '%s',
			'use_installments' => '%d',
			'is_recurring'     => '%d',
		);
	}

	/**
	 * Get Columns Defaults.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of columns with default values.
	 */
	public function get_column_defaults() {
		return array(
			'order_id'         => 0,
			'course_id'        => 0,
			'order_item_title' => '',
			'order_item_index' => 0,
			'type'             => 'course',
			'qty'              => 0,
			'amount'           => '0.00',
			'subtotal'         => '0.00',
			'discount'         => '0.00',
			'tax'              => '0.00',
			'total'            => '0.00',
			'use_installments' => 0,
			'is_recurring'     => 0,
		);
	}

	/**
	 * Insert Order Item.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The order item data.
	 */
	public function insert_order_item( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		return $this->insert( $data, 'order_item' );
	}

	/**
	 * Get Order Items.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool  $count Optional. Return only the total number of results.
	 *
	 * @return array Array of orders.
	 */
	public function get_order_items( $order_id, $args = array(), $count = false ) {
		global $wpdb;

		if ( ! $order_id ) {
			return false;
		}

		$defaults = array(
			'number'  => 10,
			'offset'  => 0,
			'order'   => 'ASC',
			'orderby' => 'order_item_index',
			'search'  => '',
			'fields'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$orders_items = array();

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

		if ( $order_id ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$order_id = intval( $order_id );

			$where .= "order_id = {$order_id}";
		}

		if ( ! empty( $args['order_item_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['order_item_id'] ) ) {
				$order_item_ids = implode( ',', array_map( 'intval', $args['order_item_id'] ) );
			} else {
				$order_item_ids = intval( $args['order_item_id'] );
			}

			$where .= "order_item_id IN( {$order_item_ids} )";
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "order_item_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "order_item_id LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = 'order_item_id';
				break;

			case 'title' :
				$orderby = 'order_item_title';
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
