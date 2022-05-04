<?php
/**
 * Checkout Form.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/form-checkout.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<form class="wpcw-checkout-form" name="checkout" method="post" action="<?php echo esc_url( wpcw_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<?php do_action( 'wpcw_checkout_form_top', $checkout ); ?>
	<?php do_action( 'wpcw_checkout_cart', $checkout ); ?>
	<?php do_action( 'wpcw_checkout_fields', $checkout ); ?>
	<?php do_action( 'wpcw_checkout_payment', $checkout ); ?>
	<?php do_action( 'wpcw_checkout_form_bottom', $checkout ); ?>
</form>