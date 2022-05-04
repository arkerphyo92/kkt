<?php
/**
 * WP Courseware Conditional Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * What type of request is this?
 *
 * @since 4.3.0
 *
 * @param string $type The string request type.
 *
 * @return bool True on success, false on failure.
 */
function wpcw_is_request( $type ) {
	switch ( $type ) {
		case 'admin':
			return is_admin();
		case 'ajax':
			return defined( 'DOING_AJAX' ) || defined( 'WPCW_DOING_AJAX' );
		case 'cron':
			return defined( 'DOING_CRON' );
		case 'frontend':
			return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
	}
}

/**
 * Is_ajax - Returns true when the page is loaded via ajax.
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_ajax() {
	return defined( 'DOING_AJAX' );
}

/**
 * Is Doing Cron?
 *
 * Returns true when a cron job is running.
 *
 * @since 4.3.0
 *
 * @return boolean True if doing a cron job.
 */
function wpcw_is_doing_cron() {
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return true;
	} elseif ( defined( 'DOING_CRON' ) && ( true === DOING_CRON ) ) {
		return true;
	}

	return false;
}

/**
 * Check if an endpoint is showing.
 *
 * @since 4.3.0
 *
 * @param string $endpoint Which endpoint to check, or any.
 *
 * @return bool True on successful check, false on failure.
 */
