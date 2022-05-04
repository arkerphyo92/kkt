<?php
/**
 * WP Courseware Backwards Compatible Functions.
 *
 * @package WPCW
 * @subpackage Functions
 * @since 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Returns the correct SQL to INSERT the specified values as columnname => data into the specified
 * table, escaping all of the data values.
 *
 * @since 1.0.0
 *
 * @param string $tablename The name of the table to insert into.
 * @param array $dataarray The list of values as columnname => data.
 *
 * @return string Valid SQL to allow the specified values to be safely INSERTed into the database.
 */
if ( ! function_exists( 'arrayToSQLInsert' ) ) {
	function arrayToSQLInsert( $tablename, $dataarray ) {
		global $wpdb;

		if ( ! $tablename || ! $dataarray || count( $dataarray ) == 0 ) {
			return false;
		}

		$SQL = "INSERT INTO $tablename (";

		// Insert Column Names
		$columnnames = array_keys( $dataarray );
		foreach ( $columnnames AS $column ) {
			$SQL .= sprintf( '`%s`, ', $column );
		}

		// Remove last comma to maintain valid SQL
		if ( substr( $SQL, -2 ) == ', ' ) {
			$SQL = substr( $SQL, 0, strlen( $SQL ) - 2 );
		}

		$SQL .= ") VALUES (";

		// Now add values, escaping them all
		foreach ( $dataarray AS $columnname => $datavalue ) {
			if ( is_array( $datavalue ) ) {
				$datavalue = serialize( $datavalue );
			}

			$SQL .= $wpdb->prepare( "%s, ", $datavalue );
		}

		// Remove last comma to maintain valid SQL
		if ( substr( $SQL, -2 ) == ', ' ) {
			$SQL = substr( $SQL, 0, strlen( $SQL ) - 2 );
		}

		return $SQL . ")";
	}
}

/**
 * Returns the correctly formed SQL to UPDATE the specified values in the database
 * using the <code>$wherecolumn</code> field to determine which field is used as part
 * of the WHERE clause of the SQL statement. The fields and data are specified in an
 * array mapping columnname => data.
 *
 * @since 1.0.0
 *
 * @param string $tablename The name of the table to UPDATE.
 * @param array $dataarray The list of values as columnname => data.
 * @param string $wherecolumn The column to use in the WHERE clause.
 *
 * @return string Valid SQL to allow the specified values to be safely UPDATEed in the database.
 */
if ( ! function_exists( 'arrayToSQLUpdate' ) ) {
	function arrayToSQLUpdate( $tablename, $dataarray, $wherecolumn ) {
		global $wpdb;

		// Handle dodgy data
		if ( ! $tablename || ! $dataarray || ! $wherecolumn || count( $dataarray ) == 0 ) {
			return false;
		}

		$SQL = "UPDATE $tablename SET ";

		// Now add values, escaping them all
		foreach ( $dataarray AS $columnname => $datavalue ) {
			// Do all fields except column we're using on the WHERE part
			if ( $columnname != $wherecolumn ) {
				// Serialise arrays before saving.
				if ( is_array( $datavalue ) ) {
					$datavalue = maybe_serialize( $datavalue );
				}

				$SQL .= $wpdb->prepare( "`$columnname` = %s, ", $datavalue );
			}
		}

		// Remove last comma to maintain valid SQL
		if ( substr( $SQL, -2 ) == ', ' ) {
			$SQL = substr( $SQL, 0, strlen( $SQL ) - 2 );
		}

		// Now add the WHERE clause
		// Have we got more than 1 item to add to WHERE clause?
		if ( is_array( $wherecolumn ) ) {
			// Create list of fields/values in the WHERE clause
			$WHERE = '';
			for ( $i = 0; $i < count( $wherecolumn ); $i++ ) {
				$WHERE .= $wpdb->prepare( "`$wherecolumn[$i]` = %s AND ", $dataarray[ $wherecolumn[ $i ] ] );
			}

			// Always going to have a final AND, so strip that off now
			$WHERE = substr( $WHERE, 0, -4 );
			$SQL   .= " WHERE " . $WHERE;
		} else {
			$SQL .= $wpdb->prepare( " WHERE `$wherecolumn` = %s", $dataarray[ $wherecolumn ] );
		}

		return $SQL;
	}
}

