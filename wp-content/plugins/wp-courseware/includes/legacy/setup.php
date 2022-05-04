<?php
/**
 * WP Courseware Legacy Setup.
 *
 * @package WPCW
 * @since 4.1.0
 */

/**
 * Create screen optionsn for the course dashboard page.
 *
 * @since 4.1.0
 */
function WPCW_course_dashboard_screen_options() {
	$user_id = get_current_user_id();

	// Check for form submission
	if ( isset( $_POST['course_dashboard_screen_options_submit'] ) AND $_POST['course_dashboard_screen_options_submit'] == 1 ) {
		if ( isset( $_POST['quiz_notification_hide'] ) && $_POST['quiz_notification_hide'] = 'show' ) {
			update_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', 'show', 'hide' );
		} else {
			update_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', 'hide', 'show' );
		}
		if ( isset( $_POST['wpcw_courses_per_page'] ) && $_POST['wpcw_courses_per_page'] > 0 ) {
			update_user_meta( $user_id, 'wpcw_courses_per_page', $_POST['wpcw_courses_per_page'], '' );
		}
	}

	// Initiate the $screen variable.
	$screen = get_current_screen();

	// Add our custom HTML to the screen options panel.
	add_filter( 'screen_layout_columns', function ( $html ) {
		$user_id = get_current_user_id();
		$html    = sprintf( '<div id="screen-options-wrap">
			<form name="course_dashboard_screen_options_form" method="post">
				<input type="hidden" name="course_dashboard_screen_options_submit" value="1">
				<legend>WP Courseware - Course Dashboard Options</legend>
					<fieldset >
						<label>Enable/Disable - Quiz Notifications</label><input class="hide-column-tog" name="quiz_notification_hide" type="checkbox" id="quiz_notification_hide" value="%s" %s>
					</fieldset>
					<fieldset >
						<label for="wpcw_courses_per_page">Number of courses per page:</label><input type="number" step="1" min="1" max="50" class="screen-per-page" name="wpcw_courses_per_page" id="wpcw_courses_per_page" maxlength="3" value="%d">
					</fieldset>
						<p class="submit"><input type="submit" name="screen-options-apply" id="screen-options-apply" class="button button-primary" value="Apply"></p>
			</form>
			</div>',
			$quiz_notify = ( get_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', true ) != false ) ? get_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', true ) : 'hide',
			$quiz_notify_check = ( get_user_meta( $user_id, 'wpcw_course_dashboard_quiz_notification_hide', true ) != 'show' ) ? '' : 'checked',
			$course_per_page = ( get_user_meta( $user_id, 'wpcw_courses_per_page', true ) != false ) ? get_user_meta( $user_id, 'wpcw_courses_per_page', true ) : 20
		);

		echo $html;
	} );

	// Register our new screen options tab.
	$screen->add_option( 'my_option', '' );
}

/**
 * Return the URL for the page Icon.
 *
 * @since 4.1.0
 *
 * @return string The URL for the page icon.
 */
function WPCW_icon_getPageIconURL() {
	return WPCW_IMG_URL . 'icon_training_32.png';
}

/**
 * Get the URL for the plugin path including a trailing slash.
 *
 * @since 4.1.0
 *
 * @return string The URL for the plugin path.
 */
function WPCW_plugin_getPluginPath() {
	return plugin_dir_url( WPCW_FILE );
}

/**
 * Get the directory path for the plugin path including a trailing slash.
 *
 * @since 4.1.0
 *
 * @return string The URL for the plugin path.
 */
function WPCW_plugin_getPluginDirPath() {
	$folder = basename( dirname( WPCW_FILE ) );

	return WP_PLUGIN_DIR . "/" . trailingslashit( $folder );
}

/**
 * Determine if we're on a page just related to this plugin in the admin area.
 *
 * @since 4.1.0
 *
 * @return boolean True if we're on an admin page, false otherwise.
 */
function WPCW_areWeOnPluginPage() {
	if ( $currentPage = WPCW_arrays_getValue( $_GET, 'page' ) ) {
		if ( substr( $currentPage, 0, 5 ) == 'WPCW_' ) {
			return true;
		}
	}

	return false;
}

/**
 * Hide items from the menu we don't want, but still want access to.
 *
 * @since 4.1.0
 */
function WPCW_menu_MainMenu_cleanUnwantedEntries() {
	global $submenu;

	// Rename the Training Courses page to include a count of quizzes that need attention.
	$quizCount = WPCW_quizzes_getCoursesNeedingAttentionCount();
	if ( $quizCount > 0 ) {
		if ( isset( $submenu[ WPCW_PLUGIN_ID ] ) ) {
			$submenu[ WPCW_PLUGIN_ID ][0][0] .= sprintf( '<span class="update-plugins count-%d"><span class="update-count">%s</span></span>', $quizCount, $quizCount );
		}
	}

	// Hide context pages
	WPCW_menu_removeSubmenuItem( WPCW_PLUGIN_ID, 'WPCW_showPage_CourseOrdering' );
	WPCW_menu_removeSubmenuItem( WPCW_PLUGIN_ID, 'WPCW_showPage_ConvertPage' );
	WPCW_menu_removeSubmenuItem( WPCW_PLUGIN_ID, 'WPCW_showPage_GradeBook' );
	//WPCW_menu_removeSubmenuItem(WPCW_PLUGIN_ID, 'WPCW_showPage_ModifyQuestion');

	// Hide User Menus
	WPCW_menu_removeSubmenuItem( 'admin.php', 'WPCW_showPage_UserCourseAccess' );
	WPCW_menu_removeSubmenuItem( 'admin.php', 'WPCW_showPage_UserProgess' );
	WPCW_menu_removeSubmenuItem( 'admin.php', 'WPCW_showPage_UserProgess_quizAnswers' );

	// Hide from single profile menus
	WPCW_menu_removeSubmenuItem( 'admin.php', 'WPCW_showPage_UserCourseAccess' );
	WPCW_menu_removeSubmenuItem( 'profile.php', 'WPCW_showPage_UserProgess' );
	WPCW_menu_removeSubmenuItem( 'profile.php', 'WPCW_showPage_UserProgess_quizAnswers' );
}

/**
 * Function called on initialisation of the function to clean up orphan tags.
 *
 * @since 4.1.0
 */
function WPCW_tag_cleanup() {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// If table doesn't exist, don't run maintenance.
	if ( ! is_null( $wpdb->get_var( "SHOW TABLES LIKE '$wpcwdb->question_tag_mapping'" ) ) ) {
		// We want to find all unique questions that have been deleted. We do this by
		// joining the tag table with the table of questions. WHERE filters out where
		// a tag exists, but a question does.
		$tags = $wpdb->get_results( "SELECT tm.*, qq.question_id AS jn_question_id
				FROM $wpcwdb->question_tag_mapping tm
				LEFT JOIN $wpcwdb->quiz_qs qq ON qq.question_id = tm.question_id
				WHERE qq.question_id IS NULL
				" );

		// Delete the tag from each question that we've found above, which will also
		// update the tag popularity once deleted.
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				WPCW_questions_tags_removeTag( $tag->question_id, $tag->tag_id );
			}
		}
	}
}