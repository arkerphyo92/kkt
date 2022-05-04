<?php
/**
 * WP Courseware Orders Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.3.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Orders;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Orders.
 *
 * @since 4.3.0
 */
class Page_Orders extends Page {

	/**
	 * @var string Screen Option Orders per page option.
	 * @since 4.1.0
	 */
	protected $screen_per_page_option = 'orders_per_page';

	/**
	 * @var int Screen Option orders per page default.
	 * @since 4.1.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Courses Page.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		$this->table = new Table_Orders( array(
			'page'            => $this,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );
		$this->table->prepare_items();
	}

	/**
	 * Get Orders Menu Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Orders', 'wp-courseware' );
	}

	/**
	 * Get Orders Page Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Orders', 'wp-courseware' );
	}

	/**
	 * Get Orders Page Capability.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_orders_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Orders Page Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-orders';
	}

	/**
	 * Get Orders Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-order' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Add New', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-subscriptions' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Subscriptions', 'wp-courseware' )
		);

		if ( wpcw_coupons_enabled() ) {
			$actions .= sprintf(
				'<a class="page-title-action" href="%s">%s</a>',
				add_query_arg( array( 'page' => 'wpcw-coupons' ), admin_url( 'admin.php' ) ),
				esc_html__( 'View Coupons', 'wp-courseware' )
			);
		}

		return $actions;
	}

	/**
	 * Orders Page Display.
	 *
	 * @since 4.3.0
	 */
	protected function display() {
		do_action( 'wpcw_admin_orders_before', $this );

		?>
        <div id="wpcw-orders">
            <form id="wpcw-admin-page-orders-form" method="get" action="<?php echo $this->get_url(); ?>">
                <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
				<?php $this->table->search_box( esc_html__( 'Search Orders', 'wp-courseware' ), 'wpcw-orders' ); ?>
				<?php $this->table->views(); ?>
				<?php $this->table->display(); ?>
            </form>
        </div>
		<?php

		do_action( 'wpcw_admin_orders_after', $this );
	}
}
