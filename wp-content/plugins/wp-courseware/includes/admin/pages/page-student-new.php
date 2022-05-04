<?php
/**
 * WP Courseware Student Add New Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.2.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Student_New.
 *
 * @since 4.2.0
 */
class Page_Student_New extends Page {

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.2.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-students';
	}

	/**
	 * Get Add New Student Menu Title.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Add New Student', 'wp-courseware' );
	}

	/**
	 * Get Add New Student Page Title.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Add New Student', 'wp-courseware' );
	}

	/**
	 * Get Student Page Capability.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_add_student_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Add New Student Page Slug.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-student-new';
	}

	/**
	 * Get Student Action Buttons.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-students' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Back to Students', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Student Page Display.
	 *
	 * @since 4.2.0
	 *
	 * @return mixed
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_student_display_top', $this );
		?>
        <div id="wpcw-new-student-page">
            <wpcw-student-new></wpcw-student-new>
        </div>
		<?php
		do_action( 'wpcw_admin_page_student_display_bottom', $this );
	}

	/**
	 * Is Student Page Hidden?
	 *
	 * @since 4.2.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}

	/**
	 * Add New Student Page Views.
	 *
	 * @since 4.2.0
	 */
	public function views() {
		$views = array(
			'common/notices',
			'common/form',
			'common/form-row',
			'common/form-field',
			'student/new',
		);

		foreach ( $views as $view ) {
			echo $this->get_view( $view );
		}
	}
}