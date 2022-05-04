<?php
/**
 * WP Courseware Course Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.1.0
 */

namespace WPCW\Models;

use WPCW\Database\DB_Course_Meta;
use WPCW\Database\DB_Courses;
use WP_Post;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Course.
 *
 * @since 4.3.0
 *
 * @property int $course_id
 * @property int $course_post_id
 * @property string $course_title
 * @property string $course_desc
 * @property int $course_author
 * @property string $course_status
 * @property string $course_opt_completion_wall
 * @property string $course_opt_use_certificate
 * @property string $course_opt_user_access
 * @property int $course_unit_count
 * @property string $course_from_name
 * @property string $course_from_email
 * @property string $course_to_email
 * @property string $course_opt_prerequisites
 * @property string $course_message_unit_complete
 * @property string $course_message_course_complete
 * @property string $course_message_unit_not_logged_in
 * @property string $course_message_unit_pending
 * @property string $course_message_unit_no_access
 * @property string $course_message_prerequisite_not_met
 * @property string $course_message_unit_not_yet
 * @property string $course_message_unit_not_yet_dripfeed
 * @property string $course_message_quiz_open_grading_blocking
 * @property string $course_message_quiz_open_grading_non_blocking
 * @property string $email_complete_module_option_admin
 * @property string $email_complete_module_option
 * @property string $email_complete_module_subject
 * @property string $email_complete_module_body
 * @property string $email_complete_course_option_admin
 * @property string $email_complete_course_option
 * @property string $email_complete_course_subject
 * @property string $email_complete_course_body
 * @property string $email_quiz_grade_option
 * @property string $email_quiz_grade_subject
 * @property string $email_quiz_grade_body
 * @property string $email_complete_course_grade_summary_subject
 * @property string $email_complete_course_grade_summary_body
 * @property string $email_complete_unit_option_admin
 * @property string $email_complete_unit_option
 * @property string $email_complete_unit_subject
 * @property string $email_complete_unit_body
 * @property string $email_unit_unlocked_subject
 * @property string $email_unit_unlocked_body
 * @property string $cert_signature_type
 * @property string $cert_sig_text
 * @property string $cert_sig_image_url
 * @property string $cert_logo_enabled
 * @property string $cert_logo_url
 * @property string $cert_background_type
 * @property string $cert_background_custom_url
 * @property string $payments_type
 * @property string $payments_price
 * @property string $payments_interval
 * @property string $course_bundles
 * @property string $installments_enabled
 * @property int $installments_number
 * @property string $installments_amount
 * @property string $installments_interval
 * @property string $course_access_expiration
 * @property string $cae_specific_date
 * @property string $cae_interval_date_int_expire
 * @property string $cae_interval_date_string_expire
 * @property string $cae_reset_on_course_expire
 * @property string $certificate_template
 */
class Course extends Model {

	/**
	 * @var DB_Courses The courses database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var DB_Course_Meta The courses meta database.
	 * @sicn
	 */
	protected $meta_db;

	/**
	 * @var int
	 * @since 4.1.0
	 */
	public $course_id;

	/**
	 * @var int
	 * @since 4.4.0
	 */
	public $course_post_id;

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_title = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_desc = '';

	/**
	 * @var int
	 * @since 4.1.0
	 */
	public $course_author = 0;

	/**
	 * @var string The Course status.
	 * @since 4.4.0
	 */
	public $course_status = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_opt_completion_wall = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_opt_use_certificate = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_opt_user_access = '';

	/**
	 * @var int
	 * @since 4.1.0
	 */
	public $course_unit_count = 0;

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_from_name = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_from_email = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_to_email = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_opt_prerequisites = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_unit_complete = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_course_complete = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_unit_not_logged_in = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_unit_pending = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_unit_no_access = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_prerequisite_not_met = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_unit_not_yet = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_unit_not_yet_dripfeed = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_quiz_open_grading_blocking = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $course_message_quiz_open_grading_non_blocking = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_module_option_admin = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_module_option = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_module_subject = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_module_body = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_course_option_admin = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_course_option = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_course_subject = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_course_body = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_quiz_grade_option = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_quiz_grade_subject = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_quiz_grade_body = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_course_grade_summary_subject = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_complete_course_grade_summary_body = '';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $email_complete_unit_option_admin = '';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $email_complete_unit_option = '';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $email_complete_unit_subject = '';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $email_complete_unit_body = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_unit_unlocked_subject = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $email_unit_unlocked_body = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_signature_type = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_sig_text = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_sig_image_url = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_logo_enabled = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_logo_url = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_background_type = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $cert_background_custom_url = '';

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $payments_type = 'free';

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $payments_price = '0.00';

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $payments_interval = 'month';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $course_bundles = '';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $installments_enabled = 'no';

	/**
	 * @var bool Charge Installments?
	 * @since 4.6.0
	 */
	public $charge_installments = false;

	/**
	 * @var int
	 * @since 4.6.0
	 */
	public $installments_number = 2;

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $installments_amount = '0.00';

	/**
	 * @var string
	 * @since 4.6.0
	 */
	public $installments_interval = 'month';

	/**
	 * @var string
	 * @since 4.6.2
	 */
	public $unit_advancement = 'manual';

	/**
	 * @var string
	 * @since 4.8.0
	 */
	public $course_access_expiration = 'none';

