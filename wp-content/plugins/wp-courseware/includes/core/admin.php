<?php
/**
 * WP Courseware Admin.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WP_Screen;
use WPCW\Admin\Notices;
use WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Admin.
 *
 * @since 4.3.0
 */
final class Admin {

	/**
	 * @var string Admin page slug.
	 * @since 4.1.0
	 */
	public $slug = 'wpcw';

	/**
	 * @var string Admin page hook.
	 * @since 4.1.0
	 */
	public $hook;

	/**
	 * @var Pages The pages factory.
	 * @since 4.1.0
	 */
	public $pages;

	/**
	 * @var array The addons array.
	 * @since 4.1.0
	 */
	public $addons = array();

	/**
	 * @var int Admin page menu position.
	 * @since 4.1.0
	 */
	public $position = 100;

	/**
	 * @var Notices Admin Notices Object.
	 * @since 4.3.0
	 */
	public $notices;

	/**
	 * Admin constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		if ( ! wpcw_is_request( 'admin' ) || is_network_admin() ) {
			return;
		}

		$notices_key   = 'wpcw_admin_' . md5( get_current_user_id() );
		$this->notices = new Notices( $notices_key );
	}

	/**
	 * Admin Load.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		if ( ! wpcw_is_request( 'admin' ) || is_network_admin() ) {
			return;
		}

		// Notices
		$this->notices->add_hooks();

		// Register Pages
		add_action( 'wpcw_loaded', array( $this, 'register_pages' ) );

		// Admin Menu
		add_action( 'admin_menu', array( $this, 'register_primary_page' ) );
		add_action( 'admin_menu', array( $this, 'redirect_primary_page' ) );
		add_action( 'admin_menu', array( $this, 'redirect_courses_page' ) );
		add_action( 'admin_head', array( $this, 'hide_primary_page' ) );

		// Notices
		add_action( 'admin_init', array( $this, 'dismiss_notices' ) );

		// Admin Body Class
		add_action( 'admin_body_class', array( $this, 'body_class' ) );

		// Scripts and Styles
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ), 100 );

		// Action Links
		add_filter( 'plugin_action_links', array( $this, 'action_links' ), 10, 2 );

		// Add labels for specific WP Courseware Pages in the pages list table
		add_filter( 'display_post_states', array( $this, 'add_display_page_labels' ), 10, 2 );

		// Allows import user with cyrillic usernames.
		add_filter( 'sanitize_user', array( $this, 'wpcw_sanitize_user' ), 10, 3 );
	}

	/**
	 * Register Admin Pages.
	 *
	 * @since 4.3.0
	 */
	public function register_pages() {
		if ( ! is_admin() ) {
			return;
		}

		$admin_page_classes = array(
			'Page_Courses',
			'Page_Course',
			'Page_Certificates',
			'Page_Course_Classroom',
			'Page_Course_Gradebook',
			'Page_Course_Ordering',
			'Page_Modules',
			'Page_Module',
			'Page_Units',
			'Page_Unit_Converter',
			'Page_Quizzes',
			'Page_Quiz',
			'Page_Questions',
			'Page_Question',
			'Page_Students',
			'Page_Student',
			'Page_Student_Access',
			'Page_Student_Progress',
			'Page_Student_Results',
			'Page_Student_New',
			'Page_Orders',
			'Page_Order',
			'Page_Subscription',
			'Page_Subscriptions',
			'Page_Coupons',
			'Page_Coupon',
			// 'Page_Reports',
			'Page_Tools',
			'Page_Settings',
		);

		$admin_pages = new Pages();

		foreach ( $admin_page_classes as $admin_page_class ) {
			$page_class = "\\WPCW\\Admin\\Pages\\{$admin_page_class}";
			if ( class_exists( $page_class ) ) {
				$admin_pages->register_page( $page_class::register() );
			}
		}

		/**
		 * Filters: Filter Admin Pages.
		 *
		 * @since 4.3.0
		 *
		 * @param Pages The registered admin pages.
		 */
		$this->pages = apply_filters( 'wpcw_admin_pages', $admin_pages );

		/**
		 * Action: Admin Register Pages.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_admin_pages_register' );
	}

	/**
	 * Register Admin Menu.
	 *
	 * @since 4.1.0
	 */
	public function register_primary_page() {
		$this->hook = add_menu_page(
			__( 'WP Courseware', 'wp-courseware' ),
			__( 'WP Courseware', 'wp-courseware' ),
			apply_filters( 'wpcw_admin_menu_capability', 'view_wpcw_courses' ),
			$this->slug,
			array( $this, 'display_primary_page' ),
			apply_filters( 'wpcw_admin_menu_icon', '' ),
			apply_filters( 'wpcw_admin_menu_position', $this->position )
		);

		/**
		 * Fires after the main admin menu has been created.
		 *
		 * @since 4.1.0
		 *
		 * @param Admin The admin object.
		 */
		do_action( 'wpcw_admin_menu', $this );

		/**
		 * Legacy: Add extensions / addons to the menu.
		 *
		 * @since 4.3.0
		 *
		 * @param array An empty array of addons.
		 */
		$addons = apply_filters( 'wpcw_extensions_menu_items', array() );

		/**
		 * Filter: Add Addons to the menu.
		 *
		 * @since 4.3.0
		 *
		 * @param array The array of addons that have already been registered.
		 */
		$addons = apply_filters( 'wpcw_admin_menu_addons', $addons );

		// Add Addons to the primary menu.
		if ( ! empty( $addons ) ) {
			add_submenu_page( $this->slug, '', '<span></span>', 'view_wpcw_courses', 'wpcw-menu-separator', '' );

			foreach ( $addons as $addon ) {
				/* translators: %s is the current addon tittle */
				$addon_title = sprintf( esc_html__( 'WP Courseware - %s', 'wp-courseware' ), $addon['page_title'] );
				$addon_hook  = add_submenu_page(
					$this->slug,
					esc_html( $addon_title ),
					esc_html( $addon['menu_label'] ),
					'view_wpcw_courses',
					esc_attr( $addon['id'] ),
					$addon['menu_function']
				);

				$this->addons[ esc_attr( $addon['id'] ) ] = $addon_hook;
			}
		}
	}

	/**
	 * Display Primary Page.
	 *
	 * @since 4.1.0
	 */
	public function display_primary_page() {
		esc_html_e( 'WP Courseware', 'wp-courseware' );
	}

	/**
	 * Redirect Primary Page.
	 *
	 * @since 4.1.4
	 */
	public function redirect_primary_page() {
		if ( 'wpcw' === wpcw_get_var( 'page' ) ) {
			wp_redirect( $this->pages->get_page( 'wpcw-courses' )->get_url() );
			die();
		}
	}

	/**
	 * Redirect Courses Page.
	 *
	 * @since 4.4.0-
	 */
	public function redirect_courses_page() {
		if ( 'wpcw-courses' === wpcw_get_var( 'page' ) ) {
			wp_redirect( $this->pages->get_page( 'wpcw-courses' )->get_url() );
			die();
		}
	}

	/**
	 * Hide Admin Menu Items.
	 *
	 * @since 4.1.0
	 */
	public function hide_primary_page() {
		$this->hide_menu( $this->slug, $this->slug );
	}

	/**
	 * Dismiss Admin Notices.
	 *
	 * There are various admin notices that
	 * need to be dismissed when displayed.
	 * This method takes care of those.
	 *
	 * @since 4.3.0
	 */
	public function dismiss_notices() {
		$current_user = wp_get_current_user();

		// If dismiss button has been clicked, user meta field is added
		if ( isset( $_GET['wpcw_perma_notice_hide'] ) && '1' == $_GET['wpcw_perma_notice_hide'] ) {
			add_user_meta( $current_user->ID, 'ignore_permalinks_notice', 'yes', true );
		}

		// Dismiss canceled license notice
		if ( isset( $_GET['wpcw_cancelled_lic_notice_hide'] ) && '1' == $_GET['wpcw_cancelled_lic_notice_hide'] ) {
			add_user_meta( $current_user->ID, 'ignore_cancelled_license_notice', 'yes', true );
		}

		// Dismiss expired license notice
		if ( isset( $_GET['wpcw_expired_lic_notice_hide'] ) && '1' == $_GET['wpcw_expired_lic_notice_hide'] ) {
			add_user_meta( $current_user->ID, 'ignore_expired_license_notice', 'yes', true );
		}
	}

	/**
	 * Is Allowed Page.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if is an allowed page.
	 */
	public function is_allowed_page() {
		if ( is_network_admin() ) {
			return false;
		}

		// Admin Screen.
		$admin_screen      = get_current_screen();
		$current_page      = $this->pages->get_current_page();
		//$current_page_slug = ( $current_page ) ? sprintf( 'wp-courseware_page_%s', $current_page->get_slug() ) : '';
		$current_page_slug = ( $current_page ) ? $admin_screen->id : '';
		$allowed_pages     = apply_filters( 'wpcw_admin_allowed_pages', array(
			'dashboard',
			'wpcw_course',
			'edit-wpcw_course',
			'edit-course_category',
			'edit-course_tag',
			'course_unit',
			'edit-course_unit',
			'edit-course_unit_category',
			'edit-course_unit_tag',
			'user',
			'profile',
			'users',
		) );

		// Check
		if ( $admin_screen->id !== $current_page_slug && ! in_array( $admin_screen->id, $allowed_pages ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Admin Body Classes.
	 *
	 * @since 4.4.0
	 *
	 * @param string $classes The admin body classes.
	 *
	 * @return string $classes The admin body classes.
	 */
	public function body_class( $classes ) {
		if ( $this->is_allowed_page() ) {
			$classes .= ' wpcw-admin-page';
		}

		return $classes;
	}

	/**
	 * Admin Assets.
	 *
	 * @since 4.3.0
	 *
	 * @param string $hook The page hook.
	 */
	public function assets( $hook ) {
		global $wp_scripts, $wp_locale;

		// Admin Menu - Put this on every page.
		wp_enqueue_style( 'wpcw-admin-menu', wpcw_css_file( 'admin-menu.css' ), array(), WPCW_VERSION );

		// Check
		if ( ! $this->is_allowed_page() ) {
			return;
		}

		// Admin Screen.
		$admin_screen = get_current_screen();
		$current_page = $this->pages->get_current_page();

		/**
		 * Action: Admin Load Scripts.
		 *
		 * @since 4.6.3
		 *
		 * @param WP_Screen  $admin_screen The screen object.
		 * @param Pages\Page $current_page The admin javascript vars.
		 * @param Admin      $this The core admin object.
		 */
		do_action( 'wpcw_admin_load_scripts', $admin_screen, $current_page, $this );

		// jQuery UI Version.
		$admin_jquery_ui_version = apply_filters( 'wpcw_admin_js_jquery_ui_version', (string) $wp_scripts->registered['jquery-ui-core']->ver );

		// Custom JQuery UI Styles.
		wp_register_style( 'wpcw-jquery-ui-css', '//code.jquery.com/ui/' . $admin_jquery_ui_version . '/themes/smoothness/jquery-ui.min.css', array(), $admin_jquery_ui_version );

		// Admin Datepicker.
		wp_register_style( 'wpcw-admin-datepicker', wpcw_css_file( 'datepicker.css' ), array( 'wpcw-jquery-ui-css' ), WPCW_VERSION );
		wp_register_script( 'wpcw-admin-datepicker', wpcw_js_file( 'datepicker.js' ), array( 'jquery', 'jquery-ui-datepicker' ), WPCW_VERSION );
		wp_localize_script( 'wpcw-admin-datepicker', 'wpcwAdminDatepickerVars', array(
			'closeText'       => esc_html__( 'Done', 'wp-courseware' ),
			'currentText'     => esc_html__( 'Today', 'wp-courseware' ),
			'monthStatus'     => esc_html__( 'Show a different month', 'wp-courseware' ),
			'timeText'        => esc_html__( 'Time', 'wp-courseware' ),
			'hourText'        => esc_html__( 'Hour', 'wp-courseware' ),
			'minuteText'      => esc_html__( 'Minute', 'wp-courseware' ),
			'secondText'      => esc_html__( 'Second', 'wp-courseware' ),
			'monthNames'      => wpcw_string_array_to_numeric( $wp_locale->month ),
			'monthNamesShort' => wpcw_string_array_to_numeric( $wp_locale->month_abbrev ),
			'dayNames'        => wpcw_string_array_to_numeric( $wp_locale->weekday ),
			'dayNamesShort'   => wpcw_string_array_to_numeric( $wp_locale->weekday_abbrev ),
			'dayNamesMin'     => wpcw_string_array_to_numeric( $wp_locale->weekday_initial ),
			'firstDay'        => get_option( 'start_of_week' ),
			'text_direction'  => $wp_locale->text_direction,
		) );

		// Strength Password Meter.
		if ( $current_page && $current_page->get_slug() === 'wpcw-student-new' ) {
			wp_enqueue_script( 'password-strength-meter' );
		}

		// Editor for students.
		if ( $current_page && in_array( $current_page->get_slug(), array( 'wpcw-students', 'wpcw-student', 'wpcw-course-classroom', 'WPCW_showPage_UserProgess_quizAnswers' ) ) ) {
			wp_enqueue_editor();
		}

		if ( 'wpcw_course' === $admin_screen->id ) {
			wp_enqueue_editor();
		}

		// Admin Style
		wp_register_style( 'wpcw-admin', wpcw_css_file( 'admin.css' ), array( 'wpcw-jquery-ui-css', 'wp-color-picker', 'thickbox', 'wpcw-admin-datepicker' ), WPCW_VERSION );
		wp_register_style( 'wpcw-admin-rtl', wpcw_css_file( 'admin-rtl.css' ), array( 'wpcw-admin' ), WPCW_VERSION );

		// Admin Scripts.
		wp_register_script( 'wpcw-admin', wpcw_js_file( 'admin.js' ), array(
			'jquery',
			'media-upload',
			'thickbox',
			'jquery-ui-core',
			'jquery-ui-widget',
			'jquery-ui-mouse',
			'jquery-ui-sortable',
			'jquery-ui-spinner',
			'jquery-ui-datepicker',
			'jquery-ui-slider',
			'wp-color-picker',
			'wpcw-admin-datepicker',
		), WPCW_VERSION, true );

		// Admin JS Vars.
		$admin_vars = apply_filters( 'wpcw_admin_js_vars', array(
			'admin_url'                          => wp_make_link_relative( admin_url( '/' ) ),
			'api_url'                            => wpcw()->api->get_rest_api_url(),
			'api_nonce'                          => wpcw()->api->get_rest_api_nonce(),
			'api_action_url'                     => wpcw()->api->get_actions_api_url(),
			'api_action_nonce'                   => wpcw()->api->get_actions_api_nonce(),
			'ajax_url'                           => admin_url( 'admin-ajax.php' ),
			'order_nonce'                        => wp_create_nonce( 'wpcw-order-nonce' ),
			'confirm_whole_course_reset'         => esc_html__( 'Are you sure you wish to reset the progress of all students on this course? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_access_change_users'        => esc_html__( 'Are you sure you wish to add access for this course for all students?', 'wp-courseware' ),
			'confirm_access_change_admins'       => esc_html__( 'Are you sure you wish to add access for this course for all admins?', 'wp-courseware' ),
			'msg_question_duplicate'             => esc_html__( 'That question already exists in this quiz, so cannot be added again.', 'wp-courseware' ),
			'name_tag_whole_pool'                => esc_html__( 'Entire Question Pool', 'wp-courseware' ),
			'confirm_modules_bulk_delete'        => esc_html__( 'Are you sure you want to delete these modules? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_courses_bulk_delete'        => esc_html__( 'Are you sure you want to delete these courses? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_quizzes_bulk_delete'        => esc_html__( 'Are you sure you want to delete these quizzes? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_questions_bulk_delete'      => esc_html__( 'Are you sure you want to delete these questions? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_orders_bulk_delete'         => esc_html__( 'Are you sure you want to delete these orders? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_subscriptions_bulk_delete'  => esc_html__( 'Are you sure you want to delete these subscriptions? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_coupons_bulk_delete'        => esc_html__( 'Are you sure you want to delete these coupons? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_remove_order_item'          => esc_html__( 'Are you sure you wish to remove this course? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_bulk_change'                => esc_html__( 'Are you sure you wish to reset the progress of the selected users? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_class_bulk_change'          => esc_html__( 'Are you sure you wish to reset the progress of the entire classroom? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_single_change'              => esc_html__( 'Are you sure you wish to reset the progress of this student? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_remove_student'             => esc_html__( 'Are you sure you wish to remove this student? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_remove_student_courses'     => esc_html__( 'Are you sure you wish to remove these students from all courses? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_remove_student_from_course' => esc_html__( 'Are you sure you wish to remove these students from this course? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_enroll_classroom'           => esc_html__( 'Are you sure you wish to enroll this classroom into the selected courses? This CANNOT be undone.', 'wp-courseware' ),
			'confirm_delete_module'              => esc_html__( 'Are you sure you wish to remove "%module_title%" from this course?', 'wp-courseware' ),
			'confirm_delete_unit'                => esc_html__( 'Are you sure you wish to remove "%unit_title%" from this course?', 'wp-courseware' ),
			'confirm_delete_quiz'                => esc_html__( 'Are you sure you wish to remove "%quiz_title%" from this course?', 'wp-courseware' ),
			'confirm_delete_orphaned_units'      => esc_html__( 'Are you sure you want to delete orphaned units? This CANNOT be undone!', 'wp-courseware' ),
			'confirm_change_instructor'          => esc_html__( 'Are you sure you want to change the instructor for this course? This action will also change the author for all Modules, Units, Quizzes, and Quiz Questions assigned to this course.', 'wp-courseware' ),
			'multiple_quiz_to_unit_error'        => esc_html__( 'Sorry, you are only allowed to have one quiz per unit. Please drag to a different Unit.', 'wp-courseware' ),
			'status_copying'                     => esc_html__( 'Copying...', 'wp-courseware' ),
			'text_success'                       => esc_html__( 'Success!', 'wp-courseware' ),
			'text_error'                         => esc_html__( 'Error!', 'wp-courseware' ),
			'confirm_text'                       => esc_html__( 'Confirmation Needed', 'wp-courseware' ),
			'builder_refreshed'                  => esc_html__( 'Course builder refreshed succssfully!', 'wp-courseware' ),
			'countries'                          => json_encode( wpcw()->countries->get_allowed_country_states() ),
			'select_state_text'                  => esc_attr__( 'Select an option&hellip;', 'wp-courseware' ),
			'currency_decimal_separator'         => wpcw_get_currency_decimal_separator(),
			'currency_format_num_decimals'       => wpcw_get_currency_decimals(),
			'currency_format_symbol'             => wpcw_get_currency_symbol(),
			'currency_format_decimal_sep'        => wpcw_get_currency_decimal_separator(),
			'currency_format_thousand_sep'       => wpcw_get_currency_thousand_separator(),
			'currency_format'                    => wpcw_get_currency_format(),
			'rounding_precision'                 => wpcw_get_rounding_precision(),
			'unit_drip_date'                     => date_i18n( 'j M Y H:i:00' ),
			'unit_drip_interval'                 => 5,
			'unit_drip_interval_type'            => 'interval_days',
			'unit_drip_date_hidden'              => date_i18n( 'Y-m-d H:i:00' ),
		) );

		// Admin Vars.
		wp_localize_script( 'wpcw-admin', 'wpcwAdminVars', $admin_vars );

		/**
		 * Action: Enqueue Scripts.
		 *
		 * @since 4.3.0
		 *
		 * @param WP_Screen $admin_screen The screen object.
		 * @param array     $admin_vars The admin javascript vars.
		 * @param Admin     $this The core admin object.
		 */
		do_action( 'wpcw_enqueue_scripts', $admin_screen, $admin_vars, $this );

		/**
		 * Action: Admin Enqueue Scripts.
		 *
		 * @since 4.6.3
		 *
		 * @param WP_Screen $admin_screen The screen object.
		 * @param array     $admin_vars The admin javascript vars.
		 * @param Admin     $this The core admin object.
		 */
		do_action( 'wpcw_admin_enqueue_scripts', $admin_screen, $admin_vars, $this );

		// Enqueue Assets
		wp_enqueue_style( 'wpcw-admin' );
		wp_enqueue_script( 'wpcw-admin' );

		// Enqueue RTL Languages
		if ( is_rtl() ) {
			wp_enqueue_style( 'wpcw-admin-rtl' );
		}
	}

	/**
	 * Plugin Action Link for the Settings.
	 *
	 * @since 4.2.0
	 *
	 * @param array  $links Defined action links.
	 * @param string $file Plugin file path.
	 *
	 * @return array $links The newly defined action links.
	 */
	public function action_links( $links, $file ) {
		if ( $file == 'wp-courseware/wp-courseware.php' ) {
			array_unshift( $links, sprintf( '<a href="%s">%s</a>', $this->pages->get_page( 'wpcw-settings' )->get_url(), esc_html__( 'Settings', 'wp-courseware' ) ) );
		}

		return $links;
	}

	/**
	 * Add Display Page Labels.
	 *
	 * Adds a label to each of the WP Courseware core pages.
	 * 'account', 'checkout', 'terms', 'courses'
	 *
	 * @since 4.3.0
	 *
	 * @param array    $post_states An array of post display states.
	 * @param \WP_Post $post The current post object.
	 */
	public function add_display_page_labels( $post_states, $post ) {
		if ( wpcw_get_page_id( 'courses' ) === $post->ID ) {
			$post_states['wpcw_page_for_courses'] = esc_html__( 'Courses Page', 'wp-courseware' );
		}

		if ( wpcw_get_page_id( 'checkout' ) === $post->ID ) {
			$post_states['wpcw_page_for_checkout'] = esc_html__( 'Checkout Page', 'wp-courseware' );
		}

		if ( wpcw_get_page_id( 'order-received' ) === $post->ID ) {
			$post_states['wpcw_page_for_order_received'] = esc_html__( 'Order Received Page', 'wp-courseware' );
		}

		if ( wpcw_get_page_id( 'order-failed' ) === $post->ID ) {
			$post_states['wpcw_page_for_order_failed'] = esc_html__( 'Order Failed Page', 'wp-courseware' );
		}

		if ( wpcw_get_page_id( 'account' ) === $post->ID ) {
			$post_states['wpcw_page_for_account'] = esc_html__( 'Student Account Page', 'wp-courseware' );
		}

		if ( wpcw_get_page_id( 'terms' ) === $post->ID ) {
			$post_states['wpcw_page_for_terms'] = esc_html__( 'Terms and Conditions Page', 'wp-courseware' );
		}

		if ( wpcw_get_page_id( 'privacy' ) === $post->ID ) {
			$post_states['wpcw_page_for_privacy'] = esc_html__( 'Privacy Policy Page', 'wp-courseware' );
		}

		return $post_states;
	}

	/**
	 * Hide Top Menu Item.
	 *
	 * @since 4.4.0
	 *
	 * @param string $name The name of the menu.
	 *
	 * @return bool|void
	 */
	public function hide_top_menu( $name ) {
		global $submenu;

		if ( ! isset( $submenu[ $name ] ) ) {
			return false;
		}

		unset( $submenu[ $name ] );
		remove_menu_page( $name );
	}

	/**
	 * Hide Menu Item.
	 *
	 * @since 4.1.0
	 *
	 * @param string $name The name of the submenu to search.
	 * @param string $id The id of the menu item to be removed.
	 *
	 * @return bool|void
	 */
	public function hide_menu( $name, $id ) {
		global $submenu;

		if ( ! isset( $submenu[ $name ] ) ) {
			return false;
		}

		foreach ( $submenu[ $name ] as $index => $details ) {
			if ( $details[2] == $id ) {
				unset( $submenu[ $name ][ $index ] );

				return;
			}
		}
	}

	/**
	 * Get Admin Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string $slug The admin page slug.
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Get Admin Hook.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_hook() {
		return $this->hook;
	}

	/**
	 * Allows import user with cyrillic usernames.
	 *
	 * @sice 4.9.0
	 */
	public function wpcw_sanitize_user( $username, $raw_username, $strict ) {
		$username = wp_strip_all_tags( $raw_username );
		$username = remove_accents( $username );
		// Kill octets
		$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );
		$username = preg_replace( '/&.+?;/', '', $username ); // Kill entities

		// If strict, reduce to ASCII and Cyrillic characters for max portability.
		if ( $strict ) {
			$username = preg_replace( '|[^a-zа-я0-9 _.\-@]|iu', '', $username );
		}

		$username = trim( $username );
		// Consolidate contiguous whitespace
		$username = preg_replace( '|\s+|', ' ', $username );

		return $username;
	}
}
