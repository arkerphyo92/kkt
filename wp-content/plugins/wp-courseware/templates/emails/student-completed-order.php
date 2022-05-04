<?php
/**
 * Email - Student - Completed Order
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/student-completed-order.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Action: Email Header
 *
 * @since 4.3.0
 *
 * @hooked \WPCW\Controllers\Emails->email_header() Output the email header
 *
 * @param string $heading The email heading.
 * @param \WPCW\Emails\Email The email object.
 */
do_action( 'wpcw_email_header', $heading, $email );

/**
 * Email Content
 *
 * @since 4.3.0
 */
echo $content;

/**
 * Action: Email Footer
 *
 * @since 4.3.0
 *
 * @hooked \WPCW\Controllers\Emails->email_footer() Output the email footer
 *
 * @param \WPCW\Emails\Email The email object.
 */
do_action( 'wpcw_email_footer', $email );
