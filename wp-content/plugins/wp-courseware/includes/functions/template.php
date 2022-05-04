<?php
/**
 * WP Courseware Template Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Template.
 *
 * Pass attributes and including the file.
 *
 * @since 4.3.0
 *
 * @param string $template_name The template name.
 * @param array $args The template arguments. (default: array).
 * @param string $template_path The template path. (default: '').
 * @param string $default_path The template default path. (default: '').
 */
function wpcw_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	$located = wpcw_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		/* translators: %s template */
		wpcw_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'wp-courseware' ), '<code>' . $located . '</code>' ), '4.3.0' );
		return;
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'wpcw_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'wpcw_before_template_part', $template_name, $template_path, $located, $args );

	include $located;

	do_action( 'wpcw_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Get Template Html.
 *
 * @see wpcw_get_template
 *
 * @since 4.3.0
 *
 * @param string $template_name The template name.
 * @param array $args The template arguments. (default: array).
 * @param string $template_path The template path. (default: '').
 * @param string $default_path The default template path. (default: '').
 *
 * @return string
 */
function wpcw_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	wpcw_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}

/**
 * Locate Template.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @since 4.3.0
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path Default path. (default: '').
 *
 * @return string The template path string.
 */
function wpcw_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = wpcw()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = WPCW_TEMPLATES_PATH;
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/.
	if ( ! $template || WPCW_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'wpcw_locate_template', $template, $template_name, $template_path );
}

/**
 * Get Theme Template File.
 *
 * @since 4.3.0
 *
 * @param string $template The theme email template.
 *
 * @return string The template File.
 */
function wpcw_get_theme_template_path() {
	return apply_filters( 'wpcw_theme_template_path', trailingslashit( get_stylesheet_directory() ) . wpcw()->template_path() );
}

/**
 * Get Theme Template File.
 *
 * @since 4.3.0
 *
 * @param string $template The theme email template.
 *
 * @return string The template File.
 */
function wpcw_get_theme_template_file( $template ) {
	return wpcw_get_theme_template_path() . $template;
}

/**
 * Implode and escape HTML attributes for output.
 *
 * @since 4.3.0
 *
 * @param array $raw_attributes Attribute name value pairs.
 *
 * @return string
 */
function wpcw_implode_html_attributes( $raw_attributes ) {
	$attributes = array();

	foreach ( $raw_attributes as $name => $value ) {
		$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}

	return implode( ' ', $attributes );
}

/**
 * Get Logout Url.
 *
 * @since 4.3.0
 *
 * @param string $redirect Redirect URL.
 *
 * @return string
 */
function wpcw_logout_url( $redirect = '' ) {
	$logout_endpoint = wpcw_get_setting( 'student_logout_endpoint', 'student-logout' );
	$redirect        = $redirect ? $redirect : wpcw_get_page_permalink( 'account' );

	if ( $logout_endpoint ) {
		return wp_nonce_url( wpcw_get_endpoint_url( 'student-logout', '', $redirect ), 'student-logout' );
	} else {
		return wp_logout_url( $redirect );
	}
}

/**
 * WP Courseware Login Form.
 *
 * @since 4.3.0
 *
 * @param array $args The login form arguments.
 */
if ( ! function_exists( 'wpcw_login_form' ) ) {
	function wpcw_login_form( $args = array() ) {
		$defaults = array(
			'message'  => '',
			'redirect' => '',
			'hidden'   => false,
		);

		$args = wp_parse_args( $args, $defaults );

		wpcw_get_template( 'common/form-login.php', $args );
	}
}

/**
 * Outputs a Form Field.
 *
 * @since 4.3.0
 *
 * @param string $key The form field key.
 * @param mixed $args The form field arguments.
 * @param string $value The form field value.
 *
 * @return string The html for the form field.
 */
