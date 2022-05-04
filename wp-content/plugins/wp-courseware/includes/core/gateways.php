<?php
/**
 * WP Courseware Gateways.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */
namespace WPCW\Core;

use WPCW\Gateways\Gateway;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Gateways.
 *
 * @since 4.6.3
 */
class Gateways {

	/**
	 * @var array Registered Gateways.
	 * @since 4.3.0
	 */
	protected $gateways = array();

	/**
	 * Gateways constructor.
	 *
	 * @since 4.6.3
	 */
	public function __construct() {
		$this->register_gateways();
	}

	/**
	 * Load Gateways.
	 *
	 * @since 4.3.0
	 */
	public function load() { /** Override. */ }

	/**
	 * Register Gateways.
	 *
	 * @since 4.3.0
	 */
	public function register_gateways() {
		$gateway_classes = array(
			'Gateway_Paypal',
			'Gateway_Stripe',
		);

		foreach ( $gateway_classes as $gateway_class ) {
			$class_name = "\\WPCW\\Gateways\\{$gateway_class}";
			if ( class_exists( $class_name ) ) {
				$gateway = new $class_name();
				if ( $gateway instanceof Gateway ) {
					$this->gateways[ $gateway->get_slug() ] = $gateway;
					$this->gateways[ $gateway->get_slug() ]->load();
				}
			}
		}

		$this->gateways = apply_filters( 'wpcw_gateways', $this->gateways );
	}

	/**
	 * Get Gateways Settings.
	 *
	 * @since 4.3.0
	 */
	public function get_gateways_settings() {
		$settings = array();

		if ( empty( $this->gateways ) ) {
			return $settings;
		}

		foreach ( $this->gateways as $gateway ) {
			if ( $gateway instanceof Gateway ) {
				$gateway_settings = $gateway->get_settings_fields();
				if ( ! empty( $gateway_settings ) ) {
					foreach ( $gateway_settings as $gateway_setting ) {
						$settings[] = $gateway_setting;
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Get Gateway.
	 *
	 * @since 4.3.0
	 *
	 * @param string $slug The gateway slug.
	 *
	 * @return Gateway|null The gateway object.
	 */
	public function get_gateway( $slug ) {
		return isset( $this->gateways[ $slug ] ) ? $this->gateways[ $slug ] : null;
	}

	/**
	 * Get Gateways.
	 *
	 * @since 4.3.0
	 *
	 * @return array|mixed|void
	 */
	public function get_gateways() {
		$gateways            = array();
		$gateways_registered = $this->gateways;
		$gateways_order      = (array) wpcw_get_setting( 'payment_gateways_order' );

		if ( ! empty( $gateways_registered ) && ! empty( $gateways_order ) ) {
			$gateways = array_replace( $gateways_order, $gateways_registered );
		} else {
			$gateways = $gateways_registered;
		}

		return $gateways;
	}

	/**
	 * Get Available Gateways.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of available gateway objects.
	 */
	public function get_available_gateways() {
		$available_gateways = array();

		foreach ( $this->get_gateways() as $gateway ) {
			if ( ! $gateway instanceof Gateway ) {
				continue;
			}

			$gateway->setup();

			if ( ! $gateway->is_available() ) {
				continue;
			}

			$available_gateways[ $gateway->get_slug() ] = $gateway;
		}

		return $available_gateways;
	}

	/**
	 * Get Available Gateways Count.
	 *
	 * @since 4.3.0
	 *
	 * @return int The count of available gateways.
	 */
	public function get_available_gateways_count() {
		return count( $this->get_available_gateways() );
	}

	/**
	 * Are Gateways Available?
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if they are available, False otherwise.
	 */
	public function are_gateways_available() {
		return (bool) ( $this->get_available_gateways_count() > 0 );
	}
}
