<?php
/**
 * WP Courseware Styles.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */
namespace WPCW\Core;

use WP_Customize_Color_Control;
use WP_Customize_Manager;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Styles.
 *
 * @since 4.6.3
 */
class Styles {

	/**
	 * @var string Main Panel Id.
	 * @since 4.2.0
	 */
	protected $panel = 'wpcw';

	/**
	 * @var string Settings Key.
	 * @since 4.2.0
	 */
	protected $key;

	/**
	 * Load Styles.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_init', array( $this, 'register_styles_customizer' ) );
	}

	/**
	 * Register Styles Customizer.
	 *
	 * @since 4.3.0
	 */
	public function register_styles_customizer() {
		$this->key = wpcw()->settings->get_key();

		$use_stylesheet   = wpcw()->settings->get_setting( 'use_default_css', 'yes' );
		$customize_colors = wpcw()->settings->get_setting( 'customize_colors', 'no' );

		if ( 'no' === $use_stylesheet || 'no' === $customize_colors ) {
			return;
		}

		add_action( 'customize_register', array( $this, 'register_customizer' ) );
		add_action( 'customize_preview_init', array( $this, 'preview_customizer' ) );
	}

	/**
	 * Get Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The styles settings fields.
	 */
	public function get_settings_fields() {
		return apply_filters( 'wpcw_styles_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'styles_settings_section_heading',
				'title' => esc_html__( 'Styles', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings that determine the look and feel of WP Courseware.', 'wp-courseware' ),
			),
			array(
				'type'      => 'styles',
				'key'       => 'styles',
				'component' => true,
				'views'     => array( 'settings/settings-field-styles', 'settings/settings-field-style-colors' ),
				'settings'  => $this->get_style_color_settings(),
			),
		) );
	}

	/**
	 * Get Style Color Settings.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of setting inputs.
	 */
	public function get_style_color_settings() {
		$settings = array();
		$colors   = $this->get_colors();

		$settings[] = array(
			'key'     => 'use_default_css',
			'type'    => 'radio',
			'default' => 'yes',
		);

		$settings[] = array(
			'key'     => 'customize_colors',
			'type'    => 'radio',
			'default' => 'no',
		);

		foreach ( $colors as $id => $color ) {
			if ( ! empty( $color['settings'] ) ) {
				foreach ( $color['settings'] as $setting => $args ) {
					$default    = ( ! empty( $args['default'] ) ) ? esc_attr( $args['default'] ) : '';
					$settings[] = array(
						'key'     => $setting,
						'type'    => 'color_picker',
						'default' => $default,
					);
				}
			}
		}

		return $settings;
	}

	/**
	 * Register Customizer Panel.
	 *
	 * @since 4.2.0
	 *
	 * @param WP_Customize_Manager $wp_customize
	 */
	public function register_customizer( WP_Customize_Manager $wp_customize ) {
		$this->register_panel( $wp_customize );

		$colors = $this->get_colors();

		if ( ! empty( $colors ) ) {
			foreach ( $colors as $id => $color ) {
				$label    = ! empty( $color['label'] ) ? $color['label'] : '';
				$desc     = ! empty( $color['desc'] ) ? $color['desc'] : '';
				$settings = ! empty( $color['settings'] ) ? $color['settings'] : array();

				$this->register_color_section( $wp_customize, $id, $label, $settings, $desc );
			}
		}

		/**
		 * Register Customizer Sections.
		 *
		 * @since 4.2.0
		 */
		do_action( 'wpcw_customizer_register', $wp_customize, $this->panel, $this->key );
	}

	/**
	 * Register Customizer Panel.
	 *
	 * @since 4.2.0
	 *
	 * @param WP_Customize_Manager $wp_customize
	 */
	protected function register_panel( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_panel( $this->panel, array(
			'title'    => esc_html__( 'WP Courseware Colors', 'wp-courseware' ),
			'priority' => 200,
		) );
	}

	/**
	 * Register Color Section.
	 *
	 * @since 4.2.0
	 *
	 * @param WP_Customize_Manager $wp_customize
	 * @param string               $id The Section id.
	 * @param string               $label The section label.
	 * @param string               $desc The section description.
	 * @param string               $settings The section settings.
	 */
	protected function register_color_section( WP_Customize_Manager $wp_customize, $id, $label, $settings, $desc = '' ) {
		$section_id = "wpcw-color-section-{$id}";

		$wp_customize->add_section( $section_id, array(
			'title'       => $label,
			'description' => $desc,
			'panel'       => $this->panel,
		) );

		if ( ! empty( $settings ) ) {
			foreach ( $settings as $setting => $args ) {
				$wp_customize->add_setting( "{$this->key}[{$setting}]", array(
					'capability' => 'manage_wpcw_settings',
					'type'       => 'option',
					'default'    => isset( $args['default'] ) ? $args['default'] : '',
					'transport'  => 'postMessage',
				) );

				$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, "{$this->key}[{$setting}]", array(
					'label'       => isset( $args['label'] ) ? esc_attr( $args['label'] ) : esc_html__( 'Setting', 'wp-courseware' ),
					'description' => isset( $args['desc'] ) ? esc_html( $args['desc'] ) : '',
					'section'     => $section_id,
					'settings'    => "{$this->key}[{$setting}]",
				) ) );
			}
		}
	}

	/**
	 * Get Customizer Preview.
	 *
	 * @since 4.2.0
	 */
	public function preview_customizer() {
		wp_enqueue_script( 'wpcw-customizer', wpcw_js_file( 'customizer.js' ), array( 'jquery', 'customize-preview' ), WPCW_VERSION, true );
		wp_localize_script( 'wpcw-customizer', 'wpcwCustomizerVars', apply_filters( 'wpcw_customizer_preview_js_vars', array(
			'key'    => $this->key,
			'colors' => $this->get_colors(),
		) ) );
	}

	/**
	 * Get Colors.
	 *
	 * @since 4.3.0
	 *
	 * @return array The colors array.
	 */
	public function get_colors() {
		$colors = array(
			'general' => array(
				'label'    => esc_html__( 'General Colors', 'wp-courseware' ),
				'desc'     => '',
				'settings' => $this->get_general_colors(),
			),
			'notice'  => array(
				'label'    => esc_html__( 'Notification Colors', 'wp-courseware' ),
				'desc'     => '',
				'settings' => $this->get_notice_colors(),
			),
			'course'  => array(
				'label'    => esc_html__( 'Course Colors', 'wp-courseware' ),
				'desc'     => '',
				'settings' => $this->get_course_colors(),
			),
			'quiz'    => array(
				'label'    => esc_html__( 'Quiz Colors', 'wp-courseware' ),
				'desc'     => '',
				'settings' => $this->get_quiz_colors(),
			),
			'unit'    => array(
				'label'    => esc_html__( 'Unit Colors', 'wp-courseware' ),
				'desc'     => '',
				'settings' => $this->get_unit_colors(),
			),
		);

		return apply_filters( 'wpcw_style_colors', $colors );
	}

	/**
	 * Get General Color.
	 *
	 * @since 4.3.0
	 *
	 * @return array The general colors array.
	 */
	public function get_general_colors() {
		return apply_filters( 'wpcw_style_colors_general', array(
			'ui_progress_fill_color' => array(
				'label'     => esc_html__( 'Progress Bar - Fill Color', 'wp-courseware' ),
				'selector'  => '.wpcw_progress_bar',
				'attribute' => 'background-color',
				'default'   => '#5CB85C',
			),
			'ui_checkmark_color'     => array(
				'label'     => esc_html__( 'Checked Icon - Color', 'wp-courseware' ),
				'selector'  => '.wpcw_checkmark',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
			),
			'ui_checkmark_bg_color'  => array(
				'label'     => esc_html__( 'Checked Icon - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_checkmark',
				'attribute' => 'background',
				'default'   => '#5CB85C',
			),
			'ui_circle_bg_color'     => array(
				'label'     => esc_html__( 'Un-Checked Icon - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_circle',
				'attribute' => 'background',
				'default'   => '#D8D8D8',
			),
		) );
	}

	/**
	 * Get Notice Colors.
	 *
	 * @since 4.3.0
	 *
	 * @return array The notice colors array.
	 */
	public function get_notice_colors() {
		return apply_filters( 'wpcw_style_colors_notifications', array(
			'ui_notice_error_color'               => array(
				'label'     => esc_html__( 'Notification - Error Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_error',
				'attribute' => 'color',
				'default'   => '#FF0000',
			),
			'ui_notice_error_bg_color'            => array(
				'label'     => esc_html__( 'Notification - Error Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_error',
				'attribute' => 'background',
				'default'   => '#FDE0E0',
			),
			'ui_notice_error_border_color'        => array(
				'label'     => esc_html__( 'Notification - Error Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_error,.wpcw_fe_progress_box_error .wpcw_fe_progress_breakdown_wrap',
				'attribute' => 'border-color',
				'default'   => '#FF0000',
			),
			'ui_notice_warning_color'             => array(
				'label'     => esc_html__( 'Notification - Warning Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_warning',
				'attribute' => 'color',
				'default'   => '#918817',
			),
			'ui_notice_warning_bg_color'          => array(
				'label'     => esc_html__( 'Notification - Warning Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_warning',
				'attribute' => 'background',
				'default'   => '#FFFFE0',
			),
			'ui_notice_warning_border_color'      => array(
				'label'     => esc_html__( 'Notification - Warning Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_warning,.wpcw_fe_progress_box_warning .wpcw_fe_progress_breakdown_wrap',
				'attribute' => 'border-color',
				'default'   => '#E6DB55',
			),
			'ui_notice_success_color'             => array(
				'label'     => esc_html__( 'Notification - Success Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_success',
				'attribute' => 'color',
				'default'   => '#008000',
			),
			'ui_notice_success_bg_color'          => array(
				'label'     => esc_html__( 'Notification - Success Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_success',
				'attribute' => 'background',
				'default'   => '#BAF5BA',
			),
			'ui_notice_success_border_color'      => array(
				'label'     => esc_html__( 'Notification - Success Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_success,.wpcw_fe_progress_box_success .wpcw_fe_progress_breakdown_wrap',
				'attribute' => 'border-color',
				'default'   => '#26C510',
			),
			'ui_notice_button_color'              => array(
				'label'     => esc_html__( 'Notification Button - Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box .wpcw_fe_progress_download .fe_btn_completion,.wpcw_fe_quiz_retake .fe_btn_completion',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
			),
			'ui_notice_button_bg_color'           => array(
				'label'     => esc_html__( 'Notification Button - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box .wpcw_fe_progress_download .fe_btn_completion,.wpcw_fe_quiz_retake .fe_btn_completion',
				'attribute' => 'background-color',
				'default'   => '#7fbf4d',
			),
			'ui_notice_button_border_color'       => array(
				'label'     => esc_html__( 'Notification Button - Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box .wpcw_fe_progress_download .fe_btn_completion,.wpcw_fe_quiz_retake .fe_btn_completion',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
			),
			'ui_notice_button_hover_color'        => array(
				'label'     => esc_html__( 'Notification Button - Hover Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box .wpcw_fe_progress_download .fe_btn_completion,.wpcw_fe_quiz_retake .fe_btn_completion',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
				'hover'     => true,
			),
			'ui_notice_button_bg_hover_color'     => array(
				'label'     => esc_html__( 'Notification Button - Hover Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box .wpcw_fe_progress_download .fe_btn_completion,.wpcw_fe_quiz_retake .fe_btn_completion',
				'attribute' => 'background-color',
				'default'   => '#76b347',
				'hover'     => true,
			),
			'ui_notice_button_border_hover_color' => array(
				'label'     => esc_html__( 'Notification Button - Hover Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box .wpcw_fe_progress_download .fe_btn_completion,.wpcw_fe_quiz_retake .fe_btn_completion',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
				'hover'     => true,
			),
		) );
	}

	/**
	 * Get Course Colors.
	 *
	 * @since 4.3.0
	 *
	 * @return array The course colors array.
	 */
	public function get_course_colors() {
		return apply_filters( 'wpcw_style_colors_course', array(
			'ui_course_complete_color'          => array(
				'label'     => esc_html__( 'Completion Box - Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_complete',
				'attribute' => 'color',
				'default'   => '#333333',
			),
			'ui_course_complete_bg_color'       => array(
				'label'     => esc_html__( 'Completion Box - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_complete',
				'attribute' => 'background',
				'default'   => '#FFFFFF',
			),
			'ui_cert_button_color'              => array(
				'label'     => esc_html__( 'Download Certificate Button - Text Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_download',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
			),
			'ui_cert_button_bg_color'           => array(
				'label'     => esc_html__( 'Download Certificate Button - Background Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_download',
				'attribute' => 'background-color',
				'default'   => '#7fbf4d',
			),
			'ui_cert_button_border_color'       => array(
				'label'     => esc_html__( 'Download Certificate Button - Border Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_download',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
			),
			'ui_cert_button_hover_color'        => array(
				'label'     => esc_html__( 'Download Certificate Button - Hover Text Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_download',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'hover'     => true,
				'important' => true,
			),
			'ui_cert_button_bg_hover_color'     => array(
				'label'     => esc_html__( 'Download Certificate Button - Hover Background Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_download',
				'attribute' => 'background-color',
				'default'   => '#76b347',
				'hover'     => true,
			),
			'ui_cert_button_border_hover_color' => array(
				'label'     => esc_html__( 'Download Certificate Button - Hover Border Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_download',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
				'hover'     => true,
			),
		) );
	}

	/**
	 * Get Quiz Colors.
	 *
	 * @since 4.3.0
	 *
	 * @return array The quiz colors array.
	 */
	public function get_quiz_colors() {
		return apply_filters( 'wpcw_style_colors_quiz', array(
			'ui_quiz_bg_color'                  => array(
				'label'     => esc_html__( 'Quiz Box - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_box_pending',
				'attribute' => 'background-color',
				'default'   => '#D9EDF7',
			),
			'ui_quiz_border_color'              => array(
				'label'     => esc_html__( 'Quiz Box - Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_box_pending',
				'attribute' => 'border-color',
				'default'   => '#3A87AD',
			),
			'ui_quiz_cfm_color'                 => array(
				'label'     => esc_html__( 'Quiz Custom Feedback Notice - Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_custom_feedback_wrap',
				'attribute' => 'color',
				'default'   => '#333333',
			),
			'ui_quiz_cfm_bg_color'              => array(
				'label'     => esc_html__( 'Quiz Custom Feedback Notice - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_custom_feedback_wrap',
				'attribute' => 'background',
				'default'   => '#efefef',
			),
			'ui_quiz_cfm_border_color'          => array(
				'label'     => esc_html__( 'Quiz Custom Feedback Notice - Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_custom_feedback_wrap',
				'attribute' => 'border-color',
				'default'   => '#CCCCCC',
			),
			'ui_quiz_q_line_border_color'       => array(
				'label'     => esc_html__( 'Quiz Box - Question Divider Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_q_single,.wpcw_fe_quiz_q_hdr',
				'attribute' => 'border-color',
				'default'   => '#3A87AD',
			),
			'ui_quiz_answers_color'             => array(
				'label'     => esc_html__( 'Quiz Box - Full Answers Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_box_full_answers b',
				'attribute' => 'color',
				'default'   => '#3A87AD',
			),
			'ui_quiz_button_color'              => array(
				'label'     => esc_html__( 'Quiz Button - Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_submit_data input.fe_btn_completion,.wpcw_fe_quiz_answer_later .fe_btn_navigation,.wpcw_fe_quiz_begin_quiz .fe_btn_completion',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
			),
			'ui_quiz_button_bg_color'           => array(
				'label'     => esc_html__( 'Quiz Button - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_submit_data input.fe_btn_completion,.wpcw_fe_quiz_answer_later .fe_btn_navigation,.wpcw_fe_quiz_begin_quiz .fe_btn_completion',
				'attribute' => 'background-color',
				'default'   => '#7fbf4d',
			),
			'ui_quiz_button_border_color'       => array(
				'label'     => esc_html__( 'Quiz Button - Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_submit_data input.fe_btn_completion,.wpcw_fe_quiz_answer_later .fe_btn_navigation,.wpcw_fe_quiz_begin_quiz .fe_btn_completion',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
			),
			'ui_quiz_button_hover_color'        => array(
				'label'     => esc_html__( 'Quiz Button - Hover Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_submit_data input.fe_btn_completion,.wpcw_fe_quiz_answer_later .fe_btn_navigation,.wpcw_fe_quiz_begin_quiz .fe_btn_completion',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
				'hover'     => true,
			),
			'ui_quiz_button_bg_hover_color'     => array(
				'label'     => esc_html__( 'Quiz Button - Hover Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_submit_data input.fe_btn_completion,.wpcw_fe_quiz_answer_later .fe_btn_navigation,.wpcw_fe_quiz_begin_quiz .fe_btn_completion',
				'attribute' => 'background-color',
				'default'   => '#76b347',
				'important' => true,
				'hover'     => true,
			),
			'ui_quiz_button_border_hover_color' => array(
				'label'     => esc_html__( 'Quiz Button - Hover Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_quiz_submit_data input.fe_btn_completion,.wpcw_fe_quiz_answer_later .fe_btn_navigation,.wpcw_fe_quiz_begin_quiz .fe_btn_completion',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
				'important' => true,
				'hover'     => true,
			),
		) );
	}

	/**
	 * Get Unit Colors.
	 *
	 * @since 4.3.0
	 *
	 * @return array The unit colors array.
	 */
	public function get_unit_colors() {
		return apply_filters( 'wpcw_style_colors_unit', array(
			'ui_unit_complete_box_color'                     => array(
				'label'     => esc_html__( 'Unit Complete Box - Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_pending',
				'attribute' => 'color',
				'default'   => '#050505',
			),
			'ui_unit_complete_box_bg_color'                  => array(
				'label'     => esc_html__( 'Unit Complete Box - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_pending',
				'attribute' => 'background',
				'default'   => '#BAF5BA',
			),
			'ui_unit_complete_box_border_color'              => array(
				'label'     => esc_html__( 'Unit Complete Box - Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_pending',
				'attribute' => 'border-color',
				'default'   => '#26C510',
			),
			'ui_unit_complete_box_button_color'              => array(
				'label'     => esc_html__( 'Unit Complete Box Button - Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_mark .fe_btn_completion',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
			),
			'ui_unit_complete_box_button_bg_color'           => array(
				'label'     => esc_html__( 'Unit Complete Box Button - Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_mark .fe_btn_completion',
				'attribute' => 'background-color',
				'default'   => '#7fbf4d',
			),
			'ui_unit_complete_box_button_border_color'       => array(
				'label'     => esc_html__( 'Unit Complete Box Button - Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_mark .fe_btn_completion',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
			),
			'ui_unit_complete_box_button_hover_color'        => array(
				'label'     => esc_html__( 'Unit Complete Box Button - Hover Text Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_mark .fe_btn_completion',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'hover'     => true,
				'important' => true,
			),
			'ui_unit_complete_box_button_bg_hover_color'     => array(
				'label'     => esc_html__( 'Unit Complete Box Button - Hover Background Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_mark .fe_btn_completion',
				'attribute' => 'background-color',
				'default'   => '#76b347',
				'hover'     => true,
			),
			'ui_unit_complete_box_button_border_hover_color' => array(
				'label'     => esc_html__( 'Unit Complete Box Button - Hover Border Color', 'wp-courseware' ),
				'selector'  => '.wpcw_fe_progress_box_mark .fe_btn_completion',
				'attribute' => 'border-color',
				'default'   => '#63a62f',
				'hover'     => true,
			),
			'ui_unit_prev_next_button_color'                 => array(
				'label'     => esc_html__( 'Unit Prev/Next Button - Text Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_navigation',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'important' => true,
			),
			'ui_unit_prev_next_button_bg_color'              => array(
				'label'     => esc_html__( 'Unit Prev/Next Button - Background Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_navigation',
				'attribute' => 'background-color',
				'default'   => '#7E84E0',
			),
			'ui_unit_prev_next_button_border_color'          => array(
				'label'     => esc_html__( 'Unit Prev/Next Button - Border Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_navigation',
				'attribute' => 'border-color',
				'default'   => '#636BDB',
			),
			'ui_unit_prev_next_button_hover_color'           => array(
				'label'     => esc_html__( 'Unit Prev/Next Button - Hover Text Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_navigation',
				'attribute' => 'color',
				'default'   => '#FFFFFF',
				'hover'     => true,
				'important' => true,
			),
			'ui_unit_prev_next_button_bg_hover_color'        => array(
				'label'     => esc_html__( 'Unit Prev/Next Button - Hover Background Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_navigation',
				'attribute' => 'background-color',
				'default'   => '#7E84E0',
				'hover'     => true,
				'important' => true,
			),
			'ui_unit_prev_next_button_border_hover_color'    => array(
				'label'     => esc_html__( 'Unit Prev/Next Button - Hover Border Color', 'wp-courseware' ),
				'selector'  => 'a.fe_btn_navigation',
				'attribute' => 'border-color',
				'default'   => '#636BDB',
				'hover'     => true,
				'important' => true,
			),
		) );
	}

	/**
	 * Get Colors in a JSON Format.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_colors_json() {
		$colors = array();
		$colors = $this->get_colors();

		foreach ( $colors as $color_section_id => $color ) {
			if ( ! empty( $color['settings'] ) ) {
				foreach ( $color['settings'] as $setting => $args ) {
					$default = ( ! empty( $args['default'] ) ) ? esc_attr( $args['default'] ) : '';
					if ( $default ) {
						$colors[ $setting ] = $default;
					}
				}
			}
		}

		return wpcw_convert_array_to_json( $colors );
	}
}
