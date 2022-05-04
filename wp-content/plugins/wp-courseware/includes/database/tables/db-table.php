<?php
/**
 * WP Courseware Table Class.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * DB_Table Abstract Class.
 *
 * Based off version 1.4.0 of the base WordPress table class created by JJJ.
 * See link below for original repository.
 *
 * @link https://github.com/stuttter/wp-db-table
 *
 * A base WordPress database table class, which facilitates the creation of
 * and schema changes to individual database tables.
 *
 * This class is intended to be extended for each unique database table,
 * including global multisite tables and users tables.
 *
 * It exists to make managing database tables in WordPress as easy as possible.
 *
 * Extending this class comes with several automatic benefits:
 * - Activation hook makes it great for plugins
 * - Tables store their versions in the database independently
 * - Tables upgrade via independent upgrade abstract methods
 * - Multisite friendly - site tables switch on "switch_blog" action
 *
 * @since 4.3.0
 */
abstract class DB_Table {

	/**
	 * @var string Table name, without the global table prefix.
	 * @since 4.3.0
	 */
	protected $name = '';

	/**
	 * @var string Optional description.
	 * @since 4.3.0
	 */
	protected $description = '';

	/**
	 * @var int Database version
	 * @since 4.3.0
	 */
	protected $version = 0;

	/**
	 * @var boolean Is this table for a site, or global
	 * @since 4.3.0
	 */
	protected $global = false;

	/**
	 * @var string Passed directly into register_activation_hook()
	 * @since 4.3.0
	 */
	protected $file = WPCW_FILE;

	/**
	 * @var string Database version key (saved in _options or _sitemeta)
	 * @since 4.3.0
	 */
	protected $db_version_key = '';

	/**
	 * @var string Current database version
	 * @since 4.3.0
	 */
	protected $db_version = 0;

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $table_name = '';

	/**
	 * @var string Table schema
	 * @since 4.3.0
	 */
	protected $schema = '';

	/**
	 * @var string Database character-set & collation for table
	 * @since 4.3.0
	 */
	protected $charset_collation = '';

	/**
	 * @var WPDB Database object (usually $GLOBALS['wpdb'])
	 * @since 4.3.0
	 */
	protected $db = false;

	/** Methods ***************************************************************/

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Setup the database
		$this->setup();

		// Bail if setup failed
		if ( empty( $this->name ) || empty( $this->db_version_key ) ) {
			return;
		}

		// Get the version of he table currently in the database
		$this->get_db_version();

		// Add the table to the object
		$this->set_wpdb_tables();

		// Setup the database schema
		$this->set_schema();

		// Add hooks to WordPress actions
		$this->add_hooks();

