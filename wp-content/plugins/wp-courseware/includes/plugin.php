<?php
/**
 * Main WP Courseware Plugin Class.
 *
 * Main class plugin file used to
 * bootstrap the rest of the plugin.
 *
 * @package WPCW
 * @since 4.3.0
 */

use WPCW\Controllers\Coupons;
use WPCW\Controllers\Courses;
use WPCW\Controllers\Logs;
use WPCW\Controllers\Membership;
use WPCW\Controllers\Modules;
use WPCW\Controllers\Notes;
use WPCW\Controllers\Orders;
use WPCW\Controllers\Progress;
use WPCW\Controllers\Questions;
use WPCW\Controllers\Quizzes;
use WPCW\Controllers\Students;
use WPCW\Controllers\Subscriptions;
use WPCW\Controllers\Units;
use WPCW\Core\Admin;
use WPCW\Core\Ajax;
use WPCW\Core\Api;
use WPCW\Core\Blocks;
use WPCW\Core\Cache;
use WPCW\Core\Cart;
use WPCW\Core\Certificates;
use WPCW\Core\Checkout;
use WPCW\Core\Countries;
use WPCW\Core\Cron;
use WPCW\Core\Database;
use WPCW\Core\Deactivate;
use WPCW\Core\Emails;
use WPCW\Core\Enrollment;
use WPCW\Core\Extensions;
use WPCW\Core\Form;
use WPCW\Core\Frontend;
use WPCW\Core\Gateways;
use WPCW\Core\HTTPS;
use WPCW\Core\i18n;
use WPCW\Core\Install;
use WPCW\Core\Legacy;
use WPCW\Core\License;
use WPCW\Core\Privacy;
use WPCW\Core\Query;
use WPCW\Core\Reports;
use WPCW\Core\Roles;
use WPCW\Core\Session;
use WPCW\Core\Settings;
use WPCW\Core\Shortcodes;
use WPCW\Core\Styles;
use WPCW\Core\Support;
use WPCW\Core\Tools;
use WPCW\Core\Tour;
use WPCW\Core\Tracker;
use WPCW\Core\Widgets;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * WP Courseware plugin class.
 *
 * The main plugin handler class is responsible for initializing WP Courseware.
 * The class registers and all the components required to run the plugin.
 *
 * @since 4.3.0
 */
final class WPCW_Plugin {

	/**
	 * @var i18n The core i18n object.
	 * @since 4.3.0
	 */
	public $i18n;

	/**
	 * @var Legacy The core legacy object.
	 * @since 4.1.0
	 */
	public $legacy;

	/**
	 * @var Settings The core settings object.
	 * @since 4.1.0
	 */
	public $settings;

	/**
	 * @var Database The core database object.
	 * @since 4.3.0
	 */
	public $database;

	/**
	 * @var Admin The core admin object.
	 * @since 4.1.0
	 */
	public $admin;

	/**
	 * @var Frontend The core frontend object.
	 * @since 4.1.0
	 */
	public $frontend;

	/**
	 * @var Shortcodes The shortcodes object.
	 * @since 4.3.0
	 */
	public $shortcodes;

	/**
	 * @var Blocks The Blocks object.
	 * @since 4.5.1
	 */
	public $blocks;

	/**
	 * @var Widgets The widgets object.
	 * @since 4.3.0
	 */
	public $widgets;

	/**
	 * @var Roles The core roles object.
	 * @since 4.3.0
	 */
	public $roles;

	/**
	 * @var Query The core query object.
	 * @since 4.3.0
	 */
	public $query;

	/**
	 * @var Api The core api object.
	 * @since 4.1.0
	 */
	public $api;

	/**
	 * @var Ajax The core ajax object.
	 * @since 4.3.0
	 */
	public $ajax;

	/**
	 * @var Cache The core cache object.
	 * @since 4.3.0
	 */
	public $cache;

	/**
	 * @var Cron The cron object.
	 * @since 4.3.0
	 */
	public $cron;

