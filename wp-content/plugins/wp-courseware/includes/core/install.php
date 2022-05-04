<?php
/**
 * WP Courseware Install.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WPCW_queue_dripfeed;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Install
 *
 * @since 4.3.0
 */
final class Install {

	/**
	 * @var Process The updater process.
	 * @since 4.4.0
	 */
	protected $updater;

	/**
	 * @var array Update callbacks.
	 * @since 4.4.0
	 */
	protected $updates = array(
		'4.3.0' => array(
			'wpcw_update_430_ecommerce',
		),
		'4.4.0' => array(
			'wpcw_update_440_courses',
		),
		'4.4.1' => array(
			'wpcw_update_441_roles',
		),
		'4.4.2' => array(
			'wpcw_update_442_fix_courses',
		),
	);

	/**
	 * @var array Update Notices.
	 * @since 4.4.3
	 */
	protected $notices = array();

	/**
	 * @var array Notices Map.
	 * @since 4.4.3
	 */
	protected $notices_map = array(
		'update' => 'update_notice',
	);

	/**
	 * @var bool Flush rewrite rules?
	 * @since 4.4.0
	 */
	protected $flush_rules = false;

	/**
	 * Load Activate Class.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Add Notices.
		$this->notices = $this->get_update_notices();

		// Install
		register_activation_hook( WPCW_FILE, array( $this, 'install' ) );

		// Actions.
		add_action( 'wpcw_init', array( $this, 'check_version' ), 5 );
		add_action( 'wpcw_init', array( $this, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( $this, 'update_actions' ) );

		// Notices
		add_action( 'wp_loaded', array( $this, 'hide_update_notices' ) );
		add_action( 'admin_print_styles', array( $this, 'display_update_notices' ) );
		add_action( 'shutdown', array( $this, 'store_update_notices' ) );
	}

	/** Version Methods ----------------------------------------------------- */

	/**
	 * Get Version.
	 *
	 * @since 4.4.3
	 *
	 * @return null|string The version.
	 */
	public function get_version() {
		return get_option( 'wpcw_plugin_version', null );
	}

	/**
	 * Update Version.
	 *
	 * @since 4.3.3
	 */
	public function update_version() {
		delete_option( 'wpcw_plugin_version' );
		add_option( 'wpcw_plugin_version', WPCW_VERSION );
	}

	/**
	 * Get DB Version.
	 *
	 * @since 4.4.3
	 *
	 * @return null|string The version.
	 */
	public function get_db_version() {
		return get_option( 'wpcw_db_version', null );
	}

	/**
	 * Update DB Version.
	 *
	 * @since 4.4.3
	 */
	public function update_db_version() {
		delete_option( 'wpcw_db_version' );
		add_option( 'wpcw_db_version', WPCW_DB_VERSION );
	}

	/**
	 * Is New Install?
	 *
	 * @since 4.4.3
	 *
	 * @return bool
	 */
	protected function is_new_install() {
		return ( is_null( $this->get_version() ) && is_null( $this->get_db_version() ) );
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since 4.4.3
	 *
	 * @return boolean
	 */
	protected function needs_db_update() {
		$current_db_version = $this->get_db_version();

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, max( array_keys( $this->updates ) ), '<' );
	}

