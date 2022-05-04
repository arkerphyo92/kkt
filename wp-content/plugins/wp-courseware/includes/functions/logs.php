<?php
/**
 * WP Courseware Log Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Debug Log.
 *
 * @since 4.3.0
 *
 * @param string $message The log message.
 */
function wpcw_log( $message ) {
	if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( print_r( $message, true ) );
		} else {
			error_log( $message );
		}
	}
}

/**
 * DB Log.
 *
 * @since 4.3.0
 *
 * @param array $data {
 * The data to log the message.
 *
 * @type string $message Required. The log message.
 * @type string $title Optional. The title of the log message.
 * @type int $object_id Opitonal. The object id its associated.
 * @type string $object_type Optional. The object type is associated.
 * @type string $type Optional. The type of log message. Default is 'debug'
 * }
 *
 * @return bool
 */
function wpcw_db_log( $data = array() ) {
	$defaults = array(
		'message'     => '',
		'title'       => '',
		'object_id'   => 0,
		'object_type' => '',
		'type'        => 'debug',
	);

	$data = wp_parse_args( $data, $defaults );

	if ( empty( $data['message'] ) ) {
		return;
	}

	return wpcw()->logs->add_log( $data, 'db' );
}

/**
 * File Log.
 *
 * @since 4.3.0
 *
 * @param array $data {
 * The data to log the message.
 *
 * @type string $message Required. The log message.
 * @type string $title Optional. The title of the log message.
 * @type int $object_id Opitonal. The object id its associated.
 * @type string $object_type Optional. The object type is associated.
 * @type string $type Optional. The type of log message. Default is 'debug'
 * }
 *
 * @return bool
 */
function wpcw_file_log( $data = array() ) {
	$defaults = array(
		'message'     => '',
		'title'       => '',
		'object_id'   => 0,
		'object_type' => '',
		'type'        => 'debug',
	);

	$data = wp_parse_args( $data, $defaults );

	if ( empty( $data['message'] ) ) {
		return;
	}

	return wpcw()->logs->add_log( $data );
}