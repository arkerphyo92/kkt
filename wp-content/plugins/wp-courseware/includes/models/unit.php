<?php
/**
 * WP Courseware Unit Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.3.0
 */

namespace WPCW\Models;

use WPCW\Database\DB_Units;
use WPCW_queue_dripfeed;
use WP_Post;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Unit.
 *
 * @since 4.3.0
 *
 * @property int $unit_id
 * @property int $parent_module_id
 * @property int $parent_course_id
 * @property int $unit_author
 * @property int $unit_order
 * @property int $unit_number
 * @property string $unit_drip_type
 * @property string $unit_drip_date
 * @property int $unit_drip_interval
 * @property string $unit_drip_interval_type
 * @property int $unit_teaser
 * @property string $unit_status
 * @property array $post_data
 * @property Module $module
 * @property Course $course
 */
class Unit extends Model {

	/**
	 * @var DB_Units The courses database.
	 * @since 4.4.0
	 */
	protected $db;

	/**
	 * @var int The Unit Id.
	 * @since 4.4.0
	 */
	public $unit_id;

	/**
	 * @var int The parent module id.
	 * @since 4.4.0
	 */
	public $parent_module_id = 0;

	/**
	 * @var int The parent course id.
	 * @since 4.4.0
	 */
	public $parent_course_id = 0;

	/**
	 * @var int The unit author.
	 * @since 4.4.0
	 */
	public $unit_author = 0;

	/**
	 * @var int The unit order.
	 * @since 4.4.0
	 */
	public $unit_order = 0;

	/**
	 * @var int The unit number.
	 * @since 4.4.0
	 */
	public $unit_number = 0;

	/**
	 * @var string The unit drip type.
	 * @since 4.4.0
	 */
	public $unit_drip_type = '';

	/**
	 * @var string The unit drip date.
	 * @since 4.4.0
	 */
	public $unit_drip_date = '0000-00-00 00:00:00';

	/**
	 * @var string The unit drip date timestamp.
	 * @since 4.6.0
	 */
	public $unit_drip_date_ts = '';

	/**
	 * @var int The unit drip interval.
	 * @since 4.4.0
	 */
	public $unit_drip_interval = 432000;

	/**
	 * @var string The unit drip interval type.
	 * @since 4.4.0
	 */
	public $unit_drip_interval_type = 'interval_days';

	/**
	 * @var int Is the unit a teaser unit?
	 * @since 4.6.0
	 */
	public $unit_teaser = 0;

	/**
	 * @var string The unit status.
	 * @since 4.4.0
	 */
	public $unit_status = '';

	/**
	 * @var string The post type slug.
	 * @since 4.4.0
	 */
	protected $post_type = 'course_unit';

	/**
	 * @var WP_Post The post data.
	 * @since 4.4.0
	 */
	protected $post_data = array(
		'ID'                    => 0,
		'post_author'           => 0,
		'post_date'             => '',
		'post_date_gmt'         => '',
		'post_content'          => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => '',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'post_name'             => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_modified'         => '',
		'post_modified_gmt'     => '',
		'post_content_filtered' => '',
		'post_parent'           => '',
		'guid'                  => '',
		'menu_order'            => '',
		'post_type'             => '',
		'post_mime_type'        => '',
		'comment_count'         => '',
	);

	/**
	 * @var array The post data map.
	 * @since 4.4.0
	 */
	protected $post_data_map = array(
		'unit_id'     => 'ID',
		'unit_title'  => 'post_title',
		'unit_desc'   => 'post_content',
		'unit_author' => 'post_author',
		'unit_status' => 'post_status',
	);

	/**
	 * @var Module The module object.
	 * @since 4.4.0
	 */
	public $module;

	/**
	 * @var Course The course object.
	 * @since 4.4.0
	 */
	public $course;

	/**
	 * Unit Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param array|int|Model $data The data to setup the course.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Units();
		parent::__construct( $data );
	}

	/**
	 * Set Unit Data.
	 *
	 * @since 4.4.0
	 *
	 * @param object $values The model data values.
	 */
	public function set_data( $values ) {
		foreach ( $values as $key => $value ) {
			if ( $this->property_exists( $key ) ) {
				$this->$key       = $value;
				$this->data->$key = $value;
			}

			if ( array_key_exists( $key, $this->post_data ) ) {
				$this->post_data[ $key ] = $value;
			}

			if ( 'unit_drip_date' === $key ) {
				$this->unit_drip_date_ts = strtotime( $value );
			}
		}
	}

