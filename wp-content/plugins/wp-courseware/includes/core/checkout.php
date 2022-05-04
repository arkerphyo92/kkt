<?php
/**
 * WP Courseware Checkout.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */
namespace WPCW\Core;

use Exception;
use WP_Error;
use WPCW\Gateways\Gateway;
use WPCW\Models\Course;
use WPCW\Models\Order;
use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Checkout.
 *
 * @since 4.6.3
 */
class Checkout {

	/**
	 * @var array Checkout Fields.
	 * @since 4.3.0
	 */
	protected $fields = array();

	/**
	 * @var Student The student object.
	 * @since 4.3.0
	 */
	protected $student;

	/**
	 * Checkout Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Checkout Form Hooks.
		add_action( 'wpcw_checkout_login', array( $this, 'checkout_login' ) );
		add_action( 'wpcw_checkout_fields', array( $this, 'checkout_fields' ) );
		add_action( 'wpcw_checkout_payment', array( $this, 'checkout_payment' ) );

		// Ajax
		add_filter( 'wpcw_ajax_api_events', array( $this, 'checkout_ajax_events' ) );
	}

	/**
	 * Get Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The checkout settings fields.
	 */
	public function get_settings_fields() {
		$pages_section_fields           = $this->get_pages_section_settings_fields();
		$currency_section_fields        = $this->get_currency_section_settings_fields();
		$payment_gateway_section_fields = $this->get_payment_gateways_section_settings_fields();
		$coupons_section_fields         = $this->get_coupons_section_settings_fields();
		$taxes_section_fields           = $this->get_taxes_section_settings_fields();
		$process_section_fields         = $this->get_process_section_settings_fields();
		$privacy_section_fields         = $this->get_privacy_section_settings_fields();

		$settings_fields = array_merge( $pages_section_fields, $currency_section_fields, $payment_gateway_section_fields, $coupons_section_fields, $taxes_section_fields, $process_section_fields, $privacy_section_fields );

		return apply_filters( 'wpcw_checkout_settings_fields', $settings_fields );
	}

