<?php
/**
 * WP Courseware Records Form Builder Utility Class
 *
 * A class that quickly creates a form for editing a single row of data in a table
 * within WordPress. There's also support to save some rows to a meta data table.
 *
 * This code is very much in alpha phase, and should not be distributed with plugins
 * other than by Dan Harrison.
 *
 * @author Dan Harrison of WP Doctors (http://www.wpdoctors.co.uk)
 * @package WPCW
 * @since 1.0.0
 */

if ( ! class_exists( 'RecordsForm' ) ) {
	/**
	 * Class RecordsForm.
	 *
	 * @since 1.0.0
	 */
	class RecordsForm extends EasyForm {

		/**
		 * The name of the database table to save the details to.
		 * @var String
		 */
		protected $tableName;

		/**
		 * The name of database column to check for the data when modifying it.
		 * @var String
		 */
		protected $primaryKey;

		/**
		 * The name of the database column in the meta table to reference the primary key.
		 * @var string
		 */
		protected $metaForeignKey;

		/**
		 * The value of the primary key, which might be false if we're creating a new record.
		 * @var Mixed
		 */
		public $primaryKeyValue;

		/**
		 * The list of elements that are being stored as meta data, not normal table entries.
		 * @var Array
		 */
		protected $meta_element_list;

		/**
		 * The message shown when the record is successfully created.
		 * @var String.
		 */
		public $msg_record_created;

		/**
		 * The message shown when the record is successfully updated.
		 * @var String.
		 */
		public $msg_record_updated;

		/**
		 * Variable set if record already exists when it was saved.
		 * @var Boolean.
		 */
		public $alreadyExists;

		/**
		 * Variable set if record already exists in database.
		 * @var Boolean.
		 */
		protected $alreadyInDB;

		/**
		 * Store the loaded record details locally.
		 * @var Array
		 */
		public $recordDetails;

		/**
		 * The callback function called when the record record is successfully created.
		 * @var String.
		 */
		public $fn_record_created;

		/**
		 * The callback function called when the record record is successfully updated.
		 * @var String.
		 */
		public $fn_record_updated;

		/**
		 * Default constructor that takes in initial parameters.
		 *
		 * @param Array $paramList The list of parameters to create the form from.
		 * @param String $tableName The name of the table to save the details to (or retrieve them from).
		 * @param String $primaryKey The name of the primary key used to locate a record to update it.
		 * @param Stirng $metaTableName The name of the table storing meta data.
		 * @param String $formID The optional ID to give to the form. Ideal for when there's more than 1 form on a page.
		 * @param String $metaTableForeignKey The optional foreign key.
		 */
		public function __construct( $paramList, $tableName, $primaryKey, $metaTableName = false, $formID = false, $metaTableForeignKey = false ) {
			parent::__construct( $paramList, $formID );

			// Default save text reflects this is a data entry form.
			$this->buttonText = 'Save Details';

			$this->tableName       = $tableName;
			$this->primaryKey      = $primaryKey;
			$this->primaryKeyValue = false;

			$this->fn_record_updated = false;
			$this->fn_record_created = false;

			// Exists checks
			$this->alreadyInDB = false;

			// Turn primary key into a hidden field, so that we can handle modifies.
			$elem = new FormElement( $primaryKey, false );
			$elem->setTypeAsHidden();
			$this->formObj->addFormElement( $elem );

			// Meta data support
			$this->metaTableName     = $metaTableName;
			$this->meta_element_list = false;
			$this->metaForeignKey    = ( $metaTableForeignKey ) ? $metaTableForeignKey : $this->primaryKey;

			// Pull out fields being stored as meta data if we have a meta table name
			if ( $metaTableName ) {
				$this->meta_element_list = array();
				if ( ! empty( $paramList ) ) {
					foreach ( $paramList as $fieldName => $fieldDetails ) {
						// We're looking for metadata => true in the list of elements
						// which determines it should be saved as meta data, not as a
						// value in the normal table column.
						if ( isset( $fieldDetails['metadata'] ) && $fieldDetails['metadata'] == true ) {
							$this->meta_element_list[] = $fieldName;
						}
					} // end foreach
				} // end if (!empty($paramList))
			} // end if ($metaTableName)

			// Default messages
			$this->msg_record_created = 'Record successfully created';
			$this->msg_record_updated = 'Record successfully updated.';
		}

		/**
		 * Set the primary key value for this form.
		 *
		 * @param Mixed $primaryKeyValue The value for the primary key for this record.
		 * @param Boolean $loadDefaults If true, load the defaults from the database into the form.
		 */
		public function setPrimaryKeyValue( $primaryKeyValue, $loadDefaults = true ) {
			$this->primaryKeyValue = $primaryKeyValue;
			$this->formObj->setDefaultValues( array( $this->primaryKey => $primaryKeyValue ) );

			if ( $primaryKeyValue && $loadDefaults ) {
				$this->loadDefaultsFromDB();
			}
		}

		/**
		 * If we have primary key and table details, load details from the database to load the defaults. This method
		 * will also load the meta values if we have any.
		 *
		 * @return Return the details that were loaded from the database.
		 */
		protected function loadDefaultsFromDB() {
			if ( ! $this->primaryKey || ! $this->tableName ) {
				$this->messages = $this->showMessage( 'loadDefaultsFromDB(): Nothing could be loaded, as the table name and primary key details are invalid.', true );

				return false;
			}

			// Fetch any existing details for the main record.
			$this->recordDetails = getRecordDetails( $this->tableName, $this->primaryKey, $this->primaryKeyValue, ARRAY_A );
			if ( empty( $this->recordDetails ) ) {
				return false;
			}

			// Try to fetch the meta values if we have any
			if ( ! empty( $this->meta_element_list ) ) {
				if ( ! $this->metaTableName ) {
					$this->messages = $this->showMessage( 'loadDefaultsFromDB(): The specified meta table is not valid, so meta values could not be loaded.', true );

					return $this->recordDetails;
				}

				global $wpdb;
				$wpdb->show_errors();

				foreach ( $this->meta_element_list as $fieldName ) {
					$SQL = $wpdb->prepare( "
						SELECT meta_value 
						FROM $this->metaTableName
						WHERE $this->metaForeignKey = %s
						AND meta_key = %s
						LIMIT 1",
						$this->primaryKeyValue,
						$fieldName
					);

					// Add details, even if blank
					$this->recordDetails[ $fieldName ] = $wpdb->get_var( $SQL );
				} // end of foreach

			} // end of if

			// Load the details into the defaults.
			parent::loadDefaults( $this->recordDetails );

			// Exists checks
			$this->alreadyInDB = true;

			return $this->recordDetails;
		}

		/**
		 * Method called when form details are being saved to a normal table.
		 *
		 * @param Array $formValues The list of details being saved.
		 *
		 * @return Integer The ID of the newly saved record, or false if something went wrong.
		 */
		protected function handleSaveMainDetails( $formValues ) {
			if ( ! $this->primaryKey || ! $this->tableName ) {
				$this->messages = $this->showMessage( 'handleSave(): Nothing could be saved, as the table name and primary key details are invalid.', true );

				return false;
			}

			global $wpdb;
			$wpdb->show_errors();

			// Have we already got this entry in the database?
			$this->alreadyExists = false;

			// Check #1 - See if we've got a primary key for it?
			$this->primaryKeyValue = $this->formObj->getArrayValue( $formValues, $this->primaryKey );
			if ( $this->primaryKeyValue ) {
				// Check #2 - See if the record exists already
				$details = getRecordDetails( $this->tableName, $this->primaryKey, $this->primaryKeyValue );

				if ( ! empty( $details ) ) {
					$this->alreadyExists = true;
				}
			}

			// Record already exists, so updated it...
			if ( $this->alreadyExists ) {
				$SQL = arrayToSQLUpdate( $this->tableName, $formValues, $this->primaryKey );
				$wpdb->query( $SQL );

				$this->messages = $this->showMessage( $this->msg_record_updated );

				// Update the locally stored details
				$this->recordDetails = getRecordDetails( $this->tableName, $this->primaryKey, $this->primaryKeyValue, ARRAY_A );

				// Function called when record has been updated.
				if ( $this->fn_record_updated && function_exists( $this->fn_record_updated ) ) {
					call_user_func( $this->fn_record_updated, $this->recordDetails );
				}

				// ID of existign record
				return $this->primaryKeyValue;
			} else {
				// Ensure that primary key is not set when creating a new one, to ensure non-dup
				// of IDs
				unset( $formValues[ $this->primaryKey ] );

				// Create new record...
				$SQL = arrayToSQLInsert( $this->tableName, $formValues );
				$wpdb->query( $SQL );

				// ...then get the newly inserted ID so that we can update, without inserting again.
				$this->primaryKeyValue = $wpdb->insert_id;

				// DJH 2013-12-03 - Moved to below
				//$this->formObj->setDefaultValues(array($this->primaryKey => $this->primaryKeyValue));

				$this->messages = $this->showMessage( $this->msg_record_created );

				// Update the locally stored details
				$this->recordDetails = getRecordDetails( $this->tableName, $this->primaryKey, $this->primaryKeyValue, ARRAY_A );

				// DJH 2013-12-03 - Added to use the stored details in the form.
				$this->formObj->setDefaultValues( $this->recordDetails );

				// Function called when record has been created.
				if ( $this->fn_record_created && function_exists( $this->fn_record_created ) ) {
					call_user_func( $this->fn_record_created, $this->recordDetails );
				}

				// Newly inserted ID.
				return $this->primaryKeyValue;
			}
		} // end of fn

		/**
		 * Method called when form details are being saved.
		 *
		 * @param Array $formValues The list of settings being saved.
		 *
		 * @see wplib/RecordsForm::handleSave()
		 */
		protected function handleSave( $formValues ) {
			// Remove all meta data from the details to be saved
			$metaSaveList = array();
			if ( ! empty( $this->meta_element_list ) ) {
				foreach ( $this->meta_element_list as $fieldName ) {
					// Copy new value to our meta save list.
					$metaSaveList[ $fieldName ] = $this->formObj->getArrayValue( $formValues, $fieldName );

					// Remove the meta data from the list about to be saved to the normal form.
					unset( $formValues[ $fieldName ] );
				}
			}

			// Save the normal details to the main table now that it's been cleaned of the metadata
			$recordID = $this->handleSaveMainDetails( $formValues );

			// Got meta data to save? If there's an error with above, we'll handle that there and quit anyway.
			if ( $recordID && ! empty( $metaSaveList ) ) {
				// Check we have a table to save data to.
				if ( ! $this->metaTableName ) {
					$this->messages = $this->showMessage( 'handleSave(): Nothing could be saved, as the meta table name is not valid.', true );

					return false;
				}

				// OK, have a meta table to save to, and normal record saved fine.
				$this->saveMetaData( $metaSaveList, $recordID );
			}
		}

		/**
		 * Method that saves the meta data, updating existing entries, or creating new ones
		 * as appropriate.
		 *
		 * @param Array $metaSaveList The list of meta_keys => meta_values to save to the database for this record.
		 * @param Mixed $recordID The ID of the record in the main table. (Integer recommended, String works fine).
		 */
		protected function saveMetaData( $metaSaveList, $recordID ) {
			if ( empty( $metaSaveList ) || ! $recordID ) {
				return false;
			}

			global $wpdb;
			$wpdb->show_errors();

			// Save each meta value to the meta table.
			foreach ( $metaSaveList as $fieldName => $fieldValue ) {
				// First check if we've got a record for this meta key already
				$SQL = $wpdb->prepare( "
					SELECT * 
					FROM $this->metaTableName
					WHERE $this->metaForeignKey = %s
					  AND meta_key = %s
					LIMIT 1",
					$recordID, $fieldName );

				// Got a row for that already, so UPDATE
				$existingDetails = $wpdb->get_row( $SQL, ARRAY_A );
				if ( ! empty( $existingDetails ) ) {
					$SQL = $wpdb->prepare( "
						UPDATE $this->metaTableName
						SET meta_value = %s
						WHERE meta_key = %s
						 AND $this->metaForeignKey = %s						
						", $fieldValue, $fieldName, $recordID );
				} else {
					$SQL = $wpdb->prepare( "
						INSERT INTO $this->metaTableName (meta_value, meta_key, $this->metaForeignKey)
						VALUES (%s, %s, %s)						
						", $fieldValue, $fieldName, $recordID );
				}

				$wpdb->query( $SQL );
			}
		}
	}
}