	/**
	 * Set Unit Properties.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key The property key.
	 * @param mixed $value The property value.
	 */
	public function set_prop( $key, $value = null ) {
		if ( $this->property_exists( $key ) && ! is_null( $value ) ) {
			$this->$key       = $value;
			$this->data->$key = $value;
		}

		if ( array_key_exists( $key, $this->post_data ) ) {
			$this->post_data[ $key ] = $value;
		}

		if ( array_key_exists( $key, $this->post_data_map ) ) {
			$this->post_data[ $this->post_data_map[ $key ] ] = $value;
		}
	}

	/**
	 * Set Unit Properties.
	 *
	 * @since 4.4.0
	 *
	 * @param array $props Key value pairs to set.
	 *
	 * @return bool True if the properties were set, False otherwise.
	 */
	public function set_props( $props = array() ) {
		if ( empty( $props ) ) {
			return false;
		}

		foreach ( $props as $key => $value ) {
			if ( $this->property_exists( $key ) && ! is_null( $value ) ) {
				$this->$key       = $value;
				$this->data->$key = $value;
			}

			if ( array_key_exists( $key, $this->post_data ) ) {
				$this->post_data[ $key ] = $value;
			}

			if ( array_key_exists( $key, $this->post_data_map ) ) {
				$this->post_data[ $this->post_data_map[ $key ] ] = $value;
			}
		}

		return true;
	}

	/**
	 * Create Unit Object.
	 *
	 * @since 4.4.0
	 *
	 * @param array $data The data to insert upon creation.
	 *
	 * @return int|bool $object_id The object id or false otherwise.
	 */
	public function create( $data = array() ) {
		if ( empty( $this->db ) || ! $this->db->get_primary_key() ) {
			return;
		}

		$post_data = array();

		foreach ( $data as $key => $value ) {
			if ( array_key_exists( $key, $this->post_data ) ) {
				$post_data[ $key ] = $value;
			}

			if ( array_key_exists( $key, $this->post_data_map ) ) {
				$post_data[ $this->post_data_map[ $key ] ] = $value;
			}
		}

		$post_defaults = $this->get_post_defaults();
		$post_data     = wp_parse_args( $post_data, $post_defaults );

		$unit_id = wp_insert_post( wp_slash( $post_data ) );

		if ( is_wp_error( $unit_id ) ) {
			$this->log( $unit_id->get_error_message() );

			return;
		}

		if ( empty( $data['unit_id'] ) ) {
			$data['unit_id'] = absint( $unit_id );
		}

		$defaults = $this->get_defaults();
		$data     = wp_parse_args( $data, $defaults );

		$type = strtolower( get_class( $this ) );

		if ( $object_id = $this->db->insert( $data, $type ) ) {
			$this->set_prop( $this->db->get_primary_key(), $object_id );
			$this->set_data( $data );
			$this->prime_post_data();
		}

		return $object_id;
	}

	/**
	 * Get Unit Defaults.
	 *
	 * @since 4.4.0
	 *
	 * @return array The model defaults.
	 */
	public function get_defaults() {
		return array(
			'parent_module_id'        => 0,
			'parent_course_id'        => 0,
			'unit_author'             => get_current_user_id(),
			'unit_order'              => 0,
			'unit_number'             => 0,
			'unit_drip_type'          => '',
			'unit_drip_date'          => '0000-00-00 00:00:00',
			'unit_drip_interval'      => 432000,
			'unit_drip_interval_type' => 'interval_days',
		);
	}

	/**
	 * Get Unit Post Defaults.
	 *
	 * @since 4.4.0
	 *
	 * @return array The model post defaults.
	 */
	public function get_post_defaults() {
		return array(
			'post_title'   => esc_html__( 'Unit', 'wp-courseware' ),
			'post_content' => esc_html__( 'Unit Content', 'wp-courseware' ),
			'post_type'    => $this->post_type,
			'post_status'  => 'publish',
		);
	}

