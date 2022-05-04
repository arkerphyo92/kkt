<?php
/**
 * Account Form - Register.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/form-register.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Check user login
if ( is_user_logged_in() ) {
	return;
}

// Print notices
wpcw_print_notices();

// Generate Password
$generate_username = apply_filters( 'wpcw_registration_generate_username', false );
$generate_password = apply_filters( 'wpcw_registration_generate_password', false );

/**
 * Action: Before Account Rregister Form.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_before_account_register_form' );
?>
    <div class="wpcw-account-register-form">
        <h2><?php esc_html_e( 'Register', 'wp-courseware' ); ?></h2>

        <form class="wpcw-form wpcw-form-register" method="post">
			<?php do_action( 'wpcw_register_form_start' ); ?>

            <p class="wpcw-form-row wpcw-form-row">
                <label for="reg_username"><?php esc_html_e( 'Email', 'wp-courseware' ); ?> <span class="required">*</span></label>
                <input type="text"
                       class="wpcw-input-text"
                       placeholder="<?php esc_html_e( 'Email', 'wp-courseware' ); ?>"
                       name="reg_email"
                       id="reg_email"
                       value="<?php echo ( ! empty( $_POST['reg_email'] ) ) ? esc_attr( wp_unslash( $_POST['reg_email'] ) ) : ''; ?>"/>
            </p>

            <p class="wpcw-form-row wpcw-form-row">
                <label for="reg_username"><?php esc_html_e( 'Username', 'wp-courseware' ); ?> <?php echo ( ! $generate_username ) ? '<span class="required">*</span>' : ''; ?></label>
                <input type="text"
                       class="wpcw-input-text"
                       placeholder="<?php esc_html_e( 'Username', 'wp-courseware' ); ?>"
                       name="reg_username"
                       id="reg_username"
                       value="<?php echo ( ! empty( $_POST['reg_username'] ) ) ? esc_attr( wp_unslash( $_POST['reg_username'] ) ) : ''; ?>"/>
	            <?php if ( $generate_username ) { ?>
		            <span class="wpcw-input-desc"><small><?php esc_html_e( 'If left blank, a username will be generated for you.', 'wp-courseware' ); ?></small></span>
	            <?php } ?>
            </p>

            <p class="wpcw-form-row wpcw-form-row">
                <label for="reg_password"><?php esc_html_e( 'Password', 'wp-courseware' ); ?> <?php echo ( ! $generate_password ) ? '<span class="required">*</span>' : ''; ?></label>
                <input class="wpcw-input-text" placeholder="<?php esc_html_e( 'Password', 'wp-courseware' ); ?>" type="password" name="reg_password" id="reg_password"/>
	            <?php if ( $generate_password ) { ?>
		            <span class="wpcw-input-desc"><small><?php esc_html_e( 'If left blank, a secure password will be generated for you.', 'wp-courseware' ); ?></small></span>
				<?php } ?>
            </p>

            <div class="wpcw-clear"></div>

			<?php do_action( 'wpcw_register_form' ); ?>

            <p class="wpcw-form-row">
				<?php wp_nonce_field( 'wpcw-register', 'wpcw-register-nonce' ); ?>

                <button type="submit" class="button" name="register" value="<?php esc_attr_e( 'Register', 'wp-courseware' ); ?>"><?php esc_html_e( 'Register', 'wp-courseware' ); ?></button>
            </p>

            <p class="wpcw-form-row wpcw-lost-password">
                <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Login', 'wp-courseware' ); ?></a>
                <span class="sep">|</span>
                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'wp-courseware' ); ?></a>
            </p>

            <div class="wpcw-clear"></div>

			<?php do_action( 'wpcw_register_form_end' ); ?>
        </form>
    </div>
<?php
/**
 * Action: After Account Register Form.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_after_account_register_form' );
