<?php
/**
 * WP Courseware Payment Gateway.
 *
 * The base gateway class for all other payment gateway classes to inherit.
 *
 * @since 4.3.0
 * @subpackage Emails
 * @package WPCW
 */

namespace WPCW\Gateways;

use WPCW\Models\Order;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Gateway.
 *
 * @since 4.3.0
 */
abstract class Gateway {

	/**
	 * @var string Gateway Unique Identifier.
	 * @since 4.3.0
	 */
	public $id;

	/**
	 * @var string Gateway Title.
	 * @since 4.3.0
	 */
	public $title;

	/**
	 * @var string Gateway Description.
	 * @since 4.3.0
	 */
	public $description;

	/**
	 * @var string Is gateway enabled? 'Yes' if enabled.
	 * @since 4.3.0
	 */
	public $enabled = 'no';

	/**
	 * @var string What title users will see when checking out.
	 * @since 4.3.0
	 */
	public $method_title = '';

	/**
	 * @var string What description users will see when checking out.
	 * @since 4.3.0
	 */
	public $method_description = '';

	/**
	 * @var string Payment Gateway order button text.
	 * @since 4.3.0
	 */
	public $order_button_text;

	/**
	 * @var bool If the payment gateway is chosen
	 * @since 4.3.0
	 */
	public $chosen;

	/**
	 * @var string The icon for the gateway.
	 * @since 4.3.0
	 */
	public $icon;

	/**
	 * @var bool True if the payment gateway shows fields on checkout.
	 * @since 4.3.0
	 */
	public $has_fields;

	/**
	 * @var array An array of supported features.
	 * @since 4.3.0
	 */
	public $supports = array( 'courses', 'cc-form' );

	/**
	 * @var string The transaction url.
	 * @since 4.3.0
	 */
	public $transaction_url;

