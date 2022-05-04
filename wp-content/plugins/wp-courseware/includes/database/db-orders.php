<?php
/**
 * WP Courseware DB Orders.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Orders.
 *
 * @since 4.3.0
 */
class DB_Orders extends DB {

	/**
	 * @var DB_Order_Items The order items db object.
	 * @since 4.3.0
	 */
	protected $order_items_db;

	/**
	 * Orders Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'orders' );
		$this->primary_key = 'order_id';

		// Order Items Database
		$this->order_items_db = new DB_Order_Items();
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
			'order_id'             => '%d',
			'order_key'            => '%s',
			'order_type'           => '%s',
			'student_id'           => '%d',
			'student_email'        => '%s',
			'student_first_name'   => '%s',
			'student_last_name'    => '%s',
			'billing_address_1'    => '%s',
			'billing_address_2'    => '%s',
			'billing_city'         => '%s',
			'billing_state'        => '%s',
			'billing_postcode'     => '%s',
			'billing_country'      => '%s',
			'order_status'         => '%s',
			'order_parent_id'      => '%d',
			'subscription_id'      => '%d',
			'payment_method'       => '%s',
			'payment_method_title' => '%s',
			'discounts'            => '%s',
			'subtotal'             => '%s',
			'tax'                  => '%s',
			'total'                => '%s',
			'currency'             => '%s',
			'transaction_id'       => '%s',
			'student_ip_address'   => '%s',
			'student_user_agent'   => '%s',
			'created_via'          => '%s',
			'date_created'         => '%s',
			'date_completed'       => '%s',
			'date_paid'            => '%s',
			'cart_hash'            => '%s',
		);
	}

	/**
	 * Get Column Defaults.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'student_id'         => 0,
			'created_via'        => 'system',
			'date_created'       => date( 'Y-m-d H:i:s' ),
			'order_status'       => 'pending',
			'order_type'         => 'order',
			'order_parent_id'    => 0,
			'subscription_id'    => 0,
			'student_ip_address' => wpcw_user_ip_address(),
			'student_user_agent' => wpcw_user_agent(),
		);
	}

	/**
	 * Insert Order.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The order data.
	 *
	 * @return int|bool The order id or false if an error occurred.
	 */
	public function insert_order( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		return $this->insert( $data, 'order' );
	}

	/**
	 * Update Order.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The order id.
	 * @param array $data The order data.
	 *
	 * @return bool True on successful update, False on failure.
	 */
	public function update_order( $order_id, $data = array() ) {
		if ( empty( $data ) ) {
			return;
		}

		$data = $this->sanitize_columns( $data );

		return $this->update( $order_id, $data );
	}

	/**
	 * Get Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 *
	 * @return array Array of orders.
	 */
	public function get_orders( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'          => 20,
			'offset'          => 0,
			'order_id'        => 0,
			'student_id'      => 0,
			'order_parent_id' => 0,
			'subscription_id' => 0,
			'transaction_id'  => '',
			'order_type'      => '',
			'order_status'    => '',
			'date_created'    => '',
			'date_completed'  => '',
			'date_paid'       => '',
			'start_date'      => '',
			'end_date'        => '',
			'date_compare'    => '=',
			'date_column'     => 'date_created',
			'order'           => 'DESC',
			'orderby'         => 'order_id',
			'search'          => '',
			'fields'          => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$orders = array();

		$fields       = '';
		$join         = '';
		$where        = '';
		$orderby      = $args['orderby'];
		$order        = strtoupper( $args['order'] );
		$wild         = '%';
		$date_compare = ! empty( $args['date_compare'] ) ? esc_attr( $args['date_compare'] ) : '=';
		$date_column  = ! empty( $args['date_column'] ) ? esc_attr( $args['date_column'] ) : 'date_created';

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'] );
		}

