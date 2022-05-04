<?php
/**
 * WP Courseware Core Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Setting.
 *
 * @since 4.3.0
 *
 * @param stirng $key The setting key.
 * @param mixed  $default_value The default value. Null by default.
 *
 * @return mixed The setting value.
 */
function wpcw_get_setting( $key, $default_value = null ) {
	return wpcw()->settings->get_setting( $key, $default_value );
}

/**
 * Update Setting.
 *
 * @since 4.3.0
 *
 * @param string $key The setting key.
 * @param string $value The setting value.
 */
function wpcw_update_setting( $key, $value = '' ) {
	wpcw()->settings->set_setting( $key, $value );

	return wpcw()->settings->save_settings();
}

/**
 * Delete Setting.
 *
 * @since 4.4.0
 *
 * @param string $name The setting anme
 */
function wpcw_delete_setting( $key ) {
	return wpcw()->settings->delete_setting( $key );
}

/**
 * Get permalink settings for things like courses and taxonomies.
 *
 * @since 4.4.0
 *
 * @return array $permalinks The permalink settings.
 */
function wpcw_get_permalink_structure() {
	$saved_permalinks = (array) get_option( 'wpcw_permalinks', array() );
	$permalinks       = wp_parse_args( array_filter( $saved_permalinks ), array(
		'course_base'            => _x( 'course', 'slug', 'wp-courseware' ),
		'course_category_base'   => _x( 'course-category', 'slug', 'wp-courseware' ),
		'course_tag_base'        => _x( 'course-tag', 'slug', 'wp-courseware' ),
		'unit_base'              => '%module_number%',
		'unit_category_base'     => _x( 'unit-category', 'slug', 'wp-courseware' ),
		'unit_tag_base'          => _x( 'unit-tag', 'slug', 'wp-courseware' ),
		'use_verbose_page_rules' => false,
		'certificate_base'       => _x( 'certificate', 'slug', 'wp-courseware' ),
	) );

	if ( $saved_permalinks !== $permalinks ) {
		update_option( 'wpcw_permalinks', $permalinks );
	}

	// Courses
	$permalinks['course_rewrite_slug']          = untrailingslashit( $permalinks['course_base'] );
	$permalinks['course_category_rewrite_slug'] = untrailingslashit( $permalinks['course_category_base'] );
	$permalinks['course_tag_rewrite_slug']      = untrailingslashit( $permalinks['course_tag_base'] );

	// Units
	$permalinks['unit_rewrite_slug']          = untrailingslashit( $permalinks['unit_base'] );
	$permalinks['unit_category_rewrite_slug'] = untrailingslashit( $permalinks['unit_category_base'] );
	$permalinks['unit_tag_rewrite_slug']      = untrailingslashit( $permalinks['unit_tag_base'] );

	// Certificate.
	$permalinks['certificate_rewrite_slug'] = untrailingslashit( $permalinks['certificate_base'] );

	return $permalinks;
}

/**
 * Get Currency Code.
 *
 * @since 4.3.0
 *
 * @return string
 */
function wpcw_get_currency() {
	return apply_filters( 'wpcw_currency', wpcw_get_setting( 'currency', 'USD' ) );
}

/**
 * Get Currency Position.
 *
 * @since 4.4.0
 *
 * @return string $currency_position The currency position.
 */
function wpcw_get_currency_position() {
	return apply_filters( 'wpcw_currency_position', wpcw_get_setting( 'currency_position', 'left' ) );
}

/**
 * Get Currency Format.
 *
 * @since 4.3.0
 *
 * @return string The currency format.
 */
function wpcw_get_currency_format() {
	$currency_pos = wpcw_get_currency_position();
	$format       = '%1$s%2$s';

	switch ( $currency_pos ) {
		case 'left' :
			$format = '%1$s%2$s';
			break;
		case 'right' :
			$format = '%2$s%1$s';
			break;
		case 'left_space' :
			$format = '%1$s&nbsp;%2$s';
			break;
		case 'right_space' :
			$format = '%2$s&nbsp;%1$s';
			break;
	}

	return apply_filters( 'wpcw_currency_format', $format, $currency_pos );
}

/**
 * Get Currency Thousands Separator.
 *
 * @since 4.3.0
 *
 * @return string The currency thousands separator.
 */
function wpcw_get_currency_thousand_separator() {
	$separator = apply_filters( 'wpcw_currency_thousand_separator', wpcw_get_setting( 'thousands_sep', ',' ) );

	return $separator ? stripslashes( $separator ) : ',';
}

/**
 * Get Currency Decimal Separator.
 *
 * @since 4.3.0
 *
 * @return string The currency decimal separator.
 */
function wpcw_get_currency_decimal_separator() {
	$separator = apply_filters( 'wpcw_currency_decimal_separator', wpcw_get_setting( 'decimal_sep', '.' ) );

	return $separator ? stripslashes( $separator ) : '.';
}

/**
 * Get Currency Number of Decimals.
 *
 * @since 4.3.0
 *
 * @return int The currency number of decimals.
 */
function wpcw_get_currency_decimals() {
	$decimals = apply_filters( 'wpcw_currency_decimals', wpcw_get_setting( 'num_decimals', 2 ) );

	return $decimals ? absint( $decimals ) : 2;
}

