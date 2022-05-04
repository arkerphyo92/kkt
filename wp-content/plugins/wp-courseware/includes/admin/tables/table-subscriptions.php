<?php
/**
 * WP Courseware Subscriptions Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.3.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Subscriptions;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Table_Subscriptions.
 *
 * @since 4.3.0
 */
class Table_Subscriptions extends Table {

	/**
	 * @var Page_Subscriptions The Subscriptions Admin Page.
	 * @since 4.3.0
	 */
	protected $page;

	/**
	 * @var int The subscription installments total.
	 * @since 4.6.0
	 */
	protected $installments_total;

	/**
	 * Subscriptions Table Constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'subscription',
			'plural'   => 'subscriptions',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->total              = $this->get_subscriptions_count();
		$this->installments_total = $this->get_installments_count();
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
			'title'  => esc_html__( 'Subscription', 'wp-courseware' ),
			'item'   => esc_html__( 'Item', 'wp-courseware' ),
			'total'  => esc_html__( 'Total', 'wp-courseware' ),
			'status' => esc_html__( 'Status', 'wp-courseware' ),
			'date'   => esc_html__( 'Start Date', 'wp-courseware' ),
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
			'item'   => array( 'item', false ),
			'total'  => array( 'total', false ),
			'status' => array( 'status', false ),
			'date'   => array( 'date', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Subscription $subscription
	 * @param string       $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $subscription, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $subscription->$column_name ) ? $subscription->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $subscription ) {
		return '<input type="checkbox" name="id[]" value="' . absint( $subscription->get_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return int
	 */
	public function column_id( $subscription ) {
		return $subscription->get_id();
	}

