<?php
/**
 * WP Courseware Privacy Handler.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.3
 */
namespace WPCW\Core;

use WPCW\Models\Order;
use WPCW\Models\Student;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Privacy
 *
 * @since 4.3.3
 */
final class Privacy {

	/**
	 * Load Privacy Handler.
	 *
	 * @since 4.3.3
	 */
	public function load() {
		add_action( 'admin_init', array( $this, 'privacy_content' ), 20 );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'data_export_handler' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'data_eraser_handler' ) );
	}

	/**
	 * Privacy Content.
	 *
	 * @since 4.3.0
	 */
	public function privacy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		// Add Default Content.
		$default_privacy_content = $this->get_default_privacy_content();
		wp_add_privacy_policy_content( wpcw()->get_name(), $default_privacy_content );
	}

	/**
	 * Get Default Privacy Content.
	 *
	 * @since 4.3.0
	 *
	 * @return string $default_privacy_content The default privacy content for the plugin.
	 */
	protected function get_default_privacy_content() {
		$privacy_content = '';

		// Intro.
		$privacy_content .= sprintf( '<p>%s</p>', __( 'The following sections are samples of disclosures you could add into your privacy policy. The following sections are NOT meant to be legal advice nor should you copy it verbatim to your site without getting professional legal council. Lighthouse Media, LLC is not responsible for any misuse or unexpected results that may come about by using any of the following language in your privacy policy.', 'wp-courseware' ) );

		// Collection.
		$privacy_content .= sprintf( '<h2>%s</h2>', __( 'What information do we collect?', 'wp-courseware' ) );
		$privacy_content .= sprintf( '<p>%s</p>', __( 'In this section you can talk about what data is collected. WP Courseware only collects first name, last name, e-mail address, and mailing address when a purchase is made. If a course is offered for free, then the users registration information is only stored in the WordPress users table. WP Courseware does not collect any credit card data whatsoever.', 'wp-courseware' ) );

		// Information Used.
		$privacy_content .= sprintf( '<h2>%s</h2>', __( 'How is your information used?', 'wp-courseware' ) );
		$privacy_content .= sprintf( '<p>%s</p>', __( 'In this section you can explain what the data collected by WP Courseware is used for. Data collected from an order is used to process transactions, and enroll users into courses. Also data collected like name and email address is used to send out email notifications for purchases or course notifications.', 'wp-courseware' ) );

		// Cookies
		$privacy_content .= sprintf( '<h2>%s</h2>', __( 'Do we use cookies?', 'wp-courseware' ) );
		$privacy_content .= sprintf( '<p>%s</p>', __( 'Yes WP Courseware uses cookies to track shopping cart sessions. These cookies expire within 48 hours. Your site may use additional cookies so be sure to research what other cookies your site might be utilizing.', 'wp-courseware' ) );

		// Third-Parties.
		$privacy_content .= sprintf( '<h2>%s</h2>', __( 'Do we disclose any information to outside parties?', 'wp-courseware' ) );
		$privacy_content .= sprintf( '<p>%s</p>', __( 'WP Courseware only sends required data to the payment gateways.', 'wp-courseware' ) );

		// Rights.
		$privacy_content .= sprintf( '<h2>%s</h2>', __( 'What rights do you have to your data?', 'wp-courseware' ) );
		$privacy_content .= sprintf( '<p>%s</p>', __( 'WP Courseware is GDPR compliant in which users can request a copy of their data or can request to have their data anonymized through the core WordPress GDPR tools.', 'wp-courseware' ) );

		return apply_filters( 'wpcw_privacy_default_content', $privacy_content );
	}

	/**
	 * Data Export Handler.
	 *
	 * @since 4.3.3
	 *
	 * @param array $exporters The exporters array.
	 */
	public function data_export_handler( $exporters ) {
		$exporters[] = array(
			'exporter_friendly_name' => esc_html__( 'Student Data', 'wp-courseware' ),
			'callback'               => array( $this, 'export_student_data' ),
		);

		$exporters[] = array(
			'exporter_friendly_name' => esc_html__( 'Student Order Data', 'wp-courseware' ),
			'callback'               => array( $this, 'export_student_order_data' ),
		);

		$exporters[] = array(
			'exporter_friendly_name' => esc_html__( 'Student Subscription Data', 'wp-courseware' ),
			'callback'               => array( $this, 'export_student_subscription_data' ),
		);

		return $exporters;
	}

	/**
	 * Data Eraser Handler.
	 *
	 * @since 4.3.0
	 *
	 * @param array $erasers The erasers array.
	 */
	public function data_eraser_handler( $erasers ) {
		$erasers[] = array(
			'eraser_friendly_name' => esc_html__( 'Student Data', 'wp-courseware' ),
			'callback'             => array( $this, 'eraser_student_data' ),
		);

		$erasers[] = array(
			'eraser_friendly_name' => esc_html__( 'Student Order Data', 'wp-courseware' ),
			'callback'             => array( $this, 'eraser_student_order_data' ),
		);

		$erasers[] = array(
			'eraser_friendly_name' => esc_html__( 'Student Subscription Data', 'wp-courseware' ),
			'callback'             => array( $this, 'eraser_student_subscription_data' ),
		);

		return $erasers;
	}

	/**
	 * Export Student Data.
	 *
	 * @since 4.3.3
	 *
	 * @param string $email_address The student email address.
	 * @param int    $page The current page.
	 *
	 * @return array
	 */
	public function export_student_data( $email_address, $page = 1 ) {
		$email_address = trim( $email_address );

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$students = wpcw()->students->get_students( array( 'user_id' => $user->ID ) );

		if ( ! $students ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$student_props_to_export = array(
			'id'                => esc_html__( 'Student ID', 'wp-courseware' ),
			'first_name'        => esc_html__( 'Student First Name', 'wp-courseware' ),
			'last_name'         => esc_html__( 'Student Last Name', 'wp-courseware' ),
			'email'             => esc_html__( 'Student Email', 'wp-courseware' ),
			'billing_address_1' => esc_html__( 'Student Billing Address', 'wp-courseware' ),
			'billing_address_2' => esc_html__( 'Student Billing Address 2', 'wp-courseware' ),
			'billing_city'      => esc_html__( 'Student Billing City', 'wp-courseware' ),
			'billing_postcode'  => esc_html__( 'Student Billing Postcode', 'wp-courseware' ),
			'billing_country'   => esc_html__( 'Student Billing Country', 'wp-courseware' ),
			'billing_state'     => esc_html__( 'Student Billing State', 'wp-courseware' ),
		);

		foreach ( $students as $student ) {
			if ( ! $student instanceof Student ) {
				continue;
			}

			$student_data = array();

			foreach ( $student_props_to_export as $field => $label ) {
				if ( is_callable( array( $student, "get_{$field}" ) ) ) {
					$value = $student->{"get_{$field}"}();
					if ( ! empty( $value ) ) {
						$student_data[] = array(
							'name'  => $label,
							'value' => $value,
						);
					}
				}
			}

			if ( $agree_terms_time = $student->get_meta( '_wpcw_agree_to_terms_time' ) ) {
				$student_data[] = array(
					'name'  => esc_html__( 'Agreed to Terms &amp; Conditions', 'wp-courseware' ),
					'value' => wpcw_format_datetime( $agree_terms_time, wpcw_date_format() . ' ' . wpcw_time_format() ),
				);
			}

			if ( $agree_privacy_policy = $student->get_meta( '_wpcw_agree_to_privacy_policy_time' ) ) {
				$student_data[] = array(
					'name'  => esc_html__( 'Agreed to Privacy Policy', 'wp-courseware' ),
					'value' => wpcw_format_datetime( $agree_terms_time, wpcw_date_format() . ' ' . wpcw_time_format() ),
				);
			}

			$data_to_export[] = array(
				'group_id'    => 'student',
				'group_label' => esc_html__( 'Student', 'wp-courseware' ),
				'item_id'     => "student-{$student->get_ID()}",
				'data'        => $student_data,
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Eraser Student Data.
	 *
	 * @since 4.3.3
	 *
	 * @param string $email_address The student email address.
	 * @param int    $page The current page.
	 *
	 * @return array
	 */
	public function eraser_student_data( $email_address, $page = 1 ) {
		$eraser_data = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( empty( $email_address ) || ! function_exists( 'wp_privacy_anonymize_data' ) ) {
			return $eraser_data;
		}

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return $eraser_data;
		}

		$students = wpcw()->students->get_students( array( 'user_id' => $user->ID ) );

		if ( ! $students ) {
			return $eraser_data;
		}

		$student_data_to_anon = array(
			'billing_address_1' => esc_html__( 'Student Billing Address', 'wp-courseware' ),
			'billing_address_2' => esc_html__( 'Student Billing Address 2', 'wp-courseware' ),
			'billing_city'      => esc_html__( 'Student Billing City', 'wp-courseware' ),
			'billing_postcode'  => esc_html__( 'Student Billing Postcode', 'wp-courseware' ),
			'billing_country'   => esc_html__( 'Student Billing Country', 'wp-courseware' ),
			'billing_state'     => esc_html__( 'Student Billing State', 'wp-courseware' ),
		);

		foreach ( $students as $student ) {
			if ( ! $student instanceof Student ) {
				continue;
			}

			foreach ( $student_data_to_anon as $field => $label ) {
				if ( is_callable( array( $student, "get_{$field}" ) ) ) {
					$value = $student->{"get_{$field}"}();
					if ( ! empty( $value ) ) {
						$student->set_prop( $field, wp_privacy_anonymize_data( 'text', $value ) );
					}
				}
			}

			if ( $agree_terms_time = $student->get_meta( '_wpcw_agree_to_terms_time' ) ) {
				$student->update_meta( '_wpcw_agree_to_terms_time', wp_privacy_anonymize_data( 'date', $agree_terms_time ) );
			}

			if ( $agree_privacy_policy = $student->get_meta( '_wpcw_agree_to_privacy_policy_time' ) ) {
				$student->update_meta( '_wpcw_agree_to_privacy_policy_time', wp_privacy_anonymize_data( 'date', $agree_privacy_policy ) );
			}

			if ( $student->save() ) {
				$eraser_data['items_removed'] = true;
			} else {
				$eraser_data['items_retained'] = true;
			}
		}

		return $eraser_data;
	}

	/**
	 * Export Student Order Data.
	 *
	 * @since 4.3.3
	 *
	 * @param string $email_address The student email address.
	 * @param int    $page The current page.
	 *
	 * @return array
	 */
	public function export_student_order_data( $email_address, $page = 1 ) {
		$email_address = trim( $email_address );

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$orders = wpcw()->orders->get_orders( array( 'student_id' => $user->ID ) );

		if ( ! $orders ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$order_props_to_export = array(
			'id'                 => esc_html__( 'Order ID', 'wp-courseware' ),
			'order_type'         => esc_html__( 'Order Type', 'wp-courseware' ),
			'date_created'       => esc_html__( 'Order Date', 'wp-courseware' ),
			'date_paid'          => esc_html__( 'Order Date Paid', 'wp-courseware' ),
			'order_status'       => esc_html__( 'Order Status', 'wp-courseware' ),
			'student_id'         => esc_html__( 'Student ID', 'wp-courseware' ),
			'student_first_name' => esc_html__( 'Student First Name', 'wp-courseware' ),
			'student_last_name'  => esc_html__( 'Student Last Name', 'wp-courseware' ),
			'student_email'      => esc_html__( 'Student Email', 'wp-courseware' ),
			'billing_address_1'  => esc_html__( 'Student Billing Address', 'wp-courseware' ),
			'billing_address_2'  => esc_html__( 'Student Billing Address 2', 'wp-courseware' ),
			'billing_city'       => esc_html__( 'Student Billing City', 'wp-courseware' ),
			'billing_postcode'   => esc_html__( 'Student Billing Postcode', 'wp-courseware' ),
			'billing_country'    => esc_html__( 'Student Billing Country', 'wp-courseware' ),
			'billing_state'      => esc_html__( 'Student Billing State', 'wp-courseware' ),
			'student_ip_address' => esc_html__( 'Student IP Address', 'wp-courseware' ),
			'student_user_agent' => esc_html__( 'Student User Agent', 'wp-courseware' ),
		);

		foreach ( $orders as $order ) {
			if ( ! $order instanceof Order ) {
				continue;
			}

			$order_data = array();

			foreach ( $order_props_to_export as $field => $label ) {
				if ( is_callable( array( $order, "get_{$field}" ) ) ) {
					$value = $order->{"get_{$field}"}();
					if ( ! empty( $value ) ) {
						$order_data[] = array(
							'name'  => $label,
							'value' => $value,
						);
					}
				}
			}

			$data_to_export[] = array(
				'group_id'    => 'student-orders',
				'group_label' => esc_html__( 'Student Orders', 'wp-courseware' ),
				'item_id'     => "student-order-{$order->get_id()}",
				'data'        => $order_data,
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Eraser Student Order Data.
	 *
	 * @since 4.3.3
	 *
	 * @param string $email_address The student email address.
	 * @param int    $page The current page.
	 *
	 * @return array
	 */
	public function eraser_student_order_data( $email_address, $page = 1 ) {
		$eraser_data = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( empty( $email_address ) || ! function_exists( 'wp_privacy_anonymize_data' ) ) {
			return $eraser_data;
		}

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return $eraser_data;
		}

		$orders = wpcw()->orders->get_orders( array( 'student_id' => $user->ID ) );

		if ( ! $orders ) {
			return $eraser_data;
		}

		$order_props_to_export = array(
			'student_first_name' => esc_html__( 'Student First Name', 'wp-courseware' ),
			'student_last_name'  => esc_html__( 'Student Last Name', 'wp-courseware' ),
			'student_email'      => esc_html__( 'Student Email', 'wp-courseware' ),
			'billing_address_1'  => esc_html__( 'Student Billing Address', 'wp-courseware' ),
			'billing_address_2'  => esc_html__( 'Student Billing Address 2', 'wp-courseware' ),
			'billing_city'       => esc_html__( 'Student Billing City', 'wp-courseware' ),
			'billing_postcode'   => esc_html__( 'Student Billing Postcode', 'wp-courseware' ),
			'billing_country'    => esc_html__( 'Student Billing Country', 'wp-courseware' ),
			'billing_state'      => esc_html__( 'Student Billing State', 'wp-courseware' ),
			'student_ip_address' => esc_html__( 'Student IP Address', 'wp-courseware' ),
			'student_user_agent' => esc_html__( 'Student User Agent', 'wp-courseware' ),
		);

		foreach ( $orders as $order ) {
			if ( ! $order instanceof Order ) {
				continue;
			}

			$order_data = array();

			foreach ( $order_props_to_export as $field => $label ) {
				if ( is_callable( array( $order, "get_{$field}" ) ) ) {
					$value = $order->{"get_{$field}"}();
					if ( ! empty( $value ) ) {
						switch ( $field ) {
							case 'student_email' :
								$order->set_prop( $field, wp_privacy_anonymize_data( 'email', $value ) );
								break;
							case 'student_ip_address' :
								$order->set_prop( $field, wp_privacy_anonymize_data( 'ip', $value ) );
								break;
							default:
								$order->set_prop( $field, wp_privacy_anonymize_data( 'text', $value ) );
								break;
						}
					}
				}
			}

			if ( $order->save() ) {
				$eraser_data['items_removed'] = true;
			} else {
				$eraser_data['items_retained'] = true;
			}
		}

		return $eraser_data;
	}

	/**
	 * Export Student Subscription Data.
	 *
	 * @since 4.3.3
	 *
	 * @param string $email_address The student email address.
	 * @param int    $page The current page.
	 *
	 * @return array
	 */
	public function export_student_subscription_data( $email_address, $page = 1 ) {
		$email_address = trim( $email_address );

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$subscriptions = wpcw()->subscriptions->get_subscriptions( array( 'student_id' => $user->ID ) );

		if ( ! $subscriptions ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$subscription_props_to_export = array(
			'id'            => esc_html__( 'Subscription ID', 'wp-courseware' ),
			'created'       => esc_html__( 'Subscription Date', 'wp-courseware' ),
			'status'        => esc_html__( 'Subscription Status', 'wp-courseware' ),
			'student_id'    => esc_html__( 'Student ID', 'wp-courseware' ),
			'student_name'  => esc_html__( 'Student Name', 'wp-courseware' ),
			'student_email' => esc_html__( 'Student Email', 'wp-courseware' ),
		);

		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription instanceof Subscription ) {
				continue;
			}

			$subscription_data = array();

			foreach ( $subscription_props_to_export as $field => $label ) {
				if ( is_callable( array( $subscription, "get_{$field}" ) ) ) {
					$value = $subscription->{"get_{$field}"}();
					if ( ! empty( $value ) ) {
						$subscription_data[] = array(
							'name'  => $label,
							'value' => $value,
						);
					}
				}
			}

			$data_to_export[] = array(
				'group_id'    => 'student-subscriptions',
				'group_label' => esc_html__( 'Student Subscriptions', 'wp-courseware' ),
				'item_id'     => "student-subscription-{$subscription->get_id()}",
				'data'        => $subscription_data,
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Eraser Student Subscription Data.
	 *
	 * @since 4.3.3
	 *
	 * @param string $email_address The student email address.
	 * @param int    $page The current page.
	 *
	 * @return array
	 */
	public function eraser_student_subscription_data( $email_address, $page = 1 ) {
		$eraser_data = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( empty( $email_address ) || ! function_exists( 'wp_privacy_anonymize_data' ) ) {
			return $eraser_data;
		}

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return $eraser_data;
		}

		$subscriptions = wpcw()->subscriptions->get_subscriptions( array( 'student_id' => $user->ID ) );

		if ( ! $subscriptions ) {
			return $eraser_data;
		}

		$subscription_props_to_export = array(
			'created'       => esc_html__( 'Subscription Date', 'wp-courseware' ),
			'student_name'  => esc_html__( 'Student Name', 'wp-courseware' ),
			'student_email' => esc_html__( 'Student Email', 'wp-courseware' ),
		);

		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription instanceof Subscription ) {
				continue;
			}

			$subscription_data = array();

			foreach ( $subscription_props_to_export as $field => $label ) {
				if ( is_callable( array( $subscription, "get_{$field}" ) ) ) {
					$value = $subscription->{"get_{$field}"}();
					if ( ! empty( $value ) ) {
						switch ( $field ) {
							case 'student_email' :
								$subscription->set_prop( $field, wp_privacy_anonymize_data( 'email', $value ) );
								break;
							case 'created' :
								$subscription->set_prop( $field, wp_privacy_anonymize_data( 'date', $value ) );
								break;
							default :
								$subscription->set_prop( $field, wp_privacy_anonymize_data( 'text', $value ) );
								break;
						}
					}
				}

				if ( $subscription->save() ) {
					$eraser_data['items_removed'] = true;
				} else {
					$eraser_data['items_retained'] = true;
				}
			}
		}

		return $eraser_data;
	}
}
