<?php
/**
 * WP Courseware Subscription Model.
 *
 * @since 4.3.0
 * @subpackage Models
 * @package WPCW
 */

namespace WPCW\Models;

use WPCW\Database\DB_Subscriptions;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription.
 *
 * @since 4.3.0
 * @property int $id
 * @property int $student_id
 * @property Student $student
 * @property int $order_id
 * @property Order $order
 * @property int $course_id
 * @property Course $course
 * @property string $student_name
 * @property string $student_email
 * @property string $course_title
 * @property string $period
 * @property string $initial_amount
 * @property string $recurring_amount
 * @property int $bill_times
 * @property string $transaction_id
 * @property string $method
 * @property string $created
 * @property string $expiration
 * @property string $status
 * @property string $profile_id
 * @property int $installment_plan
 */
class Subscription extends Model {

	/**
	 * @var DB_Subscriptions The subscriptions database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var int The Subscription Id.
	 * @since 4.3.0
	 */
	public $id;

	/**
	 * @var int The Student Id.
	 * @since 4.3.0
	 */
	public $student_id;

	/**
	 * @var Student The student object.
	 * @since 4.3.0
	 */
	public $student;

	/**
	 * @var string The Order Id.
	 * @since 4.3.0
	 */
	public $order_id;

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	public $order;

	/**
	 * @var string The Course Id.
	 * @since 4.3.0
	 */
	public $course_id;

	/**
	 * @var Course The course object.
	 * @since 4.3.0
	 */
	public $course;

	/**
	 * @var string $student_name The student name.
	 * @since 4.3.0
	 */
	public $student_name;

	/**
	 * @var string $student_email The Student Email.
	 * @since 4.3.0
	 */
	public $student_email;

	/**
	 * @var string $course_title The course title.
	 * @since 4.3.0
	 */
	public $course_title;

	/**
	 * @var string The Subscription Period.
	 * @since 4.3.0
	 */
	public $period;

	/**
	 * @var string The Subscription Initial Amount.
	 * @since 4.3.0
	 */
	public $initial_amount;

	/**
	 * @var string The Subscription Recurring Amount.
	 * @since 4.3.0
	 */
	public $recurring_amount;

	/**
	 * @var int The number of bill times.
	 * @since 4.3.0
	 */
	public $bill_times;

	/**
	 * @var string The Subscription Transaction Id.
	 * @since 4.3.0
	 */
	public $transaction_id;

	/**
	 * @var string The Subscription Payment Method.
	 * @since 4.3.0
	 */
	public $method;

	/**
	 * @var string The Subscription Created Date.
	 * @since 4.3.0
	 */
	public $created;

	/**
	 * @var string The subscription expiration date.
	 * @since 4.3.0
	 */
	public $expiration;

	/**
	 * @var string The subscription status.
	 * @since 4.3.0
	 */
	public $status;

	/**
	 * @var string The subscription profile id.
	 * @since 4.3.0
	 */
	public $profile_id;

	/**
	 * @var int Is an installment plan?
	 * @since 4.6.0
	 */
	public $installment_plan;

	/**
	 * @var bool|array Status transition.
	 * @since 4.3.0
	 */
	protected $status_transition = false;

	/**
	 * @var array $payments The associated payment orders.
	 * @since 4.3.0
	 */
	protected $payments;

	/**
	 * Subscription Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Subscriptions();
		parent::__construct( $data );
	}

	/**
	 * Get Subscription Id.
	 *
	 * @since 4.3.0
	 * @return int $id The subscription id.
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Get Student Id.
	 *
	 * @since 4.3.0
	 * @return int $student_id The related student id.
	 */
	public function get_student_id() {
		return absint( $this->student_id );
	}

	/**
	 * Get Student.
	 *
	 * @since 4.3.0
	 * @return Student|false The student data.
	 */
	public function get_student() {
		if ( ! $this->get_student_id() ) {
			return false;
		}

		if ( empty( $this->student ) ) {
			$this->student = new Student( $this->get_student_id() );
		}

		return $this->student;
	}

