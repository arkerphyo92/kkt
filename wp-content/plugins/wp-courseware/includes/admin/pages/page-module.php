<?php
/**
 * WP Courseware Module Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Module.
 *
 * @since 4.1.0
 */
class Page_Module extends Page {

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-modules';
	}

	/**
	 * Get Module Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Course Module', 'wp-courseware' );
	}

	/**
	 * Get Module Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Course Module', 'wp-courseware' );
	}

	/**
	 * Get Module Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_module_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Module Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'WPCW_showPage_ModifyModule';
	}

	/**
	 * Get Module Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_callback() {
		return 'WPCW_showPage_ModifyModule';
	}

	/**
	 * Is Module Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}