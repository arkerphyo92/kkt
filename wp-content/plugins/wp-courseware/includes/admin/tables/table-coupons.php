<?php
/**
 * WP Courseware Coupons Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.5.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Coupons;
use WPCW\Models\Coupon;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Table_Coupons.
 *
 * @since 4.5.0
 */
class Table_Coupons extends Table {

	/**
	 * @var Page_Coupons The Coupons Admin Page.
	 * @since 4.5.0
	 */
	protected $page;

	/**
	 * Courses Table Constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'coupon',
			'plural'   => 'coupons',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->total = $this->get_coupons_count();
	}

	/**
	 * Get Table Columns
	 *
	 * @since 4.5.0
	 *
	 * @return array $columns An array of columns displayed in the table.
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'coupon_code'   => esc_html__( 'Coupon Code', 'wp-courseware' ),
			'coupon_type'   => esc_html__( 'Coupon Type', 'wp-courseware' ),
			'coupon_amount' => esc_html__( 'Coupon Amount', 'wp-courseware' ),
			//'course_ids'    => esc_html__( 'Courses', 'wp-courseware' ),
			'usage_limit'   => esc_html__( 'Usage / Limit', 'wp-courseware' ),
			//'start_date'    => esc_html__( 'Start Date', 'wp-courseware' ),
			'end_date'      => esc_html__( 'Expiry Date', 'wp-courseware' ),
			//'date_created'  => esc_html__( 'Date Created', 'wp-courseware' ),
		);

		return $columns;
	}

	/**
	 * Get Sortable Columns.
	 *
	 * @since 4.5.0
	 *
	 * @return array $columns An array of sortable displayed in the table.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'coupon_code'   => array( 'code', false ),
			'coupon_type'   => array( 'type', false ),
			'coupon_amount' => array( 'amount', false ),
			'usage_limit'   => array( 'usage_limit', false ),
			'start_date'    => array( 'start_date', false ),
			'end_date'      => array( 'end_date', false ),
			//'date_created' => array( 'date_created', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Coupon $coupon The coupon object.
	 * @param string $column_name The column name.
	 *
	 * @return mixed The column value.
	 */
	public function column_default( $coupon, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $coupon->$column_name ) ? $coupon->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $coupon ) {
		return '<input type="checkbox" name="coupon_id[]" value="' . absint( $coupon->get_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return int The coupon id.
	 */
	public function column_id( $coupon ) {
		return $coupon->get_id();
	}

	/**
	 * Column Code.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupond code column string.
	 */
	public function column_coupon_code( $coupon ) {
		$row_actions = array();

		$coupon_id = $coupon->get_id();

		$base_query_args = array(
			'page'      => $this->page->get_slug(),
			'coupon_id' => $coupon_id,
		);

		$edit_coupon_url   = add_query_arg( array_merge( $base_query_args, array( 'page' => 'wpcw-coupon' ) ), admin_url( 'admin.php' ) );
		$delete_coupon_url = add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) );

		$title = sprintf( '<a class="row-title" href="%s">%s</a>', esc_url_raw( $edit_coupon_url ), $coupon->get_code() );

		$row_actions['coupon_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $coupon_id );

		$row_actions['edit_coupon'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => esc_url_raw( $edit_coupon_url ) )
		);

		$row_actions['delete_coupon'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => esc_url_raw( $delete_coupon_url ),
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete this coupon?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'coupons-nonce',
			)
		);

		$title .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $title;
	}

	/**
	 * Column Type.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon type.
	 */
	public function column_coupon_type( $coupon ) {
		return esc_html( wpcw_get_coupon_type( $coupon->get_type() ) );
	}

	/**
	 * Column Amount.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon type.
	 */
	public function column_coupon_amount( $coupon ) {

		$amount = $coupon->get_amount();
		if ( 0 > $amount ) {
			$amount = 0;
		}
		$amount = number_format( $amount, 2 );
		if ( 'percentage' === $coupon->get_type() ) {
			$coupon_amount = $amount . '%';
		} else {
			$coupon_amount = '$' . $amount;
		}
		return esc_html( $coupon_amount );

		// return esc_html( wpcw_format_localized_price( $coupon->get_amount() ) );.
	}

	/**
	 * Column Course Ids.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon type.
	 */
	public function column_course_ids( $coupon ) {
		$course_ids = $coupon->get_course_ids();

		if ( count( $course_ids ) > 0 ) {
			return esc_html( implode( ', ', $course_ids ) );
		} else {
			return '&ndash;';
		}
	}

	/**
	 * Column Usage Limit.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon type.
	 */
	public function column_usage_limit( $coupon ) {
		$usage_count = $coupon->get_usage_count();
		$usage_limit = $coupon->get_usage_limit();

		/* translators: %1$s: count %2$s: limit */
		return sprintf( __( '%1$s / %2$s', 'wp-courseware' ), esc_html( $usage_count ), $usage_limit ? esc_html( $usage_limit ) : '&infin;' );
	}

	/**
	 * Column Start Date.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon start date string.
	 */
	public function column_start_date( $coupon ) {
		$start_date = $coupon->get_start_date();

		$coupon_date = date_i18n( apply_filters( 'wpcw_coupon_date_format', __( 'F j, Y', 'wp-courseware' ) ), strtotime( $start_date ) );

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( date( 'c', strtotime( $start_date ) ) ),
			esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $start_date ) ) ),
			esc_html( $coupon_date )
		);
	}

	/**
	 * Column End Date.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon end date string.
	 */
	public function column_end_date( $coupon ) {
		$end_date = $coupon->get_end_date();

		$coupon_date = date_i18n( apply_filters( 'wpcw_coupon_date_format', __( 'F j, Y', 'wp-courseware' ) ), strtotime( $end_date ) );

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( date( 'c', strtotime( $end_date ) ) ),
			esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $end_date ) ) ),
			esc_html( $coupon_date )
		);
	}

	/**
	 * Column Coupon Date.
	 *
	 * @since 4.5.0
	 *
	 * @param Coupon $coupon The coupon object.
	 *
	 * @return string The coupon date string.
	 */
	public function column_date_created( $coupon ) {
		$date_created = $coupon->get_date_created();

		$coupon_date = date( apply_filters( 'wpcw_coupon_date_format', __( 'F j, Y', 'wp-courseware' ) ), strtotime( $date_created ) );

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( date( 'c', strtotime( $date_created ) ) ),
			esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_created ) ) ),
			esc_html( $coupon_date )
		);
	}

	/**
	 * Get Bulk Actions.
	 *
	 * @since 4.5.0
	 *
	 * @return array $actions The bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array( 'bulk-delete' => esc_html__( 'Delete', 'wp-courseware' ) );

		return $actions;
	}

	/**
	 * Process Bulk Actions.
	 *
	 * @since 4.5.0
	 */
	public function process_bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-coupons' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'coupons-nonce' ) ) {
			wp_die( __( 'Sorry, you are not allowed to delete this coupon.' ) );
		}

		if ( ! current_user_can( $this->page->get_capability() ) ) {
			wp_die( __( 'Sorry, you are not allowed to delete this coupon.' ) );
		}

		// Current Action.
		$current_action = $this->current_action();

		// Filter Action.
		if ( empty( $current_action ) && ! empty( $_GET['filter_action'] ) ) {
			$current_action = esc_attr( $_GET['filter_action'] );
		}

		// Search Action.
		if ( empty( $current_action ) && ! empty( $_GET['s'] ) ) {
			$current_action = 'search';
		}

		// Process Actions.
		switch ( $current_action ) {
			case 'delete' :
				$this->process_action_delete();
				break;
			case 'bulk-delete' :
				$this->process_action_bulk_delete();
				break;
			case 'filter-coupons-by-type' :
				$this->process_action_filter_by_type();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Coupons Table Actions.
		 *
		 * @since 4.5.0
		 */
		do_action( 'wpcw_coupons_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete.
	 *
	 * @since 4.5.0
	 */
	public function process_action_delete() {
		$ids = isset( $_GET['coupon_id'] ) ? $_GET['coupon_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		if ( wpcw()->coupons->delete_coupons( $ids ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Coupon successfully deleted!', 'wp-courseware' ) );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Delete.
	 *
	 * @since 4.5.0
	 */
	public function process_action_bulk_delete() {
		$ids = isset( $_GET['coupon_id'] ) ? $_GET['coupon_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		if ( wpcw()->coupons->delete_coupons( $ids ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Coupons successfully deleted!', 'wp-courseware' ) );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Filter by Type
	 *
	 * @since 4.5.0
	 */
	public function process_action_filter_by_type() {
		$coupon_type = ! empty( $_GET['type'] ) ? esc_attr( $_GET['type'] ) : '';

		$url = $this->page->get_url();

		if ( ! empty( $coupon_type ) ) {
			$url = add_query_arg( array( 'type' => $coupon_type ), $url );
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 4.5.0
	 *
	 * @param string $which Which tablenav.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>
		<div class="alignleft actions"><?php
		ob_start();

		$this->coupon_types_dropdown();

		$output = ob_get_clean();

		if ( ! empty( $output ) ) {
			echo $output;
			printf( '<button class="button" id="coupons-query-submit" name="filter_action" value="filter-coupons-by-type" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>', esc_html__( 'Filter', 'wp-courseware' ) );
			printf( '<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>', $this->page->get_url(), esc_html__( 'Reset', 'wp-courseware' ) );
		}
		?></div><?php
	}

	/**
	 * Display a Coupon Types Dropdown.
	 *
	 * @since 4.5.0
	 */
	protected function coupon_types_dropdown() {
		/**
		 * Filter: Disable filter by type dropdown.
		 *
		 * @since 4.5.0
		 *
		 * @param bool $disable Whether to disable the type drop-down. Default false.
		 */
		if ( false !== apply_filters( 'wpcw_coupons_disable_filter_by_type_dropdown', false ) ) {
			return;
		}

		$types = wpcw()->coupons->get_types();

		if ( empty( $types ) ) {
			return;
		}

		$current_type = ! empty( $_GET['type'] ) ? wpcw_clean( $_GET['type'] ) : '';
		$placeholder  = esc_html__( 'Show all types', 'wp-courseware' );

		printf( '<span class="wpcw-filter-wrapper">' );
		printf( '<select class="wpcw-select-field-filter" name="type" placeholder="%s" data-placeholder="%s">', $placeholder, $placeholder );
		printf( '<option value="">%s</option>', esc_html__( 'Any Type', 'wp-courseware' ) );

		foreach ( $types as $type => $type_label ) {
			$selected = selected( $current_type, $type, false );
			printf( '<option value="%s" %s>%s</option>', $type, $selected, $type_label );
		}

		printf( '</select>' );
		printf( '</span>' );
	}

	/**
	 * Get Views.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = isset( $_GET['type'] ) ? $_GET['type'] : '';

		$total = sprintf( '&nbsp;<span class="count">(%s)</span>', $this->total );

		$views = array(
			'all' => sprintf(
				'<a href="%1$s" %2$s>%3$s</a>',
				esc_url( remove_query_arg( 'status', $this->page->get_url() ) ),
				( 'all' === $current || '' === $current ) ? ' class="current"' : '',
				esc_html__( 'All', 'wp-courseware' ) . $total
			),
		);

		return $views;
	}

	/**
	 * Get Order Args.
	 *
	 * @since 4.5.0
	 */
	public function get_coupon_args() {
		$page        = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search      = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order       = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby     = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'coupon_id';
		$coupon_code = isset( $_GET['code'] ) ? $_GET['code'] : '';
		$coupon_type = isset( $_GET['type'] ) ? $_GET['type'] : '';

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'  => $per_page,
			'offset'  => $per_page * ( $page - 1 ),
			'search'  => $search,
			'orderby' => sanitize_text_field( $orderby ),
			'order'   => sanitize_text_field( $order ),
			'code'    => wpcw_clean( $coupon_code ),
			'type'    => wpcw_clean( $coupon_type ),
		);

		return $args;
	}

	/**
	 * Get Coupons Count.
	 *
	 * @since 4.5.0
	 *
	 * @return int The coupons count.
	 */
	public function get_coupons_count() {
		$this->count = wpcw()->coupons->get_coupons_count( $this->get_coupon_args() );

		return $this->count;
	}

	/**
	 * Get Coupons Data.
	 *
	 * @since 4.5.0
	 *
	 * @return array $data Coupons data.
	 */
	public function get_coupons_data() {
		return wpcw()->coupons->get_coupons( $this->get_coupon_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.5.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_coupons_data();

		$current_page = $this->get_pagenum();

		$total_items = $this->count;

		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since 4.5.0
	 */
	public function no_items() {
		esc_html_e( 'Sorry, no coupons have been created.', 'wp-courseware' );
	}
}
