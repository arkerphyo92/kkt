<?php
/**
 * WP Courseware Session Class
 *
 * In an effort to not re-invent the wheel part of this code
 * was copied and modified from the open source plugin WooCommerce
 * created by WooThemes / Automattic.
 *
 * Credit is given where its due.
 *
 * @link https://github.com/woocommerce/woocommerce
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Session.
 *
 * @since 4.3.0
 */
final class Session {

	/**
	 * @var int $_student_id Student ID.
	 * @since 4.3.0
	 */
	protected $_student_id;

	/**
	 * @var array $_data The session data.
	 * @since 4.3.0
	 */
	protected $_data = array();

	/**
	 * @var bool $_dirty Dirty when the session needs saving.
	 * @since 4.3.0
	 */
	protected $_dirty = false;

	/**
	 * @var string Cookie name used for the session.
	 * @since 4.3.0
	 */
	protected $_cookie;

	/**
	 * @var string Stores session expiry.
	 * @since 4.3.0
	 */
	protected $_session_expiring;

	/**
	 * @var string Stores session due to expire timestamp.
	 * @since 4.3.0
	 */
	protected $_session_expiration;

	/**
	 * @var bool True when the cookie exists.
	 * @since 4.3.0
	 */
	protected $_has_cookie = false;

	/**
	 * @var string Table name for session data.
	 * @since 4.3.0
	 */
	protected $_table;

	/**
	 * Session Constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Cookie constants.
		if ( ! defined( 'COOKIEHASH' ) ) {
			wp_cookie_constants();
		}

		$this->_cookie = 'wp_wpcw_session_' . COOKIEHASH;
		$this->_table  = $GLOBALS['wpdb']->prefix . 'wpcw_sessions';
	}

	/**
	 * Load Session.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		$this->_cookie = apply_filters( 'wpcw_cookie', $this->_cookie );
		add_action( 'wpcw_init', array( $this, 'register_sessions' ), 0 );
	}

	/**
	 * Register Sessions.
	 *
	 * Only registers on the frontend of the site.
	 *
	 * @since 4.3.0
	 */
	public function register_sessions() {
		if ( ! wpcw_is_request( 'frontend' ) && ! wpcw_is_request( 'ajax' ) ) {
			return;
		}

		$cookie = $this->get_session_cookie();

		if ( $cookie ) {
			$this->_student_id         = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_student_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_student_id = $this->generate_student_id();
		}

		$this->_data = $this->get_session_data();

		add_action( 'wpcw_set_cart_cookies', array( $this, 'set_student_session_cookie' ), 10 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	/**
	 * Set Student Session Cookie.
	 *
	 * Sets the session cookie on-demand ( usually after adding an item to the cart or going to checkout ).
	 *
	 * Warning: Cookies will only be set if this is called before the headers are sent.
	 *
	 * @since 4.3.0
	 *
	 * @param bool $set Should the session cookie be set.
	 */
	public function set_student_session_cookie( $set ) {
		if ( $set ) {
			$to_hash           = $this->_student_id . '|' . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_student_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			wpcw_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, apply_filters( 'wpcw_session_use_secure_cookie', false ) );
		}
	}

	/**
	 * Current User has session?
	 *
	 * Return true if the current user has an active session, i.e. a cookie to retrieve values.
	 *
	 * @since 4.3.0
	 *
	 * @return bool User has a session.
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
	}

	/**
	 * Set Session Expiration.
	 *
	 * @since 4.3.0
	 */
	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'wpcw_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
		$this->_session_expiration = time() + intval( apply_filters( 'wpcw_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
	}

	/**
	 * Generate Student Id.
	 *
	 * Generate a unique student ID for guests (if any), or return user ID if logged in.
	 * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
	 *
	 * @since 4.3.0
	 *
	 * @return string The generated student id.
	 */
	public function generate_student_id() {
		$student_id = '';

		if ( is_user_logged_in() ) {
			$student_id = get_current_user_id();
		}

		if ( empty( $student_id ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher     = new \PasswordHash( 8, false );
			$student_id = md5( $hasher->get_random_bytes( 32 ) );
		}

		return $student_id;
	}

	/**
	 * Get Session Cookie.
	 *
	 * Session cookies without a student ID are invalid.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false;

		if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
			return false;
		}

		list( $student_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $cookie_value );

		if ( empty( $student_id ) ) {
			return false;
		}

		// Validate hash.
		$to_hash = $student_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $student_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * Get Session Data.
	 *
	 * @since 4.3.0
	 *
	 * @return array The session data.
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_student_id, array() ) : array();
	}

	/**
	 * Gets a cache prefix.
	 *
	 * This is used in session names so the entire cache can be invalidated with 1 function call.
	 *
	 * @since 4.3.0
	 *
	 * @return string The cache prefix.
	 */
	private function get_cache_prefix() {
		return wpcw()->cache->get_cache_prefix( WPCW_SESSION_CACHE_GROUP );
	}

	/**
	 * Save Session Data.
	 *
	 * @since 4.3.0
	 */
	public function save_data() {
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->replace( $this->_table, array(
				'session_key'    => $this->_student_id,
				'session_value'  => maybe_serialize( $this->_data ),
				'session_expiry' => $this->_session_expiration,
			), array( '%s', '%s', '%d' ) );

			wp_cache_set( $this->get_cache_prefix() . $this->_student_id, $this->_data, WPCW_SESSION_CACHE_GROUP, $this->_session_expiration - time() );

			$this->_dirty = false;
		}
	}

	/**
	 * Destroy all session data.
	 *
	 * @since 4.3.0
	 */
	public function destroy_session() {
		wpcw_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'wpcw_session_use_secure_cookie', false ) );

		$this->delete_session( $this->_student_id );

		wpcw()->cart->empty_cart();

		$this->_data       = array();
		$this->_dirty      = false;
		$this->_student_id = $this->generate_student_id();
	}

