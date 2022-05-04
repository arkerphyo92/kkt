<?php
/**
 * WP Courseware Formatting Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Convert RGB to HEX.
 *
 * @since 4.3.0
 *
 * @param mixed $color Color.
 *
 * @return array
 */
if ( ! function_exists( 'wpcw_rgb_from_hex' ) ) {
	function wpcw_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF".
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb      = array();
		$rgb['R'] = hexdec( $color[0] . $color[1] );
		$rgb['G'] = hexdec( $color[2] . $color[3] );
		$rgb['B'] = hexdec( $color[4] . $color[5] );

		return $rgb;
	}
}

/**
 * Make HEX color darker.
 *
 * @since 4.3.0
 *
 * @param mixed $color Color.
 * @param int   $factor Darker factor. Defaults to 30.
 *
 * @return string
 */
if ( ! function_exists( 'wpcw_hex_darker' ) ) {
	function wpcw_hex_darker( $color, $factor = 30 ) {
		$base  = wpcw_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = $v / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

/**
 * Make HEX color lighter.
 *
 * @since 4.3.0
 *
 * @param mixed $color Color.
 * @param int   $factor Lighter factor. Defaults to 30.
 *
 * @return string
 */
if ( ! function_exists( 'wpcw_hex_lighter' ) ) {
	function wpcw_hex_lighter( $color, $factor = 30 ) {
		$base  = wpcw_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

/**
 * Determine whether a hex color is light.
 *
 * @since 4.3.0
 *
 * @param mixed $color Color.
 *
 * @return bool True if a light color.
 */
if ( ! function_exists( 'wpcw_hex_is_light' ) ) {
	function wpcw_hex_is_light( $color ) {
		$hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155;
	}
}

/**
 * Detect if we should use a light or dark color on a background color.
 *
 * @since 4.3.0
 *
 * @param mixed  $color Color.
 * @param string $dark Darkest reference. Defaults to '#000000'.
 * @param string $light Lightest reference. Defaults to '#FFFFFF'.
 *
 * @return string
 */
if ( ! function_exists( 'wpcw_light_or_dark' ) ) {
	function wpcw_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		return wpcw_hex_is_light( $color ) ? $dark : $light;
	}
}

/**
 * Format string as hex.
 *
 * @since 4.3.0
 *
 * @param string $hex HEX color.
 *
 * @return string|null
 */
if ( ! function_exists( 'wpcw_format_hex' ) ) {
	function wpcw_format_hex( $hex ) {
		$hex = trim( str_replace( '#', '', $hex ) );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return $hex ? '#' . $hex : null;
	}
}

/**
 * Format Decimal Numbers.
 *
 * Sanitize, remove decimals, and optionally round + trim off zeros.
 *
 * This function does not remove thousands - this should be done before passing a value to the function.
 *
 * @since 4.3.0
 *
 * @param float|string $number Expects either a float or a string with a decimal separator only (no thousands).
 * @param mixed        $dp Number of decimal points to use, blank to use wpcw_get_currency_decimals, or false to avoid all rounding.
 * @param bool         $trim_zeros From end of string.
 *
 * @return string
 */
function wpcw_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	$locale   = localeconv();
	$decimals = array( wpcw_get_currency_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'] );

	// Remove locale from string.
	if ( ! is_float( $number ) ) {
		$number = str_replace( $decimals, '.', $number );
		$number = preg_replace( '/[^0-9\.,-]/', '', wpcw_clean( $number ) );
	}

	if ( false !== $dp ) {
		$dp     = intval( '' === $dp ? wpcw_get_currency_decimals() : $dp );
		$number = number_format( floatval( $number ), $dp, '.', '' );
	} elseif ( is_float( $number ) ) {
		// DP is false - don't use number format, just return a string using whatever is given. Remove scientific notation using sprintf.
		$number = str_replace( $decimals, '.', sprintf( '%.' . wpcw_get_rounding_precision() . 'f', $number ) );
		// We already had a float, so trailing zeros are not needed.
		$trim_zeros = true;
	}

	if ( $trim_zeros && strstr( $number, '.' ) ) {
		$number = rtrim( rtrim( $number, '0' ), '.' );
	}

	return $number;
}

/**
 * Convert a float to a string.
 *
 * @since 4.3.0
 *
 * @param float $float Float value to format.
 *
 * @return string
 */
function wpcw_float_to_string( $float ) {
	if ( ! is_float( $float ) ) {
		return $float;
	}

	$locale = localeconv();
	$string = strval( $float );
	$string = str_replace( $locale['decimal_point'], '.', $string );

	return $string;
}

/**
 * Format a price with WP Courseware Currency Locale settings.
 *
 * @since 4.5.0
 *
 * @param string $value Price to localize.
 *
 * @return string The localized price.
 */
function wpcw_format_localized_price( $value ) {
	return apply_filters( 'wpcw_format_localized_price', str_replace( '.', wpcw_get_currency_decimal_separator(), strval( $value ) ), $value );
}

/**
 * Trim trailing zeros.
 *
 * @since 4.3.0
 *
 * @param string|float|int $price Price.
 *
 * @return string
 */
function wpcw_trim_zeros( $price ) {
	return preg_replace( '/' . preg_quote( wpcw_get_currency_decimal_separator(), '/' ) . '0++$/', '', $price );
}

/**
 * Format a price as currency.
 *
 * @param float $price The price float number.
 * @param array $args Arguments to format a price {
 * Array of arguments. Defaults to empty array.
 *
 * @type string $currency Currency code. Defaults to empty string (Use the result from wpcw_get_currency()).
 * @type string $decimal_separator Decimal separator. Defaults the result of wpcw_get_currency_decimal_separator().
 * @type string $thousand_separator Thousand separator. Defaults the result of wpcw_get_currency_thousand_separator().
 * @type string $decimals Number of decimals. Defaults the result of wpcw_get_currency_decimals().
 * @type string $price_format Price format depending on the currency position. Defaults the result of wpcw_get_currency_format().
 * }
 *
 * @return string
 */
function wpcw_price( $price, $args = array() ) {
	extract( apply_filters( 'wpcw_price_args', wp_parse_args( $args, array(
		'currency'           => '',
		'decimal_separator'  => wpcw_get_currency_decimal_separator(),
		'thousand_separator' => wpcw_get_currency_thousand_separator(),
		'decimals'           => wpcw_get_currency_decimals(),
		'price_format'       => wpcw_get_currency_format(),
	) ) ) );

	$unformatted_price = $price;
	$negative          = $price < 0;
	$price             = apply_filters( 'wpcw_price_raw', floatval( $negative ? $price * - 1 : $price ) );
	$price             = apply_filters( 'wpcw_price_formatted', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

	if ( apply_filters( 'wpcw_price_trim_zeros', false ) && $decimals > 0 ) {
		$price = wpcw_trim_zeros( $price );
	}

	$formatted_price = ( $negative ? '-' : '' ) . sprintf( $price_format, wpcw_get_currency_symbol( $currency ), $price );
	$return          = $formatted_price;

	/**
	 * Filter: Format the price string.
	 *
	 * @since 4.3.0
	 *
	 * @param string $return Price HTML markup.
	 * @param string $price Formatted price.
	 * @param array  $args Pass on the args.
	 * @param float  $unformatted_price Price as float to allow plugins custom formatting.
	 *
	 * @return string The formatted price string.
	 */
	return apply_filters( 'wpcw_price', $return, $price, $args, $unformatted_price );
}

/**
 * Merge two arrays.
 *
 * @since 4.3.0
 *
 * @param array $a1 First array to merge.
 * @param array $a2 Second array to merge.
 *
 * @return array
 */
function wpcw_array_overlay( $a1, $a2 ) {
	foreach ( $a1 as $k => $v ) {
		if ( ! array_key_exists( $k, $a2 ) ) {
			continue;
		}
		if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
			$a1[ $k ] = wpcw_array_overlay( $v, $a2[ $k ] );
		} else {
			$a1[ $k ] = $a2[ $k ];
		}
	}

	return $a1;
}

/**
 * Run wpcw_clean over posted textarea but maintain line breaks.
 *
 * @since 4.3.0
 *
 * @param string $var Data to sanitize.
 *
 * @return string
 */
function wpcw_sanitize_textarea( $var ) {
	return implode( "\n", array_map( 'wpcw_clean', explode( "\n", $var ) ) );
}

/**
 * Formate Postcode.
 *
 * @since 4.3.0
 *
 * @param string $postcode Unformatted postcode.
 * @param string $country Base country.
 *
 * @return string
 */
function wpcw_format_postcode( $postcode, $country ) {
	$postcode = wpcw_normalize_postcode( $postcode );

	switch ( $country ) {
		case 'CA' :
		case 'GB' :
			$postcode = trim( substr_replace( $postcode, ' ', - 3, 0 ) );
			break;
		case 'BR' :
		case 'PL' :
			$postcode = substr_replace( $postcode, '-', - 3, 0 );
			break;
		case 'JP' :
			$postcode = substr_replace( $postcode, '-', 3, 0 );
			break;
		case 'PT' :
			$postcode = substr_replace( $postcode, '-', 4, 0 );
			break;
		case 'US' :
			$postcode = rtrim( substr_replace( $postcode, '-', 5, 0 ), '-' );
			break;
	}

	return apply_filters( 'wpcw_format_postcode', $postcode, $country );
}

/**
 * Normalize Postcode.
 *
 * Remove spaces and convert characters to uppercase.
 *
 * @since 4.3.0
 *
 * @param string $postcode Postcode.
 *
 * @return string
 */
function wpcw_normalize_postcode( $postcode ) {
	return preg_replace( '/[\s\-]/', '', trim( wpcw_strtoupper( $postcode ) ) );
}

/**
 * Format phone numbers.
 *
 * @since 4.3.0
 *
 * @param string $phone Phone number.
 *
 * @return string
 */
function wpcw_format_phone_number( $phone ) {
	return str_replace( '.', '-', $phone );
}

/**
 * Wrapper for mb_strtoupper which see's if supported first.
 *
 * @since 4.3.0
 *
 * @param string $string String to format.
 *
 * @return string
 */
function wpcw_strtoupper( $string ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $string ) : strtoupper( $string );
}

/**
 * Make a string lowercase.
 *
 * Try to use mb_strtolower() when available.
 *
 * @since 4.3.0
 *
 * @param string $string String to format.
 *
 * @return string
 */
function wpcw_strtolower( $string ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string ) : strtolower( $string );
}

/**
 * Format content to display shortcodes.
 *
 * @since 4.3.0
 *
 * @param string $raw_string Raw string.
 *
 * @return string
 */
function wpcw_format_content( $raw_string ) {
	return apply_filters( 'wpcw_format_content', apply_filters( 'wpcw_short_description', $raw_string ), $raw_string );
}

/**
 * Limit length of an arg.
 *
 * @since 4.3.0
 *
 * @param string  $string The string to limit the length on.
 * @param integer $limit The number of characters to limit by.
 *
 * @return string The modified string.
 */
function wpcw_limit_length( $string, $limit = 127 ) {
	if ( strlen( $string ) > $limit ) {
		$string = substr( $string, 0, $limit - 3 ) . '...';
	}

	return $string;
}

/**
 * WP Courseware Date Format.
 *
 * @since 4.3.0
 *
 * @return string The date format.
 */
function wpcw_date_format() {
	return apply_filters( 'wpcw_date_format', get_option( 'date_format' ) );
}

/**
 * WP Courseware Time Format.
 *
 * @since 4.3.0
 *
 * @return string The time format.
 */
function wpcw_time_format() {
	return apply_filters( 'wpcw_time_format', get_option( 'time_format' ) );
}

/**
 * Format a date for output.
 *
 * @since 4.3.0
 *
 * @param string $format Data format.
 *
 * @return string
 */
function wpcw_format_datetime( $date, $format = '' ) {
	if ( ! $format ) {
		$format = wpcw_date_format();
	}

	return date_i18n( $format, strtotime( $date ) );
}

/**
 * Sanitize permalink values before insertion into DB.
 *
 * Cannot use wpcw_clean because it sometimes strips % chars and breaks the user's setting.
 *
 * @since 4.4.0
 *
 * @param string $value The permalink string.
 *
 * @return string The sanitized permalink string.
 */
function wpcw_sanitize_permalink( $value ) {
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );

	return untrailingslashit( $value );
}

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * @since 4.4.0
 *
 * @param string $string String to convert.
 *
 * @return bool
 */
function wpcw_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

/**
 * Converts a bool to a 'yes' or 'no'.
 *
 * @since 4.4.0
 *
 * @param bool $bool String to convert.
 *
 * @return string
 */
function wpcw_bool_to_string( $bool ) {
	if ( ! is_bool( $bool ) ) {
		$bool = wpcw_string_to_bool( $bool );
	}

	return true === $bool ? 'yes' : 'no';
}

/**
 * Notation to numbers.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @since 4.4.0
 *
 * @param string $size Size value.
 *
 * @return int
 */
function wpcw_let_to_num( $size ) {
	$l    = substr( $size, - 1 );
	$ret  = substr( $size, 0, - 1 );
	$byte = 1024;

	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
		// No break.
		case 'T':
			$ret *= 1024;
		// No break.
		case 'G':
			$ret *= 1024;
		// No break.
		case 'M':
			$ret *= 1024;
		// No break.
		case 'K':
			$ret *= 1024;
		// No break.
	}

	return $ret;
}

/**
 * Format Coupon Code.
 *
 * @since 4.5.0
 *
 * @param string $code The coupon code to format.
 */
function wpcw_format_coupon_code( $code ) {
	return apply_filters( 'wpcw_coupon_code', $code );
}
