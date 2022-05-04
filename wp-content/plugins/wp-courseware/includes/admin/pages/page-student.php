<?php
/**
 * WP Courseware Student Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Models\Course;
use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Student.
 *
 * @since 4.1.0
 */
class Page_Student extends Page {

	/**
	 * @var Student The student object.
	 * @since 4.2.0
	 */
	protected $student;

	/**
	 * Students Page Load.
	 *
	 * Use this method to load the
	 * admin page.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		$student_id = wpcw_get_var( 'id' );

		if ( $student_id ) {
			$this->student = new Student( $student_id );
		}

		if ( empty( $this->student ) ) {
			$notice = sprintf(
				__( 'You must specify a student. <a href="%s">Back to Students</a>', 'wp-courseware' ),
				add_query_arg( array( 'page' => 'wpcw-students' ), admin_url( 'admin.php' ) )
			);

			wpcw_admin_notice( $notice, 'error' );
		}
	}

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-students';
	}

	/**
	 * Get Student Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Student', 'wp-courseware' );
	}

	/**
	 * Get Student Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Student Details', 'wp-courseware' );
	}

	/**
	 * Get Student Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_students_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Student Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-student';
	}

	/**
	 * Get Student Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-students' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Back to Students', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Student Page Display.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed
	 */
	protected function display() {
		if ( empty( $this->student ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		$student_id           = $this->student->get_ID();
		$student_avatar       = $this->student->get_avatar();
		$student_name         = $this->student->get_display_name();
		$student_email        = $this->student->get_user_email();
		$student_edit_url     = $this->student->get_user_edit_url();
		$student_progress_url = $this->student->get_detailed_progress_url();
		$student_courses      = WPCW_users_getUserCourseList( $student_id );

		$remove_student_url = wp_nonce_url( add_query_arg( array(
			'page'       => 'wpcw-students',
			'student_id' => $student_id,
			'action'     => 'remove-student',
		), admin_url( 'admin.php' ) ), 'student-nonce' );

		echo '<div id="wpcw-student">';

		do_action( 'wpcw_admin_page_student_display_top', $this );

		echo '<wpcw-notices>' . do_action( 'wpcw_admin_notices' ) . '</wpcw-notices>';

		?>
        <form id="wpcw-admin-page-student-form" method="get" action="<?php echo $this->get_url(); ?>">
            <input type="hidden" name="page" value="<?php echo $this->get_slug(); ?>"/>
            <div class="card wpcw-student-card">
                <div class="student-id"><?php printf( __( '# %d', 'wp-courseware' ), $student_id ); ?></div>

                <div class="student-avatar">
					<?php echo $student_avatar ?>
                </div>

                <div class="student-details">
                    <h2><?php echo $student_name; ?></h2>
                    <p><a class="students-action-email" href="mailto:<?php echo $student_email; ?>"><?php echo $student_email ?></a></p>

                    <div class="student-action-buttons">
	                    <div id="wpcw-hidden-wp-email-editor" style="display: none;"><?php wp_editor( '', 'wpcw_email_content', array( 'media_buttons' => true ) ); ?></div>

						<?php if ( current_user_can( 'manage_wpcw_settings' ) ) { ?>
                            <a class="button button-secondary"
                               href="<?php echo esc_url( $student_edit_url ) ?>">
                                <i class="wpcw-fas wpcw-fa-edit left" aria-hidden="true"></i>
								<?php esc_html_e( 'Edit User', 'wp-courseware' ); ?>
                            </a>
						<?php } ?>

                        <a class="button button-secondary students-action-email" data-student="<?php echo $this->student->get_json(); ?>" href="#">
                            <i class="wpcw-fas wpcw-fa-envelope left" aria-hidden="true"></i>
							<?php esc_html_e( 'Email Student', 'wp-courseware' ); ?>
                        </a>
                    </div>

					<?php if ( ( $terms_agree_time = $this->student->get_meta( '_wpcw_agree_to_terms_time', true ) ) ) { ?>
                        <p class="student-agree-terms-time student-privacy-data"><?php printf( __( 'Agreed to Terms: %s', 'wp-courseware' ), wpcw_format_datetime( $terms_agree_time, wpcw_date_format() . ' ' . wpcw_time_format() ) ); ?></p>
					<?php } ?>

					<?php if ( ( $privacy_agree_time = $this->student->get_meta( '_wpcw_agree_to_privacy_policy_time', true ) ) ) { ?>
                        <p class="student-agree-privacy-time student-privacy-data"><?php printf( __( 'Agreed to Privacy Policy: %s', 'wp-courseware' ), wpcw_format_datetime( $privacy_agree_time, wpcw_date_format() . ' ' . wpcw_time_format() ) ); ?></p>
					<?php } ?>
                </div>

                <div style="clear:both;"></div>

                <hr class="break"/>

                <div class="student-courses">
                    <h3><?php esc_html_e( 'Courses', 'wp-courseware' ); ?></h3>
	                <div class="student-buttons">
		                <a class="button detailed-progress-button" href="<?php echo esc_url( $student_progress_url ); ?>">
			                <i class="wpcw-fa wpcw-fa-tasks" aria-hidden="true"></i>
			                <?php esc_html_e( 'View Detailed Progress', 'wp-courseware' ); ?>
		                </a>
		                <a class="button update-access-button" href="<?php echo $this->student->get_update_access_url(); ?>">
			                <i class="wpcw-fa wpcw-fa-low-vision" aria-hidden="true"></i>
			                <?php esc_html_e( 'Update Access', 'wp-courseware' ); ?>
		                </a>
	                </div>
                    <table class="wp-list-table widefat striped courses-table">
                        <thead>
                        <tr>
                            <th class="title"><?php esc_html_e( 'Title', 'wp-courseware' ); ?></th>
                            <th class="progress"><?php esc_html_e( 'Progress', 'wp-courseware' ); ?></th>
                            <th class="reset"><?php esc_html_e( 'Reset Progress', 'wp-courseware' ); ?></th>
	                        <th class="update-progress"><?php esc_html_e( 'Update Progress', 'wp-courseware' ); ?></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php if ( ! empty( $student_courses ) ) { ?>
							<?php foreach ( $student_courses as $student_course ) {
								if ( is_admin() && ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
									if ( $current_user->ID !== absint( $student_course->post_author ) ) {
										continue;
									}
								}

								$course                = new Course( $student_course );
								$course_percentage     = WPCW_stats_convertPercentageToBar( $student_course->course_progress );
								$course_reset_dropdown = wpcw()->courses->get_courses_reset_dropdown( array(
									array(
										'id'     => $course->get_course_id(),
										'title'  => $course->get_course_title(),
										'author' => $course->get_course_author(),
									),
								), false, false );
								?>
                                <tr>
	                                <td class="title"><a href="<?php echo $course->get_edit_url(); ?>" target="_blank"><?php echo $course->get_course_title(); ?></a></td>
                                    <td class="progress"><?php echo $course_percentage; ?></td>
                                    <td class="reset">
                                        <form method="get" action="<?php echo $this->get_url(); ?>">
                                            <input type="hidden" name="wpcw_users_single" value="<?php echo $student_id; ?>"/>
                                            <input type="hidden" name="wpcw_student" value="<?php echo $student_id; ?>"/>
                                            <input type="hidden" name="wpcw_student_reset_page" value="wpcw-student"/>
											<?php echo $course_reset_dropdown; ?>
                                        </form>
                                    </td>
	                                <td class="update-progress">
		                                <a class="button" @click.prevent="updateStudentProgress( '<?php echo $student_id; ?>', '<?php echo $course->get_id(); ?>' )" >
			                                <i class="wpcw-fa wpcw-fa-sync" :class="{ 'wpcw-fa-spin' : updateProgressId === '<?php echo $course->get_id(); ?>' }" aria-hidden="true"></i>
			                                <?php esc_html_e( 'Update Progress', 'wp-courseware' ); ?>
		                                </a>
	                                </td>
                                </tr>
							<?php } ?>
						<?php } else { ?>
                            <tr>
                                <td colspan="5"><?php esc_html_e( 'No courses found for this student.', 'wp-courseware' ); ?></td>
                            </tr>
						<?php } ?>
                        </tbody>
                    </table>
                </div>

				<?php if ( $orders = $this->student->get_orders() ) { ?>
                    <hr class="break">

                    <div class="student-orders">
                        <h3><?php esc_html_e( 'Orders', 'wp-courseware' ); ?></h3>
                        <table class="wp-list-table widefat striped orders-table">
                            <thead>
                            <tr>
                                <th class="number"><?php esc_html_e( 'Number', 'wp-courseware' ); ?></th>
                                <th class="date"><?php esc_html_e( 'Date', 'wp-courseware' ); ?></th>
                                <th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
                                <th class="total"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></th>
                                <th class="actions"><?php esc_html_e( 'Actions', 'wp-courseware' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php /** @var Order $order */ ?>
							<?php foreach ( $orders as $order ) { ?>
                                <tr>
                                    <td class="number"><a href="<?php echo $order->get_order_edit_url(); ?>"><?php printf( '#%s', $order->get_order_number() ); ?></a></td>
                                    <td class="date"><abbr title="<?php echo $order->get_date_created( true ); ?>"><?php echo $order->get_date_created( true ); ?></abbr></td>
                                    <td class="status">
                                        <mark class="mark-status status-<?php echo $order->get_order_status(); ?>">
											<?php echo wpcw_get_order_status_name( $order->get_order_status() ); ?>
                                        </mark>
                                    </td>
                                    <td class="total"><?php echo $order->get_total( true ); ?></td>
                                    <td class="actions">
                                        <a class="button-secondary" href="<?php echo $order->get_order_edit_url(); ?>">
                                            <i class="wpcw-fas wpcw-fa-file-alt"></i> <?php esc_html_e( 'View', 'wp-courseware' ); ?>
                                        </a>
                                    </td>
                                </tr>
							<?php } ?>
                            </tbody>
                        </table>
                    </div>
				<?php } ?>

				<?php if ( $subscriptions = $this->student->get_subscriptions() ) { ?>
                    <hr class="break">

                    <div class="student-subscriptions">
                        <h3><?php esc_html_e( 'Subscriptions', 'wp-courseware' ); ?></h3>
                        <table class="wp-list-table widefat striped subscriptions-table">
                            <thead>
                            <tr>
                                <th class="number"><?php esc_html_e( 'ID', 'wp-courseware' ); ?></th>
                                <th class="order"><?php esc_html_e( 'Order', 'wp-courseware' ); ?></th>
                                <th class="course"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
                                <th class="date"><?php esc_html_e( 'Date', 'wp-courseware' ); ?></th>
                                <th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
                                <th class="amount"><?php esc_html_e( 'Amount', 'wp-courseware' ); ?></th>
                                <th class="actions"><?php esc_html_e( 'Actions', 'wp-courseware' ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php /** @var Subscription $subscription */ ?>
							<?php foreach ( $subscriptions as $subscription ) { ?>
                                <tr>
                                    <td class="number"><a href="<?php echo $subscription->get_edit_url(); ?>"><?php printf( '#%s', $subscription->get_id() ); ?></a></td>
                                    <td class="order"><a href="<?php echo $subscription->get_order_edit_url(); ?>"><?php printf( '#%s', $subscription->get_order_id() ); ?></a></td>
                                    <td class="course"><a href="<?php echo $subscription->get_course_edit_url(); ?>"><?php echo $subscription->get_course_title(); ?></a></td>
                                    <td class="date"><abbr title="<?php echo $subscription->get_created( true ); ?>"><?php echo $subscription->get_created( true ); ?></abbr></td>
                                    <td class="status">
                                        <mark class="mark-status status-<?php echo $subscription->get_status(); ?>">
											<?php echo wpcw_get_subscription_status_name( $subscription->get_status() ); ?>
                                        </mark>
                                    </td>
                                    <td class="amount"><?php echo $subscription->get_recurring_amount( true ); ?></td>
                                    <td class="actions">
                                        <a class="button-secondary" href="<?php echo $subscription->get_edit_url(); ?>">
                                            <i class="wpcw-fas wpcw-fa-file-alt"></i> <?php esc_html_e( 'View', 'wp-courseware' ); ?>
                                        </a>
                                    </td>
                                </tr>
							<?php } ?>
                            </tbody>
                        </table>
                    </div>
				<?php } ?>

				<?php if ( current_user_can( 'manage_wpcw_settings' ) ) { ?>
                    <hr class="break"/>

                    <div class="student-actions text-right">
                        <a id="wpcw_student_remove_student" class="button button-danger remove-student" href="<?php echo esc_url( $remove_student_url ); ?>">
                            <i class="wpcw-fa wpcw-fa-trash-o" aria-hidden="true"></i>
							<?php esc_html_e( 'Remove Student', 'wp-courseware' ); ?>
                        </a>
                    </div>
				<?php } ?>
            </div>
        </form>
		<?php

		do_action( 'wpcw_admin_page_student_display_bottom', $this );

		echo '</div>';

	}

	/**
	 * Is Student Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}

	/**
	 * Student Page Views.
	 *
	 * @since 4.1.0
	 */
	public function views() {
		$views = array(
			'common/notices',
			'common/modal-notices',
			'common/form-field',
			'classroom/classroom-send-email',
		);

		foreach ( $views as $view ) {
			echo $this->get_view( $view );
		}

		?>
        <div id="wpcw-send-class-email-instance">
            <wpcw-classroom-send-email v-once></wpcw-classroom-send-email>
        </div>
		<?php
	}
}