function wpcw_is_endpoint_url( $endpoint = false ) {
	global $wp;

	$endpoints = wpcw()->query->get_query_vars();

	if ( false !== $endpoint ) {
		if ( ! isset( $endpoints[ $endpoint ] ) ) {
			return false;
		} else {
			$endpoint_var = $endpoints[ $endpoint ];
		}

		return isset( $wp->query_vars[ $endpoint_var ] );
	}

	foreach ( $endpoints as $key => $value ) {
		if ( isset( $wp->query_vars[ $key ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Is Valid url?
 *
 * Simple check for validating a URL, it must start with http:// or https://.
 * and pass FILTER_VALIDATE_URL validation.
 *
 * @since 4.3.0
 *
 * @param string $url The url to check.
 *
 * @return bool True on success, False on failure.
 */
function wpcw_is_valid_url( $url ) {
	// Must start with http:// or https://.
	if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}

	// Must pass validation.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return true;
}

/**
 * Is site secured with https.
 *
 * Check if the home URL is https. If it is, we don't need to do things such as 'force ssl'.
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_site_https() {
	return false !== strstr( get_option( 'home' ), 'https:' );
}

/**
 * Is the Checkout Page secured with https.
 *
 * Look at options, WP HTTPS plugin, or the permalink itself.
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_checkout_https() {
	return wpcw_is_site_https() || 'yes' === wpcw()->settings->get_setting( 'force_ssl' ) || class_exists( 'WordPressHTTPS' ) || strstr( wpcw_get_page_permalink( 'checkout' ), 'https:' );
}

/**
 * Check the content for a specific shortcode.
 *
 * @since 4.3.0
 *
 * @param string $tag The shortcode tag to check.
 *
 * @return bool True on success, False on failure.
 */
function wpcw_post_content_has_shortcode( $tag = '' ) {
	global $post;
	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

/**
 * Is Checkout Page?
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_checkout() {
	$checkout_page_id = wpcw_get_page_id( 'checkout' );
	return ( ! empty( $checkout_page_id ) && is_page( $checkout_page_id ) ) || wpcw_post_content_has_shortcode( 'wpcw_checkout' ) || apply_filters( 'wpcw_is_checkout', false ) || defined( 'WPCW_CHECKOUT' );
}

/**
 * Is Account Page?
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_account_page() {
	$account_page_id = wpcw_get_page_id( 'account' );
	return ( ! empty( $account_page_id ) && is_page( $account_page_id ) ) || wpcw_post_content_has_shortcode( 'wpcw_account' ) || apply_filters( 'wpcw_is_account_page', false );
}

/**
 * Is Order Received Page?
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_order_received_page() {
	global $wp;
	$order_recieved_page = wpcw_get_page_id( 'order-received' );
	return ( ! empty( $order_recieved_page ) && is_page( $order_recieved_page ) ) || apply_filters( 'wpcw_is_order_received_page', false );
}

/**
 * Is Order Failed Page?
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_is_order_failed_page() {
	global $wp;
	$order_failed_page = wpcw_get_page_id( 'order-failed' );
	return ( ! empty( $order_failed_page ) && is_page( $order_failed_page ) ) || apply_filters( 'wpcw_is_order_failed_page', false );
}

/**
 * Is Orders Page?
 *
 * @since 4.4.3
 *
 * @return bool
 */
function wpcw_is_orders_page() {
	global $wp;
	$orders_page = wpcw_get_setting( 'student_orders_endpoint', 'orders' );
	return ( wpcw_is_account_page() && isset( $wp->query_vars[ $orders_page ] ) );
}

/**
 * Is view order page?
 *
 * @since 4.4.3
 *
 * @return bool
 */
function wpcw_is_view_order_page() {
	global $wp;
	$view_order_page = wpcw_get_setting( 'student_view_order_endpoint', 'view-order' );
	return ( wpcw_is_account_page() && isset( $wp->query_vars[ $view_order_page ] ) );
}

/**
 * Is Subscriptions Page?
 *
 * @since 4.4.3
 *
 * @return bool
 */
function wpcw_is_subscriptions_page() {
	global $wp;
	$subscriptions_page = wpcw_get_setting( 'student_subscriptions_endpoint', 'subscriptions' );
	return ( wpcw_is_account_page() && isset( $wp->query_vars[ $subscriptions_page ] ) );
}

/**
 * Is view subscription page?
 *
 * @since 4.4.3
 *
 * @return bool
 */
function wpcw_is_view_subscription_page() {
	global $wp;
	$view_subscription_page = wpcw_get_setting( 'student_view_order_endpoint', 'view-subscription' );
	return ( wpcw_is_account_page() && isset( $wp->query_vars[ $view_subscription_page ] ) );
}

/**
 * Is Edit Account Page?
 *
 * @since 4.4.3
 *
 * @return bool
 */
function wpcw_is_edit_account_page() {
	global $wp;
	$edit_account = wpcw_get_setting( 'student_edit_account_endpoint', 'edit-account' );
	return ( wpcw_is_account_page() && isset( $wp->query_vars[ $edit_account ] ) );
}

/**
 * Is Post Type Query?
 *
 * @since 4.4.0
 *
 * @param WP_Query $wp_query The wp_query object.
 * @param string $post_type The post type slug.
 *
 * @return bool $is_post_type_query True if is the post type query.
 */
function wpcw_is_post_type_query( $wp_query, $post_type = 'wpcw_course' ) {
	$is_post_type_query = false;

	if ( ! empty( $wp_query->query['post_type'] ) ) {
		if ( is_string( $wp_query->query['post_type'] ) && $post_type === $wp_query->query['post_type'] ) {
			$is_post_type_query = true;
		} elseif ( is_array( $wp_query->query['post_type'] ) && in_array( $post_type, $wp_query->query['post_type'] ) ) {
			$is_post_type_query = true;
		}
	}

	return $is_post_type_query;
}

/**
 * Is Post Type Taxonomy Query?
 *
 * @since 4.4.0
 *
 * @param WP_Query $wp_query The wp_query object.
 * @param string $post_type The post type slug.
 *
 * @return bool $is_post_type_query True if is the post type query.
 */
function wpcw_is_taxonomy_query( $wp_query, $taxonomy = 'course_category' ) {
	$is_taxonomy_query = false;

	if ( ! empty( $wp_query->query[ $taxonomy ] ) ) {
		$is_taxonomy_query = true;
	}

	return $is_taxonomy_query;
}

/**
 * Is Course Single?
 *
 * @since 4.4.0
 *
 * @return bool True
 */
function wpcw_is_course_single() {
	return is_singular( array( wpcw()->courses->post_type_slug ) );
}

/**
 * Is Course Archive?
 *
 * @since 4.4.0
 *
 * @return bool True
 */
function wpcw_is_course_archive() {
	return is_post_type_archive( array( wpcw()->courses->post_type_slug ) );
}

/**
 * Is Course Taxonomy?
 *
 * @since 4.4.0
 *
 * @return bool True if is a course taxonomy.
 */
function wpcw_is_course_taxonomy() {
	return is_tax( get_object_taxonomies( wpcw()->courses->post_type_slug ) );
}

/**
 * Is Course Category?
 *
 * @since 4.4.0
 *
 * @param string $term The course category term.
 *
 * @return bool True if is a course category.
 */
function wpcw_is_course_category( $term = '' ) {
	return is_tax( wpcw()->courses->taxonomy_category_slug, $term );
}

/**
 * Is Course Tag?
 *
 * @since 4.4.0
 *
 * @param string $term The course tag term.
 *
 * @return bool True if is a course tag.
 */
function wpcw_is_course_tag( $term = '' ) {
	return is_tax( wpcw()->courses->taxonomy_tag_slug, $term );
}

/**
 * Is Ecommerce Enabled?
 *
 * @since 4.5.1
 *
 * @return bool True if enabled, false otherwise.
 */
function wpcw_is_ecommerce_enabled() {
	return wpcw_are_payment_gateways_available();
}

/**
 * Is Tracking Allowed?
 *
 * @since 4.4.0
 *
 * @return bool True if tracking is enabled, false otherwise.
 */
function wpcw_is_tracking_allowed() {
	return (bool) wpcw()->tracker->is_tracking_allowed();
}
