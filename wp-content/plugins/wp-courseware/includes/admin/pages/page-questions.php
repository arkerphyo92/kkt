<?php
/**
 * WP Courseware Questions Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Questions;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Questions.
 *
 * @since 4.1.0
 */
class Page_Questions extends Page {

	/**
	 * @var string Screen Option Quesitons per page option.
	 * @since 4.2.0
	 */
	protected $screen_per_page_option = 'questions_per_page';

	/**
	 * @var int Screen Option questions per page default.
	 * @since 4.2.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Quesitons Page.
	 *
	 * @since 4.2.0
	 */
	public function load() {
		$this->table = new Table_Questions( array(
			'page'            => $this,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );
		$this->table->prepare_items();
	}

	/**
	 * Get Quizzes Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = wpcw()->questions->get_add_question_dropdown_form();

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-quizzes' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Quizzes', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => 'wpcw_course' ), admin_url( 'edit.php' ) ),
			esc_html__( 'View Courses', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-modules' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Modules', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => 'course_unit' ), admin_url( 'edit.php' ) ),
			esc_html__( 'View Units', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Get Quesitons Page Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_screen_options() {
		return array(
			'per_page' => array(
				'label'   => esc_html__( 'Number of questions per page', 'wp-courseware' ),
				'default' => $this->screen_per_page_default,
				'option'  => $this->screen_per_page_option,
			),
		);
	}

	/**
	 * Get Questions Page Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Questions', 'wp-courseware' );
	}

	/**
	 * Get Questions Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Questions', 'wp-courseware' );
	}

	/**
	 * Get Questions Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_questions_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Questions Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-questions';
	}

	/**
	 * Display Questions Page.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_questions_display_top', $this );
		?>
        <form id="wpcw-admin-page-questions-form" method="get" action="<?php echo $this->get_url(); ?>">
            <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
			<?php $this->table->search_box( esc_html__( 'Search Questions', 'wp-courseware' ), 'wpcw-quesitons' ); ?>
			<?php $this->table->views(); ?>
			<?php $this->table->display(); ?>
        </form>
		<?php
		do_action( 'wpcw_admin_page_questions_display_bottom', $this );
	}
}