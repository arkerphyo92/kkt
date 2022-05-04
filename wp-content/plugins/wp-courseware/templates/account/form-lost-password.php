<?php
/**
 * Account Form - Lost Password.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/form-lost-password.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Print Notices
wpcw_print_notices(); ?>
<form class="wpcw-form wpcw-form-lost-password" method="post">
	<?php do_action( 'wpcw_lost_password_form_start' ); ?>

    <p><?php echo apply_filters( 'wpcw_lost_password_message', esc_html__( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'wp-courseware' ) ); ?></p>

    <p class="wpcw-form-row wpcw-form-row">
        <label for="user_login"><?php esc_html_e( 'Username or email', 'wp-courseware' ); ?> <span class="required">*</span></label>
        <input type="text" class="wpcw-input-text" placeholder="<?php esc_html_e( 'Username or email', 'wp-courseware' ); ?>" name="user_login" id="user_login"/>
    </p>

    <div class="wpcw-clear"></div>

	<?php do_action( 'wpcw_lost_password_form' ); ?>

    <p class="wpcw-form-row">
		<?php wp_nonce_field( 'wpcw-lost-password', 'wpcw-lost-password-nonce' ); ?>
        <input type="hidden" name="wpcw_lost_password" value="true"/>
        <button type="submit" class="button" value="<?php esc_attr_e( 'Reset password', 'wp-courseware' ); ?>"><?php esc_html_e( 'Reset password', 'wp-courseware' ); ?></button>
    </p>

    <p class="wpcw-form-row">
		<?php if ( get_option( 'users_can_register' ) ) { ?>
            <a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Register', 'wp-courseware' ); ?></a> <span class="sep">|</span>
		<?php } ?>

        <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Login', 'wp-courseware' ); ?></a>
    </p>

    <div class="wpcw-clear"></div>

	<?php do_action( 'wpcw_lost_password_form_end' ); ?>
</form>

