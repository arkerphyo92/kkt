<?php
/**
 * Plain Text Email - Admin - Suspended Subscription
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/plain/admin-suspended-subscription.php
 *
 * @package WPCW
 * @subpackage Templates\Emails\Plain
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

echo "= " . wp_strip_all_tags( $heading ) . " =\r\n";

echo "\r\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\r\n";

echo wp_strip_all_tags( $content ) . "\r\n";

echo "\r\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\r\n";

/**
 * Action: Email Footer Plain Text
 *
 * @since 4.3.0
 *
 * @hooked \WPCW\Controllers\Emails->email_footer_text_plain() Output the email footer
 *
 * @param \WPCW\Emails\Email The email object.
 */
do_action( 'wpcw_email_footer_text_plain', $email );