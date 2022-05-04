<?php
/**
 * Email - Order Items Table
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/email-order-items-table.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.3.0
 *
 * Variables available in this template:
 * -----------------------------------------------
 * @var \WPCW\Models\Order $order The order object.
 * @var bool               $admin_email Is this an admin email?
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
 * @param bool               $admin_email Is this an admin email?
 * @param \WPCW\Emails\Email $emal The email object.
 */
do_action( 'wpcw_email_before_order_items_table', $order, $admin_email, $email ); ?>

<h2 class="no-bold">
	<?php
	if ( $admin_email ) {
		$before = '<a class="link" href="' . esc_url( $order->get_order_edit_url() ) . '">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}
	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . sprintf( __( 'Order #%1$s', 'wp-courseware' ) . $after . ' - <time datetime="%2$s">%3$s</time>', $order->get_order_number(), wpcw_format_datetime( $order->get_date_created(), 'c' ), wpcw_format_datetime( $order->get_date_created() ) ) );
	?>
</h2>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
		<tr>
			<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
			<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Price', 'wp-courseware' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		/** @var \WPCW\Models\Order_Item $order_item */
		foreach ( $order->get_order_items() as $order_item ) {
			if ( apply_filters( 'wpcw_order_item_visible', true, $order_item ) ) {
				$order_item_course = $order_item->get_course();
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'wpcw_order_item_class', 'order_item', $order_item, $order ) ); ?>">
					<td class="td"
					    style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
						<?php echo apply_filters( 'wpcw_order_item_title', $order_item_course->get_course_title(), $order_item ); ?>
					</td>
					<td class="td" style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
						<?php echo apply_filters( 'wpcw_order_item_price', wpcw_price( $order_item_course->get_payments_price() ), $order_item ); ?>
					</td>
				</tr>
				<?php
			}
		}
		?>
		</tbody>
		<tfoot>
		<tr>
			<th class="td td-total" scope="row" style="text-align:right;">
				<?php echo esc_html__( 'Subtotal:', 'wp-courseware' ); ?>
			</th>
			<td class="td td-total" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php echo wp_kses_post( $order->get_subtotal( true ) ); ?>
			</td>
		</tr>
		<?php if ( wpcw_coupons_enabled() ) { ?>
			<tr>
				<th class="td" scope="row" style="text-align:right;">
					<?php echo esc_html__( 'Discount:', 'wp-courseware' ); ?>
				</th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
					<?php echo wp_kses_post( $order->get_discounts( true ) ); ?>
				</td>
			</tr>
		<?php } ?>
		<?php if ( wpcw_taxes_enabled() ) { ?>
			<tr>
				<th class="td" scope="row" style="text-align:right;">
					<?php echo esc_html__( 'Tax:', 'wp-courseware' ); ?>
				</th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
					<?php echo wp_kses_post( $order->get_tax( true ) ); ?>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<th class="td" scope="row" style="text-align:right;">
				<?php echo esc_html__( 'Total:', 'wp-courseware' ); ?>
			</th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;">
				<?php echo wp_kses_post( $order->get_total( true ) ); ?>
			</td>
		</tr>
		</tfoot>
	</table>
</div>
<?php
/**
 * Action: After Order Items Table.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Order $order The order object.
 * @param bool               $admin_email Is this an admin email?
 * @param \WPCW\Emails\Email $emal The email object.
 */
do_action( 'wpcw_email_after_order_items_table', $order, $admin_email, $email ); ?>
