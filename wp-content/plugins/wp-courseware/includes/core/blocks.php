<?php
/**
 * WP Courseware Gutenber Blocks Support.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.5.1
 */

namespace WPCW\Core;

use WP_REST_Request;
use WPCW\Models\Course;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Blocks.
 *
 * @since 4.5.1
 */
final class Blocks {

	/**
	 * Load Blocks.
	 *
	 * @since 4.5.1
	 */
	public function load() {
		// Post Type Support.
		add_filter( 'gutenberg_can_edit_post_type', array( $this, 'post_type_support' ), 5, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'post_type_support' ), 5, 2 );

		// Register Blocks.
		add_action( 'init', array( $this, 'register_editor_blocks' ) );

		// Register Assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_editor_assets' ) );

		// Register Block Category.
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );

		// Api Endpoints.
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Blocks Post Type Support.
	 *
	 * @since 4.5.1
	 *
	 * @param bool   $use_block_editor Whether the post type can be edited or not. Default true.
	 * @param string $post_type The post type being checked.
	 */
	public function post_type_support( $use_block_editor, $post_type ) {
		$disabled_post_types = apply_filters( 'wpcw_blocks_disabled_post_types', array( wpcw()->courses->post_type_slug ) );
		$enabled_post_types  = apply_filters( 'wpcw_blocks_enabled_post_types', array( wpcw()->units->post_type_slug ) );

		if ( in_array( $post_type, $disabled_post_types, true ) ) {
			$use_block_editor = false;
		}

		if ( in_array( $post_type, $enabled_post_types, true ) ) {
			$use_block_editor = true;
		}

		return $use_block_editor;
	}

	/**
	 * Register Editor Blocks.
	 *
	 * @since 4.5.1
	 */
	public function register_editor_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Blocks.
		$this->register_courses_block();
		$this->register_course_block();
		$this->register_course_enroll_block();
		$this->register_course_progress_block();
		$this->register_course_progressbar_block();
		$this->register_certificate_student_name_block();
		$this->register_certificate_text_field_block();
		$this->register_certificate_course_title_block();
		$this->register_certificate_instructor_name_block();
		$this->register_certificate_cumulative_grade_block();
		$this->register_certificate_image_block();
		$this->register_certificate_expiry_date_block();
		$this->register_certificate_certificate_number_block();
		$this->register_certificate_line_separator_block();
	}

	/**
	 * Register Editor Assets.
	 *
	 * @since 4.5.1
	 */
	public function register_editor_assets() {
		$post_type = get_post_type();

		wp_enqueue_script( 'wpcw-blocks-js', wpcw_js_file( 'blocks.js' ), array( 'wp-i18n', 'wp-editor', 'wp-element', 'wp-blocks', 'wp-components' ), WPCW_VERSION );
		wp_localize_script(
			'wpcw-blocks-js',
			'wpcwBlocks',
			apply_filters(
				'wpcw_blocks_js_params',
				array(
					'site_url'    => site_url(),
					'api_prefix'  => wpcw()->api->get_rest_api_namespace(),
					'api_url'     => wpcw()->api->get_rest_api_url(),
					'api_nonce'   => wpcw()->api->get_rest_api_nonce(),
					'post_type' => $post_type,
					'courses'     => apply_filters(
						'wpcw_block_courses_attributes',
						array(
							'number'      => 10,
							'orderby'     => 'date',
							'order'       => 'ASC',
							'show_image'  => true,
							'show_desc'   => true,
							'show_button' => true,
						)
					),
					'course'      => apply_filters(
						'wpcw_block_course_attributes',
						array(
							'course'          => 0,
							'module'          => 0,
							'module_desc'     => false,
							'show_title'      => false,
							'show_desc'       => false,
							'user_quiz_grade' => false,
						)
					),
					'enroll'      => apply_filters(
						'wpcw_block_course_enroll_attributes',
						array(
							'enroll_text'      => esc_html__( 'Enroll Now', 'wp-courseware' ),
							'purchase_text'    => esc_html__( 'Purchase', 'wp-courseware' ),
							'display_messages' => true,
							'redirect'         => false,
						)
					),
					'progress'    => apply_filters(
						'wpcw_block_course_progress_attributes',
						array(
							'course_desc'          => false,
							'course_prerequisites' => false,
							'user_progress'        => true,
							'user_grade'           => true,
							'user_quiz_grade'      => false,
							'certificate'          => true,
							'hide_credit_link'     => false,
							'reset_progress'       => false,
						)
					),
					'progressbar' => apply_filters(
						'wpcw_block_course_progressbar_attributes',
						array(
							'course'     => 0,
							'show_title' => false,
							'show_desc'  => false,
						)
					),
				)
			)
		);
		wp_enqueue_style( 'wpcw-blocks-css', wpcw_css_file( 'blocks.css' ), array( 'wp-edit-blocks' ), WPCW_VERSION );
	}

	/**
	 * Register Block Category.
	 *
	 * @since 4.5.1
	 *
	 * @param array    $categories The block categories.
	 * @param \WP_Post $post The post object.
	 *
	 * @return array $categories The block categories.
	 */
	public function register_block_category( $categories, $post ) {
		$categories[] = array(
			'title' => esc_html__( 'WP Courseware Blocks', 'wp-courseware' ),
			'slug'  => 'wpcw-blocks',
			'icon'  => null,
		);

		return $categories;
	}

	/** Courses Block -------------------------------------- */

	/**
	 * Register Block: Courses
	 *
	 * @since 4.5.1
	 */
	protected function register_courses_block() {
		register_block_type( 'wpcw/courses' );
	}

	/**
	 * Register Block: Course
	 *
	 * @since 4.5.1
	 */
	protected function register_course_block() {
		register_block_type( 'wpcw/course' );
	}

	/**
	 * Register Block: Course Enroll
	 *
	 * @since 4.5.1
	 */
	protected function register_course_enroll_block() {
		register_block_type( 'wpcw/enroll' );
	}

	/**
	 * Register Block: Course Progress
	 *
	 * @since 4.5.1
	 */
	protected function register_course_progress_block() {
		register_block_type( 'wpcw/progress' );
	}

	/**
	 * Register Block: Course Progress Bar
	 *
	 * @since 4.6.0
	 */
	protected function register_course_progressbar_block() {
		register_block_type( 'wpcw/progressbar' );
	}

	/**
	 * Register Block: Certificate Student Name
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_student_name_block() {
		register_block_type(
			'wpcw/student-name',
			array(
				'attributes'      => array(
					'fontSize'      => array(
						'type' => 'string',
					),
					'fontFamily'    => array(
						'type' => 'string',
					),
					'textColor'     => array(
						'type' => 'string',
					),
					'textAlign'     => array(
						'type' => 'string',
					),
					'fontWeight'    => array(
						'type' => 'string',
					),
					'fontStyle'     => array(
						'type' => 'string',
					),
					'textUnderline' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'render_callback' => 'wpcw_certificate_student_name_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Text Field
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_text_field_block() {
		register_block_type(
			'wpcw/text-field',
			array(
				'attributes'      => array(
					'fontSize'      => array(
						'type' => 'string',
					),
					'fontFamily'    => array(
						'type' => 'string',
					),
					'textColor'     => array(
						'type' => 'string',
					),
					'textAlign'     => array(
						'type' => 'string',
					),
					'fontWeight'    => array(
						'type' => 'string',
					),
					'fontStyle'     => array(
						'type' => 'string',
					),
					'textUnderline' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'textField'     => array(
						'type' => 'string',
					),
				),
				'render_callback' => 'wpcw_certificate_text_field_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Course Title
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_course_title_block() {
		register_block_type(
			'wpcw/course-title',
			array(
				'attributes'      => array(
					'fontSize'      => array(
						'type' => 'string',
					),
					'fontFamily'    => array(
						'type' => 'string',
					),
					'textColor'     => array(
						'type' => 'string',
					),
					'textAlign'     => array(
						'type' => 'string',
					),
					'fontWeight'    => array(
						'type' => 'string',
					),
					'fontStyle'     => array(
						'type' => 'string',
					),
					'textUnderline' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'render_callback' => 'wpcw_certificate_course_title_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Instructor Name
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_instructor_name_block() {
		register_block_type(
			'wpcw/instructor-name',
			array(
				'attributes'      => array(
					'fontSize'        => array(
						'type' => 'string',
					),
					'fontFamily'      => array(
						'type' => 'string',
					),
					'textColor'       => array(
						'type' => 'string',
					),
					'textAlign'       => array(
						'type' => 'string',
					),
					'fontWeight'      => array(
						'type' => 'string',
					),
					'fontStyle'       => array(
						'type' => 'string',
					),
					'textUnderline'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'instructorTitle' => array(
						'type' => 'string',
					),
				),
				'render_callback' => 'wpcw_certificate_instructor_name_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Cumulative Grad
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_cumulative_grade_block() {
		register_block_type(
			'wpcw/cumulative-grade',
			array(
				'attributes'      => array(
					'fontSize'        => array(
						'type' => 'string',
					),
					'fontFamily'      => array(
						'type' => 'string',
					),
					'textColor'       => array(
						'type' => 'string',
					),
					'textAlign'       => array(
						'type' => 'string',
					),
					'fontWeight'      => array(
						'type' => 'string',
					),
					'fontStyle'       => array(
						'type' => 'string',
					),
					'textUnderline'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'cumulativeGrade' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'render_callback' => 'wpcw_certificate_cumulative_grade_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Image/Signature
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_image_block() {
		register_block_type(
			'wpcw/image',
			array(
				'attributes'      => array(
					'selectedImage' => array(
						'type' => 'string',
					),
				),
				'render_callback' => 'wpcw_certificate_image_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Expiry Date
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_expiry_date_block() {
		register_block_type(
			'wpcw/expiry-date',
			array(
				'attributes'      => array(
					'fontSize'      => array(
						'type' => 'string',
					),
					'fontFamily'    => array(
						'type' => 'string',
					),
					'textColor'     => array(
						'type' => 'string',
					),
					'textAlign'     => array(
						'type' => 'string',
					),
					'fontWeight'    => array(
						'type' => 'string',
					),
					'fontStyle'     => array(
						'type' => 'string',
					),
					'textUnderline' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'dateFormat'    => array(
						'type' => 'string',
					),
				),
				'render_callback' => 'wpcw_certificate_expiry_date_render_callback',
			)
		);
	}

	/**
	 * Register Block: Certificate Number
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_certificate_number_block() {
		register_block_type(
			'wpcw/certificate-number',
			array(
				'attributes'      => array(
					'fontSize'          => array(
						'type' => 'string',
					),
					'fontFamily'        => array(
						'type' => 'string',
					),
					'textColor'         => array(
						'type' => 'string',
					),
					'textAlign'         => array(
						'type' => 'string',
					),
					'fontWeight'        => array(
						'type' => 'string',
					),
					'fontStyle'         => array(
						'type' => 'string',
					),
					'textUnderline'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'numberPrefix'      => array(
						'type' => 'string',
					),
					'numberLength'      => array(
						'type' => 'string',
					),
					'certificateNumber' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'render_callback' => 'wpcw_certificate_certificate_number_render_callback',
			)
		);
	}

	/**
	 * Register Block: Line Separator
	 *
	 * @since 4.8.0
	 */
	protected function register_certificate_line_separator_block() {
		register_block_type(
			'wpcw/line-separator',
			array(
				'attributes'      => array(
					'lineColor'  => array(
						'type' => 'string',
					),
					'lineHeight' => array(
						'type' => 'string',
					),
					'lineWidth'  => array(
						'type' => 'string',
					),
				),
				'render_callback' => 'wpcw_certificate_line_separator_render_callback',
			)
		);
	}
	/** Api Endpoints -------------------------------------- */

	/**
	 * Register Course Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array              $endpoints The endpoints to filter.
	 * @param Api The api object.
	 * s
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array(
			'endpoint' => 'block/courses',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_block_courses' ),
		);
		$endpoints[] = array(
			'endpoint' => 'block/course',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_block_course' ),
		);
		$endpoints[] = array(
			'endpoint' => 'block/enroll',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_block_enroll' ),
		);
		$endpoints[] = array(
			'endpoint' => 'block/progress',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_block_progress' ),
		);
		$endpoints[] = array(
			'endpoint' => 'block/progressbar',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_block_progressbar' ),
		);

		return $endpoints;
	}

	/**
	 * Api: Get Block Courses.
	 *
	 * @since 4.5.1
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_block_courses( WP_REST_Request $request ) {
		$search  = $request->get_param( 'search' );
		$number  = $request->get_param( 'number' );
		$order   = $request->get_param( 'order' );
		$orderby = $request->get_param( 'orderby' );
		$author  = $request->get_param( 'author' );
		$status  = $request->get_param( 'status' );

		if ( ! $number ) {
			$number = 1000;
		}

		if ( ! $orderby ) {
			$orderby = 'date';
		}

		if ( ! $order ) {
			$order = 'DESC';
		}

		if ( ! $status ) {
			$status = array( 'publish', 'private' );
		}

		$query_args = array(
			'number'  => $number,
			'order'   => $order,
			'orderby' => $orderby,
			'status'  => $status,
		);

		if ( $search ) {
			$query_args['search'] = $search;
		}

		if ( $author ) {
			$query_args['course_author'] = absint( $author );
		}

		$results = array();
		$courses = wpcw()->courses->get_courses( $query_args );
		$count   = wpcw()->courses->get_courses_count( $query_args );

		foreach ( $courses as $course ) {
			if ( ! $course instanceof Course ) {
				continue;
			}

			$results[] = array(
				'id'           => $course->get_course_id(),
				'title'        => $course->get_course_title(),
				'url'          => $course->get_permalink(),
				'image'        => $course->get_thumbnail_image(),
				'desc'         => $course->get_course_desc(),
				'purchaseable' => $course->is_purchasable(),
				'button'       => $course->get_enrollment_button( array( 'display_raw' => true ) ),
				'recurring'    => ( 'subscription' === $course->get_payments_type() ) ? true : false,
				'price'        => $course->get_payments_price(),
			);
		}

		return rest_ensure_response( array( 'courses' => $results ) );
	}

	/**
	 * Api: Get Block Course.
	 *
	 * @since 4.5.1
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_block_course( WP_REST_Request $request ) {
		$shortcode_params = $request->get_params();

		$course = wpcw()->shortcodes->course_shortcode( $shortcode_params, '' );

		return rest_ensure_response( array( 'course' => $course ) );
	}

	/**
	 * Api: Get Block Enroll.
	 *
	 * @since 4.5.1
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_block_enroll( WP_REST_Request $request ) {
		$shortcode_params = $request->get_params();

		$shortcode_params['display_raw'] = true;

		$enroll_button = wpcw()->shortcodes->course_enroll_shortcode( $shortcode_params, '' );

		return rest_ensure_response( array( 'button' => $enroll_button ) );
	}

	/**
	 * Api: Get Block Progress.
	 *
	 * @since 4.5.1
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_block_progress( WP_REST_Request $request ) {
		$shortcode_params = $request->get_params();

		$progress = wpcw()->shortcodes->course_progress_shortcode( $shortcode_params, '' );

		return rest_ensure_response( array( 'progress' => $progress ) );
	}

	/**
	 * Api: Get Block Progress Bar.
	 *
	 * @since 4.6.0
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_block_progressbar( WP_REST_Request $request ) {
		$shortcode_params = $request->get_params();

		$progress = wpcw()->shortcodes->course_progress_bar_shortcode( $shortcode_params, '' );

		return rest_ensure_response( array( 'progress' => $progress ) );
	}
}
