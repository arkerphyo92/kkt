<?php
/**
 * WP Courseware Students Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Student.
 *
 * @since 4.3.0
 *
 * @param int|bool $student_id The Student Id.
 *
 * @return \WPCW\Models\Student|bool An student object or false.
 */
function wpcw_get_student( $student_id = false ) {
	return new \WPCW\Models\Student( $student_id );
}

/**
 * Get Test Student.
 *
 * @since 4.3.0
 *
 * @return \WPCW\Models\Student $test_student The test student object.
 */
function wpcw_get_test_student() {
	$test_student = new \WPCW\Models\Student();

	$test_data = array(
		'ID'                  => rand(),
		'user_login'          => 'teststudent',
		'user_pass'           => wp_generate_password(),
		'user_nicename'       => 'teststudent',
		'user_email'          => 'wpcw.test.student@wpcwtest.com',
		'user_url'            => '',
		'user_registered'     => date( 'Y-m-d H:i:s' ),
		'user_activation_key' => uniqid(),
		'user_status'         => 0,
		'display_name'        => esc_html__( 'John Smith', 'wp-courseware' ),
		'first_name'          => esc_html__( 'John', 'wp-courseware' ),
		'last_name'           => esc_html__( 'Smith', 'wp-courseware' ),
		'email'               => 'wpcw.test.student@wpcwtest.com',
		'billing_address_1'   => esc_html__( '430 E. WP Courseware Street', 'wp-courseware' ),
		'billing_address_2'   => esc_html__( 'Suite 120', 'wp-courseware' ),
		'billing_city'        => esc_html__( 'Phoenix', 'wp-courseware' ),
		'billing_state'       => esc_html__( 'AZ', 'wp-courseware' ),
		'billing_postcode'    => esc_html__( '85001', 'wp-courseware' ),
		'billing_country'     => esc_html__( 'United States', 'wp-courseware' ),
	);

	$test_student->set_data( $test_data );

	return $test_student;
}

/**
 * Create a new student.
 *
 * @since 4.3.0
 *
 * @param string $email Student email.
 * @param string $username Student username.
 * @param string $password Student password.
 * @param array $courses The courses the student should be enrolled into.
 *
 * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
 */
if ( ! function_exists( 'wpcw_create_new_student' ) ) {
	function wpcw_create_new_student( $email, $username = '', $password = '', $courses = array() ) {
		// Use WP_Error to handle registration errors.
		$errors = new \WP_Error();

		// Check Email.
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors->add( 'registration-error-invalid-email', esc_html__( 'Please provide a valid email address.', 'wp-courseware' ) );
		}

		// Existing Email?
		if ( email_exists( $email ) ) {
			$errors->add( 'registration-error-email-exists', apply_filters( 'wpcw_registration_error_email_exists', esc_html__( 'An account is already registered with your email address. Please log in.', 'wp-courseware' ), $email ) );
		}

		// Auto Generate Username and Password.
		$generate_username = apply_filters( 'wpcw_registration_generate_username', false );
		$generate_password = apply_filters( 'wpcw_registration_generate_password', false );

		// Username.
		$username = sanitize_user( $username );
		if ( empty( $username ) ) {
			if ( $generate_username ) {
				$username = sanitize_user( current( explode( '@', $email ) ), true );

				$append     = 1;
				$o_username = $username;

				while ( username_exists( $username ) ) {
					$username = $o_username . $append;
					$append ++;
				}
			} else {
				$errors->add( 'registration-error-invalid-username', esc_html__( 'Please enter a valid account username.', 'wp-courseware' ) );
			}
		} else {
			if ( ! validate_username( $username ) ) {
				$errors->add( 'registration-error-invalid-username', esc_html__( 'Please enter a valid account username.', 'wp-courseware' ) );
			}

			if ( username_exists( $username ) ) {
				$errors->add( 'registration-error-username-exists', esc_html__( 'An account is already registered with that username. Please choose another.', 'wp-courseware' ) );
			}
		}

		// Password.
		if ( empty( $password ) ) {
			if ( $generate_password ) {
				$password = wp_generate_password();
			} else {
				$errors->add( 'registration-error-missing-password', __( 'Please enter an account password.', 'wp-courseware' ) );
			}
		}

		do_action( 'wpcw_register_student_before', $username, $email, $errors );

		/** @var WP_Error $errors */
		$errors = apply_filters( 'wpcw_registration_errors', $errors, $username, $email );

		// Check for errors.
		if ( $errors->get_error_codes() ) {
			return $errors;
		}

		// Generated Password.
		$password_generated = $password;

		/**
		 * Fitler: New Student Data.
		 *
		 * @since 4.3.0
		 *
		 * @param array The new student data.
		 *
		 * @return array The modified student data.
		 */
		$new_student_data = apply_filters( 'wpcw_new_student_data', array(
			'user_login' => $username,
			'user_pass'  => $password,
			'user_email' => $email,
			'role'       => 'subscriber',
		) );

		$student_id = wp_insert_user( $new_student_data );

		if ( is_wp_error( $student_id ) ) {
			return new WP_Error( 'registration-error', '<strong>' . __( 'Error:', 'wp-courseware' ) . '</strong> ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'wp-courseware' ) );
		}

		if ( ! empty( $courses ) ) {
			wpcw()->enrollment->enroll_student( $student_id, $courses );
		}

		/**
		 * Action: Created Student.
		 *
		 * @since 4.3.0
		 *
		 * @param int $student_id The student id.
		 * @param array $new_student_data The new student data.
		 * @param string $password_generated The newly generated password.
		 */
		do_action( 'wpcw_created_student', $student_id, $new_student_data, $password_generated );

		/**
		 * Action: Student Registered
		 *
		 * @since 4.3.0
		 *
		 * @param int $student_id The student id.
		 * @param array $new_student_data The new student data.
		 * @param string $password_generated The newly generated password.
		 */
		do_action( 'wpcw_student_registered', $student_id, $new_student_data, $password_generated );

		return $student_id;
	}
}

