<?php
/**
 * WP Courseware Courses Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Models\Course;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Courses.
 *
 * @since 4.1.0
 */
class Page_Courses extends Page {

	/**
	 * @var string Post Type Slug.
	 * @since 4.4.0
	 */
	protected $post_type = 'wpcw_course';

	/**
	 * Hooks.
	 *
	 * @since 4.4.0
	 */
	protected function hooks() {
		// Parent Hooks.
		parent::hooks();

		// Icon count.
		add_action( 'admin_menu', array( $this, 'add_icon_count' ) );

		// Menu
		add_action( 'admin_head', array( $this, 'hide_post_type_menu' ) );
		add_action( 'admin_head', array( $this, 'hightlight_submenu_add_edit' ) );
		add_action( 'admin_head', array( $this, 'add_icon_to_title' ) );

		// Action Buttons.
		add_action( 'admin_head-edit.php', array( $this, 'add_action_buttons' ) );

		// Screen Options.
		add_filter( 'screen_settings', array( $this, 'set_screen_custom' ), 10, 2 );
		add_action( 'init', array( $this, 'save_custom_screen_options' ) );

		// Course Row Actions.
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );

		// Course Columns
		add_filter( 'manage_edit-wpcw_course_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_wpcw_course_posts_custom_column', array( $this, 'manage_custom_columns' ), 10, 2 );