/**
 * Get Currencies.
 *
 * @since 4.3.0
 *
 * @return array The full list of currencies.
 */
function wpcw_get_currencies() {
	static $currencies;

	if ( ! isset( $currencies ) ) {
		$currencies = array_unique( apply_filters( 'wpcw_currencies', array(
			'AED' => __( 'United Arab Emirates dirham', 'wp-courseware' ),
			'AFN' => __( 'Afghan afghani', 'wp-courseware' ),
			'ALL' => __( 'Albanian lek', 'wp-courseware' ),
			'AMD' => __( 'Armenian dram', 'wp-courseware' ),
			'ANG' => __( 'Netherlands Antillean guilder', 'wp-courseware' ),
			'AOA' => __( 'Angolan kwanza', 'wp-courseware' ),
			'ARS' => __( 'Argentine peso', 'wp-courseware' ),
			'AUD' => __( 'Australian dollar', 'wp-courseware' ),
			'AWG' => __( 'Aruban florin', 'wp-courseware' ),
			'AZN' => __( 'Azerbaijani manat', 'wp-courseware' ),
			'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'wp-courseware' ),
			'BBD' => __( 'Barbadian dollar', 'wp-courseware' ),
			'BDT' => __( 'Bangladeshi taka', 'wp-courseware' ),
			'BGN' => __( 'Bulgarian lev', 'wp-courseware' ),
			'BHD' => __( 'Bahraini dinar', 'wp-courseware' ),
			'BIF' => __( 'Burundian franc', 'wp-courseware' ),
			'BMD' => __( 'Bermudian dollar', 'wp-courseware' ),
			'BND' => __( 'Brunei dollar', 'wp-courseware' ),
			'BOB' => __( 'Bolivian boliviano', 'wp-courseware' ),
			'BRL' => __( 'Brazilian real', 'wp-courseware' ),
			'BSD' => __( 'Bahamian dollar', 'wp-courseware' ),
			'BTC' => __( 'Bitcoin', 'wp-courseware' ),
			'BTN' => __( 'Bhutanese ngultrum', 'wp-courseware' ),
			'BWP' => __( 'Botswana pula', 'wp-courseware' ),
			'BYR' => __( 'Belarusian ruble (old)', 'wp-courseware' ),
			'BYN' => __( 'Belarusian ruble', 'wp-courseware' ),
			'BZD' => __( 'Belize dollar', 'wp-courseware' ),
			'CAD' => __( 'Canadian dollar', 'wp-courseware' ),
			'CDF' => __( 'Congolese franc', 'wp-courseware' ),
			'CHF' => __( 'Swiss franc', 'wp-courseware' ),
			'CLP' => __( 'Chilean peso', 'wp-courseware' ),
			'CNY' => __( 'Chinese yuan', 'wp-courseware' ),
			'COP' => __( 'Colombian peso', 'wp-courseware' ),
			'CRC' => __( 'Costa Rican col&oacute;n', 'wp-courseware' ),
			'CUC' => __( 'Cuban convertible peso', 'wp-courseware' ),
			'CUP' => __( 'Cuban peso', 'wp-courseware' ),
			'CVE' => __( 'Cape Verdean escudo', 'wp-courseware' ),
			'CZK' => __( 'Czech koruna', 'wp-courseware' ),
			'DJF' => __( 'Djiboutian franc', 'wp-courseware' ),
			'DKK' => __( 'Danish krone', 'wp-courseware' ),
			'DOP' => __( 'Dominican peso', 'wp-courseware' ),
			'DZD' => __( 'Algerian dinar', 'wp-courseware' ),
			'EGP' => __( 'Egyptian pound', 'wp-courseware' ),
			'ERN' => __( 'Eritrean nakfa', 'wp-courseware' ),
			'ETB' => __( 'Ethiopian birr', 'wp-courseware' ),
			'EUR' => __( 'Euro', 'wp-courseware' ),
			'FJD' => __( 'Fijian dollar', 'wp-courseware' ),
			'FKP' => __( 'Falkland Islands pound', 'wp-courseware' ),
			'GBP' => __( 'Pound sterling', 'wp-courseware' ),
			'GEL' => __( 'Georgian lari', 'wp-courseware' ),
			'GGP' => __( 'Guernsey pound', 'wp-courseware' ),
			'GHS' => __( 'Ghana cedi', 'wp-courseware' ),
			'GIP' => __( 'Gibraltar pound', 'wp-courseware' ),
			'GMD' => __( 'Gambian dalasi', 'wp-courseware' ),
			'GNF' => __( 'Guinean franc', 'wp-courseware' ),
			'GTQ' => __( 'Guatemalan quetzal', 'wp-courseware' ),
			'GYD' => __( 'Guyanese dollar', 'wp-courseware' ),
			'HKD' => __( 'Hong Kong dollar', 'wp-courseware' ),
			'HNL' => __( 'Honduran lempira', 'wp-courseware' ),
			'HRK' => __( 'Croatian kuna', 'wp-courseware' ),
			'HTG' => __( 'Haitian gourde', 'wp-courseware' ),
			'HUF' => __( 'Hungarian forint', 'wp-courseware' ),
			'IDR' => __( 'Indonesian rupiah', 'wp-courseware' ),
			'ILS' => __( 'Israeli new shekel', 'wp-courseware' ),
			'IMP' => __( 'Manx pound', 'wp-courseware' ),
			'INR' => __( 'Indian rupee', 'wp-courseware' ),
			'IQD' => __( 'Iraqi dinar', 'wp-courseware' ),
			'IRR' => __( 'Iranian rial', 'wp-courseware' ),
			'IRT' => __( 'Iranian toman', 'wp-courseware' ),
			'ISK' => __( 'Icelandic kr&oacute;na', 'wp-courseware' ),
			'JEP' => __( 'Jersey pound', 'wp-courseware' ),
			'JMD' => __( 'Jamaican dollar', 'wp-courseware' ),
			'JOD' => __( 'Jordanian dinar', 'wp-courseware' ),
			'JPY' => __( 'Japanese yen', 'wp-courseware' ),
			'KES' => __( 'Kenyan shilling', 'wp-courseware' ),
			'KGS' => __( 'Kyrgyzstani som', 'wp-courseware' ),
			'KHR' => __( 'Cambodian riel', 'wp-courseware' ),
			'KMF' => __( 'Comorian franc', 'wp-courseware' ),
			'KPW' => __( 'North Korean won', 'wp-courseware' ),
			'KRW' => __( 'South Korean won', 'wp-courseware' ),
			'KWD' => __( 'Kuwaiti dinar', 'wp-courseware' ),
			'KYD' => __( 'Cayman Islands dollar', 'wp-courseware' ),
			'KZT' => __( 'Kazakhstani tenge', 'wp-courseware' ),
			'LAK' => __( 'Lao kip', 'wp-courseware' ),
			'LBP' => __( 'Lebanese pound', 'wp-courseware' ),
			'LKR' => __( 'Sri Lankan rupee', 'wp-courseware' ),
			'LRD' => __( 'Liberian dollar', 'wp-courseware' ),
			'LSL' => __( 'Lesotho loti', 'wp-courseware' ),
			'LYD' => __( 'Libyan dinar', 'wp-courseware' ),
			'MAD' => __( 'Moroccan dirham', 'wp-courseware' ),
			'MDL' => __( 'Moldovan leu', 'wp-courseware' ),
			'MGA' => __( 'Malagasy ariary', 'wp-courseware' ),
			'MKD' => __( 'Macedonian denar', 'wp-courseware' ),
			'MMK' => __( 'Burmese kyat', 'wp-courseware' ),
			'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'wp-courseware' ),
			'MOP' => __( 'Macanese pataca', 'wp-courseware' ),
			'MRO' => __( 'Mauritanian ouguiya', 'wp-courseware' ),
			'MUR' => __( 'Mauritian rupee', 'wp-courseware' ),
			'MVR' => __( 'Maldivian rufiyaa', 'wp-courseware' ),
			'MWK' => __( 'Malawian kwacha', 'wp-courseware' ),
			'MXN' => __( 'Mexican peso', 'wp-courseware' ),
			'MYR' => __( 'Malaysian ringgit', 'wp-courseware' ),
			'MZN' => __( 'Mozambican metical', 'wp-courseware' ),
			'NAD' => __( 'Namibian dollar', 'wp-courseware' ),
			'NGN' => __( 'Nigerian naira', 'wp-courseware' ),
			'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'wp-courseware' ),
			'NOK' => __( 'Norwegian krone', 'wp-courseware' ),
			'NPR' => __( 'Nepalese rupee', 'wp-courseware' ),
			'NZD' => __( 'New Zealand dollar', 'wp-courseware' ),
			'OMR' => __( 'Omani rial', 'wp-courseware' ),
			'PAB' => __( 'Panamanian balboa', 'wp-courseware' ),
			'PEN' => __( 'Peruvian nuevo sol', 'wp-courseware' ),
			'PGK' => __( 'Papua New Guinean kina', 'wp-courseware' ),
			'PHP' => __( 'Philippine peso', 'wp-courseware' ),
			'PKR' => __( 'Pakistani rupee', 'wp-courseware' ),
			'PLN' => __( 'Polish z&#x142;oty', 'wp-courseware' ),
			'PRB' => __( 'Transnistrian ruble', 'wp-courseware' ),
			'PYG' => __( 'Paraguayan guaran&iacute;', 'wp-courseware' ),
			'QAR' => __( 'Qatari riyal', 'wp-courseware' ),
			'RON' => __( 'Romanian leu', 'wp-courseware' ),
			'RSD' => __( 'Serbian dinar', 'wp-courseware' ),
			'RUB' => __( 'Russian ruble', 'wp-courseware' ),
			'RWF' => __( 'Rwandan franc', 'wp-courseware' ),
			'SAR' => __( 'Saudi riyal', 'wp-courseware' ),
			'SBD' => __( 'Solomon Islands dollar', 'wp-courseware' ),
			'SCR' => __( 'Seychellois rupee', 'wp-courseware' ),
			'SDG' => __( 'Sudanese pound', 'wp-courseware' ),
			'SEK' => __( 'Swedish krona', 'wp-courseware' ),
			'SGD' => __( 'Singapore dollar', 'wp-courseware' ),
			'SHP' => __( 'Saint Helena pound', 'wp-courseware' ),
			'SLL' => __( 'Sierra Leonean leone', 'wp-courseware' ),
			'SOS' => __( 'Somali shilling', 'wp-courseware' ),
			'SRD' => __( 'Surinamese dollar', 'wp-courseware' ),
			'SSP' => __( 'South Sudanese pound', 'wp-courseware' ),
			'STD' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'wp-courseware' ),
			'SYP' => __( 'Syrian pound', 'wp-courseware' ),
			'SZL' => __( 'Swazi lilangeni', 'wp-courseware' ),
			'THB' => __( 'Thai baht', 'wp-courseware' ),
			'TJS' => __( 'Tajikistani somoni', 'wp-courseware' ),
			'TMT' => __( 'Turkmenistan manat', 'wp-courseware' ),
			'TND' => __( 'Tunisian dinar', 'wp-courseware' ),
			'TOP' => __( 'Tongan pa&#x2bb;anga', 'wp-courseware' ),
			'TRY' => __( 'Turkish lira', 'wp-courseware' ),
			'TTD' => __( 'Trinidad and Tobago dollar', 'wp-courseware' ),
			'TWD' => __( 'New Taiwan dollar', 'wp-courseware' ),
			'TZS' => __( 'Tanzanian shilling', 'wp-courseware' ),
			'UAH' => __( 'Ukrainian hryvnia', 'wp-courseware' ),
			'UGX' => __( 'Ugandan shilling', 'wp-courseware' ),
			'USD' => __( 'United States dollar', 'wp-courseware' ),
			'UYU' => __( 'Uruguayan peso', 'wp-courseware' ),
			'UZS' => __( 'Uzbekistani som', 'wp-courseware' ),
			'VEF' => __( 'Venezuelan bol&iacute;var', 'wp-courseware' ),
			'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'wp-courseware' ),
			'VUV' => __( 'Vanuatu vatu', 'wp-courseware' ),
			'WST' => __( 'Samoan t&#x101;l&#x101;', 'wp-courseware' ),
			'XAF' => __( 'Central African CFA franc', 'wp-courseware' ),
			'XCD' => __( 'East Caribbean dollar', 'wp-courseware' ),
			'XOF' => __( 'West African CFA franc', 'wp-courseware' ),
			'XPF' => __( 'CFP franc', 'wp-courseware' ),
			'YER' => __( 'Yemeni rial', 'wp-courseware' ),
			'ZAR' => __( 'South African rand', 'wp-courseware' ),
			'ZMW' => __( 'Zambian kwacha', 'wp-courseware' ),
		) ) );
	}

	return $currencies;
}

