<?php
/**
 * Australian states
 *
 * @package WPCW
 * @subpackage Common\States
 * @since 4.3.0
 */
global $wpcw_states;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

$wpcw_states['AU'] = array(
	'ACT' => __( 'Australian Capital Territory', 'wp-courseware' ),
	'NSW' => __( 'New South Wales', 'wp-courseware' ),
	'NT'  => __( 'Northern Territory', 'wp-courseware' ),
	'QLD' => __( 'Queensland', 'wp-courseware' ),
	'SA'  => __( 'South Australia', 'wp-courseware' ),
	'TAS' => __( 'Tasmania', 'wp-courseware' ),
	'VIC' => __( 'Victoria', 'wp-courseware' ),
	'WA'  => __( 'Western Australia', 'wp-courseware' ),
);
