<?php
/**
 * Email - Student - New Account Details.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/student-new-account-details.php.
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

?>

<p>
	<?php
	printf(
		__( 'Email: %1$s', 'wp-courseware' ),
		'<strong>' . esc_html( $student_email ) . '</strong>'
	);
	?>
    <br/>
	<?php
	printf(
		__( 'Username: %1$s', 'wp-courseware' ),
		'<strong>' . esc_html( $student_login ) . '</strong>'
	);
	?>
    <br/>
	<?php
	printf(
		__( 'Password: %1$s <a href="%2$s">Reset password?</a>', 'wp-courseware' ),
		wpcw_get_fo_password(),
		wp_lostpassword_url()
	);
	?>
</p>

<p><?php printf( __( 'You can access your student account area to view your orders and update your account details: %s.', 'wp-courseware' ), make_clickable( esc_url( wpcw_get_page_permalink( 'account' ) ) ) ); ?></p>