	/**
	 * Get Student Name.
	 *
	 * @since 4.3.0
	 *
	 * @param string The student email.
	 */
	public function get_student_name() {
		if ( empty( $this->student_name ) && $this->get_student_id() ) {
			$student            = $this->get_student();
			$this->student_name = $student->get_full_name();
		}

		return $this->student_name;
	}

	/**
	 * Get Student Email.
	 *
	 * @since 4.3.0
	 *
	 * @param string The student email.
	 */
	public function get_student_email() {
		if ( empty( $this->student_email ) && $this->get_student_id() ) {
			$student             = $this->get_student();
			$this->student_email = $student->get_email();
		}

		return $this->student_email;
	}

	/**
	 * Get Order Id
	 *
	 * @since 4.3.0
	 * @return int $order_id The related order id.
	 */
	public function get_order_id() {
		return absint( $this->order_id );
	}

	/**
	 * Get Order.
	 *
	 * @since 4.3.0
	 * @return bool|Order The subscription order object.
	 */
	public function get_order() {
		if ( ! $this->get_order_id() ) {
			return false;
		}

		if ( empty( $this->order ) ) {
			$this->order = new Order( $this->get_order_id() );
		}

		return $this->order;
	}

	/**
	 * Get Course Id.
	 *
	 * @since 4.3.0
	 * @return int $course_id The related course id.
	 */
	public function get_course_id() {
		return absint( $this->course_id );
	}

	/**
	 * Get Course.
	 *
	 * @since 4.3.0
	 * @return bool|Course The subscription course object.
	 */
	public function get_course() {
		if ( ! $this->get_course_id() ) {
			return false;
		}

		if ( empty( $this->course ) ) {
			$this->course = new Course( $this->get_course_id() );
		}

		return $this->course;
	}

	/**
	 * Get Coruse Title.
	 *
	 * @since 4.3.0
	 * @returns string The course title.
	 */
	public function get_course_title() {
		if ( empty( $this->course_title ) && $this->get_course_id() ) {
			$course             = $this->get_course();
			$this->course_title = $course->get_course_title();
		}

		return $this->course_title;
	}

	/**
	 * Get Billing Period.
	 *
	 * @since 4.3.0
	 * @return string $period The subscription billing period.
	 */
	public function get_period() {
		return esc_attr( $this->period );
	}

	/**
	 * Get Billing Period Name.
	 *
	 * @since 4.3.0
	 * @return string $period_name The subscription billing period name.
	 */
	public function get_period_name() {
		return wpcw_get_subscription_period_name( $this->get_period() );
	}

	/**
	 * Get Initial Subscription Amount.
	 *
	 * @since 4.3.0
	 * @return string $initial_amount The initial subscription amount.
	 */
	public function get_initial_amount() {
		return esc_attr( $this->initial_amount );
	}

	/**
	 * Get Subscription Recurring Amount.
	 *
	 * @since 4.3.0
	 * @return string $recurring_amount The subscription recurring amount.
	 */
	public function get_recurring_amount( $format = false ) {
		return $format ? sprintf( '%s / %s', wpcw_price( $this->recurring_amount ), $this->get_period_name() ) : esc_attr( $this->recurring_amount );
	}

	/**
	 * Get Number of Bill Times.
	 *
	 * @since 4.3.0
	 * @return int $bill_times The number of time the subscription should be billed.
	 */
	public function get_bill_times() {
		return absint( $this->bill_times );
	}

	/**
	 * Get Transaction Id.
	 *
	 * @since 4.3.0
	 * @return string $transaction_id The subscription transaction id.
	 */
	public function get_transaction_id() {
		return esc_attr( $this->transaction_id );
	}

	/**
	 * Get Method.
	 *
	 * @since 4.3.0
	 * @return string $method The subscription method.
	 */
	public function get_method() {
		return esc_attr( $this->method );
	}

	/**
	 * Get Date Created.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format To format the date string.
	 *
	 * @return string $created The date the subscription was created.
	 */
	public function get_created( $format = false ) {
		return $format ? date_i18n( 'F j, Y', strtotime( esc_attr( $this->created ) ) ) : esc_attr( $this->created );
	}

