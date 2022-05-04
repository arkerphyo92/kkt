<?php
/**
 * WP Courseware Enrollment.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.6.3
 */
namespace WPCW\Core;

use WP_REST_Request;
use WPCW\Models\Order;
use WPCW\Models\Order_Item;
use WPCW\Models\Subscription;
use WPCW_queue_dripfeed;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Enrollment.
 *
 * @since 4.6.3
 */
class Enrollment {

	/**
	 * Enrollment Load.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		add_action( 'user_new_form', array( $this, 'enroll_new_user_dropdown' ) );
		add_action( 'wpcw_enqueue_scripts', array( $this, 'enroll_new_user_dropdown_scripts' ), 10, 2 );

		add_action( 'user_register', array( $this, 'process_enroll_new_user' ), 1000 );
		add_action( 'added_existing_user', array( $this, 'process_enroll_new_user' ) );

		add_action( 'wpcw_order_status_completed', array( $this, 'handle_student_enrollment' ), 10, 2 );
		add_action( 'wpcw_order_status_refunded', array( $this, 'handle_student_refunded' ), 10, 2 );
		add_action( 'wpcw_order_status_cancelled', array( $this, 'handle_student_cancellation' ), 10, 2 );
		add_action( 'wpcw_subscription_status_changed', array( $this, 'handle_student_enrollment_for_subscription' ), 10, 4 );

		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/** New User Enrollment Methods ---------------------------------------------- */

	/**
	 * Enroll New User.
	 *
	 * @since 4.1.0
	 */
	public function enroll_new_user_dropdown() {
		?>
		<div id="wpcw-enroll-new-user-dropdown">
			<wpcw-enroll-new-user></wpcw-enroll-new-user>
		</div>
		<?php
	}

	/**
	 * Enroll New User Dropdown Scripts.
	 *
	 * @since 4.1.0
	 */
	public function enroll_new_user_dropdown_scripts( $admin_screen, $admin_vars ) {
		if ( $admin_screen->id !== 'user' ) {
			return;
		}

		echo wpcw_admin_get_view( 'enrollment/enroll-new-user' );
	}

	/**
	 * Process Enroll New User.
	 *
	 * @since 4.1.0
	 *
	 * @param int $user_id The user id of the user that just was added.
	 */
	public function process_enroll_new_user( $user_id = 0 ) {
		$course_ids = isset( $_POST['wpcw-enroll-new-user-course'] ) ? $_POST['wpcw-enroll-new-user-course'] : false;

		if ( empty( $user_id ) || empty( $course_ids ) ) {
			return;
		}

		$course_ids = explode( ',', $course_ids );
		$course_ids = array_map( 'absint', $course_ids );

		// Enroll User.
		if ( ! empty( $course_ids ) ) {
			$this->enroll_student( $user_id, $course_ids, 'add' );
		}
	}

	/** Core Enrollment Methods ------------------------------------------------ */

	/**
	 * Enroll Student.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $student_id The student id.
	 * @param array  $course_ids The course ids to enroll.
	 * @param string $type Optional. The type of enrollment. Default is 'add'
	 * @param bool   $force Optional. Flag to force enrollment. Default is false.
	 */
	public function enroll_student( $student_id = 0, $course_ids = array(), $type = 'add', $force = false ) {
		if ( ! $student_id || empty( $course_ids ) ) {
			return;
		}

		WPCW_courses_syncUserAccess( $student_id, $course_ids, $type, false, false, $force );
	}

	/**
	 * Unenroll Student from a set of courses.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $student_id The student id.
	 * @param array $course_ids The course ids.
	 */
	public function unenroll_student( $student_id = 0, $course_ids = array() ) {
		if ( ! $student_id || empty( $course_ids ) ) {
			return;
		}

		global $wpdb, $wpcwdb;

		$csv_course_ids = implode( ',', $course_ids );

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id IN ($csv_course_ids)", $student_id ) );

		WPCW_queue_dripfeed::updateQueueItems_removeUser_fromCourseList( $student_id, $course_ids );
	}