/**
 * Get Currency Symbol.
 *
 * @since 4.3.0
 *
 * @param string $currency Currency. (default: '').
 *
 * @return string The currency symbol
 */
function wpcw_get_currency_symbol( $currency = '' ) {
	if ( ! $currency ) {
		$currency = wpcw_get_currency();
	}

	$symbols = apply_filters( 'wpcw_currency_symbols', array(
		'AED' => '&#x62f;.&#x625;',
		'AFN' => '&#x60b;',
		'ALL' => 'L',
		'AMD' => 'AMD',
		'ANG' => '&fnof;',
		'AOA' => 'Kz',
		'ARS' => '&#36;',
		'AUD' => '&#36;',
		'AWG' => 'Afl.',
		'AZN' => 'AZN',
		'BAM' => 'KM',
		'BBD' => '&#36;',
		'BDT' => '&#2547;&nbsp;',
		'BGN' => '&#1083;&#1074;.',
		'BHD' => '.&#x62f;.&#x628;',
		'BIF' => 'Fr',
		'BMD' => '&#36;',
		'BND' => '&#36;',
		'BOB' => 'Bs.',
		'BRL' => '&#82;&#36;',
		'BSD' => '&#36;',
		'BTC' => '&#3647;',
		'BTN' => 'Nu.',
		'BWP' => 'P',
		'BYR' => 'Br',
		'BYN' => 'Br',
		'BZD' => '&#36;',
		'CAD' => '&#36;',
		'CDF' => 'Fr',
		'CHF' => '&#67;&#72;&#70;',
		'CLP' => '&#36;',
		'CNY' => '&yen;',
		'COP' => '&#36;',
		'CRC' => '&#x20a1;',
		'CUC' => '&#36;',
		'CUP' => '&#36;',
		'CVE' => '&#36;',
		'CZK' => '&#75;&#269;',
		'DJF' => 'Fr',
		'DKK' => 'DKK',
		'DOP' => 'RD&#36;',
		'DZD' => '&#x62f;.&#x62c;',
		'EGP' => 'EGP',
		'ERN' => 'Nfk',
		'ETB' => 'Br',
		'EUR' => '&euro;',
		'FJD' => '&#36;',
		'FKP' => '&pound;',
		'GBP' => '&pound;',
		'GEL' => '&#x10da;',
		'GGP' => '&pound;',
		'GHS' => '&#x20b5;',
		'GIP' => '&pound;',
		'GMD' => 'D',
		'GNF' => 'Fr',
		'GTQ' => 'Q',
		'GYD' => '&#36;',
		'HKD' => '&#36;',
		'HNL' => 'L',
		'HRK' => 'Kn',
		'HTG' => 'G',
		'HUF' => '&#70;&#116;',
		'IDR' => 'Rp',
		'ILS' => '&#8362;',
		'IMP' => '&pound;',
		'INR' => '&#8377;',
		'IQD' => '&#x639;.&#x62f;',
		'IRR' => '&#xfdfc;',
		'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
		'ISK' => 'kr.',
		'JEP' => '&pound;',
		'JMD' => '&#36;',
		'JOD' => '&#x62f;.&#x627;',
		'JPY' => '&yen;',
		'KES' => 'KSh',
		'KGS' => '&#x441;&#x43e;&#x43c;',
		'KHR' => '&#x17db;',
		'KMF' => 'Fr',
		'KPW' => '&#x20a9;',
		'KRW' => '&#8361;',
		'KWD' => '&#x62f;.&#x643;',
		'KYD' => '&#36;',
		'KZT' => 'KZT',
		'LAK' => '&#8365;',
		'LBP' => '&#x644;.&#x644;',
		'LKR' => '&#xdbb;&#xdd4;',
		'LRD' => '&#36;',
		'LSL' => 'L',
		'LYD' => '&#x644;.&#x62f;',
		'MAD' => '&#x62f;.&#x645;.',
		'MDL' => 'MDL',
		'MGA' => 'Ar',
		'MKD' => '&#x434;&#x435;&#x43d;',
		'MMK' => 'Ks',
		'MNT' => '&#x20ae;',
		'MOP' => 'P',
		'MRO' => 'UM',
		'MUR' => '&#x20a8;',
		'MVR' => '.&#x783;',
		'MWK' => 'MK',
		'MXN' => '&#36;',
		'MYR' => '&#82;&#77;',
		'MZN' => 'MT',
		'NAD' => '&#36;',
		'NGN' => '&#8358;',
		'NIO' => 'C&#36;',
		'NOK' => '&#107;&#114;',
		'NPR' => '&#8360;',
		'NZD' => '&#36;',
		'OMR' => '&#x631;.&#x639;.',
		'PAB' => 'B/.',
		'PEN' => 'S/.',
		'PGK' => 'K',
		'PHP' => '&#8369;',
		'PKR' => '&#8360;',
		'PLN' => '&#122;&#322;',
		'PRB' => '&#x440;.',
		'PYG' => '&#8370;',
		'QAR' => '&#x631;.&#x642;',
		'RMB' => '&yen;',
		'RON' => 'lei',
		'RSD' => '&#x434;&#x438;&#x43d;.',
		'RUB' => '&#8381;',
		'RWF' => 'Fr',
		'SAR' => '&#x631;.&#x633;',
		'SBD' => '&#36;',
		'SCR' => '&#x20a8;',
		'SDG' => '&#x62c;.&#x633;.',
		'SEK' => '&#107;&#114;',
		'SGD' => '&#36;',
		'SHP' => '&pound;',
		'SLL' => 'Le',
		'SOS' => 'Sh',
		'SRD' => '&#36;',
		'SSP' => '&pound;',
		'STD' => 'Db',
		'SYP' => '&#x644;.&#x633;',
		'SZL' => 'L',
		'THB' => '&#3647;',
		'TJS' => '&#x405;&#x41c;',
		'TMT' => 'm',
		'TND' => '&#x62f;.&#x62a;',
		'TOP' => 'T&#36;',
		'TRY' => '&#8378;',
		'TTD' => '&#36;',
		'TWD' => '&#78;&#84;&#36;',
		'TZS' => 'Sh',
		'UAH' => '&#8372;',
		'UGX' => 'UGX',
		'USD' => '&#36;',
		'UYU' => '&#36;',
		'UZS' => 'UZS',
		'VEF' => 'Bs F',
		'VND' => '&#8363;',
		'VUV' => 'Vt',
		'WST' => 'T',
		'XAF' => 'CFA',
		'XCD' => '&#36;',
		'XOF' => 'CFA',
		'XPF' => 'Fr',
		'YER' => '&#xfdfc;',
		'ZAR' => '&#82;',
		'ZMW' => 'ZK',
	) );

	$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

	return apply_filters( 'wpcw_currency_symbol', $currency_symbol, $currency );
}

