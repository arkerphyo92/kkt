<?php
/**
 * WP Courseware Payment Gateway - PayPal
 *
 * @since 4.3.0
 * @subpackage Emails
 * @package WPCW
 */

namespace WPCW\Gateways;

use WPCW\Models\Course;
use WPCW\Models\Order;
use WPCW\Models\Order_Item;
use WPCW\Models\Student;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Gateway_Paypal.
 *
 * @since 4.3.0
 */
class Gateway_Paypal extends Gateway {

	/**
	 * @var string PayPal Email.
	 * @since 4.3.0
	 */
	protected $email = '';

	/**
	 * @var string PayPal Identity Token.
	 * @since 4.3.0
	 */
	protected $identity_token = '';

	/**
	 * @var PayPal Image Url.
	 * @since 4.3.0
	 */
	protected $image_url = '';

	/**
	 * @var string Is PayPal Sandbox enabled?
	 */
	protected $sandbox = 'no';

	/**
	 * @var string Are IPN notifications enabled?
	 * @since 4.3.0
	 */
	protected $ipn = 'yes';

	/**
	 * @var array Api credentials.
	 * @since 4.3.0
	 */
	protected $api_creds = array(
		'live_api_username'     => '',
		'live_api_password'     => '',
		'live_api_signature'    => '',
		'sandbox_api_username'  => '',
		'sandbox_api_password'  => '',
		'sandbox_api_signature' => '',
	);

	/**
	 * @var string Logging enabled?
	 * @since 4.3.0
	 */
	protected $logging = 'no';

	/**
	 * @var bool Are we processing a subscription?
	 * @since 4.3.0
	 */
	protected $is_subscription = false;

