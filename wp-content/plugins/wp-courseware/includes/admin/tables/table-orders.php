<?php
/**
 * WP Courseware Orders Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.3.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Orders;
use WPCW\Models\Order;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Table_Orders.
 *
 * @since 4.3.0
 */
class Table_Orders extends Table {

	/**
	 * @var Page_Orders The Orders Admin Page.
	 * @since 4.3.0
	 */
	protected $page;

	/**
	 * Courses Table Constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'order',
			'plural'   => 'orders',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->total = $this->get_orders_count();
	}

	/**
	 * Get Table Columns
	 *
	 * @since 4.3.0
	 *
	 * @return array $columns An array of columns displayed in the table.
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'title'  => esc_html__( 'Order', 'wp-courseware' ),
			'date'   => esc_html__( 'Date', 'wp-courseware' ),
			'status' => esc_html__( 'Status', 'wp-courseware' ),
			'total'  => esc_html__( 'Total', 'wp-courseware' ),
		);

		return $columns;
	}

	/**
	 * Get Sortable Columns.
	 *
	 * @since 4.3.0
	 *
	 * @return array $columns An array of sortable displayed in the table.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', false ),
			'date'   => array( 'date', false ),
			'status' => array( 'status', false ),
			'total'  => array( 'total', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Order $order
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $order, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $order->$column_name ) ? $order->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $order ) {
		return '<input type="checkbox" name="order_id[]" value="' . absint( $order->get_order_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return int
	 */
	public function column_id( $order ) {
		return $order->get_order_id();
	}