/**
 * Get Currency Positions.
 *
 * @since 4.3.0
 *
 * @return string
 */
function wpcw_get_currency_positions() {
	return apply_filters( 'wpcw_currency_positions', array(
		'left'        => esc_html__( 'Left', 'wp-courseware' ),
		'right'       => esc_html__( 'Right', 'wp-courseware' ),
		'left_space'  => esc_html__( 'Left with space', 'wp-courseware' ),
		'right_space' => esc_html__( 'Right with space', 'wp-courseware' ),
	) );
}

/**
 * Get rounding precision for calculations.
 *
 * Will increase the precision of wpcw_get_currency_decimals by 2 decimals, unless WPCW_ROUNDING_PRECISION is set to a higher number.
 *
 * @since 4.3.0
 *
 * @return int The rounding precision integer.
 */
function wpcw_get_rounding_precision() {
	$precision = wpcw_get_currency_decimals();

	if ( absint( WPCW_ROUNDING_PRECISION ) > $precision ) {
		$precision = absint( WPCW_ROUNDING_PRECISION );
	}

	return $precision;
}

/**
 * Add precision to a number and return a number.
 *
 * @since 4.5.0
 *
 * @param float $value Number to add precision to.
 * @param bool  $round If should round after adding precision.
 *
 * @return int|float
 */
