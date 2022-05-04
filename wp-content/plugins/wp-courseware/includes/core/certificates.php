<?php
/**
 * WP Courseware Certificates.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */

namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Certificates.
 *
 * @since 4.63
 */
class Certificates {

	/**
	 * @var string The certificate post type slug.
	 * @since 4.8.0
	 */
	public $post_type_slug = 'wpcw_certificates';

	/**
	 * @var string The category slug.
	 * @since 4.8.0
	 */
	public $taxonomy_category_slug = 'certificates_category';

	/**
	 * @var string The certificate tag.
	 * @since 4.8.0
	 */
	public $taxonomy_tag_slug = 'certificates_tag';

	/**
	 * Load Certificates.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );

		// Certificate Post Type.
		add_action( 'init', array( $this, 'post_type' ), 5 );

		// Course Columns
		add_filter( 'manage_edit-wpcw_certificates_columns', array( $this, 'wpcw_certificates_custom_columns' ), 10, 2 );
		add_action( 'manage_wpcw_certificates_posts_custom_column', array( $this, 'manage_wpcw_certificates_custom_columns_value' ), 10, 2 );
		add_filter( 'allowed_block_types_all', array( $this, 'wpcw_certificates_allowed_block_types' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'wpcw_certificates_post_type_permission_filter' ) );
		add_action( 'save_post', array( $this, 'set_default_block_certificate_post' ), 999, 3 );
	}

	/**
	 * Get Certificate Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The certificates settings fields.
	 */
	public function get_settings_fields() {
		return apply_filters(
			'wpcw_certificate_settings_feilds',
			array(
				array(
					'type'    => 'hidden',
					'key'     => 'cert_signature_type',
					'default' => 'text',
				),
				array(
					'type'    => 'hidden',
					'key'     => 'cert_sig_text',
					'default' => esc_attr( get_bloginfo( 'name' ) ),
				),
				array(
					'type'    => 'hidden',
					'key'     => 'cert_sig_image_url',
					'default' => '',
				),
				array(
					'type'    => 'hidden',
					'key'     => 'cert_logo_enabled',
					'default' => 'no_cert_logo',
				),
				array(
					'type'    => 'hidden',
					'key'     => 'cert_logo_url',
					'default' => '',
				),
				array(
					'type'    => 'hidden',
					'key'     => 'cert_background_type',
					'default' => 'use_default',
				),
				array(
					'type'    => 'hidden',
					'key'     => 'cert_background_custom_url',
					'default' => '',
				),
				array(
					'type'    => 'hidden',
					'key'     => 'certificate_encoding',
					'default' => 'ISO-8859-1',
				),
			)
		);
	}

