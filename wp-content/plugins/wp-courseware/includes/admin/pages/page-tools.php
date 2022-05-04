<?php
/**
 * WP Courseware Tools Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.3.0
 */
namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Tools.
 *
 * @since 4.3.0
 */
class Page_Tools extends Page {

	/**
	 * Get Tools Menu Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Tools', 'wp-courseware' );
	}

	/**
	 * Get Tools Page Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Tools', 'wp-courseware' );
	}

	/**
	 * Get Tools Page Capability.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_tools_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Tools Page Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-tools';
	}

	/**
	 * Get Tabs.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|void
	 */
	public function get_tabs() {
		return apply_filters( 'wpcw_tools_tabs', array(
			'export'    => array(
				'label'   => esc_html__( 'Export', 'wp-courseware' ),
				'default' => true,
				'form'    => false,
				'fields'  => array(
					array(
						'type'  => 'heading',
						'key'   => 'export_course_section_heading',
						'title' => esc_html__( 'Export Course', 'wp-courseware' ),
					),
					array(
						'type' => 'exportcourse',
						'key'  => 'exportcourse',
					),
				),
			),
			'import'    => array(
				'label'    => esc_html__( 'Import', 'wp-courseware' ),
				'default'  => false,
				'sections' => array(
					'course'         => array(
						'label'   => esc_html__( 'Course', 'wp-courseware' ),
						'default' => true,
						'form'    => false,
						'fields'  => array(
							array(
								'type'  => 'heading',
								'key'   => 'import_course_section_heading',
								'title' => esc_html__( 'Import Course', 'wp-courseware' ),
							),
							array(
								'type' => 'importcourse',
								'key'  => 'importcourse',
							),
						),
					),
					'users'          => array(
						'label'  => esc_html__( 'Users', 'wp-courseware' ),
						'form'   => false,
						'fields' => array(
							array(
								'type'  => 'heading',
								'key'   => 'import_users_section_heading',
								'title' => esc_html__( 'Import Users', 'wp-courseware' ),
							),
							array(
								'type' => 'importusers',
								'key'  => 'importusers',
							),
						),
					),
					'quiz-questions' => array(
						'label'  => esc_html__( 'Quiz Questions', 'wp-courseware' ),
						'form'   => false,
						'fields' => array(
							array(
								'type'  => 'heading',
								'key'   => 'import_quiz_questions_section_heading',
								'title' => esc_html__( 'Import Quiz Quesitons', 'wp-courseware' ),
							),
							array(
								'type' => 'importquizquestions',
								'key'  => 'importquizquestions',
							),
						),
					),
				),
			),
			'system'    => array(
				'label'  => esc_html__( 'System Info', 'wp-courseware' ),
				'form'   => false,
				'fields' => array(
					array(
						'key'       => 'system',
						'type'      => 'system',
						'component' => true,
						'views'     => array( 'tools/tools-field-system' ),
					),
				),
			),
			'logs'      => array(
				'label'  => esc_html__( 'System Log', 'wp-courseware' ),
				'form'   => false,
				'fields' => array(
					array(
						'key'       => 'log',
						'type'      => 'log',
						'component' => true,
						'views'     => array( 'tools/tools-field-log' ),
					),
				),
			),
			'utilities' => array(
				'label'  => esc_html__( 'Utilities', 'wp-courseware' ),
				'form'   => false,
				'fields' => array(
					array(
						'key'       => 'utilities',
						'type'      => 'utilities',
						'component' => true,
						'views'     => array( 'tools/tools-field-utilities' ),
					),
				),
			),
		) );
	}

	/**
	 * Tools Page Display.
	 *
	 * @since 4.3.0
	 */
	protected function display() {
		do_action( 'wpcw_admin_tools_before', $this );

		echo '<div id="wpcw-tools">';

		echo '<wpcw-notices>' . do_action( 'wpcw_admin_notices' ) . '</wpcw-notices>';

		$this->get_tabs_navigation();
		$this->get_tab_content();

		echo '</div>';

		do_action( 'wpcw_admin_tools_after', $this );
	}

	/**
	 * Field: Import Course.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The import course field html.
	 */
	public function generate_importcourse_field_html( $key, $field ) {
		ob_start();

		WPCW_showPage_ImportExport_import();

		return ob_get_clean();
	}

	/**
	 * Field: Import Users.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The import users field html.
	 */
	public function generate_importusers_field_html( $key, $field ) {
		ob_start();

		WPCW_showPage_ImportExport_importUsers();

		return ob_get_clean();
	}

	/**
	 * Field: Import Users.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The import questions field html.
	 */
	public function generate_importquizquestions_field_html( $key, $field ) {
		ob_start();

		WPCW_showPage_ImportExport_importQuestions();

		return ob_get_clean();
	}

	/**
	 * Field: Export Course.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The export course field html.
	 */
	public function generate_exportcourse_field_html( $key, $field ) {
		ob_start();

		WPCW_showPage_ImportExport_export();

		return ob_get_clean();
	}

	/**
	 * Field: System
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The generate system field html.
	 */
	public function generate_system_field_html( $key, $field ) {
		return '<wpcw-tools-field-system></wpcw-tools-field-system>';
	}

	/**
	 * Field: Log
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The generate system field html.
	 */
	public function generate_log_field_html( $key, $field ) {
		return '<wpcw-tools-field-log></wpcw-tools-field-log>';
	}

	/**
	 * Field: Utilites
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array $field The current field data.
	 *
	 * @return string The generate utilities field html.
	 */
	public function generate_utilities_field_html( $key, $field ) {
		return '<wpcw-tools-field-utilities></wpcw-tools-field-utilities>';
	}
}