<?php
/**
 * WP Courseware DB Units.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.4.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Units.
 *
 * @since 4.4.0
 */
class DB_Units extends DB {

	/**
	 * Units Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.4.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'units' );
		$this->primary_key = 'unit_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'unit_id'                 => '%d',
			'parent_module_id'        => '%d',
			'parent_course_id'        => '%d',
			'unit_author'             => '%d',
			'unit_order'              => '%d',
			'unit_number'             => '%d',
			'unit_drip_type'          => '%s',
			'unit_drip_date'          => '%s',
			'unit_drip_interval'      => '%d',
			'unit_drip_interval_type' => '%s',
			'unit_teaser'             => '%d',
		);
	}

	/**
	 * Get Post Columns.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of post columns.
	 */
	public function get_post_columns() {
		return array(
			'ID'                    => '%d',
			'post_author'           => '%d',
			'post_date'             => '%s',
			'post_date_gmt'         => '%s',
			'post_content'          => '%s',
			'post_title'            => '%s',
			'post_excerpt'          => '%s',
			'post_status'           => '%s',
			'comment_status'        => '%s',
			'ping_status'           => '%s',
			'post_password'         => '%s',
			'post_name'             => '%s',
			'to_ping'               => '%s',
			'pinged'                => '%s',
			'post_modified'         => '%s',
			'post_modified_gmt'     => '%s',
			'post_content_filtered' => '%s',
			'post_parent'           => '%s',
			'guid'                  => '%s',
			'menu_order'            => '%d',
			'post_type'             => '%s',
			'post_mime_type'        => '%s',
			'comment_count'         => '%d',
		);
	}