	/**
	 * Get Unit Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int|void
	 */
	public function get_id() {
		return absint( $this->get_unit_id() );
	}

	/**
	 * Get Unit Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit id.
	 */
	public function get_unit_id() {
		return absint( $this->unit_id );
	}

	/**
	 * Get Parent Module Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit parent module id.
	 */
	public function get_parent_module_id() {
		return absint( $this->parent_module_id );
	}

	/**
	 * Get Parent Course Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit parent course id.
	 */
	public function get_parent_course_id() {
		return absint( $this->parent_course_id );
	}

	/**
	 * Get Unit Author.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit author.
	 */
	public function get_unit_author() {
		return absint( $this->unit_author );
	}

	/**
	 * Get Unit Order.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit order.
	 */
	public function get_unit_order() {
		return absint( $this->unit_order );
	}

	/**
	 * Get Unit Number.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit number.
	 */
	public function get_unit_number() {
		return absint( $this->unit_number );
	}

	/**
	 * Get Unit Drip Type.
	 *
	 * @since 4.4.0
	 *
	 * @return string The unit drip type.
	 */
	public function get_unit_drip_type() {
		return $this->unit_drip_type;
	}

	/**
	 * Get Unit Drip Date.
	 *
	 * @since 4.4.0
	 *
	 * @return string The unit drip date.
	 */
	public function get_unit_drip_date() {
		return $this->unit_drip_date;
	}

	/**
	 * Get Unit Drip Date Time Stamp.
	 *
	 * @since 4.6.0
	 */
	public function get_unit_drip_date_ts() {
		return $this->unit_drip_date_ts;
	}

	/**
	 * Get Unit Drip Date Visible.
	 *
	 * @since 4.6.0
	 *
	 * @return string The visible unit drip date.
	 */
	public function get_unit_drip_date_visible() {
		return $this->get_unit_drip_date_ts() && $this->get_unit_drip_date_ts() > 1 ? date_i18n( 'j M Y H:i:s', $this->get_unit_drip_date_ts() ) : date_i18n( 'j M Y H:i:00' );
	}

	/**
	 * Get Unit Drip Date Hidden.
	 *
	 * @since 4.6.0
	 *
	 * @return string The hidden unit drip date.
	 */
	public function get_unit_drip_date_hidden() {
		return $this->get_unit_drip_date_ts() && $this->get_unit_drip_date_ts() > 1 ? date_i18n( 'Y-m-d H:i:s', $this->get_unit_drip_date_ts() ) : date_i18n( 'Y-m-d H:i:00' );
	}

	/**
	 * Get Unit Drip Date Interval.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit drip date interval.
	 */
	public function get_unit_drip_interval() {
		return absint( $this->unit_drip_interval );
	}

	/**
	 * Get Unit Drip Date Interval Number.
	 *
	 * @since 4.4.0
	 *
	 * @return int The unit drip date interval.
	 */
	public function get_unit_drip_interval_number() {
		$interval        = $this->get_unit_drip_interval() ?: 432000;
		$interval_type   = $this->get_unit_drip_interval_type() ?: 'interval_days';
		$interval_number = 0;

		switch ( $interval_type ) {
			case 'interval_hours':
				$interval_number = $interval / WPCW_TIME_HR_IN_SECS;
				break;

			case 'interval_days':
				$interval_number = $interval / WPCW_TIME_DAY_IN_SECS;
				break;

			case 'interval_weeks':
				$interval_number = $interval / WPCW_TIME_WEEK_IN_SECS;
				break;

			case 'interval_months':
				$interval_number = $interval / WPCW_TIME_MONTH_IN_SECS;
				break;

			case 'interval_years':
				$interval_number = $interval / WPCW_TIME_YEAR_IN_SECS;
				break;
		}

		return $interval_number;
	}

	/**
	 * Get Unit Drip Interval Type.
	 *
	 * @since 4.4.0
	 *
	 * @return string The unit drip interval type.
	 */
	public function get_unit_drip_interval_type() {
		return $this->unit_drip_interval_type;
	}

	/**
	 * Get Unit Teaser.
	 *
	 * @since 4.6.0
	 *
	 * @return int The unit teaser condition.
	 */
	public function get_unit_teaser() {
		return absint( $this->unit_teaser );
	}