function wpcw_add_number_precision( $value, $round = true ) {
	$cent_precision = pow( 10, wpcw_get_currency_decimals() );
	$value          = $value * $cent_precision;

	return $round ? round( $value, wpcw_get_rounding_precision() - wpcw_get_currency_decimals() ) : $value;
}

/**
 * Remove precision from a number and return a float.
 *
 * @since 4.5.0
 *
 * @param float $value Number to add precision to.
 *
 * @return float
 */
function wpcw_remove_number_precision( $value ) {
	$cent_precision = pow( 10, wpcw_get_currency_decimals() );

	return $value / $cent_precision;
}

/**
 * Add precision to an array of number and return an array of int.
 *
 * @since 4.5.0
 *
 * @param array $value Number to add precision to.
 * @param bool  $round Should we round after adding precision?.
 *
 * @return int
 */
function wpcw_add_number_precision_deep( $value, $round = true ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $subvalue ) {
			$value[ $key ] = wpcw_add_number_precision_deep( $subvalue, $round );
		}
	} else {
		$value = wpcw_add_number_precision( $value, $round );
	}

	return $value;
}

/**
 * Remove precision from an array of number and return an array of int.
 *
 * @since 4.5.0
 *
 * @param array $value Number to add precision to.
 *
 * @return int
 */
