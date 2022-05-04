<?php
/**
 * WP Courseware DB Coupons.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.5.0
 */

namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Coupons.
 *
 * @since 4.5.0
 */
class DB_Coupons extends DB {

	/**
	 * Coupons Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'coupons' );
		$this->primary_key = 'coupon_id';
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
			'coupon_id'            => '%d',
			'code'                 => '%s',
			'amount'               => '%s',
			'type'                 => '%s',
			'usage_count'          => '%d',
			'usage_limit'          => '%d',
			'usage_limit_per_user' => '%d',
			'individual_use'       => '%d',
			'course_ids'           => '%s',
			'exclude_course_ids'   => '%s',
			'minimum_amount'       => '%s',
			'maximum_amount'       => '%s',
			'start_date'           => '%s',
			'end_date'             => '%s',
			'date_created'         => '%s',
		);
	}

	/**
	 * Get Column Defaults.
	 *
	 * @since 4.5.0
	 *
	 * @return array The column default values.
	 */
	public function get_column_defaults() {
		return array(
			'code'                 => '',
			'amount'               => '',
			'type'                 => 'percentage',
			'usage_count'          => 0,
			'usage_limit'          => 0,
			'usage_limit_per_user' => 0,
			'individual_use'       => 0,
			'course_ids'           => '',
			'exclude_course_ids'   => '',
			'minimum_amount'       => '',
			'maximum_amount'       => '',
			'date_created'         => date( 'Y-m-d H:i:s' ),
			'start_date'           => date( 'Y-m-d H:i:s' ),
			'end_date'             => date( 'Y-m-d H:i:s', strtotime( '+1 month' ) ),
		);
	}

	/**
	 * Get Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool  $count Optional. Return only the total number of results.
	 *
	 * @return array The array of database results.
	 */
	public function get_coupons( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'coupon_id'    => 0,
			'code'         => '',
			'type'         => '',
			'date_created' => '',
			'start_date'   => '',
			'end_date'     => '',
			'date_compare' => '=',
			'date_column'  => 'date_created',
			'order'        => 'DESC',
			'orderby'      => 'coupon_id',
			'search'       => '',
			'fields'       => '',
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

		if ( ! empty( $args['coupon_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['coupon_id'] ) ) {
				$coupon_ids = implode( ',', array_map( 'intval', $args['coupon_id'] ) );
			} else {
				$coupon_ids = intval( $args['coupon_id'] );
			}

			$where .= "coupon_id IN( {$coupon_ids} )";
		}

		if ( ! empty( $args['type'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$type = esc_attr( $args['type'] );

			if ( is_array( $args['type'] ) ) {
				$type  = implode( "','", array_map( 'esc_attr', $args['type'] ) );
				$where .= "type IN( '{$type}' )";
			} else {
				$type  = esc_attr( $args['type'] );
				$where .= "type = '{$type}'";
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "code IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "code LIKE %s", $search_value );
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
				$orderby = 'coupon_id';
				break;

			case 'code' :
				$orderby = 'coupon_id';
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
	 * Get Coupon Ids by Code.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code The coupon code.
	 *
	 * @return array The array of coupon ids.
	 */
	public function get_coupon_ids_by_code( $code ) {
		global $wpdb, $wpcwdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT coupon_id FROM $this->table_name WHERE code = %s ORDER BY date_created DESC", $code ) );
	}

	/**
	 * Get Coupon by Code.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code The coupon code.
	 *
	 * @return array The array of coupon ids.
	 */
	public function get_coupon_by_code( $code ) {
		global $wpdb, $wpcwdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE code = %s ORDER BY date_created DESC", $code ) );
	}
}
