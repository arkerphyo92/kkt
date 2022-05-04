<?php
/**
 * WP Courseware Abstract Table.
 *
 * All wp list tables should extend this class.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.3.0
 */
namespace WPCW\Admin\Tables;

// Include WP List Table if it doesn't exist
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use WPCW\Admin\Pages\Page;
use WP_List_Table;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Table.
 *
 * @since 4.1.0
 */
abstract class Table extends WP_List_Table {

	/**
	 * @var Page The current page.
	 * @since 4.1.0
	 */
	protected $page;

	/**
	 * @var \WP_Screen The current screen object.
	 * @since 4.1.0
	 */
	protected $screen;

	/**
	 * @var string Per Page Option.
	 * @since 4.1.0
	 */
	protected $per_page_option;

	/**
	 * @var int Data per page.
	 * @since 4.1.0
	 */
	protected $per_page;

	/**
	 * @var int The total count.
	 * @since 4.1.0
	 */
	protected $total;

	/**
	 * @var int The current count.
	 * @since 4.1.0
	 */
	protected $count;

	/**
	 * List Table Constructor.
	 *
	 * @since 4.1.0
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct( $args = array() ) {
		if ( empty( $args['singular'] ) || empty( $args['plural'] ) ) {
			return;
		}

		$this->screen = get_current_screen();

		// Page.
		if ( ! empty( $args['page'] ) ) {
			$this->page = $args['page'];
			unset( $args['page'] );
		}

		// Per Page Option.
		if ( ! empty( $args['per_page_option'] ) ) {
			$this->per_page_option = sanitize_key( $args['per_page_option'] );
			unset( $args['per_page_option'] );
		} else {
			$this->per_page_option = "{$args['plural']}_per_page";
		}

		// Per Page Default.
		if ( ! empty( $args['per_page'] ) ) {
			$this->per_page = absint( $args['per_page'] );
			unset( $args['per_page'] );
		} else {
			$this->per_page = apply_filters( 'wpcw_admin_list_table_per_page_default', 20 );
		}

		$args = wp_parse_args( $args, array(
			'ajax'   => false,
			'screen' => $this->screen,
		) );

		parent::__construct( $args );
	}

	/**
	 * Process Action Search.
	 *
	 * @since 4.3.0
	 */
	public function process_action_search() {
		$search_value = ! empty( $_GET['s'] ) ? $_GET['s'] : '';

		$url = $this->page->get_url();

		if ( $search_value ) {
			$url = add_query_arg( array( 's' => urlencode( $search_value ) ), $url );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Get Row Action Link.
	 *
	 * @since 4.1.0
	 *
	 * @param string $label The row action label.
	 * @param array $query_args The query argumnets.
	 * @param array $args Optional. The additional args for the link
	 *
	 * @return string The row action link.
	 */
	public function get_row_action_link( $label, $query_args, $args = array() ) {
		$base_uri = empty( $args['base_uri'] ) ? false : $args['base_uri'];

		if ( empty( $args['nonce'] ) ) {
			$url = esc_url( add_query_arg( $query_args, $base_uri ) );
		} else {
			$url = wp_nonce_url( add_query_arg( $query_args, $base_uri ), $args['nonce'] );
		}

		$class = empty( $args['class'] ) ? '' : sprintf( ' class="%s"', esc_attr( $args['class'] ) );
		$title = empty( $args['title'] ) ? '' : sprintf( ' title="%s"', esc_attr( $args['title'] ) );
		$atts  = empty( $args['atts'] ) ? '' : ' ' . $args['atts'];

		return sprintf( '<a href="%1$s"%2$s%3$s%4$s>%5$s</a>', $url, $class, $title, $atts, esc_html( $label ) );
	}
}