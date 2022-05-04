<?php
/**
 * Email - Subscription Details Table
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/email-subscription-details-table.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.3.0
 *
 * Variables available in this template:
 * -----------------------------------------------
 * @var \WPCW\Models\Subscription $subscription The subscription object
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
do_action( 'wpcw_email_before_subscription_details_table', $subscription, $admin_email, $email ); ?>

<h2 class="no-bold">
	<?php
	if ( $admin_email ) {
		$before = '<a class="link" href="' . esc_url( $subscription->get_edit_url() ) . '">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}
	/* translators: %s: Order ID. */
	echo wp_kses_post( $before . sprintf( __( 'Subscription #%1$s', 'wp-courseware' ) . $after . ' - <time datetime="%2$s">%3$s</time>', $subscription->get_id(), wpcw_format_datetime( $subscription->get_created(), 'c' ), wpcw_format_datetime( $subscription->get_created() ) ) );
	?>
</h2>

<div style="margin-bottom: 20px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <thead>
        <tr>
            <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'ID', 'wp-courseware' ); ?></th>
            <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
            <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></th>
            <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="td number"
                style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                <a href="<?php echo $subscription->get_view_url(); ?>"><?php printf( '#%s', $subscription->get_id() ); ?></a>
            </td>
            <td class="td title"
                style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
				<?php printf( '%s', $subscription->get_course_title() ); ?>
            </td>
            <td class="td amount"
                style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
				<?php printf( '%s', $subscription->is_installment_plan() ? $subscription->get_installment_plan_label() : $subscription->get_recurring_amount( true ) ); ?>
            </td>
            <td class="td status"
                style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
				<?php printf( '%s', wpcw_get_subscription_status_name( $subscription->get_status() ) ); ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<?php
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
