<?php
/**
 * WP Courseware Units Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WP_Post;
use WP_Query;
use WP_REST_Request;
use WPCW\Core\Api;
use WPCW\Database\DB_Units;
use WPCW\Models\Module;
use WPCW\Models\Unit;
use WPCW_queue_dripfeed;
use WPCW_UnitFrontend;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Units.
 *
 * @since 4.3.0
 */
class Units extends Controller {

	/**
	 * @var DB_Units The units db object.
	 * @since 4.4.0
	 */
	protected $db;

	/**
	 * @var string The post type slug.
	 * @since 4.3.0
	 */
	public $post_type_slug = 'course_unit';

	/**
	 * @var string The category slug.
	 * @since 4.3.0
	 */
	public $taxonomy_category_slug = 'course_unit_category';

	/**
	 * @var string The tag slug.
	 * @since 4.3.0
	 */
	public $taxonomy_tag_slug = 'course_unit_tag';

	/**
	 * @var array The unit permalinks.
	 * @since 4.4.0
	 */
	protected $permalinks;

	/**
	 * @var Module The module object.
	 * @since 4.4.0
	 */
	protected $module;

	/**
	 * Units Constructor.
	 *
	 * @since 4.4.0
	 */
	public function __construct() {
		$this->db = new DB_Units();
	}

	/**
	 * Units Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Unit Permalinks
		add_action( 'wpcw_init', array( $this, 'post_type_permalinks' ) );

		// Course Unit Post Type.
		add_action( 'init', array( $this, 'post_type' ), 5 );
		add_action( 'init', array( $this, 'post_type_rewrites' ) );
		add_action( 'init', array( $this, 'post_type_comments_support' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_type_updated_messages' ) );
		add_filter( 'posts_fields', array( $this, 'post_type_add_unit_fields' ), 10, 2 );
		add_filter( 'posts_join', array( $this, 'post_type_join_units_table' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'post_type_exclude_from_search' ) );
		add_filter( 'the_content', array( $this, 'post_type_remove_content' ) );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 0, 4 );
		add_action( 'pre_get_posts', array( $this, 'post_type_permission_filter' ) );
		add_action( 'current_screen', array( $this, 'post_type_permission_filter_count' ) );
		add_filter( 'register_post_type_args', array( $this, 'post_type_remove_rest_api_support' ), 20, 2 );

		// Unit Meta Boxes.
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );

		// Course Unit Taxonomies.
		add_action( 'init', array( $this, 'taxonomy_category' ), 0 );
		add_action( 'init', array( $this, 'taxonomy_tag' ), 0 );
		add_filter( 'admin_head', array( $this, 'taxonomy_menu_fix' ) );
		add_action( 'admin_head-edit-tags.php', array( $this, 'taxonomy_action_buttons' ) );
		add_action( 'admin_head-edit-tags.php', array( $this, 'taxonomy_title_icon' ) );

		// Add Taxonomy Buttons
		add_filter( 'wpcw_admin_page_units_action_buttons', array( $this, 'add_taxonomy_buttons' ) );
		add_filter( 'wpcw_admin_page_units_single_action_buttons', array( $this, 'add_taxonomy_buttons' ) );

		// Update Drip Feed Cron.
		add_action( 'wpcw_settings_after_save', array( $this, 'update_drip_feed_cron' ) );

		// Update Unit Ordering.
		add_action( 'wpcw_units_added', array( $this, 'update_unit_ordering' ), 10, 2 );
		add_action( 'wpcw_units_updated', array( $this, 'update_unit_ordering' ), 10, 2 );
		add_action( 'wpcw_units_deleted', array( $this, 'update_unit_ordering' ), 10, 2 );

		// Update Unit Total & Progress.
		add_action( 'wpcw_units_added', array( $this, 'update_unit_total_and_progress' ), 10, 2 );
		add_action( 'wpcw_units_deleted', array( $this, 'update_unit_total_and_progress' ), 10, 2 );

		// Api Endpoints
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );

		// Skip password protection for admin or teacher
		add_filter( 'post_password_required', array( $this, 'wpcw_post_password_required' ), 10, 2 );

		// Divi Builder Check.
		$theme = wp_get_theme();
		if ( 'Divi' === $theme->name || 'Divi' === $theme->parent_theme ) {
			add_action( 'et_theme_builder_template_after_body', array( $this, 'wpcw_th_render_unit_template' ), 10, 3 );
		}

		// Beaver Builder check.
		if ( class_exists( 'FLThemeBuilder' ) && ! isset( $_GET['fl_builder'] ) ) {
			add_action( 'fl_builder_after_render_shortcodes', array( $this, 'wpcw_bb_render_unit_template' ), 10, 1 );
		}

		// Avia Builder check
		add_filter('avf_template_builder_content', array( $this, 'wpcw_avia_render_unit_template' ), 12 );

		add_filter( 'template_redirect', array( $this, 'unit_template_actions' ) );
	}

	/** Settings Methods ---------------------------------------------- */

	/**
	 * Get Units Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of unit settings fields.
	 */
	public function get_settings_fields() {
		$general_section_fields    = $this->get_general_section_settings_fields();
		$permalinks_section_fields = $this->get_permalinks_section_settings_fields();

		$settings_fields = array_merge( $general_section_fields, $permalinks_section_fields );

		return apply_filters( 'wpcw_unit_settings_fields', $settings_fields );
	}

	/**
	 * Get Unit General Section Settings Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of general unit settings fields.
	 */
	public function get_general_section_settings_fields() {
		return apply_filters(
			'wpcw_unit_general_section_settings_fields',
			array(
				array(
					'type'  => 'heading',
					'key'   => 'units_section_heading',
					'title' => esc_html__( 'Unit Comments', 'wp-courseware' ),
					'desc'  => esc_html__( 'Below are settings for comments functionality on units.', 'wp-courseware' ),
				),
				array(
					'type'     => 'radio',
					'title'    => esc_html__( 'Unit Comments', 'wp-courseware' ),
					'key'      => 'enable_unit_comments',
					'default'  => 'yes',
					'desc_tip' => esc_html__( 'If you enable comments, you will have the ability to disable comments for individual units.', 'wp-courseware' ),
					'options'  => array(
						'yes' => esc_html__( 'Yes', 'wp-courseware' ),
						'no'  => esc_html__( 'No', 'wp-courseware' ),
					),
				),
				array(
					'type'  => 'heading',
					'key'   => 'drip_feed_section_heading',
					'title' => esc_html__( 'Unit Drip Feed', 'wp-courseware' ),
					'desc'  => esc_html__( 'Below are settings that determine functionality for the unit drip feed.', 'wp-courseware' ),
				),
				array(
					'type'         => 'select',
					'key'          => 'cron_time_dripfeed',
					'title'        => esc_html__( 'Notifications Interval', 'wp-courseware' ),
					'placeholder'  => esc_html__( 'Select an Interval', 'wp-courseware' ),
					'desc_tip'     => esc_html__( 'How frequently should the system check if there are any notifications to send out to trainees? When a unit that is locked by a drip feed setting becomes available, the system sends them an email. This setting determines how frequently the system should check for possible notifications.', 'wp-courseware' ),
					'options'      => $this->get_drip_feed_intervals(),
					'blank_option' => esc_html__( 'Select Interval', 'wp-courseware' ),
					'default'      => 'twicedaily',
				),
				array(
					'type'  => 'heading',
					'key'   => 'unit_labels_section_heading',
					'title' => esc_html__( 'Unit Labels', 'wp-courseware' ),
					'desc'  => esc_html__( 'Below are settings that determine what a Unit is called on the frontend.', 'wp-courseware' ),
				),
				array(
					'type'         => 'select',
					'key'          => 'unit_label',
					'title'        => esc_html__( 'Unit Label', 'wp-courseware' ),
					'placeholder'  => esc_html__( 'Select a Unit label', 'wp-courseware' ),
					'desc_tip'     => esc_html__( 'This setting will determine what a "Unit" is labled on the frontend.', 'wp-courseware' ),
					'options'      => array(
						'unit'    => esc_html__( 'Unit', 'wp-courseware' ),
						'lesson'  => esc_html__( 'Lesson', 'wp-courseware' ),
						'lecture' => esc_html__( 'Lecture', 'wp-courseware' ),
						'custom'  => esc_html__( 'Custom', 'wp-courseware' ),
					),
					'blank_option' => esc_html__( 'Select a Unit label', 'wp-courseware' ),
					'default'      => 'unit',
				),
				array(
					'type'        => 'text',
					'key'         => 'unit_label_custom',
					'default'     => '',
					'placeholder' => esc_html__( 'Unit', 'wp-courseware' ),
					'title'       => esc_html__( 'Custom Label', 'wp-courseware' ),
					'desc_tip'    => esc_html__( 'The custom Unit label.', 'wp-courseware' ),
					'condition'   => array(
						'field' => 'unit_label',
						'value' => 'custom',
					),
				),
				array(
					'type'        => 'text',
					'key'         => 'unit_label_custom_plural',
					'default'     => '',
					'placeholder' => esc_html__( 'Units', 'wp-courseware' ),
					'title'       => esc_html__( 'Custom Label - Plural', 'wp-courseware' ),
					'desc_tip'    => esc_html__( 'The custom Unit label that is plural, or more than one.', 'wp-courseware' ),
					'condition'   => array(
						'field' => 'unit_label',
						'value' => 'custom',
					),
				),
			)
		);
	}