	/**
	 * @var Session The core session object.
	 * @since 4.3.0
	 */
	public $session;

	/**
	 * @var Countries The core countries object.
	 * @since 4.3.0
	 */
	public $countries;

	/**
	 * @var HTTPS The core https class.
	 * @since 4.3.0
	 */
	public $https;

	/**
	 * @var License The core license object.
	 * @since 4.1.0
	 */
	public $license;

	/**
	 * @var Tools The core tools object.
	 * @since 4.1.0
	 */
	public $tools;

	/**
	 * @var Privacy the core privacy object.
	 * @since 4.3.0
	 */
	public $privacy;

	/**
	 * @var Tracker The core tracker object.
	 * @since 4.4.0
	 */
	public $tracker;

	/**
	 * @var Support The core support information object.
	 * @since 4.1.0
	 */
	public $support;

	/**
	 * @var Install The core install object.
	 * @since 4.3.0
	 */
	public $install;

	/**
	 * @var Deactivate The core deactivate object.
	 * @since 4.3.0
	 */
	public $deactivate;

	/**
	 * @var Tour The tour object.
	 * @since 4.6.3
	 */
	public $tour;

	/**
	 * @var Extensions The extensions object.
	 * @since 4.3.0
	 */
	public $extensions;

	/**
	 * @var Certificates The core certificates class.
	 * @since 4.6.3
	 */
	public $certificates;

	/**
	 * @var Emails The core emails class.
	 * @since 4.6.3
	 */
	public $emails;

	/**
	 * @var Gateways The core gateways object.
	 * @since 4.6.3
	 */
	public $gateways;

	/**
	 * @var Reports The core reports object.
	 * @since 4.6.3
	 */
	public $reports;

	/**
	 * @var Styles The core styles object.
	 * @since 4.6.3
	 */
	public $styles;

	/**
	 * @var Enrollment The core enrollment object.
	 * @since 4.6.3
	 */
	public $enrollment;

	/**
	 * @var Cart The core cart object.
	 * @since 4.6.3
	 */
	public $cart;

	/**
	 * @var Checkout The core checkout object.
	 * @since 4.6.3
	 */
	public $checkout;

	/**
	 * @var Courses The courses controller.
	 * @since 4.1.0
	 */
	public $courses;

	/**
	 * @var Modules The modules controller.
	 * @since 4.1.0
	 */
	public $modules;

	/**
	 * @var Units The units controller.
	 * @since 4.1.0
	 */
	public $units;

	/**
	 * @var Questions The questions controller.
	 * @since 4.1.0
	 */
	public $questions;

	/**
	 * @var Quizzes The quizzed controller.
	 * @since 4.1.0
	 */
	public $quizzes;

	/**
	 * @var Students The students controller.
	 * @since 4.1.0
	 */
	public $students;

	/**
	 * @var Orders The orders controller.
	 * @since 4.3.0
	 */
	public $orders;

	/**
	 * @var Progress The progress controller.
	 * @since 4.6.4
	 */
	public $progress;

	/**
	 * @var Subscriptions The subscriptions controller.
	 * @since 4.3.0
	 */
	public $subscriptions;

	/**
	 * @var Coupons The coupons controller.
	 * @since 4.5.0
	 */
	public $coupons;

	/**
	 * @var Logs The logs controller.
	 * @since 4.3.0
	 */
	public $logs;

	/**
	 * @var Notes The notes controller.
	 * @since 4.3.0
	 */
	public $notes;

	/**
	 * @var Plugin Plugin Singleton Instance.
	 * @since 4.1.0
	 */
	private static $instance = null;