		if ( ! empty( $args['order_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['order_id'] ) ) {
				$order_ids = implode( ',', array_map( 'intval', $args['order_id'] ) );
			} else {
				$order_ids = intval( $args['order_id'] );
			}

			$where .= "order_id IN( {$order_ids} )";
		}

		if ( ! empty( $args['subscription_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['subscription_id'] ) ) {
				$subscription_ids = implode( ',', array_map( 'intval', $args['subscription_id'] ) );
			} else {
				$subscription_ids = intval( $args['subscription_id'] );
			}

			$where .= "subscription_id IN( {$subscription_ids} )";
		}

		if ( ! empty( $args['transaction_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$transaction_id = esc_attr( $args['transaction_id'] );

			$where .= "transaction_id = '{$transaction_id}'";
		}

		if ( ! empty( $args['student_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$student_id = intval( $args['student_id'] );

			$where .= "student_id = {$student_id}";
		}

		if ( ! empty( $args['order_parent_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$order_parent_id = intval( $args['order_parent_id'] );

			$where .= "order_parent_id = {$order_parent_id}";
		}

		if ( ! empty( $args['order_status'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['order_status'] ) ) {
				$order_status = implode( "','", array_map( 'esc_attr', $args['order_status'] ) );
				$where        .= "order_status IN( '{$order_status}' )";
			} else {
				$order_status = esc_attr( $args['order_status'] );
				$where        .= "order_status = '{$order_status}'";
			}
		}

		if ( ! empty( $args['order_type'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$order_type = esc_attr( $args['order_type'] );

			if ( is_array( $args['order_type'] ) ) {
				$order_type = implode( "','", array_map( 'esc_attr', $args['order_type'] ) );
				$where      .= "order_type IN( '{$order_type}' )";
			} else {
				$order_type = esc_attr( $args['order_type'] );
				$where      .= "order_type = '{$order_type}'";
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "order_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "order_id LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		if ( ! empty( $args['date_created'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$date_created = esc_attr( $args['date_created'] );
			$date_created = date( 'Y-m-d H:i:s', strtotime( $date_created ) );

			$where .= "date_created {$date_compare} '{$date_created}'";
		}

		if ( ! empty( $args['date_completed'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$date_completed = esc_attr( $args['date_completed'] );
			$date_completed = date( 'Y-m-d H:i:s', strtotime( $date_completed ) );

			$where .= "date_created {$date_compare} '{$date_completed}'";
		}

		if ( ! empty( $args['date_paid'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$date_paid = esc_attr( $args['date_paid'] );
			$date_paid = date( 'Y-m-d H:i:s', strtotime( $date_paid ) );

			$where .= "date_paid {$date_compare} '{$date_paid}'";
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$start_date = esc_attr( $args['start_date'] );
			$start_date = date( 'Y-m-d H:i:s', $start_date );

			$where .= "{$date_column} >= '{$start_date}'";
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$end_date = esc_attr( $args['end_date'] );
			$end_date = date( 'Y-m-d H:i:s', $end_date );

			$where .= "{$date_column} <= '{$end_date}'";
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = 'order_id';
				break;

			case 'title' :
				$orderby = 'order_id';
				break;

			case 'student_id' :
				$orderby = 'u.user_login';
				$join    = "o INNER JOIN {$wpdb->users} u ON o.student_id = u.ID";
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
	 * Get Order Items.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The order id.
	 * @param array $args The order query args.
	 *
	 * @return array An array of orders.
	 */
	public function get_order_items( $order_id, $args = array() ) {
		return $this->order_items_db->get_order_items( $order_id, $args );
	}

	/**
	 * Insert Order Item.
	 *
	 * @since 4.3.0
	 *
	 * @param int|null The insert id or null.
	 */
	public function insert_order_item( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		return $this->order_items_db->insert( $data, 'order_item' );
	}

	/**
	 * Delete Order Item.
	 *
	 * @since 4.3.0
	 *
	 * @param int The order item id.
	 */
	public function delete_order_item( $order_item_id ) {
		if ( 0 === absint( $order_item_id ) ) {
			return false;
		}

		return $this->order_items_db->delete( $order_item_id );
	}
}