		// Admin Views
		add_action( 'in_admin_header', array( $this, 'views' ) );
	}

	/**
	 * Page Id.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'wpcw-courses';
	}

	/**
	 * Hide Course Post Type Menu.
	 *
	 * @since 4.4.0
	 */
	public function hide_post_type_menu() {
		if ( empty( $this->admin ) ) {
			return;
		}

		$this->admin->hide_top_menu( 'edit.php?post_type=' . $this->post_type );
	}

	/**
	 * Highlight Submenu on Post Type Add / Edit
	 *
	 * @since 4.4.0
	 */
	public function hightlight_submenu_add_edit() {
		global $current_screen, $parent_file, $submenu_file;

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( $current_screen->post_type !== 'wpcw_course' ) {
			return;
		}

		$parent_file  = $this->admin->get_slug();
		$submenu_file = $this->get_slug();
	}

	/**
	 * Add Icon to Title.
	 *
	 * @since 4.4.0
	 */
	public function add_icon_to_title() {
		global $current_screen;

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( $this->post_type !== $current_screen->post_type ) {
			return;
		}

		echo
			'<style type="text/css">
                .wrap h1.wp-heading-inline {
                    position: relative;
                    padding-top: 4px;
                    padding-left: 50px;
                }
                .wrap h1.wp-heading-inline:before {
                    background-image: url("' . wpcw_image_file( 'wp-courseware-icon.svg' ) . '");
                    background-size: 40px 40px;
                    content: "";
                    display: inline-block;
                    position: absolute;
                    top: -2px;
                    left: 0;
                    width: 40px;
                    height: 40px;
                }
            </style>';
	}

	/**
	 * Add Action Buttons.
	 *
	 * @since 4.4.0
	 */
	public function add_action_buttons() {
		global $current_screen;

		if ( $this->post_type !== $current_screen->post_type ) {
			return;
		}

		$action_buttons = $this->get_action_buttons();

		if ( empty( $action_buttons ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			( function ( $ ) {
				$( document ).ready( function () {
					$( '<?php echo $action_buttons; ?>' ).insertAfter( '.wrap a.page-title-action' );
				} )
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Get Courses Action Buttons.
	 *
	 * @since 4.4.0
	 *
	 * @return string The action buttons.
	 */
	protected function get_action_buttons() {
		$actions = sprintf( '<a class="page-title-action" href="%s">%s</a>', add_query_arg( array( 'page' => 'wpcw-modules' ), admin_url( 'admin.php' ) ), esc_html__( 'View Modules', 'wp-courseware' ) );
		$actions .= sprintf( '<a class="page-title-action" href="%s">%s</a>', add_query_arg( array( 'post_type' => 'course_unit' ), admin_url( 'edit.php' ) ), esc_html__( 'View Units', 'wp-courseware' ) );

		$actions = apply_filters( 'wpcw_admin_page_courses_action_buttons', $actions );

		$actions .= sprintf(
			'<a id="wpcw-enroll-bulk-students" class="page-title-action" href="#"><i class="wpcw-fa wpcw-fa-user-plus left" aria-hidden="true"></i> %s</a>',
			esc_html__( 'Bulk Enroll Students', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Course Actions.
	 *
	 * @since 4.1.0
	 */
	public function actions() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

		if ( ! $action ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				$course_id = wpcw_get_var( 'course_id' );
				$method    = wpcw_post_var( 'delete_course_type' );
				$method    = ( ! $method ) ? wpcw_get_var( 'delete_course_type' ) : '';
				if ( $course = wpcw()->courses->delete_course( $course_id, $method ) ) {
					wpcw_add_admin_notice_success( esc_html__( 'Course deleted successfully.', 'wp-courseware' ) );
				}
				wp_safe_redirect( $this->get_url() );
				exit;
				break;
			default:
				break;
		}
	}

	/**
	 * Admin Page Custom Screen Settings.
	 *
	 * @since 4.4.0
	 *
	 * @param array $settings The screen settings.
	 * @param object \WP_Screen The screen object.
	 *
	 * @return array $settings The screen settings.
	 */
	public function set_screen_custom( $settings, $screen ) {
		if ( $screen->id !== 'edit-wpcw_course' ) {
			return $settings;
		}

		$user_id = get_current_user_id();

		$hide_quiz_notifiations = get_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', true );

		if ( ! $hide_quiz_notifiations ) {
			$hide_quiz_notifiations = 'show';
			update_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', 'show' );
		}

		$expand = '<fieldset class="course-quiz-notifications"><legend>' . esc_html__( 'Additional Course Settings', 'wp-courseware' ) . '</legend><label for="course-quiz-notifications">';
		$expand .= '<input type="hidden" name="quiz_notification_hide_submit" value="yes" />';
		$expand .= '<input type="checkbox" name="quiz_notification_hide" id="course-quiz-notifications" value="show" ' . checked( $hide_quiz_notifiations, 'show', false ) . ' />';
		$expand .= esc_html__( 'Enable / Disable - Quiz Notifications.', 'wp-courseware' ) . '</label></fieldset>';

		$settings .= $expand;

		return $settings;
	}

	/**
	 * Save Custom Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed
	 */
	public function save_custom_screen_options() {
		global $pagenow;

		if ( ! is_admin() || wpcw_is_ajax() ) {
			return;
		}

		if ( $pagenow !== 'edit.php' && wpcw_get_var( 'post_type' ) !== $this->post_type ) {
			return;
		}

		if ( isset( $_POST['quiz_notification_hide_submit'] ) && 'yes' === $_POST['quiz_notification_hide_submit'] ) {
			if ( isset( $_POST['quiz_notification_hide'] ) && $_POST['quiz_notification_hide'] == 'show' ) {
				update_user_meta( get_current_user_id(), 'wpcw_course_dashboard_quiz_notification_hide', 'show', 'hide' );
			} else {
				update_user_meta( get_current_user_id(), 'wpcw_course_dashboard_quiz_notification_hide', 'hide', 'show' );
			}

			wpcw_add_admin_notice_success( esc_html__( 'Quiz notiifications settings updated successfully!', 'wp-courseware' ) );
		}
	}

	/**
	 * Add Quiz Needs Grading Icon Count.
	 *
	 * @since 4.1.0
	 */
	public function add_icon_count() {
		global $submenu, $pagenow;

		if ( $pagenow !== 'edit.php' && wpcw_get_var( 'post_type' ) !== $this->post_type ) {
			return;
		}

		if ( ! isset( $submenu[ $this->admin->get_slug() ] ) ) {
			return false;
		}

		foreach ( $submenu[ $this->admin->get_slug() ] as $index => $details ) {
			if ( $details[2] === $this->get_slug() ) {
				$quiz_count = wpcw()->courses->get_course_quizzes_that_need_grading_count();

				if ( $quiz_count > 0 ) {
					$submenu[ $this->admin->get_slug() ][ $index ][0] .= sprintf( '&nbsp;&nbsp;<span class="update-plugins count-%d"><span class="update-count">%s</span></span>', $quiz_count, $quiz_count );
				}

				return;
			}
		}
	}

	/**
	 * Get Courses Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Courses', 'wp-courseware' );
	}

	/**
	 * Get Courses Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Courses', 'wp-courseware' );
	}

	/**
	 * Get Courses Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_courses_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Courses Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return esc_url( add_query_arg( array( 'post_type' => $this->post_type ), 'edit.php' ) );
	}

	/**
	 * Get Admin Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The admin url.
	 */
	public function get_url() {
		return admin_url( $this->get_slug() );
	}

	/**
	 * Get Course Page Callback.
	 *
	 * @since 4.4.0
	 *
	 * @return null
	 */
	protected function get_callback() {
		return null;
	}

	/**
	 * Get Course Page hook.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_hook() {
		return '';
	}

	/**
	 * Course Page Views.
	 *
	 * @since 4.1.0
	 */
	public function views() {
		$current_screen = get_current_screen();

		if ( 'edit-wpcw_course' !== $current_screen->id ) {
			return;
		}

		$views = array(
			'common/notices',
			'common/form-field',
			'enrollment/enroll-bulk-students',
		);

		foreach ( $views as $view ) {
			echo $this->get_view( $view );
		}

		?>
		<div id="wpcw-enroll-bulk">
			<wpcw-enroll-bulk-students></wpcw-enroll-bulk-students>
		</div>
		<?php
	}

	/**
	 * Get Course.
	 *
	 * @since 4.4.0
	 *
	 * @param int $post_id The post id.
	 *
	 * @return Course|false The course object of false.
	 */
	public function get_course( $post_id = 0 ) {
		global $wp_query;

		if ( empty( $wp_query->posts ) ) {
			return false;
		}

		$post_ids       = wp_list_pluck( $wp_query->posts, 'ID' );
		$found_post_key = array_search( $post_id, $post_ids );
		$found_post     = isset( $wp_query->posts[ $found_post_key ] ) ? $wp_query->posts[ $found_post_key ] : null;

		if ( is_null( $found_post ) ) {
			return false;
		}

		return new Course( $found_post );
	}

	/**
	 * Course Row Actions.
	 *
	 * @since 4.4.0
	 */
	public function row_actions( $actions, $post ) {
		if ( $this->post_type !== $post->post_type ) {
			return $actions;
		}

		// Course Object.
		$course = $this->get_course( $post->ID );

		if ( ! empty( $course ) && $course instanceof Course ) {
			// Add to begining of the array.
			$actions = array( 'course_id' => sprintf( __( 'ID: %s', 'wp-courseware' ), $course->get_id() ) ) + $actions;

			// Classroom
			$actions['classroom'] = wpcw_table_row_action_link(
				esc_html__( 'Classroom', 'wp-courseware' ),
				array( 'base_uri' => $course->get_classroom_url() )
			);

			// Gradebook
			$actions['gradebook'] = wpcw_table_row_action_link(
				esc_html__( 'Gradebook', 'wp-courseware' ),
				array( 'base_uri' => $course->get_gradebook_url() )
			);

			// Duplicate
			$actions['duplicate'] = wpcw_table_row_action_link(
				esc_html__( 'Duplicate', 'wp-courseware' ),
				array(
					'base_uri' => '#',
					'class'    => 'wpcw_course_admin_duplicate',
					'atts'     => sprintf( 'data-postid="%d" data-nonce="%s"', $post->ID, wp_create_nonce( 'course-nonce' ) ),
				)
			);
		}

		return $actions;
	}

	/**
	 * Course Custom Columns
	 *
	 * @since 4.4.0
	 *
	 * @param array $columns The array of columns.
	 *
	 * @return array $columns The array of columns.
	 */
	public function custom_columns( $columns ) {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'title'      => esc_html__( 'Title', 'wp-courseware' ),
			'settings'   => esc_html__( 'Settings', 'wp-courseware' ),
			'shortcodes' => esc_html__( 'Shortcodes', 'wp-courseware' ),
			'actions'    => esc_html__( 'Actions', 'wp-courseware' ),
			'date'       => esc_html__( 'Date', 'wp-courseware' ),
		);

		/**
		 * Filter: Course Custom Columns.
		 *
		 * @since 4.4.0
		 *
		 * @param array $columns The custom columns.
		 *
		 * @return array $columns The course custom columns.
		 */
		return apply_filters( 'wpcw_course_custom_columns', $columns );
	}

	/**
	 * Manage Custom Columns.
	 *
	 * @since 4.4.0
	 *
	 * @param string $column The column slug string.
	 * @param int    $post_id The post id.
	 */
	public function manage_custom_columns( $column, $post_id ) {
		global $post;

		if ( $this->post_type !== $post->post_type ) {
			return;
		}

		// Course Object.
		$course = $this->get_course( $post->ID );

		if ( $course && $course instanceof Course ) {
			switch ( $column ) {
				case 'settings' :
					echo $this->get_column_settings( $course );
					break;
				case 'actions' :
					echo $this->get_column_modules( $course );
					echo $this->get_column_units( $course );
					break;
				case 'shortcodes' :
					echo $this->get_column_shortcodes( $course );
					break;
				case 'course_id' :
					echo $this->get_column_course_id( $course );
					break;
			}

			/**
			 * Action: Course Manage Custom Column
			 *
			 * @since 4.4.0
			 *
			 * @param Course       $course The course object.
			 * @param Page_Courses $this The page courses object.
			 */
			do_action( "wpcw_course_manage_custom_column_{$column}", $course, $this );
		}
	}

	/**
	 * Get Column Settings.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return string The string of settings.
	 */
	public function get_column_settings( Course $course ) {
		$settings = '<ul class="wpcw_tickitems">';

		if ( $course->is_closed() ) {
			$settings .= sprintf(
				'<li class="wpcw_%s">%s</li>',
				'disabled',
				esc_html__( 'Closed', 'wp-courseware' )
			);
		}

		// Manual Grade Count.
		$manual_grade_count = $this->maybe_get_manual_grade_quiz_count( $course->get_course_id() );
		if ( $manual_grade_count ) {
			$settings .= sprintf(
				'<li class="wpcw_%s">%s %s - <a href="%s">%s</a></li>',
				'info',
				$manual_grade_count,
				esc_html__( 'Manual grading needed', 'wp-courseware' ),
				$course->get_gradebook_url(),
				esc_html__( 'View Gradebook', 'wp-courseware' )
			);
		}

		// Payments.
		if ( ! wpcw_is_membership_integration_active() ) {
			$settings .= sprintf( '<li class="wpcw_%s">%s</li>', 'enabled', wpcw()->courses->get_payments_feature_label( $course ) );

			if ( $installments_feature_label = wpcw()->courses->get_installments_feature_label( $course ) ) {
				$settings .= sprintf( __( '<li class="wpcw_%s">%s</li>', 'wp-courseware' ), 'enabled', $installments_feature_label );
			}
		}

		// Access Message.
		$settings .= apply_filters( 'wpcw_extensions_access_control_override', sprintf(
			'<li class="wpcw_%s">%s</li>',
			( $course->is_purchasable() || 'default_show' == $course->get_course_opt_user_access() ? 'enabled' : 'disabled' ),
			$course->get_course_opt_user_access_message()
		) );

		// Completion wall
		$settings .= sprintf( '<li class="wpcw_%s">%s</li>', ( 'completion_wall' == $course->get_course_opt_completion_wall() ? 'enabled' : 'disabled' ),
			esc_html__( 'Require unit completion before showing next', 'wp-courseware' ) );

		// Certificate handling
		$settings .= sprintf( '<li class="wpcw_%s">%s</li>', ( 'use_certs' == $course->get_course_opt_use_certificate() ? 'enabled' : 'disabled' ),
			esc_html__( 'Generate certificates on course completion', 'wp-courseware' ) );

		if ( 'none' == $course->get_course_access_expiration() ) {
			$settings .= sprintf( '<li class="wpcw_%s">%s</li>', 'disabled',
				esc_html__( 'Course access will not expire', 'wp-courseware' ) );
		} elseif ( 'specific_date' == $course->get_course_access_expiration() && ! empty( $course->get_cae_specific_date() ) ) {
			$settings .= sprintf( '<li class="wpcw_%s">%s</li>', 'enabled',
				esc_html__( 'Course access will expire on ' . $course->get_cae_specific_date(), 'wp-courseware' ) );
		} elseif ( 'specific_interval' == $course->get_course_access_expiration() ) {
			$settings .= sprintf( '<li class="wpcw_%s">%s</li>', 'enabled',
				esc_html__( 'Course access will expire ' . $course->get_cae_interval_date_int_expire() . ' ' . $course->get_cae_interval_date_string_expire() . ' after enrollment', 'wp-courseware' ) );
		}

		$settings .= '</ul>';

		return $settings;
	}

	/**
	 * Maybe Get Manual Grade Quiz Count.
	 *
	 * @since 4.4.5
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string $manual_grade_quiz_count The manual grade quiz count.
	 */
	protected function maybe_get_manual_grade_quiz_count( $course_id ) {
		$manual_grade_quiz_count = '';

		$quiz_count = wpcw()->courses->get_course_quizzes_that_need_grading_count( $course_id );

		if ( $quiz_count > 0 ) {
			$manual_grade_quiz_count = sprintf( '<span class="wpcw-quiz-update-count count-%d"><span class="update-count">%s</span></span>', $quiz_count, $quiz_count );
		}

		return $manual_grade_quiz_count;
	}

	/**
	 * Get Column Modules.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return string The modules column string.
	 */
	public function get_column_modules( Course $course ) {
		$course_id = $course->get_course_id();

		$modules_link = add_query_arg( array( 'page' => 'wpcw-modules', 'course_id' => $course_id ), admin_url( 'admin.php' ) );
		$modules_icon = '<i class="wpcw-fa wpcw-fa-tasks left" aria-hidden="true"></i>';
		$modules_text = esc_html__( 'View Modules', 'wp-courseware' );

		return sprintf( '<a class="button button-secondary wpcw-modules-button" href="%s">%s %s</a>', $modules_link, $modules_icon, $modules_text );
	}

	/**
	 * Get Column Units.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return string The units column string.
	 */
	public function get_column_units( Course $course ) {
		$course_id = $course->get_course_id();

		$units_link = add_query_arg( array( 'post_type' => 'course_unit', 'course_id' => $course_id ), admin_url( 'edit.php' ) );
		$units_icon = '<i class="wpcw-fa wpcw-fa-window-restore left" aria-hidden="true"></i>';
		$units_text = esc_html__( 'View Units', 'wp-courseware' );

		return sprintf( '<a class="button button-secondary wpcw-units-button" href="%s">%s %s</a>', $units_link, $units_icon, $units_text );
	}

	/**
	 * Get Column Shortcodes.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return string The column shortcode html.
	 */
	public function get_column_shortcodes( Course $course ) {
		$support_link = esc_url_raw( add_query_arg( array( 'page' => 'wpcw-settings', 'tab' => 'support' ), admin_url( 'admin.php' ) ) );
		ob_start();
		?>
		<div id="wpcw-courses-column-shortcodes" class="wpcw-courses-column-shortcodes">
			<?php echo $this->get_course_outline_shortcode_html( $course, $support_link ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Course Outline Shortcode Html.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 * @param string $support_link The support link.
	 *
	 * @return string The course outline shortcode html.
	 */
	public function get_course_outline_shortcode_html( Course $course, $support_link ) {
		$course_outline_shortcode     = htmlentities( '[wpcourse course="' . $course->get_course_id() . '"]' );
		$course_outline_shortcode_tip = __( '<code>Course Outline Shortcode</code> - You can use this shortcode to display the course outline on any page.', 'wp-courseware' );
		ob_start();
		?>
		<div class="wpcw-courses-shortcode wpcw-courses-course-outline-shortcode">
			<abbr class="wpcw-tooltip" title="<?php echo $course_outline_shortcode_tip; ?>" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
			<input onClick="this.setSelectionRange(0, this.value.length)"
			       type="text"
			       name="wpcw-courses-course-outline-shortcode-input"
			       id="wpcw-courses-course-outline-shortcode-input"
			       class="wpcw-courses-course-outline-shortcode-input" readonly value="<?php echo wp_kses_post( $course_outline_shortcode ); ?>"/>
			<a href="<?php echo esc_url( $support_link ); ?>" target="_blank"><i class="wpcw-fas wpcw-fa-external-link-alt"></i></a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Course Progress Shortcode Html.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 * @param string $support_link The support link.
	 *
	 * @return string The course progress shortcode html.
	 */
	public function get_course_progress_shortcode_html( Course $course, $support_link ) {
		$course_progress_shortcode     = htmlentities( '[wpcourse_progress courses="' . $course->get_course_id() . '"]' );
		$course_progress_shortcode_tip = __( '<code>Course Progress Shortcode</code> - You can use this shortcode to display the course progress on any page.', 'wp-courseware' );
		ob_start();
		?>
		<div class="wpcw-courses-shortcode wpcw-courses-course-progress-shortcode">
			<abbr class="wpcw-tooltip" title="<?php echo $course_progress_shortcode_tip; ?>" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
			<input onClick="this.setSelectionRange(0, this.value.length)"
			       type="text"
			       name="wpcw-courses-course-progress-shortcode-input"
			       id="wpcw-courses-course-progress-shortcode-input"
			       class="wpcw-courses-course-progress-shortcode-input" readonly value="<?php echo $course_progress_shortcode; ?>"/>
			<a href="<?php echo $support_link; ?>" target="_blank"><i class="wpcw-fas wpcw-fa-external-link-alt"></i></a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Column Course Id.
	 *
	 * @since 4.4.0
	 *
	 * @param Course $course The course object.
	 *
	 * @return int The course id.
	 */
	public function get_column_course_id( Course $course ) {
		return $course->get_course_id();
	}
}
