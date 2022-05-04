<?php
/**
 * WP Courseware Quizzes Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WPCW\Database\DB_Quizzes;
use WPCW\Models\Quiz;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Quizzes.
 *
 * @since 4.2.0
 */
class Quizzes extends Controller {

	/**
	 * @var DB_Quizzes The quizzes db object.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Quizzes constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Quizzes();
	}

	/**
	 * Quizzes Load.
	 *
	 * @since 4.3.0
	 */
	public function load() { /* Do nothing for now */ }

	/**
	 * Get Quiz by Id.
	 *
	 * @since 4.2.0
	 *
	 * @param int $quiz_id The quiz id.
	 *
	 * @return bool|Quiz The quiz object.
	 */
	public function get_quiz( $quiz_id ) {
		if ( 0 === absint( $quiz_id ) ) {
			return false;
		}

		$result = $this->db->get( $quiz_id );

		if ( ! $result ) {
			return false;
		}

		return new Quiz( $result );
	}

	/**
	 * Get Quizzes.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args Optional. Query Arguments.
	 * @param bool  $raw Optional. Return the raw database data.
	 *
	 * @return array Array of quizzes objects.
	 */
	public function get_quizzes( $args = array(), $raw = false ) {
		$quizzes = array();
		$results = $this->db->get_quizzes( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$quizzes[] = new Quiz( $result );
		}

		return $quizzes;
	}

	/**
	 * Get Number of Quizzes.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of quizzes.
	 */
	public function get_quizzes_count( $args = array() ) {
		return $this->db->get_quizzes( $args, true );
	}

	/**
	 * Get Quiz Course Title.
	 *
	 * @since 4.2.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string The course title.
	 */
	public function get_quiz_course_title( $course_id ) {
		return $this->db->get_quiz_course_title( $course_id );
	}

	/**
	 * Get Quiz Unit Title.
	 *
	 * @since 4.2.0
	 *
	 * @param int $unit_id The unit id.
	 *
	 * @return string The unit title.
	 */
	public function get_quiz_unit_title( $unit_id ) {
		return $this->db->get_quiz_unit_title( $unit_id );
	}

	/**
	 * Delete Quiz.
	 *
	 * @since 4.2.0
	 *
	 * @param int $quiz_id The quiz id.
	 *
	 * @return Quiz|false The quiz object or false.
	 */
	public function delete_quiz( $quiz_id ) {
		if ( ! is_admin() || ! current_user_can( 'view_wpcw_courses' ) ) {
			return false;
		}

		if ( $quiz = $this->get_quiz( $quiz_id ) ) {
			global $wpdb, $wpcwdb;

			if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
				if ( $quiz->get_quiz_author() !== get_current_user_id() ) {
					return false;
				}
			}

			// Delete Quiz Questions from question map.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->quiz_qs_mapping WHERE parent_quiz_id = %d", $quiz->get_quiz_id() ) );

			// Delete User Progress.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_progress_quiz WHERE quiz_id = %d", $quiz->get_quiz_id() ) );

			// Delete Quiz.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->quiz WHERE quiz_id = %d", $quiz->get_quiz_id() ) );

			// Return the object.
			return $quiz;
		}

		return false;
	}

	/**
	 * Get the name for the type of quiz being shown.
	 *
	 * @since 4.2.0
	 *
	 * @param string $type The type of the quiz.
	 *
	 * @return string The actual name of the quiz type.
	 */
	public function get_quiz_type_name( $type ) {
		switch ( $type ) {
			case 'survey':
				return esc_html__( 'Survey', 'wp-courseware' );
				break;

			case 'quiz_block':
				return esc_html__( 'Quiz - Blocking', 'wp-courseware' );
				break;

			case 'quiz_noblock':
				return esc_html__( 'Quiz - Non-Blocking', 'wp-courseware' );
				break;
		}

		return false;
	}

	/**
	 * Get Quiz Needs Manual Grading Count.
	 *
	 * @since 4.4.5
	 *
	 * @return int $quizzes_count The number of quizzes that require manual grading.
	 */
	public function get_quiz_needs_manual_grading_count() {
		global $wpdb, $wpcwdb;

		// Current User.
		$current_user = wp_get_current_user();

		// Check permissions and only show notifications from the authors quizes
		if ( is_admin() && ! current_user_can( 'manage_wpcw_settings' ) ) {
			return $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpcwdb->user_progress_quiz} upq
				 LEFT JOIN {$wpcwdb->quiz} q ON q.quiz_id = upq.quiz_id 
				 WHERE quiz_is_latest = 'latest' 
				 AND (quiz_needs_marking > 0 OR quiz_next_step_type = 'quiz_fail_no_retakes') 
				 AND quiz_author = %d;",
				$current_user->ID
			) );
		}

		return $wpdb->get_var(
			"SELECT COUNT(*) 
			 FROM {$wpcwdb->user_progress_quiz} 
			 WHERE quiz_is_latest = 'latest' 
			 AND (quiz_needs_marking > 0 OR quiz_next_step_type = 'quiz_fail_no_retakes');"
		);
	}
}
