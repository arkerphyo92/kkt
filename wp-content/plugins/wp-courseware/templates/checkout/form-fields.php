<?php
/**
 * Checkout Form - Fields.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/form-fields.php.
 *
 * @package WPCW
 * @subpackage Templates\Common
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<div class="wpcw-checkout-fields">
    <div class="wpcw-checkout-primary-fields">
        <h3><?php esc_html_e( 'Personal Information', 'wp-courseware' ); ?></h3>
        <div class="wpcw-form">
			<?php
			foreach ( $checkout->get_checkout_fields( 'primary' ) as $key => $field ) {
				wpcw_form_field( $key, $field, $checkout->get_posted_value( $key ) );
			}
			?>
            <div class="wpcw-clear"></div>
        </div>
    </div>

	<?php if ( ! is_user_logged_in() ) { ?>
        <div class="wpcw-checkout-account-fields">
            <h3><?php esc_html_e( 'Account Information', 'wp-courseware' ); ?></h3>
            <div class="wpcw-form">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php wpcw_form_field( $key, $field, $checkout->get_posted_value( $key ) ); ?>
				<?php endforeach; ?>
                <div class="wpcw-clear"></div>
            </div>
        </div>
	<?php } ?>

    <div class="wpcw-checkout-billing-fields">
        <h3><?php esc_html_e( 'Billing Information', 'wp-courseware' ); ?></h3>
        <div class="wpcw-form">
			<?php
			foreach ( $checkout->get_checkout_fields( 'billing' ) as $key => $field ) {
				wpcw_form_field( $key, $field, $checkout->get_posted_value( $key ) );
			}
			?>
            <div class="wpcw-clear"></div>
        </div>
    </div>

    <div class="wpcw-clear"></div>
</div>
