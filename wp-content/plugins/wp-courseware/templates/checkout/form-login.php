<?php
/**
 * Checkout Form - Login.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/form-login.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Is user logged in?
if ( is_user_logged_in() ) {
	return;
}

// Is cart empty?
if ( wpcw()->cart->is_empty() ) {
	return;
}
?>
<div class="wpcw-checkout-login-form">
	<?php
	$login_form_notice = apply_filters( 'wpcw_checkout_login_form_notice', sprintf(
		'%s <a href="#" class="wpcw-show-login">%s</a>',
		esc_html__( 'Returning Student?', 'wp-courseware' ),
		esc_html__( 'Click here to login', 'wp-courseware' )
	) );

	wpcw_print_notice( $login_form_notice, 'info' );

	wpcw_login_form( apply_filters( 'wpcw_checkout_login_form', array(
		'message'  => esc_html__( 'If you have bought with us before, please enter your login details in the fields below. If you are a new student, please proceed to the account section below.', 'wp-courseware' ),
		'redirect' => wpcw_get_page_permalink( 'checkout' ),
		'hidden'   => true,
	) ) );
	?>
</div>
