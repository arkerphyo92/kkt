<?php
/**
 * Login form
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/common/form-login.php.
 *
 * @package WPCW
 * @subpackage Templates\Common
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Is user logged in?
if ( is_user_logged_in() ) {
	return;
}
?>
<form class="wpcw-form wpcw-form-login" method="post" <?php echo ( $hidden ) ? 'style="display:none;"' : ''; ?>>
	<?php do_action( 'wpcw_login_form_start' ); ?>

	<?php echo ( $message ) ? wpautop( wptexturize( $message ) ) : ''; ?>

    <p class="wpcw-form-row wpcw-form-row-first">
        <label for="login_username"><?php esc_html_e( 'Username or Email', 'wp-courseware' ); ?> <span class="required">*</span></label>
        <input type="text" class="wpcw-input-text" placeholder="<?php esc_html_e( 'Username or Email', 'wp-courseware' ); ?>" name="login_username" id="login_username"/>
    </p>

    <p class="wpcw-form-row wpcw-form-row-last">
        <label for="login_password"><?php esc_html_e( 'Password', 'wp-courseware' ); ?> <span class="required">*</span></label>
        <input class="wpcw-input-text" placeholder="<?php esc_html_e( 'Password', 'wp-courseware' ); ?>" type="password" name="login_password" id="login_password"/>
    </p>

    <div class="wpcw-clear"></div>

	<?php do_action( 'wpcw_login_form' ); ?>

    <p class="wpcw-form-row">
		<?php wp_nonce_field( 'wpcw-login', 'wpcw-login-nonce' ); ?>
        <button type="submit" class="button" name="login" value="<?php esc_attr_e( 'Login', 'wp-courseware' ); ?>"><?php esc_html_e( 'Login', 'wp-courseware' ); ?></button>

		<?php if ( $redirect ) { ?>
            <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ) ?>"/>
		<?php } ?>

        <label class="wpcw-form-label wpcw-form-label-for-checkbox inline" for="login_rememberme">
            <input class="wpcw-form-input wpcw-form-input-checkbox" name="login_rememberme" type="checkbox" id="login_rememberme" value="forever"/>
            <span><?php esc_html_e( 'Remember me', 'wp-courseware' ); ?></span>
        </label>
    </p>

    <p class="wpcw-form-row wpcw-lost-password">
	    <?php if ( get_option( 'users_can_register' ) ) { ?>
            <a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Register', 'wp-courseware' ); ?></a> <span class="sep">|</span>
	    <?php } ?>

        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'wp-courseware' ); ?></a>
    </p>

    <div class="wpcw-clear"></div>

	<?php do_action( 'wpcw_login_form_end' ); ?>
</form>
