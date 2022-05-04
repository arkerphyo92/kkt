<?php
/**
 * WP Courseware Deprecated Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Wrapper for _doing_it_wrong.
 *
 * @since 4.3.0
 *
 * @param string $function The function used.
 * @param string $message The message to log
 * @param string $version The version the message was added in.
 */
function wpcw_doing_it_wrong( $function, $message, $version ) {
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( wpcw_is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
}

/**
 * Wrapper for deprecated arguments so we can apply some extra logic.
 *
 * @since 4.3.0
 *
 * @param string $argument The deprecated argument.
 * @param string $version The version it was added in.
 * @param string $replacement The replacement argument.
 */
function wpcw_deprecated_argument( $argument, $version, $message = null ) {
	if ( wpcw_is_ajax() ) {
		do_action( 'deprecated_argument_run', $argument, $message, $version );
		error_log( "The {$argument} argument is deprecated since version {$version}. {$message}" );
	} else {
		_deprecated_argument( $argument, $version, $message );
	}
}

/**
 * Main Initializing Function.
 *
 * Primarily used for addons.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_plugin_init() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Are we the site admin?
 *
 * This is if we're not a multi-site, or we are
 * a multi-site but in the network admin area.
 *
 * @since 4.3.0
 *
 * @return bool
 * @deprecated 4.1.0
 *
 */
function WPCW_plugin_hasAdminRights() {
	_deprecated_function( __FUNCTION__, '4.1.0' );

	return ! is_multisite() || is_network_admin();
}

/**
 * License Setup Updater.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_licence_edd_setupUpdater() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * License Activation.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_licence_edd_activateLicence() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * License Check.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_licence_edd_checkLicence() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * License Renewal Notice.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_license_renewal_notice() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Load Plugin Textdomain.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_load_textdomain() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Load Old Plugin Texdomain.
 *
 * Leftover from the last re-write. Should not be used.
 *
 * @since 1.0.0
 *
 * @param $mofile
 * @param $textdomain
 *
 * @deprecated 4.1.0
 *
 */
function WPCW_load_old_textdomain( $mofile, $textdomain ) {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Array Splice Association.
 *
 * @since 4.1.0
 *
 * @param $input
 * @param $offset
 * @param $length
 * @param $replacement
 *
 * @deprecated 4.1.0
 *
 */
function wpcw_array_splice_assoc( &$input, $offset, $length, $replacement ) {
	_deprecated_function( __FUNCTION__, '4.1.0' );

	$replacement = (array) $replacement;
	$key_indices = array_flip( array_keys( $input ) );
	if ( isset( $input[ $offset ] ) && is_string( $offset ) ) {
		$offset = $key_indices[ $offset ];
	}
	if ( isset( $input[ $length ] ) && is_string( $length ) ) {
		$length = $key_indices[ $length ] - $offset;
	}

	$input = array_slice( $input, 0, $offset, true )
	         + $replacement
	         + array_slice( $input, $offset + $length, null, true );
}

/**
 * WordPress stores names arrays ( month and weekday and all variants )
 * with text value indices, so this creates an array with numerical indices instead.
 *
 * @since 4.1.0
 *
 * @param array $arrayToClean The array to strip out the text indices.
 *
 * @return array The array with numerical indicies.
 * @deprecated 4.1.0
 *
 */
function WPCW_arrays_stripStringIndices( $arrayToClean ) {
	_deprecated_function( __FUNCTION__, '4.1.0', 'wpcw_string_array_to_numeric' );

	return wpcw_string_array_to_numeric( $arrayToClean );
}

/**
 * Create the main menu.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_menu_MainMenu() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Create the main menu.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_menu_MainMenu_NetworkOnly() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Add the styles needed for the page for this plugin.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_addCustomCSS_BackEnd() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Add the scripts needed for the page for this plugin.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_addCustomScripts_BackEnd() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Add the scripts we want loaded in the header.
 *
 * @since 4.1.0
 *
 * @deprecated 4.1.0
 */
function WPCW_addCustomScripts_FrontEnd() {
	_deprecated_function( __FUNCTION__, '4.1.0' );
}

/**
 * Save the settings to the WordPress settings table.
 *
 * @since 1.0.0
 *
 * @deprectated 4.1.0
 *
 * @param array $settingDetails The list of settings to be saved.
 * @param string $settingPrefix The string key used to save the array of settings.
 *
 * @return bool True if the settings were saved, false otherwise.
 */
if ( ! function_exists( 'TidySettings_saveSettings' ) ) {
	function TidySettings_saveSettings( $settingDetails, $settingPrefix ) {
		_deprecated_function( __FUNCTION__, '4.1.0', 'WPCW_TidySettings_saveSettings' );

		return WPCW_TidySettings_saveSettings( $settingDetails, $settingPrefix );
	}
}

/**
 * Get all of the settings as an array.
 *
 * @since 1.0.0
 *
 * @param string $settingPrefix The string key used to store the array of settings.
 *
 * @return string The list of settings as an associative array.
 * @deprecated 4.1.0
 *
 */
if ( ! function_exists( 'TidySettings_getSettings' ) ) {
	function TidySettings_getSettings( $settingPrefix ) {
		_deprecated_function( __FUNCTION__, '4.1.0', 'WPCW_TidySettings_getSettings' );

		return WPCW_TidySettings_getSettings( $settingPrefix );
	}
}

/**
 * Get just a single setting from the settings list.
 *
 * @since 1.0.0
 *
 * @param string $settingPrefix The string key used to store the array of settings.
 * @param string $settingName The name of the setting key for the individual setting to retrieve.
 * @param string $defaultValue The value to return if the setting was not found.
 *
 * @return string The value of the setting.
 * @deprecated 4.1.0
 *
 */
if ( ! function_exists( 'TidySettings_getSettingSingle' ) ) {
	function TidySettings_getSettingSingle( $settingPrefix, $settingName, $defaultValue = false ) {
		_deprecated_function( __FUNCTION__, '4.1.0', 'WPCW_TidySettings_getSettingSingle' );

		return WPCW_TidySettings_getSettingSingle( $settingPrefix, $settingName, $defaultValue = false );
	}
}

/**
 * Creates the course unit post type.
 *
 * @since 4.3.0
 *
 * @deprecated 4.3.0
 */
function WPCW_plugin_registerCustomPostTypes() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Shows the documentation page for the plugin.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Documentation() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Open a pane of content.
 *
 * @since 1.0.0
 *
 * @param string $id The ID of the unit.
 * @param string $caption The caption of the unit.
 * @param string $extracss Any extra CSS styles to add.
 *
 * @deprecated 4.3.0
 *
 */
function WPCW_docs_showRHSPane_open( $id, $caption, $extracss = 'wpcw_docs_rhs_content' ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Close the pane of content.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_docs_showRHSPane_close() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Shows the documentation page for the plugin.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Documentation_load() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Documentation: Shortcodes.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Documentation_shortcodes() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Documentation: Howto Videos
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Documentation_howto() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Shows the settings page for the plugin.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Settings() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Shows the settings page for the plugin.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Settings_load() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Function called after settings are saved.
 *
 * @since 1.0.0
 *
 * @param string $formValuesFiltered The data values actually saved to the database after filtering.
 * @param string $originalFormValues The original data values before filtering.
 * @param object $formObj The form object thats doing the saving.
 *
 * @deprecated 4.3.0
 *
 */
function WPCW_showPage_Settings_afterSave( $formValuesFiltered, $originalFormValues, $formObj ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Shows the settings page for the plugin, shown just for the network page.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Settings_Network_load() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Show the page where the user can set up the certificate settings.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Certificates() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Show the page where the user can set up the certificate settings.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_showPage_Certificates_load() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Generates the upload directory with an empty index.php file to prevent directory listings.
 *
 * @since 1.0.0
 *
 * @deprecated 4.3.0
 */
function WPCW_files_createFileUploadDirectory_base() {
	_deprecated_function( __FUNCTION__, '4.3.0', 'wpcw_create_upload_directory' );
	wpcw_create_upload_directory();
}

/**
 * Function executed when the plugin is enabled in WP admin.
 *
 * @since 4.1.0
 *
 * @deprecated 4.3.0
 */
function WPCW_plugin_activationChecks() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Install the plugin, initialise the default settings, and create the tables for the websites and groups.
 *
 * @since 4.1.0
 *
 * @deprecated 4.3.0
 */
function WPCW_plugin_setup( $force ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Check to see if roles have been created, if not create them.
 *
 * @since 4.1.0
 *
 * @deprecated 4.3.0
 */
function WPCW_plugin_rolesCheck() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Adds user meta value when dismiss button is clicked
 *
 * @since 4.1.0
 *
 * @deprecated 4.3.0
 */
function WPCW_dismiss_admin_notice() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Function executed when the plugin is disabled in WP admin.
 *
 * @since 4.1.0
 *
 * @deprecated 4.3.0
 */
function WPCW_plugin_deactivationChecks() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Function executed when the plugin is uninstalled.
 *
 * @since 4.1.0
 *
 * @deprecated 4.3.0
 */
function WPCW_plugin_uninstallChecks() {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Creates an enrollment button for both "logged-on" or "logged-off" users.
 *
 *  e.g. [wpcourse_enroll courses="2,3" enroll_text="Enroll Here" ]
 *
 * @since 1.0.0
 *
 * @param array $atts The attributes of the shortcode.
 *
 * @return string
 * @deprecated 4.3.0
 *
 */
function wpcw_create_enrollment_button( $atts ) {
	_deprecated_function( __FUNCTION__, '4.3.0' );
}

/**
 * Remove Flush Rules Flag.
 *
 * @since 4.3.0
 *
 * @deprecated 4.4.0
 */
function wpcw_remove_flush_rules_flag() {
	_deprecated_function( __FUNCTION__, '4.4.0', 'wpcw_remove_flush_rewrite_rules_flag' );
	wpcw_remove_flush_rewrite_rules_flag();
}

/**
 * Creates the correct URL for course units, showing module and course names.
 *
 * @since 4.1.0
 *
 * @param string $post_link The current permalinkf for the unit (which includes %module_number%).
 * @param object $post The object of the post for which a URL is requested.
 *
 * @deprecated 4.4.0
 *
 */
function WPCW_units_createCorrectUnitURL( $post_link, $post = 0, $leavename = false ) {
	_deprecated_function( __FUNCTION__, '4.4.0', '\WPCW\Controllers\Units\post_type_link' );

	return wpcw()->units->post_type_link( $post_link, $post, $leavename, false );
}

/**
 * Get Courses.
 *
 * @since 4.0.9.4
 *
 * @param string $orderby The orderby paramater.
 *
 * @return array The courses ordered by 'course_title'
 * @deprecated 4.4.0
 *
 */
function WPCW_getCourses( $orderby = 'course_title' ) {
	_deprecated_function( __FUNCTION__, '4.4.0', 'wpcw_get_courses' );

	return wpcw_get_courses( array( 'orderby' => $orderby ) );
}

/**
 * Checks to see if the permalinks are using '/%postname%/' for WPCW to work correctly.
 *
 * @since 4.1.0
 *
 * @deprecated 4.4.0
 */
function WPCW_plugin_permalinkCheck() {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Message shown to say that multi-site is not currently supported.
 *
 * @since 4.1.0
 *
 * @deprecated 4.4.0
 */
function WPCW_plugin_multisiteCheck() {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Filter the query for course units by course.
 *
 * @since 1.0.0
 *
 * @deprecated 4.4.0
 */
function WPCW_course_unit_course_filter_query( $query ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Filter the query for course units by author
 *
 * @since 1.0.0
 *
 * @deprecated 4.4.0
 */
function WPCW_course_unit_permissions_filter_query( $query ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Change Course Unit Counts when filtering by author id for permissions.
 *
 * @since 1.0.0
 *
 * @deprecated 4.4.0
 */
function WPCW_course_unit_permissions_change_counts( $current_screen ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Update the course unit summary columns to shows the related modules and courses.
 *
 * @since 1.0.0
 *
 * @param array $column_headers The list of columns to show (before showing them).
 *
 * @return array The actual list of columns to show.
 * @deprecated 4.4.0
 *
 */
function WPCW_units_manageColumns( $column_headers ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Creates the column columns of data.
 *
 * @since 1.0.0
 *
 * @param string $column_name The name of the column we're changing.
 * @param integer $post_id The ID of the post we're rendering.
 *
 * @return string The formatted HTML code for the table.
 * @deprecated 4.4.0
 *
 */
function WPCW_units_addCustomColumnContent( $column_name, $post_id ) {
	_deprecated_function( __FUNCTION__, '4.4.0' );
}

/**
 * Attaches the meta boxes to posts and pages to add extra information to them.
 *
 * @since 1.0.0
 *
 * @deprecated 4.5.1
 */
function WPCW_units_showEditScreenMetaBoxes() {
	_deprecated_function( __FUNCTION__, '4.5.1' );
}

/**
 * Constructs the inner form to convert the post type to a course unit.
 *
 * @since 1.0.0
 *
 * @deprecated 4.5.1
 */
function WPCW_units_metabox_showConversionTool() {
	_deprecated_function( __FUNCTION__, '4.5.1' );
}

/**
 * Constructs the inner form to allow the
 * user to choose a template for a unit.
 *
 * @since 1.0.0
 *
 * @deprecated 4.5.1
 */
function WPCW_units_metabox_showTemplateSelectionTool() {
	_deprecated_function( __FUNCTION__, '4.5.1' );
}

/**
 * Constructs the inner form for the drip selection tool, for drip feeding units to a trainee.
 *
 * @since 1.0.0
 *
 * @deprecated 4.5.1
 */
function WPCW_units_metabox_showContentDripSelectionTool() {
	_deprecated_function( __FUNCTION__, '4.5.1' );
}

/**
 * This function removes the user progress for the specified list of users and units.
 *
 * @since 1.0.0
 *
 * @param array $userList The list of users to reset.
 * @param array $unitList The list of units to remove from their progress.
 * @param object $courseDetails The details of the course.
 * @param integer $totalUnitCount The total number of units in this course.
 *
 * @deprecated 4.6.4
 */
function WPCW_users_resetProgress( $userList, $unitList, $courseDetails, $totalUnitCount ) {
	_deprecated_function( __FUNCTION__, '4.6.4', 'wpcw_reset_progress' );
}
