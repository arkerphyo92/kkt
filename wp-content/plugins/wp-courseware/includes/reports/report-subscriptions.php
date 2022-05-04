<?php
/**
 * WP Courseware Report - Subscriptions.
 *
 * @package WPCW
 * @subpackage Reports
 * @since 4.3.0
 */
namespace WPCW\Reports;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Report_Subscriptions.
 *
 * @since 4.3.0
 */
class Report_Subscriptions extends Report {

	/**
	 * @var string $cache_key The cache key.
	 * @since 4.3.0
	 */
	public $cache_key = 'wpcw_report_subscriptions';

	/**
	 * Get Subscriptions Report Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The students report id.
	 */
	public function get_id() {
		return 'subscriptions';
	}

	/**
	 * Get Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param string $start_date The start date. Default is 'this_month'
	 * @param string $end_date The end date.
	 * @param string $status The status to count.
	 *
	 * @return float|int Total number of students based on passed paramaters.
	 */
	public function get_subscriptions( $start_date = false, $end_date = false, $status = 'active' ) {
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

		// Query Args.
		$query_args = array(
			'start_date' => $this->start_date,
			'end_date'   => $this->end_date,
		);

		if ( 'any' !== $status ) {
			$query_args['status'] = $status;
		}

		/**
		 * Filter: Subscriptions Report Query Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $query_args The query args to get subscriptions count.
		 * @param Report_Subscriptions $this The subscriptions report.
		 *
		 * @return array $query_args The array of query args.
		 */
		$query_args = apply_filters( 'wpcw_report_subscriptions_query_args', $query_args, $this );

		// Not a query arg, but used for caching.
		$query_args['transient'] = 'subscriptions';

		// Cache Key.
		$cache_key = md5( json_encode( $query_args ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$count   = wpcw()->subscriptions->get_subscriptions_count( $query_args );
			$results = $this->cache( $cache_key, $count );
		}

		return absint( $results );
	}

	/**
	 * Get Total Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @return float|int Total number of subscriptions.
	 */
	public function get_total_subscriptions( $status = 'active' ) {
		// Query Args.
		$query_args = array();

		if ( 'any' !== $status ) {
			$query_args['status'] = $status;
		}

		/**
		 * Filter: Total Subscriptions Report Query Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $query_args The query args to get subscriptions count.
		 * @param Report_Subscriptions $this The subscriptions report.
		 *
		 * @return array $query_args The array of query args.
		 */
		$query_args = apply_filters( 'wpcw_report_total_subscriptions_query_args', $query_args, $this );

		// Not a query arg, but used for caching.
		$query_args['transient'] = 'total_subscriptions';

		// Cache Key.
		$cache_key = md5( json_encode( $query_args ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$count   = wpcw()->subscriptions->get_subscriptions_count( $query_args );
			$results = $this->cache( $cache_key, $count );
		}

		return absint( $results );
	}
}