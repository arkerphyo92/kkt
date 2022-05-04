<?php
/**
 * Checkout Terms.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/checkout/terms.php.
 *
 * @package WPCW
 * @subpackage Templates\Checkout
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Get Terms Page Id.
$terms_page_id = wpcw_get_page_id( 'terms' );

// Display Terms Checkbox.
if ( $terms_page_id > 0 && apply_filters( 'wpcw_checkout_show_terms', true ) ) :
	$terms = get_post( $terms_page_id );
	$terms_content = has_shortcode( $terms->post_content, 'wpcw_checkout' ) ? '' : wpcw_format_content( $terms->post_content );
	if ( $terms_content ) {
		do_action( 'wpcw_checkout_before_terms_and_conditions' );
		echo '<div class="wpcw-terms-and-conditions" style="display: none; max-height: 200px; overflow: auto;">' . $terms_content . '</div>';
	}
	?>
    <p class="wpcw-form-row wpcw-terms wpcw-terms-and-conditions">
        <label id="terms" class="wpcw-form-label wpcw-form-label-for-checkbox checkbox">
            <input type="checkbox" class="wpcw-form-input wpcw-form-input-checkbox input-checkbox"
                   name="terms" <?php checked( apply_filters( 'wpcw_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?> id="terms"/>
            <span><?php printf( __( 'I&rsquo;ve read and accept the <a href="%s" target="_blank" class="wpcw-terms-and-conditions-link">terms &amp; conditions</a>', 'wp-courseware' ), esc_url( wpcw_get_page_permalink( 'terms' ) ) ); ?></span>
            <span class="required">*</span>
        </label>
        <input type="hidden" name="terms-field" value="1"/>
    </p>
	<?php do_action( 'wpcw_checkout_after_terms_and_conditions' ); ?>
<?php endif; ?>
