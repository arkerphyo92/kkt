<?php
/**
 * WP Courseware Pages Class.
 *
 * @package WPCW
 * @subpackage Admin
 * @since 4.3.0
 */
namespace WPCW\Admin;

use WPCW\Admin\Pages\Page;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Pages.
 *
 * @since 4.1.0
 */
class Pages {

	/**
	 * @var array Array of Page objects.
	 * @since 4.1.0
	 */
	protected $pages = array();

	/**
	 * Register Page.
	 *
	 * @since 4.1.0
	 *
	 * @param Page $page The Admin Page.
	 */
	public function register_page( Page $page ) {
		$this->pages[ $page->get_id() ] = $page;
	}

	/**
	 * Get Page.
	 *
	 * @since 4.4.0
	 *
	 * @param string $id The id of the page.
	 *
	 * @return Page $page The admin page object if set, false otherwise.
	 */
	public function get_page( $id ) {
		return isset( $this->pages[ $id ] ) ? $this->pages[ $id ] : false;
	}

	/**
	 * Get Current Page.
	 *
	 * @since 4.1.0
	 *
	 * @return Page|bool $page The current page, if not set false.
	 */
	public function get_current_page() {
		$page = $this->get_page( wpcw_get_var( 'page' ) );

		if ( ! $page ) {
			return false;
		}

		return $page;
	}

	/**
	 * Is Current Page?
	 *
	 * @param string $slug The slug to be matched.
	 * @param Page|bool $current_page The current page. Could be false.
	 *
	 * @return bool
	 */
	public function is_current_page( $slug, $current_page ) {
		if ( ! $current_page instanceof Page ) {
			return false;
		}

		return (bool) ( $slug === $current_page->get_slug() );
	}

	/**
	 * Is Admin Page?
	 *
	 * @param $slug
	 *
	 * @return bool|Page
	 */
	public function is_admin_page( $slug ) {
		return $this->get_page( $slug );
	}

	/**
	 * Get All Pages.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	public function get_all() {
		return $this->pages;
	}
}