		// Maybe force upgrade if testing
		$this->maybe_upgrade();
	}

	/** Abstract **************************************************************/

	/**
	 * Setup this database table
	 *
	 * @since 4.3.0
	 */
	protected abstract function set_schema();

	/**
	 * Get Upgrades.
	 *
	 * @since 4.4.0
	 */
	protected function get_upgrades() {
		return array();
	}

	/**
	 * Handle Upgrades.
	 *
	 * @since 4.4.0
	 */
	protected function upgrade() {
		$current = $this->db_version;

		$upgrades = $this->get_upgrades();

		$this->upgrade_schema();

		if ( ! empty( $upgrades ) ) {
			foreach ( $upgrades as $vers => $upgrade ) {
				if ( version_compare( absint( $current ), absint( $vers ) ) === - 1 ) {
					if ( is_callable( array( $this, $upgrade ) ) ) {
						$this->{$upgrade}();
					}
				}
			}
		}
	}

	/** Public ****************************************************************/

	/**
	 * Update table version & references.
	 *
	 * Hooked to the "switch_blog" action.
	 *
	 * @since 4.3.0
	 *
	 * @param int $site_id The site being switched to
	 */
	public function switch_blog( $site_id = 0 ) {
		// Update DB version based on the current site
		if ( ! $this->is_global() ) {
			$this->db_version = get_blog_option( $site_id, $this->db_version_key, false );
		}

		// Update table references based on th current site
		$this->set_wpdb_tables();
	}

	/**
	 * Maybe Fix.
	 *
	 * @since 4.4.4
	 */
	public function maybe_fix() {
		$this->delete_db_version();
		$this->get_db_version();
		$this->maybe_upgrade();
	}

	/**
	 * Get Table Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Get Name.
	 *
	 * @since 4.6.3
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Maybe upgrade the database table. Handles creation & schema changes.
	 *
	 * Hooked to the "admin_init" action.
	 *
	 * @since 4.3.0
	 */
	public function maybe_upgrade() {
		// Is an upgrade needed?
		$needs_upgrade = version_compare( (int) $this->db_version, (int) $this->version, '>=' );

		// Bail if no upgrade needed
		if ( true === $needs_upgrade ) {
			return;
		}

		// Bail if global and upgrading global tables is not allowed
		if ( $this->is_global() && ! wp_should_upgrade_global_tables() ) {
			return;
		}

		// Create or upgrade?
		if ( $this->exists() ) {
			$this->upgrade();
			$this->set_db_version();
		} elseif ( $this->create() ) {
			$this->set_db_version();
		}
	}

	/** Private ***************************************************************/

	/**
	 * Setup the necessary table variables
	 *
	 * @since 4.3.0
	 */
	private function setup() {
		// Setup database
		$this->db = isset( $GLOBALS['wpdb'] )
			? $GLOBALS['wpdb']
			: false;

		// Bail if no WordPress database interface is available
		if ( false === $this->db ) {
			return;
		}

		// Sanitize the database table name
		$this->name = $this->sanitize_table_name( $this->name );

		// Bail if database table name was garbage
		if ( false === $this->name ) {
			return;
		}

		// Maybe create database key
		if ( empty( $this->db_version_key ) ) {
			$this->db_version_key = "wpdb_{$this->name}_version";
		}
	}

	/**
	 * Modify the database object and add the table to it
	 *
	 * This must be done directly because WordPress does not have a mechanism
	 * for manipulating them safely
	 *
	 * @since 4.3.0
	 */
	private function set_wpdb_tables() {
		global $wpdb;

		// Global
		if ( $this->is_global() ) {
			$prefix                       = $this->db->get_blog_prefix( 0 );
			$this->db->{$this->name}      = "{$prefix}{$this->name}";
			$this->db->ms_global_tables[] = $this->name;
			// Site
		} else {
			$prefix                  = $this->db->get_blog_prefix( null );
			$this->db->{$this->name} = "{$prefix}{$this->name}";
			$this->db->tables[]      = $this->name;
		}

		// Set the table name locally
		$this->table_name = $this->db->{$this->name};

		// Charset
		if ( ! empty( $this->db->charset ) ) {
			$this->charset_collation = "DEFAULT CHARACTER SET {$this->db->charset}";
		}

		// Collation
		if ( ! empty( $this->db->collate ) ) {
			$this->charset_collation .= " COLLATE {$this->db->collate}";
		}
	}

	/**
	 * Set the database version for the table
	 *
	 * Global table version in "_sitemeta" on the main network
	 *
	 * @since 4.3.0
	 */
	private function set_db_version() {
		// Set the class version
		$this->db_version = $this->version;

		// Update the DB version
		$this->is_global()
			? update_network_option( null, $this->db_version_key, $this->version )
			: update_option( $this->db_version_key, $this->version );
	}

	/**
	 * Get the table version from the database
	 *
	 * Global table version from "_sitemeta" on the main network
	 *
	 * @since 4.3.0
	 */
	private function get_db_version() {
		$this->db_version = $this->is_global()
			? get_network_option( null, $this->db_version_key, false )
			: get_option( $this->db_version_key, false );
	}

	/**
	 * Delete the table version from the database
	 *
	 * Global table version from "_sitemeta" on the main network
	 *
	 * @since 4.4.4
	 */
	private function delete_db_version() {
		// Delete the DB version
		$this->is_global()
			? delete_network_option( null, $this->db_version_key )
			: delete_option( $this->db_version_key );
	}

	/**
	 * Add class hooks to WordPress actions.
	 *
	 * @since 4.3.0
	 */
	private function add_hooks() {
		add_action( 'switch_blog', array( $this, 'switch_blog' ) );
	}

	/**
	 * Check if the current request is from some kind of test.
	 *
	 * This is primarily used to skip 'admin_init' and force-install tables.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	private function is_testing() {
		// Tests or Scaffolded (https://make.wordpress.org/cli/handbook/plugin-unit-tests/)
		return (bool) ( defined( 'WP_TESTS_DIR' ) && WP_TESTS_DIR ) || function_exists( '_manually_load_plugin' );
	}

	/**
	 * Create the table.
	 *
	 * @since 4.3.0
	 */
	private function create() {
		global $wpdb;

		// Hide Errors
		$wpdb->hide_errors();

		// Include file with dbDelta() for create / upgrade usages
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Bail if dbDelta() moved in WordPress core
		if ( ! function_exists( 'dbDelta' ) ) {
			return false;
		}

		// Run CREATE TABLE query
		$query   = "CREATE TABLE {$this->table_name} ( {$this->schema} ) AUTO_INCREMENT=2 {$this->charset_collation};";
		$created = $wpdb->query( $query );

		// Was the table created?
		return ! empty( $created );
	}

	/**
	 * Upgrade Schema.
	 *
	 * @since 4.5.0
	 *
	 * @return bool True if successful. False otherwise.
	 */
	private function upgrade_schema() {
		global $wpdb;

		// Include file with dbDelta() for create / upgrade usages
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Bail if dbDelta() moved in WordPress core
		if ( ! function_exists( 'dbDelta' ) ) {
			return false;
		}

		// Run CREATE TABLE query
		$query = "CREATE TABLE {$this->table_name} ( {$this->schema} ) AUTO_INCREMENT=2 {$this->charset_collation};";

		// Run Upgrade Routine.
		dbDelta( $query );

		// Status of the upgrade.
		return (bool) empty( $wpdb->last_error );
	}

	/**
	 * Check if table already exists
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	private function exists() {
		$query       = "SHOW TABLES LIKE %s";
		$like        = $this->db->esc_like( $this->table_name );
		$prepared    = $this->db->prepare( $query, $like );
		$table_exist = $this->db->get_var( $prepared );

		// Does the table exist?
		return ! empty( $table_exist );
	}

	/**
	 * Check if table is global
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	private function is_global() {
		return ( true === $this->global );
	}

	/**
	 * Sanitize a table name string
	 *
	 * Applies the following formatting to a string:
	 * - No accents
	 * - No special characters
	 * - No hyphens
	 * - No double underscores
	 * - No trailing underscores
	 *
	 * @since 4.3.0
	 *
	 * @param string $name The name of the database table
	 *
	 * @return string Sanitized database table name
	 */
	private function sanitize_table_name( $name = '' ) {
		// Only non-accented table names (avoid truncation)
		$accents = remove_accents( $name );

		// Only lowercase characters, hyphens, and dashes (avoid index corruption)
		$lower = sanitize_key( $accents );

		// Replace hyphens with single underscores
		$under = str_replace( '-', '_', $lower );

		// Single underscores only
		$single = str_replace( '__', '_', $under );

		// Remove trailing underscores
		$clean = trim( $single, '_' );

		// Bail if table name was garbaged
		if ( empty( $clean ) ) {
			return false;
		}

		// Return the cleaned table name
		return $clean;
	}
}
