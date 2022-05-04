<?php
/**
 * WP Courseware Coupons Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.5.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Filters for formatting.
 *
 * @since 4.5.0
 */
add_filter( 'wpcw_coupon_code', 'html_entity_decode' );
add_filter( 'wpcw_coupon_code', 'sanitize_text_field' );
add_filter( 'wpcw_coupon_code', 'wpcw_strtolower' );

/**
 * Get Coupon.
 *
 * @since 4.5.0
 *
 * @param int|bool $coupon_id The Coupon Id.
 *
 * @return \WPCW\Models\Coupon|bool An coupon object or false.
 */
function wpcw_get_coupon( $coupon_id = false ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_coupon should not be called the coupons object is setup.', '4.5.0' );

		return false;
	}

	return new \WPCW\Models\Coupon( $coupon_id );
}

/**
 * Get Coupons.
 *
 * @since 4.5.0
 *
 * @param array $args The coupons query args.
 *
 * @return array An array of Coupon objects.
 */
function wpcw_get_coupons( $args = array() ) {
	$defaults = array();

	$args = wp_parse_args( $args, $defaults );

	return wpcw()->coupons->get_coupons( $args );
}

/**
 * Are Coupons Enabled?
 *
 * @since 4.5.0
 *
 * @return bool
 */
function wpcw_coupons_enabled() {
	return apply_filters( 'wpcw_coupons_enabled', 'yes' === wpcw_get_setting( 'enable_coupons' ) ? true : false );
}

/**
 * Get Coupon Types.
 *
 * @since 4.5.0
 *
 * @param bool $include_desc Include description of each coupon.
 *
 * @return array The array of global coupon types.
 */
function wpcw_get_coupon_types( $include_desc = false ) {
	return wpcw()->coupons->get_types( $include_desc );
}

/**
 * Get Coupon Type.
 *
 * @since 4.5.0
 *
 * @param string $type The coupon type.
 *
 * @return string The coupon type label.
 */
function wpcw_get_coupon_type( $type = '' ) {
	$types = wpcw_get_coupon_types();
	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

/**
 * Get Coupon Id by Code.
 *
 * @since 4.5.0
 *
 * @param string $code The coupon code.
 * @param int    $exclude Exclude a certain id.
 *
 * @return int The coupon code id.
 */
function wpcw_get_coupon_id_by_code( $code, $exclude = 0 ) {
	$ids = wpcw()->coupons->get_coupon_ids_by_code( $code );
	$ids = array_diff( array_filter( array_map( 'absint', (array) $ids ) ), array( $exclude ) );

	return apply_filters( 'wpcw_coupon_get_coupon_id_by_code', absint( current( $ids ) ), $code, $exclude );
}

/**
 * Get Coupon by Code.
 *
 * @since 4.5.0
 *
 * @param $code
 */
function wpcw_get_coupon_by_code( $code ) {
	return wpcw()->coupons->get_coupon_by_code( $code );
}
