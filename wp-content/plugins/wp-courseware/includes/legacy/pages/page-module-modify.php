<?php
/**
 * WP Courseware Page Module Modify.
 *
 * Functions relating to allowing you to modify the settings and details of a module.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Function that allows a module to be created or edited.
 *
 * @since 1.0.0
 */
function WPCW_showPage_ModifyModule_load() {
	global $wpcwdb;

	$page          = new PageBuilder( true );
	$moduleDetails = false;
	$moduleID      = isset( $_GET['module_id'] ) ? absint( $_GET['module_id'] ) : false;
	$adding        = ( ! $moduleID ) ? true : false;
	$canAddModule  = false;
	$canEditModule = false;
	$current_user  = wp_get_current_user();
	$moduleForm    = '';

	// Urls.
	$modules_index_url = add_query_arg( array( 'page' => 'wpcw-modules' ), admin_url( 'admin.php' ) );
	$module_page_url   = add_query_arg( array( 'page' => 'WPCW_showPage_ModifyModule' ), admin_url( 'admin.php' ) );
	$courses_page_url  = add_query_arg( array( 'post_type' => 'wpcw_course' ), admin_url( 'edit.php' ) );

	// Module Page Title.
	$module_page_title = $adding ? esc_html__( 'Add Course Module', 'wp-courseware' ) : esc_html__( 'Edit Course Module', 'wp-courseware' );

	// Module Details.
	$moduleDetails = WPCW_modules_getModuleDetails( $moduleID );

	// Permission & Data Checks.
	if ( $moduleID ) {
		if ( ! $moduleDetails ) {
			$page->showPageHeader( $module_page_title, '75%' );
			$page->showMessage( esc_html__( 'Sorry, but that course module could not be found.', 'wp-courseware' ), true );
			$page->showPageFooter();

			return;
		}

		$canEditModule     = apply_filters( 'wpcw_permissions_user_can_edit_module', $canEditModule, $current_user, $moduleDetails );
		$cantEditModuleMsg = apply_filters( 'wpcw_permissions_user_can_edit_module_msg', esc_html__( 'You are not permitted to edit this module.', 'wp-courseware' ), $current_user, $moduleDetails );

		if ( user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
			$canEditModule = true;
		}

		if ( $moduleDetails->module_author == $current_user->ID ) {
			$canEditModule = true;
		}

		if ( ! $canEditModule ) {
			$page->showPageHeader( $module_page_title, '75%' );
			$page->showMessage( $cantEditModuleMsg, true );
			$page->showPageFooter();

			return;
		}
	} else {
		if ( user_can( $current_user->ID, 'view_wpcw_courses' ) ) {
			$canAddModule = true;
		}

		$canAddModule  = apply_filters( 'wpcw_permissions_user_can_add_module', $canAddModule, $current_user );
		$cantAddModule = apply_filters( 'wpcw_permissions_user_can_add_module_msg', esc_html__( 'You are not permitted to add a new module.', 'wp-courseware' ), $current_user );
		$canAddModule  = apply_filters( 'wpcw_back_permissions_user_can_add_module', $canAddModule, $current_user->ID );
		$cantAddModule = apply_filters( 'wpcw_back_msg_permissions_user_can_add_module', esc_html__( 'You are not permitted to add a new module.', 'wp-courseware' ), $current_user->ID );

		if ( ! $canAddModule ) {
			$page->showPageHeader( $module_page_title, '75%' );
			$page->showMessage( $cantAddModule, true );
			$page->showPageFooter();

			return;
		}
	}

	// Form Details.
	$formDetails = array(
		'module_title'     => array(
			'label'    => __( 'Module Title', 'wp-courseware' ),
			'type'     => 'text',
			'required' => true,
			'cssclass' => 'wpcw_module_title',
			'desc'     => __( 'The title of your module. You <b>do not need to number the modules</b> - this is done automatically based on the order that they are arranged.', 'wp-courseware' ),
			'validate' => array(
				'type'   => 'string',
				'maxlen' => 150,
				'minlen' => 1,
				'regexp' => '/^[^<>]+$/',
				'error'  => __( 'Please specify a name for your module, up to a maximum of 150 characters, just no angled brackets (&lt; or &gt;). Your trainees will be able to see this module title.', 'wp-courseware' ),
			),
		),
		'parent_course_id' => array(
			'label'    => __( 'Associated Course', 'wp-courseware' ),
			'type'     => 'select',
			'required' => true,
			'cssclass' => 'wpcw_associated_course',
			'desc'     => __( 'The associated training course that this module belongs to.', 'wp-courseware' ),
			'data'     => WPCW_courses_getCourseList( __( '-- Select a Training Course --', 'wp-courseware' ) ),
		),
		'module_desc'      => array(
			'label'    => __( 'Module Description', 'wp-courseware' ),
			'type'     => 'textarea',
			'required' => true,
			'cssclass' => 'wpcw_module_desc',
			'desc'     => __( 'The description of this module. Your trainees will be able to see this module description.', 'wp-courseware' ),
			'validate' => array(
				'type'   => 'string',
				'maxlen' => 5000,
				'minlen' => 1,
				'error'  => __( 'Please limit the description of your module to 5000 characters.', 'wp-courseware' ),
			),
		),
		'module_author'    => array(
			'label' => __( 'Module Author', 'wp-courseware' ),
			'type'  => 'hidden',
		),
	);

	// Form Object.
	$form = new RecordsForm( $formDetails, $wpcwdb->modules, 'module_id' );

	// Custom Error Message.
	$form->customFormErrorMsg = esc_html__( 'Sorry, but unfortunately there were some errors saving the module details. Please fix the errors and try again.', 'wp-courseware' );

	// Translations.
	$form->setAllTranslationStrings( WPCW_forms_getTranslationStrings() );

	// Load defaults when adding.
	if ( $adding && ! $moduleDetails ) {
		$form->loadDefaults( array(
			'module_author' => get_current_user_id(),
		) );
	}

	// See if we have a course ID to pre-set.
	if ( $adding && $courseID = WPCW_arrays_getValue( $_GET, 'course_id' ) ) {
		$form->loadDefaults( array(
			'parent_course_id' => $courseID,
		) );
	}

	// Back to course modules link.
	$directionMsg = sprintf(
		' <a href="%s">%s</a>',
		esc_url( $modules_index_url ),
		esc_html__( 'Back to Course Modules', 'wp-courseware' )
	);

	// Override success messages
	$form->msg_record_created = esc_html__( 'Module Created Successfully!', 'wp-courseware' ) . $directionMsg;
	$form->msg_record_updated = esc_html__( 'Module Updated Successfully!', 'wp-courseware' ) . $directionMsg;

	$form->setPrimaryKeyValue( $moduleID );
	$form->setSaveButtonLabel( esc_html__( 'Save Course Module', 'wp-courseware' ) );

	// Call to re-order modules once they've been created
	$form->afterSaveFunction = 'WPCW_actions_modules_afterModuleSaved_formHook';

	// Get Form Html - This is where all the processing of the form happens.
	$moduleForm = $form->getHTML();

	// Check if it has been saved.
	if ( $form->primaryKeyValue ) {
		$adding   = false;
		$moduleID = $form->primaryKeyValue;
		if ( empty( $courseID ) ) {
			$courseID = isset( $form->recordDetails['parent_course_id'] ) ? $form->recordDetails['parent_course_id'] : false;
		}
		$moduleDetails = WPCW_modules_getModuleDetails( $moduleID );
	}

	// Module Page Title.
	$module_page_title = $adding ? esc_html__( 'Add Course Module', 'wp-courseware' ) : esc_html__( 'Edit Course Module', 'wp-courseware' );

	if ( ! $adding ) {
		$module_page_title .= sprintf(
			' <a class="page-title-action" href="%s">%s</a>',
			esc_url( $module_page_url ),
			esc_html__( 'Add New', 'wp-courseware' )
		);
	}

	$module_page_title .= sprintf(
		' <a class="page-title-action" href="%s">%s</a>',
		esc_url( add_query_arg( array( 'post_type' => 'course_unit' ), admin_url( 'post-new.php' ) ) ),
		esc_html__( 'Add Unit', 'wp-courseware' )
	);

	$module_page_title .= sprintf(
		' <a class="page-title-action" href="%s">%s</a>',
		esc_url( add_query_arg( array( 'page' => 'WPCW_showPage_ModifyQuiz' ), admin_url( 'admin.php' ) ) ),
		esc_html__( 'Add Quiz', 'wp-courseware' )
	);

	if ( ! empty( $courseID ) ) {
		$course_object = new \WPCW\Models\Course( absint( $courseID ) );
		$module_page_title .= sprintf(
			' <a class="page-title-action" href="%s">%s</a>',
			esc_url( add_query_arg( array( 'post' => $course_object->get_course_post_id(), 'action' => 'edit' ), admin_url( 'post.php' ) ) ),
			esc_html__( 'Back to Course', 'wp-courseware' )
		);
	}

	$module_page_title .= sprintf(
		' <a class="page-title-action" href="%s">%s</a>',
		esc_url( $modules_index_url ),
		esc_html__( 'Back to Modules', 'wp-courseware' )
	);

	$module_page_title .= sprintf(
		' <a class="page-title-action" href="%s">%s</a>',
		esc_url( $courses_page_url ),
		esc_html__( 'Back to Courses', 'wp-courseware' )
	);

	// Page Header.
	$page->showPageHeader( $module_page_title, '75%' );

	// Form Output.
	echo $moduleForm;

	// Middle Separator.
	$page->showPageMiddle( '25%' );

	// Sidebar.
	if ( $moduleDetails ) {
		$page->openPane( 'wpcw-deletion-module', esc_html__( 'Delete Module?', 'wp-courseware' ) );

		printf(
			'<a href="%s" class="button-primary wpcw_delete_item" title="%s">%s</a>',
			add_query_arg( array( 'action' => 'delete', 'module_id' => $moduleID ), $modules_index_url ),
			esc_html__( "Are you sure you want to delete the this module?\n\nThis CANNOT be undone!", 'wp-courseware' ),
			esc_html__( 'Delete this Module', 'wp-courseware' )
		);

		printf( '<p>%s</p>', __( 'Units will <b>not</b> be deleted, they will <b>just be disassociated</b> from this module.', 'wp-courseware' ) );

		$page->closePane();

		// Sub Units
		$page->openPane( 'wpcw-units-module', esc_html__( 'Units in this Module', 'wp-courseware' ) );

		// Unit List.
		$unitList = WPCW_units_getListOfUnits( $moduleID );

		if ( $unitList ) {
			printf( '<ul class="wpcw_unit_list">' );
			foreach ( $unitList as $unitID => $unitObj ) {
				printf(
					'<li><a href="%s">%s %d - %s</a></li>',
					add_query_arg( array( 'post' => $unitID, 'action' => 'edit' ), admin_url( 'post.php' ) ),
					esc_html__( 'Unit', 'wp-courseware' ),
					$unitObj->unit_meta->unit_number,
					$unitObj->post_title
				);
			}
			printf( '</ul>' );
		} else {
			printf( '<p>%s</p>', esc_html__( 'There are currently no units in this module.', 'wp-courseware' ) );
		}
	}

	// Footer.
	$page->showPageFooter();
}