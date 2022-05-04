<?php
/**
 * WP Courseware DB Subscriptions.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Subscriptions.
 *
 * @since 4.3.0
 */
class DB_Subscriptions extends DB {

	/**
	 * Subscriptions Database Constructor
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'subscriptions' );
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
			'id'               => '%d',
			'student_id'       => '%d',
			'order_id'         => '%d',
			'course_id'        => '%d',
			'student_name'     => '%s',
			'student_email'    => '%s',
			'course_title'     => '%s',
			'period'           => '%s',
			'initial_amount'   => '%s',
			'recurring_amount' => '%s',
			'bill_times'       => '%d',
			'transaction_id'   => '%s',
			'method'           => '%s',
			'created'          => '%s',
			'expiration'       => '%s',
			'status'           => '%s',
			'profile_id'       => '%s',
			'installment_plan' => '%d',
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
			'student_id'       => 0,
			'order_id'         => 0,
			'course_id'        => 0,
			'student_name'     => '',
			'student_email'    => '',
			'course_title'     => '',
			'period'           => 'month',
			'created'          => date( 'Y-m-d H:i:s' ),
			'expiration'       => date( 'Y-m-d H:i:s', strtotime( '+1 month' ) ),
			'status'           => 'pending',
			'bill_times'       => 0,
			'installment_plan' => 0,
		);
	}

	/**
	 * Get Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool  $count Optional. Return only the total number of results.
	 *
	 * @return array Array of orders.
	 */
	public function get_subscriptions( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'student_id'       => 0,
			'order_id'         => 0,
			'course_id'        => 0,
			'profile_id'       => '',
			'transaction_id'   => '',
			'installment_plan' => 0,
			'period'           => '',
			'status'           => '',
			'created'          => '',
			'expiration'       => '',
			'start_date'       => '',
			'end_date'         => '',
			'date_compare'     => '=',
			'date_column'      => 'created',
			'order'            => 'DESC',
			'orderby'          => 'id',
			'search'           => '',
			'fields'           => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$fields       = '';
		$join         = '';
		$where        = '';
		$orderby      = $args['orderby'];
		$order        = strtoupper( $args['order'] );
		$wild         = '%';
		$date_compare = ! empty( $args['date_compare'] ) ? esc_attr( $args['date_compare'] ) : '=';
		$date_column  = ! empty( $args['date_column'] ) ? esc_attr( $args['date_column'] ) : 'created';

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'] );
		}

		if ( ! empty( $args['student_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$student_id = intval( $args['student_id'] );

			$where .= "student_id = {$student_id}";
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

		if ( ! empty( $args['course_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_id'] ) ) {
				$course_ids = implode( ',', array_map( 'intval', $args['course_id'] ) );
			} else {
				$course_ids = intval( $args['course_id'] );
			}

			$where .= "course_id IN( {$course_ids} )";
		}

		if ( ! empty( $args['profile_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$profile_id = esc_attr( $args['profile_id'] );

			$where .= "profile_id = '{$profile_id}'";
		}

		if ( ! empty( $args['transaction_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$transaction_id = esc_attr( $args['transaction_id'] );

			$where .= "transaction_id = '{$transaction_id}'";
		}

		if ( ! empty( $args['installment_plan'] ) && $args['installment_plan'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$installment_plan = absint( $args['installment_plan'] );

			$where .= "installment_plan = {$installment_plan}";
		}

		if ( ! empty( $args['period'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$period = esc_attr( $args['period'] );

			$where .= "period = '{$period}'";
		}

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['status'] ) ) {
				$status = implode( "', '", array_map( 'esc_attr', $args['status'] ) );
				$where  .= "status IN( '{$status}' )";
			} else {
				$status = esc_attr( $args['status'] );
				$where  .= "status = '{$status}'";
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "id LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		if ( ! empty( $args['created'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$created = esc_attr( $args['created'] );
			$created = date( 'Y-m-d H:i:s', strtotime( $created ) );

			$where .= "created {$date_compare} '{$created}'";
		}

		if ( ! empty( $args['expiration'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$expiration = esc_attr( $args['expiration'] );
			$expiration = date( 'Y-m-d H:i:s', strtotime( $expiration ) );

			$where .= "expiration {$date_compare} '{$expiration}'";
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
				$orderby = 'id';
				break;

			case 'title' :
				$orderby = 'id';
				break;

			case 'student_id' :
				$orderby = 'u.user_login';
				$join    = "s INNER JOIN {$wpdb->users} u ON s.student_id = u.ID";
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
	 * Insert Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The subscription data.
	 *
	 * @return int|bool The subscription id or false if an error occurred.
	 */
	public function insert_subscription( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		return $this->insert( $data, 'subscription' );
	}

	/**
	 * Update Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $subscription_id The subscription id.
	 * @param array $data The subscription data.
	 *
	 * @return bool True on successful update, False on failure.
	 */
	public function update_subscription( $subscription_id, $data = array() ) {
		if ( empty( $data ) ) {
			return;
		}

		$data = $this->sanitize_columns( $data );

		return $this->update( $subscription_id, $data );
	}
}
