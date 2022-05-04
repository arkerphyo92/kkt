<?php
/**
 * WP Courseware Students Controller.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.1.0
 */

namespace WPCW\Controllers;

use Exception;
use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_User;
use WP_User_Query;
use WPCW\Admin\Pages\Page_Settings;
use WPCW\Common\Settings_Api;
use WPCW\Core\Api;
use WPCW\Database\DB_Students;
use WPCW\Models\Student;
use WPCW_queue_dripfeed;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Students.
 *
 * @since 4.1.0
 */
class Students extends Controller {

	/**
	 * @var DB_Students The students db object.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var array Student Courses.
	 * @since 4.3.0
	 */
	protected $courses = array();

	/**
	 * Students constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Students();
	}

	/**
	 * Students Load.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		// Add Billing Fields to Users Screen.
		add_action( 'show_user_profile', array( $this, 'add_billing_user_meta_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_billing_user_meta_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_billing_user_meta_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_billing_user_meta_fields' ) );

		// Process Forms.
		add_action( 'wp_loaded', array( $this, 'process_login' ), 20 );
		add_action( 'wp_loaded', array( $this, 'process_registration' ), 20 );
		add_action( 'wp_loaded', array( $this, 'process_lost_password' ), 20 );
		add_action( 'wp_loaded', array( $this, 'process_reset_password' ), 20 );
		add_action( 'wp_loaded', array( $this, 'process_edit_account_details' ), 20 );

		// Reset Password.
		add_filter( 'lostpassword_url', array( $this, 'get_lost_password_url' ), 10, 1 );
		add_action( 'template_redirect', array( $this, 'redirect_reset_password_link' ), 5 );

		// Register Url.
		add_filter( 'register_url', array( $this, 'get_register_url' ), 10, 1 );

		// Login Url.
		add_filter( 'login_url', array( $this, 'get_login_url' ), 10, 3 );

		// Redirect Certain Pages.
		add_action( 'template_redirect', array( $this, 'redirect_account_pages' ) );
		add_action( 'template_redirect', array( $this, 'check_woocommerce_account_conflict' ), 5 );

		// Logout.
		add_action( 'template_redirect', array( $this, 'process_logout' ) );

		// Student Account.
		add_action( 'wpcw_student_account_navigation', 'wpcw_student_account_navigation' );
		add_action( 'wpcw_student_account_content', 'wpcw_student_account_content' );
		add_action( 'wpcw_student_account_courses_endpoint', 'wpcw_student_account_courses' );
		add_action( 'wpcw_student_account_orders_endpoint', 'wpcw_student_account_orders' );
		add_action( 'wpcw_student_account_view-order_endpoint', 'wpcw_student_account_view_order' );
		add_action( 'wpcw_student_account_subscriptions_endpoint', 'wpcw_student_account_subscriptions' );
		add_action( 'wpcw_student_account_view-subscription_endpoint', 'wpcw_student_account_view_subscription' );
		add_action( 'wpcw_student_account_edit-account_endpoint', 'wpcw_student_account_edit_account' );

		// Account Endpoints.
		add_filter( 'wpcw_settings_before_generate_fields_html', array( $this, 'maybe_hide_settings_endpoints' ), 10, 2 );
		add_filter( 'wpcw_student_account_menu_items', array( $this, 'maybe_hide_account_endpoints' ), 10 );

		// Api Endpoints.
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );

		// Account Endpoint Permalinks.
		add_action( 'wpcw_settings_after_save', array( $this, 'maybe_flush_account_endpoint_permalinks' ) );
		add_action( 'wpcw_admin_settings_after', array( $this, 'flush_account_endpoint_permalinks' ) );
	}

	/**
	 * Get Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The students settings fields.
	 */
	public function get_settings_fields() {
		return apply_filters( 'wpcw_students_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'students_section_heading',
				'title' => esc_html__( 'Students', 'wp-courseware' ),
				'desc'  => esc_html__( 'These pages are set to allow students to access account related functionality.', 'wp-courseware' ),
			),
			array(
				'type'     => 'page',
				'key'      => 'account_page',
				'title'    => esc_html__( 'Student Account Page', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'The student account page.', 'wp-courseware' ),
			),
			array(
				'type'  => 'heading',
				'key'   => 'students_account_endpoints_section_heading',
				'title' => esc_html__( 'Student Account Endpoints', 'wp-courseware' ),
				'desc'  => esc_html__( 'Endpoints are appended to the student account URLs to handle specific actions. They should be unique and can be left blank to disable the endpoint.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_courses_endpoint',
				'default'  => 'courses',
				'title'    => esc_html__( 'Courses', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint that shows the courses the student is enrolled.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_orders_endpoint',
				'default'  => 'orders',
				'title'    => esc_html__( 'Orders', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint that shows the student their orders.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_view_order_endpoint',
				'default'  => 'view-order',
				'title'    => esc_html__( 'View Order', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint that shows an individual student order.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_subscriptions_endpoint',
				'default'  => 'subscriptions',
				'title'    => esc_html__( 'Subscriptions', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint that shows the student their subscriptions.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_view_subscription_endpoint',
				'default'  => 'view-subscription',
				'title'    => esc_html__( 'View Subscription', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint that shows an individual student subscription.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_edit_account_endpoint',
				'default'  => 'edit-account',
				'title'    => esc_html__( 'Edit Account', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint that allows students to edit details of their account.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_register_endpoint',
				'default'  => 'register',
				'title'    => esc_html__( 'Register', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint for students to register an account.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_lost_password_endpoint',
				'default'  => 'lost-password',
				'title'    => esc_html__( 'Lost Password', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint for students to recover their password.', 'wp-courseware' ),
			),
			array(
				'type'     => 'text',
				'key'      => 'student_logout_endpoint',
				'default'  => 'student-logout',
				'title'    => esc_html__( 'Logout', 'wp-courseware' ),
				'desc_tip' => esc_html__( 'Endpoint to trigger a logout action for the student You can add this to your menus via custom link: "http://yoursite.com?student-logout=true"', 'wp-courseware' ),
			),
			array(
				'type'  => 'heading',
				'key'   => 'student_account_courses_section_heading',
				'title' => esc_html__( 'Student Account Courses Display Options', 'wp-courseware' ),
				'desc'  => esc_html__( 'The following options apply to the student courses outline displayed on their account page under the "Courses" tab.', 'wp-courseware' ),
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_course_desc',
				'title'    => esc_html__( 'Show Description?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show the course description.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display the course description for each course. Default is disabled.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_course_prerequisites',
				'title'    => esc_html__( 'Show Prerequisites?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show the course prerequisites.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display the course prerequisites for each course. Default is disabled.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_user_progress',
				'title'    => esc_html__( 'Show Progress?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show student course progress.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display a progress bar of the students\'s current course progress. Default is enabled.', 'wp-courseware' ),
				'default'  => 'yes',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_user_grade',
				'title'    => esc_html__( 'Show Overall Grade?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show the students overall grade in the course.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display the students overall grade so far in the course. Default is enabled.', 'wp-courseware' ),
				'default'  => 'yes',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_quiz_grade',
				'title'    => esc_html__( 'Show Quiz Grades?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show the unit quiz grades of the student.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display the students quiz grade if required by the unit. Default is disabled.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_reset_progress',
				'title'    => esc_html__( 'Show Reset Progress?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show the reset progress button.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display a reset progress button that will allow the student to reset their own progress. Default is disabled.', 'wp-courseware' ),
				'default'  => 'no',
			),
			array(
				'type'     => 'checkbox',
				'key'      => 'student_account_courses_show_certificate',
				'title'    => esc_html__( 'Show Certificate?', 'wp-courseware' ),
				'label'    => esc_html__( 'Yes, show the download certificate button.', 'wp-coureware' ),
				'desc_tip' => esc_html__( 'This option, when enabled, will display a certificate button if certificates are enabled and course is complete. Default is disabled.', 'wp-courseware' ),
				'default'  => 'no',
			),
		) );
	}

	/**
	 * Get Students.
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool $raw Optional. Return the raw db data.
	 *
	 * @return array Array of student objects.
	 */
	public function get_students( $args = array(), $raw = false ) {
		$students = array();
		$results  = $this->db->get_students( $args );

		if ( $raw ) {
			return $results;
		}

		if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
			return wp_list_pluck( $results, 'ID' );
		}

		foreach ( $results as $result ) {
			$students[] = new Student( $result );
		}

		return $students;
	}

	/**
	 * Get Number of Students.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 *
	 * @return int The number of students.
	 */
	public function get_students_count( $args = array() ) {
		return $this->db->get_students( $args, true );
	}

	/**
	 * Student Courses.
	 *
	 * @since 4.3.0
	 *
	 * @param int $student_id The student id.
	 */
	public function get_student_courses( $student_id ) {
		if ( 0 === absint( $student_id ) ) {
			return false;
		}

		if ( empty( $this->courses[ $student_id ] ) ) {
			$this->courses[ $student_id ] = WPCW_users_getUserCourseList( $student_id );
		}

		return $this->courses[ $student_id ];
	}

	/**
	 * Get Student Progress Bar.
	 *
	 * @param int $student_id The student id.
	 * @param int $course_id Optional. If you only want to display the bar for the current course id. Default is 0.
	 * @param bool $return_blank Optional. If true, will return a blank string when nothing matches. Default is false.
	 *
	 * @return string $student_progress The student progress bar.
	 */
	public function get_student_progress_bar( $student_id, $course_id = 0, $return_blank = false ) {
		if ( 0 === absint( $student_id ) ) {
			return false;
		}

		$student_progress = '';
		$student_courses  = $this->get_student_courses( $student_id );

		$current_user = wp_get_current_user();

		if ( $student_courses ) {
			if ( $course_id ) {
				foreach ( $student_courses as $course ) {
					if ( is_admin() && ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
						if ( $current_user->ID !== absint( $course->post_author ) ) {
							continue;
						}
					}

					if ( absint( $course_id ) === absint( $course->course_id ) ) {
						$student_progress = WPCW_stats_convertPercentageToBar( $course->course_progress );
					}
				}
			} else {
				foreach ( $student_courses as $course ) {
					if ( is_admin() && ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
						if ( $current_user->ID !== absint( $course->post_author ) ) {
							continue;
						}
					}

					$student_progress .= WPCW_stats_convertPercentageToBar( $course->course_progress, $course->course_title );
				}
			}
		} else {
			$student_progress = ! $return_blank ? esc_html__( 'No associated courses', 'wp-courseware' ) : '';
		}

		if ( empty( $student_progress ) ) {
			$student_progress = ! $return_blank ? esc_html__( 'N/A', 'wp-courseware' ) : '';
		}

		return $student_progress;
	}

	/**
	 * Get Student Primary Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The default fields for the student.
	 */
	public function get_student_primary_fields() {
		return apply_filters( 'wpcw_student_primary_fields', array(
			'first_name' => array(
				'label'        => esc_html__( 'First name', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'First name', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-first' ),
				'autocomplete' => 'given-name',
				'autofocus'    => true,
				'priority'     => 10,
			),
			'last_name'  => array(
				'label'        => esc_html__( 'Last name', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'Last name', 'wp-courseware' ),
				'required'     => true,
				'class'        => array( 'wpcw-form-row-last' ),
				'autocomplete' => 'family-name',
				'priority'     => 20,
			),
			'email'      => array(
				'label'        => esc_html__( 'Email address', 'wp-courseware' ),
				'placeholder'  => esc_attr__( 'Email address', 'wp-courseware' ),
				'required'     => true,
				'type'         => 'email',
				'class'        => array( 'wpcw-form-row-wide' ),
				'validate'     => array( 'email' ),
				'autocomplete' => 'email',
				'priority'     => 30,
			),
		) );
	}

	/**
	 * Get Student Account Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The account fields for the student.
	 */
	public function get_student_account_fields() {
		return apply_filters( 'wpcw_student_account_fields', array(
			'account_username' => array(
				'type'        => 'text',
				'label'       => esc_html__( 'Username', 'wp-courseware' ),
				'placeholder' => esc_attr__( 'Username', 'wp-courseware' ),
				'class'       => array( 'wpcw-form-row-wide' ),
				'required'    => true,
			),
			'account_password' => array(
				'type'        => 'password',
				'label'       => esc_html__( 'Password', 'wp-courseware' ),
				'placeholder' => esc_attr__( 'Password', 'wp-courseware' ),
				'class'       => array( 'wpcw-form-row-wide' ),
				'required'    => true,
			),
		) );
	}

	/**
	 * Get Student Billing Fields.
	 *
	 * @since 4.3.0
	 *
	 * @param string $country The country for which to get the billing fields.
	 *
	 * @return array The billing fields for the student.
	 */
	public function get_student_billing_fields( $country = '' ) {
		return apply_filters( 'wpcw_student_billing_fields', wpcw()->countries->get_billing_address_fields( $country ) );
	}

	/**
	 * Add Billing User Meta Fields.
	 *
	 * @since 4.3.0
	 *
	 * @param \WP_User The WP User Object.
	 */
	public function add_billing_user_meta_fields( $user ) {
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return;
		}

		if ( ! wpcw()->students->account_billing_fields_enabled() ) {
			return;
		}

		$country = get_user_meta( $user->ID, 'billing_country', true );
		$country = ( $country ) ? $country : wpcw()->countries->get_base_country();

		$billing_meta_fields = $this->get_student_billing_fields( $country );
		?>
		<h2><?php esc_html_e( 'Student Billing Fields', 'wp-courseware' ); ?></h2>
		<table class="form-table">
			<?php foreach ( $billing_meta_fields as $key => $field ) :
				$classes = ( is_array( $field['class'] ) ) ? implode( ' ', array_map( 'esc_attr', $field['class'] ) ) : $field['class'];
				$placeholder = ( ! empty( $field['placeholder'] ) ) ? sprintf( ' placeholder="%s"', esc_html( $field['placeholder'] ) ) : '';
				?>
				<tr>
					<th>
						<?php if ( isset( $field['label'] ) ) { ?>
							<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						<?php } ?>
					</th>
					<td>
						<?php if ( ! empty( $field['type'] ) && 'select' === $field['type'] ) : ?>
							<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $classes ); ?>"
							        style="width: 25em;"<?php echo $placeholder; ?>>
								<?php
								$selected = esc_attr( get_user_meta( $user->ID, $key, true ) );
								foreach ( $field['options'] as $option_key => $option_value ) : ?>
									<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $selected, $option_key, true ); ?>><?php echo esc_attr( $option_value ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php elseif ( ! empty( $field['type'] ) && 'country' === $field['type'] ) :
							$countries = wpcw()->countries->get_allowed_countries();
							$default_country = wpcw()->countries->get_base_country();
							?>
							<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $classes ); ?>"
							        style="width: 25em;"<?php echo $placeholder; ?>>
								<?php
								$selected = esc_attr( get_user_meta( $user->ID, $key, true ) );
								$selected = ( $selected ) ? $selected : $default_country;
								foreach ( $countries as $ckey => $cvalue ) : ?>
									<option value="<?php echo esc_attr( $ckey ); ?>" <?php selected( $selected, $ckey, true ); ?>><?php echo esc_attr( $cvalue ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php elseif ( ! empty( $field['type'] ) && 'checkbox' === $field['type'] ) : ?>
							<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1"
							       class="<?php echo esc_attr( $classes ); ?>" <?php checked( (int) get_user_meta( $user->ID, $key, true ), 1, true ); ?> />
						<?php elseif ( ! empty( $field['type'] ) && 'button' === $field['type'] ) : ?>
							<button type="button" id="<?php echo esc_attr( $key ); ?>" class="button <?php echo esc_attr( $classes ); ?>"><?php echo esc_html( $field['text'] ); ?></button>
						<?php else : ?>
							<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"
							       value="<?php echo esc_attr( get_user_meta( $user->ID, $key, true ) ); ?>"
							       class="<?php echo( ! empty( $classes ) ? esc_attr( $classes ) . ' regular-text' : 'regular-text' ); ?>"<?php echo $placeholder; ?>/>
						<?php endif; ?>
						<?php if ( isset( $field['description'] ) ) { ?>
							<br/>
							<span class="description"><?php echo wp_kses_post( $field['description'] ); ?></span>
						<?php } ?>
					</td>
				</tr>
			<?php
			endforeach;
			?>
		</table>
		<?php
	}

	/**
	 * Save Billing User Meta Fields.
	 *
	 * @since 4.3.0
	 *
	 * @param int $user_id The user id.
	 */
	public function save_billing_user_meta_fields( $user_id ) {
		if ( ! wpcw()->students->account_billing_fields_enabled() ) {
			return;
		}

		$fields = $this->get_student_billing_fields();
		foreach ( $fields as $key => $field ) {
			if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
				update_user_meta( $user_id, $key, isset( $_POST[ $key ] ) );
			} elseif ( isset( $_POST[ $key ] ) ) {
				update_user_meta( $user_id, $key, wpcw_clean( $_POST[ $key ] ) );
			}
		}
	}

	/**
	 * Remove student from all courses.
	 *
	 * @since 4.1.0
	 *
	 * @param int $student_id The student id.
	 */
	public function remove_student_from_all_courses( $student_id ) {
		if ( 0 === absint( $student_id ) ) {
			return;
		}

		global $wpdb, $wpcwdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE user_id = %d", $student_id ) );

		WPCW_queue_dripfeed::updateQueueItems_removeUser_fromAllCourses( $student_id );
	}

	/**
	 * Remove Student from Course.
	 *
	 * @since 4.1.0
	 *
	 * @param int $student_id The student id.
	 * @param int $course_id The course id.
	 */
	public function remove_student_from_course( $student_id, $course_id ) {
		if ( 0 === absint( $student_id ) || 0 === absint( $course_id ) ) {
			return;
		}

		global $wpdb, $wpcwdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id = %d", $student_id, $course_id ) );

		WPCW_queue_dripfeed::updateQueueItems_removeUser_fromCourseList( $student_id, array( $course_id ) );
	}

	/**
	 * Can Student Access Course.
	 *
	 * @since 4.6.8
	 *
	 * @param int $course_id The course id.
	 * @param int $student_id The student id. Optional.
	 *
	 * @return bool $can_student_access_course True if student can access the course. False otherwise.
	 */
	public function can_student_access_course( $course_id, $student_id = 0 ) {
		global $wpdb, $wpcwdb;

		$can_student_access_course = false;

		if ( ! $student_id ) {
			$student_id = get_current_user_id();
		}

		if ( $course_id && $student_id ) {
			$student_id = absint( $student_id );
			$course_id  = absint( $course_id );

			$can_student_access_course_query = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpcwdb->user_courses}
				 WHERE user_id = %d
				 AND course_id = %d",
				$student_id,
				$course_id
			) );

			$can_student_access_course = ! empty( $can_student_access_course_query ) ? true : false;
		}

		/**
		 * Filter: Can User Access Course?
		 *
		 * @since 4.6.8
		 *
		 * @param bool $can_student_access_course Can the student access the course?
		 * @param int $course_id The course id.
		 * @param int $student_id The student id.
		 *
		 * @return bool $can_student_access_course Can the student access the course?
		 */
		$can_student_access_course = apply_filters( 'wpcw_courses_canuseraccesscourse', $can_student_access_course, $course_id, $student_id );
		$can_student_access_course = apply_filters( 'wpcw_can_student_access_course', $can_student_access_course, $course_id, $student_id );

		return $can_student_access_course;
	}

	/**
	 * Can Student Access Unit?
	 *
	 * @since 4.6.8
	 *
	 * @param int $unit_id The unit id.
	 * @param int $student_id The student id. Optional.
	 *
	 * @return bool $can_student_access_unit True if student can access the unit. False otherwise.
	 */
	public function can_student_access_unit( $unit_id, $student_id = 0 ) {
		$can_student_access_unit    = false;
		$is_student_access_elevated = false;

		if ( ! $student_id ) {
			$student_id = get_current_user_id();
		}

		// Get Unit.
		$unit = wpcw_get_unit( $unit_id );

		if ( $unit->exists() ) {
			if ( $student_id ) {
				// Admin and Teachers Capability.
				$admins_can_access   = user_can( $student_id, apply_filters( 'wpcw_units_accessible_admin_capability', 'manage_wpcw_settings' ) );
				$teachers_can_access = user_can( $student_id, apply_filters( 'wpcw_units_accessible_minimum_capability', 'view_wpcw_courses' ) );

				// If teachers are not admins, we need to check for authorship.
				if ( ! $admins_can_access && $teachers_can_access ) {
					$unit_post = get_post( $unit_id );
					if ( absint( $student_id ) !== absint( $unit_post->post_author ) ) {
						$teachers_can_access = false;
					}
				}

				if ( $admins_can_access || $teachers_can_access ) {
					$can_student_access_unit    = true;
					$is_student_access_elevated = true;
				}
			}

			// If the student access is not elevated, check manually.
			if ( ! $is_student_access_elevated ) {
				if ( $unit->is_teaser() ) {
					$can_student_access_unit = true;
				}

				if ( ! $can_student_access_unit && $unit->get_parent_course_id() ) {
					if ( wpcw_can_student_access_course( $unit->get_parent_course_id(), $student_id ) ) {
						$can_student_access_unit = true;
					}
				}
			}
		}

		$can_student_access_unit = apply_filters( 'wpcw_can_student_access_unit', $can_student_access_unit, $unit_id, $student_id );

		return $can_student_access_unit;
	}

	/**
	 * Create Students Account Page.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|int $page_id The page to be created.
	 */
	public function create_students_account_page() {
		return wp_insert_post(
			array(
				'post_title'     => esc_html__( 'Account', 'wp-courseware' ),
				'post_content'   => '[wpcw_account]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
				'menu_order'     => 15,
			)
		);
	}

	/**
	 * Shortcode Student Accounts Display.
	 *
	 * @since 4.3.0
	 *
	 * @param array $atts The array of shortcode attributes.
	 */
	public function account_display( $atts = array() ) {
		global $wp;

		if ( ! is_user_logged_in() ) {
			$message = apply_filters( 'wpcw_student_account_message', '' );

			if ( ! empty( $message ) ) {
				wpcw_add_notice( $message );
			}

			// After password reset, add confirmation message.
			if ( ! empty( $_GET['password-reset'] ) ) {
				wpcw_add_notice( __( 'Your password has been reset successfully.', 'wp-courseware' ) );
			}

			if ( isset( $wp->query_vars['lost-password'] ) ) {
				$this->lost_password_display();
			} elseif ( isset( $wp->query_vars['register'] ) ) {
				wpcw_get_template( 'account/form-register.php' );
			} else {
				wpcw_get_template( 'account/form-login.php' );
			}
		} else {
			if ( isset( $wp->query_vars['student-logout'] ) ) {
				wpcw_add_notice( sprintf( __( 'Are you sure you want to log out? <a href="%s">Confirm and log out</a>', 'wp-courseware' ), wpcw_logout_url() ) );
			}

			wpcw_get_template( 'account/account.php' );
		}
	}

	/**
	 * Lost Password Display.
	 *
	 * @since 4.3.0
	 */
	public function lost_password_display() {
		if ( ! empty( $_GET['reset-link-sent'] ) ) {
			return wpcw_get_template( 'account/lost-password-confirmation.php' );
		} elseif ( ! empty( $_GET['show-reset-form'] ) ) {
			if ( isset( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ) && 0 < strpos( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ], ':' ) ) {
				list( $rp_login, $rp_key ) = array_map( 'wpcw_clean', explode( ':', wp_unslash( $_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] ), 2 ) );

				$user = $this->check_lost_password_reset_key( $rp_key, $rp_login );

				if ( is_object( $user ) ) {
					return wpcw_get_template( 'account/form-reset-password.php', array(
						'key'   => $rp_key,
						'login' => $rp_login,
					) );
				}
			}
		}

		wpcw_get_template( 'account/form-lost-password.php' );
	}

	/**
	 * Process Login Form.
	 *
	 * @since 4.3.0
	 */
	public function process_login() {
		$nonce_value = isset( $_POST['wpcw-login-nonce'] ) ? $_POST['wpcw-login-nonce'] : '';

		if ( ! empty( $_POST['login'] ) && wp_verify_nonce( $nonce_value, 'wpcw-login' ) ) {
			try {
				$creds = array(
					'user_login'    => trim( $_POST['login_username'] ),
					'user_password' => $_POST['login_password'],
					'remember'      => isset( $_POST['login_rememberme'] ),
				);

				$validation_error = new WP_Error();
				$validation_error = apply_filters( 'wpcw_process_login_errors', $validation_error, $_POST['login_username'], $_POST['login_password'] );

				if ( $validation_error->get_error_code() ) {
					throw new Exception( '<strong>' . __( 'Error:', 'wp-courseware' ) . '</strong> ' . $validation_error->get_error_message() );
				}

				if ( empty( $creds['user_login'] ) ) {
					throw new Exception( '<strong>' . __( 'Error:', 'wp-courseware' ) . '</strong> ' . __( 'Username is required.', 'wp-courseware' ) );
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$student_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

					if ( $student_data && ! is_user_member_of_blog( $student_data->ID, get_current_blog_id() ) ) {
						add_user_to_blog( get_current_blog_id(), $student_data->ID, 'subscriber' );
					}
				}

				/**
				 * Filter: Login Credentials.
				 *
				 * @since 4.3.0
				 *
				 * @param array $creds The credentials for which to login.
				 */
				$student = wp_signon( apply_filters( 'wpcw_login_credentials', $creds ), is_ssl() );

				if ( is_wp_error( $student ) ) {
					$message = $student->get_error_message();
					$message = str_replace( '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', $message );
					throw new Exception( $message );
				} else {
					if ( ! empty( $_POST['redirect'] ) ) {
						$redirect = $_POST['redirect'];
					} elseif ( wpcw_get_raw_referer() ) {
						$redirect = wpcw_get_raw_referer();
					} else {
						$redirect = wpcw_get_page_permalink( 'account' );
					}

					wp_redirect( wp_validate_redirect( apply_filters( 'wpcw_login_redirect', remove_query_arg( 'wpcw_error', $redirect ), $student ), wpcw_get_page_permalink( 'account' ) ) );
					exit;
				}
			} catch ( Exception $exception ) {
				wpcw_add_notice( apply_filters( 'wpcw_login_errors', $exception->getMessage() ), 'error' );

				/**
				 * Action: Login Failed.
				 *
				 * @since 4.3.0
				 *
				 * @param Exception $exception The exception object.
				 * @param Students $this The students controller.
				 */
				do_action( 'wpcw_login_failed', $exception, $this );
			}
		}
	}

	/**
	 * Process Registration Form.
	 *
	 * @since 4.3.0
	 */
	public function process_registration() {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['wpcw-register-nonce'] ) ? $_POST['wpcw-register-nonce'] : $nonce_value;

		if ( ! empty( $_POST['register'] ) && wp_verify_nonce( $nonce_value, 'wpcw-register' ) ) {
			/**
			 * Filter: Generate Username.
			 *
			 * @since 4.3.0
			 *
			 * @param bool If to generate the username. Default is False.
			 *
			 * @return bool True or false to determine if the username should be generated.
			 */
			$username = apply_filters( 'wpcw_registration_generate_username', false ) ? '' : $_POST['reg_username'];

			/**
			 * Filter: Generate Password.
			 *
			 * @since 4.3.0
			 *
			 * @param bool If to generate the password. Default is False.
			 *
			 * @return bool True or false to determine if the password should be generated.
			 */
			$password = apply_filters( 'wpcw_registration_generate_password', false ) ? '' : $_POST['reg_password'];

			$email = isset( $_POST['reg_email'] ) ? $_POST['reg_email'] : '';

			$courses = isset( $_POST['course_id'] ) ? array_map( 'absint', $_POST['course_id'] ) : '';

			try {
				$validation_error = new WP_Error();

				/**
				 * Filter: Process Registration Errors.
				 *
				 * @since 4.3.0
				 *
				 * @param WP_Error $validation_error The WP_Error object.
				 * @param string $username The username string.
				 * @param string $password The password string.
				 * @param string $email The email string.
				 *
				 * @return WP_Error $validation_error The validation error.
				 */
				$validation_error = apply_filters( 'wpcw_process_registration_errors', $validation_error, $username, $password, $email );

				/** @var WP_Error $validation_error */
				if ( $validation_error->get_error_codes() ) {
					throw new Exception( json_encode( $validation_error->get_error_messages() ) );
				}

				$new_student = wpcw_create_new_student( sanitize_email( $email ), wpcw_clean( $username ), $password, $courses );

				if ( is_wp_error( $new_student ) ) {
					throw new Exception( json_encode( $new_student->get_error_messages() ) );
				}

				/**
				 * Filter: Registration - Authorize new student.
				 *
				 * Allows the user to be logged in automatically.
				 *
				 * @since 4.3.0
				 *
				 * @param bool If it should be allowed to auth the new student. Default is true.
				 * @param WP_User $new_student The new student user object.
				 *
				 * @return bool True to enable. False to disable. Default is true.
				 */
				if ( apply_filters( 'wpcw_registration_auth_new_student', true, $new_student ) ) {
					wpcw_set_student_auth_cookie( $new_student );
				}

				if ( ! empty( $_POST['redirect'] ) ) {
					$redirect = wp_sanitize_redirect( $_POST['redirect'] );
				} elseif ( wpcw_get_raw_referer() ) {
					$redirect = wpcw_get_raw_referer();
				} else {
					$redirect = wpcw_get_page_permalink( 'account' );
				}

				$redirect = remove_query_arg( array( 'course_id', '_wp_enroll' ), $redirect );

				/**
				 * Filter: Registration Redirect.
				 *
				 * @since 4.3.0
				 *
				 * @param string $redirect The redirect url.
				 */
				wp_redirect( wp_validate_redirect( apply_filters( 'wpcw_registration_redirect', $redirect ), wpcw_get_page_permalink( 'account' ) ) );
				exit;
			} catch ( Exception $exception ) {
				$data = json_decode( $exception->getMessage(), true );
				if ( is_array( $data ) ) {
					foreach ( $data as $error ) {
						wpcw_add_notice( '<strong>' . __( 'Error:', 'wp-courseware' ) . '</strong> ' . $error, 'error' );
					}
				} else {
					wpcw_add_notice( '<strong>' . __( 'Error:', 'wp-courseware' ) . '</strong> ' . $exception->getMessage(), 'error' );
				}
			}
		}
	}

	/**
	 * Process Lost Password Form.
	 *
	 * @since 4.3.0
	 */
	public function process_lost_password() {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['wpcw-lost-password-nonce'] ) ? $_POST['wpcw-lost-password-nonce'] : $nonce_value;

		if ( isset( $_POST['wpcw_lost_password'] ) && isset( $_POST['user_login'] ) && wp_verify_nonce( $nonce_value, 'wpcw-lost-password' ) ) {
			$success = $this->retrieve_password();

			// If successful, redirect to my account with query arg set.
			if ( $success ) {
				wp_redirect( add_query_arg( 'reset-link-sent', 'true', wpcw_get_student_account_endpoint_url( 'lost-password' ) ) );
				exit;
			}
		}
	}