	/**
	 * Get Unit Status.
	 *
	 * @since 4.4.0
	 *
	 * @return string The unit status.
	 */
	public function get_unit_status() {
		if ( empty( $this->unit_status ) ) {
			$this->unit_status = $this->get_post_value( 'post_status' );
		}

		return $this->unit_status;
	}

	/**
	 * Prime Post Data.
	 *
	 * @since 4.4.0
	 */
	protected function prime_post_data() {
		if ( ! $this->get_unit_id() ) {
			return;
		}

		$post = get_post( $this->get_unit_id() );

		foreach ( $post->to_array() as $key => $value ) {
			if ( array_key_exists( $key, $this->post_data ) ) {
				$this->post_data[ $key ] = $value;
			}
		}
	}

	/**
	 * Get Post data.
	 *
	 * @since 4.4.0
	 *
	 * @return object|null
	 */
	public function get_post_data() {
		if ( ! $this->get_unit_id() ) {
			return;
		}

		if ( empty( $this->post_data ) ) {
			$this->prime_post_data();
		}

		return $this->post_data;
	}

	/**
	 * Get Post Value.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key The post value key.
	 *
	 * @return mixed The value of the post.
	 */
	public function get_post_value( $key ) {
		if ( ! $this->get_unit_id() ) {
			return;
		}

		$post_data = $this->get_post_data();

		return isset( $post_data[ $key ] ) ? $post_data[ $key ] : false;
	}

	/**
	 * Get Unit Status.
	 *
	 * @since 4.4.0
	 *
	 * @return int|bool The unit post id or false.
	 */
	public function get_unit_post_id() {
		return $this->get_post_value( 'ID' );
	}

	/**
	 * Get Unit Title.
	 *
	 * @since 4.4.0
	 *
	 * @return string|bool The unit title or false.
	 */
	public function get_unit_title() {
		return $this->get_post_value( 'post_title' );
	}

	/**
	 * Get Unit Content.
	 *
	 * @since 4.4.0
	 *
	 * @return string|bool The unit status or false.
	 */
	public function get_unit_content() {
		return $this->get_post_value( 'post_content' );
	}

	/**
	 * Get Unit Description.
	 *
	 * @since 4.4.0
	 *
	 * @return string|bool The unit status or false.
	 */
	public function get_unit_desc() {
		return $this->get_unit_content();
	}

	/**
	 * Get a Unit Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return get_post_meta( $this->get_unit_id(), $meta_key, $single );
	}

	/**
	 * Add Unit Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Metadata value.
	 * @param bool $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function add_meta( $meta_key = '', $meta_value = null, $unique = false ) {
		return add_post_meta( $this->get_unit_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Unit Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @param mixed $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, true if success.
	 */
	public function update_meta( $meta_key = '', $meta_value = null, $prev_value = '' ) {
		return update_post_meta( $this->get_unit_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Unit Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return delete_post_meta( $this->get_unit_id(), $meta_key, $meta_value );
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
			$this->course = new Course( $this->get_parent_course_id() );
		}

		return $this->course;
	}

	/**
	 * Get Module.
	 *
	 * @since 4.4.0
	 *
	 * @return Module The module object.
	 */
	public function get_module() {
		if ( empty( $this->module ) ) {
			$this->module = new Module( $this->get_parent_module_id() );
		}

		return $this->module;
	}

	/**
	 * Get Quizzes.
	 *
	 * @since 4.4.0
	 *
	 * @return array An array of associated  quizzes.
	 */
	public function get_quizzes() {
		return wpcw_get_quizzes( array( 'unit_id' => $this->get_unit_id() ) );
	}

