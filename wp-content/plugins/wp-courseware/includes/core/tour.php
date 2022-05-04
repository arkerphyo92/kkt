<?php
/**
 * WP Courseware License.
 *
 * @packcage WPCW
 * @since 4.6.3
 * @subpackage Core
 */

namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Tour.
 *
 * @since 4.6.3
 */
final class Tour {

	/**
	 * @var array The tour array.
	 * @since 4.6.3
	 */
	protected $tour = array();

	/**
	 * @var bool Is tour enabled?
	 * @since 4.6.3
	 */
	protected $enabled = false;

	/**
	 * Load Tour.
	 *
	 * @since 4.6.3
	 */
	public function load() {
		add_action( 'wpcw_admin_load_scripts', array( $this, 'setup_tour' ) );
		add_filter( 'wpcw_admin_js_vars', array( $this, 'setup_tour_js_vars' ) );
		add_filter( 'wpcw_admin_tour-wpcw_course', array( $this, 'tour_course' ) );
	}

	/**
	 * Setup Tours.
	 *
	 * @since 4.6.3
	 *
	 * @param \WP_Screen $admin_screen The Admin Screen
	 */
	public function setup_tour( $admin_screen ) {
		if ( ! $admin_screen ) {
			return;
		}

		$current_screen = $admin_screen;

		/**
		 * Filter: WP Courseware Admin Tour.
		 *
		 * @since 4.6.3
		 *
		 * @param array The tour array.
		 * @param Tour $this The Tour object.
		 *
		 * @return array The tour array.
		 */
		$this->tour = apply_filters( "wpcw_admin_tour-{$current_screen->id}", array(), $this );

		if ( ! is_array( $this->tour ) || empty( $this->tour ) || empty( $this->tour['id'] ) || empty( $this->tour['steps'] ) ) {
			return;
		}

		$dismissed     = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$courses_count = wpcw()->courses->get_courses_count();

		if ( ! apply_filters( 'wpcw_tour_force_enable', false ) && ( in_array( $this->tour['id'], $dismissed ) || $courses_count > 0 ) ) {
			return;
		}

		$default_tour_buttons = apply_filters( "wpcw_admin_tour_{$current_screen->id}_default_buttons", array(
			'close'  => esc_html__( 'Dismiss', 'wp-courseware' ),
			'next'   => esc_html__( 'Next', 'wp-courseware' ),
			'finish' => esc_html__( 'Finish', 'wp-courseware' ),
		) );

		$this->tour['buttons'] = ! empty( $this->tour['buttons'] )
			? array_merge( $default_tour_buttons, $this->tour['buttons'] )
			: $default_tour_buttons;

		foreach ( $this->tour['steps'] as $id => $step ) {
			if ( empty( $step['target'] ) || empty( $step['options'] ) ) {
				unset( $this->tour['steps'][ $id ] );
				continue;
			}
		}

		if ( empty( $this->tour['id'] ) || empty( $this->tour['buttons'] ) || empty( $this->tour['steps'] ) ) {
			return;
		}

		$this->enabled = true;

		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
	}

	/**
	 * Setup Tour JS Variables.
	 *
	 * @since 4.6.3
	 *
	 * @param array $js_vars The javascript variables.
	 *
	 * @return array $js_vars The javsript variables.
	 */
	public function setup_tour_js_vars( $js_vars ) {
		if ( ! $this->enabled ) {
			return $js_vars;
		}

		return array_merge( $js_vars, array( 'tour' => $this->tour ) );
	}

	/**
	 * Course Tour.
	 *
	 * @since 4.6.3
	 *
	 * @param array $tours The tours array.
	 *
	 * @return array $tours The new tours array.
	 */
	public function tour_course( $tour ) {
		return apply_filters( 'wpcw_admin_tour_course_vars', array(
			'id'    => 'wpcw-course-tour',
			'steps' => array(
				'title'          => array(
					'target'       => '#title',
					'next'         => 'description',
					'next_trigger' => array(
						'target' => '#title',
						'event'  => 'input',
					),
					'options'      => array(
						'content'  => sprintf(
							'<h3>%s</h3> <p>%s</p>',
							esc_html__( 'Course Title', 'wp-courseware' ),
							esc_html__( 'Give your new course a title here. This is a required field and will be what your students will see.', 'wp-courseware' )
						),
						'position' => array(
							'edge'  => 'top',
							'align' => 'left',
						),
					),
				),
				'description'    => array(
					'target'  => '#course-desc',
					'next'    => 'certificates',
					'before'  => array(
						'target' => '#wpcw-tab-link-description',
						'event'  => 'click',
					),
					'options' => array(
						'content'  => sprintf(
							'<h3>%s</h3> <p>%s</p>',
							esc_html__( 'Course Description', 'wp-courseware' ),
							esc_html__( 'This is your courses main description. Here you should describe your course in detail.', 'wp-courseware' )
						),
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'certificates'   => array(
					'target'  => '#certificates',
					'next'    => 'course_builder',
					'before'  => array(
						'target' => '#wpcw-tab-link-certificates',
						'event'  => 'click',
					),
					'options' => array(
						'content'  => sprintf(
							'<h3>%s</h3> <p>%s</p>',
							esc_html__( 'Enable Certificates?', 'wp-courseware' ),
							esc_html__( 'By enabling certificates, each student will recieve a generated PDF certificate when the course has been completed.', 'wp-courseware' )
						),
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'course_builder' => array(
					'target'  => '#wpcw-course-builder-metabox',
					'next'    => 'submitdiv',
					'options' => array(
						'content'  => sprintf(
							'<h3>%s</h3> <p>%s</p>',
							esc_html__( 'Course Builder', 'wp-courseware' ),
							esc_html__( 'Use the course builder to add Modules, Units, and Quizzes to build out your course.', 'wp-courseware' )
						),
						'position' => array(
							'edge'  => 'top',
							'align' => 'left',
						),
					),
				),
				'submitdiv'      => array(
					'target'  => '#submitdiv',
					'next'    => '',
					'options' => array(
						'content'  => sprintf(
							'<h3>%s</h3> <p>%s</p>',
							esc_html__( 'Publish Course!', 'wp-courseware' ),
							esc_html__( 'When you are finished editing your course, hit the "Publish" button to publish your course.', 'wp-courseware' )
						),
						'position' => array(
							'edge'  => 'right',
							'align' => 'middle',
						),
					),
					'last'    => true,
				),
			),
		) );
	}
}
