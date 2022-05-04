<?php
/**
 * WP Courseware Gateway Stripe - Stripe Customer.
 *
 * @package WPCW
 * @subpackage Gateways\Stripe
 * @since 4.3.0
 */
namespace WPCW\Gateways\Stripe;

use Stripe\Customer;
use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Stripe_Customer
 *
 * @since 4.3.0
 */
class Stripe_Customer {

	/**
	 * @var string The Stripe Customer Id.
	 * @since 4.3.0
	 */
	protected $id;

	/**
	 * @var Customer The stripe customer.
	 * @since 4.3.0
	 */
	protected $object;

	/**
	 * @var int The Student Id.
	 * @since 4.3.0
	 */
	protected $student_id;

	/**
	 * @var Stripe_Api The stripe api object.
	 * @since 4.3.0
	 */
	protected $api;

	/**
	 * Stripe_Customer constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param int        $student_id The student id.
	 * @param Stripe_Api $api Required. The stripe api.
	 */
	public function __construct( $student_id = 0, $api ) {
		if ( $student_id ) {
			$this->set_student_id( $student_id );
			if ( $stripe_customer_id = get_user_meta( $student_id, '_stripe_customer_id', true ) ) {
				$this->set_id( $stripe_customer_id );
			}
		}

		$this->api = $api;

		if ( ! $this->api->is_available() ) {
			throw new Stripe_Exception( 'api-not-available', esc_html__( 'Stripe Api Not Available', 'wp-courseware' ) );
		}
	}

	/**
	 * Set Stripe Customer Id.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The Stripe Customer Id.
	 */
	public function set_id( $id ) {
		$this->id = wpcw_clean( $id );
	}

	/**
	 * Get Stripe Customer Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The Stripe Customer Id.
	 */
	public function get_id() {
		return wpcw_clean( $this->id );
	}

	/**
	 * Set Customer Object.
	 *
	 * @since 4.3.0
	 *
	 * @param Customer $customer
	 */
	public function set_object( Customer $customer ) {
		$this->object = $customer;
	}

	/**
	 * Get Customer Object.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The args to get the customer.
	 *
	 * @return Customer The customer object.
	 */
	public function get_object( $args = array() ) {
		if ( ! $this->get_id() ) {
			$this->create( $args );
		}

		if ( empty( $this->object ) && $this->get_id() ) {
			$this->object = $this->api->get_customer( $this->get_id() );

			if ( ! empty( $this->object->error ) ) {
				throw new Stripe_Exception( $this->object->error->type, $this->object->error->message );
			}
		}

		return $this->object;
	}

	/**
	 * Set Student Id.
	 *
	 * @since 4.3.0
	 *
	 * @param int $student_id The student id.
	 */
	public function set_student_id( $student_id ) {
		$this->student_id = absint( $student_id );
	}

	/**
	 * Get Student Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The student id.
	 */
	public function get_student_id() {
		return absint( $this->student_id );
	}

	/**
	 * Get Student.
	 *
	 * @since 4.3.0
	 *
	 * @return Student|bool The student object or false.
	 */
	protected function get_student() {
		return $this->get_student_id() ? new Student( $this->get_student_id() ) : false;
	}

	/**
	 * Create Stripe Customer.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The Stripe customer args.
	 *
	 * @return string The Stripe customer id.
	 */
	public function create( $args = array() ) {
		$email   = isset( $_POST['email'] ) ? filter_var( $_POST['email'], FILTER_SANITIZE_EMAIL ) : '';
		$student = $this->get_student();

		if ( $student ) {
			$defaults = array(
				'email'       => $student->get_email(),
				'description' => $student->get_full_name(),
			);
		} else {
			$defaults = array(
				'email'       => $email,
				'description' => $email,
			);
		}

		/**
		 * Filter: Add Metadata to the Stripe Customer.
		 *
		 * @since 4.3.0
		 *
		 * @param array The metadata to add.
		 * @param Student The student object.
		 *
		 * @return array The metadata.
		 */
		$customer_metadata = apply_filters( 'wpcw_stripe_customer_metadata', array(), $student );

		// Set Metadata.
		if ( ! empty( $metadata ) ) {
			$defaults['metadata'] = $customer_metadata;
		}

		// Stripe Customer Args.
		$args = wp_parse_args( $args, $defaults );

		// Create Customer.
		$customer = $this->api->create_customer( $args );

		if ( ! empty( $customer->error ) ) {
			throw new Stripe_Exception( wpcw_print_r( $customer, true ), $customer->error->message );
		}

		// Set Details and Clear Cache.
		$this->set_id( $customer->id );
		$this->set_object( $customer );

		// Attach Stripe Customer Id.
		if ( $this->get_student_id() ) {
			update_user_meta( $this->get_student_id(), '_stripe_customer_id', $customer->id );
		}

		/**
		 * Action: Stripe Create Customer.
		 *
		 * @since 4.3.0
		 *
		 * @param array $args The args passed to create the customer.
		 * @param Customer The stripe customer.
		 */
		do_action( 'wpcw_stripe_create_customer', $args, $customer );

		return $customer->id;
	}

	/**
	 * Add Stripe Customer Source.
	 *
	 * @since 4.3.0
	 *
	 * @param int  $source_id The source id.
	 * @param bool $retry If we need to retry.
	 */
	public function add_source( $source_id, $retry = true ) {
		if ( ! $this->get_id() ) {
			$this->create();
		}

		$source = $this->api->add_customer_source( $this->get_id(), $source_id );

		if ( ! empty( $source->error ) ) {
			if ( $this->is_no_such_customer_error( $source->error ) && $retry ) {
				delete_user_meta( $this->get_student_id(), '_stripe_customer_id' );
				$this->create();

				return $this->add_source( $source_id, false );
			} else {
				return $source;
			}
		} elseif ( empty( $source->id ) ) {
			throw new Stripe_Exception( wpcw_print_r( $source, true ), sprintf( __( 'Unable to add Source Id %1$s to Stripe Customer %2$s.', 'wp-courseware' ), wpcw_clean( $source_id ), wpcw_clean( $this->get_id() ) ) );
		}

		/**
		 * Action: Stripe Add Customer Source.
		 *
		 * @since 4.3.0
		 *
		 * @param string $customer_id The customer id.
		 * @param string $source_id The source id.
		 * @param Source The stripe source object.
		 * @param Stripe_Customer The stripe customer object.
		 */
		do_action( 'wpcw_stripe_add_customer_source', $this->get_id(), $source_id, $source, $this );

		return $source->id;
	}

	/**
	 * Error: No such customer.
	 *
	 * @since 4.3.0
	 *
	 * @param object $error The error object.
	 */
	public function is_no_such_customer_error( $error ) {
		return ( $error && 'invalid_request_error' === $error->type && preg_match( '/No such customer/i', $error->message ) );
	}
}
