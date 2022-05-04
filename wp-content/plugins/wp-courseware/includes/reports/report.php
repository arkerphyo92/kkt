<?php
/**
 * WP Courseware Report.
 *
 * @package WPCW
 * @subpackage Reports
 * @since 4.3.0
 */
namespace WPCW\Reports;

use WP_Error;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Report.
 *
 * @since 4.3.0
 */
abstract class Report {

	/**
	 * @var string $cache_key The cache key.
	 * @since 4.3.0
	 */
	public $cache_key = 'wpcw_reports';

	/**
	 * @var float|int $cache_expiration The length of expiration.
	 */
	public $cache_expiration = HOUR_IN_SECONDS;

	/**
	 * @var array $cache The cache.
	 * @since 4.3.0
	 */
	public $cache;

	/**
	 * @var string|WP_Error $start_date The report start date.
	 * @since 4.3.0
	 */
	public $start_date;

	/**
	 * @var string|WP_Error $end_date The report end date.
	 * @since 4.3.0
	 */
	public $end_date;

	/**
	 * @var bool Flag to determine if date is based on timestamps.
	 * @since 4.3.0
	 */
	public $timestamp = false;

	/**
	 * Get Report Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The report identifier.
	 */
	abstract public function get_id();

	/**
	 * Get Predefined Dates.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of predifined dates.
	 */
	public function get_predfined_date_ranges() {
		$date_ranges = array(
			'today'        => esc_html__( 'Today', 'wp-courseware' ),
			'yesterday'    => esc_html__( 'Yesterday', 'wp-courseware' ),
			'this_week'    => esc_html__( 'This Week', 'wp-courseware' ),
			'last_week'    => esc_html__( 'Last Week', 'wp-courseware' ),
			'this_month'   => esc_html__( 'This Month', 'wp-courseware' ),
			'last_month'   => esc_html__( 'Last Month', 'wp-courseware' ),
			'this_quarter' => esc_html__( 'This Quarter', 'wp-courseware' ),
			'last_quarter' => esc_html__( 'Last Quarter', 'wp-courseware' ),
			'this_year'    => esc_html__( 'This Year', 'wp-courseware' ),
			'last_year'    => esc_html__( 'Last Year', 'wp-courseware' ),
		);

		return apply_filters( 'wpcw_report_predefined_date_ranges', $date_ranges );
	}

	/**
	 * Setup Dates.
	 *
	 * @since 4.3.0
	 */
	public function setup_dates( $start_date = 'this_month', $end_date = false ) {
		if ( empty( $start_date ) ) {
			$start_date = 'this_month';
		}

		if ( empty( $end_date ) ) {
			$end_date = $start_date;
		}

		$this->start_date = $this->convert_date( $start_date );
		$this->end_date   = $this->convert_date( $end_date, true );
	}

