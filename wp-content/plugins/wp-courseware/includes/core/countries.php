<?php
/**
 * WP Courseware Countries.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Cache.
 *
 * @since 4.3.0
 */
final class Countries {

	/**
	 * @var array Locale.
	 * @since 4.3.0
	 */
	public $locale = array();

	/**
	 * @var array Address formats.
	 * @since 4.3.0
	 */
	public $address_formats = array();

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $key Key.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( 'countries' === $key ) {
			return $this->get_countries();
		} elseif ( 'states' === $key ) {
			return $this->get_states();
		}
	}

	/**
	 * Get all countries.
	 *
	 * @since 4.3.0
	 *
	 * @return array An array of all coutries.
	 */
	public function get_countries() {
		if ( empty( $this->countries ) ) {
			$this->countries = apply_filters( 'wpcw_countries', include WPCW_COMMON_PATH . 'countries.php' );
			if ( apply_filters( 'wpcw_sort_countries', true ) ) {
				asort( $this->countries );
			}
		}

		return $this->countries;
	}

	/**
	 * Get all continents.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_continents() {
		if ( empty( $this->continents ) ) {
			$this->continents = apply_filters( 'wpcw_continents', include WPCW_COMMON_PATH . 'continents.php' );
		}

		return $this->continents;
	}

	/**
	 * Get continent code for a country code.
	 *
	 * @since 4.3.0
	 *
	 * @param string $cc Continent code.
	 *
	 * @return string
	 */
	public function get_continent_code_for_country( $cc ) {
		$cc                 = trim( strtoupper( $cc ) );
		$continents         = $this->get_continents();
		$continents_and_ccs = wp_list_pluck( $continents, 'countries' );

		foreach ( $continents_and_ccs as $continent_code => $countries ) {
			if ( false !== array_search( $cc, $countries ) ) {
				return $continent_code;
			}
		}

		return '';
	}

	/**
	 * Load the states.
	 *
	 * @since 4.3.0
	 */
	public function load_country_states() {
		global $wpcw_states;

		// States set to array() are blank i.e. the country has no use for the state field.
		$wpcw_states = array(
			'AF' => array(),
			'AT' => array(),
			'AX' => array(),
			'BE' => array(),
			'BI' => array(),
			'CZ' => array(),
			'DE' => array(),
			'DK' => array(),
			'EE' => array(),
			'FI' => array(),
			'FR' => array(),
			'GP' => array(),
			'GF' => array(),
			'IS' => array(),
			'IL' => array(),
			'IM' => array(),
			'KR' => array(),
			'KW' => array(),
			'LB' => array(),
			'MQ' => array(),
			'NL' => array(),
			'NO' => array(),
			'PL' => array(),
			'PT' => array(),
			'RE' => array(),
			'SG' => array(),
			'SK' => array(),
			'SI' => array(),
			'LK' => array(),
			'SE' => array(),
			'VN' => array(),
			'YT' => array(),
		);

		// Load only the state files the shop owner wants/needs.
		$allowed = $this->get_allowed_countries();

		if ( ! empty( $allowed ) ) {
			foreach ( $allowed as $code => $country ) {
				if ( ! isset( $wpcw_states[ $code ] ) && file_exists( WPCW_COMMON_PATH . 'states/' . $code . '.php' ) ) {
					include WPCW_COMMON_PATH . 'states/' . $code . '.php';
				}
			}
		}

		$this->states = apply_filters( 'wpcw_states', $wpcw_states );
	}

	/**
	 * Get the states for a country.
	 *
	 * @since 4.3.0
	 *
	 * @param string $cc Country code.
	 *
	 * @return false|array of states
	 */
	public function get_states( $cc = null ) {
		if ( empty( $this->states ) ) {
			$this->load_country_states();
		}

		if ( ! is_null( $cc ) ) {
			return isset( $this->states[ $cc ] ) ? $this->states[ $cc ] : false;
		} else {
			return $this->states;
		}
	}

	/**
	 * Get base country.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_base_country() {
		$country = wpcw_get_setting( 'base_country', 'US' );

		return apply_filters( 'wpcw_countries_base_country', $country );
	}

	/**
	 * Get base state.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_base_state() {
		$state = wpcw_get_setting( 'base_state', false );

		return apply_filters( 'wpcw_countries_base_state', $state );
	}

	/**
	 * Get the allowed countries.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_allowed_countries() {
		return apply_filters( 'wpcw_countries_allowed_countries', $this->countries );
	}

	/**
	 * Get allowed country states.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_allowed_country_states() {
		return apply_filters( 'wpcw_countries_allowed_country_states', $this->states );
	}

	/**
	 * Gets an array of countries in the EU.
	 *
	 * MC (monaco) and IM (isle of man, part of UK) also use VAT.
	 *
	 * @since 4.3.0
	 *
	 * @param string $type Type of countries to retrieve. Blank for EU member countries. eu_vat for EU VAT countries.
	 *
	 * @return string[]
	 */
	public function get_european_union_countries( $type = '' ) {
		$countries = array( 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK' );

		if ( 'eu_vat' === $type ) {
			$countries[] = 'MC';
			$countries[] = 'IM';
		}

		return $countries;
	}

	/**
	 * Prefix certain countries with 'the'.
	 *
	 * @since 4.3.0
	 *
	 * @param string $country_code Country code.
	 *
	 * @return string
	 */
	public function estimated_for_prefix( $country_code = '' ) {
		$country_code = $country_code ? $country_code : $this->get_base_country();
		$countries    = array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' );
		$return       = in_array( $country_code, $countries ) ? __( 'the', 'wp-courseware' ) . ' ' : '';

		return apply_filters( 'wpcw_countries_estimated_for_prefix', $return, $country_code );
	}

	/**
	 * Correctly name tax in some countries VAT on the frontend.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function tax_or_vat() {
		$return = in_array( $this->get_base_country(), array_merge( $this->get_european_union_countries( 'eu_vat' ), array( 'NO' ) ) ) ? __( 'VAT', 'wp-courseware' ) : __( 'Tax', 'wp-courseware' );

		return apply_filters( 'wpcw_countries_tax_or_vat', $return );
	}

	/**
	 * Include the Inc Tax label.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function inc_tax_or_vat() {
		$return = in_array( $this->get_base_country(), array_merge( $this->get_european_union_countries( 'eu_vat' ), array( 'NO' ) ) ) ? __( '(incl. VAT)', 'wp-courseware' ) : __( '(incl. tax)', 'wp-courseware' );

		return apply_filters( 'wpcw_countries_inc_tax_or_vat', $return );
	}

	/**
	 * Include the Ex Tax label.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function ex_tax_or_vat() {
		$return = in_array( $this->get_base_country(), array_merge( $this->get_european_union_countries( 'eu_vat' ), array( 'NO' ) ) ) ? __( '(ex. VAT)', 'wp-courseware' ) : __( '(ex. tax)', 'wp-courseware' );

		return apply_filters( 'wpcw_countries_ex_tax_or_vat', $return );
	}

	/**
	 * Outputs the list of countries and states for use in dropdown boxes.
	 *
	 * @since 4.3.0
	 *
	 * @param string $selected_country Selected country.
	 * @param string $selected_state Selected state.
	 * @param bool   $escape If should escape HTML.
	 */
	public function country_dropdown_options( $selected_country = '', $selected_state = '', $escape = false ) {
		if ( $this->countries ) {
			foreach ( $this->countries as $key => $value ) {
				$states = $this->get_states( $key );
				if ( $states ) {
					echo '<optgroup label="' . esc_attr( $value ) . '">';
					foreach ( $states as $state_key => $state_value ) {
						echo '<option value="' . esc_attr( $key ) . ':' . esc_attr( $state_key ) . '"';

						if ( $selected_country === $key && $selected_state === $state_key ) {
							echo ' selected="selected"';
						}

						echo '>' . esc_html( $value ) . ' &mdash; ' . ( $escape ? esc_js( $state_value ) : $state_value ) . '</option>';
					}
					echo '</optgroup>';
				} else {
					echo '<option';
					if ( $selected_country === $key && '*' === $selected_state ) {
						echo ' selected="selected"';
					}
					echo ' value="' . esc_attr( $key ) . '">' . ( $escape ? esc_js( $value ) : $value ) . '</option>';
				}
			}
		}
	}

	/**
	 * Get country address formats.
	 *
	 * These define how addresses are formatted for display in various countries.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_address_formats() {
		if ( empty( $this->address_formats ) ) {
			$this->address_formats = apply_filters(
				'wpcw_localisation_address_formats', array(
					'default' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
					'AU'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
					'AT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'BE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'CA'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
					'CH'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'CL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{state}\n{postcode} {city}\n{country}",
					'CN'      => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
					'CZ'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'DE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'EE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'FI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'DK'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'FR'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
					'HK'      => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
					'HU'      => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
					'IN'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} - {postcode}\n{state}, {country}",
					'IS'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'IT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
					'JP'      => "{postcode}\n{state} {city} {address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}",
					'TW'      => "{company}\n{last_name} {first_name}\n{address_1}\n{address_2}\n{state}, {city} {postcode}\n{country}",
					'LI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'NL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'NZ'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
					'NO'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'PL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'PT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'SK'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'SI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'ES'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
					'SE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'TR'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
					'US'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
					'VN'      => "{name}\n{company}\n{address_1}\n{city}\n{country}",
				)
			);
		}

		return $this->address_formats;
	}

	/**
	 * Get country address format.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Arguments.
	 *
	 * @return string
	 */
	public function get_formatted_address( $args = array() ) {
		$default_args = array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);

		$args    = array_map( 'trim', wp_parse_args( $args, $default_args ) );
		$state   = $args['state'];
		$country = $args['country'];

		// Get all formats.
		$formats = $this->get_address_formats();

		// Get format for the address' country.
		$format = ( $country && isset( $formats[ $country ] ) ) ? $formats[ $country ] : $formats['default'];

		// Handle full country name.
		$full_country = ( isset( $this->countries[ $country ] ) ) ? $this->countries[ $country ] : $country;

		// Country is not needed if the same as base.
		if ( $country === $this->get_base_country() && ! apply_filters( 'wpcw_formatted_address_force_country_display', false ) ) {
			$format = str_replace( '{country}', '', $format );
		}

		// Handle full state name.
		$full_state = ( $country && $state && isset( $this->states[ $country ][ $state ] ) ) ? $this->states[ $country ][ $state ] : $state;

		// Substitute address parts into the string.
		$replace = array_map(
			'esc_html', apply_filters(
				'wpcw_formatted_address_replacements', array(
				'{first_name}'       => $args['first_name'],
				'{last_name}'        => $args['last_name'],
				'{name}'             => $args['first_name'] . ' ' . $args['last_name'],
				'{company}'          => $args['company'],
				'{address_1}'        => $args['address_1'],
				'{address_2}'        => $args['address_2'],
				'{city}'             => $args['city'],
				'{state}'            => $full_state,
				'{postcode}'         => $args['postcode'],
				'{country}'          => $full_country,
				'{first_name_upper}' => strtoupper( $args['first_name'] ),
				'{last_name_upper}'  => strtoupper( $args['last_name'] ),
				'{name_upper}'       => strtoupper( $args['first_name'] . ' ' . $args['last_name'] ),
				'{company_upper}'    => strtoupper( $args['company'] ),
				'{address_1_upper}'  => strtoupper( $args['address_1'] ),
				'{address_2_upper}'  => strtoupper( $args['address_2'] ),
				'{city_upper}'       => strtoupper( $args['city'] ),
				'{state_upper}'      => strtoupper( $full_state ),
				'{state_code}'       => strtoupper( $state ),
				'{postcode_upper}'   => strtoupper( $args['postcode'] ),
				'{country_upper}'    => strtoupper( $full_country ),
			), $args
			)
		);

		$formatted_address = str_replace( array_keys( $replace ), $replace, $format );

		// Clean up white space.
		$formatted_address = preg_replace( '/  +/', ' ', trim( $formatted_address ) );
		$formatted_address = preg_replace( '/\n\n+/', "\n", $formatted_address );

		// Break newlines apart and remove empty lines/trim commas and white space.
		$formatted_address = array_filter( array_map( array( $this, 'trim_formatted_address_line' ), explode( "\n", $formatted_address ) ) );

		// Add html breaks.
		$formatted_address = implode( '<br/>', $formatted_address );

		// We're done!
		return $formatted_address;
	}

	/**
	 * Trim white space and commas off a line.
	 *
	 * @since 4.3.0
	 *
	 * @param string $line Line.
	 *
	 * @return string
	 */
	private function trim_formatted_address_line( $line ) {
		return trim( $line, ', ' );
	}

	/**
	 * Returns the biling address fields we show by default. This can be filtered later on.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_default_billing_address_fields() {
		$fields = array(
			'address_1' => array(
				'label'        => esc_html__( 'Street address', 'wp-courseware' ),
				/* translators: use local order of street name and house number. */
				'placeholder'  => esc_attr__( 'House number and street name', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-wide', 'wpcw-address-field' ),
				'autocomplete' => 'address-line1',
				'priority'     => 50,
			),
			'address_2' => array(
				'placeholder'  => esc_attr__( 'Apartment, suite, unit etc. (optional)', 'wp-courseware' ),
				'class'        => array( 'wpcw-form-row-wide', 'wpcw-address-field' ),
				'required'     => false,
				'autocomplete' => 'address-line2',
				'priority'     => 60,
			),
			'city'      => array(
				'label'        => esc_html__( 'Town / City', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'Town / City', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-first', 'wpcw-address-field' ),
				'autocomplete' => 'address-level2',
				'priority'     => 70,
			),
			'postcode'  => array(
				'label'        => esc_html__( 'Postcode / ZIP', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'Postcode / ZIP', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-last', 'wpcw-address-field' ),
				'validate'     => array( 'postcode' ),
				'autocomplete' => 'postal-code',
				'priority'     => 90,
			),
			'country'   => array(
				'type'         => 'country',
				'label'        => esc_html__( 'Country', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'Country', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-first', 'wpcw-address-field', 'wpcw-country-field', 'wpcw-select-field-wpcwselect2' ),
				'autocomplete' => 'country',
				'priority'     => 40,
			),
			'state'     => array(
				'type'         => 'state',
				'label'        => esc_html__( 'State / County', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'State / County', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-last', 'wpcw-address-field', 'wpcw-state-field' ),
				'validate'     => array( 'state' ),
				'autocomplete' => 'address-level1',
				'priority'     => 80,
			),
		);

		return apply_filters( 'wpcw_default_billing_address_fields', $fields );
	}

	/**
	 * Get JS selectors for fields which are shown / hidden depending on the locale.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_country_locale_field_selectors() {
		$locale_fields = array(
			'billing_address_1' => '#billing_address_1_field',
			'billing_address_2' => '#billing_address_2_field',
			'billing_state'     => '#billing_state_field',
			'billing_postcode'  => '#billing_postcode_field',
			'billing_city'      => '#billing_city_field',
		);

		return apply_filters( 'wpcw_country_locale_field_selectors', $locale_fields );
	}

	/**
	 * Get country locale settings.
	 *
	 * These locales override the default country selections after a country is chosen.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_country_locale() {
		if ( empty( $this->locale ) ) {
			$this->locale = apply_filters(
				'wpcw_get_country_locale', array(
					'AE' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'AF' => array(
						'state' => array(
							'required' => false,
						),
					),
					'AT' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'AU' => array(
						'city'     => array(
							'label' => __( 'Suburb', 'wp-courseware' ),
						),
						'postcode' => array(
							'label' => __( 'Postcode', 'wp-courseware' ),
						),
						'state'    => array(
							'label' => __( 'State', 'wp-courseware' ),
						),
					),
					'AX' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'BD' => array(
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'label' => __( 'District', 'wp-courseware' ),
						),
					),
					'BE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Province', 'wp-courseware' ),
						),
					),
					'BI' => array(
						'state' => array(
							'required' => false,
						),
					),
					'BO' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'BS' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'CA' => array(
						'state' => array(
							'label' => __( 'Province', 'wp-courseware' ),
						),
					),
					'CH' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Canton', 'wp-courseware' ),
							'required' => false,
						),
					),
					'CL' => array(
						'city'     => array(
							'required' => true,
						),
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'label' => __( 'Region', 'wp-courseware' ),
						),
					),
					'CN' => array(
						'state' => array(
							'label' => __( 'Province', 'wp-courseware' ),
						),
					),
					'CO' => array(
						'postcode' => array(
							'required' => false,
						),
					),
					'CZ' => array(
						'state' => array(
							'required' => false,
						),
					),
					'DE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'DK' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'EE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'FI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'FR' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'GP' => array(
						'state' => array(
							'required' => false,
						),
					),
					'GF' => array(
						'state' => array(
							'required' => false,
						),
					),
					'HK' => array(
						'postcode' => array(
							'required' => false,
						),
						'city'     => array(
							'label' => __( 'Town / District', 'wp-courseware' ),
						),
						'state'    => array(
							'label' => __( 'Region', 'wp-courseware' ),
						),
					),
					'HU' => array(
						'state' => array(
							'label' => __( 'County', 'wp-courseware' ),
						),
					),
					'ID' => array(
						'state' => array(
							'label' => __( 'Province', 'wp-courseware' ),
						),
					),
					'IE' => array(
						'postcode' => array(
							'required' => false,
							'label'    => __( 'Eircode', 'wp-courseware' ),
						),
						'state'    => array(
							'label' => __( 'County', 'wp-courseware' ),
						),
					),
					'IS' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'IL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'IM' => array(
						'state' => array(
							'required' => false,
						),
					),
					'IT' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => true,
							'label'    => __( 'Province', 'wp-courseware' ),
						),
					),
					'JP' => array(
						'state'    => array(
							'label'    => __( 'Prefecture', 'wp-courseware' ),
							'priority' => 66,
						),
						'postcode' => array(
							'priority' => 65,
						),
					),
					'KR' => array(
						'state' => array(
							'required' => false,
						),
					),
					'KW' => array(
						'state' => array(
							'required' => false,
						),
					),
					'LB' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MQ' => array(
						'state' => array(
							'required' => false,
						),
					),
					'NL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Province', 'wp-courseware' ),
						),
					),
					'NZ' => array(
						'postcode' => array(
							'label' => __( 'Postcode', 'wp-courseware' ),
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Region', 'wp-courseware' ),
						),
					),
					'NO' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'NP' => array(
						'state'    => array(
							'label' => __( 'State / Zone', 'wp-courseware' ),
						),
						'postcode' => array(
							'required' => false,
						),
					),
					'PL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'PT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'RE' => array(
						'state' => array(
							'required' => false,
						),
					),
					'RO' => array(
						'state' => array(
							'label'    => __( 'County', 'wp-courseware' ),
							'required' => false,
						),
					),
					'SG' => array(
						'state' => array(
							'required' => false,
						),
					),
					'SK' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'SI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'SR' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'ES' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label' => __( 'Province', 'wp-courseware' ),
						),
					),
					'LI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Municipality', 'wp-courseware' ),
							'required' => false,
						),
					),
					'LK' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MD' => array(
						'state' => array(
							'label' => __( 'Municipality / District', 'wp-courseware' ),
						),
					),
					'SE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'TR' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label' => __( 'Province', 'wp-courseware' ),
						),
					),
					'US' => array(
						'postcode' => array(
							'label' => __( 'ZIP', 'wp-courseware' ),
						),
						'state'    => array(
							'label' => __( 'State', 'wp-courseware' ),
						),
					),
					'GB' => array(
						'postcode' => array(
							'label' => __( 'Postcode', 'wp-courseware' ),
						),
						'state'    => array(
							'label'    => __( 'County', 'wp-courseware' ),
							'required' => false,
						),
					),
					'VN' => array(
						'state'     => array(
							'required' => false,
						),
						'postcode'  => array(
							'priority' => 65,
							'required' => false,
							'hidden'   => false,
						),
						'address_2' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'WS' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'YT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'ZA' => array(
						'state' => array(
							'label' => __( 'Province', 'wp-courseware' ),
						),
					),
					'ZW' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
				)
			);

			// Default Locale Can be filtered to override fields in get_address_fields(). Countries with no specific locale will use default.
			$this->locale['default'] = apply_filters( 'wpcw_get_country_locale_default', $this->get_default_billing_address_fields() );

			// Filter default AND shop base locales to allow overides via a single function. These will be used when changing countries on the checkout.
			if ( ! isset( $this->locale[ $this->get_base_country() ] ) ) {
				$this->locale[ $this->get_base_country() ] = $this->locale['default'];
			}

			$this->locale['default']                   = apply_filters( 'wpcw_get_country_locale_base', $this->locale['default'] );
			$this->locale[ $this->get_base_country() ] = apply_filters( 'wpcw_get_country_locale_base', $this->locale[ $this->get_base_country() ] );
		}

		return $this->locale;
	}

	/**
	 * Apply locale and get billing address fields.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $country Country.
	 *
	 * @return array
	 */
	public function get_billing_address_fields( $country = '', $type = 'billing_' ) {
		if ( ! $country ) {
			$country = $this->get_base_country();
		}

		$fields = $this->get_default_billing_address_fields();
		$locale = $this->get_country_locale();

		if ( isset( $locale[ $country ] ) ) {
			$fields = wpcw_array_overlay( $fields, $locale[ $country ] );
		}

		// Prepend field keys.
		$address_fields = array();

		foreach ( $fields as $key => $value ) {
			if ( 'state' === $key ) {
				$value['country_field'] = $type . 'country';
			}
			$address_fields[ $type . $key ] = $value;
		}

		/**
		 * Filtler: Address Fields by Type.
		 *
		 * @since 4.4.3
		 *
		 * @param array  $address_fields The billing address fields.
		 * @param string $country The country that is set.
		 *
		 * @return array $address_fields The billing address fields.
		 */
		$address_fields = apply_filters( 'wpcw_address_' . $type . 'fields', $address_fields, $country );

		/**
		 * Filter: Address Fields.
		 *
		 * @since 4.4.3
		 *
		 * @param array  $address_fields The billing address fields.
		 * @param string $country The country that is set.
		 *
		 * @return array $address_fields The billing address fields.
		 */
		return apply_filters( 'wpcw_address_fields', $address_fields, $country );
	}
}