	/** Order Enrollment Methods ------------------------------------------------ */

	/**
	 * Handle Student Enrollment.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $order_id The order id.
	 * @param Order $order The order object.
	 */
	public function handle_student_enrollment( $order_id, $order ) {
		if ( 'order' !== $order->get_order_type() ) {
			return;
		}

		$student_id = absint( $order->get_student_id() );
		$course_ids = array();

		if ( ! $student_id ) {
			return;
		}

		if ( $order_items = $order->get_order_items() ) {
			/** @var Order_Item $order_item */
			foreach ( $order_items as $order_item ) {
				// If ther order is recurring skip it. We will take care of subscription enrollment on a difference hooks.
				if ( $order_item->get_is_recurring() ) {
					continue;
				}

				if ( $course_id = $order_item->get_course_id() ) {
					$course_ids[] = $course_id;
				}
			}
		}

		$course_ids = array_map( 'absint', $course_ids );

		if ( ! empty( $course_ids ) && $student_id ) {
			$this->enroll_student( $student_id, $course_ids );
		}

		/**
		 * Action: Student Enrolled After Payment.
		 *
		 * @since 4.3.0
		 *
		 * @param int   $student_id The student Id.
		 * @param array $course_ids The course ids.
		 * @param Order $order The order object.
		 */
		do_action( 'wpcw_student_enrolled_after_payment', $student_id, $course_ids, $order );
	}

	/**
	 * Handle Student Refunded.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $order_id The order id.
	 * @param Order $order The order object.
	 */
	public function handle_student_refunded( $order_id, $order ) {
		$student_id = absint( $order->get_student_id() );
		$course_ids = array();

		if ( ! $student_id ) {
			return;
		}

		if ( $order_items = $order->get_order_items() ) {
			/** @var Order_Item $order_item */
			foreach ( $order_items as $order_item ) {
				if ( $course = $order_item->get_course() ) {
					$course_ids[] = $course->get_id();

					if ( $course_bundles = $course->get_course_bundles() ) {
						foreach ( $course_bundles as $course_bundle_id ) {
							$course_ids[] = $course_bundle_id;
						}
					}
				}
			}
		}

		$course_ids = array_map( 'absint', $course_ids );

		if ( ! empty( $course_ids ) && $student_id ) {
			$this->unenroll_student( $student_id, $course_ids );
		}

		/**
		 * Action: Student Refunded.
		 *
		 * @since 4.3.0
		 *
		 * @param int   $student_id The student Id.
		 * @param array $course_ids The course ids.
		 * @param Order $order The order object.
		 */
		do_action( 'wpcw_student_refunded', $student_id, $course_ids, $order );
	}

	/**
	 * Handle Student Cancellation.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $order_id The order id.
	 * @param Order $order The order object.
	 */
	public function handle_student_cancellation( $order_id, $order ) {
		if ( 'order' !== $order->get_order_type() ) {
			return;
		}

		$student_id = absint( $order->get_student_id() );
		$course_ids = array();

		if ( ! $student_id ) {
			return;
		}

		if ( $order_items = $order->get_order_items() ) {
			/** @var Order_Item $order_item */
			foreach ( $order_items as $order_item ) {
				if ( $course = $order_item->get_course() ) {
					$course_ids[] = $course->get_id();

					if ( $course_bundles = $course->get_course_bundles() ) {
						foreach ( $course_bundles as $course_bundle_id ) {
							$course_ids[] = $course_bundle_id;
						}
					}
				}
			}
		}

		$course_ids = array_map( 'absint', $course_ids );

		if ( ! empty( $course_ids ) && $student_id ) {
			$this->unenroll_student( $student_id, $course_ids );
		}

		/**
		 * Action: Student Cancellation.
		 *
		 * @since 4.3.0
		 *
		 * @param int   $student_id The student Id.
		 * @param array $course_ids The course ids.
		 * @param Order $order The order object.
		 */
		do_action( 'wpcw_student_cancellation', $student_id, $course_ids, $order );
	}