function wpcw_remove_number_precision_deep( $value ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $subvalue ) {
			$value[ $key ] = wpcw_remove_number_precision_deep( $subvalue );
		}
	} else {
		$value = wpcw_remove_number_precision( $value );
	}

	return $value;
}

/**
 * Round a number.
 *
 * @param float $decimal
 *
 * @return float
 */
function wpcw_round( $value ) {
	$value = is_string( $value ) ? floatval( $value ) : $value;

	return round( $value, wpcw_get_rounding_precision() );
}

/**
 * Round Discount.
 *
 * @since 4.5.0
 *
 * @param double $value Amount to round.
 * @param int    $precision DP to round.
 *
 * @return float
 */
function wpcw_round_discount( $value, $precision ) {
	if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
		return round( $value, $precision, 2 ); // phpcs:ignore PHPCompatibility.PHP.NewFunctionParameters.round_modeFound
	} else {
		return round( $value, $precision );
	}
}

/**
 * Get Upload Directory Path.
 *
 * @since 4.3.0
 *
 * @retun string The upload directory path.
 */
function wpcw_get_upload_directory_path() {
	return apply_filters( 'wpcw_upload_dir_path', trailingslashit( WP_CONTENT_DIR . '/wpcourseware_uploads' ) );
}

/**
 * Create WP Courseware Upload Directory.
 *
 * @since 4.3.0
 */
function wpcw_create_upload_directory() {
	$path = wpcw_get_upload_directory_path();

	if ( ! file_exists( $path ) ) {
		@mkdir( $path, 0777, true );
	}

	// Create an empty index page to stop directory listings.
	if ( file_exists( $path ) ) {
		touch( $path . 'index.php' );
	}
}

/**
 * Remove Flush Rewrite Rules Flag.
 *
 * @since 4.4.0
 */
function wpcw_remove_flush_rewrite_rules_flag() {
	delete_option( 'wpcw_flush_rules' );
}

/**
 * Enable Flush Rules Flag.
 *
 * @since 4.4.0
 */
function wpcw_enable_flush_rewrite_rules_flag() {
	update_option( 'wpcw_flush_rules', 'yes' );
}

/**
 * Flush Rewrite Rules.
 *
 * @since 4.4.0
 *
 * @param bool $force True if we want to force a flush.
 */
function wpcw_flush_rewrite_rules( $force = false ) {
	$flush_rules = get_option( 'wpcw_flush_rules' );

	if ( ! $flush_rules || 'yes' === $flush_rules || true === $force ) {
		add_action( 'shutdown', function () {
			update_option( 'wpcw_flush_rules', 'no' );
			flush_rewrite_rules( false );
		} );
	}
}

add_action( 'wpcw_flush_rewrite_rules', 'wpcw_flush_rewrite_rules' );

/**
 * Determines if a page, identified by the specified ID, exist
 * within the WordPress database.
 *
 * @since 4.3.0
 *
 * @param int $id The ID of the post to check
 *
 * @return bool True if the post exists; otherwise, false.
 */
function wpcw_page_exists( $id ) {
	$post_status = get_post_status( $id );

	return ( 0 !== $id && is_string( $post_status ) && 'publish' === $post_status ) ? true : false;
}