	/**
	 * Plugin Singleton Instance.
	 *
	 * @since 4.1.0
	 *
	 * @return null|Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->includes();
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Plugin Includes.
	 *
	 * @since 4.1.0
	 */
	protected function includes() {
		spl_autoload_register( array( $this, 'autoloader' ) );

		require_once WPCW_PATH . 'includes/common/constants.php';
		require_once WPCW_PATH . 'includes/common/globals.php';

		require_once WPCW_PATH . 'includes/functions/core.php';
		require_once WPCW_PATH . 'includes/functions/backcompat.php';
		require_once WPCW_PATH . 'includes/functions/deprecated.php';
		require_once WPCW_PATH . 'includes/functions/utilities.php';
		require_once WPCW_PATH . 'includes/functions/template.php';
		require_once WPCW_PATH . 'includes/functions/conditional.php';
		require_once WPCW_PATH . 'includes/functions/formatting.php';
		require_once WPCW_PATH . 'includes/functions/admin.php';
		require_once WPCW_PATH . 'includes/functions/notices.php';
		require_once WPCW_PATH . 'includes/functions/cart.php';
		require_once WPCW_PATH . 'includes/functions/checkout.php';
		require_once WPCW_PATH . 'includes/functions/validation.php';
		require_once WPCW_PATH . 'includes/functions/ajax.php';
		require_once WPCW_PATH . 'includes/functions/logs.php';
		require_once WPCW_PATH . 'includes/functions/courses.php';
		require_once WPCW_PATH . 'includes/functions/modules.php';
		require_once WPCW_PATH . 'includes/functions/units.php';
		require_once WPCW_PATH . 'includes/functions/quizzes.php';
		require_once WPCW_PATH . 'includes/functions/orders.php';
		require_once WPCW_PATH . 'includes/functions/coupons.php';
		require_once WPCW_PATH . 'includes/functions/students.php';
		require_once WPCW_PATH . 'includes/functions/subscriptions.php';
		require_once WPCW_PATH . 'includes/functions/enrollment.php';
		require_once WPCW_PATH . 'includes/functions/reports.php';
		require_once WPCW_PATH . 'includes/functions/progress.php';
		require_once WPCW_PATH . 'includes/functions/certificates.php';

		require_once WPCW_PATH . 'includes/core/addons.php';

		require_once WPCW_LEGACY_PATH . 'functions.php';
		require_once WPCW_LEGACY_PATH . 'builder/formbuilder.php';
		require_once WPCW_LEGACY_PATH . 'builder/pagebuilder.php';
		require_once WPCW_LEGACY_PATH . 'builder/tablebuilder.php';
		require_once WPCW_LEGACY_PATH . 'builder/easyform.php';
		require_once WPCW_LEGACY_PATH . 'builder/recordsform.php';
		require_once WPCW_LEGACY_PATH . 'builder/settingsform.php';
		require_once WPCW_LEGACY_PATH . 'classes/userprogress.php';
		require_once WPCW_LEGACY_PATH . 'classes/courseprogress.php';
		require_once WPCW_LEGACY_PATH . 'classes/quizcustomfeedback.php';
		require_once WPCW_LEGACY_PATH . 'classes/dripfeed.php';
		require_once WPCW_LEGACY_PATH . 'quiz/base.php';
		require_once WPCW_LEGACY_PATH . 'quiz/multi.php';
		require_once WPCW_LEGACY_PATH . 'quiz/truefalse.php';
		require_once WPCW_LEGACY_PATH . 'quiz/open.php';
		require_once WPCW_LEGACY_PATH . 'quiz/upload.php';
		require_once WPCW_LEGACY_PATH . 'quiz/random.php';
		require_once WPCW_LEGACY_PATH . 'classes/quizresults.php';
		require_once WPCW_LEGACY_PATH . 'classes/unitfrontend.php';

		require_once WPCW_LEGACY_PATH . 'classes/coursemap.php';
		require_once WPCW_LEGACY_PATH . 'classes/import.php';
		require_once WPCW_LEGACY_PATH . 'classes/export.php';
		require_once WPCW_LEGACY_PATH . 'admin/functions.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-course-dashboard.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-course-modify.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-course-ordering.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-gradebook.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-import-export.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-module-modify.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-question-modify.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-question-pool.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-quiz-modify.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-quiz-summary.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-unit-convert.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-course-access.php';
		require_once WPCW_LEGACY_PATH . 'pages/page-user-progress.php';
		require_once WPCW_LEGACY_PATH . 'frontend/functions.php';
		require_once WPCW_LEGACY_PATH . 'frontend/templates.php';

		require_once WPCW_LEGACY_PATH . 'classes/certificate.php';
		require_once WPCW_LEGACY_PATH . 'pdf/certificates.php';
		require_once WPCW_LEGACY_PATH . 'pdf/results.php';
		require_once WPCW_LEGACY_PATH . 'ajax.php';
		require_once WPCW_LEGACY_PATH . 'shortcodes.php';
		require_once WPCW_LEGACY_PATH . 'setup.php';
		require_once WPCW_PATH . 'includes/block-functions/student-name.php';
		require_once WPCW_PATH . 'includes/block-functions/text-field.php';
		require_once WPCW_PATH . 'includes/block-functions/course-title.php';
		require_once WPCW_PATH . 'includes/block-functions/instructor-name.php';
		require_once WPCW_PATH . 'includes/block-functions/cumulative-grade.php';
		require_once WPCW_PATH . 'includes/block-functions/image.php';
		require_once WPCW_PATH . 'includes/block-functions/expiry-date.php';
		require_once WPCW_PATH . 'includes/block-functions/expiry-date.php';
		require_once WPCW_PATH . 'includes/block-functions/certificate-number.php';
		require_once WPCW_PATH . 'includes/block-functions/line-separator.php';
	}

