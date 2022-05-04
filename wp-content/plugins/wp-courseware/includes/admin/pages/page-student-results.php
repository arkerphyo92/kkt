<?php
/**
 * WP Courseware Student Results Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Student_Results.
 *
 * @since 4.1.0
 */
class Page_Student_Results extends Page {

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'edit.php?post_type=wpcw_course';
	}

	/**
	 * Get Student Results Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Student Results', 'wp-courseware' );
	}

	/**
	 * Get Student Results Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Student Results', 'wp-courseware' );
	}

	/**
	 * Get Student Results Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_students_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Student Results Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_UserProgess_quizAnswers';
	}

	/**
	 * Get Student Results Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_callback() {
		return 'WPCW_showPage_UserProgess_quizAnswers';
	}

	/**
	 * Is Student Results Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}
