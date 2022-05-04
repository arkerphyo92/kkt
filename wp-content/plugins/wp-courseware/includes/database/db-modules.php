<?php
/**
 * WP Courseware DB Modules.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.1.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Modules.
 *
 * @since 4.1.0
 */
class DB_Modules extends DB {

	/**
	 * Modules Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.1.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'modules' );
		$this->primary_key = 'module_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'module_id'        => '%d',
			'parent_course_id' => '%d',
			'module_author'    => '%d',
			'module_title'     => '%s',
			'module_desc'      => '%s',
			'module_order'     => '%s',
			'module_number'    => '%s',
		);
	}

	/**
	 * Get Column Defaults.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'parent_course_id' => 0,
			'module_author'    => 0,
			'module_title'     => '',
			'module_desc'      => '',
			'module_order'     => 10000,
			'module_number'    => 0,
		);
	}

	/**
	 * Get Modules.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 *
	 * @return array Array of modules.
	 */
	public function get_modules( $args = array(), $count = false, $join_courses = true ) {
		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'course_id'        => false,
			'parent_course_id' => false,
			'module_id'        => 0,
			'module_author'    => 0,
			'order'            => 'DESC',
			'orderby'          => array( 'module_order', 'module_title' ),
			'search'           => '',
			'fields'           => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$modules = array();

		$modules_prefix = 'm';
		$courses_prefix = 'c';

		$course_table = wpcw()->database->get_table_name( 'courses' );

		$fields  = '';
		$join    = '';
		$where   = '';
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] );
		$wild    = '%';

		$join = "{$modules_prefix}";

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$modules_prefix}.{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'], $modules_prefix );
		}

		if ( $join_courses ) {
			$join .= " INNER JOIN {$course_table} {$courses_prefix} ON {$modules_prefix}.parent_course_id = {$courses_prefix}.course_id";

			if ( 'ids' == $args['fields'] ) {
				$fields .= ", {$courses_prefix}.course_id";
			} else {
				$fields .= ", {$courses_prefix}.course_id, {$courses_prefix}.course_title";
			}
		}

		if ( false !== $args['course_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_id'] ) ) {
				$course_ids = implode( ',', array_map( 'intval', $args['course_id'] ) );
			} else {
				$course_ids = intval( $args['course_id'] );
			}

			$where .= "{$modules_prefix}.parent_course_id IN( {$course_ids} )";
		}

		if ( false !== $args['parent_course_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['parent_course_id'] ) ) {
				$course_ids = implode( ',', array_map( 'intval', $args['parent_course_id'] ) );
			} else {
				$course_ids = intval( $args['parent_course_id'] );
			}

			$where .= "{$modules_prefix}.parent_course_id IN( {$course_ids} )";
		}

		if ( ! empty( $args['module_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['module_id'] ) ) {
				$modules = implode( ',', array_map( 'intval', $args['module_id'] ) );
			} else {
				$modules = intval( $args['module_id'] );
			}

			$where .= "{$modules_prefix}.module_id IN( {$modules} )";
		}

		if ( ! empty( $args['module_author'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$module_author = intval( $args['module_author'] );

			$where .= "{$modules_prefix}.module_author = {$module_author}";
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "{$modules_prefix}.module_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "{$modules_prefix}.module_title LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = "{$modules_prefix}.module_id";
				break;

			case 'title' :
				$orderby = "{$modules_prefix}.module_title";
				break;

			case 'course_id' :
				$orderby = "{$courses_prefix}.course_id";
				break;

			case 'course' :
				if ( $join_courses ) {
					$orderby = "{$courses_prefix}.course_title";
				} else {
					$orderby = "{$courses_prefix}.course_id";
				}
				break;

			case 'module_author' :
				$orderby = 'u.user_login';
				$join    = " INNER JOIN {$wpdb->users} u ON {$modules_prefix}.module_author = u.ID";
				break;

			default :
				if ( is_array( $args['orderby'] ) ) {
					$orderbys = array();
					foreach ( $args['orderby'] as $ordercondition ) {
						if ( array_key_exists( $ordercondition, $this->get_columns() ) ) {
							$orderbys[] = "{$modules_prefix}.{$ordercondition}";
						}
					}
					$orderby = ! empty( $orderbys ) ? implode( ', ', $orderbys ) : "{$modules_prefix}.{$this->primary_key}";
				} else {
					$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? "{$modules_prefix}.{$args['orderby']}" : "{$modules_prefix}.{$this->primary_key}";
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
	 * Get Module Course Title.
	 *
	 * @since 4.1.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string $course_title The course title.
	 */
	public function get_module_course_title( $course_id ) {
		global $wpdb;

		$courses_table = wpcw()->database->get_table_name( 'courses' );

		$title = $wpdb->get_var( $wpdb->prepare( "SELECT course_title FROM {$courses_table} WHERE course_id = %d;", $course_id ) );

		return $title;
	}

	/**
	 * Get Module Course Post Id.
	 *
	 * @since 4.1.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string $course_title The course title.
	 */
	public function get_module_course_post_id( $course_id ) {
		global $wpdb;

		$courses_table = wpcw()->database->get_table_name( 'courses' );

		$title = $wpdb->get_var( $wpdb->prepare( "SELECT course_post_id FROM {$courses_table} WHERE course_id = %d;", $course_id ) );

		return $title;
	}

	/**
	 * Sanitize data for create / update.
	 *
	 * @since 4.4.0
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
					} elseif ( 'module_desc' == $key ) {
						$data[ $key ] = wp_kses_post( $data[ $key ] );
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