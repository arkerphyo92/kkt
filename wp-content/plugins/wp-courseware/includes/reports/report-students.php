<?php
/**
 * WP Courseware Report - Students.
 *
 * @package WPCW
 * @subpackage Reports
 * @since 4.3.0
 */
namespace WPCW\Reports;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Report_Students.
 *
 * @since 4.3.0
 */
class Report_Students extends Report {

	/**
	 * @var string $cache_key The cache key.
	 * @since 4.3.0
	 */
	public $cache_key = 'wpcw_report_students';

	/**
	 * Get Students Report Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The students report id.
	 */
	public function get_id() {
		return 'students';
	}

	/**
	 * Get Student.
	 *
	 * @since 4.3.0
	 *
	 * @param string $start_date The start date. Default is 'this_month'
	 * @param string $end_date The end date.
	 * @param string $status The status to count.
	 *
	 * @return float|int Total number of students based on passed paramaters.
	 */
	public function get_students( $start_date = false, $end_date = false ) {
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

		/**
		 * Filter: Students Report Query Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $query_args The query args to get subscriptions count.
		 * @param Report_Subscriptions $this The subscriptions report.
		 *
		 * @return array $query_args The array of query args.
		 */
		$query_args = apply_filters( 'wpcw_report_students_query_args', $query_args, $this );

		// Not a query arg, but used for caching.
		$query_args['transient'] = 'students';

		// Cache Key.
		$cache_key = md5( json_encode( $query_args ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$count   = wpcw()->students->get_students_count( $query_args );
			$results = $this->cache( $cache_key, $count );
		}

		return absint( $results );
	}

	/**
	 * Get Total Students.
	 *
	 * @since 4.3.0
	 *
	 * @return float|int Total number of students.
	 */
	public function get_total_students() {
		/**
		 * Filter: Total Students Report Query Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $query_args The query args to get subscriptions count.
		 * @param Report_Subscriptions $this The subscriptions report.
		 *
		 * @return array $query_args The array of query args.
		 */
		$query_args = apply_filters( 'wpcw_report_total_students_query_args', array(), $this );

		// Not a query arg, but used for caching.
		$query_args['transient'] = 'total_students';

		// Cache Key.
		$cache_key = md5( json_encode( $query_args ) );

		// Results.
		$results = $this->get_cache( $cache_key, true );

		// Count Orders
		if ( false === $results ) {
			$count   = wpcw()->students->get_students_count( $query_args );
			$results = $this->cache( $cache_key, $count );
		}

		return absint( $results );
	}
}