<?php
/**
 * Account Form - Lost Password Confirmation.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/lost-passworod-confirmation.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Print Notices.
wpcw_print_notices();

// Password Reset Email.
wpcw_print_notice( __( 'Password reset email has been sent.', 'wp-courseware' ) );
?>

<p><?php echo apply_filters( 'wpcw_lost_password_message', __( 'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.', 'wp-courseware' ) ); ?></p>