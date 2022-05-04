<?php
/**
 * WP Courseware Requirements.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class WPCW_Requirements.
 *
 * @since 4.3.0
 */
class WPCW_Requirements {

	/**
	 * @var string Minimum WP Version.
	 * @since 4.3.0
	 */
	protected $wp_version = '4.8.0';

	/**
	 * @var string Minimum PHP Version.
	 * @since 4.1.0
	 */
	protected $php_version = '5.6.0';

	/**
	 * @var array Errors.
	 * @since 4.1.0
	 */
	protected $errors = array();

	/**
	 * Check Requirements.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public static function check() {
		$requirements = new self();
		return $requirements->test();
	}

	/**
	 * Test Requirements.
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function test() {
		$php_requirement_fail = false;
		$wp_requirement_fail  = false;

		$php_version = phpversion();
		$wp_version  = get_bloginfo( 'version' );

		if ( ! version_compare( $php_version, $this->php_version, '>=' ) ) {
			$php_requirement_fail = true;
		}

		if ( ! version_compare( $wp_version, $this->wp_version, '>=' ) ) {
			$wp_requirement_fail = true;
		}

		if ( $wp_requirement_fail && $php_requirement_fail ) {
			/* translators: %1$s - Minimum PHP version, %2$s - Minimum WordPress version, %3$s - Current PHP version, %4$s - Current WordPress version */
			$this->errors[] = sprintf(
				__( '<strong>WP Courseware</strong> requires <strong>PHP version %1$s</strong> or later and <strong>WordPress version %2$s</strong> or later to run. You are running <strong>PHP version %3$s</strong> and WordPress version %4$s.', 'wp-courseware' ),
				$this->php_version,
				$this->wp_version,
				$php_version
			);
		} elseif ( $php_requirement_fail ) {
			/* translators: %1$s - Minimum PHP version, %2$s - Current PHP version */
			$this->errors[] = sprintf(
				__( '<strong>WP Courseware</strong> requires <strong>PHP version %1$s</strong> or later to run. You are running <strong>%2$s</strong>.', 'wp-courseware' ),
				$this->php_version,
				phpversion()
			);
		} elseif ( $wp_requirement_fail ) {
			/* translators: %1$s - Minimum WordPress version, %2$s - Current WordPress version */
			$this->errors[] = sprintf(
				__( '<strong>WP Courseware</strong> requires <strong>WordPress version %1$s</strong> or later to run. You are running <strong>%2$s</strong>.', 'wp-courseware' ),
				$this->wp_version,
				get_bloginfo( 'version' )
			);
		}

		if ( true === $wp_requirement_fail || true === $php_requirement_fail || ! empty( $this->errors ) ) {
			$this->errors[] = __( '<strong>WP Courseware</strong> has been deactivated.', 'wp-courseware' );

			add_action( 'plugins_loaded', array( $this, 'textdomain' ) );
			add_action( 'admin_init', array( $this, 'deactivate' ) );
			add_action( 'all_admin_notices', array( $this, 'errors' ) );

			return false;
		}

		return true;
	}

	/**
	 * Textdomain.
	 *
	 * @since 4.3.0
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wp-courseware', false, trailingslashit( dirname( WPCW_FILE ) . '/languages' ) );
	}

	/**
	 * Errors.
	 *
	 * @since 4.1.0
	 */
	public function errors() {
		if ( ! empty ( $this->errors ) ) {
			foreach ( $this->errors as $error ) {
				$class = 'notice notice-error is-dismissable';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $error ) );
			}
		}
	}

	/**
	 * Deactivate.
	 *
	 * @since 4.1.0
	 */
	public function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		deactivate_plugins( WPCW_PATH . 'wp-courseware.php' );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}
