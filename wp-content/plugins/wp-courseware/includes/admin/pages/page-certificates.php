<?php
/**
 * WP Courseware Cerificates Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.8.0
 */
namespace WPCW\Admin\Pages;

use WP_Query;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Certificates.
 *
 * @since 4.8.0
 */
class Page_Certificates extends Page {

	/**
	 * @var string Post Type Slug.
	 * @since 4.8.0
	 */
	protected $post_type = 'wpcw_certificates';

	/**
	 * Certificates - Setup.
	 *
	 * @since 4.8.0
	 */
	protected function setup() {
		add_action( 'admin_head', array( $this, 'hide_post_type_menu' ) );
		add_action( 'admin_head', array( $this, 'hightlight_submenu_add_edit' ) );
		add_action( 'admin_head', array( $this, 'add_icon_to_title' ) );
	}

	/**
	 * Hide Certificates Post Type Menu.
	 *
	 * @since 4.8.0
	 */
	public function hide_post_type_menu() {
		if ( empty( $this->admin ) ) {
			return;
		}

		$this->admin->hide_top_menu( 'edit.php?post_type=' . $this->post_type );
	}

	/**
	 * Highlight Submenu on Post Type Add / Edit
	 *
	 * @since 4.8.0
	 */
	public function hightlight_submenu_add_edit() {
		global $current_screen, $parent_file, $submenu_file;

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( $current_screen->post_type !== 'wpcw_certificates' ) {
			return;
		}

		$parent_file  = $this->admin->get_slug();
		$submenu_file = $this->get_slug();

	}

	/**
	 * Add Icon to Title.
	 *
	 * @since 4.8.0
	 */
	public function add_icon_to_title() {
		global $current_screen;

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( $this->post_type !== $current_screen->post_type ) {
			return;
		}

		echo '<style type="text/css">
                .wrap h1.wp-heading-inline {
                    position: relative;
                    padding-top: 4px;
                    padding-left: 50px;
                }
                .wrap h1.wp-heading-inline:before {
                    background-image: url("' . wpcw_image_file( 'wp-courseware-icon.svg' ) . '");
                    background-size: 40px 40px;
                    content: "";
                    display: inline-block;
                    position: absolute;
                    top: -2px;
                    left: 0;
                    width: 40px;
                    height: 40px;
                }
            </style>';
	}

	/**
	 * Get Units Page Menu Title.
	 *
	 * @since 4.8.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Certificates', 'wp-courseware' );
	}

	/**
	 * Get Units Page Title.
	 *
	 * @since 4.8.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Units', 'wp-courseware' );
	}

	/**
	 * Get Certificates Page Slug.
	 *
	 * @since 4.8.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return esc_url( add_query_arg( array( 'post_type' => $this->post_type ), 'edit.php' ) );
	}

	/**
	 * Get Admin Url.
	 *
	 * @since 4.8.0
	 *
	 * @return string The admin url.
	 */
	public function get_url() {
		return admin_url( $this->get_slug() );
	}

	/**
	 * Get Units Page Callback.
	 *
	 * @since 4.8.0
	 *
	 * @return null
	 */
	protected function get_callback() {
		return null;
	}

	/**
	 * Get Units Page hook.
	 *
	 * @since 4.8.0
	 */
	public function get_hook() {
		return '';
	}
}