	/**
	 * Handle Student Enrollment - Subscription
	 *
	 * @since 4.3.0
	 *
	 * @param int          $subscription_id The subscription id.
	 * @param string       $previous_status The previous status.
	 * @param string       $current_status The current status.
	 * @param Subscription $subscription The subscription object.
	 */
	public function handle_student_enrollment_for_subscription( $subscription_id, $previous_status, $current_status, $subscription ) {
		$course     = $subscription->get_course();
		$student_id = $subscription->get_student_id();

		if ( ! $course || ! $student_id ) {
			return;
		}

		$course_ids   = array();
		$course_ids[] = $course->get_id();

		if ( $course_bundles = $course->get_course_bundles() ) {
			foreach ( $course_bundles as $course_bundle_id ) {
				$course_ids[] = $course_bundle_id;
			}
		}

		switch ( $current_status ) {
			case 'active' :
				$this->enroll_student( $student_id, $course_ids );
				break;
			case 'on-hold' :
			case 'suspended' :
			case 'cancelled' :
			case 'expired' :
				$this->unenroll_student( $student_id, $course_ids );
				break;
		}
	}

	/** API Endpoint Methods -------------------------------------------------- */

	/**
	 * Register Course Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object reference.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'enroll-users', 'method' => 'POST', 'callback' => array( $this, 'api_enroll_users' ) );
		$endpoints[] = array( 'endpoint' => 'bulk-enroll-students', 'method' => 'POST', 'callback' => array( $this, 'api_bulk_enroll_students' ) );
		$endpoints[] = array( 'endpoint' => 'enroll-classroom', 'method' => 'POST', 'callback' => array( $this, 'api_enroll_classroom' ) );

		return $endpoints;
	}

	/**
	 * Api: Enroll Users
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_enroll_users( WP_REST_Request $request ) {
		$course = $request->get_param( 'course' );
		$users  = $request->get_param( 'users' );

		if ( ! $course ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the course id. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		if ( ! $users ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You did not select any students.', 'wp-courseware' ),
			) );
		}

		// Sanitize Them.
		$users = array_map( 'absint', $users );

		foreach ( $users as $user ) {
			$this->enroll_student( $user, array( $course ), 'add' );
		}

		$success         = true;
		$success_message = esc_html__( 'Students Enrolled Successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}

	/**
	 * Api: Bulk Enroll Students
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_bulk_enroll_students( WP_REST_Request $request ) {
		$courses  = $request->get_param( 'courses' );
		$students = $request->get_param( 'students' );

		if ( ! $courses ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You did not select one or more courses. Please select a course.', 'wp-courseware' ),
			) );
		}

		if ( ! $students ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You did not select any students.', 'wp-courseware' ),
			) );
		}

		// Sanitize Them.
		$students = array_map( 'absint', $students );

		foreach ( $students as $student ) {
			$this->enroll_student( $student, $courses, 'add' );
		}

		$success         = true;
		$success_message = esc_html__( 'Students Enrolled Successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}

	/**
	 * Api: Enroll Classroom.
	 *
	 * @since 4.5.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_enroll_classroom( WP_REST_Request $request ) {
		$course  = $request->get_param( 'course' );
		$courses = $request->get_param( 'courses' );

		if ( ! $course ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the course id. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		if ( ! $courses ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You did not select one or more courses. Please select a course.', 'wp-courseware' ),
			) );
		}

		// Student Results
		$students_results = wpcw()->students->get_students( array( 'course_id' => absint( $course ), 'number' => - 1, 'fields' => 'ids' ) );

		// Check.
		if ( ! $students_results ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'There are currently not students in the classroom to enroll. Please add some students and try again.', 'wp-courseware' ),
			) );
		}

		// Sanitize Them.
		$students = array_map( 'absint', $students_results );

		foreach ( $students as $student ) {
			$this->enroll_student( $student, $courses, 'add' );
		}

		$success         = true;
		$success_message = esc_html__( 'Classroom Enrolled Successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}
}
