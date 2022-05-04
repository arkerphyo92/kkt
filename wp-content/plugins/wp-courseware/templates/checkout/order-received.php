<?php
/**
 * Checkout Thank You / Order Received.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/thankyou.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>

<div class="wpcw-order">
	<?php if ( $order && $order instanceof \WPCW\Models\Order ) : ?>
		<?php if ( $order->has_order_status( 'failed' ) ) : ?>
            <div class="wpcw-notice wpcw-notice-error"><?php _e( 'Unfortunately your order cannot be processed as the originating bank / merchant has declined your transaction. Please attempt your purchase again.', 'wp-courseware' ); ?></div>
			<?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wpcw_get_page_permalink( 'account' ) ); ?>" class="button alt"><?php _e( 'View Account', 'wp-courseware' ); ?>&rarr;</a>
			<?php endif; ?>
		<?php else : ?>
            <div class="wpcw-notice wpcw-notice-success"><?php echo apply_filters( 'wpcw_thankyou_order_received_text', sprintf( __( 'Thank you. Your order has been received. <a href="%s">View Account &rarr;</a>', 'wp-courseware' ), wpcw_get_page_permalink( 'account' ) ), $order ); ?></div>
            <ul class="wpcw-order-received-details">
                <li class="wpcw-order-detail order">
					<?php esc_html_e( 'Order #:', 'wp-courseware' ); ?>
                    <strong><?php echo $order->get_order_number(); ?></strong>
                </li>
                <li class="wpcw-order-detail date">
					<?php esc_html_e( 'Date:', 'wp-courseware' ); ?>
                    <strong><?php echo wpcw_format_datetime( $order->get_date_created() ); ?></strong>
                </li>
				<?php if ( is_user_logged_in() && $order->get_student_id() === get_current_user_id() && $order->get_student_email() ) : ?>
                    <li class="wpcw-order-detail email">
						<?php esc_html_e( 'Email:', 'wp-courseware' ); ?>
                        <strong><?php echo $order->get_student_email(); ?></strong>
                    </li>
				<?php endif; ?>
                <li class="wpcw-order-detail total">
					<?php esc_html_e( 'Total:', 'wp-courseware' ); ?>
                    <strong><?php echo $order->get_total( true ); ?></strong>
                </li>
				<?php if ( $order->get_payment_method_title() ) : ?>
                    <li class="wpcw-order-detail payment-method">
						<?php _e( 'Payment Method:', 'wp-courseware' ); ?>
                        <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
                    </li>
				<?php endif; ?>
            </ul>
		<?php endif; ?>
		<?php do_action( 'wpcw_checkout_order_received_' . $order->get_payment_method(), $order->get_order_id() ); ?>
		<?php do_action( 'wpcw_checkout_order_received', $order->get_order_id() ); ?>
	<?php else : ?>
        <div class="wpcw-notice wpcw-notice-success"><?php echo apply_filters( 'wpcw_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'wp-courseware' ), null ); ?></div>
	<?php endif; ?>
</div>