	/**
	 * Get Pages Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The pages section settings fields.
	 */
	public function get_pages_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_pages_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'checkout_pages_section_heading',
				'title' => esc_html__( 'Checkout Pages', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings that determine the display of checkout pages.', 'wp-courseware' ),
			),
			array(
				'type'     => 'page',
				'key'      => 'checkout_page',
				'title'    => esc_html__( 'Checkout', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The checkout page.', 'wp-courseware' ),
			),
			array(
				'type'     => 'page',
				'key'      => 'order_received_page',
				'title'    => esc_html__( 'Order Received', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The checkout order received page.', 'wp-courseware' ),
			),
			array(
				'type'     => 'page',
				'key'      => 'order_failed_page',
				'title'    => esc_html__( 'Order Failed', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The checkout order failed page.', 'wp-courseware' ),
			),
			array(
				'type'     => 'page',
				'key'      => 'terms_page',
				'title'    => esc_html__( 'Terms and Conditions', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The terms and conditions page.', 'wp-courseware' ),
			),
		) );
	}

	/**
	 * Get Currency Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The currency section settings fields.
	 */
	public function get_currency_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_currency_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'checkout_currency_section_heading',
				'title' => esc_html__( 'Currency', 'wp-courseware' ),
				'desc'  => esc_html__( 'The following options affect how prices are displayed on the frontend.', 'wp-courseware' ),
			),
			array(
				'type'         => 'select',
				'key'          => 'currency',
				'default'      => 'USD',
				'title'        => esc_html__( 'Currency', 'wp-courseware' ),
				'placeholder'  => esc_html__( 'Select currency', 'wp-courseware' ),
				'desc_tip'     => esc_html__( 'Note: Certain payment gateways have currency restrictions.', 'wp-courseware' ),
				'options'      => $this->get_currency_options(),
				'blank_option' => esc_html__( 'Select currency', 'wp-courseware' ),
			),
			array(
				'type'         => 'select',
				'key'          => 'currency_position',
				'default'      => 'left',
				'title'        => esc_html__( 'Currency position', 'wp-courseware' ),
				'placeholder'  => esc_html__( 'Select currency position', 'wp-courseware' ),
				'desc_tip'     => esc_html__( 'This controls the position of the currency symbol.', 'wp-courseware' ),
				'options'      => wpcw_get_currency_positions(),
				'blank_option' => esc_html__( 'Select currency position', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'thousands_sep',
				'default'  => ',',
				'title'    => esc_html__( 'Thousands separator', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'This sets the thousands separator or displayed prices.', 'wp-courseware' ),
				'size'     => 'small',
			),
			array(
				'type'     => 'text',
				'key'      => 'decimal_sep',
				'default'  => '.',
				'title'    => esc_html__( 'Decimal separator', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'This sets the decimal separator or displayed prices.', 'wp-courseware' ),
				'size'     => 'small',
			),
			array(
				'type'     => 'number',
				'key'      => 'num_decimals',
				'default'  => 2,
				'title'    => esc_html__( 'Number of decimals', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'This sets the decimal separator or displayed prices.', 'wp-courseware' ),
				'min'      => 0,
				'step'     => 1,
				'size'     => 'small',
			),
		) );
	}

	/**
	 * Get Coupons Section Settings Fields.
	 *
	 * @since 4.5.0
	 *
	 * @return array The coupons section settings fields.
	 */
	public function get_coupons_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_coupons_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'checkout_coupons_section_heading',
				'title' => esc_html__( 'Coupons', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to coupons when purchasing courses.', 'wp-courseware' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'enable_coupons',
				'title'    => esc_html__( 'Enable Coupons?', 'wp-courseware' ),
				'label'    => esc_html__( 'Enable the use of coupons when purchasing courses.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'Coupons can be applied from the cart and checkout pages.', 'wp-courseware' ),
				'default'  => 'yes',
			),
		) );
	}

	/**
	 * Get Taxes Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The taxes section settings fields.
	 */
	public function get_taxes_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_taxes_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'checkout_taxes_section_heading',
				'title' => esc_html__( 'Taxes', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to taxes when purchasing courses.', 'wp-courseware' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'enable_taxes',
				'title'    => esc_html__( 'Enable Taxes?', 'wp-courseware' ),
				'label'    => esc_html__( 'Enable taxes on purchases.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'When taxes are enabled, WP Courseware will use the settings below to properly calcuate taxes.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'      => 'text',
				'key'       => 'tax_percent',
				'default'   => '5.6',
				'title'     => esc_html__( 'Tax Percentage', 'wp-courseware' ),
				'desc'      => esc_html__( 'Enter value without the % sign.', 'wp-courseware' ),
				'desc_tip'  => esc_html__( 'This sets the percentage used to calculate taxes.', 'wp-courseware' ),
				'size'      => 'small',
				'condition' => array(
					'field' => 'enable_taxes',
					'value' => 'on',
				),
			),
		) );
	}

	/**
	 * Get Payment Gateways Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The currency section settings fields.
	 */
	public function get_payment_gateways_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_payment_gateways_section_settings_fields', array(
			array(
				'type'      => 'payment_gateways',
				'key'       => 'payment_gateways',
				'wrapper'   => false,
				'component' => true,
				'settings'  => wpcw()->gateways->get_gateways_settings(),
			),
			array(
				'type'    => 'hidden',
				'key'     => 'payment_gateways_order',
				'default' => '',
			),
		) );
	}

	/**
	 * Get Process Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The ssl section settings fields.
	 */
	public function get_process_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_process_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'checkout_process_section_heading',
				'title' => esc_html__( 'Checkout Process', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to how the cart and checkout process works.', 'wp-courseware' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'force_ssl',
				'title'    => esc_html__( 'Force SSL (HTTPS)?', 'wp-courseware' ),
				'label'    => esc_html__( 'Force secure checkout on the checkout pages.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'By checking this box it will make sure that the checkout pages are served over SSL.', 'wp-courseware' ),
				'desc'     => sprintf( __( 'You must have an <a href="%s">SSL Certificate installed</a>.', 'wp-courseware' ), '#' ),
				'default'  => 'no',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'redirect_to_checkout',
				'title'    => esc_html__( 'Redirect to Checkout?', 'wp-courseware' ),
				'label'    => esc_html__( 'Immediately redirect to checkout after adding an item to the cart?', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'When enabled, once an item has been added to the cart, the customer will be redirected directly to your checkout page.', 'wp-courseware' ),
				'default'  => 'no',
			),
		) );
	}

	/**
	 * Get Privacy Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The privacy section settings fields.
	 */
	public function get_privacy_section_settings_fields() {
		return apply_filters( 'wpcw_checkout_privacy_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'checkout_privacy_section_heading',
				'title' => esc_html__( 'Privacy', 'wp-courseware' ),
				'desc'  => esc_html__( 'Below are settings related to the privacy policy and privacy of students data.', 'wp-courseware' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'privacy_policy',
				'title'    => esc_html__( 'Agree to Privacy Policy?', 'wp-courseware' ),
				'label'    => esc_html__( 'Show an agree to privacy policy on checkout page.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'By checking this box it will make sure the user checks a box agreeing to the privacy policy before checking out.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'     => 'page',
				'key'      => 'privacy_page',
				'title'    => esc_html__( 'Privacy Policy Page', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The privacy policy page.', 'wp-courseware' ),
			),
		) );
	}

	/**
	 * Create Checkout Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_checkout_page() {
		return wp_insert_post(
			array(
				'post_title'     => esc_html__( 'Checkout', 'wp-courseware' ),
				'post_content'   => '[wpcw_checkout]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 11,
			)
		);
	}

	/**
	 * Create Checkout Order Recieved Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_checkout_order_received_page() {
		return wp_insert_post(
			array(
				'post_title'     => esc_html__( 'Order Received', 'wp-courseware' ),
				'post_content'   => '[wpcw_order_received]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 12,
			)
		);
	}

	/**
	 * Create Checkout Order Failed Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_checkout_order_failed_page() {
		return wp_insert_post(
			array(
				'post_title'     => esc_html__( 'Order Failed', 'wp-courseware' ),
				'post_content'   => '[wpcw_order_failed]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 13,
			)
		);
	}

	/**
	 * Create Checkout Terms and Conditions Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_checkout_terms_page() {
		return wp_insert_post(
			array(
				'post_title'     => __( 'Terms & Conditions', 'wp-courseware' ),
				'post_content'   => '',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 14,
			)
		);
	}

	/**
	 * Create Checkout Privacy Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_checkout_privacy_page() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_pages' ) ) {
			return false;
		}

		return wp_insert_post(
			array(
				'post_title'     => __( 'Privacy Policy', 'wp-courseware' ),
				'post_content'   => '',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 14,
			)
		);
	}

	/**
	 * Get Available Gateways.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of available gateway objects.
	 */
	public function get_available_gateways() {
		return wpcw()->gateways->get_available_gateways();
	}

	/**
	 * Set Current Gateway.
	 *
	 * @since 4.3.0
	 *
	 * @param array $gateways The currently available gateways.
	 */
	public function set_current_gateway( $gateways ) {
		if ( ! is_array( $gateways ) || empty( $gateways ) ) {
			return;
		}

		$current = ( isset( $default_token_gateway ) ? $default_token_gateway : wpcw()->session->get( 'chosen_payment_method' ) );

		if ( $current && isset( $gateways[ $current ] ) ) {
			$current_gateway = $gateways[ $current ];
		} else {
			$current_gateway = current( $gateways );
		}

		// Ensure we can make a call to set_current() without triggering an error
		if ( $current_gateway && is_callable( array( $current_gateway, 'set_current' ) ) ) {
			$current_gateway->set_current();
		}
	}

	/**
	 * Get Currency Options.
	 *
	 * @since 4.3.0
	 *
	 * @return array $currencies A list of the available currencies.
	 */
	public function get_currency_options() {
		$currency_options = array();

		$currencies = wpcw_get_currencies();

		foreach ( $currencies as $code => $name ) {
			$currency_options[ $code ] = sprintf( esc_html__( '%1$s (%2$s)', 'wp-courseware' ), $name, wpcw_get_currency_symbol( $code ) );
		}

		return apply_filters( 'wpcw_checkout_currency_options', $currency_options );
	}

	/**
	 * Get Available Payment Methods.
	 *
	 * @since 4.3.0
	 *
	 * @return array An array of available payment methods.
	 */
	public function get_payment_methods() {
		$methods  = array();
		$gateways = wpcw()->gateways->get_gateways();

		if ( ! empty( $gateways ) ) {
			foreach ( $gateways as $gateway ) {
				if ( ! $gateway instanceof Gateway ) {
					continue;
				}
				$methods[ esc_attr( $gateway->get_slug() ) ] = esc_html( $gateway->get_method_title() );
			}
		}

		return apply_filters( 'wpcw_payment_methods', $methods );
	}

	/**
	 * Checkout Display.
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The array of shortcode attributes.
	 */
	public function checkout_display( $atts = array() ) {
		global $wp;

		if ( isset( $wp->query_vars['order-received'] ) ) {
			$this->checkout_page_order_received( absint( $wp->query_vars['order-received'] ) );
		} elseif ( isset( $wp->query_vars['order-failed'] ) ) {
			$this->checkout_page_order_failed( absint( $wp->query_vars['order-failed'] ) );
		} else {
			$this->checkout_page();
		}
	}

	/**
	 * Checkout Order Received Display.
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The array of shortcode attributes.
	 */
	public function checkout_order_received_display( $atts = array() ) {
		global $wp;

		$order_id = empty( $_GET['order_id'] ) ? 0 : wpcw_clean( absint( $_GET['order_id'] ) );

		$this->checkout_page_order_received( $order_id );
	}

	/**
	 * Checkout Page: Order Received.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The order id.
	 */
	protected function checkout_page_order_received( $order_id ) {

		if ( 0 === $order_id ) {
			wpcw_print_notice( sprintf( __( 'No order was found. <a href="%s">Return to Account &rarr;</a>', 'wp-courseware' ), wpcw_get_page_permalink( 'account' ) ), 'error' );

			return;
		}

		// Get the order
		$order_id  = apply_filters( 'wpcw_checkout_order_received_order_id', absint( $order_id ) );
		$order_key = apply_filters( 'wpcw_checkout_order_received_order_key', empty( $_GET['key'] ) ? '' : wpcw_clean( $_GET['key'] ) );
		$order     = wpcw_get_order( $order_id );

		// Bail if there is no order.
		if ( ! $order ) {
			wpcw_print_notice( sprintf( __( 'No order was found. <a href="%s">Return to Account &rarr;</a>', 'wp-courseware' ), wpcw_get_page_permalink( 'account' ) ), 'error' );

			return;
		}

		// Log it.
		if ( $order->get_order_key() !== $order_key ) {
			$this->log( sprintf( 'Order #%1$s matched an existing order, but the Order Key "%2$s" did not match the key returned "%3$s".', $order_id, $order->get_order_key(), $order_key ), false );
		}

		// Empty Awaiting Payment Session.
		unset( wpcw()->session->order_awaiting_payment );

		// Empty Cart.
		wpcw_empty_cart();

		// Display Template.
		wpcw_get_template( 'checkout/order-received.php', array( 'order' => $order ) );

		/**
		 * Action: Checkout After Order Received by Payment Method.
		 *
		 * @since 4.3.0
		 *
		 * @param int $order_id The order id.
		 */
		do_action( 'wpcw_checkout_after_order_received_' . $order->get_payment_method(), $order->get_order_id() );

		/**
		 * Action: Checkout After Order Received.
		 *
		 * @since 4.3.0
		 *
		 * @param int $order_id The order id.
		 */
		do_action( 'wpcw_checkout_after_order_received', $order->get_order_id() );
	}

	/**
	 * Checkout Order Failed Display.
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The array of shortcode attributes.
	 */
	public function checkout_order_failed_display( $atts = array() ) {
		global $wp;

		$order_id = empty( $_GET['order_id'] ) ? 0 : wpcw_clean( absint( $_GET['order_id'] ) );

		$this->checkout_page_order_failed( $order_id );
	}

	/**
	 * Checkout Page: Order Failed
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The order id.
	 */
	protected function checkout_page_order_failed( $order_id ) {
		if ( 0 === $order_id ) {
			wpcw_print_notice( sprintf( __( 'No order was found. <a href="%s">Return to Account &rarr;</a>', 'wp-courseware' ), wpcw_get_page_permalink( 'account' ) ), 'error' );

			return;
		}

		printf( __( 'Order %d Failed', 'wp-courseware' ), absint( $order_id ) );
	}

	/**
	 * Checkout Page.
	 *
	 * @since 4.3.0
	 */
	protected function checkout_page() {
		wpcw_print_notices();

		if ( wpcw()->cart->is_empty() ) {
			/**
			 * Action: Checkout Cart Empty.
			 *
			 * @since 4.3.0
			 *
			 * @param Checkout The checkout controller object.
			 */
			do_action( 'wpcw_checkout_cart_empty', $this );

			return;
		}

		if ( is_user_logged_in() ) {
			$this->student = new Student( get_current_user_id() );
		}

		/**
		 * Action: Before Checkout Form.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_before_checkout_form', $this );

		/**
		 * Action: Checkout Login.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_checkout_login', $this );

		wpcw_get_template( 'checkout/form-checkout.php', array( 'checkout' => $this ) );

		/**
		 * Action: After Checkout Form.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_after_checkout_form', $this );
	}

	/**
	 * Checkout Login Form.
	 *
	 * @since 4.3.0
	 *
	 * @param Checkout The checkout controller object.
	 */
	public function checkout_login( $checkout ) {
		/**
		 * Action: Checkout Login Before.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_checkout_login_before', $checkout );

		wpcw_get_template( 'checkout/form-login.php', array(
			'checkout' => $checkout,
		) );

		/**
		 * Checkout Login After.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_checkout_login_after', $checkout );
	}

	/**
	 * Checkout Fields.
	 *
	 * @since 4.3.0
	 *
	 * @param Checkout $checkout The checkout object.
	 */
	public function checkout_fields( $checkout ) {
		/**
		 * Action: Checkout Fields Before.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_checkout_fields_before', $checkout );

		wpcw_get_template( 'checkout/form-fields.php', array(
			'checkout' => $checkout,
		) );

		/**
		 * Action: Checkout Fields After.
		 *
		 * @since 4.3.0
		 *
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_checkout_fields_after', $checkout );
	}

	/**
	 * Get Checkout Fields.
	 *
	 * @since 4.3.0
	 *
	 * @param string $fieldset The fields set to get.
	 *
	 * @return array The checkout fields.
	 */
	public function get_checkout_fields( $fieldset = '' ) {
		if ( empty( $this->fields ) ) {
			$this->fields = array(
				'primary' => wpcw()->students->get_student_primary_fields(),
				'account' => wpcw()->students->get_student_account_fields(),
				'billing' => wpcw()->students->get_student_billing_fields( $this->get_posted_value( 'billing_country' ) ),
			);

			/**
			 * Filter: Get Checkout Fields.
			 *
			 * @since 4.3.0
			 *
			 * @param array $fields The checkout fields.
			 *
			 * @return array $fields The checkout fields.
			 */
			$this->fields = apply_filters( 'wpcw_get_checkout_fields', $this->fields );
		}
		if ( $fieldset ) {
			return $this->fields[ $fieldset ];
		} else {
			return $this->fields;
		}
	}

	/**
	 * Get Posted Value.
	 *
	 * Gets the value either from the posted data, or from the users meta data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $input The input name.
	 *
	 * @return string
	 */
	public function get_posted_value( $input ) {
		if ( ! empty( $_POST[ $input ] ) ) {
			return wpcw_clean( $_POST[ $input ] );
		} else {
			$value = apply_filters( 'wpcw_checkout_get_value', null, $input );

			if ( null !== $value ) {
				return $value;
			}

			if ( ! is_user_logged_in() ) {
				return $value;
			}

			if ( empty( $this->student ) ) {
				$this->student = new Student( get_current_user_id() );
			}

			if ( is_callable( array( $this->student, "get_$input" ) ) ) {
				$value = $this->student->{"get_$input"}() ? $this->student->{"get_$input"}() : null;
			} else {
				$value = get_user_meta( $this->student->get_ID(), wpcw_clean( $input ), true );
			}

			return apply_filters( 'default_checkout_' . $input, $value, $input );
		}
	}

	/**
	 * Checkout Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param Checkout The checkout object.
	 */
	public function checkout_payment( $checkout ) {
		if ( wpcw()->cart->needs_payment() ) {
			$available_gateways = $this->get_available_gateways();
			$this->set_current_gateway( $available_gateways );
		} else {
			$available_gateways = array();
		}

		wpcw_get_template( 'checkout/payment.php', array(
			'checkout'           => $checkout,
			'available_gateways' => $available_gateways,
			'order_button_text'  => apply_filters( 'wpcw_order_button_text', esc_html__( 'Place order', 'wp-courseware' ) ),
		) );
	}

	/**
	 * Checkout Ajax Events.
	 *
	 * @since 4.3.0
	 *
	 * @param array $ajax_events The ajax Events.
	 */
	public function checkout_ajax_events( $ajax_events ) {
		$checkout_ajax_events = array(
			'review'   => array( $this, 'ajax_checkout_review' ),
			'validate' => array( $this, 'ajax_checkout_process_validate' ),
			'checkout' => array( $this, 'ajax_checkout_process' ),
		);

		return array_merge( $ajax_events, $checkout_ajax_events );
	}

	/**
	 * AJAX: Checkout Review.
	 *
	 * @since 4.3.0
	 */
	public function ajax_checkout_review() {
		wpcw()->ajax->verify_nonce();

		wpcw_maybe_define_constant( 'WPCW_CHECKOUT', true );

		if ( wpcw()->cart->is_empty() ) {
			wp_send_json( array(
				'fragments' => apply_filters( 'wpcw_review_fragments', array(
					'form.wpcw-checkout-form' => sprintf(
						'<div class="wpcw-error">%s <a href="%s" class="wpcw-backward">%s</a></div>',
						esc_html__( 'Sorry, your session has expired.', 'wp-courseware' ),
						esc_url( wpcw_get_page_permalink( 'courses' ) ),
						esc_html__( 'Return to courses', 'wp-courseware' )
					),
				) ),
			) );
		}

		do_action( 'wpcw_checkout_review', $_POST['post_data'] );

		// Get checkout payment fragment
		ob_start();
		$this->checkout_payment( $this );
		$checkout_payment = ob_get_clean();

		// Get messages if reload checkout is not true
		$messages = '';
		if ( ! isset( wpcw()->session->reload_checkout ) ) {
			$messages = wpcw_get_notices();
		}

		wp_send_json( array(
			'result'    => empty( $messages ) ? 'success' : 'failure',
			'messages'  => $messages,
			'reload'    => isset( wpcw()->session->reload_checkout ) ? 'true' : 'false',
			'fragments' => apply_filters( 'wpcw_checkout_review_fragments', array(
				'.wpcw-checkout-payment' => $checkout_payment,
			) ),
		) );
	}

	/**
	 * AJAX: Checkout Process Validate.
	 *
	 * @since 4.3.4
	 */
	public function ajax_checkout_process_validate() {
		wpcw_maybe_define_constant( 'WPCW_CHECKOUT', true );
		wpcw_maybe_catch_form_data();
		$this->process_checkout_validate();
		wp_die( 0 );
	}

	/**
	 * AJAX: Checkout Process.
	 *
	 * @since 4.3.0
	 */
	public function ajax_checkout_process() {
		wpcw_maybe_define_constant( 'WPCW_CHECKOUT', true );
		wpcw_maybe_catch_form_data();
		$this->process_checkout();
		wp_die( 0 );
	}

	/**
	 * Process Checkout Validate.
	 *
	 * @since 4.3.4
	 */
	public function process_checkout_validate() {
		try {
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpcw-process-checkout' ) ) {
				throw new Exception( esc_html__( 'We were unable to process your order, please try again.', 'wp-courseware' ) );
			}

			// Set Checkout Constant.
			wpcw_maybe_define_constant( 'WPCW_CHECKOUT', true );

			// Set Time limit.
			wpcw_set_time_limit( 0 );

			/**
			 * Action: Before Checkout Process Validate.
			 *
			 * @since 4.3.4
			 *
			 * @param Checkout The checkout controller.
			 */
			do_action( 'wpcw_before_checkout_process_validate', $this );

			// Check for empty cart.
			if ( wpcw()->cart->is_empty() ) {
				throw new Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s" class="wpcw-backward">Back to Courses</a>', 'wp-courseware' ), esc_url( wpcw_get_page_permalink( 'checkout' ) ) ) );
			}

			/**
			 * Action: Checkout Process.
			 *
			 * @since 4.3.0
			 *
			 * @param Checkout The checkout controller object
			 */
			do_action( 'wpcw_checkout_process_validate', $this );

			$errors      = new WP_Error();
			$posted_data = $this->get_checkout_posted_data();

			$this->update_checkout_session( $posted_data );
			$this->validate_checkout( $posted_data, $errors );

			foreach ( $errors->get_error_messages() as $message ) {
				wpcw_add_notice( $message, 'error' );
			}

			if ( 0 === wpcw_notice_count( 'error' ) ) {
				wp_send_json_success();
			}
		} catch ( Exception $e ) {
			wpcw_add_notice( $e->getMessage(), 'error' );
		}

		$this->process_checkout_failed();
	}

	/**
	 * Process Checkout.
	 *
	 * @since 4.3.0
	 */
	public function process_checkout() {
		try {
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpcw-process-checkout' ) ) {
				throw new Exception( esc_html__( 'We were unable to process your order, please try again.', 'wp-courseware' ) );
			}

			// Set Checkout Constant.
			wpcw_maybe_define_constant( 'WPCW_CHECKOUT', true );

			// Set Time limit.
			wpcw_set_time_limit( 0 );

			/**
			 * Action: Before Checkout Process.
			 *
			 * @since 4.3.0
			 *
			 * @param Checkout The checkout controller.
			 */
			do_action( 'wpcw_before_checkout_process', $this );

			// Check for empty cart.
			if ( wpcw()->cart->is_empty() ) {
				throw new Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s" class="wpcw-backward">Back to Courses</a>', 'wp-courseware' ), esc_url( wpcw_get_page_permalink( 'checkout' ) ) ) );
			}

			/**
			 * Action: Checkout Process.
			 *
			 * @since 4.3.0
			 *
			 * @param Checkout The checkout controller object
			 */
			do_action( 'wpcw_checkout_process', $this );

			$errors      = new WP_Error();
			$posted_data = $this->get_checkout_posted_data();

			$this->update_checkout_session( $posted_data );
			$this->validate_checkout( $posted_data, $errors );

			foreach ( $errors->get_error_messages() as $message ) {
				wpcw_add_notice( $message, 'error' );
			}

			if ( 0 === wpcw_notice_count( 'error' ) ) {
				$this->process_student( $posted_data );

				$order_id = $this->create_order( $posted_data );

				if ( is_wp_error( $order_id ) ) {
					throw new Exception( $order_id->get_error_message() );
				}

				$order = wpcw_get_order( $order_id );

				/**
				 * Action: Checkout Order Processed.
				 *
				 * @since 4.3.0
				 *
				 * @param int   $order_id The order id.
				 * @param array $posted_data The posted data.
				 * @param Order The order object.
				 */
				do_action( 'wpcw_checkout_order_processed', $order_id, $posted_data, $order );

				if ( wpcw()->cart->needs_payment() ) {
					$this->process_order_payment( $order_id, $posted_data['payment_method'] );
				} else {
					$this->process_order_without_payment( $order_id );
				}
			}
		} catch ( Exception $e ) {
			wpcw_add_notice( $e->getMessage(), 'error' );
		}

		$this->process_checkout_failed();
	}

	/**
	 * Process Checkout Failed.
	 *
	 * Collects all errors and returns
	 * the checkout as failed.
	 *
	 * @since 4.3.0
	 */
	protected function process_checkout_failed() {
		if ( wpcw_is_ajax() ) {
			$response = array(
				'result'   => 'failure',
				'messages' => wpcw_get_notices(),
				'reload'   => isset( wpcw()->session->reload_checkout ),
			);

			unset( wpcw()->session->reload_checkout );

			wp_send_json_error( $response );
		} else {
			wp_safe_redirect( apply_filters( 'wpcw_checkout_failed_redirect', wpcw_get_page_permalink( 'checkout' ) ) );
			exit;
		}
	}

	/**
	 * Checkout: Get Posted Data.
	 *
	 * @since 4.3.0
	 *
	 * @return array of data.
	 */
	protected function get_checkout_posted_data() {
		$data = array(
			'terms'          => (int) isset( $_POST['terms'] ),
			'privacy'        => (int) isset( $_POST['privacy'] ),
			'payment_method' => isset( $_POST['payment_method'] ) ? wpcw_clean( $_POST['payment_method'] ) : '',
		);

		foreach ( $this->get_checkout_fields() as $fieldset_key => $fieldset ) {
			if ( $this->maybe_skip_fieldset( $fieldset_key, $data ) ) {
				continue;
			}

			foreach ( $fieldset as $key => $field ) {
				$type = sanitize_title( isset( $field['type'] ) ? $field['type'] : 'text' );

				switch ( $type ) {
					case 'checkbox' :
						$value = isset( $_POST[ $key ] ) ? 1 : '';
						break;
					case 'multiselect' :
						$value = isset( $_POST[ $key ] ) ? implode( ', ', wpcw_clean( $_POST[ $key ] ) ) : '';
						break;
					case 'textarea' :
						$value = isset( $_POST[ $key ] ) ? wpcw_sanitize_textarea( $_POST[ $key ] ) : '';
						break;
					default :
						$value = isset( $_POST[ $key ] ) ? wpcw_clean( $_POST[ $key ] ) : '';
						break;
				}

				$data[ $key ] = apply_filters( 'wpcw_checkout_process_' . $type . '_field', apply_filters( 'wpcw_process_checkout_field_' . $key, $value ) );
			}
		}

		return apply_filters( 'wpcw_checkout_posted_data', $data );
	}

	/**
	 * Check to see if a set of fields should be skipped.
	 *
	 * @since 4.3.0
	 *
	 * @param string $fieldset_key The fieldset key.
	 * @param array  $data The array data.
	 *
	 * @return bool True if needs to be skipped, False otherwise.
	 */
	protected function maybe_skip_fieldset( $fieldset_key, $data ) {
		if ( 'account' === $fieldset_key && is_user_logged_in() ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate Checkout Posted Data.
	 *
	 * @since 4.3.0
	 *
	 * @param array    $data An array of posted data.
	 * @param WP_Error $errors The array of WP_Error objects.
	 */
	protected function validate_checkout_posted_data( &$data, &$errors ) {
		foreach ( $this->get_checkout_fields() as $fieldset_key => $fieldset ) {
			if ( $this->maybe_skip_fieldset( $fieldset_key, $data ) ) {
				continue;
			}

			foreach ( $fieldset as $key => $field ) {
				if ( ! isset( $data[ $key ] ) ) {
					continue;
				}

				$required    = ! empty( $field['required'] );
				$format      = array_filter( isset( $field['validate'] ) ? (array) $field['validate'] : array() );
				$field_label = isset( $field['label'] ) ? $field['label'] : '';

				switch ( $fieldset_key ) {
					case 'billing' :
						/* translators: %s: field name */
						$field_label = sprintf( __( 'Billing %s', 'wp-courseware' ), $field_label );
						break;
				}

				if ( in_array( 'postcode', $format ) ) {
					$country      = isset( $data[ $fieldset_key . '_country' ] ) ? $data[ $fieldset_key . '_country' ] : '';
					$data[ $key ] = wpcw_format_postcode( $data[ $key ], $country );

					if ( '' !== $data[ $key ] && ! wpcw_validation_is_postcode( $data[ $key ], $country ) ) {
						$errors->add( 'validation', sprintf( __( '%s is not a valid postcode / ZIP.', 'wp-courseware' ), '<strong>' . esc_html( $field_label ) . '</strong>' ) );
					}
				}

				if ( in_array( 'phone', $format ) ) {
					$data[ $key ] = wpcw_format_phone_number( $data[ $key ] );

					if ( '' !== $data[ $key ] && ! wpcw_validation_is_phone( $data[ $key ] ) ) {
						/* translators: %s: phone number */
						$errors->add( 'validation', sprintf( __( '%s is not a valid phone number.', 'wp-courseware' ), '<strong>' . esc_html( $field_label ) . '</strong>' ) );
					}
				}

				if ( in_array( 'email', $format ) && '' !== $data[ $key ] ) {
					$data[ $key ] = sanitize_email( $data[ $key ] );

					if ( ! is_email( $data[ $key ] ) ) {
						/* translators: %s: email address */
						$errors->add( 'validation', sprintf( __( '%s is not a valid email address.', 'wp-courseware' ), '<strong>' . esc_html( $field_label ) . '</strong>' ) );
						continue;
					}
				}

				if ( '' !== $data[ $key ] && in_array( 'state', $format ) ) {
					$country      = isset( $data[ $fieldset_key . '_country' ] ) ? $data[ $fieldset_key . '_country' ] : '';
					$valid_states = wpcw()->countries->get_states( $country );

					if ( ! empty( $valid_states ) && is_array( $valid_states ) && sizeof( $valid_states ) > 0 ) {
						$valid_state_values = array_map( 'wpcw_strtoupper', array_flip( array_map( 'wpcw_strtoupper', $valid_states ) ) );
						$data[ $key ]       = wpcw_strtoupper( $data[ $key ] );

						if ( isset( $valid_state_values[ $data[ $key ] ] ) ) {
							// With this part we consider state value to be valid as well, convert it to the state key for the valid_states check below.
							$data[ $key ] = $valid_state_values[ $data[ $key ] ];
						}

						if ( ! in_array( $data[ $key ], $valid_state_values ) ) {
							/* translators: 1: state field 2: valid states */
							$errors->add( 'validation', sprintf( __( '%1$s is not valid. Please enter one of the following: %2$s', 'wp-courseware' ), '<strong>' . esc_html( $field_label ) . '</strong>', implode( ', ', $valid_states ) ) );
						}
					}
				}

				if ( $required && '' === $data[ $key ] ) {
					/* translators: %s: field name */
					$errors->add( 'required-field', apply_filters( 'wpcw_checkout_required_field_notice', sprintf( __( '%s is a required field.', 'wp-courseware' ), '<strong>' . esc_html( $field_label ) . '</strong>' ), $field_label ) );
				}
			}
		}
	}

	/**
	 * Update Checkout Sesssion.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The data of the session.
	 */
	protected function update_checkout_session( $data ) {
		wpcw()->session->set( 'chosen_payment_method', $data['payment_method'] );
	}

	/**
	 * Valildate Checkout.
	 *
	 * @since 4.3.0
	 *
	 * @param array     $data An array of posted data.
	 * @param \WP_Error $errors
	 */
	protected function validate_checkout( &$data, &$errors ) {
		$this->validate_checkout_posted_data( $data, $errors );

		if ( empty( $data['terms'] ) && apply_filters( 'wpcw_checkout_show_terms', ( wpcw_get_page_id( 'terms' ) > 0 ) ) ) {
			$errors->add( 'terms', __( 'You must accept our Terms &amp; Conditions.', 'wp-courseware' ) );
		}

		if ( empty( $data['privacy'] ) && ( 'yes' === wpcw_get_setting( 'privacy_policy' ) ) && apply_filters( 'wpcw_checkout_show_privacy', ( wpcw_get_page_id( 'privacy' ) > 0 ) ) ) {
			$errors->add( 'privacy-policy', __( 'You must accept our Privay Policy.', 'wp-courseware' ) );
		}

		if ( wpcw()->cart->needs_payment() ) {
			$available_gateways = $this->get_available_gateways();

			if ( ! isset( $available_gateways[ $data['payment_method'] ] ) ) {
				$errors->add( 'payment', __( 'Invalid payment method.', 'wp-courseware' ) );
			} else {
				$available_gateways[ $data['payment_method'] ]->validate_fields( $data, $errors );
			}
		}

		/**
		 * Action: Checkout After Validation.
		 *
		 * @since 4.3.0
		 *
		 * @param array    $data The array of posted data.
		 * @param WP_Error $errors The WP_Error objects.
		 * @param Checkout The checkout controller object.
		 */
		do_action( 'wpcw_checkout_after_validation', $data, $errors, $this );
	}

	/**
	 * Create a new student account if needed.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The posted data.
	 */
	protected function process_student( $data ) {
		$student_id = apply_filters( 'wpcw_checkout_student_id', get_current_user_id() );

		if ( ! is_user_logged_in() ) {
			$username   = ! empty( $data['account_username'] ) ? $data['account_username'] : '';
			$password   = ! empty( $data['account_password'] ) ? $data['account_password'] : '';
			$student_id = wpcw_create_new_student( $data['email'], $username, $password );

			if ( is_wp_error( $student_id ) ) {
				throw new Exception( $student_id->get_error_message() );
			}

			wp_set_current_user( $student_id );
			wpcw_set_student_auth_cookie( $student_id );
		}

		// On multisite, ensure user exists on current site, if not add them before allowing login.
		if ( $student_id && is_multisite() && is_user_logged_in() && ! is_user_member_of_blog() ) {
			add_user_to_blog( get_current_blog_id(), $student_id, 'subscriber' );
		}

		// Add customer info from other fields.
		if ( $student_id && apply_filters( 'wpcw_checkout_update_student_data', true, $this ) ) {
			$student = new Student( $student_id );

			if ( ! empty( $data['first_name'] ) ) {
				$student->set_prop( 'first_name', esc_attr( $data['first_name'] ) );
			}

			if ( ! empty( $data['last_name'] ) ) {
				$student->set_prop( 'last_name', esc_attr( $data['last_name'] ) );
			}

			// If the display name is an email, update to the user's full name.
			if ( ( ! empty( $data['first_name'] ) && ! empty( $data['last_name'] ) ) || is_email( $student->get_display_name() ) ) {
				$student->set_prop( 'display_name', esc_attr( $data['first_name'] ) . ' ' . esc_attr( $data['last_name'] ) );
			}

			foreach ( $data as $key => $value ) {
				$student->set_prop( $key, $value );
			}

			if ( ! empty( $data['terms'] ) ) {
				$student->update_meta( '_wpcw_agree_to_terms_time', date_i18n( 'Y-m-d H:i:s' ) );
			}

			if ( ( 'yes' === wpcw_get_setting( 'privacy_policy' ) ) && ! empty( $data['privacy'] ) ) {
				$student->update_meta( '_wpcw_agree_to_privacy_policy_time', date_i18n( 'Y-m-d H:i:s' ) );
			}

			/**
			 * Action: Checkout Update Student.
			 *
			 * @since 4.3.0
			 *
			 * @param Student The student object.
			 * @param array The posted data.
			 */
			do_action( 'wpcw_checkout_update_student', $student, $data );

			$student->save();
		}
	}

	/**
	 * Process Order without Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param int $order_id The order id.
	 */
	protected function process_order_without_payment( $order_id ) {
		$order = new Order( $order_id );
		$order->payment_complete();

		wpcw_empty_cart();

		if ( wpcw_is_ajax() ) {
			wp_send_json_success( array(
				'redirect' => apply_filters( 'wpcw_checkout_no_payment_needed_redirect', $order->get_order_received_url(), $order ),
			) );
		} else {
			wp_safe_redirect( apply_filters( 'wpcw_checkout_no_payment_needed_redirect', $order->get_order_received_url(), $order ) );
			exit;
		}
	}

	/**
	 * Process Order Payment.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $order_id The order id.
	 * @param string $payment_method The current payment method.
	 */
	protected function process_order_payment( $order_id, $payment_method ) {
		$available_gateways = $this->get_available_gateways();

		if ( ! isset( $available_gateways[ $payment_method ] ) ) {
			return;
		}

		wpcw()->session->set( 'order_awaiting_payment', $order_id );

		if ( ! method_exists( $available_gateways[ $payment_method ], 'process_payment' ) ) {
			return;
		}

		$result = $available_gateways[ $payment_method ]->process_payment( $order_id );

		if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
			$result = apply_filters( 'wpcw_checkout_payment_success', $result, $order_id );

			if ( ! wpcw_is_ajax() ) {
				wp_redirect( $result['redirect'] );
				exit;
			}

			wp_send_json_success( $result );
		}
	}

	/**
	 * Create Order.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The order data.
	 *
	 * @return int|WP_Error The order id or WP_Error on failure.
	 */
	protected function create_order( $data ) {
		if ( $order_id = apply_filters( 'wpcw_checkout_create_order', null, $this ) ) {
			return $order_id;
		}

		try {
			$order_id           = absint( wpcw()->session->get( 'order_awaiting_payment' ) );
			$cart_hash          = md5( json_encode( wpcw_clean( wpcw()->cart->get_cart_for_session() ) ) . wpcw()->cart->total() );
			$available_gateways = $this->get_available_gateways();

			/**
			 * If there is an order pending payment, we can resume it here so
			 * long as it has not changed. If the order has changed, i.e.
			 * different items or cost, create a new order. We use a hash to
			 * detect changes which is based on cart items + order total.
			 */
			if ( $order_id && ( $order = wpcw_get_order( $order_id ) ) && $order->has_cart_hash( $cart_hash ) && $order->has_order_status( array( 'pending', 'failed' ) ) ) {
				/**
				 * Action: Checkout - Resume Order.
				 *
				 * @since 4.3.0
				 *
				 * @param int $order_id The order id.
				 */
				do_action( 'wpcw_resume_order', $order_id );

				// Delete the order items, we will re-add them later in the routine.
				$order->delete_order_items( $order->get_order_items() );
			} else {
				$order = new Order();
				$order->create( array( 'created_via' => 'checkout' ) );
			}

			// Set Gateway Properties.
			$gateway = isset( $available_gateways[ $data['payment_method'] ] ) ? $available_gateways[ $data['payment_method'] ] : $data['payment_method'];
			if ( $gateway instanceof Gateway ) {
				$order->set_prop( 'payment_method', $gateway->get_slug() );
				$order->set_prop( 'payment_method_title', $gateway->get_title() );
			} else {
				$order->set_prop( 'payment_method', $gateway );
				$order->set_prop( 'payment_method_title', '' );
			}

			// Set Properties.
			$order->set_prop( 'student_id', get_current_user_id() );
			$order->set_prop( 'student_ip_address', wpcw_user_ip_address() );
			$order->set_prop( 'student_user_agent', wpcw_user_agent() );
			$order->set_prop( 'student_email', esc_attr( $data['email'] ) );
			$order->set_prop( 'student_first_name', esc_attr( $data['first_name'] ) );
			$order->set_prop( 'student_last_name', esc_attr( $data['last_name'] ) );
			$order->set_prop( 'billing_address_1', ! empty( $data['billing_address_1'] ) ? $data['billing_address_1'] : '' );
			$order->set_prop( 'billing_address_2', ! empty( $data['billing_address_2'] ) ? $data['billing_address_2'] : '' );
			$order->set_prop( 'billing_city', ! empty( $data['billing_city'] ) ? $data['billing_city'] : '' );
			$order->set_prop( 'billing_postcode', ! empty( $data['billing_postcode'] ) ? $data['billing_postcode'] : '' );
			$order->set_prop( 'billing_country', ! empty( $data['billing_country'] ) ? $data['billing_country'] : '' );
			$order->set_prop( 'billing_state', ! empty( $data['billing_state'] ) ? $data['billing_state'] : '' );
			$order->set_prop( 'cart_hash', $cart_hash );
			$order->set_prop( 'currency', wpcw_get_currency() );
			$order->set_prop( 'discounts', wpcw()->cart->get_discount_total() );
			$order->set_prop( 'subtotal', wpcw()->cart->get_subtotal() );
			$order->set_prop( 'tax', wpcw()->cart->get_tax() );
			$order->set_prop( 'total', wpcw()->cart->get_total() );

			// Set Order Items.
			$order_items = array();
			foreach ( wpcw()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( ! isset( $cart_item['id'] ) || empty( $cart_item['data'] ) ) {
					continue;
				}

				// Set Course.
				$course = new Course( $cart_item['data'] );

				// Set Details.
				$price        = $course->get_payments_price();
				$quantity     = isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
				$discount     = wpcw()->cart->get_course_discount_amount( $course );
				$discount_tax = wpcw_calculate_tax_amount( $discount );
				$subtotal     = floatval( $price ) * $quantity;
				$subtotal_tax = wpcw_calculate_tax_amount( $subtotal );
				$tax          = $subtotal_tax - $discount_tax;
				$total        = ( $subtotal - $discount ) + $tax;

				if ( $total < 0 ) {
					$total = 0.00;
				}

				$order_items[] = array(
					'id'               => absint( $course->get_course_id() ),
					'title'            => esc_attr( $course->get_course_title() ),
					'amount'           => wpcw_round( $price ),
					'qty'              => absint( $quantity ),
					'discount'         => wpcw_round( $discount ),
					'discount_tax'     => wpcw_round( $discount_tax ),
					'subtotal'         => wpcw_round( $subtotal ),
					'subtotal_tax'     => wpcw_round( $subtotal_tax ),
					'tax'              => wpcw_round( $tax ),
					'total'            => wpcw_round( $total ),
					'use_installments' => $course->charge_installments(),
					'is_recurring'     => ( 'subscription' === $course->get_payments_type() ) ? true : false,
				);
			}

			// Insert Order Items.
			if ( ! empty( $order_items ) ) {
				$order->insert_order_items( $order_items );
			}

			// Add Applied Coupons.
			$applied_coupons = wpcw()->cart->get_applied_coupons();
			if ( ! empty( $applied_coupons ) ) {
				$order->add_applied_coupons( $applied_coupons );
			}

			/**
			 * Action: Checkout - Create Order
			 *
			 * @since 4.3.0
			 *
			 * @param Order The order object.
			 * @param array $data The checkout data array.
			 */
			do_action( 'wpcw_checkout_create_order', $order, $data );

			// Save the order, and return the id.
			return $order->save();
		} catch ( Exception $e ) {
			return new WP_Error( 'checkout-error', $e->getMessage() );
		}
	}
}
