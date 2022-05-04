<?php
/**
 * WP Courseware Shortcodes.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */

namespace WPCW\Core;

use WPCW\Models\Course;
use WPCW\Models\Unit;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcodes.
 *
 * @since 4.3.0
 */
final class Shortcodes {

	/**
	 * Load Shortcodes.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Register Shortcodes.
	 *
	 * @since 4.3.0
	 */
	public function register_shortcodes() {
		// Courses Shortcode
		add_shortcode( 'wpcw_courses', array( $this, 'courses_shortcode' ) );

		// Course Shortcode.
		add_shortcode( 'wpcourse', array( $this, 'course_shortcode' ) );
		add_shortcode( 'wpcw_course', array( $this, 'course_shortcode' ) );

		// Course Progress Shortcode
		add_shortcode( 'wpcourse_progress', array( $this, 'course_progress_shortcode' ) );
		add_shortcode( 'wpcw_course_progress', array( $this, 'course_progress_shortcode' ) );

		// Course Progress Bar Shortcode
		add_shortcode( 'wpcourse_progress_bar', array( $this, 'course_progress_bar_shortcode' ) );
		add_shortcode( 'wpcw_course_progress_bar', array( $this, 'course_progress_bar_shortcode' ) );

		// Course Next Available Unit Shortcode.
		add_shortcode( 'wpcourse_next_available_unit', array( $this, 'course_next_available_unit_shortcode' ) );
		add_shortcode( 'wpcw_course_next_available_unit', array( $this, 'course_next_available_unit_shortcode' ) );

		// Enroll Shortcode
		add_shortcode( 'wpcourse_enroll', array( $this, 'course_enroll_shortcode' ) );
		add_shortcode( 'wpcw_course_enroll', array( $this, 'course_enroll_shortcode' ) );

		// Purchase Shortcode
		add_shortcode( 'wpcw_purchase_course', array( $this, 'purchase_course_shortcode' ) );

		// Checkout Shortcode
		add_shortcode( 'wpcw_checkout', array( $this, 'checkout_shortcode' ) );

		// Order Shortcodes
		add_shortcode( 'wpcw_order_received', array( $this, 'order_received_shortcode' ) );
		add_shortcode( 'wpcw_order_failed', array( $this, 'order_failed_shortcode' ) );

		// Account Shortcode
		add_shortcode( 'wpcw_account', array( $this, 'account_shortcode' ) );
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @since 4.3.0
	 *
	 * @param string $content The shortcode content.
	 * @param array $wrapper The wrapper atrribute data.
	 *
	 * @return string
	 */
	protected function shortcode_wrapper(
		$function, $atts = array(), $wrapper = array(
		'class'  => '',
		'before' => null,
		'after'  => null
	)
	) {
		ob_start();

		$classes = apply_filters( 'wpcw_shortcode_wrapper_classes', array( 'wpcw-shortcode wpcw' ) );
		$classes = ! empty( $classes ) ? ' ' . implode( ' ', array_map( 'esc_attr', $classes ) ) : '';

		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . $classes . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

		return ob_get_clean();
	}

	/**
	 * Course Shortcode.
	 *
	 * examples:
	 *  - [wpcourse course="2" module_desc="false" show_title="false" show_desc="false"]
	 *  - [wpcw_course course="2" module_desc="false" show_title="false" show_desc="false"]
	 *
	 * @since 4.4.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The course html string.
	 */
	public function course_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(
			'course'                => 0,
			'module'                => 0,
			'module_desc'           => false,
			'show_title'            => false,
			'show_desc'             => false,
			'hide_credit_link'      => false,
			'widget_mode'           => false,
			'show_toggle_col'       => false,
			'show_modules_previous' => 'all',
			'show_modules_next'     => 'all',
			'toggle_modules'        => 'expand_all',
			'user_quiz_grade'       => false,
		), $atts, 'wpcw_course' );

