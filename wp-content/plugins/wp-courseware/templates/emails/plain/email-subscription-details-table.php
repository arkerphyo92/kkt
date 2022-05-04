<?php
/**
 * Email - Subscription Details Table - Plain
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/plain/email-subscription-details-table.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails\Plain
 * @version 4.3.0
 *
 * Variables available in this template:
 * -----------------------------------------------
 * @var \WPCW\Models\Subscription $subscription The subscription object.
 * @var bool $admin_email Is this an admin email?
 * @var \WPCW\Emails\Email $email The email object.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Text Alignment.
$text_align = is_rtl() ? 'right' : 'left';

/**
 * Action: Before Subscription Details Table.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Subscription $subscription The subscription object.
 * @param bool $admin_email Is this an admin email?
 * @param \WPCW\Emails\Email $emal The email object.
 */
do_action( 'wpcw_email_before_subscription_details_table', $subscription, $admin_email, $email );

echo "\n==========\n";
/* translators: %s: Order ID. */
echo wp_kses_post( sprintf( __( 'Subscription ID: %s', 'wp-courseware' ), $subscription->get_id() ) ) . "\n";
echo sprintf( __( 'Created: %s', 'wp-courseware' ), wpcw_format_datetime( $subscription->get_created() ) ) . "\n";
echo sprintf( __( 'Course: %s', 'wp-courseware' ), $subscription->get_course_title() ) . "\n";
echo sprintf( __( 'Amount: %s', 'wp-courseware' ), $subscription->get_recurring_amount( true ) ) . "\n";
echo sprintf( __( 'Status: %s', 'wp-courseware' ), wpcw_get_subscription_status_name( $subscription->get_status() ) ) . "\n";
echo "==========\n";

if ( $admin_email ) {
	/* translators: %s: Order link. */
	echo "\n" . sprintf( esc_html__( 'View subscription: %s', 'wp-courseware' ), esc_url( $subscription->get_edit_url() ) ) . "\n";
}

/**
 * Action: After Subscription Details Table.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Subscription $subscription The subscription object.
 * @param bool $admin_email Is this an admin email?
 * @param \WPCW\Emails\Email $emal The email object.
 */
do_action( 'wpcw_email_after_subscriptions_details_table', $subscription, $admin_email, $email ); ?>
