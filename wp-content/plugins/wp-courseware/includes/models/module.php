<?php
/**
 * WP Courseware Module Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.1.0
 */
namespace WPCW\Models;

use WPCW\Database\DB_Modules;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Module.
 *
 * @since 4.1.0
 *
 * @property int    $module_id
 * @property int    $parent_course_id
 * @property int    $module_author
 * @property string $module_title
 * @property string $module_desc
 * @property int    $module_order
 * @property int    $module_number
 * @property int    $course_id
 * @property int    $course_title
 * @property int    $course_post_id
 */
class Module extends Model {

	/**
	 * @var DB_Modules The modules database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var int Module Id.
	 * @since 4.1.0
	 */
	public $module_id;

	/**
	 * @var int Module Course Id.
	 * @since 4.1.0
	 */
	public $parent_course_id;

	/**
	 * @var int Module Author
	 * @since 4.1.0
	 */
	public $module_author = 0;

	/**
	 * @var string Module Author.
	 * @since 4.1.0
	 */
	public $module_title = '';

	/**
	 * @var string Module Description.
	 * @since 4.1.0
	 */
	public $module_desc = '';

	/**
	 * @var int Module Order.
	 * @since 4.1.0
	 */
	public $module_order = 0;

	/**
	 * @var int Module Number.
	 * @since 4.1.0
	 */
	public $module_number = 0;

	/**
	 * @var int Course Id.
	 * @since 4.1.0
	 */
	public $course_id = 0;

	/**
	 * @var string Course Title.
	 * @since 4.1.0
	 */
	public $course_title = '';

	/**
	 * @var int Course Post Id.
	 * @since 4.4.0
	 */
	public $course_post_id = 0;

	/**
	 * @var Course The course object.
	 * @since 4.4.0
	 */
	public $course;

	/**
	 * Module Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Modules();
		parent::__construct( $data );
	}

	/**
	 * Get Module Defaults.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of default values for a new module.
	 */
	public function get_defaults() {
		return array(
			'parent_course_id' => 0,
			'module_author'    => get_current_user_id(),
			'module_title'     => '',
			'module_order'     => 10000,
			'module_number'    => 1,
		);
	}

	/**
	 * Get Module Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int|void
	 */
	public function get_id() {
		return absint( $this->get_module_id() );
	}

	/**
	 * Get Module Id.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed
	 */
	public function get_module_id() {
		return absint( $this->module_id );
	}

	/**
	 * Get Parent Course Id.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed
	 */
	public function get_parent_course_id() {
		return absint( $this->parent_course_id );
	}

	/**
	 * Get Module Author
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_module_author() {
		return absint( $this->module_author );
	}

	/**
	 * Get Module Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_module_title() {
		return esc_attr( $this->module_title );
	}

	/**
	 * Get Module Description.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_module_desc() {
		return wp_kses_post( $this->module_desc );
	}

	/**
	 * Get Module Order.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_module_order() {
		return absint( $this->module_order );
	}

	/**
	 * Get Module Number.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_module_number() {
		return absint( $this->module_number );
	}

	/**
	 * Get Course Id.
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_course_id() {
		if ( empty( $this->course_id ) ) {
			return $this->get_parent_course_id();
		}

		return absint( $this->course_id );
	}

	/**
	 * Get Course Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_title() {
		if ( empty( $this->course_title ) ) {
			$this->course_title = $this->db->get_module_course_title( $this->get_course_id() );
		}

		return esc_attr( $this->course_title );
	}

	/**
	 * Get Course Post Id.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_post_id() {
		if ( empty( $this->course_post_id ) ) {
			$this->course_post_id = $this->db->get_module_course_post_id( $this->get_course_id() );
		}

		return esc_attr( $this->course_post_id );
	}

	/**
	 * Get Edit Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The edit module url.
	 */
	public function get_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_module_get_edit_url', add_query_arg( array(
			'page'      => 'WPCW_showPage_ModifyModule',
			'module_id' => $this->get_module_id(),
		), admin_url( 'admin.php' ) ), $this ) );
	}

	/**
	 * Get Course.
	 *
	 * @since 4.4.0
	 *
	 * @return Course The course object.
	 */
	public function get_course() {
		if ( empty( $this->course ) ) {
			$this->course = new Course( $this->get_course_id() );
		}

		return $this->course;
	}

	/**
	 * Get Units.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args The units query args.
	 *
	 * @return array The units associated with this module.
	 */
	public function get_units( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'number'    => 10000,
			'module_id' => $this->get_module_id(),
			'orderby'   => 'unit_number',
			'order'     => 'ASC',
		) );

		return wpcw_get_units( $args );
	}

	/**
	 * Disconnect Units.
	 *
	 * @since 4.4.0
	 */
	public function disconnect_units() {
		$args = array( 'unit_status' => array( 'publish', 'draft' ) );
		if ( $units = $this->get_units( $args ) ) {
			/** @var Unit $unit */
			foreach ( $units as $unit ) {
				$unit->disconnect();
			}
		}
	}

	/**
	 * Update Author.
	 *
	 * @since 4.5.2
	 *
	 * @param int $author_id The new author id.
	 */
	public function update_author( $author_id ) {
		$this->set_prop( 'module_author', absint( $author_id ) );
		$this->save();
	}

	/**
	 * Save Module.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if successfull, False on failure
	 */
	public function save() {
		$data = $this->get_data( true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		$data = wp_unslash( $data );

		$this->db->update( $this->get_id(), $data );

		return $this->get_id();
	}

	/**
	 * Delete Module.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if it is deleted, false otherwise.
	 */
	public function delete() {
		$module_id = $this->get_id();
		$course_id = $this->get_course_id();

		// Disconnect Units.
		$this->disconnect_units();

		if ( $course = new Course( $course_id ) ) {
			/**
			 * Hook: Course Details Updated.
			 *
			 * @since 4.4.0
			 *
			 * @param Course The course object.
			 */
			do_action( 'wpcw_course_details_updated', $course );
		}

		// Delete Module.
		$deleted = $this->db->delete( $module_id );

		if ( $deleted ) {
			/**
			 * Action: Module Deleted.
			 *
			 * @since 4.4.0
			 *
			 * @param Module The module object.
			 */
			do_action( 'wpcw_module_deleted', $this );
		}

		return $deleted;
	}
}
