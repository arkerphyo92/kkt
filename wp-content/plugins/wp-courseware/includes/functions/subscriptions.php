<?php
/**
 * WP Courseware Subscription Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Subscription.
 *
 * @since 4.3.0
 *
 * @param int|bool $subscription_id The Subscription Id.
 *
 * @return \WPCW\Models\Subscription|bool An subscription object or false.
 */
function wpcw_get_subscription( $subscription_id = false ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_subscription should not be called the subscription object is setup.', '4.3.0' );

		return false;
	}

	return new \WPCW\Models\Subscription( $subscription_id );
}

/**
 * Get Subscriptions.
 *
 * @since 4.3.0
 *
 * @param array $args The subscription query args.
 */
function wpcw_get_subscriptions( $args = array() ) {
	return wpcw()->subscriptions->get_subscriptions( $args );
}

/**
 * Get Test Subscription.
 *
 * @since 4.3.0
 *
 * @return \WPCW\Models\Subscription $test_subscription The test subscription object.
 */
function wpcw_get_test_subscription() {
	// Initiate a Model
	$test_subscription = new \WPCW\Models\Subscription();

	// Order Id.
	$subscription_id = rand();
	$amount          = 10.00;
	$subtotal        = $amount;
	$tax             = wpcw_taxes_enabled() ? wpcw_round( $amount * wpcw_get_tax_percentage() ) : 0;
	$total           = wpcw_round( $amount + $tax );

	// Test Objects.
	$test_student = wpcw_get_test_student();
	$test_order   = wpcw_get_test_order();
	$test_course  = wpcw_get_test_course();

	// Test Data
	$test_data = array(
		'id'               => $subscription_id,
		'student_id'       => $test_student->get_ID(),
		'student'          => $test_student,
		'order_id'         => $test_order->get_id(),
		'order'            => $test_order,
		'course_id'        => $test_course->get_id(),
		'course'           => $test_course,
		'period'           => 'month',
		'initial_amount'   => $amount,
		'recurring_amount' => $amount,
		'bill_times'       => '',
		'transaction_id'   => 'ch_Ck4LpwPdbAi5Ls',
		'method'           => 'stripe',
		'created'          => date( 'Y-m-d H:i:s' ),
		'expiration'       => date( 'Y-m-d H:i:s', strtotime( '+1 month' ) ),
		'status'           => 'active',
		'profile_id'       => 'sub_Ck4LpwPdbAi5Ls',
	);

	// Set Data.
	$test_subscription->set_data( $test_data );

	return $test_subscription;
}

/**
 * Get Subscription by Profile Id.
 *
 * @since 4.3.0
 *
 * @param string $profile_id The profile Id.
 *
 * @return \WPCW\Models\Subscription|bool An subscription object or false.
 */
function wpcw_get_subscription_by_profile_id( $profile_id = '' ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_subscription_by_profile_id should not be called the order subscription is setup.', '4.3.0' );

		return false;
	}

	return wpcw()->subscriptions->get_subscription_by_profile_id( $profile_id );
}

/**
 * Get the nice name for a subscription status.
 *
 * @since 4.3.0
 *
 * @param string $status The subscription status.
 *
 * @return string
 */
function wpcw_get_subscription_status_name( $status ) {
	$statuses = wpcw()->subscriptions->get_statuses();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}

/**
 * Get the nice name for a subscription status.
 *
 * @since 4.3.0
 *
 * @param string $status The subscription status.
 *
 * @return string
 */
function wpcw_get_subscription_period_name( $period ) {
	$periods = wpcw()->subscriptions->get_periods();

	return isset( $periods[ $period ] ) ? $periods[ $period ] : $period;
}