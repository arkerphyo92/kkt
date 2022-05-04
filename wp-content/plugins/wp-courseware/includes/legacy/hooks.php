<?php
/**
 * WP Courseware Legacy Hooks.
 *
 * @package WPCW
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( is_admin() ) {
	// Units
	add_action( 'save_post', 'WPCW_units_saveUnitPostMetaData', 10, 2 );
	add_action( 'delete_post', 'WPCW_units_deleteUnitHandler' );
	add_action( 'trashed_post', 'WPCW_units_deleteUnitHandler_inTrash' );

	// AJAX - Admin
	add_action( 'wp_ajax_wpcw_handle_unit_ordering_saving', 'WPCW_AJAX_handleUnitOrderingSaving' );
	add_action( 'wp_ajax_wpcw_handle_unit_duplication', 'WPCW_AJAX_handleUnitDuplication' );
	add_action( 'wp_ajax_wpcw_handle_course_duplication', 'WPCW_AJAX_handleCourseDuplication' );
	add_action( 'wp_ajax_wpcw_handle_modules_duplication', 'WPCW_AJAX_handleModulesDuplication' );
	add_action( 'wp_ajax_wpcw_handle_quizzes_duplication', 'WPCW_AJAX_handleQuizzesDuplication' );
	add_action( 'wp_ajax_wpcw_handle_question_new_tag', 'WPCW_AJAX_handleQuestionNewTag' );
	add_action( 'wp_ajax_wpcw_handle_question_remove_tag', 'WPCW_AJAX_handleQuestionRemoveTag' );
	add_action( 'wp_ajax_wpcw_handle_tb_action_question_pool', 'WPCW_AJAX_handleThickboxAction_QuestionPool' );
	add_action( 'wp_ajax_wpcw_handle_tb_action_add_question', 'WPCW_AJAX_handleThickboxAction_QuestionPool_addQuestion' );
	add_filter( 'post_row_actions', 'WPCW_units_admin_addActionRows', 10, 2 );

	// CSV Export
	add_action( 'wp_loaded', 'WPCW_data_handleDataExport' );

	// Students Reset Funciontalilty
	add_action( 'init', 'WPCW_users_processUserResetAbility' );
	add_action( 'admin_init', 'WPCW_users_processUserResetAbility_showSuccess' );
} else {
	// Post Content
	if ( class_exists( 'MathJax' ) || class_exists( 'MathJax_Latex' ) ) {
		add_filter( 'the_content', 'WPCW_units_processUnitContent' );
	}

	// Templates - Course Units
	add_filter( 'single_template', 'WPCW_templates_units_filterTemplateForUnit' );
}

// AJAX - Frontend
add_action( 'wp_ajax_wpcw_handle_unit_track_progress', 'WPCW_AJAX_units_handleUserProgress' );
add_action( 'wp_ajax_nopriv_wpcw_handle_unit_track_progress', 'WPCW_AJAX_units_handleUserProgress' );
add_action( 'wp_ajax_wpcw_handle_unit_quiz_response', 'WPCW_AJAX_units_handleQuizResponse' );
add_action( 'wp_ajax_nopriv_wpcw_handle_unit_quiz_response', 'WPCW_AJAX_units_handleQuizResponse' );
add_action( 'wp_ajax_wpcw_handle_unit_quiz_retake_request', 'WPCW_AJAX_units_handleQuizRetakeRequest' );
add_action( 'wp_ajax_nopriv_wpcw_handle_unit_quiz_retake_request', 'WPCW_AJAX_units_handleQuizRetakeRequest' );
add_action( 'wp_ajax_wpcw_handle_unit_quiz_jump_question', 'WPCW_AJAX_units_handleQuizJumpQuestion' );
add_action( 'wp_ajax_nopriv_wpcw_handle_unit_quiz_jump_question', 'WPCW_AJAX_units_handleQuizJumpQuestion' );
add_action( 'wp_ajax_wpcw_handle_unit_quiz_timer_begin', 'WPCW_AJAX_units_handleQuizTimerBegin' );
add_action( 'wp_ajax_nopriv_wpcw_handle_unit_quiz_timer_begin', 'WPCW_AJAX_units_handleQuizTimerBegin' );
add_action( 'wp_ajax_wpcw_handle_course_enrollment_button', 'WPCW_AJAX_course_handleEnrollment_button' );
add_action( 'wp_ajax_nopriv_wpcw_handle_course_enrollment_button', 'WPCW_AJAX_course_handleEnrollment_button' );

// Action when admin has updated the course details.
add_action( 'wpcw_course_details_updated', 'WPCW_actions_courses_courseDetailsUpdated' );

// Action when user has completed a unit / module / course.
add_action( 'wpcw_user_completed_unit', 'WPCW_actions_users_unitCompleted', 10, 3 );
add_action( 'wpcw_user_completed_module', 'WPCW_actions_users_moduleCompleted', 10, 3 );
add_action( 'wpcw_user_completed_course', 'WPCW_actions_users_courseCompleted', 10, 3 );

// Modified modules - when a module is created or edited
add_action( 'wpcw_modules_modified', 'WPCW_actions_modules_modulesModified' );

// Action called when user has been created
add_action( 'user_register', 'WPCW_actions_users_newUserCreated' );
add_action( 'register_form', 'WPCW_course_enrollment_via_shortcode' );
add_action( 'wpcw_register_form', 'WPCW_course_enrollment_via_shortcode' );

// Action called when user has been deleted
add_action( 'delete_user', 'WPCW_actions_users_userDeleted' );

// Action called when quiz has been completed and needs grading or needs attention as user is blocked.
add_action( 'wpcw_quiz_needs_grading', 'WPCW_actions_userQuizNeedsGrading_notifyAdmin', 10, 2 );
add_action( 'wpcw_quiz_user_needs_unblocking', 'WPCW_actions_userQuizUserNeedsUnblocking_notifyAdmin', 10, 2 );

// Action called when quiz has been graded or needs attention
add_action( 'wpcw_quiz_graded', 'WPCW_actions_userQuizGraded_notifyUser', 10, 4 );

// Add handler for cron for sending out notifications
add_action( WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID, 'WPCW_cron_notifications_dripfeed' );

// PDF Generation
add_action( 'parse_request', 'WPCW_create_pdf_certificates' );
add_action( 'parse_request', 'WPCW_create_pdf_results' );