/**
 * Login a student (set auth cookie and set global user object).
 *
 * @since 4.3.0
 *
 * @param int $student_id
 */
function wpcw_set_student_auth_cookie( $student_id ) {
	global $current_user;

	$current_user = get_user_by( 'id', $student_id );

	wp_set_auth_cookie( $student_id, true );
}

/**
 * Get Student Account Menu Items.
 *
 * @since 4.3.0
 *
 * @return array The array of menu items.
 */
function wpcw_get_student_account_menu_items() {
	$endpoints = array(
		'courses'        => wpcw_get_setting( 'student_courses_endpoint' ),
		'orders'         => wpcw_get_setting( 'student_orders_endpoint' ),
		'subscriptions'  => wpcw_get_setting( 'student_subscriptions_endpoint' ),
		'edit-account'   => wpcw_get_setting( 'student_edit_account_endpoint' ),
		'student-logout' => wpcw_get_setting( 'student_logout_endpoint' ),
	);

	$menu_items = array(
		'dashboard'      => esc_html__( 'Dashboard', 'wp-courseware' ),
		'courses'        => esc_html__( 'Courses', 'wp-courseware' ),
		'orders'         => esc_html__( 'Orders', 'wp-courseware' ),
		'subscriptions'  => esc_html__( 'Subscriptions', 'wp-courseware' ),
		'edit-account'   => esc_html__( 'Account', 'wp-courseware' ),
		'student-logout' => esc_html__( 'Logout', 'wp-courseware' ),
	);

	// Remove missing endpoints.
	foreach ( $endpoints as $endpoint_id => $endpoint ) {
		if ( empty( $endpoint ) ) {
			unset( $menu_items[ $endpoint_id ] );
		}
	}

	/**
	 * Action: Student Account Menu Items.
	 *
	 * @since 4.3.0
	 *
	 * @param array $menu_items The student account menu items.
	 */
	return apply_filters( 'wpcw_student_account_menu_items', $menu_items );
}

/**
 * Get Student Account Menu Items Classes.
 *
 * @since 4.3.0
 *
 * @param string $endpoint The account endpoint.
 *
 * @return string The account endpoint classes.
 */
