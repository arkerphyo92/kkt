<?php
/**
 * WP Courseware Settings API.
 *
 * Used as a common api between all classes to
 * access settings for WP Courseware.
 *
 * @package WPCW
 * @subpackage Common
 * @since 4.3.0
 */
namespace WPCW\Common;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Settings_Api.
 *
 * @since 4.3.0
 */
abstract class Settings_Api {

	/**
	 * @var string The settings key string.
	 * @since 4.3.0
	 */
	public $key = 'wpcw';

	/**
	 * @var array Array of validation errors.
	 * @since 4.3.0
	 */
	public $errors = array();

	/**
	 * @var array Array of settings values.
	 * @since 4.3.0
	 */
	public $settings = array();

	/**
	 * @var array Array of form fields.
	 * @since 4.3.0
	 */
	public $fields = array();

	/**
	 * @var array The site pages array. Used for the pages field.
	 * @since 4.3.0
	 */
	protected $pages = array();

	/**
	 * Get Settings Key.
	 *
	 * @since 4.3.0
	 *
	 * @return string The settings key.
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Load Settings
	 *
	 * Store all settings in a single database entry
	 * and make sure the $settings array is either the default
	 * or the settings stored in the database.
	 *
	 * @since 4.3.0
	 */
	public function load_settings() {
		$this->settings = get_option( $this->get_key(), array() );

		// If there are no settings defined, use defaults, if registered.
		if ( empty( $this->settings ) ) {
			$fields         = $this->get_fields();
			$this->settings = array_merge( array_fill_keys( array_keys( $fields ), '' ), wp_list_pluck( $fields, 'default' ) );
		}
	}

	/**
	 * Set Settings.
	 *
	 * @since 4.1.0
	 *
	 * @param array $settings The settings array.
	 */
	public function set_settings( $settings ) {
		$this->settings = (array) $settings;
	}

	/**
	 * Get Settings.
	 *
	 * @since 4.3.0
	 *
	 * @return array The settings array.
	 */
	public function get_settings() {
		if ( empty ( $this->settings ) ) {
			$this->load_settings();
		}

		return $this->settings;
	}

