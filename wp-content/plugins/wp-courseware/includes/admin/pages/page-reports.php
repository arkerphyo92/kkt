<?php
/**
 * WP Courseware Reports Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.3.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Reports.
 *
 * @since 4.3.0
 */
class Page_Reports extends Page {

	/**
	 * Get Reports Menu Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Reports', 'wp-courseware' );
	}

	/**
	 * Get Reports Page Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Reports', 'wp-courseware' );
	}

	/**
	 * Get Reports Page Capability.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_reports_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Reports Page Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-reports';
	}

	/**
	 * Reports Page Display.
	 *
	 * @since 4.3.0
	 */
	protected function display() {
		do_action( 'wpcw_admin_reports_before', $this );

		echo '<div id="wpcw-reports">';

		// $this->get_notices();
		// $this->get_tabs_navigation();
		// $this->get_tab_content();

		echo '</div>';

		do_action( 'wpcw_admin_reports_after', $this );
	}
}