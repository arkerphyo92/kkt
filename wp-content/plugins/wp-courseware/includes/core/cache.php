<?php
/**
 * WP Courseware Cache.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Cache.
 *
 * @since 4.3.0
 */
final class Cache {

	/**
	 * Load Cache.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wp', array( $this, 'prevent_caching' ) );
	}

	/**
	 * Prevent Caching.
	 *
	 * This prevents caching on certain pages.
	 *
	 * @since 4.3.0
	 */
	public function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}

		$page_ids = array_filter( array(
			wpcw_get_page_id( 'checkout' ),
			wpcw_get_page_id( 'account' ),
			wpcw_get_page_id( 'order-received' ),
			wpcw_get_page_id( 'order-failed' ),
		) );

		if ( is_page( $page_ids ) ) {
			$this->set_nocache_constants();
			nocache_headers();
		}
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param mixed $return Value to return. Previously hooked into a filter.
	 *
	 * @return bool
	 */
	public function set_nocache_constants( $return = true ) {
		wpcw_maybe_define_constant( 'DONOTCACHEPAGE', true );
		wpcw_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		wpcw_maybe_define_constant( 'DONOTCACHEDB', true );

		return $return;
	}

	/**
	 * Get Cache prefix.
	 *
	 * Used with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @since 4.3.0
	 *
	 * @param string $group Group of cache to get.
	 *
	 * @return string
	 */
	public function get_cache_prefix( $group ) {
		$prefix = wp_cache_get( 'wpcw_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'wpcw_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'wpcw_cache_' . $prefix . '_';
	}

	/**
	 * Increment Cache Prefix.
	 *
	 * This invalidates the cache.
	 *
	 * @since 4.3.0
	 *
	 * @param string $group Group of cache to clear.
	 */
	public function incr_cache_prefix( $group ) {
		wp_cache_incr( 'wpcw_' . $group . '_cache_prefix', 1, $group );
	}

	/**
	 * Get transient version.
	 *
	 * When using transients with unpredictable names, e.g. those containing an md5.
	 * hash in the name, we need a way to invalidate them all at once.
	 *
	 * When using default WP transients we're able to do this with a DB query to.
	 * delete transients manually.
	 *
	 * With external cache however, this isn't possible. Instead, this function is used.
	 * to append a unique string (based on time()) to each transient. When transients.
	 * are invalidated, the transient version will increment and data will be regenerated.
	 *
	 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
	 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
	 *
	 * @since 4.3.0
	 *
	 * @param string  $group Name for the group of transients we need to invalidate.
	 * @param boolean $refresh True to force a new version.
	 *
	 * @return string The transient version based on time(), 10 digits.
	 */
	public function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			$this->delete_version_transients( $transient_value );

			$transient_value = time();

			set_transient( $transient_name, $transient_value );
		}

		return $transient_value;
	}

	/**
	 * Delete Version Transients.
	 *
	 * When the transient version increases, this is used to remove all past transients to avoid filling the DB.
	 *
	 * Note; this only works on transients appended with the transient version, and when object caching is not being used.
	 *
	 * @since 4.3.0
	 *
	 * @param string $version The version of the transient to remove.
	 */
	public function delete_version_transients( $version = '' ) {
		if ( ! wp_using_ext_object_cache() && ! empty( $version ) ) {
			global $wpdb;

			$limit = apply_filters( 'wpcw_delete_version_transients_limit', 1000 );

			$affected = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id LIMIT %d;", '\_transient\_%' . $version, $limit ) );

			// If affected rows is equal to limit, there are more rows to delete. Delete in 10 secs.
			if ( $affected === $limit ) {
				wp_schedule_single_event( time() + 10, 'delete_version_transients', array( $version ) );
			}
		}
	}
}
