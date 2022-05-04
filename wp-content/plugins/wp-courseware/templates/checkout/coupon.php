<?php
/**
 * Checkout Coupon.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/coupon.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.5.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Check if enabled.
if ( ! wpcw_coupons_enabled() ) {
	return;
}
?>
<div class="wpcw-checkout-coupon">
	<div class="wpcw-form">
		<p class="wpcw-form-row wpcw-form-row-coupon-input validate-ignore" id="coupon_code_field">
			<input type="text" name="coupon_code" class="wpcw-input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'wp-courseware' ); ?>"/>
		</p>
		<p class="wpcw-form-row wpcw-form-row-wide wpcw-form-row-coupon-button" id="coupon_code_field">
			<button type="button" class="button wpcw-input-button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'wp-courseware' ); ?>"><?php esc_attr_e( 'Apply coupon', 'wp-courseware' ); ?></button>
		</p>
	</div>
</div>