	/**
	 * Register Settings Api Endpoints.
	 *
	 * @since 4.6.4
	 *
	 * @param array                 $endpoints The endpoints to filter.
	 * @param Api The api endpoints.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array(
			'endpoint' => 'certificate-image-url',
			'method'   => 'POST',
			'callback' => array( $this, 'api_get_certificate_image_url' ),
		);

		return $endpoints;
	}

	/**
	 * Api Get Certificate Image Url.
	 *
	 * @since 4.6.4
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function api_get_certificate_image_url( \WP_REST_Request $request ) {
		$attachment_id = $request->get_param( 'id' );

		$original_image_url = function_exists( 'wp_get_original_image_url' )
			? wp_get_original_image_url( $attachment_id )
			: wp_get_attachment_url( $attachment_id );

		return rest_ensure_response( array( 'url' => $original_image_url ) );
	}

	/**
	 * Get Permalinks.
	 *
	 * @since 4.8.0
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
	 * Register Post Type Certificate.
	 *
	 * @since 4.8.0
	 */
	public function post_type() {
		$permalinks = $this->get_permalinks();

		// Certificate Archive
		$certificates_page   = wpcw_get_page_id( 'certificates' );
		$certificate_archive = $certificates_page && get_post( $certificates_page )
			? urldecode( get_page_uri( $certificates_page ) )
			: apply_filters( 'wpcw_certificate_default_archive_slug', _x( 'certificates', 'slug', 'wp-courseware' ) );

		register_post_type(
			$this->post_type_slug,
			apply_filters(
				'wpcw_certificate_post_type_args',
				array(
					'labels'                => array(
						'name'               => __( 'Certificates', 'wp-courseware' ),
						'singular_name'      => __( 'Certificate', 'wp-courseware' ),
						'all_items'          => __( 'All Certificates', 'wp-courseware' ),
						'new_item'           => __( 'New Certificate', 'wp-courseware' ),
						'add_new'            => __( 'Add New', 'wp-courseware' ),
						'add_new_item'       => __( 'Add New Certificate', 'wp-courseware' ),
						'edit_item'          => __( 'Edit Certificate', 'wp-courseware' ),
						'view_item'          => __( 'View Certificate', 'wp-courseware' ),
						'view_items'         => __( 'View Certificates', 'wp-courseware' ),
						'search_items'       => __( 'Search Certificates', 'wp-courseware' ),
						'not_found'          => sprintf( __( 'No certificates found. <a href="%s">Add a new certificate</a>.', 'wp-courseware' ), admin_url( 'post-new.php?post_type=wpcw_certificates' ) ),
						'not_found_in_trash' => __( 'No certificates found in trash', 'wp-courseware' ),
						'parent_item_colon'  => __( 'Parent Certificate:', 'wp-courseware' ),
						'menu_name'          => __( 'Certificates', 'wp-courseware' ),
					),
					'public'                => true,
					'hierarchical'          => false,
					'show_ui'               => true,
					'show_in_nav_menus'     => true,
					'show_in_menu'          => true,
					'show_in_admin_bar'     => true,
					'supports'              => array( 'title', 'thumbnail', 'revisions', 'editor' ),
					'has_archive'           => $certificate_archive,
					'rewrite'               => $permalinks['certificate_rewrite_slug'] ? array(
						'slug'       => $permalinks['certificate_rewrite_slug'],
						'with_front' => false,
					) : false,
					'query_var'             => true,
					'map_meta_cap'          => true,
					'capability_type'       => 'wpcw_course',
					'can_export'            => true,
					'show_in_rest'          => true,
					'map_meta_cap'          => true,
					'rest_base'             => $this->post_type_slug,
					'rest_controller_class' => 'WP_REST_Posts_Controller',
				)
			)
		);
	}

	/**
	 * Course Custom Columns
	 *
	 * @since 4.4.0
	 *
	 * @param array $columns The array of columns.
	 *
	 * @return array $columns The array of columns.
	 */
	public function wpcw_certificates_custom_columns( $columns ) {
		$columns = array(
			'cb'                  => '<input type="checkbox" />',
			'title'               => esc_html__( 'Title', 'wp-courseware' ),
			'associated-course'   => esc_html__( 'Associated Course', 'wp-courseware' ),
			'preview-certificate' => esc_html__( 'Preview', 'wp-courseware' ),
			'date'                => esc_html__( 'Date', 'wp-courseware' ),
		);
		return $columns;
		/**
		 * Filter: Course Custom Columns.
		 *
		 * @since 4.4.0
		 *
		 * @param array $columns The custom columns.
		 *
		 * @return array $columns The course custom columns.
		 */
		// return apply_filters( 'wpcw_course_custom_columns', $columns );
	}

