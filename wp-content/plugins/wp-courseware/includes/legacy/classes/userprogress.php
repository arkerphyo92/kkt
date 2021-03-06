<?php
/**
 * WP Courseware User Progress.
 *
 * @package WPCW
 * @since 1.0.0
 */

if ( ! class_exists( 'WPCW_UserProgress' ) ) {
	/**
	 * Class UserProgress
	 *
	 * @since 1.0.0
	 */
	class WPCW_UserProgress {

		/** A complete list of units in this course. */
		protected $unitList;

		/** A complete list of units that the user has completed for this course. */
		protected $unitListProgress;

		/** ID of course we're getting progress data for. */
		protected $courseID;

		/** ID of user we're getting progress data for. */
		protected $userID;

		/** Determines if the user is allowed to access the course. */
		protected $canAccessCourse;

		/** If true, the user can only access the next available unit and all previously completed units. */
		public $userCanOnlyAccessNext;

		/** If true, we've stored the next item that the user can access (which might be false, hence this flag). */
		protected $nextUnitLoaded;

		/** The next unit to complete by the user (loaded by getNextPendingUnit()). */
		protected $nextUnit;

		/**
		 * Create user progress object.
		 *
		 * @param Integer $courseID The ID of the course that we're checking.
		 * @param Integer $userID The ID of the user that we're checking.
		 * @param object  $loadedCourseDetails The loaded course details.
		 */
		public function __construct( $courseID, $userID, $loadedCourseDetails = false ) {
			$this->courseID = $courseID;
			$this->userID   = $userID;

			// Work out if access allowed
			$this->canAccessCourse = WPCW_courses_canUserAccessCourse( $this->courseID, $this->userID );

			// Work out if want walled access to units
			$this->userCanOnlyAccessNext = false;

			if ( $loadedCourseDetails && ! empty( $loadedCourseDetails->course_id ) ) {
				$courseDetails = $loadedCourseDetails;
			} else {
				$courseDetails = WPCW_courses_getCourseDetails( $this->courseID );
			}

			// Completion wall is set, so need to stop access to all units.
			if ( $courseDetails && 'completion_wall' == $courseDetails->course_opt_completion_wall ) {
				$this->userCanOnlyAccessNext = true;
			}

			// Get the next item user is allowed to access
			$this->nextUnitLoaded = false;
			$this->nextUnit       = false;
		}

		/**
		 * Determine if the user can access the course.
		 * @return Boolean True if they can, false otherwise.
		 */
		public function canUserAccessCourse() {
			return $this->canAccessCourse;
		}

		/**
		 * Load the full list of units for this course.
		 */
		private function loadFullUnitList() {
			if ( $this->unitList ) {
				return;
			}

			global $wpcwdb, $wpdb;
			$wpdb->show_errors();

			// Get a list of all units for this course in absolute order
			$SQL = $wpdb->prepare( "
				SELECT *
				FROM $wpcwdb->units_meta
				WHERE parent_course_id = %d
				ORDER BY unit_order ASC
			", $this->courseID );

			// Always create an array - so we know if we've tried this or not.
			// If unitList is false, we've not tried to fill it yet.
			$this->unitList = array();

			// Convert into an ID => object list
			$items = $wpdb->get_results( $SQL );
			if ( $items ) {
				foreach ( $items as $item ) {
					$this->unitList[ $item->unit_id ] = $item;
				}
			}
		}

		/**
		 * Load a list of the units that the user has completed in this course.
		 */
		private function loadUserUnitProgress() {
			if ( $this->unitListProgress ) {
				return;
			}

			global $wpcwdb, $wpdb;
			$wpdb->show_errors();

			// Get a list of all units for this course in absolute order
			$SQL = $wpdb->prepare( "SELECT * FROM $wpcwdb->user_progress up
									LEFT JOIN $wpcwdb->units_meta um ON up.unit_id = um.unit_id
									WHERE user_id = %d
			  						AND parent_course_id = %d
									ORDER BY unit_order ASC", $this->userID, $this->courseID );

			// Always create an array - so we know if we've tried this or not.
			// If unitListProgress is false, we've not tried to fill it yet.
			$this->unitListProgress = array();

			// Convert into an ID => object list
			$items = $wpdb->get_results( $SQL );
			if ( $items ) {
				foreach ( $items as $item ) {
					$this->unitListProgress[ $item->unit_id ] = $item;
				}
			}
		}

		/**
		 * Returns the details of the next unit that needs to be done by the user.
		 *
		 * @return Object The object if there's a next unit to complete, or false if no units,
		 * not allowed to see course, or already completed the course.
		 */
		private function getNextPendingUnit_fetch() {
			if ( ! $this->canUserAccessCourse() ) {
				return false;
			}

			// Get list of units to choose from. Abort if no units in course.
			$this->loadFullUnitList();
			if ( ! $this->unitList ) {
				return false;
			}

			// If no progress, then choose first item in list of course units.
			$this->loadUserUnitProgress();
			if ( ! $this->unitListProgress ) {
				return current( $this->unitList ); // Previously using array_shift, which caused issues.
			}

			// Go through list of all items, and see what's not been done yet.
			foreach ( $this->unitList as $unitID => $unitMeta ) {
				if ( ! isset( $this->unitListProgress[ $unitID ] ) ) {
					$unitMeta->data = get_post( $unitID );

					return $unitMeta;
				}
			}

			// Done them already, so return nothing.
			return false;
		}

		/**
		 * Returns the details of the next unit that needs to be done by the user.
		 *
		 * @return Object The object if there's a next unit to complete, or false if no units,
		 * not allowed to see course, or already completed the course.
		 */
		public function getNextPendingUnit() {
			// Already fetched it
			if ( $this->nextUnitLoaded ) {
				return $this->nextUnit;
			}

			// Not loaded the next unit yet, so load it, cache it, and return it.
			$this->nextUnit       = $this->getNextPendingUnit_fetch();
			$this->nextUnitLoaded = true;

			return $this->nextUnit;
		}

		/**
		 * Quick function to check if the specified Unit has been completed by the user.
		 *
		 * @param Integer $unitID The ID of the unit that's being checked.
		 * @param Boolean True if the unit has been completed, false otherwise.
		 */
		public function isUnitCompleted( $unitID ) {
			if ( $unitID < 1 ) {
				return false;
			}

			$this->loadUserUnitProgress();

			// Simply check if ID is in list of progress items.
			return ( isset( $this->unitListProgress[ $unitID ] ) );
		}

		/**
		 * Checks if the specified unit was the last unit in the module to complete.
		 *
		 * @param Integer $unitID The ID of the unit that's being checked.
		 * @param Boolean True if the module has been completed, false otherwise.
		 */
		public function isModuleCompleted( $unitID ) {
			$this->loadFullUnitList();

			// If no units to complete, then not complete.
			if ( ! $this->unitList ) {
				return false;
			}

			// Get details for this particular unit
			$thisUnitDetails = $this->unitList[ $unitID ];
			$parentModuleID  = $thisUnitDetails->parent_module_id;

			// Bad parent module. So not complete. Shouldn't be here, but
			// check anyway.
			if ( $parentModuleID < 1 ) {
				return false;
			}

			// Get list of all units that are in same module
			$moduleList = array();
			foreach ( $this->unitList as $aUnitID => $aUnitObj ) {
				// Same module, so copy it to list
				if ( $aUnitObj->parent_module_id == $parentModuleID ) {
					$moduleList[ $aUnitID ] = $aUnitObj;
				}
			}

			// Ensure all units in the module are complete
			foreach ( $moduleList as $aUnitObj ) {
				if ( ! $this->isUnitCompleted( $aUnitObj->unit_id ) ) {
					return false;
				}
			}

			// Got this far, so all units in module should be complete.
			return true;
		}

		/**
		 * Checks if the course is now complete.
		 *
		 * @param Boolean True if the course has been completed, false otherwise.
		 */
		public function isCourseCompleted() {
			$this->loadFullUnitList();

			// If no units to complete, then not complete.
			if ( ! $this->unitList ) {
				return false;
			}

			// Ensure all units in the course are complete
			foreach ( $this->unitList as $aUnitObj ) {
				if ( ! $this->isUnitCompleted( $aUnitObj->unit_id ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks for the course completion date by finding the completed date of the last unit.
		 *
		 * @param Returns date
		 */
		public function courseCompletedDate() {
			$this->loadFullUnitList();

			// If no units to complete, then not complete.
			if ( ! $this->unitList ) {
				return false;
			}

			global $wpcwdb, $wpdb;
			$wpdb->show_errors();

			if ( $this->isCourseCompleted() ) {
				$last_unit    = end( $this->unitList );
				$last_unit_id = $last_unit->unit_id;

				$last_unit_date = $wpdb->get_var( "SELECT unit_completed_date FROM $wpcwdb->user_progress WHERE unit_id = $last_unit_id AND user_id = $this->userID" );

				return $last_unit_date;
			}

			return;
		}

		/**
		 * Quick function to check if the user can access the specified unit based on course settings.
		 *
		 * @param Integer $unitID The ID of the unit that's being checked.
		 * @param Boolean True if the unit can be accessed by this user, false otherwise.
		 */
		public function canUserAccessUnit( $unitID ) {
			// Invalid unit
			if ( $unitID < 1 ) {
				return apply_filters( 'wpcw_user_progress_canUserAccessUnit', false, $unitID, $this );
			}

			// Check early on for admin or teacher.
			if ( wpcw_is_unit_admin_or_teacher( $unitID, $this->userID ) ) {
				return apply_filters( 'wpcw_user_progress_canUserAccessUnit', true, $unitID, $this );
			}

			// Not allowed to access course.
			if ( ! $this->canUserAccessCourse() ) {
				return apply_filters( 'wpcw_user_progress_canUserAccessUnit', false, $unitID, $this );
			}

			// Allowed to access all.
			if ( ! $this->userCanOnlyAccessNext ) {
				return apply_filters( 'wpcw_user_progress_canUserAccessUnit', true, $unitID, $this );
			}

			// Allow access to anything that's complete. As user has already seen it.
			$this->loadUserUnitProgress();
			if ( isset( $this->unitListProgress[ $unitID ] ) ) {
				return apply_filters( 'wpcw_user_progress_canUserAccessUnit', true, $unitID, $this );
			}

			// Now check for the next item after what's been completed.
			$nextUnit = $this->getNextPendingUnit();
			if ( $nextUnit && $nextUnit->unit_id == $unitID ) {
				return apply_filters( 'wpcw_user_progress_canUserAccessUnit', true, $unitID, $this );
			}

			return apply_filters( 'wpcw_user_progress_canUserAccessUnit', false, $unitID, $this );
		}

		/**
		 * Quick function to check if the user has completed the course prerequisites.
		 *
		 * @param Integer $userID The ID of the user that is being checked.
		 *
		 * @return Boolean True if the course pre-requisites have been completed, false otherwise.
		 */
		public function hasUserCompletedCoursePrerequisites( $userID ) {
			// Get Prerequisites
			$course_prereqs = WPCW_users_getCoursePrerequisites( $this->courseID );

			// Check
			if ( is_array( $course_prereqs ) ) {
				foreach ( $course_prereqs as $prereq_key => $prereq_id ) {
					if ( 0 === absint( $prereq_id ) ) {
						$prereq_id = $prereq_key;
					}

					$course_progress = new WPCW_UserProgress( $prereq_id, $userID );

					// If the courses are not completed, get out and return false
					if ( ! $course_progress->isCourseCompleted() ) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Work out if the user can access the next and previous units based on the current unit, and return them.
		 *
		 * @param Integer $unitID The unit to check for previous and next units.
		 *
		 * @return Array The list of next and previous details.
		 */
		public function getNextAndPreviousUnit( $unitID ) {
			$details = array( 'next' => false, 'prev' => false );

			// Load all units.
			$this->loadFullUnitList();

			$prev     = false;
			$next     = false;
			$prevPrev = false;

			// Check this unit is in the list
			if ( isset( $this->unitList[ $unitID ] ) ) {
				// Find previous and next by iterating through list of units
				foreach ( $this->unitList as $unitObjID => $unitObj ) {
					if ( $prev == $unitID ) {
						$next = $unitObjID;
						break;
					}

					$prevPrev = $prev;
					$prev     = $unitObjID;
				}

				// Copy next and previous from search.
				$details['next'] = $next;
				$details['prev'] = $prevPrev;
			}

			return $details;
		}
	}

	/**
	 * Class UserProgress.
	 *
	 * Backwards Compatability Fix for older installations.
	 *
	 * @since 4.3.2
	 */
	class UserProgress extends WPCW_UserProgress {

	}
}