/**
 * Get Page Id.
 *
 * Used to retrieve the pages for:
 * 'courses', 'account', 'checkout', 'terms'
 *
 * @since 4.3.0
 *
 * @param string $page The page slug.
 *
 * @return int $page_id The page id, 0 if false.
 */
function wpcw_get_page_id( $page ) {
	$page_id = 0;

	switch ( $page ) {
		case 'courses' :
			$page_id = wpcw_get_setting( 'courses_page' );
			break;
		case 'account' :
			$page_id = wpcw_get_setting( 'account_page' );
			break;
		case 'checkout' :
			$page_id = wpcw_get_setting( 'checkout_page' );
			break;
		case 'order-received' :
			$page_id = wpcw_get_setting( 'order_received_page' );
			break;
		case 'order-failed' :
			$page_id = wpcw_get_setting( 'order_failed_page' );
			break;
		case 'terms' :
			$page_id = wpcw_get_setting( 'terms_page' );
			break;
		case 'privacy' :
			$page_id = wpcw_get_setting( 'privacy_page' );
			break;
		default :
			break;
	}

	return $page_id ? absint( $page_id ) : - 1;
}

/**
 * Get Page Permaink.
 *
 * @since 4.3.0
 *
 * @param string $page The page slug.
 *
 * @return string The page permalink.
 */
function wpcw_get_page_permalink( $page ) {
	$page_id   = wpcw_get_page_id( $page );
	$permalink = 0 < $page_id ? get_permalink( $page_id ) : get_home_url();

	return apply_filters( 'wpcw_get_' . $page . '_page_permalink', esc_url_raw( $permalink ) );
}

/**
 * Recursively Get Page Children
 *
 * @since 4.4.0
 *
 * @author WooCommerce
 *
 * @param int $page_id Page ID.
 *
 * @return int[] The page ids.
 */
function wpcw_get_page_children( $page_id ) {
	$page_ids = get_posts(
		array(
			'post_parent' => $page_id,
			'post_type'   => 'page',
			'numberposts' => - 1, // @codingStandardsIgnoreLine
			'post_status' => 'any',
			'fields'      => 'ids',
		)
	);

	if ( ! empty( $page_ids ) ) {
		foreach ( $page_ids as $page_id ) {
			$page_ids = array_merge( $page_ids, wpcw_get_page_children( $page_id ) );
		}
	}

	return $page_ids;
}

/**
 * Get Endpoint Url
 *
 * Gets the URL for an endpoint, which varies depending on permalink settings.
 *
 * @since 4.3.0
 *
 * @param string $endpoint The endpoint slug.
 * @param string $value The query param value.
 * @param string $permalink The permalink.
 *
 * @return string The endpoint url.
 */
function wpcw_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	if ( ! $permalink ) {
		$permalink = get_permalink();
	}

	$query_vars = wpcw()->query->get_query_vars();
	$endpoint   = ! empty( $query_vars[ $endpoint ] ) ? $query_vars[ $endpoint ] : $endpoint;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $permalink, '?' ) ) {
			$query_string = '?' . wp_parse_url( $permalink, PHP_URL_QUERY );
			$permalink    = current( explode( '?', $permalink ) );
		} else {
			$query_string = '';
		}

		$url = trailingslashit( $permalink ) . trailingslashit( $endpoint );

		if ( $value ) {
			$url .= trailingslashit( $value );
		}

		$url .= $query_string;
	} else {
		$url = add_query_arg( $endpoint, $value, $permalink );
	}

	return apply_filters( 'wpcw_get_endpoint_url', $url, $endpoint, $value, $permalink );
}

/**
 * Maybe Define a Constant.
 *
 * Define a constant if it hasn't already been defined.
 *
 * @since 4.3.0
 *
 * @param string $name The constant name.
 * @param string $value The constant value.
 */
function wpcw_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Set a Cookie.
 *
 * @since 4.3.0
 *
 * @param string  $name Name of the cookie being set.
 * @param string  $value Value of the cookie.
 * @param integer $expire Expiry of the cookie.
 * @param bool    $secure Whether the cookie should be served only over https.
 */
function wpcw_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'wpcw_cookie_httponly', false, $name, $value, $expire, $secure ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	}
}

/**
 * No Cache Headers.
 *
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since 4.3.0
 */
function wpcw_nocache_headers() {
	wpcw()->cache->set_nocache_constants();
	nocache_headers();
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 4.3.0
 *
 * @param int $limit Time limit.
 */
function wpcw_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( $limit );
	}
}

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments blacklist some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blacklisted functions.
 *
 * @since 4.3.0
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 */
function wpcw_print_r( $expression, $return = false ) {
	$alternatives = array(
		array(
			'func' => 'print_r',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'var_export',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'json_encode',
			'args' => array( $expression ),
		),
		array(
			'func' => 'serialize',
			'args' => array( $expression ),
		),
	);

	$alternatives = apply_filters( 'wpcw_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = call_user_func_array( $alternative['func'], $alternative['args'] );
			if ( $return ) {
				return $res;
			} else {
				echo $res;

				return true;
			}
		}
	}

	return false;
}

