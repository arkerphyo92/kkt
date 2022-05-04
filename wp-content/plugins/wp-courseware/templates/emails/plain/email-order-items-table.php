<?php
/**
 * Email - Order Items Table - Plain
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/plain/email-order-items-table.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails\Plain
 * @version 4.3.0
 *
 * Variables available in this template:
 * -----------------------------------------------
 * @var \WPCW\Models\Order $order The order object.
 * @var bool $admin_email Is this an admin email?
 * @var \WPCW\Emails\Email $email The email object.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Text Alignment.
$text_align = is_rtl() ? 'right' : 'left';

/**
 * Action: Before Order Items Table.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Order $order The order object.
 * @param bool $admin_email Is this an admin email?
 * @param \WPCW\Emails\Email $emal The email object.
 */
do_action( 'wpcw_email_before_order_items_table', $order, $admin_email, $email );

echo "==========\n\n";
/* translators: %s: Order ID. */
echo wp_kses_post( strtoupper( sprintf( __( 'Order number: %s', 'wp-courseware' ), $order->get_order_number() ) ) ) . "\n";
echo wpcw_format_datetime( $order->get_date_created() ) . "\n";
echo "==========\n\n";

/** @var \WPCW\Models\Order_Item $order_item */
foreach ( $order->get_order_items() as $order_item ) {
	$order_item_course = $order_item->get_course();
	if ( apply_filters( 'wpcw_order_item_visible', true, $order_item ) ) {
		echo apply_filters( 'wpcw_order_item_title', $order_item_course->get_course_title(), $order_item );
		echo ' ' . apply_filters( 'wpcw_order_item_price', wpcw_price( $order_item_course->get_payments_price() ), $order_item ) . "\n";
	}
	echo "\n\n";
}

echo "==========\n\n";
echo wp_kses_post( esc_html__( 'Subtotal', 'wp-courseware' ) . "\t " . $order->get_subtotal( true ) ) . "\n";
if ( wpcw_taxes_enabled() ) {
	echo wp_kses_post( esc_html__( 'Taxes', 'wp-courseware' ) . "\t " . $order->get_tax( true ) ) . "\n";
}
echo wp_kses_post( esc_html__( 'Total', 'wp-courseware' ) . "\t " . $order->get_total( true ) ) . "\n";
echo "==========\n\n";

if ( $admin_email ) {
	/* translators: %s: Order link. */
	echo "\n" . sprintf( esc_html__( 'View order: %s', 'wp-courseware' ), esc_url( $order->get_order_edit_url() ) ) . "\n";
}

/**
 * Action: After Order Items Table.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Order $order The order object.
 * @param bool $admin_email Is this an admin email?
 * @param \WPCW\Emails\Email $emal The email object.
 */
do_action( 'wpcw_email_after_order_items_table', $order, $admin_email, $email ); ?>