	/**
	 * Retrieve Password.
	 *
	 * Handles sending password retrieval email to student.
	 *
	 * Based on retrieve_password() in core wp-login.php.
	 *
	 * @return bool True: when finish. False: on error
	 * @uses $wpdb WordPress Database object
	 *
	 */
	public function retrieve_password() {
		$login = isset( $_POST['user_login'] ) ? trim( $_POST['user_login'] ) : '';

		if ( empty( $login ) ) {
			wpcw_add_notice( __( 'Enter a username or email address.', 'wp-courseware' ), 'error' );

			return false;
		} else {
			$student_data = get_user_by( 'login', $login );
		}

		/**
		 * Filter: Get username from email.
		 *
		 * If no student found, check if it login is email and lookup user based on email.
		 *
		 * @since 4.3.0
		 *
		 * @param bool True to get username from email. False otherwise. Default is true.
		 *
		 * @return bool True to get username from email. False otherwise. Default is true.
		 */
		if ( ! $student_data && is_email( $login ) && apply_filters( 'wpcw_get_username_from_email', true ) ) {
			$student_data = get_user_by( 'email', $login );
		}

		$errors = new WP_Error();

		/**
		 * Action: Lost Password Post
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Error $errors The WP_Error object.
		 */
		do_action( 'lostpassword_post', $errors );

		if ( $errors->get_error_code() ) {
			wpcw_add_notice( $errors->get_error_message(), 'error' );

			return false;
		}

		if ( ! $student_data ) {
			wpcw_add_notice( __( 'Invalid username or email.', 'wp-courseware' ), 'error' );

			return false;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $student_data->ID, get_current_blog_id() ) ) {
			wpcw_add_notice( __( 'Invalid username or email.', 'wp-courseware' ), 'error' );

			return false;
		}

