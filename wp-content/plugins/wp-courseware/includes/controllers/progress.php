<?php
/**
 * WP Courseware Progress Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.6.4
 */

namespace WPCW\Controllers;

use WPCW\Controllers\Controller;
use WPCW\Models\Course;
use WPCW\Models\Quiz;
use WPCW\Models\Student;
use WPCW\Models\Student_Course;
use WPCW\Models\Student_Quiz;
use WPCW\Models\Unit;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Progress.
 *
 * @since 4.6.4
 */
class Progress extends Controller {

	/**
	 * Load Progress.
	 *
	 * @since 4.6.4
	 */
	public function load() {
		add_filter( 'wpcw_ajax_api_events', array( $this, 'ajax_events' ) );
	}

	/**
	 * Ajax Events.
	 *
	 * @since 4.6.4
	 *
	 * @param array $ajax_events The ajax Events.
	 */
	public function ajax_events( $ajax_events ) {
		$progress_ajax_events = array(
			'reset-unit-progress' => array( $this, 'ajax_reset_unit_progress' ),
		);

		return array_merge( $ajax_events, $progress_ajax_events );
	}

	/** -- Ajax Methods ----------------------- */

	/**
	 * Ajax: Reset Unit Progress
	 *
	 * @since 4.6.3
	 */
	public function ajax_reset_unit_progress() {
		wpcw()->ajax->verify_nonce();

		$unit_id    = wpcw_post_var( 'unit_id' );
		$student_id = wpcw_post_var( 'student_id' );
		$reset_type = wpcw_post_var( 'reset_type' );

		if ( ! $unit_id || ! $student_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'An error occurred. Please try again.' ) ) );
		}

		try {
			$course_map = new \WPCW_CourseMap();
			$course_map->loadDetails_byUnitID( $unit_id );

			$students = array( $student_id );
			$units    = 'bulk' === $reset_type ? $course_map->getUnitIDList_afterUnit( $unit_id ) : array( $unit_id );

			wpcw_reset_progress( $course_map->getCourseDetails(), $students, $units );
		} catch ( \Exception $exception ) {
			wpcw_log( $exception->getMessage() );
			wp_send_json_error( array( 'message' => esc_html__( 'An error occurred. Please try again.' ) ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'Progress reset successfully! Press OK to reload the page.', 'wp-courseware' ) ) );
	}
}
