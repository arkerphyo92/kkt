<?php
/**
 * WP Courseware Student Access Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Student_Access.
 *
 * @since 4.1.0
 */
class Page_Student_Access extends Page {

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-students';
	}

	/**
	 * Get Student Access Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Student Access', 'wp-courseware' );
	}

	/**
	 * Get Student Access Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Student Access', 'wp-courseware' );
	}

	/**
	 * Get Student Access Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_students_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Student Access Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_UserCourseAccess';
	}

	/**
	 * Get Student Access Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_callback() {
		return 'WPCW_showPage_UserCourseAccess';
	}

	/**
	 * Is Student Access Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}