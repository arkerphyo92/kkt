<?php
/**
 * WP Courseware Gateway Stripe - Exception.
 *
 * @package WPCW
 * @subpackage Gateways\Stripe
 * @since 4.3.0
 */
namespace WPCW\Gateways\Stripe;

use Exception;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Exception.
 *
 * @since 4.3.0
 */
class Stripe_Exception extends Exception {

	/**
	 * @var string The Sanitized / localized error message.
	 * @since 4.3.0
	 */
	protected $localized_message;

	/**
	 * Stripe Exception Constructor..
	 *
	 * @since 4.3.0
	 *
	 * @param string $error_message The full error message.
	 * @param string $localized_message User-friendly translated error message.
	 */
	public function __construct( $error_message = '', $localized_message = '' ) {
		$this->localized_message = $localized_message;
		parent::__construct( $error_message );
	}

	/**
	 * Returns the localized message.
	 *
	 * @since 4.3.0
	 *
	 * @return string The localized message.
	 */
	public function getLocalizedMessage() {
		return $this->localized_message;
	}
}