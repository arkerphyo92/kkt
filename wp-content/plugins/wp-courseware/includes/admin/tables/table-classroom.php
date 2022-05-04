<?php
/**
 * WP Courseware Classroom Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.3.0
 */

namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Course_Classroom;
use WPCW\Models\Course;
use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Classroom Table.
 *
 * @since 4.1.0
 */
class Table_Classroom extends Table_Students {

	/**
	 * @var Page_Course_Classroom The Course Classroom Admin Page.
	 * @since 4.1.0
	 */
	protected $page;

	/**
	 * @var Course The course object.
	 * @since 4.1.0
	 */
	protected $course;

	/**
	 * Table_Classroom Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args = array() ) {
		if ( isset( $args['course'] ) ) {
			$this->course = $args['course'];
			unset( $args['course'] );
		} else {
			$this->course = new Course( 0 );
		}

		parent::__construct( $args );
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
			'name'     => esc_html__( 'Name', 'wp-courseware' ),
			'email'    => esc_html__( 'Email', 'wp-courseware' ),
			'percent'  => esc_html__( 'Progress %', 'wp-courseware' ),
			'progress' => esc_html__( 'Detailed Progress', 'wp-courseware' ),
		);

		return $columns;
	}

	/**
	 * Get Sortable Columns.
	 *
	 * @since 4.6.4
	 *
	 * @return array $columns An array of sortable displayed in the table.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'      => array( 'id', false ),
			'name'    => array( 'name', false ),
			'email'   => array( 'email', false ),
			'percent' => array( 'percent', false ),
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
	 * Column Percent.
	 *
	 * @since 4.1.0
	 *
	 * @param Student $student The current student.
	 *
	 * @return string The student progress bar.
	 */
	public function column_percent( $student ) {
		$student_id = $student->get_ID();
		$course_id  = $this->course->get_course_id();

		return wpcw()->students->get_student_progress_bar( $student_id, $course_id );
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
		$student_id = $student->get_ID();
		$course_id  = $this->course->get_course_id();

		ob_start();
		?>
		<form method="get" action="<?php echo $this->page->get_url(); ?>">
			<input type="hidden" name="wpcw_users_single" value="<?php echo $student_id; ?>"/>
			<input type="hidden" name="course_id" value="<?php echo $course_id; ?>"/>
			<?php echo $this->get_single_reset_dropdown( array( $course_id ) ); ?>
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
	public function get_bulk_reset_dropdown() {
		$course_id = $this->course->get_course_id();

		if ( ! $course_id ) {
			return;
		}

		ob_start();
		?>
		<input type="hidden" name="bulk_course_id" value="<?php echo $course_id; ?>"/>
		<?php echo wpcw()->courses->get_courses_reset_dropdown( array( $course_id ), true ); ?>
		<button type="submit" name="wpcw_user_bulk_progress_reset" id="wpcw_user_progress_reset_point_bulk_btn" class="button" value="<?php esc_html_e( 'Bulk Reset', 'wp-courseware' ); ?>"><i
				class="wpcw-fas wpcw-fa-undo"></i> <?php esc_html_e( 'Student Reset', 'wp-courseware' ); ?></button>
		<span class="or-divider"><?php esc_html_e( 'or', 'wp-courseware' ); ?></span>
		<button type="submit" name="wpcw_classroom_bulk_progress_reset" id="wpcw_classroom_bulk_progress_reset" class="button" value="<?php esc_html_e( 'Classroom Reset', 'wp-courseware' ); ?>"><i
				class="wpcw-fas wpcw-fa-undo"></i> <?php esc_html_e( 'Full Classroom Reset', 'wp-courseware' ); ?></button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Bulk Actions.
	 *
	 * @since 4.5.0
	 *
	 * @return array $actions The bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			/* translators: %s - Course Title */
			'remove-from-course' => sprintf( esc_html__( 'Remove student(s) from %s', 'wp-courseware' ), $this->course->get_course_title() ),
		);

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$actions = array();
		}

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
			case 'remove-from-course':
				$this->process_action_bulk_remove_student();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Classroom Table Actions.
		 *
		 * @since 4.5.0
		 */
		do_action( 'wpcw_classroom_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Remove Student.
	 *
	 * @since 4.5.0
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
			wpcw()->students->remove_student_from_course( $id, $this->course->get_id() );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Student(s) removed successfully!', 'wp-courseware' ) );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @since 4.1.0
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		$bulk_actions = $this->get_bulk_actions();
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( $this->has_items() && ! empty( $bulk_actions ) ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php endif;

			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>
		<div class="alignleft wpcw-custom-actions wpcw-custom-bulkactions">
			<?php echo $this->get_bulk_reset_dropdown(); ?>
		</div>
		<?php
	}

	/**
	 * Get Classroom Student Args.
	 *
	 * @since 4.3.0
	 */
	public function get_classroom_students_args() {
		global $wpdb;

		$page      = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search    = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order     = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby   = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'user_id';
		$course_id = isset( $_GET['course_id'] ) ? $_GET['course_id'] : 0;

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		if ( 'percent' === $orderby ) {
			$orderby = 'progress';
		}

		return array(
			'number'    => $per_page,
			'offset'    => $per_page * ( $page - 1 ),
			'search'    => $search,
			'orderby'   => sanitize_text_field( $orderby ),
			'order'     => sanitize_text_field( $order ),
			'course_id' => $course_id,
		);
	}

	/**
	 * Get Students Count.
	 *
	 * @since 4.1.0
	 *
	 * @return int The students count.
	 */
	public function get_students_count() {
		$this->count = wpcw()->students->get_students_count( $this->get_classroom_students_args() );

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
		return wpcw()->students->get_students( $this->get_classroom_students_args() );
	}
}
