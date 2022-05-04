<?php
/**
 * WP Courseware Coupon Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.5.0
 */
namespace WPCW\Models;

use WPCW\Database\DB_Coupon_Meta;
use WPCW\Database\DB_Coupons;
use Exception;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Coupon.
 *
 * @since 4.5.0
 *
 * @property int    $coupon_id
 * @property string $code
 * @property string $type
 * @property string $amount
 * @property int    $max_uses
 * @property int    $use_count
 * @property string $date_created
 * @property string $start_date
 * @property string $end_date
 */
class Coupon extends Model {

	/**
	 * @var DB_Coupons The coupons database.
	 * @since 4.5.0
	 */
	protected $db;

	/**
	 * @var DB_Coupon_Meta The coupons meta database.
	 * @since 4.5.0
	 */
	protected $meta_db;

	/**
	 * @var int $coupon_id The coupon id.
	 * @since 4.5.0
	 */
	public $coupon_id;

	/**
	 * @var string $code The coupon code.
	 * @since 4.5.0
	 */
	public $code = '';

	/**
	 * @var string $type The coupon type.
	 * @since 4.5.0
	 */
	public $type = 'percentage';

	/**
	 * @var string $amount The coupon amount.
	 * @since 4.5.0
	 */
	public $amount = '';

	/**
	 * @var int $usage_count The coupon usage count.
	 * @since 4.5.0
	 */
	public $usage_count = 0;

	/**
	 * @var int $usage_limit The coupon usage limit.
	 * @since 4.5.0
	 */
	public $usage_limit = 0;

	/**
	 * @var int $usage_limit_per_user The coupon usage limit per user.
	 * @since 4.5.0
	 */
	public $usage_limit_per_user = 0;

	/**
	 * @var bool $individual_use Is this coupon individual use?
	 * @since 4.5.0
	 */
	public $individual_use = false;

	/**
	 * @var array $course_ids Courses the coupon can be applied to.
	 * @since 4.5.0
	 */
	public $course_ids = array();

	/**
	 * @var array $exclude_course_ids Courses the coupon will not be applied to.
	 * @since 4.5.0
	 */
	public $exclude_course_ids = array();

	/**
	 * @var string $minimum_amount The minimum amount to use the coupon.
	 * @since 4.5.0
	 */
	public $minimum_amount = '';

	/**
	 * @var string $maximum_amount The maximum amount to use the coupon.
	 * @since 4.5.0
	 */
	public $maximum_amount = '';

	/**
	 * @var string $start_date The coupond start date.
	 * @since 4.5.0
	 */
	public $start_date = '';

	/**
	 * @var string $end_date The coupon end date.
	 * @since 4.5.0
	 */
	public $end_date = '';

	/**
	 * @var string $date_created The coupon date created.
	 * @since 4.5.0
	 */
	public $date_created = '';

	/**
	 * Coupon Error / Message Codes.
	 * @since 4.5.0
	 */
	const E_COUPON_INVALID = 100;
	const E_COUPON_INVALID_REMOVED = 101;
	const E_COUPON_NOT_YOURS_REMOVED = 102;
	const E_COUPON_ALREADY_APPLIED = 103;
	const E_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY = 104;
	const E_COUPON_NOT_EXIST = 105;
	const E_COUPON_USAGE_LIMIT_REACHED = 106;
	const E_COUPON_EXPIRED = 107;
	const E_COUPON_MIN_SPEND_LIMIT_NOT_MET = 108;
	const E_COUPON_MAX_SPEND_LIMIT_MET = 109;
	const E_COUPON_NOT_APPLICABLE = 110;
	const E_COUPON_MISSING = 110;
	const E_COUPON_EXCLUDED_COURSES_NOT_APPLICABLE = 111;
	const E_COUPON_NOT_AVAILABLE = 112;
	const M_COUPON_SUCCESS = 200;
	const M_COUPON_REMOVED = 201;

	/**
	 * Coupon Constructor.
	 *
	 * @since 4.5.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db      = new DB_Coupons();
		$this->meta_db = new DB_Coupon_Meta();
		parent::__construct( $data );
	}

	/**
	 * Get Coupon Id.
	 *
	 * @since 4.5.0
	 *
	 * @return int The coupon id.
	 */
	public function get_id() {
		return absint( $this->get_coupon_id() );
	}

