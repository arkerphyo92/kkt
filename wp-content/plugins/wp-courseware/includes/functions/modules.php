<?php
/**
 * WP Courseware Module Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.4.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Module.
 *
 * @since 4.4.0
 *
 * @param int|bool $module_id The Module Id.
 *
 * @return \WPCW\Models\Module|bool An module object or false.
 */
function wpcw_get_module( $module_id = false ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_get_module should not be called before the module object is setup.', '4.3.0' );
		return false;
	}

	return new \WPCW\Models\Module( $module_id );
}

/**
 * Insert Module.
 *
 * @since 4.4.0
 *
 * @param array $data The module data.
 *
 * @return \WPCW\Models\Module|bool $module The module object or false if can't be created.
 */
function wpcw_insert_module( $data = array() ) {
	if ( ! did_action( 'wpcw_loaded' ) ) {
		wpcw_doing_it_wrong( __FUNCTION__, 'wpcw_insert_module should not be called before the module object is setup.', '4.3.0' );
		return false;
	}

	$module    = new \WPCW\Models\Module();
	$module_id = $module->create( $data );

	return $module_id ? $module : false;
}

/**
 * Get Modules.
 *
 * @since 4.4.0
 *
 * @param array $args The modules query args.
 *
 * @return array The array of Module objects.
 */
function wpcw_get_modules( $args = array() ) {
	return wpcw()->modules->get_modules( $args );
}