	/**
	 * Check Version?
	 *
	 * @since 4.4.3
	 */
	public function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'wpcw_plugin_version' ), WPCW_VERSION, '<' ) ) {
			$this->install();

			/**
			 * Action: WP Courseware Updated.
			 *
			 * @since 4.4.3
			 */
			do_action( 'wpcw_upgraded' );
			do_action( 'wpcw_updated' );
		}
	}

	/** Install Methods ----------------------------------------------------- */

	/**
	 * Install Plugin.
	 *
	 * @since 4.3.0
	 */
	public function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'wpcw_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'wpcw_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		wpcw_maybe_define_constant( 'WPCW_INSTALLING', true );

		// Run Install Methods.
		$this->register_settings();
		$this->add_roles();
		$this->upload_directory();
		$this->setup_dripfeed();
		$this->tag_cleanup();
		$this->remove_rewrite_rules_flag();
		$this->maybe_install_ecommerce();
		$this->maybe_send_initial_checkin();
		$this->update_version();
		$this->maybe_update_db_version();

		// Done. Delete the initial transient.
		delete_transient( 'wpcw_installing' );

		/**
		 * Action: Flush Rewrite Rules.
		 *
		 * @since 4.4.3
		 */
		do_action( 'wpcw_flush_rewrite_rules' );

		/**
		 * Action: WP Courseware Installed.
		 *
		 * @since 4.4.3
		 */
		do_action( 'wpcw_installed' );
	}

	/**
	 * Register Settings.
	 *
	 * @since 4.4.3
	 */
	protected function register_settings() {
		wpcw()->settings->register_settings();
	}

	/**
	 * Add Roles.
	 *
	 * @since 4.4.3
	 */
	protected function add_roles() {
		$roles = new Roles();
		$roles->add_roles();
		$roles->add_caps();
	}

	/**
	 * Tag Cleanup.
	 *
	 * @since 4.4.3
	 */
	protected function tag_cleanup() {
		WPCW_tag_cleanup();
	}

	/**
	 * Upload Directory.
	 *
	 * @since 4.4.0
	 */
	protected function upload_directory() {
		wpcw_create_upload_directory();
	}

	/**
	 * Setup Dripfeed.
	 *
	 * @since 4.4.3
	 */
	protected function setup_dripfeed() {
		// Setup or clear the scheduler for the notifications timers. If the setting is 'never'
		// then clear the scheduler. If it's anything else, then add it.
		$dripfeedSetting = WPCW_TidySettings_getSettingSingle( WPCW_DATABASE_SETTINGS_KEY, 'cron_time_dripfeed' );
		WPCW_queue_dripfeed::installNotificationHook_dripfeed( ( ! empty( $dripfeedSetting ) ) ? $dripfeedSetting : 'twicedaily' );
	}

	/**
	 * Maybe Install E-Commerce.
	 *
	 * @since 4.4.3
	 */
	protected function maybe_install_ecommerce() {
		if ( $this->can_install_ecommerce() ) {
			$this->install_pages();
		}
	}

	/**
	 * Can Install E-Commerce?
	 *
	 * @since 4.4.5
	 *
	 * @return bool True if the pages can be installed.
	 */
	protected function can_install_ecommerce() {
		if ( ! function_exists( 'wpcw_is_ecommerce_integration_active' ) ) {
			require_once WPCW_PATH . 'includes/functions/core.php';
		}

		return ( ! wpcw_is_ecommerce_integration_active() ) ? true : false;
	}

	/**
	 * Install Initial Pages.
	 *
	 * @since 4.4.0
	 */
	protected function install_pages() {
		$pages = array( 'courses_page', 'checkout_page', 'order_received_page', 'order_failed_page', 'terms_page', 'account_page' );

		foreach ( $pages as $page ) {
			$page_id = wpcw_get_setting( $page );

			if ( wpcw_page_exists( $page_id ) ) {
				continue;
			}

			switch ( $page ) {
				case 'courses_page' :
					$page_id = wpcw()->courses->create_courses_page();
					break;
				case 'checkout_page' :
					$page_id = wpcw()->checkout->create_checkout_page();
					break;
				case 'order_received_page' :
					$page_id = wpcw()->checkout->create_checkout_order_received_page();
					break;
				case 'order_failed_page' :
					$page_id = wpcw()->checkout->create_checkout_order_failed_page();
					break;
				case 'terms_page' :
					$page_id = wpcw()->checkout->create_checkout_terms_page();
					break;
				case 'account_page' :
					$page_id = wpcw()->students->create_students_account_page();
					break;
			}

			if ( 0 !== absint( $page_id ) ) {
				wpcw_update_setting( $page, absint( $page_id ) );
			}
		}

		add_action( 'shutdown', array( $this, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Remove Rewrite Rules Flag.
	 *
	 * @since 4.4.3
	 */
	public function remove_rewrite_rules_flag() {
		wpcw_remove_flush_rewrite_rules_flag();
	}

	/**
	 * Maybe Send Initial Checkin.
	 *
	 * @since 4.4.3
	 */
	protected function maybe_send_initial_checkin() {
		wp_schedule_single_event( time() + 60, 'wpcw_tracker_send_initial_checkin' );
	}

	/** Updater Methods ----------------------------------------------------- */

	/**
	 * Get Background Updater.
	 *
	 * @since 4.4.3
	 *
	 * @return Process The process updater.
	 */
	protected function get_background_updater() {
		$action = 'wpcw_upgrader';
		$prefix = 'wpcw_' . get_current_blog_id();

		return new Process( $action, $prefix );
	}

	/**
	 * Initialize Background Upgrader.
	 *
	 * @since 4.4.3
	 */
	public function init_background_updater() {
		$this->updater = $this->get_background_updater();
	}

	/**
	 * Maybe Update.
	 *
	 * @since 4.4.3
	 */
	public function maybe_update_db_version() {
		if ( $this->needs_db_update() ) {
			if ( apply_filters( 'wpcw_enable_auto_update_db', false ) ) {
				$this->init_background_updater();
				$this->update();
			} else {
				$this->add_update_notice( 'update' );
			}
		} else {
			$this->update_db_version();
		}
	}

	/**
	 * Update WP Courseware.
	 *
	 * @since 4.3.0
	 */
	public function update() {
		// Check one last time.
		if ( ! $this->needs_db_update() ) {
			return;
		}

		// Get Installed Version.
		$installed_version = $this->get_db_version();
		$current_version   = WPCW_DB_VERSION;
		$update_queued     = false;

		// Log It.
		$this->log( sprintf( 'Updating from version %s to %s.', $installed_version, $current_version ) );

		// Check for upgrade callbacks.
		foreach ( $this->updates as $version => $upgrade_callbacks ) {
			if ( version_compare( $installed_version, $version, '<' ) ) {
				foreach ( $upgrade_callbacks as $upgrade_callback ) {
					$this->log( sprintf( 'Queuing %s - %s', $version, $upgrade_callback ) );
					$this->updater->push_to_queue( array( $this, $upgrade_callback ) );
					$update_queued = true;
				}
			}
		}

		// Run Updates that are queued.
		if ( $update_queued ) {
			$this->updater->save()->dispatch();
		}
	}

	/**
	 * Update Actions.
	 *
	 * @since 4.4.3
	 */
	public function update_actions() {
		if ( ! empty( $_GET['do_update_wpcw'] ) ) {
			$this->update();
			$this->add_update_notice( 'update' );
			update_option( 'wpcw_update_notices', $this->notices );
			wp_safe_redirect( admin_url( 'admin.php?page=wpcw-settings&do_updating_wpcw=true' ) );
		}

		if ( ! empty( $_GET['force_update_wpcw'] ) ) {
			$this->log( 'Force Running Update' );

			// Get Current Blog Id.
			$blog_id = get_current_blog_id();

			// Run Action wpcw_1_wpcw_upgrader_cron.
			do_action( 'wpcw_' . $blog_id . '_wpcw_upgrader_cron' );

			// Redirect.
			wp_safe_redirect( admin_url( 'admin.php?page=wpcw-settings' ) );
			exit;
		}
	}

	/** Update Notice Methods ----------------------------------------------------- */

	/**
	 * Display Update Notices.
	 *
	 * @since 4.4.3
	 */
	public function display_update_notices() {
		if ( empty( $this->notices ) ) {
			return;
		}

		if ( ! wpcw()->admin->is_allowed_page() ) {
			return;
		}

		foreach ( $this->notices as $notice ) {
			if ( ! empty( $this->notices_map[ $notice ] ) && apply_filters( 'wpcw_show_admin_notice', true, $notice ) ) {
				add_action( 'admin_notices', array( $this, $this->notices_map[ $notice ] ) );
			}
		}
	}

	/**
	 * Get Update Notices.
	 *
	 * @since 4.4.3
	 *
	 * @return array|null The array of updated notices.
	 */
	protected function get_update_notices() {
		return get_option( 'wpcw_update_notices', array() );
	}

	/**
	 * Remove Update Notices.
	 *
	 * @since 4.4.3
	 */
	protected function remove_update_notices() {
		$this->notices = array();
	}

	/**
	 * Add Update Notice.
	 *
	 * @since 4.4.3
	 *
	 * @param string $name The update notice name.
	 */
	protected function add_update_notice( $name ) {
		$this->notices = array_unique( array_merge( $this->notices, array( $name ) ) );
	}

	/**
	 * Remove Update Notice.
	 *
	 * @since 4.4.3
	 *
	 * @param string $name Notice name.
	 */
	protected function remove_update_notice( $name ) {
		$this->notices = array_diff( $this->notices, array( $name ) );
	}

	/**
	 * Hide Update Notices.
	 *
	 * @since 4.4.3
	 */
	public function hide_update_notices() {
		if ( isset( $_GET['wpcw-hide-update-notice'] ) && isset( $_GET['_wpcw_update_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpcw_update_notice_nonce'] ) ), 'wpcw_hide_update_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'wp-courseware' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'wp-courseware' ) );
			}

			$hide_notice = sanitize_text_field( wp_unslash( $_GET['wpcw-hide-update-notice'] ) );

			$this->remove_update_notice( $hide_notice );

			do_action( 'wpcw_hide_update_notice' );

			update_option( 'wpcw_update_notices', $this->notices );
			wp_safe_redirect( admin_url( 'admin.php?page=wpcw-settings' ) );
			exit;
		}
	}

	/**
	 * Store Update Notices.
	 *
	 * @since 4.4.3
	 */
	public function store_update_notices() {
		update_option( 'wpcw_update_notices', $this->notices );
	}

	/**
	 * Update Notice.
	 *
	 * @since 4.4.3
	 */
	public function update_notice() {
		if ( version_compare( get_option( 'wpcw_db_version' ), WPCW_DB_VERSION, '<' ) ) {
			$updater = $this->get_background_updater();
			if ( $updater->is_updating() || ! empty( $_GET['do_updating_wpcw'] ) ) {
				echo $this->get_updating_notice();
			} else {
				echo $this->get_needs_update_notice();
			}
		} else {
			echo $this->get_update_completed_notice();
		}
	}

	/**
	 * Get Needs Update Notice.
	 *
	 * @since 4.4.3
	 *
	 * @return string The needs update notice.
	 */
	public function get_needs_update_notice() {
		ob_start();
		?>
		<div class="wpcw-admin-notice wpcw-update-notice notice notice-info">
			<p>
				<span
					class="wpcw-p"><strong><?php esc_html_e( 'WP Courseware Data Update', 'wp-courseware' ); ?></strong> &#8211; <?php esc_html_e( 'We need to update your database to the latest version.', 'wp-courseware' ); ?></span>
				<a href="<?php echo esc_url( add_query_arg( 'do_update_wpcw', 'true', admin_url( 'admin.php?page=wpcw-settings' ) ) ); ?>" class="wpcw-update-now button-primary">
					<i class="wpcw-fas wpcw-fa-database left"></i>
					<?php esc_html_e( 'Run the Updater', 'wp-courseware' ); ?>
				</a>
			</p>
		</div>
		<script type="text/javascript">
			jQuery( '.wpcw-update-now' ).click( 'click', function () {
				return window.confirm( '<?php echo esc_js( esc_html__( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'wp-courseware' ) ); ?>' );
			} );
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Updating Notice.
	 *
	 * @since 4.4.3
	 *
	 * @return string The updating notice.
	 */
	public function get_updating_notice() {
		ob_start();
		?>
		<div class="wpcw-admin-notice wpcw-update-notice notice notice-info">
			<p>
				<strong><?php esc_html_e( 'WP Courseware Data Update', 'woocommerce' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'wp-courseware' ); ?>
				<a href="<?php echo esc_url( add_query_arg( 'force_update_wpcw', 'true', admin_url( 'admin.php?page=wpcw-settings' ) ) ); ?>">
					<?php esc_html_e( 'Taking a while? Click here to run it now.', 'wp-courseware' ); ?>
				</a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Update Completed Notice.
	 *
	 * @since 4.4.3
	 *
	 * @return string The update completed notice.
	 */
	public function get_update_completed_notice() {
		$dismiss_url = wp_nonce_url( add_query_arg( 'wpcw-hide-update-notice', 'update', remove_query_arg( 'do_update_wpcw' ) ), 'wpcw_hide_update_notices_nonce', '_wpcw_update_notice_nonce' );
		ob_start();
		?>
		<div class="wpcw-admin-notice wpcw-update-notice notice notice-info">
			<p><?php esc_html_e( 'WP Courseware data update complete. Thank you for updating to the latest version!', 'wp-courseware' ); ?></p>
			<a class="wpcw-admin-notice-dismiss notice-dismiss" href="<?php echo esc_url( $dismiss_url ); ?>"><?php _e( 'Dismiss', 'wp-courseware' ); ?></a>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Update Methods ----------------------------------------------------- */

	/**
	 * Upgrade 4.3.0 - Ecommerce.
	 *
	 * @since 4.3.0
	 */
	public function wpcw_update_430_ecommerce() {
		if ( $this->can_install_ecommerce() ) {
			$this->install_pages();
			$this->maybe_flush_rewrite_rules();
		}
	}

	/**
	 * Upgrade 4.4.0 - Courses.
	 *
	 * This will perform the upgrade of courses.
	 *
	 * @since 4.4.0
	 */
	public function wpcw_update_440_courses() {
		// Reset Roles.
		$this->reset_roles();

		// Maybe Send Inintial Checkin.
		$this->maybe_send_initial_checkin();

		// Upgrade Courses.
		wpcw()->courses->maybe_upgrade_courses();
	}

	/**
	 * Upgrade 4.4.1 - Roles
	 *
	 * This will perform the upgrade to fix the roles issues.
	 *
	 * @since 4.4.1
	 */
	public function wpcw_update_441_roles() {
		$this->reset_roles();
	}

	/**
	 * Upgrade 4.4.2 - Fix Courses
	 *
	 * This upgrade will fix the duplicate course issues found by 4.4.0.
	 *
	 * @since 4.4.2
	 */
	public function wpcw_update_442_fix_courses() {
		wpcw()->courses->maybe_fix_duplicate_courses();
	}

	/** Misc Methods ----------------------------------------------------- */

	/**
	 * Manually Kill Updater.
	 *
	 * @since 4.4.3
	 */
	public function manually_kill_updater() {
		$updater = $this->get_background_updater();
		$updater->kill_process();
	}

	/**
	 * Manually Run Updates.
	 *
	 * @since 4.4.3
	 */
	public function manually_run_updates() {
		if ( ! $this->needs_db_update() ) {
			return;
		}

		// Manually Kill Updater.
		$this->manually_kill_updater();

		// Get Installed Version.
		$installed_version = $this->get_db_version();
		$current_version   = WPCW_DB_VERSION;
		$update_queued     = false;

		// Log It.
		$this->log( sprintf( 'Manually Updating from version %s to %s.', $installed_version, $current_version ) );

		// Check for upgrade callbacks.
		foreach ( $this->updates as $version => $upgrade_callbacks ) {
			if ( version_compare( $installed_version, $version, '<' ) ) {
				foreach ( $upgrade_callbacks as $upgrade_callback ) {
					$this->log( sprintf( 'Manually Updating %s - %s', $version, $upgrade_callback ) );
					$this->{$upgrade_callback}();
				}
			}
		}

		// Log It.
		$this->log( sprintf( 'Successfully Updated from version %s to %s.', $installed_version, $current_version ) );

		// Update Version.
		$this->update_version();
		$this->update_db_version();
	}

	/**
	 * Reset Roles.
	 *
	 * @since 4.4.0
	 */
	protected function reset_roles() {
		wpcw()->roles->reset_roles_caps();
	}

	/**
	 * Maybe Flush Rewrite Rules.
	 *
	 * @since 4.4.0
	 */
	public function maybe_flush_rewrite_rules() {
		flush_rewrite_rules( false );
	}

	/**
	 * Log Message.
	 *
	 * @since 4.4.0
	 *
	 * @param string $message The log message.
	 */
	public function log( $message = '' ) {
		if ( empty( $message ) || ! defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {
			return;
		}

		$log_entry = "\n" . '====Start ' . get_called_class() . ' Log====' . "\n" . $message . "\n" . '====End ' . get_called_class() . ' Log====' . "\n";

		wpcw_log( $log_entry );
		wpcw_file_log( array( 'message' => $log_entry ) );
	}
}
