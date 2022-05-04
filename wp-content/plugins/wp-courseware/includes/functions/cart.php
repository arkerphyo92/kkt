<?php
/**
 * WP Courseware Cart Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add to Cart Url.
 *
 * @since 4.3.0
 *
 * @param int  $course_id The course id. Required.
 * @param bool $installments Use installments when adding to the cart.
 *
 * @return string The add to cart url.
 */
function wpcw_add_to_cart_url( $course_id, $installments = false ) {
	$url = site_url( '/wpcw-cart-add/' . absint( $course_id ) );

	if ( $installments ) {
		$url = add_query_arg( array( 'installments' => true ), $url );
	}

	return esc_url( apply_filters( 'wpcw_add_to_cart_url', $url, $course_id ) );
}

/**
 * Add to Cart Link.
 *
 * @since 4.3.0
 *
 * @param \WPCW\Models\Course The course object.
 * @param array $args The array of arguments to pass to the button
 * @param bool  $display_message To display the already enrolled message.
 * @param bool  $force Force the display of the button if they are logged in or not.
 *
 * @return string The cart link html.
 */
function wpcw_add_to_cart_link( $course, $args = array(), $display_message = true, $force = false ) {
	if ( ! $course instanceof \WPCW\Models\Course ) {
		return;
	}

	if ( ! $force && is_user_logged_in() && $course->can_user_access( get_current_user_id() ) ) {
		if ( $display_message ) {
			/* translators: %s is the Course Title */
			return apply_filters( 'wpcw_course_already_enrolled_text', sprintf( __( '<p>You have already been enrolled into <strong>%s</strong>.</p>', 'wp-courseware' ), $course->get_course_title() ) );
		} else {
			return;
		}
	}

	if ( ! $force && wpcw_is_course_in_cart( $course->get_course_id() ) ) {
		return wpcw_get_checkout_link();
	}

	// Link Defaults.
	$defaults = array(
		'text'         => esc_html__( 'Purchase', 'wp-courseware' ),
		'quantity'     => 1,
		'class'        => implode( ' ', array_filter( array(
			'wpcw-button wpcw-button-primary',
			'wpcw-add-to-cart',
			apply_filters( 'wpcw_cart_ajax_enabled', true ) ? 'wpcw-ajax-add-to-cart' : '',
		) ) ),
		'attributes'   => array(
			'data-course_id' => $course->get_course_id(),
			'rel'            => 'nofollow',
		),
		'installments' => false,
	);

	$args = apply_filters( 'wpcw_add_to_cart_args', wp_parse_args( $args, $defaults ), $course->get_course_id() );

	if ( $args['installments'] ) {
		$args['attributes']['data-installments'] = true;

		$args['class'] .= ' wpcw-add-to-cart-installments';

		/* translators: %1$s - Course price, %2$s - Course title */
		$text = ( 'Installments' === $args['text'] )
			? $course->get_installments_label()
			: esc_html( $args['text'] );
	} else {
		/* translators: %1$s - Course price, %2$s - Course title */
		$text = ( 'Purchase' === $args['text'] )
			? sprintf(
				__( '%1$s - Purchase %2$s', 'wp-courseware' ),
				esc_attr( $course->get_price_label() ),
				esc_html( $course->get_course_title() )
			)
			: esc_html( $args['text'] );
	}

	return apply_filters( 'wpcw_add_to_cart_link', sprintf(
		'<a href="%s" class="%s" %s>%s</a>',
		$args['installments'] ? esc_url( wpcw_add_to_cart_url( $course->get_course_id(), true ) ) : esc_url( wpcw_add_to_cart_url( $course->get_course_id() ) ),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'wpcw-button' ),
		isset( $args['attributes'] ) ? wpcw_implode_html_attributes( $args['attributes'] ) : '',
		esc_html( $text )
	), $course, $args );
}

/**
 * Remove from Cart Url.
 *
 * @since 4.3.0
 *
 * @param int|string $course_key The cart course key.
 *
 * @return string The remove from cart url.
 */
function wpcw_remove_from_cart_url( $course_key ) {
	return esc_url( apply_filters( 'wpcw_remove_from_cart_url', site_url( '/wpcw-cart-remove/' . absint( $course_key ) ), $course_key ) );
}

/**
 * Remove from cart link.
 *
 * @since 4.3.0
 *
 * @param string $key The course cart key.
 * @param array  $args The array of arguments to pass to the button
 *
 * @return string The cart link html.
 */
