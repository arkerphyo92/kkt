<?php
/**
 * WP Courseware Cart.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */
namespace WPCW\Core;

use WPCW\Models\Coupon;
use WPCW\Models\Course;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Cart.
 *
 * @since 4.6.3
 */
class Cart {

	/**
	 * @var array Cart contents.
	 * @since 4.3.0
	 */
	public $contents = array();

	/**
	 * @var array Cart Details.
	 * @since 4.3.0
	 */
	public $details = array();

	/**
	 * @var int Cart Quantity.
	 * @since 4.3.0
	 */
	public $quantity = 0;

	/**
	 * @var float Cart subtotal.
	 * @since 4.3.0
	 */
	public $subtotal = 0.00;

	/**
	 * @var float Cart Subtotal Tax.
	 * @since 4.5.2
	 */
	public $subtotal_tax = 0.00;

	/**
	 * @var float Cart Discount.
	 * @since 4.5.2
	 */
	public $discount = 0.00;

	/**
	 * @var float Cart Discount Tax.
	 * @since 4.5.2
	 */
	public $discount_tax = 0.00;

	/**
	 * @var float Flat Discount.
	 * @since 4.5.0
	 */
	public $flat_discount = 0.00;

	/**
	 * @var float Cart Tax.
	 * @since 4.3.0
	 */
	public $tax = 0.00;

	/**
	 * @var float Cart total.
	 * @since 4.3.0
	 */
	public $total = 0.00;

	/**
	 * @var bool Last item in the cart.
	 * @since 4.3.0
	 */
	public $last = false;

	/**
	 * @var array The array of the applied coupons.
	 * @since 4.5.0
	 */
	public $applied_coupons = array();

	/**
	 * @var array Coupon discount amounts.
	 * @since 4.5.0
	 */
	public $coupon_discount_totals = array();

	/**
	 * Load Cart.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Cart Setup
		add_action( 'wp_loaded', array( $this, 'setup_cart' ) );

		// Cart Cookies
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		add_action( 'wpcw_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'wpcw_empty_cart', array( $this, 'destroy_cart_session' ) );

		// Cart Display
		add_action( 'wpcw_checkout_cart', array( $this, 'display_cart' ) );
		add_action( 'wpcw_checkout_cart_empty', array( $this, 'display_empty_cart' ) );
		add_action( 'wpcw_cart_course_title_after', array( $this, 'display_course_subscription_renewal_message' ), 10, 2 );
		add_action( 'wpcw_cart_course_title_after', array( $this, 'display_course_installments_message' ), 11, 2 );
		add_action( 'wpcw_cart_course_title_after', array( $this, 'display_course_bundles_message' ), 12, 2 );

		// Cart Api
		add_action( 'wpcw_init', array( $this, 'add_to_cart_endpoints' ) );
		add_action( 'template_redirect', array( $this, 'process_add_to_cart_endpoints' ) );
		add_action( 'wpcw_ajax_api_events', array( $this, 'register_cart_ajax_events' ) );

		// Coupons
		add_action( 'wpcw_checkout_after_validation', array( $this, 'check_customer_coupons' ), 1 );
	}

	/**
	 * Setup Cart.
	 *
	 * @since 4.3.0
	 */
	public function setup_cart() {
		$this->get_session_contents();
		$this->get_contents();
		$this->get_session_applied_coupons();
		$this->get_quantity();
	}

	/**
	 * Get Cart Contents from Session.
	 *
	 * Checks the session to see if there
	 * are any cart items in the session.
	 *
	 * @since 4.3.0
	 */
	public function get_session_contents() {
		$this->contents = wpcw()->session->get( 'wpcw_cart' );

		/**
		 * Action: Get Cart Session Contents.
		 *
		 * @since 4.3.0
		 *
		 * @param Cart The cart object.
		 * @param array The cart contents.
		 */
		do_action( 'wpcw_cart_get_session_contents', $this, $this->contents );
	}

	/**
	 * Get Cart Contents.
	 *
	 * @since 4.3.0
	 *
	 * @return array The cart contents.
	 */
	public function get_contents() {
		if ( ! did_action( 'wpcw_cart_get_session_contents' ) ) {
			$this->get_session_contents();
		}

		$cart        = is_array( $this->contents ) && ! empty( $this->contents ) ? array_values( $this->contents ) : array();
		$cart_count  = count( $cart );
		$cart_length = $cart_count - 1;
		$count       = 0;

		// Populate the cart details.
		if ( ! empty( $cart ) ) {
			foreach ( $cart as $cart_key => $cart_course ) {
				if ( empty( $cart_course['id'] ) || empty( $cart_course['data'] ) ) {
					continue;
				}

				$course = new Course( $cart_course['data'] );

				if ( ! $course || ( $course && ! $course->is_purchasable() ) ) {
					unset( $cart[ $cart_key ] );
					continue;
				}

				if ( $cart_key >= $cart_length ) {
					$this->last = true;
				}

				$price        = $course->get_payments_price();
				$quantity     = apply_filters( 'wpcw_cart_course_quantity', 1, $course );
				$discount     = $this->get_course_discount_amount( $course );
				$discount_tax = wpcw_calculate_tax_amount( $discount );
				$subtotal     = floatval( $price ) * $quantity;
				$subtotal_tax = wpcw_calculate_tax_amount( $subtotal );
				$tax          = $subtotal_tax - $discount_tax;
				$total        = ( $subtotal - $discount ) + $tax;

				if ( $total < 0 ) {
					$total = 0.00;
				}

				$this->details[ $count ] = array(
					'id'           => absint( $course->get_course_id() ),
					'name'         => esc_html( $course->get_course_title() ),
					'title'        => esc_html( $course->get_course_title() ),
					'price'        => wpcw_round( $price ),
					'quantity'     => absint( $quantity ),
					'discount'     => wpcw_round( $discount ),
					'discount_tax' => wpcw_round( $discount_tax ),
					'subtotal'     => wpcw_round( $subtotal ),
					'subtotal_tax' => wpcw_round( $subtotal_tax ),
					'tax'          => wpcw_round( $tax ),
					'total'        => wpcw_round( $total ),
					'last'         => $this->last,
				);

				if ( $this->last ) {
					$this->last          = false;
					$this->flat_discount = 0.00;
				}

				$count ++;
			}
		}

		// Refresh contents after removing items.
		if ( count( $cart ) < $cart_count ) {
			$this->contents = $cart;
			$this->update_cart();
		}

		/**
		 * Filter: Cart Contents.
		 *
		 * @since 4.3.0
		 *
		 * @param array $cart The cart contents.
		 * @param array $details The cart contents details.
		 *
		 * @return array $cart The cart contents.
		 */
		$this->contents = apply_filters( 'wpcw_cart_contents', $cart, $this->details );

		/**
		 * Action: Cart Contents Loaded
		 *
		 * @since 4.3.0
		 *
		 * @param Cart The cart object.
		 * @param array $cart The cart contents.
		 * @param array $details The cart contents details.
		 */
		do_action( 'wpcw_cart_contents_loaded', $this, $cart, $this->details );

		return (array) $this->contents;
	}

