<?php
/**
 * WP Courseware Courses Controller.
 *
 * @since 4.1.0
 * @subpackage Controllers
 * @package WPCW
 */

namespace WPCW\Controllers;

use WP_Post;
use WP_Query;
use WP_REST_Request;
use WPCW\Core\Api;
use WPCW\Database\DB_Courses;
use WPCW\Models\Course;
use WPCW\Models\Module;
use WPCW\Models\Quiz;
use WPCW\Models\Unit;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Courses.
 *
 * @since 4.3.0
 */
class Courses extends Controller {

	/**
	 * @var DB_Courses The courses database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var string The course post type slug.
	 * @since 4.4.0
	 */
	public $post_type_slug = 'wpcw_course';

	/**
	 * @var string The category slug.
	 * @since 4.4.0
	 */
	public $taxonomy_category_slug = 'course_category';

	/**
	 * @var string The course tag.
	 * @since 4.4.0
	 */
	public $taxonomy_tag_slug = 'course_tag';

	/**
	 * @var string The course builder cache key.
	 * @since 4.4.0
	 */
	protected $builder_cache_key = 'builder';

	/**
	 * @var array The post type permalinks.
	 * @since 4.4.0
	 */
	protected $permalinks;

	/**
	 * Courses constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Courses();
	}

	/**
	 * Load Courses Controller.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		// Course Permalinks
		add_action( 'wpcw_init', array( $this, 'post_type_permalinks' ) );

		// Course Rewrite Rules.
		add_filter( 'rewrite_rules_array', array( $this, 'fix_post_type_rewrite_rules' ), 10, 1 );

		// Course Post Type.
		add_action( 'init', array( $this, 'post_type' ), 5 );
		add_filter( 'post_updated_messages', array( $this, 'post_type_updated_messages' ) );
		add_filter( 'posts_fields', array( $this, 'post_type_add_course_fields' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'post_type_join_course_table' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 0, 4 );
		add_action( 'pre_get_posts', array( $this, 'post_type_permission_filter' ) );
		add_action( 'current_screen', array( $this, 'post_type_permission_filter_count' ) );

		// Course Taxonomies.
		add_action( 'init', array( $this, 'taxonomy_category' ), 0 );
		add_action( 'init', array( $this, 'taxonomy_tag' ), 0 );
		add_filter( 'admin_head', array( $this, 'taxonomy_menu_fix' ) );
		add_action( 'admin_head-edit-tags.php', array( $this, 'taxonomy_action_buttons' ) );
		add_action( 'admin_head-edit-tags.php', array( $this, 'taxonomy_title_icon' ) );
		add_filter( 'wpcw_admin_page_courses_action_buttons', array( $this, 'add_taxonomy_buttons' ) );
		add_filter( 'wpcw_admin_page_courses_single_action_buttons', array( $this, 'add_taxonomy_buttons' ) );

		// Automatic Enrollment.
		add_filter( 'wpcw_course_automatic_enrollment_disable', array( $this, 'maybe_disable_autommatic_enrollment' ), 10, 2 );

		// Api Endpoints.
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );

		// Modify Modules.
		add_action( 'wpcw_modules_modified', array( $this, 'invalidate_builder_cache' ) );

		// Single Template.
		add_action( 'the_post', array( $this, 'setup_course_data' ), 10 );
		add_filter( 'template_redirect', array( $this, 'course_template_actions' ) );

		// Delete.
		add_action( 'before_delete_post', array( $this, 'delete' ), 10 );

		// Exclude expiration courses.
		add_action( 'pre_get_posts', array( $this, 'wpcw_exclude_expiration_courses' ) );
	}

	/** Settings Methods ---------------------------------------------- */

	/**
	 * Get Course Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of course settings fields.
	 */
	public function get_settings_fields() {
		$general_section_fields    = $this->get_general_section_settings_fields();
		$permalinks_section_fields = $this->get_permalinks_section_settings_fields();
		$taxonomies_section_fields = $this->get_taxonomies_section_settings_fields();

		$settings_fields = array_merge( $general_section_fields, $permalinks_section_fields, $taxonomies_section_fields );

		return apply_filters( 'wpcw_course_settings_fields', $settings_fields );
	}

	public function get_membership_section_settings_fields() {

		return apply_filters( 'wpcw_course_membership_section_settings_fields', array(
			array(
				'type'  => 'restricted',
				'key'   => 'restricted_content',
				'label' => esc_html__( 'Membership Restriction Messages', 'wp-courseware' ),
				'tip'	=> esc_html__( 'These settings determine what to display to website users when access to a page or post is restricted to course members and the user does not have access.', 'wp-courseware' ),
				'fields' => array(
					array(
						'type' => 'accordion_item',
						'title' => esc_html__( 'Logged-In Users', 'wp-courseware' ),
						'key'       => 'wpcw_restricted_content_loggedin',
						'tip' => esc_html__( 'This message will display for users who are logged in, but do not have access to the required course for the requested page or post.', 'wp-courseware' ),
						'default' => esc_html__( 'This content is only available to course members. Enroll into the course to see content.', 'wp-courseware' )
					),
					array(
						'type' => 'accordion_item',
						'title' => esc_html__( 'Everyone Else', 'wp-courseware' ),
						'key'       => 'wpcw_restricted_content_default',
						'tip' => esc_html__( 'This message will display for users who are not logged in when they attempt to access a page or post with restricted content.', 'wp-courseware' ),
						'default' => esc_html__( 'This content is only available to course members. Login to your account or enroll into the course to see content.', 'wp-courseware' )
					)
				)
			)
		) );
	}

