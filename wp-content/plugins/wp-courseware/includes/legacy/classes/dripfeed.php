<?php
/**
 * WP Courseware Dripfeed Queue.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Function called by WP Cron to fire off the dripfeed notifications. Added
 * as a simple method ot allow something to override or remove the action if
 * required.
 *
 * @since 1.0.0
 */
function WPCW_cron_notifications_dripfeed() {
	// Process 50 items, unless overridden by someone hooking into add_filter('wpcw_cron_notifications_dripfeed_items_to_process', N);
	WPCW_queue_dripfeed::processQueueItems( apply_filters( 'wpcw_cron_notifications_dripfeed_items_to_process', 50 ) );
}

if ( ! class_exists( 'WPCW_queue_dripfeed' ) ) {
	/**
	 * Class WPCW_queue_dripfeed.
	 *
	 * A class that handles management of the drip feed queue.
	 *
	 * @since 1.0.0
	 */
	class WPCW_queue_dripfeed {

		/**
		 * Setup or clear the scheduler for the notifications timers for the dripfeed. If the setting is 'never'
		 * then clear the scheduler. If it's anything else, then add it.
		 *
		 * @param String $timeSetting The time setting to use (daily, twicedaily, hourly)
		 */
		public static function installNotificationHook_dripfeed( $timeSetting ) {
			// Always clear what's there already
			wp_clear_scheduled_hook( WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID );

			switch ( $timeSetting ) {
				// Valid times, so add the event in the way that we want.
				case 'twicedaily':
				case 'daily':
				case 'hourly':
					// time() is used, because we're using internal time, not localised time.
					wp_schedule_event( time() + 60, $timeSetting, WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID );
					break;

				// Do nothing. Event already cleared.
				case 'never':
					break;

				// Catchall case - ensure there's some kind of event executing. Defaulting to twice daily. Set
				// to trigger in an hour, just in case the system needs the queue database to be set up.
				default:
					wp_schedule_event( time() + 3600, 'twicedaily', WPCW_WPCRON_NOTIFICATIONS_DRIPFEED_ID );
					break;
			}
		}

		/**
		 * Method called when a unit has been updated, so we need to adjust the notification times in the queue.
		 *
		 * @param Integer $unitID The ID of the unit that has been updated.
		 */
		public static function updateQueueItems_unitUpdated( $unitID ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			// See if we have any items in the queue that need updating.
			$queueItems = $wpdb->get_results( $wpdb->prepare( "
				SELECT *
				FROM $wpcwdb->queue_dripfeed
				WHERE queue_unit_id = %d
				", $unitID ) );

			// Right, we've got some items to update, we had better get the dates on these updated.
			if ( ! empty( $queueItems ) ) {
				// #### 1 - We need the meta data for this unit to calculate the dates.
				$unitMetaData = WPCW_units_getUnitMetaData( $unitID );

				// #### 2 - Now we need to see we we're expecting to happen.
				switch ( $unitMetaData->unit_drip_type ) {
					// We've got a specific date to set to. So set all items to this date.
					case 'drip_specific':
						$wpdb->query( $wpdb->prepare( "
						UPDATE $wpcwdb->queue_dripfeed
						   SET queue_trigger_date = %s
						WHERE queue_unit_id = %d
						", $unitMetaData->unit_drip_date, $unitID ) );
						break;

					// We've got to calculate the interval for each user.
					case 'drip_interval':
						// Calculate the new date for each item.
						foreach ( $queueItems as $singleItem ) {
							// Fetch the enrolement date for this user to use in our calculation.
							$enrolmentDate = WPCW_users_getCourseEnrolmentDate( $singleItem->queue_user_id, $singleItem->queue_course_id );

							// Add the interval type, which is stored in seconds.
							$newTargetDate = $unitMetaData->unit_drip_interval + $enrolmentDate;

							// Now update this queue item to the new date. Assume that if the date has been set backwards,
							// we still notify the user, because they'll want to know that they can access the unit. Any old
							// queue items will always get triggered.
							$wpdb->query( $wpdb->prepare( "
								UPDATE $wpcwdb->queue_dripfeed
								   SET queue_trigger_date = %s
								WHERE queue_id = %d
								", date( 'Y-m-d H:i:s', $newTargetDate ), $singleItem->queue_id ) );
						}

						break;

					// No drip-feeding is needed now. Set the time to 15 minutes from now.
					// As they're expecting to be notified when the unit is accessible.
					default:
						$wpdb->query( $wpdb->prepare( "
						UPDATE $wpcwdb->queue_dripfeed
						   SET queue_trigger_date = %s
						WHERE queue_unit_id = %d
						", date( 'Y-m-d H:i:s', strtotime( '+15 minutes' ) ), $unitID ) );
						break;
				}
			}
		}

		/**
		 * Function called when a unit has been deleted from the system, meaning
		 * we need to delete the queue items associted with this unit.
		 *
		 * @param Integer $unitID The ID of the unit that has been deleted.
		 */
		public static function updateQueueItems_unitDeleted( $unitID ) {
			WPCW_queue_dripfeed::updateQueueItems_removeItemsByUnit( $unitID );
		}

		/**
		 * Function called when a unit has been removed from a course. This means
		 * we need to delete the queue items associated with this unit.
		 *
		 * @param Integer $unitID The ID of the unit that has been removed.
		 */
		public static function updateQueueItems_unitRemovedFromCourse( $unitID ) {
			WPCW_queue_dripfeed::updateQueueItems_removeItemsByUnit( $unitID );
		}

		/**
		 * Function called when a user has been removed from all courses, so remove
		 * all queue items associated with this user.
		 *
		 * @param Integer $userID The ID of the user that has been removed.
		 */
		public static function updateQueueItems_removeUser_fromAllCourses( $userID ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			$wpdb->query( $wpdb->prepare( "
				DELETE
				FROM $wpcwdb->queue_dripfeed
				WHERE queue_user_id = %d
				", $userID ) );
		}

		/**
		 * Function called when a user has been removed from specified courses, so remove
		 * all queue items associated with this user.
		 *
		 * @param Integer $userID The ID of the user that has been removed.
		 * @param Array $courseList The list of course IDs that this user has been removed from.
		 */
		public static function updateQueueItems_removeUser_fromCourseList( $userID, $courseList ) {
			if ( empty( $courseList ) ) {
				return;
			}

			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			foreach ( $courseList as $courseID ) {
				$wpdb->query( $wpdb->prepare( "
					DELETE
					FROM $wpcwdb->queue_dripfeed
					WHERE queue_user_id = %d
					  AND queue_course_id = %d
					", $userID, $courseID ) );
			}
		}

		/**
		 * Function called when a users enrollment date as been updated, so ensure that the
		 * notification dates are correct.
		 *
		 * @param Integer $userID The ID of the user that has been updated.
		 * @param Integer $courseID The ID of the course that's been updated for this user.
		 */
		public static function updateQueueItems_updateNotifications_forCourse( $userID, $courseID ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			// See if we have any items in the queue that need updating.
			$queueItems = $wpdb->get_results( $wpdb->prepare( "
				SELECT *
				FROM $wpcwdb->queue_dripfeed
				WHERE queue_user_id = %d
				  AND queue_course_id = %d
				", $userID, $courseID ) );

			if ( empty( $queueItems ) ) {
				return;
			}

			foreach ( $queueItems as $singleItem ) {
				// #### 1 - We need the meta data for this unit to calculate the dates.
				$unitMetaData = WPCW_units_getUnitMetaData( $singleItem->queue_unit_id );

				// #### 2 - Now we need to see we we're expecting to happen.
				switch ( $unitMetaData->unit_drip_type ) {
					// We've got to calculate the interval for this user, for each item.
					case 'drip_interval':

						// Fetch the enrolement date for this user to use in our calculation.
						$enrolmentDate = WPCW_users_getCourseEnrolmentDate( $singleItem->queue_user_id, $singleItem->queue_course_id );

						// Add the interval type, which is stored in seconds.
						$newTargetDate = $unitMetaData->unit_drip_interval + $enrolmentDate;

						// Now update this queue item to the new date. Assume that if the date has been set backwards,
						// we still notify the user, because they'll want to know that they can access the unit. Any old
						// queue items will always get triggered.
						$wpdb->query( $wpdb->prepare( "
							UPDATE $wpcwdb->queue_dripfeed
							   SET queue_trigger_date = %s
							WHERE queue_id = %d
							", date( 'Y-m-d H:i:s', $newTargetDate ), $singleItem->queue_id ) );
						break;


					// Either we've got a specific date to set to. No action needed, it's an absolute date.
					// or we've not got a drip type to worry about.
					//case 'drip_specific':
					//case '':
					default:
						break;
				}
			} // end foreach

		}

		/**
		 * Function that removes item by ID from the queue.
		 *
		 * @param Integer $unitID The ID of the unit whose queue items we need to delete.
		 */
		private static function updateQueueItems_removeItemsByUnit( $unitID ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			$wpdb->query( $wpdb->prepare( "
				DELETE
				FROM $wpcwdb->queue_dripfeed
				WHERE queue_unit_id = %d
				", $unitID ) );
		}

		/**
		 * Process a defined number of items from the queue, and send the right emails to those users.
		 *
		 * @param Integer $count The number of items to send out to users.
		 */
		public static function processQueueItems( $count ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			// See if we have any items that have expired.
			$queueItems = $wpdb->get_results( $wpdb->prepare(
				"SELECT *
				 FROM $wpcwdb->queue_dripfeed
				 WHERE queue_trigger_date <= %s
				 LIMIT %d",
				current_time( 'mysql' ),
				$count
			) );

			/**
			 * Filter: Dripfeed Queue Items
			 *
			 * @since 4.4.0
			 *
			 * @param object $queueItems The dripfeed queue items.
			 *
			 * @return object $queueItems The dripfeed queue items.
			 */
			$queueItems = apply_filters( 'wpcw_dripfed_queue_items', $queueItems );

			if ( ! empty( $queueItems ) ) {
				// Send an email to the user for each queue item.
				foreach ( $queueItems as $singleTrigger ) {
					// Performance enhancements could be possible by caching the parent data for units that are the same.

					// Need user and parent data in order to send the email.
					$parentData  = WPCW_units_getAssociatedParentData( $singleTrigger->queue_unit_id );
					$userDetails = get_userdata( $singleTrigger->queue_user_id );

					/**
					 * Filter: Dripfeed Queue Item Email Subject.
					 *
					 * @since 4.4.0
					 *
					 * @param string The unlocked unit subject.
					 * @param object $singleTrigger The single trigger item
					 * @param object $parentData The parent unit data.
					 * @param object $userDetails The user details.
					 *
					 * @return string The unlocked unit subject.
					 */
					$subject = apply_filters( 'wpcw_dripfeed_queue_item_email_subject', $parentData->email_unit_unlocked_subject, $singleTrigger, $parentData, $userDetails );

					/**
					 * Filter: Dripfeed Queue Item Email Body.
					 *
					 * @since 4.4.0
					 *
					 * @param string The unlocked unit body.
					 * @param object $singleTrigger The single trigger item
					 * @param object $parentData The parent unit data.
					 * @param object $userDetails The user details.
					 *
					 * @return string The unlocked unit body.
					 */
					$body = apply_filters( 'wpcw_dripfeed_queue_item_email_body', $parentData->email_unit_unlocked_body, $singleTrigger, $parentData, $userDetails );

					/**
					 * Action: Dripfeed Queue Item.
					 *
					 * @since 4.4.0
					 *
					 * @param object $singleTrigger The single trigger item
					 * @param object $parentData The parent unit data.
					 * @param object $userDetails The user details.
					 */
					do_action( 'wpcw_dripfeed_queue_item', $singleTrigger, $parentData, $userDetails );

					// Send the email with the details and a link to the unit.
					WPCW_email_sendEmail( $parentData, $userDetails, $userDetails->user_email, $subject, $body );

					// Delete the queue item so that it doesn't need to execute again.
					$wpdb->query( $wpdb->prepare( "DELETE FROM $wpcwdb->queue_dripfeed WHERE queue_id = %d", $singleTrigger->queue_id ) );
				}
			}

			die();
		}

		/**
		 * Add an item to the queue to notify the user when it becomes available.
		 *
		 * @param Integer $unitID The ID of the unit to add to the queue.
		 * @param Integer $userID The ID of the user that's going to be notified.
		 * @param Integer $unlockDate The date as a timestamp, of when the user should be notified that the unit is available.
		 *
		 */
		public static function addQueueItem( $unitID, $userID, $unlockDate ) {
			global $wpdb, $wpcwdb;
			$wpdb->show_errors();

			// Need date as MySQL date for comparison or insertion below
			$triggerDateAsMySQL = date( 'Y-m-d H:i:s', $unlockDate );

			// Need the course ID - going to assume this is valid, otherwise it wouldn't get triggered
			// in the first place.
			$parentData   = WPCW_units_getAssociatedParentData( $unitID );
			$unitCourseID = $parentData->parent_course_id;

			/**
			 * Action: Dripfeed Before Add Queue Item.
			 *
			 * @since 4.4.0
			 *
			 * @param int $unitID The unit id.
			 * @param int $userID The user id.
			 * @param string $unlockDate The unlock date.
			 * @param string $triggerDateAsMySQL The unlock dat as mysql.
			 * @param int $unitCourseID The unit course id.
			 * @param object $parentData The unit parent data.
			 */
			do_action( 'wpcw_dripfeed_before_add_queue_item', $unitID, $userID, $unlockDate, $triggerDateAsMySQL, $unitCourseID, $parentData );

			// Check if the queue item already exists for this unit/user. If we find it,
			// then we need to update the unlock date (if it's different).

			// ### 1 - Yep, got an existing queue item
			if ( $existingQueueItem = $wpdb->get_row( $wpdb->prepare(
				"SELECT *
				 FROM $wpcwdb->queue_dripfeed
				 WHERE queue_unit_id = %d
				 AND queue_user_id = %d",
				$unitID,
				$userID
			) ) ) {
				/**
				 * Action: Dripfeed Existing Queue Item
				 *
				 * @since 4.4.0
				 *
				 * @param object $existingQueueItem The existing queue item.
				 * @param int $unitID The unit id.
				 * @param int $userID The user id.
				 * @param string $unlockDate The unlock date.
				 * @param string $triggerDateAsMySQL The unlock dat as mysql.
				 * @param int $unitCourseID The unit course id.
				 * @param object $parentData The unit parent data.
				 */
				do_action( 'wpcw_dripfeed_existing_queue_item', $existingQueueItem, $unitID, $userID, $unlockDate, $triggerDateAsMySQL, $unitCourseID, $parentData );

				// Do a simple string match on the trigger date. If no match, then just
				// update database with the new date.
				if ( $existingQueueItem->queue_trigger_date != $triggerDateAsMySQL ) {
					// Update the trigger date.
					$wpdb->query( $wpdb->prepare(
						"UPDATE $wpcwdb->queue_dripfeed
					   	 SET queue_trigger_date = %s, queue_enqueued_date = %s, queue_course_id = %d 
					   	 WHERE queue_id = %d",
						$triggerDateAsMySQL,
						current_time( 'mysql' ),
						$unitCourseID,
						$existingQueueItem->queue_id
					) );
				}
			} // ### 2 - No entry for this user or unit, so add one.
			else {
				/**
				 * Action: Dripfeed New Queue Item
				 *
				 * @since 4.4.0
				 *
				 * @param object $existingQueueItem The existing queue item.
				 * @param int $unitID The unit id.
				 * @param int $userID The user id.
				 * @param string $unlockDate The unlock date.
				 * @param string $triggerDateAsMySQL The unlock dat as mysql.
				 * @param int $unitCourseID The unit course id.
				 * @param object $parentData The unit parent data.
				 */
				do_action( 'wpcw_dripfeed_new_queue_item', $unitID, $userID, $unlockDate, $triggerDateAsMySQL, $unitCourseID, $parentData );

				$wpdb->query( $wpdb->prepare(
					"INSERT INTO $wpcwdb->queue_dripfeed (queue_unit_id, queue_course_id, queue_user_id, queue_trigger_date, queue_enqueued_date)
					 VALUES (%d, %d, %d, %s, %s)",
					$unitID,
					$unitCourseID,
					$userID,
					$triggerDateAsMySQL,
					current_time( 'mysql' )
				) );
			}

			/**
			 * Action: Dripfeed After Add Queue Item.
			 *
			 * @since 4.4.0
			 *
			 * @param int $unitID The unit id.
			 * @param int $userID The user id.
			 * @param string $unlockDate The unlock date.
			 * @param string $triggerDateAsMySQL The unlock dat as mysql.
			 * @param int $unitCourseID The unit course id.
			 * @param object $parentData The unit parent data.
			 */
			do_action( 'wpcw_dripfeed_after_add_queue_item', $unitID, $userID, $unlockDate, $triggerDateAsMySQL, $unitCourseID, $parentData );
		}
	}
}