		return WPCW_courses_renderCourseList( $shortcode_atts['course'], $shortcode_atts );
	}

	/**
	 * Course Progress Shortcode.
	 *
	 * examples:
	 *  - [wpcourse_progress courses="1,2" user_progress="true" user_grade="true" user_quiz_grade="true" certificate="true" /]
	 *
	 * @since 4.5.1
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The course progress html string.
	 */
	public function course_progress_shortcode( $atts, $content = '' ) {
		return WPCW_shortcodes_showTrainingCourseProgress( $atts, $content );
	}

	/**
	 * Course Progress Bar Shortcode.
	 *
	 * examples:
	 *  - [wpcourse_progress_bar course="1" /]
	 *
	 * @since 4.6.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The course progress bar html string.
	 */
	public function course_progress_bar_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(
			'course'     => 0,
			'show_title' => false,
			'show_desc'  => false,
			'student_id' => 0,
		), $atts, 'wpcw_course_progress_bar' );

		if ( ! $shortcode_atts['course'] && ! is_null( $shortcode_atts['course'] ) ) {
			return;
		}

		if ( ! $shortcode_atts['student_id'] && ! is_user_logged_in() ) {
			return;
		}

		$show_title = filter_var( $shortcode_atts['show_title'], FILTER_VALIDATE_BOOLEAN );
		$show_desc  = filter_var( $shortcode_atts['show_desc'], FILTER_VALIDATE_BOOLEAN );

		$student_id = $shortcode_atts['student_id'] ?: get_current_user_id();
		$course_id  = absint( $shortcode_atts['course'] );

		$shortcode_html = wpcw()->students->get_student_progress_bar( $student_id, $course_id, true );

		if ( $shortcode_html ) {
			if ( $show_title || $show_desc ) {
				$course         = wpcw_get_course( $course_id );
				$title          = $show_title ? apply_filters( 'wpcw_course_progress_bar_shortcode_course_title', sprintf( '<h3>%s</h3>', $course->get_course_title() ) ) : '';
				$desc           = $show_desc ? apply_filters( 'wpcw_course_progress_bar_shortcode_course_desc', wpautop( $course->get_course_desc() ) ) : '';
				$shortcode_html = $title . $desc . $shortcode_html;
			}

			$shortcode_html = sprintf( '<div class="wpcw-course-progress-bar-shortcode wpcw-course-progress-%d wpcw-course-progress-student-%d">%s</div>', $course_id, $student_id, $shortcode_html );
		}

		return apply_filters( 'wpcw_course_progress_bar_shortcode_html', $shortcode_html );
	}

	/**
	 * Course Next Available Unit Shortcode.
	 *
	 * examples:
	 *  - [wpcourse_next_available_unit course="1" text="Next Unit" class="next-unit" /]
	 *
	 * @since 4.6.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The course progress bar html string.
	 */
	public function course_next_available_unit_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(
			'course' => 0,
			'text'   => esc_html__( 'Next Unit', 'wp-courseware' ),
			'class'  => false,
		), $atts );

		// Check for courses.
		if ( ! $shortcode_atts['course'] || ! is_user_logged_in() ) {
			return;
		}

		$student_id = get_current_user_id();
		$course_id  = absint( $shortcode_atts['course'] );

		/** @var Unit $next_available_unit */
		$next_available_unit_id = wpcw_get_student_progress_next_course_unit( $student_id, $course_id, 'id' );

		if ( ! $next_available_unit_id ) {
			return;
		}

		/**
		 * Fitler: Next Available Unit Shortcode Html.
		 *
		 * @since 4.6.0
		 *
		 * @param string The next available unit shortcode html.
		 * @param array $shortcode_atts The shortcode attributes.
		 * @param int $next_available_unit_id The next available unit id.
		 *
		 * @return string The next available unit shortcode html.
		 */
		return apply_filters( 'wpcw_course_next_available_unit_shortcode_html', sprintf(
			'<a href="%1$s"%2$s>%3$s</a>',
			get_post_permalink( $next_available_unit_id ),
			$shortcode_atts['class'] ? sprintf( ' class="%s"', esc_attr( $shortcode_atts['class'] ) ) : '',
			wp_kses_post( $shortcode_atts['text'] )
		), $shortcode_atts, $next_available_unit_id );
	}

	/**
	 * Course Enroll Shortcode.
	 *
	 * e.g. [wpcourse_enroll courses="2,3" enroll_text="Enroll Here"]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The course enroll button string.
	 */
	public function course_enroll_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(
			'courses'           => false,
			'enroll_text'       => esc_html__( 'Enroll Now', 'wp-courseware' ),
			'purchase_text'     => 'Purchase',
			'installments_text' => 'Installments',
			'display_messages'  => true,
			'display_raw'       => false,
			'redirect'          => false,
		), $atts, 'wpcourse_enroll' );

		// Check for courses.
		if ( ! $shortcode_atts['courses'] && ! is_null( $shortcode_atts['courses'] ) ) {
			return;
		}

		/**
		 * Filter: Ignore Active embership Integrations.
		 *
		 * @since 4.4.5
		 *
		 * @return bool True to ignore membership integrations, false otherwise.
		 */
		if ( wpcw_is_membership_integration_active() && ! apply_filters( 'wpcw_ignore_active_membership_integration', false ) ) {
			return;
		}

		/**
		 * Filter: Disable Course Enroll Button.
		 *
		 * @since 4.4.5
		 *
		 * @return bool True to disable course enroll button, false otherwise.
		 */
		if ( apply_filters( 'wpcw_disable_course_enroll_button', false ) ) {
			return;
		}

		$shortcode_html      = '';
		$course_ids          = ! is_array( $shortcode_atts['courses'] ) ? explode( ',', $shortcode_atts['courses'] ) : $shortcode_atts['courses'];
		$courses_to_purchase = array();
		$courses_to_enroll   = array();
		$enroll_text         = esc_html( $shortcode_atts['enroll_text'] );
		$display_raw         = filter_var( $shortcode_atts['display_raw'], FILTER_VALIDATE_BOOLEAN );
		$redirect            = filter_var( $shortcode_atts['redirect'], FILTER_VALIDATE_BOOLEAN );

		$display_enroll_messages = filter_var( $shortcode_atts['display_messages'], FILTER_VALIDATE_BOOLEAN );

		$course_query_args = array( 'course_id' => $course_ids );

		// If ther user is logged in, show enrollment buttons for both
		// public and private courses.
		if ( is_user_logged_in() ) {
			$course_query_args['status'] = array( 'publish', 'private' );
		}

		$courses        = array();
		$course_objects = wpcw()->courses->get_courses( $course_query_args );

		/** @var \WPCW\Models\Course $course_object */
		foreach ( $course_objects as $course_object ) {
			$found_key = array_search( $course_object->get_id(), $course_ids );
			if ( false !== $found_key ) {
				$courses[] = $course_objects[ $found_key ];
			}
		}

		if ( ! $display_raw && is_user_logged_in() ) {
			/** @var Course $course */
			foreach ( $courses as $course ) {
				if ( $course->is_purchasable() ) {
					$courses_to_purchase[] = $course;
				} elseif ( ! $course->is_closed() ) {
					$courses_to_enroll[] = $course;
				}
			}

			// Enroll Courses.
			if ( ! empty( $courses_to_enroll ) ) {
				$courses_ids_to_add = array();

				/** @var Course $course_enroll */
				foreach ( $courses_to_enroll as $course_enroll ) {
					if ( $course_enroll->can_user_access( get_current_user_id() ) ) {
						if ( $display_enroll_messages ) {
							/* translators: %s is the Course Title */
							$shortcode_html .= sprintf( __( '<div class="wpcw_fe_enrolled"><p>You have already been enrolled into <strong>%s</strong>.</p></div>', 'wp-courseware' ), $course_enroll->get_course_title() );
						}
					} elseif ( ! $course_enroll->is_closed() && ! $course_enroll->is_manual() ) {
						$courses_ids_to_add[] = $course_enroll->get_course_id();
					}
				}

				if ( ! empty( $courses_ids_to_add ) ) {
					$courses_ids_to_add = implode( "_", $courses_ids_to_add );

					$shortcode_html .= sprintf(
						__( '<div class="wpcw_fe_enroll_button" id="wpcw_fe_enroll_%s">
							<img src="%s" class="wpcw_loader" style="display: none;" />
							<a href="#" class="fe_btn fe_btn_completion btn_completion" id="enroll_%s" data-display-messages="%s" data-redirect="%s">%s</a>
						</div>', 'wp-courseware' ),
						$courses_ids_to_add,
						wpcw_image_file( 'ajax_loader.gif' ),
						$courses_ids_to_add,
						$display_enroll_messages,
						$redirect,
						$enroll_text
					);
				}
			}

			// Purchase Courses.
			if ( ! empty( $courses_to_purchase ) ) {
				/** @var Course $course_to_purchase */
				foreach ( $courses_to_purchase as $course_to_purchase ) {
					$shortcode_html .= wpcw_add_to_cart_link( $course_to_purchase, array( 'text' => $shortcode_atts['purchase_text'] ), $display_enroll_messages );
					if ( $course_to_purchase->are_installments_enabled() && ! wpcw_is_course_in_cart( $course_to_purchase->get_course_id() ) ) {
						$shortcode_html .= wpcw_add_to_cart_link( $course_to_purchase, array(
							'text'         => $shortcode_atts['installments_text'],
							'installments' => true
						), $display_enroll_messages );
					}
				}
			}
		} else {
			/** @var Course $course */
			foreach ( $courses as $key => $course ) {
				if ( $course->is_purchasable() ) {
					$courses_to_purchase[] = $course;
				} elseif ( ! $course->is_closed() && ! $course->is_manual() ) {
					$courses_to_enroll[ 'course_id[' . $key . ']' ] = $course->get_course_id();
				}
			}

			// Enroll Courses
			if ( ! empty( $courses_to_enroll ) ) {
				$query_args            = array_merge( $courses_to_enroll, array( 'unit_redirect' => $redirect ) );
				$course_enrollment_url = wp_nonce_url( add_query_arg( $query_args, wp_registration_url() ), 'wpcw_enroll', '_wp_enroll' );
				$shortcode_html        .= sprintf( __( '<a href="%s" class="fe_btn fe_btn_completion btn_completion" id="enroll_registration">%s</a>', 'wp-courseware' ), esc_url_raw( $course_enrollment_url ), esc_html( $enroll_text ) );
			}

			// Courses to Purchase
			if ( ! empty( $courses_to_purchase ) ) {
				/** @var Course $course_to_purchase */
				foreach ( $courses_to_purchase as $course_to_purchase ) {
					$shortcode_html .= wpcw_add_to_cart_link( $course_to_purchase, array( 'text' => $shortcode_atts['purchase_text'] ), $display_enroll_messages, $display_raw );
					if ( $course_to_purchase->are_installments_enabled() && ! wpcw_is_course_in_cart( $course_to_purchase->get_course_id() ) ) {
						$shortcode_html .= wpcw_add_to_cart_link( $course_to_purchase, array(
							'text'         => $shortcode_atts['installments_text'],
							'installments' => true
						), $display_enroll_messages, $display_raw );
					}
				}
			}
		}

		return apply_filters( 'wpcw_course_enroll_shortcode_html', $shortcode_html );
	}

	/**
	 * Purchase Course Shortcode.
	 *
	 * e.g. [wpcw_purchase_course course="1" text="Purchase" ]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The course enroll button string.
	 */
	public function purchase_course_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(
			'course'           => false,
			'text'             => esc_html__( 'Purchase', 'wp-courseware' ),
			'display_messages' => false,
		), $atts, 'wpcw_purchase_course' );

		// Check for courses.
		if ( ! $shortcode_atts['course'] && ! empty( $shortcode_atts['course'] ) ) {
			return;
		}

		$course_id = absint( $shortcode_atts['course'] );
		$course    = new Course( absint( $course_id ) );

		if ( ! $course ) {
			return;
		}

		if ( ! $course->is_purchasable() ) {
			return do_shortcode( '[wpcw_course_enroll courses=' . $course->get_course_id() . ' display_messages=' . (bool) $shortcode_atts['display_messages'] . ']' );
		}

		return apply_filters( 'wpcw_purchase_course_shortcode_html', wpcw_add_to_cart_link( $course, array( 'text' => $shortcode_atts['text'] ), false ) );
	}

	/**
	 * Courses Shortcode.
	 *
	 * e.g. [wpcw_courses]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The courses html string.
	 */
	public function courses_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(
			'number'        => 100,
			'orderby'       => 'date',
			'order'         => 'ASC',
			'show_image'    => true,
			'show_desc'     => true,
			'show_button'   => true,
			'course_author' => 0,
		), $atts, 'wpcw_courses' );

		return $this->shortcode_wrapper( array(
			wpcw()->courses,
			'courses_display'
		), $shortcode_atts, array( 'class' => 'wpcw-shortcode-courses' ) );
	}

	/**
	 * Checkout Shortcode.
	 *
	 * e.g. [wpcw_checkout]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The checkout shortcode html.
	 */
	public function checkout_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(), $atts, 'wpcw_checkout' );

		return $this->shortcode_wrapper( array(
			wpcw()->checkout,
			'checkout_display'
		), $shortcode_atts, array( 'class' => 'wpcw-shortcode-checkout' ) );
	}

	/**
	 * Order Received Shortcode.
	 *
	 * e.g. [wpcw_order_received]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The checkout shortcode html.
	 */
	public function order_received_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(), $atts, 'wpcw_order_received' );

		return $this->shortcode_wrapper( array(
			wpcw()->checkout,
			'checkout_order_received_display'
		), $shortcode_atts, array( 'class' => 'wpcw-shortcode-order-received' ) );
	}

	/**
	 * Order Failed Shortcode.
	 *
	 * e.g. [wpcw_order_failed]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The checkout shortcode html.
	 */
	public function order_failed_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(), $atts, 'wpcw_order_failed' );

		return $this->shortcode_wrapper( array(
			wpcw()->checkout,
			'checkout_order_failed_display'
		), $shortcode_atts, array( 'class' => 'wpcw-shortcode-order-failed' ) );
	}

	/**
	 * Account Shortcode.
	 *
	 * e.g. [wpcw_account]
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The shortcode attributes.
	 * @param string $content The shortcode content.
	 *
	 * @return string The account shortcode html.
	 */
	public function account_shortcode( $atts, $content = '' ) {
		$shortcode_atts = shortcode_atts( array(), $atts, 'wpcw_account' );

		return $this->shortcode_wrapper( array(
			wpcw()->students,
			'account_display'
		), $shortcode_atts, array( 'class' => 'wpcw-shortcode-account wpcw-student-account' ) );
	}
}
