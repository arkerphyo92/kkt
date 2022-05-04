<?php
/**
 * WP Courseware DB Abstract Class.
 *
 * All Database classes should inherit this class.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB.
 *
 * @since 4.1.0
 */
abstract class DB {

	/**
	 * @var string DB Table Name.
	 * @since 4.3.0
	 */
	protected $table_name;

	/**
	 * @var string DB Primary Key.
	 * @since 4.1.0
	 */
	protected $primary_key;

	/**
	 * @var string The cache group.
	 * @since 4.1.0
	 */
	protected $cache_group;

	/**
	 * @var string The cache key.
	 * @since 4.1.0
	 */
	protected $cache_key;

	/**
	 * Get Database Table Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array An array of column names.
	 */
	public function get_columns() {
		return array(); /* Override in child class. */
	}

	/**
	 * Get Database Column Defaults.
	 *
	 * @since 4.1.0
	 *
	 * @return array An array of column defaults.
	 */
	public function get_column_defaults() {
		return array(); /* Override in child class. */
	}

	/**
	 * Get Table Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string The table name.
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Get Primary Key.
	 *
	 * @since 4.3.0
	 *
	 * @return string The primary key.
	 */
	public function get_primary_key() {
		return $this->primary_key;
	}

	/**
	 * Get Cache Key.
	 *
	 * @since 4.3.0
	 *
	 * @return string The cache key.
	 */
	public function get_cache_key() {
		return $this->cache_key;
	}

	/**
	 * Get Cache Group.
	 *
	 * @since 4.3.0
	 *
	 * @return string The cache group.
	 */
	public function get_cache_group() {
		return $this->cache_group;
	}

	/**
	 * Get a single item by id.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The primary key id.
	 *
	 * @return array|null|object|void
	 */
	public function get( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE {$this->primary_key} = %s LIMIT 1;", $id ) );
	}

	/**
	 * Get and item by column and id.
	 *
	 * @since 4.4.0
	 *
	 * @param string $column The column name.
	 * @param int|string $column_value The value of the column.
	 *
	 * @return object|false Query result or false on failure.
	 */
	public function get_by( $column, $column_value ) {
		global $wpdb;

		if ( ! array_key_exists( $column, $this->get_columns() ) || empty( $column_value ) ) {
			return false;
		}

		if ( empty( $column ) || empty( $column_value ) ) {
			return false;
		}

		$column = esc_sql( $column );

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE {$column} = '%s' LIMIT 1;", $column_value ) );
	}

	/**
	 * Get an item value based on column name and id.
	 *
	 * @since 4.1.0
	 *
	 * @param string $column The column name.
	 * @param int|string $id The primary key id.
	 *
	 * @return mixed|null Query result or false on failure.
	 */
	public function get_column( $column, $id ) {
		global $wpdb;

		if ( ! array_key_exists( $column, $this->get_columns() ) || empty( $id ) ) {
			return false;
		}

		$column = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare( "SELECT {$column} FROM {$this->table_name} WHERE {$this->primary_key} = %s LIMIT 1;", $id ) );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $column The column name.
	 * @param string $column_where The column where condition.
	 * @param string $column_value The column value.
	 *
	 * @return string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;

