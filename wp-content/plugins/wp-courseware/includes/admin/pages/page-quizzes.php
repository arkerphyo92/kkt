<?php
/**
 * WP Courseware Quizzes / Surveys Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Quizzes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Quizzes.
 *
 * @since 4.1.0
 */
class Page_Quizzes extends Page {

	/**
	 * @var string Screen Option Quizzes per page option.
	 * @since 4.2.0
	 */
	protected $screen_per_page_option = 'quizzes_per_page';

	/**
	 * @var int Screen Option quizzes per page default.
	 * @since 4.2.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Quizzes Page.
	 *
	 * @since 4.2.0
	 */
	public function load() {
		$this->table = new Table_Quizzes( array(
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
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'WPCW_showPage_ModifyQuiz' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Add New', 'wp-courseware' )
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
	 * Quizzes Actions.
	 *
	 * @since 4.1.0
	 */
	public function actions() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

		if ( ! $action ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				$quiz_id = wpcw_get_var( 'quiz_id' );
				if ( $quiz = wpcw()->quizzes->delete_quiz( $quiz_id ) ) {
					$message = sprintf( __( 'Quiz <strong>%s</strong> deleted successfully.', 'wp-courseware' ), $quiz->get_quiz_title() );
					wpcw_add_admin_notice_success( $message );
				}

				wp_safe_redirect( $this->get_url() );
				exit;
				break;
			default:
				break;
		}
	}

	/**
	 * Get Quizzes Page Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_screen_options() {
		return array(
			'per_page' => array(
				'label'   => esc_html__( 'Number of quizzes / surveys per page', 'wp-courseware' ),
				'default' => $this->screen_per_page_default,
				'option'  => $this->screen_per_page_option,
			),
		);
	}

	/**
	 * Get Quizzes / Surveys Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Quizzes', 'wp-courseware' );
	}

	/**
	 * Get Quizzes/Surveys Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Quizzes', 'wp-courseware' );
	}

	/**
	 * Get Quizzes / Surveys Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_quizzes_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Quizzes / Surveys Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-quizzes';
	}

	/**
	 * Display Quizzes Page.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_quizzes_display_top', $this );
		?>
        <form id="wpcw-admin-page-quizzes-form" method="get" action="<?php echo $this->get_url(); ?>">
            <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
			<?php $this->table->search_box( esc_html__( 'Search Quizzes / Surveys', 'wp-courseware' ), 'wpcw-quizzes' ); ?>
			<?php $this->table->views(); ?>
			<?php $this->table->display(); ?>
        </form>
		<?php
		do_action( 'wpcw_admin_page_quizzes_display_bottom', $this );
	}
}