<?php
/**
 * Checkout Cart.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/cart.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<table id="wpcw-cart" class="wpcw-table wpcw-cart-table">
	<thead>
	<tr>
		<th class="course-name" colspan="2"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
		<th class="course-price"><?php esc_html_e( 'Price', 'wp-courseware' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php do_action( 'wpcw_cart_courses_before' ); ?>
	<?php
	foreach ( wpcw()->cart->get_cart() as $cart_course_key => $cart_course ) {
		$course = wpcw()->cart->get_cart_course_object( $cart_course );
		if ( ! $course || ! $course->is_purchasable() ) {
			continue;
		}
		?>
		<tr>
			<td class="course-remove">
				<?php do_action( 'wpcw_cart_course_actions', $cart_course_key, $course ); ?>
				<?php echo wpcw_remove_from_cart_link( $cart_course_key ); ?>
			</td>
			<td class="course-name">
				<?php echo esc_html( $course->get_course_title() ); ?>
				<?php do_action( 'wpcw_cart_course_title_after', $cart_course_key, $course ); ?>
			</td>
			<td class="course-price">
				<?php echo esc_attr( $course->get_price_label() ); ?>
				<?php do_action( 'wpcw_cart_course_price_after', $cart_course_key, $course ); ?>
			</td>
		</tr>
	<?php } ?>
	<?php do_action( 'wpcw_cart_courses_after' ); ?>
	</tbody>
	<tfoot>
	<tr class="cart-subtotal">
		<td colspan="3">
			<span class="total-label"><?php esc_html_e( 'Subtotal:', 'wp-courseware' ); ?></span>
			<span class="total-amount"><?php echo wpcw()->cart->subtotal(); ?></span>
		</td>
	</tr>
	<?php if ( wpcw_coupons_enabled() ) { ?>
		<?php foreach ( wpcw()->cart->get_coupons() as $code => $coupon ) { ?>
			<tr class="cart-coupon coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<td colspan="3">
					<span class="coupon-label"><?php wpcw_cart_coupon_label( $coupon ); ?></span>
					<span class="coupon-amount"><?php wpcw_cart_coupon_html( $coupon ); ?></span>
				</td>
			</tr>
		<?php } ?>
	<?php } ?>
	<?php if ( wpcw_taxes_enabled() ) { ?>
		<tr class="cart-tax-total">
			<td colspan="3">
				<span class="total-label"><?php esc_html_e( 'Taxes', 'wp-courseware' ); ?></span>
				<span class="total-amount"><?php echo wpcw()->cart->tax(); ?></span>
			</td>
		</tr>
	<?php } ?>
	<tr class="cart-total">
		<td colspan="3">
			<span class="total-label"><?php esc_html_e( 'Total:', 'wp-courseware' ); ?></span>
			<span class="total-amount"><?php echo wpcw()->cart->total(); ?></span>
		</td>
	</tr>
	</tfoot>
</table>
