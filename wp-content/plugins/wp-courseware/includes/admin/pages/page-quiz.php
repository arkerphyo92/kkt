<?php
/**
 * WP Courseware Quiz / Survey Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Quiz.
 *
 * @since 4.1.0
 */
class Page_Quiz extends Page {

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-quizzes';
	}

	/**
	 * Get Quiz / Survey Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Quiz / Survey', 'wp-courseware' );
	}

	/**
	 * Get Quiz / Survey Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Quiz / Survey', 'wp-courseware' );
	}

	/**
	 * Get Quiz / Survey Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_quiz_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Quiz / Survey Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_ModifyQuiz';
	}

	/**
	 * Get Quiz / Survey Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_callback() {
		return 'WPCW_showPage_ModifyQuiz';
	}

	/**
	 * Is Quiz / Survey Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}