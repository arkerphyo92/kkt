<?php
/**
 * Checkout Privacy.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/privacy.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Get Privacy Page Id.
$privacy_policy  = ( 'yes' === wpcw_get_setting( 'privacy_policy' ) ) ? true : false;
$privacy_page_id = wpcw_get_page_id( 'privacy' );

// Display Terms Checkbox.
if ( $privacy_policy && apply_filters( 'wpcw_checkout_show_privacy', ( $privacy_page_id > 0 ) ) && ( $privacy = get_post( $privacy_page_id ) ) ) :
	$privacy_content = has_shortcode( $privacy->post_content, 'wpcw_checkout' ) ? '' : wpcw_format_content( $privacy->post_content );
	if ( $privacy_content ) {
		do_action( 'wpcw_checkout_before_privacy_policy' );
		echo '<div class="wpcw-privacy-policy" style="display: none; max-height: 200px; overflow: auto;">' . $privacy_content . '</div>';
	}
	?>
    <p class="wpcw-form-row wpcw-privacy wpcw-privacy-policy">
        <label id="privacy" class="wpcw-form-label wpcw-form-label-for-checkbox checkbox">
            <input type="checkbox" class="wpcw-form-input wpcw-form-input-checkbox input-checkbox"
                   name="privacy" <?php checked( apply_filters( 'wpcw_privacy_policy_is_checked_default', isset( $_POST['privacy'] ) ), true ); ?> id="privacy"/>
            <span><?php printf( __( 'I&rsquo;ve read and accept the <a href="%s" target="_blank" class="wpcw-privacy-policy-link">privacy policy</a>', 'wp-courseware' ), esc_url( wpcw_get_page_permalink( 'privacy' ) ) ); ?></span>
            <span class="required">*</span>
        </label>
        <input type="hidden" name="privacy-field" value="1"/>
    </p>
	<?php do_action( 'wpcw_checkout_after_privacy_policy' ); ?>
<?php endif; ?>
