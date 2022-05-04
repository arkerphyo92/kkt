<?php
/**
 * WP Courseware Students Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Students;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Students.
 *
 * @since 4.1.0
 */
class Page_Students extends Page {

	/**
	 * @var string Screen Option Students per page option.
	 * @since 4.1.0
	 */
	protected $screen_per_page_option = 'students_per_page';

	/**
	 * @var int Screen Option modules per page default.
	 * @since 4.1.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Students Page.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		$this->table = new Table_Students( array(
			'page'            => $this,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );
		$this->table->prepare_items();
	}

	/**
	 * Get Students Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-student-new' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Add New', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a id="wpcw-enroll-bulk-students" class="page-title-action" href="#">
                <i class="wpcw-fa wpcw-fa-user-plus left" aria-hidden="true"></i> 
                %s
            </a>',
			esc_html__( 'Bulk Enroll Students', 'wp-courseware' )
		);

		return $actions;
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
	 * Get Students Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Students', 'wp-courseware' );
	}

	/**
	 * Get Students Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Students', 'wp-courseware' );
	}

	/**
	 * Get Students Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_students_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Students Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-students';
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
			'enrollment/enroll-bulk-students',
		);

		foreach ( $views as $view ) {
			echo $this->get_view( $view );
		}

		?>
        <div id="wpcw-send-class-email-instance">
            <wpcw-classroom-send-email v-once></wpcw-classroom-send-email>
	        <div id="wpcw-hidden-wp-email-editor" style="display: none;"><?php wp_editor( '', 'wpcw_email_content', array( 'media_buttons' => true ) ); ?></div>
        </div>
        <div id="wpcw-enroll-bulk">
            <wpcw-enroll-bulk-students></wpcw-enroll-bulk-students>
        </div>
		<?php
	}

	/**
	 * Students Page Display.
	 *
	 * @since 4.1.0
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_students_display_top', $this );
		?>
        <form id="wpcw-admin-page-students-form" method="get" action="<?php echo $this->get_url(); ?>">
            <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
			<?php $this->table->search_box( esc_html__( 'Search Students', 'wp-courseware' ), 'wpcw-students' ); ?>
			<?php $this->table->views(); ?>
			<?php $this->table->display(); ?>
        </form>
		<?php
		do_action( 'wpcw_admin_page_students_display_bottom', $this );
	}
}