	/**
	 * Gateway PayPal constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id                 = 'paypal';
		$this->method_title       = esc_html__( 'PayPal', 'wp-courseware' );
		$this->method_description = esc_html__( 'PayPal Standard sends customers to PayPal to enter their payment information. PayPal IPN requires fsockopen/cURL support to update order statuses after payment.', 'wp-courseware' );
		$this->title              = esc_html__( 'PayPal', 'wp-courseware' );
		$this->description        = esc_html__( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', 'wp-courseware' );
		$this->order_button_text  = esc_html__( 'Proceed to PayPal', 'wp-courseware' );
		$this->has_fields         = false;
		$this->supports           = array( 'courses', 'refunds', 'cancellations' );

		parent::__construct();
	}

	/**
	 * Get PayPal Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of settings fields for PayPal
	 */
	public function get_settings_fields() {
		$settings = parent::get_settings_fields();

		$paypal_settings = array(
			array(
				'type'        => 'text',
				'key'         => $this->get_setting_key( 'email' ),
				'title'       => esc_html__( 'PayPal Email', 'wp-courseware' ),
				'placeholder' => esc_html__( 'PayPal Email', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Please enter your PayPal email address. This is needed in order to make a payment.', 'wp-courseware' ),
				'default'     => '',
			),
			array(
				'type'        => 'fopassword',
				'key'         => $this->get_setting_key( 'identity_token' ),
				'title'       => esc_html__( 'PayPal Identity Token', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'This will allow payments to be verified without the need for PayPal IPN.', 'wp-courseware' ),
				'desc'        => sprintf(
					__( '<a href="%s" target="_blank">Enable Paymet Data Transfer (PDT)</a> in PayPal and then copy your Identity Token above.', 'wp-courseware' ),
					$this->is_sandbox_enabled() ? esc_url_raw( 'https://www.sandbox.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-website-payments' ) : esc_url_raw( 'https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-website-payments' )
				),
				'default'     => '',
			),
			array(
				'type'     => 'content',
				'key'      => $this->get_setting_key( 'ipn_url' ),
				'title'    => esc_html__( 'PayPal IPN ( required )', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'In order for PayPal to function accurately, you must configure your PayPal IPN.', 'wp-courseware' ),
				/* translators: %1$s The webhook url, %2$s The stripe account url. */
				'content'  => sprintf(
					__( 'The following url <code>%1$s</code> is required to indicate refunds, chargebacks and cancellations. You can paste it in to your <a target="_blank" href="%2$s">PayPal IPN Settings</a>.', 'wp-courseware' ),
					esc_url_raw( $this->get_notify_url() ),
					$this->is_sandbox_enabled() ? esc_url_raw( 'https://www.sandbox.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify' ) : esc_url_raw( 'https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_profile-ipn-notify' )
				),
				'default'  => '',
			),
			array(
				'type'  => 'heading',
				'key'   => $this->get_setting_key( 'advanced_settings_heading' ),
				'title' => esc_html__( 'Advanced Settings', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to the interactions with the PayPal gateway.', 'wp-courseware' ),
			),
			array(
				'type'        => 'imageinput',
				'key'         => $this->get_setting_key( 'image' ),
				'image_key'   => $this->get_setting_key( 'image_id' ),
				'title'       => esc_html__( 'PayPal Image Url', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Optionally enter the URL to a 150x50px image displayed as your logo in the upper left corner of the PayPal checkout pages.', 'wp-courseware' ),
				'desc'        => __( 'Image size must be <strong>150x50</strong> pixels.', 'wp-courseware' ),
				'component'   => true,
				'settings'    => array(
					array(
						'key'     => $this->get_setting_key( 'image' ),
						'type'    => 'imageinput',
						'default' => '',
					),
					array(
						'key'     => $this->get_setting_key( 'image_id' ),
						'type'    => 'number',
						'default' => 0,
					),
				),
			),
			array(
				'type'     => 'checkbox',
				'key'      => $this->get_setting_key( 'sandbox' ),
				'title'    => esc_html__( 'PayPal Sandbox', 'wp-courseware' ),
				'label'    => esc_html__( 'Enable PayPal sandbox', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'PayPal Sandbox can be used to test payments.', 'wp-courseware' ),
				'desc'     => sprintf( __( 'To enable PayPal Sandbox, sign up for a <a target="_blank" href="%s">developer account.</a>', 'wp-courseware' ), 'https://developer.paypal.com/' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => $this->get_setting_key( 'ipn_notifications' ),
				'title'    => esc_html__( 'IPN Notifications', 'wp-courseware' ),
				'label'    => esc_html__( 'Enable IPN email notifications', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'Send notifications when an IPN is received from PayPal indicating refunds, chargebacks and cancellations.', 'wp-courseware' ),
				'desc'     => esc_html__( 'Send notifications when an IPN is received from PayPal indicating refunds, chargebacks and cancellations.', 'wp-courseware' ),
				'default'  => 'yes',
			),
			array(
				'type'     => 'checkbox',
				'key'      => $this->get_setting_key( 'logging' ),
				'title'    => esc_html__( 'Logging', 'wp-courseware' ),
				'label'    => esc_html__( 'Enable logging', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'Log PayPal events, such as IPN requests.', 'wp-courseware' ),
				'desc'     => esc_html__( 'Log PayPal events, such as IPN requests.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'  => 'heading',
				'key'   => $this->get_setting_key( 'api_credentials_heading' ),
				'title' => esc_html__( 'API Credentials', 'wp-courseware' ),
				'desc'  => sprintf(
					__( 'Enter your <a target="_blank" href="%s">PayPal API Credentials</a> to process refunds and cancellations via PayPal.', 'wp-courseware' ),
					$this->is_sandbox_enabled() ? esc_url_raw( 'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty/signature' ) : esc_url_raw( 'https://www.paypal.com/businessprofile/mytools/apiaccess/firstparty/signature' )
				),
			),
			array(
				'type'        => 'text',
				'key'         => $this->get_setting_key( 'live_api_username' ),
				'title'       => esc_html__( 'Live API Username', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Your PayPal Live API Username.', 'wp-courseware' ),
				'default'     => '',
				'class'       => 'paypal-api-creds-live',
			),
			array(
				'type'        => 'fopassword',
				'key'         => $this->get_setting_key( 'live_api_password' ),
				'title'       => esc_html__( 'Live API Password', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Your PayPal Live API Password.', 'wp-courseware' ),
				'default'     => '',
				'class'       => 'paypal-api-creds-live',
			),
			array(
				'type'        => 'fopassword',
				'key'         => $this->get_setting_key( 'live_api_signature' ),
				'title'       => esc_html__( 'Live API Signature', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Your PayPal Live API Signature.', 'wp-courseware' ),
				'default'     => '',
				'class'       => 'paypal-api-creds-live',
			),
			array(
				'type'        => 'text',
				'key'         => $this->get_setting_key( 'sandbox_api_username' ),
				'title'       => esc_html__( 'Sandbox API Username', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Your PayPal Sandbox API Username.', 'wp-courseware' ),
				'default'     => '',
				'class'       => 'paypal-api-creds-sandbox',
			),
			array(
				'type'        => 'fopassword',
				'key'         => $this->get_setting_key( 'sandbox_api_password' ),
				'title'       => esc_html__( 'Sandbox API Password', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Your PayPal Sandbox API Password.', 'wp-courseware' ),
				'default'     => '',
				'class'       => 'paypal-api-creds-sandbox',
			),
			array(
				'type'        => 'fopassword',
				'key'         => $this->get_setting_key( 'sandbox_api_signature' ),
				'title'       => esc_html__( 'Sandbox API Signature', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Optional', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Your PayPal Sandbox API Signature.', 'wp-courseware' ),
				'default'     => '',
				'class'       => 'paypal-api-creds-sandbox',
			),
		);

		return array_merge( $settings, $paypal_settings );
	}

	/**
	 * Load PayPal Gateway.
	 *
	 * Initialize actions and filters. This is called
	 * right after constructor in checkout controller.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_api_gateway-paypal', array( $this, 'ipn_check_response' ) );
		add_action( 'wpcw_checkout_after_order_received_paypal', array( $this, 'pdt_check_response' ), 10, 1 );
		add_action( 'wpcw_gateway_paypal_process_ipn_request', array( $this, 'ipn_process_valid_response' ) );
		add_filter( 'wpcw_subscription_profile_link_paypal', array( $this, 'subscription_profile_link' ), 10, 2 );
		add_filter( 'wpcw_subscription_transaction_link_paypal', array( $this, 'subscription_transaction_link' ), 10, 2 );
		add_filter( 'wpcw_order_transaction_link_paypal', array( $this, 'order_transaction_link' ), 10, 2 );
	}

	/**
	 * Setup PayPal Payment Gateway.
	 *
	 * @since 4.3.0
	 */
	public function setup() {
		parent::setup();

		// Setup all initial settings.
		$this->email          = $this->get_setting( 'email', $this->email );
		$this->identity_token = $this->get_setting( 'identity_token', $this->identity_token );
		$this->image_url      = $this->get_setting( 'image', $this->image_url );
		$this->ipn            = $this->get_setting( 'ipn_notifications', $this->ipn );
		$this->sandbox        = $this->get_setting( 'sandbox', $this->sandbox );
		$this->api_creds      = array(
			'live_api_username'     => $this->get_setting( 'live_api_username', $this->api_creds['live_api_username'] ),
			'live_api_password'     => $this->get_setting( 'live_api_password', $this->api_creds['live_api_password'] ),
			'live_api_signature'    => $this->get_setting( 'live_api_signature', $this->api_creds['live_api_signature'] ),
			'sandbox_api_username'  => $this->get_setting( 'sandbox_api_username', $this->api_creds['sandbox_api_username'] ),
			'sandbox_api_password'  => $this->get_setting( 'sandbox_api_password', $this->api_creds['sandbox_api_password'] ),
			'sandbox_api_signature' => $this->get_setting( 'sandbox_api_signature', $this->api_creds['sandbox_api_signature'] ),
		);
		$this->logging        = $this->get_setting( 'logging', $this->logging );

		// Description for Sandbox
		if ( $this->is_sandbox_enabled() ) {
			$this->description .= ' ' . sprintf( __( 'SANDBOX ENABLED. You can use sandbox testing accounts only. See the <a target="_blank" href="%s">PayPal Sandbox Testing Guide</a> for more details.', 'wp-courseware' ), 'https://developer.paypal.com/docs/classic/lifecycle/ug_sandbox/' );
			$this->description = trim( $this->description );
		}
	}

	/**
	 * Is PayPal Gateway Available?
	 *
	 * @since 4.3.0
	 */
	public function is_available() {
		if ( ! $this->supports_currency() ) {
			return false;
		}

		if ( ! $this->get_email() ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Supports Currency?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the currency is supported.
	 */
	public function supports_currency() {
		$supported_currencies = array(
			'AUD',
			'BRL',
			'CAD',
			'MXN',
			'NZD',
			'HKD',
			'SGD',
			'USD',
			'EUR',
			'JPY',
			'TRY',
			'NOK',
			'CZK',
			'DKK',
			'HUF',
			'ILS',
			'MYR',
			'PHP',
			'PLN',
			'SEK',
			'CHF',
			'TWD',
			'THB',
			'GBP',
			'RMB',
			'RUB',
			'INR',
		);

		if ( ! in_array( wpcw_get_currency(), apply_filters( 'wpcw_paypal_supported_currencies', $supported_currencies ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get PayPal Email.
	 *
	 * @since 4.3.0
	 *
	 * @return string The PayPal email.
	 */
	public function get_email() {
		return esc_attr( $this->email );
	}

	/**
	 * Get PayPal Identity Token.
	 *
	 * @since 4.3.0
	 *
	 * @return string The PayPal identity token.
	 */
	public function get_identity_token() {
		return $this->identity_token;
	}

	/**
	 * Get PayPal Image Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The PayPal image url.
	 */
	public function get_image_url() {
		$image_url = wpcw_maybe_change_home_url( $this->image_url );

		return esc_url_raw( apply_filters( 'wpcw_gateway_paypal_image_url', $image_url, $this ) );
	}

	/**
	 * Are IPN Notifications Enabled?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if IPN notifications are enabled, false otherwise.
	 */
	public function is_ipn_enabled() {
		return 'yes' === $this->ipn ? true : false;
	}

	/**
	 * Is PayPal Sandbox Enabled?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if sandbox enabled, false otherwise
	 */
	public function is_sandbox_enabled() {
		return 'yes' === $this->sandbox ? true : false;
	}

	/**
	 * Get Api Credential.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The api credential key.
	 *
	 * @return string The api credential value. Default is blank.
	 */
	protected function get_api_cred( $key ) {
		return isset( $this->api_creds[ $key ] ) ? $this->api_creds[ $key ] : '';
	}

	/**
	 * Get PayPal Api Username.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_api_username() {
		return $this->is_sandbox_enabled() ? $this->get_api_cred( 'sandbox_api_username' ) : $this->get_api_cred( 'live_api_username' );
	}

	/**
	 * Get Api Password.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_api_password() {
		return $this->is_sandbox_enabled() ? $this->get_api_cred( 'sandbox_api_password' ) : $this->get_api_cred( 'live_api_password' );
	}

	/**
	 * Get Api Password.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_api_signature() {
		return $this->is_sandbox_enabled() ? $this->get_api_cred( 'sandbox_api_signature' ) : $this->get_api_cred( 'live_api_signature' );
	}

	/**
	 * Get Api Endpoint.
	 *
	 * @since 4.3.0
	 */
	public function get_api_endpoint() {
		return $this->is_sandbox_enabled() ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';
	}

	/**
	 * Get Icon.
	 *
	 * @since 4.3.0
	 *
	 * @return string The icon html.
	 */
	public function get_icon() {
		$icon = (array) $this->get_icon_image( wpcw()->countries->get_base_country() );

		$icon_html = sprintf(
			'&nbsp;<a href="%1$s" class="wpcw-payment-method-question" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;">%2$s</a>',
			esc_url( $this->get_icon_url( wpcw()->countries->get_base_country() ) ),
			esc_attr__( 'What is PayPal?', 'wp-courseware' )
		);

		foreach ( $icon as $i ) {
			if ( wpcw_is_valid_url( $i ) ) {
				$icon_html .= '<span class="wpcw-payment-method-icon"><img src="' . esc_attr( $i ) . '" alt="' . esc_attr__( 'PayPal acceptance mark', 'wp-courseware' ) . '" /></span>';
			} else {
				$icon_html .= '<span class="wpcw-payment-method-icon">' . wp_kses_post( $i ) . '</span>';
			}
		}

		return apply_filters( 'wpcw_paypal_gateway_icon', $icon_html );
	}

	/**
	 * Get Icon Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $country The country code.
	 *
	 * @return string The icon url.
	 */
	protected function get_icon_url( $country ) {
		$url           = 'https://www.paypal.com/' . strtolower( $country );
		$home_counties = array( 'BE', 'CZ', 'DK', 'HU', 'IT', 'JP', 'NL', 'NO', 'ES', 'SE', 'TR', 'IN' );
		$countries     = array(
			'DZ',
			'AU',
			'BH',
			'BQ',
			'BW',
			'CA',
			'CN',
			'CW',
			'FI',
			'FR',
			'DE',
			'GR',
			'HK',
			'ID',
			'JO',
			'KE',
			'KW',
			'LU',
			'MY',
			'MA',
			'OM',
			'PH',
			'PL',
			'PT',
			'QA',
			'IE',
			'RU',
			'BL',
			'SX',
			'MF',
			'SA',
			'SG',
			'SK',
			'KR',
			'SS',
			'TW',
			'TH',
			'AE',
			'GB',
			'US',
			'VN',
		);

		if ( in_array( $country, $home_counties ) ) {
			return $url . '/webapps/mpp/home';
		} elseif ( in_array( $country, $countries ) ) {
			return $url . '/webapps/mpp/paypal-popup';
		} else {
			return $url . '/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside';
		}
	}

	/**
	 * Get Icon Image.
	 *
	 * @since 4.3.0
	 *
	 * @param string $country The country code being considered.
	 *
	 * @return array The array of image urls.
	 */
	protected function get_icon_image( $country ) {
		switch ( $country ) {
			case 'US' :
			case 'NZ' :
			case 'CZ' :
			case 'HU' :
			case 'MY' :
				$icon = '<i class="wpcw-fab wpcw-fa-cc-visa"></i> <i class="wpcw-fab wpcw-fa-cc-mastercard"></i> <i class="wpcw-fab wpcw-fa-cc-amex"></i> <i class="wpcw-fab wpcw-fa-cc-discover"></i> <i class="wpcw-fab wpcw-fa-cc-paypal"></i>';
				// $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TR' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_odeme_secenekleri.jpg';
				break;
			case 'GB' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png';
				break;
			case 'MX' :
				$icon = array(
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_visa_mastercard_amex.png',
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_debit_card_275x60.gif',
				);
				break;
			case 'FR' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_moyens_paiement_fr.jpg';
				break;
			case 'AU' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_AU/mktg/logo/Solutions-graphics-1-184x80.jpg';
				break;
			case 'DK' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_PayPal_betalingsmuligheder_dk.jpg';
				break;
			case 'RU' :
				$icon = 'https://www.paypalobjects.com/webstatic/ru_RU/mktg/business/pages/logo-center/AM_mc_vs_dc_ae.jpg';
				break;
			case 'NO' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/banner_pl_just_pp_319x110.jpg';
				break;
			case 'CA' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_CA/mktg/logo-image/AM_mc_vs_dc_ae.jpg';
				break;
			case 'HK' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_HK/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			case 'SG' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_SG/mktg/Logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TW' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_TW/mktg/logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TH' :
				$icon = 'https://www.paypalobjects.com/webstatic/en_TH/mktg/Logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'JP' :
				$icon = 'https://www.paypal.com/ja_JP/JP/i/bnr/horizontal_solution_4_jcb.gif';
				break;
			case 'IN' :
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			default :
				$icon = '<i class="wpcw-fab wpcw-fa-cc-paypal"></i>';
				break;
		}

		return apply_filters( 'wpcw_gateway_paypal_icon', $icon );
	}

	/**
	 * Is Logging Enabled?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if enabled, False otherwise.
	 */
	public function is_logging_enabled() {
		return 'yes' === $this->logging ? true : false;
	}

	/**
	 * Log PayPal Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The log message.
	 */
	public function log( $message = '' ) {
		if ( empty( $message ) || ! $this->is_logging_enabled() ) {
			return;
		}

		$log_entry = "\n" . '====Start PayPal Gateway Log====' . "\n" . $message . "\n" . '====End PayPal Gateway Log====' . "\n";

		wpcw_log( $log_entry );
		wpcw_file_log( array( 'message' => $log_entry ) );
	}

	/**
	 * Validate Fields.
	 *
	 * Validation logic for the frontend fields.
	 *
	 * @since 4.6.3
	 *
	 * @param array $data An array of posted data.
	 * @param \WP_Error $errors The WP_Error object.
	 */
	public function validate_fields( &$data, &$errors ) {
		$cart = wpcw()->cart->get_cart();

		if ( empty( $cart ) ) {
			return;
		}

		$subscription_items = 0;
		$one_time_items     = 0;

		foreach ( $cart as $cart_item_key => $cart_item ) {
			if ( ! isset( $cart_item['id'] ) || empty( $cart_item['data'] ) ) {
				continue;
			}

			$course = new Course( $cart_item['data'] );

			if ( 'subscription' === $course->get_payments_type() || $course->charge_installments() ) {
				$subscription_items ++;
			} else {
				$one_time_items ++;
			}
		}

		if ( $subscription_items > 1 ) {
			$errors->add( 'gateway-paypal-multiple-subscriptions-error', esc_html__( 'You cannot purchase multiple subscriptions together when using PayPal.', 'wp-courseware' ) );
		} elseif ( $subscription_items === 1 && $one_time_items >= 1 ) {
			$errors->add( 'gateway-paypal-one-time-and-subscriptions-error', esc_html__( 'You cannot purchase a subscription course and a one-time payment course together when using PayPal.', 'wp-courseware' ) );
		}
	}

	/**
	 * Process Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		$order = wpcw_get_order( $order_id );

		if ( ! $this->can_process_payment( $order ) ) {
			if ( wpcw_is_ajax() ) {
				$response = array(
					'result'   => 'failure',
					'messages' => wpcw_get_notices(),
					'reload'   => isset( wpcw()->session->reload_checkout ),
				);

				unset( wpcw()->session->reload_checkout );

				wp_send_json_error( $response );
			} else {
				return array(
					'result'   => 'failure',
					'redirect' => wpcw_get_page_permalink( 'checkout' ),
				);
			}
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_request_url( $order ),
		);
	}

	/**
	 * Can we proceed processing the payment?
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return bool True if validated, false otherwise.
	 */
	protected function can_process_payment( $order ) {
		$items = $order->get_order_items();

		if ( empty( $items ) ) {
			wpcw_add_notice( esc_html__( 'It appears your cart is empty. Please try again.', 'wp-courseware' ), 'error' );
		}

		$subscription_items = 0;
		$one_time_items     = 0;

		foreach ( $items as $item ) {
			if ( $item instanceof Order_Item ) {
				if ( $item->get_is_recurring() || $item->get_use_installments() ) {
					$subscription_items ++;
				} else {
					$one_time_items ++;
				}
			}
		}

		if ( $subscription_items > 1 ) {
			wpcw_add_notice( esc_html__( 'You cannot purchase multiple subscriptions together when using PayPal.', 'wp-courseware' ), 'error' );

			return false;
		} elseif ( $subscription_items === 1 && $one_time_items >= 1 ) {
			wpcw_add_notice( esc_html__( 'Subscriptions and a one-time courses cannot be purchased together using PayPal.', 'wp-courseware' ), 'error' );

			return false;
		}

		if ( $subscription_items ) {
			$this->is_subscription = true;
		}

		return true;
	}

	/**
	 * Get Notify Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The notify url.
	 */
	protected function get_notify_url() {
		/**
		 * Filter: Gateway Notify Url.
		 *
		 * @since 4.3.0
		 *
		 * @param string $notify_url The gateway notify url.
		 * @param Gateway $gateway The gateway object.
		 *
		 * @return string $notify_url The modified notify url.
		 */
		return esc_url_raw( apply_filters( "wpcw_gateway_{$this->get_id()}_notify_url", wpcw()->api->get_api_url( 'gateway-paypal' ), $this ) );
	}

	/**
	 * Get Request Url.
	 *
	 * @since 4.3.0
	 *
	 * @param Order The order object.
	 *
	 * @return string The PayPal request url.
	 */
	protected function get_request_url( $order ) {
		$request_args = $this->get_request_args( $order );

		$paypal_args = http_build_query( $request_args, '', '&' );

		$this->log( sprintf( 'PayPal Request arguments for Order %1$s: %2$s', $order->get_order_number(), wpcw_print_r( $request_args, true ) ) );

		if ( $this->is_sandbox_enabled() ) {
			return 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1&' . $paypal_args;
		} else {
			return 'https://www.paypal.com/cgi-bin/webscr?' . $paypal_args;
		}
	}

	/**
	 * Get the state to send to paypal.
	 *
	 * @param string $cc The country code.
	 * @param string $state The state code.
	 *
	 * @return string The state code.
	 */
	protected function get_paypal_state( $cc, $state ) {
		if ( 'US' === $cc ) {
			return $state;
		}

		$states = wpcw()->countries->get_states( $cc );

		if ( isset( $states[ $state ] ) ) {
			return $states[ $state ];
		}

		return $state;
	}

	/**
	 * Get Process Type Args.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return array $args The process type args.
	 */
	protected function get_process_type_args( $order ) {
		$args = array();

		if ( $this->is_subscription ) {
			$args = array( 'cmd' => '_xclick-subscriptions', 'src' => 1, 'sra' => 1 );
		} else {
			$args = array( 'cmd' => '_cart', 'upload' => 1 );
		}

		return $args;
	}

	/**
	 * Get Billing Args.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return array $args The billing args.
	 */
	protected function get_billing_args( $order ) {
		return array(
			'address1' => wpcw_limit_length( $order->get_billing_address_1(), 100 ),
			'address2' => wpcw_limit_length( $order->get_billing_address_2(), 100 ),
			'city'     => wpcw_limit_length( $order->get_billing_city(), 40 ),
			'state'    => $this->get_paypal_state( $order->get_billing_country(), $order->get_billing_state() ),
			'zip'      => wpcw_limit_length( wpcw_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ), 32 ),
			'country'  => wpcw_limit_length( $order->get_billing_country(), 2 ),
		);
	}

	/**
	 * Get Items Args.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return array $args The line item args.
	 */
	protected function get_items_args( $order ) {
		$args       = array();
		$item_count = 1;

		foreach ( $order->get_order_items() as $order_item ) {
			if ( $order_item instanceof Order_Item ) {
				if ( $order_item->get_is_recurring() ) {
					$args = $this->get_subscription_item_args( $order_item );
					break;
				} else {
					$args[ 'item_name_' . $item_count ] = stripslashes_deep( html_entity_decode( $order_item->get_order_item_title(), ENT_COMPAT, 'UTF-8' ) );
					$args[ 'quantity_' . $item_count ]  = $order_item->get_qty();
					$args[ 'amount_' . $item_count ]    = $order_item->get_total();

					$item_count ++;
				}
			}
		}

		/**
		 * Filter: PayPal Items Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $args The PayPal args.
		 * @param Order The order object.
		 * @param Gateway_Paypal The paypal gateway object.
		 *
		 * @return array $args The PayPal args.
		 */
		return apply_filters( 'wpcw_gateway_paypal_items_args', $args, $order, $this );
	}

	/**
	 * Get Subscription Item Args.
	 *
	 * @since 4.3.0
	 *
	 * @param Order_Item $order_item The order item object.
	 *
	 * @return array $args The request args.
	 */
	protected function get_subscription_item_args( $order_item ) {
		$args = array();

		// Check Type.
		if ( ! $order_item instanceof Order_Item ) {
			return $args;
		}

		// Get Course.
		$course       = $order_item->get_course();
		$amount       = $order_item->get_amount();
		$discount     = $order_item->get_discount();
		$discount_tax = $order_item->get_discount_tax();
		$subtotal     = $order_item->get_subtotal();
		$subtotal_tax = $order_item->get_subtotal_tax();
		$tax          = $order_item->get_tax();
		$total        = $order_item->get_total();

		// Item Name.
		$args['item_name'] = stripslashes_deep( html_entity_decode( $order_item->get_order_item_title(), ENT_COMPAT, 'UTF-8' ) );

		// Recurring Total.
		$recurring_total = wpcw_round( $subtotal + $subtotal_tax );

		// Amount.
		$args['a3'] = $recurring_total;

		// Duration / Frequency - Hard coded for now.
		$args['p3'] = 1;

		// Trial Logic - If a discount was applied and the amount is different from the recurring amount, we need to
		// do a free trial for the specified period to cover the cost and not distrupt the subscription.
		$trial_needed = false;
		if ( $total < $recurring_total ) {
			$trial_needed = true;
			$args['a1']   = wpcw_round( $total );
			$args['p1']   = 1;
		}

		// Units of Duration / Frequency
		switch ( $course->get_payments_interval() ) {
			case 'day' :
				$args['t3'] = 'D';
				if ( true === $trial_needed ) {
					$args['t1'] = 'D';
				}
				break;
			case 'week' :
				$args['t3'] = 'W';
				if ( true === $trial_needed ) {
					$args['t1'] = 'W';
				}
				break;
			case 'month' :
				$args['t3'] = 'M';
				if ( true === $trial_needed ) {
					$args['t1'] = 'M';
				}
				break;
			case 'quarter' :
				$args['p3'] = 3;
				$args['t3'] = 'M';
				if ( true === $trial_needed ) {
					$args['p1'] = 3;
					$args['t1'] = 'M';
				}
				break;
			case 'semi-year' :
				$args['p3'] = 6;
				$args['t3'] = 'M';
				if ( true === $trial_needed ) {
					$args['p1'] = 6;
					$args['t1'] = 'M';
				}
				break;
			case 'year' :
				$args['t3'] = 'Y';
				if ( true === $trial_needed ) {
					$args['t1'] = 'Y';
				}
				break;
		}

		if ( $order_item->use_installments() ) {
			$installments_number = absint( $course->get_installments_number() );

			// If a trial is needed, which means the total amount is different than the recurring amount
			// then we need to reduce the installments number by 1 as the presence of a trial or
			// a different initial ammount will add an additional payment in the eyes of paypal.
			$args['srt'] = $trial_needed ? absint( $installments_number - 1 ) : $installments_number;

			$args['disp_tot'] = 'Y';
		}

		/**
		 * Filter: PayPal Subscription Item Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array $args The paypal args.
		 * @param Order_Item The order item.
		 * @param Gateway_Paypal The payapl gateway.
		 *
		 * @return array $args The paypal args.
		 */
		return apply_filters( 'wpcw_gateway_paypal_subscription_item_args', $args, $order_item, $this );
	}

	/**
	 * Get Request Args for PayPal.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return array The PayPal arguments.
	 */
	protected function get_request_args( $order ) {
		$this->log( 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->get_notify_url() );

		$primary_request_args = array(
			'business'      => $this->get_email(),
			'first_name'    => wpcw_limit_length( $order->get_student_first_name(), 32 ),
			'last_name'     => wpcw_limit_length( $order->get_student_last_name(), 64 ),
			'email'         => wpcw_limit_length( $order->get_student_email() ),
			'page_style'    => apply_filters( 'wpcw_gateway_paypal_page_style', 'primary' ),
			'image_url'     => esc_url_raw( $this->get_image_url() ),
			'no_shipping'   => 1,
			'shipping'      => 0,
			'no_note'       => 1,
			'currency_code' => wpcw_get_currency(),
			'charset'       => get_bloginfo( 'charset' ),
			'custom'        => json_encode( array(
				'order_id'  => $order->get_order_id(),
				'order_key' => $order->get_order_key()
			) ),
			'rm'            => is_ssl() ? 2 : 1,
			'return'        => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ),
			'cancel_return' => esc_url_raw( $this->get_cancel_url( $order ) ),
			'notify_url'    => wpcw_limit_length( $this->get_notify_url(), 255 ),
			'cbt'           => get_bloginfo( 'name' ),
			'bn'            => 'WPCourseware_SP',
			'paymentaction' => apply_filters( 'wpcw_gateway_paypal_payment_action', 'sale' ),
		);

		if ( apply_filters( 'wpcw_gateway_paypal_use_invoice_prefix', false ) ) {
			$primary_request_args['invoice'] = wpcw_limit_length( apply_filters( 'wpcw_gateway_paypal_invoice_prefix', '' ) . $order->get_order_number(), 127 );
		}

		return apply_filters( 'wpcw_gateway_paypal_request_args', array_merge(
			$primary_request_args,
			$this->get_process_type_args( $order ),
			$this->get_billing_args( $order ),
			$this->get_items_args( $order )
		), $order );
	}

	/**
	 * Format Prices.
	 *
	 * @since 4.3.0
	 *
	 * @param float|int $price The price.
	 * @param Order $order The order object.
	 *
	 * @return string
	 */
	protected function number_format( $price, $order ) {
		$decimals = 2;

		if ( ! $this->currency_has_decimals( $order->get_currency() ) ) {
			$decimals = 0;
		}

		return number_format( $price, $decimals, '.', '' );
	}

	/**
	 * Check if currency has decimals.
	 *
	 * @since 4.3.0
	 *
	 * @param string $currency The currency.
	 *
	 * @return bool
	 */
	protected function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get PayPal Order.
	 *
	 * @since 4.3.0
	 *
	 * @param string $raw_custom JSON data passed back by PayPal.
	 *
	 * @return bool|Order False on failure, Order object on success.
	 */
	protected function get_paypal_order( $raw_custom ) {
		// We have the data in the correct format, so get the order.
		if ( ( $custom = json_decode( $raw_custom ) ) && is_object( $custom ) ) {
			$order_id  = $custom->order_id;
			$order_key = $custom->order_key;
		} else {
			$this->log( 'Order ID and key were not found in "custom".', 'error' );

			return false;
		}

		if ( ! $order = wpcw_get_order( $order_id ) ) {
			$order = wpcw_get_order_by_order_key( $order_key );
		}

		if ( ! $order || $order->get_order_key() !== $order_key ) {
			$this->log( 'Order Keys do not match.', 'error' );

			return false;
		}

		return $order;
	}

	/**
	 * Payment Complete.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param string $txn_id The transaction id.
	 * @param string $note The order note.
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		$order->add_order_note( $note );
		$order->payment_complete( $txn_id );

		if ( $note ) {
			$this->log( $note );
		}
	}

	/**
	 * Payment On-Hold.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param string $reason The order reason.
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		$order->update_status( 'on-hold', $reason );
		wpcw_empty_cart();
	}

	/**
	 * Payment Pending.
	 *
	 * @since 4.6.0
	 *
	 * @param Order $order The order object.
	 * @param string $reason The order reason.
	 */
	protected function payment_pending( $order, $reason = '' ) {
		$order->update_status( 'pending', $reason );
		wpcw_empty_cart();
	}

	/**
	 * Payment Processing.
	 *
	 * @since 4.6.0
	 *
	 * @param Order $order The order object.
	 * @param string $reason The order reason.
	 */
	protected function payment_processing( $order, $reason = '' ) {
		$order->update_status( 'processing', $reason );
		wpcw_empty_cart();
	}

	/**
	 * Process Refund.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param null $amount The amount that needs to be refunded.
	 * @param string $reason The reason for the refund.
	 *
	 * @return bool True if successful, False otherwise.
	 */
	public function process_refund( $order, $amount = null, $reason = '' ) {
		// Setup Gateway.
		$this->setup();

		/**
		 * Filter: PayPal Refund Request Body Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of args for the request body.
		 * @param Order $order The order object.
		 * @param Gateway_Paypal $this The gateway object.
		 *
		 * @return array The array of args for the request body.
		 */
		$request_body = apply_filters( 'wpcw_gateway_paypal_refund_api_request_body_args', array(
			'USER'          => $this->get_api_username(),
			'PWD'           => $this->get_api_password(),
			'SIGNATURE'     => $this->get_api_signature(),
			'VERSION'       => '124',
			'METHOD'        => 'RefundTransaction',
			'TRANSACTIONID' => $order->get_transaction_id(),
			'REFUNDTYPE'    => 'Full',
		), $order, $this );

		/**
		 * Filter: PayPal Refund Request Headers.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of request headers.
		 * @param Order $order The order object.
		 * @param Gateway_Paypal $this The gateway object.
		 *
		 * @return array he array of request headers.
		 */
		$request_headers = apply_filters( 'wpcw_gateway_paypal_refund_api_request_headers', array(
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Cache-Control' => 'no-cache',
		), $order, $this );

		/**
		 * Filter: PayPal Refund Request Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of request args.
		 * @param Order $order The order object.
		 * @param Gateway_Paypal $this The gateway object.
		 *
		 * @return array he array of request args.
		 */
		$request_args = apply_filters( 'wpcw_gateway_paypal_request_args', array(
			'body'        => $request_body,
			'headers'     => $request_headers,
			'httpversion' => '1.1',
		), $order, $this );

		$error   = '';
		$request = wp_remote_post( $this->get_api_endpoint(), $request_args );
		$success = false;

		if ( is_wp_error( $request ) ) {
			$error = $request->get_error_message();
		} else {
			$body    = wp_remote_retrieve_body( $request );
			$code    = wp_remote_retrieve_response_code( $request );
			$message = wp_remote_retrieve_response_message( $request );

			if ( is_string( $body ) ) {
				wp_parse_str( $body, $body );
			}

			if ( empty( $code ) || 200 !== (int) $code ) {
				$success = false;
			}

			if ( empty( $message ) || 'OK' !== $message ) {
				$success = false;
			}

			$this->log( 'PayPal Refund Body Message: ' . wpcw_print_r( $body, true ) );

			if ( isset( $body['ACK'] ) && 'success' === strtolower( $body['ACK'] ) ) {
				$success = true;
			} else {
				if ( isset( $body['L_LONGMESSAGE0'] ) ) {
					$error = $body['L_LONGMESSAGE0'];
				} else {
					$error = __( 'PayPal refund failed for unknown reason.', 'wp-courseware' );
				}
			}

			/**
			 * Action: After Order Refund Request.
			 *
			 * @since 4.3.0
			 *
			 * @param Order $order The order object.
			 * @param mixed $body The request response body.
			 * @param string $code The request response code.
			 * @param string $message The request response message if any.
			 * @param string $error The request response error if any.
			 */
			do_action( 'wpcw_gateway_paypal_after_order_refund_request', $order, $body, $code, $message, $error );
		}

		if ( $success ) {
			$order->update_meta( '_paypal_refunded', true );
			$order->update_meta( '_paypal_refund_id', $body['REFUNDTRANSACTIONID'] );
			$order->update_status( 'refunded', sprintf( __( 'Order Refunded. Amount: %1$s, PayPal Refund Transaction ID: %2$s', 'wp-courseware' ), $order->get_total_refunded( true ), $body['REFUNDTRANSACTIONID'] ) );
		} else {
			$order->add_order_note( sprintf( __( 'Order Refund Failed. PayPal Error: %s', 'wp-courseware' ), $error ) );
		}

		/**
		 * Action: Order Refund.
		 *
		 * @since 4.3.0
		 *
		 * @param Order $order The order object.
		 */
		do_action( 'wpcw_gateway_paypal_order_refund', $order );

		return $success;
	}

	/**
	 * Process Subscription Cancellation.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return bool True if successful, False otherwise.
	 */
	public function process_subscription_cancellation( $subscription ) {
		// Setup Gateway.
		$this->setup();

		/**
		 * Filter: PayPal Cancel Subscription Request Body Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of args for the request body.
		 * @param Subscription $subscription The subscription object.
		 * @param Gateway_Paypal $this The gateway object.
		 *
		 * @return array The array of args for the request body.
		 */
		$request_body = apply_filters( 'wpcw_gateway_paypal_cancel_subscription_api_request_body_args', array(
			'USER'      => $this->get_api_username(),
			'PWD'       => $this->get_api_password(),
			'SIGNATURE' => $this->get_api_signature(),
			'VERSION'   => '124',
			'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
			'PROFILEID' => $subscription->get_profile_id(),
			'ACTION'    => 'Cancel',
		), $subscription, $this );

		/**
		 * Filter: PayPal Cancel Subscription Request Headers.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of request headers.
		 * @param Subscription $subscription The subscription object.
		 * @param Gateway_Paypal $this The gateway object.
		 *
		 * @return array The array of request headers.
		 */
		$request_headers = apply_filters( 'wpcw_gateway_paypal_cancel_subscription_api_request_headers', array(
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Cache-Control' => 'no-cache',
		), $subscription, $this );

		/**
		 * Filter: PayPal Cancel Subscription Request Args.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of request args.
		 * @param Subscription $subscription The order object.
		 * @param Gateway_Paypal $this The gateway object.
		 *
		 * @return array he array of request args.
		 */
		$request_args = apply_filters( 'wpcw_gateway_paypal_cancel_subscription_request_args', array(
			'body'        => $request_body,
			'headers'     => $request_headers,
			'timeout'     => 30,
			'httpversion' => '1.1',
		), $subscription, $this );

		$error   = '';
		$request = wp_remote_post( $this->get_api_endpoint(), $request_args );
		$success = false;

		if ( is_wp_error( $request ) ) {
			$error = $request->get_error_message();
		} else {
			$body    = wp_remote_retrieve_body( $request );
			$code    = wp_remote_retrieve_response_code( $request );
			$message = wp_remote_retrieve_response_message( $request );

			if ( is_string( $body ) ) {
				wp_parse_str( $body, $body );
			}

			if ( empty( $code ) || 200 !== (int) $code ) {
				$success = false;
			}

			if ( empty( $message ) || 'OK' !== $message ) {
				$success = false;
			}

			$this->log( 'PayPal Subscription Cancellation Response Body' . wpcw_print_r( $body, true ) );

			if ( isset( $body['ACK'] ) && 'success' === strtolower( $body['ACK'] ) ) {
				$success = true;
			} else {
				if ( isset( $body['L_LONGMESSAGE0'] ) ) {
					$error = $body['L_LONGMESSAGE0'];
				} else {
					$error = esc_html__( 'PayPal failed to cancel the subscription for an unknown reason.', 'wp-courseware' );
				}
			}

			/**
			 * Sometimes a subscription has already been cancelled in PayPal and PayPal returns an error indicating it's not active
			 * Let's catch those cases and consider the cancellation successful.
			 *
			 * @since 4.3.0
			 */
			$cancelled_codes = apply_filters( 'wpcw_gateway_paypal_cancel_codes', array( 11556, 11557, 11531 ) );
			if ( $error && in_array( $error, $cancelled_codes ) ) {
				$success = true;
			}

			/**
			 * Action: Subscription Cancellation.
			 *
			 * @since 4.3.0
			 *
			 * @param Subscription $subscription The subscription object.
			 * @param mixed $body The request response body.
			 * @param string $code The request response code.
			 * @param string $message The request response message if any.
			 * @param string $error The request response error if any.
			 */
			do_action( 'wpcw_gateway_paypal_after_subscription_cancellation_request', $subscription, $body, $code, $message, $error );
		}

		if ( $success ) {
			if ( ! $subscription->is_expired() && ! $subscription->has_status( 'refunded' ) ) {
				$subscription->cancel_at_period_end();
			} else {
				$subscription->cancel();
			}
		} else {
			$subscription->add_note( sprintf( __( 'Subscription Failed to Cancel. PayPal Error: %s', 'wp-courseware' ), $error ) );
		}

		/**
		 * Action: Subscription Cancellation.
		 *
		 * @since 4.3.0
		 *
		 * @param Subscription $subscription The subscription object.
		 */
		do_action( 'wpcw_gateway_paypal_subscription_cancellation', $subscription );

		return $success;
	}

	/**
	 * Get Transaction Link.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction_id The transaction id.
	 *
	 * @return string $transaction_link The transaction link.
	 */
	public function get_transaction_link( $transaction_id ) {
		$transaction_link = $transaction_id;

		if ( ! empty( $transaction_id ) ) {
			$link = $this->is_sandbox_enabled() ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
			$link = sprintf( '%s/activity/payment/%s', $link, $transaction_id );

			$transaction_link = sprintf( '<a target="_blank" href="%s">%s</a>', $link, $transaction_id );
		}

		return $transaction_link;
	}

	/**
	 * Get Profile Link.
	 *
	 * @since 4.3.0
	 *
	 * @param string $profile_id The profile id.
	 *
	 * @return string $profile_link The profile link link.
	 */
	public function get_profile_link( $profile_id ) {
		$profile_link = $profile_id;

		if ( ! empty( $profile_id ) ) {
			$link = $this->is_sandbox_enabled() ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';
			$link = sprintf( '%s/cgi-bin/webscr?cmd=_history-details-from-hub&show_legacy=true&id=%s', $link, $profile_id );

			$profile_link = sprintf( '<a target="_blank" href="%s">%s</a>', $link, $profile_id );
		}

		return $profile_link;
	}

	/**
	 * Profile Link.
	 *
	 * @since 4.3.0
	 *
	 * @param string $profile_id The profile id.
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return string $profile_link The PayPal profile link.
	 */
	public function subscription_profile_link( $profile_id, $subscription ) {
		$this->setup();

		return $this->get_profile_link( $profile_id );
	}

	/**
	 * Subscription Transaction Link.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction_id The transaction id.
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return string $profile_link The PayPal profile link.
	 */
	public function subscription_transaction_link( $transaction_id, $subscription ) {
		$this->setup();

		return $this->get_transaction_link( $transaction_id );
	}

	/**
	 * Order Transaction Link.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction_id The transaction id.
	 * @param Order $order The order object.
	 *
	 * @return string $profile_link The PayPal profile link.
	 */
	public function order_transaction_link( $transaction_id, $order ) {
		$this->setup();

		$link = $this->get_transaction_link( $transaction_id );

		if ( $order->get_meta( '__paypal_is_trial' ) || $order->get_meta( '_paypal_is_trial' ) ) {
			$link = $this->get_profile_link( $transaction_id );
		}

		return $link;
	}

	/** IPN Functions -------------------------------------- */

	/**
	 * IPN: Check Response.
	 *
	 * @since 4.3.0
	 */
	public function ipn_check_response() {
		$this->setup();

		if ( ! empty( $_POST ) && $this->ipn_validate_request() ) {
			$posted = wp_unslash( $_POST );

			/**
			 * Action: Process IPN Request.
			 *
			 * @since 4.3.0
			 *
			 * @param array $posted The posted ipn variables.
			 */
			do_action( 'wpcw_gateway_paypal_process_ipn_request', $posted );
			exit;
		}

		wp_die( esc_html__( 'PayPal IPN Request Failure', 'wp-courseware' ), esc_html__( 'PayPal IPN Request', 'wp-courseware' ), array( 'response' => 500 ) );
	}

	/**
	 * IPN: Validate Request.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if is valid, false otherwise.
	 */
	protected function ipn_validate_request() {
		$this->log( 'Validating PayPal IPN response.' );

		// Get received values from post data.
		$validate_ipn        = wp_unslash( $_POST );
		$validate_ipn['cmd'] = '_notify-validate';

		// Check for spaces in emails and convert to +
		$verify_emails = array( 'payer_email', 'business', 'receiver_email', 'receiver_id' );
		foreach ( $verify_emails as $email ) {
			if ( isset( $validate_ipn[ $email ] ) ) {
				$validate_ipn[ $email ] = str_replace( ' ', '+', $validate_ipn[ $email ] );
			}
		}

		// Send back post vars to paypal.
		$params = array(
			'body'        => $validate_ipn,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'WPCW-IPN-VERIFICATION/' . WPCW_VERSION,
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->is_sandbox_enabled() ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $params );

		// Log Request.
		$this->log( 'IPN Request: ' . wpcw_print_r( $params, true ) );
		$this->log( 'IPN Response: ' . wpcw_print_r( $response['body'], true ) );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' ) ) {
			$this->log( 'Received valid response from PayPal' );

			return true;
		}

		$this->log( 'Received invalid response from PayPal' );

		if ( is_wp_error( $response ) ) {
			$this->log( 'Error response: ' . $response->get_error_message() );
		}

		return false;
	}

	/**
	 * IPN: Process Valid Response.
	 *
	 * @since 4.3.0
	 *
	 * @param array $posted The posted data.
	 */
	public function ipn_process_valid_response( $posted ) {
		if ( isset( $posted['txn_type'] ) && in_array( strtolower( $posted['txn_type'] ), $this->ipn_subscription_get_valid_transaction_types() ) ) {
			$this->ipn_subscription_process_valid_response( $posted );
		} elseif ( isset( $posted['payment_status'] ) ) {
			$order = ! empty( $posted['custom'] ) ? $this->get_paypal_order( $posted['custom'] ) : false;

			if ( $order ) {
				$posted['payment_status'] = strtolower( $posted['payment_status'] );

				$this->log( 'Found order #' . $order->get_order_id() );
				$this->log( 'Payment status: ' . $posted['payment_status'] );

				if ( method_exists( $this, 'ipn_payment_status_' . $posted['payment_status'] ) ) {
					call_user_func( array( $this, 'ipn_payment_status_' . $posted['payment_status'] ), $order, $posted );
				}
			}
		} else {
			$this->log( 'Unknown IPN: ' . wpcw_print_r( $posted, true ) );
		}
		exit;
	}

	/**
	 * IPN: Get Valid Transacation Types.
	 *
	 * @since 4.3.0
	 *
	 * @return array An array of valid transaction types.
	 */
	public function ipn_get_valid_transaction_types() {
		return apply_filters( 'wpcw_gateway_paypal_valid_transaction_types', array(
			'cart',
			'instant',
			'express_checkout',
			'web_accept',
			'masspay',
			'send_money',
			'paypal_here',
			'subscr_signup',
			'subscr_payment',
		) );
	}

	/**
	 * IPN: Validate Transaction Type.
	 *
	 * @since 4.3.0
	 *
	 * @param string $txn_type The transaction type.
	 */
	protected function ipn_validate_transaction_type( $txn_type ) {
		if ( ! in_array( strtolower( $txn_type ), $this->ipn_get_valid_transaction_types(), true ) ) {
			$this->log( sprintf( 'Aborting, Invalid transaction type: %s', $txn_type ) );
			exit;
		}
	}

	/**
	 * IPN: Validate Currency.
	 *
	 * Check currency from IPN matches the order.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param string $currency The currency code.
	 */
	protected function ipn_validate_currency( $order, $currency ) {
		if ( $order->get_currency() !== $currency ) {
			$this->log( sprintf( 'Payment error: Currencies do not match (sent "%1$s" | returned "%2$s")', $order->get_currency(), $currency ) );

			/* translators: %s: currency code. */
			$order->update_status( 'pending', sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'wp-courseware' ), $currency ) );
			exit;
		}
	}

	/**
	 * IPN: Validate Amount.
	 *
	 * Check payment amount from IPN matches the order.
	 *
	 * @param Order $order The order object.
	 * @param int $amount The amount to compare with the order.
	 */
	protected function ipn_validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) !== number_format( $amount, 2, '.', '' ) ) {
			$this->log( sprintf( 'IPN Payment error: Amounts do not match (total %1$s) (gross %2$s).', $order->get_total(), $amount ) );

			$this->payment_on_hold( $order, sprintf( __( 'Validation error: PayPal amounts do not match (total %1$s) (gross %2$s).', 'wp-courseware' ), $order->get_total(), $amount ) );
			exit;
		}
	}

	/**
	 * IPN: Validate Receiver Email.
	 *
	 * @param Order $order The order object.
	 * @param string $receiver_email Email to validate against the order.
	 */
	protected function ipn_validate_email( $order, $receiver_email ) {
		if ( strcasecmp( trim( $receiver_email ), trim( $this->get_email() ) ) !== 0 ) {
			$this->log( "IPN Response is for another account: {$receiver_email}. Your email is {$this->get_email()}" );

			/* translators: %s: email address . */
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s).', 'wp-courseware' ), $receiver_email ) );
			exit;
		}
	}

	/**
	 * IPN: Save Metadata.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data
	 */
	protected function ipn_save_metadata( $order, $posted ) {
		if ( ! empty( $posted['payer_email'] ) ) {
			$order->update_meta( '_paypal_payer_email', wpcw_clean( $posted['payer_email'] ) );
		}
		if ( ! empty( $posted['first_name'] ) ) {
			$order->update_meta( '_paypal_payer_first_anme', wpcw_clean( $posted['first_name'] ) );
		}
		if ( ! empty( $posted['last_name'] ) ) {
			$order->update_meta( '_paypal_payer_last_name', wpcw_clean( $posted['last_name'] ) );
		}
		if ( ! empty( $posted['payment_type'] ) ) {
			$order->update_meta( '_paypal_payment_type', wpcw_clean( $posted['payment_type'] ) );
		}
		if ( ! empty( $posted['payer_id'] ) ) {
			$order->update_meta( '_paypal_payer_id', wpcw_clean( $posted['payer_id'] ) );
		}
		if ( ! empty( $posted['payment_status'] ) ) {
			$order->update_meta( '_paypal_status', wpcw_clean( $posted['payment_status'] ) );
		}
		if ( ! empty( $posted['mc_fee'] ) ) {
			$order->update_meta( '_paypal_transaction_fee', wpcw_clean( $posted['mc_fee'] ) );
		}
	}

	/**
	 * IPN: Send Email Notification.
	 *
	 * @since 4.3.0
	 *
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 */
	protected function ipn_send_email_notification( $subject, $message ) {
		if ( ! $this->is_ipn_enabled() ) {
			return;
		}

		$email_object = wpcw()->emails->get_email( 'new-order' );

		if ( ! $email_object->is_enabled() ) {
			return;
		}

		$message = $email_object->wrap_message( $subject, $message );

		$email_object->send( $email_object->get_recipient(), strip_tags( $subject ), $message, $email_object->get_headers(), $email_object->get_attachments() );
	}

	/**
	 * IPN: Payment Status - Completed.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted object.
	 */
	protected function ipn_payment_status_completed( $order, $posted ) {
		if ( $order->has_order_status( array( 'completed' ) ) ) {
			$this->log( sprintf( 'Aborting, Order #%d is already complete.', $order->get_order_id() ) );

			return;
		}

		$this->ipn_validate_transaction_type( $posted['txn_type'] );
		$this->ipn_validate_currency( $order, $posted['mc_currency'] );
		$this->ipn_validate_amount( $order, $posted['mc_gross'] );
		$this->ipn_validate_email( $order, $posted['receiver_email'] );
		$this->ipn_save_metadata( $order, $posted );

		if ( 'completed' === $posted['payment_status'] ) {
			if ( $order->has_order_status( 'cancelled' ) ) {
				$this->ipn_payment_status_paid_cancelled_order( $order, $posted );
			}

			if ( 'subscr_payment' === $posted['txn_type'] ) {
				$this->payment_complete( $order, 'multiple', __( 'IPN order completed with multiple payments attached.', 'wp-courseware' ) );
			} else {
				$transaction_id = ! empty( $posted['txn_id'] ) ? wpcw_clean( $posted['txn_id'] ) : '';
				$this->payment_complete( $order, $transaction_id, sprintf( __( 'IPN payment completed. Transaction Id: %s', 'wp-courseware' ), $transaction_id ) );
			}
		} else {
			if ( 'authorization' === $posted['pending_reason'] ) {
				$this->payment_on_hold( $order, __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'wp-courseware' ) );
			} else {
				/* translators: %s: pending reason. */
				$this->payment_on_hold( $order, sprintf( __( 'Payment Pending - Reason: %s.', 'wp-courseware' ), $posted['pending_reason'] ) );
			}
		}
	}

	/**
	 * IPN: Payment Status - Trialing.
	 *
	 * @since 4.5.2
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted object.
	 * @param string $trial_txn_id The trial transaction id. Default is empty.
	 */
	protected function ipn_payment_status_trial( $order, $posted, $trial_txn_id = '' ) {
		if ( $order->has_order_status( array( 'completed' ) ) ) {
			$this->log( sprintf( 'Aborting, Order #%d is already complete.', $order->get_order_id() ) );

			return;
		}

		$this->ipn_validate_transaction_type( $posted['txn_type'] );
		$this->ipn_validate_currency( $order, $posted['mc_currency'] );
		$this->ipn_validate_email( $order, $posted['receiver_email'] );
		$this->ipn_save_metadata( $order, $posted );

		// Add Trial.
		$order->update_meta( '_paypal_has_trial', true );

		$transaction_id = $trial_txn_id ? $trial_txn_id : '';

		$this->payment_complete( $order, $transaction_id, sprintf( __( 'IPN subscription trial setup. Transaction Id: %s', 'wp-courseware' ), $transaction_id ) );
	}

	/**
	 * IPN: Payment Status - Pending.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_pending( $order, $posted ) {
		$this->ipn_payment_status_completed( $order, $posted );
	}

	/**
	 * IPN: Payment Status - Failed.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_failed( $order, $posted ) {
		/* translators: %s: payment status. */
		$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'wp-courseware' ), wpcw_clean( $posted['payment_status'] ) ) );
	}

	/**
	 * IPN: Payment Status - Denied.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_denied( $order, $posted ) {
		$this->ipn_payment_status_failed( $order, $posted );
	}

	/**
	 * IPN: Payment Status - Expired.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_expired( $order, $posted ) {
		$this->ipn_payment_status_failed( $order, $posted );
	}

	/**
	 * IPN: Payment Status - Paid Cancelled Order.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_paid_cancelled_order( $order, $posted ) {
		/* translators: %s: Order Edit Url. */
		$subject = sprintf( __( 'Payment for cancelled order %s received.', 'wp-courseware' ), '<a class="link" href="' . esc_url( $order->get_order_edit_url() ) . '">' . $order->get_order_number() . '</a>' );

		/* translators: %s: Order ID. */
		$message = sprintf( __( 'Order #%s has been marked paid by PayPal IPN, but was previously cancelled. Admin handling required.', 'wp-courseware' ), $order->get_order_number() );

		// Send Email Notification.
		$this->ipn_send_email_notification( $subject, $message );
	}

	/**
	 * IPN: Payment Status - Voided.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_voided( $order, $posted ) {
		$this->ipn_payment_status_failed( $order, $posted );
	}

	/**
	 * IPN: Payment Status - Refunded.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_refunded( $order, $posted ) {
		if ( $order->has_order_status( 'refunded' ) ) {
			$this->log( sprintf( 'Aborting, Order #%d is already refunded.', $order->get_order_id() ) );

			return;
		}

		if ( $order->get_total() === wpcw_format_decimal( $posted['mc_gross'] * - 1 ) ) {
			/* translators: %s: Payment Status. */
			$order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'wp-courseware' ), strtolower( $posted['payment_status'] ) ) );

			/* translators: %s: Order Link. */
			$subject = sprintf( __( 'Payment for order %s refunded', 'wp-courseware' ), '<a class="link" href="' . esc_url( $order->get_order_edit_url() ) . '">' . $order->get_order_number() . '</a>' );

			/* translators: %1$s: Order ID, %2$s: Reason Code. */
			$message = sprintf( __( 'Order #%1$s has been marked as refunded - PayPal reason code: %2$s', 'wp-courseware' ), $order->get_order_number(), $posted['reason_code'] );

			// Send Email Notification.
			$this->ipn_send_email_notification( $subject, $message );
		}
	}

	/**
	 * IPN: Payment Status - Reversed.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_reversed( $order, $posted ) {
		/* translators: %s: payment status. */
		$order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'wp-courseware' ), wpcw_clean( $posted['payment_status'] ) ) );

		/* translators: %s: order link. */
		$subject = sprintf( __( 'Payment for order %s reversed', 'wp-courseware' ), '<a class="link" href="' . esc_url( $order->get_order_edit_url() ) . '">' . $order->get_order_number() . '</a>' );

		/* translators: %1$s: order ID, %2$s: reason code. */
		$message = sprintf( __( 'Order #%1$s has been marked on-hold due to a reversal - PayPal reason code: %2$s', 'wp-courseware' ), $order->get_order_number(), wpcw_clean( $posted['reason_code'] ) );

		// Send Email Notification.
		$this->ipn_send_email_notification( $subject, $message );
	}

	/**
	 * IPN: Payment Status - Reversal.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order data.
	 * @param array $posted The posted data.
	 */
	protected function ipn_payment_status_canceled_reversal( $order, $posted ) {
		/* translators: %s: order link. */
		$subject = sprintf( __( 'Reversal cancelled for order #%s', 'wp-courseware' ), $order->get_order_number() );

		/* translators: %1$s: order ID, %2$s: order link. */
		$message = sprintf( __( 'Order #%1$s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %2$s', 'wp-courseware' ), $order->get_order_number(), esc_url_raw( $order->get_order_edit_url() ) );

		// Send Email Notification.
		$this->ipn_send_email_notification( $subject, $message );
	}

	/** IPN Subscription Functions -------------------------- */

	/**
	 * IPN Subscription: Process Valid Response.
	 *
	 * @since 4.3.0
	 *
	 * @param $posted
	 */
	public function ipn_subscription_process_valid_response( $posted ) {
		if ( ! $this->ipn_subscription_validate_transaction_type( $posted['txn_type'] ) ) {
			return;
		}

		$order = ! empty( $posted['custom'] ) ? $this->get_paypal_order( $posted['custom'] ) : false;

		if ( $order ) {
			$posted['txn_type'] = strtolower( $posted['txn_type'] );

			$this->log( 'Found Subscription Parent Order #' . $order->get_order_id() );
			$this->log( 'Subscription Type: ' . $posted['txn_type'] );
			$this->log( 'Subscription Data: ' . wpcw_print_r( $posted, true ) );

			if ( method_exists( $this, 'ipn_subscription_' . $posted['txn_type'] ) ) {
				call_user_func( array( $this, 'ipn_subscription_' . $posted['txn_type'] ), $order, $posted );
			}
		}
	}

	/**
	 * IPN Subscription: Get Transaction Types.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of ipn subscription transaction types.
	 */
	public function ipn_subscription_get_valid_transaction_types() {
		return apply_filters( 'wpcw_gateway_paypal_subscription_valid_transaction_types', array(
			'subscr_signup',
			// Subscription Started
			'subscr_payment',
			// Subscription Payment
			'subscr_cancel',
			// Subscription Cancelled
			'subscr_eot',
			// Subscription Expired
			'subscr_failed',
			// Subscription Payment Failed
			'subscr_modify',
			// Subscription Modified
			'recurring_payment_skipped',
			// Recurring Payment Skipped
			'recurring_payment_suspended',
			// Recurring Payment Suspended
			'recurring_payment_failed',
			// Recurring Payment Failed.
			'recurring_payment_suspended_due_to_max_failed_payment',
			// Recurring Payment Suspended; Due to Max Failed Attempts
		) );
	}

	/**
	 * IPN Subscription: Validate Transaction Type.
	 *
	 * @since 4.3.0
	 *
	 * @param string $txn_type The transaction type.
	 *
	 * @return bool True if validated and false otherwise.
	 */
	public function ipn_subscription_validate_transaction_type( $txn_type ) {
		if ( ! in_array( strtolower( $txn_type ), $this->ipn_subscription_get_valid_transaction_types(), true ) ) {
			$this->log( sprintf( 'Aborting, Invalid or Unknown IPN Subscription Type: %s', $txn_type ) );

			return false;
		}

		return true;
	}

	/**
	 * IPN Subscription: Get Subscription.
	 *
	 * Gets a subscription with the data passed.
	 *
	 * @param Order $order The order object.
	 * @param array $posted The data passed from PayPal.
	 * @param bool $create Optional. If the subscription does not exist should it be created.
	 *
	 * @return bool|Subscription The Subscription object or false on failure.
	 */
	protected function ipn_subscription_get_subscription( $order, $posted, $create = false ) {
		// Get Profile Id.
		$profile_id = isset( $posted['subscr_id'] ) ? wpcw_clean( $posted['subscr_id'] ) : '';

		// Check for a different posted key in case its empty.
		if ( empty( $profile_id ) ) {
			$profile_id = isset( $posted['recurring_payment_id'] ) ? wpcw_clean( $posted['recurring_payment_id'] ) : '';
		}

		// Check for existing subscription.
		$subscription = wpcw_get_subscription_by_profile_id( $profile_id );

		// Check the order for the referenced subscription.
		if ( ! $subscription && ( $subscription_id = $order->get_meta( '_subscription_id', true ) ) ) {
			$subscription = new Subscription( absint( $subscription_id ) );
		}

		// If all else fails, create a new Subscription.
		if ( ! $subscription && $create ) {
			$subscription = new Subscription();
			$subscription->create();
		}

		return $subscription;
	}

	/**
	 * IPN Subscription: Subscription Started
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_subscr_signup( $order, $posted ) {
		// Get Subscription, Create if it doesn't exist.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted, true );

		// Check one more time, just in case.
		if ( ! $subscription || ! $subscription->get_id() ) {
			$this->log( sprintf( 'Subscription Setup Error: %s', wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Check to see if its already active.
		if ( $subscription->has_status( 'active' ) ) {
			$this->log( sprintf( 'Aborting, Subscription #%d is already active.', $subscription->get_id() ) );
			exit;
		}

		// Subscription Id.
		$subscription_id = ! empty( $posted['subscr_id'] ) ? wpcw_clean( $posted['subscr_id'] ) : '';

		// Get Order Total.
		$order_total = $order->get_total();

		// If order total is zero, that means its a trial and parent order needs to be completed.
		if ( $order_total <= 0 ) {
			$subscription_id = ! empty( $posted['subscr_id'] ) ? wpcw_clean( $posted['subscr_id'] ) : '';
			$trial_txn_id    = $subscription_id ? sprintf( '%s', $subscription_id ) : 'trial_txn';

			$this->ipn_payment_status_trial( $order, $posted, $trial_txn_id );
			$this->ipn_subscription_create_subscr_payment( $order, $posted, $trial_txn_id, true );

			$order->refresh();
		}

		// Variables.
		$period     = isset( $posted['period3'] ) ? $this->ipn_subscription_get_paypal_period( wpcw_clean( $posted['period3'] ) ) : 'month';
		$interval   = isset( $posted['period3'] ) ? $this->ipn_subscription_get_paypal_period_interval( wpcw_clean( $posted['period3'] ) ) : 1;
		$created    = isset( $posted['subscr_date'] ) ? date( 'Y-m-d H:i:s', strtotime( $posted['subscr_date'] ) ) : date( 'Y-m-d H:i:s' );
		$expiration = date( 'Y-m-d H:i:s', strtotime( "+{$interval} {$period}" ) );

		// Set Properties.
		$subscription->set_props( array(
			'student_id'     => $order->get_student_id(),
			'student_name'   => $order->get_student_full_name(),
			'student_email'  => $order->get_student_email(),
			'order_id'       => $order->get_order_id(),
			'transaction_id' => $order->get_transaction_id(),
			'method'         => $order->get_payment_method(),
			'profile_id'     => $subscription_id,
			'created'        => $created,
			'expriation'     => $expiration,
		) );

		// Set Course Id.
		/** @var Order_Item $order_item */
		foreach ( $order->get_order_items() as $order_item ) {
			if ( $course_id = $order_item->get_course_id() ) {
				$subscription->set_prop( 'course_id', $course_id );
				$subscription->set_prop( 'course_title', $order_item->get_order_item_title() );
				$subscription->set_prop( 'installment_plan', $order_item->use_installments() );
			}
		}

		// Set Amounts and Period.
		$subscription->set_props( array(
			'initial_amount'   => isset( $posted['mc_amount3'] ) ? wpcw_round( $posted['mc_amount3'] ) : $order->get_total(),
			'recurring_amount' => isset( $posted['mc_amount3'] ) ? wpcw_round( $posted['mc_amount3'] ) : $order->get_total(),
			'period'           => isset( $posted['period3'] ) ? $this->ipn_subscription_get_paypal_period( wpcw_clean( $posted['period3'] ) ) : 'month',
		) );

		// Check for recur times for installments.
		if ( isset( $posted['recur_times'] ) ) {
			$order->update_meta( '_subscription_installments', absint( $posted['recur_times'] ) );
		}

		// Update Subscription Id.
		$order->set_prop( 'subscription_id', $subscription->get_id() );
		$order->save();

		// Set Status.
		$subscription->set_status( 'active', esc_html__( 'Subscription activated.', 'wp-courseware' ) );

		// Save Subscription.
		if ( $subscription->save() ) {
			$this->log( sprintf( 'Subscription Saved: %s', wpcw_print_r( $subscription->get_data(), true ) ) );
		} else {
			$this->log( sprintf( 'Subscription Save Error: %s', wpcw_print_r( $subscription->get_data(), true ) ) );
		}
	}

	/**
	 * IPN Subscription: Subscription Payment
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_subscr_payment( $order, $posted ) {
		$posted['payment_status'] = strtolower( $posted['payment_status'] );

		$this->log( 'Found order #' . $order->get_order_id() );
		$this->log( 'Payment status: ' . $posted['payment_status'] );

		if ( method_exists( $this, 'ipn_payment_status_' . $posted['payment_status'] ) ) {
			call_user_func( array( $this, 'ipn_payment_status_' . $posted['payment_status'] ), $order, $posted );
		}

		$this->ipn_subscription_create_subscr_payment( $order, $posted );
	}

	/**
	 * IPN Subscription: Create Subscription Payment
	 *
	 * @since 4.5.2
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 * @param string $trial_txn The trial transaction id. Default is empty.
	 * @param bool $is_trial Is this a trial payment. Default is false.
	 */
	protected function ipn_subscription_create_subscr_payment( $order, $posted, $trial_txn_id = '', $is_trial = false ) {
		// Fetch a new set of data.
		$order->refresh();

		// Create a new payment Order.
		$payment_order = new Order();
		$payment_order->create();

		// Set Type as Payment.
		$payment_order->set_prop( 'order_type', 'payment' );

		// Parent Data.
		$parent_order       = $order;
		$parent_order_id    = $order->get_order_id();
		$parent_order_data  = $order->get_data( true );
		$parent_order_items = $order->get_order_items();

		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $parent_order, $posted );

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
			'total'
		);

		foreach ( $parent_data_unset as $item_to_unset ) {
			unset( $parent_order_data[ $item_to_unset ] );
		}

		// Log Information.
		$this->log( sprintf( 'Creating Payment Order #%s', $payment_order->get_order_id() ) );
		$this->log( sprintf( 'Parent Order #%s', $parent_order_id ) );
		$this->log( sprintf( 'Parent Order Data: %s', wpcw_print_r( $parent_order_data, true ) ) );
		$this->log( sprintf( 'Posted Data: %s', wpcw_print_r( $posted, true ) ) );

		// Set Data and Parent Order Id.
		$payment_order->set_props( $parent_order_data );
		$payment_order->set_prop( 'order_parent_id', $parent_order_id );

		// Set Order Items.
		$payment_order->insert_order_items( $parent_order_items, false );

		// Update Totals
		$payment_order->update_totals( false );

		// Payment Transaction Id.
		$payment_transaction_id = ! empty( $posted['txn_id'] ) ? wpcw_clean( $posted['txn_id'] ) : wpcw_clean( $trial_txn_id );

		// Set Transaction Id.
		$payment_order->set_prop( 'transaction_id', $payment_transaction_id );

		// Save Metadata for order.
		$this->ipn_save_metadata( $payment_order, $posted );

		// Check for trial.
		if ( $is_trial ) {
			$payment_order->update_meta( '_paypal_is_trial', $is_trial );
		}

		// Check for subscription.
		if ( $subscription ) {
			// Get Bill Times for subscription.
			$bill_times = $subscription->get_bill_times();
			$bill_times = $bill_times + 1;

			// Check for installments.
			if ( $subscription->is_installment_plan() && $parent_order->get_meta( '_subscription_installments' ) ) {
				$payment_order->update_meta( '_installment_payment', true );
				$payment_order->update_meta( '_installment_payment_number', absint( $bill_times ) );
			}

			// Update Subscription
			$subscription->set_prop( 'bill_times', absint( $bill_times ) );

			// Record Initial Payment.
			if ( 1 === $bill_times ) {
				$payment_order->update_meta( '_initial_payment', true );
			}

			// Update Expiration.
			$subscription->set_prop( 'expiration', date( 'Y-m-d H:i:s', strtotime( "+1 {$subscription->get_period()}" ) ) );

			// Save Subscription.
			if ( $subscription->save() ) {
				$this->log( sprintf( 'Subscription Saved: %s', wpcw_print_r( $subscription->get_data(), true ) ) );
			} else {
				$this->log( sprintf( 'Subscription Save Error: %s', wpcw_print_r( $subscription->get_data(), true ) ) );
			}
		}

		// Save Order.
		if ( $payment_order->save() ) {
			$this->log( sprintf( 'Payment Order #%1$s saved successfully! Order Data: %2$s', $payment_order->get_order_id(), wpcw_print_r( $payment_order->get_data( true ), true ) ) );
		} else {
			$this->log( sprintf( 'Payment Order #%1$s failed to save. Order Data: %2$s', $payment_order->get_order_id(), wpcw_print_r( $payment_order->get_data( true ), true ) ) );
		}

		// Refresh Order.
		$payment_order->refresh();

		// Complete Order.
		$payment_order->payment_complete( $payment_transaction_id, esc_html__( 'Subscription Payment Complete.', 'wp-courseware' ) );

		// Check to see if installment plan is complete.
		if ( $subscription && $subscription->is_installment_plan() ) {
			// Get installments number.
			$instllments_number = $subscription->get_course()->get_installments_number();

			// Check to see if bill times equals the number of installments needed.
			if ( absint( $bill_times ) === absint( $instllments_number ) ) {
				$this->log( sprintf( 'Subscription #%1$s has processed all %2$s installments. Completing Subscription...', $subscription->get_id(), $bill_times ) );
				$subscription->complete();
			}
		}
	}

	/**
	 * IPN Subscription: Subscription Cancelled
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_subscr_cancel( $order, $posted ) {
		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted );

		// Abort if it doesn't exist.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Aborting: Subscription does not exist. Order #%1$s. Posted Data: %2$s', $order->get_order_id(), wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Abort if already cancelled.
		if ( $subscription->has_status( 'cancelled' ) ) {
			$this->log( sprintf( 'Aborting: Subscription #%s is already cancelled.', $subscription->get_id() ) );

			return;
		}

		// Abort if already completed.
		if ( $subscription->is_installment_plan() && $subscription->has_status( 'completed' ) ) {
			$this->log( sprintf( 'Aborting: Subscription #%s is already completed.', $subscription->get_id() ) );

			return;
		}

		// Abort if is not expired.
		if ( ! $subscription->is_expired() && $subscription->has_status( 'pending-cancel' ) ) {
			$this->log( sprintf( 'Aborting: Subscription is not at the end of its paid term. The subscription will expire on: %s', $subscription->get_expiration( true ) ) );

			return;
		}

		// Check to see if its an installment plan. Otherwise cancel subscription.
		if ( $subscription->is_installment_plan() ) {
			// Get installments number.
			$instllments_number = $subscription->get_course()->get_installments_number();

			// Check to see if bill times equals the number of installments needed.
			// TODO: $bill_times is not defined here. This cannot possibly work unless $instllments_number == 0 and it is expected for that to be the only valid condition
			if ( absint( $bill_times ) === absint( $instllments_number ) ) {
				$subscription->complete();
				$this->log( sprintf( 'Subscription #%s completed.', $subscription->get_id() ) );
			} else {
				$subscription->cancel();
				$this->log( sprintf( 'Subscription #%s cancelled.', $subscription->get_id() ) );
			}
		} else {
			$subscription->cancel();
			$this->log( sprintf( 'Subscription #%s cancelled.', $subscription->get_id() ) );
		}
	}

	/**
	 * IPN Subscription: Subscription Expired
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_subscr_eot( $order, $posted ) {
		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted );

		// Abort if it doesn't exist.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Aborting: Subscription does not exist. Order #%1$s. Posted Data: %2$s', $order->get_order_id(), wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Abort if installment plan and completed.
		if ( $subscription->is_installment_plan() && $subscription->has_status( 'completed' ) ) {
			$this->log( sprintf( 'Aborting: Subscription #%s is already completed.', $subscription->get_id() ) );

			return;
		}

		// Abort if already expired.
		if ( $subscription->has_status( 'expired' ) ) {
			$this->log( sprintf( 'Aborting: Subscription #%s is already expired.', $subscription->get_id() ) );

			return;
		}

		// Check if installment plan and complete. Otherwise expire subscription.
		if ( $subscription->is_installment_plan() ) {
			$subscription->complete();
			$this->log( sprintf( 'Subscription #%s completed.', $subscription->get_id() ) );
		} else {
			$subscription->expire();
			$this->log( sprintf( 'Subscription #%s expired.', $subscription->get_id() ) );
		}
	}

	/**
	 * IPN Subscription: Subscription Payment Failed
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_subscr_failed( $order, $posted ) {
		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted );

		// Abort if it doesn't exist.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Aborting: Subscription does not exist. Order #%1$s. Posted Data: %2$s', $order->get_order_id(), wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Mark payment as failed.
		$subscription->payment_failed();

		// Log it.
		$this->log( sprintf( 'Subscription #%s payment failed.', $subscription->get_id() ) );
	}

	/**
	 * IPN Subscription: Subscription Modified
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_subscr_modify( $order, $posted ) {
		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted );

		// Abort if it doesn't exist.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Aborting: Subscription does not exist. Order #%1$s. Posted Data: %2$s', $order->get_order_id(), wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Set Updated Props
		$subscription->set_props( array(
			'recurring_amount' => isset( $posted['mc_amount3'] ) ? wpcw_round( $posted['mc_amount3'] ) : $subscription->get_recurring_amount(),
			'period'           => isset( $posted['period3'] ) ? $this->ipn_subscription_get_paypal_period( wpcw_clean( $posted['period3'] ) ) : $subscription->get_period(),
		) );

		// Save Subscription.
		$subscription->save();

		// Add Note.
		$subscription->add_note( sprintf(
			__( 'PayPal subscription was modified. The new recurring amount billed is %1$s and the recurring interval is %2$s.', 'wp-courseware' ),
			wpcw_price( $subscription->get_recurring_amount() ),
			wpcw_get_subscription_period_name( $subscription->get_period() )
		) );
	}

	/**
	 * IPN Subscription: Recurring Payment Skipped
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_recurring_payment_skipped( $order, $posted ) {
		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted );

		// Abort if it doesn't exist.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Aborting: Subscription does not exist. Order #%1$s. Posted Data: %2$s', $order->get_order_id(), wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Add Note about it.
		/* translators: %s: Order Id. */
		$subscription->add_note( sprintf( __( 'Subscription #%s payment skipped. it will be retried up to 3 times, 5 days apart.', 'wp-courseware' ), $subscription->get_id() ) );
	}

	/**
	 * IPN Subscription: Recurring Payment Suspended
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_recurring_payment_suspended( $order, $posted ) {
		// Get Subscription.
		$subscription = $this->ipn_subscription_get_subscription( $order, $posted );

		// Abort if it doesn't exist.
		if ( ! $subscription ) {
			$this->log( sprintf( 'Aborting: Subscription does not exist. Order #%1$s. Posted Data: %2$s', $order->get_order_id(), wpcw_print_r( $posted, true ) ) );

			return;
		}

		// Abort if already suspended.
		if ( $subscription->has_status( 'suspended' ) ) {
			$this->log( sprintf( 'Aborting: Subscription #%s has already been suspended.', $subscription->get_id() ) );

			return;
		}

		// Suspend the subscription.
		$subscription->suspend();

		// Log it.
		$this->log( sprintf( 'Subscription #%s suspended.', $subscription->get_id() ) );
	}

	/**
	 * IPN Subscription: Recurring Payment Suspended; Due to Max Failed Attempts
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 * @param array $posted The posted data.
	 */
	protected function ipn_subscription_recurring_payment_suspended_due_to_max_failed_payment( $order, $posted ) {
		$this->ipn_subscription_recurring_payment_suspended( $order, $posted );
	}

	/**
	 * IPN Subscription: Validate Receiver Email.
	 *
	 * @param Subscription $subscription The subscription object.
	 * @param string $receiver_email Email to validate against the order.
	 */
	protected function ipn_subscription_validate_email( $subscription, $receiver_email ) {
		if ( strcasecmp( trim( $receiver_email ), trim( $this->get_email() ) ) !== 0 ) {
			$this->log( "IPN Subscription Response is for another account: {$receiver_email}. Your email is {$this->get_email()}" );
			exit;
		}
	}

	/**
	 * IPN Subscription: Get PayPal Period.
	 *
	 * @since 4.3.0
	 *
	 * @param string $paypal_period The paypal period that is passed with the request.
	 *
	 * @return string $period The actual period name.
	 */
	protected function ipn_subscription_get_paypal_period( $paypal_period ) {
		$period   = substr( $paypal_period, - 1, 1 );
		$interval = absint( substr( $paypal_period, 0, 1 ) );
		$actual   = '';

		$this->log( sprintf( 'Passed Period: %s', $paypal_period ) );
		$this->log( sprintf( 'Period: %s', $period ) );
		$this->log( sprintf( 'Interval: %s', $interval ) );

		switch ( $period ) {
			case 'D' :
				$actual = 'day';
				break;
			case 'W' :
				$actual = 'week';
				break;
			case 'M' :
				$actual = 'month';
				if ( 3 === absint( $interval ) ) {
					$actual = 'quarter';
				}
				if ( 6 === absint( $interval ) ) {
					$actual = 'semi-year';
				}
				break;
			case 'Y' :
				$actual = 'year';
				break;
			default :
				$actual = 'month';
				break;
		}

		$this->log( sprintf( 'Actual: %s', $actual ) );

		return $actual;
	}

	/**
	 * IPN Subscription: Get PayPal Period Interval.
	 *
	 * @since 4.6.0
	 *
	 * @param string $paypal_period The paypal period that is passed with the request.
	 *
	 * @return string $interval The actual period interval
	 */
	protected function ipn_subscription_get_paypal_period_interval( $paypal_period ) {
		$period   = substr( $paypal_period, - 1, 1 );
		$interval = absint( substr( $paypal_period, 0, 1 ) );

		$this->log( sprintf( 'Passed Period: %s', $paypal_period ) );
		$this->log( sprintf( 'Period: %s', $period ) );
		$this->log( sprintf( 'Interval: %s', $interval ) );

		return $interval;
	}

	/** PDT Functions -------------------------------------- */

	/**
	 * PDT: Check Response.
	 *
	 * @since 4.3.0
	 */
	public function pdt_check_response( $order_id ) {
		if ( ! empty( $_REQUEST['cm'] ) && ! empty( $_REQUEST['tx'] ) && ! empty( $_REQUEST['st'] ) ) {
			$this->setup();

			$this->log( sprintf( 'Checking PayPal PDT: %s', wpcw_print_r( $_REQUEST, true ) ) );

			$order_id    = wpcw_clean( stripslashes( $_REQUEST['cm'] ) );
			$status      = wpcw_clean( strtolower( stripslashes( $_REQUEST['st'] ) ) );
			$amount      = wpcw_clean( stripslashes( $_REQUEST['amt'] ) );
			$transaction = wpcw_clean( stripslashes( $_REQUEST['tx'] ) );

			$order = $this->get_paypal_order( $order_id );

			if ( ! $order || ! $order->has_order_status( 'pending' ) ) {
				return false;
			}

			$transaction_result = $this->pdt_validate_transaction( $transaction );

			if ( $transaction_result ) {
				$this->log( 'PDT Transaction Result: ' . wpcw_print_r( $transaction_result, true ) );

				$order->update_meta( '_paypal_status', $status );

				if ( 'completed' === strtolower( $status ) ) {
					if ( $order->get_total() != $amount ) {
						/* translators: %1$s: amount, %2$s: total */
						$this->log( sprintf( 'PDT Payment error: Amounts do not match (amt %1$s) (total %2$s)', $amount, $order->get_total() ), 'error' );
						/* translators: %1$s: amount, %2$s: total */
						$this->payment_on_hold( $order, sprintf( __( 'Validation error: PayPal amounts do not match (amt %1$s) (total %2$s).', 'wp-courseware' ), $amount, $order->get_subtotal() ) );
					} else {
						if ( 'subscr_payment' === $transaction_result['txn_type'] ) {
							/* translators: %1$s - subscription id */
							$this->payment_processing( $order, sprintf( __( 'Payment pending. Waiting for subscription (id: %1$s) to be setup and record initial payment.', 'wp-courseware' ), $transaction_result['subscr_id'] ) );
						} else {
							if ( ! empty( $transaction_result['mc_fee'] ) ) {
								$order->update_meta( '_paypal_transaction_fee', wpcw_clean( $transaction_result['mc_fee'] ) );
							}
							if ( ! empty( $transaction_result['payer_email'] ) ) {
								$order->update_meta( '_paypal_payer_email', wpcw_clean( $transaction_result['payer_email'] ) );
							}
							if ( ! empty( $transaction_result['first_name'] ) ) {
								$order->update_meta( '_paypal_first_name', wpcw_clean( $transaction_result['first_name'] ) );
							}
							if ( ! empty( $transaction_result['last_name'] ) ) {
								$order->update_meta( '_paypal_last_name', wpcw_clean( $transaction_result['last_name'] ) );
							}
							if ( ! empty( $transaction_result['payer_id'] ) ) {
								$order->update_meta( '_paypal_payer_id', wpcw_clean( $transaction_result['payer_id'] ) );
							}
							if ( ! empty( $transaction_result['payment_type'] ) ) {
								$order->update_meta( '_paypal_payment_type', wpcw_clean( $transaction_result['payment_type'] ) );
							}

							// Complete Payment.
							$this->payment_complete( $order, $transaction, esc_html__( 'Payment Complete.', 'wp-courseware' ) );
						}
					}
				} else {
					if ( 'authorization' === $transaction_result['pending_reason'] ) {
						$this->payment_on_hold( $order, __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'wp-courseware' ) );
					} else {
						/* translators: %s: pending reason */
						$this->payment_on_hold( $order, sprintf( __( 'Payment pending (%s).', 'wp-courseware' ), $transaction_result['pending_reason'] ) );
					}
				}
			} else {
				$this->log( 'Received invalid response from PayPal PDT' );
			}
		} elseif ( ! empty( $_REQUEST['txn_type'] ) && ! empty( $_REQUEST['subscr_id'] ) && ! empty( $_REQUEST['custom'] ) ) {
			$this->setup();

			$posted = wp_unslash( $_REQUEST );
			$order  = $this->get_paypal_order( $posted['custom'] );

			if ( $order ) {
				$posted['txn_type'] = strtolower( $posted['txn_type'] );

				$this->log( 'Found Subscription Parent Order #' . $order->get_order_id() );
				$this->log( 'Subscription Type: ' . $posted['txn_type'] );
				$this->log( 'Subscription Data: ' . wpcw_print_r( $posted, true ) );

				if ( method_exists( $this, 'ipn_subscription_' . $posted['txn_type'] ) ) {
					call_user_func( array( $this, 'ipn_subscription_' . $posted['txn_type'] ), $order, $posted );
				}
			}
		}
	}

	/**
	 * PDT: Validate Transaction.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transaction
	 *
	 * @return array|bool The transaction restuls.
	 */
	protected function pdt_validate_transaction( $transaction ) {
		$pdt_params = array(
			'body'        => array(
				'cmd' => '_notify-synch',
				'tx'  => $transaction,
				'at'  => $this->get_identity_token(),
			),
			'timeout'     => 60,
			'httpversion' => '1.1',
			'user-agent'  => 'WPCW-PDT-VERIFICATION/' . WPCW_VERSION,
		);

		// Post back to get a response.
		$pdt_response = wp_safe_remote_post( $this->is_sandbox_enabled() ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $pdt_params );

		// Log Request.
		$this->log( 'PDT Request: ' . wpcw_print_r( $pdt_params, true ) );
		$this->log( 'PDT Response: ' . wpcw_print_r( $pdt_response['body'], true ) );

		if ( is_wp_error( $pdt_response ) || strpos( $pdt_response['body'], "SUCCESS" ) !== 0 ) {
			return false;
		}

		$transaction_result  = array_map( 'wpcw_clean', array_map( 'urldecode', explode( "\n", $pdt_response['body'] ) ) );
		$transaction_results = array();

		foreach ( $transaction_result as $line ) {
			$line                            = explode( "=", $line );
			$transaction_results[ $line[0] ] = isset( $line[1] ) ? $line[1] : '';
		}

		if ( ! empty( $transaction_results['charset'] ) && function_exists( 'iconv' ) ) {
			foreach ( $transaction_results as $key => $value ) {
				$transaction_results[ $key ] = iconv( $transaction_results['charset'], 'utf-8', $value );
			}
		}

		return $transaction_results;
	}
}