	/**
	 * @var string
	 * @since 4.8.0
	 */
	public $cae_specific_date = '0000-00-00 00:00:00';

	/**
	 * @var int
	 * @since 4.8.0
	 */
	public $cae_interval_date_int_expire = 1;

	/**
	 * @var string
	 * @since 4.8.0
	 */
	public $cae_interval_date_string_expire = 'hour';

	/**
	 * @var string
	 * @since 4.8.0
	 */
	public $cae_reset_on_course_expire = 'no';

	/**
	 * @var int
	 * @since 4.8.0
	 */
	public $certificate_template = 0;

	/**
	 * @var string The post type slug.
	 * @since 4.4.0
	 */
	protected $post_type_slug = 'wpcw_course';

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
		'post_type'             => 'wpcw_course',
		'post_mime_type'        => '',
		'comment_count'         => '',
	);

	/**
	 * @var array The post data map.
	 * @since 4.4.0
	 */
	protected $post_data_map = array(
		'course_title'  => 'post_title',
		'course_desc'   => 'post_content',
		'course_status' => 'post_status',
		'course_author' => 'post_author',
	);

	/**
	 * @var bool Setup by post id.
	 * @since 4.4.0
	 */
	protected $setup_by_post_id = false;

	/**
	 * Course Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The data to setup the course.
	 */
	public function __construct( $data = array(), $setup_by_post_id = false ) {
		$this->db      = new DB_Courses();
		$this->meta_db = new DB_Course_Meta();

		$this->setup_by_post_id = $setup_by_post_id;

		parent::__construct( $data );
	}

	/**
	 * Setup Model Object.
	 *
	 * @since 4.3.0
	 *
	 * @param int $data The data id.
	 */
	public function setup( $data ) {
		if ( 0 === absint( $data ) ) {
			return;
		}

		if ( $this->setup_by_post_id ) {
			if ( ! $data_object = $this->db->get_by( 'course_post_id', $data ) ) {
				return;
			}
		} else {
			if ( ! $data_object = $this->db->get( $data ) ) {
				$data_object_by_post_id = $this->db->get_by( 'course_post_id', $data );

				if ( ! $data_object_by_post_id ) {
					return;
				}

				$data_object = $data_object_by_post_id;
			}
		}

		if ( $data_object && is_object( $data_object ) ) {
			$this->set_data( $data_object );
		}
	}

	/**
	 * Set Course Data.
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
		}
	}

	/**
	 * Set Course Properties.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key The property key.
	 * @param mixed $value The property value.a
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
	 * Set Course Properties.
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
	 * Create Course.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The data to insert upon creation.
	 * @param bool $create_post Should the post be created. Defaul is true.
	 *
	 * @return int|bool $course_id The course id or false otherwise.
	 */
	public function create( $data = array(), $create_post = true ) {
		if ( empty( $this->db ) || ! $this->db->get_primary_key() ) {
			return;
		}

		$defaults = $this->get_defaults();
		$data     = wp_parse_args( $data, $defaults );

		if ( $course_id = $this->db->insert_course( $data ) ) {
			$this->set_prop( 'course_id', $course_id );
			$this->set_data( $data );

			if ( $create_post ) {
				$this->create_post();
			}
		}

		return $course_id;
	}

	/**
	 * Create Inital Post.
	 *
	 * @since 4.4.0
	 *
	 * @param array $post_data The post data.
	 *
	 * @param int $course_post_id The course post id.
	 */
	public function create_post( $post_data = array() ) {
		$data = $this->get_data( true );

		foreach ( $data as $key => $value ) {
			if ( array_key_exists( $key, $this->post_data ) ) {
				$post_data[ $key ] = $value;
			}

			if ( array_key_exists( $key, $this->post_data_map ) ) {
				$post_data[ $this->post_data_map[ $key ] ] = $value;
			}
		}

		// Post Defaults.
		$post_defaults = $this->get_post_defaults();
		$post_data     = wp_parse_args( $post_data, $post_defaults );

		// Insert Post.
		remove_all_actions( 'save_post_wpcw_course', 10 );
		$course_post_id = wp_insert_post( $post_data );

		if ( ! is_wp_error( $course_post_id ) ) {
			$this->set_data( $data );
			$this->set_prop( 'course_post_id', $course_post_id );
			$this->prime_post_data();
			$this->maybe_enroll_author();
			$this->save( false );
		} else {
			$this->log( $course_post_id->get_error_message() );
		}

		return $course_post_id;
	}

	/**
	 * Get Course Defaults.
	 *
	 * @since 4.4.0
	 *
	 * @return array The course defaults.
	 */
	public function get_defaults() {
		return array(
			'course_author'                                 => get_current_user_id(),
			'course_unit_count'                             => 0,

			// Add basic Email Template to defaults when creating a new course.
			'email_complete_course_subject'                 => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_COURSE_SUBJECT' ),
			'email_complete_module_subject'                 => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_MODULE_SUBJECT' ),
			'email_complete_unit_subject'                   => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_SUBJECT' ),
			'email_quiz_grade_subject'                      => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_QUIZ_GRADE_SUBJECT' ),
			'email_complete_course_grade_summary_subject'   => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_SUBJECT' ),
			'email_unit_unlocked_subject'                   => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_UNIT_UNLOCKED_SUBJECT' ),

			// Email bodies
			'email_complete_course_body'                    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_COURSE_BODY' ),
			'email_complete_module_body'                    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_MODULE_BODY' ),
			'email_complete_unit_body'                      => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_BODY' ),
			'email_quiz_grade_body'                         => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_QUIZ_GRADE_BODY' ),
			'email_complete_course_grade_summary_body'      => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_BODY' ),
			'email_unit_unlocked_body'                      => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_UNIT_UNLOCKED_BODY' ),

			// Email address details
			'course_from_name'                              => wpcw_course_get_default_email_from_name(),
			'course_from_email'                             => wpcw_course_get_default_email_from_email(),
			'course_to_email'                               => wpcw_course_get_default_email_to_email(),

			// Completion wall default (blocking mode)
			'course_opt_completion_wall'                    => 'completion_wall',
			'course_opt_user_access'                        => 'default_show',

			// Email notification defaults (yes to send email)
			'email_complete_course_option_admin'            => 'send_email',
			'email_complete_course_option'                  => 'send_email',
			'email_complete_module_option_admin'            => 'send_email',
			'email_complete_module_option'                  => 'send_email',
			'email_complete_unit_option_admin'              => 'no_email',
			'email_complete_unit_option'                    => 'no_email',
			'email_quiz_grade_option'                       => 'send_email',

			// Certificate defaults
			'course_opt_use_certificate'                    => 'no_certs',
			'cert_signature_type'                           => 'text',
			'cert_sig_text'                                 => get_bloginfo( 'name' ),
			'cert_sig_image_url'                            => '',
			'cert_logo_enabled'                             => 'no_cert_logo',
			'cert_logo_url'                                 => '',
			'cert_background_type'                          => 'use_default',

			// User Messages
			'course_message_unit_not_yet'                   => __( 'You need to complete the previous unit first.', 'wp-courseware' ),
			'course_message_unit_pending'                   => __( 'Have you completed this unit? Then mark this unit as completed.', 'wp-courseware' ),
			'course_message_unit_complete'                  => __( 'You have now completed this unit.', 'wp-courseware' ),
			'course_message_course_complete'                => __( 'You have now completed the whole course. Congratulations!', 'wp-courseware' ),
			'course_message_unit_no_access'                 => __( 'Sorry, but you\'re not allowed to access this course.', 'wp-courseware' ),
			'course_message_prerequisite_not_met'           => __( 'This course can not be accessed until the prerequisites for this course are complete.', 'wp-courseware' ),
			'course_message_unit_not_logged_in'             => __( 'You cannot view this unit as you\'re not logged in yet.', 'wp-courseware' ),
			'course_message_unit_not_yet_dripfeed'          => __( 'This unit isn\'t available just yet. Please check back in about {UNIT_UNLOCKED_TIME}.', 'wp-courseware' ),

			// User Messages - quizzes
			'course_message_quiz_open_grading_blocking'     => __( 'Your quiz has been submitted for grading by the course instructor. Once your grade has been entered, you will be able to access the next unit.', 'wp-courseware' ),
			'course_message_quiz_open_grading_non_blocking' => __( 'Your quiz has been submitted for grading by the course instructor. You have now completed this unit.', 'wp-courseware' ),

			// Payments
			'payments_type'                                 => 'free',
			'payments_price'                                => '0.00',
			'payments_interval'                             => 'month',

			// Course Bundles
			'course_bundles'                                => '',

			// Installments
			'installments_enabled'                          => 'no',
			'installments_number'                           => 2,
			'installments_amount'                           => '0.00',
			'installments_interval'                         => 'month',

			// Course Post Id.
			'course_post_id'                                => 0,

			// Course access expiration.
			'course_access_expiration'                      => 'none',
			'cae_specific_date'                             => '0000-00-00 00:00:00',
			'cae_interval_date_int_expire'                  => 1,
			'cae_interval_date_string_expire'               => 'hour',
			'cae_reset_on_course_expire'                    => 'no',

			// Course Certificate Template
			'certificate_template'                    => 0,
		);
	}

	/**
	 * Get Course Post Defaults.
	 *
	 * @since 4.4.0
	 *
	 * @return array The course post defaults.
	 */
	public function get_post_defaults() {
		return array(
			'post_title'   => $this->get_course_title(),
			'post_content' => $this->get_course_desc(),
			'post_type'    => $this->post_type_slug,
			'post_status'  => 'publish',
			'post_author'  => $this->get_course_author(),
		);
	}

	/**
	 * Get Course Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int|void
	 */
	public function get_id() {
		return absint( $this->get_course_id() );
	}

	/**
	 * Get course_id
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_course_id() {
		return absint( $this->course_id );
	}

	/**
	 * Get Course Post Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int
	 */
	public function get_course_post_id() {
		return absint( $this->course_post_id );
	}

	/**
	 * Get Course Post Status.
	 *
	 * @since 4.8.8
	 *
	 * @return int
	 */
	public function get_course_post_status() {
		return get_post_status( $this->course_post_id );
	}

	/**
	 * Get course_title
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_title() {
		return wp_kses_post( $this->course_title );
	}

	/**
	 * Get course_desc
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_desc() {
		return wp_kses_post( $this->course_desc );
	}

	/**
	 * Get Course Desc Shortened.
	 *
	 * @since 4.6.0
	 *
	 * @param int $words The number of words the short description should be.
	 *
	 * @return string The course description shortened.
	 */
	public function get_course_desc_shortened( $words = 20 ) {
		return wp_trim_words( $this->get_course_desc(), $words, '...' );
	}

	/**
	 * Get course_author
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_course_author() {
		return absint( $this->course_author );
	}

	/**
	 * Get Course Instructor.
	 *
	 * @since 4.5.2
	 *
	 * @return int
	 */
	public function get_course_instructor() {
		return $this->get_course_author();
	}

	/**
	 * Get Course Status.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course status.
	 */
	public function get_course_status() {
		if ( empty( $this->course_status ) ) {
			$this->course_status = $this->get_post_value( 'post_status' );
		}

		return $this->course_status;
	}

	/**
	 * Get Course Slug.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course slug.
	 */
	public function get_course_slug() {
		$course_slug   = $this->get_post_value( 'post_name' );
		$course_status = $this->get_course_status();

		if ( ! $course_slug && in_array( $course_status, array( 'draft', 'pending', 'future' ) ) ) {
			$course_id    = $this->get_course_post_id();
			$course_title = $this->get_course_title();
			$course_slug  = sanitize_title( $course_slug ? $course_slug : $course_title, $course_id );
		}

		return $course_slug;
	}

	/**
	 * Get course_opt_completion_wall
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_opt_completion_wall() {
		return $this->course_opt_completion_wall;
	}

	/**
	 * Get course_opt_use_certificate
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_opt_use_certificate() {
		return $this->course_opt_use_certificate;
	}

	/**
	 * Get course_opt_user_access
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_opt_user_access() {
		return $this->course_opt_user_access;
	}

	/**
	 * Get Course User Access Message.
	 *
	 * @since 4.3.0
	 *
	 * @return string $message The user access message.
	 */
	public function get_course_opt_user_access_message() {
		$message = esc_html__( 'Give new users access by default', 'wp-courseware' );

		if ( $this->is_purchasable() ) {
			if ( $this->is_subscription() ) {
				$message = esc_html__( 'New users given access if they have an active subscription', 'wp-courseware' );
			} else {
				$message = esc_html__( 'New users given access if they have paid for this course', 'wp-courseware' );
			}
		}

		return apply_filters( 'wpcw_course_user_access_message', $message );
	}

	/**
	 * Get course_unit_count
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_course_unit_count() {
		return $this->course_unit_count;
	}

	/**
	 * Get course_from_name
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_from_name() {
		return $this->course_from_name;
	}

	/**
	 * Get course_from_email
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_from_email() {
		return $this->course_from_email;
	}

	/**
	 * Get course_to_email
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_to_email() {
		return $this->course_to_email;
	}

	/**
	 * Get course_opt_prerequisites
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_opt_prerequisites() {
		return $this->course_opt_prerequisites;
	}

	/**
	 * Get course_message_unit_complete
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_unit_complete() {
		return $this->course_message_unit_complete;
	}

	/**
	 * Get course_message_course_complete
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_course_complete() {
		return $this->course_message_course_complete;
	}

	/**
	 * Get course_message_unit_not_logged_in
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_unit_not_logged_in() {
		return $this->course_message_unit_not_logged_in;
	}

	/**
	 * Get course_message_unit_pending
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_unit_pending() {
		return $this->course_message_unit_pending;
	}

	/**
	 * Get course_message_unit_no_access
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_unit_no_access() {
		return $this->course_message_unit_no_access;
	}

	/**
	 * Get course_message_prerequisite_not_met
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_prerequisite_not_met() {
		return $this->course_message_prerequisite_not_met;
	}

	/**
	 * Get course_message_unit_not_yet
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_unit_not_yet() {
		return $this->course_message_unit_not_yet;
	}

	/**
	 * Get course_message_unit_not_yet_dripfeed
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_unit_not_yet_dripfeed() {
		return $this->course_message_unit_not_yet_dripfeed;
	}

	/**
	 * Get course_message_quiz_open_grading_blocking
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_quiz_open_grading_blocking() {
		return $this->course_message_quiz_open_grading_blocking;
	}

	/**
	 * Get course_message_quiz_open_grading_non_blocking
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_course_message_quiz_open_grading_non_blocking() {
		return $this->course_message_quiz_open_grading_non_blocking;
	}

	/**
	 * Get email_complete_module_option_admin
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_module_option_admin() {
		return $this->email_complete_module_option_admin;
	}

	/**
	 * Get email_complete_module_option
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_module_option() {
		return $this->email_complete_module_option;
	}

	/**
	 * Get email_complete_module_subject
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_module_subject() {
		return $this->email_complete_module_subject;
	}

	/**
	 * Get email_complete_module_body
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_module_body() {
		return $this->email_complete_module_body;
	}

	/**
	 * Get email_complete_course_option_admin
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_course_option_admin() {
		return $this->email_complete_course_option_admin;
	}

	/**
	 * Get email_complete_course_option
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_course_option() {
		return $this->email_complete_course_option;
	}

	/**
	 * Get email_complete_course_subject
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_course_subject() {
		return $this->email_complete_course_subject;
	}

	/**
	 * Get email_complete_course_body
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_course_body() {
		return $this->email_complete_course_body;
	}

	/**
	 * Get email_quiz_grade_option
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_quiz_grade_option() {
		return $this->email_quiz_grade_option;
	}

	/**
	 * Get email_quiz_grade_subject
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_quiz_grade_subject() {
		return $this->email_quiz_grade_subject;
	}

	/**
	 * Get email_quiz_grade_body
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_quiz_grade_body() {
		return $this->email_quiz_grade_body;
	}

	/**
	 * Get email_complete_course_grade_summary_subject
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_course_grade_summary_subject() {
		return $this->email_complete_course_grade_summary_subject;
	}

	/**
	 * Get email_complete_course_grade_summary_body
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_course_grade_summary_body() {
		return $this->email_complete_course_grade_summary_body;
	}

	/**
	 * Get email_complete_unit_option_admin
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_unit_option_admin() {
		return $this->email_complete_unit_option_admin;
	}

	/**
	 * Get email_complete_unit_option
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_unit_option() {
		return $this->email_complete_unit_option;
	}

	/**
	 * Get email_complete_unit_subject
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_unit_subject() {
		return $this->email_complete_unit_subject;
	}

	/**
	 * Get email_complete_unit_body
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_complete_unit_body() {
		return $this->email_complete_unit_body;
	}

	/**
	 * Get email_unit_unlocked_subject
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_unit_unlocked_subject() {
		return $this->email_unit_unlocked_subject;
	}

	/**
	 * Get email_unit_unlocked_body
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_email_unit_unlocked_body() {
		return $this->email_unit_unlocked_body;
	}

	/**
	 * Get cert_signature_type
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_signature_type() {
		return $this->cert_signature_type;
	}

	/**
	 * Get cert_sig_text
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_sig_text() {
		return $this->cert_sig_text;
	}

	/**
	 * Get cert_sig_image_url
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_sig_image_url() {
		return $this->cert_sig_image_url;
	}

	/**
	 * Get cert_logo_enabled
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_logo_enabled() {
		return $this->cert_logo_enabled;
	}

	/**
	 * Get cert_logo_url
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_logo_url() {
		return $this->cert_logo_url;
	}

	/**
	 * Get cert_background_type
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_background_type() {
		return $this->cert_background_type;
	}

	/**
	 * Get cert_background_custom_url
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_cert_background_custom_url() {
		return $this->cert_background_custom_url;
	}

	/**
	 * Get Course Payments Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string The course payments type.
	 */
	public function get_payments_type() {
		return 'one-time' === $this->payments_type && $this->charge_installments()
			? 'subscription'
			: esc_attr( $this->payments_type );
	}

	/**
	 * Get Course Payments Price.
	 *
	 * @since 4.3.0
	 *
	 * @return string The course payments price.
	 */
	public function get_payments_price() {
		return $this->charge_installments() ? $this->get_installments_amount() : $this->payments_price;
	}

	/**
	 * Get Course Payments Price Refunded.
	 *
	 * @since 4.3.0
	 *
	 * @return string The course payments price.
	 */
	public function get_payments_price_refunded() {
		return $this->get_payments_price() * - 1;
	}

	/**
	 * Get Course Bundles.
	 *
	 * @since 4.6.0
	 *
	 * @return string|array The course bundles.
	 */
	public function get_course_bundles() {
		return $this->course_bundles ? maybe_unserialize( $this->course_bundles ) : '';
	}

	/**
	 * Get Course Payments Interval.
	 *
	 * @since 4.3.0
	 *
	 * @return string The course payments interval.
	 */
	public function get_payments_interval() {
		return $this->charge_installments() ? $this->get_installments_interval() : $this->payments_interval;
	}

	/**
	 * Get Installments Enabled.
	 *
	 * @since 4.6.0
	 *
	 * @return string Are installments enabled? Default is 'no'
	 */
	public function get_installments_enabled() {
		return $this->installments_enabled;
	}

	/**
	 * Are Installments Enabled?
	 *
	 * @since 4.6.0
	 *
	 * @return bool Are installments enabled? Default is false.
	 */
	public function are_installments_enabled() {
		return $this->get_installments_enabled() === 'yes' && ( $this->get_installments_amount() > 0 )
			? true
			: false;
	}

	/**
	 * Get Installments Number.
	 *
	 * @since 4.6.0
	 *
	 * @return int The installments number.
	 */
	public function get_installments_number() {
		return $this->installments_number;
	}

	/**
	 * Get Installments Amount.
	 *
	 * @since 4.6.0
	 *
	 * @return string The installments amount.
	 */
	public function get_installments_amount() {
		return $this->installments_amount;
	}

	/**
	 * Get Installments Amount Label.
	 *
	 * @since 4.6.0
	 *
	 * @return string The installments amount label.
	 */
	public function get_installments_amount_label() {
		return wpcw_price( $this->get_installments_amount() );
	}

	/**
	 * Get Installments Interval.
	 *
	 * @since 4.6.0
	 *
	 * @return string The installments interval.
	 */
	public function get_installments_interval() {
		return $this->installments_interval;
	}

	/**
	 * Get Installment Interval Label.
	 *
	 * @since 4.6.0
	 *
	 * @return string The installments interval label.
	 */
	public function get_installments_interval_label() {
		$periods = wpcw()->subscriptions->get_periods();

		$interval = $this->get_installments_interval();

		return isset( $periods[ $interval ] ) ? $periods[ $interval ] : $interval;
	}

	/**
	 * Get Installments Label.
	 *
	 * @since 4.6.0
	 *
	 * @return string The installments label.
	 */
	public function get_installments_label() {
		/**
		 * Filter: Course Installments Label.
		 *
		 * @since 4.6.0
		 *
		 * @param string The installment plan label.
		 * @param Course $course The course model object.
		 * @param Subscription $this The subscription model object.
		 *
		 * @return string The installment plan label.
		 */
		return apply_filters( 'wpcw_course_installments_label', sprintf(
			esc_html__( '%d %s Installments of %s', 'wp-courseware' ),
			$this->get_installments_number(),
			$this->get_installments_interval_label(),
			$this->get_installments_amount_label()
		) );
	}

	/**
	 * Get Unit Advancement.
	 *
	 * @since 4.6.2
	 *
	 * @return string Value of 'manual' or 'automatic' Default is manual.
	 */
	public function get_unit_advancement() {
		return $this->unit_advancement;
	}

	/**
	 * Get Course Access Expiration.
	 *
	 * @since 4.8.0
	 *
	 * @return string Value of 'specific_date' or 'specific_interval' Default is none.
	 */
	public function get_course_access_expiration() {
		return $this->course_access_expiration;
	}

	/**
	 * Get Course Access Expiration Specific date.
	 *
	 * @since 4.8.0
	 *
	 * @return string Value of selected date. Default is ''.
	 */
	public function get_cae_specific_date() {
		return $this->cae_specific_date;
	}

	/**
	 * Get Course Access Expiration interval date integer number.
	 *
	 * @since 4.8.0
	 *
	 * @return number integer number. Default is 1.
	 */
	public function get_cae_interval_date_int_expire() {
		return $this->cae_interval_date_int_expire;
	}

	/**
	 * Get Course Access Expiration date string.
	 *
	 * @since 4.8.0
	 *
	 * @return string Value of 'hour' or 'day' or 'week' or 'month' or 'year' Default is hour.
	 */
	public function get_cae_interval_date_string_expire() {
		return $this->cae_interval_date_string_expire;
	}

	/**
	 * Get Course Certificate template.
	 *
	 * @since 4.8.0
	 *
	 * @return number integer number. Default is 0.
	 */
	public function get_certificate_template() {
		return $this->certificate_template;
	}

	/**
	 * Get Course Date Interval Number.
	 *
	 * @since 4.4.0
	 *
	 * @return int The course date interval.
	 */
	public function get_course_date_interval_number( $interval, $interval_type ) {
		$interval_number = 0;

		switch ( $interval_type ) {
			case 'hour':
				$interval_number = $interval / WPCW_TIME_HR_IN_SECS;
				break;

			case 'day':
				$interval_number = $interval / WPCW_TIME_DAY_IN_SECS;
				break;

			case 'week':
				$interval_number = $interval / WPCW_TIME_WEEK_IN_SECS;
				break;

			case 'month':
				$interval_number = $interval / WPCW_TIME_MONTH_IN_SECS;
				break;

			case 'year':
				$interval_number = $interval / WPCW_TIME_YEAR_IN_SECS;
				break;
		}

		return $interval_number;
	}

	/**
	 * Get Course Access Expiration reset on course expire.
	 *
	 * @since 4.8.0
	 *
	 * @return string Value of 'yes' or 'no' Default is no.
	 */
	public function get_cae_reset_on_course_expire() {
		return $this->cae_reset_on_course_expire;
	}

	/**
	 * Is Course a Subscription?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if it is, false otherwise.
	 */
	public function is_subscription() {
		return 'subscription' === $this->get_payments_type();
	}

	/**
	 * Use Installments?
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function charge_installments() {
		return ( $this->are_installments_enabled() && $this->charge_installments )
			? true
			: false;
	}

	/**
	 * Course Closed?
	 *
	 * @since 4.6.4
	 *
	 * @return bool True if is closed, false otherwise.
	 */
	public function is_closed() {
		return 'closed' === $this->get_course_opt_user_access() ? true : false;
	}

		/**
	 * Manual Enrollment?
	 *
	 * @since 4.8.1
	 *
	 * @return bool True if is closed, false otherwise.
	 */
	public function is_manual() {
		return 'default_hide' === $this->get_course_opt_user_access() ? true : false;
	}

	/**
	 * Can Course be Purchased?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if it can be purchased, false otherwise.
	 */
	public function is_purchasable() {
		$payments_type = $this->get_payments_type();

		return ( ! $this->is_closed() && ! empty( $payments_type ) && 'free' !== $payments_type ) ? true : false;
	}

	/**
	 * Can User Access Course.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id The user id.
	 *
	 * @return bool True if the user can acccess a course. False otherwise.
	 */
	public function can_user_access( $user_id = 0 ) {
		global $wpdb, $wpcwdb;

		$course_id = $this->get_course_id();

		if ( empty( $user_id ) || empty( $course_id ) ) {
			return false;
		}

		// Set the Course Id.
		$course_id = $this->get_course_id();

		// MySQL query to check for access in the user courses table.
		$can_access = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpcwdb->user_courses}
			 WHERE user_id = %d
			 AND course_id = %d LIMIT 1;",
			absint( $user_id ),
			absint( $course_id )
		) );

		/**
		 * Legacy Filter: Can user Access Course.
		 *
		 * @since 4.3.0
		 *
		 * @param object $can_access The access check result.
		 * @param int $course_id The course id.
		 * @param int $user_id The user id.
		 *
		 * @return object $can_access The mysql result.
		 */
		$can_access = apply_filters( 'wpcw_courses_canuseraccesscourse', $can_access, $course_id, $user_id );

		/**
		 * Filter: Can User Access Course.
		 *
		 * @since 4.4.0
		 *
		 * @param object $can_access The access check result.
		 * @param int $course_id The course id.
		 * @param int $user_id The user id.
		 *
		 * @return object $can_access The access result.
		 */
		return (bool) apply_filters( 'wpcw_courses_can_user_access', $can_access, $course_id, $user_id );
	}

	/**
	 * Enroll Course Author.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True on successfull enrollment. False otherwise.
	 */
	public function maybe_enroll_author() {
		if ( ! $this->can_user_access( absint( $this->get_course_author() ) ) ) {
			wpcw()->enrollment->enroll_student( $this->get_course_author(), array( $this->get_id() ), 'add', true );
		}
	}

	/**
	 * Get Subscription Interval Price
	 *
	 * @since 4.3.0
	 */
	public function get_subscription_interval() {
		$periods = wpcw()->subscriptions->get_periods();

		$interval = $this->get_payments_interval();

		return isset( $periods[ $interval ] ) ? $periods[ $interval ] : $interval;
	}

	/**
	 * Get Price Label.
	 *
	 * @since 4.3.0
	 *
	 * @return string The price label.
	 */
	public function get_price_label() {
		$price = wpcw_price( $this->get_payments_price() );

		if ( $this->is_subscription() && ! $this->charge_installments() ) {
			$price = sprintf( '%s / %s', $price, $this->get_subscription_interval() );
		}

		return $price;
	}

	/**
	 * Get Course Modules.
	 *
	 * @since 4.4.0
	 *
	 * @return array $modules An array of Module objects.
	 */
	public function get_modules( $args = array() ) {
		$defaults = array(
			'number'    => 1000,
			'course_id' => $this->get_id(),
			'orderby'   => array( 'module_order', 'module_title' ),
			'order'     => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		return wpcw_get_modules( $args );
	}

	/**
	 * Get Course Units.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args The query args.
	 */
	public function get_units( $args = array() ) {
		$defaults = array(
			'number'           => - 1,
			'parent_course_id' => $this->get_id(),
		);

		$args = wp_parse_args( $args, $defaults );

		return wpcw_get_units( $args );
	}

	/**
	 * Get Course Quizzes.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args The query args.
	 */
	public function get_quizzes( $args = array() ) {
		$defaults = array( 'parent_course_id' => $this->get_id() );

		$args = wp_parse_args( $args, $defaults );

		return wpcw_get_quizzes( $args );
	}

	/**
	 * Prime Post Data.
	 *
	 * @since 4.4.0
	 */
	protected function prime_post_data() {
		if ( ! $this->get_course_post_id() ) {
			return;
		}

		$post = get_post( $this->get_course_post_id() );

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
		if ( ! $this->get_course_post_id() ) {
			return;
		}

		if ( empty( $this->post_data ) || 0 === $this->post_data['ID'] ) {
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
		if ( ! $this->get_course_id() ) {
			return;
		}

		$post_data = $this->get_post_data();

		return isset( $post_data[ $key ] ) ? $post_data[ $key ] : false;
	}

	/**
	 * Get a Course Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return $this->meta_db->get_meta( $this->get_course_id(), $meta_key, $single );
	}

	/**
	 * Add Course Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Metadata value.
	 * @param bool $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function add_meta( $meta_key = '', $meta_value = null, $unique = false ) {
		return $this->meta_db->add_meta( $this->get_course_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Course Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @param mixed $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, true if success.
	 */
	public function update_meta( $meta_key = '', $meta_value = null, $prev_value = '' ) {
		return $this->meta_db->update_meta( $this->get_course_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Course Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return $this->meta_db->delete_meta( $this->get_course_id(), $meta_key, $meta_value );
	}

	/**
	 * Get a Course Post Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_post_meta( $meta_key = '', $single = true ) {
		return get_post_meta( $this->get_course_id(), $meta_key, $single );
	}

	/**
	 * Add Course Post Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Metadata value.
	 * @param bool $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function add_post_meta( $meta_key = '', $meta_value = null, $unique = false ) {
		return add_post_meta( $this->get_course_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Post Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @param mixed $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, true if success.
	 */
	public function update_post_meta( $meta_key = '', $meta_value = null, $prev_value = '' ) {
		return update_post_meta( $this->get_course_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Course Meta Field.
	 *
	 * @since 4.4.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_post_meta( $meta_key = '', $meta_value = '' ) {
		return delete_post_meta( $this->get_unit_id(), $meta_key, $meta_value );
	}

	/**
	 * Delete All Meta Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function delete_all_meta() {
		return $this->meta_db->delete_all_meta( $this->get_course_id() );
	}

	/**
	 * Save Course.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $save_post Should the course post be saved? Default is true.
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

			wp_update_post( $this->get_post_data() );
		}

		return $this->get_id();
	}

	/**
	 * Delete Course.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $delete_post Should we delete the course post? Default is true.
	 *
	 * @return True on success, false on failure.
	 */
	public function delete( $delete_post = true ) {
		global $wpdb, $wpcwdb;

		$deleted = $this->db->delete( $this->get_course_id() );

		if ( $deleted ) {
			// Delete Meta
			$this->delete_all_meta();

			// Delete Post.
			if ( $delete_post ) {
				wp_delete_post( $this->get_id(), true );
			}

			// Disassociate quizzes, units, etc.
			if ( $units = $this->get_units() ) {
				/** @var Unit $unit */
				foreach ( $units as $unit ) {
					$unit->disconnect();
				}
			}

			// Delete Modules
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->modules WHERE parent_course_id = %d", $this->get_id() ) );

			// Delete Certificates.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->certificates WHERE cert_course_id = %d", $this->get_id() ) );

			// Delete User Course Progress
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE course_id = %d", $this->get_id() ) );

			/**
			 * Action: Course Deleted.
			 *
			 * @since 4.4.0
			 *
			 * @param Course $this The course object.
			 */
			do_action( 'wpcw_course_deleted', $this );
		}

		return $deleted;
	}

	/**
	 * Get Course Outline.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args The course outline args.
	 */
	public function get_outline( $args = array() ) {
		$defaults = array(
			'course'          => $this->get_id(),
			'show_title'      => false,
			'show_desc'       => false,
			'module'          => '',
			'module_desc'     => false,
			'user_quiz_grade' => false,
		);

		$defaults = apply_filters( 'wpcw_course_single_outline_args', $defaults , $this );

		$args = wp_parse_args( $args, $defaults );

		return wpcw()->shortcodes->course_shortcode( $args );
	}

	/**
	 * Get Thumbnail Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int The Course Thumbnail Id.
	 */
	public function get_thumbnail_id() {
		return get_post_thumbnail_id( $this->get_course_post_id() );
	}

	/**
	 * Get Thumbnail Url.
	 *
	 * @since 4.4.0
	 *
	 * @param string $size The post thumbnail size. Default is 'post-thumbnail'
	 *
	 * @return string The course thumbnail url.
	 */
	public function get_thumbnail_url( $size = 'post-thumbnail' ) {
		return get_the_post_thumbnail_url( $this->get_course_post_id(), $size );
	}

	/**
	 * Get Course Thumnail Image.
	 *
	 * @since 4.4.0
	 *
	 * @param string $size The post thumbnail size. Default is 'post-thumbnail'
	 *
	 * @return string The course thumbnail image html.
	 */
	public function get_thumbnail_image( $size = 'post-thumbnail' ) {
		return apply_filters( 'wpcw_course_thumbnail_image', get_the_post_thumbnail( $this->get_course_post_id(), $size ) );
	}

	/**
	 * Get Enrollment Button.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args The enrollment button args.
	 *
	 * @return string The enrollment content.
	 */
	public function get_enrollment_button( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'courses'          => array( $this->get_id() ),
			'display_messages' => false,
			'display_raw'      => false,
		) );

		return wpcw()->shortcodes->course_enroll_shortcode( $args );
	}

	/**
	 * Get Course Permalink.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course permalink.
	 */
	public function get_permalink() {
		return get_the_permalink( $this->get_course_post_id() );
	}

	/**
	 * Get Edit Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course edit url.
	 */
	public function get_edit_url() {
		return esc_url_raw( add_query_arg( array( 'post' => $this->get_course_post_id(), 'action' => 'edit' ), admin_url( 'post.php' ) ) );
	}

	/**
	 * Get Classroom Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The classroom url.
	 */
	public function get_classroom_url() {
		return esc_url_raw( add_query_arg( array( 'page' => 'wpcw-course-classroom', 'course_id' => $this->get_id() ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Get Gradebook Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The gradebook url.
	 */
	public function get_gradebook_url() {
		return esc_url_raw( add_query_arg( array( 'page' => 'WPCW_showPage_GradeBook', 'course_id' => $this->get_id() ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Get Legacy Course Builder Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The course edit url.
	 */
	public function get_legacy_course_builder_url() {
		return esc_url_raw( add_query_arg( array( 'page' => 'WPCW_showPage_CourseOrdering', 'course_id' => $this->get_id() ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Update Instructor.
	 *
	 * @since 4.5.2
	 *
	 * @param int $instructor_id The instructor id to will become the new instructor.
	 * @param bool $update_questions Update all associations with this course. Default is True.
	 */
	public function update_instructor( $instructor_id, $update_questions = true ) {
		$this->set_prop( 'course_author', absint( $instructor_id ) );

		if ( $modules = $this->get_modules() ) {
			/** @var Module $module */
			foreach ( $modules as $module ) {
				$module->update_author( $instructor_id );
			}
		}

		if ( $units = $this->get_units() ) {
			/** @var Unit $unit */
			foreach ( $units as $unit ) {
				$unit->update_author( $instructor_id );
			}
		}

		if ( $quizzes = $this->get_quizzes() ) {
			/** @var Quiz $quiz */
			foreach ( $quizzes as $quiz ) {
				$quiz->update_author( $instructor_id, $update_questions );
			}
		}

		$this->save();
	}
}