	/**
	 * Column Title.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return string
	 */
	public function column_title( $subscription ) {
		$row_actions = array();

		$subscription_id = $subscription->get_id();

		$base_query_args = array(
			'page' => $this->page->get_slug(),
			'id'   => $subscription_id,
		);

		$student_name = $subscription->get_student_name();

		if ( $student_name ) {
			/* translators: %1$s: order number, %2$s: Student Name. */
			$title = sprintf( __( '#%1$s - %2$s', 'wp-courseware' ), $subscription->get_id(), $student_name );
		} else {
			/* translators: %1$s: order number */
			$title = sprintf( __( '#%1$s - No Student Assigned', 'wp-courseware' ), $subscription->get_id() );
		}

		$edit_subscription_url   = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'wpcw-subscription' ) ), admin_url( 'admin.php' ) ) );
		$edit_student_url        = esc_url_raw( add_query_arg( array( 'page' => 'wpcw-student', 'id' => $subscription->get_student_id() ), admin_url( 'admin.php' ) ) );
		$delete_subscription_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) ) );

		$title = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_subscription_url, $title );

		$row_actions['subscription_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $subscription_id );

		$row_actions['edit_subscription'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_subscription_url )
		);

		$row_actions['edit_student'] = $this->get_row_action_link(
			esc_html__( 'Edit Student', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_student_url )
		);

		$row_actions['delete_subscription'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => $delete_subscription_url,
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete this subscription?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'subscriptions-nonce',
			)
		);

		$title .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $title;
	}

	/**
	 * Column Item.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return string
	 */
	public function column_item( $subscription ) {
		$course_edit_url = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url_raw( add_query_arg( array( 'page' => 'WPCW_showPage_ModifyCourse', 'course_id' => $subscription->get_course_id() ), admin_url( 'admin.php' ) ) ),
			$subscription->get_course_title()
		);

		return $course_edit_url;
	}

	/**
	 * Column Subscription Date.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return int
	 */
	public function column_date( $subscription ) {
		$date_created = $subscription->get_created();

		$subscription_date = date( apply_filters( 'wpcw_subscription_date_format', __( 'M j, Y', 'wp-courseware' ) ), strtotime( $date_created ) );

		return sprintf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( date( 'c', strtotime( $date_created ) ) ),
			esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_created ) ) ),
			esc_html( $subscription_date )
		);
	}

	/**
	 * Column Subscription Status.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return int
	 */
	public function column_status( $subscription ) {
		$status = $subscription->get_status();

		return sprintf( '<mark class="mark-status status-%s">%s</mark>', $status, esc_attr( wpcw_get_subscription_status_name( $status ) ) );
	}

	/**
	 * Column Subscription Total.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return int
	 */
	public function column_total( $subscription ) {
		$period = $subscription->get_period_name();
		$amount = $subscription->get_recurring_amount();

		if ( $subscription->is_installment_plan() ) {
			return $subscription->get_installment_plan_label();
		}

		return sprintf( '%s / %s', wpcw_price( $amount ), $period );
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

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-subscriptions' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'subscriptions-nonce' ) ) {
			wp_die( __( 'Sorry, you are not allowed to delete this subscription.' ) );
		}

		if ( ! current_user_can( $this->page->get_capability() ) ) {
			wp_die( __( 'Sorry, you are not allowed to delete this subscription.' ) );
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
			case 'filter-subscriptions-by-status' :
				$this->process_action_filter_by_status();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Subscriptions Table Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_subscriptions_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_delete() {
		$ids = isset( $_GET['id'] ) ? $_GET['id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		if ( wpcw()->subscriptions->delete_subscriptions( $ids ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Subscription successfully deleted!', 'wp-courseware' ) );
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
		$ids = isset( $_GET['id'] ) ? $_GET['id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		if ( wpcw()->subscriptions->delete_subscriptions( $ids ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Subscriptions successfully deleted!', 'wp-courseware' ) );
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
		$status = ! empty( $_GET['sub_status'] ) ? esc_attr( $_GET['sub_status'] ) : '';

		$url = $this->page->get_url();

		if ( ! empty( $status ) ) {
			$url = add_query_arg( array( 'sub_status' => $status ), $url );
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

		$this->subscription_statuses_dropdown();

		$output = ob_get_clean();

		if ( ! empty( $output ) ) {
			echo $output;
			printf( '<button class="button" id="subscriptions-query-submit" name="filter_action" value="filter-subscriptions-by-status" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>', esc_html__( 'Filter', 'wp-courseware' ) );
			printf( '<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>', $this->page->get_url(), esc_html__( 'Reset', 'wp-courseware' ) );
		}
		?></div><?php
	}

	/**
	 * Display a Subscriptions Statuses Dropdown.
	 *
	 * @since 4.3.0
	 */
	protected function subscription_statuses_dropdown() {
		/**
		 * Filter: Disable filter by status dropdown.
		 *
		 * @since 4.3.0
		 *
		 * @param bool $disable Whether to disable the status drop-down. Default false.
		 */
		if ( apply_filters( 'wpcw_subscriptions_disable_filter_by_status_dropdown', false ) ) {
			return;
		}

		$statuses = wpcw()->subscriptions->get_statuses();

		if ( empty( $statuses ) ) {
			return;
		}

		$current_order_status = ! empty( $_GET['sub_status'] ) ? wpcw_clean( $_GET['sub_status'] ) : '';
		$placeholder          = esc_html__( 'Any Status', 'wp-courseware' );

		printf( '<span class="wpcw-filter-wrapper">' );
		printf( '<select class="wpcw-select-field-filter" name="sub_status" placeholder="%s" data-placeholder="%s">', $placeholder, $placeholder );
		printf( '<option value="">%s</option>', $placeholder );

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

		if ( $this->installments_total ) {
			$ip_total = sprintf( '&nbsp;<span class="count">(%s)</span>', $this->installments_total );

			$views['installments'] = sprintf(
				'<a href="%1$s" %2$s>%3$s</a>',
				esc_url( add_query_arg( array( 'status' => 'installment_plans', 'installment_plan' => 1 ), $this->page->get_url() ) ),
				( 'installment_plans' === $current ) ? ' class="current"' : '',
				esc_html__( 'Installment Plans', 'wp-courseware' ) . $ip_total
			);
		}

		return $views;
	}

	/**
	 * Get Subscription Args.
	 *
	 * @since 4.3.0
	 */
	public function get_subscription_args() {
		$page             = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search           = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$course_id        = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
		$student_id       = isset( $_GET['student_id'] ) ? absint( $_GET['student_id'] ) : 0;
		$order_id         = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order            = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby          = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'id';
		$status           = isset( $_GET['sub_status'] ) ? $_GET['sub_status'] : 'all';
		$installment_plan = isset( $_GET['installment_plan'] ) ? $_GET['installment_plan'] : 0;

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'           => $per_page,
			'offset'           => $per_page * ( $page - 1 ),
			'search'           => $search,
			'course_id'        => $course_id,
			'student_id'       => $student_id,
			'order_id'         => $order_id,
			'installment_plan' => $installment_plan,
			'orderby'          => sanitize_text_field( $orderby ),
			'order'            => sanitize_text_field( $order ),
			'status'           => $status,
		);

		return $args;
	}

	/**
	 * Get Subscriptions Count.
	 *
	 * @since 4.3.0
	 *
	 * @return int The subscriptions count.
	 */
	public function get_subscriptions_count() {
		$this->count = wpcw()->subscriptions->get_subscriptions_count( $this->get_subscription_args() );

		return $this->count;
	}

	/**
	 * Get Installments Count.
	 *
	 * @since 4.6.0
	 *
	 * @return int The subscription installments total.
	 */
	public function get_installments_count() {
		$subscription_args = $this->get_subscription_args();

		$subscription_args['installment_plan'] = 1;

		return wpcw()->subscriptions->get_subscriptions_count( $subscription_args );
	}

	/**
	 * Get Subscriptions Data.
	 *
	 * @since 4.3.0
	 *
	 * @return array $data Subscriptions data.
	 */
	public function get_subscriptions_data() {
		return wpcw()->subscriptions->get_subscriptions( $this->get_subscription_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.3.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_subscriptions_data();

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
		esc_html_e( 'Sorry, no subscriptions have been created.', 'wp-courseware' );
	}
}