	/**
	 * Column Title.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return string
	 */
	public function column_title( $order ) {
		$row_actions = array();

		$order_id = $order->get_order_id();

		$base_query_args = array(
			'page'     => $this->page->get_slug(),
			'order_id' => $order_id,
		);

		$student_name = $order->get_student_full_name();

		if ( $student_name ) {
			/* translators: %1$s: order number, %2$s: Student Name. */
			$title = sprintf( __( '#%1$s - %2$s', 'wp-courseware' ), $order->get_order_id(), $student_name );
		} else {
			/* translators: %1$s: order number */
			$title = sprintf( __( '#%1$s - No Student Assigned', 'wp-courseware' ), $order->get_order_id() );
		}

		$edit_order_url   = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'wpcw-order' ) ), admin_url( 'admin.php' ) ) );
		$edit_student_url = esc_url_raw( add_query_arg( array( 'page' => 'wpcw-student', 'id' => $order->get_student_id() ), admin_url( 'admin.php' ) ) );
		$delete_order_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) ) );

		$title = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_order_url, $title );

		$row_actions['order_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $order_id );

		$row_actions['edit_order'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_order_url )
		);

		$row_actions['edit_student'] = $this->get_row_action_link(
			esc_html__( 'Edit Student', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_student_url )
		);

		$row_actions['delete_order'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => $delete_order_url,
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete this order?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'orders-nonce',
			)
		);

		$title .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $title;
	}

	/**
	 * Column Order Date.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return int
	 */
	public function column_date( $order ) {
		$date_created = $order->get_date_created();

		$order_date = date( apply_filters( 'wpcw_order_date_format', __( 'M j, Y', 'wp-courseware' ) ), strtotime( $date_created ) );

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( date( 'c', strtotime( $date_created ) ) ),
			esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_created ) ) ),
			esc_html( $order_date )
		);
	}

	/**
	 * Column Order Status.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return int
	 */
	public function column_status( $order ) {
		$status = $order->get_order_status();

		return sprintf( '<mark class="mark-status status-%s">%s</mark>', $status, esc_attr( wpcw_get_order_status_name( $status ) ) );
	}

	/**
	 * Column Order Total.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return int
	 */
	public function column_total( $order ) {
		$total = $order->get_total();

		return wpcw_price( $total );
	}

	/**
	 * Get Bulk Actions.
	 *
	 * @since 4.3.0
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
	 * @since 4.3.0
	 */
	public function process_bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-orders' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'orders-nonce' ) ) {
			wp_die( __( 'Sorry, you are not allowed to delete this order.' ) );
		}

		if ( ! current_user_can( $this->page->get_capability() ) ) {
			wp_die( __( 'Sorry, you are not allowed to delete this order.' ) );
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
			case 'filter-orders-by-status' :
				$this->process_action_filter_by_status();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Orders Table Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_orders_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_delete() {
		$ids = isset( $_GET['order_id'] ) ? $_GET['order_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		if ( wpcw()->orders->delete_orders( $ids ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Order successfully deleted!', 'wp-courseware' ) );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_bulk_delete() {
		$ids = isset( $_GET['order_id'] ) ? $_GET['order_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		if ( wpcw()->orders->delete_orders( $ids ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Orders successfully deleted!', 'wp-courseware' ) );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Filter by Status
	 *
	 * @since 4.3.0
	 */
	public function process_action_filter_by_status() {
		$order_status = ! empty( $_GET['order_status'] ) ? esc_attr( $_GET['order_status'] ) : '';

		$url = $this->page->get_url();

		if ( ! empty( $order_status ) ) {
			$url = add_query_arg( array( 'order_status' => $order_status ), $url );
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 4.3.0
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

		$this->order_statuses_dropdown();

		$output = ob_get_clean();

		if ( ! empty( $output ) ) {
			echo $output;
			printf( '<button class="button" id="orders-query-submit" name="filter_action" value="filter-orders-by-status" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>', esc_html__( 'Filter', 'wp-courseware' ) );
			printf( '<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>', $this->page->get_url(), esc_html__( 'Reset', 'wp-courseware' ) );
		}
		?></div><?php
	}

	/**
	 * Display a Order Statuses Dropdown.
	 *
	 * @since 4.3.0
	 */
	protected function order_statuses_dropdown() {
		/**
		 * Filter: Disable filter by status dropdown.
		 *
		 * @since 4.3.0
		 *
		 * @param bool $disable Whether to disable the status drop-down. Default false.
		 */
		if ( false !== apply_filters( 'wpcw_orders_disable_filter_by_status_dropdown', false ) ) {
			return;
		}

		$statuses = wpcw()->orders->get_order_statuses();

		if ( empty( $statuses ) ) {
			return;
		}

		$current_order_status = ! empty( $_GET['order_status'] ) ? wpcw_clean( $_GET['order_status'] ) : '';
		$placeholder          = esc_html__( 'Any Status', 'wp-courseware' );

		printf( '<span class="wpcw-filter-wrapper">' );
		printf( '<select class="wpcw-select-field-filter" name="order_status" placeholder="%s" data-placeholder="%s">', $placeholder, $placeholder );
		printf( '<option value="">%s</option>', esc_html__( 'Any Status', 'wp-courseware' ) );

		foreach ( $statuses as $status => $status_label ) {
			$selected = selected( $current_order_status, $status, false );
			printf( '<option value="%s" %s>%s</option>', $status, $selected, $status_label );
		}

		printf( '</select>' );
		printf( '</span>' );
	}

	/**
	 * Get Views.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = isset( $_GET['status'] ) ? $_GET['status'] : '';

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
	 * @since 4.3.0
	 */
	public function get_order_args() {
		$page         = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search       = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order        = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby      = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'order_id';
		$order_type   = isset( $_GET['order_type'] ) ? $_GET['order_type'] : 'order';
		$order_status = isset( $_GET['order_status'] ) ? $_GET['order_status'] : '';

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'       => $per_page,
			'offset'       => $per_page * ( $page - 1 ),
			'search'       => $search,
			'orderby'      => sanitize_text_field( $orderby ),
			'order'        => sanitize_text_field( $order ),
			'order_type'   => wpcw_clean( $order_type ),
			'order_status' => wpcw_clean( $order_status ),
		);

		return $args;
	}

	/**
	 * Get Orders Count.
	 *
	 * @since 4.3.0
	 *
	 * @return int The orders count.
	 */
	public function get_orders_count() {
		$this->count = wpcw()->orders->get_orders_count( $this->get_order_args() );

		return $this->count;
	}

	/**
	 * Get Orders Data.
	 *
	 * @since 4.3.0
	 *
	 * @return array $data Orders data.
	 */
	public function get_orders_data() {
		return wpcw()->orders->get_orders( $this->get_order_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.3.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_orders_data();

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
	 * @since 4.3.0
	 */
	public function no_items() {
		esc_html_e( 'Sorry, no orders have been created.', 'wp-courseware' );
	}
}
