<?php
/**
 * WP Courseware Student Model.
 *
 * @package WPCW
 * @subpackage Modles
 * @since 4.1.0
 */
namespace WPCW\Models;

use WPCW\Database\DB_Students;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Student.
 *
 * @since 4.1.0
 *
 * @property int $ID
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property int $user_status
 * @property string $display_name
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $billing_address_1
 * @property string $billing_address_2
 * @property string $billing_city
 * @property string $billing_postcode
 * @property string $billing_country
 * @property string $billing_state
 */
class Student extends Model {

	/**
	 * @var int
	 * @since 4.1.0
	 */
	public $ID;

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_login = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_pass = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_nicename = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_email = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_url = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_registered = '';

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $user_activation_key = '';

	/**
	 * @var int
	 * @since 4.1.0
	 */
	public $user_status = 0;

	/**
	 * @var string
	 * @since 4.1.0
	 */
	public $display_name = '';

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $first_name;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $last_name;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $email;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $billing_address_1;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $billing_address_2;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $billing_city;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $billing_postcode;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $billing_country;

	/**
	 * @var string
	 * @since 4.3.0
	 */
	public $billing_state;

	/**
	 * @var array An array of student orders.
	 * @since 4.3.0
	 */
	public $orders;

	/**
	 * @var array An array of student subscriptions.
	 * @since 4.3.0
	 */
	public $subscriptions;

	/**
	 * Student Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Students();
		parent::__construct( $data );
	}

	/**
	 * Setup Student Object.
	 *
	 * @since 4.3.0
	 *
	 * @param int $data The student Id.
	 */
	public function setup( $data ) {
		if ( 0 === absint( $data ) ) {
			return;
		}

		$data_object = $this->db->get( $data );

		if ( empty( $data_object ) ) {
			if ( ! ( $user = get_user_by( 'id', $data ) ) ) {
				return;
			}
			$data_object = $user->data;
		}

		if ( $data_object && is_object( $data_object ) ) {
			$this->set_data( $data_object );
		}

		if ( $data_object ) {
			$this->prime_meta_fields();
		}
	}

	/**
	 * Get ID.
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_ID() {
		return $this->ID;
	}

	/**
	 * Get user_login
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_login() {
		return $this->user_login;
	}

	/**
	 * Get user_pass
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_pass() {
		return $this->user_pass;
	}

	/**
	 * Get user_nicename
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_nicename() {
		return $this->user_nicename;
	}

	/**
	 * Get user_email
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_email() {
		return $this->user_email;
	}

	/**
	 * Get user_url
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_url() {
		return $this->user_url;
	}

	/**
	 * Get user_registered
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_registered() {
		return $this->user_registered;
	}

	/**
	 * Get user_activation_key
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_user_activation_key() {
		return $this->user_activation_key;
	}

	/**
	 * Get user_status
	 *
	 * @since 4.1.0
	 *
	 * @return int
	 */
	public function get_user_status() {
		return $this->user_status;
	}

	/**
	 * Get display_name
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_display_name() {
		return $this->display_name;
	}

	/**
	 * Get Email JSON.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_json() {
		return htmlspecialchars( wp_json_encode( array(
			'id'    => $this->get_ID(),
			'name'  => $this->get_display_name(),
			'email' => $this->get_user_email(),
		) ) );
	}

	/**
	 * Get Student Avatar.
	 *
	 * @since 4.2.0
	 *
	 * @return false|string
	 */
	public function get_avatar( $size = 96 ) {
		return get_avatar( $this->ID, $size );
	}

	/**
	 * Get Student User Edit Url.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_user_edit_url() {
		return esc_url( add_query_arg( array( 'user_id' => $this->get_ID() ), admin_url( 'user-edit.php' ) ) );
	}

	/**
	 * Get Detailed Progress Url.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_detailed_progress_url() {
		return esc_url( add_query_arg( array( 'page' => 'WPCW_showPage_UserProgess', 'user_id' => $this->get_ID() ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Get Update Access Url.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_update_access_url() {
		return esc_url( add_query_arg( array( 'page' => 'WPCW_showPage_UserCourseAccess', 'user_id' => $this->get_ID() ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Get First Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_first_name() {
		if ( empty( $this->first_name ) ) {
			$this->first_name = $this->get_meta( 'first_name', true );
		}

		return $this->first_name;
	}

	/**
	 * Get Last Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_last_name() {
		if ( empty( $this->last_name ) ) {
			$this->last_name = $this->get_meta( 'last_name', true );
		}

		return $this->last_name;
	}

	/**
	 * Get Full Name.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $display_name Return display name if first and last name does not exist. Default is false.
	 *
	 * @return string $full_name The first and last name combined.
	 */
	public function get_full_name( $display_name = false ) {
		$full_name = '';

		if ( $this->get_first_name() && $this->get_last_name() ) {
			$full_name = sprintf( '%s %s', $this->get_first_name(), $this->get_last_name() );
		}

		if ( empty( $full_name ) ) {
			$full_name = $display_name ? $this->get_display_name() : $this->get_user_nicename();
		}

		return $full_name;
	}

