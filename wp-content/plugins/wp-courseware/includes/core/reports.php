<?php
/**
 * WP Courseware Reports.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */
namespace WPCW\Core;

use WP_REST_Request;
use WPCW\Reports\Report;
use WPCW\Reports\Report_Orders;
use WPCW\Reports\Report_Students;
use WPCW\Reports\Report_Subscriptions;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Repors.
 *
 * @since 4.6.3
 */
class Reports {

	/**
	 * @var array $reports The registered reports.
	 * @since 4.3.0
	 */
	protected $reports = array();

	/**
	 * Reports Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Register Reports.
		$this->register_reports();

		// Reports Dashboard Widget.
		add_action( 'wp_dashboard_setup', array( $this, 'add_reports_dashboard' ), 10 );

		// Api Endpoints.
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Register Reports.
	 *
	 * @since 4.3.0
	 */
	public function register_reports() {
		$report_classes = array(
			'Report_Orders',
			'Report_Students',
			'Report_Subscriptions',
		);

		foreach ( $report_classes as $report_class ) {
			$class_name = "\\WPCW\\Reports\\{$report_class}";
			if ( class_exists( $class_name ) ) {
				$report = new $class_name();
				if ( $report instanceof Report ) {
					$this->reports[ $report->get_id() ] = $report;
				}
			}
		}

		$this->reports = apply_filters( 'wpcw_reports', $this->reports );
	}

	/**
	 * Get Report.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The report id.
	 *
	 * @return Report|null The report object or null if false.
	 */
	public function get_report( $id ) {
		return isset( $this->reports[ $id ] ) ? $this->reports[ $id ] : null;
	}

	/**
	 * Get Reports Data.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $refresh Should we refresh data.
	 *
	 * @return array $data The report data.
	 */
	public function get_reports_data( $refresh = false ) {
		$data = array();

		// Delete Caches if refresh flag is true.
		if ( $refresh ) {
			$this->delete_caches();
		}

		/** @var Report_Orders $report_orders */
		$report_orders = $this->get_report( 'orders' );

		if ( ! is_null( $report_orders ) ) {
			$data['sales'] = array(
				'today'      => $report_orders->get_sales( 'today' ),
				'this_month' => $report_orders->get_sales( 'this_month' ),
				'last_month' => $report_orders->get_sales( 'last_month' ),
				'total'      => $report_orders->get_total_sales(),
			);

			$data['orders'] = array(
				'today'      => $report_orders->get_orders( 'today' ),
				'this_month' => $report_orders->get_orders( 'this_month' ),
				'last_month' => $report_orders->get_orders( 'last_month' ),
				'total'      => $report_orders->get_total_orders(),
			);
		}

		/** @var Report_Subscriptions $report_subscriptions */
		$report_subscriptions = $this->get_report( 'subscriptions' );

		if ( ! is_null( $report_subscriptions ) ) {
			$data['subscriptions'] = array(
				'this_month' => $report_subscriptions->get_subscriptions( 'this_month' ),
				'this_year'  => $report_subscriptions->get_subscriptions( 'this_year' ),
				'total'      => $report_subscriptions->get_total_subscriptions(),
			);
		}

		/** @var Report_Students $report_students */
		$report_students = $this->get_report( 'students' );

		if ( ! is_null( $report_students ) ) {
			$data['students'] = array(
				'this_month' => $report_students->get_students( 'this_month' ),
				'this_year'  => $report_students->get_students( 'this_year' ),
				'total'      => $report_students->get_total_students(),
			);
		}

		/**
		 * Filter: Reports Dashboard Data.
		 *
		 * @since 4.3.0
		 *
		 * @param array   $data The reports dashboard data.
		 * @param Reports $this The reports controller object.
		 */
		return apply_filters( 'wpcw_reports_dashboard_data', $data, $this );
	}

	/**
	 * Add Reports Dashboard.
	 *
	 * @since 4.3.0
	 */
	public function add_reports_dashboard() {
		if ( current_user_can( 'manage_wpcw_settings' ) ) {
			wp_add_dashboard_widget( 'wpcw_reports_dashboard', esc_html__( 'WP Courseware Reports', 'wp-courseware' ), array( $this, 'reports_dashboard' ) );
		}
	}

	/**
	 * Reports Dashboard.
	 *
	 * @since 4.3.0
	 */
	public function reports_dashboard() {
		echo wpcw_admin_get_view( 'reports/reports-dashboard' );
		?>
		<div id="wpcw-reports-dashboard-widget">
			<wpcw-reports-dashboard></wpcw-reports-dashboard>
		</div>
		<?php
	}

	/**
	 * Custom Dashboard Reports.
	 *
	 * @since 4.3.0
	 *
	 * @return string $html The custom dashboard reports.
	 */
	public function reports_dashboard_custom_reports() {
		ob_start();
		/**
		 * Action: Reports Dashboard - Custom Reports.
		 *
		 * @since 4.3.0
		 *
		 * @param Reports $this The reports controller.
		 */
		do_action( 'wpcw_reports_dashboard_custom_reports', $this );

		return ob_get_clean();
	}

	/**
	 * Delete Report Caches.
	 *
	 * @since 4.3.0
	 */
	public function delete_caches() {
		/** @var Report $report */
		foreach ( $this->reports as $report ) {
			if ( method_exists( $report, 'delete_cache' ) ) {
				$report->delete_cache();
			}
		}
	}

	/** API Methods -------------------------------------------------- */

	/**
	 * Register Reports Api Endpoints.
	 *
	 * @since 4.3.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'reports-dashboard', 'method' => 'GET', 'callback' => array( $this, 'api_get_reports_dashboard' ) );

		return $endpoints;
	}

	/**
	 * Api: Get Reports Dashboard
	 *
	 * @since 4.3.0
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_reports_dashboard( WP_REST_Request $request ) {
		// Get Refresh Var.
		$refresh = $request->get_param( 'refresh' );

		// Report Data.
		$data = $this->get_reports_data( $refresh );

		return rest_ensure_response( array( 'reports' => $data, 'custom' => $this->reports_dashboard_custom_reports() ) );
	}
}