	/**
	 * Get Cart Contents Details.
	 *
	 * @since 4.3.0
	 *
	 * @return array The cart content details.
	 */
	public function get_contents_details() {
		if ( empty( $this->details ) ) {
			$this->get_contents();
		}

		return apply_filters( 'wpcw_cart_contents_details', $this->details );
	}

	/**
	 * Get Cart Quantity.
	 *
	 * @since 4.3.0
	 *
	 * @return int The quantity of items in the cart.
	 */
	public function get_quantity() {
		if ( empty( $this->details ) ) {
			$this->get_contents();
		}

		$quantities     = wp_list_pluck( $this->details, 'quantity' );
		$this->quantity = absint( array_sum( $quantities ) );

		/**
		 * Filter: Get Cart Quantity.
		 *
		 * @since 4.3.0
		 *
		 * @param int   $quanitity The total of items in the cart.
		 * @param array $contents The contents of the cart.
		 */
		return apply_filters( 'wpcw_cart_get_quantity', $this->quantity, $this->contents );
	}

	/**
	 * Is Cart Empty?
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_empty() {
		return 0 === sizeof( $this->contents );
	}

	/**
	 * Maybe Set Cart Cookies.
	 *
	 * Will set cart cookies if needed and when possible.
	 *
	 * @since 4.3.0
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['wpcw_courses_in_cart'] ) ) {
				$this->set_cart_cookies( false );
			}
		}
	}

	/**
	 * Destroy Cart Session.
	 *
	 * @since 4.3.0
	 */
	public function destroy_cart_session() {
		wpcw()->session->set( 'wpcw_cart', null );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @since 4.3.0
	 *
	 * @return array $cart_session Contents of the cart session.
	 */
	public function get_cart_for_session() {
		$cart_session = array();

		foreach ( $this->get_cart() as $key => $values ) {
			$cart_session[ $key ] = $values;
			unset( $cart_session[ $key ]['data'] ); // Unset course object.
		}

		return $cart_session;
	}

	/**
	 * Set cart hash cookie and items in cart.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $set Should cookies be set (true) or unset.
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			wpcw_setcookie( 'wpcw_courses_in_cart', 1 );
			wpcw_setcookie( 'wpcw_cart_hash', md5( wp_json_encode( $this->get_cart_for_session() ) ) );
		} elseif ( isset( $_COOKIE['wpcw_courses_in_cart'] ) ) {
			wpcw_setcookie( 'wpcw_courses_in_cart', 0, time() - HOUR_IN_SECONDS );
			wpcw_setcookie( 'wpcw_cart_hash', '', time() - HOUR_IN_SECONDS );
		}

		do_action( 'wpcw_set_cart_cookies', $set );
	}

	/**
	 * Add to Cart
	 *
	 * @since 4.3.0
	 *
	 * @param int   $course_id The course id.
	 * @param array $options Any options that need to be added.
	 * @param bool  $installments Is this course using installments? Default is false.
	 *
	 * @return int The position in the cart object.
	 */
	public function add_to_cart( $course_id, $installments = false ) {
		try {
			if ( $this->is_course_in_cart( $course_id ) ) {
				throw new \Exception( esc_html__( 'You cannot purchase more than one of the same course.', 'wp-courseware' ) );
			}

			$course = new Course( absint( $course_id ) );

			if ( ! $course->get_course_id() ) {
				throw new \Exception( esc_html__( 'Sorry, this course does not exist.', 'wp-courseware' ) );
			}

			if ( ! $course->is_purchasable() ) {
				throw new \Exception( __( 'Sorry, this course cannot be purchased.', 'wp-courseware' ) );
			}

			// Check for an installment.
			if ( $installments ) {
				$course->set_prop( 'charge_installments', true );
			}

			$course_quantity = 1;
			$course_data     = $course->to_array();

			$this->contents[] = array(
				'id'       => $course_id,
				'data'     => $course_data,
				'quantity' => $course_quantity,
			);

			$this->update_cart();

			/**
			 * Action: Add to Cart.
			 *
			 * @since 4.3.0
			 *
			 * @param int   $course_id The course id.
			 * @param array $course_data The course data.
			 * @param Course The course object.
			 */
			do_action( 'wpcw_add_to_cart', $course_id, $course_data, $course );

			if ( 'yes' === wpcw_get_setting( 'redirect_to_checkout' ) ) {
				/* translators: %s - Course Title */
				wpcw_add_notice( sprintf( __( '%s succesfully added to your cart.', 'wp-courseware' ), esc_html( $course->get_course_title() ) ), 'success' );
			}

			return count( $this->contents ) - 1;
		} catch ( \Exception $e ) {
			if ( $e->getMessage() ) {
				wpcw_add_notice( $e->getMessage(), 'error' );
			}

			return false;
		}
	}

	/**
	 * Remove Course from the Cart.
	 *
	 * @since 4.3.0
	 *
	 * @param int $key The cart key.
	 */
	public function remove_from_cart( $key ) {
		if ( empty( $this->contents ) ) {
			$this->get_contents();
		}

		$cart = $this->contents;

		if ( isset( $cart[ $key ] ) ) {
			if ( empty( $cart ) ) {
				return true;
			}

			unset( $cart[ $key ] );

			$this->contents = $cart;
			$this->update_cart();

			return true;
		}

		return false;
	}

	/**
	 * Empty Cart.
	 *
	 * @since 4.3.0
	 */
	public function empty_cart() {
		$this->contents = array();

		// Set Discounts.
		$this->set_coupon_discount_totals( array() );
		$this->set_applied_coupons( array() );

		// Session Contents.
		wpcw()->session->set( 'wpcw_cart', null );
		wpcw()->session->set( 'wpcw_applied_coupons', null );

		/**
		 * Action: Empty Cart.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_empty_cart', $this );
	}

	/**
	 * Update Cart.
	 *
	 * @since 4.3.0
	 */
	public function update_cart() {
		wpcw()->session->set( 'wpcw_cart', $this->contents );

		/**
		 * Action: Cart Updated.
		 *
		 * @since 4.5.0
		 *
		 * @param array The cart contents.
		 * @param Cart The cart controller object.
		 */
		do_action( 'wpcw_cart_updated', $this->contents, $this );
	}

	/**
	 * Is Course in Cart?
	 *
	 * @since 4.3.0
	 *
	 * @param int   $course_id The course id.
	 * @param array $options The cart item options.
	 *
	 * @return bool
	 */
	public function is_course_in_cart( $course_id = 0, $options = array() ) {
		if ( empty( $this->contents ) ) {
			$this->get_contents();
		}

		$cart    = $this->contents;
		$in_cart = false;

		if ( is_array( $cart ) ) {
			foreach ( $cart as $course ) {
				if ( absint( $course_id ) === absint( $course['id'] ) ) {
					$in_cart = true;
				}
			}
		}

		return (bool) apply_filters( 'wpcw_course_in_cart', $in_cart, $course_id, $options );
	}

	/**
	 * Get Course Position in Cart.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $course_id The course id.
	 * @param array $options The cart item options.
	 *
	 * @return bool|int|string
	 */
	public function get_course_position_in_cart( $course_id = 0, $options = array() ) {
		if ( empty( $this->contents ) ) {
			$this->get_contents();
		}

		$cart = $this->contents;

		if ( ! is_array( $cart ) || empty( $cart ) ) {
			return false;
		}

		foreach ( $cart as $cart_position => $cart_course ) {
			if ( absint( $course_id ) === absint( $cart_course['id'] ) ) {
				return $cart_position;
			}
		}

		return false;
	}

	/**
	 * Get Course Discount Amount.
	 *
	 * @since 4.5.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return float $discount_amount The discounted amount.
	 */
	public function get_course_discount_amount( $course ) {
		// Default Discount Amount.
		$discount_amount = 0.00;

		// Check for Course Id.
		if ( ! $course->get_id() ) {
			return $discount_amount;
		}

		// Variables Neede.
		$price = $course->get_payments_price();

		// Discounted Price.
		$discounted_price = $price;

		// Get Coupons.
		$coupons = $this->get_coupons();

		// Calculate discounts per course.
		if ( ! empty( $coupons ) ) {
			/** @var Coupon $coupon */
			foreach ( $coupons as $coupon ) {
				if ( ! $coupon instanceof Coupon || ! $coupon->get_id() ) {
					continue;
				}

				// Discount Total.
				$discount_total = 0.00;

				// Course Ids.
				$course_ids         = $coupon->get_course_ids();
				$exclude_course_ids = $coupon->get_exclude_course_ids();

				if ( ! empty( $course_ids ) ) {
					foreach ( $course_ids as $course_id ) {
						if ( $course_id === $course->get_id() && ! in_array( $course->get_id(), $exclude_course_ids ) ) {
							$discounted_price -= $price - $coupon->get_discount_amount( $price );
							$discount_total   += $price - $coupon->get_discount_amount( $price );
						}
					}
				} else {
					if ( ! in_array( $course->get_id(), $exclude_course_ids ) ) {
						if ( 'fixed_cart' === $coupon->get_type() ) {
							/**
							 * In order to correctly record individual course amounts, global flat rate discounts
							 * are distributed across all cart items. The discount amount is divided by the number
							 * of courses in the cart and then a portion is evenly applied to each course.
							 */
							$courses_subtotal = 0.00;
							$cart_courses     = $this->contents;
							foreach ( $cart_courses as $cart_course ) {
								if ( ! in_array( $cart_course['id'], $exclude_course_ids ) ) {
									$course_object    = new Course( $cart_course['data'] );
									$course_price     = $course_object->get_payments_price();
									$courses_subtotal += $course_price * 1;
								}
							}

							$subtotal_percent = ( ( $price * 1 ) / $courses_subtotal );

							$code_amount      = $coupon->get_amount();
							$discount_amt     = $code_amount * $subtotal_percent;
							$discounted_price -= $discount_amt;

							$this->flat_discount += wpcw_round( $discount_amt );

							if ( $this->last && ( $this->flat_discount < $code_amount ) ) {
								$adjustment       = $code_amount - $this->flat_discount;
								$discounted_price -= $adjustment;
								$discount_total   += wpcw_round( $discount_amt + $adjustment );
							} else {
								$discount_total += wpcw_round( $discount_amt );
							}
						} else {
							$discounted_price -= $price - $coupon->get_discount_amount( $price );
							$discount_total   += $price - $coupon->get_discount_amount( $price );
						}
					}
				}

				// Check for zero values.
				$discounted_price = ( $discounted_price < 0 ) ? 0 : $discounted_price;
				$discount_total   = ( $discount_total < 0 ) ? 0 : $discount_total;

				// Add Discount to discount totals.
				$this->coupon_discount_totals[ $course->get_id() ][ $coupon->get_code() ] = wpcw_round( $discount_total );
			}

			/**
			 * Filter: Cart Course Discount Amount.
			 *
			 * @since 4.5.0
			 *
			 * @param string $discount_amount The discount amount.
			 */
			$discount_amount = wpcw_round( $price - apply_filters( 'wpcw_cart_course_discount_amount', $discounted_price, $coupons, $course, $price ) );
		}

		return $discount_amount;
	}

	/**
	 * Get Cart Course Object.
	 *
	 * @since 4.3.0
	 *
	 * @param array $cart_course The cart course data.
	 *
	 * @return Course The course model object.
	 */
	public function get_cart_course_object( $cart_course ) {
		$course_data = isset( $cart_course['data'] ) ? $cart_course['data'] : array();

		if ( empty( $course_data ) ) {
			return false;
		}

		return new Course( $course_data );
	}

	/**
	 * Get Courses Subtotal.
	 *
	 * @since 4.3.0
	 *
	 * @param array $courses The array of items in the cart.
	 *
	 * @return float|mixed|void
	 */
	public function get_courses_subtotal( $courses ) {
		$subtotal = 0.00;

		if ( is_array( $courses ) && ! empty( $courses ) ) {
			$prices = wp_list_pluck( $courses, 'subtotal' );

			if ( is_array( $prices ) ) {
				$subtotal = array_sum( $prices );
			} else {
				$subtotal = 0.00;
			}

			if ( $subtotal < 0 ) {
				$subtotal = 0.00;
			}
		}

		/**
		 * Filter: Get Cart Courses Subtotal
		 *
		 * @since 4.3.0
		 *
		 * @param double|float|int $subtotal The cart courses subtotal.
		 *
		 * @return double|float|int $subtotal The modified cart courses subtotal.
		 */
		$this->subtotal = apply_filters( 'wpcw_cart_get_courses_subtotal', $subtotal );

		return $this->subtotal;
	}

	/**
	 * Get Cart Subtotal.
	 *
	 * @since 4.3.0
	 *
	 * @return float Total before taxes.
	 */
	public function get_subtotal() {
		$courses  = $this->get_contents_details();
		$subtotal = $this->get_courses_subtotal( $courses );

		/**
		 * Filter: Get Cart Subtotal
		 *
		 * @since 4.3.0
		 *
		 * @param double|float|int $subtotal The cart subtotal.
		 *
		 * @return double|float|int $subtotal The cart subtotal.
		 */
		return apply_filters( 'wpcw_cart_get_subtotal', $subtotal );
	}

	/**
	 * Get Cart Subtotal Tax.
	 *
	 * @since 4.5.2
	 *
	 * @return float The subtotal tax amount.
	 */
	public function get_subtotal_tax() {
		$subtotal_tax = 0;
		$items        = $this->details;

		if ( $items ) {
			$subtotal_taxes = wp_list_pluck( $items, 'subtotal_tax' );

			if ( is_array( $subtotal_taxes ) ) {
				$subtotal_tax = array_sum( $subtotal_taxes );
			}
		}

		/**
		 * Filter: Cart Subtotal Tax.
		 *
		 * @since 4.5.2
		 *
		 * @param double|int $subtotal_tax The cart subtotal tax.
		 */
		$this->subtotal_tax = apply_filters( 'wpcw_cart_get_subtotal_tax', $subtotal_tax );

		return $this->subtotal_tax;
	}

	/**
	 * Get Cart Subtotal Formatted.
	 *
	 * @since 4.3.0
	 *
	 * @return string The subtotal formatted.
	 */
	public function subtotal() {
		return wpcw_price( $this->get_subtotal() );
	}

	/**
	 * Get Cart Discount Total.
	 *
	 * @since 4.5.2
	 *
	 * @return float|int|mixed|void
	 */
	public function get_discount_tax() {
		$discount_tax = 0.00;
		$items        = $this->details;

		if ( $items ) {
			$discount_taxes = wp_list_pluck( $items, 'discount_tax' );

			if ( is_array( $discount_taxes ) ) {
				$discount_tax = array_sum( $discount_taxes );
			}
		}

		/**
		 * Filter: Cart Discount Tax.
		 *
		 * @since 4.5.2
		 *
		 * @param double|int $discount_tax The cart discount tax.
		 */
		$this->discount_tax = apply_filters( 'wpcw_cart_get_discount_tax', $discount_tax );

		return $this->discount_tax;
	}

	/**
	 * Get Cart Discounts Total.
	 *
	 * @since 4.3.0
	 *
	 * @deprecated 4.5.0
	 *
	 * @return float|int|mixed|void
	 */
	public function get_discounts_total() {
		_deprecated_function( __METHOD__, '4.5.2', '\WPCW\Cart\get_discount_total()' );

		return apply_filters( 'wpcw_cart_get_discounts_total', $this->get_discount_total() );
	}

	/**
	 * Get Cart Discount Total.
	 *
	 * @since 4.5.2
	 *
	 * @return float|int|mixed|void
	 */
	public function get_discount_total() {
		$cart_discounts = 0.00;
		$items          = $this->details;

		if ( $items ) {
			$discounts = wp_list_pluck( $items, 'discount' );
			if ( is_array( $discounts ) ) {
				$cart_discounts = array_sum( $discounts );
			}
		}

		$subtotal = $this->get_subtotal();

		if ( empty( $subtotal ) ) {
			$cart_discounts = 0.00;
		}

		/**
		 * Filter: Cart Discount.
		 *
		 * @since 4.3.0
		 *
		 * @param double|int $cart_discounts The cart tax.
		 */
		$this->discount = apply_filters( 'wpcw_cart_get_discount_total', $cart_discounts );

		return $this->discount;
	}

	/**
	 * Get Cart Tax.
	 *
	 * @since 4.3.0
	 *
	 * @return float|int|mixed|void
	 */
	public function get_tax() {
		$tax   = 0;
		$items = $this->details;

		if ( $items ) {
			$taxes = wp_list_pluck( $items, 'tax' );

			if ( is_array( $taxes ) ) {
				$tax = array_sum( $taxes );
			}
		}

		$subtotal = (float) $this->get_subtotal();

		if ( ! $subtotal ) {
			$tax = 0;
		}

		/**
		 * Filter: Cart Tax.
		 *
		 * @since 4.3.0
		 *
		 * @param double|int $tax The cart tax.
		 */
		$this->tax = apply_filters( 'wpcw_cart_get_tax', $tax );

		return $this->tax;
	}

	/**
	 * Get Cart Tax Formatted.
	 *
	 * @since 4.3.0
	 *
	 * @return string The tax amount formatted.
	 */
	public function tax() {
		return wpcw_price( $this->get_tax() );
	}

	/**
	 * Get Cart Total.
	 *
	 * @since 4.3.0
	 *
	 * @return float
	 */
	public function get_total() {
		$subtotal = (float) $this->get_subtotal();
		$discount = (float) $this->get_discount_total();
		$tax      = (float) $this->get_tax();
		$total    = ( $subtotal - $discount ) + $tax;

		if ( $total < 0 ) {
			$total = 0.00;
		}

		/**
		 * Filter: Get Cart Total.
		 *
		 * @since 4.3.0
		 *
		 * @param float $total The total amount.
		 *
		 * @return float $total The total amount modified.
		 */
		$this->total = (float) apply_filters( 'wpcw_cart_get_total', $total );

		return $this->total;
	}

	/**
	 * Get Cart Total Formatted.
	 *
	 * @since 4.3.0
	 *
	 * @return string The cart total formatted.
	 */
	public function total() {
		return wpcw_price( $this->get_total() );
	}

	/**
	 * Does the cart needs payment.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the cart needs payemnt.
	 */
	public function needs_payment() {
		return apply_filters( 'wpcw_cart_needs_payment', 0 < $this->get_total(), $this );
	}

	/**
	 * Get Cart.
	 *
	 * @since 4.3.0
	 *
	 * @return array The cart contents.
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			wpcw_doing_it_wrong( __FUNCTION__, esc_html__( 'The "get_cart" functions should not be called before the wp_loaded action.', 'wp-courseware' ), '4.3.0' );
		}

		return empty( $this->contents ) ? array_filter( $this->get_contents() ) : array_filter( $this->contents );
	}

	/**
	 * Display Cart.
	 *
	 * @since 4.3.0
	 */
	public function display_cart() {
		if ( $this->is_empty() ) {
			return;
		}

		do_action( 'wpcw_cart_before_display', $this );

		wpcw_get_template( 'checkout/cart.php', array( 'cart' => $this ) );

		do_action( 'wpcw_cart_after_display', $this );
	}

	/**
	 * Display Empty Cart.
	 *
	 * @since 4.3.0
	 */
	public function display_empty_cart() {
		wpcw_get_template( 'checkout/cart-empty.php', array( 'cart' => $this ) );
	}

	/**
	 * Display Course Subscription Renewal Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $course_key The course key.
	 * @param Course $course The Course object.
	 *
	 * @return string The course subscription renewal message.
	 */
	public function display_course_subscription_renewal_message( $course_key, $course ) {
		if ( ! $course->is_purchasable() || ! $course->is_subscription() || ( $course->is_subscription() && $course->charge_installments() ) ) {
			return;
		}

		$message = sprintf( __( '<p><em>Billed %s until cancelled.</em></p>', 'wp-courseware' ), strtolower( $course->get_subscription_interval() ) );

		/**
		 * Filter: Cart Course Subscription Renewal Message.
		 *
		 * @since 4.6.0
		 *
		 * @param string $message The course subscription renewal message.
		 * @param Course $course The course object.
		 *
		 * @return string $message The course subscription renewal message.
		 */
		echo apply_filters( 'wpcw_cart_course_subscription_renewal_message', $message, $course );
	}

	/**
	 * Display Course Installments Message.
	 *
	 * @since 4.6.0
	 *
	 * @param string $course_key The course key.
	 * @param Course $course The Course object.
	 *
	 * @return string The course installments message.
	 */
	public function display_course_installments_message( $course_key, $course ) {
		if ( ! $course->is_purchasable() || ! $course->charge_installments() ) {
			return;
		}

		$message = sprintf( __( '<div class="wpcw-cart-item-message"><em>%s</em></div>', 'wp-courseware' ), $course->get_installments_label() );

		/**
		 * Filter: Cart Course Installments Message.
		 *
		 * @since 4.6.0
		 *
		 * @param string $message The course installments message.
		 * @param Course $course The course object.
		 *
		 * @return string $message The course installments message.
		 */
		echo apply_filters( 'wpcw_cart_course_installments_message', $message, $course );
	}

	/**
	 * Display Course Bundles Message.
	 *
	 * @since 4.6.0
	 *
	 * @param string $course_key The course key.
	 * @param Course $course The Course object.
	 *
	 * @return string The course bundles
	 */
	public function display_course_bundles_message( $course_key, $course ) {
		if ( ! $course->is_purchasable() ) {
			return;
		}

		if ( ! $course_bundles = $course->get_course_bundles() ) {
			return;
		}

		$bundled_courses = array();
		$message         = '';

		foreach ( $course_bundles as $course_bundle_id ) {
			$course_bundle                        = wpcw_get_course( $course_bundle_id );
			$bundled_courses[ $course_bundle_id ] = sprintf( '<a target="_blank" href="%s">%s</a>', $course_bundle->get_permalink(), $course_bundle->get_course_title() );
		}

		if ( ! empty( $bundled_courses ) ) {
			$message = sprintf( __( '<div class="wpcw-cart-item-message"><em>You will also be given access to: %s</em></div>', 'wp-courseware' ), implode( ', ', $bundled_courses ) );
		}

		/**
		 * Filter: Cart Course Bundles Message.
		 *
		 * @since 4.6.0
		 *
		 * @param string $message The course bundles message.
		 * @param Course $course The course object.
		 *
		 * @return string $message The course bundles message.
		 */
		echo apply_filters( 'wpcw_cart_course_bundles_message', $message, $course );
	}

	/**
	 * Add Cart Endpoints.
	 *
	 * @since 4.3.0
	 */
	public function add_to_cart_endpoints() {
		add_rewrite_endpoint( 'wpcw-cart-add', EP_ALL );
		add_rewrite_endpoint( 'wpcw-cart-remove', EP_ALL );
	}

	/**
	 * Process Cart Endpoints.
	 *
	 * @since 4.3.0
	 */
	public function process_add_to_cart_endpoints() {
		global $wp_query;

		// Adds an item to the cart with a /wpcw-cart-add/# URL
		if ( isset( $wp_query->query_vars['wpcw-cart-add'] ) ) {
			$course_id    = absint( $wp_query->query_vars['wpcw-cart-add'] );
			$installments = isset( $_GET['installments'] ) ? true : false;
			if ( $cart = $this->add_to_cart( $course_id, $installments ) ) {
				wpcw_add_notice( __( 'Course succesfully added to your cart.', 'wp-courseware' ), 'success' );
			}
			wp_redirect( wpcw_get_checkout_url() );
			die();
		}

		// Removes an item from the cart with a /wpcw-cart-remove/# URL
		if ( isset( $wp_query->query_vars['wpcw-cart-remove'] ) ) {
			$key = absint( $wp_query->query_vars['wpcw-cart-remove'] );
			if ( $cart = $this->remove_from_cart( $key ) ) {
				wpcw_add_notice( __( 'Course succesfully removed from your cart.', 'wp-courseware' ), 'success' );
			}
			wp_redirect( wpcw_get_checkout_url() );
			die();
		}
	}

	/**
	 * Register REST API Cart Endpoints.
	 *
	 * @since 4.3.0
	 *
	 * @return array $ajax_events The array of ajax events.
	 */
	public function register_cart_ajax_events( $ajax_events ) {
		$cart_ajax_events = array(
			'add-to-cart'      => array( $this, 'ajax_add_to_cart' ),
			'remove-from-cart' => array( $this, 'ajax_remove_from_cart' ),
			'update-cart'      => array( $this, 'ajax_update_cart' ),
		);

		return array_merge( $ajax_events, $cart_ajax_events );
	}

	/**
	 * Ajax Api: Add to Cart.
	 *
	 * @since 4.1.0
	 *
	 * @return array The ajax response.
	 */
	public function ajax_add_to_cart() {
		$course_id    = isset( $_REQUEST['course_id'] ) ? absint( $_REQUEST['course_id'] ) : 0;
		$installments = isset( $_REQUEST['installments'] ) ? esc_attr( $_REQUEST['installments'] ) : false;

		if ( empty( $course_id ) ) {
			$message = wpcw_get_notice( esc_html__( 'You must provide a course ID.', 'wp-courseware' ), 'error' );
			wp_send_json_success( array( 'error' => true, 'message' => $message ) );
		}

		$course = new Course( absint( $course_id ) );

		if ( ! $course ) {
			$message = wpcw_get_notice( esc_html__( 'Sorry, this course does not exist.', 'wp-courseware' ), 'error' );
			wp_send_json_success( array( 'error' => true, 'message' => $message ) );
		}

		if ( ! $course->is_purchasable() ) {
			$message = wpcw_get_notice( esc_html__( 'Sorry, this course cannot be purchased.', 'wp-courseware' ), 'error' );
			wp_send_json_success( array( 'error' => true, 'message' => $message ) );
		}

		$button   = wpcw_get_checkout_link();
		$redirect = false;

		if ( 'yes' === wpcw_get_setting( 'redirect_to_checkout' ) ) {
			$redirect = wpcw_get_checkout_url();
		}

		if ( ! $this->is_course_in_cart( $course_id ) ) {
			$this->add_to_cart( $course_id, $installments );
		}

		wp_send_json_success( array( 'redirect' => $redirect, 'button' => $button ) );
	}

	/**
	 * Ajax Api: Remove from Cart.
	 *
	 * @since 4.3.0
	 *
	 * @return array The ajax response.
	 */
	public function ajax_remove_from_cart() {
		wp_send_json_success( array( 'success' => true ) );
	}

	/**
	 * Ajax Api: Update Cart.
	 *
	 * @since 4.5.0
	 */
	public function ajax_update_cart() {
		wpcw()->ajax->verify_nonce();

		$this->setup_cart();

		if ( ! $this->is_empty() ) {
			wpcw_get_template( 'checkout/cart.php', array( 'cart' => $this ) );
		}

		wp_die();
	}

	/** Coupon Methods ------------------------------------------------------- */

	/**
	 * Get Coupons.
	 *
	 * @since 4.5.0
	 *
	 * @return array $coupons The array of applied Coupon objects.
	 */
	public function get_coupons() {
		$coupons = array();

		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon           = wpcw_get_coupon_by_code( $code );
			$coupons[ $code ] = $coupon;
		}

		return $coupons;
	}

	/**
	 * Get Applied Coupons from Session.
	 *
	 * Checks the session to see if there
	 * are any applied coupons to the cart.
	 *
	 * @since 4.5.0
	 */
	public function get_session_applied_coupons() {
		$this->applied_coupons = wpcw()->session->get( 'wpcw_applied_coupons' );

		/**
		 * Action: Get Cart Session Discounts.
		 *
		 * @since 4.3.0
		 *
		 * @param array The applied coupons.
		 * @param Cart The cart object.
		 */
		do_action( 'wpcw_cart_get_session_applied_coupons', $this->applied_coupons, $this );
	}

	/**
	 * Gets the array of applied coupon codes.
	 *
	 * @since 4.5.0
	 *
	 * @return array The arrray of applied coupons.
	 */
	public function get_applied_coupons() {
		if ( ! did_action( 'wpcw_cart_get_session_applied_coupons' ) ) {
			$this->get_session_applied_coupons();
		}

		return (array) $this->applied_coupons;
	}

	/**
	 * Update Applied Coupons.
	 *
	 * @since 4.5.0
	 */
	public function update_applied_coupons() {
		wpcw()->session->set( 'wpcw_applied_coupons', $this->applied_coupons );

		/**
		 * Action: Applied Coupons Updated.
		 *
		 * @since 4.5.0
		 *
		 * @param array The applied coupons.
		 */
		do_action( 'wpcw_applied_coupons_updated', $this->applied_coupons );
	}

	/**
	 * Sets the array of applied coupon codes.
	 *
	 * @since 4.5.0
	 *
	 * @param array $value List of applied coupon codes.
	 */
	public function set_applied_coupons( $value = array() ) {
		$this->applied_coupons = (array) $value;
	}

	/**
	 * Get Coupon Discount Totals.
	 *
	 * @since 4.5.0
	 *
	 * @return array The array of coupone discount amounts.
	 */
	public function get_coupon_discount_totals() {
		return (array) $this->coupon_discount_totals;
	}

	/**
	 * Return all calculated coupon totals.
	 *
	 * @since 4.5.0
	 *
	 * @param array $value The coupon discount totals.
	 */
	public function set_coupon_discount_totals( $value = array() ) {
		$this->coupon_discount_totals = (array) $value;
	}

	/**
	 * Get the discount amount for a used coupon.
	 *
	 * @since 4.5.0
	 *
	 * @param string $code coupon code.
	 *
	 * @return float discount amount
	 */
	public function get_coupon_discount_amount( $code ) {
		$discount_amount = 0;
		$discount_totals = $this->get_coupon_discount_totals();

		$discounts_by_code = wp_list_pluck( $discount_totals, $code );

		if ( is_array( $discounts_by_code ) ) {
			$discount_amount = array_sum( $discounts_by_code );
		}

		return wpcw_cart_round_discount( $discount_amount, wpcw_get_currency_decimals() );
	}

	/**
	 * Check cart coupons for errors.
	 *
	 * @since 4.5.0
	 */
	public function check_cart_coupons() {
		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon = wpcw_get_coupon_by_code( $code );

			if ( ! $coupon->is_valid() ) {
				$coupon->add_coupon_error( Coupon::E_COUPON_INVALID_REMOVED );
				$this->remove_coupon( $code );
			}
		}
	}

