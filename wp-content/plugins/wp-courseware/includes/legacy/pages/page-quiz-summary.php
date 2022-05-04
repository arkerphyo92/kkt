<?php
/**
 * WP Courseware Page Quiz Summary.
 *
 * Functions relating to showing the quiz summary page.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Function that show a summary of the quizzes.
 *
 * @since 1.0.0
 */
function WPCW_showPage_QuizSummary_load() {
	// Globals
	global $wpcwdb, $wpdb;

	// Vars
	$page         = new PageBuilder( false );
	$current_user = wp_get_current_user();
	$user_id      = $current_user->ID;

	// Get the requested page number
	$paging_pageWanted = absint( WPCW_arrays_getValue( $_GET, 'pagenum' ) );
	if ( $paging_pageWanted == 0 ) {
		$paging_pageWanted = 1;
	}

	$quiz_url = add_query_arg( array( 'page' => 'WPCW_showPage_ModifyQuiz' ), admin_url( 'admin.php' ) );

	// Title for page with page number
	$titlePage = false;
	if ( $paging_pageWanted > 1 ) {
		$titlePage = sprintf( ' - %s %s', __( 'Page', 'wp-courseware' ), $paging_pageWanted );
	}

	$titlePage .= sprintf( ' <a class="page-title-action" href="%s">%s</a>', esc_url( $quiz_url ), esc_html__( 'Add New', 'wp-courseware' ) );

	// Page Header
	$page->showPageHeader( __( 'Quiz &amp; Survey Summary', 'wp-courseware' ) . $titlePage, '75%', WPCW_icon_getPageIconURL() );

	// Check permissions
	if ( ! user_can( $current_user, 'view_wpcw_courses' ) ) {
		$page->showMessage( __( 'You do not have sufficient permissions to access this page.', 'wp-courseware' ), true );
		$page->showPageFooter();

		return;
	}

	// Handle the quiz deletion before showing remaining quizzes...
	WPCW_quizzes_handleQuizDeletion( $page );

	// Handle the sorting and filtering
	$orderBy  = WPCW_arrays_getValue( $_GET, 'orderby' );
	$ordering = WPCW_arrays_getValue( $_GET, 'order' );

	// Validate ordering
	switch ( $orderBy ) {
		case 'quiz_title':
		case 'quiz_id':
			break;

		default:
			$orderBy = 'quiz_id';
			break;
	}

	// Create opposite ordering for reversing it.
	$ordering_opposite = false;
	switch ( $ordering ) {
		case 'desc':
			$ordering_opposite = 'asc';
			break;

		case 'asc':
			$ordering_opposite = 'desc';
			break;

		default:
			$ordering          = 'desc';
			$ordering_opposite = 'asc';
			break;
	}

	// Was a search string specified? Or a specific item?
	$searchString = esc_html( WPCW_arrays_getValue( $_GET, 's' ) );

	// Summary page url
	$summaryPageURL = admin_url( 'admin.php?page=WPCW_showPage_QuizSummary' );

	// Show the form for searching
	?>
    <form id="wpcw_quizzes_search_box" method="get" action="<?php echo $summaryPageURL; ?>">
        <p class="search-box">
            <label class="screen-reader-text" for="wpcw_quizzes_search_input"><?php _e( 'Search Quizzes', 'wp-courseware' ); ?></label>
            <input id="wpcw_quizzes_search_input" type="text" value="<?php echo $searchString ?>" name="s"/>
            <input class="button" type="submit" value="<?php _e( 'Search Quizzes', 'wp-courseware' ); ?>"/>
            <input type="hidden" name="page" value="WPCW_showPage_QuizSummary"/>
        </p>
    </form>
	<?php

	// WHERE CONDITIONS and WHERE string for sql queries later on.
	$SQL_WHERE_CONDITIONS = array();
	$SQL_WHERE            = false;

	// Search String Condition
	if ( $searchString ) {
		$SQL_WHERE_CONDITIONS[] = $wpdb->prepare( 'quiz_title LIKE %s OR quiz_desc LIKE %s', '%' . $searchString . '%', '%' . $searchString . '%' );
	}

	// User Permissions Condition
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		$SQL_WHERE_CONDITIONS[] = $wpdb->prepare( 'quiz_author = %d', $current_user->ID );
	}

	// Build Where Query
	if ( is_array( $SQL_WHERE_CONDITIONS ) && ! empty( $SQL_WHERE_CONDITIONS ) ) {
		$SQL_WHERE       = 'WHERE ';
		$counter         = 1;
		$condition_count = count( $SQL_WHERE_CONDITIONS );
		foreach ( $SQL_WHERE_CONDITIONS as $SQL_CONDITION ) {
			$SQL_WHERE .= ( $counter !== $condition_count ) ? $SQL_CONDITION . ' AND ' : $SQL_CONDITION;
			$counter++;
		}
	}

	$SQL_PAGING = "SELECT COUNT(*) as quiz_count FROM $wpcwdb->quiz $SQL_WHERE ORDER BY quiz_id DESC";

	$paging_resultsPerPage = 50;
	$paging_totalCount     = $wpdb->get_var( $SQL_PAGING );
	$paging_recordStart    = ( ( $paging_pageWanted - 1 ) * $paging_resultsPerPage ) + 1;
	$paging_recordEnd      = ( $paging_pageWanted * $paging_resultsPerPage );
	$paging_pageCount      = ceil( $paging_totalCount / $paging_resultsPerPage );
	$paging_sqlStart       = $paging_recordStart - 1;

	// Show search message - that a search has been tried.
	if ( $searchString ) {
		printf( '<div class="wpcw_search_count">%s "%s" (%s %s) (<a href="%s">%s</a>)</div>', __( 'Search results for', 'wp-courseware' ), htmlentities( $searchString ), $paging_totalCount, _n( 'result', 'results', $paging_totalCount, 'wp-courseware' ), $summaryPageURL, __( 'reset', 'wp-courseware' ) );
	}

	// Do main query
	$SQL = "SELECT *
			FROM $wpcwdb->quiz
			$SQL_WHERE
			ORDER BY $orderBy $ordering
			LIMIT $paging_sqlStart, $paging_resultsPerPage
			"; // These are already checked, so they are safe, hence no prepare()

	// Generate paging code
	$baseURL = WPCW_urls_getURLWithParams( $summaryPageURL, 'pagenum' ) . "&pagenum=";
	$paging  = WPCW_tables_showPagination( $baseURL, $paging_pageWanted, $paging_pageCount, $paging_totalCount, $paging_recordStart, $paging_recordEnd );

	$quizzes = $wpdb->get_results( $SQL );
	if ( $quizzes ) {
		$tbl             = new TableBuilder();
		$tbl->attributes = array(
			'id'    => 'wpcw_tbl_quiz_summary',
			'class' => 'widefat wpcw_tbl',
		);

		// ID - sortable
		$sortableLink = sprintf( '<a href="%s&order=%s&orderby=quiz_id"><span>%s</span><span class="sorting-indicator"></span></a>', $baseURL, ( 'quiz_id' == $orderBy ? $ordering_opposite : 'asc' ), __( 'ID', 'wp-courseware' ) );

		// ID - render
		$tblCol              = new TableColumn( $sortableLink, 'quiz_id' );
		$tblCol->headerClass = ( 'quiz_id' == $orderBy ? 'sorted ' . $ordering : 'sortable' );
		$tblCol->cellClass   = "quiz_id";
		$tbl->addColumn( $tblCol );

		// Title - sortable
		$sortableLink = sprintf( '<a href="%s&order=%s&orderby=quiz_title"><span>%s</span><span class="sorting-indicator"></span></a>', $baseURL, ( 'quiz_title' == $orderBy ? $ordering_opposite : 'asc' ), __( 'Quiz Title', 'wp-courseware' ) );

		// Title - render
		$tblCol              = new TableColumn( $sortableLink, 'quiz_title' );
		$tblCol->headerClass = ( 'quiz_title' == $orderBy ? 'sorted ' . $ordering : 'sortable' );
		$tblCol->cellClass   = "quiz_title";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Associated Unit', 'wp-courseware' ), 'associated_unit' );
		$tblCol->cellClass = "associated_unit";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Quiz Type', 'wp-courseware' ), 'quiz_type' );
		$tblCol->cellClass = "quiz_type";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Show Answers', 'wp-courseware' ), 'quiz_show_answers' );
		$tblCol->cellClass = "quiz_type wpcw_center";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Paging', 'wp-courseware' ), 'quiz_use_paging' );
		$tblCol->cellClass = "quiz_type wpcw_center";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Questions', 'wp-courseware' ), 'total_questions' );
		$tblCol->cellClass = "total_questions wpcw_center";
		$tbl->addColumn( $tblCol );

		$tblCol            = new TableColumn( __( 'Actions', 'wp-courseware' ), 'actions' );
		$tblCol->cellClass = "actions";
		$tbl->addColumn( $tblCol );

		// Stores course details in a mini cache to save lots of MySQL lookups.
		$miniCourseDetailCache = array();

		// Format row data and show it.
		$odd = false;
		foreach ( $quizzes as $quiz ) {
			$data = array();

			// URLs
			$editURL         = admin_url( 'admin.php?page=WPCW_showPage_ModifyQuiz&quiz_id=' . $quiz->quiz_id );
			$surveyExportURL = admin_url( 'admin.php?page=WPCW_showPage_QuizSummary&wpcw_export=csv_export_survey_data&quiz_id=' . $quiz->quiz_id );

			// Maintain paging where possible.
			$deleteURL = $baseURL . '&action=delete&quiz_id=' . $quiz->quiz_id;

			// Basic Details
			$data['quiz_id'] = $quiz->quiz_id;

			// Quiz Title
			$data['quiz_title'] = sprintf( '<b><a href="%s">%s</a></b>', $editURL, $quiz->quiz_title );
			if ( $quiz->quiz_desc ) {
				$data['quiz_title'] .= '<span class="wpcw_quiz_desc">' . $quiz->quiz_desc . '</span>';
			}

			// Associated Unit
			if ( $quiz->parent_unit_id > 0 && $unitDetails = get_post( $quiz->parent_unit_id ) ) {
				$data['associated_unit'] = sprintf( '<span class="associated_unit_unit"><b>%s</b>: <a href="%s" target="_blank" title="%s \'%s\'...">%s</a></span>', __( 'Unit', 'wp-courseware' ), get_permalink( $unitDetails->ID ), __( 'View ', 'wp-courseware' ), $unitDetails->post_title, $unitDetails->post_title );

				// Also add associated course
				if ( isset( $miniCourseDetailCache[ $quiz->parent_course_id ] ) ) {
					$courseDetails = $miniCourseDetailCache[ $quiz->parent_course_id ];
				} else {
					// Save course details to cache (as likely to use it again).
					$courseDetails = $miniCourseDetailCache[ $quiz->parent_course_id ] = WPCW_courses_getCourseDetails( $quiz->parent_course_id );
				}

				// Might not have course details.
				if ( $courseDetails ) {
					$data['associated_unit'] .= sprintf( '<span class="associated_unit_course"><b>%s:</b> <a href="admin.php?page=WPCW_showPage_ModifyCourse&course_id=%d" title="%s \'%s\'...">%s</a></span>', __( 'Course', 'wp-courseware' ), $courseDetails->course_id, __( 'Edit ', 'wp-courseware' ), $courseDetails->course_title, $courseDetails->course_title );
				}
			} // No associated unit yet
			else {
				$data['associated_unit'] = esc_html__( 'N/A', 'wp-courseware' );
			}

			// Showing Answers or paging?
			$data['quiz_show_answers'] = ( 'show_answers' == $quiz->quiz_show_answers ? '<span class="wpcw_tick"></span>' : '-' );
			$data['quiz_use_paging']   = ( 'use_paging' == $quiz->quiz_paginate_questions ? '<span class="wpcw_tick"></span>' : '-' );

			// Type of quiz
			$data['quiz_type'] = WPCW_quizzes_getQuizTypeName( $quiz->quiz_type );

			// Show passmark for blocking quizzes.
			if ( 'quiz_block' == $quiz->quiz_type ) {
				$data['quiz_type'] .= '<span class="wpcw_quiz_pass_info">' . sprintf( __( 'Min. Pass Mark of %d%%', 'wp-courseware' ), $quiz->quiz_pass_mark ) . '</span>';
			}

			// Total number of questions
			$data['total_questions'] = WPCW_quizzes_calculateActualQuestionCount( $quiz->quiz_id );

			// Actions
			$data['actions'] = '<ul class="wpcw_action_link_list">';

			$data['actions'] .= sprintf( '<li><a href="%s" class="button-primary">%s</a></li>', $editURL, __( 'Edit', 'wp-courseware' ) );

			$data['actions'] .= sprintf( '<li><a href="%s" class="button-secondary wpcw_action_link_delete_quiz wpcw_action_link_delete" rel="%s">%s</a></li>', $deleteURL, __( 'Are you sure you wish to delete this quiz?', 'wp-courseware' ), __( 'Delete', 'wp-courseware' ) );

			// Add export button for surveys
			if ( 'survey' == $quiz->quiz_type ) {
				$data['actions'] .= sprintf( '<li class="wpcw_action_item_newline"><a href="%s" class="button-secondary">%s</a></li>', $surveyExportURL, __( 'Export Responses', 'wp-courseware' ) );
			}

			$data['actions'] .= '</ul>';

			// Odd/Even row colouring.
			$odd = ! $odd;
			$tbl->addRow( $data, ( $odd ? 'alternate' : '' ) );
		}

		// Finally show table
		echo $paging;
		echo $tbl->toString();
		echo $paging;
	} else {
		printf( '<p>%s</p>', __( 'There are currently no quizzes to show. Why not create one?', 'wp-courseware' ) );
	}

	$page->showPageFooter();
}