function wpcw_get_student_account_menu_item_classes( $endpoint ) {
	global $wp;

	$classes = array(
		'wpcw-student-account-navigation-link',
		'wpcw-student-account-navigation-link-' . $endpoint,
	);

	// Set current item class.
	$current = isset( $wp->query_vars[ $endpoint ] );
	if ( 'dashboard' === $endpoint && ( isset( $wp->query_vars['page'] ) || empty( $wp->query_vars ) ) ) {
		$current = true; // Dashboard is not an endpoint, so needs a custom check.
	}

	if ( 'orders' === $endpoint && ( isset( $wp->query_vars['view-order'] ) ) ) {
		$current = true;
	}

	if ( 'subscriptions' === $endpoint && ( isset( $wp->query_vars['view-subscription'] ) ) ) {
		$current = true;
	}

	if ( $current ) {
		$classes[] = 'is-active';
	}

	/**
	 * Filter: Student Account Menu Item Classes.
	 *
	 * @since 4.3.0
	 */
	$classes = apply_filters( 'wpcw_student_account_menu_item_classes', $classes, $endpoint );

	return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/**
 * Get Student Account Endpoint Url.
 *
 * @since 4.3.0
 *
 * @param string $endpoint The endpoint string.
 *
 * @return string The endpoint url.
 */
function wpcw_get_student_account_endpoint_url( $endpoint ) {
	if ( 'dashboard' === $endpoint ) {
		return wpcw_get_page_permalink( 'account' );
	}

	if ( 'student-logout' === $endpoint ) {
		return wpcw_logout_url();
	}

	return wpcw_get_endpoint_url( $endpoint, '', wpcw_get_page_permalink( 'account' ) );
}

/**
 * Get Student Account Order Actions.
 *
 * @since 4.3.0
 *
 * @param int|\WPCW\Models\Order $order The order object.
 *
 * @return array The array of action items.
 */
function wpcw_get_student_account_orders_actions( $order ) {
	if ( ! is_object( $order ) ) {
		$order_id = absint( $order );
		$order    = wpcw_get_order( $order_id );
	}

	$actions = array(
		'view'   => array(
			'url'  => $order->get_view_order_url(),
			'name' => __( 'View', 'wp-courseware' ),
		),
		'cancel' => array(
			'url'  => $order->get_order_cancel_url( wpcw_get_page_permalink( 'account' ) ),
			'name' => __( 'Cancel', 'wp-courseware' ),
		),
	);

	if ( ! in_array( $order->get_status(), apply_filters( 'wpcw_valid_order_statuses_for_cancel', array( 'pending', 'failed' ), $order ) ) ) {
		unset( $actions['cancel'] );
	}

	/**
	 * Filter: Student Account Order Actions.
	 *
	 * @since 4.3.0
	 *
	 * @param array $actions The order actions.
	 * @param \WPCW\Models\Order $order The order object.
	 */
	return apply_filters( 'wpcw_student_account_order_actions', $actions, $order );
}

/**
 * Get Student Progress Bar.
 *
 * @since 4.3.0
 *
 * @param int $student_id The student id.
 * @param int $course_id The course id.
 *
 * @return string $student_progress The student progress bar.
 */
function wpcw_get_student_progress_bar( $student_id, $course_id = 0 ) {
	return wpcw()->students->get_student_progress_bar( $student_id, $course_id );
}

/**
 * Update Student Progress.
 *
 * @since 4.5.1
 *
 * @param int $student_id The student id.
 * @param int $course_id The course id.
 *
 * @return bool True upon success, false on failure.
 */
function wpcw_update_student_progress( $student_id, $course_id ) {
	global $wpdb, $wpcwdb;

	// Check for required paramaters.
	if ( ! $student_id || ! $course_id ) {
		return false;
	}

	// Get Course Details.
	$course = wpcw_get_course( $course_id );

	// Check for course details.
	if ( ! $course ) {
		return false;
	}

	// Get a total unit count of this course.
	$unit_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
    	 FROM {$wpcwdb->units_meta}
    	 WHERE parent_course_id = %d",
		$course->get_course_id()
	) );

	// Update User Unit Progress.
	WPCW_users_updateUserUnitProgress( $course_id, $student_id, $unit_count );

	return true;
}

/**
 * Can Student Access Course?
 *
 * @since 4.6.5
 *
 * @param int $course_id The course id.
 * @param int $student_id The student id. Optional.
 *
 * @return bool True if the student can access the course. False otherwise.
 */
function wpcw_can_student_access_course( $course_id, $student_id = 0 ) {
	return wpcw()->students->can_student_access_course( $course_id, $student_id );
}

/**
 * Can Student Access Unit?
 *
 * @since 4.6.5
 *
 * @param int $unit_id The unit id.
 * @param int $student_id The student id. Optional.
 *
 * @return bool True if the student can access unit.
 */
function wpcw_can_student_access_unit( $unit_id, $student_id = 0 ) {
	return wpcw()->students->can_student_access_unit( $unit_id, $student_id );
}

