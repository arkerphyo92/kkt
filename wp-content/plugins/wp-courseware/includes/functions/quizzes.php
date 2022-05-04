<?php
/**
 * WP Courseware Quizzes Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.4.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Quiz.
 *
 * @since 4.4.0
 *
 * @param int|bool $quiz_id The Unit Id.
 *
 * @return \WPCW\Models\Quiz|bool An unit object or false.
 */
function wpcw_get_quiz( $quiz_id = false ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_quiz should not be called before the quiz object is setup.', '4.4.0' );

		return false;
	}

	return new \WPCW\Models\Quiz( $quiz_id );
}

/**
 * Insert Quiz.
 *
 * @since 4.4.0
 *
 * @param array $data The quiz data.
 *
 * @return \WPCW\Models\Quiz|bool An unit object or false upon failure.
 */
function wpcw_insert_quiz( $data = array() ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_insert_quiz should not be called before the quiz object is setup.', '4.4.0' );

		return false;
	}

	$quiz    = new \WPCW\Models\Quiz();
	$quiz_id = $quiz->create( $data );

	return $quiz_id ? $quiz : $quiz_id;
}

/**
 * Get Quizzes.
 *
 * @since 4.4.0
 *
 * @param array $args The quizzes query args.
 *
 * @return array The array of Quiz objects.
 */
function wpcw_get_quizzes( $args = array() ) {
	return wpcw()->quizzes->get_quizzes( $args );
}