<?php
/**
 * Checkout Cart Empty.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/cart-empty.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

$message = wp_kses_post( apply_filters( 'wpcw_checkout_cart_empty_message', esc_html__( 'Your cart is currently empty.', 'wp-courseware' ) ) );

printf( '<p class="wpcw-checkout-cart-empty">%s</p>', $message );