if ( ! function_exists( 'wpcw_create_new_student' ) ) {
	/**
	 * Create a new student.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Student email.
	 * @param string $username Student username.
	 * @param string $password Student password.
	 * @param array $courses The courses the student should be enrolled into.
	 *
	 * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
	 */
	function wpcw_create_new_student( $email, $username = '', $password = '', $courses = array() ) {
		// Use WP_Error to handle registration errors.
		$errors = new \WP_Error();

		// Check Email.
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors->add( 'registration-error-invalid-email', esc_html__( 'Please provide a valid email address.', 'wp-courseware' ) );
		}

		// Existing Email?
		if ( email_exists( $email ) ) {
			$errors->add( 'registration-error-email-exists', apply_filters( 'wpcw_registration_error_email_exists', esc_html__( 'An account is already registered with your email address. Please log in.', 'wp-courseware' ), $email ) );
		}

		// Auto Generate Username and Password.
		$generate_username = apply_filters( 'wpcw_registration_generate_username', false );
		$generate_password = apply_filters( 'wpcw_registration_generate_password', false );

		// Username.
		$username = sanitize_user( $username );
		if ( empty( $username ) ) {
			if ( $generate_username ) {
				$username = sanitize_user( current( explode( '@', $email ) ), true );

				$append     = 1;
				$o_username = $username;

				while ( username_exists( $username ) ) {
					$username = $o_username . $append;
					$append ++;
				}
			} else {
				$errors->add( 'registration-error-invalid-username', esc_html__( 'Please enter a valid account username.', 'wp-courseware' ) );
			}
		} else {
			if ( ! validate_username( $username ) ) {
				$errors->add( 'registration-error-invalid-username', esc_html__( 'Please enter a valid account username.', 'wp-courseware' ) );
			}

			if ( username_exists( $username ) ) {
				$errors->add( 'registration-error-username-exists', esc_html__( 'An account is already registered with that username. Please choose another.', 'wp-courseware' ) );
			}
		}

		// Password.
		if ( empty( $password ) ) {
			if ( $generate_password ) {
				$password = wp_generate_password();
			} else {
				$errors->add( 'registration-error-missing-password', __( 'Please enter an account password.', 'wp-courseware' ) );
			}
		}

		do_action( 'wpcw_register_student_before', $username, $email, $errors );

		/** @var WP_Error $errors */
		$errors = apply_filters( 'wpcw_registration_errors', $errors, $username, $email );

		// Check for errors.
		if ( $errors->get_error_codes() ) {
			return $errors;
		}

		// Generated Password.
		$password_generated = $password;

		/**
		 * Fitler: New Student Data.
		 *
		 * @since 4.3.0
		 *
		 * @param array The new student data.
		 *
		 * @return array The modified student data.
		 */
		$new_student_data = apply_filters( 'wpcw_new_student_data', array(
			'user_login' => $username,
			'user_pass'  => $password,
			'user_email' => $email,
			'role'       => 'subscriber',
		) );

		$student_id = wp_insert_user( $new_student_data );

		if ( is_wp_error( $student_id ) ) {
			return new WP_Error( 'registration-error', '<strong>' . __( 'Error:', 'wp-courseware' ) . '</strong> ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'wp-courseware' ) );
		}

		if ( ! empty( $courses ) ) {
			wpcw()->enrollment->enroll_student( $student_id, $courses );
		}

		/**
		 * Action: Created Student.
		 *
		 * @since 4.3.0
		 *
		 * @param int $student_id The student id.
		 * @param array $new_student_data The new student data.
		 * @param string $password_generated The newly generated password.
		 */
		do_action( 'wpcw_created_student', $student_id, $new_student_data, $password_generated );

		/**
		 * Action: Student Registered
		 *
		 * @since 4.3.0
		 *
		 * @param int $student_id The student id.
		 * @param array $new_student_data The new student data.
		 * @param string $password_generated The newly generated password.
		 */
		do_action( 'wpcw_student_registered', $student_id, $new_student_data, $password_generated );

		return $student_id;
	}
}

if ( ! function_exists( 'wpcw_get_student_progress_next_course_unit' ) ) {
	/**
	 * Get Student Progress Next Course Unit.
	 *
	 * @since 4.5.2
	 *
	 * @param int $student_id The student Id.
	 * @param int $course_id The course id.
	 * @param string $return The return type. 'id' or 'object'
	 *
	 * @return mixed The unit progress.
	 */
	function wpcw_get_student_progress_next_course_unit( $student_id, $course_id, $return = 'id' ) {
		// Check for required paramaters.
		if ( ! $student_id || ! $course_id ) {
			return false;
		}

		// Get User Progress.
		$student_progress = new WPCW_UserProgress( $course_id, $student_id );

		// Get Next Unit.
		if ( ! $next_unit = $student_progress->getNextPendingUnit() ) {
			return false;
		}

		return ( 'id' === $return ) ? $next_unit->unit_id : new \WPCW\Models\Unit( $next_unit->unit_id );
	}
}

