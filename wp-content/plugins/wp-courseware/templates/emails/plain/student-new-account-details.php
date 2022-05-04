<?php
/**
 * Email - Plain Text - Student - New Account Details.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/plain/student-new-account-details.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.3.0
 *
 * Variables available in this template:
 * ---------------------------------------------------------
 * @var string $student_login The student login username.
 * @var string $student_pass The student login password.
 * @var string $student_email The student email.
 * @var string $site_title The site title.
 * @var string $password_generated Was the password generated?
 * @var \WPCW\Emails\Email $email The email object.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

echo sprintf( __( 'Thanks for creating an account on %1$s. Your username is %2$s', 'wp-courseware' ), $site_title, '<strong>' . $student_login . '</strong>' ) . "\n\n";

if ( $password_generated ) {
	echo sprintf( __( 'Your password is %s.', 'wp-courseware' ), '<strong>' . $student_pass . '</strong>' ) . "\n\n";
}

echo sprintf( __( 'You can access your account area to view your orders and change your password here: %s.', 'wp-courseware' ), wpcw_get_page_permalink( 'account' ) ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";