	/**
	 * Get Subscription Renewal.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format To format the date string.
	 *
	 * @return string $renewal The subscription renewal.
	 */
	public function get_renewal( $format = false ) {
		return $this->get_expiration( $format );
	}

	/**
	 * Get Subscription Expiration.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $format To format the date string.
	 *
	 * @return string $expiration The subscription expiration.
	 */
	public function get_expiration( $format = false ) {
		return $format ? date_i18n( 'F j, Y', strtotime( esc_attr( $this->expiration ) ) ) : esc_attr( $this->expiration );
	}

	/**
	 * Get Subscription Status.
	 *
	 * @since 4.3.0
	 * @return string $status The subscription status.
	 */
	public function get_status() {
		return esc_attr( $this->status );
	}

	/**
	 * Checks the subscription status against a passed in status.
	 *
	 * @since 4.3.0
	 *
	 * @param string $status The subscription status.
	 *
	 * @return mixed|void
	 */
	public function has_status( $status ) {
		$has_status = ( ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ) ? true : false;

		return apply_filters( 'wpcw_subscription_has_status', $has_status, $this, $status );
	}

	/**
	 * Set Subscription Status.
	 *
	 * @since 4.3.0
	 *
	 * @param string $new_status The new subscription status.
	 * @param string $note Optional. Note to add.
	 * @param bool $manual_update Is this a manual order status change.
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$old_status = $this->get_status();

		// Only allow valid new status
		if ( ! array_key_exists( $new_status, wpcw()->subscriptions->get_statuses() ) && 'trash' !== $new_status ) {
			$new_status = 'pending';
		}

		// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
		if ( $old_status && ! array_key_exists( $old_status, wpcw()->subscriptions->get_statuses() ) && 'trash' !== $old_status ) {
			$old_status = 'pending';
		}

		// Set Status Property.
		$this->set_prop( 'status', $new_status );

		// Set Status Transition.
		$this->status_transition = array(
			'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $old_status,
			'to'     => $new_status,
			'note'   => $note,
			'manual' => (bool) $manual_update,
		);
	}

	/**
	 * Updates status of subscription immediately. Subscription must exist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $new_status Status to change the subscription to.
	 * @param string $note Optional note to add.
	 * @param bool $manual Is this a manual order status change?
	 *
	 * @return bool
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		try {
			if ( ! $this->get_id() ) {
				return false;
			}

			$this->set_status( $new_status, $note, $manual );

			$this->save();
		} catch ( Exception $e ) {
			wpcw_file_log( array(
				'message' => sprintf( 'Update status of subscription #%d failed!', $this->get_id() ),
				array( 'order' => $this, 'error' => $e )
			) );

			return false;
		}

		return true;
	}

	/**
	 * Handle Subscription Status Transition.
	 *
	 * @since 4.3.0
	 */
	protected function status_transition() {
		// Store Status Transition for local use.
		$status_transition = $this->status_transition;

		// Handle Status Transition.
		if ( $status_transition ) {
			/**
			 * Action: Subscription Status Transition To
			 *
			 * @since 4.3.0
			 *
			 * @param int $subscription_id The subscription id.
			 * @param Subscription The subscription object.
			 */
			// Don't transition if the status is attempting to change from "completed" to "completed"
			if( empty( $status_transition['from'] ) || 
				strtolower( $status_transition['from'] ) != 'completed' || 
				strtolower( $status_transition['to'] ) != 'completed' ) {
					
				do_action( 'wpcw_subscription_status_' . $status_transition['to'], $this->get_id(), $this );
			}

			if ( ! empty( $status_transition['from'] ) ) {
				/* translators: 1: old subscription status 2: new subscription status */
				$transition_note = sprintf( __( 'Subscription status changed from "%1$s" to "%2$s".', 'wp-courseware' ), wpcw_get_subscription_status_name( $status_transition['from'] ), wpcw_get_subscription_status_name( $status_transition['to'] ) );

				/**
				 * Action: Subscription Status Transition From To
				 *
				 * @since 4.3.0
				 *
				 * @param int $subscription_id The subscription id.
				 * @param Subscription The subscription object.
				 */
				do_action( 'wpcw_subscription_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );

				/**
				 * Action: Subscriptions Status Changed
				 *
				 * @since 4.3.0
				 *
				 * @param int $subscription_id The subscription id.
				 * @param string $status_transition ['from'] The old status or from status.
				 * @param string $status_transition ['to'] The new status or to status.
				 * @param Subscription The subscription object.
				 */
				do_action( 'wpcw_subscription_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
			} else {
				/* translators: %s: new order status */
				$transition_note = sprintf( __( 'Subscription status set to "%s".', 'wp-courseware' ), wpcw_get_subscription_status_name( $status_transition['to'] ) );
			}

			// Add Note.
			$this->add_note( trim( $status_transition['note'] . ' ' . $transition_note ), false, $status_transition['manual'] );
		}

		// Reset status transition variable.
		$this->status_transition = false;
	}

	/**
	 * Get Subscription Profile Id.
	 *
	 * @since 4.3.0
	 * @return string $profile_id The subscription profile id.
	 */
	public function get_profile_id() {
		return esc_attr( $this->profile_id );
	}

	/**
	 * Get Installment Plan.
	 *
	 * @since 4.6.0
	 * @return bool Get Installment Plan Boolean. Default is false.
	 */
	public function get_installment_plan() {
		return (bool) $this->installment_plan ? true : false;
	}

	/**
	 * Is an Installment Plan?
	 *
	 * @since 4.6.0
	 * @return bool Is this an installment plan? Default is false.
	 */
	public function is_installment_plan() {
		return $this->get_installment_plan();
	}

	/**
	 * Get Installment Plan Label.
	 *
	 * @since 4.6.0
	 * @return string The installments plan label.
	 */
	public function get_installment_plan_label() {
		$course = $this->get_course();

		if ( ! $course ) {
			return $this->get_recurring_amount( true );
		}

		/**
		 * Filter: Subscription Installment Plan Label.
		 *
		 * @since 4.6.0
		 *
		 * @param string The installment plan label.
		 * @param Course $course The course model object.
		 * @param Subscription $this The subscription model object.
		 *
		 * @return string The installment plan label.
		 */
		return apply_filters( 'wpcw_subscription_installment_plan_label', $course->get_installments_label(), $course, $this );
	}

	/**
	 * Create Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param string $data The subscription data.
	 *
	 * @return int|bool $subscription_id The subscription id or false otherwise.
	 */
	public function create( $data = array() ) {
		$defaults = array(
			'status'  => 'pending',
			'created' => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( $subscription_id = $this->db->insert_subscription( $data ) ) {
			$this->set_prop( 'id', $subscription_id );
			$this->set_data( $data );
		}

		return $subscription_id;
	}

	/**
	 * Save Subscription.
	 *
	 * @since 4.3.0
	 * @return bool True if successfull, False on failure
	 */
	public function save() {
		$data = $this->get_data( true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		$this->db->update_subscription( $this->get_id(), $data );

		$this->status_transition();

		return $this->get_id();
	}

	/**
	 * Delete Subscription.
	 *
	 * @since 4.3.0
	 */
	public function delete() {
		$this->delete_notes();

		return $this->db->delete( $this->get_id() );
	}

	/**
	 * Delete Subscription Notes.
	 *
	 * @since 4.3.0
	 * @return bool True on success, false on failure.
	 */
	public function delete_notes() {
		if ( ! $this->get_id() ) {
			return false;
		}

		return wpcw()->notes->delete_notes_by_object_id( $this->get_id() );
	}

	/**
	 * Cancel at Period End.
	 *
	 * @since 4.3.0
	 */
	public function cancel_at_period_end() {
		if ( ! $this->get_id() ) {
			return;
		}

		// Check if already has status.
		if ( $this->has_status( array( 'pending-cancel', 'expired', 'cancelled' ) ) ) {
			return;
		}

		// Cancel Subscription.
		$this->update_status( 'pending-cancel', sprintf( __( 'Subscription #%s is pending cancellation. It will be cancelled on %s.', 'wp-courseware' ), $this->get_id(), $this->get_expiration( true ) ) );

		/**
		 * Action: Subscription Cancel at period end.
		 *
		 * @since 4.3.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_cancel_at_period_end', $this->get_id(), $this );
	}

	/**
	 * Can Cancel Subscription?
	 *
	 * @since 4.3.0
	 * @return bool True if can cancel, False otherwise.
	 */
	public function can_cancel() {
		$can_cancel = true;
		$gateway    = wpcw()->gateways->get_gateway( $this->get_method() );

		if ( ! $gateway || ! $gateway->supports( 'cancellations' ) ) {
			$can_cancel = false;
		}

		if ( $this->has_status( array( 'expired', 'pending-cancel', 'cancelled', 'completed' ) ) ) {
			$can_cancel = false;
		}

		return $can_cancel;
	}

	/**
	 * Get Cancel Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $redirect Redirect URL.
	 *
	 * @return string The cancel url.
	 */
	public function get_cancel_url( $redirect = '' ) {
		/**
		 * Filter: Get Subscription Cancel Url.
		 *
		 * @since 4.3.0
		 *
		 * @param array $url The cancellation url.
		 * @param Subscription $this The subscription object.
		 *
		 * @return string The cancellation url.
		 */
		return apply_filters( 'wpwc_subscription_get_cancel_url', wp_nonce_url( add_query_arg( array(
			'cancel_subscription' => 'true',
			'subscription_id'     => $this->get_id(),
			'redirect'            => $redirect,
		), $this->get_view_url() ), 'wpcw-cancel-subscription' ), $this );
	}

	/**
	 * Get Admin Cancel Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $redirect Redirect URL.
	 *
	 * @return string The cancel url.
	 */
	public function get_admin_cancel_url() {
		/**
		 * Filter: Get Subscription Admin Cancel Url.
		 *
		 * @since 4.3.0
		 *
		 * @param array $url The cancellation url.
		 * @param Subscription $this The subscription object.
		 *
		 * @return string The cancellation url.
		 */
		return apply_filters( 'wpwc_subscription_get_admin_cancel_url', wp_nonce_url( add_query_arg( array(
			'cancel_subscription' => 'true',
			'subscription_id'     => $this->get_id(),
		), $this->get_edit_url() ), 'wpcw-admin-cancel-subscription' ), $this );
	}

	/**
	 * Cancel Subscription.
	 *
	 * @since 4.3.0
	 */
	public function cancel() {
		if ( ! $this->get_id() ) {
			return;
		}

		// Check if already expired or cancelled.
		if ( $this->has_status( array( 'expired', 'cancelled' ) ) ) {
			return;
		}

		// Cancel Subscription.
		$this->update_status( 'cancelled', sprintf( __( 'Subscription #%s has been cancelled.', 'wp-courseware' ), $this->get_id() ) );

		/**
		 * Action: Subscription Cancelled.
		 *
		 * @since 4.3.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_cancelled', $this->get_id(), $this );
	}

	/**
	 * Complete Subscription.
	 *
	 * @since 4.6.0
	 */
	public function complete() {
		if ( ! $this->get_id() ) {
			return;
		}

		// Check if already expired or cancelled.
		if ( $this->has_status( array( 'expired', 'cancelled' ) ) ) {
			return;
		}

		// Cancel Subscription.
		$this->update_status( 'completed', sprintf( __( 'Subscription #%s has been completed.', 'wp-courseware' ), $this->get_id() ) );

		/**
		 * Action: Subscription Completed.
		 *
		 * @since 4.6.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_completed', $this->get_id(), $this );
	}

	/**
	 * Put Subscription on Hold.
	 *
	 * @since 4.3.0
	 */
	public function hold() {
		if ( ! $this->get_id() ) {
			return;
		}

		// Cancel Subscription.
		$this->update_status( 'on-hold', sprintf( __( 'Subscription #%s is now on hold.', 'wp-courseware' ), $this->get_id() ) );

		/**
		 * Action: Subscription On-Hold.
		 *
		 * @since 4.3.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_on_hold', $this->get_id(), $this );
	}

	/**
	 * Suspend Subscription.
	 *
	 * @since 4.3.0
	 */
	public function suspend() {
		if ( ! $this->get_id() ) {
			return;
		}

		// Cancel Subscription.
		$this->update_status( 'suspended', sprintf( __( 'Subscription #%s has been suspended.', 'wp-courseware' ), $this->get_id() ) );

		/**
		 * Action: Subscription Suspended.
		 *
		 * @since 4.3.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_suspended', $this->get_id(), $this );
	}

	/**
	 * Expire a Subscription.
	 *
	 * @since 4.3.0
	 */
	public function expire() {
		if ( ! $this->get_id() ) {
			return;
		}

		// Check if already expired or cancelled.
		if ( $this->has_status( array( 'expired', 'cancelled' ) ) ) {
			return;
		}

		// Update Status.
		$this->update_status( 'expired', sprintf( __( 'Subscription #%s has expired.', 'wp-courseware' ), $this->get_id() ) );

		/**
		 * Action: Subscription Expired.
		 *
		 * @since 4.3.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_expired', $this->get_id(), $this );
	}

	/**
	 * Is Subscription Expired?
	 *
	 * @since 4.3.0
	 */
	public function is_expired() {
		if ( $this->has_status( array( 'expired', 'cancelled' ) ) ) {
			return true;
		}

		$expiration_date = wpcw_format_datetime( 'Y-m-d H:i:s', $this->get_expiration() );
		$todays_date     = date_i18n( 'Y-m-d H:i:s' );

		if ( apply_filters( 'wpcw_subscription_expiration_allow_buffer', false, $expiration_date, $todays_date ) ) {
			$expiration_buffer = apply_filters( 'wpcw_subscription_expiration_buffer', date_i18n( 'Y-m-d H:i:s', strtotime( $expiration_date . ' +5 days' ) ), $expiration_date, $todays_date );
			$expiration_date   = $expiration_buffer;
		}

		if ( $todays_date > $expiration_date ) {
			return true;
		}

		return false;
	}

	/**
	 * Payment Failed.
	 *
	 * @since 4.3.0
	 */
	public function payment_failed() {
		if ( ! $this->get_id() ) {
			return;
		}

		$payment_failed_note = $this->is_installment_plan()
			? sprintf( __( 'Installment Subscription #%s payment failed to process.', 'wp-courseware' ), $this->get_id() )
			: sprintf( __( 'Subscription #%s payment failed to process.', 'wp-courseware' ), $this->get_id() );

		$this->add_note( $payment_failed_note );

		// Put subscription on hold.
		$this->hold();

		/**
		 * Action: Subscription Payment Failed.
		 *
		 * @since 4.3.0
		 *
		 * @param int $subscription_id The subscription id.
		 * @param Subscription The subscription object.
		 */
		do_action( 'wpcw_subscription_payment_failed', $this->get_id(), $this );

		if ( $this->is_installment_plan() ) {
			/**
			 * Action: Installment Payment Failed.
			 *
			 * @since 4.6.0
			 *
			 * @param int $subscription_id The subscription id.
			 * @param Subscription The subscription object.
			 */
			do_action( 'wpcw_installment_payment_failed', $this->get_id(), $this );
		}
	}

	/**
	 * Is Subscritpion Setup?
	 *
	 * @since 4.3.0
	 * @return bool True if it is setup, False otherwise.
	 */
	public function is_setup() {
		return ( $this->get_course_id() && $this->get_order_id() && $this->get_student_id() && ( $this->get_profile_id() || $this->get_transaction_id() ) );
	}

	/**
	 * Get View Subscription Url.
	 *
	 * @since 4.3.0
	 * @return string The view order url.
	 */
	public function get_view_url() {
		$view_subscription_url = sprintf( '%s/%s/', untrailingslashit( wpcw_get_student_account_endpoint_url( 'view-subscription' ) ), $this->get_id() );

		return apply_filters( 'wpcw_order_get_view_subscription_url', $view_subscription_url, $this );
	}

	/**
	 * Get Subscription Notes.
	 *
	 * @since 4.3.0
	 * @return array The array of order note objects.
	 */
	public function get_notes( $args = array() ) {
		$notes = array();

		if ( ! $this->get_id() ) {
			return $notes;
		}

		$notes = wpcw()->notes->get_notes( array(
			'object_id'   => $this->get_id(),
			'object_type' => 'subscription',
		) );

		return $notes;
	}

	/**
	 * Add Subscription Note. Subscription must exist.
	 *
	 * @since 4.3.0
	 *
	 * @param string $content The note content.
	 * @param bool $is_public Is this note public. Shown to Student?
	 * @param bool $added_by_user Was this added manually by a user.
	 *
	 * @return bool|int The note id or false if something goes wrong.
	 */
	public function add_note( $content, $is_public = false, $added_by_user = false ) {
		if ( ! $this->get_id() ) {
			return false;
		}

		$note = new Note();
		$note->create( $this->get_id(), 'subscription' );

		if ( is_user_logged_in() && current_user_can( 'manage_wpcw_settings' ) && $added_by_user ) {
			$note->set_prop( 'user_id', get_current_user_id() );
		}

		if ( $is_public ) {
			$note->set_prop( 'is_public', absint( $is_public ) );
		}

		$note->set_prop( 'content', wp_kses_post( $content ) );

		return $note->save();
	}

	/**
	 * Get Edit Subscription Url.
	 *
	 * @since 4.3.0
	 * @return string The edit subscription url.
	 */
	public function get_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_subscriptions_get_edit_url', add_query_arg( array(
			'page' => 'wpcw-subscription',
			'id'   => $this->get_id()
		), admin_url( 'admin.php' ) ), $this ) );
	}

	/**
	 * Get Student Edit Url.
	 *
	 * @since 4.3.0
	 * @return string The edit student url.
	 */
	public function get_student_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_student_get_edit_url', add_query_arg( array(
			'page' => 'wpcw-student',
			'id'   => $this->get_student_id(),
		), admin_url( 'admin.php' ) ), $this ) );
	}

