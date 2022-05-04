<?php
/**
 * WP Courseware - Admin Fields.
 *
 * @package WPCW
 * @subpackage Admin
 * @since 4.4.0
 */

namespace WPCW\Admin;

use WPCW\Models\Model;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Fields.
 *
 * @since 4.4.0
 *
 * @package WPCW\Admin
 */
class Fields {

	/**
	 * Render Field.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field to render.
	 */
	public function render_field( $field = array() ) {
		$type = $this->get_field_type( $field );

		if ( empty( $field ) ) {
			return;
		}

		$field = wp_parse_args( $field, array(
			'type'       => 'text',
			'id'         => '',
			'name'       => '',
			'label'      => '',
			'desc'       => '',
			'default'    => '',
			'options'    => array(),
			'settings'   => array(),
			'type'       => 'text',
			'class'      => '',
			'first'      => false,
			'last'       => false,
			'before'     => '',
			'after'      => '',
			'tip'        => '',
			'condition'  => array(),
			'merge_tags' => array(),
			'req'        => false,
			'req_msg'    => '',
		) );

		/**
		 * Filter: Field Args.
		 *
		 * @since 4.4.0
		 *
		 * @param array  $field The field data.
		 * @param Fields $this The fields object.
		 *
		 * @return array $field The field data.
		 */
		$field = apply_filters( 'wpcw_fields_field_args', $field, $this );

		ob_start();
		if ( method_exists( $this, "field_{$type}" ) ) {
			$this->{"field_{$type}"}( $field );
		} else {
			/**
			 * Action: Render Field - Type.
			 *
			 * @since 4.4.0
			 *
			 * @param array $field The field data.
			 * @param Fields The fields object.
			 */
			do_action( "wpcw_fields_field_{$type}", $field, $this );
		}
		$html = ob_get_clean();

		// Field Wrap.
		$html = $this->get_field_wrap( $html, $field );

		/**
		 * Filter: Field Type Html.
		 *
		 * @since 4.4.0
		 *
		 * @param string $html The field html.
		 * @param string $type The field type.
		 * @param Fields $this The fields object.
		 *
		 * @return string $html The field html.
		 */
		return apply_filters( "wpcw_fields_field_{$type}_html", $html, $type, $this );
	}

	/**
	 * Get Field Id.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field id.
	 */
	public function get_field_id( $field ) {
		return isset( $field['id'] ) ? str_replace( '_', '-', wpcw_sanitize_key( $field['id'] ) ) : '';
	}

	/**
	 * Get Field Type.
	 *
	 * @since 4.4.0
	 *
	 * @param string $field The field array.
	 *
	 * @return string The field type.
	 */
	public function get_field_type( $field ) {
		return isset( $field['type'] ) ? $field['type'] : '';
	}

	/**
	 * Get Field Name.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field name.
	 *
	 * @return string The field name.
	 */
	public function get_field_name( $field ) {
		return isset( $field['name'] ) ? wpcw_sanitize_key( $field['name'] ) : '';
	}

	/**
	 * Get Field Label.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field name.
	 *
	 * @return string The field label.
	 */
	public function get_field_label( $field ) {
		return isset( $field['label'] ) ? wp_kses_post( $field['label'] ) : '';
	}

	/**
	 * Get Field Checkbox Label.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field checkbox label.
	 */
	public function get_field_clabel( $field ) {
		return isset( $field['clabel'] ) ? wp_kses_post( $field['clabel'] ) : '';
	}

	/**
	 * Get Field Value.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return mixed The field value.
	 */
	public function get_field_value( $field ) {
		$field_type  = $this->get_field_type( $field );
		$field_name  = $this->get_field_name( $field );
		$field_value = isset( $field['value'] ) ? $field['value'] : null;

		$post_data = $this->get_post_data();
		if ( ! empty( $post_data ) ) {
			$field_value = isset( $post_data[ $field_name ] ) ? $post_data[ $field_name ] : $field_value;
		}

		if ( is_null( $field_value ) ) {
			$field_value = $this->get_field_default( $field );
		}

		return $this->validate_field( $field_type, $field_name, $field_value );
	}

	/**
	 * Get Field Default.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return mixed The field default value.
	 */
	public function get_field_default( $field ) {
		return isset( $field['default'] ) ? $field['default'] : null;
	}

	/**
	 * Get Field Description.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field description.
	 */
	public function get_field_desc( $field ) {
		return isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : '';
	}

	/**
	 * Get Field Tip.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field tip.
	 */
	public function get_field_tip( $field ) {
		return isset( $field['tip'] ) ? wp_kses_post( $field['tip'] ) : '';
	}

