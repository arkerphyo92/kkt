<?php
/**
 * WP Courseware Support Class.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Support Class.
 *
 * @since 4.1.0
 */
final class Support {

	/**
	 * Load.
	 *
	 * @since 4.1.0
	 */
	public function load() {
		add_filter( 'wpcw_api_endoints', array( $this, 'register_api_endpoints' ), 10, 2 );
	}

	/**
	 * Register Api Endpoints.
	 *
	 * @since 4.1.0
	 *
	 * @param array $endpoints The current api endpoints.
	 * @param Api The api reference object.
	 *
	 * @return array $endpoints The newly modified api endpoints.
	 */
	public function register_api_endpoints( $endpoints, Api $api ) {
		$endpoints[] = array(
			'endpoint' => 'otherproducts',
			'method'   => 'GET',
			'callback' => array( $this, 'api_get_products' ),
		);

		return $endpoints;
	}

	/**
	 * Get Products.
	 *
	 * @since 4.1.0
	 *
	 * @return array $products An array of other products.
	 */
	public function get_products() {
		return apply_filters( 'wpcw_other_products', array(
			's3-media-maestro' => array(
				'title'            => esc_html__( 'S3 Media Maestro', 'wp-courseware' ),
				'url'              => 'https://flyplugins.com/s3-media-maestro/?discount=15OFF4CC',
				'image'            => wpcw_image_file( 's3-media-maestro.svg' ),
				'desc'             => esc_html__( 'Are you delivering Amazon S3 or CloudFront hosted content through your WordPress site? If so, digital thieves may be eating into your profits! S3 Media Maestro will help you deliver your video, audio, and download files securely!', 'wp-courseware' ),
				'discount'         => esc_html__( 'Save 15%', 'wp-courseware' ),
				'discount_enabled' => true,
			),
			'churnly'          => array(
				'title'            => esc_html__( 'Churnly', 'wp-courseware' ),
				'url'              => 'https://flyplugins.com/churnly-for-wordpress/?discount=15OFF4CC',
				'image'            => wpcw_image_file( 'churnly.svg' ),
				'desc'             => esc_html__( 'Churnly is the only solution for WordPress that allows you to easily recapture failed recurring credit card payments through a completely automated process. On average, Churnly increases its users\' annual revenues by 7%!', 'wp-courseware' ),
				'discount'         => esc_html__( 'Save 15%', 'wp-courseware' ),
				'discount_enabled' => true,
			),
		) );
	}

	/**
	 * Api Get Products Callback.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function api_get_products( WP_REST_Request $request ) {
		$products = $this->get_products();

		if ( empty( $products ) ) {
			return new WP_Error( 'wpcw-api-error', esc_html__( 'There are currently no products available. Please try again later.', 'wp-courseware' ) );
		}

		return rest_ensure_response( array( 'products' => $products ) );
	}
}
