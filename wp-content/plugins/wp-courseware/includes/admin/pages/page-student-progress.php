<?php
/**
 * WP Courseware Student Progress Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Student_Progress.
 *
 * @since 4.1.0
 */
class Page_Student_Progress extends Page {

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
	 * Get Student Progress Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Student Progress', 'wp-courseware' );
	}

	/**
	 * Get Student Progress Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Student Progress', 'wp-courseware' );
	}

	/**
	 * Get Student Progress Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_students_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Student Progress Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_UserProgess';
	}

	/**
	 * Get Student Progress Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_callback() {
		return 'WPCW_showPage_UserProgess';
	}

	/**
	 * Is Student Progress Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}