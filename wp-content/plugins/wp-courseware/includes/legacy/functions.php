<?php
/**
 * WP Courseware Common Functions.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Update the quiz results in the database.
 *
 * Assume that the data exists when doing this update.
 *
 * @since 1.0.0
 *
 * @param object $quizResultsSoFar The updated list of results data.
 */
function WPCW_quizzes_updateQuizResults( $quizResultsSoFar ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$markinglistCount = 0;
	$markinglist      = false;

	// Relabel variables for clarity.
	$needsMarkingList = $quizResultsSoFar->quiz_needs_marking_list;
	$newQuizData      = $quizResultsSoFar->quiz_data;

	// Got items that need marking.
	if ( ! empty( $needsMarkingList ) ) {
		$markinglist      = maybe_serialize( $needsMarkingList );
		$markinglistCount = count( $needsMarkingList );
	}

	$dataToUpdate = array(
		'user_id'                 => $quizResultsSoFar->user_id,
		'unit_id'                 => $quizResultsSoFar->unit_id,
		'quiz_id'                 => $quizResultsSoFar->quiz_id,
		'quiz_needs_marking_list' => $markinglist,
		'quiz_needs_marking'      => $markinglistCount,
		'quiz_data'               => maybe_serialize( $newQuizData ),
		'quiz_grade'              => - 1,
		'quiz_attempt_id'         => $quizResultsSoFar->quiz_attempt_id,
	);

	// Update with the quiz grade
	$dataToUpdate['quiz_grade'] = WPCW_quizzes_calculateGradeForQuiz( $newQuizData, $markinglistCount );

	$SQL = arrayToSQLUpdate( $wpcwdb->user_progress_quiz, $dataToUpdate, array(
		'user_id',
		'unit_id',
		'quiz_id',
		'quiz_attempt_id'
	) );
	$wpdb->query( $SQL );
}

/**
 * Simple debug function to echo a variable to the page.
 *
 * @since 1.0.0
 *
 * @param array $showvar The variable to echo.
 * @param boolean $return If true, then return the information rather than echo it.
 *
 * @return string The HTML to render the array as debug output.
 */
function WPCW_debug_showArray( $showvar, $return = false ) {
	$html = "<pre style=\"background: #FFF; margin: 10px; padding: 10px; border: 2px solid grey; clear: both; display: block;\">";
	$html .= print_r( $showvar, true );
	$html .= "</pre>";

	if ( ! $return ) {
		echo $html;
	}

	return $html;
}

/**
 * Simple debug function to echo a variable to the debug.log
 *
 * @since 1.0.0
 *
 * @param string $message Message that you would like to pass to the debug.log file.
 */
function WPCW_debug( $message ) {
	if ( WP_DEBUG === true ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( print_r( $message, true ) );
		} else {
			error_log( $message );
		}
	}
}

/**
 * Safe method to get the value from an array using the specified key.
 *
 * @since 1.0.0
 *
 * @param array $array The array to search.
 * @param string $key The key to use to index the array.
 * @param mixed $returnDefault Return this value if the value is not found.
 *
 * @return string The array value.
 */
function WPCW_arrays_getValue( $array, $key, $returnDefault = false ) {
	if ( $array && isset( $array[ $key ] ) ) {
		return $array[ $key ];
	}

	return $returnDefault;
}

/**
 * Shuffles an array, maintaining the keys.
 *
 * @since 1.0.0
 *
 * @param array $list The array to sort.
 *
 * @return array The shuffled list.
 */
function WPCW_arrays_shuffle_assoc( $list ) {
	if ( ! is_array( $list ) ) {
		return $list;
	}

	$keys = array_keys( $list );
	shuffle( $keys );
	$random = array();

	foreach ( $keys as $key ) {
		$random[ $key ] = $list[ $key ];
	}

	return $random;
}

/**
 * Function to get all of the course details.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course for which we want to get details.
 *
 * @return \WPCW\Models\Course|bool The course object or false if it doesn't exist.
 */
function WPCW_courses_getCourseDetails( $courseID ) {
	if ( ! $courseID ) {
		return false;
	}

	$course = wpcw_get_course( absint( $courseID ) );

	return ( $course && $course->exists() ) ? $course : false;
}

/**
 * Function to get all of the module details.
 *
 * @since 1.0.0
 *
 * @param integer $moduleID The ID of the module for which we want to get details.
 *
 * @return object The details of the module as an object.
 */
