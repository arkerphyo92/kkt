<?php
/**
 * WP Courseware Validation Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Validates an email using WordPress native is_email function.
 *
 * @since 4.3.0
 *
 * @param string $email Email address to validate.
 *
 * @return bool
 */
function wpcw_validation_is_email( $email ) {
	return is_email( $email );
}

/**
 * Validates a phone number using a regular expression.
 *
 * @since 4.3.0
 *
 * @param string $phone Phone number to validate.
 *
 * @return bool
 */
function wpcw_validation_is_phone( $phone ) {
	if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)]/', '', $phone ) ) ) ) {
		return false;
	}

	return true;
}

/**
 * Checks for a valid postcode.
 *
 * @since 4.3.0
 *
 * @param string $postcode Postcode to validate.
 * @param string $country Country to validate the postcode for.
 *
 * @return bool
 */
function wpcw_validation_is_postcode( $postcode, $country ) {
	if ( strlen( trim( preg_replace( '/[\s\-A-Za-z0-9]/', '', $postcode ) ) ) > 0 ) {
		return false;
	}

	switch ( $country ) {
		case 'AT' :
			$valid = (bool) preg_match( '/^([0-9]{4})$/', $postcode );
			break;
		case 'BR' :
			$valid = (bool) preg_match( '/^([0-9]{5})([-])?([0-9]{3})$/', $postcode );
			break;
		case 'CH' :
			$valid = (bool) preg_match( '/^([0-9]{4})$/i', $postcode );
			break;
		case 'DE' :
			$valid = (bool) preg_match( '/^([0]{1}[1-9]{1}|[1-9]{1}[0-9]{1})[0-9]{3}$/', $postcode );
			break;
		case 'ES' :
		case 'FR' :
			$valid = (bool) preg_match( '/^([0-9]{5})$/i', $postcode );
			break;
		case 'GB' :
			$valid = wpcw_validation_is_GB_postcode( $postcode );
			break;
		case 'JP' :
			$valid = (bool) preg_match( '/^([0-9]{3})([-])([0-9]{4})$/', $postcode );
			break;
		case 'PT' :
			$valid = (bool) preg_match( '/^([0-9]{4})([-])([0-9]{3})$/', $postcode );
			break;
		case 'US' :
			$valid = (bool) preg_match( '/^([0-9]{5})(-[0-9]{4})?$/i', $postcode );
			break;
		case 'CA' :
			// CA Postal codes cannot contain D,F,I,O,Q,U and cannot start with W or Z. https://en.wikipedia.org/wiki/Postal_codes_in_Canada#Number_of_possible_postal_codes
			$valid = (bool) preg_match( '/^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])([\ ])?(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$/i', $postcode );
			break;
		case 'PL':
			$valid = (bool) preg_match( '/^([0-9]{2})([-])([0-9]{3})$/', $postcode );
			break;
		case 'CZ':
		case 'SK':
			$valid = (bool) preg_match( '/^([0-9]{3})(\s?)([0-9]{2})$/', $postcode );
			break;

		default :
			$valid = true;
			break;
	}

	return apply_filters( 'wpcw_validate_postcode', $valid, $postcode, $country );
}

/**
 * Check if is a GB postcode.
 *
 * @author John Gardner
 *
 * @since 4.3.0
 *
 * @param string $to_check A postcode.
 *
 * @return bool
 */
function wpcw_validation_is_GB_postcode( $to_check ) {
	// Permitted letters depend upon their position in the postcode.
	// https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom#Validation
	$alpha1 = "[abcdefghijklmnoprstuwyz]"; // Character 1
	$alpha2 = "[abcdefghklmnopqrstuvwxy]"; // Character 2
	$alpha3 = "[abcdefghjkpstuw]";         // Character 3 == ABCDEFGHJKPSTUW
	$alpha4 = "[abehmnprvwxy]";            // Character 4 == ABEHMNPRVWXY
	$alpha5 = "[abdefghjlnpqrstuwxyz]";    // Character 5 != CIKMOV

	$pcexp = array();

	// Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA
	$pcexp[0] = '/^(' . $alpha1 . '{1}' . $alpha2 . '{0,1}[0-9]{1,2})([0-9]{1}' . $alpha5 . '{2})$/';

	// Expression for postcodes: ANA NAA
	$pcexp[1] = '/^(' . $alpha1 . '{1}[0-9]{1}' . $alpha3 . '{1})([0-9]{1}' . $alpha5 . '{2})$/';

	// Expression for postcodes: AANA NAA
	$pcexp[2] = '/^(' . $alpha1 . '{1}' . $alpha2 . '[0-9]{1}' . $alpha4 . ')([0-9]{1}' . $alpha5 . '{2})$/';

	// Exception for the special postcode GIR 0AA
	$pcexp[3] = '/^(gir)(0aa)$/';

	// Standard BFPO numbers
	$pcexp[4] = '/^(bfpo)([0-9]{1,4})$/';

	// c/o BFPO numbers
	$pcexp[5] = '/^(bfpo)(c\/o[0-9]{1,3})$/';

	// Load up the string to check, converting into lowercase and removing spaces
	$postcode = strtolower( $to_check );
	$postcode = str_replace( ' ', '', $postcode );

	// Assume we are not going to find a valid postcode
	$valid = false;

	// Check the string against the six types of postcodes
	foreach ( $pcexp as $regexp ) {
		if ( preg_match( $regexp, $postcode, $matches ) ) {
			// Remember that we have found that the code is valid and break from loop
			$valid = true;
			break;
		}
	}

	return $valid;
}

/**
 * Format the postcode according to the country and length of the postcode.
 *
 * @since 4.3.0
 *
 * @param string $postcode Postcode to format.
 * @param string $country Country to format the postcode for.
 *
 * @return string Formatted postcode.
 */
function wpcw_validation_format_postcode( $postcode, $country ) {
	return wpcw_format_postcode( $postcode, $country );
}

/**
 * Format Phone.
 *
 * @since 4.3.0
 *
 * @param mixed $tel Phone number to format.
 *
 * @return string
 */
function wpcw_validation_format_phone( $tel ) {
	return wpcw_format_phone_number( $tel );
}