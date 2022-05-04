<?php
/**
 * WP Courseware Coupons Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.5.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table_Coupons;
use WPCW\Admin\Tables\Table_Orders;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Coupons.
 *
 * @since 4.5.0
 */
class Page_Coupons extends Page {

	/**
	 * @var string Screen Option Coupons per page option.
	 * @since 4.5.0
	 */
	protected $screen_per_page_option = 'coupons_per_page';

	/**
	 * @var int Screen Option Coupons per page default.
	 * @since 4.5.0
	 */
	protected $screen_per_page_default = 20;

	/**
	 * Load Coupons Page.
	 *
	 * @since 4.5.0
	 */
	public function load() {
		$this->table = new Table_Coupons( array(
			'page'            => $this,
			'per_page_option' => $this->screen_per_page_option,
			'per_page'        => $this->screen_per_page_default,
		) );
		$this->table->prepare_items();
	}

	/**
	 * Get Coupons Menu Title.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Coupons', 'wp-courseware' );
	}

	/**
	 * Get Coupons Page Title.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Coupons', 'wp-courseware' );
	}

	/**
	 * Get Coupons Page Capability.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_coupons_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Coupons Page Slug.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-coupons';
	}

	/**
	 * Get Coupons Action Buttons.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-coupon' ), admin_url( 'admin.php' ) ),
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
	 * Coupons Page Display.
	 *
	 * @since 4.5.0
	 */
	protected function display() {
		do_action( 'wpcw_admin_coupons_before', $this );

		?>
		<div id="wpcw-coupons">
			<form id="wpcw-admin-page-coupons-form" method="get" action="<?php echo $this->get_url(); ?>">
				<input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
				<?php $this->table->search_box( esc_html__( 'Search Coupons', 'wp-courseware' ), 'wpcw-coupons' ); ?>
				<?php $this->table->views(); ?>
				<?php $this->table->display(); ?>
			</form>
		</div>
		<?php

		do_action( 'wpcw_admin_coupons_after', $this );
	}

	/**
	 * Page Hidden?
	 *
	 * @since 4.5.0
	 *
	 * @return bool Default is false.
	 */
	public function is_hidden() {
		return ! wpcw_coupons_enabled() ? true : false;
	}
}
