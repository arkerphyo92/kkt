<?php
/**
 * WP Courseware Course Classroom Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Models\Course;
use WPCW\Admin\Tables\Table_Classroom;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Course_Classroom.
 *
 * @since 4.1.0
 */
class Page_Course_Classroom extends Page {

	/**
	 * @var Table_Classroom The list table.
	 * @since 4.1.0
	 */
	protected $table;

	/**
	 * @var Course The course object.
	 * @since 4.1.0
	 */
	protected $course;

	/**
	 * @var string Screen Option Students per page option.
	 * @since 4.1.0
	 */
	protected $screen_per_page_option = 'classroom_students_per_page';

	/**
	 * @var int Screen Option modules per page default.
	 * @since 4.1.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Page Init
	 *
	 * @since 4.1.0
	 */
	public function init() {
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;

		if ( $course_id ) {
			$this->course = new Course( absint( $course_id ) );
		}
	}

	/**
	 * Highlight Orders Parent Submenu.
	 *
	 * @since 4.3.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'edit.php?post_type=wpcw_course';
	}

	/**
	 * Load Students Page.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		if ( empty( $this->course ) ) {
			wpcw_add_admin_notice( esc_html__( 'You must choose a course to be able to view this page.', 'wp-courseware' ) );
			wp_redirect( add_query_arg( array( 'page' => 'wpcw-courses' ), admin_url( 'admin.php' ) ) );
			die();
		}

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			if ( $this->course->get_course_author() !== get_current_user_id() ) {
				wpcw_add_admin_notice( esc_html__( 'You do not have access to this course.', 'wp-courseware' ) );
				wp_redirect( add_query_arg( array( 'page' => 'wpcw-courses' ), admin_url( 'admin.php' ) ) );
				die();
			}
		}

		$this->table = new Table_Classroom( array(
			'page'            => $this,
			'course'          => $this->course,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );

		$this->table->prepare_items();
	}

	/**
	 * Get Students Page Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_screen_options() {
		return array(
			'per_page' => array(
				'label'   => esc_html__( 'Number of students per page', 'wp-courseware' ),
				'default' => $this->screen_per_page_default,
				'option'  => $this->screen_per_page_option,
			),
		);
	}

	/**
	 * Get Course Classroom Page Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Classroom', 'wp-courseware' );
	}

	/**
	 * Get Course Classroom Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		if ( $this->course ) {
			return sprintf( '%s: %s', esc_html__( 'Classroom', 'wp-courseware' ), $this->course->course_title );
		}

		return esc_html__( 'Classroom', 'wp-courseware' );
	}

	/**
	 * Get Course Classroom Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_course_ordering_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Course Classroom Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-course-classroom';
	}

	/**
	 * Page - Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The admin url.
	 */
	public function get_url() {
		$url = parent::get_url();

		if ( ! empty( $this->course ) && $this->course instanceof Course ) {
			$url = add_query_arg( array( 'course_id' => $this->course->get_id() ), $url );
		}

		return $url;
	}

	/**
	 * Is the Course Classroom Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}

	/**
	 * Get Classroom Page Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$course_id = $this->course->get_course_id();

		$actions = sprintf( '<a class="page-title-action" href="%s">%s</a>', add_query_arg( array(
			'page'      => 'WPCW_showPage_GradeBook',
			'course_id' => $course_id,
		), admin_url( 'admin.php' ) ), esc_html__( 'View Gradebook', 'wp-courseware' ) );
		$actions .= sprintf( '<a id="wpcw-send-class-email-button" class="page-title-action" href="#">%s</a>', esc_html__( 'Email Classroom', 'wp-courseware' ) );
		$actions .= sprintf( '<a class="page-title-action" href="%s">%s</a>', $this->course->get_edit_url(), esc_html__( 'Back to Course', 'wp-courseware' ) );
		$actions .= sprintf( '<a class="page-title-action" href="%s">%s</a>', add_query_arg( array( 'post_type' => 'wpcw_course' ), admin_url( 'edit.php' ) ), esc_html__( 'Back to Courses', 'wp-courseware' ) );

		return $actions;
	}

	/**
	 * Page - Before Display.
	 *
	 * @since 4.1.0
	 */
	protected function get_before_display() {
		echo '<div class="wpcw-classroom-actions">';
		echo wpcw()->courses->get_courses_switch_dropdown( array(
			'action' => $this->get_url(),
			'page'   => $this->get_slug(),
			'label'  => false,
		) );
		echo $this->get_enroll_users_button();
		echo '</div>';
	}

	/**
	 * Get Enroll Users Button.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_enroll_users_button() {
		ob_start();
		?>
        <button type="button" class="button button-primary" id="wpcw-enroll-users-button">
            <i class="wpcw-fa wpcw-fa-user-plus left" aria-hidden="true"></i>
			<?php esc_html_e( 'Enroll Students', 'wp-courseware' ); ?>
        </button>
		<button type="button" class="button button-primary" id="wpcw-enroll-classroom-button">
			<i class="wpcw-fa wpcw-fa-chalkboard-teacher left" aria-hidden="true"></i>
			<?php esc_html_e( 'Enroll Classroom', 'wp-courseware' ); ?>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Classroom Page Views.
	 *
	 * @since 4.1.0
	 */
	public function views() {
		$views = array(
			'common/notices',
			'common/modal-notices',
			'common/form-field',
			'classroom/classroom-send-email',
			'enrollment/enroll-existing-user',
			'enrollment/enroll-classroom',
		);

		foreach ( $views as $view ) {
			echo $this->get_view( $view );
		}

		?>
        <div id="wpcw-send-class-email-instance">
	        <wpcw-classroom-send-email course="<?php echo $this->course->get_course_id(); ?>" v-once></wpcw-classroom-send-email>
	        <div id="wpcw-hidden-wp-email-editor" style="display: none;"><?php wp_editor( '', 'wpcw_email_content', array( 'media_buttons' => true ) ); ?></div>
        </div>

        <div id="wpcw-enroll-existing-user-dropdown">
            <wpcw-enroll-existing-user course="<?php echo $this->course->get_course_id(); ?>"></wpcw-enroll-existing-user>
        </div>

		<div id="wpcw-enroll-classroom-dropdown">
			<wpcw-enroll-classroom course="<?php echo $this->course->get_course_id(); ?>"></wpcw-enroll-classroom>
		</div>
		<?php
	}

	/**
	 * Course Classroom Page Display.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed
	 */
	protected function display() {
		if ( empty( $this->course ) ) {
			return;
		}

		/**
		 * Fires before the course classroom display.
		 *
		 * @since 4.1.0
		 */
		do_action( 'wpcw_admin_page_course_classroom_display_top', $this );
		?>
        <form id="wpcw-admin-page-course-classroom-form" method="get" action="<?php echo $this->get_url(); ?>">
            <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
            <input type="hidden" name="course_id" value="<?php echo $this->course->get_course_id(); ?>"/>
			<?php $this->table->search_box( esc_html__( 'Search Classroom', 'wp-courseware' ), 'wpcw-course-classroom' ); ?>
			<?php $this->table->views(); ?>
			<?php $this->table->display(); ?>
        </form>
		<?php

		/**
		 * Fires after the course classroom display.
		 *
		 * @since 4.1.0
		 */
		do_action( 'wpcw_admin_page_course_classroom_display_bottom', $this );
	}
}
