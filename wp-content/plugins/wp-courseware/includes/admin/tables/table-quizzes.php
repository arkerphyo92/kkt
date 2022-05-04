<?php
/**
 * WP Courseware Quizzes Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.2.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Quizzes;
use WPCW\Models\Quiz;
use WPCW\Models\Course;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Quizzes Table.
 *
 * @since 4.2.0
 */
class Table_Quizzes extends Table {

	/**
	 * @var Page_Quizzes The Quizzes Admin Page.
	 * @since 4.2.0
	 */
	protected $page;

	/**
	 * Quizzes Table Constructor.
	 *
	 * @since 4.2.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'quiz',
			'plural'   => 'quizzes',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->total = $this->get_quizzes_count();
	}

	/**
	 * Get Table Columns
	 *
	 * @since 4.2.0
	 *
	 * @return array $columns An array of columns displayed in the table.
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => esc_html__( 'Title', 'wp-courseware' ),
			'course'    => esc_html__( 'Course', 'wp-courseware' ),
			'unit'      => esc_html__( 'Unit', 'wp-courseware' ),
			'type'      => esc_html__( 'Quiz Type', 'wp-courseware' ),
			'answers'   => esc_html__( 'Show Answers?', 'wp-courseware' ),
			'paging'    => esc_html__( 'Use Paging?', 'wp-courseware' ),
			'questions' => esc_html__( 'Questions', 'wp-courseware' ),
		);

		return $columns;
	}

	/**
	 * Get Sortable Columns.
	 *
	 * @since 4.2.0
	 *
	 * @return array $columns An array of sortable displayed in the table.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'  => array( 'title', false ),
			'unit'   => array( 'unit', false ),
			'course' => array( 'course', false ),
			'type'   => array( 'type', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Quiz $quiz
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $quiz, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $quiz->$column_name ) ? $quiz->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The quiz object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $quiz ) {
		return '<input type="checkbox" name="quiz_id[]" value="' . absint( $quiz->get_quiz_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_id( $quiz ) {
		return $quiz->get_quiz_id();
	}

	/**
	 * Column Title.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz
	 *
	 * @return string
	 */
	public function column_title( $quiz ) {
		$row_actions = array();

		$quiz_id = $quiz->get_quiz_id();

		$base_query_args = array(
			'page'    => $this->page->get_slug(),
			'quiz_id' => $quiz_id,
		);

		$title = $quiz->get_quiz_title();

		$edit_quiz_url   = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'WPCW_showPage_ModifyQuiz' ) ), admin_url( 'admin.php' ) ) );
		$delete_quiz_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) ) );

		$value = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_quiz_url, $title );

		$row_actions['quiz_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $quiz_id );

		$row_actions['edit_quiz'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_quiz_url )
		);

		$row_actions['duplicate_quizzes'] = $this->get_row_action_link(
			esc_html__( 'Duplicate', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => '#',
				'class'    => 'wpcw_quizzes_admin_duplicate',
				'atts'     => sprintf( 'data-postid="%d" data-nonce="%s"', $quiz_id, wp_create_nonce( 'quiz-nonce' ) ),
			)
		);

		$row_actions['delete'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => $delete_quiz_url,
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete the this quiz?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'quiz-nonce',
			)
		);

		$value .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $value;
	}

	/**
	 * Column Unit.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_unit( $quiz ) {
		$unit_id = $quiz->get_parent_unit_id();

		if ( ! $unit_id ) {
			return esc_html__( 'N/A', 'wp-courseware' );
		}

		$unit_url = esc_url( add_query_arg( array( 'post' => $unit_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) );
		$unit     = sprintf( '<a href="%s">%s</a>', $unit_url, wpcw()->quizzes->get_quiz_unit_title( $unit_id ) );

		return $unit;
	}

	/**
	 * Column Course.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_course( $quiz ) {
		$course_id = wpcw_course_get_post_id( $quiz->get_parent_course_id() );

		if ( ! $course_id ) {
			return esc_html__( 'N/A', 'wp-courseware' );
		}

		$course_object = new Course( absint( $course_id ) );
		$course  = sprintf( '<a target="_blank" href="%s">%s</a>', $course_object->get_edit_url(), $course_object->get_course_title() );

		return $course;
	}

	/**
	 * Column Type.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_type( $quiz ) {
		$quiz_type = wpcw()->quizzes->get_quiz_type_name( $quiz->get_quiz_type() );

		if ( 'quiz_block' === $quiz->get_quiz_type() ) {
			$quiz_type .= sprintf( __( '<span class="wpcw_quiz_pass_info">Min. Pass Mark of <strong>%d%%</strong></span>', 'wp-courseware' ), $quiz->get_quiz_pass_mark() );
		}

		if ( 'survey' == $quiz->get_quiz_type() ) {
			$export_responses_url = esc_url( add_query_arg( array( 'wpcw_export' => 'csv_export_survey_data', 'quiz_id' => $quiz->get_quiz_id() ), $this->page->get_url() ) );
			$quiz_type            .= sprintf( '<a class="export-link" href="%s"><i class="wpcw-fa wpcw-fa-download" aria-hidden="true"></i> %s</a>', $export_responses_url, esc_html__( 'Export Responses', 'wp-courseware' ) );
		}

		return $quiz_type;
	}

	/**
	 * Column Show Answers.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_answers( $quiz ) {
		return ( 'show_answers' === $quiz->get_quiz_show_answers() || 'show_responses' === $quiz->get_quiz_show_survey_responses() ) ? '<span class="wpcw_checkmark"></span>' : '<span class="wpcw_circle"></span>';
	}

	/**
	 * Column Paging.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_paging( $quiz ) {
		return ( 'use_paging' == $quiz->get_quiz_paginate_questions() ? '<span class="wpcw_checkmark"></span>' : '<span class="wpcw_circle"></span>' );
	}

	/**
	 * Column Questions.
	 *
	 * @since 4.2.0
	 *
	 * @param Quiz $quiz The module object.
	 *
	 * @return int
	 */
	public function column_questions( $quiz ) {
		$quiz_quesitons = WPCW_quizzes_calculateActualQuestionCount( $quiz->get_quiz_id() );

		return $quiz_quesitons ? absint( $quiz_quesitons ) : 0;
	}

	/**
	 * Get Bulk Actions.
	 *
	 * @since 4.2.0
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
	 * @since 4.2.0
	 */
	public function process_bulk_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-quizzes' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'quiz-nonce' ) ) {
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

		// Process Actions.
		switch ( $current_action ) {
			case 'delete' :
				$this->process_action_delete();
				break;
			case 'bulk-delete' :
				$this->process_action_bulk_delete();
				break;
			case 'filter-by-course-and-unit' :
				$this->process_action_filter_by_course_and_unit();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Quizzes Table Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_quizzes_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_delete() {
		$quiz_id = wpcw_get_var( 'quiz_id' );

		if ( $quiz = wpcw()->quizzes->delete_quiz( $quiz_id ) ) {
			$message = sprintf( __( 'Quiz <strong>%s</strong> deleted successfully.', 'wp-courseware' ), $quiz->get_quiz_title() );
			wpcw_add_admin_notice_success( $message );
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
		$ids = isset( $_GET['quiz_id'] ) ? $_GET['quiz_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			wpcw()->quizzes->delete_quiz( $id );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Quizzes successfully deleted!', 'wp-courseware' ) );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Filter by Course and Unit.
	 *
	 * @since 4.3.0
	 */
	public function process_action_filter_by_course_and_unit() {
		$course_id = ! empty( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
		$unit_id   = ! empty( $_GET['unit_id'] ) ? absint( $_GET['unit_id'] ) : 0;

		$url = $this->page->get_url();

		if ( ! empty( $course_id ) ) {
			$url = add_query_arg( array( 'course_id' => $course_id ), $url );
		}

		if ( ! empty( $unit_id ) ) {
			$url = add_query_arg( array( 'unit_id' => $unit_id ), $url );
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 4.2.0
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
			$this->units_dropdown();

			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo $output;
				printf( '<button class="button" id="quizzes-query-submit" name="filter_action" value="filter-by-course-and-unit" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>', esc_html__( 'Filter', 'wp-courseware' ) );
				printf( '<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>', $this->page->get_url(), esc_html__( 'Reset', 'wp-courseware' ) );
			}
			?>
        </div>
		<?php
	}

	/**
	 * Displays a Courses drop-down for filtering on the Quizzes Table.
	 *
	 * @since 4.2.0
	 */
	protected function courses_dropdown() {
		/**
		 * Filters whether to remove the 'Courses' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool $disable Whether to disable the categories drop-down. Default false.
		 */
		if ( false !== apply_filters( 'wpcw_quizzes_disable_filter_by_courses_dropdown', false ) ) {
			return;
		}

		echo wpcw()->courses->get_courses_filter_dropdown();
	}

	/**
	 * Displays a Units drop-down for filtering on the Quizzes Table.
	 *
	 * @since 4.2.0
	 */
	protected function units_dropdown() {
		/**
		 * Filters whether to remove the 'Units' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool $disable Whether to disable the categories drop-down. Default false.
		 */
		if ( false !== apply_filters( 'wpcw_quizzes_disable_filter_by_units_dropdown', false ) ) {
			return;
		}

		echo wpcw()->units->get_units_filter_dropdown();
	}

	/**
	 * Get Views.
	 *
	 * @since 4.2.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = isset( $_GET['status'] ) ? $_GET['status'] : '';

		$total = sprintf( '&nbsp;<span class="count">(%s)</span>', $this->total );

		$views = array(
			'all' => sprintf(
				'<a href="%s" %s>%s</a>',
				esc_url( remove_query_arg( 'status', $this->page->get_url() ) ),
				( 'all' === $current || '' === $current ) ? ' class="current"' : '',
				esc_html__( 'All', 'wp-courseware' ) . $total
			),
		);

		return $views;
	}

	/**
	 * Get Quizzes Query Args.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_quizzes_args() {
		$page      = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search    = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order     = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby   = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'quiz_id';
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : false;
		$unit_id   = isset( $_GET['unit_id'] ) ? absint( $_GET['unit_id'] ) : false;
		$author    = 0;

		// Check if admin
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$author = get_current_user_id();
		}

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'      => $per_page,
			'offset'      => $per_page * ( $page - 1 ),
			'quiz_author' => $author,
			'course_id'   => $course_id,
			'unit_id'     => $unit_id,
			'search'      => $search,
			'orderby'     => sanitize_text_field( $orderby ),
			'order'       => sanitize_text_field( $order ),
		);

		return $args;
	}

	/**
	 * Get Quizzes Count.
	 *
	 * @since 4.2.0
	 *
	 * @return int The quizzes count.
	 */
	public function get_quizzes_count() {
		$this->count = wpcw()->quizzes->get_quizzes_count( $this->get_quizzes_args() );

		return $this->count;
	}

	/**
	 * Get Quizzes Data.
	 *
	 * @since 4.2.0
	 *
	 * @return array $data Quizzes data.
	 */
	public function get_quizzes_data() {
		return wpcw()->quizzes->get_quizzes( $this->get_quizzes_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.2.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_quizzes_data();

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
	 * @since 4.2.0
	 */
	public function no_items() {
		esc_html_e( 'Sorry, no quizzes have been created.', 'wp-courseware' );
	}
}