	/**
	 * Get Order Edit Url.
	 *
	 * @since 4.3.0
	 * @return string The edit student url.
	 */
	public function get_order_edit_url() {
		return esc_url_raw( apply_filters( 'wpcw_order_get_edit_url', add_query_arg( array(
			'page'     => 'wpcw-order',
			'order_id' => $this->get_order_id(),
		), admin_url( 'admin.php' ) ), $this ) );
	}

	/**
	 * Get Course Edit Url.
	 *
	 * @since 4.3.0
	 * @return string The edit student url.
	 */
	public function get_course_edit_url() {
		$course = $this->get_course();

		return esc_url_raw( apply_filters( 'wpcw_course_get_edit_url', $course->get_edit_url(), $this ) );
	}

	/**
	 * Get Payments.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The payments query args.
	 *
	 * @return array The array of payments for this subscription.
	 */
	public function get_payments( $args = array() ) {
		if ( empty( $this->payments ) ) {
			if ( ! $this->get_id() ) {
				return $this->payments;
			}

			$defaults = array(
				'order_type'      => 'payment',
				'subscription_id' => $this->get_id(),
				'transaction_id'  => 0,
				'order'           => 'ASC',
			);

			$args = wp_parse_args( $args, $defaults );

			$this->payments = wpcw()->orders->get_orders( $args, false, true );
		}

		return $this->payments;
	}

