<?php
/**
 * Account Form - Reset Password.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/form-reset-password.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Print Notices
wpcw_print_notices(); ?>
<form class="wpcw-form wpcw-form-reset-password" method="post">
	<?php do_action( 'wpcw_reset_password_form_start' ); ?>

    <p><?php echo apply_filters( 'wpcw_reset_password_message', esc_html__( 'Enter a new password below.', 'wp-courseware' ) ); ?></p>

    <p class="wpcw-form-row wpcw-form-row">
        <label for="password_1"><?php esc_html_e( 'New password', 'wp-courseware' ); ?> <span class="required">*</span></label>
        <input type="password" class="wpcw-input-text" placeholder="<?php esc_html_e( 'New password', 'wp-courseware' ); ?>" name="password_1" id="password_1"/>
    </p>

    <p class="wpcw-form-row wpcw-form-row">
        <label for="password_2"><?php esc_html_e( 'Re-enter new password', 'wp-courseware' ); ?> <span class="required">*</span></label>
        <input type="password" class="wpcw-input-text" placeholder="<?php esc_html_e( 'Re-enter new password', 'wp-courseware' ); ?>" name="password_2" id="password_2"/>
    </p>

    <input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>"/>
    <input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>"/>

    <div class="wpcw-clear"></div>

	<?php do_action( 'wpcw_reset_password_form' ); ?>

    <p class="wpcw-form-row">
		<?php wp_nonce_field( 'wpcw-reset-password', 'wpcw-reset-password-nonce' ); ?>
        <input type="hidden" name="wpcw_reset_password" value="true"/>
        <button type="submit" class="button" value="<?php esc_attr_e( 'Save', 'wp-courseware' ); ?>"><?php esc_html_e( 'Save', 'wp-courseware' ); ?></button>
    </p>

    <div class="wpcw-clear"></div>

	<?php do_action( 'wpcw_reset_password_form_end' ); ?>
</form>

