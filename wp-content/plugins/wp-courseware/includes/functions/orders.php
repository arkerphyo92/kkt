<?php
/**
 * WP Courseware Order Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Order.
 *
 * @since 4.3.0
 *
 * @param int|bool $order_id The Order Id.
 *
 * @return \WPCW\Models\Order|bool An order object or false.
 */
function wpcw_get_order( $order_id = false ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_order should not be called the order object is setup.', '4.3.0' );

		return false;
	}

	return new \WPCW\Models\Order( $order_id );
}

/**
 * Get Orders.
 *
 * @since 4.3.0
 *
 * @param array $args The orders query args.
 *
 * @return array An array of Order objects.
 */
function wpcw_get_orders( $args = array() ) {
	$defaults = array(
		'order_type' => 'order',
	);

	$args = wp_parse_args( $args, $defaults );

	return wpcw()->orders->get_orders( $args );
}

/**
 * Get Test Order.
 *
 * Used for test data.
 *
 * @since 4.3.0
 *
 * @return \WPCW\Models\Order $test_order The order object.
 */
function wpcw_get_test_order() {
	// Initiate a Model
	$test_order = new \WPCW\Models\Order();

	// Test Student.
	$test_student = wpcw_get_test_student();
	$test_course  = wpcw_get_test_course();

	// Order Id.
	$order_id = rand();
	$amount   = 10.00;
	$subtotal = $amount;
	$tax      = wpcw_taxes_enabled() ? wpcw_round( $amount * ( wpcw_get_tax_percentage() / 100 ) ) : 0;
	$total    = wpcw_round( $subtotal + $tax );

	// Test Data
	$test_data = array(
		'order_id'             => $order_id,
		'order_key'            => $test_order->create_order_key(),
		'student_id'           => $test_student->get_ID(),
		'student'              => $test_student,
		'student_email'        => 'wpcw.test.student@wpcwtest.com',
		'student_first_name'   => esc_html__( 'John', 'wp-courseware' ),
		'student_last_name'    => esc_html__( 'Smith', 'wp-courseware' ),
		'billing_address_1'    => esc_html__( '430 E. WP Courseware Street', 'wp-courseware' ),
		'billing_address_2'    => esc_html__( 'Suite 120', 'wp-courseware' ),
		'billing_city'         => esc_html__( 'Phoenix', 'wp-courseware' ),
		'billing_state'        => esc_html__( 'AZ', 'wp-courseware' ),
		'billing_postcode'     => esc_html__( '85001', 'wp-courseware' ),
		'billing_country'      => esc_html__( 'United States', 'wp-courseware' ),
		'order_type'           => 'order',
		'order_status'         => 'completed',
		'order_parent_id'      => 0,
		'payment_method'       => 'stripe',
		'payment_method_title' => esc_html__( 'Credit Card (Stripe)', 'wp-courseware' ),
		'discounts'            => 0,
		'subtotal'             => $subtotal,
		'tax'                  => $tax,
		'total'                => $total,
		'currency'             => 'USD',
		'transaction_id'       => 'ch_Ck4LpwPdbAi5Ls',
		'student_ip_address'   => '127.0.0.1',
		'student_user_agent'   => '',
		'created_via'          => 'system',
		'date_created'         => date( 'Y-m-d H:i:s' ),
		'date_completed'       => date( 'Y-m-d H:i:s' ),
		'date_paid'            => date( 'Y-m-d H:i:s' ),
		'cart_hash'            => '117db4732f14d05b29b1426e623703e1',
	);

	// Set Data.
	$test_order->set_data( $test_data );

	// Order Items
	$order_item_one = wpcw_get_test_order_item( $test_order, $test_course, ( $amount / 2 ), false );
	$order_item_two = wpcw_get_test_order_item( $test_order, $test_course, ( $amount / 2 ), true );
	$test_order->set_prop( 'order_items', array( $order_item_one, $order_item_two ) );

	return $test_order;
}

/**
 * Get Test Order Item.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Order $order The order object.
 * @param \WPCW\Models\Course $course The course object.
 * @param float $amount The amount minus tax.
 * @param bool $is_recurring Is the order item recurring?
 *
 * @return \WPCW\Models\Order_Item The order item object.
 */
function wpcw_get_test_order_item( $order, $course, $amount = 5.00, $is_recurring = false ) {
	$order_id = $order->get_id();
	$tax      = wpcw_taxes_enabled() ? wpcw_round( $amount * wpcw_get_tax_percentage() ) : 0;
	$total    = wpcw_round( $amount + $tax );

	$order_item_data = array(
		'order_item_id'    => $order_id + rand(),
		'order_id'         => $order->get_order_id(),
		'order'            => $order,
		'course_id'        => $course->get_course_id(),
		'course'           => $course,
		'order_item_title' => $course->get_course_title(),
		'order_item_index' => 0 + rand(),
		'type'             => 'course',
		'qty'              => 1,
		'amount'           => $amount,
		'subtotal'         => $amount,
		'discount'         => 0,
		'tax'              => $tax,
		'total'            => $total,
		'is_recurring'     => $is_recurring ? 1 : 0,
	);

	return new \WPCW\Models\Order_Item( $order_item_data );
}

/**
 * Get Order by Order Key.
 *
 * @since 4.3.0
 *
 * @param string $order_id The Order Id.
 *
 * @return \WPCW\Models\Order|bool An order object or false.
 */
function wpcw_get_order_by_order_key( $order_key = '' ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_order_by_order_key should not be called the order object is setup.', '4.3.0' );

		return false;
	}

	return wpcw()->orders->get_order_by_order_key( $order_key );
}

/**
 * Get the nice name for an order status.
 *
 * @since 4.3.0
 *
 * @param string $status The order status.
 *
 * @return string
 */
function wpcw_get_order_status_name( $status ) {
	$statuses = wpcw()->orders->get_order_statuses();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Get the nice name for an order type.
 *
 * @since 4.3.0
 *
 * @param string $status The order type.
 *
 * @return string
 */
function wpcw_get_order_type_name( $type ) {
	$types = wpcw()->orders->get_order_types();

	return isset( $types[ $type ] ) ? $types[ $type ] : ucfirst( $type );
}