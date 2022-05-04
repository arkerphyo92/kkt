<?php
/**
 * WP Courseware Gutenber Blocks Support.
 *
 * @package WPCW
 * @subpackage Block Function
 * @since 4.8.0
 */

/**
 * Certificate Cumulative Grade block render callback function.
 *
 * @since 4.8.0
 * 
 * @param object $attributes Attributes of Render function.
 *
 * @return string
 */
function wpcw_certificate_cumulative_grade_render_callback( $attributes ) {

	$font_size        = isset( $attributes['fontSize'] ) ? $attributes['fontSize'] : '';
	$font_family      = isset( $attributes['fontFamily'] ) ? $attributes['fontFamily'] : '';
	$text_color       = isset( $attributes['textColor'] ) ? $attributes['textColor'] : '';
	$text_align       = isset( $attributes['textAlign'] ) ? $attributes['textAlign'] : '';
	$font_weight      = isset( $attributes['fontWeight'] ) ? $attributes['fontWeight'] : '';
	$font_style       = isset( $attributes['fontStyle'] ) ? $attributes['fontStyle'] : '';
	$text_underline   = isset( $attributes['textUnderline'] ) ? $attributes['textUnderline'] : '';
	$cumulative_grade = isset( $attributes['cumulativeGrade'] ) ? $attributes['cumulativeGrade'] : '';

	ob_start();
	$style  = '';
	$style .= $font_size ? 'font-size:' . $font_size . 'px;' : '';
	$style .= $text_color ? 'color:' . $text_color . ';' : '';
	$style .= $font_family ? 'font-family:' . $font_family . ';' : '';
	$style .= $text_align ? 'text-align:' . $text_align . ';' : '';
	$style .= 'text-decoration:' . ( $text_underline ? 'underline' : 'none' ) . ';';
	?>
	<div class="wpcw-certificate-cumulative-grade" style="<?php echo esc_attr( $style ); ?>">
		<?php if ( $cumulative_grade ) { ?>
			XX%
		<?php } ?>
	</div>

	<?php
	$html = ob_get_clean();

	return $html;
}