	/**
	 * Plugin Setup.
	 *
	 * @since 4.1.0
	 */
	protected function setup() {
		// Set Up Early Objects.
		$this->i18n       = new i18n();
		$this->settings   = new Settings();
		$this->database   = new Database();
		$this->install    = new Install();
		$this->deactivate = new Deactivate();

		// Setup Objects.
		$this->setup_core();
		$this->setup_controllers();

		// Load Early Objects.
		$this->i18n->load();
		$this->settings->load();
		$this->database->load();
		$this->install->load();
		$this->deactivate->load();

		// All other objects will be loaded late on 'plugins_loaded'
		add_action( 'plugins_loaded', array( $this, 'load' ) );

		// Hook onto init to register items later in the process.
		add_action( 'init', array( $this, 'init' ), 0 );

		// Initiate core objects when switching blog.
		add_action( 'switch_blog', array( $this, 'switch_blog' ) );

		// Late filter on the_content to restrict page/post access to Course members
		add_filter( 'the_content', array( $this, 'maybe_restrict_access' ) );
	}

	/**
	 * Enforcement of restricted access to WP pages/posts based on user's course membership
	 *
	 * @return string       The HTML content to display within the theme
	 * @since 4.7.0
	 */
	public function maybe_restrict_access( $content ) {

		global $post;

		// If this is an admin page, do not restrict access to the content
		// Also get out of here if the post object isn't yet available
		if ( is_admin() || ! isset( $post ) ) {
			return $content;
		}

		global $wp;

		// Do not consider requests for /favicon.ico
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		if ( 1 === preg_match( '/\/favicon.ico$/', $current_url ) ) {
			return $content;
		}

		// We have a post and this is not /favicon.ico or an admin screen, check for user restrictions
		$restricted_options = get_post_meta( $post->ID, 'wpcw_course_restriction', true );
		if ( ! is_array( $restricted_options ) ) {
			$restricted_options = array( $restricted_options );
		}
		// There are no WPCW course restrictions on the page, so allow the content
		if ( count( $restricted_options ) < 1 || ( count( $restricted_options ) == 1 && isset( $restricted_options[0] ) && strlen( $restricted_options[0] ) < 1 ) ) {
			return $content;
		}

		// ...otherwise, check the user's course subscriptions
		$current_user = wp_get_current_user();
		// content on page is restricted and the user is not logged in - show the "You need to login" message
		if ( ! is_object( $current_user ) || ! isset( $current_user->ID ) || $current_user->ID < 1 ) {
			return '<p>' .
			get_option(
				'wpcw_restricted_content_default',
				esc_html__( 'This content is only available to course members. Login to your account or enroll into the course to see content.', 'wp-courseware' )
			) .
			'</p>';
		}

		// get the user's enrolled courses
		$courses         = $this->courses->get_courses_by_student( $current_user->ID );
		$user_may_access = false;
		// see if this page's required courses match
		foreach ( $courses as $course ) {

			// break the loop if we've already found what we're looking for
			if ( $user_may_access ) {
				break; }

			if ( is_object( $course ) && isset( $course->course_id ) ) {
				// user is allowed on this page
				if ( in_array( $course->course_id, $restricted_options ) ) {
					$user_may_access = true;
					break;
				}
			}
		}

		// Final decision:
		// The user is OK for this page, return the content
		if ( $user_may_access ) {
			return $content;
		} else { // User may not access the page, show an informative message instead
			return '<p>' .
			get_option(
				'wpcw_restricted_content_loggedin',
				esc_html__( 'This content is only available to course members. Enroll into the course to see content.', 'wp-courseware' )
			) .
			'</p>';
		}
	}

