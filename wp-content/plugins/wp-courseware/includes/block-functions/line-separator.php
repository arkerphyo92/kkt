<?php
/**
 * WP Courseware Gutenber Blocks Support.
 *
 * @package WPCW
 * @subpackage Block Function
 * @since 4.8.0
 */

/**
 * Certificate Line Separator block render callback function.
 *
 * @since 4.8.0
 * 
 * @param object $attributes Attributes of Render function.
 *
 * @return string
 */
function wpcw_certificate_line_separator_render_callback( $attributes ) {

	$line_color  = isset( $attributes['lineColor'] ) ? $attributes['lineColor'] : '';
	$line_height = isset( $attributes['lineHeight'] ) ? $attributes['lineHeight'] : '';
	$line_width  = isset( $attributes['lineWidth'] ) ? $attributes['lineWidth'] : '';

	ob_start();
	$style  = '';
	$style .= $line_height ? 'height:' . $line_height . 'px;' : '';
	$style .= $line_color ? 'background-color:' . $line_color . ';' : '';
	$style .= $line_width ? 'width:' . $line_width . '%;' : '';
	ob_start();

	?>
		<hr style="<?php echo esc_attr( $style ); ?>"></hr>
	<?php
	$html = ob_get_clean();

	return $html;
}