/**
 * Handle the quiz deletion from the summary page.
 *
 * @since 1.0.0
 *
 * @param PageBuilder $page The page rendering object.
 */
function WPCW_quizzes_handleQuizDeletion( $page ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Check that the quiz exists and deletion has been requested
	if ( isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['quiz_id'] ) ) {
		$quizID      = $_GET['quiz_id'];
		$quizDetails = WPCW_quizzes_getQuizDetails( $quizID, false, false, false );

		// Only do deletion if quiz details are valid.
		if ( $quizDetails ) {
			// Delete quiz questions from question map
			$wpdb->query( $wpdb->prepare( "
				DELETE FROM $wpcwdb->quiz_qs_mapping
				WHERE parent_quiz_id = %d
			", $quizDetails->quiz_id ) );

			// Delete user progress
			$wpdb->query( $wpdb->prepare( "
				DELETE FROM $wpcwdb->user_progress_quiz
				WHERE quiz_id = %d
			", $quizDetails->quiz_id ) );

			// Finally delete quiz itself
			$wpdb->query( $wpdb->prepare( "
				DELETE FROM $wpcwdb->quiz
				WHERE quiz_id = %d
			", $quizDetails->quiz_id ) );

			$page->showMessage( sprintf( __( 'The quiz \'%s\' was successfully deleted.', 'wp-courseware' ), $quizDetails->quiz_title ) );
		} // end of if $quizDetails

	} // end of check for deletion action
}