	/**
	 * Disconnect Unit.
	 *
	 * This will make the quiz disconnected from any module
	 * and disassociate any quizzes.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if it is successfully unassociated.
	 */
	public function disconnect() {
		global $wpdb, $wpcwdb;

		// Set Properties.
		$this->set_prop( 'unit_order', 0 );
		$this->set_prop( 'unit_number', 0 );
		$this->set_prop( 'parent_module_id', 0 );
		$this->set_prop( 'parent_course_id', 0 );

		// Delete Module Meta.
		$this->delete_meta( 'wpcw_associated_module' );

		// Delete from user progress.
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $wpcwdb->user_progress
			 WHERE unit_id = %d",
			$this->get_id()
		) );

		// Unconnect it from the quiz that it's associated with.
		$wpdb->query( $wpdb->prepare(
			"UPDATE $wpcwdb->quiz
			 SET parent_unit_id = %d, parent_course_id = %d
			 WHERE parent_unit_id = %d",
			0, 0, $this->get_id()
		) );

		// Update Queue Items.
		WPCW_queue_dripfeed::updateQueueItems_unitRemovedFromCourse( $this->get_id() );

		// Save Unit.
		$this->save();
	}

	/**
	 * Update Author.
	 *
	 * @param int $author_id
	 */
	public function update_author( $author_id ) {
		$this->set_prop( 'unit_author', absint( $author_id ) );
		$this->save();
	}

	/**
	 * Save Unit
	 *
	 * @since 4.4.0
	 *
	 * @param bool $save_post Should the course post be saved? Default is true.
	 * @param bool $run_hook Should we run the course details updated hook. Used for updating course progress.
	 *
	 * @return bool True if successfull, False on failure
	 */
	public function save( $save_post = true ) {
		$data = $this->get_data( true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		$data = wp_unslash( $data );
		
		$this->db->update( $this->get_id(), $data );

		if ( $save_post ) {
			if ( isset( $this->post_data['post_modified'], $this->post_data['post_modified_gmt'] ) ) {
				unset( $this->post_data['post_modified'], $this->post_data['post_modified_gmt'] );
			}

			// Update Post.
			wp_update_post( wp_slash( (array) $this->get_post_data() ) );

		}
		return $this->get_id();
	}

	/**
	 * Delete Unit.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True upon successful deletion. False otherwise.
	 */
	public function delete() {
		global $wpdb, $wpcwdb;

		$unit_deleted = $this->db->delete( $this->get_id() );

		if ( $unit_deleted ) {
			// Delete Post.
			wp_delete_post( $this->get_id(), true );

			// Parent Course Id.
			$parent_course_id = $this->get_parent_course_id();

			// Ony run if parent course id exists.
			if ( $parent_course_id > 0 ) {
				// Re-Order Unit Numbers.
				WPCW_courses_reorderUnitNumbers( $parent_course_id );

				// Get Course Details.
				$course = wpcw_get_course( $parent_course_id );

				/**
				 * Action: Course Details Updated.
				 *
				 * @since 4.4.0
				 */
				do_action( 'wpcw_course_details_updated', $course );
			}

			// Delete User Progress.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_progress WHERE unit_id = %d", $this->get_id() ) );

			// Disconnnect Quiz.
			$wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->quiz SET parent_unit_id = %d, parent_course_id = %d WHERE parent_unit_id = %d", 0, 0, $this->get_id() ) );

			// Remove Quiz Progress.
			$wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->user_progress_quiz SET unit_id = %d WHERE unit_id = %d", 0, $this->get_id() ) );

			// Update Queue Items.
			WPCW_queue_dripfeed::updateQueueItems_unitDeleted( $this->get_id() );
		}

		return $deleted;
	}

	/**
	 * Get Edit Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The edit module url.
	 */
	public function get_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_unit_get_edit_url', add_query_arg( array( 'post' => $this->get_unit_id(), 'action' => 'edit' ), admin_url( 'post.php' ) ), $this ) );
	}

	/**
	 * Get View Url.
	 *
	 * @since 4.6.0
	 *
	 * @return string The edit module url.
	 */
	public function get_view_url() {
		return esc_url_raw( apply_filters( 'wpcw_unit_get_view_url', get_permalink( $this->get_unit_post_id() ), $this ) );
	}

	/**
	 * Get Permalink.
	 *
	 * @since 4.6.8
	 *
	 * @return string The unit permalink.
	 */
	public function get_permalink() {
		return $this->get_view_url();
	}

	/**
	 * Is Unit a Teaser?
	 *
	 * @since 4.6.8
	 *
	 * @return bool True if the unit is a teaser unit.
	 */
	public function is_teaser() {
		return $this->get_unit_teaser()
			? true
			: false;
	}
}