	/**
	 * Generate a unique nonce when user is logged out.
	 *
	 * Ensure they have a unique nonce by using the student/session ID.
	 *
	 * @since 4.3.0
	 *
	 * @param int $uid User ID.
	 *
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_student_id ? $this->_student_id : $uid;
	}

	/**
	 * Cleanup Sessions.
	 *
	 * Cleanup session data from the database and clear caches.
	 *
	 * @since 4.3.0
	 */
	public function cleanup_sessions() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) );

		wpcw()->cache->incr_cache_prefix( WPCW_SESSION_CACHE_GROUP );
	}

	/**
	 * Returns the session.
	 *
	 * @since 4.3.0
	 *
	 * @param string $student_id The student id.
	 * @param mixed  $default Default session value.
	 *
	 * @return string|array
	 */
	public function get_session( $student_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = wp_cache_get( $this->get_cache_prefix() . $student_id, WPCW_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $student_id ) );

			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $student_id, $value, WPCW_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @since 4.3.0
	 *
	 * @param int $student_id Student ID.
	 */
	public function delete_session( $student_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $student_id, WPCW_SESSION_CACHE_GROUP );

		$wpdb->delete( $this->_table, array( 'session_key' => $student_id ) );
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @since 4.3.0
	 *
	 * @param string $student_id Student ID.
	 * @param int    $timestamp Timestamp to expire the cookie.
	 */
	public function update_session_timestamp( $student_id, $timestamp ) {
		global $wpdb;

		$wpdb->update( $this->_table, array( 'session_expiry' => $timestamp ), array( 'session_key' => $student_id ), array( '%d' ) );
	}

	/**
	 * Magic get method.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $key Key to get.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic set method.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $key Key to set.
	 * @param mixed $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * Magic isset method.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $key Key to check.
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	/**
	 * Magic unset method.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $key Key to unset.
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * Get a session variable.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default used if the session variable isn't set.
	 *
	 * @return array|string value of session variable
	 */
	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );

		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	/**
	 * Set a session variable.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty                        = true;
		}
	}

	/**
	 * Get student ID.
	 *
	 * @since 4.3.0
	 *
	 * @return int The student ID.
	 */
	public function get_student_id() {
		return $this->_student_id;
	}
}