	/**
	 * Get Column Defaults.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'parent_module_id'        => 0,
			'parent_course_id'        => 0,
			'unit_author'             => ( is_user_logged_in() && current_user_can( 'manage_wpcw_settings' ) ) ? get_current_user_id() : 0,
			'unit_order'              => 0,
			'unit_number'             => 0,
			'unit_drip_type'          => '',
			'unit_drip_date'          => '0000-00-00 00:00:00',
			'unit_drip_interval'      => 432000,
			'unit_drip_interval_type' => 'interval_days',
			'unit_teaser'             => 0,
		);
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

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.{$this->primary_key} = {$wpdb->posts}.ID 
			 WHERE {$this->primary_key} = %s LIMIT 1;",
			$id
		) );
	}

	/**
	 * Get and item by column and id.
	 *
	 * @since 4.4.0
	 *
	 * @param string     $column The column name.
	 * @param int|string $column_value The value of the column.
	 *
	 * @return object|false Query result or false on failure.
	 */
	public function get_by( $column, $column_value ) {
		global $wpdb;

		if ( ( ! array_key_exists( $column, $this->get_columns() ) && ! array_key_exists( $column, $this->get_post_columns() ) ) || empty( $column_value ) ) {
			return false;
		}

		if ( empty( $column ) || empty( $column_value ) ) {
			return false;
		}

		$column = esc_sql( $column );

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->table_name} 
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.{$this->primary_key} = {$wpdb->posts}.ID 
			 WHERE {$column} = '%s' LIMIT 1;",
			$column_value
		) );
	}

	/**
	 * Get an item value based on column name and id.
	 *
	 * @since 4.1.0
	 *
	 * @param string     $column The column name.
	 * @param int|string $id The primary key id.
	 *
	 * @return mixed|null Query result or false on failure.
	 */
	public function get_column( $column, $id ) {
		global $wpdb;

		if ( ( ! array_key_exists( $column, $this->get_columns() ) && ! array_key_exists( $column, $this->get_post_columns() ) ) || empty( $id ) ) {
			return false;
		}

		$column = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT {$column} FROM {$this->table_name} 
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.{$this->primary_key} = {$wpdb->posts}.ID 
			 WHERE {$this->primary_key} = %s LIMIT 1;",
			$id
		) );
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

		if ( ( ! array_key_exists( $column, $this->get_columns() ) && ! array_key_exists( $column, $this->get_post_columns() ) ) || empty( $column_where ) || empty( $column_value ) ) {
			return false;
		}

		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT {$column} FROM {$this->table_name} 
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.{$this->primary_key} = {$wpdb->posts}.ID 
			 WHERE {$column_where} = %s LIMIT 1;",
			$column_value
		) );
	}

	/**
	 * Get Units.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args The query args.
	 * @param bool  $count The count flag.
	 *
	 * @return array The array of units.
	 */
	public function get_units( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'                  => 20,
			'offset'                  => 0,
			'unit_id'                 => 0,
			'unit_author'             => 0,
			'unit_order'              => 0,
			'unit_number'             => 0,
			'course_id'               => false,
			'module_id'               => false,
			'parent_course_id'        => false,
			'parent_module_id'        => false,
			'unit_drip_type'          => '',
			'unit_drip_date'          => '',
			'unit_drip_interval'      => '',
			'unit_drip_interval_type' => '',
			'unit_status'             => 'publish',
			'status'                  => '',
			'order'                   => 'ASC',
			'orderby'                 => 'unit_order',
			'search'                  => '',
			'fields'                  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$units = array();

		$units_prefix = 'u';
		$posts_prefix = 'p';

		$fields  = '';
		$where   = '';
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] );
		$wild    = '%';

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'], $units_prefix, $this->get_post_columns(), $posts_prefix );
		}

		$join = "{$units_prefix} INNER JOIN {$wpdb->posts} p ON unit_id = p.ID";

		if ( ! empty( $args['unit_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['unit_id'] ) ) {
				$unit_ids = implode( ',', array_map( 'intval', $args['unit_id'] ) );
			} else {
				$unit_ids = intval( $args['unit_id'] );
			}

			$where .= "{$units_prefix}.unit_id IN( {$unit_ids} )";
		}

		if ( false !== $args['course_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_id'] ) ) {
				$course_ids = implode( ',', array_map( 'intval', $args['course_id'] ) );
			} else {
				$course_ids = intval( $args['course_id'] );
			}

			$where .= "{$units_prefix}.parent_course_id IN( {$course_ids} )";
		}

		if ( false !== $args['parent_course_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['parent_course_id'] ) ) {
				$course_ids = implode( ',', array_map( 'intval', $args['parent_course_id'] ) );
			} else {
				$course_ids = intval( $args['parent_course_id'] );
			}

			$where .= "{$units_prefix}.parent_course_id IN( {$course_ids} )";
		}

		if ( false !== $args['module_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['module_id'] ) ) {
				$module_ids = implode( ',', array_map( 'intval', $args['module_id'] ) );
			} else {
				$module_ids = intval( $args['module_id'] );
			}

			$where .= "{$units_prefix}.parent_module_id IN( {$module_ids} )";
		}

		if ( false !== $args['parent_module_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['parent_module_id'] ) ) {
				$module_ids = implode( ',', array_map( 'intval', $args['parent_module_id'] ) );
			} else {
				$module_ids = intval( $args['parent_module_id'] );
			}

			$where .= "{$units_prefix}.parent_module_id IN( {$module_ids} )";
		}

		if ( ! empty( $args['unit_author'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$unit_author = intval( $args['unit_author'] );

			$where .= "{$units_prefix}.unit_author = {$unit_author}";
		}

		if ( ! empty( $args['status'] ) ) {
			$args['unit_status'] = $args['status'];
		}

		if ( ! empty( $args['unit_status'] ) ) {
			if ( is_array( $args['unit_status'] ) ) {
				$where       .= empty( $where ) ? ' WHERE ' : ' AND ';
				$unit_status = implode( "','", array_map( 'esc_attr', $args['unit_status'] ) );
				$where       .= "{$posts_prefix}.post_status IN( '{$unit_status}' )";
			} else {
				$unit_status = esc_attr( $args['unit_status'] );
				if ( 'all' !== $unit_status ) {
					$unit_status = explode( ',', $unit_status );
					$unit_status = implode( "','", array_map( 'esc_attr', $unit_status ) );
					$where       .= empty( $where ) ? ' WHERE ' : ' AND ';
					$where       .= "{$posts_prefix}.post_status IN( '{$unit_status}' )";
				}
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
			$search       = $wpdb->prepare( "{$posts_prefix}.post_title LIKE %s", $search_value );

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = "{$units_prefix}.unit_id";
				break;

			case 'title' :
				$orderby = "{$posts_prefix}.post_title";
				break;

			case 'unit_author' :
				$orderby = "{$units_prefix}.unit_author";
				break;

			case 'unit_order' :
				$orderby = "{$units_prefix}.unit_order";
				break;

			case 'unit_number' :
				$orderby = "{$units_prefix}.unit_number";
				break;

			case 'date' :
				$orderby = "{$posts_prefix}.post_date";
				break;

			default :
				if ( is_array( $args['orderby'] ) ) {
					$orderbys = array();
					foreach ( $args['orderby'] as $ordercondition ) {
						if ( array_key_exists( $ordercondition, $this->get_columns() ) ) {
							$orderbys[] = "{$units_prefix}.{$ordercondition}";
						}
						if ( array_key_exists( $ordercondition, $this->get_post_columns() ) ) {
							$orderbys[] = "{$posts_prefix}.{$ordercondition}";
						}
					}
					$orderby = ! empty( $orderbys ) ? implode( ',', $orderbys ) : "{$units_prefix}.{$this->primary_key}";
				} elseif ( array_key_exists( $args['orderby'], $this->get_post_columns() ) ) {
					$orderby = "{$posts_prefix}.{$args['orderby']}";
				} elseif ( array_key_exists( $args['orderby'], $this->get_columns() ) ) {
					$orderby = "{$units_prefix}.{$args['orderby']}";
				} else {
					$orderby = "{$units_prefix}.{$this->primary_key}";
				}
				break;
		}

		$args['orderby'] = $orderby;
		$args['order']   = $order;

		$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

		$results = $this->get_results( $clauses, $args );

		return $results;
	}

	/**
	 * Insert a new unit row.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $data The data to be inserted.
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

		$wpdb_insert = $wpdb->insert( $this->table_name, $data, $column_formats );

		if ( $wpdb_insert > 0 && ! empty( $data['unit_id'] ) ) {
			$wpdb_insert_id = absint( $data['unit_id'] );
		}

		do_action( 'wpcw_db_post_insert_' . $type, $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}
}
