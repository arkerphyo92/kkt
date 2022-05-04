<?php
/**
 * Checkout Payment Method.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/payment-method.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<li class="wpcw-payment-method wpcw-payment-method-<?php echo $gateway->get_id(); ?>">
    <input id="wpcw-payment-method-<?php echo $gateway->get_id(); ?>"
           type="radio"
           class="input-radio"
           name="payment_method"
           value="<?php echo esc_attr( $gateway->get_id() ); ?>"
           data-order_button_text="<?php echo esc_attr( $gateway->get_order_button_text() ); ?>"
		<?php checked( $gateway->is_chosen(), true ); ?> />

    <label for="wpcw-payment-method-<?php echo $gateway->get_id(); ?>">
		<?php echo $gateway->get_title(); ?>
        <?php echo $gateway->get_icon(); ?>
        <span class="wpcw-clear"></span>
    </label>

	<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
        <div class="wpcw-payment-method-box wpcw-payment-method-<?php echo $gateway->get_id(); ?>" <?php if ( ! $gateway->is_chosen() ) : ?>style="display:none;"<?php endif; ?>>
			<?php $gateway->payment_fields(); ?>
        </div>
	<?php endif; ?>

    <div class="wpcw-clear"></div>
</li>