/**
 * Does a record exist for the specified table? Handles multiple fields for $field and $value
 * if $field and $value are arrays.
 *
 * @since 1.0.0
 *
 * @param String $table The name of the table to check.
 * @param String $field The field of the table to use as part of the search.
 * @param String $value The value of the field to use as part of the search.
 *
 * @return Mixed The row of data if this row is found.
 */
if ( ! function_exists( 'doesRecordExistAlready' ) ) {
	function doesRecordExistAlready( $table, $field, $value ) {
		return getRecordDetails( $table, $field, $value );
	}
}

/**
 * Get all of the details for the specified record.
 *
 * @since 1.0.0
 *
 * @param string $table The name of the table to check.
 * @param string $field The field of the table to use as part of the search.
 * @param string $value The value of the field to use as part of the search.
 * @param string $returnType The type of data to return (ARRAY_A or OBJECT)
 *
 * @return mixed The row of data if this row is found.
 */
if ( ! function_exists( 'getRecordDetails' ) ) {
	function getRecordDetails( $table, $field, $value, $returnType = OBJECT ) {
		global $wpdb;
		$wpdb->show_errors();

		// We've got 2 arrays, so make sure we have the right number of elements.
		if ( is_array( $field ) && is_array( $value ) ) {
			if ( count( $field ) != count( $value ) ) {
				die( 'Error! Mismatched field count for checking record exists.' );
			}

			// Create list of fields/values in the WHERE clause
			$WHERE = '';
			for ( $i = 0; $i < count( $field ); $i++ ) {
				$WHERE .= $wpdb->prepare( "`$field[$i]` = %s AND ", $value[ $i ] );
			}

			// Always going to have a final AND, so strip that off now
			$WHERE = substr( $WHERE, 0, -4 );
			$SQL   = sprintf( "SELECT * FROM %s WHERE %s", $table, $WHERE );
		} else {
			$SQL = $wpdb->prepare( "SELECT * FROM $table WHERE `$field` = %s", $value );
		}

		$rowToReturn = $wpdb->get_row( $SQL, $returnType );

		// Nothing to return.
		if ( ! $rowToReturn ) {
			return false;
		}

		// Dealing with an object to unserialise.
		if ( OBJECT == $returnType ) {
			$rowToReturnNew = new stdClass();

			// Need to create a new object with unserialised data for this to work.
			foreach ( $rowToReturn as $field => $value ) {
				// Unserialise each property into the new object.
				$rowToReturnNew->$field = maybe_unserialize( $value );
			}

			$rowToReturn = $rowToReturnNew;
		} else {
			// Unserialise each field
			foreach ( $rowToReturn as $field => $value ) {
				$rowToReturn[ $field ] = maybe_unserialize( $value );
			}
		}

		return $rowToReturn;
	}
}

/**
 * Save the settings to the WordPress settings table.
 *
 * @since 1.0.0
 *
 * @param array $settingDetails The list of settings to be saved.
 * @param string $settingPrefix The string key used to save the array of settings.
 *
 * @return bool True if the settings were saved, false otherwise.
 */
function WPCW_TidySettings_saveSettings( $settingDetails, $settingPrefix ) {
	if ( ! ( $settingDetails && is_array( $settingDetails ) ) ) {
		error_log( 'SettingsForm: Settings details are null or not in an array.' );
		return false;
	}

	if ( ! $settingPrefix ) {
		error_log( 'SettingsForm: Settings Prefix is empty, so nothing can be saved.' );
		return false;
	}

	// Convert array to string for saving, then store in settings table.
	update_option( $settingPrefix, serialize( $settingDetails ) );

	return true;
}

/**
 * Get all of the settings as an array.
 *
 * @since 1.0.0
 *
 * @param string $settingPrefix The string key used to store the array of settings.
 *
 * @return string The list of settings as an associative array.
 */