	/**
	 * Gateway Constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() { /* Override in child class */
	}

	/**
	 * Get Gateway Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The gateway settings array.
	 */
	public function get_settings_fields() {
		$settings_fields = array(
			array(
				'key'      => $this->get_setting_key( 'enabled' ),
				'type'     => 'checkbox',
				'title'    => esc_html__( 'Enable / Disable', 'wp-courseware' ),
				/* translators: %s - Payment Gateway Title */
				'label'    => sprintf( __( 'Enable %s', 'wp-courseware' ), $this->title ),
				'desc_tip' => esc_html__( 'Enable or Disable the payment gateway.', 'wp-courseware' ),
				'default'  => $this->enabled,
			),
			array(
				'key'         => $this->get_setting_key( 'title' ),
				'title'       => esc_html__( 'Title', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Title', 'wp-courseware' ),
				'type'        => 'text',
				'desc_tip'    => esc_html__( 'This controls the title which the user sees during checkout.', 'wp-courseware' ),
				'default'     => esc_html( $this->title ),
			),
			array(
				'key'         => $this->get_setting_key( 'desc' ),
				'title'       => esc_html__( 'Description', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Description', 'wp-courseware' ),
				'type'        => 'text',
				'desc_tip'    => esc_html__( 'This controls the description which the user sees during checkout.', 'wp-courseware' ),
				'default'     => wp_kses_post( $this->description ),
			),
		);

		/**
		 * Fitler: Filter the settings fields for a specific gateway.
		 *
		 * @since 4.3.0
		 *
		 * @param Gateway The payment gateway object.
		 */
		return apply_filters( "wpcw_gateway_{$this->id}_settings_fields", $settings_fields, $this );
	}

	/**
	 * Load Gateway.
	 *
	 * Initialize actions and filters. This is called
	 * right after constructor in checkout controller.
	 *
	 * @since 4.3.0
	 */
	public function load() { /* Override in child gateway */
	}

	/**
	 * Setup Gateway.
	 *
	 * Initalize some settings and perform other actions.
	 *
	 * @since 4.3.0
	 */
	public function setup() {
		$this->enabled     = $this->get_setting( 'enabled', $this->enabled );
		$this->title       = $this->get_setting( 'title', $this->title );
		$this->description = $this->get_setting( 'desc', $this->description );
	}

	/**
	 * Get Gateway  Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email id.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get Gateway Setting.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The setting key.
	 * @param mixed|null $default_value The setting default value.
	 *
	 * @return mixed The setting by key.
	 */
	public function get_setting( $key, $default_value = null ) {
		$value = wpcw_get_setting( $this->get_setting_key( $key ), $default_value );

		return apply_filters( "wpcw_gateway_{$this->id}_get_setting", $value, $this, $value, $key, $default_value );
	}

	/**
	 * Get Setting Key.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The setting key.
	 *
	 * @return string The setting key prepended with the id of hte gateway.
	 */
	public function get_setting_key( $key ) {
		return wpcw_sanitize_key( "{$this->get_id()}_{$key}" );
	}

	/**
	 * Get Admin Url.
	 *
	 * @since 4.3.0
	 */
	public function get_admin_url() {
		return esc_url_raw( add_query_arg( array(
			'page'    => 'wpcw-settings',
			'tab'     => 'checkout',
			'section' => 'gateways',
			'gateway' => $this->get_slug()
		), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Is Gateway Enabled?
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|void
	 */
	public function is_enabled() {
		return apply_filters( "wpcw_gateway_enabled_{$this->get_id()}", 'yes' === $this->enabled );
	}

	/**
	 * Is Force SSL Enabled.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed
	 */
	public function is_force_ssl_enabled() {
		return 'yes' === wpcw_get_setting( 'force_ssl' ) ? true : false;
	}

	/**
	 * Is Gateway Available?
	 *
	 * @since 4.3.0
	 */
	public function is_available() {
		return $this->is_enabled();
	}

	/**
	 * Supports Currency?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the currency is supported.
	 */
	public function supports_currency() {
		return true;
	}

	/**
	 * Get Gateway Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string The gateway slug.
	 */
	public function get_slug() {
		$slug = $this->get_id();
		$slug = str_replace( '_', '-', $slug );
		$slug = str_replace( 'gateway-', '', $slug );

		return apply_filters( "wpcw_gateway_{$this->get_id()}_slug", $slug );
	}

	/**
	 * Get Gateway Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string The gateway title.
	 */
	public function get_title() {
		return apply_filters( 'wpcw_gateway_title', $this->title, $this );
	}

	/**
	 * Get Gateway Description.
	 *
	 * @since 4.3.0
	 *
	 * @return string The gateway description.
	 */
	public function get_description() {
		return apply_filters( 'wpcw_gateway_description', $this->description, $this );
	}

	/**
	 * Get Gateway Method Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string The gateway method title.
	 */
	public function get_method_title() {
		return apply_filters( 'wpcw_gateway_method_title', $this->method_title, $this );
	}

	/**
	 * Get Gateway Method Description.
	 *
	 * @since 4.3.0
	 *
	 * @return string The gateway method description.
	 */
	public function get_method_description() {
		return apply_filters( 'wpcw_gateway_method_description', $this->method_description, $this );
	}

	/**
	 * Set the gatway as chosen.
	 *
	 * @since 4.3.0
	 */
	public function set_current() {
		$this->chosen = true;
	}

	/**
	 * Is Payment Gateway Chosen?
	 *
	 * @since 4.3.0
	 *
	 * @return bool The gateway is chosen.
	 */
	public function is_chosen() {
		return $this->chosen;
	}

	/**
	 * Get Return Url.
	 *
	 * @since 4.3.0
	 *
	 * @param null|Order The order object.
	 * @param bool Failed flag.
	 *
	 * @return string The return url.
	 */
	public function get_return_url( $order = null, $failed = false ) {
		if ( ! is_null( $order ) && $order instanceof Order ) {
			$return_url = $order->has_status( 'failed' )
				? $order->get_order_failed_url()
				: $order->get_order_received_url();
		} else {
			$return_url = $failed
				? wpcw_get_page_permalink( 'order-failed' )
				: wpcw_get_page_permalink( 'order-received' );
		}

		if ( is_ssl() || 'yes' === wpcw_get_setting( 'force_ssl' ) ) {
			$return_url = set_url_scheme( $return_url, 'https' );
		}

		return apply_filters( 'wpcw_get_return_url', $return_url );
	}

	/**
	 * Get Cancel Url.
	 *
	 * @since 4.3.0
	 *
	 * @param null|Order The order object.
	 *
	 * @return string The return url.
	 */
	public function get_cancel_url( $order = null ) {
		$checkout_url = remove_query_arg( '_wpnonce', wpcw_get_page_permalink( 'checkout' ) );

		if ( ! is_null( $order ) && $order instanceof Order ) {
			$cancel_url = $order->get_cancel_order_url_raw();
		} else {
			$cancel_url = $checkout_url;
		}

		if ( is_ssl() || 'yes' === wpcw_get_setting( 'force_ssl' ) ) {
			$cancel_url = set_url_scheme( $cancel_url, 'https' );
		}

		return apply_filters( 'wpcw_get_cancel_url', $cancel_url );
	}

	/**
	 * Get Icon.
	 *
	 * @since 4.3.0
	 *
	 * @return string The icon html.
	 */
	public function get_icon() {
		$icon = $this->icon ? '<img class="wpcw-payment-method-icon" src="' . wpcw()->https->force_https_url( esc_url_raw( $this->icon ) ) . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';

		return apply_filters( 'wpcw_gateway_icon', $icon, $this->get_id() );
	}

	/**
	 * Get Order Button Text.
	 *
	 * @since 4.3.0
	 *
	 * @return string The order button text.
	 */
	public function get_order_button_text() {
		return ! empty( $this->order_button_text ) ? esc_html( $this->order_button_text ) : esc_html__( 'Place Order', 'wp-courseware' );
	}

	/**
	 * Check support for a feature.
	 *
	 * @since 4.3.0
	 *
	 * @param string $feature The name of a feature to test support for.
	 *
	 * @return bool True if the gateway supports the feature, false otherwise.
	 */
	public function supports( $feature ) {
		return apply_filters( 'wpcw_payment_gateway_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

	/**
	 * Get Supported Features.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of supported features.
	 */
	public function get_supported_features() {
		return $this->supports;
	}

	/**
	 * Has Fields?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the payment gateway shows fields.
	 */
	public function has_fields() {
		return $this->has_fields ? true : false;
	}

	/**
	 * Validate Fields.
	 *
	 * Validation logic for the frontend fields.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data An array of posted data.
	 * @param \WP_Error $errors The WP_Error object.
	 */
	public function validate_fields( &$data, &$errors ) {
	}

	/**
	 * Payment Fields.
	 *
	 * @since 4.3.0
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			printf( '<div class="wpcw-payment-method-desc">%s</div>', wpautop( wptexturize( $description ) ) );
		}

		if ( $this->supports( 'cc-form' ) ) {
			$this->credit_card_form();
		}
	}

	/**
	 * Output field name HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param string $name The field name.
	 *
	 * @return string The field name.
	 */
	public function field_name( $name ) {
		return ' name="' . esc_attr( $this->get_id() . '-' . $name ) . '" ';
	}

	/**
	 * Credit Card Form.
	 *
	 * @since 4.3.0
	 *
	 * @param array $fields The array of fields. Optional.
	 */
	public function credit_card_form( $fields = array() ) {
		$default_fields = array(
			'card-number-field' =>
				'<p class="wpcw-form-row wpcw-form-row-wide">
                    <label for="' . esc_attr( $this->get_id() ) . '-card-number">' . esc_html__( 'Card number', 'wp-courseware' ) . ' <span class="required">*</span></label>
                    <input id="' . esc_attr( $this->get_id() ) . '-card-number" class="input-text wpcw-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="false" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
                </p>',
			'card-expiry-field' =>
				'<p class="wpcw-form-row wpcw-form-row-first">
                    <label for="' . esc_attr( $this->get_id() ) . '-card-expiry">' . esc_html__( 'Expiry (MM/YY)', 'wp-courseware' ) . ' <span class="required">*</span></label>
                    <input id="' . esc_attr( $this->get_id() ) . '-card-expiry" class="input-text wpcw-cc-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="false" type="tel" placeholder="' . esc_attr__( 'MM / YY', 'wp-courseware' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
                </p>',
			'card-cvc-field'    =>
				'<p class="wpcw-form-row wpcw-form-row-last">
                    <label for="' . esc_attr( $this->get_id() ) . '-card-cvc">' . esc_html__( 'Card code', 'wp-courseware' ) . ' <span class="required">*</span></label>
                    <input id="' . esc_attr( $this->get_id() ) . '-card-cvc" 
                           class="input-text wc-credit-card-form-card-cvc" 
                           inputmode="numeric" 
                           autocomplete="off" 
                           autocorrect="no" 
                           autocapitalize="no" 
                           spellcheck="false" 
                           type="tel" 
                           maxlength="4" 
                           placeholder="' . esc_attr__( 'CVC', 'wp-courseware' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
                </p>',
		);

		$cc_fields = wp_parse_args( $fields, apply_filters( 'wpcw_gateway_cc_form_fields', $default_fields, $this->get_id() ) );

		if ( ! empty( $cc_fields ) ) {
			?>
			<fieldset id="wpcw-<?php echo esc_attr( $this->get_id() ); ?>-cc-form" class="wpcw-cc-form wpcw-form">
				<?php
				do_action( 'wpcw_gateway_cc_form_start', $this->get_id() );

				foreach ( $cc_fields as $field ) {
					echo $field;
				}

				do_action( 'wpcw_gateway_cc_form_end', $this->get_id() );
				?>
				<div class="clear"></div>
			</fieldset>
			<?php
		}
	}

	/**
	 * Get Transaction Url.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return string The Transaction Url.
	 */
	public function get_transaction_url( $order ) {
		$return_url     = '';
		$transaction_id = $order->get_transaction_id();

		if ( ! empty( $this->transaction_url ) && ! empty( $transaction_id ) ) {
			$return_url = sprintf( $this->transaction_url, $transaction_id );
		}

		return apply_filters( 'wpcw_get_transaction_url', $return_url, $order, $this );
	}

	/**
	 * Process Payment.
	 *
	 * Process the payment. Override this in your gateway. When implemented, this should.
	 * return the success and redirect in an array. e.g:
	 *
	 * return array(
	 *      'result'   => 'success',
	 *      'redirect' => $this->get_return_url( $order )
	 * );
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The Order Id.
	 *
	 * @return array See above.
	 */
	public function process_payment( $order_id ) {
		return array();
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The Order object.
	 * @param float $amount The amount to be refunded.
	 * @param string $reason Optional. The reason to process the refund.
	 *
	 * @return boolean True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Process Subscription Cancellation.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 */
	public function process_subscription_cancellation( $subscription ) {
		return false;
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
		return '';
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
		return '';
	}
}