function wpcw_remove_from_cart_link( $course_key, $args = array() ) {
	$defaults = array(
		'icon'       => '<i class="wpcw-far wpcw-fa-times-circle"></i>',
		'text'       => '',
		'class'      => implode( ' ', array_filter( array(
			'wpcw-remove-from-cart',
			apply_filters( 'wpcw_cart_ajax_enabled', true ) ? 'wpcw-ajax-remove-from-cart' : '',
		) ) ),
		'attributes' => array(
			'rel' => 'nofollow',
		),
	);

	$args = apply_filters( 'wpcw_remove_from_cart_args', wp_parse_args( $args, $defaults ), $course_key );

	return apply_filters( 'wpcw_remove_from_cart_link', sprintf(
		'<a href="%s" class="%s" %s>%s</a>',
		esc_url( wpcw_remove_from_cart_url( $course_key ) ),
		esc_attr( isset( $args['class'] ) ? $args['class'] : 'wpcw-button' ),
		isset( $args['attributes'] ) ? wpcw_implode_html_attributes( $args['attributes'] ) : '',
		$args['icon']
	), $course_key, $args );
}

/**
 * Is Course in the cart already?
 *
 * @since 4.3.0
 *
 * @param int $course_id The course id.
 *
 * @return bool True if in the cart, false otherwise.
 */
function wpcw_is_course_in_cart( $course_id ) {
	return wpcw()->cart->is_course_in_cart( $course_id );
}

/**
 * Are Taxes Enabled?
 *
 * @since 4.3.0
 *
 * @return bool
 */
function wpcw_taxes_enabled() {
	return apply_filters( 'wpcw_cart_taxes_enabled', 'yes' === wpcw_get_setting( 'enable_taxes' ) ? true : false );
}

/**
 * Get Tax Rate.
 *
 * @since 4.3.0
 *
 * @return string The tax percentage.
 */
function wpcw_get_tax_percentage() {
	return apply_filters( 'wpcw_taxes_get_percentage', wpcw()->settings->get_setting( 'tax_percent' ) );
}

/**
 * Calculate Tax Amount.
 *
 * @since 4.3.0
 *
 * @param float $amount The original amount to calculate a tax cost.
 *
 * @return float $tax Taxed amount
 */
function wpcw_calculate_tax_amount( $amount = 0 ) {
	$tax     = 0.00;
	$percent = wpcw_get_tax_percentage();

	if ( wpcw_taxes_enabled() && $amount > 0 ) {
        if( is_numeric( $amount ) && is_numeric( $percent ) ) {
            $tax = $amount * ($percent / 100);
		} else {
			$percent = 0;
		}
	}

	return apply_filters( 'wpcw_tax_amount', $tax, $percent );
}

/**
 * Clears the cart session.
 *
 * @since 4.3.0
 */
function wpcw_empty_cart() {
	wpcw()->cart->empty_cart();
}

/**
 * Round discount.
 *
 * @since 4.5.0
 *
 * @param double $value Amount to round.
 * @param int    $precision DP to round.
 *
 * @return float
 */
function wpcw_cart_round_discount( $value, $precision ) {
	return wpcw_round_discount( $value, $precision );
}

/**
 * Get a coupon label.
 *
 * @since 4.5.0
 *
 * @param string|\WPCW\Models\Coupon $coupon Coupon data or code.
 * @param bool                       $echo Echo or return.
 *
 * @return string|void
 */
function wpcw_cart_coupon_label( $coupon, $echo = true ) {
	if ( is_string( $coupon ) ) {
		$coupon = wpcw_get_coupon_by_code( $coupon );
	}

	/* translators: %s: coupon code */
	$label = apply_filters( 'wpcw_cart_totals_coupon_label', sprintf( esc_html__( 'Coupon: %s', 'wp-courseware' ), '<span class="coupon-label-code">' . $coupon->get_code() . '</span>' ), $coupon );

	if ( $echo ) {
		echo $label; // WPCS: XSS ok.
	} else {
		return $label;
	}
}

/**
 * Get Cart Coupon Html.
 *
 * @since 4.5.0
 *
 * @param string|\WPCW\Models\Coupon $coupon The coupon object or code.
 *
 * @return void
 */
function wpcw_cart_coupon_html( $coupon ) {
	if ( is_string( $coupon ) ) {
		$coupon = wpcw_get_coupon_by_code( $coupon );
	}

	$discount_html = '';

	$amount        = wpcw()->cart->get_coupon_discount_amount( $coupon->get_code() );
	$discount_html = '- ' . wpcw_price( $amount );

	$discount_html = apply_filters( 'wpcw_coupon_discount_amount_html', $discount_html, $coupon );
	$coupon_html   = $discount_html . ' <a href="' . esc_url( add_query_arg( 'remove_coupon', rawurlencode( $coupon->get_code() ), wpcw_get_checkout_url() ) ) . '" class="wpcw-remove-coupon" data-coupon="' . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'wp-courseware' ) . '</a>';

	echo wp_kses( apply_filters( 'wpcw_cart_totals_coupon_html', $coupon_html, $coupon, $discount_html ), array_replace_recursive( wp_kses_allowed_html( 'post' ), array( 'a' => array( 'data-coupon' => true ) ) ) ); // phpcs:ignore PHPCompatibility.PHP.NewFunctions.array_replace_recursiveFound
}