function WPCW_modules_getModuleDetails( $moduleID ) {
	if ( ! $moduleID ) {
		return false;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "SELECT *
			FROM $wpcwdb->modules
			WHERE module_id = %d
			", $moduleID );

	return $wpdb->get_row( $SQL );
}

/**
 * Function to get all of the quiz details.
 *
 * @since 1.0.0
 *
 * @param integer $quizID The ID of the quiz for which we want to get details.
 * @param boolean $getQuestionsToo If true, get the questions for the quiz too.
 * @param boolean $resolveRandomQuestions If true, convert any randomised questions to live questions.
 * @param integer $userID The ID of the user to resolve the questions to.
 *
 * @return object The details of the quiz as an object.
 */
function WPCW_quizzes_getQuizDetails( $quizID, $userID, $getQuestionsToo = false, $resolveRandomQuestions = false ) {
	if ( ! $quizID ) {
		return false;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "SELECT *
			FROM $wpcwdb->quiz
			WHERE quiz_id = %d
			", $quizID );

	$quizObj = $wpdb->get_row( $SQL );

	if ( ! $quizObj ) {
		return false;
	}

	// Add flag indicating if random questions are resolved or not.
	$quizObj->resolved_random_questions = $resolveRandomQuestions;

	if ( $getQuestionsToo ) {
		// Something found, so return the questions for this quiz too.
		$quizObj->questions = WPCW_quizzes_getListOfQuestions( $quizObj->quiz_id );

		// Check if we need to expand any random questions
		if ( $resolveRandomQuestions && $userID > 0 && ! empty( $quizObj->questions ) ) {
			$questionListToRender = array();

			foreach ( $quizObj->questions as $question ) {
				switch ( $question->question_type ) {
					// Got a random selection - extract these questions
					case 'random_selection':
						$quObj    = new WPCW_quiz_RandomSelection( $question );
						$randList = $quObj->questionSelection_getLockedQuestionSelection( $userID, $quizObj->parent_unit_id );

						// Append the random questions.
						if ( ! empty( $randList ) ) {
							$questionListToRender += $randList;
						}
						break;

					// Got a standard question
					case 'multi':
					case 'open':
					case 'upload':
					case 'truefalse':
						$questionListToRender[ $question->question_id ] = $question;
						break;

					// Not expecting anything here... so not handling the error case.
					default:
						die( __( 'Unexpected question type, aborting.', 'wp-courseware' ) );
						break;
				}
			}

			// Overwrite existing questions
			$quizObj->questions = $questionListToRender;
		}
	}

	// Simple flag to see if we have open questions or not.
	$quizObj->has_open_questions = false;

	// Are we expecting any uploads? If so, set a flag to make answer processing faster.
	$quizObj->want_uploads = false;
	if ( ! empty( $quizObj->questions ) ) {
		foreach ( $quizObj->questions as $quizID => $quizItem ) {
			// We're searching for an upload anyway, so check for an open question
			if ( 'upload' == $quizItem->question_type ) {
				$quizObj->want_uploads       = true;
				$quizObj->has_open_questions = true;
				break;
			}
		}

		// Not found an open question yet even though we checked for uploads.
		if ( ! $quizObj->has_open_questions ) {
			foreach ( $quizObj->questions as $quizID => $quizItem ) {
				// Look for an open question (already checked uploads). This s
				// saves some computation time.
				if ( 'open' == $quizItem->question_type ) {
					$quizObj->has_open_questions = true;
					break;
				}
			}
		}
	}

	return $quizObj;
}

/**
 * Get the associated quiz for a unit.
 *
 * @since 1.0.0
 *
 * @param integer $unitID The ID of the unit to get the associated quiz for.
 * @param boolean $resolveRandomQuestions If true, convert any randomised questions to live questions.
 * @param integer $userID The ID of the user to resolve the questions to.
 *
 * @return object The Object of the associated quiz, or false if no quiz found.
 */
function WPCW_quizzes_getAssociatedQuizForUnit( $unitID, $userID, $resolveRandomQuestions = false ) {
	if ( ! $unitID ) {
		return false;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "
			SELECT quiz_id
			FROM $wpcwdb->quiz
			WHERE parent_unit_id = %d
			", $unitID );

	$quizObj = $wpdb->get_row( $SQL );

	if ( ! $quizObj ) {
		return false;
	}

	// Return full details for this quiz
	return WPCW_quizzes_getQuizDetails( $quizObj->quiz_id, $userID, true, $resolveRandomQuestions );
}

/**
 * Get a list of quiz post objects that match the specified parent unit ID.
 *
 * @since 1.0.0
 *
 * @param integer $unitID The ID of the unit to get the quizzes for (0 = unassigned units).
 *
 * @return array A list of quiz objects in the order that they appear.
 */
function WPCW_quizzes_getListOfQuizzes( $unitID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Current user
	$current_user = wp_get_current_user();

	// check permissions
	$SQL_AND = false;
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		$SQL_AND = $wpdb->prepare( ' AND quiz_author = %d', $current_user->ID );
	}

	$SQL = $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->quiz
		WHERE parent_unit_id = %d
		$SQL_AND
	", $unitID );

	// No list of associated IDs? Abort, and return false, as no quizzes objects.
	$rawQuizzes = $wpdb->get_results( $SQL );
	if ( ! $rawQuizzes ) {
		return false;
	}

	// Re-order post objects so that they are ID => Object details, rather than 0 => Object, 1 => Object
	$quizObjList = array();
	foreach ( $rawQuizzes as $obj ) {
		$quizObjList[ $obj->quiz_id ] = $obj;
	}

	return $quizObjList;
}

/**
 * Get a list of questions that match the specified quiz ID.
 *
 * @since 1.0.0
 *
 * @param integer $quizID The ID of the quiz to get the questions for.
 * @param boolean $getTagsToo If true, then get the list of these tags too.
 *
 * @return array A list of questions objects in the order that they appear within the quiz.
 */
function WPCW_quizzes_getListOfQuestions( $quizID, $getTagsToo = true ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "
			SELECT *
			FROM $wpcwdb->quiz_qs_mapping qqm
				LEFT JOIN $wpcwdb->quiz_qs qq ON qq.question_id = qqm.question_id
			WHERE qqm.parent_quiz_id = %d
			ORDER BY qqm.question_order ASC
		", $quizID );

	// No list of associated IDs? Abort, and return false, as no question objects.
	$rawQuestions = $wpdb->get_results( $SQL );
	if ( ! $rawQuestions ) {
		return false;
	}

	// Re-order post objects so that they are ID => Object details, rather than 0 => Object, 1 => Object
	$questionObjList = array();
	foreach ( $rawQuestions as $obj ) {
		$obj->tags = false;

		// Also grab any tags that this question has.
		if ( $getTagsToo ) {
			$obj->tags = $wpdb->get_results( $wpdb->prepare( "
				SELECT qt.*
				FROM $wpcwdb->question_tag_mapping qtm
					LEFT JOIN $wpcwdb->question_tags qt ON qtm.tag_id = qt.question_tag_id
				WHERE question_id = %d
				ORDER BY question_tag_name ASC
			", $obj->question_id ) );
		}

		$questionObjList[ $obj->question_id ] = $obj;
	}

	return $questionObjList;
}

/**
 * Converts the encoded database answers into an array of answers.
 *
 * @since 1.0.0
 *
 * @param string $rawDatabaseAnswers The raw answers that need to be decoded.
 *
 * @return array A list of the decoded answers.
 */
function WPCW_quizzes_decodeAnswers( $rawDatabaseAnswers ) {
	if ( ! $rawDatabaseAnswers ) {
		return false;
	}

	// Unserialize the array to a PHP array
	$answerData = unserialize( $rawDatabaseAnswers );

	// Decode each answer in the array
	if ( ! empty( $answerData ) ) {
		foreach ( $answerData as $idx => $innerData ) {
			// Decode to exactly the same space in the array.
			$answerData[ $idx ]['answer'] = base64_decode( $answerData[ $idx ]['answer'] );
		}

		return $answerData;
	} else {
		return array();
	}
}

/**
 * Get the name for the type of quiz being shown.
 *
 * @since 1.0.0
 *
 * @param string $quizType The type of the quiz.
 *
 * @return string The actual name of the quiz type.
 */
function WPCW_quizzes_getQuizTypeName( $quizType ) {
	switch ( $quizType ) {
		case 'survey':
			return __( 'Survey', 'wp-courseware' );
			break;

		case 'quiz_block':
			return __( 'Quiz - Blocking', 'wp-courseware' );
			break;

		case 'quiz_noblock':
			return __( 'Quiz - Non-Blocking', 'wp-courseware' );
			break;
	}

	return false;
}

/**
 * Get all of the quiz details for the specified unit and quiz ID.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user.
 * @param integer $unitID The ID of the unit.
 * @param integer $quizID The ID of the quiz.
 *
 * @return object The quiz results as an object.
 */
function WPCW_quizzes_getUserResultsForQuiz( $userID, $unitID, $quizID ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Get the latest version of the quiz results, as there may be other versions to check for.
	$SQL = $wpdb->prepare( "
    	SELECT *, UNIX_TIMESTAMP(quiz_completed_date) AS quiz_completed_date_ts
    	FROM $wpcwdb->user_progress_quiz
    	WHERE user_id = %d
    	  AND unit_id = %d
    	  AND quiz_id = %d
    	ORDER BY quiz_attempt_id DESC
    	LIMIT 1
   	", $userID, $unitID, $quizID );

	$quizObj = $wpdb->get_row( $SQL );

	// Sort out the array of quiz data.
	if ( $quizObj && $quizObj->quiz_data ) {
		$quizObj->quiz_data = maybe_unserialize( $quizObj->quiz_data );
	}

	// Unserialize the quiz marking list.
	if ( $quizObj && $quizObj->quiz_needs_marking_list ) {
		$quizObj->quiz_needs_marking_list = unserialize( $quizObj->quiz_needs_marking_list );
	}

	// Count how many attempts there were.
	if ( $quizObj ) {
		$quizObj->attempt_count = $wpdb->get_var( $wpdb->prepare( "
	    	SELECT COUNT(*) AS attempt_count
	    	FROM $wpcwdb->user_progress_quiz
	    	WHERE user_id = %d
	    	  AND unit_id = %d
	    	  AND quiz_id = %d
	   	", $userID, $unitID, $quizID ) );
	}

	return $quizObj;
}

/**
 * Calculates the grade for a set of results, taking into account the
 * different types of questions.
 *
 * @since 1.0.0
 *
 * @param array $quizData The list of quiz results data.
 * @param integer $questionsThatNeedMarking How many questions need marking.
 *
 * @return integer The overall grade for the results.
 */
function WPCW_quizzes_calculateGradeForQuiz( $quizData, $questionsThatNeedMarking = 0 ) {
	if ( $questionsThatNeedMarking > 0 ) {
		return '-1';
	}

	$questionTotal = 0;
	$gradeTotal    = 0;
	foreach ( $quizData as $questionID => $questionResults ) {
		// It's a truefalse/multi question
		if ( $questionResults['got_right'] ) {
			// Got it right, so add 100%.
			if ( $questionResults['got_right'] == 'yes' ) {
				$gradeTotal += 100;
			}
		} else { // It's a graded question.
			// Making assumption that the grade number exists
			// Otherwise we'd never get this far as the question still needs marking.
			$gradeTotal += WPCW_arrays_getValue( $questionResults, 'their_grade' );
		}

		$questionTotal ++;
	}

	// Simple calculation that averages the grade.
	$grade = 0;
	if ( $questionTotal ) {
		$grade = number_format( $gradeTotal / $questionTotal, 1 );
	}

	return $grade;
}

/**
 * Function to get all of the module details.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course for which we want to get details.
 * @param integer $moduleNumber The module number for the module in this course
 *
 * @return object The details of the module as an object.
 */
function WPCW_modules_getModuleDetails_byModuleNumber( $courseID, $moduleNumber ) {
	if ( ! $courseID || ! $moduleNumber ) {
		return false;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "SELECT *
			FROM $wpcwdb->modules
			WHERE module_number = %d
			  AND parent_course_id = %d
			", $moduleNumber, $courseID );

	return $wpdb->get_row( $SQL );
}

/**
 * Function to get a list of the courses for use in a select list.
 *
 * @since 1.0.0
 *
 * @param string $addBlank If set, use this string as the blank option at the top of the list.
 * @param integer $excludeCourseId If set, it will exclude the course id from the list.
 *
 * @return array The list of courses as an array of (courseID => Course name).
 */
function WPCW_courses_getCourseList( $addBlank = false, $excludeCourseId = false ) {
	$courses = array();

	if ( $addBlank ) {
		$courses[] = $addBlank;
	}

	$current_user_id = get_current_user_id();
	$course_args     = array(
		'status'  => 'publish',
		'number'  => - 1,
		'orderby' => 'post_title',
	);

	if ( ! user_can( $current_user_id, 'manage_wpcw_settings' ) ) {
		$course_args['course_author'] = $current_user_id;
	}

	$course_objects = wpcw()->courses->get_courses( $course_args, true );

	if ( empty( $course_objects ) ) {
		return $courses;
	}

	foreach ( $course_objects as $course_object ) {
		if ( ! empty( $course_object->course_title ) ) {
			$courses[ $course_object->course_id ] = $course_object->course_title;
		}
	}

	if ( $excludeCourseId !== false ) {
		unset( $courses[ $excludeCourseId ] );
	}

	return $courses;
}

/**
 * Get a list of the modules for a training course, in the order required for training.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course to get the modules for.
 */
function WPCW_courses_getModuleDetailsList( $courseID ) {
	$list = array();

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "SELECT *
			FROM $wpcwdb->modules
			WHERE parent_course_id = %d
			ORDER BY module_order, module_title ASC
			", $courseID );

	$items = $wpdb->get_results( $SQL );
	if ( count( $items ) < 1 ) {
		return false;
	}

	// List modules in array using module ID
	foreach ( $items as $item ) {
		$list[ $item->module_id ] = $item;
	}

	return $list;
}

/**
 * Get a list of all quizzes for a training course, in the order that they are used.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course to get the quizzes for.
 *
 * @return array A list of the quizzes in order.
 */
function WPCW_quizzes_getAllQuizzesForCourse( $courseID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	return $wpdb->get_results( $wpdb->prepare( "
    	SELECT *
    	FROM $wpcwdb->quiz q
    		LEFT JOIN $wpcwdb->units_meta um ON um.unit_id = q.parent_unit_id
    	WHERE q.parent_course_id = %d
    	  AND quiz_type != 'survey'
    	ORDER BY unit_order
   	", $courseID ) );
}

/**
 * Get the quiz results data for the specified user and list of quizzes.
 *
 * @param integer $userID The ID of the user to get the progress data for.
 * @param string $quizIDListForSQL The SQL that contains an SQL list of quiz IDs.
 *
 * @return array A list of the quiz progress for the specified user.
 */
function WPCW_quizzes_getQuizResultsForUser( $userID, $quizIDListForSQL ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "
		SELECT * FROM $wpcwdb->user_progress_quiz
		WHERE quiz_id IN $quizIDListForSQL
		  AND user_id = %d
		  AND quiz_is_latest = 'latest'
	", $userID );

	$quizResults = $wpdb->get_results( $SQL );
	$quizData    = array();

	if ( $quizResults ) {
		// Convert list into quid_id => object
		foreach ( $quizResults as $aResult ) {
			$quizData[ $aResult->quiz_id ] = $aResult;
		}
	}

	return $quizData;
}

/**
 * Function called to check if there are any ungraded quizzes left for a specific user now they've completed the unit.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user that's completed the unit.
 * @param integer $courseID The ID of the course that the user appears to have completed.
 *
 * @return boolean True if they have any ungraded quizzes, false otherwise.
 */
function WPCW_quizzes_doesUserHaveAnyUngradedQuizzes( $userID, $courseID ) {
	$quizzesForCourse = WPCW_quizzes_getAllQuizzesForCourse( $courseID );

	// No quizzes for this course.
	if ( empty( $quizzesForCourse ) ) {
		return false;
	}

	// Create a simple list of quiz IDs to use in SQL queries
	$quizIDList = array();
	foreach ( $quizzesForCourse as $singleQuiz ) {
		$quizIDList[] = $singleQuiz->quiz_id;
	}

	// Convert list of IDs into an SQL list
	$quizIDListForSQL = '(' . implode( ',', $quizIDList ) . ')';

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Find out how many quizzes in this course have questions
	// that need marking.
	$SQL = $wpdb->prepare( "
		SELECT COUNT(*) AS needs_grading
		  FROM $wpcwdb->user_progress_quiz
		WHERE quiz_id IN $quizIDListForSQL
		  AND user_id = %d
		  AND quiz_is_latest = 'latest'
		  AND quiz_needs_marking > 0
	", $userID );

	$quizzesThatNeedMarking = $wpdb->get_var( $SQL );

	return $quizzesThatNeedMarking > 0;
}

/**
 * Attempt to get the unit meta data for a single unit.
 *
 * @since 1.0.0
 *
 * @param integer $unit_id The ID of the unit to get the meta details for.
 *
 * @return object The details of the unit if found, false otherwise.
 */
function WPCW_units_getUnitMetaData( $unit_id ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// See if there's an entry in the courseware table
	$unitMeta = $wpdb->get_row( $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->units_meta
		WHERE unit_id = %d
	", $unit_id ) );

	// Manually convert the release date into a timestamp.
	if ( $unitMeta && $unitMeta->unit_drip_date ) {
		$unitMeta->unit_drip_date_ts = strtotime( $unitMeta->unit_drip_date );
	}

	return $unitMeta;
}

/**
 * Marks a unit as complete for the specified user. No error checking is made to check that the user
 * is allowed to update the record, it's assumed that the permission checking has been done before this step.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user that's completed the unit.
 * @param integer $unitID The ID of the unit that's been completed.
 */
function WPCW_units_saveUserProgress_Complete( $userID, $unitID ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	$keyColumns = array( 'user_id', 'unit_id' );

	$data                          = array();
	$data['unit_completed_status'] = 'complete';
	$data['unit_completed_date']   = current_time( 'mysql' );
	$data['user_id']               = $userID;
	$data['unit_id']               = $unitID;

	$progress = doesRecordExistAlready( $wpcwdb->user_progress, $keyColumns, array( $userID, $unitID ) );
	if ( $progress ) {
		// Has it been marked as complete? If so, we don't want to do that again to preserve the date.
		// We generally shouldn't get here, but protect anyway.
		if ( $progress->unit_completed_status == 'complete' ) {
			return false;
		}

		$SQL = arrayToSQLUpdate( $wpcwdb->user_progress, $data, $keyColumns );
	} else {
		$SQL = arrayToSQLInsert( $wpcwdb->user_progress, $data );
	}

	$wpdb->query( $SQL );
}

/**
 * Calculates the cumulative grade for a course and user.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course.
 * @param integer $userID The ID of the user.
 *
 * @return string The progress for the course/user, or n/a if there's nothing to report.
 */
function WPCW_courses_getCourseCumulativeGrade( $courseID, $userID ) {
	// Get all the quizzes for this course
	$quizIDList       = array();
	$quizIDListForSQL = false;
	$quizzesForCourse = WPCW_quizzes_getAllQuizzesForCourse( $courseID );

	// Create a simple list of IDs to use in SQL queries
	if ( $quizzesForCourse ) {
		foreach ( $quizzesForCourse as $singleQuiz ) {
			$quizIDList[ $singleQuiz->quiz_id ] = $singleQuiz;
		}

		// Convert list of IDs into an SQL list
		$quizIDListForSQL = '(' . implode( ',', array_keys( $quizIDList ) ) . ')';
	} else { // (!$quizzesForCourse) Break here if there are no quizzes for this course.
		return __( 'N/A', 'wp-courseware' );
	}

	// Get quiz results for this user
	$quizResults = WPCW_quizzes_getQuizResultsForUser( $userID, $quizIDListForSQL );

	// Track cumulative data
	$quizScoresSoFar       = 0;
	$quizScoresSoFar_count = 0;

	// Now render results for each quiz
	foreach ( $quizIDList as $aQuizID => $singleQuiz ) {
		// Got progress data, process the result
		if ( isset( $quizResults[ $aQuizID ] ) ) {
			// Extract results and unserialise the data array.
			$theResults            = $quizResults[ $aQuizID ];
			$theResults->quiz_data = maybe_unserialize( $theResults->quiz_data );

			// We've got something that needs grading.
			if ( $theResults->quiz_needs_marking == 0 && 'incomplete' != $theResults->quiz_paging_status ) {
				// Calculate score, and use for cumulative.
				$score           = $theResults->quiz_grade;
				$quizScoresSoFar += $score;
				$quizScoresSoFar_count ++;
			}
		}
	}

	// Calculate the cumulative grade
	return ( $quizScoresSoFar_count > 0 ? number_format( ( $quizScoresSoFar / $quizScoresSoFar_count ), 2 ) : __( '-', 'wp-courseware' ) );
}

/**
 * Get a list of unit post objects that match the specified module ID.
 *
 * @since 1.0.0
 *
 * @param integer $moduleID The ID of the module to get the units for (0 = unassigned units).
 *
 * @return array A list of unit objects in the order that they appear.
 */
function WPCW_units_getListOfUnits( $moduleID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Current user
	$current_user = wp_get_current_user();

	$SQL_USER_CONDITION = false;
	if ( is_admin() && ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		$SQL_USER_CONDITION = $wpdb->prepare( ' AND unit_author = %d', $current_user->ID );
	}

	$SQL = $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->units_meta
		WHERE parent_module_id = %d
		$SQL_USER_CONDITION
		ORDER BY unit_order ASC, unit_id ASC
	", $moduleID );

	// No list of associated IDs? Abort, and return false, as no units or unit objects.
	$rawUnits = $wpdb->get_results( $SQL );
	if ( ! $rawUnits ) {
		return false;
	}

	// Turn list into ID => meta list
	$unitIDList = array();
	foreach ( $rawUnits as $rawUnit ) {
		$unitIDList[ $rawUnit->unit_id ] = $rawUnit;
	}

	// Get list of IDs, and use this for WordPress query to get the full objects
	$uniPostObjsArgs = array(
		'post_type' => 'course_unit',                // Just course units
		'number'    => - 1,                            // No limit, i.e. all
		'orderby'   => 'none',                        //
		'include'   => array_keys( $unitIDList )        // List of posts to get.
	);

	// Check permissions
	if ( is_admin() && ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		$uniPostObjsArgs['post_author'] = $current_user->ID;
	}

	// Get Units
	$unitPostObjsRaw = get_posts( $uniPostObjsArgs );

	if ( ! $unitPostObjsRaw ) {
		return false;
	}

	// Re-order post objects so that they are ID => Object details, rather than 0 => Object, 1 => Object
	$unitPostObjs = array();
	foreach ( $unitPostObjsRaw as $obj ) {
		// Add our metadata
		$obj->unit_meta = $unitIDList[ $obj->ID ];

		$unitPostObjs[ $obj->ID ] = $obj;
	}

	// Use unit ordering from table to return actual ordering list.
	$unitDataRet = array();
	foreach ( $unitIDList as $unitID => $unitObj ) {
		if ( isset( $unitPostObjs[ $unitID ] ) ) {
			$unitDataRet[ $unitID ] = $unitPostObjs[ $unitID ];
		}
	}

	return $unitDataRet;
}

/**
 * Get all of the associated parent data for the specified course unit.
 *
 * @since 1.0.0
 *
 * @param integer $post_id The ID of the course unit
 *
 * @return object The details of the parent objects, or false if there is no parent.
 */
function WPCW_units_getAssociatedParentData( $post_id ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->units_meta um
		LEFT JOIN $wpcwdb->modules m ON m.module_id = um.parent_module_id
		LEFT JOIN $wpcwdb->courses c ON c.course_id = m.parent_course_id
		WHERE um.unit_id = %d AND course_title IS NOT NULL
	", $post_id );

	return $wpdb->get_row( $SQL );
}

/**
 * Convert a percentage to a percentage bar.
 *
 * @param integer $percent The number to show in the progress bar.
 * @param integer $title The optional title of the course.
 *
 * @return integer The HTML to render the percentage bar.
 */
function WPCW_stats_convertPercentageToBar( $percent, $title = false ) {
	if ( $title ) {
		$title = sprintf( __( '<span class="wpcw_progress_bar_title">%s</span>', 'wp-courseware' ), $title );
	}

	return WPCW_content_progressBar( $percent, false, $title );
}

/**
 * Check if a user can access the specified training course.
 *
 * @param integer $courseID The ID of the course to check.
 * @param integer $userID The ID of the user to check.
 *
 * @return boolean True if the user can access this course, false otherwise.
 */
function WPCW_courses_canUserAccessCourse( $courseID, $userID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$SQL = $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->user_courses
		WHERE user_id = %d AND course_id = %d
	", $userID, $courseID );

	return apply_filters( 'wpcw_courses_canuseraccesscourse', ( $wpdb->get_row( $SQL ) != false ), $courseID, $userID );
}

/**
 * Get the enrollment date for a user from the courses database.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user to get the details for.
 * @param integer $courseID The ID of the course to get the enrollment date for.
 *
 * @return integer The course enrolement date as a timestamp, or false if the user wasn't found.
 */
function WPCW_users_getCourseEnrolmentDate( $userID, $courseID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$thedate = $wpdb->get_var( $wpdb->prepare( "
		SELECT course_enrolment_date
		FROM $wpcwdb->user_courses
		WHERE user_id = %d AND course_id = %d
	", $userID, $courseID ) );

	// Convert into a timestamp with no timezone conversion.
	if ( $thedate ) {
		return strtotime( $thedate );
	}

	return false;
}

/**
 * Get the enrollment actual date for a user from the courses database.
 *
 * @since 4.8.0
 *
 * @param integer $userID The ID of the user to get the details for.
 * @param integer $courseID The ID of the course to get the enrollment date for.
 *
 * @return integer The course enrollment actual date, or false if the user wasn't found.
 */
function WPCW_users_getCourseEnrolmentActualDate( $userID, $courseID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$thedate = $wpdb->get_var( $wpdb->prepare( "
		SELECT course_enrolment_date
		FROM $wpcwdb->user_courses
		WHERE user_id = %d AND course_id = %d
	", $userID, $courseID ) );

	// Convert into a timestamp with no timezone conversion.
	if ( $thedate ) {
		return $thedate;
	}

	return false;
}

/**
 * Get the course prerequisites.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course to get the enrollment date for.
 *
 * @return integer The course enrolement date as a timestamp, or false if the user wasn't found.
 */
function WPCW_users_getCoursePrerequisites( $courseID = 0 ) {
	if ( $courseID == 0 ) {
		return;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	$prerequisites = $wpdb->get_var( $wpdb->prepare( "
		SELECT course_opt_prerequisites
		FROM $wpcwdb->courses
		WHERE course_id = %d
	", $courseID ) );

	if ( $prerequisites ) {
		return maybe_unserialize( $prerequisites );
	}

	return false;
}

/**
 * Get the access date for a unit based on the enrollment date and the details of the unit meta.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user to get the details for.
 * @param object $unitMeta An object with the meta data for this unit.
 *
 * @return integer The date that this unit should unlock if it's drip-fed.
 */
function WPCW_users_getUnitUnlockDate_forUser( $userID, $unitMeta ) {
	if ( ! $unitMeta ) {
		return false;
	}

	// Calculate the future date based on the type.
	switch ( $unitMeta->unit_drip_type ) {
		case 'drip_specific':
			return $unitMeta->unit_drip_date_ts;
			break;

		case 'drip_interval':
			// Fetch the enrolement date for this user
			$enrolmentDate = WPCW_users_getCourseEnrolmentDate( $userID, $unitMeta->parent_course_id );

			// Add the interval type, which is stored in seconds.
			return $unitMeta->unit_drip_interval + $enrolmentDate;
			break;

		// If unknown, just return the date the user registered.
		default:
			return $enrolmentDate;
			break;
	}

	return false;
}

/**
 * Update the user progress count based on units completed.
 *
 * @since 1.0.0
 *
 * @param integer $courseID ID of course.
 * @param integer $userID ID of user.
 * @param integer $totalUnitCount The total number of units for this course.
 */
function WPCW_users_updateUserUnitProgress( $courseID, $userID, $totalUnitCount ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Get number of completed units
	$completed = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
    	 FROM $wpcwdb->user_progress up
      	 LEFT JOIN $wpcwdb->units_meta um ON up.unit_id = um.unit_id
    	 WHERE user_id = %d
    	 AND parent_course_id = %d
    	 AND unit_completed_status = 'complete'",
		$userID,
		$courseID
	) );

	// Calculate progress as a percentage
	$progress = 0;
	if ( $totalUnitCount > 0 ) {
		$progress = floor( ( $completed / $totalUnitCount ) * 100 );
	}

	// Update database with the completed progress
	$wpdb->query( $wpdb->prepare(
		"UPDATE $wpcwdb->user_courses
		 SET course_progress = %d
    	 WHERE user_id = %d
    	 AND course_id = %d",
		$progress,
		$userID,
		$courseID
	) );
}

/**
 * Course Details Updated.
 *
 * This will update the metrics associated with the course such as the total number of units.
 *
 * @since 1.0.0
 *
 * @param Array $courseDetails The course details that have just been updated.
 */
function WPCW_actions_courses_courseDetailsUpdated( $courseDetails ) {
	if ( ! $courseDetails ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Get a total count of units in this course
	$SQL = $wpdb->prepare(
		"SELECT COUNT(*)
    	 FROM $wpcwdb->units_meta
    	 WHERE parent_course_id = %d",
		$courseDetails->course_id
	);

	$totalUnitCount = $wpdb->get_var( $SQL );

	// Update database with actual count
	$wpdb->query( $wpdb->prepare(
		"UPDATE $wpcwdb->courses
    	 SET course_unit_count = %d
    	 WHERE course_id = %d",
		$totalUnitCount,
		$courseDetails->course_id
	) );

	// User progress counts will now be out of sync too, particularly with new or deleted units.
	$SQL = $wpdb->prepare( "SELECT * FROM $wpcwdb->user_courses WHERE course_id = %d", $courseDetails->course_id );

	// Get Users.
	$users = $wpdb->get_results( $SQL );

	// Update User Unit Progress.
	if ( $users ) {
		foreach ( $users as $userCourseDetails ) {
			WPCW_users_updateUserUnitProgress( $userCourseDetails->course_id, $userCourseDetails->user_id, $totalUnitCount );
		}
	}
}

/**
 * Handle a modified module.
 *
 * Used to ensure that all modules have a valid module number.
 *
 * @since 1.0.0
 *
 * @param integer $courseID The ID of the course that looks after the module that's been created or edited.
 */
function WPCW_actions_modules_modulesModified( $courseID ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	$modules = $wpdb->get_results( $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->modules
		WHERE parent_course_id = %d
		ORDER BY module_order ASC
	", $courseID ) );

	// Nothing to do
	if ( ! $modules ) {
		return;
	}

	$moduleOrderCount = 0;
	foreach ( $modules as $module ) {
		$moduleOrderCount ++;

		// Update module list with new ordering
		$SQL = $wpdb->prepare( "
			UPDATE $wpcwdb->modules
			   SET module_order = %d, module_number = %d
			WHERE module_id = %d
		", $moduleOrderCount, $moduleOrderCount, $module->module_id );

		$wpdb->query( $SQL );
	}
}

/**
 * Function called after a module has been created with the modify module form.
 *
 * @since 1.0.0
 *
 * @param array $formValues The processed form values.
 * @param array $originalFormValues The raw form values.
 * @param EasyForm $thisObject The reference to the form object doing the saving.
 */
function WPCW_actions_modules_afterModuleSaved_formHook( $formValues, $originalFormValues, $thisObject ) {
	// Modules have been modified. Call action to update module numbers.
	do_action( 'wpcw_modules_modified', $formValues['parent_course_id'] );

	// Run only if modifying module
	if ( isset( $_GET['module_id'] ) ) {
		global $wpdb, $wpcwdb;

		// Check units associated with this module in order to update parent course ID
		$unitList = WPCW_units_getListOfUnits( $formValues['module_id'] );

		if ( $unitList && count( $unitList ) > 0 ) {
			// Get max unit order value
			$unit_count = $wpdb->get_var( $wpdb->prepare( "
				SELECT max(unit_order)
				FROM $wpcwdb->units_meta
				WHERE parent_course_id = %d
				", $formValues['parent_course_id'] ) );

			// Need to adjust each unit's metadata with in the module
			foreach ( $unitList as $unitID => $unitData ) {
				$unitMeta = WPCW_units_getUnitMetaData( $unitID );

				// Check if parent course ID is different
				if ( $unitMeta->parent_course_id != $formValues['parent_course_id'] ) {
					$unit_count += 10;

					// Set unit meta data for new course ID and unit order
					$SQL = $wpdb->prepare( "
						UPDATE $wpcwdb->units_meta
						SET parent_course_id = %d, unit_order = %d
						WHERE unit_id = %d
					", $formValues['parent_course_id'], $unit_count,
						$unitID );

					$wpdb->query( $SQL );

					// Check quizzes associated with this unit in order to update parent course ID
					$quizData = WPCW_quizzes_getListOfQuizzes( $unitID );
					if ( $quizData ) {
						foreach ( $quizData as $quizID => $quizObj ) {
							$SQL = $wpdb->prepare( "
							UPDATE $wpcwdb->quiz
						   	SET parent_unit_id = %d, parent_course_id = %d
							WHERE quiz_id = %d
							", $unitID, $formValues['parent_course_id'], $quizID );

							$wpdb->query( $SQL );
						}
					}
				}
			}
		}
	}
}

/**
 * Function called when the user completes a unit.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user that's completed the unit.
 * @param integer $unitID The ID of the unit that's been completed.
 * @param object $unitParentData The object of parent data associated with the unit, such as module and course.
 */
function WPCW_actions_users_unitCompleted( $userID, $unitID, $unitParentData ) {
	if ( ! $userID || ! $unitID || ! $unitParentData ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Update the user progress count.
	WPCW_users_updateUserUnitProgress( $unitParentData->course_id, $userID, $unitParentData->course_unit_count );

	// Work out if module/course completed.
	$userProgress = new WPCW_UserProgress( $unitParentData->course_id, $userID );

	// Cache result of course completion
	$hasCourseCompleted = $userProgress->isCourseCompleted();

	// Module is complete, but course isn't yet complete
	if ( $userProgress->isModuleCompleted( $unitID ) && ! $hasCourseCompleted ) {
		do_action( 'wpcw_user_completed_module', $userID, $unitID, $unitParentData );
	}

	// Course has also completed
	if ( $hasCourseCompleted ) {
		do_action( 'wpcw_user_completed_course', $userID, $unitID, $unitParentData );
	}

	// Get User Details.
	$userDetails = get_userdata( $userID );

	// Admin Subject.
	$adminSubject = __( "Unit Complete Notification - {USER_NAME} - {UNIT_TITLE}", 'wp-courseware' );

	// Admin Body.
	$adminBody = __( "Hi Admin!

Just to let you know, {USER_NAME} has just completed '{UNIT_TITLE}'.

{SITE_NAME}
{SITE_URL}", 'wp-courseware' );

	// Admin wants an email notification, and email address exists. Assumption is that it's valid.
	if ( $unitParentData->email_complete_unit_option_admin === 'send_email' && $unitParentData->course_to_email ) {
		WPCW_email_sendEmail( $unitParentData, $userDetails, $unitParentData->course_to_email, $adminSubject, $adminBody );
	}

	// Check if admin wants user to have an email.
	if ( $unitParentData->email_complete_unit_option === 'send_email' ) {
		$adminSubject = $unitParentData->email_complete_unit_subject;
		$adminBody    = $unitParentData->email_complete_unit_body;
		WPCW_email_sendEmail( $unitParentData, $userDetails, $userDetails->user_email, $unitParentData->email_complete_unit_subject, $unitParentData->email_complete_unit_body );
	}

	do_action( 'wpcw_user_completed_unit_notification', $unitParentData, $userDetails, $adminSubject, $adminBody );
}

/**
 * Function called when the user completes a module.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user that's completed the unit.
 * @param integer $unitID The ID of the unit that's just been completed.
 * @param object $unitParentData The object of parent data associated with the unit, such as module and course.
 */
function WPCW_actions_users_moduleCompleted( $userID, $unitID, $unitParentData ) {
	if ( ! $userID || ! $unitID || ! $unitParentData ) {
		return;
	}

	$userDetails = get_userdata( $userID );

	$adminSubject = __( "Module Complete Notification - {USER_NAME} - Module {MODULE_NUMBER}", 'wp-courseware' );
	$adminBody    = __( "Hi Trainer!

Just to let you know, {USER_NAME} has just completed 'Module {MODULE_NUMBER} - {MODULE_TITLE}'.

{SITE_NAME}
{SITE_URL}
", 'wp-courseware' );

	// Admin wants an email notification, and email address exists. Assumption is that it's valid.
	if ( $unitParentData->email_complete_module_option_admin == 'send_email' && $unitParentData->course_to_email ) {
		WPCW_email_sendEmail( $unitParentData,
			$userDetails, // User who's done the completion
			$unitParentData->course_to_email,
			$adminSubject,
			$adminBody
		);
	}

	// Check if admin wants user to have an email.
	if ( $unitParentData->email_complete_module_option == 'send_email' ) {
		$adminSubject = $unitParentData->email_complete_module_subject;
		$adminBody    = $unitParentData->email_complete_module_body;
		WPCW_email_sendEmail( $unitParentData,
			$userDetails, // User who's done the completion
			$userDetails->user_email,
			$unitParentData->email_complete_module_subject, // Use subject template in the settings
			$unitParentData->email_complete_module_body // Use body template in the settings
		);
	}

	// Any additional admin-level notifications?
	do_action( "wpcw_user_completed_module_notification", $unitParentData, $userDetails, $adminSubject, $adminBody );
}

/**
 * Function called when the user completes a module.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user that's completed the unit.
 * @param integer $unitID The ID of the unit that's just been completed.
 * @param object $unitParentData The object of parent data associated with the unit, such as module and course.
 */
function WPCW_actions_users_courseCompleted( $userID, $unitID, $unitParentData ) {
	if ( ! $userID || ! $unitID || ! $unitParentData ) {
		return;
	}

	// Certificates have been requested, so generate one for this user and course.
	if ( $unitParentData->course_opt_use_certificate == 'use_certs' ) {
		// Add a certificate entry to the database for the user.
		WPCW_certificate_generateCertificateEntry( $userID, $unitParentData->course_id );
	}

	$userHasUngradedQuestions = WPCW_quizzes_doesUserHaveAnyUngradedQuizzes( $userID, $unitParentData->course_id );

	$userDetails = get_userdata( $userID );

	$adminSubject = __( "Course Complete Notification - {USER_NAME} - '{COURSE_TITLE}'", 'wp-courseware' );
	$adminBody    = __( "Hi Trainer!

Just to let you know, {USER_NAME} has just completed the '{COURSE_TITLE}' course.

{SITE_NAME}
{SITE_URL}
", 'wp-courseware' );

	// Admin wants an email notification, and email address exists. Assumption is that it's valid.
	if ( $unitParentData->email_complete_course_option_admin == 'send_email' && $unitParentData->course_to_email && ! $userHasUngradedQuestions ) {
		WPCW_email_sendEmail( $unitParentData,
			$userDetails,
			$unitParentData->course_to_email,
			$adminSubject, $adminBody
		);
	}

	// Check if admin wants user to have an email.
	if ( $unitParentData->email_complete_course_option == 'send_email' && ! $userHasUngradedQuestions ) {
		do_action( 'wpcw_user_completed_module', $userID, $unitID, $unitParentData );
		WPCW_email_sendEmail( $unitParentData,
			$userDetails,                                    // User who's done the completion
			$userDetails->user_email,
			$unitParentData->email_complete_course_subject, // Use subject template in the settings
			$unitParentData->email_complete_course_body        // Use body template in the settings
		);
	}

	// Any additional admin-level notifications?
	do_action( "wpcw_user_completed_course_notification", $unitParentData, $userDetails, $adminSubject, $adminBody );
}

/**
 * The function called when a user receives a grade for a quiz, either when marked manually, or
 * when the question it automatically graded.
 *
 * Triggered by wpcw_quiz_graded.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user to notify.
 * @param object $quizDetails The details of the quiz.
 * @param integer $grade The grade that they've been given
 * @param string $additionalDetail Any additional data to add to the message.
 */
function WPCW_actions_userQuizGraded_notifyUser( $userID, $quizDetails, $grade, $additionalDetail ) {
	if ( ! $userID || ! $quizDetails ) {
		return;
	}

	// Need parent details to determine if we can send an email or not.
	$unitParentData = WPCW_units_getAssociatedParentData( $quizDetails->parent_unit_id );

	// Check if admin wants user to have an email.
	if ( $unitParentData->email_quiz_grade_option == 'send_email' ) {
		// User details - for sending the email.
		$userDetails = get_userdata( $userID );

		// Need post object to create the notification.
		$post = get_post( $quizDetails->parent_unit_id );
		if ( ! $post ) {
			return;
		}

		// Set current user before calling WPCW_UnitFrontend class
		wp_set_current_user( $userID );

		// Initalise the unit details. Check we have access.
		$fe = new WPCW_UnitFrontend( $post );
		if ( ! $fe->check_unit_doesUnitHaveParentData() || ! $fe->check_user_canUserAccessCourse() ) {
			return;
		}

		// Do email body first
		$emailBody    = $unitParentData->email_quiz_grade_body;
		$tagList_Body = WPCW_email_getTagList( $emailBody );
		$emailBody    = WPCW_email_replaceTags_quizData( $fe, $tagList_Body, $emailBody, $additionalDetail );

		// Then do subject line
		$emailSubject    = $unitParentData->email_quiz_grade_subject;
		$tagList_Subject = WPCW_email_getTagList( $emailSubject );
		$emailSubject    = WPCW_email_replaceTags_quizData( $fe, $tagList_Subject, $emailSubject, $additionalDetail );

		// Now send email
		WPCW_email_sendEmail( $unitParentData,
			$userDetails, // User who's done the completion
			$userDetails->user_email,
			$emailSubject,
			$emailBody
		);
	}
}

/**
 * Function called when a user quiz needs grading by the admin.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user who's quiz needs grading
 * @param object $quizDetails The details of the quiz that needs grading.
 */
function WPCW_actions_userQuizNeedsGrading_notifyAdmin( $userID, $quizDetails ) {
	if ( ! $userID || ! $quizDetails ) {
		return;
	}

	$adminSubject = __( "Quiz Needs Grading Notification - {USER_NAME} - '{COURSE_TITLE}'", 'wp-courseware' );
	$adminBody    = __( "Hi Trainer!

Just to let you know, {USER_NAME} has just completed a quiz ({QUIZ_TITLE}), which requires grading. You can grade the question here:
{QUIZ_GRADE_URL}

{SITE_NAME}
{SITE_URL}
", 'wp-courseware' );

	// Generate the quiz name and URL to mark the quiz before passing for email to be sent.
	$quizGradeURL = sprintf( '%s&user_id=%d&quiz_id=%d&unit_id=%d',
		admin_url( 'admin.php?page=WPCW_showPage_UserProgess_quizAnswers' ),
		$userID, $quizDetails->quiz_id, $quizDetails->parent_unit_id
	);

	$adminBody = str_ireplace( '{QUIZ_TITLE}', $quizDetails->quiz_title, $adminBody );
	$adminBody = str_ireplace( '{QUIZ_GRADE_URL}', $quizGradeURL, $adminBody );

	// User and Unit details
	$userDetails    = get_userdata( $userID );
	$unitParentData = WPCW_units_getAssociatedParentData( $quizDetails->parent_unit_id );

	// Check admin email address exists before sending.
	if ( $unitParentData->course_to_email ) {
		WPCW_email_sendEmail( $unitParentData,
			$userDetails,                                    // User who's done the completion
			$unitParentData->course_to_email,
			$adminSubject, $adminBody );
	}

	// Any additional admin-level notifications?
	do_action( "wpcw_user_quiz_needs_marking_notification", $unitParentData, $userDetails, $adminSubject, $adminBody );
}

/**
 * Function called when a user is blocked on a quiz due to running out of attempts.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user who has run out of attempts.
 * @param object $quizDetails The details of the quiz that needs grading.
 */
function WPCW_actions_userQuizUserNeedsUnblocking_notifyAdmin( $userID, $quizDetails ) {
	if ( ! $userID || ! $quizDetails ) {
		return;
	}

	$adminSubject = __( "User Needs Unblocking Notification - {USER_NAME} - '{COURSE_TITLE}'", 'wp-courseware' );
	$adminBody    = __( "Hi Trainer!

Just to let you know, {USER_NAME} has just completed a quiz ({QUIZ_TITLE}), but they have failed and run out of attempts. You can unblock their progress here:
{QUIZ_GRADE_URL}

{SITE_NAME}
{SITE_URL}
", 'wp-courseware' );

	// Generate the quiz name and URL to mark the quiz before passing for email to be sent.
	$quizGradeURL = sprintf( '%s&user_id=%d&quiz_id=%d&unit_id=%d',
		admin_url( 'admin.php?page=WPCW_showPage_UserProgess_quizAnswers' ),
		$userID, $quizDetails->quiz_id, $quizDetails->parent_unit_id
	);

	$adminBody = str_ireplace( '{QUIZ_TITLE}', $quizDetails->quiz_title, $adminBody );
	$adminBody = str_ireplace( '{QUIZ_GRADE_URL}', $quizGradeURL, $adminBody );

	// User and Unit details
	$userDetails    = get_userdata( $userID );
	$unitParentData = WPCW_units_getAssociatedParentData( $quizDetails->parent_unit_id );

	// Check admin email address exists before sending.
	if ( $unitParentData->course_to_email ) {
		WPCW_email_sendEmail( $unitParentData,
			$userDetails, // User who's done the completion
			$unitParentData->course_to_email,
			$adminSubject, $adminBody );
	}

	// Any additional admin-level notifications?
	do_action( "wpcw_user_quiz_user_needs_blocking_notification", $unitParentData, $userDetails, $adminSubject, $adminBody );
}

/**
 * Send an email out using a template.
 *
 * @since 1.0.0
 *
 * @param object $unitParentData The parent data for a unit.
 * @param object $userDetails The details of the user who's done the completing.
 * @param string $targetEmail The email address of the recipient.
 * @param string $subjectTemplate The content of the subject template, before substitutions.
 * @param string $bodyTemplate The content of the email body template, before substitutions.
 */
function WPCW_email_sendEmail( $unitParentData, $userDetails, $targetEmail, $subjectTemplate, $bodyTemplate ) {
	// Replace content in email body first
	$tagList_Body = WPCW_email_getTagList( $bodyTemplate );
	$bodyTemplate = WPCW_email_replaceTags_generic( $unitParentData, $userDetails, $tagList_Body, $bodyTemplate );

	// Then do subject line
	$tagList_Subject = WPCW_email_getTagList( $subjectTemplate );
	$subjectTemplate = WPCW_email_replaceTags_generic( $unitParentData, $userDetails, $tagList_Subject, $subjectTemplate );

	// Encode.
	$bodyTemplate    = html_entity_decode( $bodyTemplate, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$subjectTemplate = html_entity_decode( $subjectTemplate, ENT_QUOTES, get_bloginfo( 'charset' ) );

	// Html.
	$headers = "Content-Type: text/html; charset=UTF-8\r\n" ;

	// Construct the from part of the email
	if ( $unitParentData->course_from_email ) {
		$headers .= "From: {$unitParentData->course_from_name} <{$unitParentData->course_from_email}>\r\n";
	}

	// Autop.
	$bodyTemplate = wpautop( wp_kses_post( wptexturize( $bodyTemplate ) ) );

	// Actually send the email
	if ( ! wp_mail( $targetEmail, $subjectTemplate, $bodyTemplate, $headers ) ) {
		error_log( 'WPCW_email_sendEmail() - email did not send.' );
	}
}

/**
 * Used to uppercase each item in the array.
 *
 * @since 1.0.0
 */
function WPCW_email_getTagList_upper( &$item, $key ) {
	$item = strtoupper( $item );
}

/**
 * Given a template, return a list of all of the tags being used in the body.
 *
 * @since 1.0.0
 *
 * @param string $templateBody The template body to check for tags.
 *
 * @return array A list if template tags being used in the template.
 */
function WPCW_email_getTagList( $templateBody ) {
	preg_match_all( "%{[A-Za-z_]+}%", $templateBody, $matches );

	// This returns Array ( [0] => Array() ) by default.
	if ( ! empty( $matches ) ) {
		// Ensure all items are uppercase.
		array_walk( $matches[0], 'WPCW_email_getTagList_upper' );

		return $matches[0];
	}

	return array();
}

/**
 * Given a frontend object with user progress data, replace the tags with quiz-related information
 * based on the tags that have been found in the email.
 *
 * @since 1.0.0
 *
 * @param WPCW_UnitFrontend $feObj The frontend object with details.
 * @param array $tagList The list of tags found in the template.
 * @param string $emailData The data to replace the strings with.
 * @param string $additionalData Any additional data triggered from the trainer.
 *
 * @return The modified email data ready for sending.
 */
function WPCW_email_replaceTags_quizData( $feObj, $tagList, $emailData, $additionalData = false ) {
	if ( ! $feObj || empty( $tagList ) ) {
		return $emailData;
	}

	$quizDetails  = $feObj->fetch_getUnitQuizDetails();
	$progressData = $feObj->fetch_getQuizProgressDetails();

	// Replace each tag for quiz-related data.
	foreach ( $tagList as $tagToReplace ) {
		switch ( $tagToReplace ) {
			case '{QUIZ_TITLE}':
				$emailData = str_replace( '{QUIZ_TITLE}', $quizDetails->quiz_title, $emailData );
				break;

			case '{QUIZ_GRADE}':
				$emailData = str_replace( '{QUIZ_GRADE}', $progressData->quiz_grade . '%', $emailData );
				break;

			case '{QUIZ_ATTEMPTS}':
				$emailData = str_replace( '{QUIZ_ATTEMPTS}', $progressData->attempt_count, $emailData );
				break;

			case '{QUIZ_TIME}':
				$timeToShare = __( '-', 'wp-courseware' );

				if ( $progressData->quiz_completion_time_seconds > 0 ) {
					$timeToShare = WPCW_time_convertSecondsToHumanLabel( $progressData->quiz_completion_time_seconds );
				}

				$emailData = str_replace( '{QUIZ_TIME}', $timeToShare, $emailData );
				break;

			case '{QUIZ_RESULT_DETAIL}':
				$emailData = str_replace( '{QUIZ_RESULT_DETAIL}', $additionalData, $emailData );
				break;

			case '{QUIZ_GRADES_BY_TAG}':
				// Use existing frontend code to get the list of messages relating to tags.
				$msgList    = $feObj->fetch_quizzes_questionResultsByTag();
				$msgSummary = false;

				if ( ! empty( $msgList ) ) {
					foreach ( $msgList as $tagDetails ) {
						// Got open questions
						if ( $tagDetails['question_open_count'] > 0 ) {
							$msgSummary .= sprintf( "%s: %s\n", $tagDetails['tag_details']->question_tag_name,
								sprintf( __( 'Your grade is %d%%', 'wp-courseware' ), $tagDetails['score_total'] )
							);
						} else {
							$msgSummary .= sprintf( "%s: %s\n", $tagDetails['tag_details']->question_tag_name,
								sprintf( __( '%d out of %d correct (%d%%)', 'wp-courseware' ),
									$tagDetails['score_correct_questions'],
									$tagDetails['question_count'],
									$tagDetails['score_total'] )
							);
						}
					}
				}

				$emailData = str_replace( '{QUIZ_GRADES_BY_TAG}', $msgSummary, $emailData );
				break;

			case '{CUSTOM_FEEDBACK}':
				// Use existing frontend code to get the list of custom feedback messages.
				$customFeedback = false;
				$msgList        = $feObj->fetch_customFeedbackMessage_calculateMessages();

				if ( ! empty( $msgList ) ) {
					$customFeedback = apply_filters( 'wpcw_email_feedback_separator_top', "\n\n------\n\n" );
					foreach ( $msgList as $singleMsg ) {
						// Separate each custom feedback message slightly
						$customFeedback .= $singleMsg . apply_filters( 'wpcw_email_feedback_separator', "\n\n------\n\n" );
					}
				}

				$emailData = str_replace( '{CUSTOM_FEEDBACK}', $customFeedback, $emailData );
				break;
		}
	}

	return $emailData;
}

/**
 * Replace all of the email tags with the actual details.
 *
 * @since 1.0.0
 *
 * @param object $unitParentData The parent data for a unit.
 * @param object $userDetails The details of the user who's done the completing.
 * @param array $tagList The list of tags found in the template.
 * @param string $emailData The data to replace the strings with.
 *
 * @return The modified email data ready for sending.
 */
function WPCW_email_replaceTags_generic( $unitParentData, $userDetails, $tagList, $emailData ) {
	if ( empty( $tagList ) ) {
		return $emailData;
	}

	// Replace each tag for quiz-related data.
	foreach ( $tagList as $tagToReplace ) {
		switch ( $tagToReplace ) {
			case '{USER_NAME}':
				$emailData = str_replace( '{USER_NAME}', $userDetails->display_name, $emailData );
				break;

			case '{FIRST_NAME}':
				if ( ! isset( $userDetails->first_name ) ) {
					$userDetails = get_userdata( $userDetails->user_id );
				}
				$emailData = str_replace( '{FIRST_NAME}', $userDetails->first_name, $emailData );
				break;

			case '{LAST_NAME}':
				if ( ! isset( $userDetails->first_name ) ) {
					$userDetails = get_userdata( $userDetails->user_id );
				}
				$emailData = str_replace( '{LAST_NAME}', $userDetails->last_name, $emailData );
				break;

			case '{SITE_NAME}':
				$emailData = str_replace( '{SITE_NAME}', get_bloginfo( 'name' ), $emailData );
				break;

			case '{SITE_URL}':
				$emailData = str_replace( '{SITE_URL}', site_url(), $emailData );
				break;

			case '{COURSE_TITLE}':
				$emailData = str_replace( '{COURSE_TITLE}', $unitParentData->course_title, $emailData );
				break;

			case '{MODULE_TITLE}':
				$moduleTitle = false;

				if ( isset( $unitParentData->module_title ) ) {
					$moduleTitle = $unitParentData->module_title;
				}

				$emailData = str_replace( '{MODULE_TITLE}', $moduleTitle, $emailData );
				break;

			case '{MODULE_NUMBER}':
				$moduleNumber = false;

				if ( isset( $unitParentData->module_number ) ) {
					$moduleNumber = $unitParentData->module_number;
				}

				$emailData = str_replace( '{MODULE_NUMBER}', $moduleNumber, $emailData );
				break;

			case '{UNIT_TITLE}':
				$emailData = str_replace( '{UNIT_TITLE}', get_the_title( $unitParentData->unit_id ), $emailData );
				break;

			case '{UNIT_URL}':
				$emailData = str_replace( '{UNIT_URL}', get_permalink( $unitParentData->unit_id ), $emailData );
				break;

			case '{CERTIFICATE_LINK}':
				// Certificates - generate a link if enabled.
				$certificateLink = false;
				if ( 'use_certs' == $unitParentData->course_opt_use_certificate ) {
					$certificateDetails = WPCW_certificate_getCertificateDetails( $userDetails->ID, $unitParentData->course_id, false );
					if ( $certificateDetails ) {
						$certificateLink = WPCW_certificate_generateLink( $certificateDetails->cert_access_key );
					}
				}

				$emailData = str_ireplace( '{CERTIFICATE_LINK}', $certificateLink, $emailData );
				break;
		}
	}

	return apply_filters( 'wpcw_email_data', $emailData );
}

/**
 * Action called when a user is deleted in WordPress. Remove all progress
 * and certificate details.
 *
 * @since 1.0.0
 *
 * @param integer $user_id The ID of the user that's just been deleted.
 */
function WPCW_actions_users_userDeleted( $user_id ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Course progress summary for user needs to be removed.
	$wpdb->query( $SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->user_courses
				WHERE user_id = %d
			", $user_id ) );

	// User's progress
	$wpdb->query( $SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->user_progress
				WHERE user_id = %d
			", $user_id ) );

	// User's quiz answers
	$wpdb->query( $SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->user_progress_quiz
				WHERE user_id = %d
			", $user_id ) );

	// User's question locks
	$wpdb->query( $SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->question_rand_lock
				WHERE question_user_id = %d
			", $user_id ) );

	// User's queue entries
	$wpdb->query( $SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->queue_dripfeed
				WHERE queue_user_id = %d
			", $user_id ) );

	// User's certificates
	$wpdb->query( $SQL = $wpdb->prepare( "
				DELETE FROM $wpcwdb->certificates
				WHERE cert_user_id = %d
			", $user_id ) );
}

/**
 * Function called when registration form loads up.
 *
 * Looks for course_id parameter and adds hidden fields into the registration form.
 *
 * @since 1.0.0
 */
function WPCW_course_enrollment_via_shortcode() {
	if ( isset( $_GET['_wp_enroll'] ) && wp_verify_nonce( $_GET['_wp_enroll'], 'wpcw_enroll' ) ) {
		$courses = array();
		if ( isset( $_GET['course_id'] ) ) {
			foreach ( $_GET['course_id'] as $key => $value ) {
				echo '<input type="hidden" name="course_id[' . $key . ']" id="course_ID" value="' . $value . '" />';
			}
		}
		if ( isset( $_GET['unit_redirect'] ) && $_GET['unit_redirect'] ) {
			echo '<input type="hidden" name="course_unit_redirect" id="course_ID_redirect" value="true" />';
		}
	}
}

/**
 * Action called when a new user is created in WordPress. Used to check if we need to
 * automatically add access for this user to access a training course.
 *
 * @since 1.0.0
 *
 * @param integer $user_id The ID of the user that's just been added.
 */
function WPCW_actions_users_newUserCreated( $user_id ) {
	// Checks for course ID on post which will enroll student into specified course.
	$courses = array();

	if ( isset( $_POST['course_id'] ) ) {
		foreach ( $_POST['course_id'] as $key => $value ) {
			$courses[] = $value;
		}
	} else {
		/**
		 * Filter: Extensions Ignore New User.
		 *
		 * See if an extension is taking over the checking
		 * of access control. If a function is defined to
		 * return true, then this section of code is ignored.
		 *
		 * @since 4.3.0
		 *
		 * @param bool True to ignore user. Default is false
		 *
		 * @return bool The boolean value to ignore a new user.
		 */
		$ignoreOnNewUser = apply_filters( 'wpcw_extensions_ignore_new_user', false );
		if ( $ignoreOnNewUser ) {
			return;
		}
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Get a list of all courses that want users added automatically.
	$automatic_courses = $wpdb->get_col( "SELECT * FROM $wpcwdb->courses WHERE course_opt_user_access = 'default_show'" );
	if ( $automatic_courses ) {
		foreach ( $automatic_courses as $automatic_course ) {
			/**
			 * Filter: Disable Course Automatic Enrollment.
			 *
			 * @since 4.3.0
			 *
			 * @param bool True to disabled, false to enable. Default is false.
			 * @param int $automatic_course The automatic course id.
			 *
			 * @return bool The boolean value to disable automatic enrollment for a certain course.
			 */
			if ( apply_filters( 'wpcw_course_automatic_enrollment_disable', false, $automatic_course ) ) {
				continue;
			}

			$courses[] = $automatic_course;
		}
	}

	// None found
	if ( ! $courses || count( $courses ) < 1 ) {
		return;
	}

	// Add access for this user to all courses we're associated with.
	WPCW_courses_syncUserAccess( $user_id, $courses, 'sync' );

	// Check for redirect.
	if ( isset( $_POST['course_unit_redirect'] ) && $_POST['course_unit_redirect'] ) {
		add_filter( 'wpcw_registration_redirect', function ( $redirect ) use ( $courses ) {
			$course_to_redirect = $courses[0];

			if ( ( $course = wpcw_get_course( $course_to_redirect ) ) ) {
				if ( ( $units = $course->get_units( array( 'number' => 1 ) ) ) ) {
					/** @var \WPCW\Models\Unit $unit */
					$unit = $units[0];
					if ( $unit instanceof \WPCW\Models\Unit ) {
						$redirect = $unit->get_view_url();
					}
				}
			}

			return $redirect;
		} );
	}
}

/**
 * Function to add the specified list of course IDs for the specified user.
 *
 * @since 1.0.0
 *
 * @param integer $user_id The ID of the user to update.
 * @param mixed $courseIDs The ID or array of IDs of the course IDs to give the user access to.
 * @param boolean $syncMode If 'sync', then remove access to any course IDs not mentioned in $courseIDs parameter. If 'add', then just add access for course IDs that have been specified.
 * @param array $enrolmentDates An optional list of course IDs => Timestamps of enrollment dates to add to the database.
 */
function WPCW_courses_syncUserAccess( $user_id, $courseIDs, $syncMode = 'add', $enrolmentDates = false, $courseAuthorID = false, $force = false ) {
	if ( ! get_userdata( $user_id ) ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// List of course IDs that actually exist.
	$courseList         = array();
	$statuses_to_enroll = array( 'publish', 'private' );

	// Got a list of IDs?
	if ( is_array( $courseIDs ) ) {
		// List is empty, save a query
		if ( count( $courseIDs ) > 0 ) {
			// Yep, this course actually exists
			foreach ( $courseIDs as $potentialCourseID ) {
				if ( $courseDetails = WPCW_courses_getCourseDetails( $potentialCourseID ) ) {
					if ( ! $courseDetails->is_closed() && in_array( $courseDetails->get_course_status(), $statuses_to_enroll ) || $force ) {
						$courseList[ $potentialCourseID ] = $courseDetails;

						// Handle Course Bundles.
						if ( $course_bundles = $courseDetails->get_course_bundles() ) {
							foreach ( $course_bundles as $course_bundle_id ) {
								if ( $course_bundle_details = WPCW_courses_getCourseDetails( $course_bundle_id ) ) {
									if ( in_array( $course_bundle_details->get_course_status(), $statuses_to_enroll ) ) {
										$courseList[ $course_bundle_id ] = $course_bundle_details;
									}
								}
							}
						}
					}
				}
			}
		}
	} else {
		// Got a single ID..., so add to list of courses to process having
		// checked ID belongs to a proper course.
		if ( $courseDetails = WPCW_courses_getCourseDetails( $courseIDs ) ) {
			if ( ! $courseDetails->is_closed() && in_array( $courseDetails->get_course_status(), $statuses_to_enroll ) || $force ) {
				$courseList[ $courseIDs ] = $courseDetails;

				// Handle Course Bundles.
				if ( $course_bundles = $courseDetails->get_course_bundles() ) {
					foreach ( $course_bundles as $course_bundle_id ) {
						if ( $course_bundle_details = WPCW_courses_getCourseDetails( $course_bundle_id ) ) {
							if ( in_array( $course_bundle_details->get_course_status(), $statuses_to_enroll ) ) {
								$courseList[ $course_bundle_id ] = $course_bundle_details;
							}
						}
					}
				}
			}
		}
	}

	// Check if we want to remove access for courses that are not mentioned.
	// We'll add any they should have access to in a mo.
	if ( $syncMode == 'sync' ) {
		$str_courseIDs     = false;
		$courseIDCount     = count( array_keys( $courseList ) );
		$courseIDsToRemove = false;

		// Actually got some IDs to remove, so create an SQL string with all IDs
		if ( $courseIDCount > 0 ) {
			// List Course Ids
			$list_courseIDs = array_keys( $courseList );

			// If a teacher, we need to get all the courses authored by others as to not remove them.
			if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
				if ( ! $courseAuthorID ) {
					$current_user   = wp_get_current_user();
					$courseAuthorID = $current_user->ID;
				}

				$courseIdsFromOtherAuthors = $wpdb->get_col( "
					SELECT uc.course_id
					FROM $wpcwdb->user_courses uc
					LEFT JOIN $wpcwdb->courses c ON c.course_id = uc.course_id
					WHERE c.course_author != $courseAuthorID"
				);

				if ( ! empty( $courseIdsFromOtherAuthors ) ) {
					$list_courseIDs = array_merge( $courseIdsFromOtherAuthors, $list_courseIDs );
				}
			}

			// Make the list_courseIDs comma separated
			$str_courseIDs = implode( ",", $list_courseIDs );

			// Get the courses to delete.
			$list_courseIDsToDelete = $wpdb->get_col( "SELECT course_id FROM $wpcwdb->courses WHERE course_id NOT IN ($str_courseIDs)", 0 );

			if ( ! empty( $list_courseIDsToDelete ) ) {
				// Get array of courses to be removed
				$courseIDsToRemove = $wpdb->get_col( $wpdb->prepare( "SELECT course_id FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id NOT IN ($str_courseIDs)", $user_id ), 0 );

				// Remove meta for this user all previous courses.
				// Previous version deleteted all courses then re-created them. As a result, data was being lost about email being sent.
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id NOT IN ($str_courseIDs)", $user_id ) );

				// Remove any associated notifications from the queue for just these courses
				WPCW_queue_dripfeed::updateQueueItems_removeUser_fromCourseList( $user_id, $list_courseIDsToDelete );
			}
		} else { // Got no IDs - user is being removed from all courses.

			// If a teacher, we need to get all the courses authored by others as to not remove them.
			if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
				if ( ! $courseAuthorID ) {
					$current_user   = wp_get_current_user();
					$courseAuthorID = $current_user->ID;
				}

				$courseIdsFromOtherAuthors = $wpdb->get_col( "
					SELECT uc.course_id
					FROM $wpcwdb->user_courses uc
					LEFT JOIN $wpcwdb->courses c ON c.course_id = uc.course_id
					WHERE c.course_author != $courseAuthorID"
				);

				// Make the list_courseIDs comma separated
				$str_courseIDs = implode( ",", $courseIdsFromOtherAuthors );

				if ( ! $str_courseIDs ) {
					$str_courseIDs = "-1";
				}

				// Get list of all courses for removal
				$courseIDsToRemove = $wpdb->get_col( $wpdb->prepare( "SELECT course_id FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id NOT IN ($str_courseIDs)", $user_id ), 0 );

				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id NOT IN ($str_courseIDs)", $user_id ) );

				// Remove any associated notifications from the queue for all courses
				WPCW_queue_dripfeed::updateQueueItems_removeUser_fromAllCourses( $user_id );
			} else {
				// Get list of all courses for removal
				$courseIDsToRemove = $wpdb->get_col( $wpdb->prepare( "SELECT course_id FROM $wpcwdb->user_courses WHERE user_id = %d", $user_id ), 0 );

				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->user_courses WHERE user_id = %d", $user_id ) );

				// Remove any associated notifications from the queue for all courses
				WPCW_queue_dripfeed::updateQueueItems_removeUser_fromAllCourses( $user_id );
			}
		}

		do_action( 'wpcw_unenroll_user', $user_id, $courseIDsToRemove );
	}

	// Only process valid course IDs
	if ( count( $courseList ) > 0 ) {
		$courses_enrolled = array();

		foreach ( $courseList as $validCourseID => $courseDetails ) {
			$existingCourseDetails = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpcwdb->user_courses WHERE user_id = %d AND course_id = %d", $user_id, $validCourseID ) );

			// See if this is already in the database.
			if ( $existingCourseDetails ) {
				// Got an enrollment date for this course ID? Convert it to MySQL date and update the course. Don't both trying to
				// update the enrollment date if we're not specifically providing one.
				if ( isset( $enrolmentDates[ $validCourseID ] ) ) {
					$dateForEnrolment = date( 'Y-m-d H:i:s', $enrolmentDates[ $validCourseID ] );

					$wpdb->query( $wpdb->prepare(
						"UPDATE $wpcwdb->user_courses
					     SET course_enrolment_date = %s
					     WHERE user_id = %d AND course_id = %d",
						$dateForEnrolment,
						$user_id,
						$validCourseID
					) );
				}
			} else { // Course already exists.
				$dateForEnrolment = current_time( 'mysql' );

				// Got an enrollment date for this course ID? Convert it to MySQL date and use it when adding this course.
				if ( isset( $enrolmentDates[ $validCourseID ] ) ) {
					$dateForEnrolment = date( 'Y-m-d H:i:s', $enrolmentDates[ $validCourseID ] );
				}

				// Actually add reference in database as it doesn't exist.
				$wpdb->query( $wpdb->prepare( "INSERT INTO $wpcwdb->user_courses (user_id, course_id, course_progress, course_enrolment_date) VALUES(%d, %d, 0, %s)",
					$user_id, $validCourseID,
					$dateForEnrolment
				) );

				// Generate array of courses user is enrolled in
				$courses_enrolled[] = $validCourseID;
			}

			// Get a total count of units in this course
			$SQL = $wpdb->prepare( "
		    	SELECT COUNT(*)
		    	FROM $wpcwdb->units_meta
		    	WHERE parent_course_id = %d
		    ", $validCourseID );

			$totalUnitCount = $wpdb->get_var( $SQL );

			// Calculate the user's progress, in case they've still got completed progress
			// in the database.
			WPCW_users_updateUserUnitProgress( $validCourseID, $user_id, $totalUnitCount );

			// Update the notifications, just in case the enrollment dates have changed for each course.
			WPCW_queue_dripfeed::updateQueueItems_updateNotifications_forCourse( $user_id, $validCourseID );
		}

		/**
		 * Action: Enroll User.
		 *
		 * @since 4.3.0
		 *
		 * @param int $user_id The user id.
		 * @param array $courses_enrolled The courses enrolled.
		 */
		do_action( 'wpcw_enroll_user', $user_id, $courses_enrolled );

		// Transform variables for next action.
		$student_id = $user_id;
		$course_ids = $courses_enrolled;

		/**
		 * Action: Enroll Student.
		 *
		 * @since 4.3.0
		 *
		 * @param int $student_id The student id.
		 * @param array $course_ids The course ids.
		 */
		do_action( 'wpcw_enroll_student', $student_id, $course_ids );
	}
}

/**
 * Fetch a list of courses for the specified user.
 *
 * @since 1.0.0
 *
 * @param integer $user_id The ID of the user to get the course list for.
 *
 * @return array The list of courses for this user (or false if there are none).
 */
function WPCW_users_getUserCourseList( $user_id ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	return $wpdb->get_results( $wpdb->prepare( "
    	SELECT *
    	FROM {$wpcwdb->user_courses} uc
		LEFT JOIN {$wpcwdb->courses} c ON c.course_id = uc.course_id
		LEFT JOIN {$wpdb->posts} ucp ON c.course_post_id = ucp.ID
   		WHERE user_id = %d
   		ORDER BY course_title ASC
    ", $user_id ) );
}

/**
 * Fetch a list of courses for the specified user - Admin.
 *
 * @since 1.0.0
 *
 * @param integer $user_id The ID of the user to get the course list for.
 *
 * @return array The list of courses for this user (or false if there are none).
 */
function WPCW_users_getUserCourseListAdmin( $user_id ) {
	// Global
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Vars
	$current_user = wp_get_current_user();

	// SQL course author id condition
	$SQL_COURSE_AUTHOR = false;
	if ( ! user_can( $current_user, 'manage_wpcw_settings' ) ) {
		$SQL_COURSE_AUTHOR = $wpdb->prepare( ' AND course_author = %d', $current_user->ID );
	}

	$courseData = $wpdb->get_results( $wpdb->prepare( "
    	SELECT *
    	FROM $wpcwdb->user_courses uc
    	LEFT JOIN  $wpcwdb->courses c ON c.course_id = uc.course_id
   		WHERE user_id = %d
   		$SQL_COURSE_AUTHOR
   		ORDER BY course_title ASC
    ", $user_id ) );

	return $courseData;
}

/**
 * Updates the database to generate a certificate entry. If a certificate already exists for the user/course ID,
 * then no new entry is created.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user that the certificate is being generated for.
 * @param integer $courseID The ID of the associated course.
 */
function WPCW_certificate_generateCertificateEntry( $userID, $courseID ) {
	if ( ! $userID || ! $courseID ) {
		return;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// Already have a record for this certificate.
	if ( $certificateDetails = doesRecordExistAlready( $wpcwdb->certificates, array(
		'cert_user_id',
		'cert_course_id'
	), array( $userID, $courseID ) ) ) {
		return $certificateDetails;
	}

	// Create anonymous entry to allow users to access a certificate when they've completed a course.  Means that certificates
	// stay existing even if units are added to a course.
	$data                    = array();
	$data['cert_user_id']    = $userID;
	$data['cert_course_id']  = $courseID;
	$data['cert_generated']  = current_time( 'mysql' );
	$data['cert_access_key'] = md5( serialize( $data ) ); // Unique key based on data we've just added

	$SQL = arrayToSQLInsert( $wpcwdb->certificates, $data );
	$wpdb->query( $SQL );

	// Return details of the added certificate
	return getRecordDetails( $wpcwdb->certificates, array( 'cert_user_id', 'cert_course_id' ), array(
		$userID,
		$courseID
	) );
}

/**
 * Get the certificate details for a user, or false if not found.
 *
 * @since 1.0.0
 *
 * @param integer $userID The ID of the user to check.
 * @param integer $courseID The ID of the associated course.
 * @param boolean $tryToCreate If true, try to create the certificate if details don't exist.
 *
 * @return object The certificate details if they were found, or false if not found.
 */
function WPCW_certificate_getCertificateDetails( $userID, $courseID, $tryToCreate = true ) {
	global $wpcwdb;
	$certificateDetails = getRecordDetails( $wpcwdb->certificates, array(
		'cert_user_id',
		'cert_course_id'
	), array( $userID, $courseID ) );

	if ( $tryToCreate && ! $certificateDetails ) {
		return WPCW_certificate_generateCertificateEntry( $userID, $courseID );
	}

	return $certificateDetails;
}

/**
 * Get the certificate details for a user, or false if not found.
 *
 * @since 1.0.0
 *
 * @param string $accessID The unique access key for the certificate.
 *
 * @return object The certificate details if they were found, or false if not found.
 */
function WPCW_certificate_getCertificateDetails_byAccessKey( $accessKey ) {
	// Validate for a MD5 hash
	if ( ! preg_match( '/^[A-Za-z0-9]{32}$/', $accessKey ) ) {
		return false;
	}

	global $wpcwdb;

	return getRecordDetails( $wpcwdb->certificates, 'cert_access_key', $accessKey );
}

/**
 * Gets the user's name if set up, or their username otherwise.
 *
 * @since 1.0.0
 *
 * @param object $userDetails The user details as an object.
 *
 * @return string The user's name.
 */
function WPCW_users_getUsersName( $userDetails ) {
	if ( ! $userDetails ) {
		return false;
	}

	// Generate the name from the user's first and last name. If they don't exist
	// then use the display name as a default.
	$name = $userDetails->user_firstname . ' ' . $userDetails->user_lastname;

	if ( ! trim( $name ) ) {
		$name = $userDetails->data->display_name;
	}

	return $name;
}

/**
 * Return a URL to download a certificate.
 *
 * @since 1.0.0
 *
 * @param string $accessKey The access key for the certificate.
 *
 * @return string The full URL to the certificate.
 */
function WPCW_certificate_generateLink( $accessKey ) {
	return apply_filters( 'wpcw_certificate_generated_url', add_query_arg( array(
		'page'        => 'wpcw_pdf_create_certificate',
		'certificate' => $accessKey
	), esc_url( home_url( '/' ) ) ) );
}

/**
 * Converts a list of raw file extensions into a list of permitted file extensions.
 *
 * @since 1.0.0
 *
 * @param string $rawInput The list of raw file extensions.
 *
 * @return array The list of file extensions.
 */
function WPCW_files_cleanFileExtensionList( $rawInput ) {
	$list = array();

	// Turn comma list into array of items
	$rawList = explode( ',', $rawInput );
	if ( ! empty( $rawList ) ) {
		// Check each item
		foreach ( $rawList as $ext ) {
			$ext = strtolower( $ext );
			$ext = preg_replace( '/[^a-z0-9]/', '', $ext ); // Remove anything other than numbers and letters.

			// Got anything left? Add it to the list.
			if ( $ext ) {
				$list[] = $ext;
			}
		}
	}

	return $list;
}

/**
 * Returns the maximally uploadable file size in megabytes.
 *
 * @since 1.0.0
 *
 * @return string
 */
function WPCW_files_getMaxUploadSize() {
	$max_upload   = (int) ( ini_get( 'upload_max_filesize' ) );
	$max_post     = (int) ( ini_get( 'post_max_size' ) );
	$memory_limit = (int) ( ini_get( 'memory_limit' ) );

	return min( $max_upload, $max_post, $memory_limit ) . __( 'MB', 'wp-courseware' );
}

/**
 * Create a directory that can be used to store the uploaded files in for the user completing a specific quiz.
 *
 * @since 1.0.0
 *
 * @param object $quizDetails The details of the quiz being completed.
 * @param integer $userID The ID of the user completing the quiz.
 * @param boolean $createItToo If true, then create the new directory.
 *
 * @return array The full server path to the newly created upload directory and URL version.
 */
function WPCW_files_getFileUploadDirectory_forUser( $quizDetails, $userID, $createItToo = true ) {
	// Create path based on the quiz ID, user ID, and date.
	$keyString = sprintf( '%d_%d_%s', $quizDetails->quiz_id, $userID, date( 'Ymd_His' ) );
	$pathName  = $keyString . '_' . md5( 'user_upload_directory' . $keyString );

	// Generate the full file path
	$fullPath = WP_CONTENT_DIR . '/wpcourseware_uploads/' . $pathName . '/';

	if ( $createItToo && ! file_exists( $fullPath ) ) {
		@mkdir( $fullPath, 0777, true );
	}

	// Create an empty index page to stop directory listings.
	if ( file_exists( $fullPath ) ) {
		touch( $fullPath . 'index.php' );
	}

	// Need URL and directory versions
	return array(
		'dir_path'  => $fullPath,
		'path_only' => '/wpcourseware_uploads/' . $pathName . '/',
	);
}

/**
 * Generates the upload directory with an empty index.php file to prevent directory listings.
 *
 * @since 1.0.0
 */
function WPCW_files_getFileSize_human( $fileName ) {
	$fileSizeBytes = filesize( WP_CONTENT_DIR . $fileName );
	if ( $fileSizeBytes === false ) {
		return __( 'Not found.', 'wp-courseware' );
	}

	return WPCW_files_formatBytes( $fileSizeBytes, 0 );
}

/**
 * Format a size into the right KB, MB, etc
 *
 * @since 1.0.0
 *
 * @param integer $size The size in bytes.
 * @param integer $precision The number of decimal places.
 *
 * @return string The file size as a string.
 */
function WPCW_files_formatBytes( $size, $precision = 2 ) {
	$base     = log( $size ) / log( 1024 );
	$suffixes = array( '', 'KB', 'MB', 'GB', 'TB' );

	return round( pow( 1024, $base - floor( $base ) ), $precision ) . $suffixes[ floor( $base ) ];
}

/**
 * Creates a progress bar.
 *
 * @since 1.0.0
 *
 * @param integer $percentage The percentage completion so far.
 * @param string $cssClass The optional string to use for the CSS class for the progress bar.
 * @param string $extraHTML Any extra HTML to show for the progress bar line.
 *
 * @return string The HTML for the progress bar.
 */
function WPCW_content_progressBar( $percentage, $cssClass = false, $extraHTML = false ) {
	return sprintf( '
		<span class="wpcw_progress_wrap %s">
			<span class="wpcw_progress">
				<span class="wpcw_progress_bar" style="width: %d%%"></span>
			</span>
			<span class="wpcw_progress_percent">%d%%</span>
			%s
		</span>',
		$cssClass,
		$percentage,
		$percentage,
		$extraHTML
	);
}

/**
 * Given the ID of a tag, fetch the full details.
 *
 * @since 1.0.0
 *
 * @param integer $tagID The ID of the tag to get.
 *
 * @return object The details of the tag to return.
 */
function WPCW_questions_tags_getTagDetails( $tagID ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	return $wpdb->get_row( $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->question_tags
		WHERE question_tag_id = %d
	", $tagID ) );
}

/**
 * Given the name of a tag, fetch the full details.
 *
 * @since 1.0.0
 *
 * @param string $tagName The name of the tag to get.
 *
 * @return object The details of the tag to return.
 */
function WPCW_questions_tags_getTagDetails_byName( $tagName ) {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	return $wpdb->get_row( $wpdb->prepare( "
		SELECT *
		FROM $wpcwdb->question_tags
		WHERE question_tag_name = %s
	", $tagName ) );
}

/**
 * Calculate how many questions there are.
 *
 * @since 1.0.0
 *
 * @return integer The total number of questions.
 */
function WPCW_questions_getQuestionCount() {
	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	return $wpdb->get_var( "
		SELECT COUNT(*) as q_count
		FROM $wpcwdb->quiz_qs
		WHERE question_type != 'random_selection'
	" );
}

/**
 * Get a list of tags for the specified question ID.
 *
 * @since 1.0.0
 *
 * @param integer $questionID The ID of the question to get tags for.
 *
 * @return array The list of tags for this question.
 */
function WPCW_questions_tags_getTagsForQuestion( $questionID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	return $wpdb->get_results( $wpdb->prepare( "
		SELECT qt.*
		FROM $wpcwdb->question_tag_mapping qtm
			LEFT JOIN $wpcwdb->question_tags qt ON qtm.tag_id = qt.question_tag_id
		WHERE question_id = %d
		  AND question_tag_name IS NOT NULL
		ORDER BY question_tag_name ASC
	", $questionID ) );
}

/**
 * Get a list of tags for the specified list of question IDs.
 *
 * @since 1.0.0
 *
 * @param array $questionList The list of question IDs to get tags for.
 *
 * @return array The list of tags for this question.
 */
function WPCW_questions_tags_getTagsForQuestionList( $questionList ) {
	if ( empty( $questionList ) ) {
		return false;
	}

	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Generate the right number of %d in the SQL, removing final comma.
	$numberOfIDs = count( $questionList );
	$sqlForIDs   = rtrim( str_repeat( '%d,', $numberOfIDs ), ',' );

	return $wpdb->get_results( $wpdb->prepare( "
		SELECT qtm.question_id, qt.*
		FROM $wpcwdb->question_tag_mapping qtm
			LEFT JOIN $wpcwdb->question_tags qt ON qtm.tag_id = qt.question_tag_id
		WHERE question_id IN ($sqlForIDs)
		  AND question_tag_name IS NOT NULL
		ORDER BY question_id ASC
	", $questionList ) );
}

/**
 * Convert a time in minutes to a human label.
 *
 * @since 1.0.0
 *
 * @param integer $minutes The time in minutes.
 *
 * @return string The time in hours, minutes and seconds.
 */
function WPCW_time_convertMinutesToHumanLabel( $minutes ) {
	return WPCW_time_convertSecondsToHumanLabel( $minutes * 60 );
}

/**
 * Convert a time in seconds to a human label.
 *
 * @since 1.0.0
 *
 * @param integer $seconds The time in seconds.
 *
 * @return string The time in hours, minutes and seconds.
 */
function WPCW_time_convertSecondsToHumanLabel( $seconds ) {
	$lbl_seconds = 0;
	$lbl_minutes = 0;
	$lbl_hours   = 0;

	// Calculate everything and break it down.
	if ( $seconds > 0 ) {
		$lbl_hours   = floor( $seconds / 3600 );
		$lbl_minutes = ( $seconds / 60 ) % 60;
		$lbl_seconds = $seconds % 60;
	}

	// Start of with minutes and seconds - always use these.
	$labelToReturn = sprintf( '%d %s %d %s',
		$lbl_minutes, __( 'mins', 'wp-courseware' ),
		$lbl_seconds, __( 'secs', 'wp-courseware' )
	);

	// If we have hours too, then prepend.
	if ( $lbl_hours > 0 ) {
		$labelToReturn = sprintf( '%d %s ', $lbl_hours, __( 'hrs', 'wp-courseware' ) ) . $labelToReturn;
	}

	return $labelToReturn;
}

/**
 * Get all of the custom feedback messages for a specific quiz.
 *
 * @since 1.0.0
 *
 * @param integer $quizID The ID of the quiz that's being shown.
 * @param array $tagIDList A list of tag IDs to filter the messages for.
 *
 * @return array The list of feedback messages for this quiz.
 */
function WPCW_quizzes_feedback_getFeedbackMessagesForQuiz( $quizID, $tagIDList = null ) {
	if ( ! is_null( $tagIDList ) && empty( $tagIDList ) ) {
		return false;
	}

	global $wpdb, $wpcwdb;
	$wpdb->show_errors();

	// If we have a list of tag IDs, then add to SQL.
	$extraSQL = false;
	if ( ! empty( $tagIDList ) ) {
		$extraSQL = 'AND qfeedback_tag_id IN (' . implode( ",", $tagIDList ) . ')';
	}

	$SQL = $wpdb->prepare( "
    	SELECT *
    	FROM $wpcwdb->quiz_feedback
    	WHERE qfeedback_quiz_id = %d
    	$extraSQL
    	ORDER BY qfeedback_tag_id ASC
   	", $quizID );

	return $wpdb->get_results( $SQL );
}

/**
 * Get Email Template Message
 *
 * @since 4.0.3
 *
 * @param string $template_name The name of the template.
 *
 * @return string $template_text The template text.
 */
function wpcw_get_email_template_text( $template_name ) {
	/* Template text holder */
	$template_text = '';

	/* Quick check */
	if ( ! $template_name ) {
		return $template_text;
	}

	/* Get the template text by template name */
	switch ( $template_name ) {
		case 'EMAIL_TEMPLATE_COMPLETE_MODULE_SUBJECT':
			$template_text = __( 'Module {MODULE_TITLE} - Complete', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COMPLETE_MODULE_BODY' :
			$template_text = __( 'Hi {USER_NAME}

Great work for completing the "{MODULE_TITLE}" module!

{SITE_NAME}
{SITE_URL}', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COMPLETE_COURSE_SUBJECT' :
			$template_text = __( 'Course {COURSE_TITLE} - Complete', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COMPLETE_COURSE_BODY' :
			$template_text = __( 'Hi {USER_NAME}

Great work for completing the "{COURSE_TITLE}" training course! Fantastic!

{SITE_NAME}
{SITE_URL}', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_QUIZ_GRADE_SUBJECT' :
			$template_text = __( '{COURSE_TITLE} - Your Quiz Grade - For "{QUIZ_TITLE}"', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_QUIZ_GRADE_BODY' :
			$template_text = __( 'Hi {USER_NAME}

Your grade for the "{QUIZ_TITLE}" quiz is:
{QUIZ_GRADE}

This was for the quiz at the end of this unit:
{UNIT_URL}

{QUIZ_RESULT_DETAIL}

{SITE_NAME}
{SITE_URL}', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_SUBJECT' :
			$template_text = __( 'Your final grade summary for "{COURSE_TITLE}"', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_BODY' :
			$template_text = __( 'Hi {USER_NAME}

Congratulations on completing the "{COURSE_TITLE}" training course! Fantastic!

Your final grade is: {CUMULATIVE_GRADE}

Here is a summary of your quiz results:
{QUIZ_SUMMARY}

You can download your certificate here:
{CERTIFICATE_LINK}

I hope you enjoyed the course!

{SITE_NAME}
{SITE_URL}', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_UNIT_UNLOCKED_SUBJECT' :
			$template_text = __( 'Your next unit ({UNIT_TITLE}) is now available!', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_UNIT_UNLOCKED_BODY' :
			$template_text = __( 'Hi {USER_NAME}

Good news! You can now access the next unit!

The unit called "{UNIT_TITLE}" from "{COURSE_TITLE}" is now available for you to access.

To access the unit, just click on this link:
{UNIT_URL}

Thanks!

{SITE_NAME}
{SITE_URL}', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COMPLETE_UNIT_SUBJECT' :
			$template_text = __( 'Unit {UNIT_TITLE} - Complete', 'wp-courseware' );
			break;
		case 'EMAIL_TEMPLATE_COMPLETE_UNIT_BODY' :
			$template_text = __( 'Hi {USER_NAME}

Great work for completing the "{UNIT_TITLE}" in the "{COURSE_TITLE}" training course! Fantastic!

{SITE_NAME}
{SITE_URL}', 'wp-courseware' );
			break;
		default :
			break;
	}

	return $template_text;
}

/**
 * Function to upgrade the database tables.
 *
 * @since 1.0.0
 *
 * @param integer $installedVersion The version that exists prior to the upgrade.
 * @param boolean $forceUpgrade If true, we force an upgrade.
 * @param boolean $showErrors If true, show any debug errors.
 */
function WPCW_database_upgradeTables( $installedVersion, $forceUpgrade, $showErrors = false ) {
	// globals
	global $wpdb, $wpcwdb;

	// Show errors if debugging
	if ( $showErrors ) {
		$wpdb->show_errors();
	}

	// Always upgrade tables. Conditionally execute any other table changes.
	$upgradeNow = true;

	// Courses Table
	$SQL = "CREATE TABLE $wpcwdb->courses (
			  course_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			  course_title varchar(150) NOT NULL,
			  course_desc text NULL,
			  course_author bigint(20) unsigned NOT NULL default '0',
			  course_opt_completion_wall varchar(20) NOT NULL,
			  course_opt_use_certificate varchar(20) NOT NULL DEFAULT 'no_certs',
			  course_opt_user_access varchar(20) NOT NULL,
			  course_unit_count int(11) unsigned NULL DEFAULT '0',
			  course_from_name varchar(150) NOT NULL,
			  course_from_email varchar(150) NOT NULL,
			  course_to_email varchar(150) NOT NULL,
			  course_opt_prerequisites longtext NOT NULL,
			  course_message_unit_complete text NULL,
			  course_message_course_complete text NULL,
			  course_message_unit_not_logged_in text NULL,
			  course_message_unit_pending text NULL,
			  course_message_unit_no_access text NULL,
			  course_message_prerequisite_not_met text NULL,
			  course_message_unit_not_yet text NULL,
			  course_message_unit_not_yet_dripfeed text NULL,
			  course_message_quiz_open_grading_blocking text NULL,
			  course_message_quiz_open_grading_non_blocking text NULL,
			  email_complete_module_option_admin varchar(20) NOT NULL,
			  email_complete_module_option varchar(20) NOT NULL,
			  email_complete_module_subject varchar(300) NOT NULL,
			  email_complete_module_body text NULL,
			  email_complete_course_option_admin varchar(20) NOT NULL,
			  email_complete_course_option varchar(20) NOT NULL,
			  email_complete_course_subject varchar(300) NOT NULL,
			  email_complete_course_body text NULL,
			  email_quiz_grade_option varchar(20) NOT NULL,
			  email_quiz_grade_subject varchar(300) NOT NULL,
			  email_quiz_grade_body text NULL,
			  email_complete_course_grade_summary_subject varchar(300) NOT NULL,
			  email_complete_course_grade_summary_body text NULL,
			  email_unit_unlocked_subject varchar(300) NOT NULL,
			  email_unit_unlocked_body text NULL,
			  cert_signature_type varchar(20) NOT NULL DEFAULT 'text',
			  cert_sig_text varchar(300) NOT NULL,
			  cert_sig_image_url varchar(300) NOT NULL DEFAULT  '',
			  cert_logo_enabled varchar(20) NOT NULL DEFAULT 'no_cert_logo',
			  cert_logo_url varchar(300) NOT NULL DEFAULT '',
			  cert_background_type varchar(20) NOT NULL DEFAULT 'use_default',
			  cert_background_custom_url varchar(300) NOT NULL DEFAULT  '',
			  course_access_expiration varchar(20) NOT NULL DEFAULT 'none',
			  cae_specific_date datetime DEFAULT '0000-00-00 00:00:00',
			  cae_interval_date_int_expire int(11) NOT NULL DEFAULT '1',
			  cae_interval_date_string_expire varchar(20) NOT NULL DEFAULT 'hour',
			  cae_reset_on_course_expire varchar(20) NOT NULL DEFAULT 'no',
			  certificate_template int(11) NOT NULL DEFAULT '0',
			  PRIMARY KEY  (course_id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8";
	WPCW_database_installTable( $wpcwdb->courses, $SQL, $upgradeNow );

	// Modules Table
	$SQL = "CREATE TABLE $wpcwdb->modules (
			  module_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			  parent_course_id int(11) unsigned NOT NULL DEFAULT '0',
			  module_author bigint(20) unsigned NOT NULL default '0',
			  module_title varchar(150) NOT NULL,
			  module_desc text NULL,
			  module_order int(11) unsigned NOT NULL DEFAULT '10000',
			  module_number int(11) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY  (module_id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->modules, $SQL, $upgradeNow );

	// Units Meta Table
	$SQL = "CREATE TABLE $wpcwdb->units_meta (
			  unit_id int(11) unsigned NOT NULL,
			  parent_module_id int(11) unsigned NOT NULL DEFAULT '0',
			  parent_course_id int(11) unsigned NOT NULL DEFAULT '0',
			  unit_author bigint(20) unsigned NOT NULL default '0',
			  unit_order int(11) unsigned NOT NULL DEFAULT '0',
			  unit_number int(11) unsigned NOT NULL DEFAULT '0',
			  unit_drip_type varchar(50) NOT NULL DEFAULT '',
			  unit_drip_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  unit_drip_interval int(11) NOT NULL DEFAULT '432000',
			  unit_drip_interval_type varchar(15) NOT NULL DEFAULT 'interval_days',
			  PRIMARY KEY  (unit_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->units_meta, $SQL, $upgradeNow );

	// User Courses Allocations Table
	$SQL = "CREATE TABLE $wpcwdb->user_courses (
			  user_id int(11) unsigned NOT NULL,
			  course_id int(11) unsigned NOT NULL,
			  course_progress int(11) NOT NULL DEFAULT '0',
			  course_final_grade_sent VARCHAR(30) NOT NULL DEFAULT '',
			  course_enrolment_date datetime DEFAULT '0000-00-00 00:00:00',
			  UNIQUE KEY user_id (user_id,course_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->user_courses, $SQL, $upgradeNow );

	// User Progress Table
	$SQL = "CREATE TABLE $wpcwdb->user_progress (
			  user_id int(11) unsigned NOT NULL,
			  unit_id int(11) unsigned NOT NULL,
			  unit_completed_date datetime DEFAULT NULL,
			  unit_completed_status varchar(20) NOT NULL,
			  PRIMARY KEY  (user_id,unit_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->user_progress, $SQL, $upgradeNow );

	// Quizzes Table
	$SQL = "CREATE TABLE $wpcwdb->quiz (
			  quiz_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			  quiz_title varchar(150) NOT NULL,
			  quiz_desc text NULL,
			  quiz_author bigint(20) unsigned NOT NULL default '0',
			  parent_unit_id int(11) unsigned NOT NULL DEFAULT '0',
			  parent_course_id int(11) NOT NULL DEFAULT '0',
			  quiz_type varchar(15) NOT NULL,
			  quiz_pass_mark int(11) NOT NULL DEFAULT '0',
			  quiz_show_answers varchar(15) NOT NULL DEFAULT 'no_answers',
			  quiz_show_survey_responses varchar(15) NOT NULL DEFAULT 'no_responses',
			  quiz_attempts_allowed int(11) NOT NULL DEFAULT '-1',
			  show_answers_settings VARCHAR(500) NOT NULL DEFAULT '',
			  quiz_paginate_questions VARCHAR(15) NOT NULL DEFAULT 'no_paging',
			  quiz_paginate_questions_settings VARCHAR(500) NOT NULL DEFAULT '',
			  quiz_timer_mode varchar(25) NOT NULL DEFAULT 'no_timer',
			  quiz_timer_mode_limit int(11) unsigned NOT NULL DEFAULT '15',
			  quiz_results_downloadable varchar(10) NOT NULL DEFAULT 'on',
			  quiz_results_by_tag varchar(10) NOT NULL DEFAULT 'on',
			  quiz_results_by_timer varchar(10) NOT NULL DEFAULT 'on',
			  quiz_recommended_score varchar(20) NOT NULL DEFAULT 'no_recommended',
			  show_recommended_percentage int(10) unsigned NOT NULL DEFAULT 50,
			  PRIMARY KEY  (quiz_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->quiz, $SQL, $upgradeNow );

	// Quiz - Questions
	$SQL = "CREATE TABLE $wpcwdb->quiz_qs (
			  question_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  question_author bigint(20) unsigned NOT NULL default '0',
			  question_type VARCHAR(20) NOT NULL DEFAULT 'multi',
			  question_question text NULL,
			  question_answers text NULL,
			  question_data_answers text NULL,
			  question_correct_answer VARCHAR(300) NOT NULL,
			  question_answer_type VARCHAR(50) NOT NULL DEFAULT '',
			  question_answer_hint text NULL,
			  question_answer_explanation text NULL,
			  question_image VARCHAR(300) NOT NULL DEFAULT '',
			  question_answer_file_types VARCHAR(300) NOT NULL DEFAULT '',
			  question_usage_count int(11) UNSIGNED DEFAULT 0,
			  question_expanded_count int(11) UNSIGNED DEFAULT 1,
			  question_multi_random_enable int(2) UNSIGNED DEFAULT 0,
			  question_multi_random_count  int(4) UNSIGNED DEFAULT 5,
			  question_multi_checkboxes_enable int(2) UNSIGNED DEFAULT 0,
			  PRIMARY KEY  (question_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->quiz_qs, $SQL, $upgradeNow );

	// Quiz - User Progress
	$SQL = "CREATE TABLE $wpcwdb->user_progress_quiz (
			  user_id int(11) NOT NULL,
			  unit_id int(11) NOT NULL,
			  quiz_id bigint(20) NOT NULL,
			  quiz_attempt_id int(11) NOT NULL DEFAULT '0',
			  quiz_completed_date datetime NOT NULL,
			  quiz_started_date datetime NOT NULL,
			  quiz_correct_questions int(11) unsigned NOT NULL,
			  quiz_grade FLOAT(8,2) NOT NULL DEFAULT '-1',
			  quiz_question_total int(11) unsigned NOT NULL,
			  quiz_data text NULL,
			  quiz_is_latest VARCHAR(50) DEFAULT 'latest',
			  quiz_needs_marking int(11) unsigned NOT NULL DEFAULT '0',
			  quiz_needs_marking_list TEXT NULL,
			  quiz_next_step_type VARCHAR(50) DEFAULT '',
			  quiz_next_step_msg TEXT DEFAULT '',
			  quiz_paging_status VARCHAR(20) NOT NULL DEFAULT 'complete',
			  quiz_paging_next_q int(11) NOT NULL DEFAULT 0,
			  quiz_paging_incomplete int(11) NOT NULL DEFAULT 0,
			  quiz_completion_time_seconds BIGINT NOT NULL DEFAULT 0,
			  UNIQUE KEY unique_progress_item (user_id,unit_id,quiz_id,quiz_attempt_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->user_progress_quiz, $SQL, $upgradeNow );

	// Mapping of membership levels
	$SQL = "CREATE TABLE $wpcwdb->map_member_levels (
			  	course_id int(11) NOT NULL,
  				member_level_id varchar(100) NOT NULL,
  				UNIQUE KEY course_id (course_id,member_level_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->map_member_levels, $SQL, $upgradeNow );

	// Mapping of certificates
	$SQL = "CREATE TABLE $wpcwdb->certificates (
			  cert_user_id int(11) NOT NULL,
			  cert_course_id int(11) NOT NULL,
			  cert_access_key varchar(50) NOT NULL,
			  cert_generated datetime NOT NULL,
			  cert_number varchar(50) NOT NULL,
			  UNIQUE KEY cert_user_id (cert_user_id,cert_course_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->certificates, $SQL, $upgradeNow );

	// Questions - Tags
	$SQL = "CREATE TABLE $wpcwdb->question_tags (
				question_tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				question_tag_name varchar(150) NOT NULL,
				question_tag_usage int(11) unsigned NOT NULL,
				question_tag_author bigint(20) unsigned NOT NULL default '0',
				PRIMARY KEY  (question_tag_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->question_tags, $SQL, $upgradeNow );

	// Questions - Tag Mappings
	$SQL = "CREATE TABLE $wpcwdb->question_tag_mapping (
				question_id bigint(20) unsigned NOT NULL,
				tag_id bigint(20) unsigned NOT NULL,
				UNIQUE KEY question_tag_id (question_id,tag_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->question_tag_mapping, $SQL, $upgradeNow );

	// Questions/Quizzes - Mappings
	$SQL = "CREATE TABLE $wpcwdb->quiz_qs_mapping (
				parent_quiz_id bigint(20) unsigned NULL,
				question_id bigint(20) unsigned NOT NULL,
				question_order int(11) unsigned NOT NULL DEFAULT '0',
				UNIQUE KEY question_assoc_id (parent_quiz_id,question_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->quiz_qs_mapping, $SQL, $upgradeNow );

	// Random Questions - Lock selections to a user so that they don't see different variations if refreshed.
	$SQL = "CREATE TABLE $wpcwdb->question_rand_lock (
				question_user_id int(11) unsigned NOT NULL,
				rand_question_id int(11) unsigned NOT NULL,
				parent_unit_id int(11) unsigned NOT NULL,
				question_selection_list text NOT NULL,
				UNIQUE KEY wpcw_question_rand_lock (question_user_id,rand_question_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->question_rand_lock, $SQL, $upgradeNow );

	// List of quiz custom feedback
	$SQL = "CREATE TABLE $wpcwdb->quiz_feedback (
			  	qfeedback_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  				qfeedback_tag_id bigint(20) unsigned NOT NULL,
  				qfeedback_quiz_id int(1) unsigned NOT NULL,
  				qfeedback_summary varchar(300) NOT NULL,
  				qfeedback_score_type varchar(20) NOT NULL DEFAULT 'below',
  				qfeedback_score_grade int(11) unsigned NOT NULL DEFAULT '50',
  				qfeedback_message text NOT NULL,
  				PRIMARY KEY  (qfeedback_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->quiz_feedback, $SQL, $upgradeNow );

	// Queue used for notifying trainees that the next unit is available.
	$SQL = "CREATE TABLE $wpcwdb->queue_dripfeed (
			  	queue_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  				queue_unit_id int(11) unsigned NOT NULL,
  				queue_course_id int(11) unsigned NOT NULL,
  				queue_user_id int(11) NOT NULL,
  				queue_trigger_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  				queue_enqueued_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  				PRIMARY KEY  (queue_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	WPCW_database_installTable( $wpcwdb->queue_dripfeed, $SQL, $upgradeNow );

	// Update settings once upgrade has happened
	delete_option( 'WPCW_version' );
	update_option( 'wpcw_plugin_version', WPCW_VERSION );
	update_option( 'wpcw_db_version', WPCW_DB_VERSION );
}

/**
 * Install or upgrade a table for this plugin.
 *
 * @since 1.0.0
 *
 * @param string $tableName The name of the table to upgrade/install.
 * @param string $SQL The core SQL to create or upgrade the table
 * @param string $upgradeTables If true, we're upgrading to a new level of database tables.
 */
function WPCW_database_installTable( $tableName, $SQL, $upgradeTables ) {
	global $wpdb;

	// Determine if the table exists or not.
	$tableExists = ( $wpdb->get_var( "SHOW TABLES LIKE '$tableName'" ) == $tableName );

	// Table doesn't exist or needs upgrading
	if ( ! $tableExists || $upgradeTables ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $SQL );
	}
}

/**
 * Function that upgrades quiz questions to use the new structure for storage
 * so that they can also contain images too.
 *
 * @since 1.0.0
 */
function WPCW_database_upgrade_quizQuestions() {
	global $wpdb, $wpcwdb;

	$listOfQuestions = $wpdb->get_results( "
		SELECT *
		FROM $wpcwdb->quiz_qs
		WHERE question_type = 'multi'
		  AND question_data_answers = ''
	" );

	if ( ! empty( $listOfQuestions ) ) {
		foreach ( $listOfQuestions as $questionItem ) {
			// Turn list into an array
			$answerListRaw = explode( "\n", $questionItem->question_answers );

			// Base64 encode each item in case we have HTML to worry about.
			$dataToSave = array();

			// Check if list is empty. If it is, that's fine. We just save an empty array to the database.
			if ( ! empty( $answerListRaw ) ) {
				foreach ( $answerListRaw as $idx => $data ) {
					// Creates the following
					// [1] => array('answer' => 'data')
					// [2] => array('answer' => 'data')
					$dataToSave[ $idx + 1 ] = array( 'answer' => base64_encode( $data ) );
				}
			}

			// Serialise the data
			$dataToSave = serialize( $dataToSave );

			$wpdb->query( $wpdb->prepare( "
				UPDATE $wpcwdb->quiz_qs
				   SET question_data_answers = %s
				 WHERE question_id = %d
			", $dataToSave, $questionItem->question_id ) );
		}
	}
}

/**
 * Migrate the enrolement dates for the users on the system for courses.
 *
 * @since 1.0.0
 */
function WPCW_database_migration_enrolementDates() {
	global $wpdb, $wpcwdb;

	// 1 - Find the blank enrolement dates that need updating. Use
	// group by, as some users will be enroled on multiple courses, and this
	// saves work by updating multiple at once.
	$findCoursesToUpdateSQL = "
		SELECT *
		FROM $wpcwdb->user_courses
		WHERE course_enrolment_date = '0000-00-00 00:00:00'
		GROUP BY user_id
		LIMIT 200
	";
	$rowsToUpdate           = $wpdb->get_results( $findCoursesToUpdateSQL );

	// 2 - Loop through all records that need an update. Using a few records at a
	// time to save memory.
	while ( ! empty( $rowsToUpdate ) ) {
		// 3 - Iterate through each user and their progress, and get their
		// started date from their user meta.
		foreach ( $rowsToUpdate as $singleRow ) {
			$dateUpdated = false;

			// 4 Try to get the meta data for this user. If the user has been deleted
			// but the course table still has a user, then $userData will return false.
			$userData = get_userdata( $singleRow->user_id );
			if ( $userData ) {
				// Check date we've got is valid before we use it
				$registered = $userData->user_registered;
				if ( $registered != '0000-00-00 00:00:00' ) {
					$wpdb->query( $wpdb->prepare( "
	            		UPDATE $wpcwdb->user_courses
	            		SET course_enrolment_date = %s
	            		WHERE user_id = %d",
						$registered, $singleRow->user_id ) );

					$dateUpdated = true;
				}
			}// end of userData

			// 5 - Just in case we have an invalid date from user_registered, we set today's
			// date. As we don't want any infinite loops from the null date loop above.
			if ( ! $dateUpdated ) {
				$wpdb->query( $wpdb->prepare( "
	            		UPDATE $wpcwdb->user_courses
	            		SET course_enrolment_date = %s
	            		WHERE user_id = %d",
					current_time( 'mysql' ), $singleRow->user_id ) );
			}
		}

		// Continue the loop by looking for the next ones.
		$rowsToUpdate = $wpdb->get_results( $findCoursesToUpdateSQL );
	}
}

/**
 * Migrate certificate data to each course.
 *
 * @since 1.0.0
 */
function WPCW_database_migrate_certificateData() {
	global $wpdb, $wpcwdb;
	// 1 - Define SQL to lookup courses with certs enabled.
	$findCoursesToUpdateSQL = "SELECT * FROM $wpcwdb->courses";
	// 2 - Get Results from db.
	$rowsToUpdate = $wpdb->get_results( $findCoursesToUpdateSQL );
	// 3 - Get Settings for the certificate
	$wpcwSettings = WPCW_TidySettings_getSettings( WPCW_DATABASE_SETTINGS_KEY );
	// 4 - Get the Settings Values
	$wpcwSettingsValues = array(
		'cert_signature_type'        => WPCW_arrays_getValue( $wpcwSettings, 'cert_signature_type' ),
		'cert_sig_text'              => WPCW_arrays_getValue( $wpcwSettings, 'cert_sig_text' ),
		'cert_sig_image_url'         => WPCW_arrays_getValue( $wpcwSettings, 'cert_sig_image_url' ),
		'cert_logo_enabled'          => WPCW_arrays_getValue( $wpcwSettings, 'cert_logo_enabled' ),
		'cert_logo_url'              => WPCW_arrays_getValue( $wpcwSettings, 'cert_logo_url' ),
		'cert_background_type'       => WPCW_arrays_getValue( $wpcwSettings, 'cert_background_type' ),
		'cert_background_custom_url' => WPCW_arrays_getValue( $wpcwSettings, 'cert_background_custom_url' ),
	);
	// 5 - Go through each db row
	foreach ( $rowsToUpdate as $singleRow ) {
		// Check
		if ( ! isset( $singleRow->course_id ) ) {
			continue;
		}
		// 6 - Migrate Certificate Settings already set.
		$wpdb->query(
			$wpdb->prepare( "
		            UPDATE $wpcwdb->courses
		            SET cert_signature_type = %s,
		                cert_sig_text = %s,
		                cert_sig_image_url = %s,
		                cert_logo_enabled = %s,
		                cert_logo_url = %s,
		                cert_background_type = %s,
		                cert_background_custom_url = %s
		            WHERE course_id = %d;",
				$wpcwSettingsValues['cert_signature_type'],
				$wpcwSettingsValues['cert_sig_text'],
				$wpcwSettingsValues['cert_sig_image_url'],
				$wpcwSettingsValues['cert_logo_enabled'],
				$wpcwSettingsValues['cert_logo_url'],
				$wpcwSettingsValues['cert_background_type'],
				$wpcwSettingsValues['cert_background_custom_url'],
				$singleRow->course_id
			)
		);
	}
}

/**
 * Retrieve all of the questions associated with this course.
 *
 * @since 1.0.0
 *
 * @param int $courseID The ID of the course to get the questions for.
 *
 * @return array The list of question objects.
 */
function WPCW_questions_getAllQuestionsforCourse( $courseID ) {
	global $wpcwdb, $wpdb;
	$wpdb->show_errors();

	// Get a list of all quizzes, as we'll need this to build a list of questions.
	$quizIDList = $wpdb->get_col( $wpdb->prepare( "
    	SELECT quiz_id
    	FROM $wpcwdb->quiz q
    		LEFT JOIN $wpcwdb->units_meta um ON um.unit_id = q.parent_unit_id
    	WHERE q.parent_course_id = %d
   	", $courseID ) );

	if ( empty( $quizIDList ) ) {
		return false;
	}

	$quizIDListStr = implode( ',', $quizIDList );

	// Now we need to get a list of all question IDs being used in all of these quizzes.
	// Just getting IDs and type to save memory.
	$questions = $wpdb->get_results( "
			SELECT qq.question_id, qq.question_type, qq.question_question
			FROM $wpcwdb->quiz_qs qq
				LEFT JOIN $wpcwdb->quiz_qs_mapping qqm ON qqm.question_id = qq.question_id
			WHERE qqm.parent_quiz_id IN ($quizIDListStr)
			GROUP BY qq.question_id
			" );

	$list = array();
	if ( ! empty( $questions ) ) {
		// Create ID => details
		foreach ( $questions as $singleQuestion ) {
			$list[ $singleQuestion->question_id ] = $singleQuestion;
		}

		return $list;
	}

	return false;
}
