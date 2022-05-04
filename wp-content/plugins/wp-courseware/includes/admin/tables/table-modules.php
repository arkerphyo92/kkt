<?php
/**
 * WP Courseware Modules Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.1.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Modules;
use WPCW\Models\Course;
use WPCW\Models\Module;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Modules Table.
 *
 * @since 4.1.0
 */
class Table_Modules extends Table {

	/**
	 * @var Page_Modules The modules admin page.
	 * @since 4.1.0
	 */
	protected $page;

	/**
	 * Module Table Constructor.
	 *
	 * @since 4.1.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'module',
			'plural'   => 'modules',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->total = $this->get_modules_count();
	}

	/**
	 * Get Table Columns
	 *
	 * @since 4.1.0
	 *
	 * @return array $columns An array of columns displayed in the table.
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'title'  => esc_html__( 'Module Title', 'wp-courseware' ),
			'course' => esc_html__( 'Associated Course', 'wp-courseware' ),
		);

		return $columns;
	}

	/**
	 * Get Sortable Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array $columns An array of sortable displayed in the table.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', false ),
			'course' => array( 'course', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Module $module
	 * @param string $column_name
	 *
	 * @return mixed Displays the Module property.
	 */
	public function column_default( $module, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $module->$column_name ) ? $module->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.1.0
	 *
	 * @param Module $module The module object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $module ) {
		return '<input type="checkbox" name="module_id[]" value="' . absint( $module->get_module_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.1.0
	 *
	 * @param Module $module The module object.
	 *
	 * @return int The Module Id.
	 */
	public function column_id( $module ) {
		return $module->get_module_id();
	}

	/**
	 * Column Title.
	 *
	 * @since 4.1.0
	 *
	 * @param Module $module The Module being displayed.
	 *
	 * @return string The action links html.
	 */
	public function column_title( $module ) {
		$row_actions = array();

		$module_id = $module->get_module_id();

		$base_query_args = array(
			'page'      => $this->page->get_slug(),
			'module_id' => $module_id,
		);

		$title = $module->get_module_title();

		$edit_module_url   = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'WPCW_showPage_ModifyModule' ) ), admin_url( 'admin.php' ) ) );
		$delete_module_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) ) );
		$duplicate_module_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'duplicate' ) ), admin_url( 'admin.php' ) ) );

		$value = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_module_url, $title );

		$row_actions['module_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $module_id );

		$row_actions['edit_module'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_module_url )
		);

		$row_actions['duplicate_modules'] = $this->get_row_action_link(
			esc_html__( 'Duplicate', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => $duplicate_module_url,
				'class'    => 'wpcw_modules_admin_duplicate',
				'atts'     => sprintf( 'data-postid="%d" data-nonce="%s"', $module_id, wp_create_nonce( 'module-nonce' ) ),
				'nonce'    => 'module-nonce',
			)
		);

		$row_actions['delete'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => $delete_module_url,
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete the this module?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'module-nonce',
			)
		);

		$value .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $value;
	}

	/**
	 * Column Course.
	 *
	 * @since 4.1.0
	 *
	 * @param Module $module The Module being displayed.
	 *
	 * @return string The module course title as a link.
	 */
	public function column_course( $module ) {
		$course_id      = $module->get_parent_course_id();
		$course_title   = $module->get_course_title();
		$course_post_id = $module->get_course_post_id( $course_id );

		$course_link = add_query_arg( array( 'post' => $course_post_id, 'action' => 'edit' ), admin_url( 'post.php' ) );

		return sprintf( '<a href="%s">%s</a>', $course_link, $course_title );
	}

	/**
	 * Column Description.
	 *
	 * @since 4.1.0
	 *
	 * @param Module $module The Module being displayed.
	 *
	 * @return string The module description.
	 */
	public function column_desc( $module ) {
		$course_desc = $module->get_module_desc();

		return wp_kses_post( $course_desc );
	}

	/**
	 * Get Bulk Actions.
	 *
	 * @since 4.1.0
	 *
	 * @return array $actions The bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => esc_html__( 'Delete', 'wp-courseware' ),
		);

		return $actions;
	}

	/**
	 * Process Bulk Actions.
	 *
	 * @since 4.1.0
	 */
	public function process_bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-modules' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'module-nonce' ) ) {
			return;
		}

		if ( ! current_user_can( $this->page->get_capability() ) ) {
			return;
		}

		// Current ACtion.
		$current_action = $this->current_action();

		// Filter Action.
		if ( empty( $current_action ) && ! empty( $_GET['filter_action'] ) ) {
			$current_action = esc_attr( $_GET['filter_action'] );
		}

		// Search Action.
		if ( empty( $current_action ) && ! empty( $_GET['s'] ) ) {
			$current_action = 'search';
		}

		// Search Action.
		if ( empty( $current_action ) && ! empty( $_GET['action'] ) ) {
			$current_action = $_GET['action'];
		}

		// Process Actions.
		switch ( $current_action ) {
			case 'delete' :
				$this->process_action_delete();
				break;
			case 'bulk-delete' :
				$this->process_action_bulk_delete();
				break;
			case 'filter-modules-by-course' :
				$this->process_action_filter_by_course();
				break;
			case 'search' :
				$this->process_action_search();
				break;
			case 'duplicate' :
				$this->process_action_duplicate();
		}

		/**
		 * Action: Process Modules Table Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_modules_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_delete() {
		$module_id = wpcw_get_var( 'module_id' );

		if ( $module = wpcw()->modules->delete_module( $module_id ) ) {
			$message = sprintf( __( 'Module <strong>%s</strong> deleted successfully.', 'wp-courseware' ), $module->module_title );
			wpcw_add_admin_notice_success( $message, 'success' );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Duplicate.
	 *
	 * @since 4.8.0
	 */
	public function process_action_duplicate( ) {
		$module_id = wpcw_get_var( 'module_id' );
		$module_action = wpcw_get_var( 'action' );
		$module_title = '';
		if( !empty ( $module_id ) ){
			global $wpdb, $wpcwdb;
			$SQL = $wpdb->prepare( "SELECT module_title FROM $wpcwdb->modules WHERE module_id=%d", $module_id );
			$result = $wpdb->get_results( $SQL );
			if( !empty($result)){
				$module_title = $result[0]->module_title;
			}
		}
		if ( 'duplicate' === $module_action ) {
			$message = sprintf( __( 'Module <strong>%s</strong> duplicated successfully. Please change Associated Course.', 'wp-courseware' ), $module_title );
			wpcw_add_admin_notice_success( $message, 'success' );
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
		$ids = isset( $_GET['module_id'] ) ? $_GET['module_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			wpcw()->modules->delete_module( $id );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Modules successfully deleted!', 'wp-courseware' ) );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Filter by Course
	 *
	 * @since 4.3.0
	 */
	public function process_action_filter_by_course() {
		$course_id = ! empty( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : '';

		$url = $this->page->get_url();

		if ( ! empty( $course_id ) ) {
			$url = add_query_arg( array( 'course_id' => $course_id ), $url );
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 4.1.0
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>
        <div class="alignleft actions"><?php
		ob_start();

		$this->courses_dropdown();

		$output = ob_get_clean();

		if ( ! empty( $output ) ) {
			echo $output;
			printf(
				'<button class="button" id="courses-query-submit" name="filter_action" value="filter-modules-by-course" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>',
				esc_html__( 'Filter', 'wp-courseware' )
			);
			printf(
				'<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>',
				$this->page->get_url(),
				esc_html__( 'Reset', 'wp-courseware' )
			);
		}
		?></div><?php
	}

	/**
	 * Displays a Courses drop-down for filtering on the Modules Table.
	 *
	 * @since 4.1.0
	 */
	protected function courses_dropdown() {
		/**
		 * Filters whether to remove the 'Courses' drop-down from the post list table.
		 *
		 * @since 4.1.0
		 *
		 * @param bool $disable Whether to disable the categories drop-down. Default false.
		 */
		if ( apply_filters( 'wpcw_modules_disable_filter_by_courses_dropdown', false ) ) {
			return;
		}

		echo wpcw()->courses->get_courses_filter_dropdown();
	}

	/**
	 * Get Views.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = isset( $_GET['status'] ) ? $_GET['status'] : '';

		$total = sprintf( '&nbsp;<span class="count">(%s)</span>', $this->total );

		$views = array(
			'all' => sprintf(
				'<a href="%s"%s>%s</a>',
				esc_url( remove_query_arg( 'status', $this->page->get_url() ) ),
				( 'all' === $current || '' === $current ) ? ' class="current"' : '',
				esc_html__( 'All', 'wp-courseware' ) . $total
			),
		);

		return $views;
	}

	/**
	 * Get Modules Args.
	 *
	 * @since 4.3.0
	 *
	 * @return array The module query args.
	 */
	public function get_modules_args() {
		$page      = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search    = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order     = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby   = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'module_id';
		$course_id = isset( $_GET['course_id'] ) ? $_GET['course_id'] : false;
		$author    = 0;

		// Check if admin
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$author = get_current_user_id();
		}

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'        => $per_page,
			'offset'        => $per_page * ( $page - 1 ),
			'module_author' => $author,
			'course_id'     => $course_id,
			'search'        => $search,
			'orderby'       => sanitize_text_field( $orderby ),
			'order'         => sanitize_text_field( $order ),
		);

		return $args;
	}

	/**
	 * Get Modules Count.
	 *
	 * @since 4.1.0
	 *
	 * @return int The modules count.
	 */
	public function get_modules_count() {
		$this->count = wpcw()->modules->get_modules_count( $this->get_modules_args() );

		return $this->count;
	}

	/**
	 * Get Modules Data.
	 *
	 * @since 4.1.0
	 *
	 * @return array $data Modules data.
	 */
	public function get_modules_data() {
		return wpcw()->modules->get_modules( $this->get_modules_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.1.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_modules_data();

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
	 * @since 4.1.0
	 */
	public function no_items() {
		esc_html_e( 'Sorry, no Modules have been created.', 'wp-courseware' );
	}
}
