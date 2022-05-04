<?php
/**
 * WP Courseware Course Module & Unit Ordering Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Course_Ordering.
 *
 * @since 4.1.0
 */
class Page_Course_Ordering extends Page {

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
	 * Get Course Ordering Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Module &amp; Unit Ordering', 'wp-courseware' );
	}

	/**
	 * Get Course Ordering Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Module &amp; Unit Ordering', 'wp-courseware' );
	}

	/**
	 * Get Course Ordering Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_course_ordering_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Course Ordering Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_CourseOrdering';
	}

	/**
	 * Get Course Ordering Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_callback() {
		return 'WPCW_showPage_CourseOrdering';
	}

	/**
	 * Is Course Ordering Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}