	/**
	 * Check for user coupons (now that we have billing email). If a coupon is invalid, add an error.
	 *
	 * Checks two types of coupons:
	 *  1. Where a list of customer emails are set (limits coupon usage to those defined).
	 *  2. Where a usage_limit_per_user is set (limits coupon usage to a number based on user ID and email).
	 *
	 * @since 4.5.0
	 *
	 * @param array $posted Post data.
	 */
	public function check_customer_coupons( $posted ) {
		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon = wpcw_get_coupon_by_code( $code );

			if ( $coupon->is_valid() ) {
				// Get user and posted emails to compare.
				$current_user = wp_get_current_user();
				$check_emails = array_unique(
					array_filter(
						array_map(
							'strtolower', array_map(
								'sanitize_email', array(
									$posted['email'],
									$current_user->user_email,
								)
							)
						)
					)
				);

				// Usage limits per user - check against billing and user email and user ID.
				$limit_per_user = $coupon->get_usage_limit_per_user();

				if ( 0 < $limit_per_user ) {
					$used_by         = $coupon->get_used_by();
					$usage_count     = 0;
					$user_id_matches = array( get_current_user_id() );

					// Check usage against emails.
					foreach ( $check_emails as $check_email ) {
						$usage_count       += count( array_keys( $used_by, $check_email, true ) );
						$user              = get_user_by( 'email', $check_email );
						$user_id_matches[] = $user ? $user->ID : 0;
					}

					foreach ( $user_id_matches as $user_id ) {
						$usage_count += count( array_keys( $used_by, (string) $user_id, true ) );
					}

					if ( $usage_count >= $coupon->get_usage_limit_per_user() ) {
						$coupon->add_coupon_error( Coupon::E_COUPON_USAGE_LIMIT_REACHED );
						$this->remove_coupon( $code );
					}
				}
			}
		}
	}

	/**
	 * Returns whether or not a coupon has been applied.
	 *
	 * @since 4.5.0
	 *
	 * @param string $coupon_code Coupon code to check.
	 *
	 * @return bool
	 */
	public function has_coupon_been_applied( $coupon_code = '' ) {
		return $coupon_code ? in_array( wpcw_format_coupon_code( $coupon_code ), $this->get_applied_coupons(), true ) : count( $this->get_applied_coupons() ) > 0;
	}

	/**
	 * Apply Coupon.
	 *
	 * @since 4.5.0
	 *
	 * @param string $coupon_code The coupon code.
	 *
	 * @return bool True if the coupon is applied, false if it does not exist or cannot be applied.
	 */
	public function apply_coupon( $coupon_code ) {
		// Check if coupons are enabled.
		if ( ! wpcw_coupons_enabled() ) {
			return false;
		}

		// Format coupon code.
		$coupon_code = wpcw_format_coupon_code( $coupon_code );

		// Coupon.
		$coupon = wpcw_get_coupon_by_code( $coupon_code );

		// Prevent adding coupons by Id.
		if ( $coupon->get_code() !== $coupon_code ) {
			$coupon->set_prop( 'code', $coupon_code );
			$coupon->add_coupon_error( Coupon::E_COUPON_NOT_EXIST );

			return false;
		}

		// Check it can be used with cart.
		if ( ! $coupon->is_valid() ) {
			return false;
		}

		// Check if applied.
		if ( $this->has_coupon_been_applied( $coupon_code ) ) {
			$coupon->add_coupon_error( Coupon::E_COUPON_ALREADY_APPLIED );

			return false;
		}

		// If its individual use then remove other coupons.
		if ( $coupon->get_individual_use() ) {
			/**
			 * Filter: Apply Individual Use Coupon.
			 *
			 * @since 4.5.0
			 *
			 * @param string $coupon The coupon code.
			 * @param array The array of applied coupons.
			 *
			 * @return array The array of coupons to keep.
			 */
			$coupons_to_keep = apply_filters( 'wpcw_apply_individual_use_coupon', array(), $coupon, $this->applied_coupons );

			foreach ( $this->applied_coupons as $applied_coupon ) {
				$keep_key = array_search( $applied_coupon, $coupons_to_keep, true );
				if ( false === $keep_key ) {
					$this->remove_coupon( $applied_coupon );
				} else {
					unset( $coupons_to_keep[ $keep_key ] );
				}
			}

			if ( ! empty( $coupons_to_keep ) ) {
				$this->applied_coupons += $coupons_to_keep;
			}
		}

		// Check to see if an individual use coupon is set.
		if ( $this->applied_coupons ) {
			foreach ( $this->applied_coupons as $code ) {
				$iu_coupon = wpcw_get_coupon_by_code( $code );

				/**
				 * Filter: Apply with Individual Use Coupon.
				 *
				 * @since 4.5.0
				 *
				 * @param string $coupon The coupon code.
				 * @param string $iu_coupon The individual use coupon.
				 * @param array The array of applied coupons.
				 *
				 * @return bool True if the individual use coupon should be applied.
				 */
				$apply_with_iu_coupon = apply_filters( 'wpcw_apply_with_individual_use_coupon', false, $coupon, $iu_coupon, $this->applied_coupons );
				if ( $iu_coupon->get_individual_use() && false === $apply_with_iu_coupon ) {
					$iu_coupon->add_coupon_error( Coupon::E_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY );

					return false;
				}
			}
		}

		// Add to applied coupons.
		$this->applied_coupons[] = $coupon_code;

		// Add Success Message.
		$coupon->add_coupon_message( Coupon::M_COUPON_SUCCESS );

		// Update Applied Coupons.
		$this->update_applied_coupons();

		/**
		 * Hook: Applied Coupon.
		 *
		 * @sicne 4.5.0
		 *
		 * @param string $coupon_code The coupon code.
		 * @param Coupon $coupon The coupon object.
		 * @param Cart   $this The cart object.
		 */
		do_action( 'wpcw_applied_coupon', $coupon_code, $coupon, $this );

		return true;
	}

	/**
	 * Remove a single coupon by code.
	 *
	 * @since 4.5.0
	 *
	 * @param string $coupon_code Code of the coupon to remove.
	 *
	 * @return bool
	 */
	public function remove_coupon( $coupon_code ) {
		$coupon_code = wpcw_format_coupon_code( $coupon_code );
		$position    = array_search( $coupon_code, $this->get_applied_coupons(), true );

		if ( false !== $position ) {
			unset( $this->applied_coupons[ $position ] );
		}

		// Update Coupons.
		$this->update_applied_coupons();

		/**
		 * Action: Removed Coupons.
		 *
		 * @since 4.5.0
		 *
		 * @param string $coupon_code The coupon code.
		 */
		do_action( 'wpcw_removed_coupon', $coupon_code );

		return true;
	}

	/**
	 * Remove Coupons.
	 *
	 * @since 4.5.0
	 */
	public function remove_coupons() {
		// Set Class variable to empty array.
		$this->set_coupon_discount_totals( array() );
		$this->set_applied_coupons( array() );

		// Empty Session.
		wpcw()->session->set( 'wpcw_applied_coupons', null );

		/**
		 * Action: Remove Coupons.
		 *
		 * @since 4.5.0
		 */
		do_action( 'wpcw_remove_coupons' );
	}
}
