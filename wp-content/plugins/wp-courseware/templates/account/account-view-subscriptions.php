<?php
/**
 * Student Account - View Subscriptions.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account-view-subscriptions.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 *
 * Variables available in this template:
 * ---------------------------------------------------
 * @var array $subscriptions The array of subscription objects.
 * @var int   $current_page The current page of the student subscriptions.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

$subscriptions_columns = apply_filters( 'wpcw_student_account_subscriptions_columns', array(
	'subscription-id'      => esc_html__( 'ID', 'wp-courseware' ),
	'subscription-status'  => esc_html__( 'Status', 'wp-courseware' ),
	'subscription-renews'  => esc_html__( 'Renews', 'wp-courseware' ),
	'subscription-amount'  => esc_html__( 'Amount', 'wp-courseware' ),
	'subscription-actions' => '&nbsp;',
) );

if ( $subscriptions ) : ?>
	<h2><?php echo apply_filters( 'wpcw_student_account_subscriptions_title', esc_html__( 'Subscriptions', 'wp-courseware' ) ); ?></h2>

	<table class="wpcw-table wpcw-table-responsive wpcw-table-subscriptions">
		<thead>
		<tr>
			<?php foreach ( $subscriptions_columns as $column_id => $column_name ) : ?>
				<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
			<?php endforeach; ?>
		</tr>
		</thead>
		<tbody>
		<?php
		/** @var \WPCW\Models\Subscription $subscription */
		foreach ( $subscriptions as $subscription ) :
			?>
			<tr class="subscription">
				<?php foreach ( $subscriptions_columns as $column_id => $column_name ) : ?>
					<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
						<?php if ( has_action( 'wpcw_student_account_subscription_column_' . $column_id ) ) : ?>
							<?php do_action( 'wpcw_student_account_subscription_column_' . $column_id, $subscription ); ?>
						<?php elseif ( 'subscription-id' === $column_id ) : ?>
							<a href="<?php echo esc_url( $subscription->get_view_url() ); ?>">
								<?php echo _x( '#', 'hash before subscription id', 'wp-courseware' ) . $subscription->get_id(); ?>
							</a>
						<?php elseif ( 'subscription-renews' === $column_id ) : ?>
							<time datetime="<?php echo esc_attr( wpcw_format_datetime( $subscription->get_renewal(), 'c' ) ); ?>"><?php echo esc_html( wpcw_format_datetime( $subscription->get_renewal() ) ); ?></time>
						<?php elseif ( 'subscription-status' === $column_id ) : ?>
							<?php echo esc_html( wpcw_get_subscription_status_name( $subscription->get_status() ) ); ?>
						<?php elseif ( 'subscription-amount' === $column_id ) : ?>
							<?php echo $subscription->is_installment_plan() ? $subscription->get_installment_plan_label() : $subscription->get_recurring_amount( true ); ?>
						<?php elseif ( 'subscription-actions' === $column_id ) : ?>
							<a href="<?php echo esc_url( $subscription->get_view_url() ); ?>"><?php esc_html_e( 'View', 'wp-courseware' ); ?></a>
						<?php endif; ?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<?php wpcw_print_notice( esc_html__( 'You are not subscribed to any courses.', 'wp-courseware' ), 'info' ); ?>
<?php endif; ?>
