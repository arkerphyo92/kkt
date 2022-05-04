<?php
/**
 * WP Courseware Report - Orders.
 *
 * @package WPCW
 * @subpackage Reports
 * @since 4.3.0
 */
namespace WPCW\Reports;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Report_Orders.
 *
 * @since 4.3.0
 */
class Report_Orders extends Report {

	/**
	 * @var string $cache_key The cache key.
	 * @since 4.3.0
	 */
	public $cache_key = 'wpcw_report_orders';

	/**
	 * Get Orders Report Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The orders report id.
	 */
	public function get_id() {
		return 'orders';
	}

	/**
	 * Get Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param string $start_date The start date. Default is 'this_month'
	 * @param string $end_date The end date.
	 * @param string $status The status to count.
	 *
	 * @return float|int Total number of orders based on passed paramaters.
	 */
	public function get_orders( $start_date = false, $end_date = false, $status = 'completed' ) {
		global $wpdb;

		// Setup Dates.
		$this->setup_dates( $start_date, $end_date );

		// Check Start Date.
		if ( is_wp_error( $this->start_date ) ) {
			$this->log( $this->start_date->get_error_message() );
			return;
		}

		// Check End Date.
		if ( is_wp_error( $this->end_date ) ) {
			$this->log( $this->end_date->get_error_message() );
			return;
		}

		/**
		 * Filter: Orders Report Query Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $query_args The query args for the orders report.
		 * @param Report_Orders $this The orders report object.
		 *
		 * @return array $query_args The modified query args.
		 */
		$query_args = apply_filters( 'wpcw_report_orders_query_args', array(
			'order_status' => $status,
			'order_type'   => 'order',
			'start_date'   => $this->start_date,
			'end_date'     => $this->end_date,
		), $this );

		// Not a query arg, but used for caching.
		$query_args['transient'] = 'orders';

		// Cache Key.
		$cache_key = md5( json_encode( $query_args ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$count   = wpcw()->orders->get_orders_count( $query_args );
			$results = $this->cache( $cache_key, $count );
		}

		return absint( $results );
	}

	/**
	 * Get Total Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status The status to count.
	 *
	 * @return int $total_orders The number of total orders.
	 */
	public function get_total_orders( $status = 'completed' ) {
		/**
		 * Filter: Total Orders Report Query Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $query_args The total orders report query args.
		 * @param Report_Orders $this The orders report object.
		 *
		 * @return array $query_args The total orders report query args.
		 */
		$query_args = apply_filters( 'wpcw_report_total_orders_query_args', array(
			'order_status' => $status,
			'order_type'   => 'order',
		), $this );

		// Not a query arg, but used for caching.
		$query_args['transient'] = 'total_orders';

		// Cache Key.
		$cache_key = md5( json_encode( $query_args ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$count   = wpcw()->orders->get_orders_count( $query_args );
			$results = $this->cache( $cache_key, $count );
		}

		return absint( $results );
	}

	/**
	 * Get Sales.
	 *
	 * @since 4.3.0
	 *
	 * @param string $start_date The start date. Default is 'this_month'
	 * @param string $end_date The end date. Default is 'this_month'
	 * @param string $status The status to count.
	 *
	 * @return float|int Total number of sales base on passed paramaters.
	 */
	public function get_sales( $start_date = false, $end_date = false, $status = 'completed' ) {
		global $wpdb, $wpcwdb;

		// Setup Dates.
		$this->setup_dates( $start_date, $end_date );

		// Check Start Date.
		if ( is_wp_error( $this->start_date ) ) {
			$this->log( $this->start_date->get_error_message() );
			return;
		}

		// Check End Date.
		if ( is_wp_error( $this->end_date ) ) {
			$this->log( $this->end_date->get_error_message() );
			return;
		}

		if ( ! is_array( $status ) ) {
			$status = array( $status );
		}

		$statuses = apply_filters( 'wpcw_report_orders_sales_valid_statuses', $status, $this );
		$statuses = "'" . implode( "', '", array_map( 'esc_attr', $statuses ) ) . "'";

		$query = $wpdb->prepare(
			"SELECT SUM(orders.total) AS total,
					SUM(orders.tax) as tax, 
					DATE_FORMAT( orders.date_paid, '%m') AS month, 
					YEAR( orders.date_paid ) AS year, 
					COUNT( DISTINCT orders.order_id ) as count
			 FROM {$wpcwdb->orders} AS orders 
			 WHERE orders.date_paid >= %s 
			 AND orders.date_paid <= %s 
			 AND orders.order_status IN ($statuses)
			 AND orders.transaction_id != 'multiple'
			 AND orders.order_type = 'payment'
			 GROUP BY YEAR( orders.date_paid ), MONTH( orders.date_paid )
			 ORDER BY orders.date_paid ASC",
			date( 'Y-m-d H:i:s', $this->start_date ),
			date( 'Y-m-d H:i:s', $this->end_date )
		);

		// Cache Key.
		$cache_key = md5( 'sales_' . date( 'Y-m-d H:i:s', $this->start_date ) . '_' . date( 'Y-m-d H:i:s', $this->end_date ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			$sales   = $wpdb->get_results( $query );
			$results = $this->cache( $cache_key, $sales );
		}

		return ! empty( $results[0]->total ) ? wpcw_price( $results[0]->total ) : '0';
	}

	/**
	 * Get Total Sales.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status The status to add sales.
	 *
	 * @return string $total_sales The dollar amount of sales.
	 */
	public function get_total_sales( $status = 'completed' ) {
		global $wpdb, $wpcwdb;

		if ( ! is_array( $status ) ) {
			$status = array( $status );
		}

		$statuses = apply_filters( 'wpcw_report_orders_total_sales_valid_statuses', $status );
		$statuses = "'" . implode( "', '", $statuses ) . "'";

		$query = "SELECT SUM(orders.total) AS total 
			 FROM {$wpcwdb->orders} AS orders 
			 WHERE orders.order_status IN ($statuses)
			 AND orders.transaction_id != 'multiple'
			 AND orders.order_type = 'payment'
			 GROUP BY $statuses";

		// Cache Key.
		$cache_key = md5( 'orders_total_sales_' . $query );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			$sales   = $wpdb->get_results( $query );
			$results = $this->cache( $cache_key, $sales );
		}

		return ! empty( $results[0]->total ) ? wpcw_price( $results[0]->total ) : '0';
	}
}