	/**
	 * Get Field Settings.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array The field settings.
	 */
	public function get_field_settings( $field ) {
		return ! empty( $field['settings'] ) ? $field['settings'] : array();
	}

	/**
	 * Get Field Condition.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array The field condition.
	 */
	public function get_field_condition( $field ) {
		return ! empty( $field['condition'] ) ? $field['condition'] : array();
	}

	/**
	 * Get Field Merge Tags.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array The field merge tags.
	 */
	public function get_field_merge_tags( $field ) {
		return ! empty( $field['merge_tags'] ) ? $field['merge_tags'] : array();
	}

	/**
	 * Get Field Class.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field class.
	 */
	public function get_field_class( $field ) {
		$class = ! empty( $field['class'] ) ? wpcw_sanitize_classes( (array) $field['class'], true ) : '';
		$class = ! empty( $class ) ? ' ' . $class : '';

		return $class;
	}

	/**
	 * Get Field Placeholder.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field placeholder.
	 */
	public function get_field_placeholder( $field ) {
		return ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
	}

	/**
	 * Get Field Before.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field before html.
	 */
	public function get_field_before( $field ) {
		return ! empty( $field['before'] ) ? $field['before'] : '';
	}

	/**
	 * Get Field After.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field after html.
	 */
	public function get_field_after( $field ) {
		return ! empty( $field['after'] ) ? $field['after'] : '';
	}

