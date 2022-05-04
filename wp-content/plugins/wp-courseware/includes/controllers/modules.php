<?php
/**
 * WP Courseware Modules Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WP_REST_Request;
use WPCW\Core\Api;
use WPCW\Database\DB_Modules;
use WPCW\Models\Module;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Modules.
 *
 * @since 4.1.0
 */
class Modules extends Controller {

	/**
	 * @var DB_Modules The modules db object.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Modules constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Modules();
	}

	/**
	 * Modules Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Get Module by Id.
	 *
	 * @since 4.1.0
	 *
	 * @param int $module_id The module id.
	 *
	 * @return bool|Module The module object.
	 */
	public function get_module( $module_id ) {
		if ( 0 === absint( $module_id ) ) {
			return false;
		}

		$row = $this->db->get( $module_id );

		if ( ! $row ) {
			return false;
		}

		return new Module( $row );
	}

	/**
	 * Get Modules.
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 *
	 * @return array Array of module objects.
	 */
	public function get_modules( $args = array(), $raw = false ) {
		$modules = array();
		$results = $this->db->get_modules( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$modules[] = new Module( $result );
		}

		return $modules;
	}

	/**
	 * Get Number of Modules.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of modules.
	 */
	public function get_modules_count( $args = array() ) {
		return $this->db->get_modules( $args, true );
	}

	/**
	 * Get Module Course Title.
	 *
	 * @since 4.1.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string $course_title The course title.
	 */
	public function get_module_course_title( $course_id ) {
		return $this->db->get_module_course_title( $course_id );
	}

	/**
	 * Get Modules Filter Dropdown.
	 *
	 * @since 4.4.0
	 *
	 * @return string The html for the courses filter dropdown.
	 */
	public function get_modules_filter_dropdown() {
		$module_id = isset( $_GET['module_id'] ) ? absint( $_GET['module_id'] ) : 0;

		ob_start();

		printf( '<span class="wpcw-filter-wrapper">' );
		printf( '<select id="wpcw-modules-filter" class="select-field-wpcwselect2-filter" name="module_id" data-placeholder="%s">', esc_html__( 'All Modules', 'wp-courseware' ) );

		if ( $module_id ) {
			$module = new Module( $module_id );
			printf( '<option value="%s">%s</option>', $module->get_id(), sprintf( '%s (%s)', $module->get_module_title(), $module->get_course_title() ) );
		}

		printf( '</select>' );
		printf( '</span>' );

		return ob_get_clean();
	}

	/**
	 * Delete Module.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The module id.
	 */
	public function delete_module( $module_id ) {
		if ( ! is_admin() || ! current_user_can( 'view_wpcw_courses' ) ) {
			return false;
		}

		if ( $module = $this->get_module( $module_id ) ) {
			if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
				if ( $module->get_module_author() !== get_current_user_id() ) {
					return false;
				}
			}

			if ( WPCW_modules_deleteModule( $module ) ) {
				return $module;
			}
		}

		return false;
	}

	/** API Endpoint Methods -------------------------------------------------- */

	/**
	 * Register Modules Api Endpoints.
	 *
	 * @since 4.4.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'modules', 'method' => 'GET', 'callback' => array( $this, 'api_get_modules' ) );
		$endpoints[] = array( 'endpoint' => 'modules-filtered', 'method' => 'GET', 'callback' => array( $this, 'api_get_modules_filtered' ) );

		return $endpoints;
	}

	/**
	 * Api: Get Modules.
	 *
	 * @since 4.4.0
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_modules( WP_REST_Request $request ) {
		$search    = $request->get_param( 'search' );
		$number    = $request->get_param( 'number' );
		$order     = $request->get_param( 'order' );
		$author    = $request->get_param( 'author' );
		$course_id = $request->get_param( 'course_id' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = 10000;
		}

		if ( ! $order ) {
			$order = 'ASC';
		}

		if ( ! $author ) {
			$author = '';
		}

		if ( ! $course_id ) {
			$course_id = false;
		}

		$results    = array();
		$query_args = array(
			'search'        => $search,
			'number'        => $number,
			'order'         => $order,
			'module_author' => $author,
			'course_id'     => $course_id,
		);

		$modules = $this->get_modules( $query_args );
		$count   = $this->get_modules_count( $query_args );

		foreach ( $modules as $module ) {
			if ( ! $module instanceof Module ) {
				continue;
			}

			$results[] = array(
				'id'     => $module->get_id(),
				'title'  => $module->get_module_title(),
				'desc'   => $module->get_module_desc(),
				'number' => $module->get_module_number(),
			);
		}

		return rest_ensure_response( array( 'modules' => $results ) );
	}

	/**
	 * Api: Get Modules Fifltered.
	 *
	 * @since 4.4.0
	 *
	 * @param object WP_REST_Request The api request.
	 *
	 * @return object WP_REST_Response The api response.
	 */
	public function api_get_modules_filtered( WP_REST_Request $request ) {
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
			$args['module_author'] = get_current_user_id();
		}

		$modules = $this->get_modules( $args );

		if ( $modules ) {
			/** @var Module $module */
			foreach ( $modules as $module ) {
				$results[] = array(
					'id'           => $module->get_module_id(),
					'title'        => html_entity_decode( $module->get_module_title(), ENT_QUOTES, get_bloginfo( 'charset' ) ),
					'course_title' => $module->get_course_title(),
				);
			}
		}

		return rest_ensure_response( array( 'modules' => $results ) );
	}
}
