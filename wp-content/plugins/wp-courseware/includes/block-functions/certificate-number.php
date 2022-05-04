<?php
/**
 * WP Courseware Gutenber Blocks Support.
 *
 * @package WPCW
 * @subpackage Block Function
 * @since 4.8.0
 */

/**
 * Certificate Certificate Number block render callback function.
 *
 * @since 4.8.0
 * 
 * @param object $attributes Attributes of Render function.
 *
 * @return string
 */
function wpcw_certificate_certificate_number_render_callback( $attributes ) {

	$font_size      = isset( $attributes['fontSize'] ) ? $attributes['fontSize'] : '';
	$font_family    = isset( $attributes['fontFamily'] ) ? $attributes['fontFamily'] : '';
	$text_color     = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '';
	$text_align     = isset( $attributes['textAlign'] ) ? $attributes['textAlign'] : '';
	$font_weight    = isset( $attributes['fontWeight'] ) ? $attributes['fontWeight'] : '';
	$font_style     = isset( $attributes['fontStyle'] ) ? $attributes['fontStyle'] : '';
	$text_underline = isset( $attributes['textUnderline'] ) ? $attributes['textUnderline'] : '';
	$number_prefix  = isset( $attributes['numberPrefix'] ) ? $attributes['numberPrefix'] : '';
	$number_length  = isset( $attributes['numberLength'] ) ? $attributes['numberLength'] : '';
	$certificate_number = isset( $attributes['certificateNumber'] ) ? $attributes['certificateNumber'] : '';

	ob_start();
	$style  = '';
	$style .= $font_size ? 'font-size:' . $font_size . 'px;' : '';
	$style .= $text_color ? 'color:' . $text_color . ';' : '';
	$style .= $font_family ? 'font-family:' . $font_family . ';' : '';
	$style .= $text_align ? 'text-align:' . $text_align . ';' : '';
	$style .= 'text-decoration:' . ( $text_underline ? 'underline' : 'none' ) . ';';
	ob_start();

	?>
		<div class="wpcw-certificate-certificate-number" style="<?php echo esc_attr( $style ); ?>">
			<?php if ( $certificate_number ) { ?>
				CERTIFICATE NUMBER
			<?php } ?>
		</div>
	<?php
	$html = ob_get_clean();

	return $html;
}