	/**
	 * Get Coupond Id.
	 *
	 * @since 4.5.0
	 *
	 * @return int $coupon_id The coupon_id.
	 */
	public function get_coupon_id() {
		return absint( $this->coupon_id );
	}

	/**
	 * Get Coupon Code.
	 *
	 * @since 4.5.0
	 *
	 * @return string $code The coupon code.
	 */
	public function get_code() {
		return wpcw_format_coupon_code( $this->code );
	}

	/**
	 * Get Coupon Amount.
	 *
	 * @since 4.5.0
	 *
	 * @return string $amount The coupon amount.
	 */
	public function get_amount( $format = false ) {
		return ( $format ) ? wpcw_price( $this->amount ) : $this->amount;
	}

	/**
	 * Get Coupon Type.
	 *
	 * @since 4.5.0
	 *
	 * @return string $type The coupon type.
	 */
	public function get_type() {
		return wpcw_clean( $this->type );
	}

	/**
	 * Get Usage Count.
	 *
	 * @since 4.5.0
	 *
	 * @return string $usage_count The coupon usage count.
	 */
	public function get_usage_count() {
		return absint( $this->usage_count );
	}

	/**
	 * Get Coupon Usage Limit.
	 *
	 * @since 4.5.0
	 *
	 * @return string $usage_limit The usage limit.
	 */
	public function get_usage_limit() {
		return absint( $this->usage_limit );
	}

	/**
	 * Get Coupon Usage Limit per User.
	 *
	 * @since 4.5.0
	 *
	 * @return string $usage_limit_per_user The coupon usage limit per user.
	 */
	public function get_usage_limit_per_user() {
		return absint( $this->usage_limit_per_user );
	}

	/**
	 * Is this coupon individual use only?
	 *
	 * @since 4.5.0
	 *
	 * @return bool True if individual use, false otherwise.
	 */
	public function get_individual_use() {
		return (bool) $this->individual_use;
	}

	/**
	 * Get Course Ids.
	 *
	 * @since 4.5.0
	 *
	 * @return array $course_ids The course ids.
	 */
	public function get_course_ids() {
		return maybe_unserialize( $this->course_ids );
	}

	/**
	 * Get Excluded Course Ids.
	 *
	 * @since 4.5.0
	 *
	 * @return array $exclude_course_ids The course ids.
	 */
	public function get_exclude_course_ids() {
		return maybe_unserialize( $this->exclude_course_ids );
	}

	/**
	 * Get Minimum Amount.
	 *
	 * @since 4.5.0
	 *
	 * @return string $minimum_amount The minimum amount.
	 */
	public function get_minimum_amount() {
		return $this->minimum_amount;
	}

	/**
	 * Get Maximum Amount.
	 *
	 * @since 4.5.0
	 *
	 * @return string $maximum_amount The maximum amount.
	 */
	public function get_maximum_amount() {
		return $this->maximum_amount;
	}

	/**
	 * Get Coupon Start Date.
	 *
	 * @since 4.5.0
	 *
	 * @return string $start_date The coupon start date.
	 */
	public function get_start_date() {
		return $this->start_date;
	}

	/**
	 * Get Coupon End Date.
	 *
	 * @since 4.5.0
	 *
	 * @return string $end_date The coupon end date.
	 */
	public function get_end_date() {
		return $this->end_date;
	}

