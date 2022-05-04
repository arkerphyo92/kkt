<?php
/**
 * WP Courseware Https.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class HTTPS.
 *
 * @since 4.3.0
 */
final class HTTPS {

	/**
	 * Load HTTPS.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'wpcw_init', array( $this, 'maybe_secure_urls' ) );
	}

	/**
	 * Maybe Secure Certain Urls with HTTPs.
	 *
	 * This depends on if the settings 'force_ssl' is true.
	 *
	 * @since 4.3.0
	 */
	public function maybe_secure_urls() {
		if ( 'yes' === wpcw_get_setting( 'force_ssl' ) ) {
			// HTTPS urls with SSL on
			$filters = array(
				'post_thumbnail_html',
				'wp_get_attachment_image_attributes',
				'wp_get_attachment_url',
				'option_stylesheet_url',
				'option_template_url',
				'script_loader_src',
				'style_loader_src',
				'template_directory_uri',
				'stylesheet_directory_uri',
				'site_url',
			);

			foreach ( $filters as $filter ) {
				add_filter( $filter, array( $this, 'force_https_url' ), 999 );
			}

			add_filter( 'page_link', array( $this, 'force_https_page_link' ), 10, 2 );
			add_action( 'template_redirect', array( $this, 'force_https_template_redirect' ) );
		}

		add_action( 'http_api_curl', array( $this, 'http_api_curl' ), 10, 3 );
	}

	/**
	 * Force HTTPS for urls.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $content The content to be forced.
	 *
	 * @return string
	 */
	public function force_https_url( $content ) {
		if ( is_ssl() ) {
			if ( is_array( $content ) ) {
				$content = array_map( array( $this, 'force_https_url' ), $content );
			} else {
				$content = str_replace( 'http:', 'https:', $content );
			}
		}

		return $content;
	}

	/**
	 * Force a page link to be SSL if needed.
	 *
	 * @since 4.3.0
	 *
	 * @param string $link The page link.
	 * @param int    $page_id The page ID.
	 *
	 * @return string The modified page link.
	 */
	public function force_https_page_link( $link, $page_id ) {
		if ( in_array( $page_id, array( wpcw_get_page_id( 'checkout' ), wpcw_get_page_id( 'account' ) ) ) ) {
			$link = str_replace( 'http:', 'https:', $link );
		}

		return $link;
	}

	/**
	 * Force HTTPS on Template redirect.
	 *
	 * If we end up on a page ensure it has the correct http/https url.
	 *
	 * @since 4.3.0
	 */
	public function force_https_template_redirect() {
		if ( ! is_ssl() && ( wpcw_is_checkout() || wpcw_is_account_page() || apply_filters( 'wpcw_force_ssl_checkout', false ) ) ) {
			if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
				wp_safe_redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ) );
				exit;
			} else {
				wp_safe_redirect( 'https://' . ( ! empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'] ) . $_SERVER['REQUEST_URI'] );
				exit;
			}
		}
	}

	/**
	 * Force posts to PayPal to use TLS v1.2. See:
	 *        https://core.trac.wordpress.org/ticket/36320
	 *        https://core.trac.wordpress.org/ticket/34924#comment:13
	 *        https://www.paypal-knowledge.com/infocenter/index?page=content&widgetview=true&id=FAQ1914&viewlocale=en_US
	 *
	 * @since 4.3.0
	 *
	 * @param string $handle The curl handle.
	 * @param mixed  $r
	 * @param string $url The curl url.
	 */
	public static function http_api_curl( $handle, $r, $url ) {
		if ( strstr( $url, 'https://' ) && ( strstr( $url, '.paypal.com/nvp' ) || strstr( $url, '.paypal.com/cgi-bin/webscr' ) ) ) {
			curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
		}
	}
}
