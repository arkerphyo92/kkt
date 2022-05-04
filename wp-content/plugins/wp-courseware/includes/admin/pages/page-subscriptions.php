<?php
/**
 * WP Courseware Subscriptions Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.3.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Subscriptions;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Subscriptions.
 *
 * @since 4.3.0
 */
class Page_Subscriptions extends Page {

	/**
	 * @var string Screen Option Subscriptions per page option.
	 * @since 4.1.0
	 */
	protected $screen_per_page_option = 'subscriptions_per_page';

	/**
	 * @var int Screen Option subscriptions per page default.
	 * @since 4.1.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Courses Page.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		$this->table = new Table_Subscriptions( array(
			'page'            => $this,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );
		$this->table->prepare_items();
	}

	/**
	 * Highlight Subscriptions Parent Submenu.
	 *
	 * @since 4.3.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-subscriptions';
	}

	/**
	 * Get Subscriptions Menu Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Subscriptions', 'wp-courseware' );
	}

	/**
	 * Get Subscriptions Page Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Subscriptions', 'wp-courseware' );
	}

	/**
	 * Get Subscriptions Page Capability.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_subscriptions_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Subscriptions Page Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-subscriptions';
	}

	/**
	 * Get Subscriptions Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-subscription' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Add New', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-orders' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Orders', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Subscriptions Page Display.
	 *
	 * @since 4.3.0
	 */
	protected function display() {
		do_action( 'wpcw_admin_subscriptions_before', $this );

		?>
        <div id="wpcw-subscriptions">
            <form id="wpcw-admin-page-subscriptions-form" method="get" action="<?php echo $this->get_url(); ?>">
                <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
				<?php $this->table->search_box( esc_html__( 'Search Subscriptions', 'wp-courseware' ), 'wpcw-subscriptions' ); ?>
				<?php $this->table->views(); ?>
				<?php $this->table->display(); ?>
            </form>
        </div>
		<?php

		do_action( 'wpcw_admin_subscriptions_after', $this );
	}
}