	/**
	 * Setup Core.
	 *
	 * @since 4.6.3
	 */
	public function setup_core() {
		$this->legacy       = new Legacy();
		$this->admin        = new Admin();
		$this->frontend     = new Frontend();
		$this->shortcodes   = new Shortcodes();
		$this->blocks       = new Blocks();
		$this->widgets      = new Widgets();
		$this->roles        = new Roles();
		$this->query        = new Query();
		$this->api          = new Api();
		$this->ajax         = new Ajax();
		$this->cache        = new Cache();
		$this->cron         = new Cron();
		$this->session      = new Session();
		$this->countries    = new Countries();
		$this->https        = new HTTPS();
		$this->license      = new License();
		$this->tools        = new Tools();
		$this->tracker      = new Tracker();
		$this->privacy      = new Privacy();
		$this->support      = new Support();
		$this->tour         = new Tour();
		$this->extensions   = new Extensions();
		$this->certificates = new Certificates();
		$this->gateways     = new Gateways();
		$this->reports      = new Reports();
		$this->styles       = new Styles();
		$this->enrollment   = new Enrollment();
		$this->cart         = new Cart();
		$this->checkout     = new Checkout();
	}

	/**
	 * Load Core.
	 *
	 * @since 4.6.3
	 */
	public function load_core() {
		$this->legacy->load();
		$this->admin->load();
		$this->frontend->load();
		$this->shortcodes->load();
		$this->blocks->load();
		$this->widgets->load();
		$this->roles->load();
		$this->api->load();
		$this->ajax->load();
		$this->cache->load();
		$this->cron->load();
		$this->session->load();
		$this->query->load();
		$this->https->load();
		$this->license->load();
		$this->tools->load();
		$this->tracker->load();
		$this->privacy->load();
		$this->support->load();
		$this->tour->load();
		$this->extensions->load();
		$this->certificates->load();
		$this->emails->load();
		$this->reports->load();
		$this->gateways->load();
		$this->styles->load();
		$this->enrollment->load();
		$this->cart->load();
		$this->checkout->load();
	}

	/**
	 * Setup Controllers.
	 *
	 * @since 4.6.3
	 */
	public function setup_controllers() {
		$this->courses       = new Courses();
		$this->modules       = new Modules();
		$this->units         = new Units();
		$this->quizzes       = new Quizzes();
		$this->questions     = new Questions();
		$this->students      = new Students();
		$this->orders        = new Orders();
		$this->progress      = new Progress();
		$this->subscriptions = new Subscriptions();
		$this->coupons       = new Coupons();
		$this->emails        = new Emails();
		$this->logs          = new Logs();
		$this->notes         = new Notes();
	}