if ( ! function_exists( 'wpcw_has_student_completed_course' ) ) {
	/**
	 * Has Student Completed Course?
	 *
	 * @since 4.6.0
	 *
	 * @param int $student_id The student Id.
	 * @param int $course_id The course id.
	 *
	 * @return bool True if the user has completed the course. Default is false.
	 */
	function wpcw_has_student_completed_course( $student_id, $course_id ) {
		// Check for required paramaters.
		if ( ! $student_id || ! $course_id ) {
			return false;
		}

		// Get User Progress.
		$student_progress = new WPCW_UserProgress( $course_id, $student_id );

		return $student_progress->isCourseCompleted();
	}
}

/**
 * Student Account Content.
 *
 * @since 4.3.0
 */
if ( ! function_exists( 'wpcw_student_account_content' ) ) {
	function wpcw_student_account_content() {
		global $wp;

		if ( ! empty( $wp->query_vars ) ) {
			$account_query_vars = wpcw()->query->get_query_vars();

			foreach ( $wp->query_vars as $key => $value ) {
				if ( 'pagename' === $key || ! isset( $account_query_vars[ $key ] ) ) {
					continue;
				}

				if ( has_action( 'wpcw_student_account_' . $key . '_endpoint' ) ) {
					do_action( 'wpcw_student_account_' . $key . '_endpoint', $value );

					return;
				}
			}
		}

		wpcw_get_template( 'account/account-dashboard.php', array( 'student' => get_user_by( 'id', get_current_user_id() ) ) );
	}
}

/**
 * Student Account Navigation.
 *
 * @since 4.3.0
 */
if ( ! function_exists( 'wpcw_student_account_navigation' ) ) {
	function wpcw_student_account_navigation() {
		wpcw_get_template( 'account/account-navigation.php' );
	}
}

/**
 * Student Account Courses.
 *
 * @since 4.3.0
 *
 * @param int $current_page The current page of courses. Default is 1.
 */
if ( ! function_exists( 'wpcw_student_account_courses' ) ) {
	function wpcw_student_account_courses( $current_page = 1 ) {
		$current_page = empty( $current_page ) ? 1 : absint( $current_page );

		$courses = wpcw_get_courses( apply_filters( 'wpcw_student_account_courses_query', array(
			'student_id' => get_current_user_id(),
			'page'       => $current_page,
		) ) );

		/**
		 * Filter: Student Account Courses Settings.
		 *
		 * @since 4.6.4
		 *
		 * @param array The student account courses settings.
		 * @param array The array of course objects.
		 *
		 * @return array The student account courses settings.
		 */
		$settings = apply_filters( 'wpcw_student_account_courses_settings', array(
			'description'    => wpcw_get_setting( 'student_account_courses_show_course_desc', 'no' ),
			'prerequisites'  => wpcw_get_setting( 'student_account_courses_show_course_prerequisites', 'no' ),
			'progress'       => wpcw_get_setting( 'student_account_courses_show_user_progress', 'yes' ),
			'grade'          => wpcw_get_setting( 'student_account_courses_show_user_grade', 'yes' ),
			'quiz_grade'     => wpcw_get_setting( 'student_account_courses_show_quiz_grade', 'no' ),
			'reset_progress' => wpcw_get_setting( 'student_account_courses_show_reset_progress', 'no' ),
			'certificate'    => wpcw_get_setting( 'student_account_courses_show_certificate', 'no' ),
		), $courses );

		foreach ( $settings as $setting_key => $setting_value ) {
			$settings[ $setting_key ] = 'yes' === $setting_value ? 'true' : 'false';
		}

		$courses_table = sprintf(
			'[wpcw_course_progress course_desc="%s" course_prerequisites="%s" user_progress="%s" user_grade="%s" user_quiz_grade="%s" reset_progress="%s" certificate="%s"]',
			$settings['description'],
			$settings['prerequisites'],
			$settings['progress'],
			$settings['grade'],
			$settings['quiz_grade'],
			$settings['reset_progress'],
			$settings['certificate']
		);

		wpcw_get_template( 'account/account-view-courses.php', array(
			'courses'       => $courses,
			'current_page'  => absint( $current_page ),
			'courses_table' => do_shortcode( $courses_table ),
			'settings'      => $settings,
		) );
	}
}