		if ( ! array_key_exists( $column, $this->get_columns() ) || empty( $column_where ) || empty( $column_value ) ) {
			return false;
		}

		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare( "SELECT {$column} FROM {$this->table_name} WHERE {$column_where} = %s LIMIT 1;", $column_value ) );
	}

	/**
	 * Get Generic Results.
	 *
	 * @since 4.1.0
	 *
	 * @param array $clauses Compacted array of query clauses.
	 * @param array $args Query arguments.
	 *
	 * @return array|int|null|object Query results.
	 */
	public function get_results( $clauses, $args ) {
		global $wpdb;

		$distinct = ! empty( $clauses['distinct'] ) && true === $clauses['distinct'] ? 'DISTINCT ' : '';
		$groupby  = ! empty( $clauses['groupby'] ) ? " GROUP BY {$clauses['groupby']}" : '';

		if ( $distinct ) {
			if ( ! empty( $clauses['distinct_column'] ) ) {
				$clauses['fields'] = $clauses['distinct_column'];
			} else {
				$clauses['fields'] = $this->primary_key;
			}
		}

		if ( true === $clauses['count'] ) {
			$prepared_sql = "SELECT {$distinct}COUNT( {$this->primary_key} ) FROM {$this->table_name} {$clauses['join']} {$clauses['where']}{$groupby};";

			$results = $wpdb->get_var( $prepared_sql );
		} else {
			$prepared_sql = $wpdb->prepare(
				"SELECT {$distinct}{$clauses['fields']} FROM {$this->table_name} {$clauses['join']} {$clauses['where']}{$groupby} ORDER BY {$clauses['orderby']} {$clauses['order']} LIMIT %d, %d;",
				absint( $args['offset'] ),
				absint( $args['number'] )
			);

			$results = $wpdb->get_results( $prepared_sql );
		}

		return $results;
	}

	/**
	 * Insert a new database row.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The data to be inserted.
	 * @param string $type The type of insert.
	 *
	 * @return int The row id.
	 */
	public function insert( $data, $type = '' ) {
		if ( empty( $data ) ) {
			return false;
		}

		global $wpdb;

		$data = $this->sanitize_columns( $data );

		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'wpcw_db_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Unslash data
		$data = wp_unslash( $data );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );

		$wpdb_insert_id = $wpdb->insert_id;

		do_action( 'wpcw_db_post_insert_' . $type, $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Update a database row.
	 *
	 * @since 4.3.0
	 *
	 * @param int $id The row id to be updated.
	 * @param array $data The data to be updated.
	 *
	 * @return bool True or false if the row was inserted.
	 */
	public function update( $id, $data = array(), $where = '' ) {
		global $wpdb;

		// Row ID must be positive integer
		$id = absint( $id );

		if ( empty( $id ) ) {
			return false;
		}

		if ( empty( $data ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		$data = $this->sanitize_columns( $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $id ), $column_formats ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a row.
	 *
	 * @since 4.3.0
	 *
	 * @param int $id The row id.
	 *
	 * @return  bool
	 */
	public function delete( $id = 0 ) {
		global $wpdb;

		$id = absint( $id );

		if ( empty( $id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table_name} WHERE {$this->primary_key} = %d", $id ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Parses fields into a SQL-friendly format.
	 *
	 * @since 4.1.0
	 *
	 * @param string|array $fields Object fields.
	 * @param string $prefix Optional. Include a prefix for each column name. Useful for joins.
	 * @param array $joined_column Optional. Whitelist the fields of the joined column as well.
	 * @param array $joined_prefix Optional. The joined columns prefix.
	 *
	 * @return string SQL-ready fields list. If empty, default is '*'.
	 */
	public function parse_fields( $fields, $prefix = '', $joined_columns = array(), $joined_prefix = '' ) {
		$fields_sql = '';

		if ( ! is_array( $fields ) ) {
			$fields = array( $fields );
		}

		$count    = count( $fields );
		$columns  = array_keys( $this->get_columns() );
		$jcolumns = ! empty( $joined_columns ) ? array_keys( $joined_columns ) : array();

		foreach ( $fields as $index => $field ) {
			if ( ! in_array( $field, $columns, true ) && ! in_array( $field, $jcolumns ) ) {
				unset( $fields[ $index ] );
				continue;
			}

			if ( in_array( $field, $columns, true ) && $prefix ) {
				$fields[ $index ] = "{$prefix}.{$field}";
			}

			if ( in_array( $field, $jcolumns, true ) && $joined_prefix ) {
				$fields[ $index ] = "{$joined_prefix}.{$field}";
			}
		}

		if ( empty( $fields ) ) {
			foreach ( $columns as $column ) {
				$fields[] = ( $prefix ) ? "{$prefix}.{$column}" : $column;
			}

			if ( ! empty( $jcolumns ) ) {
				foreach ( $jcolumns as $jcolumn ) {
					$fields[] = ( $joined_prefix ) ? "{$joined_prefix}.{$jcolumn}" : $jcolumn;
				}
			}
		}

		$fields_sql = implode( ', ', $fields );

		if ( empty ( $fields_sql ) ) {
			$fields_sql = '*';
		}

		return $fields_sql;
	}

	/**
	 * Sanitize data for create / update.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The data to sanitize.
	 *
	 * @return array The sanitized data, based off column defaults.
	 */
	protected function sanitize_columns( $data ) {
		$columns        = $this->get_columns();
		$default_values = $this->get_column_defaults();

		foreach ( $columns as $key => $type ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch ( $type ) {
				case '%s':
					if ( 'email' == $key ) {
						$data[ $key ] = sanitize_email( $data[ $key ] );
					} elseif ( 'notes' == $key ) {
						$data[ $key ] = strip_tags( $data[ $key ] );
					} elseif ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
						$data[ $key ] = maybe_serialize( $data[ $key ] );
					} else {
						$data[ $key ] = sanitize_text_field( $data[ $key ] );
					}
					break;
				case '%d':
					if ( is_bool( $data[ $key ] ) ) {
						$data[ $key ] = (bool) $data[ $key ];
					} elseif ( ! is_numeric( $data[ $key ] ) || (int) $data[ $key ] !== absint( $data[ $key ] ) ) {
						$data[ $key ] = isset( $default_values[ $key ] ) ? $default_values[ $key ] : null;
					} else {
						$data[ $key ] = absint( $data[ $key ] );
					}
					break;
				case '%f':
					// Convert what was given to a float
					$value = floatval( $data[ $key ] );
					if ( ! is_float( $value ) ) {
						$data[ $key ] = isset( $default_values[ $key ] ) ? $default_values[ $key ] : null;
					} else {
						$data[ $key ] = $value;
					}
					break;

				default:
					$data[ $key ] = sanitize_text_field( $data[ $key ] );
					break;
			}
		}

		return $data;
	}
}
