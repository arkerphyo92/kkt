<?php
/**
 * Student Account - View Order.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account-view-order.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 *
 * Variables available in this template:
 * ---------------------------------------------------
 * @var \WPCW\Models\Order $order The order object.
 * @var int                $order_id The order id.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit; ?>
<div class="wpcw-order-details">
	<?php
	/* translators: 1: order number 2: order date 3: order status */
	wpcw_print_notice( sprintf(
		__( 'Order #%1$s was placed on %2$s and is currently %3$s.', 'wp-courseware' ),
		'<mark class="order-number">' . $order->get_order_number() . '</mark>',
		'<mark class="order-date">' . wpcw_format_datetime( $order->get_date_created() ) . '</mark>',
		'<mark class="order-status">' . wpcw_get_order_status_name( $order->get_status() ) . '</mark>'
	), 'info' );
	?>

	<?php do_action( 'wpcw_before_order_details_table', $order ); ?>

	<h2><?php esc_html_e( 'Order Details', 'wp-courseware' ); ?></h2>

	<table class="wpcw-table wpcw-table-responsive wpcw-order-details-table">
		<thead>
		<tr>
			<th class="order-item-title"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
			<th class="order-item-total"><?php esc_html_e( 'Price', 'wp-courseware' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		/** @var \WPCW\Models\Order_Item $order_item */
		foreach ( $order->get_order_items() as $order_item ) {
			if ( apply_filters( 'wpcw_order_item_visible', true, $order_item ) ) {
				do_action( 'wpcw_order_details_before_order_item', $order_item, $order );
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'wpcw_order_item_class', 'order_item', $order_item, $order ) ); ?>">
					<td class="order-item-title">
						<?php echo apply_filters( 'wpcw_order_item_title', $order_item->get_order_item_title(), $order_item ); ?>
					</td>
					<td class="order-item-price">
						<?php echo apply_filters( 'wpcw_order_item_price', wpcw_price( $order_item->get_amount() ), $order_item ); ?>
					</td>
				</tr>
				<?php
				do_action( 'wpcw_order_details_after_order_item', $order_item, $order );
			}
		}
		?>
		</tbody>
		<tfoot>
		<tr>
			<th class="order-subtotal-name">
				<?php echo esc_html__( 'Subtotal:', 'wp-courseware' ); ?>
			</th>
			<td class="order-subtotal-value">
				<?php echo wp_kses_post( $order->get_subtotal( true ) ); ?>
			</td>
		</tr>
		<?php if ( wpcw_coupons_enabled() ) { ?>
			<tr>
				<th class="order-discounts-name">
					<?php echo esc_html__( 'Discount:', 'wp-courseware' ); ?>
				</th>
				<td class="order-discounts-value">
					<?php echo wp_kses_post( $order->get_discounts( true ) ); ?>
				</td>
			</tr>
		<?php } ?>
		<?php if ( wpcw_taxes_enabled() ) { ?>
			<tr>
				<th class="order-taxes-name">
					<?php echo esc_html__( 'Tax:', 'wp-courseware' ); ?>
				</th>
				<td class="order-taxes-value">
					<?php echo wp_kses_post( $order->get_tax( true ) ); ?>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<th class="order-total-name">
				<?php echo esc_html__( 'Total:', 'wp-courseware' ); ?>
			</th>
			<td class="order-total-value">
				<?php echo wp_kses_post( $order->get_total( true ) ); ?>
			</td>
		</tr>
		</tfoot>
	</table>

	<?php if ( $subscriptions = $order->get_subscriptions( array( 'order' => 'DESC' ) ) ) { ?>
		<h2><?php esc_html_e( 'Related Subscriptions', 'wp-courseware' ); ?></h2>

		<table class="wpcw-table wpcw-table-responsive wpcw-order-subscriptions-table">
			<thead>
			<tr>
				<th class="number"><?php esc_html_e( 'ID', 'wp-courseware' ); ?></th>
				<th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
				<th class="renewal"><?php esc_html_e( 'Renews', 'wp-courseware' ); ?></th>
				<th class="amount"><?php esc_html_e( 'Amount', 'wp-courseware' ); ?></th>
				<th class="actions">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php /** @var \WPCW\Models\Subscription $subscription */ ?>
			<?php foreach ( $subscriptions as $subscription ) { ?>
				<tr>
					<td class="number"><a href="<?php echo $subscription->get_view_url(); ?>"><?php printf( '#%s', $subscription->get_id() ); ?></a></td>
					<td class="status"><?php echo wpcw_get_subscription_status_name( $subscription->get_status() ); ?></td>
					<td class="renewal"><abbr title="<?php echo $subscription->get_renewal( true ); ?>"><?php echo $subscription->get_renewal( true ); ?></abbr></td>
					<td class="amount"><?php echo $subscription->is_installment_plan() ? $subscription->get_installment_plan_label() : $subscription->get_recurring_amount( true ); ?></td>
					<td class="actions"><a href="<?php echo esc_url( $subscription->get_view_url() ); ?>"><?php esc_html_e( 'View', 'wp-courseware' ); ?></a></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	<?php } ?>

	<?php if ( $related_payments = $order->get_related_orders( array( 'order' => 'DESC' ) ) ) { ?>
		<h2><?php esc_html_e( 'Related Payments', 'wp-courseware' ); ?></h2>

		<table class="wpcw-table wpcw-table-responsive wpcw-order-related-payments-table">
			<thead>
			<tr>
				<th class="number"><?php esc_html_e( 'Number', 'wp-courseware' ); ?></th>
				<th class="type"><?php esc_html_e( 'Type', 'wp-courseware' ); ?></th>
				<th class="date"><?php esc_html_e( 'Date', 'wp-courseware' ); ?></th>
				<th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
				<th class="total"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php /** @var \WPCW\Models\Order $related_payment */ ?>
			<?php foreach ( $related_payments as $related_payment ) { ?>
				<tr>
					<td class="number"><a href="<?php echo $related_payment->get_view_order_url(); ?>"><?php printf( '#%s', $related_payment->get_order_number() ); ?></a></td>
					<td class="type"><?php echo wpcw_get_order_type_name( $related_payment->get_order_type() ); ?></td>
					<td class="date">
						<abbr title="<?php echo $related_payment->get_date_created( true ); ?>">
							<?php echo $related_payment->get_date_created( true ); ?>
						</abbr>
					</td>
					<td class="status"><?php echo wpcw_get_order_status_name( $related_payment->get_order_status() ); ?></td>
					<td class="total"><?php echo $related_payment->get_total( true ); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	<?php } ?>

	<?php do_action( 'wpcw_after_order_details_table', $order ); ?>
</div>