	/**
	 * Load Controllers.
	 *
	 * @since 4.6.3
	 */
	public function load_controllers() {
		$this->courses->load();
		$this->modules->load();
		$this->units->load();
		$this->quizzes->load();
		$this->questions->load();
		$this->students->load();
		$this->orders->load();
		$this->progress->load();
		$this->subscriptions->load();
		$this->coupons->load();
		$this->logs->load();
		$this->notes->load();
	}

	/**
	 * Plugin Load.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		$this->load_core();
		$this->load_controllers();

		/**
		 * Action: WP Courseware is fully loaded.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_loaded' );
	}

	/**
	 * Plugin Init.
	 *
	 * @since 4.3.0
	 */
	public function init() {
		/**
		 * Action: WP Courseware is initialized.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_init' );
	}

	/**
	 * Switch Blog.
	 *
	 * @since 4.6.3
	 */
	public function switch_blog() {
		$this->setup_controllers();

		/**
		 * Action: WP Courseware switch blog.
		 *
		 * @since 4.6.3
		 */
		do_action( 'wpcw_switch_blog' );
	}

	/**
	 * Get Plugin Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'WP Courseware';
	}

	/**
	 * Get Plugin Company Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string The plugin company name.
	 */
	public function get_company_name() {
		return esc_attr( apply_filters( 'wpcw_company_name', 'Fly Plugins' ) );
	}

	/**
	 * Get Plugin Company Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The plugin company url.
	 */
	public function get_company_url() {
		return esc_url( apply_filters( 'wpcw_company_url', 'https://flyplugins.com/' ) );
	}

	/**
	 * Get Plugin Company Member Portal Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The plugin company member portal url.
	 */
	public function get_member_portal_url() {
		return esc_url( apply_filters( 'wpcw_company_member_portal_url', 'https://flyplugins.com/member-portal/' ) );
	}

	/**
	 * Get Plugin Company Member Portal Support Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The plugin company member portal support url.
	 */
	public function get_member_portal_support_url() {
		return esc_url( apply_filters( 'wpcw_company_member_portal_support_url', 'https://flyplugins.com/member-portal/support/' ) );
	}

	/**
	 * Member Portal License Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The plugin company member portal license url.
	 */
	public function get_member_portal_license_url() {
		return esc_url( apply_filters( 'wpcw_company_member_portal_license_url', 'https://flyplugins.com/member-portal/license-keys/' ) );
	}

	/**
	 * Get the plugin template path.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'wpcw_template_path', trailingslashit( 'wp-courseware' ) );
	}

	/**
	 * Plugin Class Autoloader.
	 *
	 * @since 4.1.0
	 *
	 * @param string $class The class name.
	 *
	 * @return bool|mixed|void
	 */
	public function autoloader( $class ) {
		if ( 0 !== strpos( $class, 'WPCW\\', 0 ) ) {
			return;
		}

		static $loaded = array();

		if ( isset( $loaded[ $class ] ) ) {
			return $loaded[ $class ];
		}

		$class = str_replace( 'WPCW\\', '', $class );
		$class = strtolower( $class );
		$class = str_replace( '_', '-', $class );
		$class = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__ . DIRECTORY_SEPARATOR . $class . '.php' );

		if ( false === ( $class = realpath( $class ) ) ) {
			return false;
		}

		return $loaded[ $class ] = (bool) require $class;
	}

	/**
	 * Plugin Constructor.
	 *
	 * @since 4.1.0
	 */
	private function __construct() {
		/* Do Nothing */ }

	/**
	 * Disable plugin cloning.
	 *
	 * @since 4.1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-courseware' ), '4.1.0' ); }

	/**
	 * Disable plugin unserializing.
	 *
	 * @since 4.1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-courseware' ), '4.1.0' ); }

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since 4.1.0
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong( "WPCW::{$method}", esc_html__( 'WP Courseware class method does not exist.', 'wp-courseware' ), '4.1.0' );
		unset( $method, $args );

		return null;
	}
}