	/**
	 * Save Settings.
	 *
	 * If there is an error thrown, this will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the settings were saved.
	 */
	public function save_settings() {
		if ( empty( $this->settings ) ) {
			$this->load_settings();
		}

		$fields    = $this->get_fields();
		$post_data = $this->get_post_data();

		// Save restricted content message text
		if( isset( $post_data['action'] ) && $post_data['action'] == 'wpcw-update-courses-membership' ) {
			foreach( $post_data as $key => $content ) {
				if( 1 === preg_match( "/^wpcw_restricted_content_/", $key ) ) {
					update_option( $key, $content );
				}
			}
			return;
		}

		foreach ( $fields as $key => $field ) {
			if ( ! in_array( $this->get_field_type( $field ), $this->get_excluded_fields() ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		$updated = update_option( $this->get_key(), apply_filters( 'wpcw_settings_api_sanitized_fields_' . $this->get_key(), $this->settings ) );

		/**
		 * Action: After Settings Save.
		 *
		 * @since 4.4.0
		 *
		 * @param array The settings array
		 * @param array $fields The fields array.
		 * @param array $post_data The post data array.
		 */
		do_action( 'wpcw_settings_after_save', $this->settings, $fields, $post_data );

		return $updated;
	}

	/**
	 * Get Setting.
	 *
	 * Gets a setting from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The setting key.
	 * @param mixed  $default_value The default value.
	 *
	 * @return string The value or if blank the default value.
	 */
	public function get_setting( $key, $default_value = null ) {
		if ( empty( $this->settings ) ) {
			$this->load_settings();
		}

		if ( ! isset( $this->settings[ $key ] ) ) {
			$fields = $this->get_fields();

			$this->settings[ $key ] = isset( $fields[ $key ] ) ? $this->get_field_default( $fields[ $key ] ) : '';
		}

		if ( ! is_null( $default_value ) && '' === $this->settings[ $key ] ) {
			$this->settings[ $key ] = $default_value;
		}

		return $this->settings[ $key ];
	}

	/**
	 * Set a setting.
	 *
	 * @since 4.1.0
	 *
	 * @param string $setting The setting name.
	 * @param string $value The setting value.
	 */
	public function set_setting( $key, $value = '' ) {
		if ( empty( $this->settings ) ) {
			$this->load_settings();
		}

		if ( isset( $this->settings[ $key ] ) ) {
			$this->settings[ $key ] = $value;
		}
	}

	/**
	 * Delete a setting.
	 *
	 * @since 4.1.0
	 *
	 * @param stirng $setting The setting name.
	 * @param array  $settings Optional. The default settings to be used.
	 */
	public function delete_setting( $key ) {
		if ( empty( $this->settings ) ) {
			$this->load_settings();
		}

		if ( isset( $this->settings[ $key ] ) ) {
			unset( $this->settings[ $key ] );
		}
	}

	/**
	 * Get Post Data.
	 *
	 * @since 4.3.0
	 *
	 * @return array The POSTed data, to be used to save the settings.
	 */
	public function get_post_data() {
		return $_POST;
	}

	/**
	 * Add Error Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $error The error message to add.
	 */
	public function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * Get Error Messages.
	 *
	 * @since 4.3.0
	 *
	 * @return array The error messages.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Display Admin Error Messages.
	 *
	 * @since 4.3.0
	 */
	public function display_errors() {
		if ( $this->get_errors() ) {
			foreach ( $this->get_errors() as $error ) {
				wpcw_admin_notice( wp_kses_post( $error ), 'error' );
			}
		}
	}

	/**
	 * Get Settings in a JSON format.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_settings_json() {
		return wpcw_convert_array_to_json( array( 'settings' => $this->get_settings() ) );
	}

	/**
	 * Set Fields.
	 *
	 * This should be overloaded in child class
	 * to set the $fields property.
	 *
	 * @since 4.3.0
	 */
	public function set_fields() { /* Overload in child class */ }

	/**
	 * Get Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The form fields after they have been initialized.
	 */
	public function get_fields() {
		$fields = $this->normalilze_fields( $this->fields );

		return apply_filters( 'wpcw_settings_fields', array_map( array( $this, 'set_defaults' ), $fields ) );
	}

	/**
	 * Normalize Fields.
	 *
	 * @since 4.3.0
	 *
	 * @param array $fields The fields array.
	 *
	 * @return array $fields The normalized field array.
	 */
	public function normalilze_fields( $fields ) {
		$normalized_fields = array();

		if ( ! is_array( $fields ) ) {
			return $normalized_fields;
		}

		foreach ( $fields as $key => $field ) {
			if ( isset( $field['component'] ) && $field['component'] ) {
				if ( ! empty( $field['settings'] ) ) {
					foreach ( $field['settings'] as $setting ) {
						if ( isset( $setting['component'] ) && $setting['component'] ) {
							if ( ! empty( $setting['settings'] ) ) {
								foreach ( $setting['settings'] as $sub_setting ) {
									$normalized_fields[ $sub_setting['key'] ] = $sub_setting;
								}
							}
						} elseif ( isset( $setting['key'] ) ) {
							$normalized_fields[ $setting['key'] ] = $setting;
						}
					}
				}
			} elseif ( isset( $field['key'] ) ) {
				$normalized_fields[ $field['key'] ] = $field;
			}
		}

		return $normalized_fields;
	}

	/**
	 * Get Excluded Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of excluded fields.
	 */
	public function get_excluded_fields() {
		$user_excluded_fields = apply_filters( 'wpcw_settings_api_excluded_fields', array(), $this->get_fields(), $this->settings );

		$core_excluded_fields = array( 'heading' );

		return array_merge( $user_excluded_fields, $core_excluded_fields );
	}

	/**
	 * Set Defaults.
	 *
	 * @param array $field The current field.
	 *
	 * @return array $field The modified field.
	 */
	public function set_defaults( $field ) {
		if ( ! isset( $field['default'] ) ) {
			$field['default'] = '';
		}

		return $field;
	}

	/**
	 * Get Field Key.
	 *
	 * Prefix the key if needed.
	 *
	 * @param mixed $key The field key.
	 *
	 * @return string The field key.
	 */
	public function get_field_key( $key ) {
		return wpcw_sanitize_key( $key );
	}

	/**
	 * Get Field Type.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field type string. Defaults to "text" if not set.
	 */
	public function get_field_type( $field ) {
		return empty( $field['type'] ) ? 'text' : $field['type'];
	}

	/**
	 * Get Field Title.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field title string. Defaults to "" if not set.
	 */
	public function get_field_title( $field ) {
		return ! empty( $field['title'] ) ? esc_html( $field['title'] ) : '';
	}

	/**
	 * Get Field Content.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field content string. Defaults to "" if not set.
	 */
	public function get_field_content( $field ) {
		return ! empty( $field['content'] ) ? wp_kses_post( $field['content'] ) : '';
	}

	/**
	 * Get Field Description.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field description. Defaults to "" if not set.
	 */
	public function get_field_desc( $field ) {
		return ! empty( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : '';
	}

	/**
	 * Get Field Description Tooltip.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field description tooltip. Defaults to "" if not set.
	 */
	public function get_field_desc_tip( $field ) {
		return ! empty( $field['desc_tip'] ) ? wp_kses_post( $field['desc_tip'] ) : '';
	}

	/**
	 * Get Field Label.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field label string. Defaults to "" if not set.
	 */
	public function get_field_label( $field ) {
		return ! empty( $field['label'] ) ? wp_kses_post( $field['label'] ) : '';
	}

	/**
	 * Get Field Placeholder.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The field placeholder. Defaults to "" if not set.
	 */
	public function get_field_placeholder( $field ) {
		return ! empty( $field['placeholder'] ) ? esc_html( $field['placeholder'] ) : '';
	}

	/**
	 * Get Field Default Value.
	 *
	 * @since 4.3.0
	 *
	 * @param array $field The current field.
	 *
	 * @return string The default field value. Defaults to "" if not set.
	 */
	public function get_field_default( $field ) {
		return empty( $field['default'] ) ? '' : $field['default'];
	}

	/**
	 * Get Field Value.
	 *
	 * Get a field's posted and validated value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field array.
	 * @param array  $settings The current settings array. Empty if not set.
	 * @param array  $post_data The post data. Empty if not set.
	 *
	 * @return string The field value validated and sanitized.
	 */
	public function get_field_value( $key, $field, $post_data = array() ) {
		$field_type = $this->get_field_type( $field );
		$field_key  = $this->get_field_key( $key );

		if ( ! empty( $post_data ) ) {
			$field_value = isset( $post_data[ $field_key ] ) ? $post_data[ $field_key ] : null;
		} else {
			$field_value = $this->get_setting( $field_key );
		}

		if ( is_null( $field_value ) && ! in_array( $field_type, array( 'checkbox' ) ) ) {
			$field_value = $this->get_setting( $field_key );
		}

		return $this->validate_field( $field_type, $field_key, $field_value );
	}

	/**
	 * Generate Fields HTML.
	 *
	 * Generate the HTML for the fields.
	 *
	 * @since 4.3.0
	 *
	 * @param array $fields The form fields.
	 * @param bool  $echo Should the fields html be echoed or returned.
	 *
	 * @return string The html string for the settings.
	 */
	public function generate_fields_html( $fields = array(), $echo = true ) {
		if ( empty( $fields ) ) {
			return;
		}

		$html = '';

		/**
		 * Filter: Before Generate Fields Html.
		 *
		 * @since 4.4.0
		 *
		 * @param array        $fields The fields array.
		 * @param Settings_Api $this The settings api object.
		 *
		 * @return array $fields The fields array.
		 */
		$fields = apply_filters( 'wpcw_settings_before_generate_fields_html', $fields, $this );

		foreach ( $fields as $id => $field ) {
			$type = $this->get_field_type( $field );

			$key = isset( $field['key'] ) ? $this->get_field_key( $field['key'] ) : '';

			if ( 'hidden' === $type || '' === $key ) {
				continue;
			}

			if ( ! isset( $field['wrapper'] ) || ( isset( $field['wrapper'] ) && $field['wrapper'] ) ) {
				$html .= $this->generate_field_row_start_html( $key, $field );
			}

			if ( method_exists( $this, 'generate_' . $type . '_field_html' ) ) {
				$html .= $this->{'generate_' . $type . '_field_html'}( $key, $field );
			} else {
				$html .= $this->generate_missing_field_type_html( $key, $field );
			}

			if ( ! isset( $field['wrapper'] ) || ( isset( $field['wrapper'] ) && $field['wrapper'] ) ) {
				$html .= $this->generate_field_row_end_html( $key, $field );
			}
		}

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Generate Field Row Html.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The field html.
	 */
	public function generate_field_row_start_html( $key, $field ) {
		$class = ! empty( $field['class'] ) ? wpcw_sanitize_classes( (array) $field['class'], true ) : '';
		$class = ! empty( $class ) ? ' ' . $class : '';

		$field_id      = str_replace( '_', '-', $key );
		$field_type    = sanitize_html_class( $field['type'] );
		$field_classes = $class;

		$condition  = ! empty( $field['condition'] ) ? $field['condition'] : '';
		$conditions = '';

		// Conditions.
		if ( $condition ) {
			$condition_field = isset( $condition['field'] ) ? wpcw_sanitize_key( $condition['field'] ) : '';
			$condition_value = isset( $condition['value'] ) ? $condition['value'] : '';

			if ( is_array( $condition_field ) ) {
				$condition_field = implode( ',', $condition_field );
			}

			if ( is_array( $condition_value ) ) {
				$condition_value = implode( ',', $condition_value );
			}

			if ( $condition_field && $condition_value ) {
				$conditions    = sprintf( 'data-cond-field="%s" data-cond-value="%s"', $condition_field, $condition_value );
				$field_classes .= ' wpcw-field-row-hide wpcw-field-conditional';
			}
		}

		return sprintf( '<div id="wpcw-field-row-%s" class="wpcw-field-row wpcw-field-row-%s%s wpcw-field-clear" %s>', $field_id, $field_type, $field_classes, $conditions );
	}

	/**
	 * Generate Field Row End Html.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The field html.
	 */
	public function generate_field_row_end_html( $key, $field ) {
		return '</div>';
	}

	/**
	 * Generate Field Table Html.
	 *
	 * Wraps the form into a form table for proper display.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The field html args.
	 *
	 * @return string The field table html.
	 */
	public function generate_field_table_html( $args = array() ) {
		$defaults = array(
			'field_data' => array(),
			'field_html' => '',
			'classes'    => '',
		);

		$field_table = wp_parse_args( $args, $defaults );
		$field_data  = $field_table['field_data'];
		$field_html  = $field_table['field_html'];

		if ( empty( $field_data ) || empty( $field_html ) ) {
			return;
		}

		$key     = isset( $field_data['key'] ) ? $this->get_field_key( $field_data['key'] ) : '';
		$type    = $this->get_field_type( $field_data );
		$title   = $this->get_field_title( $field_data );
		$desc    = $this->get_field_desc( $field_data );
		$tip     = $this->get_field_desc_tip( $field_data );
		$classes = ! empty( $field_table['classes'] ) ? ' ' . $field_table['classes'] : '';

		if ( empty ( $key ) ) {
			$key = strtolower( $type );
		}

		ob_start();
		?>
		<table id="wpcw-form-table-<?php echo str_replace( '_', '-', $key ); ?>" class="wpcw-form-table">
			<tbody>
			<tr valign="top" class="wpcw-form-row wpcw-form-row-<?php echo $type; ?><?php echo $classes; ?>">
				<?php if ( ! empty( $title ) ) { ?>
					<th scope="row" valign="top" class="label-cell">
						<div class="label-cell-content">
							<div>
								<h3><?php echo esc_attr( $title ); ?></h3>
								<?php if ( ! empty( $tip ) ) { ?>
									<abbr class="wpcw-tooltip" title="<?php echo wp_kses_post( $tip ); ?>" rel="wpcw-tooltip">
										<i class="wpcw-fas wpcw-fa-info-circle"></i>
									</abbr>
								<?php } ?>
							</div>
						</div>
					</th>
				<?php } ?>
				<?php if ( ! empty( $field_html ) ) { ?>
					<td scope="row" valign="top" class="input-cell">
						<div class="input-cell-content">
							<div>
								<div class="wpcw-form-field">
									<?php echo $field_html; ?>
									<?php if ( ! empty( $desc ) ) { ?>
										<span class="desc"><?php echo wp_kses_post( $desc ); ?></span>
									<?php } ?>
								</div>
							</div>
						</div>
					</td>
				<?php } ?>
			</tr>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Missing field Type Html Callback..
	 *
	 * Called when the field callback doesn't exist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The field html.
	 */
	public function generate_missing_field_type_html( $key, $field ) {
		return sprintf( __( 'The callback function for field type <strong>%s</strong> is missing.', 'wp-courseware' ), wpcw_sanitize_key( $field['type'] ) );
	}

	/**
	 * Field: Heading
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The field html.
	 */
	public function generate_heading_field_html( $key, $field ) {
		$field_key   = $this->get_field_key( $key );
		$field_data  = wp_parse_args( $field, array(
			'title' => '',
			'desc'  => '',
		) );
		$field_title = $this->get_field_title( $field_data );
		$field_desc  = $this->get_field_desc( $field_data );
		$field_html  = '';

		if ( ! empty( $field_title ) ) {
			$field_html .= sprintf( '<h2 class="wpcw-field-heading">%s</h2>', esc_html( $field_title ) );
		}

		if ( ! empty( $field_desc ) ) {
			$field_html .= sprintf( '<p>%s</p>', wp_kses_post( $field_desc ) );
		}

		return $field_html;
	}

	/**
	 * Field: Separator.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The separator field html.
	 */
	public function generate_separator_field_html( $key, $field ) {
		return '<hr />';
	}

	/**
	 * Field: Radio
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The radio field html.
	 */
	public function generate_radio_field_html( $key, $field ) {
		$defaults = array(
			'type'     => 'radio',
			'title'    => '',
			'label'    => '',
			'desc'     => '',
			'desc_tip' => '',
			'options'  => array(),
		);

		$field_key   = $this->get_field_key( $key );
		$field_data  = wp_parse_args( $field, $defaults );
		$field_label = $this->get_field_label( $field_data );
		$field_value = $this->get_setting( $field_key );

		if ( empty ( $field_data['options'] ) ) {
			return;
		}

		ob_start();

		foreach ( $field_data['options'] as $option => $option_label ) {
			?>
			<span class="radio">
			    <label for="<?php echo esc_attr( $field_key ); ?>-<?php echo esc_attr( $option ); ?>">
			        <input type="radio"
			               id="<?php echo esc_attr( $field_key ); ?>-<?php echo esc_attr( $option ); ?>"
			               name="<?php echo esc_attr( $field_key ); ?>"
			               value="<?php echo esc_attr( $option ); ?>"
				        <?php checked( esc_attr( $field_value ), esc_attr( $option ) ); ?> />
				    <?php if ( $option_label ) { ?>
					    <span class="radio-label"><?php echo esc_html( $option_label ); ?></span>
				    <?php } ?>
                </label>
			</span>
			<?php
		}

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Select
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The radio field html.
	 */
	public function generate_select_field_html( $key, $field ) {
		$defaults = array(
			'type'         => 'select',
			'title'        => '',
			'placeholder'  => '',
			'desc'         => '',
			'desc_tip'     => '',
			'blank_option' => esc_html__( 'Select', 'wp-courseware' ),
			'options'      => array(),
		);

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_value       = $this->get_setting( $field_key );

		ob_start();

		?>
		<select class="select-field-wpcwselect2"
		        id="<?php echo esc_attr( $field_key ); ?>"
		        name="<?php echo esc_attr( $field_key ); ?>"
		        data-placeholder="<?php echo esc_html( $field_placeholder ); ?>">
			<?php if ( ! empty( $field_data['blank_option'] ) ) { ?>
				<option disabled value=""><?php echo esc_html( $field_data['blank_option'] ); ?></option>
			<?php } ?>
			<?php foreach ( $field_data['options'] as $option => $option_label ) { ?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( esc_attr( $field_value ), esc_attr( $option ) ); ?>><?php echo esc_html( $option_label ); ?></option>
			<?php } ?>
		</select>
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Checkbox.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field.
	 *
	 * @return string The checkbox field html.
	 */
	public function generate_checkbox_field_html( $key, $field ) {
		$defaults = array(
			'type'     => 'checkbox',
			'title'    => '',
			'label'    => '',
			'desc'     => '',
			'desc_tip' => '',
		);

		$field_key   = $this->get_field_key( $key );
		$field_data  = wp_parse_args( $field, $defaults );
		$field_title = $this->get_field_title( $field_data );
		$field_label = $this->get_field_label( $field_data );
		$field_value = $this->get_setting( $field_key );

		ob_start();

		?>
		<span class="checkbox">
            <label for="<?php echo esc_attr( $field_key ); ?>">
                <input type="checkbox" id="<?php echo esc_attr( $field_key ); ?>" name="<?php echo esc_attr( $field_key ); ?>" <?php checked( $field_value, 'yes' ); ?>>
	            <?php if ( ! empty( $field_label ) ) { ?>
		            <span class="checkbox-label"><?php echo wp_kses_post( $field_label ); ?></span>
	            <?php } ?>
            </label>
        </span>
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Text
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The text field html.
	 */
	public function generate_text_field_html( $key, $field ) {
		$defaults = array(
			'type'        => 'text',
			'title'       => '',
			'placeholder' => '',
			'desc'        => '',
			'desc_tip'    => '',
			'size'        => 'regular',
			'default'     => '',
			'condition'   => array(),
		);

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_label       = $this->get_field_label( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_value       = $this->get_setting( $field_key );
		$field_size        = ! empty( $field_data['size'] ) ? esc_attr( $field_data['size'] ) : 'regular';

		ob_start();

		?>
		<input type="text"
		       id="<?php echo esc_attr( $field_key ); ?>"
		       name="<?php echo esc_attr( $field_key ); ?>"
		       class="size-<?php echo esc_attr( $field_size ); ?>"
		       placeholder="<?php echo esc_html( $field_placeholder ); ?>"
		       value="<?php echo esc_attr( $field_value ); ?>">
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Password
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The text field html.
	 */
	public function generate_password_field_html( $key, $field ) {
		$defaults = array(
			'type'        => 'text',
			'title'       => '',
			'placeholder' => '',
			'desc'        => '',
			'desc_tip'    => '',
			'size'        => 'regular',
			'default'     => '',
			'condition'   => array(),
		);

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_label       = $this->get_field_label( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_default     = $this->get_field_default( $field_data );
		$field_value       = $this->get_setting( $field_key, $field_default );
		$field_size        = ! empty( $field_data['size'] ) ? esc_attr( $field_data['size'] ) : 'regular';

		ob_start();

		?>
		<input type="password"
		       id="<?php echo esc_attr( $field_key ); ?>"
		       name="<?php echo esc_attr( $field_key ); ?>"
		       class="size-<?php echo esc_attr( $field_size ); ?>"
		       placeholder="<?php echo esc_html( $field_placeholder ); ?>"
		       value="<?php echo esc_attr( $field_value ); ?>">
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Fo-Password
	 *
	 * This is a password fields that uses a font to mask
	 * instead of an actaul password.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The text field html.
	 */
	public function generate_fopassword_field_html( $key, $field ) {
		$defaults = array(
			'type'        => 'text',
			'title'       => '',
			'placeholder' => '',
			'desc'        => '',
			'desc_tip'    => '',
			'size'        => 'regular',
			'default'     => '',
			'condition'   => array(),
		);

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_label       = $this->get_field_label( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_default     = $this->get_field_default( $field_data );
		$field_value       = $this->get_setting( $field_key, $field_default );
		$field_size        = ! empty( $field_data['size'] ) ? esc_attr( $field_data['size'] ) : 'regular';

		ob_start();

		?>
		<input type="text"
		       id="<?php echo esc_attr( $field_key ); ?>"
		       name="<?php echo esc_attr( $field_key ); ?>"
		       class="fopassword size-<?php echo esc_attr( $field_size ); ?>"
		       placeholder="<?php echo esc_html( $field_placeholder ); ?>"
		       value="<?php echo esc_attr( $field_value ); ?>">
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Textarea
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The text field html.
	 */
	public function generate_textarea_field_html( $key, $field ) {
		$defaults = array(
			'type'        => 'textarea',
			'title'       => '',
			'placeholder' => '',
			'desc'        => '',
			'desc_tip'    => '',
			'size'        => 'regular',
		);

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_label       = $this->get_field_label( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_value       = $this->get_setting( $field_key );
		$field_size        = ! empty( $field_data['size'] ) ? esc_attr( $field_data['size'] ) : 'regular';

		ob_start();

		?>
		<textarea id="<?php echo esc_attr( $field_key ); ?>"
		          name="<?php echo esc_attr( $field_key ); ?>"
		          class="size-<?php echo esc_attr( $field_size ); ?>"
		          placeholder="<?php echo esc_html( $field_placeholder ); ?>"><?php echo esc_attr( $field_value ); ?></textarea>
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Number
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The number field html.
	 */
	public function generate_number_field_html( $key, $field ) {
		$defaults = array(
			'type'        => 'number',
			'title'       => '',
			'placeholder' => '',
			'min'         => 0,
			'max'         => 10000,
			'step'        => 1,
			'desc'        => '',
			'desc_tip'    => '',
			'size'        => 'regular',
		);

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_label       = $this->get_field_label( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_value       = $this->get_setting( $field_key );
		$field_size        = ! empty( $field_data['size'] ) ? esc_attr( $field_data['size'] ) : 'regular';
		$field_min         = ! empty( $field_data['min'] ) ? absint( $field_data['max'] ) : 0;
		$field_max         = ! empty( $field_data['max'] ) ? absint( $field_data['max'] ) : 1000;
		$field_step        = ! empty( $field_data['step'] ) ? absint( $field_data['step'] ) : 1;

		ob_start();

		?>
		<input type="number"
		       id="<?php echo esc_attr( $field_key ); ?>"
		       name="<?php echo esc_attr( $field_key ); ?>"
		       class="size-<?php echo esc_attr( $field_size ); ?>"
		       placeholder="<?php echo esc_html( $field_placeholder ); ?>"
		       value="<?php echo esc_attr( $field_value ); ?>"
		       min="<?php echo absint( $field_min ); ?>"
		       max="<?php echo absint( $field_max ); ?>"
		       step="<?php echo absint( $field_step ); ?>"/>
		<?php

		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Field: Page
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The page field html.
	 */
	public function generate_page_field_html( $key, $field ) {
		$defaults = array(
			'type'         => 'page',
			'title'        => '',
			'desc'         => '',
			'desc_tip'     => '',
			'placeholder'  => esc_html__( 'Select a Page', 'wp-courseware' ),
			'blank_option' => esc_html__( 'Select a Page', 'wp-courseware' ),
		);

		if ( empty( $this->pages ) ) {
			$pages = get_pages();
			if ( $pages ) {
				foreach ( $pages as $page ) {
					$this->pages[] = array(
						'id'    => $page->ID,
						'title' => htmlspecialchars_decode( $page->post_title ),
					);
				}
			}
		}

		$found              = false;
		$field_key          = $this->get_field_key( $key );
		$field_data         = wp_parse_args( $field, $defaults );
		$field_title        = $this->get_field_title( $field_data );
		$field_label        = $this->get_field_label( $field_data );
		$field_placeholder  = $this->get_field_placeholder( $field_data );
		$field_desc         = $this->get_field_desc( $field_data );
		$field_desc_tip     = $this->get_field_desc_tip( $field_data );
		$field_blank_option = ! empty( $field_data['blank_option'] ) ? $field_data['blank_option'] : esc_html__( 'Select a Page', 'wp-courseware' );
		$field_value        = $this->get_setting( $field_key );

		foreach ( $this->pages as $page_key => $page ) {
			if ( absint( $page['id'] ) === absint( $field_value ) ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$field_value = '';
		}

		ob_start();

		?>
		<wpcw-form-field-page id="<?php echo esc_attr( $field_key ); ?>"
		                      name="<?php echo esc_attr( $field_key ); ?>"
		                      label="<?php echo esc_html( $field_title ); ?>"
		                      placeholder="<?php echo esc_html( $field_placeholder ); ?>"
		                      desc="<?php echo wp_kses_post( $field_desc ); ?>"
		                      tip="<?php echo wp_kses_post( $field_desc_tip ); ?>"
		                      blank="<?php echo esc_html( $field_blank_option ); ?>"
		                      value="<?php echo esc_attr( $field_value ); ?>"
		                      clear="true"
		                      pages="<?php echo wpcw_convert_array_to_json( $this->pages ); ?>"></wpcw-form-field-page>
		<?php

		return ob_get_clean();
	}

	/**
	 * Field: Image Input.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The image input field html.
	 */
	public function generate_imageinput_field_html( $key, $field ) {
		$defaults = array(
			'type'            => 'imageinput',
			'title'           => '',
			'placeholder'     => esc_html__( 'Upload Image', 'wp-courseware' ),
			'desc'            => '',
			'desc_tip'        => '',
			'image_key'       => '',
			'button'          => esc_html__( 'Select Image', 'wp-courseware' ),
			'uploader_title'  => esc_html__( 'Upload Image', 'wp-courseware' ),
			'uploader_button' => esc_html__( 'Select Image', 'wp-courseware' ),
		);

		wp_enqueue_media();

		$field_key         = $this->get_field_key( $key );
		$field_data        = wp_parse_args( $field, $defaults );
		$field_title       = $this->get_field_title( $field_data );
		$field_label       = $this->get_field_label( $field_data );
		$field_placeholder = $this->get_field_placeholder( $field_data );
		$field_desc        = $this->get_field_desc( $field_data );
		$field_desc_tip    = $this->get_field_desc_tip( $field_data );
		$field_value       = $this->get_setting( $field_key );

		$field_image_key       = ! empty( $field_data['image_key'] ) ? esc_attr( $field_data['image_key'] ) : '';
		$field_image_key_value = ! empty( $field_image_key ) ? $this->get_setting( $field_image_key ) : '';

		ob_start();

		?>
		<wpcw-form-field-image-input id="<?php echo esc_attr( $field_key ); ?>"
		                             name="<?php echo esc_attr( $field_key ); ?>"
		                             label="<?php echo esc_html( $field_title ); ?>"
		                             placeholder="<?php echo esc_html( $field_placeholder ); ?>"
		                             button="<?php echo esc_html( $field_data['button'] ); ?>"
		                             desc="<?php echo wp_kses_post( $field_desc ); ?>"
		                             tip="<?php echo wp_kses_post( $field_desc_tip ); ?>"
		                             value="<?php echo esc_attr( $field_value ); ?>"
		                             image_key="<?php echo esc_attr( $field_image_key ); ?>"
		                             image_key_value="<?php echo esc_attr( $field_image_key_value ); ?>"
		                             uploader_title="<?php echo esc_html( $field_data['uploader_title'] ); ?>"
		                             uploader_button="<?php echo esc_html( $field_data['uploader_button'] ); ?>"></wpcw-form-field-image-input>
		<?php

		return ob_get_clean();
	}

	/**
	 * Field: Color
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The text field html.
	 */
	public function generate_color_field_html( $key, $field ) {
		$defaults = array(
			'type'     => 'color',
			'title'    => '',
			'desc'     => '',
			'desc_tip' => '',
			'default'  => '',
		);

		$field_key      = $this->get_field_key( $key );
		$field_data     = wp_parse_args( $field, $defaults );
		$field_title    = $this->get_field_title( $field_data );
		$field_default  = $this->get_field_default( $field_data );
		$field_desc     = $this->get_field_desc( $field_data );
		$field_desc_tip = $this->get_field_desc_tip( $field_data );
		$field_value    = $this->get_setting( $field_key );

		ob_start();

		?>
		<wpcw-form-field-color-picker id="<?php echo esc_attr( $field_key ); ?>"
		                              label="<?php echo esc_html( $field_title ); ?>"
		                              name="<?php echo esc_attr( $field_key ); ?>"
		                              value="<?php echo esc_attr( $field_value ); ?>"
		                              defaultcolor="<?php echo esc_attr( $field_default ); ?>"
		                              desc="<?php echo wp_kses_post( $field_desc ); ?>"
		                              tip="<?php echo wp_kses_post( $field_desc_tip ); ?>"></wpcw-form-field-color-picker>
		<?php

		return ob_get_clean();
	}

	/**
	 * Field: Content.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The text field html.
	 */
	public function generate_content_field_html( $key, $field ) {
		$defaults = array(
			'type'     => 'content',
			'title'    => '',
			'content'  => '',
			'desc_tip' => '',
		);

		$field_key     = $this->get_field_key( $key );
		$field_data    = wp_parse_args( $field, $defaults );
		$field_title   = $this->get_field_title( $field_data );
		$field_content = $this->get_field_content( $field_data );

		ob_start();

		?>
		<div class="content"><?php echo $field_content; ?></div>
		<?php
		$field_html = ob_get_clean();

		return $this->generate_field_table_html( array(
			'field_data' => $field_data,
			'field_html' => $field_html,
		) );
	}

	/**
	 * Validate Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The field key.
	 * @param mixed  $value The field value.
	 *
	 * @return mixed The field value.
	 */
	public function validate_field( $type, $key, $value ) {
		if ( is_callable( array( $this, 'validate_' . $key . '_field' ) ) ) {
			$value = $this->{'validate_' . $key . '_field'}( $key, $value );
		} elseif ( is_callable( array( $this, 'validate_' . $type . '_field' ) ) ) {
			$value = $this->{'validate_' . $type . '_field'}( $key, $value );
		} else {
			$value = $this->validate_text_field( $key, $value );
		}

		return $value;
	}

	/**
	 * Validate Text Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.3.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_text_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate Password Field.
	 *
	 * No input sanitization is used to avoid corrupting passwords.
	 *
	 * @since 4.3.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_password_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return trim( stripslashes( $value ) );
	}

	/**
	 * Validate Textarea Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_textarea_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return wp_kses( trim( stripslashes( $value ) ),
			array_merge(
				array(
					'iframe' => array( 'src' => true, 'style' => true, 'id' => true, 'class' => true ),
				),
				wp_kses_allowed_html( 'post' )
			)
		);
	}

	/**
	 * Validate Checkbox Field.
	 *
	 * If not set, return "no", otherwise return "yes".
	 *
	 * @since 4.3.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_checkbox_field( $key, $value ) {
		return ! is_null( $value ) && 'no' !== $value ? 'yes' : 'no';
	}

	/**
	 * Validate Select Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_select_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return wpcw_clean( stripslashes( $value ) );
	}

	/**
	 * Validate Payment Gateways Order Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_payment_gateways_order_field( $key, $value ) {
		$order = array();

		$post_data = $this->get_post_data();

		if ( empty( $post_data ) || empty( $post_data['payment_gateways_order'] ) ) {
			return $this->get_setting( $key, array() );
		}

		if ( is_array( $value ) && sizeof( $value ) > 0 ) {
			$loop = 0;
			foreach ( $value as $gateway_id ) {
				$order[ esc_attr( $gateway_id ) ] = $loop;
				$loop ++;
			}
		}

		return ! empty( $order ) ? $order : $value;
	}
}