	/**
	 * Get Coupon Date Created.
	 *
	 * @since 4.5.0
	 *
	 * @return string $date_created The coupon date created.
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Get a Coupon Meta Field.
	 *
	 * @since 4.5.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool   $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return $this->meta_db->get_meta( $this->get_id(), $meta_key, $single );
	}

	/**
	 * Add Coupon Meta Field.
	 *
	 * @since 4.5.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed  $meta_value Metadata value.
	 * @param bool   $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return $this->meta_db->add_meta( $this->get_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Coupon Meta Field.
	 *
	 * @since 4.5.0
	 *
	 * @param string $meta_key Metadata key.
	 * @param mixed  $meta_value Metadata value.
	 * @param mixed  $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, true if success.
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return $this->meta_db->update_meta( $this->get_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Coupon Meta Field.
	 *
	 * @since 4.5.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return $this->meta_db->delete_meta( $this->get_id(), $meta_key, $meta_value );
	}

	/**
	 * Get Coupon Usage by User Id.
	 *
	 * @since 4.5.0
	 *
	 * @param int $user_id The user id.
	 *
	 * @return int The coupon usage number by user id.
	 */
	public function get_usage_by_user_id( $user_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT( meta_id ) 
			 FROM {$this->meta_db->get_table_name()} 
			 WHERE wpcw_coupon_id = %d 
			 AND meta_key = '_used_by' 
			 AND meta_value = %d;",
			absint( $this->get_id() ),
			absint( $user_id )
		) );
	}

	/**
	 * Get Used By.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public function get_used_by() {
		return (array) $this->get_meta( '_used_by', false );
	}

	/**
	 * Increase Usage Count.
	 *
	 * @since 4.5.0
	 *
	 * @param string $used_by Either user id or billing email.
	 */
	public function increase_usage_count( $used_by = '' ) {
		if ( $this->get_id() ) {
			$usage_count = $this->get_usage_count();
			$usage_count += 1;
			$this->set_prop( 'usage_count', absint( $usage_count ) );

			if ( ! empty( $used_by ) ) {
				$this->add_meta( '_used_by', strtolower( $used_by ), false );
			}
		}
	}

	/**
	 * Decrease Usage Count.
	 *
	 * @since 4.5.0
	 *
	 * @param string $used_by Either user id or billing email.
	 */
	public function decrease_usage_count( $used_by = '' ) {
		if ( $this->get_id() ) {
			$usage_count = $this->get_usage_count();
			$usage_count -= 1;

			if ( $usage_count < 0 ) {
				$usage_count = 0;
			}

			$this->set_prop( 'usage_count', absint( $usage_count ) );

			if ( ! empty( $used_by ) ) {
				$this->delete_meta( '_used_by', strtolower( $used_by ) );
			}
		}
	}

	/**
	 * Delete Coupon.
	 *
	 * @since 4.5.0
	 *
	 * @return True on success, false on failure.
	 */
	public function delete() {
		$deleted = $this->db->delete( $this->get_id() );

		// Check if deleted.
		if ( $deleted ) {
			$this->delete_all_meta();
		}

		/**
		 * Action: Coupon Deleted.
		 *
		 * @since 4.5.0
		 *
		 * @param Coupon $this The course object.
		 */
		do_action( 'wpcw_coupon_deleted', $this );

		return $deleted;
	}

	/**
	 * Delete All Meta Fields.
	 *
	 * @since 4.5.0
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function delete_all_meta() {
		return $this->meta_db->delete_all_meta( $this->get_id() );
	}

	/** Validation Methods ------------------------------------------------------- */

	/**
	 * Is Coupon Valid?
	 *
	 * @since 4.5.0
	 */
	public function is_valid() {
		try {
			$this->validate_coupon_exists();
			$this->validate_coupon_usage_limit();
			$this->validate_coupon_user_usage_limit();
			$this->validate_coupon_start_date();
			$this->validate_coupon_expiry_date();
			$this->validate_coupon_minimum_amount();
			$this->validate_coupon_maximum_amount();
			$this->validate_coupon_course_ids();
			$this->validate_coupon_excluded_course_ids();

			if ( ! apply_filters( 'wpcw_coupon_is_valid', true, $this ) ) {
				throw new Exception( esc_html__( 'Coupon is not valid.', 'wp-courseware' ), self::E_COUPON_INVALID );
			}
		} catch ( Exception $e ) {
			/**
			 * Filter the coupon error message.
			 *
			 * @since 4.5.0
			 *
			 * @param string $error_message The coupon error message.
			 * @param int    $error_code The coupon error code.
			 * @param Coupon $this The coupon object.
			 */
			$message = apply_filters( 'wpcw_coupon_error', is_numeric( $e->getMessage() ) ? $this->get_coupon_error( $e->getMessage() ) : $e->getMessage(), $e->getCode(), $this );

			// Add Error Notice.
			wpcw_add_notice( $message, 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Validate Coupon Exists.
	 *
	 * @since 4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_exists() {
		if ( ! $this->get_id() ) {
			throw new Exception( sprintf( __( 'Coupon "%s" does not exist!', 'wp-courseware' ), $this->get_code() ), self::E_COUPON_NOT_EXIST );
		}

		return true;
	}

	/**
	 * Validate Coupon Usage Limit.
	 *
	 * @since  4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_usage_limit() {
		if ( $this->get_usage_limit() > 0 && $this->get_usage_count() >= $this->get_usage_limit() ) {
			throw new Exception( esc_html__( 'Coupon usage limit has been reached.', 'wp-courseware' ), self::E_COUPON_USAGE_LIMIT_REACHED );
		}

		return true;
	}

	/**
	 * Validate Coupon User Usage Limit.
	 *
	 * Per user usage limit - check here if user is logged in (against user IDs).
	 * Checked again for emails later on in WC_Cart::check_customer_coupons().
	 *
	 * @since 4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @param  int $user_id User ID.
	 *
	 * @return bool
	 */
	protected function validate_coupon_user_usage_limit( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id && $this->get_usage_limit_per_user() > 0 && $this->get_id() ) {
			$usage_count = $this->get_usage_by_user_id( $user_id );
			if ( $usage_count >= $this->get_usage_limit_per_user() ) {
				throw new Exception( esc_html__( 'Coupon usage limit has been reached.', 'wp-courseware' ), self::E_COUPON_USAGE_LIMIT_REACHED );
			}
		}

		return true;
	}

	/**
	 * Validate Coupon Start Date.
	 *
	 * @since  4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_start_date() {
		if ( $this->get_start_date() && apply_filters( 'wpcw_coupon_validate_start_date', current_time( 'timestamp' ) < strtotime( $this->get_start_date() ), $this ) ) {
			throw new Exception( esc_html__( 'This coupon is not yet available for use.', 'wp-courseware' ), self::E_COUPON_NOT_AVAILABLE );
		}

		return true;
	}

	/**
	 * Validate Coupon Expiry Date.
	 *
	 * @since  4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_expiry_date() {
		if ( $this->get_end_date() && apply_filters( 'wpcw_coupon_validate_end_date', current_time( 'timestamp' ) > strtotime( $this->get_end_date() ), $this ) ) {
			throw new Exception( esc_html__( 'This coupon has expired.', 'wp-courseware' ), self::E_COUPON_EXPIRED );
		}

		return true;
	}

	/**
	 * Validate Coupon Minimum Amount.
	 *
	 * @since  4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_minimum_amount() {
		$subtotal = wpcw_round( wpcw()->cart->get_subtotal() );
		if ( $this->get_minimum_amount() > 0 && apply_filters( 'wpcw_coupon_validate_minimum_amount', ( (float) $this->get_minimum_amount() > (float) $subtotal ), $subtotal, $this ) ) {
			/* translators: %s: coupon minimum amount */
			throw new Exception( sprintf( esc_html__( 'The minimum spend for this coupon is %s.', 'wp-courseware' ), wpcw_price( $this->get_minimum_amount() ) ), self::E_COUPON_MIN_SPEND_LIMIT_NOT_MET );
		}

		return true;
	}

	/**
	 * Validate Coupon Maximum Amount.
	 *
	 * @since 4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_maximum_amount() {
		$subtotal = wpcw_round( wpcw()->cart->get_subtotal() );
		if ( $this->get_maximum_amount() > 0 && apply_filters( 'wpcw_coupon_validate_maximum_amount', ( (float) $this->get_maximum_amount() < (float) $subtotal ), $subtotal, $this ) ) {
			/* translators: %s: coupon maximum amount */
			throw new Exception( sprintf( esc_html__( 'The maximum spend for this coupon is %s.', 'wp-courseware' ), wpcw_price( $this->get_maximum_amount() ) ), self::E_COUPON_MAX_SPEND_LIMIT_MET );
		}

		return true;
	}

	/**
	 * Validate Coupon Course Ids.
	 *
	 * @since 4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_course_ids() {
		if ( count( $this->get_course_ids() ) > 0 ) {
			$valid = false;

			if ( ! wpcw()->cart->is_empty() ) {
				foreach ( wpcw()->cart->get_cart() as $cart_item ) {
					$course = wpcw()->cart->get_cart_course_object( $cart_item );
					if ( $course && $course->get_id() && in_array( $course->get_id(), $this->get_course_ids(), true ) ) {
						$valid = true;
						break;
					}
				}

				if ( ! $valid ) {
					throw new Exception( sprintf( esc_html__( 'Sorry, this coupon is not applicable to your cart contents.', 'wp-courseware' ) ), self::E_COUPON_NOT_APPLICABLE );
				}
			}
		}

		return true;
	}

	/**
	 * Validate Coupon Excluded Course Ids.
	 *
	 * @since 4.5.0
	 *
	 * @throws Exception Error message.
	 *
	 * @return bool
	 */
	protected function validate_coupon_excluded_course_ids() {
		if ( in_array( $this->get_type(), array( 'fixed_cart', 'percentage' ), true ) && count( $this->get_exclude_course_ids() ) > 0 ) {
			$courses = array();

			if ( ! wpcw()->cart->is_empty() ) {
				foreach ( wpcw()->cart->get_cart() as $cart_item ) {
					$course = wpcw()->cart->get_cart_course_object( $cart_item );
					if ( $course && $course->get_id() && in_array( $course->get_id(), $this->get_exclude_course_ids(), true ) ) {
						$courses[] = $course->get_course_title();
						break;
					}
				}

				if ( ! empty( $courses ) ) {
					/* translators: %s: courses */
					throw new Exception( sprintf( __( 'Sorry, this coupon is not applicable to the courses: %s.', 'wp-courseware' ), implode( ', ', $courses ) ), self::E_COUPON_EXCLUDED_COURSES_NOT_APPLICABLE );
				}
			}
		}

		return true;
	}

	/** Message Methods ------------------------------------------------------- */

	/**
	 * Add Coupon Message.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code The coupon message code.
	 */
	public function add_coupon_message( $code ) {
		$message = $this->get_coupon_message( $code );

		if ( ! $message ) {
			return;
		}

		wpcw_add_notice( $message, 'success' );
	}

	/**
	 * Add Coupon Error.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code The coupon message code.
	 */
	public function add_coupon_error( $code ) {
		$message = $this->get_coupon_error( $code );

		if ( ! $message ) {
			return;
		}

		wpcw_add_notice( $message, 'error' );
	}

	/**
	 * Get Coupon Message.
	 *
	 * @since 4.5.0
	 *
	 * @param string $msg_code The coupon message code.
	 *
	 * @return string The coupon message string.
	 */
	public function get_coupon_message( $msg_code ) {
		switch ( $msg_code ) {
			case self::M_COUPON_SUCCESS:
			case 'success':
				$msg = esc_html__( 'Coupon code applied successfully.', 'wp-courseware' );
				break;
			case self::M_COUPON_REMOVED:
			case 'removed':
				$msg = esc_html__( 'Coupon code removed successfully.', 'wp-courseware' );
				break;
			default:
				$msg = '';
				break;
		}

		return apply_filters( 'wpcw_coupon_message', $msg, $msg_code, $this );
	}

	/**
	 * Get Coupon Error.
	 *
	 * @since 4.5.0
	 *
	 * @param string $err_code The coupon error code.
	 *
	 * @return string The coupon error string.
	 */
	public function get_coupon_error( $err_code ) {
		switch ( $err_code ) {
			case self::E_COUPON_MISSING:
			case 'coupon-missing' :
				$err = esc_html__( 'Please enter a coupon code.', 'wp-courseware' );
				break;
			case self::E_COUPON_INVALID:
			case 'coupon-invalid':
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Coupon "%s" is not valid!', 'wp-courseware' ), $this->get_code() );
				break;
			case self::E_COUPON_INVALID_REMOVED:
			case 'coupon-invalid-removed':
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is invalid - it has now been removed from your cart.', 'wp-courseware' ), $this->get_code() );
				break;
			case self::E_COUPON_NOT_EXIST:
			case 'coupon-does-not-exist':
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Coupon "%s" does not exist!', 'wp-courseware' ), $this->get_code() );
				break;
			case self::E_COUPON_NOT_YOURS_REMOVED:
			case 'coupon-not-yours':
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Sorry, it seems the coupon "%s" is not yours - it has now been removed from your cart.', 'wp-courseware' ), $this->get_code() );
				break;
			case self::E_COUPON_ALREADY_APPLIED:
			case 'coupon-already-applied':
				$err = esc_html__( 'Coupon code already applied!', 'wp-courseware' );
				break;
			case self::E_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY:
			case 'coupon-already-applied-in-conjunction':
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Sorry, coupon "%s" has already been applied and cannot be used in conjunction with other coupons.', 'wp-courseware' ), $this->get_code() );
				break;
			case self::E_COUPON_USAGE_LIMIT_REACHED:
			case 'coupon-usage-limit-reached':
				$err = esc_html__( 'Coupon usage limit has been reached.', 'wp-courseware' );
				break;
			case self::E_COUPON_NOT_AVAILABLE:
			case 'coupon-does-not-available':
				/* translators: %s: coupon code */
				$err = sprintf( __( 'Coupon "%s" is not yet available for use!', 'wp-courseware' ), $this->get_code() );
				break;
			case self::E_COUPON_EXPIRED:
			case 'coupon-expired':
				$err = esc_html__( 'This coupon has expired.', 'wp-courseware' );
				break;
			case self::E_COUPON_MIN_SPEND_LIMIT_NOT_MET:
			case 'coupon-min-spend-not-met':
				/* translators: %s: coupon minimum amount */
				$err = sprintf( __( 'The minimum spend for this coupon is %s.', 'wp-courseware' ), wpcw_price( $this->get_minimum_amount() ) );
				break;
			case self::E_COUPON_MAX_SPEND_LIMIT_MET:
			case 'coupon-max-spend-met':
				/* translators: %s: coupon maximum amount */
				$err = sprintf( __( 'The maximum spend for this coupon is %s.', 'wp-courseware' ), wpcw_price( $this->get_maximum_amount() ) );
				break;
			case self::E_COUPON_NOT_APPLICABLE:
			case 'coupon-not-applicable':
				$err = esc_html__( 'Sorry, this coupon is not applicable to your cart contents.', 'wp-courseware' );
				break;
			case self::E_COUPON_EXCLUDED_COURSES_NOT_APPLICABLE:
			case 'coupon-not-applicable-to-excluded-products':
				if ( count( $this->get_exclude_course_ids() ) > 0 ) {
					$courses = array();

					if ( ! wpcw()->cart->is_empty() ) {
						foreach ( wpcw()->cart->get_cart() as $cart_key => $cart_course ) {
							$course = wpcw()->cart->get_cart_course_object( $cart_course );
							if ( $course && $course->get_id() && in_array( $course->get_id(), $this->get_exclude_course_ids(), true ) ) {
								$courses[] = $course->get_course_title();
							}
						}
					}

					/* translators: %s: courses list */
					$err = sprintf( __( 'Sorry, this coupon is not applicable to the following courses: %s.', 'wp-courseware' ), implode( ', ', $courses ) );
				}
				break;
			default:
				$err = '';
				break;
		}

		return apply_filters( 'wpcw_coupon_error', $err, $err_code, $this );
	}

	/** Discount Methods ------------------------------------------------------- */

	/**
	 * Get Discount Amount.
	 *
	 * @since 4.5.0
	 *
	 * @param string|int $price The base prices.
	 *
	 * @return float $discounted The discounted price.
	 */
	public function get_discount_amount( $price ) {
		$amount = $price;
		$type   = $this->get_type();

		switch ( $type ) {
			case 'percentage' :
			case 'percentage_course' :
				$amount = $price - ( $price * ( $this->get_amount() / 100 ) );
				break;
			case 'fixed_cart' :
			case 'fixed_course' :
				$amount = $price - $this->get_amount();
				if ( $amount < 0 ) {
					$amount = 0;
				}
				break;
		}


		/**
		 * Filter: Discount Amount.
		 *
		 * @since 4.5.0
		 *
		 * @param float|int $amount The discount amount.
		 *
		 * @return float|int $amount The discount amount.
		 */
		return apply_filters( 'wpcw_coupon_discount_amount', $amount );
	}

	/** Misc Methods ----------------------------------------------------------- */

	/**
	 * Get Edit Url.
	 *
	 * @since 4.5.0
	 *
	 * @return string The edit coupon url
	 */
	public function get_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_coupon_get_edit_url', add_query_arg( array( 'page' => 'wpcw-coupon', 'coupon_id' => $this->get_id() ), admin_url( 'admin.php' ) ), $this ) );
	}
}