		// Redefining user_login ensures we return the right case in the email.
		$student_login = $student_data->user_login;

		/**
		 * Action: Retrieve Password.
		 *
		 * @since 4.3.0
		 *
		 * @param string $student_login The student login.
		 */
		do_action( 'retrieve_password', $student_login );

		/**
		 * Filter: Allow password reset.
		 *
		 * @since 4.3.0
		 *
		 * @param bool True to allow. False otherwise.
		 * @param int The student data id.
		 *
		 * @return bool TRue to allow. False otherwise.
		 */
		$allow = apply_filters( 'allow_password_reset', true, $student_data->ID );

		if ( ! $allow ) {
			wpcw_add_notice( __( 'Password reset is not allowed for this user', 'wp-courseware' ), 'error' );

			return false;
		} elseif ( is_wp_error( $allow ) ) {
			wpcw_add_notice( $allow->get_error_message(), 'error' );

			return false;
		}

		// Get password reset key (function introduced in WordPress 4.4).
		$reset_key = get_password_reset_key( $student_data );

		/**
		 * Action: Reset Password.
		 *
		 * Used to trigger emails associated.
		 *
		 * @since 4.3.0
		 *
		 * @param string $student_login The student login.
		 * @param string $reset_key The reset key.
		 */
		do_action( 'wpcw_reset_password', $student_login, $reset_key );