	/**
	 * Get Field Wrap.
	 *
	 * @since 4.4.0
	 *
	 * @param string $html The field html.
	 * @param string $field The field data.
	 *
	 * @return string $field_html The field html.
	 */
	public function get_field_wrap( $html, $field ) {
		$field = wp_parse_args( $field, array(
			'type'       => 'text',
			'label'      => '',
			'desc'       => '',
			'class'      => '',
			'first'      => false,
			'last'       => false,
			'before'     => '',
			'after'      => '',
			'tip'        => '',
			'condition'  => array(),
			'merge_tags' => array(),
		) );

		$id         = $this->get_field_id( $field );
		$type       = $this->get_field_type( $field );
		$class      = $this->get_field_class( $field );
		$condition  = $this->get_field_condition( $field );
		$conditions = '';

		$class .= ! empty( $field['first'] ) ? ' wpcw-field-first' : '';
		$class .= ! empty( $field['last'] ) ? ' wpcw-field-last' : '';

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
				$conditions = sprintf( 'data-cond-field="%s" data-cond-value="%s"', $condition_field, $condition_value );
				$class      .= ' wpcw-field-conditional';
			}
		}

		// Top Label Fields.
		if ( $top_label_fields = $this->get_top_label_fields() ) {
			$class .= ( is_array( $top_label_fields ) && in_array( $type, $top_label_fields ) ) ? ' wpcw-field-top-label' : '';
		}

		// Hide Label
		if ( ! empty( $field['hide_label'] ) && $field['hide_label'] ) {
			$class .= ' wpcw-field-hide-label';
		}

		// Merge Tag Html
		$merge_tag_html = $this->print_field_merge_tags( $field );

		if ( ! empty( $merge_tag_html ) ) {
			$class .= ' wpcw-field-with-merge-tags';
		}

		$field_html = sprintf( '<div id="%s" class="wpcw-field wpcw-field-%s %s wpcw-field-clear" %s>', $id, $type, $class, $conditions );
		$field_html .= $this->print_field_label( $field );
		$field_html .= $this->get_field_before( $field );
		$field_html .= sprintf( '<div class="wpcw-field-wrapper">%s %s</div>', $html, $merge_tag_html );
		$field_html .= $this->get_field_after( $field );
		$field_html .= $this->print_field_desc( $field );
		$field_html .= sprintf( '</div>' );

		return $field_html;
	}

	/**
	 * Get Field Views.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function get_field_views( $field ) {
		$type = $this->get_field_type( $field );

		ob_start();

		switch ( $type ) {
			case 'image' :
				echo $this->get_view( 'fields/field-image' );
				break;
			case 'prerequisites' :
				echo $this->get_view( 'fields/field-prerequisites' );
				break;
			case 'coursebundles' :
				echo $this->get_view( 'fields/field-course-bundles' );
				break;
			case 'coursesselect' :
				echo $this->get_view( 'fields/field-courses-select' );
				break;
			case 'bulkgrantaccess' :
				echo $this->get_view( 'fields/field-bulk-grant-access' );
				break;
			case 'courseinstructor' :
				echo $this->get_view( 'fields/field-course-instructor' );
				break;
			case 'resetprogress' :
				echo $this->get_view( 'fields/field-reset-progress' );
				break;
			case 'datepicker' :
				echo $this->get_view( 'fields/field-date-picker' );
				break;
		}

		do_action( "wpcw_fields_field_{$type}_views", $type, $this );

		return ob_get_clean();
	}

	/**
	 * Get Field is Required?
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 *
	 * @return bool True if field is required. False otherwise.
	 */
	public function get_field_is_required( $field ) {
		return isset( $field['req'] ) && $field['req'] ? true : false;
	}

	/**
	 * Get Field Required Message.
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string $req_msg The required message.
	 */
	public function get_field_required_message( $field ) {
		return ! empty( $field['req_msg'] ) ? wp_kses_post( $field['req_msg'] ) : '';
	}

	/** Print --------------------------------------- */

	/**
	 * Print Field Label.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string $field_label_html The field label html.
	 */
	public function print_field_label( $field ) {
		$type  = $this->get_field_type( $field );
		$name  = $this->get_field_name( $field );
		$label = $this->get_field_label( $field );

		if ( empty( $label ) || ( ! empty( $field['hide_label'] ) && $field['hide_label'] ) ) {
			return;
		}

		/**
		 * Filter: No label field types.
		 *
		 * @since 4.4.0
		 *
		 * @param array  $field_types The no label field types.
		 * @param Fields $this The fields object.
		 *
		 * @return array $field_types The no label field types.
		 */
		$no_label_field_types = apply_filters( 'wpcw_fields_no_label_field_types', array( 'radio', 'checkbox' ), $this );

		if ( in_array( $type, $no_label_field_types ) ) {
			$field_label_html = '<span class="wpcw-field-label">';
			$field_label_html .= $label;
			$field_label_html .= $this->print_field_tip( $field );
			$field_label_html .= '</span>';
		} else {
			$field_label_html = sprintf( '<label for="%s" class="wpcw-field-label">', $name );
			$field_label_html .= $label;
			$field_label_html .= $this->print_field_tip( $field );
			$field_label_html .= '</label>';
		}

		return $field_label_html;
	}

	/**
	 * Print Field Description.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field description html.
	 */
	public function print_field_desc( $field ) {
		$desc = $this->get_field_desc( $field );

		$field_desc_html = ! empty( $desc ) ? sprintf( '<div class="wpcw-field-desc">%s</div>', $desc ) : '';

		return $field_desc_html;
	}

	/**
	 * Print Field Tip.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string $field_tip_html The field tip html.
	 */
	public function print_field_tip( $field ) {
		$tip = $this->get_field_tip( $field );

		if ( empty( $tip ) ) {
			return;
		}

		return sprintf( '<abbr class="wpcw-tooltip" title="%s" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>', wp_kses_post( $tip ) );
	}

	/**
	 * Print Field Merge Tags.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return string The field merge tags.
	 */
	public function print_field_merge_tags( $field ) {
		$merge_tags = $this->get_field_merge_tags( $field );

		if ( empty( $merge_tags ) ) {
			return;
		}

		$merge_tag_html = '<div class="wpcw-merge-tags">';
		$merge_tag_html .= sprintf( '<a title="%s" class="button-secondary" href="#"><i class="wpcw-fas wpcw-fa-tags"></i></a>', esc_html__( 'Merge Tags', 'wp-courseware' ) );
		$merge_tag_html .= '<div class="wpcw-merge-tags-dropdown-wrapper" style="display: none;">';
		$merge_tag_html .= '<ul class="wpcw-merge-tags-dropdown">';
		foreach ( $merge_tags as $merge_tag => $merge_tag_label ) {
			$merge_tag_html .= sprintf( '<li class="wpcw-merge-tag"><a href="#" data-tag="{%s}" class="wpcw-merge-tag-link">{%s} <abbr class="wpcw-tooltip" title="%s" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr></a></li>', $merge_tag, $merge_tag, $merge_tag_label );
		}
		$merge_tag_html .= '</ul>';
		$merge_tag_html .= '</div>';
		$merge_tag_html .= '</div>';

		return $merge_tag_html;
	}

	/** Fields --------------------------------------- */

	/**
	 * Field: Text
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_text( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'placeholder' => '',
			'size'        => 'normal',
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'normal';

		printf( '<input type="text" id="%s" name="%s" class="size-%s supports-merge-tags" value="%s" placeholder="%s" />', $id, $name, $size, esc_html( $value ), $placeholder );
	}

	/**
	 * Field: Number
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 */
	public function field_number( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'placeholder' => '',
			'step'        => 1,
			'min'         => '',
			'max'         => '',
			'ignore_zero' => true,
			'size'        => 'normal',
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'normal';

		// Min & Max
		$min = ( ! empty( $field['min'] ) || 0 === $field['min'] ) ? 'min="' . absint( $field['min'] ) . '"' : '';
		$max = ( ! empty( $field['max'] ) ) ? 'max="' . absint( $field['max'] ) . '"' : '';

		// Placeholder.
		$placeholder = ! empty( $placeholder ) ? 'placeholder="' . esc_attr( $placeholder ) . '"' : '';

		// Ignore Zero.
		$value = ( true === $field['ignore_zero'] && 0 === absint( $value ) ) ? '' : $value;

		printf( '<input type="number" id="%s" name="%s" class="size-%s" value="%s" %s %s %s />', esc_attr( $id ), esc_attr( $name ), $size, $value, $placeholder, $min, $max );
	}

	/**
	 * Field: Date Picker
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 */
	public function field_datepicker( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'placeholder' => '',
			'size'        => 'normal',
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'normal';

		if ( empty( $value ) ) {
			$value = $this->get_field_default( $field );
		}

		printf(
			'<wpcw-field-date-picker name="%s" size="%s" dateselected="%s" maxlength="%s" pattern="%s" v-once></wpcw-field-date-picker>',
			esc_attr( $name ),
			esc_attr( $size ),
			esc_attr( date_i18n( 'Y-m-d', strtotime( $value ) ) ),
			absint( apply_filters( 'wpcw_date_input_size', 10 ) ),
			esc_attr( apply_filters( 'wpcw_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) )
		);
	}

	/**
	 * Field: Money
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_money( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'placeholder' => '0.00',
			'size'        => 'money',
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'money';

		if ( in_array( wpcw_get_currency_position(), array( 'left', 'left_space' ) ) ) {
			printf( '<span class="wpcw-before-field-input wpcw-currency-symbol">%s</span>', wpcw_get_currency_symbol() );
		}

		printf( '<input type="text" id="%s" name="%s" class="size-%s" value="%s" placeholder="%s" />', $id, $name, $size, $value, $placeholder );

		if ( in_array( wpcw_get_currency_position(), array( 'right', 'right_space' ) ) ) {
			printf( '<span class="wpcw-after-field-input wpcw-currency-symbol">%s</span>', wpcw_get_currency_symbol() );
		}
	}

	/**
	 * Field: Textarera
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_textarea( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'placeholder' => '',
			'size'        => 'normal',
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'normal';

		printf( '<textarea type="text" id="%s" name="%s" class="size-%s supports-merge-tags" placeholder="%s">%s</textarea>', $id, $name, $size, $placeholder, $value );
	}

	/**
	 * Field: Wysiwyg
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_wysiwyg( $field ) {
		$field = wp_parse_args( $field, array(
			'id'       => '',
			'name'     => '',
			'value'    => '',
			'default'  => '',
			'settings' => array(),
		) );

		wp_enqueue_editor();

		$value    = $this->get_field_value( $field );
		$name     = $this->get_field_name( $field );
		$settings = wp_parse_args( $this->get_field_settings( $field ), array( 'textarea_rows' => 10, 'editor_class' => 'supports-merge-tags wpcw-field-wysywig-textarea' ) );

		wp_editor( $value, $name, $settings );
	}

	/**
	 * Field: Radio
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_radio( $field ) {
		$field = wp_parse_args( $field, array(
			'id'      => '',
			'name'    => '',
			'value'   => '',
			'default' => '',
			'style'   => 'block',
			'options' => array(),
		) );

		if ( empty( $field['options'] ) ) {
			return;
		}

		$value = $this->get_field_value( $field );
		$name  = $this->get_field_name( $field );

		printf( '<ul class="wpcw-field-radio-options style-%s">', esc_attr( $field['style'] ) );
		foreach ( $field['options'] as $option_value => $option_label ) {
			$option_id = "{$name}_choice_{$option_value}";
			$checked   = checked( $value, $option_value, false );
			printf(
				'<li>
					<label for="%s">
						<input id="%s" type="radio" name="%s" value="%s" %s> %s
					</label>
				</li>',
				$option_id,
				$option_id,
				$name,
				$option_value,
				$checked,
				$option_label
			);
		}
		printf( '</ul>' );
	}

	/**
	 * Field: Course Access Expiration
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_select( $field ) {
		$field = wp_parse_args( $field, array(
			'id'      => '',
			'name'    => '',
			'value'   => '',
			'default' => '',
			'style'   => 'block',
			'options' => array(),
		) );

		if ( empty( $field['options'] ) ) {
			return;
		}

		$value = $this->get_field_value( $field );
		$name  = $this->get_field_name( $field );

		printf( '<select name="%s" class="wpcw-field-select-options style-%s">', $name, esc_attr( $field['style'] ) );
		foreach ( $field['options'] as $option_value => $option_label ) {
			$option_id = "{$name}_choice_{$option_value}";
			$selected   = selected( $value, $option_value, false );
			printf(
				'<option %s value="%s">%s</option>',
				$selected,
				$option_value,
				$option_label
			);
		}
		printf( '</select>' );
	}

	/**
	 * Field: Checkbox
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 */
	public function field_checkbox( $field ) {
		$field = wp_parse_args( $field, array(
			'id'      => '',
			'name'    => '',
			'value'   => '',
			'default' => 'no',
			'clabel'  => '',
		) );

		$id     = $this->get_field_id( $field );
		$name   = $this->get_field_name( $field );
		$value  = $this->get_field_value( $field );
		$clabel = $this->get_field_clabel( $field );

		?>
		<span class="checkbox">
            <label for="<?php echo esc_attr( $id ); ?>">
                <input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php checked( $value, 'yes' ); ?>>
	            <?php if ( ! empty( $clabel ) ) { ?>
		            <span class="checkbox-label"><?php echo wp_kses_post( $clabel ); ?></span>
	            <?php } ?>
            </label>
        </span>
		<?php
	}

	/**
	 * Field: True / False.
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 */
	public function field_truefalse( $field ) {
		$field = wp_parse_args( $field, array(
			'default' => false,
		) );

		$id     = $this->get_field_id( $field );
		$name   = $this->get_field_name( $field );
		$value  = $this->get_field_value( $field );
		$clabel = $this->get_field_clabel( $field );
		?>
		<span class="checkbox truefalse">
            <label for="<?php echo esc_attr( $name ); ?>">
                <input type="checkbox" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php checked( $value, true ); ?>>
	            <?php if ( ! empty( $clabel ) ) { ?>
		            <span class="checkbox-label"><?php echo wp_kses_post( $clabel ); ?></span>
	            <?php } ?>
            </label>
        </span>
		<?php
	}

	/**
	 * Field: Colorpicker
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_colorpicker( $field ) {
	}

	/**
	 * Field: HTML
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_html( $field ) {
		$field = wp_parse_args( $field, array(
			'id'   => '',
			'name' => '',
			'html' => '',
		) );

		if ( empty( $field['html'] ) ) {
			return;
		}

		echo wp_kses_post( $field['html'] );
	}

	/**
	 * Field: Accordion
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_accordion( $field ) {
		$field = wp_parse_args( $field, array(
			'id'     => '',
			'name'   => '',
			'fields' => array(),
		) );

		if ( empty( $field['fields'] ) ) {
			return;
		}

		$accordion_id     = $this->get_field_id( $field );
		$accordion_name   = $this->get_field_name( $field );
		$accordion_fields = $field['fields'];

		echo '<div class="wpcw-accordion">';
		$accordion_count = 0;
		foreach ( $accordion_fields as $accordion_field ) {
			$accordion_field['class'] = 'wpcw-sub-field';
			$accordion_field_label    = $this->get_field_label( $accordion_field );
			$accordion_field_tip      = $this->print_field_tip( $accordion_field );
			$accordion_toggle         = sprintf(
				'<button type="button" class="wpcw-sub-field" aria-expanded="false">
					<span class="screen-reader-text">%s: %s</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>', esc_html__( 'Toggle Accordion', 'wp-courseware' ),
				$accordion_field_label
			);
			echo '<div class="wpcw-accordion-item">';
			printf( '<a class="wpcw-accordion-toggle" href="javascript:void(0);">%s %s %s</a>', $accordion_field_label, $accordion_field_tip, $accordion_toggle );
			echo '<div class="wpcw-accordion-content">';
			if ( isset( $accordion_field['type'] ) && 'accordion_item' === $accordion_field['type'] && ! empty( $accordion_field['fields'] ) ) {
				foreach ( $accordion_field['fields'] as $accordion_sub_field ) {
					echo $this->render_field( $accordion_sub_field );
				}
			} else {
				echo $this->render_field( $accordion_field );
			}
			echo '</div>';
			echo '</div>';
			$accordion_count ++;
		}
		echo '</div>';
	}

	/**
	 * Field: Image
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_image( $field ) {
		$field = wp_parse_args( $field, array(
			'id'              => '',
			'name'            => '',
			'value'           => '',
			'default'         => '',
			'placeholder'     => esc_html__( 'Upload Image', 'wp-courseware' ),
			'size'            => 'large',
			'button'          => esc_html__( 'Select Image', 'wp-courseware' ),
			'uploader_title'  => esc_html__( 'Upload Image', 'wp-courseware' ),
			'uploader_button' => esc_html__( 'Select Image', 'wp-courseware' ),
		) );

		wp_enqueue_media();

		$id              = $this->get_field_id( $field );
		$name            = $this->get_field_name( $field );
		$placeholder     = $this->get_field_placeholder( $field );
		$value           = $this->get_field_value( $field );
		$size            = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'large';
		$button          = isset( $field['button'] ) ? esc_attr( $field['button'] ) : esc_html__( 'Select Image', 'wp-courseware' );
		$uploader_title  = isset( $field['uploader_title'] ) ? esc_attr( $field['uploader_title'] ) : esc_html__( 'Upload Image', 'wp-courseware' );
		$uploader_button = isset( $field['uploader_button'] ) ? esc_attr( $field['uploader_button'] ) : esc_html__( 'Select Image', 'wp-courseware' );

		echo $this->get_view( 'fields/field-image' );

		printf(
			'<wpcw-field-image id="%s" name="%s" inputclass="size-%s" value="%s" placeholder="%s" inputbutton="%s" uploader_title="%s" uploader_button="%s"></wpcw-field-image>',
			$id,
			$name,
			$size,
			esc_url( $value ),
			$placeholder,
			$button,
			$uploader_title,
			$uploader_button
		);
	}

	/**
	 * Field: Prerequisite
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_prerequisites( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'size'        => 'large',
			'placeholder' => esc_html__( 'Select Course Prerequisites', 'wp-courseware' ),
			'course_id'   => 0,
		) );

		$id            = $this->get_field_id( $field );
		$name          = $this->get_field_name( $field );
		$placeholder   = $this->get_field_placeholder( $field );
		$value         = $this->get_field_value( $field );
		$size          = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'large';
		$course_id     = ! empty( $field['course_id'] ) ? absint( $field['course_id'] ) : 0;
		$prerequisites = array();

		if ( is_array( $value ) && ! empty( $value ) ) {
			if ( $courses = wpcw()->courses->get_courses( array( 'course_id' => $value, 'fields' => array( 'course_id', 'course_title' ) ), true ) ) {
				foreach ( $courses as $course ) {
					$prerequisites[] = array( 'id' => $course->course_id, 'text' => $course->course_title );
				}
			}
		}

		printf( '<wpcw-field-prerequisites id="%s" name="%s[]" sizeclass="size-%s" prerequisites="%s" placeholder="%s" course_id="%d"></wpcw-field-prerequisites>', $id, $name, $size, htmlspecialchars( wp_json_encode( $prerequisites ) ), $placeholder, $course_id );
	}

	/**
	 * Field: Course Bundles
	 *
	 * @since 4.6.0
	 *
	 * @param array $field The field data.
	 */
	public function field_coursebundles( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'size'        => 'large',
			'placeholder' => esc_html__( 'Select Course Bundles', 'wp-courseware' ),
			'course_id'   => 0,
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'large';
		$course_id   = ! empty( $field['course_id'] ) ? absint( $field['course_id'] ) : 0;
		$bundles     = array();

		if ( is_array( $value ) && ! empty( $value ) ) {
			if ( $courses = wpcw()->courses->get_courses( array( 'course_id' => $value, 'fields' => array( 'course_id', 'course_title' ) ), true ) ) {
				foreach ( $courses as $course ) {
					$bundles[] = array( 'id' => $course->course_id, 'text' => $course->course_title );
				}
			}
		}

		printf( '<wpcw-field-course-bundles id="%s" name="%s[]" sizeclass="size-%s" bundles="%s" placeholder="%s" course_id="%d"></wpcw-field-course-bundles>', $id, $name, $size, htmlspecialchars( wp_json_encode( $bundles ) ), $placeholder, $course_id );
	}

	/**
	 * Field: Courses Select
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 */
	public function field_coursesselect( $field ) {
		$field = wp_parse_args( $field, array(
			'id'          => '',
			'name'        => '',
			'value'       => '',
			'default'     => '',
			'size'        => 'large',
			'placeholder' => esc_html__( 'Search for a Course...', 'wp-courseware' ),
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'large';

		$selected_courses = array();

		if ( is_array( $value ) && ! empty( $value ) ) {
			if ( $courses = wpcw()->courses->get_courses( array( 'course_id' => $value, 'fields' => array( 'course_id', 'course_title' ) ), true ) ) {
				foreach ( $courses as $course ) {
					$selected_courses[] = array( 'id' => $course->course_id, 'text' => $course->course_title );
				}
			}
		}

		printf( '<wpcw-field-courses-select id="%s" name="%s[]" sizeclass="size-%s" courses="%s" placeholder="%s" v-once></wpcw-field-courses-select>', $id, $name, $size, htmlspecialchars( wp_json_encode( $selected_courses ) ), $placeholder );
	}

	/**
	 * Field: Course Instructor
	 *
	 * @since 4.5.2
	 *
	 * @param array $field The field data.
	 */
	public function field_courseinstructor( $field ) {
		$field = wp_parse_args( $field, array(
			'id'            => '',
			'name'          => '',
			'value'         => '',
			'default'       => '',
			'size'          => 'large',
			'placeholder'   => esc_html__( 'Search for an Instructor...', 'wp-courseware' ),
			'course_id'     => 0,
			'instructor_id' => 0,
		) );

		$id          = $this->get_field_id( $field );
		$name        = $this->get_field_name( $field );
		$placeholder = $this->get_field_placeholder( $field );
		$value       = $this->get_field_value( $field );
		$size        = isset( $field['size'] ) ? esc_attr( $field['size'] ) : 'large';

		$course_id     = ! empty( $field['course_id'] ) ? absint( $field['course_id'] ) : 0;
		$instructor_id = ! empty( $field['instructor_id'] ) ? absint( $field['instructor_id'] ) : get_current_user_id();
		$instructor    = array();

		if ( ! empty( $instructor_id ) ) {
			if ( $user = get_user_by( 'id', $instructor_id ) ) {
				$instructor = array( 'id' => $instructor_id, 'name' => sprintf( '%s ( %s )', $user->display_name, $user->user_email ) );
			}
		}

		printf( '<wpcw-field-course-instructor id="%s" name="%s" sizeclass="size-%s" instructor="%s" placeholder="%s" course_id="%s" v-once></wpcw-field-course-instructor>', $id, $name, $size, htmlspecialchars( wp_json_encode( $instructor ) ), $placeholder, $course_id );
	}

	/**
	 * Field: Bulk Grant Access.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_bulkgrantaccess( $field ) {
		$field = wp_parse_args( $field, array(
			'course_id' => 0,
		) );

		$course_id = ! empty( $field['course_id'] ) ? absint( $field['course_id'] ) : 0;

		printf( '<wpcw-field-bulk-grant-access course_id="%s"></wpcw-field-bulk-grant-access>', $course_id );
	}

	/**
	 * Field: Reset Progress.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 */
	public function field_resetprogress( $field ) {
		$field = wp_parse_args( $field, array(
			'course_id' => 0,
		) );

		$course_id = ! empty( $field['course_id'] ) ? absint( $field['course_id'] ) : 0;

		printf( '<wpcw-field-reset-progress course_id="%s"></wpcw-field-reset-progress>', absint( $course_id ) );
	}

	/**
	 * Field: Installments.
	 *
	 * @since 4.6.0
	 *
	 * @param array $field The field data.
	 */
	public function field_installments( $field ) {
		$field = wp_parse_args( $field, array(
			'id'     => '',
			'fields' => array()
		) );

		if ( empty( $field['fields'] ) ) {
			return;
		}

		$id = $this->get_field_id( $field );

		echo '<div class="wpcw-installment-sub-fields">';
		foreach ( $field['fields'] as $sub_field ) {
			echo $this->render_field( $sub_field );
		}
		echo '</div>';
	}

	/** Validation ----------------------------------- */

	/**
	 * Validate Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $type The field type.
	 * @param string $name The field name.
	 * @param mixed  $value The field value.
	 *
	 * @return mixed The field value.
	 */
	public function validate_field( $type, $name, $value ) {
		if ( is_callable( array( $this, 'validate_' . $name . '_field' ) ) ) {
			$value = $this->{'validate_' . $name . '_field'}( $name, $value );
		} elseif ( is_callable( array( $this, 'validate_' . $type . '_field' ) ) ) {
			$value = $this->{'validate_' . $type . '_field'}( $name, $value );
		} elseif ( has_filter( "wpcw_fields_validate_field_{$type}" ) ) {
			$value = apply_filters( "wpcw_fields_validate_field_{$type}", $value );
		} elseif ( has_filter( "wpcw_fields_validate_field_{$name}" ) ) {
			$value = apply_filters( "wpcw_fields_validate_field_{$name}", $value );
		} else {
			$value = $this->validate_text_field( $name, $value );
		}

		return $value;
	}

	/**
	 * Validate Text Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.4.0
	 *
	 * @param string      $name The field name.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_text_field( $name, $value ) {
		if ( ! is_null( $value ) && ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		// Backwards Compatability.
		if ( is_array( $value ) ) {
			$new_value_array = array();

			foreach ( $value as $select_key => $select_value ) {
				if ( 'on' === $select_value ) {
					$new_value_array[] = $select_key;
				}
			}

			if ( ! empty( $new_value_array ) ) {
				$value = $new_value_array;
			}
		}

		$value = is_null( $value ) ? '' : $value;

		return ! is_array( $value ) ? wp_kses_post( trim( stripslashes( $value ) ) ) : $value;
	}

	/**
	 * Validate Wysywig Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.4.0
	 *
	 * @param string      $name The field name.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_wysiwyg_field( $name, $value ) {
		$value = is_null( $value ) ? '' : $value;

		return wp_kses_post( $value );
	}

	/**
	 * Validate Checkbox Field.
	 *
	 * If not set, return "no", otherwise return "yes".
	 *
	 * @since 4.5.0
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
	 * Validate True / False Field.
	 *
	 * If not set, return false, otherwise return true.
	 *
	 * @since 4.5.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_truefalse_field( $key, $value ) {
		return ( 'on' === $value || true === $value ) ? true : false;
	}

	/**
	 * Validate Prerequisites Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.4.0
	 *
	 * @param string      $name The field name.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_prerequisites_field( $name, $value ) {
		if ( ! is_null( $value ) && ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		// Backwards Compatability.
		if ( is_array( $value ) ) {
			$new_value_array = array();

			foreach ( $value as $prerequisite_key => $prerequisite_value ) {
				if ( 'on' === $prerequisite_value ) {
					$new_value_array[] = $prerequisite_key;
				}
			}

			if ( ! empty( $new_value_array ) ) {
				$value = $new_value_array;
			}
		}

		$value = is_null( $value ) ? '' : $value;

		return $value;
	}

	/**
	 * Validate Course Bundles Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.6.0
	 *
	 * @param string      $name The field name.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_course_bundles_field( $name, $value ) {
		if ( ! is_null( $value ) && ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		// Backwards Compatability.
		if ( is_array( $value ) ) {
			$new_value_array = array();

			foreach ( $value as $prerequisite_key => $prerequisite_value ) {
				if ( 'on' === $prerequisite_value ) {
					$new_value_array[] = $prerequisite_key;
				}
			}

			if ( ! empty( $new_value_array ) ) {
				$value = $new_value_array;
			}
		}

		$value = is_null( $value ) ? '' : $value;

		return $value;
	}

	/**
	 * Validate Courses Select Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.5.0
	 *
	 * @param string      $name The field name.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_coursesselect_field( $name, $value ) {
		if ( ! is_null( $value ) && ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		if ( ! is_array( $value ) ) {
			$value = array_filter( (array) explode( ',', $value ) );
		}

		$value = is_null( $value ) ? '' : $value;

		return $value;
	}

	/** Misc --------------------------------------- */

	/**
	 * Get Post Data.
	 *
	 * @since 4.4.0
	 *
	 * @return array The POSTed data, to be used to save the settings.
	 */
	public function get_post_data() {
		return $_POST;
	}

	/**
	 * Ignore Field?
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return bool True if we are to ignore the field, false otherwise.
	 */
	public function ignore_field( $field ) {
		return isset( $field['ignore'] ) && $field['ignore'] ? true : false;
	}

	/**
	 * Get Top Label Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The top label fields.
	 */
	public function get_top_label_fields() {
		return apply_filters( 'wpcw_fields_top_label_fields', array( 'accordion' ) );
	}

	/**
	 * Get View.
	 *
	 * @since 4.3.0
	 *
	 * @param string $template The template name.
	 *
	 * @return string The template contents.
	 */
	public function get_view( $view ) {
		if ( ! file_exists( $view ) ) {
			$view = WPCW_ADMIN_PATH . "views/{$view}.php";
			if ( ! file_exists( $view ) ) {
				return '';
			}
		}

		ob_start();

		include $view;

		return ob_get_clean();
	}
}