if ( ! function_exists( 'wpcw_form_field' ) ) {
	function wpcw_form_field( $key, $args, $value = null ) {
		$defaults = array(
			'type'              => 'text',
			'label'             => '',
			'description'       => '',
			'placeholder'       => '',
			'maxlength'         => false,
			'required'          => false,
			'autocomplete'      => false,
			'id'                => $key,
			'class'             => array(),
			'label_class'       => array(),
			'input_class'       => array(),
			'return'            => false,
			'options'           => array(),
			'custom_attributes' => array(),
			'validate'          => array(),
			'default'           => '',
			'autofocus'         => '',
			'priority'          => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'wpcw_form_field_args', $args, $key, $value );

		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'wp-courseware' ) . '">*</abbr>';
		} else {
			$required = '';
		}

		if ( is_string( $args['label_class'] ) ) {
			$args['label_class'] = array( $args['label_class'] );
		}

		if ( is_null( $value ) ) {
			$value = $args['default'];
		}

		// Custom attribute handling.
		$custom_attributes         = array();
		$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

		if ( $args['maxlength'] ) {
			$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
		}

		if ( ! empty( $args['autocomplete'] ) ) {
			$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
		}

		if ( true === $args['autofocus'] ) {
			$args['custom_attributes']['autofocus'] = 'autofocus';
		}

		if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
			foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		if ( ! empty( $args['validate'] ) ) {
			foreach ( $args['validate'] as $validate ) {
				$args['class'][] = 'validate-' . $validate;
			}
		}

		$field           = '';
		$label_id        = $args['id'];
		$sort            = $args['priority'] ? $args['priority'] : '';
		$field_container = '<p class="wpcw-form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

		switch ( $args['type'] ) {
			case 'country':
				$countries = wpcw()->countries->get_allowed_countries();

				if ( 1 === count( $countries ) ) {
					$field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

					$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';
				} else {
					$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="wpcw-select wpcw-country-to-state wpcw-country-select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '"><option value="">' . esc_html__( 'Select a country&hellip;', 'wp-courseware' ) . '</option>';

					foreach ( $countries as $ckey => $cvalue ) {
						$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
					}

					$field .= '</select>';

					$field .= '<noscript><button type="submit" name="wpcw_checkout_update_totals" value="' . esc_attr__( 'Update country', 'wp-courseware' ) . '">' . esc_html__( 'Update country', 'wp-courseware' ) . '</button></noscript>';
				}

				break;
			case 'state':
				/* Get country this state field is representing */
				$for_country = wpcw()->checkout->get_posted_value( 'billing_country' );
				$states      = wpcw()->countries->get_states( $for_country );

				if ( is_array( $states ) && empty( $states ) ) {
					$field_container = '<p class="wpcw-form-row %1$s" id="%2$s" style="display: none">%3$s</p>';

					$field .= '<input type="hidden" class="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '" readonly="readonly" />';
				} elseif ( ! is_null( $for_country ) && is_array( $states ) ) {
					$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="wpcw-state-select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
						<option value="">' . esc_html__( 'Select a state&hellip;', 'wp-courseware' ) . '</option>';

					foreach ( $states as $ckey => $cvalue ) {
						$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
					}

					$field .= '</select>';
				} else {
					$field .= '<input type="text" class="wpcw-input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' />';
				}

				break;
			case 'textarea':
				$field .= '<textarea name="' . esc_attr( $key ) . '" class="wpcw-input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) . ( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $value ) . '</textarea>';

				break;
			case 'checkbox':
				$field = '<label class="checkbox ' . implode( ' ', $args['label_class'] ) . '" ' . implode( ' ', $custom_attributes ) . '>
						<input type="checkbox" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ' . $args['label'] . $required . '</label>';

				break;
			case 'password':
			case 'text':
			case 'email':
			case 'tel':
			case 'number':
				$field .= '<input type="' . esc_attr( $args['type'] ) . '" class="wpcw-input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

				break;
			case 'select':
				$field   = '';
				$options = '';

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						if ( '' === $option_key ) {
							// If we have a blank option, wpcwselect2 needs a placeholder.
							if ( empty( $args['placeholder'] ) ) {
								$args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'wp-courseware' );
							}
							$custom_attributes[] = 'data-allow_clear="true"';
						}
						$options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_attr( $option_text ) . '</option>';
					}

					$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="wpcw-select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
							' . $options . '
						</select>';
				}

				break;
			case 'radio':
				$label_id = current( array_keys( $args['options'] ) );

				if ( ! empty( $args['options'] ) ) {
					foreach ( $args['options'] as $option_key => $option_text ) {
						$field .= '<input type="radio" class="wpcw-input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" ' . implode( ' ', $custom_attributes ) . ' id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
						$field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="wpcw-radio ' . implode( ' ', $args['label_class'] ) . '">' . $option_text . '</label>';
					}
				}

				break;
		}

		if ( ! empty( $field ) ) {
			$field_html = '';

			if ( $args['label'] && 'checkbox' !== $args['type'] ) {
				$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
			}

			$field_html .= $field;

			if ( $args['description'] ) {
				$field_html .= '<span class="description">' . esc_html( $args['description'] ) . '</span>';
			}

			$container_class = esc_attr( implode( ' ', $args['class'] ) );
			$container_id    = esc_attr( $args['id'] ) . '_field';
			$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
		}

		$field = apply_filters( 'wpcw_form_field_' . $args['type'], $field, $key, $args, $value );

		if ( $args['return'] ) {
			return $field;
		} else {
			echo $field;
		}
	}
}
