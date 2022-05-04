<?php
/**
 * WP Courseware Courses Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.3.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Courses;
use WPCW\Models\Course;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Table_Courses.
 *
 * @since 4.1.0
 */
class Table_Courses extends Table {

	/**
	 * @var Page_Courses The Courses Admin Page.
	 * @since 4.1.0
	 */
	protected $page;

	/**
	 * Courses Table Constructor.
	 *
	 * @since 4.1.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'course',
			'plural'   => 'courses',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->total = $this->get_courses_count();
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
			'cb'       => '<input type="checkbox" />',
			'title'    => esc_html__( 'Title', 'wp-courseware' ),
			'settings' => esc_html__( 'Settings', 'wp-courseware' ),
			'modules'  => esc_html__( 'Modules', 'wp-courseware' ),
			'ordering' => esc_html__( 'Ordering', 'wp-courseware' ),
			'id'       => esc_html__( 'ID', 'wp-courseware' ),
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
			'id'    => array( 'id', false ),
			'title' => array( 'title', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param object Course $module
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $course, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $course->$column_name ) ? $course->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $course ) {
		return '<input type="checkbox" name="course_id[]" value="' . absint( $course->get_course_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return int
	 */
	public function column_id( $course ) {
		return $course->get_course_id();
	}

	/**
	 * Column Title.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course
	 *
	 * @return string
	 */
	public function column_title( $course ) {
		$row_actions = array();

		$course_id = $course->get_course_id();

		$base_query_args = array(
			'page'      => $this->page->get_slug(),
			'course_id' => $course_id,
		);

		$title = $course->get_course_title();

		$edit_course_url   = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'WPCW_showPage_ModifyCourse' ) ), admin_url( 'admin.php' ) ) );
		$delete_course_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) ) );
		$classroom_url     = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'wpcw-course-classroom' ) ), admin_url( 'admin.php' ) ) );
		$gradebook_url     = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'WPCW_showPage_GradeBook' ) ), admin_url( 'admin.php' ) ) );

		$title = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_course_url, $title );

		$quiz_count = wpcw()->courses->get_course_quizzes_that_need_grading_count( $course_id );

		if ( $quiz_count > 0 ) {
			$title .= sprintf(
				'&nbsp;&nbsp;<span class="wpcw-quiz-update-count count-%d"><span class="update-count">%s</span></span>',
				$quiz_count,
				$quiz_count
			);
		}

		$row_actions['course_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $course_id );

		$row_actions['edit_course'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_course_url )
		);

		$row_actions['delete-everything'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array( 'delete_course_type' => 'complete' ),
			array(
				'base_uri' => $delete_course_url,
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete the this course?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'course-nonce',
			)
		);

		$row_actions['classroom'] = $this->get_row_action_link(
			esc_html__( 'Classroom', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $classroom_url )
		);

		$row_actions['gradebook'] = $this->get_row_action_link(
			esc_html__( 'Gradebook', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $gradebook_url )
		);

		$title .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $title;
	}

	/**
	 * Column Course Description.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return int
	 */
	public function column_desc( $course ) {
		$description = $course->get_course_desc();

		return wp_kses_post( $description );
	}

	/**
	 * Column Course Settings.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return int
	 */
	public function column_settings( $course ) {
		$settings = '<ul class="wpcw_tickitems">';

		$settings .= sprintf( __( '<li class="wpcw_%s">%s</li>', 'wp-courseware' ), 'enabled', wpcw()->courses->get_payments_feature_label( $course ) );

		if ( $installments_feature_label = wpcw()->courses->get_installments_feature_label( $course ) ) {
			$settings .= sprintf( __( '<li class="wpcw_%s">%s</li>', 'wp-courseware' ), 'enabled', $installments_feature_label );
		}

		$settings .= apply_filters( 'wpcw_extensions_access_control_override', sprintf(
			'<li class="wpcw_%s">%s</li>',
			( 'default_show' == $course->get_course_opt_user_access() ? 'enabled' : 'disabled' ),
			$course->get_course_opt_user_access_message()
		) );

		// Completion wall
		$settings .= sprintf( '<li class="wpcw_%s">%s</li>', ( 'completion_wall' == $course->get_course_opt_completion_wall() ? 'enabled' : 'disabled' ),
			esc_html__( 'Require unit completion before showing next', 'wp-courseware' ) );

		// Certificate handling
		$settings .= sprintf( '<li class="wpcw_%s">%s</li>', ( 'use_certs' == $course->get_course_opt_use_certificate() ? 'enabled' : 'disabled' ),
			esc_html__( 'Generate certificates on course completion', 'wp-courseware' ) );

		$settings .= '</ul>';

		return $settings;
	}

	/**
	 * Column Course Modules.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return int
	 */
	public function column_modules( $course ) {
		$course_id = $course->get_course_id();

		$modules_link = add_query_arg( array( 'page' => 'wpcw-modules', 'course_id' => $course_id ) );
		$modules_icon = '<i class="wpcw-fa wpcw-fa-tasks left" aria-hidden="true"></i>';
		$modules_text = esc_html__( 'View Modules', 'wp-courseware' );

		return sprintf( '<a class="button button-secondary" href="%s">%s %s</a>', $modules_link, $modules_icon, $modules_text );
	}

	/**
	 * Column Ordering.
	 *
	 * @since 4.1.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return int
	 */
	public function column_ordering( $course ) {
		$course_id = $course->get_course_id();

		$ordering_link = add_query_arg( array( 'page' => 'WPCW_showPage_CourseOrdering', 'course_id' => $course_id ) );
		$ordering_icon = '<i class="wpcw-fa wpcw-fa-th-list left" aria-hidden="true"></i>';
		$ordering_text = esc_html__( 'Module & Unit Ordering', 'wp-courseware' );

		return sprintf( '<a class="button button-secondary" href="%s">%s %s</a>', $ordering_link, $ordering_icon, $ordering_text );
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
			'bulk-delete-settings' => esc_html__( 'Delete Modules & Settings', 'wp-courseware' ),
			'bulk-delete-all'      => esc_html__( 'Delete Modules, Units, Quizzes & Settings', 'wp-courseware' ),
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

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-courses' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'course-nonce' ) ) {
			return;
		}

		if ( ! current_user_can( $this->page->get_capability() ) ) {
			return;
		}

		// Current ACtion.
		$current_action = $this->current_action();

		// Search Action.
		if ( empty( $current_action ) && ! empty( $_GET['s'] ) ) {
			$current_action = 'search';
		}

		// Process Actions.
		switch ( $current_action ) {
			case 'delete' :
				$this->process_action_delete();
				break;
			case 'bulk-delete-all' :
				$this->process_action_bulk_delete_all();
				break;
			case 'bulk-delete-settings' :
				$this->process_action_bulk_delete_settings();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Courses Table Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_courses_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_delete() {
		$course_id = wpcw_get_var( 'course_id' );
		$method    = wpcw_post_var( 'delete_course_type' );
		$method    = ( ! $method ) ? wpcw_get_var( 'delete_course_type' ) : '';

		if ( empty( $course_id ) ) {
			return;
		}

		if ( $course = wpcw()->courses->delete_course( $course_id, $method ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Course deleted successfully.', 'wp-courseware' ) );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Delete All.
	 *
	 * @since 4.3.0
	 */
	public function process_action_bulk_delete_all() {
		$ids = isset( $_GET['course_id'] ) ? $_GET['course_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			wpcw()->courses->delete_course( $id );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Courses successfully deleted!', 'wp-courseware' ) );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Delete Settings.
	 *
	 * @since 4.3.0
	 */
	public function process_action_bulk_delete_settings() {
		$ids = isset( $_GET['course_id'] ) ? $_GET['course_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			wpcw()->courses->delete_course( $id, 'course_and_module' );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Courses successfully deleted!', 'wp-courseware' ) );

		wp_safe_redirect( $this->page->get_url() );
		exit;
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
	 * Get Course Args.
	 *
	 * @since 4.3.0
	 */
	public function get_course_args() {
		$page    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search  = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order   = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'module_id';
		$author  = '';

		// Check if admin
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$author = get_current_user_id();
		}

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'        => $per_page,
			'offset'        => $per_page * ( $page - 1 ),
			'search'        => $search,
			'course_author' => $author,
			'orderby'       => sanitize_text_field( $orderby ),
			'order'         => sanitize_text_field( $order ),
		);

		return $args;
	}

	/**
	 * Get Courses Count.
	 *
	 * @since 4.1.0
	 *
	 * @return int The courses count.
	 */
	public function get_courses_count() {
		$this->count = wpcw()->courses->get_courses_count( $this->get_course_args() );

		return $this->count;
	}

	/**
	 * Get Courses Data.
	 *
	 * @since 4.1.0
	 *
	 * @return array $data Courses data.
	 */
	public function get_courses_data() {
		return wpcw()->courses->get_courses( $this->get_course_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.1.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_courses_data();

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
		esc_html_e( 'Sorry, no Courses have been created.', 'wp-courseware' );
	}
}