/**
 * Student Account Orders.
 *
 * @since 4.3.0
 *
 * @param int $current_page The current page of orders.
 */
if ( ! function_exists( 'wpcw_student_account_orders' ) ) {
	function wpcw_student_account_orders( $current_page ) {
		$current_page = empty( $current_page ) ? 1 : absint( $current_page );

		$orders = wpcw_get_orders( apply_filters( 'wpcw_student_account_orders_query', array(
			'student_id' => get_current_user_id(),
			'page'       => $current_page,
		) ) );

		wpcw_get_template( 'account/account-view-orders.php', array( 'orders' => $orders, 'current_page' => absint( $current_page ) ) );
	}
}

/**
 * Student Account View Order.
 *
 * @since 4.3.0
 *
 * @param int $order_id The order id.
 */
if ( ! function_exists( 'wpcw_student_account_view_order' ) ) {
	function wpcw_student_account_view_order( $order_id ) {
		$order = wpcw_get_order( $order_id );

		if ( get_current_user_id() !== $order->get_student_id() ) {
			echo '<div class="wpcw-notice wpcw-notice-info">' . __( 'The order you are trying to access is invalid.', 'wp-courseware' ) . ' <a href="' . wpcw_get_student_account_endpoint_url( 'orders' ) . '" class="wpcw-forward">' . __( 'View Orders', 'wp-courseware' ) . ' &rarr;</a>' . '</div>';

			return;
		}

		wpcw_get_template( 'account/account-view-order.php', array( 'order' => $order, 'order_id' => $order_id ) );
	}
}

/**
 * Student Account Subscriptions.
 *
 * @since 4.3.0
 *
 * @param int $current_page The current page of subscriptions.
 */
if ( ! function_exists( 'wpcw_student_account_subscriptions' ) ) {
	function wpcw_student_account_subscriptions( $current_page ) {
		$current_page = empty( $current_page ) ? 1 : absint( $current_page );

		$subscriptions = wpcw_get_subscriptions( apply_filters( 'wpcw_student_account_subscriptions_query', array(
			'student_id' => get_current_user_id(),
			'page'       => $current_page,
		) ) );

		wpcw_get_template( 'account/account-view-subscriptions.php', array( 'subscriptions' => $subscriptions, 'current_page' => absint( $current_page ) ) );
	}
}

/**
 * Student Account View Subscription.
 *
 * @since 4.3.0
 */
if ( ! function_exists( 'wpcw_student_account_view_subscription' ) ) {
	function wpcw_student_account_view_subscription( $subscription_id ) {
		$subscription = wpcw_get_subscription( $subscription_id );

		if ( get_current_user_id() !== $subscription->get_student_id() ) {
			wpcw_print_notice( sprintf( __( 'Invalid Subscription <a href="%s">View Account &rarr;</a>', 'wp-courseware' ), wpcw_get_page_permalink( 'account' ) ), 'info' );

			return;
		}

		wpcw_get_template( 'account/account-view-subscription.php', array( 'subscription' => $subscription, 'subscription_id' => $subscription_id ) );
	}
}

/**
 * Student Edit Account.
 *
 * @since 4.3.0
 */
if ( ! function_exists( 'wpcw_student_account_edit_account' ) ) {
	function wpcw_student_account_edit_account() {
		$current_student = wp_get_current_user();
		$address         = array();

		if ( wpcw()->students->account_billing_fields_enabled() ) {
			$address = wpcw()->countries->get_billing_address_fields( get_user_meta( get_current_user_id(), 'billing_country', true ), 'billing_' );

			foreach ( $address as $key => $field ) {
				$value = get_user_meta( get_current_user_id(), $key, true );

				if ( ! $value ) {
					switch ( $key ) {
						case 'billing_email' :
							$value = $current_student->user_email;
							break;
						case 'billing_country' :
							$value = wpcw()->countries->get_base_country();
							break;
						case 'billing_state' :
							$value = wpcw()->countries->get_base_state();
							break;
					}
				}

				$address[ $key ]['value'] = apply_filters( 'wpcw_student_account_edit_address_field_value', $value, $key, 'billing' );
			}
		}

		wpcw_get_template( 'account/form-edit-account.php', array(
			'student' => get_user_by( 'id', get_current_user_id() ),
			'address' => $address,
		) );
	}
}
