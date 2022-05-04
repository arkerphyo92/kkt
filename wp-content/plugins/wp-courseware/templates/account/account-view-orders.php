<?php
/**
 * Student Account - View Orders.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account-view-orders.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 *
 * Variables available in this template:
 * ---------------------------------------------------
 * @var array $orders The array of orders.
 * @var int $current_page The current page of orders.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

$orders_columns = apply_filters( 'wpcw_student_account_orders_columns', array(
	'order-number'  => esc_html__( 'Order', 'wp-courseware' ),
	'order-date'    => esc_html__( 'Date', 'wp-courseware' ),
	'order-status'  => esc_html__( 'Status', 'wp-courseware' ),
	'order-total'   => esc_html__( 'Total', 'wp-courseware' ),
	'order-actions' => '&nbsp;',
) );

if ( $orders ) : ?>
    <h2><?php echo apply_filters( 'wpcw_student_account_orders_title', esc_html__( 'Orders', 'wp-courseware' ) ); ?></h2>
    <table class="wpcw-table wpcw-table-responsive wpcw-orders-table">
        <thead>
        <tr>
			<?php foreach ( $orders_columns as $column_id => $column_name ) : ?>
                <th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
			<?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
		<?php
		/** @var \WPCW\Models\Order $order */
		foreach ( $orders as $order ) :
			$order_items = $order->get_order_items();
			$order_item_count = count( $order_items );
			?>
            <tr>
				<?php foreach ( $orders_columns as $column_id => $column_name ) : ?>
                    <td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
						<?php if ( has_action( 'wpcw_student_account_order_column_' . $column_id ) ) : ?>
							<?php do_action( 'wpcw_student_account_order_column_' . $column_id, $order ); ?>
						<?php elseif ( 'order-number' === $column_id ) : ?>
                            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
								<?php echo _x( '#', 'hash before order number', 'wp-courseware' ) . $order->get_order_number(); ?>
                            </a>
						<?php elseif ( 'order-date' === $column_id ) : ?>
                            <time datetime="<?php echo esc_attr( wpcw_format_datetime( $order->get_date_created(), 'c' ) ); ?>"><?php echo esc_html( wpcw_format_datetime( $order->get_date_created() ) ); ?></time>
						<?php elseif ( 'order-status' === $column_id ) : ?>
							<?php echo esc_html( wpcw_get_order_status_name( $order->get_status() ) ); ?>
						<?php elseif ( 'order-total' === $column_id ) : ?>
							<?php
							/* translators: 1: formatted order total 2: total order items */
							printf( _n( '%1$s for %2$s item', '%1$s for %2$s items', $order_item_count, 'wp-courseware' ), $order->get_total( true ), $order_item_count );
							?>
						<?php elseif ( 'order-actions' === $column_id ) : ?>
							<?php
							if ( $actions = $order->get_actions() ) {
								foreach ( $actions as $key => $action ) {
									if ( ! isset( $action['url'] ) || ! isset( $action['name'] ) ) {
										continue;
									}

									$action_confirm       = isset( $action['confirm'] ) ? strip_tags( $action['confirm'] ) : '';
									$action_confirm_class = ( $action_confirm ) ? 'wpcw-action-confirm' : '';
									$action_confirm_title = ( $action_confirm ) ? 'title="' . $action_confirm . '"' : '';

									printf( '<a href="%1$s" class="%2$s %3$s" %4$s>%5$s</a>', esc_url( $action['url'] ), sanitize_html_class( $key ), $action_confirm_class, $action_confirm_title, esc_html( $action['name'] ) );
								}
							}
							?>
						<?php endif; ?>
                    </td>
				<?php endforeach; ?>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
	<?php wpcw_print_notice( esc_html__( 'You have no orders to display.', 'wp-courseware' ), 'info' ); ?>
<?php endif; ?>
