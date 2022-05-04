<?php
/**
 * WP Courseware Gateway Stripe - Api.
 *
 * @since 4.3.0
 * @subpackage Gateways\Stripe
 * @package WPCW
 */

namespace WPCW\Gateways\Stripe;

use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Collection;
use Stripe\Coupon;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;
use Stripe\Exception\ExceptionInterface;
use Stripe\Exception\IdempotencyException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Refund;
use Stripe\SetupIntent;
use Stripe\Source;
use Stripe\Stripe;
use Stripe\Subscription;
use WP_Error;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Stripe_Api.
 *
 * @since 4.3.0
 */
class Stripe_Api {

	/**
	 * @var string Stripe API version.
	 * @since 4.3.0
	 */
	private $api_version = '2019-03-14';

	/**
	 * @var string Stripe API Secret Key.
	 * @since 4.3.0
	 */
	private $secret_key;

	/**
	 * @var bool Test Mode
	 * @since 4.3.0
	 */
	private $test_mode = false;

	/**
	 * @var bool Is logging enabled?
	 * @since 4.3.0
	 */
	private $logging = false;

	/**
	 * @var bool Is API available?
	 * @since 4.3.0
	 */
	private $available = false;

	/**
	 * Stripe_Api constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct( $key, $test_mode = false, $logging = false ) {
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once WPCW_INC_PATH . 'gateways/stripe/stripe-sdk/init.php';
		}

		$this->secret_key = $key;
		$this->test_mode  = $test_mode;
		$this->logging    = $logging;

		$this->setup_api();
	}

	/**
	 * Internal: Setup Stripe API.
	 *
	 * @since 4.3.0
	 *
	 * @throws Stripe_Exception
	 *
	 * @return bool|object True on successful setup or error object on failure.
	 */
	protected function setup_api() {
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			throw new Stripe_Exception( 'stripe-api-not-included', esc_html__( 'Stripe API SDK is not included. Check for a conflict with another plugin.', 'wp-courseware' ) );
		}

		if ( empty( $this->secret_key ) ) {
			throw new Stripe_Exception( 'stripe-api-secret-key-empty', esc_html__( 'The Stripe API keys entered in settings are incorrect.', 'wp-courseware' ) );
		}

		// Set Stripe Vars.
		Stripe::setAppInfo( 'WP Courseware - Stripe', WPCW_VERSION, esc_url( site_url() ) );
		Stripe::setApiKey( $this->secret_key );
		Stripe::setApiVersion( $this->api_version );

		// Set Available
		$this->available = true;