function WPCW_TidySettings_getSettings( $settingPrefix ) {
	$rawSettings = get_option( $settingPrefix );
	if ( $rawSettings ) {
		// Sometimes data is not serialised yet
		if ( is_array( $rawSettings ) ) {
			return $rawSettings;
		}

		return unserialize( $rawSettings );
	}

	return false;
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
 */
function WPCW_TidySettings_getSettingSingle( $settingPrefix, $settingName, $defaultValue = false ) {
	$settings = WPCW_TidySettings_getSettings( $settingPrefix );
	if ( isset( $settings[ $settingName ] ) ) {
		return $settings[ $settingName ];
	} else {
		return $defaultValue;
	}
}

if ( ! function_exists( 'array_column' ) ) {
	/**
	 * Returns the values from a single column of the input array, identified by
	 * the $columnKey.
	 *
	 * Optionally, you may provide an $indexKey to index the values in the returned
	 * array by the values from the $indexKey column in the input array.
	 *
	 * @param array $input A multi-dimensional array (record set) from which to pull
	 *                     a column of values.
	 * @param mixed $columnKey The column of values to return. This value may be the
	 *                         integer key of the column you wish to retrieve, or it
	 *                         may be the string key name for an associative array.
	 * @param mixed $indexKey (Optional.) The column to use as the index/keys for
	 *                        the returned array. This value may be the integer key
	 *                        of the column, or it may be the string key name.
	 *
	 * @return array
	 */
	function array_column( $input = null, $columnKey = null, $indexKey = null ) {
		// Using func_get_args() in order to check for proper number of
		// parameters and trigger errors exactly as the built-in array_column()
		// does in PHP 5.5.
		$argc   = func_num_args();
		$params = func_get_args();
		if ( $argc < 2 ) {
			trigger_error( "array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING );
			return null;
		}
		if ( ! is_array( $params[0] ) ) {
			trigger_error(
				'array_column() expects parameter 1 to be array, ' . gettype( $params[0] ) . ' given',
				E_USER_WARNING
			);
			return null;
		}
		if ( ! is_int( $params[1] )
		     && ! is_float( $params[1] )
		     && ! is_string( $params[1] )
		     && $params[1] !== null
		     && ! ( is_object( $params[1] ) && method_exists( $params[1], '__toString' ) )
		) {
			trigger_error( 'array_column(): The column key should be either a string or an integer', E_USER_WARNING );
			return false;
		}
		if ( isset( $params[2] )
		     && ! is_int( $params[2] )
		     && ! is_float( $params[2] )
		     && ! is_string( $params[2] )
		     && ! ( is_object( $params[2] ) && method_exists( $params[2], '__toString' ) )
		) {
			trigger_error( 'array_column(): The index key should be either a string or an integer', E_USER_WARNING );
			return false;
		}
		$paramsInput     = $params[0];
		$paramsColumnKey = ( $params[1] !== null ) ? (string) $params[1] : null;
		$paramsIndexKey  = null;
		if ( isset( $params[2] ) ) {
			if ( is_float( $params[2] ) || is_int( $params[2] ) ) {
				$paramsIndexKey = (int) $params[2];
			} else {
				$paramsIndexKey = (string) $params[2];
			}
		}
		$resultArray = array();
		foreach ( $paramsInput as $row ) {
			$key    = $value = null;
			$keySet = $valueSet = false;
			if ( $paramsIndexKey !== null && array_key_exists( $paramsIndexKey, $row ) ) {
				$keySet = true;
				$key    = (string) $row[ $paramsIndexKey ];
			}
			if ( $paramsColumnKey === null ) {
				$valueSet = true;
				$value    = $row;
			} elseif ( is_array( $row ) && array_key_exists( $paramsColumnKey, $row ) ) {
				$valueSet = true;
				$value    = $row[ $paramsColumnKey ];
			}
			if ( $valueSet ) {
				if ( $keySet ) {
					$resultArray[ $key ] = $value;
				} else {
					$resultArray[] = $value;
				}
			}
		}
		return $resultArray;
	}
}
