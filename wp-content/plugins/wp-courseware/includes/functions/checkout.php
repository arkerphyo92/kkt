<?php
/**
 * WP Courseware Checkout Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get the URL to the checkout page.
 *
 * @since 4.3.0
 *
 * @return string The url to the checkout page.
 */
function wpcw_get_checkout_url() {
	$checkout_url = wpcw_get_page_permalink( 'checkout' );

	if ( $checkout_url ) {
		if ( is_ssl() || 'yes' === wpcw_get_setting( 'force_ssl' ) ) {
			$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
		}
	}

	return apply_filters( 'wpcw_get_checkout_url', $checkout_url );
}

/**
 * Get Checkout Link
 *
 * @since 4.3.0
 *
 * @param array $args The array of arguments to pass to the link
 *
 * @return string The checkout link html.
 */
function wpcw_get_checkout_link( $args = array() ) {
	$defaults = array(
		'icon'       => '<i class="wpcw-fas wpcw-fa-shopping-cart left"></i> ',
		'text'       => apply_filters( 'wpcw_checkout_button_text', esc_html__( 'Checkout', 'wp-courseware' ) ),
		'class'      => implode( ' ', array_filter( array(
			'wpcw-button',
			'wpcw-button-primary',
		) ) ),
		'attributes' => array(
			'rel' => 'nofollow',
		),
	);

	$args = apply_filters( 'wpcw_checkout_link_args', wp_parse_args( $args, $defaults ) );

	return apply_filters( 'wpcw_checkout_button_link', sprintf(
		'<a href="%s" class="%s" %s>%s%s</a>',
		wpcw_get_checkout_url(),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'wpcw-button wpcw-button-primary' ),
		isset( $args['attributes'] ) ? wpcw_implode_html_attributes( $args['attributes'] ) : '',
		$args['icon'],
		$args['text']
	), $args );
}

/**
 * Get No Payment Methods Available Message.
 *
 * @since 4.3.0
 *
 * @return string The no payment methods available message.
 */
function wpcw_get_checkout_no_payment_methods_notice() {
	return apply_filters( 'wpcw_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods to place this order. Please contact us if you require assistance or wish to make alternate arrangements.', 'wp-courseware' ) );
}

/**
 * Are Payment Gateways Available?
 *
 * @since 4.4.0
 *
 * @return bool True if they are available, false otherwise.
 */
function wpcw_are_payment_gateways_available() {
	return wpcw()->gateways->are_gateways_available();
}
