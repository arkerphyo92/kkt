<?php
/**
 * Student Account - View Subscription.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account-view-subscription.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 *
 * Variables available in this template:
 * ---------------------------------------------------
 * @var \WPCW\Models\Subscription $subscription The subscription object.
 * @var int $subscription_id The subscription id.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit; ?>

<div class="wpcw-subscription-details">
	<?php
	/* translators: 1: subscription number 2: subscription date 3: subscription status */
	wpcw_print_notice( sprintf(
		__( 'Subscription #%1$s was created on %2$s and is currently %3$s.', 'wp-courseware' ),
		'<mark class="subscription-number">' . $subscription->get_id() . '</mark>',
		'<mark class="subscription-date">' . wpcw_format_datetime( $subscription->get_created() ) . '</mark>',
		'<mark class="subscription-status">' . wpcw_get_subscription_status_name( $subscription->get_status() ) . '</mark>'
	), 'info' );
	?>

	<?php do_action( 'wpcw_before_subscription_details_table', $subscription ); ?>

    <h2><?php esc_html_e( 'Subscription Details', 'wp-courseware' ); ?></h2>

    <table class="wpcw-table wpcw-table-responsive wpcw-table-subscription-details">
        <tr>
            <td><?php esc_html_e( 'Status', 'wp-courseware' ); ?></td>
            <td><?php echo esc_html( wpcw_get_subscription_status_name( $subscription->get_status() ) ); ?></td>
        </tr>
        <tr>
            <td><?php echo esc_html_x( 'Start Date', 'table heading', 'wp-courseware' ); ?></td>
            <td><?php echo esc_html( wpcw_format_datetime( $subscription->get_created() ) ); ?></td>
        </tr>
        <tr>
            <td><?php echo esc_html_x( 'Renewal Date', 'table heading', 'wp-courseware' ); ?></td>
            <td><?php echo esc_html( wpcw_format_datetime( $subscription->get_renewal() ) ); ?></td>
        </tr>

		<?php do_action( 'wpcw_before_subscription_actions', $subscription ); ?>

		<?php if ( $actions = $subscription->get_actions() ) : ?>
            <tr>
                <td><?php esc_html_e( 'Actions', 'wp-courseware' ); ?></td>
                <td>
					<?php foreach ( $actions as $key => $action ) :
						if ( ! isset( $action['url'] ) || ! isset( $action['name'] ) ) {
							continue;
						}

						$action_confirm = isset( $action['confirm'] ) ? strip_tags( $action['confirm'] ) : '';
						?>
                        <a href="<?php echo esc_url( $action['url'] ); ?>"
                           class="button subscription-action <?php echo sanitize_html_class( $key ) ?> <?php echo ( $action_confirm ) ? ' wpcw-action-confirm' : ''; ?>"<?php echo ( $action_confirm ) ? ' title="' . $action_confirm . '"' : ''; ?>>
							<?php echo esc_html( $action['name'] ); ?>
                        </a>
					<?php endforeach; ?>
                </td>
            </tr>
		<?php endif; ?>

		<?php do_action( 'wpcw_after_subscription_actions', $subscription ); ?>
    </table>

	<?php if ( $parent_order = $subscription->get_order() ) { ?>
        <h2><?php esc_html_e( 'Parent Order', 'wp-courseware' ); ?></h2>
        <table class="wpcw-table wpcw-table-responsive wpcw-table-subscription-payments">
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
            <tr>
                <td class="number"><a href="<?php echo $parent_order->get_view_order_url(); ?>"><?php printf( '#%s', $parent_order->get_order_number() ); ?></a></td>
                <td class="type"><?php echo wpcw_get_order_type_name( $parent_order->get_order_type() ); ?></td>
                <td class="date"><abbr title="<?php echo $parent_order->get_date_created( true ); ?>"><?php echo $parent_order->get_date_created( true ); ?></abbr></td>
                <td class="status"><?php echo wpcw_get_order_status_name( $parent_order->get_order_status() ); ?></td>
                <td class="total"><?php echo $parent_order->get_total( true ); ?></td>
            </tr>
            </tbody>
        </table>
	<?php } ?>

	<?php if ( $payments = $subscription->get_payments( array( 'order' => 'DESC' ) ) ) { ?>
        <h2><?php esc_html_e( 'Related Payments', 'wp-courseware' ); ?></h2>
        <table class="wpcw-table wpcw-table-responsive wpcw-table-subscription-payments">
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
			<?php /** @var \WPCW\Models\Order $payment */ ?>
			<?php foreach ( $payments as $payment ) { ?>
                <tr>
                    <td class="number"><a href="<?php echo $payment->get_view_order_url(); ?>"><?php printf( '#%s', $payment->get_order_number() ); ?></a></td>
                    <td class="type"><?php echo wpcw_get_order_type_name( $payment->get_order_type() ); ?></td>
                    <td class="date"><abbr title="<?php echo $payment->get_date_created( true ); ?>"><?php echo $payment->get_date_created( true ); ?></abbr></td>
                    <td class="status"><?php echo wpcw_get_order_status_name( $payment->get_order_status() ); ?></td>
                    <td class="total"><?php echo $payment->get_total( true ); ?></td>
                </tr>
			<?php } ?>
            </tbody>
        </table>
	<?php } ?>

	<?php do_action( 'wpcw_after_subscription_details_table', $subscription ); ?>
</div>

