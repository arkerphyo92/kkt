<?php
/**
 * Checkout Payment.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/payment.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<div id="wpcw-checkout-payment" class="wpcw-checkout-payment">
	<?php if ( wpcw()->cart->needs_payment() ) : ?>
        <ul class="wpcw-payment-methods">
			<?php if ( ! empty( $available_gateways ) ) { ?>
				<?php foreach ( $available_gateways as $gateway ) { ?>
					<?php wpcw_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) ); ?>
				<?php } ?>
			<?php } else { ?>
                <li class="wpcw-notice wpcw-notice-info"><?php echo wpcw_get_checkout_no_payment_methods_notice(); ?></li>
			<?php } ?>
        </ul>
	<?php endif; ?>
    <div class="wpcw-form-row wpcw-place-order<?php echo ( ! wpcw()->cart->needs_payment() ) ? ' wpcw-place-order-no-payment' : ''; ?>">
		<?php do_action( 'wpcw_before_submit' ); ?>

        <div class="wpcw-privacy-checkboxes">
			<?php wpcw_get_template( 'checkout/terms.php' ); ?>
			<?php wpcw_get_template( 'checkout/privacy.php' ); ?>
        </div>

		<?php echo apply_filters( 'wpcw_order_button_html', '<button type="submit" class="button alt wpcw-checkout-payment-button" name="wpcw_checkout_place_order" id="wpcw-place-order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); ?>

		<?php do_action( 'wpcw_after_submit' ); ?>

		<?php wp_nonce_field( 'wpcw-process-checkout' ); ?>
    </div>
</div>