		return $this->available;
	}

	/**
	 * Call Api Method.
	 *
	 * @since 4.3.0
	 *
	 * @param string           $method_name The method name.
	 * @param string|int|array $method_args Optional. The method args.
	 *
	 * @return WP_Error
	 */
	protected function call_api_method( $method_name ) {
		try {
			$number_of_arguments = func_num_args();

			if ( ! $number_of_arguments ) {
				throw new Stripe_Exception( 'stripe-api-method-does-not-exist', esc_html__( 'Stripe API Error: Method does not exist or is not callable.', 'wp-courseware' ) );
			}

			$this->check_is_api_available();

			$method_name = func_get_arg( 0 );

			if ( ! method_exists( $this, $method_name ) ) {
				throw new Stripe_Exception( 'stripe-api-method-does-not-exist', esc_html__( 'Stripe API Error: Method does not exist or is not callable.', 'wp-courseware' ) );
			}

			if ( $number_of_arguments === 3 ) {
				$method_id   = func_get_arg( 1 );
				$method_args = func_get_arg( 2 );

				$this->log( sprintf( 'Calling API Method: "%1$s" - ID: %2$s - Arguments: %2$s', $method_name, $method_id, wpcw_print_r( $method_args, true ) ) );

				return $this->{$method_name}( $method_id, $method_args );
			} else {
				$method_args = func_get_arg( 1 );

				$this->log( sprintf( 'Calling API Method: "%1$s" - Arguments: %2$s', $method_name, wpcw_print_r( $method_args, true ) ) );

				return $this->{$method_name}( $method_args );
			}
		} catch ( ApiErrorException $exception ) {
			return $this->catch_stripe_exception( $exception, 'api_error', esc_html__( 'The Stripe API request was invalid, please try again.', 'wp-courseware' ) );
		} catch ( ApiConnectionException $exception ) {
			return $this->catch_stripe_exception( $exception, 'api_connection_error', esc_html__( 'There was an error processing your payment ( Stripe\'s API may be down ), please try again.', 'wp-courseware' ) );
		} catch ( AuthenticationException $exception ) {
			return $this->catch_stripe_exception( $exception, 'authentication_error', esc_html__( 'The Stripe API keys entered in settings are incorrect.', 'wp-courseware' ) );
		} catch ( CardException $exception ) {
			return $this->catch_stripe_exception( $exception, 'card_error', esc_html__( 'There was an error processing your payment, please try again.', 'wp-courseware' ) );
		} catch ( IdempotencyException $exception ) {
			return $this->catch_stripe_exception( $exception, 'idempotency_error', esc_html__( 'Your request to process this order has expired, please try again.', 'wp-courseware' ) );
		} catch ( InvalidRequestException $exception ) {
			return $this->catch_stripe_exception( $exception, 'invalid_request_error', esc_html__( 'The Stripe API request was invalid, please try again.', 'wp-courseware' ) );
		} catch ( RateLimitException $exception ) {
			return $this->catch_stripe_exception( $exception, 'rate_limit_error', esc_html__( 'There was an error processing your payment ( Too many requests made to the Stripe API too quickly ), please wait a while and try again.', 'wp-courseware' ) );
		} catch ( Stripe_Exception $exception ) {
			return $this->catch_exception( $exception, 'stripe_error' );
		}
	}

	/**
	 * Is Api Available?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if is available, false otherwise.
	 */
	public function is_available() {
		return (bool) $this->available;
	}

	/**
	 * Check: Is Api Available?
	 *
	 * @since 4.3.0
	 *
	 * @throws Stripe_Exception
	 */
	protected function check_is_api_available() {
		if ( ! $this->is_available() ) {
			throw new Stripe_Exception( 'api-not-available', esc_html__( 'Stripe Api Not Available', 'wp-courseware' ) );
		}
	}

	/**
	 * Log Stripe API Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The log message.
	 */
	protected function log( $message = '' ) {
		if ( empty( $message ) || ! $this->logging ) {
			return;
		}

		$log_entry = "\n" . '====Start Stripe API Log====' . "\n" . $message . "\n" . '====End Stripe API Log====' . "\n";

		wpcw_log( $log_entry );
		wpcw_file_log( array( 'message' => $log_entry ) );
	}

	/**
	 * Catch Stripe Exception.
	 *
	 * @since 4.3.0
	 *
	 * @param ExceptionInterface $exception The stripe exception.
	 * @param string             $localized_message Optional. The localized message.
	 *
	 * @return object The stripe exception object.
	 */
	protected function catch_stripe_exception( ExceptionInterface $exception, $type = 'card_error', $localized_message = '' ) {
		return (object) array(
			'error' => (object) array(
				'type'      => $type,
				'code'      => ! $exception->getCode() ? $exception->getCode() : $type,
				'message'   => $exception->getMessage(),
				'localized' => $localized_message,
			),
		);
	}

	/**
	 * Catch Exception.
	 *
	 * @since 4.3.0
	 *
	 * @param Stripe_Exception $exception The error string.
	 *
	 * @return object The error object.
	 */
	protected function catch_exception( Stripe_Exception $exception, $type = 'stripe_error' ) {
		return (object) array(
			'error' => (object) array(
				'type'      => $type,
				'code'      => ! $exception->getCode() ? $exception->getCode() : $type,
				'message'   => $exception->getMessage(),
				'localized' => $exception->getLocalizedMessage(),
			),
		);
	}

	/** Core API Methods -------------------------------------------------- */

	/**
	 * Get Source Object.
	 *
	 * @since 4.3.0
	 *
	 * @param string $source_id The source id.
	 *
	 * @return Source|object A source object or error object on failure.
	 */
	public function get_source( $source_id = '' ) {
		return $this->call_api_method( 'api_get_source', $source_id );
	}

	/**
	 * Internal Stripe API: Get Source Object.
	 *
	 * @param string $source_id The source id.
	 *
	 * @return Source|object A source object or error object on failure.
	 */
	protected function api_get_source( $source_id = '' ) {
		if ( empty( $source_id ) ) {
			throw new Stripe_Exception( 'stripe-api-source-empty', esc_html__( 'Source Id is empty. Please try again.', 'wp-courseware' ) );
		}

		return Source::retrieve( $source_id );
	}

	/**
	 * Get Stripe Customer.
	 *
	 * @since 4.3.0
	 *
	 * @param string $customer_id The stripe customer id.
	 *
	 * @return Customer|object The stripe customer object or error object on failure.
	 */
	public function get_customer( $customer_id = '' ) {
		return $this->call_api_method( 'api_get_customer', $customer_id );
	}

	/**
	 * Internal Stripe API: Get Customer.
	 *
	 * @param string $customer_id The customer id.
	 *
	 * @return Customer|object The customer object or error object on failure.
	 */
	protected function api_get_customer( $customer_id = '' ) {
		if ( empty( $customer_id ) ) {
			throw new Stripe_Exception( 'stripe-api-customer-id-empty', esc_html__( 'The customer id is empty. Please try again.', 'wp-courseware' ) );
		}

		return Customer::retrieve( $customer_id );
	}

	/**
	 * Create Stripe Customer.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The stripe customer args.
	 *
	 * @return Customer|object The stripe customer object or error object on failure.
	 */
	public function create_customer( $args = array() ) {
		return $this->call_api_method( 'api_create_customer', $args );
	}

	/**
	 * Internal Stripe Api: Create Stripe Customer.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The stripe customer args.
	 *
	 * @return Customer|object The stripe customer object or error object on failure.
	 */
	protected function api_create_customer( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-create-customer-args-empty', esc_html__( 'The data used to create the stripe customer we\'re empty. Please try again.', 'wp-courseware' ) );
		}

		return Customer::create( $args );
	}

	/**
	 * Update Stripe Customer.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe customer id.
	 * @param array  $args The stripe customer args.
	 *
	 * @return Customer|object The stripe customer object or error object on failure.
	 */
	public function update_customer( $id = '', $args = array() ) {
		return $this->call_api_method( 'api_update_customer', $id, $args );
	}

	/**
	 * Internal Stripe Api: Update Stripe Customer.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe customer id.
	 * @param array  $args The stripe customer args.
	 *
	 * @return Customer|object The stripe customer object or error object on failure.
	 */
	protected function api_update_customer( $id, $args = array() ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-update-customer-id-empty', esc_html__( 'The id used to update the stripe customer was empty. Please try again.', 'wp-courseware' ) );
		}

		return Customer::update( $id, $args );
	}

	/**
	 * Add Customer Source.
	 *
	 * @since 4.3.0
	 *
	 * @param string $customer_id The stripe customer id.
	 * @param string $source_id The source id to add to customer.
	 *
	 * @return Source|object Source object or error object on failure.
	 */
	public function add_customer_source( $customer_id, $source_id ) {
		return $this->call_api_method( 'api_add_customer_source', array(
			'customer_id' => $customer_id,
			'source_id'   => $source_id
		) );
	}

	/**
	 * Internal Stripe API: Add Customer Source.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args {
	 *  customer_id The customer id.
	 *  source_id The source id.
	 * }
	 *
	 * @return Source|object The Source object or error object on failure.
	 */
	protected function api_add_customer_source( $args = array() ) {
		$customer_id = $args['customer_id'];
		$source_id   = $args['source_id'];

		if ( empty( $customer_id ) ) {
			throw new Stripe_Exception( 'stripe-api-add-customer-source-customer-id-empty', esc_html__( 'Customer Id cannot be empty when adding a source. Please try again.', 'wp-courseware' ) );
		}

		if ( empty( $source_id ) ) {
			throw new Stripe_Exception( 'stripe-api-add-customer-source-id-empty', esc_html__( 'Source Id cannot be empty when adding a source. Please try again.', 'wp-courseware' ) );
		}

		$customer = $this->get_customer( $customer_id );

		if ( ! empty( $customer->error ) ) {
			return $customer;
		}

		return $customer->sources->create( array( 'source' => $source_id ) );
	}

	/**
	 * Create Stripe Charge.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The stripe charge arguments.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return Charge|object The stripe charge object or error object on failure.
	 */
	public function create_charge( $args = array() ) {
		return $this->call_api_method( 'api_create_charge', $args );
	}

	/**
	 * Internal Stripe Api: Create Stripe Charge.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The stripe charge arguments.
	 *
	 * @return Charge|object The stripe charge object or error object on failure.
	 */
	protected function api_create_charge( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-create-charge-data-empty', esc_html__( 'The arguments needed to create the stripe chare were empty. Please try again.', 'wp-courseware' ) );
		}

		return Charge::create( $args );
	}

	/**
	 * Get Stripe Charge.
	 *
	 * @since 4.3.0
	 *
	 * @param string $charge_id The stripe charge id.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return Charge|object The stripe charge object or error object on failure.
	 */
	public function get_charge( $charge_id = '' ) {
		return $this->call_api_method( 'api_get_charge', $charge_id );
	}

	/**
	 * Internal Stripe Api: Get Stripe Charge.
	 *
	 * @since 4.3.0
	 *
	 * @param string $charge_id The stripe charge id.
	 *
	 * @return Charge|object The stripe charge object or error object on failure.
	 */
	protected function api_get_charge( $charge_id = '' ) {
		if ( empty( $charge_id ) ) {
			throw new Stripe_Exception( 'stripe-api-get-charge-id-empty', esc_html__( 'The charge id needed to retrieve the stripe charge was empty. Please try again.', 'wp-courseware' ) );
		}

		return Charge::retrieve( array( 'id' => $charge_id, 'expand' => array( 'balance_transaction' ) ) );
	}

	/**
	 * Create Stripe SetupIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param array $args The stripe setup intent arguments.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return SetupIntent|object The stripe setup intent object or error object on failure.
	 */
	public function create_setup_intent( $args = array() ) {
		return $this->call_api_method( 'api_create_setup_intent', $args );
	}

	/**
	 * Internal Stripe Api: Create Stripe SetupIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param array $args The stripe charge data.
	 *
	 * @return SetupIntent|object The stripe setup intent object or error object on failure.
	 */
	protected function api_create_setup_intent( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-create-setup-intent-args-empty', esc_html__( 'The arguments needed to create the stripe setup intent object was empty. Please try again.', 'wp-courseware' ) );
		}

		return SetupIntent::create( $args );
	}

	/**
	 * Get Stripe SetupIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe setup intent id.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return SetupIntent|object The stripe setup intent object or error object on failure.
	 */
	public function get_setup_intent( $id = '' ) {
		return $this->call_api_method( 'api_get_setup_intent', $id );
	}

	/**
	 * Internal Stripe Api: Get Stripe SetupIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe setup intent id.
	 *
	 * @return SetupIntent|object The stripe setup intent object or error object on failure.
	 */
	protected function api_get_setup_intent( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-get-setup-intent-id-empty', esc_html__( 'The id needed to retrieve the stripe setup intent was empty. Please try again.', 'wp-courseware' ) );
		}

		return SetupIntent::retrieve( $id );
	}

	/**
	 * Update Stripe SetupIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe setup intent id.
	 * @param array  $args The stripe setup intent arguments.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return SetupIntent|object The stripe setup intent object or error object on failure.
	 */
	public function update_setup_intent( $id = '', $args = array() ) {
		return $this->call_api_method( 'api_update_setup_intent', $id, $args );
	}

	/**
	 * Internal Stripe Api: Update Stripe SetupIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe setup intent id.
	 * @param array  $args The stripe setup intent arguments.
	 *
	 * @return SetupIntent|object The stripe setup intent object or error object on failure.
	 */
	protected function api_update_setup_intent( $id = '', $args = array() ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-update-setup-intent-id-empty', esc_html__( 'The id needed to update the stripe setup intent was empty. Please try again.', 'wp-courseware' ) );
		}

		return SetupIntent::update( $id, $args );
	}

	/**
	 * Create Stripe PaymentIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param array $args The stripe payment intent arguments.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return PaymentIntent|object The stripe payment intent object or error object on failure.
	 */
	public function create_payment_intent( $args = array() ) {
		return $this->call_api_method( 'api_create_payment_intent', $args );
	}

	/**
	 * Internal Stripe Api: Create Stripe PaymentIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param array $args The stripe payment intent arguments.
	 *
	 * @return PaymentIntent|object The stripe payment intent object or error object on failure.
	 */
	protected function api_create_payment_intent( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-create-payment-intent-args-empty', esc_html__( 'The arguments needed to create the stripe payment intent were empty. Please try again.', 'wp-courseware' ) );
		}

		return PaymentIntent::create( $args );
	}

	/**
	 * Get Stripe PaymentIntent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe payment intent id.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return PaymentIntent|object The stripe payment intent object or error object on failure.
	 */
	public function get_payment_intent( $id = '' ) {
		return $this->call_api_method( 'api_get_payment_intent', $id );
	}

	/**
	 * Internal Stripe Api: Get Stripe Payment Intent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe payment intent id.
	 *
	 * @return PaymentIntent|object The stripe payment intent object or error object on failure.
	 */
	protected function api_get_payment_intent( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-get-payment-intent-id-empty', esc_html__( 'The id needed to retrieve the stripe payment intent was empty. Please try again.', 'wp-courseware' ) );
		}

		return PaymentIntent::retrieve( $id );
	}

	/**
	 * Update Stripe Payment Intent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe payment intent id.
	 * @param array  $args The arguments to update payment intent.
	 *
	 * @throws Stripe_Exception
	 *
	 * @return PaymentIntent|object The stripe payment intent object or error object on failure.
	 */
	public function update_payment_intent( $id = '', $args = array() ) {
		return $this->call_api_method( 'api_update_payment_intent', $id, $args );
	}

	/**
	 * Internal Stripe Api: Update Stripe Payment Intent.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe payment intent id.
	 * @param array  $args The arguments to update payment intent.
	 *
	 * @return PaymentIntent|object The stripe payment intent object or error object on failure.
	 */
	protected function api_update_payment_intent( $id = '', $args = array() ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-update-payment-intent-id-empty', esc_html__( 'The id needed to update the stripe payment intent was empty. Please try again.', 'wp-courseware' ) );
		}

		return PaymentIntent::update( $id, $args );
	}

	/**
	 * Get Balance Transaction.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id
	 *
	 * @return BalanceTransaction|object The stripe balance transaction object or error object on failure.
	 */
	public function get_balance_transaction( $id = '' ) {
		return $this->call_api_method( 'api_get_balance_transaction', $id );
	}

	/**
	 * Internal Stripe Api: Get Balance Transaction.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The stripe payment intent id.
	 *
	 * @return BalanceTransaction|object The stripe balance transaction object or error object on failure.
	 */
	protected function api_get_balance_transaction( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-get-balance-transaction-id-empty', esc_html__( 'The id needed to retrieve the stripe balance transaction was empty. Please try again.', 'wp-courseware' ) );
		}

		return BalanceTransaction::retrieve( $id );
	}

	/**
	 * Create Stripe Payment Method.
	 *
	 * @since 4.6.3
	 *
	 * @param array $args The arguments to create the payment method.
	 *
	 * @return PaymentMethod|object The stripe payment method object or error object on failure.
	 */
	public function create_payment_method( $args = array() ) {
		return $this->call_api_method( 'api_create_payment_method', $args );
	}

	/**
	 * Internal Stripe Api: Create PaymentMethod.
	 *
	 * @since 4.6.3
	 *
	 * @param array $args The arguments to create the payment method.
	 *
	 * @return PaymentMethod|object The stripe payment method object or error object on failure.
	 */
	protected function api_create_payment_method( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-create-payment-method-args-empty', esc_html__( 'The arguments needed to create a stripe payment method were empty. Please try again.', 'wp-courseware' ) );
		}

		return PaymentMethod::create( $args );
	}

	/**
	 * Update Stripe Payment Method.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The id of the payment method.
	 * @param array  $args The arguments to update the payment method.
	 *
	 * @return PaymentMethod|object The stripe payment method object or error object on failure.
	 */
	public function update_payment_method( $id = '', $args = array() ) {
		return $this->call_api_method( 'api_update_payment_method', $id, $args );
	}

	/**
	 * Internal Stripe Api: Update PaymentMethod.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The id of the payment method.
	 * @param array  $args The paramaters to update the payment method.
	 *
	 * @return PaymentMethod|object The stripe payment method object or error object on failure.
	 */
	protected function api_update_payment_method( $id = '', $args = array() ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-update-payment-method-id-empty', esc_html__( 'The id needed to update the stripe payment method was empty. Please try again.', 'wp-courseware' ) );
		}

		return PaymentMethod::update( $id, $args );
	}

	/**
	 * Get Stripe Payment Method.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The id of the payment method.
	 *
	 * @return PaymentMethod|object The stripe payment method object or error object on failure.
	 */
	public function get_payment_method( $id = '', $args = array() ) {
		return $this->call_api_method( 'api_get_payment_method', $id, $args );
	}

	/**
	 * Internal Stripe Api: Get PaymentMethod.
	 *
	 * @since 4.6.3
	 *
	 * @param string $id The id of the payment method.
	 *
	 * @return PaymentMethod|object The stripe payment method object or error object on failure.
	 */
	protected function api_get_payment_method( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-update-payment-method-id-empty', esc_html__( 'The id needed to update the stripe payment method was empty. Please try again.', 'wp-courseware' ) );
		}

		return PaymentMethod::retrieve( $id );
	}

	/**
	 * Get Customers Stripe Payment Methods.
	 *
	 * @since 4.6.3
	 *
	 * @param string $args The customer payment methods args.
	 *
	 * @return Collection Collection of PaymentMethod Objects.
	 */
	public function get_customer_payment_methods( $args = array() ) {
		return $this->call_api_method( 'api_get_customer_payment_methods', $id, $args );
	}

	/**
	 * Internal Stripe Api: Get PaymentMethod.
	 *
	 * @since 4.6.3
	 *
	 * @param string $args The customer payment methods args.
	 *
	 * @return Collection Collection of PaymentMethod Objects.
	 */
	protected function api_get_customer_payment_methods( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception(
				'stripe-api-get-customer-payment-methods-args-empty',
				esc_html__( 'The args needed to retrieve the customers stripe payment methods were empty. Please try again.', 'wp-courseware' )
			);
		}

		return PaymentMethod::all( $args );
	}

	/**
	 * Get a Product.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The product id.
	 *
	 * @return Product|object The Stripe Product object or error object on failure.
	 */
	public function get_product( $id = '' ) {
		return $this->call_api_method( 'api_get_product', $id );
	}

	/**
	 * Internal Stripe Api: Get a Product.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The product id.
	 *
	 * @return Product|object The Stripe Product object or error object on failure.
	 */
	protected function api_get_product( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-product-id-empty', esc_html__( 'The id to retrieve the stripe product is empty. Please try again.', 'wp-courseware' ) );
		}

		return Product::retrieve( $id );
	}

	/**
	 * Create Product.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The product args.
	 *
	 * @return Product|object The Stripe Product object or error object on failure.
	 */
	public function create_product( $args = array() ) {
		return $this->call_api_method( 'api_create_product', $args );
	}

	/**
	 * Internal Stripe Api: Create Product.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The product args.
	 *
	 * @return Product|object The Stripe Product object or error object on failure.
	 */
	protected function api_create_product( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-product-args-empty', esc_html__( 'The args to create the stripe product are empty. Please try again.', 'wp-courseware' ) );
		}

		return Product::create( $args );
	}

	/**
	 * Get a Plan.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The plan id.
	 *
	 * @return Plan|object The Stripe Plan object or error object on failure.
	 */
	public function get_plan( $id = '' ) {
		return $this->call_api_method( 'api_get_plan', $id );
	}

	/**
	 * Internal Stripe Api: Get a Plan.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The plan id.
	 *
	 * @return Plan|object The Stripe Plan object or error object on failure.
	 */
	protected function api_get_plan( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-plan-id-empty', esc_html__( 'The id to retrieve the stripe plan is empty. Please try again.', 'wp-courseware' ) );
		}

		return Plan::retrieve( $id );
	}

	/**
	 * Create a Plan.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The plan args.
	 *
	 * @return Plan|object The Stripe Plan object or error object on failure.
	 */
	public function create_plan( $args = array() ) {
		return $this->call_api_method( 'api_create_plan', $args );
	}

	/**
	 * Internal Stripe Api: Create a Plan.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The plan args.
	 *
	 * @return Plan|object The Stripe Plan object or error object on failure.
	 */
	protected function api_create_plan( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-plan-args-empty', esc_html__( 'The args to create the stripe plan are empty. Please try again.', 'wp-courseware' ) );
		}

		return Plan::create( $args );
	}

	/**
	 * Get a Subscripton.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The plan id.
	 *
	 * @return Subscription|object The Stripe Subscription object or error object on failure.
	 */
	public function get_subscription( $id = '' ) {
		return $this->call_api_method( 'api_get_subscription', $id );
	}

	/**
	 * Internal Stripe Api: Get a Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The subscription id.
	 *
	 * @return Subscription|object The Stripe Subscription object or error object on failure.
	 */
	protected function api_get_subscription( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-subscription-id-empty', esc_html__( 'The id to retrieve the stripe subscription is empty. Please try again.', 'wp-courseware' ) );
		}

		return Subscription::retrieve( $id );
	}

	/**
	 * Get Subscription Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The subscription id.
	 *
	 * @return Charge The subscription charge.
	 */
	public function get_subscription_payment( $id = '' ) {
		return $this->call_api_method( 'api_get_subscription_payment', $id );
	}

	/**
	 * Internal Stripe Api: Get Subscription Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The subscription id.
	 *
	 * @return Charge The subscription charge.
	 */
	protected function api_get_subscription_payment( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-subscription-id-empty', esc_html__( 'The subscription id required to get the charge/payment is empty or not set. Please try again.', 'wp-courseware' ) );
		}

		$invoices = $this->get_invoices( array(
			'subscription' => $id,
			'limit'        => 1,
			'expand'       => array( 'data.charge.balance_transaction' ),
		) );

		if ( ! empty( $invoices->error ) ) {
			return $invoices;
		}

		$payment = false;

		if ( is_array( $invoices->data ) && isset( $invoices->data[0] ) ) {
			$payment = $invoices->data[0]->charge;
		}

		return $payment;
	}

	/**
	 * Create a Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The subscription args.
	 *
	 * @return Subscription|object The Stripe Subscription object or error object on failure.
	 */
	public function create_subscription( $args = array() ) {
		return $this->call_api_method( 'api_create_subscription', $args );
	}

	/**
	 * Internal Stripe Api: Create a Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The plan args.
	 *
	 * @return Subscription|object The Stripe Subscription object or error object on failure.
	 */
	protected function api_create_subscription( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-subscription-args-empty', esc_html__( 'The args to create the stripe subscription are empty. Please try again.', 'wp-courseware' ) );
		}

		return Subscription::create( $args );
	}

	/**
	 * Update a Subscription.
	 *
	 * @since 4.6.0
	 *
	 * @param string $id The subscription id.
	 * @param array  $args The subscription args.
	 *
	 * @return Subscription|object The Stripe Subscription object or error object on failure.
	 */
	public function update_subscription( $id = '', $args = array() ) {
		return $this->call_api_method( 'api_update_subscription', $id, $args );
	}

	/**
	 * Internal Stripe Api: Create a Subscription.
	 *
	 * @since 4.6.0
	 *
	 * @param string $id The subscription id.
	 * @param array  $args The subscription arguments.
	 *
	 * @return Subscription|object The Stripe Subscription object or error object on failure.
	 */
	protected function api_update_subscription( $id = '', $args = array() ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-update-subscription-id-empty', esc_html__( 'The id to update the stripe subscription is empty. Please try again.', 'wp-courseware' ) );
		}

		return Subscription::update( $id, $args );
	}

	/**
	 * Get Stripe Event.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The event id.
	 *
	 * @return Event|object The stripe event object.
	 */
	public function get_event( $id = '' ) {
		return $this->call_api_method( 'api_get_event', $id );
	}

	/**
	 * Internal Stripe Api: Get Stripe Event.
	 *
	 * @since 4.3.0
	 *
	 * @param string $id The event id.
	 *
	 * @return Event|object The stripe event object.
	 */
	protected function api_get_event( $id = '' ) {
		if ( empty( $id ) ) {
			throw new Stripe_Exception( 'stripe-api-event-id-empty', esc_html__( 'The event id is empty. Please try again.', 'wp-courseware' ) );
		}

		return Event::retrieve( $id );
	}

	/**
	 * Get Invoices.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The invoice query.
	 *
	 * @return Collection A collection object with invoices.
	 */
	public function get_invoices( $args = array() ) {
		return $this->call_api_method( 'api_get_invoices', $args );
	}

	/**
	 * Internal Stripe Api: Get Invoices.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The invoice query.
	 *
	 * @return Collection A collection object with invoices.
	 */
	public function api_get_invoices( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-invoice-query-args-empty', esc_html__( 'The query args to retrieve invoices are empty. Please try again.', 'wp-courseware' ) );
		}

		return Invoice::all( $args );
	}

	/**
	 * Create Refund.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The refund args.
	 *
	 * @return Refund The stripe refund object.
	 */
	public function create_refund( $args = array() ) {
		return $this->call_api_method( 'api_create_refund', $args );
	}

	/**
	 * Internal Stripe Api: Create Refund
	 *
	 * @since 4.3.0
	 *
	 * @param array $args The refund args.
	 *
	 * @return Refund The stripe refund object.
	 */
	public function api_create_refund( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-refund-args-empty', esc_html__( 'The refund args were empty. Please try again.', 'wp-courseware' ) );
		}

		return Refund::create( $args );
	}

	/**
	 * Create Coupon.
	 *
	 * @since 4.5.0
	 *
	 * @param array $args The coupon args.
	 *
	 * @return Refund The stripe coupon object.
	 */
	public function create_coupon( $args = array() ) {
		return $this->call_api_method( 'api_create_coupon', $args );
	}

	/**
	 * Internal Stripe Api: Create Coupon
	 *
	 * @since 4.5.0
	 *
	 * @param array $args The coupon args.
	 *
	 * @return Coupon The stripe coupon object.
	 */
	public function api_create_coupon( $args = array() ) {
		if ( empty( $args ) ) {
			throw new Stripe_Exception( 'stripe-api-coupon-args-empty', esc_html__( 'The coupon args were empty. Please try again.', 'wp-courseware' ) );
		}

		return Coupon::create( $args );
	}
}
