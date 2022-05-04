<?php
/**
 * WP Courseware Question Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Question.
 *
 * @since 4.1.0
 */
class Page_Question extends Page {

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-questions';
	}

	/**
	 * Get Question Page Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Question', 'wp-courseware' );
	}

	/**
	 * Get Question Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Question', 'wp-courseware' );
	}

	/**
	 * Get Question Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_question_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Question Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_ModifyQuestion';
	}

	/**
	 * Get Question Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return array|string
	 */
	public function get_callback() {
		return 'WPCW_showPage_ModifyQuestion';
	}

	/**
	 * Is Question Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}