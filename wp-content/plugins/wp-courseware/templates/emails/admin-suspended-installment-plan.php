<?php
/**
 * Email - Admin - Suspended Installment Plan
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/admin-suspended-installment-plan.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.6.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Action: Email Header
 *
 * @since 4.6.0
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
 * @since 4.6.0
 */
echo $content;

/**
 * Action: Email Footer
 *
 * @since 4.6.0
 *
 * @hooked \WPCW\Controllers\Emails->email_footer() Output the email footer
 *
 * @param \WPCW\Emails\Email The email object.
 */
do_action( 'wpcw_email_footer', $email );
