<?php
/**
 * Account Form - Login.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/form-login.php.
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

/**
 * Action: Before Account Login Form.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_before_account_login_form' );
?>
<div class="wpcw-account-login-form">
    <h2><?php esc_html_e( 'Login', 'wp-courseware' ); ?></h2>
    <?php
    wpcw_login_form( apply_filters( 'wpcw_account_login_form', array(
        'redirect' => wpcw_get_page_permalink( 'account' ),
        'hidden'   => false,
    ) ) );
    ?>
</div>
<?php
/**
 * Action: After Account Login Form.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_after_account_login_form' );