	/**
	 * Get Unit Permalinks Section Settings Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of permalinks settings fields.
	 */
	public function get_permalinks_section_settings_fields() {
		return apply_filters(
			'wpcw_unit_permalinks_section_settings_fields',
			array(
				array(
					'type'  => 'heading',
					'key'   => 'unit_permalinks',
					'title' => esc_html__( 'Unit Permalinks', 'wp-courseware' ),
					'desc'  => esc_html__( 'Below are settings related to the course unit single, category, and tag permalinks.', 'wp-courseware' ),
				),
				array(
					'type'      => 'unit_permalinks',
					'key'       => 'unit_permalinks',
					'component' => true,
					'views'     => array( 'settings/settings-field-unit-permalinks' ),
					'settings'  => array(
						array(
							'key'     => 'unit_permalink',
							'type'    => 'radio',
							'default' => '/' . trailingslashit( '%module_number%' ),
						),
						array(
							'key'     => 'unit_permalink_structure',
							'type'    => 'text',
							'default' => '/' . trailingslashit( '%module_number%' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Get Unit Taxonomies Section Settings Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of permalinks settings fields.
	 */
	public function get_taxonomies_section_settings_fields() {
		return apply_filters(
			'wpcw_unit_taxonomies_section_settings_fields',
			array(
				array(
					'type'  => 'heading',
					'key'   => 'unit_permalinks',
					'title' => esc_html__( 'Unit Taxonomies', 'wp-courseware' ),
					'desc'  => esc_html__( 'Below are settings related to the course unit category and tag taxonomies.', 'wp-courseware' ),
				),
				array(
					'type'     => 'text',
					'key'      => 'unit_category_base',
					'default'  => 'unit-category',
					'title'    => esc_html__( 'Unit Category Base', 'wp-courseware' ),
					'desc_tip' => esc_html__( 'Unit category base for unit category permalinks.', 'wp-courseware' ),
				),
				array(
					'type'     => 'text',
					'key'      => 'unit_tag_base',
					'default'  => 'unit-tag',
					'title'    => esc_html__( 'Unit Tag Base', 'wp-courseware' ),
					'desc_tip' => esc_html__( 'Unit tag base for unit tag permalinks.', 'wp-courseware' ),
				),
			)
		);
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

		if ( isset( $post_data['action'] ) && $post_data['action'] === 'wpcw-update-units-permalinks' && wp_verify_nonce( $post_data['nonce'], 'wpcw-units-permalinks-nonce' ) ) {
			$this->update_post_type_permalinks( $post_data );
		}

		if ( isset( $post_data['action'] ) && $post_data['action'] === 'wpcw-update-units-taxonomies' && wp_verify_nonce( $post_data['nonce'], 'wpcw-units-taxonomies-nonce' ) ) {
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

		$unit_base = isset( $post_data['unit_permalink'] ) ? wpcw_clean( wp_unslash( $post_data['unit_permalink'] ) ) : ''; // WPCS: input var ok,

		if ( 'custom' === $unit_base ) {
			if ( isset( $post_data['unit_permalink_structure'] ) ) { // WPCS: input var ok.
				$unit_base = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', trim( wp_unslash( $post_data['unit_permalink_structure'] ) ) ) ); // WPCS: input var ok, sanitization ok.
			} else {
				$unit_base = '/%module_number%';
			}

			// This is an invalid base structure and breaks pages.
			if ( '/%course_unit_category%/' === trailingslashit( $unit_base ) ) {
				$unit_base = '/' . '%module_number%' . $unit_base;
			}
		} elseif ( empty( $unit_base ) ) {
			$unit_base = '%module_number%';
		}

		// Update Unit Base.
		$permalinks['unit_base'] = wpcw_sanitize_permalink( $unit_base );

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

		$category_base = isset( $post_data['unit_category_base'] ) ? $post_data['unit_category_base'] : _x( 'unit-category', 'slug', 'wp-unitware' );
		$tag_base      = isset( $post_data['unit_tag_base'] ) ? $post_data['unit_tag_base'] : _x( 'unit-tag', 'slug', 'wp-courseware' );

		$permalinks['unit_category_base'] = wpcw_sanitize_permalink( wp_unslash( $category_base ) );
		$permalinks['unit_tag_base']      = wpcw_sanitize_permalink( wp_unslash( $tag_base ) );

		// Update Option
		update_option( 'wpcw_permalinks', $permalinks );

		// Flush Rewrite Rules Flag.
		wpcw_enable_flush_rewrite_rules_flag();

		// Flush Rewrite Rules.
		$this->flush_post_type_rewrite_rules();
	}

	/** Post Type Methods -------------------------------------------- */

	/**
	 * Register Course Units Post Type.
	 *
	 * @since 4.3.0
	 */
	public function post_type() {
		$permalinks = $this->get_permalinks();

		register_post_type(
			$this->post_type_slug,
			apply_filters(
				'wpcw_units_post_type_args',
				array(
					'labels'                => array(
						'name'               => __( 'Units', 'wp-courseware' ),
						'singular_name'      => __( 'Unit', 'wp-courseware' ),
						'all_items'          => __( 'All Units', 'wp-courseware' ),
						'new_item'           => __( 'New Unit', 'wp-courseware' ),
						'add_new'            => __( 'Add New', 'wp-courseware' ),
						'add_new_item'       => __( 'Add New Unit', 'wp-courseware' ),
						'edit_item'          => __( 'Edit Unit', 'wp-courseware' ),
						'view_item'          => __( 'View Unit', 'wp-courseware' ),
						'search_items'       => __( 'Search Units', 'wp-courseware' ),
						'not_found'          => sprintf( __( 'No units found. <a href="%s">Add a new unit</a>.', 'wp-courseware' ), admin_url( 'post-new.php?post_type=course_unit' ) ),
						'not_found_in_trash' => __( 'No units found in trash', 'wp-courseware' ),
						'parent_item_colon'  => __( 'Parent Unit:', 'wp-courseware' ),
						'menu_name'          => __( 'Units', 'wp-courseware' ),
					),
					'public'                => true,
					'publicly_queryable'    => true,
					'hierarchical'          => false,
					'show_ui'               => true,
					'show_in_nav_menus'     => false,
					'show_in_menu'          => true,
					'supports'              => array( 'title', 'editor', 'revisions' ),
					'has_archive'           => false,
					'rewrite'               => $permalinks['unit_rewrite_slug'] ? array(
						'slug'       => $permalinks['unit_rewrite_slug'],
						'with_front' => false,
					) : false,
					'query_var'             => true,
					'map_meta_cap'          => true,
					'menu_position'         => WPCW_MENU_POSITION + 1,
					'can_export'            => true,
					'show_in_rest'          => true,
					'capability_type'       => 'wpcw_course_unit',
					'rest_base'             => $this->post_type_slug,
					'rest_controller_class' => 'WP_REST_Posts_Controller',
				)
			)
		);
	}

	/**
	 * Post Type Rewrites.
	 *
	 * @since 4.4.0
	 */
	public function post_type_rewrites() {
		global $wp_rewrite;

		// Get Permalinks
		$permalinks = $this->get_permalinks();

		// Set initial permalink structure.
		$permalink_structure = '%module_number%/%course_unit%';

		// Rewrite Module.
		$module_rewrite_tag   = apply_filters( 'wpcw_units_rewrite_tag_module', '%module_number%' );
		$module_rewrite_regex = apply_filters( 'wpcw_permalinks_urlmatch_module', '(module-[^/]+)' );
		$module_rewrite_regex = apply_filters( 'wpcw_units_rewrite_regex_module', $module_rewrite_regex );
		$module_rewrite_query = apply_filters( 'wpcw_units_rewrite_query_module', 'module_number=' );
		$wp_rewrite->add_rewrite_tag( $module_rewrite_tag, $module_rewrite_regex, $module_rewrite_query );

		// Rewrite Unit.
		$unit_rewrite_tag   = apply_filters( 'wpcw_units_rewrite_tag_unit', '%course_unit%' );
		$unit_rewrite_regex = apply_filters( 'wpcw_permalinks_urlmatch_unit', '([^/]+)' );
		$unit_rewrite_regex = apply_filters( 'wpcw_units_rewrite_regex_unit', $unit_rewrite_regex );
		$unit_rewrite_query = apply_filters( 'wpcw_units_rewrite_query_unit', 'course_unit=' );
		$wp_rewrite->add_rewrite_tag( $unit_rewrite_tag, $unit_rewrite_regex, $unit_rewrite_query );

		if ( ! empty( $permalinks['unit_rewrite_slug'] ) ) {
			$unit_base_slug = ltrim( $permalinks['unit_rewrite_slug'], '/' );

			// Rewrite Course.
			if ( false !== strpos( $unit_base_slug, '%course%' ) ) {
				$course_rewrite_tag   = apply_filters( 'wpcw_units_rewrite_tag_course', '%course%' );
				$course_rewrite_regex = apply_filters( 'wpcw_permalinks_urlmatch_course', '([^/]+)' );
				$course_rewrite_regex = apply_filters( 'wpcw_units_rewrite_regex_course', $course_rewrite_regex );
				$course_rewrite_query = apply_filters( 'wpcw_units_rewrite_query_course', 'module_course=' );
				$wp_rewrite->add_rewrite_tag( $course_rewrite_tag, $course_rewrite_regex, $course_rewrite_query );
			}
		}
	}

	/**
	 * Register Course Unit Comments Support.
	 *
	 * This will allow comments to be supported on units
	 * if the setting is set otherwise it will remove support.
	 *
	 * @since 4.3.0
	 */
	public function post_type_comments_support() {
		$unit_comments_setting = wpcw_get_setting( 'enable_unit_comments' );

		if ( 'enable_comments' === $unit_comments_setting || 'yes' === $unit_comments_setting ) {
			add_post_type_support( 'course_unit', 'comments' );
		} else {
			remove_post_type_support( 'course_unit', 'comments' );
		}
	}

	/**
	 * Register Post Type Course Unit Updated Messages.
	 *
	 * @since 4.3.0
	 *
	 * @param array $messages The updated messages.
	 *
	 * @return array $messages The updated messages.
	 */
	public function post_type_updated_messages( $messages ) {
		global $post;

		$permalink = get_permalink( $post );

		$messages[ $this->post_type_slug ] = apply_filters(
			'wpcw_units_post_type_updated_messages',
			array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Unit updated. <a target="_blank" href="%s">View Unit</a>', 'wp-courseware' ), esc_url( $permalink ) ),
				2  => __( 'Custom field updated.', 'wp-courseware' ),
				3  => __( 'Custom field deleted.', 'wp-courseware' ),
				4  => __( 'Unit updated.', 'wp-courseware' ),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Unit restored to revision from %s', 'wp-courseware' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => sprintf( __( 'Unit published. <a href="%s">View Unit</a>', 'wp-courseware' ), esc_url( $permalink ) ),
				7  => __( 'Unit saved.', 'wp-courseware' ),
				8  => sprintf( __( 'Unit submitted. <a target="_blank" href="%s">Preview Unit</a>', 'wp-courseware' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
				9  => sprintf(
					__( 'Unit scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Unit</a>', 'wp-courseware' ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ),
					esc_url( $permalink )
				),
				10 => sprintf( __( 'Unit draft updated. <a target="_blank" href="%s">Preview Unit</a>', 'wp-courseware' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
			)
		);

		return $messages;
	}

	/**
	 * Exclude Post Type Course Units from Search.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_Query $query The current query.
	 */
	public function post_type_exclude_from_search( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Current Post Type.
		$current_post_type = $query->get( 'post_type' );

		// Check for post type.
		if ( $current_post_type && 'course_unit' !== $current_post_type ) {
			return;
		}

		/**
		 * Filter: Units Exclude From Home Query.
		 *
		 * @since 4.4.2
		 *
		 * @param bool True if excluded, False otherwise.
		 *
		 * @return bool True if you want to exclude units from home query.
		 */
		if ( $query->is_home() && apply_filters( 'wpcw_units_exclude_from_home_query', true ) ) {
			$query->set( 'post_type', '' );
		}

		/**
		 * Filter: Units Exclude From Search
		 *
		 * @since 4.3.0
		 *
		 * @param bool True if excluded, False otherwise.
		 *
		 * @return bool True if you want to exclude units from search.
		 */
		if ( $query->is_search() && apply_filters( 'wpcw_units_exclude_from_search', true ) ) {
			$post_type_to_remove   = $this->post_type_slug;
			$searchable_post_types = get_post_types( array( 'exclude_from_search' => false ) );
			if ( is_array( $searchable_post_types ) && in_array( $post_type_to_remove, $searchable_post_types ) ) {
				unset( $searchable_post_types[ $post_type_to_remove ] );
				$query->set( 'post_type', $searchable_post_types );
			}
		}
	}

	/**
	 * Remove Unit Content on Post Type Archives.
	 *
	 * @since 4.3.0
	 */
	public function post_type_remove_content( $content ) {
		global $post;

		if ( ! is_main_query() || ! is_a( $post, 'WP_Post' ) || ( $this->post_type_slug !== $post->post_type ) ) {
			return $content;
		}

		// Divi Builder Check.
		if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
			return $content;
		}

		// Is Unit Taxonomy.
		$is_taxonomy = ( is_archive() && ( is_tax( $this->taxonomy_tag_slug ) || is_tax( $this->taxonomy_category_slug ) ) );

		// Is Unit Single.
		$is_single = is_singular( $this->post_type_slug );

		// No Access Message.
		$message = WPCW_UnitFrontend::message_createMessage_error( esc_html__( 'Sorry, but you\'re not allowed to access this unit.', 'wp-courseware' ) );

		/**
		 * Filter: Units Enable Taxonomy Archive Content.
		 *
		 * @since 4.3.0
		 *
		 * @param bool True to enable unit content to be displayed on archive pages. Default is False.
		 *
		 * @return bool The boolean paramater to enable or disable content on archive pages.
		 */
		if ( $is_taxonomy && apply_filters( 'wpcw_units_enable_taxonomy_archive_content', false ) ) {
			return $content;
		}

		if ( $is_taxonomy && ! wpcw_can_student_access_unit( get_the_ID() ) ) {
			/**
			 * Filter: Units Taxonomy Archive Content - No Access Message.
			 *
			 * @since 4.3.0
			 *
			 * @param string $message The no access message.
			 *
			 * @return string $message The no access message.
			 */
			$content = apply_filters( 'wpcw_units_taxonomy_archive_content_no_access_message', $message );
		}

		/**
		 * Filter: Units Enable Single Content.
		 *
		 * @since 4.5.1
		 *
		 * @param bool True to enable unit content to be displayed on single pages. Default is False.
		 *
		 * @return bool The boolean paramater to enable or disable content on single pages.
		 */
		if ( $is_single && apply_filters( 'wpcw_units_enable_single_content', false ) ) {
			return $content;
		}

		// Single Content.
		if ( $is_single && ! wpcw_can_student_access_unit( get_the_ID() ) ) {
			/**
			 * Filter: Units Single Content - No Access Message.
			 *
			 * @since 4.5.1
			 *
			 * @param string $message The no access message.
			 *
			 * @return string $message The no access message.
			 */
			$content = apply_filters( 'wpcw_units_single_content_no_access_message', $message );
		}

		return $content;
	}

	/**
	 * Post Type Add Unit Fields
	 *
	 * @since 4.4.0
	 *
	 * @param array    $fields The query fields.
	 * @param WP_Query $wp_query The wp query being run.
	 *
	 * @return array $fields The query fields.
	 */
	public function post_type_add_unit_fields( $fields, $wp_query ) {
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

			$course_fields = array_map(
				function ( $column ) use ( $table_name ) {
					return "{$table_name}.{$column}";
				},
				$table_columns
			);

			if ( ! empty( $course_fields ) ) {
				$additional_fields = "{$wpdb->posts}.post_author, {$wpdb->posts}.post_status";

				$fields .= ", {$additional_fields}, " . implode( ', ', $course_fields );
			}
		}

		return $fields;
	}

	/**
	 * Join Post Type with Units Table.
	 *
	 * @since 4.4.0
	 *
	 * @param string    $clause The where clause.
	 * @param \WP_Query $wp_query The wp query being run.
	 */
	public function post_type_join_units_table( $clause, $wp_query ) {
		global $wpdb, $wpcwdb;

		$join_clause = '';

		// Post Type.
		if ( wpcw_is_post_type_query( $wp_query, $this->post_type_slug ) ) {
			$join_clause = " LEFT JOIN {$wpcwdb->units_meta} ON {$wpdb->posts}.ID = {$wpcwdb->units_meta}.unit_id";
		}

		// Taxonomy - Category
		if ( wpcw_is_taxonomy_query( $wp_query, $this->taxonomy_category_slug ) ) {
			$join_clause = " LEFT JOIN {$wpcwdb->units_meta} ON {$wpdb->posts}.ID = {$wpcwdb->units_meta}.unit_id";
		}

		// Taxonomy - Tag
		if ( wpcw_is_taxonomy_query( $wp_query, $this->taxonomy_tag_slug ) ) {
			$join_clause = " LEFT JOIN {$wpcwdb->units_meta} ON {$wpdb->posts}.ID = {$wpcwdb->units_meta}.unit_id";
		}

		if ( ! empty( $join_clause ) ) {
			$clause .= $join_clause;
		}

		return $clause;
	}

	/**
	 * Filter Unit Post Type Links.
	 *
	 * Used to replace %module_number% and %course_unit%.
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

		/**
		 * Filter: Unit Permalinks Module Unassigned Slug.
		 *
		 * @since 4.4.0
		 *
		 * @param string The unit unasigned module slug. Default is 'module-unassigned'
		 * @param WP_Post $post The WP_Post object.
		 *
		 * @return string The unit unassigned module slug.
		 */
		$module_number = apply_filters( 'wpcw_permalinks_urlgen_module_unassigned', 'module-unassigned', $post );
		$module_number = apply_filters( 'wpcw_unit_permalinks_module_unassigned_slug', $module_number, $post );

		/**
		 * Filter: Unit Permalinks Course Unassigned Slug.
		 *
		 * @since 4.4.0
		 *
		 * @param string The unit unasigned course slug. Default is 'course-unassigned'
		 * @param WP_Post $post The WP_Post object.
		 *
		 * @return string The unit unassigned course slug.
		 */
		$course_slug = apply_filters( 'wpcw_unit_permalinks_course_unassigned_slug', 'course-unassigned', $post );

		// Check for Module Number.
		if ( $module_id = get_post_meta( $post->ID, 'wpcw_associated_module', true ) ) {
			$module = $this->get_module( absint( $module_id ) );

			// Check if exists.
			if ( $module->exists() ) {
				/**
				 * Filter: Unit Module Slug.
				 *
				 * @since 4.4.0
				 *
				 * @param string The unit module slug.
				 * @param WP_Post $post The WP_Post object
				 * @param Module  $module The module object.
				 *
				 * @return string The unit module slug.
				 */
				$module_number = sprintf( apply_filters( 'wpcw_permalinks_urlgen_module', 'module-%d', $post, $module ), $module->get_module_number() );
				$module_number = apply_filters( 'wpcw_unit_permalinks_module_slug', $module_number, $post, $module );

				// Get Module Course.
				$course = $module->get_course();

				// Check if exists.
				if ( $course->exists() ) {
					/**
					 * Filter: Unit Course Slug.
					 *
					 * @since 4.4.0
					 *
					 * @param string The unit module slug.
					 * @param WP_Post $post The WP_Post object
					 * @param Module  $module The module object.
					 *
					 * @return string The unit module slug.
					 */
					$course_slug = apply_filters( 'wpcw_unit_permalinks_course_slug', $course->get_course_slug(), $post, $module );
				}
			}
		}

		$find = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			'%post_id%',
			'%course%',
			'%module_number%',
		);

		$replace = array(
			date_i18n( 'Y', strtotime( $post->post_date ) ),
			date_i18n( 'm', strtotime( $post->post_date ) ),
			date_i18n( 'd', strtotime( $post->post_date ) ),
			date_i18n( 'H', strtotime( $post->post_date ) ),
			date_i18n( 'i', strtotime( $post->post_date ) ),
			date_i18n( 's', strtotime( $post->post_date ) ),
			$post->ID,
			$course_slug,
			$module_number,
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

	/**
	 * Post Type Remove Rest Api Support.
	 *
	 * Removes the REST API if the user is not logged in.
	 *
	 * @since 4.5.1
	 *
	 * @param array  $args The post type args.
	 * @param string $post_type The post type slug.
	 */
	public function post_type_remove_rest_api_support( $args, $post_type ) {
		$post_type_enabled = apply_filters( 'wpcw_units_rest_api_enabled', is_user_logged_in() );
		$show_in_rest      = apply_filters( 'wpcw_course_unit_show_in_rest', false );

		if ( $this->post_type_slug === $post_type && ! $post_type_enabled && ! $show_in_rest ) {
			$args['show_in_rest'] = false;
		}

		return $args;
	}

	/** Taxonomy Methods -------------------------------------------- */

	/**
	 * Register Course Unit Category.
	 *
	 * @since 4.3.0
	 */
	public function taxonomy_category() {
		$permalinks = $this->get_permalinks();

		register_taxonomy(
			$this->taxonomy_category_slug,
			array( $this->post_type_slug ),
			apply_filters(
				'wpcw_units_category_args',
				array(
					'hierarchical'          => true,
					'public'                => true,
					'show_in_nav_menus'     => true,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'query_var'             => true,
					'rewrite'               => array(
						'slug'         => $permalinks['unit_category_rewrite_slug'],
						'with_front'   => false,
						'hierarchical' => true,
					),
					'capabilities'          => array(
						'manage_terms' => 'edit_posts',
						'edit_terms'   => 'edit_posts',
						'delete_terms' => 'edit_posts',
						'assign_terms' => 'edit_posts',
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
				)
			)
		);
	}

	/**
	 * Register Course Unit Tag.
	 *
	 * @since 4.3.0
	 */
	public function taxonomy_tag() {
		$permalinks = $this->get_permalinks();

		register_taxonomy(
			$this->taxonomy_tag_slug,
			array( $this->post_type_slug ),
			apply_filters(
				'wpcw_units_tag_args',
				array(
					'hierarchical'          => false,
					'public'                => true,
					'show_in_nav_menus'     => true,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'query_var'             => true,
					'rewrite'               => array(
						'slug'       => $permalinks['unit_tag_rewrite_slug'],
						'with_front' => false,
					),
					'capabilities'          => array(
						'manage_terms' => 'edit_posts',
						'edit_terms'   => 'edit_posts',
						'delete_terms' => 'edit_posts',
						'assign_terms' => 'edit_posts',
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
				)
			)
		);
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
		$submenu_file = esc_url( add_query_arg( array( 'post_type' => 'course_unit' ), 'edit.php' ) );
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
				} )
			} )( jQuery );
		</script>
		<?php

	}

	/**
	 * Add Icon to Taxonomy Title.
	 *
	 * @since 4.3.0
	 */
	public function taxonomy_title_icon() {
		global $current_screen;

		if ( empty( $current_screen->taxonomy ) || ! in_array( $current_screen->taxonomy, array( $this->taxonomy_tag_slug, $this->taxonomy_category_slug ) ) ) {
			return;
		}

		echo '<style type="text/css">
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
	 * @since 4.3.0
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
	 * @since 4.3.0
	 *
	 * @return array $action_buttons The action buttons that will go on the taxonomy pages.
	 */
	public function get_taxonomy_category_action_buttons() {
		$action_buttons = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => $this->post_type_slug ), admin_url( 'edit.php' ) ),
			esc_html__( 'Back to Units', 'wp-courseware' )
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
	 * @since 4.3.0
	 *
	 * @return string $action_buttons The action buttons that will go on the taxonomy tag page.
	 */
	public function get_taxonomy_tag_action_buttons() {
		$action_buttons = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => $this->post_type_slug ), admin_url( 'edit.php' ) ),
			esc_html__( 'Back to Units', 'wp-courseware' )
		);

		$action_buttons .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'taxonomy' => $this->taxonomy_category_slug ), admin_url( 'edit-tags.php' ) ),
			esc_html__( 'View Categories', 'wp-courseware' )
		);

		return $action_buttons;
	}

	/** Metabox Methods ----------------------------------------------------- */

	/**
	 * Unit Meta Boxes.
	 *
	 * @since 4.5.1
	 */
	public function meta_boxes() {
		// Posts - Shows the conversion metabox to convert the post type.
		add_meta_box(
			'wpcw_units_convert_post',
			esc_html__( 'Convert Post to Course Unit', 'wp-courseware' ),
			array( $this, 'meta_box_post_type_to_unit_conversion' ),
			'post',
			'side',
			'low'
		);

		// Pages - Shows the conversion metabox to convert the post type.
		add_meta_box(
			'wpcw_units_convert_post',
			esc_html__( 'Convert Page to Course Unit', 'wp-courseware' ),
			array( $this, 'meta_box_post_type_to_unit_conversion' ),
			'page',
			'side',
			'low'
		);

		add_meta_box(
			'wpcw_restrict_access',
			esc_html__( 'Restrict access to members of course(s)', 'wp-courseware' ),
			array( $this, 'meta_box_restrict_access' ),
			array( 'page', 'post' ),
			'side',
			'high'
		);

		// Course Units - Template Selection.
		add_meta_box(
			'wpcw_units_choose_template',
			esc_html__( 'Unit Template', 'wp-courseware' ),
			array( $this, 'meta_box_template_selection' ),
			'course_unit',
			'side',
			'default'
		);

		// Course Units - Content Dripfeed options.
		add_meta_box(
			'wpcw_units_contentfeed_options',
			esc_html__( 'Unit Content Drip', 'wp-courseware' ),
			array( $this, 'meta_box_content_drip_selection' ),
			'course_unit',
			'side',
			'default'
		);

		// Course Teaser
		add_meta_box(
			'wpcw_units_access',
			esc_html__( 'Unit Teaser / Preview', 'wp-courseware' ),
			array( $this, 'meta_box_teaser' ),
			'course_unit',
			'side',
			'default'
		);
	}

	/**
	 * Meta Box: Post Type => Unit Conversion.
	 *
	 * @since 4.5.1
	 *
	 * @param WP_Post $post The post object.
	 */
	public function meta_box_post_type_to_unit_conversion( $post ) {
		/* translators: %1$s - Conversion Url, %2$s - Post Type Label */
		printf(
			__( '<div class="wpcw-convert-unit-button"><a href="%1$s" class="button-primary"><i class="wpcw-fas wpcw-fa-exchange-alt left"></i> Convert %2$s to Unit</a></div>', 'wp-courseware' ),
			add_query_arg(
				array(
					'page'   => 'wpcw-unit-converter',
					'postid' => $post->ID,
				),
				'admin.php'
			),
			ucfirst( get_post_type( $post ) )
		);
	}

	public function meta_box_restrict_access( $post ) {
		$courses = wpcw()->courses->get_courses(
			array(
				'orderby' => 'course_title',
				'order'   => 'ASC',
				'number'  => -1,
				'fields'  => array(
					'course_id',
					'course_title',
				),
			),
			true
		);

		$restricted_options = ( isset( $post ) && isset( $post->ID ) ) ? get_post_meta( $post->ID, 'wpcw_course_restriction', true ) : array();
		if ( ! is_array( $restricted_options ) ) {
			$restricted_options = array( $restricted_options );
		}

		if ( isset( $courses ) && is_array( $courses ) ) {
			foreach ( $courses as $course ) {
				$checked = in_array( $course->course_id, $restricted_options ) ? " checked='checked'" : '';

				echo "<div class='wpcw_checkbox_container'><input class='wpcw_course_restriction' type='checkbox' name='wpcw_course_restriction[]' value='" . $course->course_id . "' " . $checked . '>&nbsp;' . $course->course_title . "</div>\n";

			}
		}

	}

	/**
	 * Meta Box: Unit Template Selection.
	 *
	 * @since 4.5.1
	 *
	 * @param WP_Post $post The post object.
	 */
	public function meta_box_template_selection( $post ) {
		echo '<div class="wpcw-unit-metabox-template-selection">';

		// Output description.
		printf( '<p>%s</p>', __( 'Here you can choose which template to use for this unit.', 'wp-courseware' ) );

		// Get a list of all templates
		$theme = wp_get_theme();

		// N.B. No caching, even though core Page Templates has that.
		// Nacin advises:
		// "ultimately, "caching" for page templates is not very helpful"
		// "by default, the themes bucket is non-persistent. also, calling
		// get_page_templates() no longer requires us to load up all theme
		// data for all themes so overall, it's much quicker already."
		$post_templates = array( 'no-template.php' => '--- ' . __( 'Use default template', 'wp-courseware' ) . ' ---' );

		// Get a list of all PHP files in the theme, so that we can check for theme headers.
		// Allow the search to go into 1 level of folders.
		$file_list = (array) $theme->get_files( 'php', 2 );
		foreach ( $file_list as $file_name => $file_path ) {
			// Progressively check the headers for each file. The header is called 'Unit Template Name'.
			// e.g.
			//
			// Unit Template Name: Your Custom Template
			//
			$headers = get_file_data(
				$file_path,
				array(
					'unit_template_name' => 'Unit Template Name',
				)
			);

			// No header found.
			if ( empty( $headers['unit_template_name'] ) ) {
				continue;
			}

			// We got one!
			$post_templates[ $file_name ] = $headers['unit_template_name'];
		}

		// Show form with selected template that the user can choose from.
		$selectedTemplate = get_post_meta( $post->ID, WPCW_TEMPLATE_META_ID, true );

		// Create Dropdown.
		echo WPCW_forms_createDropdown( 'wpcw_units_choose_template_list', $post_templates, $selectedTemplate );

		echo '</div>';
	}

	/**
	 * Meta Box: Unit Content Drip Selection
	 *
	 * @since 4.5.1
	 *
	 * @param WP_Post $post The post object.
	 */
	public function meta_box_content_drip_selection( $post ) {
		echo '<div class="wpcw-unit-metabox-content-drip-selection">';

		// Delay Options.
		$delay_options = array(
			''              => '--- ' . __( 'No Delay', 'wp-courseware' ) . ' ---',
			'drip_specific' => __( 'On a specific date', 'wp-courseware' ),
			'drip_interval' => __( 'A specific interval after the course start date', 'wp-courseware' ),
		);

		// Interval Options.
		$interval_options = array(
			'interval_hours'  => __( 'Hour(s)', 'wp-courseware' ),
			'interval_days'   => __( 'Days(s)', 'wp-courseware' ),
			'interval_weeks'  => __( 'Weeks(s)', 'wp-courseware' ),
			'interval_months' => __( 'Months(s)', 'wp-courseware' ),
			'interval_years'  => __( 'Years(s)', 'wp-courseware' ),
		);

		// Load up the settings that we've got for this unit in the database.
		$unit_meta = WPCW_units_getUnitMetaData( $post->ID );

		// Convert the date we've got a valid timestamp for the date. We've already converted
		// the data into a timestamp from the meta when calling WPCW_units_getUnitMetaData above.
		$convert_date_visible = false;
		$convert_date_hidden  = false;

		// Got a specific date, so use it. Set to 1, as epoch is 1970-01-01 00:00:01, therefore
		// we'll want this to be at least 1 second since then. Since this date should be in the future
		// this is a completely fair assumption.
		if ( ! empty( $unit_meta->unit_drip_date_ts ) && $unit_meta->unit_drip_date_ts > 1 ) {
			$convert_date_visible = date_i18n( 'j M Y H:i:s', $unit_meta->unit_drip_date_ts );
			$convert_date_hidden  = date_i18n( 'Y-m-d H:i:s', $unit_meta->unit_drip_date_ts );
		} else { // Not got a specific date, we've not specified it. So set to today.
			$convert_date_visible = date_i18n( 'j M Y H:i:00' );
			$convert_date_hidden  = date_i18n( 'Y-m-d H:i:00' );
		}

		// Calculate the interval from a timestamp interval into a human-readable number
		$interval_type   = ! empty( $unit_meta->unit_drip_interval_type ) ? $unit_meta->unit_drip_interval_type : 'interval_days';
		$drip_interval   = ! empty( $unit_meta->unit_drip_interval ) ? $unit_meta->unit_drip_interval : 432000;
		$drip_type       = ! empty( $unit_meta->unit_drip_type ) ? $unit_meta->unit_drip_type : '';
		$interval_number = 0;
		switch ( $interval_type ) {
			case 'interval_hours':
				$interval_number = $drip_interval / WPCW_TIME_HR_IN_SECS;
				break;

			case 'interval_days':
				$interval_number = $drip_interval / WPCW_TIME_DAY_IN_SECS;
				break;

			case 'interval_weeks':
				$interval_number = $drip_interval / WPCW_TIME_WEEK_IN_SECS;
				break;

			case 'interval_months':
				$interval_number = $drip_interval / WPCW_TIME_MONTH_IN_SECS;
				break;

			case 'interval_years':
				$interval_number = $drip_interval / WPCW_TIME_YEAR_IN_SECS;
				break;
		}

		// 1 - The drip type
		printf( '<div class="wpcw_units_drip_section">' );
		printf( '<label for="wpcw_units_drip_type">%s</label>', __( 'When should this unit become available?', 'wp-courseware' ) );
		echo WPCW_forms_createDropdown( 'wpcw_units_drip_type', $delay_options, $drip_type, 'wpcw_units_drip_type' );
		printf( '</div>' );

		// 2 - For a specific date, show a date time picker
		printf( '<div class="wpcw_units_drip_section wpcw_units_drip_date_section wpcw_datepicker_wrapper" id="wpcw_units_drip_type-drip_specific">' );
		printf( '<label for="wpcw_units_drip_date">%s</label>', __( 'Select the date on which this unit should become available...', 'wp-courseware' ) );
		printf( '<input type="text" id="wpcw_units_drip_date_vis" name="wpcw_units_drip_date_vis" class="wpcw_datepicker_vis" value="%s" />', $convert_date_visible );
		printf( '<input type="hidden" id="wpcw_units_drip_date" name="wpcw_units_drip_date" class="wpcw_datepicker_nonvis" value="%s" />', $convert_date_hidden );
		printf( '</div>' );

		// 3 - For a relative date, show a digit and a number of days/weeks
		printf( '<div class="wpcw_units_drip_section wpcw_units_drip_interval_section" id="wpcw_units_drip_type-drip_interval">' );
		printf( '<label for="wpcw_units_drip_interval">%s</label>', __( 'How long after the user is enrolled should this unit become available?', 'wp-courseware' ) );
		printf( '<input type="text" id="wpcw_units_drip_interval" name="wpcw_units_drip_interval" class="wpcw-number" value="%s" />', $interval_number );
		echo WPCW_forms_createDropdown( 'wpcw_units_drip_interval_type', $interval_options, $interval_type, 'wpcw_units_drip_interval_type' );
		printf( '</div>' );

		echo '</div>';
	}

	/**
	 * Meta Box: Unit Teaser
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Post $post The post object.
	 */
	public function meta_box_teaser( $post ) {
		$unit_meta = WPCW_units_getUnitMetaData( $post->ID );
		?>
		<div class="wpcw-unit-metabox-teaser">
			<label for="wpcw_unit_teaser">
				<input name="wpcw_unit_teaser" type="checkbox" id="wpcw_unit_teaser" class="wpcw-unit-checkbox" value="1" <?php checked( absint( $unit_meta->unit_teaser ), 1, true ); ?>>
				<span class="wpcw-unit-checkbox-label"><?php esc_html_e( 'Teaser / Preview Unit', 'wp-courseware' ); ?></span>
			</label>
			<p><?php _e( 'Check the box above to allow this Unit to be accessed as a <strong>Teaser</strong> or <strong>Free</strong> Unit.', 'wp-courseware' ); ?></p>
		</div>
		<?php
	}

	/** Getter Methods ----------------------------------------------------- */

	/**
	 * Get Module.
	 *
	 * @since 4.4.0
	 *
	 * @param int  $id The module id.
	 * @param bool $force True if needs refreshed. Default is false.
	 *
	 * @return Module The module object.
	 */
	protected function get_module( $id, $force = false ) {
		$refresh_module = false;

		if ( empty( $this->module ) ) {
			$refresh_module = true;
		}

		if ( ! empty( $this->module ) && $this->module instanceof Module && $id !== $this->module->get_id() ) {
			$refresh_module = true;
		}

		if ( $refresh_module || $force ) {
			$this->module = new Module( absint( $id ) );
		}

		return $this->module;
	}

	/**
	 * Get Units.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Return the raw db data.
	 *
	 * @return array Array of unit objects.
	 */
	public function get_units( $args = array(), $raw = false ) {
		$units   = array();
		$results = $this->db->get_units( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$units[] = new Unit( $result );
		}

		return $units;
	}

	/**
	 * Get Number of Units.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of units.
	 */
	public function get_units_count( $args = array() ) {
		return $this->db->get_units( $args, true );
	}

	/**
	 * Get Units Filter By Dropdown.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args The dropdown args. Can be used to customize the dropdown.
	 */
	public function get_units_filter_by_dropdown( array $args = array() ) {
		$defaults = array(
			'label'       => false,
			'placeholder' => esc_html__( 'All Units', 'wp-courseware' ),
			'name'        => 'unit_id',
			'classes'     => array(),
			'selected'    => isset( $_GET['unit_id'] ) ? absint( $_GET['unit_id'] ) : 0,
			'orderby'     => 'post_title',
		);

		$args = wp_parse_args( $args, $defaults );

		$form = '';

		// Check if admin
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$args['post_author'] = get_current_user_id();
		}

		$units = new WP_Query(
			array(
				'post_type'              => 'course_unit',
				'posts_per_page'         => 1000,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'no_found_rows'          => true,
			)
		);

		if ( $units->have_posts() ) {
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

			while ( $units->have_posts() ) {
				$units->the_post();
				$selected = ( get_the_ID() === absint( $args['selected'] ) ) ? ' selected="selected"' : '';

				$form .= sprintf( '<option value="%s" %s>%s</option>', get_the_ID(), $selected, get_the_title() );
			}
			wp_reset_postdata();
			wp_reset_query();

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
	public function get_units_filter_dropdown() {
		$unit_id = isset( $_GET['unit_id'] ) ? absint( $_GET['unit_id'] ) : 0;

		ob_start();

		printf( '<span class="wpcw-filter-wrapper">' );
		printf( '<select id="wpcw-units-filter" class="select-field-wpcwselect2-filter" name="unit_id" data-placeholder="%s">', esc_html__( 'All Units', 'wp-courseware' ) );

		if ( $unit_id ) {
			printf( '<option value="%s">%s</option>', $unit_id, get_the_title( $unit_id ) );
		}

		printf( '</select>' );
		printf( '</span>' );

		return ob_get_clean();
	}

	/** Drip Feed Methods -------------------------------------------------- */

	/**
	 * Get Drip Feed Intervals.
	 *
	 * @since 4.1.0
	 *
	 * @return array The drip feed intervals.
	 */
	public function get_drip_feed_intervals() {
		return apply_filters(
			'wpcw_units_drip_feed_intervals',
			array(
				'never'      => esc_html__( 'Never check', 'wp-courseware' ),
				'hourly'     => esc_html__( 'Every hour', 'wp-courseware' ),
				'twicedaily' => esc_html__( 'Twice a day', 'wp-courseware' ),
				'daily'      => esc_html__( 'Daily', 'wp-courseware' ),
			)
		);
	}

	/**
	 * Update Drip Feed Cron Interval.
	 *
	 * @since 4.1.0
	 *
	 * @param array $settings The settings array.
	 */
	public function update_drip_feed_cron( $settings ) {
		wpcw()->settings->set_settings( $settings );

		$drip_feed = wpcw_get_setting( 'cron_time_dripfeed' );

		WPCW_queue_dripfeed::installNotificationHook_dripfeed( $drip_feed );
	}

	/** Unit Ordering --------------------------------------------------------- */

	/**
	 * Update Unit Ordering.
	 *
	 * @since 4.4.0
	 *
	 * @param int $course_id The course id.
	 * @param int $module_id The module id.
	 */
	public function update_unit_ordering( $course_id = 0, $module_id = 0 ) {
		if ( empty( $course_id ) ) {
			return;
		}

		// Get Modules
		$modules = wpcw_get_modules(
			array(
				'number'    => - 1,
				'course_id' => absint( $course_id ),
				'orderby'   => 'module_order',
				'order'     => 'ASC',
			)
		);

		if ( empty( $modules ) ) {
			return;
		}

		// Overall Unit Order.
		$unit_order = 0;

		/** @var Module $module */
		foreach ( $modules as $module ) {
			// Unit Number within the module.
			$unit_number = 0;

			// Get Units
			$units = $module->get_units(
				array(
					'number'  => - 1,
					'orderby' => 'unit_number',
					'order'   => 'ASC',
				)
			);

			if ( empty( $units ) ) {
				continue;
			}

			/** @var Unit $unit */
			foreach ( $units as $unit ) {
				$unit_order += 10;
				$unit_number ++;
				$unit->set_prop( 'unit_order', $unit_order );
				$unit->set_prop( 'unit_number', $unit_number );
				$unit->save( false );
			}
		}
	}

	/**
	 * Update Unit Total and Progress.
	 *
	 * @since 4.5.1
	 *
	 * @param int $course_id The course id.
	 * @param int $module_id The module id.
	 */
	public function update_unit_total_and_progress( $course_id = 0, $module_id = 0 ) {
		if ( empty( $course_id ) ) {
			return;
		}

		// Globals.
		global $wpdb, $wpcwdb;

		// Get Unit Count.
		$unit_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
    	 	 FROM $wpcwdb->units_meta
    	 	 WHERE parent_course_id = %d",
				$course_id
			)
		);

		// Update Course Unit Count.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpcwdb->courses
    	 	 SET course_unit_count = %d
    	 	 WHERE course_id = %d",
				$unit_count,
				$course_id
			)
		);

		// User progress counts will now be out of sync too, particularly with new or deleted units.
		$users = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpcwdb->user_courses WHERE course_id = %d", $course_id ) );

		// Update User Unit Progress.
		if ( $users ) {
			foreach ( $users as $users_course ) {
				WPCW_users_updateUserUnitProgress( $users_course->course_id, $users_course->user_id, $unit_count );
			}
		}
	}

	/** API Endpoint Methods -------------------------------------------------- */

	/**
	 * Register Units Api Endpoints.
	 *
	 * @since 4.3.0
	 *
	 * @param array              $endpoints The endpoints to filter.
	 * @param Api The api object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array(
			'endpoint' => 'units',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_units' ),
		);
		$endpoints[] = array(
			'endpoint' => 'units-filtered',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_units_filtered' ),
		);

		return $endpoints;
	}

	/**
	 * Api: Get Units.
	 *
	 * @since 4.3.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_units( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$order  = $request->get_param( 'order' );
		$author = $request->get_param( 'author' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = 10000;
		}

		if ( ! $order ) {
			$order = 'DESC';
		}

		if ( ! $author ) {
			$author = '';
		}

		$results = array();
		$args    = array(
			'number'      => $number,
			'search'      => $search,
			'order'       => $order,
			'unit_author' => $author,
		);

		$units = $this->get_units( $args );

		if ( $units ) {
			/** @var Unit $unit */
			foreach ( $units as $unit ) {
				$results[] = array(
					'id'    => $unit->get_unit_id(),
					'title' => html_entity_decode( $unit->get_unit_title(), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				);
			}
		}

		return rest_ensure_response( array( 'units' => $results ) );
	}

	/**
	 * Api: Get Units Fifltered.
	 *
	 * @since 4.3.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_units_filtered( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );
		$order  = $request->get_param( 'order' );
		$author = $request->get_param( 'author' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = 1000;
		}

		if ( ! $order ) {
			$order = 'DESC';
		}

		$results = array();
		$args    = array(
			'number' => $number,
			'search' => $search,
			'order'  => $order,
		);

		if ( is_user_logged_in() && ! $author && ! current_user_can( 'manage_wpcw_settings' ) ) {
			$args['unit_author'] = get_current_user_id();
		}

		$units = $this->get_units( $args );

		if ( $units ) {
			/** @var Unit $unit */
			foreach ( $units as $unit ) {
				$results[] = array(
					'id'    => $unit->get_unit_id(),
					'title' => html_entity_decode( $unit->get_unit_title(), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				);
			}
		}

		return rest_ensure_response( array( 'units' => $results ) );
	}

	/** Utility Methods ----------------------------------------------------- */

	/**
	 * Delete Orphaned Units.
	 *
	 * @since 4.5.1
	 */
	public function delete_orphaned_units() {
		global $wpdb, $wpcwdb;

		try {
			$ounits  = array();
			$results = $wpdb->get_results( "SELECT um.unit_id FROM {$wpcwdb->units_meta} um WHERE NOT EXISTS( SELECT 1 FROM {$wpdb->posts} p WHERE um.unit_id = p.ID )" );

			if ( $results ) {
				foreach ( $results as $result ) {
					$ounits[] = $result->unit_id;
				}

				if ( ! empty( $ounits ) ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpcwdb->units_meta} WHERE unit_id IN (%s)", implode( ',', array_map( 'intval', $ounits ) ) ) );
				}
			}
		} catch ( Exception $exception ) {
			$this->log( $exception->getMessage(), false );

			return false;
		}

		return true;
	}

	/**
	 * Unit Template Actions.
	 *
	 * @since 4.9.0
	 */
	public function unit_template_actions() {
		if ( is_singular( array( wpcw()->units->post_type_slug ) ) ) {
			if ( class_exists( 'TCB_Post' ) && isset( $_GET['tve'] ) && $_GET['tve'] === 'true' && ! is_admin() ) {
				return;
			}
			add_filter( 'the_content', array( $this, 'single_content' ), 10 );

		}
	}

	/**
	 * Unit Single Content.
	 *
	 * @since 4.9.0
	 *
	 * @param string $content The post content.
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
		wpcw_get_template( 'units/single-content.php' );
		$content = ob_get_clean();
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', array( $this, 'single_content' ), 10 );

		return $content;

	}

	/**
	 * Check if user is admin or teacher.
	 *
	 * @since 4.9.0
	 */
	public function wpcw_post_password_required( $returned, $post ) {
		global $post;
		$fe = new WPCW_UnitFrontend( $post );
		if ( $fe->check_is_admin_or_teacher() ) {
			$returned = false;
		}

		return $returned;
	}

	/**
	 * Render unit template for Divi theme builder.
	 *
	 * @since 4.9.0
	 */
	public function wpcw_th_render_unit_template( $layout_id, $layout_enabled, $template_id ) {
		if ( ! is_main_query() ) {
			return;
		}
		if ( isset( $layout_id ) && isset( $template_id ) && $layout_enabled ) {
			// Remove the filter we're in to avoid nested calls.
			if ( is_singular( 'course_unit' ) ) {
				wpcw_get_template( 'units/single-content.php' );
			}
		}

	}

	/**
	 * Render unit template for Beaver Builder Themer (theme builder).
	 *
	 * @since 4.8.0
	 */

	public function wpcw_bb_render_unit_template( $content ) {

		if ( is_singular( 'course_unit' ) ) {
			global $post;
			$flag = get_post_meta( $post->ID, '_fl_builder_enabled', true );
			remove_filter( 'fl_builder_do_render_content', '__return_false' );
			if ( ! isset( $flag ) || empty( $flag ) ) {
				return $content;
			}
			global $wpdb;
			$data       = array(
				'location' => 'post:course_unit',
				'object'   => "post:course_unit:$post->ID",
			);
			$location   = esc_sql( $data['location'] );
			$meta_query = "pm.meta_value LIKE '%\"{$location}\"%' OR pm.meta_value LIKE '%\"general:site\"%'";
			$query      = "SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} as pm
					   INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
					   WHERE pm.meta_key = '_fl_theme_builder_locations'
					   AND p.post_type = 'fl-theme-layout'
					   AND p.post_status = 'publish'";

			if ( $data['object'] ) {
				$object      = esc_sql( $data['object'] );
				$meta_query .= " OR pm.meta_value LIKE '%\"{$object}\"%'";
			}
			if ( is_archive() || is_home() || is_search() ) {
				$meta_query .= " OR pm.meta_value LIKE '%\"general:archive\"%'";
			}
			if ( is_singular() ) {
				$meta_query .= " OR pm.meta_value RLIKE '\"{$location}:post:.*\"'";
				$meta_query .= " OR pm.meta_value RLIKE '\"{$location}:ancestor:.*\"'";
				$meta_query .= " OR pm.meta_value RLIKE '\"{$location}:taxonomy:.*\"'";
				$meta_query .= " OR pm.meta_value LIKE '%\"general:single\"%'";
			}

			$query = $query . ' AND (' . $meta_query . ')';
			$posts = $wpdb->get_results( $query );

			if ( empty( $posts ) || ! ( $post ) ) {
				return $content;
			}

			ob_start();
			return wpcw_get_template( 'units/single-content.php' );
		} else {
			return $content;
		}
	}

	/**
	 * Render unit template for Enfold's Avia page builder.
	 *
	 * @since 4.8.18
	 */
	public function wpcw_avia_render_unit_template( $content ){
			if ( is_singular( 'course_unit' ) ) {
				wpcw_get_template( 'units/single-content.php' );
			}else{
				return $content;
			}
	}
}