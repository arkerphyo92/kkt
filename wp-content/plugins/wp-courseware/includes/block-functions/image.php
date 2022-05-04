<?php
/**
 * WP Courseware Gutenber Blocks Support.
 *
 * @package WPCW
 * @subpackage Block Function
 * @since 4.8.0
 */

/**
 * Certificate Image block render callback function.
 *
 * @since 4.8.0
 * 
 * @param object $attributes Attributes of Render function.
 *
 * @return string
 */
function wpcw_certificate_image_render_callback( $attributes ) {

	$selected_image = isset( $attributes['selectedImage'] ) ? $attributes['selectedImage'] : '';

	ob_start();
	?>
		<div class="wpcw-certificate-image">
		<?php if ( ! empty( $selected_image ) && isset( $selected_image ) ) { ?>
			<img src="<?php echo esc_url( $selected_image ); ?>" height="100" width="100" />
		<?php } else { 
			?>
			<img src="<?php echo esc_url( WPCW_URL . 'assets/img/certificates/default-img-1.jpg' ); ?>" height="100" width="100" />
		<?php } ?>
		</div>
	<?php
	$html = ob_get_clean();

	return $html;
}