		return true;
	}

	/**
	 * Process Reset Password Form.
	 *
	 * @since 4.3.0
	 */
	public function process_reset_password() {
		$posted_fields = array( 'wpcw_reset_password', 'password_1', 'password_2', 'reset_key', 'reset_login', 'wpcw-reset-password-nonce' );

		foreach ( $posted_fields as $field ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				return;
			}

			$posted_fields[ $field ] = $_POST[ $field ];
		}

		if ( ! wp_verify_nonce( $posted_fields['wpcw-reset-password-nonce'], 'wpcw-reset-password' ) ) {
			return;
		}

		$student = $this->check_lost_password_reset_key( $posted_fields['reset_key'], $posted_fields['reset_login'] );

		if ( $student instanceof WP_User ) {
			if ( empty( $posted_fields['password_1'] ) ) {
				wpcw_add_notice( __( 'Please enter your password.', 'wp-courseware' ), 'error' );
			}

			if ( $posted_fields['password_1'] !== $posted_fields['password_2'] ) {
				wpcw_add_notice( __( 'Passwords do not match.', 'wp-courseware' ), 'error' );
			}

			$errors = new WP_Error();

			/**
			 * Action: Validate Password Reset.
			 *
			 * @since 4.3.0
			 *
			 * @param WP_Error $errors The WP Error object.
			 * @param WP_User $student The student WP_User object.
			 */
			do_action( 'validate_password_reset', $errors, $student );

			wpcw_add_wp_error_notices( $errors );

			if ( 0 === wpcw_notice_count( 'error' ) ) {
				$this->reset_password( $student, $posted_fields['password_1'] );

				/**
				 * Action: Student Reset Password.
				 *
				 * @since 4.3.0
				 *
				 * @param WP_User $student The student wp user object.
				 */
				do_action( 'wpcw_student_reset_password', $student );

				wp_redirect( add_query_arg( 'password-reset', 'true', wpcw_get_page_permalink( 'account' ) ) );
				exit;
			}
		}
	}

	/**
	 * Reset Password.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_User $student The student user object.
	 * @param string $new_pass The new password string.
	 */
	public function reset_password( $student, $new_pass ) {
		/**
		 * Action: Password Reset.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_User The student wp user object.
		 * @param string $new_pass The new password string.
		 */
		do_action( 'password_reset', $student, $new_pass );

		wp_set_password( $new_pass, $student->ID );

		$this->set_reset_password_cookie();

		wp_password_change_notification( $student );
	}

	/**
	 * Set Reset Password Cookie.
	 *
	 * @since 4.3.0
	 *
	 * @param string $value The reset password cookie value.
	 */
	public function set_reset_password_cookie( $value = '' ) {
		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
		$rp_path   = current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

		if ( $value ) {
			setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		} else {
			setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Check lost password reset key.
	 *
	 * Retrieves a user row based on password reset key and login.
	 *
	 * @param string $key Hash to validate sending user's password.
	 * @param string $login The user login.
	 *
	 * @return WP_User|bool User's database row on success, false for invalid keys
	 * @uses $wpdb WordPress Database object
	 *
	 */
	public function check_lost_password_reset_key( $key, $login ) {
		$user = get_user_by( 'ID', $login );

		if ( ! empty( $user ) ) {
			$login = $user->user_login;
		}

		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			wpcw_add_notice( __( 'This key is invalid or has already been used. Please reset your password again if needed.', 'wp-courseware' ), 'error' );

			return false;
		}

		return $user;
	}

	/**
	 * Get Lost Passwordd Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $default_url The default url to use.
	 *
	 * @return string The lost password url.
	 */
	public function get_lost_password_url( $default_url = '' ) {
		// Avoid loading too early.
		if ( ! did_action( 'init' ) ) {
			return $default_url;
		}

		// Don't redirect to the wpcw endpoint on global network admin lost passwords.
		if ( is_multisite() && isset( $_GET['redirect_to'] ) && false !== strpos( wp_unslash( $_GET['redirect_to'] ), network_admin_url() ) ) {
			return $default_url;
		}

		if ( ! wpcw_is_account_page() && ! wpcw_is_checkout() ) {
			return $default_url;
		}

		$account_page_url       = wpcw_get_page_permalink( 'account' );
		$account_page_id        = wpcw_get_page_id( 'account' );
		$account_page_exists    = wpcw_page_exists( $account_page_id );
		$lost_password_endpoint = wpcw_get_setting( 'student_lost_password_endpoint', 'lost-password' );

		if ( $account_page_exists && ! empty( $lost_password_endpoint ) ) {
			return wpcw_get_endpoint_url( $lost_password_endpoint, '', $account_page_url );
		} else {
			return $default_url;
		}
	}

	/**
	 * Get Register Url
	 *
	 * @since 4.3.0
	 *
	 * @param string $default_url The default url to use.
	 *
	 * @return string The register password url.
	 */
	public function get_register_url( $default_url = '' ) {
		// Avoid loading too early.
		if ( ! did_action( 'init' ) ) {
			return $default_url;
		}

		// Don't redirect to the wpcw endpoint on global network admin lost passwords.
		if ( is_multisite() && isset( $_GET['redirect_to'] ) && false !== strpos( wp_unslash( $_GET['redirect_to'] ), network_admin_url() ) ) {
			return $default_url;
		}

		$account_page_url    = wpcw_get_page_permalink( 'account' );
		$account_page_id     = wpcw_get_page_id( 'account' );
		$account_page_exists = wpcw_page_exists( $account_page_id );
		$register_endpoint   = wpcw_get_setting( 'student_register_endpoint', 'register' );

		if ( $account_page_exists && ! empty( $register_endpoint ) ) {
			return wpcw_get_endpoint_url( $register_endpoint, '', $account_page_url );
		} else {
			return $default_url;
		}
	}

	/**
	 * Get Register Url
	 *
	 * @since 4.3.0
	 *
	 * @param string $default_url The default url to use.
	 *
	 * @return string The register password url.
	 */
	public function get_login_url( $login_url = '', $redirect = '', $force_reauth = false ) {
		// Avoid loading too early.
		if ( ! did_action( 'init' ) ) {
			return $login_url;
		}

		if ( ! wpcw_is_account_page() && ! wpcw_is_checkout() ) {
			return $login_url;
		}

		// Don't redirect to the wpcw endpoint on global network admin lost passwords.
		if ( is_multisite() && isset( $_GET['redirect_to'] ) && false !== strpos( wp_unslash( $_GET['redirect_to'] ), network_admin_url() ) ) {
			return $login_url;
		}

		$account_page_url = wpcw_get_page_permalink( 'account' );

		if ( ! empty( $redirect ) ) {
			$account_page_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $account_page_url );
		}

		if ( $force_reauth ) {
			$account_page_url = add_query_arg( 'reauth', '1', $account_page_url );
		}

		if ( $account_page_url ) {
			return $account_page_url;
		} else {
			return $login_url;
		}
	}

	/**
	 * Redirect Reset password Link.
	 *
	 * @since 4.3.0
	 */
	public function redirect_reset_password_link() {
		if ( wpcw_is_account_page() && ! empty( $_GET['key'] ) && ! empty( $_GET['login'] ) ) {
			$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
			$this->set_reset_password_cookie( $value );

			wp_safe_redirect( add_query_arg( 'show-reset-form', 'true', $this->get_lost_password_url() ) );
			exit;
		}
	}

	/**
	 * Process Edit Account Details.
	 *
	 * @since 4.3.0
	 */
	public function process_edit_account_details() {
		$nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
		$nonce_value = isset( $_POST['wpcw-account-details-nonce'] ) ? $_POST['wpcw-account-details-nonce'] : $nonce_value;

		if ( empty( $_POST['account_details'] ) || empty( $_POST['action'] ) || 'account_details' !== $_POST['action'] || ! wp_verify_nonce( $nonce_value, 'wpcw-account-details' ) ) {
			return;
		}

		wpcw_nocache_headers();

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$current_user       = get_user_by( 'id', $user_id );
		$current_first_name = $current_user->first_name;
		$current_last_name  = $current_user->last_name;
		$current_email      = $current_user->user_email;

		$account_first_name = ! empty( $_POST['account_first_name'] ) ? wpcw_clean( $_POST['account_first_name'] ) : '';
		$account_last_name  = ! empty( $_POST['account_last_name'] ) ? wpcw_clean( $_POST['account_last_name'] ) : '';
		$account_email      = ! empty( $_POST['account_email'] ) ? wpcw_clean( $_POST['account_email'] ) : '';
		$pass_cur           = ! empty( $_POST['password_current'] ) ? $_POST['password_current'] : '';
		$pass1              = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : '';
		$pass2              = ! empty( $_POST['password_2'] ) ? $_POST['password_2'] : '';
		$save_pass          = true;

		$user             = new stdClass();
		$user->ID         = $user_id;
		$user->first_name = $account_first_name;
		$user->last_name  = $account_last_name;

		// Set Student.
		$student = new Student( $user_id );

		// Prevent emails being displayed, or leave alone.
		$user->display_name = is_email( $current_user->display_name ) ? $user->first_name : $current_user->display_name;

		// Billing Country.
		if ( ! empty( $_POST['billing_country'] ) ) {
			$address = wpcw()->countries->get_billing_address_fields( esc_attr( $_POST['billing_country'] ), 'billing_' );
			foreach ( $address as $key => $field ) {
				if ( ! isset( $field['type'] ) ) {
					$field['type'] = 'text';
				}

				switch ( $field['type'] ) {
					case 'checkbox' :
						$_POST[ $key ] = (int) isset( $_POST[ $key ] );
						break;
					default :
						$_POST[ $key ] = isset( $_POST[ $key ] ) ? wpcw_clean( $_POST[ $key ] ) : '';
						break;
				}

				// Hook to allow modification of value.
				$_POST[ $key ] = apply_filters( 'wpcw_process_student_account_field_' . $key, $_POST[ $key ] );

				// Validation: Required fields.
				if ( ! empty( $field['required'] ) && empty( $_POST[ $key ] ) ) {
					wpcw_add_notice( sprintf( __( '%s is a required field.', 'wp-courseware' ), $field['label'] ), 'error' );
				} else {
					if ( $student ) {
						$student->set_prop( $key, wpcw_clean( $_POST[ $key ] ) );
					}
				}
			}
		}

		// Handle required fields.
		$required_fields = apply_filters( 'wpcw_save_student_account_details_required_fields', array(
			'account_first_name' => __( 'First name', 'wp-courseware' ),
			'account_last_name'  => __( 'Last name', 'wp-courseware' ),
			'account_email'      => __( 'Email address', 'wp-courseware' ),
		) );

		foreach ( $required_fields as $field_key => $field_name ) {
			if ( empty( $_POST[ $field_key ] ) ) {
				wpcw_add_notice( sprintf( __( '%s is a required field.', 'wp-courseware' ), '<strong>' . esc_html( $field_name ) . '</strong>' ), 'error' );
			}
		}

		if ( $account_email ) {
			$account_email = sanitize_email( $account_email );
			if ( ! is_email( $account_email ) ) {
				wpcw_add_notice( __( 'Please provide a valid email address.', 'wp-courseware' ), 'error' );
			} elseif ( email_exists( $account_email ) && $account_email !== $current_user->user_email ) {
				wpcw_add_notice( __( 'This email address is already registered.', 'wp-courseware' ), 'error' );
			}
			$user->user_email = $account_email;
		}

		if ( ! empty( $pass_cur ) && empty( $pass1 ) && empty( $pass2 ) ) {
			wpcw_add_notice( __( 'Please fill out all password fields.', 'wp-courseware' ), 'error' );
			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && empty( $pass_cur ) ) {
			wpcw_add_notice( __( 'Please enter your current password.', 'wp-courseware' ), 'error' );
			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && empty( $pass2 ) ) {
			wpcw_add_notice( __( 'Please re-enter your password.', 'wp-courseware' ), 'error' );
			$save_pass = false;
		} elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
			wpcw_add_notice( __( 'New passwords do not match.', 'wp-courseware' ), 'error' );
			$save_pass = false;
		} elseif ( ! empty( $pass1 ) && ! wp_check_password( $pass_cur, $current_user->user_pass, $current_user->ID ) ) {
			wpcw_add_notice( __( 'Your current password is incorrect.', 'wp-courseware' ), 'error' );
			$save_pass = false;
		}

		if ( $pass1 && $save_pass ) {
			$user->user_pass = $pass1;
		}

		// Allow plugins to return their own errors.
		$errors = new WP_Error();
		do_action_ref_array( 'wpcw_save_student_account_details_errors', array( &$errors, &$user ) );

		if ( $errors->get_error_messages() ) {
			foreach ( $errors->get_error_messages() as $error ) {
				wpcw_add_notice( $error, 'error' );
			}
		}

		if ( wpcw_notice_count( 'error' ) === 0 ) {
			wp_update_user( $user );

			if ( $student ) {
				// Keep billing data in sync if data changed.
				if ( is_email( $user->user_email ) && $current_email !== $user->user_email ) {
					$student->set_prop( 'user_email', $user->user_email );
				}

				if ( $current_first_name !== $user->first_name ) {
					$student->set_prop( 'first_name', $user->first_name );
				}

				if ( $current_last_name !== $user->last_name ) {
					$student->set_prop( 'last_name', $user->last_name );
				}

				// Save Student.
				$student->save();
			}

			wpcw_add_notice( __( 'Student Account details updated successfully.', 'wp-courseware' ) );

			/**
			 * Action: Save Student Account Details.
			 *
			 * @since 4.3.0
			 *
			 * @param int $user_id The student user id.
			 */
			do_action( 'wpcw_save_student_account_details', $user->ID );

			wp_safe_redirect( wpcw_get_student_account_endpoint_url( 'edit-account' ) );
			exit;
		}
	}

	/**
	 * Redirect Account Pages.
	 *
	 * @since 4.3.0
	 */
	public function redirect_account_pages() {
		global $wp;

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( wpcw_is_endpoint_url( 'register' ) || wpcw_is_endpoint_url( 'lost-password' ) ) {
			wp_safe_redirect( wpcw_get_page_permalink( 'account' ) );
			exit;
		}
	}

	/**
	 * Check WooCommerce Account Conflict..
	 *
	 * @since 4.6.1
	 */
	public function check_woocommerce_account_conflict() {
		if ( function_exists( 'wc_template_redirect' ) && wpcw_is_endpoint_url() && wpcw_is_account_page() ) {
			add_filter( 'woocommerce_is_account_page', '__return_true' );
		}
	}

	/**
	 * Process Logout.
	 *
	 * @since 4.3.0
	 */
	public function process_logout() {
		global $wp_query, $wp;

		if ( isset( $wp->query_vars['student-logout'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'student-logout' ) ) {
			wp_safe_redirect( str_replace( '&amp;', '&', wp_logout_url( wpcw_get_page_permalink( 'account' ) ) ) );
			exit;
		} elseif ( isset( $wp->query_vars['student-logout'] ) && 'true' === $wp->query_vars['student-logout'] ) {
			wp_safe_redirect( esc_url_raw( wpcw_get_student_account_endpoint_url( 'student-logout' ) ) );
			exit;
		}
	}

	/**
	 * Account Billing Fields Enabled?
	 *
	 * @since 4.3.0
	 */
	public function account_billing_fields_enabled() {
		$enabled = true;

		if ( ! wpcw_is_ecommerce_enabled() ) {
			$enabled = false;
		}

		return apply_filters( 'wpcw_student_account_billing_fields_enabled', $enabled );
	}

	/** API Methods -------------------------------------------------- */

	/**
	 * Register Course Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The endpoints to filter.
	 * @param Api The api object reference.
	 *
	 * @return array $endpoints The modified array of endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array( 'endpoint' => 'email-student', 'method' => 'POST', 'callback' => array( $this, 'api_email_student' ) );
		$endpoints[] = array( 'endpoint' => 'email-students', 'method' => 'POST', 'callback' => array( $this, 'api_email_students' ) );
		$endpoints[] = array( 'endpoint' => 'enrollment-users', 'method' => 'GET', 'callback' => array( $this, 'api_get_students' ) );
		$endpoints[] = array( 'endpoint' => 'students', 'method' => 'GET', 'callback' => array( $this, 'api_get_students' ) );
		$endpoints[] = array( 'endpoint' => 'add-student', 'method' => 'POST', 'callback' => array( $this, 'api_add_student' ) );
		$endpoints[] = array( 'endpoint' => 'update-student-progress', 'method' => 'POST', 'callback' => array( $this, 'api_update_student_progress' ) );

		return $endpoints;
	}

	/**
	 * Api: Email Student - Singular
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_email_student( WP_REST_Request $request ) {
		$student 	= $request->get_param( 'student' );
		$subject 	= $request->get_param( 'subject' );
		$message 	= $request->get_param( 'message' );
		$course_id 	= $request->get_param( 'course' );

		if ( ! $student ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing information about the student. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		if ( ! $subject ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the email subject. Please enter an email subject.', 'wp-courseware' ),
			) );
		}

		if ( ! $message ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the email message. Please enter an email message.', 'wp-courseware' ),
			) );
		}

		$success         = true;
		$success_message = esc_html__( 'Email sent successfully!', 'wp-courseware' );

		if ( is_array( $student ) ) {
			$student_name  = esc_attr( $student['name'] );
			$student_email = esc_attr( $student['email'] );
		} elseif ( is_numeric( $student ) ) {
			$student = new Student( $student );

			if ( $student && $student instanceof Student ) {
				$student_name  = $student->get_display_name();
				$student_email = $student->get_user_email();
			}
		}

		if ( ! $student_name || ! $student_email ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'Sorry, the student you are trying to send an email to does not exist. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		if ( $course_id ){
			$course = new \WPCW\Models\Course( $course_id );
			$course_author = $course->get_course_author();
			$instructor = get_userdata( $course_author );
			$instructor_name = $instructor->display_name;
			$instructor_email = $instructor->user_email;
			$headers = "Content-Type: text/html; charset=UTF-8\r\n";
			$headers .= "From: {$instructor_name} <{$instructor_email}>\r\n";
		}else{
			$headers = 'Content-Type: text/html; charset=UTF-8';
		}



		$to      = esc_attr( $student_email );
		$subject = wp_kses_post( $subject );
		$message = wpautop( wptexturize( wp_kses_post( $message ) ) );

		wp_mail( $to, $subject, $message, $headers );

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}

	/**
	 * Api: Email Students.
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_email_students( WP_REST_Request $request ) {
		$course_id = $request->get_param( 'course' );
		$subject   = $request->get_param( 'subject' );
		$message   = $request->get_param( 'message' );

		if ( ! $course_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the course id. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		if ( ! $subject ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the email subject. Please enter an email subject.', 'wp-courseware' ),
			) );
		}

		if ( ! $message ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing the email message. Please enter an email message.', 'wp-courseware' ),
			) );
		}

		$success         = true;
		$success_message = esc_html__( 'Email sent successfully!', 'wp-courseware' );

		$students = $this->get_students( array( 'course_id' => $course_id, 'number' => - 1 ) );
		$course = new \WPCW\Models\Course( $course_id );
		$course_author = $course->get_course_author();
		$instructor = get_userdata( $course_author );
		$instructor_name = $instructor->display_name;
		$instructor_email = $instructor->user_email;
		

		if ( $students ) {
			foreach ( $students as $student ) {
				if ( ! $student instanceof Student ) {
					continue;
				}

				$student_name  = $student->get_display_name();
				$student_email = $student->get_user_email();

				if ( ! $student_name || ! $student_email ) {
					continue;
				}

				$to      = esc_attr( $student_email );
				$subject = wp_kses_post( $subject );
				$message = wpautop( wptexturize( wp_kses_post( $message ) ) );

				$headers = "Content-Type: text/html; charset=UTF-8\r\n";
				$headers .= "From: {$instructor_name} <{$instructor_email}>\r\n";

				wp_mail( $to, $subject, $message, $headers );
			}
		}

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}

	/**
	 * Api: Get Students
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_get_students( WP_REST_Request $request ) {
		$search = $request->get_param( 'search' );
		$number = $request->get_param( 'number' );

		if ( ! $search ) {
			$search = '';
		}

		if ( ! $number ) {
			$number = 1000;
		}

		$results    = array();
		$query_args = array( 'search' => '*' . esc_attr( $search ) . '*', 'number' => $number );

		$user_query = new WP_User_Query( $query_args );

		$users = $user_query->get_results();
		$count = $user_query->get_total();

		foreach ( $users as $user ) {
			$results[] = array(
				'id'      => $user->ID,
				'name'    => $user->display_name,
				'display' => sprintf( '%s (%s)', $user->display_name, $user->user_email ),
				'email'   => $user->user_email,
			);
		}

		return rest_ensure_response( array( 'students' => $results ) );
	}

	/**
	 * Api: Add Student
	 *
	 * @since 4.1.0
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_add_student( WP_REST_Request $request ) {
		$method       = $request->get_param( 'method' );
		$username     = $request->get_param( 'username' );
		$email        = $request->get_param( 'email' );
		$password     = $request->get_param( 'password' );
		$first        = $request->get_param( 'first' );
		$last         = $request->get_param( 'last' );
		$notification = $request->get_param( 'notification' );
		$existing     = $request->get_param( 'existing' );
		$courses      = $request->get_param( 'courses' );

		if ( ! current_user_can( 'create_users' ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are not allowed to add users.', 'wp-courseware' ),
			) );
		}

		if ( ! $method ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You did not select a method.', 'wp-courseware' ),
			) );
		}

		if ( 'new' === $method && ( ! $username || ! $email || ! $password || ! $first || ! $last ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are missing some required fields to add a new user. Please check your fields and try again.', 'wp-courseware' ),
			) );
		}

		if ( 'existing' === $method && ! $existing ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You forgot to select an existing user. Please select an existing user.', 'wp-courseware' ),
			) );
		}

		if ( 'new' === $method ) {
			if ( username_exists( $username ) ) {
				return rest_ensure_response( array(
					'success' => false,
					'message' => sprintf( __( 'A user with username <strong>%s</strong> already exists. Please set and new username.', 'wp-courseware' ), $username ),
				) );
			}

			if ( email_exists( $email ) ) {
				return rest_ensure_response( array(
					'success' => false,
					'message' => sprintf( __( 'A user with the email <strong>%s</strong> already exists. Please choose a different email.', 'wp-courseware' ), $email ),
				) );
			}

			$user_id = wp_insert_user( array(
				'user_pass'    => $password,
				'user_login'   => $username,
				'user_email'   => $email,
				'display_name' => sprintf( '%s %s', $first, $last ),
				'first_name'   => $first,
				'last_name'    => $last,
			) );

			if ( is_wp_error( $user_id ) ) {
				return rest_ensure_response( array(
					'success' => false,
					'message' => $user_id->get_error_message(),
				) );
			}

			if ( $notification ) {
				wp_new_user_notification( $user_id, null, 'both' );
			}
		}

		if ( 'existing' === $method ) {
			$user_id = $existing;
		}

		if ( ! empty( $user_id ) && ! empty( $courses ) ) {
			wpcw()->enrollment->enroll_student( $user_id, $courses );
		}

		$success         = true;
		$success_message = esc_html__( 'Student added successfully!', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}

	/**
	 * Api: Update Student Progress.
	 *
	 * @since 4.5.1
	 *
	 * @param object \WP_REST_Request The api request.
	 *
	 * @return object \WP_REST_Response The api response.
	 */
	public function api_update_student_progress( WP_REST_Request $request ) {
		global $wpdb, $wpcwdb;

		$course_id  = $request->get_param( 'course_id' );
		$student_id = $request->get_param( 'student_id' );

		if ( ! current_user_can( 'create_users' ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'You are not allowed to update student progress.', 'wp-courseware' ),
			) );
		}

		if ( ! $student_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'There was no student specified. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		if ( ! $course_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'There was no course specified. Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		// Update Progress.
		if ( ! wpcw_update_student_progress( $student_id, $course_id ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => esc_html__( 'Student Progress could not be updated! Please refresh and try again.', 'wp-courseware' ),
			) );
		}

		$success         = true;
		$success_message = esc_html__( 'Student progress updated successfully! Refreshing...', 'wp-courseware' );

		return rest_ensure_response( array( 'success' => $success, 'message' => $success_message ) );
	}

	/** Misc Methods -------------------------------------------------- */

	/**
	 * Maybe Hide Settings Endpoints.
	 *
	 * @since 4.4.0
	 *
	 * @param array $fields The settings fields.
	 * @param Settings_Api $settings_api The settings api.
	 *
	 * @return array $fields The settings fields.
	 */
	public function maybe_hide_settings_endpoints( $fields, $settings_api ) {
		$tab = wpcw_get_var( 'tab' );

		if ( 'students' === $tab ) {
			if ( ! wpcw_is_ecommerce_enabled() ) {
				$exclude_fields = array(
					'student_view_subscription_endpoint',
					'student_subscriptions_endpoint',
					'student_view_order_endpoint',
					'student_orders_endpoint',
				);

				foreach ( $fields as $id => $field ) {
					$field_key = isset( $field['key'] ) ? $field['key'] : '';
					if ( in_array( $field_key, $exclude_fields ) ) {
						unset( $fields[ $id ] );
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Maybe Hide Account Endpoints.
	 *
	 * @since 4.4.0
	 *
	 * @param array $menu_items The account endpoints.
	 */
	public function maybe_hide_account_endpoints( $menu_items ) {
		if ( ! wpcw_is_ecommerce_enabled() ) {
			$disabled_endpoints = array(
				'orders',
				'subscriptions',
			);

			foreach ( $menu_items as $menu_item_id => $menu_item ) {
				if ( in_array( $menu_item_id, $disabled_endpoints ) ) {
					unset( $menu_items[ $menu_item_id ] );
				}
			}
		}

		return $menu_items;
	}

	/**
	 * Flush Account Endpoint Permalinks.
	 *
	 * @since 4.5.1
	 */
	public function maybe_flush_account_endpoint_permalinks() {
		if ( ! is_admin() ) {
			return;
		}

		$post_data = $_POST;

		if ( ! isset( $post_data['wpcw-form-submit'] ) ) {
			return;
		}

		if ( ! current_user_can( apply_filters( 'wpcw_admin_page_form_process_capability', 'manage_options' ) ) ) {
			return;
		}

		if ( isset( $post_data['action'] ) && $post_data['action'] === 'wpcw-update-students' && wp_verify_nonce( $post_data['nonce'], 'wpcw-students-nonce' ) ) {
			$endpoint_changed = false;

			$endpoints = array(
				'order-received'    => 'order_received_endpoint',
				'order-failed'      => 'order_failed_endpoint',
				'courses'           => 'student_courses_endpoint',
				'orders'            => 'student_orders_endpoint',
				'view-order'        => 'student_view_order_endpoint',
				'subscriptions'     => 'student_subscriptions_endpoint',
				'view-subscription' => 'student_view_subscription_endpoint',
				'register'          => 'student_register_endpoint',
				'edit-account'      => 'student_edit_account_endpoint',
				'lost-password'     => 'student_lost_password_endpoint',
				'student-logout'    => 'student_logout_endpoint',
			);

			// Query Vars.
			$query_vars = wpcw()->query->get_query_vars();

			foreach ( $endpoints as $query_var => $endpoint ) {
				$setting      = isset( $query_vars[ $query_var ] ) ? $query_vars[ $query_var ] : '';
				$post_setting = isset( $post_data[ $endpoint ] ) ? $post_data[ $endpoint ] : '';

				if ( $post_setting && $setting !== $post_setting ) {
					$endpoint_changed = true;
					break;
				}
			}

			if ( $endpoint_changed ) {
				// Enable Flush Rewrite Rules Flag.
				wpcw_enable_flush_rewrite_rules_flag();
			}
		}
	}

	/**
	 * Flush Account Endpoint Permalinks.
	 *
	 * @since 4.5.1
	 *
	 * @param Page_Settings $page_settings The settings page.
	 */
	public function flush_account_endpoint_permalinks( $page_settings ) {
		if ( 'students' === $page_settings->get_current_tab_slug() ) {
			/**
			 * Flush Rewrite Rules.
			 *
			 * @since 4.5.1
			 */
			do_action( 'wpcw_flush_rewrite_rules' );
		}
	}
}