	/**
	 * Get Course General Section Settings Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The course general section settings fields.
	 */
	public function get_general_section_settings_fields() {
		return apply_filters( 'wpcw_course_general_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'courses_heading',
				'title' => esc_html__( 'Course Pages', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings that can set the courses index page.', 'wp-courseware' ),
			),
			array(
				'type'    => 'hidden',
				'key'     => 'course_enrollment_method',
				'default' => 'sync',
			),
			array(
				'type'     => 'page',
				'key'      => 'courses_page',
				'title'    => esc_html__( 'Courses Page', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The main course index page.', 'wp-courseware' ),
				'default'  => '',
			),
			array(
				'type'  => 'heading',
				'key'   => 'course_outlines_section_heading',
				'title' => esc_html__( 'Course Outline', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to the display of a course outline.', 'wp-courseware' ),
			),
			array(
				'type'      => 'affiliates',
				'key'       => 'affiliates',
				'component' => true,
				'views'     => array( 'settings/settings-field-affiliates' ),
				'settings'  => array(
					array(
						'key'     => 'show_powered_by',
						'type'    => 'radio',
						'default' => 'yes',
					),
					array(
						'key'     => 'affiliate_id',
						'type'    => 'text',
						'default' => '',
					),
				),
			),
		) );
	}

	/**
	 * Get Course Permalinks Section Settings Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The course general section settings fields.
	 */
	public function get_permalinks_section_settings_fields() {
		return apply_filters( 'wpcw_course_permalinks_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'course_permalinks',
				'title' => esc_html__( 'Course Permalinks', 'wp-courseware' ),
				'desc'  => wp_kses_post( wpautop( sprintf( __( 'Below are settings for course permalinks that allow you to enter custom structures for your course URLs. For example, using <code>courses</code> would make your course links look like <code>%scourses/sample-course/</code>. This setting affects course URLs only, not things such as course categories.', 'wp-courseware' ), esc_url( home_url( '/' ) ) ) ) ),
			),
			array(
				'type'      => 'course_permalinks',
				'key'       => 'course_permalinks',
				'component' => true,
				'views'     => array( 'settings/settings-field-course-permalinks' ),
				'settings'  => array(
					array(
						'key'     => 'course_permalink',
						'type'    => 'radio',
						'default' => '',
					),
					array(
						'key'     => 'course_permalink_structure',
						'type'    => 'text',
						'default' => '',
					),
				),
			),
		) );
	}

	/**
	 * Get Course Taxonomies Section Settings Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The course general section settings fields.
	 */
	public function get_taxonomies_section_settings_fields() {
		return apply_filters( 'wpcw_course_taxonomies_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'course_taxonomies',
				'title' => esc_html__( 'Course Taxonomies', 'wp-courseware' ),
				'desc'  => wp_kses_post( wpautop( sprintf( __( 'Below are settings that allow you to enter custom structures for your course category and course tag URLs. For example, using <code>course-topics</code> as your course category base would make your course category links look like <code>%scourse-topics/uncategorized/</code>. If you leave these blank, defaults will be used.', 'wp-courseware' ), esc_url( home_url( '/' ) ) ) ) ),
			),
			array(
				'type'     => 'text',
				'key'      => 'course_category_base',
				'default'  => 'course-category',
				'title'    => esc_html__( 'Course Category Base', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Course category base for course category permalinks.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'course_tag_base',
				'default'  => 'course-tag',
				'title'    => esc_html__( 'Course Tag Base', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Course tag base for course tag permalinks.', 'wp-courseware' ),
			),
		) );
	}

	/** Post Type Permalinks Methods -------------------------------------- */

	/**
	 * Get Permalinks.
	 *
	 * @since 4.4.0
	 *
	 * @return array The permalinks array.
	 */
	public function get_permalinks() {
		if ( empty( $this->permalinks ) ) {
			$this->permalinks = wpcw_get_permalink_structure();
		}

		return $this->permalinks;
	}

	/**
	 * Save Post Type Permalinks.
	 *
	 * @since 4.4.0
	 */
	public function post_type_permalinks() {
		if ( ! is_admin() ) {
			return;
		}

		$post_data = $_POST;

		if ( ! isset( $post_data['wpcw-form-submit'] ) ) {
			return;
		}

		if ( ! current_user_can( apply_filters( 'wpcw_admin_page_form_process_capability', 'manage_options' ) ) ) {
			return;
		}

		if ( isset( $post_data['action'] ) && $post_data['action'] === 'wpcw-update-courses-permalinks' && wp_verify_nonce( $post_data['nonce'], 'wpcw-courses-permalinks-nonce' ) ) {
			$this->update_post_type_permalinks( $post_data );
		}

		if ( isset( $post_data['action'] ) && $post_data['action'] === 'wpcw-update-courses-taxonomies' && wp_verify_nonce( $post_data['nonce'], 'wpcw-courses-taxonomies-nonce' ) ) {
			$this->update_post_type_taxonomy_permalinks( $post_data );
		}
	}

	/**
	 * Update Post Type Permalinks.
	 *
	 * @since 4.4.0
	 *
	 * @param array $post_data The post data array.
	 */
	public function update_post_type_permalinks( $post_data ) {
		$permalinks = (array) get_option( 'wpcw_permalinks', array() );

		$course_base = isset( $post_data['course_permalink'] ) ? wpcw_clean( wp_unslash( $post_data['course_permalink'] ) ) : ''; // WPCS: input var ok,

		if ( 'custom' === $course_base ) {
			if ( isset( $post_data['course_permalink_structure'] ) ) { // WPCS: input var ok.
				$course_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $post_data['course_permalink_structure'] ) ) ) ); // WPCS: input var ok, sanitization ok.
			} else {
				$course_base = '/';
			}

			// This is an invalid base structure and breaks pages.
			if ( '/%course_category%/' === trailingslashit( $course_base ) ) {
				$course_base = '/' . _x( 'course', 'slug', 'wp-courseware' ) . $course_base;
			}
		} elseif ( empty( $course_base ) ) {
			$course_base = _x( 'course', 'slug', 'wp-courseware' );
		}

		$permalinks['course_base'] = wpcw_sanitize_permalink( $course_base );

		// Courses base may require verbose page rules if nesting pages.
		$courses_page      = wpcw_get_page_id( 'courses' );
		$courses_permalink = ( $courses_page > 0 && get_post( $courses_page ) ) ? get_page_uri( $courses_page ) : _x( 'courses', 'default-slug', 'wp-courseware' );

		if ( $courses_page && stristr( trim( $permalinks['course_base'], '/' ), $courses_permalink ) ) {
			$permalinks['use_verbose_page_rules'] = true;
		}

		// Update Option
		update_option( 'wpcw_permalinks', $permalinks );

		// Flush Rewrite Rules Flag.
		wpcw_enable_flush_rewrite_rules_flag();

		// Flush Rewrite Rules.
		$this->flush_post_type_rewrite_rules();
	}

	/**
	 * Update Taxonomy Permalinks.
	 *
	 * @since 4.4.0
	 *
	 * @param array $settings The settings array.
	 */
	public function update_post_type_taxonomy_permalinks( $post_data ) {
		$permalinks = (array) get_option( 'wpcw_permalinks', array() );

		$category_base = isset( $post_data['course_category_base'] ) ? $post_data['course_category_base'] : _x( 'course-category', 'slug', 'wp-courseware' );
		$tag_base      = isset( $post_data['course_tag_base'] ) ? $post_data['course_tag_base'] : _x( 'course-tag', 'slug', 'wp-courseware' );

		$permalinks['course_category_base'] = wpcw_sanitize_permalink( wp_unslash( $category_base ) );
		$permalinks['course_tag_base']      = wpcw_sanitize_permalink( wp_unslash( $tag_base ) );

		// Update Option
		update_option( 'wpcw_permalinks', $permalinks );

		// Flush Rewrite Rules Flag.
		wpcw_enable_flush_rewrite_rules_flag();

		// Flush Rewrite Rules.
		$this->flush_post_type_rewrite_rules();
	}

	/**
	 * Fix Course Rewrite Rules.
	 *
	 * @since 4.4.0
	 *
	 * @param array $rules The rewrite rules.
	 *
	 * @return array $rules the rewrite rules.
	 */
	public function fix_post_type_rewrite_rules( $rules ) {
		global $wp_rewrite;

		$permalinks = $this->get_permalinks();

		// Fix the rewrite rules when the course permalink have %course_category% flag.
		if ( preg_match( '`/(.+)(/%course_category%)`', $permalinks['course_rewrite_slug'], $matches ) ) {
			foreach ( $rules as $rule => $rewrite ) {
				if ( preg_match( '`^' . preg_quote( $matches[1], '`' ) . '/\(`', $rule ) && preg_match( '/^(index\.php\?course_category)(?!(.*course))/', $rewrite ) ) {
					unset( $rules[ $rule ] );
				}
			}
		}

		// If the courses page is used as the base, we need to handle courses page subpages to avoid 404s.
		if ( ! $permalinks['use_verbose_page_rules'] ) {
			return $rules;
		}

		$courses_page_id = wpcw_get_page_id( 'courses' );
		if ( ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ) {
			$page_rewrite_rules = array();

			// Get Subpages.
			$subpages = wpcw_get_page_children( $courses_page_id );

			// Subpage rules.
			foreach ( $subpages as $subpage ) {
				$uri                                = get_page_uri( $subpage );
				$page_rewrite_rules[ $uri . '/?$' ] = 'index.php?pagename=' . $uri;
				$wp_generated_rewrite_rules         = $wp_rewrite->generate_rewrite_rules( $uri, EP_PAGES, true, true, false, false );
				foreach ( $wp_generated_rewrite_rules as $key => $value ) {
					$wp_generated_rewrite_rules[ $key ] = $value . '&pagename=' . $uri;
				}
				$page_rewrite_rules = array_merge( $page_rewrite_rules, $wp_generated_rewrite_rules );
			}

			// Merge with rules.
			$rules = array_merge( $page_rewrite_rules, $rules );
		}

		return $rules;
	}

	/** Post Type Methods -------------------------------------------- */

	/**
	 * Register Post Type Course.
	 *
	 * @since 4.4.0
	 */
	public function post_type() {
		$permalinks = $this->get_permalinks();

		// Course Archive
		$courses_page   = wpcw_get_page_id( 'courses' );
		$course_archive = $courses_page && get_post( $courses_page )
			? urldecode( get_page_uri( $courses_page ) )
			: apply_filters( 'wpcw_course_default_archive_slug', _x( 'courses', 'slug', 'wp-courseware' ) );

		register_post_type( $this->post_type_slug, apply_filters( 'wpcw_course_post_type_args', array(
			'labels'                => array(
				'name'               => __( 'Courses', 'wp-courseware' ),
				'singular_name'      => __( 'Course', 'wp-courseware' ),
				'all_items'          => __( 'All Courses', 'wp-courseware' ),
				'new_item'           => __( 'New Course', 'wp-courseware' ),
				'add_new'            => __( 'Add New', 'wp-courseware' ),
				'add_new_item'       => __( 'Add New Course', 'wp-courseware' ),
				'edit_item'          => __( 'Edit Course', 'wp-courseware' ),
				'view_item'          => __( 'View Course', 'wp-courseware' ),
				'view_items'         => __( 'View Courses', 'wp-courseware' ),
				'search_items'       => __( 'Search Courses', 'wp-courseware' ),
				'not_found'          => sprintf( __( 'No courses found. <a href="%s">Add a new course</a>.', 'wp-courseware' ), admin_url( 'post-new.php?post_type=wpcw_course' ) ),
				'not_found_in_trash' => __( 'No courses found in trash', 'wp-courseware' ),
				'parent_item_colon'  => __( 'Parent Course:', 'wp-courseware' ),
				'menu_name'          => __( 'Courses', 'wp-courseware' ),
			),
			'public'                => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_nav_menus'     => true,
			'show_in_menu'          => true,
			'show_in_admin_bar'     => true,
			'supports'              => array( 'title', 'thumbnail' ),
			'has_archive'           => $course_archive,
			'rewrite'               => $permalinks['course_rewrite_slug'] ? array( 'slug' => $permalinks['course_rewrite_slug'], 'with_front' => false ) : false,
			'query_var'             => true,
			'map_meta_cap'          => true,
			'can_export'            => true,
			'taxonomies'            => array( $this->taxonomy_category_slug, $this->taxonomy_tag_slug ),
			'show_in_rest'          => true,
			'capability_type'       => 'wpcw_course',
			'rest_base'             => $this->post_type_slug,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		) ) );
	}

	/**
	 * Register Post Type Course Updated Messages.
	 *
	 * @since 4.4.0
	 *
	 * @param array $messages The updated messages.
	 *
	 * @return array $messages The updated messages.
	 */
	public function post_type_updated_messages( $messages ) {
		global $post;

		$permalink = get_permalink( $post );

		$messages[ $this->post_type_slug ] = apply_filters( 'wpcw_course_post_type_updated_messages', array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Course updated. <a target="_blank" href="%s">View Course</a>', 'wp-courseware' ), esc_url( $permalink ) ),
			2  => __( 'Custom field updated.', 'wp-courseware' ),
			3  => __( 'Custom field deleted.', 'wp-courseware' ),
			4  => __( 'Course updated.', 'wp-courseware' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Course restored to revision from %s', 'wp-courseware' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Course published. <a href="%s">View Course</a>', 'wp-courseware' ), esc_url( $permalink ) ),
			7  => __( 'Course saved.', 'wp-courseware' ),
			8  => sprintf( __( 'Course submitted. <a target="_blank" href="%s">Preview Course</a>', 'wp-courseware' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
			9  => sprintf( __( 'Course scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Course</a>', 'wp-courseware' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
			10 => sprintf( __( 'Course draft updated. <a target="_blank" href="%s">Preview Course</a>', 'wp-courseware' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		) );

		return $messages;
	}

	/**
	 * Post Type Add Course Fields
	 *
	 * @since 4.4.0
	 *
	 * @param array    $fields The query fields.
	 * @param WP_Query $wp_query The wp query being run.
	 *
	 * @return array $fields The query fields.
	 */
	public function post_type_add_course_fields( $fields, $wp_query ) {
		global $wpdb, $wpcwdb;

		$allow_fields = false;

		if ( wpcw_is_post_type_query( $wp_query, $this->post_type_slug ) ) {
			$allow_fields = true;
		}

		if ( wpcw_is_taxonomy_query( $wp_query, $this->taxonomy_category_slug ) ) {
			$allow_fields = true;
		}

		if ( wpcw_is_taxonomy_query( $wp_query, $this->taxonomy_tag_slug ) ) {
			$allow_fields = true;
		}

		if ( $allow_fields ) {
			$course_fields = '';
			$table_name    = $this->db->get_table_name();
			$table_columns = array_keys( $this->db->get_columns() );

			$course_fields = array_map( function ( $column ) use ( $table_name ) {
				return "{$table_name}.{$column}";
			}, $table_columns );

			if ( ! empty( $course_fields ) ) {
				$additional_fields = "{$wpdb->posts}.post_author, {$wpdb->posts}.post_status";

				$fields .= ", {$additional_fields}, " . implode( ', ', $course_fields );
			}
		}

		return $fields;
	}

	/**
	 * Join Post Type with Courses Table.
	 *
	 * @since 4.4.0
	 *
	 * @param string    $clause The where clause.
	 * @param \WP_Query $wp_query The wp query being run.
	 */
	public function post_type_join_course_table( $clause, $wp_query ) {
		global $wpdb, $wpcwdb;

		$join_clause = '';

		// Post Type.
		if ( wpcw_is_post_type_query( $wp_query, $this->post_type_slug ) ) {
			$join_clause = " LEFT JOIN {$wpcwdb->courses} ON {$wpdb->posts}.ID = {$wpcwdb->courses}.course_post_id";
		}

		// Taxonomy - Category
		if ( wpcw_is_taxonomy_query( $wp_query, $this->taxonomy_category_slug ) ) {
			$join_clause = " LEFT JOIN {$wpcwdb->courses} ON {$wpdb->posts}.ID = {$wpcwdb->courses}.course_post_id";
		}

		// Taxonomy - Tag
		if ( wpcw_is_taxonomy_query( $wp_query, $this->taxonomy_tag_slug ) ) {
			$join_clause = " LEFT JOIN {$wpcwdb->courses} ON {$wpdb->posts}.ID = {$wpcwdb->courses}.course_post_id";
		}

		if ( ! empty( $join_clause ) ) {
			$clause .= $join_clause;
		}

		return $clause;
	}

	/**
	 * Filter to allow %course_category% in the permalinks for courses.
	 *
	 * @since 4.4.0
	 *
	 * @param string  $post_link The post's permalink.
	 * @param WP_Post $post The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 * @param bool    $sample Is it a sample permalink.
	 *
	 * @return string $permalink The newly modified course permalink.
	 */
	public function post_type_link( $post_link, $post, $leavename, $sample ) {
		if ( $this->post_type_slug !== $post->post_type ) {
			return $post_link;
		}

		if ( false === strpos( $post_link, '%' ) ) {
			return $post_link;
		}

		// Get the custom taxonomy terms in use by this post.
		$terms = get_the_terms( $post->ID, $this->taxonomy_category_slug );

		if ( ! empty( $terms ) ) {
			if ( function_exists( 'wp_list_sort' ) ) {
				$terms = wp_list_sort( $terms, 'term_id', 'ASC' );
			} else {
				usort( $terms, '_usort_terms_by_ID' );
			}

			$category_object = apply_filters( 'wpcw_course_post_type_link_course_cat', $terms[0], $terms, $post );
			$category_object = get_term( $category_object, $this->taxonomy_category_slug );
			$course_cat      = $category_object->slug;

			if ( $category_object->parent ) {
				$ancestors = get_ancestors( $category_object->term_id, $this->taxonomy_category_slug );
				foreach ( $ancestors as $ancestor ) {
					$ancestor_object = get_term( $ancestor, $this->taxonomy_category_slug );
					$course_cat      = $ancestor_object->slug . '/' . $course_cat;
				}
			}
		} else {
			// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
			$course_cat = _x( 'uncategorized', 'slug', 'wp-courseware' );
		}

		$find = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%post_id%',
			'%category%',
			'%course_category%',
		);

		$replace = array(
			date_i18n( 'Y', strtotime( $post->post_date ) ),
			date_i18n( 'm', strtotime( $post->post_date ) ),
			date_i18n( 'd', strtotime( $post->post_date ) ),
			date_i18n( 'H', strtotime( $post->post_date ) ),
			date_i18n( 'i', strtotime( $post->post_date ) ),
			date_i18n( 's', strtotime( $post->post_date ) ),
			$post->ID,
			$course_cat,
			$course_cat,
		);

		$post_link = str_replace( $find, $replace, $post_link );

		return $post_link;
	}

	/**
	 * Flush Post Type Rewrite Rules
	 *
	 * @since 4.4.0
	 */
	public function flush_post_type_rewrite_rules() {
		do_action( 'wpcw_flush_rewrite_rules' );
	}

	/**
	 * Post Type Permission Filter.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Query $wp_query The WP_Query object.
	 */
	public function post_type_permission_filter( $wp_query ) {
		global $pagenow, $typenow;

		// Check, if is admin
		if ( ! $wp_query->is_admin ) {
			return;
		}

		// Check
		if ( 'edit.php' !== $pagenow || $this->post_type_slug !== $typenow ) {
			return;
		}

		// Get Current User
		$current_user = wp_get_current_user();

		// Check permissions
		if ( ! user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
			$wp_query->set( 'author', $current_user->ID );
		}
	}

	/**
	 * Post Type Permission Filter Count.
	 *
	 * @since 4.4.0
	 *
	 * @param \WP_Screen $current_screen The current screen
	 */
	public function post_type_permission_filter_count( $current_screen ) {
		// Check
		if ( $current_screen->id != 'edit-' . $this->post_type_slug ) {
			return $current_screen;
		}

		// Get Current user
		$current_user = wp_get_current_user();

		// Check permission
		if ( ! user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
			add_filter( "views_{$current_screen->id}", array( $this, 'post_type_permission_filter_change_count' ) );
		}
	}

	/**
	 * Post Type Permission Filter Change Count.
	 *
	 * @since 4.4.0
	 *
	 * @param array $views The views array.
	 */
	public function post_type_permission_filter_change_count( $views ) {
		// Global
		global $wpdb;

		// Get Current User
		$current_user = wp_get_current_user();

		// Get Post Type
		$post_type = $this->post_type_slug;

		/*
		 * Change the counts
		 *
		 * [all] => <a href="edit.php?post_type=course_unit&#038;all_posts=1" class="current">All <span class="count">(6)</span></a>
		 * [mine] => <a href="edit.php?post_type=course_unit&#038;author=2">Mine <span class="count">(1)</span></a>
		 * [publish] => <a href="edit.php?post_status=publish&#038;post_type=course_unit">Published <span class="count">(5)</span></a>
		 * [draft] => <a href="edit.php?post_status=draft&#038;post_type=course_unit">Draft <span class="count">(1)</span></a>
		 */
		$all = $wpdb->get_var(
			"SELECT COUNT(*)
		     FROM $wpdb->posts
		     WHERE ( post_status = 'publish' OR post_status = 'draft' )
		     AND ( post_author = '$current_user->ID' AND post_type = '$post_type' )"
		);

		if ( isset( $views['mine'] ) ) {
			$mine = $wpdb->get_var(
				"SELECT COUNT(*) FROM $wpdb->posts
                 WHERE post_status = 'publish'
                 AND post_author = '$current_user->ID'
                 AND post_type = '$post_type'"
			);
		}

		$publish = $wpdb->get_var(
			"SELECT COUNT(*)
             FROM $wpdb->posts
             WHERE post_status = 'publish'
             AND post_author = '$current_user->ID'
             AND post_type = '$post_type' "
		);

		if ( isset( $views['draft'] ) ) {
			$draft = $wpdb->get_var(
				"SELECT COUNT(*)
                 FROM $wpdb->posts
                 WHERE post_status = 'draft'
                 AND post_author = '$current_user->ID'
                 AND post_type = '$post_type'"
			);
		}

		// All
		if ( isset( $views['all'] ) ) {
			$views['all'] = preg_replace( '/\(.+\)/U', '(' . $all . ')', $views['all'] );
		}

		// Mine
		if ( isset( $views['mine'] ) ) {
			$views['mine'] = preg_replace( '/\(.+\)/U', '(' . $mine . ')', $views['mine'] );
		}

		// Publish
		if ( isset( $views['publish'] ) ) {
			$views['publish'] = preg_replace( '/\(.+\)/U', '(' . $publish . ')', $views['publish'] );
		}

		// Draft
		if ( isset( $views['draft'] ) ) {
			$views['draft'] = preg_replace( '/\(.+\)/U', '(' . $draft . ')', $views['draft'] );
		}

		return $views;
	}

	/** Taxonomy Methods -------------------------------------------- */

	/**
	 * Register Course Categories.
	 *
	 * @since 4.4.0
	 */
	public function taxonomy_category() {
		$permalinks = $this->get_permalinks();

		register_taxonomy( $this->taxonomy_category_slug, array( $this->post_type_slug ), apply_filters( 'wpcw_course_category_args', array(
			'hierarchical'          => true,
			'public'                => true,
			'show_in_nav_menus'     => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'query_var'             => true,
			'rewrite'               => array( 'slug' => $permalinks['course_category_rewrite_slug'], 'with_front' => false, 'hierarchical' => true ),
			'capabilities'          => array(
				'manage_terms' => 'manage_wpcw_course_terms',
				'edit_terms'   => 'edit_wpcw_course_terms',
				'delete_terms' => 'delete_wpcw_course_terms',
				'assign_terms' => 'assign_wpcw_course_terms',
			),
			'labels'                => array(
				'name'                       => __( 'Categories', 'wp-courseware' ),
				'singular_name'              => _x( 'Category', 'taxonomy general name', 'wp-courseware' ),
				'search_items'               => __( 'Search categories', 'wp-courseware' ),
				'popular_items'              => __( 'Popular categories', 'wp-courseware' ),
				'all_items'                  => __( 'All categories', 'wp-courseware' ),
				'parent_item'                => __( 'Parent category', 'wp-courseware' ),
				'parent_item_colon'          => __( 'Parent category:', 'wp-courseware' ),
				'edit_item'                  => __( 'Edit category', 'wp-courseware' ),
				'update_item'                => __( 'Update category', 'wp-courseware' ),
				'add_new_item'               => __( 'New category', 'wp-courseware' ),
				'new_item_name'              => __( 'New category', 'wp-courseware' ),
				'separate_items_with_commas' => __( 'Separate categories with commas', 'wp-courseware' ),
				'add_or_remove_items'        => __( 'Add or remove categories', 'wp-courseware' ),
				'choose_from_most_used'      => __( 'Choose from the most used categories', 'wp-courseware' ),
				'not_found'                  => __( 'No categories found.', 'wp-courseware' ),
				'menu_name'                  => __( 'Categories', 'wp-courseware' ),
			),
			'show_in_rest'          => true,
			'rest_base'             => $this->taxonomy_category_slug,
			'rest_controller_class' => 'WP_REST_Terms_Controller',
		) ) );
	}

	/**
	 * Register Course Tags.
	 *
	 * @since 4.4.0
	 */
	public function taxonomy_tag() {
		$permalinks = $this->get_permalinks();

		register_taxonomy( $this->taxonomy_tag_slug, array( $this->post_type_slug ), apply_filters( 'wpcw_course_tag_args', array(
			'hierarchical'          => false,
			'public'                => true,
			'show_in_nav_menus'     => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'query_var'             => true,
			'rewrite'               => array( 'slug' => $permalinks['course_tag_rewrite_slug'], 'with_front' => false ),
			'capabilities'          => array(
				'manage_terms' => 'manage_wpcw_course_terms',
				'edit_terms'   => 'edit_wpcw_course_terms',
				'delete_terms' => 'delete_wpcw_course_terms',
				'assign_terms' => 'assign_wpcw_course_terms',
			),
			'labels'                => array(
				'name'                       => __( 'Tags', 'wp-courseware' ),
				'singular_name'              => _x( 'Tag', 'taxonomy general name', 'wp-courseware' ),
				'search_items'               => __( 'Search tags', 'wp-courseware' ),
				'popular_items'              => __( 'Popular tags', 'wp-courseware' ),
				'all_items'                  => __( 'All tags', 'wp-courseware' ),
				'parent_item'                => __( 'Parent tag', 'wp-courseware' ),
				'parent_item_colon'          => __( 'Parent tag:', 'wp-courseware' ),
				'edit_item'                  => __( 'Edit tag', 'wp-courseware' ),
				'update_item'                => __( 'Update tag', 'wp-courseware' ),
				'add_new_item'               => __( 'New tag', 'wp-courseware' ),
				'new_item_name'              => __( 'New tag', 'wp-courseware' ),
				'separate_items_with_commas' => __( 'Separate tags with commas', 'wp-courseware' ),
				'add_or_remove_items'        => __( 'Add or remove tags', 'wp-courseware' ),
				'choose_from_most_used'      => __( 'Choose from the most used tags', 'wp-courseware' ),
				'not_found'                  => __( 'No tags found.', 'wp-courseware' ),
				'menu_name'                  => __( 'Tags', 'wp-courseware' ),
			),
			'show_in_rest'          => true,
			'rest_base'             => $this->taxonomy_tag_slug,
			'rest_controller_class' => 'WP_REST_Terms_Controller',
		) ) );
	}

	/**
	 * Taxonomy Menu Fix.
	 *
	 * @since 4.3.0
	 */
	public function taxonomy_menu_fix() {
		global $parent_file, $submenu_file;

		$current_screen = get_current_screen();

		if ( empty( $current_screen->taxonomy ) || ! in_array( $current_screen->taxonomy, array( $this->taxonomy_tag_slug, $this->taxonomy_category_slug ) ) ) {
			return;
		}

		$parent_file  = 'wpcw';
		$submenu_file = esc_url( add_query_arg( array( 'post_type' => $this->post_type_slug ), 'edit.php' ) );
	}

	/**
	 * Taxonomy Action Buttons.
	 *
	 * @since 4.3.0
	 */
	public function taxonomy_action_buttons() {
		global $current_screen;

		if ( empty( $current_screen->taxonomy ) || ! in_array( $current_screen->taxonomy, array( $this->taxonomy_tag_slug, $this->taxonomy_category_slug ) ) ) {
			return;
		}

		if ( $this->taxonomy_tag_slug === $current_screen->taxonomy ) {
			$action_buttons = $this->get_taxonomy_tag_action_buttons();
		} else {
			$action_buttons = $this->get_taxonomy_category_action_buttons();
		}

		if ( empty( $action_buttons ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			( function ( $ ) {
				$( document ).ready( function () {
					$( '<?php echo $action_buttons; ?>' ).insertAfter( '.wrap h1.wp-heading-inline' );
				} );
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Add Icon to Taxonomy Title.
	 *
	 * @since 4.4.0
	 */
	public function taxonomy_title_icon() {
		global $current_screen;

		if ( empty( $current_screen->taxonomy ) || ! in_array( $current_screen->taxonomy, array( $this->taxonomy_tag_slug, $this->taxonomy_category_slug ) ) ) {
			return;
		}

		echo
			'<style type="text/css">
                .wrap h1.wp-heading-inline {
                    position: relative;
                    padding-top: 4px;
                    padding-left: 50px;
                    margin-right: 10px;
                }
                .wrap h1.wp-heading-inline:before {
                    background-image: url("' . wpcw_image_file( 'wp-courseware-icon.svg' ) . '");
                    background-size: 40px 40px;
                    content: "";
                    display: inline-block;
                    position: absolute;
                    top: -2px;
                    left: 0;
                    width: 40px;
                    height: 40px;
                }
            </style>';
	}

	/**
	 * Add Taxonomy Buttons.
	 *
	 * @since 4.4.0
	 *
	 * @param string $action_buttons The action buttons html.
	 *
	 * @return string $action_buttons The actions buttons html.
	 */
	public function add_taxonomy_buttons( $action_buttons ) {
		$taxonomy_buttons = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'taxonomy' => $this->taxonomy_category_slug ), admin_url( 'edit-tags.php' ) ),
			esc_html__( 'View Categories', 'wp-courseware' )
		);

		$taxonomy_buttons .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'taxonomy' => $this->taxonomy_tag_slug ), admin_url( 'edit-tags.php' ) ),
			esc_html__( 'View Tags', 'wp-courseware' )
		);

		return $action_buttons . $taxonomy_buttons;
	}

	/**
	 * Get Taxonomy Tag Action Buttons.
	 *
	 * @since 4.4.0
	 *
	 * @return array $action_buttons The action buttons that will go on the taxonomy pages.
	 */
	public function get_taxonomy_category_action_buttons() {
		$action_buttons = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => $this->post_type_slug ), admin_url( 'edit.php' ) ),
			esc_html__( 'Back to Courses', 'wp-courseware' )
		);

		$action_buttons .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'taxonomy' => $this->taxonomy_tag_slug ), admin_url( 'edit-tags.php' ) ),
			esc_html__( 'View Tags', 'wp-courseware' )
		);

		return $action_buttons;
	}

	/**
	 * Get Taxonomy Tag Action Buttons.
	 *
	 * @since 4.4.0
	 *
	 * @return string $action_buttons The action buttons that will go on the taxonomy tag page.
	 */
	public function get_taxonomy_tag_action_buttons() {
		$action_buttons = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => $this->post_type_slug ), admin_url( 'edit.php' ) ),
			esc_html__( 'Back to Courses', 'wp-courseware' )
		);

		$action_buttons .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'taxonomy' => $this->taxonomy_category_slug ), admin_url( 'edit-tags.php' ) ),
			esc_html__( 'View Categories', 'wp-courseware' )
		);

		return $action_buttons;
	}

	/** Getter Methods ----------------------------------------------------- */

	/**
	 * Get Courses.
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 *
	 * @return array Array of course objects.
	 */
	public function get_courses( $args = array(), $raw = false ) {
		$courses = array();
		$results = $this->db->get_courses( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$courses[] = new Course( $result );
		}

		return $courses;
	}

	/**
	 * Get Number of Courses.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of courses.
	 */
	public function get_courses_count( $args = array() ) {
		return $this->db->get_courses( $args, true );
	}

	/**
	 * Get Course Post Id.
	 *
	 * @since 4.4.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return int $course_post_id The course post id.
	 */
	public function get_course_post_id( $course_id ) {
		return $this->db->get_column( 'course_post_id', $course_id );
	}

	/**
	 * Get Courses by Student.
	 *
	 * @since 4.3.0
	 *
	 * @param int $student_id The student id.
	 *
	 * @return array An array of student courses.
	 */
	public function get_courses_by_student( $student_id ) {
		global $wpdb, $wpcwdb;
		$courses = array();

		if ( ! $student_id ) {
			return $courses;
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $wpcwdb->user_courses uc
			 LEFT JOIN  $wpcwdb->courses c ON c.course_id = uc.course_id
			 WHERE user_id = %d
			 ORDER BY course_title ASC",
			$student_id
		) );

		foreach ( $results as $result ) {
			$courses[] = new Course( $result );
		}

		return $courses;
	}

	/**
	 * Get Payment Types.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of payment types.
	 */
	public function get_payment_types() {
		return apply_filters( 'wpcw_payment_types', array(
			'free'         => __( '<strong>Free</strong> - No payment to enroll in course.', 'wp-courseware' ),
			'one-time'     => __( '<strong>One-Time Purchase</strong> - A single payment to enroll in course.', 'wp-courseware' ),
			'subscription' => __( '<strong>Subscription</strong> - Monthly or Annual billing interval for continued enrollment in course.', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Payment Intervals.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of payment intervals.
	 */
	public function get_payment_intervals() {
		$intervals = array();
		$periods   = wpcw()->subscriptions->get_periods();

		foreach ( $periods as $period => $period_label ) {
			/* translators: %1$s - The subscription period capitalized. %2$s - The subscription period lowercase. */
			$intervals[ $period ] = sprintf( __( '<strong>%1$s</strong> - Subscription is billed %2$s until cancelled.', 'wp-courseware' ), $period_label, strtolower( $period_label ) );
		}

		return apply_filters( 'wpcw_payment_intervals', $intervals );
	}

	/**
	 * Get Course Dropdown Html.
	 *
	 * @since 4.1.0
	 *
	 * @param array  $courses The courses in which will populate the dropdown.
	 * @param string $bulk For the bulk dropdown.
	 *
	 * @return string
	 */
	public function get_courses_reset_dropdown( array $courses, $bulk = false, $query = true ) {
		if ( $query ) {
			$results = $this->get_courses( array(
				'course_id' => $courses,
				'fields'    => array( 'course_id', 'course_title' ),
				'orderby'   => 'course_title',
			) );
		} else {
			$results = $courses;
		}

		$count         = 1;
		$blank_message = esc_html__( 'No courses available.', 'wp-courseware' );

		if ( $bulk ) {
			$course_dropdown = array( '' => esc_html__( 'Reset student(s) to beginning of...', 'wp-courseware' ) );
		} else {
			$course_dropdown = array( '' => esc_html__( 'Reset student to beginning of...', 'wp-courseware' ) );
		}

		$current_user = wp_get_current_user();

		if ( ! empty( $results ) ) {
			foreach ( $results as $course ) {
				if ( $query ) {
					if ( ! $course instanceof Course ) {
						continue;
					}

					$course_id     = $course->get_course_id();
					$course_title  = $course->get_course_title();
					$course_author = $course->get_course_author();
				} else {
					$course_id     = $course['id'];
					$course_title  = $course['title'];
					$course_author = $course['author'];
				}

				if ( empty( $course_id ) || empty( $course_title ) ) {
					continue;
				}

				if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
					if ( $current_user->ID !== absint( $course_author ) ) {
						continue;
					}
				}

				$course_dropdown[ 'course_' . $course_id ] = $course_title;

				$course_modules = WPCW_courses_getModuleDetailsList( $course_id );

				if ( empty( $course_modules ) ) {
					$count ++;
					continue;
				}

				foreach ( $course_modules as $module_id => $module ) {
					$units = WPCW_units_getListOfUnits( $module_id );

					if ( ! empty( $units ) ) {
						$course_dropdown[ 'module_' . $module_id ] = sprintf( '&nbsp;&nbsp;- %s %d: %s',
							__( 'Module', 'wp-courseware' ),
							$module->module_number,
							$module->module_title
						);

						foreach ( $units as $unit_id => $unit ) {
							$course_dropdown[ 'unit_' . $unit_id ] = sprintf( '&nbsp;&nbsp;-- %s %d: %s',
								__( 'Unit', 'wp-courseware' ),
								$unit->unit_meta->unit_number,
								$unit->post_title
							);
						}
					}
				}

				if ( $count !== count( $courses ) ) {
					$padding = str_pad( false, $count ++, ' ' );

					$course_dropdown[ $padding ] = '&nbsp;';
				}
			}
		}

		// No Courses.
		if ( count( $course_dropdown ) === 0 ) {
			$course_dropdown[' '] = $blank_message;
		}

		if ( $bulk ) {
			$field_name    = 'wpcw_user_progress_reset_point_bulk';
			$field_classes = 'wpcw_user_progress_reset_select';
		} else {
			$field_name    = 'wpcw_user_progress_reset_point_single';
			$field_classes = 'wpcw_user_progress_reset_select wpcw_user_progress_reset_point_single';
		}

		return WPCW_forms_createDropdown( $field_name, $course_dropdown, false, false, $field_classes );
	}

	/**
	 * Get Courses Dropdown.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args The dropdown args. Can be used to customize the dropdown.
	 */
	public function get_courses_switch_dropdown( array $args = array() ) {
		$defaults = array(
			'label'       => esc_html__( 'Switch Courses', 'wp-courseware' ),
			'placeholder' => esc_html__( 'Switch Courses', 'wp-courseware' ),
			'name'        => 'course_id',
			'classes'     => array(),
			'selected'    => 0,
			'action'      => '',
			'page'        => '',
			'fields'      => array( 'course_id', 'course_title' ),
			'orderby'     => 'course_title',
		);

		$args = wp_parse_args( $args, $defaults );

		$form = '';

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$args['course_author'] = get_current_user_id();
		}

		$results = $this->get_courses( $args );

		if ( ! empty( $results ) && count( $results ) > 1 ) {
			$classes = ! empty( $args['classes'] ) ? sprintf( ' class="%s"', implode( ' ', $args['classes'] ) ) : '';

			$form = sprintf( '<form id="wpcw-courses-switch-dropdown" class="wpcw-courses-switch-dropdown" method="get" action="%s">', $args['action'] );
			if ( $args['label'] ) {
				$form .= sprintf( '<label for="%s">%s:</label> ', $args['name'], $args['label'] );
			}

			if ( $args['page'] ) {
				$form .= sprintf( '<input name="page" type="hidden" value="%s" />', $args['page'] );
			}

			$form .= sprintf(
				'<select name="%s" placeholder="%s" %s>',
				$args['name'],
				$args['placeholder'],
				$classes
			);

			$form .= sprintf( '<option value="">%s</option>', $args['placeholder'] );

			foreach ( $results as $course ) {
				if ( ! $course instanceof Course ) {
					continue;
				}

				$selected = ( $course->get_course_id() === absint( $args['selected'] ) ) ? ' selected="selected"' : '';

				$form .= sprintf( '<option value="%s" %s>%s</option>', $course->get_course_id(), $selected, $course->get_course_title() );
			}

			$form .= '</select>';
			$form .= '</form>';
		}

		return $form;
	}

	/**
	 * Get Courses Filter By Dropdown.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args The dropdown args. Can be used to customize the dropdown.
	 *
	 * @return stirng $form The filter by courses dropdown form.
	 */
	public function get_courses_filter_by_dropdown( array $args = array() ) {
		$defaults = array(
			'label'       => false,
			'placeholder' => esc_html__( 'All Courses', 'wp-courseware' ),
			'name'        => 'course_id',
			'classes'     => array(),
			'selected'    => isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0,
			'fields'      => array( 'course_id', 'course_title' ),
			'orderby'     => 'course_title',
			'order'       => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$form = '';

		// Check if admin
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$args['course_author'] = get_current_user_id();
		}

		$results = $this->get_courses( $args );

		if ( ! empty( $results ) && count( $results ) > 0 ) {
			$classes = ! empty( $args['classes'] ) ? sprintf( ' class="%s"', implode( ' ', $args['classes'] ) ) : '';

			if ( $args['label'] ) {
				$form .= sprintf( '<label for="%s">%s:</label> ', $args['name'], $args['label'] );
			}

			$form .= sprintf(
				'<select name="%s" placeholder="%s" %s>',
				$args['name'],
				$args['placeholder'],
				$classes
			);

			$form .= sprintf( '<option value="">%s</option>', $args['placeholder'] );

			foreach ( $results as $course ) {
				if ( ! $course instanceof Course ) {
					continue;
				}

				$selected = ( $course->get_course_id() === absint( $args['selected'] ) ) ? ' selected="selected"' : '';

				$form .= sprintf( '<option value="%s" %s>%s</option>', $course->get_course_id(), $selected, $course->get_course_title() );
			}

			$form .= '</select>';
		}

		return $form;
	}

	/**
	 * Get Courses Filter Dropdown.
	 *
	 * @since 4.3.0
	 *
	 * @return string The html for the courses filter dropdown.
	 */
	public function get_courses_filter_dropdown() {
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;

		ob_start();

		printf( '<span class="wpcw-filter-wrapper">' );
		printf( '<select id="wpcw-courses-filter" class="select-field-wpcwselect2-filter" name="course_id" data-placeholder="%s">', esc_html__( 'All Courses', 'wp-courseware' ) );

		if ( $course_id && ( $course = new Course( $course_id ) ) ) {
			printf( '<option value="%s">%s</option>', $course->get_id(), $course->get_course_title() );
		}

		printf( '</select>' );
		printf( '</span>' );

		return ob_get_clean();
	}

	/**
	 * Get Course Quizzes Count that need grading.
	 *
	 * @since 4.4.0
	 *
	 * @return int $id The grading course id.
	 */
	public function get_course_quizzes_that_need_grading_count( $id = 0 ) {
		$grading_show = get_user_meta( get_current_user_id(), 'wpcw_course_dashboard_quiz_notification_hide', true );

		// Grading.
		if ( ! $grading_show || 'hide' === $grading_show ) {
			return false;
		}

		// Single.
		if ( $id ) {
			$course_quizzes_that_needs_grading = wpcw_get_course_meta( $id, 'quizzes_need_grading', true );

			if ( ! $course_quizzes_that_needs_grading ) {
				global $wpdb, $wpcwdb;

				$course_quizzes = $wpdb->get_col( $wpdb->prepare(
					"SELECT quiz_id
					 FROM $wpcwdb->quiz
					 WHERE parent_course_id = %d", $id
				) );

				$need_grading     = 0;
				$need_manual_help = 0;

				if ( ! empty( $course_quizzes ) ) {
					$ids = '(' . implode( ',', $course_quizzes ) . ')';

					$need_grading = $wpdb->get_var(
						"SELECT COUNT(*)
						 FROM $wpcwdb->user_progress_quiz
						 WHERE quiz_id IN $ids
				    	 AND quiz_needs_marking > 0
				    	 AND quiz_is_latest = 'latest'"
					);

					$need_manual_help = $wpdb->get_var(
						"SELECT COUNT(*)
					 	 FROM $wpcwdb->user_progress_quiz
					 	 WHERE quiz_id IN $ids
					 	 AND quiz_next_step_type = 'quiz_fail_no_retakes'
					 	 AND quiz_is_latest = 'latest'"
					);
				}

				$course_quizzes_that_needs_grading = absint( $need_grading ) + absint( $need_manual_help );

				if ( 0 === $course_quizzes_that_needs_grading ) {
					$course_quizzes_that_needs_grading = 'none';
				}

				wpcw_update_course_meta( $course_quizzes_that_needs_grading, $id, 'quizzes_need_grading' );
			}

			return $course_quizzes_that_needs_grading;
		}

		// All.
		$quizzes_that_need_grading = get_transient( 'wpcw_quizzes_need_grading' );
		if ( false === $quizzes_that_need_grading ) {
			$quizzes_that_need_grading = wpcw()->quizzes->get_quiz_needs_manual_grading_count();
			set_transient( 'wpcw_quizzes_need_grading', $quizzes_that_need_grading, 12 * HOUR_IN_SECONDS );
		}

		return $quizzes_that_need_grading;
	}

	/**
	 * Get Payment Feature Label.
	 *
	 * @since 4.3.0
	 *
	 * @param Course $course The course object.
	 */
	public function get_payments_feature_label( Course $course ) {
		// Get Types & Intervals.
		$payment_types     = $this->get_payment_types();
		$payment_intervals = $this->get_payment_intervals();

		// Get Course Payments Details.
		$payments_type     = $course->get_payments_type();
		$payments_price    = $course->get_payments_price();
		$payments_interval = $course->get_subscription_interval();

		// Default.
		$payments_feature_label = '';

		// Define the labels internally.
		switch ( $payments_type ) {
			case 'one-time' :
				$payments_feature_label = sprintf( __( 'One-Time Payment - %s', 'wp-courseware' ), wpcw_price( $payments_price ) );
				break;
			case 'subscription' :
				/* translators: %1$s - Payments Price, %2$s - Payments Interval. */
				$payments_feature_label = sprintf( __( 'Subscription - %1$s / %2$s', 'wp-courseware' ), wpcw_price( $payments_price ), $payments_interval ? $payments_interval : esc_html__( 'Monthly', 'wp-courseware' ) );
				break;
			case 'free' :
				$payments_feature_label = __( 'Free', 'wp-courseware' );
				break;
			default:
				break;
		}

		/**
		 * Filter: Course Payments Feature Label.
		 *
		 * @since 4.3.0
		 *
		 * @param Course The Course model object.
		 * @param array The valid payment types array.
		 * @param array The valid payment intervals array.
		 *
		 * @return string The Course Payments Feature Label.
		 */
		return apply_filters( "wpcw_course_payments_feature_label", $payments_feature_label, $course, $payment_types, $payment_intervals );
	}

	/**
	 * Get Payment Installments Feature Label.
	 *
	 * @since 4.6.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return string|bool The installments feature label. Blank if installments are disabled.
	 */
	public function get_installments_feature_label( Course $course ) {
		return $course->are_installments_enabled()
			? $course->get_installments_label()
			: '';
	}

	/**
	 * Get Course Payments Type.
	 *
	 * @since 4.3.0
	 *
	 * @param int $course_id The course id.
	 */
	public function get_course_payments_type( $course_id = 0 ) {
		if ( ! $course_id ) {
			return '';
		}

		return $this->db->get_column( 'payments_type', $course_id );
	}

	/**
	 * Get Email Merge Tags.
	 *
	 * @since 4.4.0
	 *
	 * @return array $merge_tags The email merge tags.
	 */
	public function get_email_merge_tags() {
		return apply_filters( 'wpcw_course_all_email_merge_tags', array(
			'USER_NAME'     => esc_html__( 'The display name of the user.', 'wp-courseware' ),
			'FIRST_NAME'    => esc_html__( 'The first name of the user.', 'wp-courseware' ),
			'LAST_NAME'     => esc_html__( 'The last name of the user.', 'wp-courseware' ),
			'SITE_NAME'     => esc_html__( 'The name of the website.', 'wp-courseware' ),
			'SITE_URL'      => esc_html__( 'The URL of the website.', 'wp-courseware' ),
			'COURSE_TITLE'  => esc_html__( 'The title of the course for the unit that\'s just been completed.', 'wp-courseware' ),
			'MODULE_TITLE'  => esc_html__( 'The title of the module for the unit that\'s just been completed.', 'wp-courseware' ),
			'MODULE_NUMBER' => esc_html__( 'The number of the module for the unit that\'s just been completed.', 'wp-courseware' ),
			'UNIT_TITLE'    => esc_html__( 'The title of the unit that is associated with the quiz.', 'wp-courseware' ),
			'UNIT_URL'      => esc_html__( 'The URL of the unit that is associated with the quiz.', 'wp-courseware' ),
		) );
	}

	/**
	 * Get Course Complete Email Merge Tags.
	 *
	 * @since 4.4.0
	 *
	 * @return array $merge_tags The email merge tags.
	 */
	public function get_course_complete_email_merge_tags() {
		$general_merge_tags         = $this->get_email_merge_tags();
		$course_complete_merge_tags = array(
			'CERTIFICATE_LINK' => esc_html__( 'If the course has PDF certificates enabled, this is the link of the PDF certficate. (If there is no certificate or certificates are not enabled, this is simply blank)', 'wp-courseware' ),
		);

		return apply_filters( 'wpcw_course_complete_email_merge_tags', array_merge( $general_merge_tags, $course_complete_merge_tags ) );
	}

	/**
	 * Get Quiz Email Merge Tags.
	 *
	 * @since 4.4.0
	 *
	 * @return array $merge_tags The email merge tags.
	 */
	public function get_quiz_email_merge_tags() {
		$general_merge_tags = $this->get_email_merge_tags();
		$quiz_merge_tags    = array(
			'QUIZ_TITLE'         => esc_html__( 'The title of the quiz that has been graded.', 'wp-courseware' ),
			'QUIZ_GRADE'         => esc_html__( 'The overall percentage grade for a quiz.', 'wp-courseware' ),
			'QUIZ_GRADES_BY_TAG' => esc_html__( 'Includes a breakdown of scores by tag if available.', 'wp-courseware' ),
			'QUIZ_TIME'          => esc_html__( 'If the quiz was timed, displays the time used to complete the quiz.', 'wp-courseware' ),
			'QUIZ_ATTEMPTS'      => esc_html__( 'Indicates the number of attempts for the quiz.', 'wp-courseware' ),
			'CUSTOM_FEEDBACK'    => esc_html__( 'Includes any custom feedback messages that have been triggered based on the user\'s specific results in the quiz.', 'wp-courseware' ),
			'QUIZ_RESULT_DETAIL' => esc_html__( 'Any optional information relating to the result of the quiz, e.g. information about retaking the quiz.', 'wp-courseware' ),
		);

		return apply_filters( 'wpcw_course_quiz_email_merge_tags', array_merge( $general_merge_tags, $quiz_merge_tags ) );
	}

	/**
	 * Get Final Summary Email Merge Tags.
	 *
	 * @since 4.4.0
	 *
	 * @return array $merge_tags The email merge tags.
	 */
	public function get_final_summary_email_merge_tags() {
		$general_merge_tags       = $this->get_course_complete_email_merge_tags();
		$final_summary_merge_tags = array(
			'CUMULATIVE_GRADE' => esc_html__( 'The overall cumulative grade that the user has scored from completing all quizzes on the course.', 'wp-courseware' ),
			'QUIZ_SUMMARY'     => esc_html__( 'The summary of each quiz, and what the user scored on each.', 'wp-courseware' ),
		);

		unset( $general_merge_tags['MODULE_TITLE'] );
		unset( $general_merge_tags['MODULE_NUMBER'] );
		unset( $general_merge_tags['UNIT_TITLE'] );
		unset( $general_merge_tags['UNIT_URL'] );

		return apply_filters( 'wpcw_course_final_summary_email_merge_tags', array_merge( $general_merge_tags, $final_summary_merge_tags ) );
	}

	/** API Endpoint Methods -------------------------------------------------- */

	/**
	 * Register Course Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'courses', 'method' => 'GET', 'callback' => array( $this, 'api_get_courses' ) );
		$endpoints[] = array( 'endpoint' => 'courses-filtered', 'method' => 'GET', 'callback' => array( $this, 'api_get_courses_filtered' ) );
		$endpoints[] = array( 'endpoint' => 'courses-access', 'method' => 'POST', 'callback' => array( $this, 'api_courses_access' ) );
		$endpoints[] = array( 'endpoint' => 'courses-reset-progress', 'method' => 'POST', 'callback' => array( $this, 'api_courses_reset_progress' ) );
		$endpoints[] = array( 'endpoint' => 'courses-reset-progress-for-user', 'method' => 'POST', 'callback' => array( $this, 'api_courses_reset_progress_for_user' ) );
		$endpoints[] = array( 'endpoint' => 'course-instructors', 'method' => 'GET', 'callback' => array( $this, 'api_get_course_instructors' ) );
		$endpoints[] = array( 'endpoint' => 'course-instructor', 'method' => 'POST', 'callback' => array( $this, 'api_course_instructor' ) );

		// Builder
		$endpoints[] = array( 'endpoint' => 'course-builder', 'method' => 'GET', 'callback' => array( $this, 'api_get_course_builder' ) );
		$endpoints[] = array( 'endpoint' => 'course-refresh-builder', 'method' => 'GET', 'callback' => array( $this, 'api_refresh_course_builder' ) );

		// Modules
		$endpoints[] = array( 'endpoint' => 'course-builder-modules', 'method' => 'GET', 'callback' => array( $this, 'api_get_course_builder_modules' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-add-module', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_add_module' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-update-module', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_update_module' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-update-module-order', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_update_module_order' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-delete-module', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_delete_module' ) );

		// Units
		$endpoints[] = array( 'endpoint' => 'course-builder-units', 'method' => 'GET', 'callback' => array( $this, 'api_get_course_builder_units' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-add-unit', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_add_unit' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-add-units', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_add_units' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-update-unit', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_update_unit' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-update-unit-order', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_update_unit_order' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-delete-unit', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_delete_unit' ) );

		// Quizzes
		$endpoints[] = array( 'endpoint' => 'course-builder-quizzes', 'method' => 'GET', 'callback' => array( $this, 'api_get_course_builder_quizzes' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-add-quiz', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_add_quiz' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-add-quizzes', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_add_quizzes' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-update-quiz', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_update_quiz' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-update-quiz-order', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_update_quiz_order' ) );
		$endpoints[] = array( 'endpoint' => 'course-builder-delete-quiz', 'method' => 'POST', 'callback' => array( $this, 'api_course_builder_delete_quiz' ) );

		return $endpoints;
	}

	/**
	 * Api: Get Courses.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_courses( WP_REST_Request $request ) {
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
		$courses = $this->get_courses( $query_args );
		$count   = $this->get_courses_count( $query_args );

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
				'button'       => $course->get_enrollment_button(),
				'recurring'    => ( 'subscription' === $course->get_payments_type() ) ? true : false,
				'price'        => $course->get_payments_price(),
			);
		}

		return rest_ensure_response( array( 'courses' => $results ) );
	}

	/**
	 * Api: Get Courses Filtered.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_courses_filtered( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$order  = $request->get_param( 'order' );
		$author = $request->get_param( 'author' );
		$status = $request->get_param( 'status' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = 100;
		}

		if ( ! $order ) {
			$order = 'DESC';
		}

		if ( ! $author ) {
			$author = '';
		}

		if ( ! $status ) {
			$status = 'all';
		}

		$results    = array();
		$query_args = array(
			'search' => $search,
			'number' => $number,
			'order'  => $order,
			'status' => $status,
		);

		// Check if admin
		if ( is_user_logged_in() && ! $author && ! current_user_can( 'manage_wpcw_settings' ) ) {
			$query_args['course_author'] = get_current_user_id();
		}

		$courses = $this->get_courses( $query_args );
		$count   = $this->get_courses_count( $query_args );

		foreach ( $courses as $course ) {
			if ( ! $course instanceof Course ) {
				continue;
			}

			$results[] = array(
				'id'        => $course->get_course_id(),
				'title'     => $course->get_course_title(),
				'price'     => $course->get_payments_price(),
				'recurring' => ( 'subscription' === $course->get_payments_type() ) ? true : false,
			);
		}

		return rest_ensure_response( array( 'courses' => $results ) );
	}

	/**
	 * Api: Courses Access.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_courses_access( WP_REST_Request $request ) {
		$messages = '';
		$notices  = '';
		$errors   = '';

		$access_type = $request->get_param( 'type' );
		$course_id   = $request->get_param( 'course_id' );

		$allowed_access_types = array( 'all', 'admins', 'subscribers' );

		if ( ! in_array( $access_type, $allowed_access_types ) ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'This access type is not allowed. Please try again', 'wp-courseware' ), false, true );
		}

		if ( ! $course_id ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The course id to allow access is not set.', 'wp-courseware' ), false, true );
		}

		if ( $errors ) {
			return rest_ensure_response( array( 'notices' => $errors, 'messages' => $messages ) );
		}

		$user_query_args = array();

		if ( 'admins' === $access_type ) {
			$user_query_args['role'] = 'administrator';
		}

		if ( 'subscribers' === $access_type ) {
			$user_query_args['role'] = 'subscriber';
		}

		$user_query_args['fields'] = array( 'ID', 'user_login' );

		$users = get_users( $user_query_args );

		if ( ! empty( $users ) ) {
			$added_count = 0;
			$users_count = count( $users );

			/* translators: %d is the users count. */
			$messages .= sprintf( __( 'Found <strong>%d User(s)</strong> to add to this course.', 'wp-courseware' ), $users_count );

			$messages .= '<br />---------------------------------------------------------------';

			foreach ( $users as $user ) {
				$grant_access = $this->grant_course_access( $course_id, $user->ID );

				if ( $grant_access ) {
					$added_count ++;
					/* translators: %1$s: User login. %2$s: User ID */
					$messages .= '<br /> - ' . sprintf( __( 'User: <strong>%1$s (ID: %2$s)</strong> has been granted access to this course.', 'wp-courseware' ), $user->user_login, $user->ID );
				} else {
					/* translators: %1$s: User login. %2$s: User ID */
					$messages .= '<br /> - ' . sprintf( __( 'User: <strong>%1$s (ID: %2$s)</strong> already has access to this course. Skipping...', 'wp-courseware' ), $user->user_login, $user->ID );
				}
			}

			$notices .= wpcw_admin_notice_success( esc_html__( 'Processing Complete!', 'wp-courseware' ), false, true );

			if ( $added_count ) {
				$notices .= wpcw_admin_notice_success( sprintf( __( 'Successfully added %d users to this course.', 'wp-courseware' ), $added_count ), false, true );
			}

			$messages .= '<br />---------------------------------------------------------------<br />';
		}

		return rest_ensure_response( array( 'notices' => $notices, 'messages' => $messages ) );
	}

	/**
	 * Api: Reset Progress.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_courses_reset_progress_for_user( WP_REST_Request $request ) {
		global $wpdb, $wpcwdb;

		$notices = '';
		$errors  = '';

		$course_id = $request->get_param( 'course_id' );
		$user_id   = $request->get_param( 'user_id' );

		if ( ! $course_id ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The course id to allow access is not set.', 'wp-courseware' ), false, true );
		}

		if ( ! $user_id ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The user id to allow access is not set.', 'wp-courseware' ), false, true );
		}

		if ( $errors ) {
			return rest_ensure_response( array( 'notices' => $errors ) );
		}

		// Get Users.
		$users = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM $wpcwdb->user_courses WHERE course_id = %d AND user_id = %d", $course_id, $user_id ) );

		// Get All Units for a course.
		$coursemap = new \WPCW_CourseMap();
		$coursemap->loadDetails_byCourseID( $course_id );
		$units = $coursemap->getUnitIDList_forCourse();

		// Reset Progress.
		wpcw_reset_progress( $coursemap->getCourseDetails(), $users, $units );

		// Notices.
		$notices = wpcw_admin_notice_success( __( 'User progress for this course has been reset.', 'wp-courseware' ), false, true );

		return rest_ensure_response( array( 'notices' => $notices ) );
	}

	/**
	 * Api: Reset Progress for particular user.
	 *
	 * @since 4.4.8
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_courses_reset_progress( WP_REST_Request $request ) {
		global $wpdb, $wpcwdb;

		$notices = '';
		$errors  = '';

		$course_id = $request->get_param( 'course_id' );

		if ( ! $course_id ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The course id to allow access is not set.', 'wp-courseware' ), false, true );
		}

		if ( $errors ) {
			return rest_ensure_response( array( 'notices' => $errors ) );
		}

		// Get Users.
		$users = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM $wpcwdb->user_courses WHERE course_id = %d", $course_id ) );

		// Get All Units for a course.
		$coursemap = new \WPCW_CourseMap();
		$coursemap->loadDetails_byCourseID( $course_id );
		$units = $coursemap->getUnitIDList_forCourse();

		// Reset Progress.
		wpcw_reset_progress( $coursemap->getCourseDetails(), $users, $units );

		// Notices.
		$notices = wpcw_admin_notice_success( __( 'User progress for this course has been reset.', 'wp-courseware' ), false, true );

		return rest_ensure_response( array( 'notices' => $notices ) );
	}

	/**
	 * Api: Get Course Instructors.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_course_instructors( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );

		$query_args = array(
			'number'   => 100,
			'role__in' => apply_filters( 'wpcw_course_instructor_user_roles', array( 'administrator', 'wpcw_instructor' ) ),
			'order'    => 'ASC',
			'orderby'  => 'display_name'
		);

		if ( $search ) {
			$query_args['search']         = '*' . esc_attr( $search ) . '*';
			$query_args['search_columns'] = array( 'display_name', 'user_email' );
		}

		$users = get_users( $query_args );

		$instructors = array();

		if ( $users ) {
			foreach ( $users as $user ) {
				$instructors[] = array( 'id' => $user->ID, 'name' => sprintf( '%s ( %s )', $user->display_name, $user->user_email ) );
			}
		}

		if ( empty( $instructors ) ) {
			$current_user  = wp_get_current_user();
			$instructors[] = array( 'id' => $current_user->ID, 'name' => sprintf( '%s ( %s )', $current_user->display_name, $current_user->user_email ) );
		}

		return rest_ensure_response( array( 'instructors' => $instructors ) );
	}

	/**
	 * Api: Courses Instructor.
	 *
	 * @since 4.5.2
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_instructor( WP_REST_Request $request ) {
		$messages = '';
		$notices  = '';
		$errors   = '';

		$instructor_id    = $request->get_param( 'instructor_id' );
		$course_id        = $request->get_param( 'course_id' );
		$update_questions = $request->get_param( 'update_questions' );

		if ( ! $instructor_id ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The instructor id was not found. Please refresh and try again.', 'wp-courseware' ), false, true );
		}

		if ( ! user_can( $instructor_id, 'view_wpcw_courses' ) ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The selected user does not have permission to be an instructor.', 'wp-courseware' ), false, true );
		}

		if ( ! $course_id ) {
			$errors .= wpcw_admin_notice_error( esc_html__( 'The course id was not found. Please refresh and try again.', 'wp-courseware' ), false, true );
		}

		try {
			$course = new Course( $course_id );
			$course->update_instructor( $instructor_id, ( 'no' === $update_questions ) ? false : true );
		} catch ( \Exception $exception ) {
			$errors .= wpcw_admin_notice_error( $exception->getMessage(), false, true );
		}

		if ( $errors ) {
			return rest_ensure_response( array( 'notices' => $errors ) );
		}

		$notices = wpcw_admin_notice_success( __( 'The instructor has successfully been changed.', 'wp-courseware' ), false, true );

		return rest_ensure_response( array( 'notices' => $notices ) );
	}

	/**
	 * Api: Get Course Builder.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_course_builder( WP_REST_Request $request ) {
		$errors    = array();
		$course_id = $request->get_param( 'course_id' );

		if ( ! $course_id ) {
			$errors[] = __( '<strong>Course ID not found!</strong> Therefore, the builder could not be created. Is this course saved yet? Please save and try again.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$builder = $this->get_builder( $course_id );

		return rest_ensure_response( array( 'success' => true, 'builder' => $builder ) );
	}

	/**
	 * Api: Refresh Course Builder.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_refresh_course_builder( WP_REST_Request $request ) {
		$course_id = $request->get_param( 'course_id' );

		// Check for Course Id.
		if ( ! $course_id ) {
			return rest_ensure_response( array( 'errors' => esc_html__( 'The Course ID could not be found. Is this course saved yet? Please save and try again.', 'wp-courseware' ) ) );
		}

		// Get Builder.
		$builder = $this->get_builder( $course_id, true );

		return rest_ensure_response( array( 'builder' => $builder ) );
	}

	/**
	 * Api: Get Modules.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_course_builder_modules( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$order  = $request->get_param( 'order' );
		$author = $request->get_param( 'author' );
		$course = $request->get_param( 'course' );
		$module = $request->get_param( 'module' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = - 1;
		}

		if ( ! $order ) {
			$order = 'ASC';
		}

		if ( ! $author ) {
			$author = '';
		}

		if ( ! $course ) {
			$course = 0;
		}

		if ( ! $module ) {
			$module = 0;
		}

		if ( is_user_logged_in() && ! $author && ! current_user_can( 'manage_wpcw_settings' ) ) {
			$args['module_author'] = get_current_user_id();
		}

		$results    = array();
		$query_args = array(
			'number'           => - 1,
			'search'           => $search,
			'number'           => $number,
			'order'            => $order,
			'module_id'        => $module,
			'module_author'    => $author,
			'parent_course_id' => $course,
		);

		$modules = wpcw()->modules->get_modules( $query_args );
		$count   = wpcw()->modules->get_modules_count( $query_args );

		foreach ( $modules as $module ) {
			if ( ! $module instanceof Module ) {
				continue;
			}

			$results[] = array(
				'id'    => $module->get_id(),
				'title' => $module->get_module_title(),
				'desc'  => $module->get_module_desc(),
			);
		}

		return rest_ensure_response( array( 'modules' => $results ) );
	}

	/**
	 * Api: Add Module.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_add_module( WP_REST_Request $request ) {
		$errors        = array();
		$messages      = array();
		$course_id     = $request->get_param( 'course_id' );
		$module_data   = $request->get_param( 'module' );
		$module_number = $request->get_param( 'number' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no Course ID defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $module_data ) ) {
			$errors[] = esc_html__( 'The module data was empty. Please try again.', 'wp-courseware' );
		}

		if ( empty( $module_data['title'] ) ) {
			$errors[] = esc_html__( 'The module title is required.', 'wp-courseware' );
		}

		if ( empty( $module_data['desc'] ) ) {
			$errors[] = esc_html__( 'The module description is required.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		if ( ! $module_number ) {
			$module_number = 0;
		}

		$module        = array();
		$module_object = wpcw_insert_module( array(
			'parent_course_id' => absint( $course_id ),
			'module_author'    => get_current_user_id(),
			'module_title'     => wp_kses_post( $module_data['title'] ),
			'module_desc'      => wp_kses_post( $module_data['desc'] ),
			'module_order'     => $module_number + 1,
			'module_number'    => $module_number + 1,
		) );

		if ( $module_object ) {
			// Populate Module.
			$module = array(
				'id'     => $module_object->get_module_id(),
				'title'  => $module_object->get_module_title(),
				'number' => $module_object->get_module_number(),
				'order'  => $module_object->get_module_order(),
				'edit'   => $module_object->get_edit_url(),
				'units'  => $module_object->get_units(),
			);

			// Success Message.
			$messages[] = sprintf( __( '<strong>%s</strong> added successfully!', 'wp-courseware' ), $module_object->get_module_title() );

			// Invalidate Builder Cache.
			$this->invalidate_builder_cache( $course_id );
		}

		return rest_ensure_response( array( 'success' => true, 'module' => $module, 'messages' => $messages ) );
	}

	/**
	 * Api: Update Module.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_update_module( WP_REST_Request $request ) {
		$errors      = array();
		$messages    = array();
		$course_id   = $request->get_param( 'course_id' );
		$module_id   = $request->get_param( 'module_id' );
		$module_data = $request->get_param( 'module' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_data ) {
			$errors[] = esc_html__( 'There was no module information provided. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $module_data['title'] ) ) {
			$errors[] = esc_html__( 'Module title is required.', 'wp-courseware' );
		}

		if ( empty( $module_data['desc'] ) ) {
			$errors[] = esc_html__( 'Module description is required.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$module_object = wpcw_get_module( absint( $module_id ) );

		if ( ! empty( $module_data['title'] ) ) {
			$module_object->set_prop( 'module_title', wp_kses_post( $module_data['title'] ) );
		}

		if ( ! empty( $module_data['desc'] ) ) {
			$module_object->set_prop( 'module_desc', wp_kses_post( $module_data['desc'] ) );
		}

		// Save Module
		$module_object->save();

		// Invalidate Builder Cache.
		$this->invalidate_builder_cache( $course_id );

		// New Module Data.
		$module = array(
			'id'     => $module_object->get_module_id(),
			'title'  => $module_object->get_module_title(),
			'number' => $module_object->get_module_number(),
			'order'  => $module_object->get_module_order(),
			'edit'   => $module_object->get_edit_url(),
		);

		// Add Success Message
		$messages[] = sprintf( __( '<strong>%s</strong> updated successfully!', 'wp-courseware' ), $module_object->get_module_title() );

		return rest_ensure_response( array( 'success' => true, 'module' => $module, 'messages' => $messages ) );
	}

	/**
	 * Api: Update Module Order.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_update_module_order( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$modules   = $request->get_param( 'modules' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $modules ) ) {
			$errors[] = esc_html__( 'There are no modules defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$modules_map = array();
		foreach ( $modules as $key => $module ) {
			$modules_map[ $module['id'] ] = $module['order'];
		}

		$module_objects = wpcw_get_modules( array( 'number' => - 1, 'module_id' => array_keys( $modules_map ) ) );

		/** @var Module $module */
		foreach ( $module_objects as $module ) {
			$module->set_prop( 'module_order', $modules_map[ $module->get_id() ] );
			$module->set_prop( 'module_number', $modules_map[ $module->get_id() ] );
			$module->save();

			// Invalidate Builder Cache.
			$this->invalidate_builder_cache( $course_id );
		}

		// Message
		$messages[] = esc_html__( 'Module order updated successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => true, 'messages' => $messages ) );
	}

	/**
	 * Api: Delete Module.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_delete_module( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined when attempting to delete this module. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		// Get the module.
		$module = wpcw_get_module( $module_id );

		if ( $module && $module instanceof Module ) {
			// Delete Module
			$module->delete();

			// Invalidate the cache.
			$this->invalidate_builder_cache( $course_id );

			// Add Message
			$messages[] = sprintf( __( '<strong>%s</strong> deleted successfully!', 'wp-courseware' ), $module->get_module_title() );
		}

		return rest_ensure_response( array( 'success' => true, 'messages' => $messages ) );
	}

	/**
	 * Api: Get Units.
	 *
	 * @since 4.4.0
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_course_builder_units( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$order  = $request->get_param( 'order' );
		$author = $request->get_param( 'author' );
		$course = $request->get_param( 'course' );
		$module = $request->get_param( 'module' );
		$unit   = $request->get_param( 'unit' );
		$status = $request->get_param( 'status' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = - 1;
		}

		if ( ! $order ) {
			$order = 'ASC';
		}

		if ( ! $author ) {
			$author = '';
		}

		if ( ! $course ) {
			$course = 0;
		}

		if ( ! $module ) {
			$module = 0;
		}

		if ( ! $unit ) {
			$unit = 0;
		}

		if ( ! $status ) {
			$status = 'publish,private';
		}

		if ( is_user_logged_in() && ! $author && ! current_user_can( 'manage_wpcw_settings' ) ) {
			$author = get_current_user_id();
		}

		$results    = array();
		$query_args = array(
			'number'      => - 1,
			'search'      => $search,
			'number'      => $number,
			'order'       => $order,
			'orderby'     => 'unit_id',
			'status'      => $status,
			'unit_author' => $author,
			'course_id'   => $course,
			'module_id'   => $module,
			'unit_id'     => $unit,
		);

		$units = wpcw()->units->get_units( $query_args );
		$count = wpcw()->units->get_units_count( $query_args );

		foreach ( $units as $unit ) {
			if ( ! $unit instanceof Unit ) {
				continue;
			}

			$results[] = array(
				'id'     => $unit->get_id(),
				'title'  => $unit->get_unit_title(),
				'desc'   => $unit->get_unit_content(),
				'drip'   => array(
					'type'          => $unit->get_unit_drip_type(),
					'date'          => $unit->get_unit_drip_date_visible(),
					'interval'      => $unit->get_unit_drip_interval_number(),
					'interval_type' => $unit->get_unit_drip_interval_type() ?: 'interval_days',
				),
				'teaser' => $unit->get_unit_teaser()
			);
		}

		return rest_ensure_response( array( 'units' => $results ) );
	}

	/**
	 * Api: Add Unit.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_add_unit( WP_REST_Request $request ) {
		$errors      = array();
		$messages    = array();
		$course_id   = $request->get_param( 'course_id' );
		$module_id   = $request->get_param( 'module_id' );
		$unit_data   = $request->get_param( 'unit' );
		$unit_number = $request->get_param( 'number' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $unit_data ) ) {
			$errors[] = esc_html__( 'The unit data is empty. Please try again.', 'wp-courseware' );
		}

		if ( empty( $unit_data['title'] ) ) {
			$errors[] = esc_html__( 'The unit title is required.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		if ( empty( $unit_data['desc'] ) ) {
			$unit_data['desc'] = '';
		}

		if ( ! $unit_number ) {
			$unit_number = 0;
		}

		$unit = array();

		$unit_object_data = array(
			'parent_module_id' => absint( $module_id ),
			'parent_course_id' => absint( $course_id ),
			'unit_author'      => get_current_user_id(),
			'unit_title'       => $unit_data['title'],
			'unit_desc'        => $unit_data['desc'],
			'unit_order'       => $unit_number + 10,
			'unit_number'      => $unit_number + 1,
			'unit_teaser'      => absint( $unit_data['teaser'] ),
		);

		if ( ! empty( $unit_data['drip'] ) ) {
			$unit_object_data['unit_drip_type']          = sanitize_text_field( $unit_data['drip']['type'] );
			$unit_object_data['unit_drip_interval_type'] = sanitize_text_field( $unit_data['drip']['interval_type'] );
			$unit_object_data['unit_drip_interval']      = wpcw_unit_convert_drip_interval( $unit_data['drip']['interval'], $unit_object_data['unit_drip_interval_type'] );
			$unit_object_data['unit_drip_date']          = date( 'Y-m-d H:i:s', strtotime( $unit_data['drip']['date'] ) );
		}

		$unit_object = wpcw_insert_unit( $unit_object_data );

		if ( $unit_object ) {
			$unit_object->update_meta( 'wpcw_associated_module', $module_id );

			/**
			 * Action: Units Added.
			 *
			 * @since 4.5.1
			 *
			 * @param int $course_id The course id.
			 * @param int $module_id The module id.
			 */
			do_action( 'wpcw_units_added', $course_id, $module_id );

			// Refresh Unit.
			if ( $unit_object = wpcw_get_unit( $unit_object->get_id() ) ) {
				$unit = array(
					'id'      => $unit_object->get_id(),
					'title'   => $unit_object->get_unit_title(),
					'number'  => $unit_object->get_unit_number(),
					'order'   => $unit_object->get_unit_order(),
					'edit'    => $unit_object->get_edit_url(),
					'view'    => $unit_object->get_view_url(),
					'quizzes' => $unit_object->get_quizzes(),
					'teaser'  => $unit_object->get_unit_teaser(),
				);
			}

			// Invalidate Builder Cache.
			$this->invalidate_builder_cache( $course_id );

			// Add Message
			$messages[] = sprintf( __( '<strong>%s</strong> added successfully!', 'wp-courseware' ), $unit_object->get_unit_title() );
		}

		return rest_ensure_response( array( 'success' => true, 'unit' => $unit, 'messages' => $messages ) );
	}

	/**
	 * Api: Add Units.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_add_units( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$unit_ids  = $request->get_param( 'unit_ids' );
		$number    = $request->get_param( 'number' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $unit_ids ) ) {
			$errors[] = esc_html__( 'There were no units selected. Please try again.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		if ( ! $number ) {
			$number = 0;
		}

		$units           = array();
		$unit_query_args = array(
			'number'  => - 1,
			'unit_id' => $unit_ids,
			'status'  => 'private,publish',
			'orderby' => 'unit_id',
			'order'   => 'ASC',
		);

		if ( $unit_objects = wpcw_get_units( $unit_query_args ) ) {
			$unit_order  = 0;
			$unit_number = $number + 1;

			/** @var Unit $unit_object */
			foreach ( $unit_objects as $unit_object ) {
				// Update Counts
				$unit_order += 10;
				$unit_number ++;

				// Set properties.
				$unit_object->set_prop( 'parent_module_id', $module_id );
				$unit_object->set_prop( 'parent_course_id', $course_id );
				$unit_object->set_prop( 'unit_order', $unit_order );
				$unit_object->set_prop( 'unit_number', $unit_number );

				// Update Associated Module.
				if ( $unit_object->save( false ) ) {
					$unit_object->update_meta( 'wpcw_associated_module', $module_id );
				}
			}

			/**
			 * Action: Units Added.
			 *
			 * @since 4.5.1
			 *
			 * @param int $course_id The course id.
			 * @param int $module_id The module id.
			 */
			do_action( 'wpcw_units_added', $course_id, $module_id );

			// Requery the units.
			$units_objects_refreshed = wpcw_get_units( array(
				'number'  => - 1,
				'unit_id' => $unit_ids,
				'orderby' => 'unit_number',
				'status'  => 'private,publish',
				'order'   => 'ASC',
			) );

			if ( $units_objects_refreshed ) {
				/** @var Unit $unit_objects_refreshed */
				foreach ( $units_objects_refreshed as $unit_objects_refreshed ) {
					$units[] = array(
						'id'      => $unit_objects_refreshed->get_id(),
						'title'   => $unit_objects_refreshed->get_unit_title(),
						'number'  => $unit_objects_refreshed->get_unit_number(),
						'order'   => $unit_objects_refreshed->get_unit_order(),
						'edit'    => $unit_objects_refreshed->get_edit_url(),
						'view'    => $unit_objects_refreshed->get_view_url(),
						'quizzes' => $unit_objects_refreshed->get_quizzes(),
						'teaser'  => $unit_objects_refreshed->get_unit_teaser(),
					);
				}
			}

			// Invalidate Builder Cache.
			$this->invalidate_builder_cache( $course_id );

			// Message
			$messages[] = esc_html__( 'Units inserted successfully!', 'wp-courseware' );
		}

		return rest_ensure_response( array( 'success' => true, 'units' => $units, 'messages' => $messages ) );
	}

	/**
	 * Api: Update Unit.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_update_unit( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$unit_id   = $request->get_param( 'unit_id' );
		$unit_data = $request->get_param( 'unit' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_data ) {
			$errors[] = esc_html__( 'There was no unit data defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $unit_data['title'] ) ) {
			$errors[] = esc_html__( 'The unit title is required.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		if ( empty( $unit_data['desc'] ) ) {
			$unit_data['desc'] = '';
		}

		$unit = wpcw_get_unit( absint( $unit_id ) );

		if ( ! empty( $unit_data['title'] ) ) {
			$unit->set_prop( 'unit_title', $unit_data['title'] );
			$unit->set_prop( 'unit_desc', $unit_data['desc'] );
			$unit->set_prop( 'unit_teaser', absint( $unit_data['teaser'] ) );

			if ( ! empty( $unit_data['drip'] ) ) {
				$unit->set_prop( 'unit_drip_type', sanitize_text_field( $unit_data['drip']['type'] ) );
				$unit->set_prop( 'unit_drip_interval_type', sanitize_text_field( $unit_data['drip']['interval_type'] ) );
				$unit->set_prop( 'unit_drip_interval', wpcw_unit_convert_drip_interval( $unit_data['drip']['interval'], $unit->get_unit_drip_interval_type() ) );
				$unit->set_prop( 'unit_drip_date', date( 'Y-m-d H:i:s', strtotime( $unit_data['drip']['date'] ) ) );
			}
		}

		// Save Unit.
		$unit->save();

		// Update Post Meta
		$unit->update_meta( 'wpcw_associated_module', $module_id );

		/**
		 * Action: Units Updated.
		 *
		 * @since 4.5.1
		 *
		 * @param int $course_id The course id.
		 * @param int $module_id The module id.
		 */
		do_action( 'wpcw_units_updated', $course_id, $module_id );

		// Invalidate Builder Cache.
		$this->invalidate_builder_cache( $course_id );

		// New Module Data.
		$unit_data = array(
			'id'     => $unit->get_id(),
			'title'  => $unit->get_unit_title(),
			'number' => $unit->get_unit_number(),
			'order'  => $unit->get_unit_order(),
			'edit'   => $unit->get_edit_url(),
			'view'   => $unit->get_view_url(),
			'teaser' => $unit->get_unit_teaser(),
		);

		// Add Message.
		$messages[] = sprintf( __( '%s updated successfully!', 'wp-courseware' ), $unit->get_unit_title() );

		return rest_ensure_response( array( 'success' => true, 'unit' => $unit_data, 'messages' => $messages ) );
	}

	/**
	 * Api: Update Unit.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_update_unit_order( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$units     = $request->get_param( 'units' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined when trying to re-order units. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined when trying to re-order units. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $units ) ) {
			$errors[] = esc_html__( 'There were no units defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$units_map = array();
		foreach ( $units as $key => $unit ) {
			$units_map[ $unit['id'] ] = array(
				'order'  => $unit['order'],
				'number' => $unit['number'],
			);
		}

		$unit_objects = wpcw_get_units( array(
			'number'  => - 1,
			'unit_id' => array_keys( $units_map ),
			'status'  => 'private,publish,draft',
		) );

		/** @var Unit $unit */
		foreach ( $unit_objects as $unit ) {
			$unit->set_prop( 'parent_module_id', $module_id );
			$unit->set_prop( 'unit_order', $units_map[ $unit->get_id() ]['order'] );
			$unit->set_prop( 'unit_number', $units_map[ $unit->get_id() ]['number'] );
			$unit->save( false );

			// Update Post Meta
			$unit->update_meta( 'wpcw_associated_module', $module_id );
		}

		/**
		 * Action: Units Updated.
		 *
		 * @since 4.4.0
		 *
		 * @param int $course_id The course id.
		 * @param int $module_id The module id.
		 */
		do_action( 'wpcw_units_updated', $course_id, $module_id );

		// Invalidate Builder Cache.
		$this->invalidate_builder_cache( $course_id );

		// Add Message.
		$messages[] = esc_html__( 'Unit order updated successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => true, 'messages' => $messages ) );
	}

	/**
	 * Api: Delete Unit.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_delete_unit( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$unit_id   = $request->get_param( 'unit_id' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined when attempting to delete this unit. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined when attempting to delete this unit. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		// Get the unit.
		$unit = wpcw_get_unit( $unit_id );

		if ( $unit && $unit instanceof Unit ) {
			// Disconnect Unit
			$unit->disconnect();

			/**
			 * Action: Units Deleted.
			 *
			 * @since 4.5.1
			 *
			 * @param int $course_id The course id.
			 * @param int $module_id The module id.
			 */
			do_action( 'wpcw_units_deleted', $course_id, $module_id );

			// Invalidate Cache.
			$this->invalidate_builder_cache( $course_id );

			// Add Message.
			$messages[] = sprintf( __( '<strong>%s</strong> deleted successfully.', 'wp-courseware' ), $unit->get_unit_title() );
		}

		return rest_ensure_response( array( 'success' => true, 'messages' => $messages ) );
	}

	/**
	 * Api: Get Quizzes.
	 *
	 * @since 4.4.0
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_course_builder_quizzes( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$order  = $request->get_param( 'order' );
		$author = $request->get_param( 'author' );
		$course = $request->get_param( 'course' );
		$unit   = $request->get_param( 'unit' );
		$quiz   = $request->get_param( 'quiz' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = - 1;
		}

		if ( ! $order ) {
			$order = 'DESC';
		}

		if ( ! $author ) {
			$author = '';
		}

		if ( ! $course ) {
			$course = 0;
		}

		if ( ! $unit ) {
			$unit = 0;
		}

		if ( ! $quiz ) {
			$quiz = 0;
		}

		if ( is_user_logged_in() && ! $author && ! current_user_can( 'manage_wpcw_settings' ) ) {
			$author = get_current_user_id();
		}

		$quizzes      = array();
		$quiz_objects = wpcw_get_quizzes( array(
			'search'      => $search,
			'number'      => $number,
			'order'       => $order,
			'quiz_author' => $author,
			'course_id'   => $course,
			'unit_id'     => $unit,
			'quiz_id'     => $quiz,
		) );

		if ( $quiz_objects ) {
			/** @var Quiz $quiz_object */
			foreach ( $quiz_objects as $quiz_object ) {
				$quizzes[] = array(
					'id'    => $quiz_object->get_id(),
					'title' => $quiz_object->get_quiz_title(),
					'desc'  => $quiz_object->get_quiz_desc(),
				);
			}
		}

		return rest_ensure_response( array( 'quizzes' => $quizzes ) );
	}

	/**
	 * Api: Add Quiz.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_add_quiz( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$unit_id   = $request->get_param( 'unit_id' );
		$module_id = $request->get_param( 'module_id' );
		$quiz_data = $request->get_param( 'quiz' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $quiz_data ) ) {
			$errors[] = esc_html__( 'The quiz data was empty. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $quiz_data['title'] ) ) {
			$errors[] = esc_html__( 'The quiz title is required.', 'wp-courseware' );
		}

		if ( ! empty( $quiz_data['title'] ) && strlen( $quiz_data['title'] ) >= 500 ) {
			$errors[] = esc_html__( 'The quiz title cannot be more than 500 characters.', 'wp-courseware' );
		}

		if ( empty( $quiz_data['desc'] ) ) {
			$errors[] = esc_html__( 'The quiz description is required.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$quiz        = array();
		$quiz_object = wpcw_insert_quiz( array(
			'parent_unit_id'   => absint( $unit_id ),
			'parent_course_id' => absint( $course_id ),
			'quiz_author'      => get_current_user_id(),
			'quiz_title'       => wp_kses_post( $quiz_data['title'] ),
			'quiz_desc'        => wp_kses_post( $quiz_data['desc'] ),
		) );

		if ( $quiz_object ) {
			// Populate Module.
			$quiz = array(
				'id'    => $quiz_object->get_id(),
				'title' => $quiz_object->get_quiz_title(),
				'edit'  => $quiz_object->get_edit_url(),
			);

			// Invalidate Builder Cache.
			$this->invalidate_builder_cache( $course_id );

			// Add Message.
			$messages[] = sprintf( __( '<strong>%s</strong> added successfully!', 'wp-courseware' ), $quiz_object->get_quiz_title() );
		}

		return rest_ensure_response( array( 'success' => true, 'quiz' => $quiz, 'messages' => $messages ) );
	}

	/**
	 * Api: Add Quizzes.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_add_quizzes( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$unit_id   = $request->get_param( 'unit_id' );
		$module_id = $request->get_param( 'module_id' );
		$quiz_id   = $request->get_param( 'quiz_id' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $quiz_id ) {
			$errors[] = esc_html__( 'No quiz was selected. Please try again.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$quiz        = array();
		$quiz_object = wpcw_get_quiz( absint( $quiz_id ) );

		if ( $quiz_object ) {
			$quiz_object->set_prop( 'parent_course_id', $course_id );
			$quiz_object->set_prop( 'parent_unit_id', $unit_id );

			if ( $quiz_object->save() ) {
				$quiz = array(
					'id'    => $quiz_object->get_id(),
					'title' => $quiz_object->get_quiz_title(),
					'edit'  => $quiz_object->get_edit_url(),
				);

				// Invalidate Builder Cache.
				$this->invalidate_builder_cache( $course_id );

				// Add Message
				$messages[] = esc_html__( 'Quizzes added successfully!', 'wp-courseware' );
			}
		}

		return rest_ensure_response( array( 'success' => true, 'quiz' => $quiz, 'messages' => $messages ) );
	}

	/**
	 * Api: Update Quiz.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_update_quiz( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$unit_id   = $request->get_param( 'unit_id' );
		$quiz_id   = $request->get_param( 'quiz_id' );
		$quiz_data = $request->get_param( 'quiz' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $quiz_id ) {
			$errors[] = esc_html__( 'There was no quiz defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $quiz_data ) {
			$errors[] = esc_html__( 'There was no quiz data defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( empty( $quiz_data['title'] ) ) {
			$errors[] = esc_html__( 'Quiz title is required.', 'wp-courseware' );
		}

		if ( ! empty( $quiz_data['title'] ) && strlen( $quiz_data['title'] ) >= 500 ) {
			$errors[] = esc_html__( 'The quiz title cannot be more than 500 characters.', 'wp-courseware' );
		}

		if ( empty( $quiz_data['desc'] ) ) {
			$errors[] = esc_html__( 'Quiz description is required.', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		$quiz = wpcw_get_quiz( absint( $quiz_id ) );

		if ( ! empty( $quiz_data['title'] ) ) {
			$quiz->set_prop( 'quiz_title', wp_kses_post( $quiz_data['title'] ) );
		}

		if ( ! empty( $quiz_data['desc'] ) ) {
			$quiz->set_prop( 'quiz_desc', wp_kses_post( $quiz_data['desc'] ) );
		}

		// Save Quiz
		$quiz->save();

		// Invalidate Builder Cache.
		$this->invalidate_builder_cache( $course_id );

		// New Module Data.
		$quiz_data = array(
			'id'    => $quiz->get_id(),
			'title' => $quiz->get_quiz_title(),
			'edit'  => $quiz->get_edit_url(),
		);

		// Add Message.
		$messages[] = sprintf( __( '<strong>%s</strong> updated successfully!', 'wp-courseware' ), $quiz->get_quiz_title() );

		return rest_ensure_response( array( 'success' => true, 'quiz' => $quiz_data, 'messages' => $messages ) );
	}

	/**
	 * Api: Update Quiz Order.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_update_quiz_order( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$unit_id   = $request->get_param( 'unit_id' );
		$quiz_id   = $request->get_param( 'quiz_id' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined when trying to re-order quizzes. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined when trying to re-order quizzes. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined when trying to re-order quizzes. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $quiz_id ) {
			$errors[] = esc_html__( 'There was no quiz defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		// Update Quiz Association.
		if ( $quiz = wpcw_get_quiz( absint( $quiz_id ) ) ) {
			// Update Associations.
			$quiz->set_prop( 'parent_course_id', $course_id );
			$quiz->set_prop( 'parent_unit_id', $unit_id );
			$quiz->save();

			// Update Student Progress Quiz Association.
			$quiz->update_spq_association();

			// Invalidate Cache.
			$this->invalidate_builder_cache( $course_id );

			// Add Message.
			$messages[] = esc_html__( 'Quiz order updated successfully!', 'wp-courseware' );
		}

		return rest_ensure_response( array( 'success' => true, 'messages' => $messages ) );
	}

	/**
	 * Api: Delete Quiz.
	 *
	 * @since 4.4.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_course_builder_delete_quiz( WP_REST_Request $request ) {
		$errors    = array();
		$messages  = array();
		$course_id = $request->get_param( 'course_id' );
		$module_id = $request->get_param( 'module_id' );
		$unit_id   = $request->get_param( 'unit_id' );
		$quiz_id   = $request->get_param( 'quiz_id' );

		if ( ! $course_id ) {
			$errors[] = esc_html__( 'There was no course defined when attempting to delete this quiz. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $module_id ) {
			$errors[] = esc_html__( 'There was no module defined when attempting to delete this quiz. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $unit_id ) {
			$errors[] = esc_html__( 'There was no unit defined when attempting to delete this quiz. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! $quiz_id ) {
			$errors[] = esc_html__( 'There was no quiz defined. Please refresh your page and try again!', 'wp-courseware' );
		}

		if ( ! empty( $errors ) ) {
			return rest_ensure_response( array( 'success' => false, 'errors' => $errors ) );
		}

		// Get the quiz.
		$quiz = wpcw_get_quiz( $quiz_id );

		if ( $quiz && $quiz instanceof Quiz ) {
			// Disconnect the quiz.
			$quiz->disconnect();

			// Invalidate Cache.
			$this->invalidate_builder_cache( $course_id );

			// Add message.
			$messages[] = sprintf( __( '<strong>%s</strong> deleted successfully!', 'wp-courseware' ), $quiz->get_quiz_title() );
		}

		return rest_ensure_response( array( 'success' => true, 'messages' => $messages ) );
	}

	/** Miscellaneous Methods -------------------------------------------------- */

	/**
	 * Grant Course Access.
	 *
	 * @sicne 4.4.0
	 *
	 * @param int $course_id The course id.
	 * @param int $user_id The user id.
	 *
	 * @return bool True if the user is given access, false otherwise.
	 */
	public function grant_course_access( $course_id = 0, $user_id = 0 ) {
		global $wpdb, $wpcwdb;

		if ( ! $course_id || ! $user_id ) {
			return false;
		}

		// Check if has access.
		$has_access = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id = %d", absint( $user_id ), absint( $course_id ) ) );

		// Already has access.
		if ( $has_access ) {
			return false;
		}

		// Give Access.
		return $wpdb->query( $wpdb->prepare( "INSERT INTO $wpcwdb->user_courses (user_id, course_id, course_progress, course_final_grade_sent) VALUES(%d, %d, 0, '')", absint( $user_id ), absint( $course_id ) ) );
	}

	/**
	 * Delete Course.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The module id.
	 */
	public function delete_course( $id, $method = 'complete' ) {
		if ( ! is_admin() || ! current_user_can( 'view_wpcw_courses' ) ) {
			return false;
		}

		$course = new Course( $id );

		if ( $course->get_course_id() ) {
			if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
				if ( $course->get_course_author() !== get_current_user_id() ) {
					return false;
				}
			}

			if ( WPCW_modules_deleteCourse( $course, $method ) ) {
				return $course;
			}
		}

		return false;
	}

	/**
	 * Create Courses Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_courses_page() {
		return wp_insert_post(
			array(
				'post_title'     => esc_html__( 'Courses', 'wp-courseware' ),
				'post_content'   => '[wpcw_courses]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 10,
			)
		);
	}

	/**
	 * Maybe Disable Automatic Enrollment.
	 *
	 * If a course is paid, either a one-time or subscription
	 * then disabled the automatic enrollment that happens
	 * when a user is registered.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $disable Boolean to disable automatic enrollment. Default is false.
	 * @param int  $course_id The course id.
	 *
	 * @return bool $disable Boolean value to disable automatic enrollment.
	 */
	public function maybe_disable_autommatic_enrollment( $disable, $course_id ) {
		$payments_type = $this->get_course_payments_type( $course_id );

		if ( $payments_type && 'free' !== $payments_type ) {
			$disable = true;
		}

		return $disable;
	}

	/**
	 * Maybe Upgrade Courses.
	 *
	 * @since 4.4.0
	 */
	public function maybe_upgrade_courses() {
		$courses = $this->get_courses( array( 'course_status' => '', 'number' => - 1 ) );

		if ( $courses ) {
			/** @var Course $course */
			foreach ( $courses as $course ) {
				$post_id = $course->get_course_post_id();
				if ( empty( $post_id ) || 0 === $post_id ) {
					$course->create_post();
				}
			}
		}
	}

	/**
	 * Maybe Fix Duplicated Courses.
	 *
	 * @since 4.4.2
	 */
	public function maybe_fix_duplicate_courses() {
		global $wpdb, $wpcwdb;

		$courses  = $this->get_courses( array( 'course_status' => '', 'number' => - 1 ) );
		$post_ids = array();

		if ( $courses ) {
			/** @var Course $course */
			foreach ( $courses as $course ) {
				$post_id = $course->get_course_post_id();
				if ( ! empty( $post_id ) ) {
					$post_ids[ $course->get_id() ] = $post_id;
				}
			}
		}

		if ( ! empty( $post_ids ) ) {
			ksort( $post_ids );
			$duplicates = wpcw_array_get_duplicates( $post_ids );
			foreach ( $duplicates as $duplicate_course_id => $duplicate_course_post_id ) {
				$this->log( sprintf( 'About to fix duplicate course #%d with duplicate Post ID: #%d', $duplicate_course_id, $duplicate_course_post_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->courses WHERE course_id = %d", $duplicate_course_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE course_id = %d", $duplicate_course_id ) );
				$this->log( sprintf( 'Finished fixing duplicate course #%d with duplicate Post ID: #%d', $duplicate_course_id, $duplicate_course_post_id ) );
			}
		}
	}

	/** Course Builder Methods -------------------------------------------------- */

	/**
	 * Get Course Builder.
	 *
	 * @since 4.4.0
	 *
	 * @param int $course_id The course Id.
	 *
	 * @return mixed
	 */
	public function get_builder( $course_id, $refresh = false ) {
		$course  = new Course( $course_id );
		$builder = $course->get_meta( $this->builder_cache_key, true );

		// Is builder cache invalidated.
		$builder_cache_invalidated = $course->get_meta( $this->builder_cache_key . '_invalidated', true );

		// Cache Builder.
		if ( ! $builder || $builder_cache_invalidated || $refresh ) {
			$builder      = array();
			$module_count = 0;
			$unit_count   = 0;
			$quiz_count   = 0;

			// Get Modules.
			$modules = $course->get_modules( array( 'number' => - 1 ) );

			// Populate Modules, Units, Quizzes
			if ( ! empty( $modules ) ) {
				$module_counter = 0;

				/** @var Module $module */
				foreach ( $modules as $module ) {
					$builder['modules'][ $module_counter ] = array(
						'id'     => $module->get_module_id(),
						'title'  => $module->get_module_title(),
						'number' => $module->get_module_number(),
						'order'  => $module->get_module_order(),
						'edit'   => $module->get_edit_url(),
					);

					if ( $module_units = $module->get_units( array( 'number' => - 1, 'status' => array( 'private', 'publish' ) ) ) ) {
						$unit_counter = 0;

						/** @var Unit $module_unit */
						foreach ( $module_units as $module_unit ) {
							$builder['modules'][ $module_counter ]['units'][ $unit_counter ] = array(
								'id'     => $module_unit->get_id(),
								'title'  => $module_unit->get_unit_title(),
								'edit'   => $module_unit->get_edit_url(),
								'view'   => $module_unit->get_view_url(),
								'number' => $module_unit->get_unit_number(),
								'order'  => $module_unit->get_unit_order(),
								'teaser' => $module_unit->get_unit_teaser()
							);

							if ( $unit_quizzes = $module_unit->get_quizzes() ) {
								$quiz_counter = 0;

								/** @var Quiz $unit_quiz */
								foreach ( $unit_quizzes as $unit_quiz ) {
									$builder['modules'][ $module_counter ]['units'][ $unit_counter ]['quizzes'][ $quiz_counter ] = array(
										'id'    => $unit_quiz->get_id(),
										'title' => $unit_quiz->get_quiz_title(),
										'edit'  => $unit_quiz->get_edit_url(),
									);
								}

								$quiz_counter ++;
								$quiz_count ++;
							}

							$unit_counter ++;
							$unit_count ++;
						}
					}

					$module_counter ++;
					$module_count ++;
				}
			} else {
				$builder['modules'] = array();
			}

			// Record Count
			$builder['count'] = array(
				'modules' => $module_count,
				'units'   => $unit_count,
				'quizzes' => $quiz_count,
			);

			// Update Builder.
			if ( ! empty( $modules ) ) {
				$course->update_meta( $this->builder_cache_key, $builder );
				$course->update_meta( $this->builder_cache_key . '_invalidated', false );
			}
		}

		return $builder;
	}

	/**
	 * Invalidate Course Builder Cache.
	 *
	 * @since 4.4.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return bool True on update, False otherwise.
	 */
	public function invalidate_builder_cache( $course_id ) {
		$course = new Course( $course_id );

		return $course->update_meta( $this->builder_cache_key . '_invalidated', true );
	}

	/** Course Single Methods -------------------------------------------------- */

	/**
	 * When the_post is called, put product data into a global.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Post  $post The Post object (passed by reference).
	 * @param WP_Query $this The current Query object (passed by reference).
	 */
	public function setup_course_data( $post ) {
		unset( $GLOBALS['course'] );

		if ( is_int( $post ) ) {
			$the_post = get_post( $post );
		} else {
			$the_post = $post;
		}

		if ( empty( $the_post->post_type ) || ! in_array( $the_post->post_type, array( $this->post_type_slug ), true ) ) {
			return;
		}

		$GLOBALS['course'] = wpcw_get_course( $the_post );

		return $GLOBALS['course'];
	}

	/**
	 * Course Template Actions.
	 *
	 * @since 4.4.0
	 */
	public function course_template_actions() {
		$courses_page_id = wpcw_get_page_id( 'courses' );

		if ( 0 < $courses_page_id ) {
			if ( wpcw_is_course_single() ) {
				add_filter( 'the_content', array( $this, 'single_content' ), 10 );
			}

			if ( wpcw_is_course_archive() || wpcw_is_course_taxonomy() ) {
				add_filter( 'the_excerpt', array( $this, 'archive_excerpt' ), 10 );
				add_filter( 'the_content', array( $this, 'archive_content' ), 10 );
			}
		}
	}

	/**
	 * Course Single Content.
	 *
	 * @since 4.4.0
	 *
	 * @param string $content The post content.s
	 */
	public function single_content( $content ) {
		global $wp_query;

		if ( ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'single_content' ), 10 );
		add_filter( 'the_content', 'wpautop' );
		ob_start();
		wpcw_get_template( 'course/single-content.php' );
		$content = ob_get_clean();
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', array( $this, 'single_content' ), 10 );

		return $content;
	}

	/**
	 * Course Archive Content.
	 *
	 * @since 4.4.0
	 *
	 * @param string $content The post content.s
	 */
	public function archive_content( $content ) {
		global $wp_query;

		if ( ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( $this, 'archive_content' ), 10 );
		add_filter( 'the_content', 'wpautop' );
		ob_start();
		wpcw_get_template( 'course/archive-content.php' );
		$content = ob_get_clean();
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', array( $this, 'archive_content' ), 10 );

		return $content;
	}

	/**
	 * Course Archive Excerpt.
	 *
	 * @since 4.4.3
	 *
	 * @param string $post_excerpt The post excerpt.
	 */
	public function archive_excerpt( $post_excerpt ) {
		global $wp_query;

		if ( ! is_main_query() || ! in_the_loop() ) {
			return $post_excerpt;
		}

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_excerpt', array( $this, 'archive_excerpt' ), 10 );
		remove_filter( 'the_content', array( $this, 'archive_content' ), 10 );
		ob_start();
		wpcw_get_template( 'course/archive-excerpt.php' );
		$post_excerpt = ob_get_clean();
		add_filter( 'the_content', array( $this, 'archive_content' ), 10 );
		add_filter( 'the_excerpt', array( $this, 'archive_excerpt' ), 10 );

		return $post_excerpt;
	}

	/** Shortcode Methods ----------------------------------------------------- */

	/**
	 * Shortcode Courses Display.
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The array of shortcode attributes.
	 */
	public function courses_display( $atts = array() ) {
		$display_atts = array(
			'number'        => 100,
			'order'         => 'DESC',
			'orderby'       => 'date',
			'course_author' => '',
			'course_id'     => 0,
			'search'        => '',
			'show_image'    => true,
			'show_desc'     => true,
			'show_button'   => true,
		);

		$display_atts = wp_parse_args( $atts, $display_atts );

		// Boolean values.
		$display_atts['show_image']  = filter_var( $display_atts['show_image'], FILTER_VALIDATE_BOOLEAN );
		$display_atts['show_desc']   = filter_var( $display_atts['show_desc'], FILTER_VALIDATE_BOOLEAN );
		$display_atts['show_button'] = filter_var( $display_atts['show_button'], FILTER_VALIDATE_BOOLEAN );

		// Query Aargs.
		$query_args = array(
			'number'        => ! empty( $display_atts['number'] ) ? absint( $display_atts['number'] ) : 10,
			'order'         => ! empty( $display_atts['order'] ) ? esc_attr( $display_atts['order'] ) : 'DESC',
			'orderby'       => ! empty( $display_atts['orderby'] ) ? esc_attr( $display_atts['orderby'] ) : 'date',
			'course_author' => ! empty( $display_atts['course_author'] ) ? absint( $display_atts['course_author'] ) : '',
			'course_id'     => ! empty( $display_atts['course_id'] ) ? absint( $display_atts['course_id'] ) : 0,
			'search'        => ! empty( $display_atts['search'] ) ? $display_atts['search'] : '',
		);

		// Get Courses.
		$courses = $this->get_courses( $query_args );

		if ( empty( $courses ) ) {
			wpcw_print_notice( esc_html__( 'There are currently no courses available to display.', 'wp-courseware' ), 'info' );

			return;
		}

		wpcw_get_template( 'courses.php', array( 'courses' => $courses, 'atts' => $display_atts ) );
	}

	/** Delete Methods ----------------------------------------------------- */

	/**
	 * Delete Course.
	 *
	 * @since 4.4.0
	 *
	 * @param int $post_id The post id.
	 */
	public function delete( $post_id ) {
		if ( get_post_type( $post_id ) !== $this->post_type_slug ) {
			return;
		}

		// Instantiate Course.
		$course = new Course( $post_id, true );

		// Delete Course.
		if ( $course instanceof Course && $course->exists() ) {
			$course->delete( false );
		}
	}

	/**
	* Exclude expiration courses.
	*/
	public function wpcw_exclude_expiration_courses( $query ) {
		$exclude_courses = array();
		if ( 'wpcw_course' === $query->get( 'post_type' ) && ! is_admin() ) {
			if ( $courses = wpcw()->courses->get_courses( array( 'fields' => array( 'course_post_id' ) ), true ) ) {
				foreach ( $courses as $course ) {
					if ( WPCW_check_course_expiration( $course->course_post_id ) ) {
						$exclude_courses[] = intval( $course->course_post_id );
					}
				}
			}
			if ( ! empty( $exclude_courses ) ) {
				$query->set( 'post__not_in', $exclude_courses );
			}
			return $query;
		}
	}
}
