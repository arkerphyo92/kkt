<?php
/**
 * WP Courseware Modules Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Modules;
use WPCW\Models\Module;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Modules.
 *
 * @since 4.1.0
 */
class Page_Modules extends Page {

	/**
	 * @var string Screen Option Modules per page option.
	 * @since 4.1.0
	 */
	protected $screen_per_page_option = 'modules_per_page';

	/**
	 * @var int Screen Option modules per page default.
	 * @since 4.1.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Modules Page.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		$this->table = new Table_Modules( array(
			'page'            => $this,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );
		$this->table->prepare_items();
	}

	/**
	 * Get Modules Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'WPCW_showPage_ModifyModule' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Add New', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => 'course_unit' ), admin_url( 'edit.php' ) ),
			esc_html__( 'View Units', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => 'wpcw_course' ), admin_url( 'edit.php' ) ),
			esc_html__( 'View Courses', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Module Actions.
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
				$module_id = wpcw_get_var( 'module_id' );
				if ( $module = wpcw()->modules->delete_module( $module_id ) ) {
					$message = sprintf( __( 'Module <strong>%s</strong> deleted successfully.', 'wp-courseware' ), $module->module_title );
					wpcw_add_admin_notice_success( $message, 'success' );
				}
				wp_safe_redirect( $this->get_url() );
				exit;
				break;
			default:
				break;
		}
	}

	/**
	 * Get Modules Page Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_screen_options() {
		return array(
			'per_page' => array(
				'label'   => esc_html__( 'Number of modules per page', 'wp-courseware' ),
				'default' => $this->screen_per_page_default,
				'option'  => $this->screen_per_page_option,
			),
		);
	}

	/**
	 * Get Modules Page Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Modules', 'wp-courseware' );
	}

	/**
	 * Get Modules Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Modules', 'wp-courseware' );
	}

	/**
	 * Get Modules Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_modules_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Modules Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-modules';
	}

	/**
	 * Display Modules Page.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_modules_display_top', $this );
		?>
        <form id="wpcw-admin-page-modules-form" method="get" action="<?php echo $this->get_url(); ?>">
            <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
			<?php $this->table->search_box( esc_html__( 'Search Modules', 'wp-courseware' ), 'wpcw-modules' ); ?>
			<?php $this->table->views(); ?>
			<?php $this->table->display(); ?>
        </form>
		<?php
		do_action( 'wpcw_admin_page_modules_display_bottom', $this );
	}
}