	/**
	 * Convert Date.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $date The date to convert.
	 * @param bool|mixed $end_date Optional. The end date.
	 *
	 * @return string|WP_Error If the date is invalid, a WP_Error object will be returned.
	 */
	public function convert_date( $date, $end_date = false ) {
		$this->timestamp = false;

		$second = $end_date ? 59 : 0;
		$minute = $end_date ? 59 : 0;
		$hour   = $end_date ? 23 : 0;
		$day    = 1;
		$month  = date( 'n', current_time( 'timestamp' ) );
		$year   = date( 'Y', current_time( 'timestamp' ) );

		if ( ( is_string( $date ) || is_int( $date ) ) && array_key_exists( $date, $this->get_predfined_date_ranges() ) ) {
			switch ( $date ) {
				case 'this_month' :
					if ( $end_date ) {
						$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
						$hour   = 23;
						$minute = 59;
						$second = 59;
					}
					break;
				case 'last_month' :
					if ( $month == 1 ) {
						$month = 12;
						$year--;
					} else {
						$month--;
					}
					if ( $end_date ) {
						$day = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
					}
					break;
				case 'today' :
					$day = date( 'd', current_time( 'timestamp' ) );
					if ( $end_date ) {
						$hour   = 23;
						$minute = 59;
						$second = 59;
					}
					break;
				case 'yesterday' :
					$day = date( 'd', current_time( 'timestamp' ) ) - 1;
					// Check if Today is the first day of the month ( meaning subtracting one will get us 0 )
					if ( $day < 1 ) {
						// If current month is 1
						if ( 1 == $month ) {
							$year  -= 1; // Today is January 1, so skip back to last day of December
							$month = 12;
							$day   = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
						} else {
							// Go back one month and get the last day of the month
							$month -= 1;
							$day   = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
						}
					}
					break;
				case 'this_week' :
					$days_to_week_start = ( date( 'w', current_time( 'timestamp' ) ) - 1 ) * 60 * 60 * 24;
					$today              = date( 'd', current_time( 'timestamp' ) ) * 60 * 60 * 24;
					if ( $today < $days_to_week_start ) {
						if ( $month > 1 ) {
							$month -= 1;
						} else {
							$month = 12;
						}
					}
					if ( ! $end_date ) {
						// Getting the start day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 1;
						$day += get_option( 'start_of_week' );
					} else {
						// Getting the end day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 1;
						$day += get_option( 'start_of_week' ) + 6;
					}
					break;
				case 'last_week' :
					$days_to_week_start = ( date( 'w', current_time( 'timestamp' ) ) - 1 ) * 60 * 60 * 24;
					$today              = date( 'd', current_time( 'timestamp' ) ) * 60 * 60 * 24;
					if ( $today < $days_to_week_start ) {
						if ( $month > 1 ) {
							$month -= 1;
						} else {
							$month = 12;
						}
					}
					if ( ! $end_date ) {
						// Getting the start day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 8;
						$day += get_option( 'start_of_week' );
					} else {
						// Getting the end day
						$day = date( 'd', current_time( 'timestamp' ) - $days_to_week_start ) - 8;
						$day += get_option( 'start_of_week' ) + 6;
					}
					break;
				case 'this_quarter' :
					$month_now = date( 'n', current_time( 'timestamp' ) );
					if ( $month_now <= 3 ) {
						if ( ! $end_date ) {
							$month = 1;
						} else {
							$month  = 3;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 6 ) {
						if ( ! $end_date ) {
							$month = 4;
						} else {
							$month  = 6;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 9 ) {
						if ( ! $end_date ) {
							$month = 7;
						} else {
							$month  = 9;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					} else {
						if ( ! $end_date ) {
							$month = 10;
						} else {
							$month  = 12;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					}
					break;
				case 'last_quarter' :
					$month_now = date( 'n', current_time( 'timestamp' ) );
					if ( $month_now <= 3 ) {
						if ( ! $end_date ) {
							$month = 10;
						} else {
							$year   -= 1;
							$month  = 12;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 6 ) {
						if ( ! $end_date ) {
							$month = 1;
						} else {
							$month  = 3;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					} else if ( $month_now <= 9 ) {
						if ( ! $end_date ) {
							$month = 4;
						} else {
							$month  = 6;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					} else {
						if ( ! $end_date ) {
							$month = 7;
						} else {
							$month  = 9;
							$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
							$hour   = 23;
							$minute = 59;
							$second = 59;
						}
					}
					break;
				case 'this_year' :
					if ( ! $end_date ) {
						$month = 1;
					} else {
						$month  = 12;
						$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
						$hour   = 23;
						$minute = 59;
						$second = 59;
					}
					break;
				case 'last_year' :
					$year -= 1;
					if ( ! $end_date ) {
						$month = 1;
						$day   = 1;
					} else {
						$month  = 12;
						$day    = wpcw_cal_days_in_month( CAL_GREGORIAN, $month, $year );
						$hour   = 23;
						$minute = 59;
						$second = 59;
					}
					break;
			}
		} else if ( is_numeric( $date ) ) {
			$this->timestamp = true;
		} else if ( false !== strtotime( $date ) ) {
			$date  = strtotime( $date, current_time( 'timestamp' ) );
			$year  = date( 'Y', $date );
			$month = date( 'm', $date );
			$day   = date( 'd', $date );
		} else {
			return new WP_Error( 'wpcw-invalid-report-date', __( 'Invalid report date provided.', 'wp-courseware' ) );
		}

		if ( false === $this->timestamp ) {
			$date = mktime( $hour, $minute, $second, $month, $day, $year );
		}

		/**
		 * Filter: Report Date.
		 *
		 * @since 4.3.0
		 *
		 * @param string $date The report date.
		 * @param string $end_date The report end date.
		 * @param Report The report object.
		 *
		 * @return string $date The date to use for the report.
		 */
		return apply_filters( 'wpcw_report_date', $date, $end_date, $this );
	}

	/**
	 * Is Cacheable?
	 *
	 * @since 4.3.0
	 *
	 * @param string $date_range The date range.
	 *
	 * @return bool True if the date range is allowed to be cached, false otherwise.
	 */
	public function is_cacheable( $date_range = '' ) {
		if ( empty( $date_range ) ) {
			return false;
		}

		$cacheable_date_ranges = array(
			'today',
			'yesterday',
			'this_week',
			'last_week',
			'this_month',
			'last_month',
			'this_quarter',
			'last_quarter',
			'this_year',
			'last_year',
		);

		return in_array( $date_range, $cacheable_date_ranges );
	}

	/**
	 * Get Cache.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The reference key to pull data.
	 *
	 * @return mixed|bool The cached results or false if it doesn't exist.
	 */
	public function get_cache( $key, $fresh = false ) {
		if ( empty( $this->cache ) || $fresh ) {
			$this->cache = get_transient( $this->cache_key );
		}

		return isset( $this->cache[ $key ] ) ? $this->cache[ $key ] : false;
	}

	/**
	 * Cache Data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The cache key.
	 * @param mixed $data The data to be cached..
	 *
	 * @return mixed The cached results.
	 */
	public function cache( $key, $data = '' ) {
		if ( empty( $this->cache ) ) {
			$this->cache = get_transient( $this->cache_key );
		}

		$this->cache[ $key ] = $data;

		set_transient( $this->cache_key, $this->cache, $this->cache_expiration );

		return $this->cache[ $key ];
	}

	/**
	 * Delete Cache.
	 *
	 * @since 4.3.0
	 */
	public function delete_cache() {
		delete_transient( $this->cache_key );
	}

	/**
	 * Log Report Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The log message.
	 */
	public function log( $message = '' ) {
		if ( empty( $message ) ) {
			return;
		}

		$log_entry = "\n" . '====Start Report Log====' . "\n" . $message . "\n" . '====End Report Log====' . "\n";

		wpcw_log( $log_entry );
	}
}