	/**
	 * Get Email.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_email() {
		if ( empty( $this->email ) ) {
			$this->email = $this->get_user_email();
		}

		return $this->email;
	}

	/**
	 * Get Billing Address 1.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_billing_address_1() {
		if ( empty( $this->billing_address_1 ) ) {
			$this->billing_address_1 = $this->get_meta( 'billing_address_1', true );
		}

		return $this->billing_address_1;
	}

	/**
	 * Get Billing Address 2.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_billing_address_2() {
		if ( empty( $this->billing_address_2 ) ) {
			$this->billing_address_2 = $this->get_meta( 'billing_address_2', true );
		}

		return $this->billing_address_2;
	}

	/**
	 * Get Billing City.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_billing_city() {
		if ( empty( $this->billing_city ) ) {
			$this->billing_city = $this->get_meta( 'billing_city', true );
		}

		return $this->billing_city;
	}

	/**
	 * Get Billing Postal Code.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_billing_postcode() {
		if ( empty( $this->billing_postcode ) ) {
			$this->billing_postcode = $this->get_meta( 'billing_postcode', true );
		}

		return $this->billing_postcode;
	}

	/**
	 * Get Billing Country.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_billing_country() {
		if ( empty( $this->billing_country ) ) {
			$this->billing_country = $this->get_meta( 'billing_country', true );
		}

		return $this->billing_country;
	}

	/**
	 * Get Billing State.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_billing_state() {
		if ( empty( $this->billing_state ) ) {
			$this->billing_state = $this->get_meta( 'billing_state', true );
		}

		return $this->billing_state;
	}

	/**
	 * Prime Billing Data.
	 *
	 * @since 4.3.0
	 *
	 * @return array $data The billing data.
	 */
	public function prime_meta_fields() {
		$meta_data = array();

		foreach ( $this->get_meta_fields() as $field ) {
			if ( is_callable( array( $this, "get_{$field}" ) ) ) {
				$meta_data[ $field ] = $this->{"get_{$field}"}();
			}
		}

		return $meta_data;
	}

	/**
	 * Get Meta Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|void
	 */
	public function get_meta_fields() {
		return apply_filters( 'wpcw_student_meta_fields', array(
			'first_name',
			'last_name',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_postcode',
			'billing_country',
			'billing_state',
		) );
	}

	/**
	 * Is Meta Field?
	 *
	 * @since 4.3.0
	 *
	 * @param $key
	 */
	public function is_meta_field( $key ) {
		return in_array( $key, $this->get_meta_fields() );
	}

	/**
	 * Get a Student Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return get_user_meta( $this->get_ID(), $meta_key, $single );
	}

	/**
	 * Add Student Meta Field.
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
		return add_user_meta( $this->get_ID(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Student Meta Field.
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
		return update_user_meta( $this->get_ID(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Student Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return delete_user_meta( $this->get_ID(), $meta_key, $meta_value );
	}

	/**
	 * Save Student Data.
	 *
	 * @since 4.3.0
	 */
	public function save() {
		$data   = $this->get_data( true );
		$skip   = array( 'user_pass', 'user_login', 'user_registered', 'user_activation_key', 'user_status' );
		$update = array();
		$meta   = array();

		if ( ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( in_array( $key, $skip ) ) {
					continue;
				}

				if ( $this->is_meta_field( $key ) ) {
					$meta[ $key ] = $value;
				} else {
					$update[ $key ] = $value;
				}
			}
		}

		foreach ( $meta as $key => $value ) {
			$this->update_meta( $key, $value );
		}

		return wp_update_user( $update );
	}

	/**
	 * Get Edit Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The edit student url.
	 */
	public function get_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_student_get_edit_url', add_query_arg( array( 'page' => 'wpcw-student', 'id' => $this->get_ID() ), admin_url( 'admin.php' ) ), $this ) );
	}

	/**
	 * Get Student Orders.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Optional. Query args for orders.
	 * @param bool $refresh Optional. Refresh the student orders array.
	 *
	 * @return array The array of student orders.
	 */
	public function get_orders( $args = array(), $refresh = false ) {
		if ( empty( $this->orders ) || $refresh ) {
			$defaults = array(
				'student_id' => $this->get_ID(),
				'order_type' => 'order',
				'order'      => 'ASC',
			);

			$args = wp_parse_args( $args, $defaults );

			$this->orders = wpcw()->orders->get_orders( $args );
		}

		return $this->orders;
	}

	/**
	 * Get Student Subscriptions.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args Optional. Query args for subscriptions.
	 * @param bool $refresh Optional. Refresh the student subscriptinos array.
	 *
	 * @return array The array of student subscriptions.
	 */
	public function get_subscriptions( $args = array(), $refresh = false ) {
		if ( empty( $this->subscriptions ) || $refresh ) {
			$defaults = array(
				'student_id' => $this->get_ID(),
				'order'      => 'ASC',
			);

			$args = wp_parse_args( $args, $defaults );

			$this->subscriptions = wpcw()->subscriptions->get_subscriptions( $args );
		}

		return $this->subscriptions;
	}
}