	/**
	 * Create Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $inital_payment The initial payment flag.
	 * @param Order|bool $payment_order The payment order to use or false.
	 *
	 * @return Order $payment The payment order.
	 */
	public function create_payment( $initial_payment = false, $payment = false ) {
		if ( ! $this->get_id() || ! $this->get_order_id() ) {
			return;
		}

		// Defined Parent Order.
		$parent_order   = $this->get_order();
		$payment_action = 'Attaching';
		$payment_type   = 'Recurring';

		// If we don't have a payment, create one.
		if ( ! $payment ) {
			$payment_action = 'Creating';

			// Create a new payment Order.
			$payment = new Order();
			$payment->create();

			// Set Type as Payment.
			$payment->set_prop( 'order_type', 'payment' );

			// Parent Data.
			$parent_order_id   = $parent_order->get_order_id();
			$parent_order_data = $parent_order->get_data( true );

			// Unset Certain Parent Data.
			$parent_data_unset = array(
				'order_id',
				'order_type',
				'order_key',
				'order_status',
				'transaction_id',
				'student_ip_address',
				'student_user_agent',
				'created_via',
				'date_created',
				'date_completed',
				'date_paid',
				'cart_hash',
				'subtotal',
				'tax',
				'total',
			);
			foreach ( $parent_data_unset as $item_to_unset ) {
				unset( $parent_order_data[ $item_to_unset ] );
			}

			// Set Data and Parent Order Id.
			$payment->set_props( $parent_order_data );
			$payment->set_prop( 'order_parent_id', $parent_order_id );

			// Set Initial Status.
			$payment->set_prop( 'order_status', 'pending' );

			/** @var Order_Item $order_item Set Order Items */
			foreach ( $parent_order->get_order_items() as $order_item ) {
				if ( $order_item->get_course_id() === $this->get_course_id() ) {
					$payment->insert_order_items( array( $order_item ), $initial_payment );
				}
			}
		}

		$payment_type = $initial_payment ? 'Initial' : $payment_type;
		$payment_type = $this->is_installment_plan() ? 'Installment' : $payment_type;
		$payment_type = $initial_payment && $this->is_installment_plan() ? 'Initial Installment' : $payment_type;

		$this->log( sprintf(
			'%1$s %2$s Order Payment #%3$s to Parent Order #%4$s',
			$payment_action,
			$payment_type,
			$payment->get_order_id(),
			$parent_order->get_order_id()
		) );

		// Applied Coupons.
		if ( $initial_payment ) {
			$parent_applied_coupons = $parent_order->get_applied_coupons();
			if ( ! empty( $parent_applied_coupons ) ) {
				$payment->update_applied_coupons( $parent_applied_coupons );
			}
		}

		// Update Totals.
		$payment->update_totals();

		// Initial Payment.
		if ( $initial_payment ) {
			$payment->update_meta( '_initial_payment', true );
		}

		// Save Order.
		if ( $payment->save() ) {
			$this->log( sprintf( 'Payment #%1$s saved successfully! Order Data: %2$s', $payment->get_order_id(), wpcw_print_r( $payment->get_data( true ), true ) ) );
		} else {
			$this->log( sprintf( 'Payment #%1$s failed to save. Order Data: %2$s', $payment->get_order_id(), wpcw_print_r( $payment->get_data( true ), true ) ) );
		}

		/**
		 * Action: Subscription Payment.
		 *
		 * @since 4.3.0
		 *
		 * @param Order $payment The payment order object.
		 * @param Order $parent_order The parent order object.
		 * @param Subscription $this The subscription object.
		 */
		do_action( 'wpcw_subscription_payment', $payment, $parent_order, $this );

		return $payment;
	}

	/**
	 * Get Subscription Actions.
	 *
	 * @since 4.3.0
	 * @return array $actions The actions that can be performed.
	 */
	public function get_actions() {
		$actions = array();

		if ( $this->can_cancel() ) {
			/**
			 * Filter: Subscription Action - Cancel
			 *
			 * @since 4.3.0
			 *
			 * @param array The action array.
			 *
			 * @return array The action array.
			 */
			$actions['cancel'] = apply_filters( 'wpcw_subscription_action_cancel', array(
				'url'     => $this->get_cancel_url(),
				'name'    => esc_html__( 'Cancel', 'wp-courseware' ),
				'confirm' => esc_html__( 'Are you sure you want to cancel your subscription? This CANNOT be undone!', 'wp-courseware' ),
			) );
		}

		/**
		 * Filter: Subscription Actions.
		 *
		 * @since 4.3.0
		 *
		 * @param array $actions The array of actions.
		 * @param Subscription $this The subscription object.
		 *
		 * @return array $actions The array of newly added or modified actions.
		 */
		return apply_filters( 'wpcw_subscriptio_actions', $actions, $this );
	}
}
