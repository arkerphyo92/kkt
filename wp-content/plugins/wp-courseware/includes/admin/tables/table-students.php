<?php
/**
 * WP Courseware Students Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.1.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Students;
use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Students Table.
 *
 * @since 4.1.0
 */
class Table_Students extends Table {

	/**
	 * @var Page_Students The Students Admin Page.
	 * @since 4.1.0
	 */
	protected $page;

	/**
	 * @var string The Course reset dropdown.
	 * @since 4.1.0
	 */
	protected $reset_dropdown;

	/**
	 * @var array The array of hidden columns.
	 * @since 4.1.0
	 */
	protected $hidden_columns;

	/**
	 * Students Table Constructor.
	 *
	 * @since 4.1.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'student',
			'plural'   => 'students',
		) );

		parent::__construct( $args );

		$this->process_bulk_actions();

		$this->hidden_columns = get_hidden_columns( $this->screen );

		$this->total = $this->get_students_count();
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
			'cb'          => '<input type="checkbox" />',
			'name'        => esc_html__( 'Name', 'wp-courseware' ),
			'email'       => esc_html__( 'Email', 'wp-courseware' ),
			'progressbar' => esc_html__( 'Progress', 'wp-courseware' ),
			'reset'       => esc_html__( 'Reset Progress', 'wp-courseware' ),
			// 'progress'    => esc_html__( 'Detailed Progress', 'wp-courseware' ),
			'access'      => esc_html__( 'Update Access', 'wp-courseware' ),
		);

		/**
		 * Filter: Students Table Columns
		 *
		 * @since 4.4.0
		 *
		 * @param array $columns The array of columns.
		 *
		 * @return array $columns The array of columns.
		 */
		return apply_filters( 'manage_wpcw_students_table_columns', $columns );
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
			'name'  => array( 'name', false ),
			'email' => array( 'email', false ),
		);

		/**
		 * Filter: Students Sortable Table Columns
		 *
		 * @since 4.4.0
		 *
		 * @param array $sortable_columns The array of sortable columns.
		 *
		 * @return array $sortable_columns The array of sortable columns.
		 */
		return apply_filters( 'manage_wpcw_students_table_sortable_columns', $sortable_columns );
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Student $student
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $student, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $student->$column_name ) ? $student->$column_name : '';
				break;
		}

		/**
		 * Action: Students Table Custom Column.
		 *
		 * Allows you to output custom information into each
		 * custom column.
		 *
		 * @since 4.4.0
		 *
		 * @param string $column_name The name of the column to display.
		 * @param Student $post_id The current student object.
		 */
		do_action( "manage_wpcw_students_table_custom_column", $column_name, $student );

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The student object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $student ) {
		return '<input type="checkbox" name="student_id[]" value="' . absint( $student->get_ID() ) . '" />';
	}

	/**
	 * Column Name.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student
	 *
	 * @return string
	 */
	public function column_name( $student ) {
		$row_actions = array();

		$student_id = $student->get_ID();

		$base_query_args = array(
			'page'    => $this->page->get_slug(),
			'user_id' => $student_id,
		);

		$title = $student->get_full_name( true );

		$edit_student_url = esc_url_raw( add_query_arg( array( 'page' => 'wpcw-student', 'id' => $student_id ), admin_url( 'admin.php' ) ) );

		$value = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_student_url, $title );

		$row_actions['student_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $student_id );

		$row_actions['edit_student'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_student_url )
		);

		$student_json = $this->get_student_json( $student );

		$row_actions['email_student'] = $this->get_row_action_link(
			esc_html__( 'Email', 'wp-courseware' ),
			array(),
			array(
				'class'    => 'students-action-email',
				'base_uri' => $edit_student_url,
				'atts'     => sprintf( 'data-student="%s"', $student_json ),
			)
		);

		$detailed_progress_url = add_query_arg( array( 'page' => 'WPCW_showPage_UserProgess', 'user_id' => $student_id ), admin_url( 'admin.php' ) );

		$row_actions['detailed_progress'] = $this->get_row_action_link(
			esc_html__( 'View Detailed Progress', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $detailed_progress_url )
		);

		$value .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $value;
	}

	/**
	 * Column Email.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string
	 */
	public function column_email( $student ) {
		$student_id    = $student->get_ID();
		$student_email = $student->get_user_email();

		$student_json = $this->get_student_json( $student );

		return sprintf( '<a class="students-action-email" data-student="%s" href="#">%s</a>', $student_json, $student_email );
	}

	/**
	 * Column Progress Bar.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string
	 */
	public function column_progressbar( $student ) {
		if ( in_array( 'progressbar', $this->hidden_columns ) ) {
			return esc_html__( 'Please refresh your page to see this column populated.', 'wp-courseware' );
		}

		$student_id = $student->get_ID();

		$user_progress = wpcw()->students->get_student_progress_bar( $student_id );

		return $user_progress;
	}

	/**
	 * Column Progress.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string
	 */
	public function column_progress( $student ) {
		$detailed_progress_url = add_query_arg( array( 'page' => 'WPCW_showPage_UserProgess', 'user_id' => $student->get_ID() ), admin_url( 'admin.php' ) );

		$button = sprintf( '<a class="button button-secondary" href="%s"><i class="wpcw-fa wpcw-fa-tasks" aria-hidden="true"></i> %s</a>', $detailed_progress_url, esc_html__( 'View Detailed Progress', 'wp-courseware' ) );

		return $button;
	}

	/**
	 * Column Access.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string
	 */
	public function column_access( $student ) {
		$access_url = add_query_arg( array( 'page' => 'WPCW_showPage_UserCourseAccess', 'user_id' => $student->get_ID() ), admin_url( 'admin.php' ) );

		$button = sprintf( '<a class="button" href="%s"><i class="wpcw-fa wpcw-fa-low-vision" aria-hidden="true"></i> %s</a>', $access_url, esc_html__( 'Update Access', 'wp-courseware' ) );

		return $button;
	}

	/**
	 * Column Reset.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string
	 */
	public function column_reset( $student ) {
		if ( in_array( 'reset', $this->hidden_columns ) ) {
			return esc_html__( 'Please refresh your page to see this column populated.', 'wp-courseware' );
		}

		$student_id = $student->get_ID();

		$student_courses = wpcw()->students->get_student_courses( $student_id );
		$courses         = array();

		if ( ! empty( $student_courses ) ) {
			foreach ( $student_courses as $index => $course ) {
				$courses[ $index ]['id']     = $course->course_id;
				$courses[ $index ]['title']  = $course->course_title;
				$courses[ $index ]['author'] = $course->course_author;
			}
		}

		ob_start();
		?>
        <form method="get" action="<?php echo $this->page->get_url(); ?>">
            <input type="hidden" name="wpcw_users_single" value="<?php echo $student_id; ?>"/>
			<?php echo $this->get_single_reset_dropdown( $courses, false ); ?>
        </form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Course Reset Dropdown.
	 *
	 * @since 4.1.0
	 *
	 * @return string Course Reset Dropdown html.
	 */
	public function get_single_reset_dropdown( array $courses, $query = true ) {
		return wpcw()->courses->get_courses_reset_dropdown( $courses, false, $query );
	}

	/**
	 * Column Send Email.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string
	 */
	public function column_semail( $student ) {
		$student_json = $this->get_student_json( $student );

		return sprintf(
			'<a class="button button-secondary students-action-email" data-student="%s">
                <i class="wpcw-fa wpcw-fa-envelope" aria-hidden="true"></i> %s
            </a>',
			$student_json,
			esc_html__( 'Email Student', 'wp-courseware' )
		);
	}

	/**
	 * Get Student Json.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student
	 *
	 * @return string
	 */
	public function get_student_json( $student ) {
		return htmlspecialchars( wp_json_encode( array(
			'id'    => $student->get_ID(),
			'name'  => $student->get_display_name(),
			'email' => $student->get_user_email(),
		) ) );
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
			'bulk-remove-student' => esc_html__( 'Remove from all courses', 'wp-courseware' ),
		);

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$actions = array();
		}

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

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-students' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'student-nonce' ) ) {
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
			case 'remove-student':
			case 'bulk-remove-student' :
				$this->process_action_bulk_remove_student();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Students Table Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_students_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Remove Student.
	 *
	 * @since 4.3.0
	 */
	public function process_action_bulk_remove_student() {
		$ids = isset( $_GET['student_id'] ) ? $_GET['student_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			wpcw()->students->remove_student_from_all_courses( $id );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Students removed successfully!', 'wp-courseware' ) );

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
	 * Get Student Args.
	 *
	 * @since 4.3.0
	 */
	public function get_student_args() {
		global $wpdb;

		$page    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search  = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order   = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'user_id';

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'  => $per_page,
			'offset'  => $per_page * ( $page - 1 ),
			'search'  => $search,
			'orderby' => sanitize_text_field( $orderby ),
			'order'   => sanitize_text_field( $order ),
		);

		return $args;
	}

	/**
	 * Get Students Count.
	 *
	 * @since 4.1.0
	 *
	 * @return int The students count.
	 */
	public function get_students_count() {
		$this->count = wpcw()->students->get_students_count( $this->get_student_args() );

		return $this->count;
	}

	/**
	 * Get Students Data.
	 *
	 * @since 4.1.0
	 *
	 * @return array $data Modules data.
	 */
	public function get_students_data() {
		return wpcw()->students->get_students( $this->get_student_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.1.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_students_data();

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
	 * Text displayed when no student data available.
	 *
	 * @since 4.1.0
	 */
	public function no_items() {
		esc_html_e( 'Sorry, no students have been enrolled.', 'wp-courseware' );
	}
}