	/**
	 * Get Course.
	 *
	 * @since 4.4.0
	 *
	 * @param int $post_id The post id.
	 *
	 * @return Course|false The course object of false.
	 */
	public function get_course( $post_id = 0 ) {
		global $wp_query;

		if ( empty( $wp_query->posts ) ) {
			return false;
		}

		$post_ids       = wp_list_pluck( $wp_query->posts, 'ID' );
		$found_post_key = array_search( $post_id, $post_ids );
		$found_post     = isset( $wp_query->posts[ $found_post_key ] ) ? $wp_query->posts[ $found_post_key ] : null;

		if ( is_null( $found_post ) ) {
			return false;
		}

		return new Course( $found_post );
	}
	/**
	 * Manage Custom Columns.
	 *
	 * @since 4.8.0
	 *
	 * @param string $column The column slug string.
	 * @param int    $post_id The post id.
	 */
	public function manage_wpcw_certificates_custom_columns_value( $column, $post_id ) {
		global $post, $wp_query, $wpdb, $wpcwdb;

		if ( $this->post_type_slug !== $post->post_type ) {
			return;
		}

		$results           = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT course_post_id, course_title FROM $wpcwdb->courses
			 WHERE certificate_template = %d
			 ORDER BY course_post_id ASC",
				$post->ID
			)
		);
		$associated_corses = '';
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				// echo '<a class="" href="' . esc_url( site_url() ) . '?page=wpcw_pdf_create_certificate&certificate=preview&course_id=block_preview&certificate_template_id=' . esc_attr( $post->ID ) . '" target="_blank">Preview Certificate</a>';
				$associated_corses .= '<a href="' . esc_url( admin_url( 'post.php?post=' . $result->course_post_id ) ) . '&action=edit">' . esc_html( $result->course_title ) . '</a>, ';
			}
			$associated_corses = rtrim( $associated_corses, ', ' );
		} else {
			$associated_corses .= '-';
		}
		switch ( $column ) {
			case 'preview-certificate':
				echo '<a class="button-primary" href="' . esc_url( site_url() ) . '?page=wpcw_pdf_create_certificate&certificate=preview&course_id=block_preview&certificate_template_id=' . esc_attr( $post->ID ) . '" target="_blank">Preview Certificate</a>';
				break;
			case 'associated-course':
				echo $associated_corses;

		}

			/**
			 * Action: Course Manage Custom Column
			 *
			 * @since 4.4.0
			 *
			 * @param Course       $course The course object.
			 * @param Page_Courses $this The page courses object.
			 */
			// do_action( "wpcw_course_manage_custom_column_{$column}", $course, $this );
		// }
	}

	/**
	 * Limit the blocks allowed in Gutenberg.
	 *
	 * @since 4.8.0
	 *
	 * @param mixed $allowed_blocks Array of allowable blocks for Gutenberg Editor.
	 * @param mixed $post Gets current post type.
	 *
	 * @return mixed $allowed_blocks Returns the allowed blocks.
	 * */
	public function wpcw_certificates_allowed_block_types( $allowed_block_types, $post ) {
		if ( 'wpcw_certificates' === $post->post->post_type ) {
			return array(
				'wpcw/text-field',
				'wpcw/course-title',
				'wpcw/student-name',
				'wpcw/signature',
				'wpcw/image',
				'wpcw/certificate-detail',
				'wpcw/pre-template-1',
				'wpcw/pre-template-2',
				'wpcw/raw-template',
				'wpcw/courseware-certificate',
			);
		} else {
			return $allowed_block_types;
		}
	}

	/**
	 * Action to set default block for certificate post.
	 *
	 * @since 4.8.0
	 *
	 * @param int $post_ID The post id.
	 */
	public function set_default_block_certificate_post( $post_ID, $post, $update ) {
		if ( ! $update ) {
			$http_referer = filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING );
			parse_str( wp_parse_url( $http_referer, PHP_URL_QUERY ), $queries );
			if ( isset( $queries['post_type'] ) && 'wpcw_certificates' === $queries['post_type'] ) {
				$page_type_object           = get_post_type_object( 'wpcw_certificates' );
				$page_type_object->template = array(
					array( 'wpcw/courseware-certificate' ),
				);
				return;
			}
		}
	}

	/**
	 * Post Type Permission Filter.
	 *
	 * @since 4.8.4
	 *
	 * @param WP_Query $wp_query The WP_Query object.
	 */
	public function wpcw_certificates_post_type_permission_filter( $wp_query ) {
		global $pagenow, $typenow;
		// Check, if is admin.
		if ( ! $wp_query->is_admin ) {
			return;
		}
		// Check.
		if ( 'edit.php' !== $pagenow || $this->post_type_slug !== $typenow ) {
			return;
		}
		// Get Current User.
		$current_user = wp_get_current_user();
		// Check permissions.
		if ( ! user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
			$wp_query->set( 'author', $current_user->ID );
		}
	}
}