/**
 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
 *
 * Do not use for redirects, use {@see wp_get_referer()} instead.
 *
 * @since 4.3.0
 *
 * @return string|false Referer URL on success, false on failure.
 */
function wpcw_get_raw_referer() {
	if ( function_exists( 'wp_get_raw_referer' ) ) {
		return wp_get_raw_referer();
	}

	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		return wp_unslash( $_REQUEST['_wp_http_referer'] );
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		return wp_unslash( $_SERVER['HTTP_REFERER'] );
	}

	return false;
}

/**
 * Filter local testing api home url.
 *
 * @since 4.3.0
 *
 * @param string $url The home url.
 * @param string $path The url path.
 *
 * @return string
 */
function wpcw_maybe_filter_home_url( $url, $path, $orig_scheme ) {
	$url = ( WPCW_LOCAL_TESTING && '' !== WPCW_LOCAL_URL ) ? WPCW_LOCAL_URL : $url;

	if ( $path && is_string( $path ) ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	$url = set_url_scheme( $url, $orig_scheme );

	return $url;
}

/**
 * Filter local testing api home url.
 *
 * @since 4.3.0
 *
 * @param string $url The home url.
 * @param string $path The url path.
 *
 * @return string
 */
function wpcw_maybe_change_home_url( $url ) {
	$is_ssl = is_ssl() ? 'https' : 'http';

	$url = set_url_scheme( $url, $is_ssl );
	$url = ( WPCW_LOCAL_TESTING && '' !== WPCW_LOCAL_URL ) ? str_replace( trailingslashit( home_url( '', $is_ssl ) ), trailingslashit( WPCW_LOCAL_URL ), $url ) : $url;

	$url = set_url_scheme( $url, is_ssl() ? 'https' : 'http' );

	return $url;
}

/**
 * Get E-Commerce Integrations.
 *
 * @since 4.4.5
 *
 * @return array The array of ecommerce integrations.
 */
function wpcw_get_ecommerce_integrations() {
	return apply_filters( 'wpcw_ecommerce_integrations', array(
		'easy-digital-downloads/easy-digital-downloads.php',
		'woocommerce/woocommerce.php',
		'learnpress/learnpress.php',
		'memberpress/memberpress.php',
		'paid-memberships-pro/paid-memberships-pro.php',
		'easy-digital-downloads-addon-for-wp-courseware/wp-courseware-edd.php',
		'magic-member-addon-for-wp-courseware/wp-courseware-magic-members.php',
		'membermouse-addon-for-wp-courseware/wp-courseware-member-mouse.php',
		'memberpress-addon-for-wp-courseware/wp-courseware-memberpress.php',
		'membersonic-addon-for-wp-courseware/wp-courseware-membersonic.php',
		'om-addon-for-wp-courseware/wp-courseware-optimizemember.php',
		'paid-memberships-pro-for-wp-courseware/wp-courseware-pmpro.php',
		'premise-addon-for-wp-courseware/wp-courseware-premise.php',
		's2member-addon-for-wp-courseware/wp-courseware-s2-member.php',
		'wishlist-member-addon-for-wp-courseware/wp-courseware-wishlist-member.php',
		'woo-commerce-addon-for-wp-courseware/wp-courseware-woo-commerce.php',
	) );
}

/**
 * Is E-commerce Integration Active?
 *
 * @since 4.4.5
 *
 * @return bool True if a e-commerce integration is active, false otherwise.
 */
function wpcw_is_ecommerce_integration_active() {
	$ecommerce_integrations = wpcw_get_ecommerce_integrations();

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	foreach ( $ecommerce_integrations as $plugin ) {
		if ( is_plugin_active( $plugin ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Get Membership Integrations.
 *
 * @since 4.4.5
 *
 * @return array The array of membership integrations.
 */
function wpcw_get_membership_integrations() {
	return apply_filters( 'wpcw_membership_integrations', array(
		'easy-digital-downloads-addon-for-wp-courseware/wp-courseware-edd.php',
		'magic-member-addon-for-wp-courseware/wp-courseware-magic-members.php',
		'membermouse-addon-for-wp-courseware/wp-courseware-member-mouse.php',
		'memberpress-addon-for-wp-courseware/wp-courseware-memberpress.php',
		'membersonic-addon-for-wp-courseware/wp-courseware-membersonic.php',
		'om-addon-for-wp-courseware/wp-courseware-optimizemember.php',
		'paid-memberships-pro-for-wp-courseware/wp-courseware-pmpro.php',
		'premise-addon-for-wp-courseware/wp-courseware-premise.php',
		's2member-addon-for-wp-courseware/wp-courseware-s2-member.php',
		'wishlist-member-addon-for-wp-courseware/wp-courseware-wishlist-member.php',
		'woo-commerce-addon-for-wp-courseware/wp-courseware-woo-commerce.php',
	) );
}

/**
 * Is Membership Integration Active?
 *
 * @since 4.4.5
 *
 * @return bool True if a membership integration is active, false otherwise.
 */
function wpcw_is_membership_integration_active() {
	$membership_integrations = wpcw_get_membership_integrations();

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	foreach ( $membership_integrations as $plugin ) {
		if ( is_plugin_active( $plugin ) ) {
			return true;
		}
	}

	return false;
}
