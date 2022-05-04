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
 * Class DB_Students.
 *
 * @since 4.1.0
 */
class DB_Students extends DB {

	/**
	 * @var string Users Table Name.
	 * @since 4.1.0
	 */
	protected $users_table_name;

	/**
	 * @var string User Meta Table Name.
	 * @since 4.5.0
	 */
	protected $usermeta_table_name;

	/**
	 * Students Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.1.0
	 */
	public function __construct() {
		global $wpdb;

		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'user_courses' );
		$this->primary_key = 'user_id';

		// Attach the users table to join
		$this->users_table_name    = $wpdb->users;
		$this->usermeta_table_name = $wpdb->usermeta;
	}

	/**
	 * Get Students Database Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'user_id'                 => '%d',
			'course_id'               => '%d',
			'course_progress'         => '%d',
			'course_final_grade_sent' => '%s',
			'course_enrolment_date'   => '%s',
		);
	}

	/**
	 * Get User Columns.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	public function get_user_columns() {
		return array(
			'ID'                  => '%d',
			'user_login'          => '%s',
			'user_pass'           => '%s',
			'user_nicename'       => '%s',
			'user_email'          => '%s',
			'user_url'            => '%s',
			'user_registered'     => '%s',
			'user_activation_key' => '%s',
			'user_status'         => '%d',
			'display_name'        => '%s',
		);
	}

	/**
	 * Get Student.
	 *
	 * @since 4.1.0
	 *
	 * @param int $id The user id.
	 *
	 * @return array The student data.
	 */
	public function get( $id ) {
		if ( 0 === absint( $id ) ) {
			return false;
		}

		$students = $this->get_students( array( 'number' => 1, 'user_id' => $id ) );

		if ( empty( $students ) ) {
			return false;
		}

		return current( $students );
	}

	/**
	 * Get Students.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 * @param bool $join_users Default is true. Join the users table.
	 *
	 * @return array Array of students.
	 */
	public function get_students( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'                => 20,
			'offset'                => 0,
			'user_id'               => 0,
			'course_id'             => 0,
			'course_enrolment_date' => '',
			'progress'              => '',
			'progress_compare'      => '=',
			'name'                  => '',
			'first_name'            => '',
			'last_name'             => '',
			'email'                 => '',
			'search'                => '',
			'start_date'            => '',
			'end_date'              => '',
			'date_compare'          => '=',
			'date_column'           => 'course_enrolment_date',
			'order'                 => 'DESC',
			'orderby'               => 'ID',
			'fields'                => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$users_table    = $this->users_table_name;
		$usermeta_table = $this->usermeta_table_name;
		$courses_table  = $this->table_name;

		$subquery         = '';
		$subquery_where   = '';
		$query            = '';
		$query_where      = '';
		$needs_subquery   = empty( $args['course_id'] ) ? true : false;
		$orderby          = $args['orderby'];
		$order            = strtoupper( $args['order'] );
		$number           = absint( $args['number'] );
		$offset           = absint( $args['offset'] );
		$wild             = '%';
		$progress_compare = ! empty( $args['progress_compare'] ) ? $args['progress_compare'] : '=';
		$date_compare     = ! empty( $args['date_compare'] ) ? $args['date_compare'] : '=';
		$date_column      = ! empty( $args['date_column'] ) ? esc_attr( $args['date_column'] ) : 'date_created';
		$join_usermeta    = false;
		$ids_only         = false;

		if ( ! empty( $args['fields'] ) && 'ids' === $args['fields'] ) {
			$ids_only = true;
		}

		if ( ! empty( $args['course_id'] ) ) {
			$query_where .= empty( $query_where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_id'] ) ) {
				$course_ids  = implode( ',', array_map( 'intval', $args['course_id'] ) );
				$query_where .= "uc.course_id IN( {$course_ids} ) ";
			} else {
				$course_ids  = intval( $args['course_id'] );
				$query_where .= "uc.course_id = {$course_ids}";
			}
		}

		if ( ! empty( $args['user_id'] ) ) {
			$query_where .= empty( $query_where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['user_id'] ) ) {
				$students    = implode( ',', array_map( 'intval', $args['user_id'] ) );
				$query_where .= "u.ID IN( {$students} )";
			} else {
				$students    = intval( $args['user_id'] );
				$query_where .= "u.ID = {$students}";
			}
		}

		if ( ! empty( $args['name'] ) ) {
			$query_where .= empty( $query_where ) ? ' WHERE ' : ' AND ';
			$name_value  = $wild . $wpdb->esc_like( stripslashes( $args['name'] ) ) . $wild;
			$query_where .= $wpdb->prepare( "u.display_name LIKE %s", $name_value );
		}

		if ( ! empty( $args['first_name'] ) ) {
			$query_where   .= empty( $query_where ) ? ' WHERE ' : ' AND ';
			$join_usermeta = true;
			$name_value    = $wild . $wpdb->esc_like( stripslashes( $args['first_name'] ) ) . $wild;
			$query_where   .= $wpdb->prepare( "( um.meta_key = 'first_name' AND um.meta_value LIKE %s )", $name_value );
		}

		if ( ! empty( $args['last_name'] ) ) {
			$query_where   .= empty( $query_where ) ? ' WHERE ' : ' AND ';
			$join_usermeta = true;
			$name_value    = $wild . $wpdb->esc_like( stripslashes( $args['last_name'] ) ) . $wild;
			$query_where   .= $wpdb->prepare( "( um.meta_key = 'last_name' AND um.meta_value LIKE %s )", $name_value );
		}

		if ( ! empty( $args['email'] ) ) {
			$query_where .= empty( $query_where ) ? ' WHERE ' : ' AND ';
			$email_value = $wild . $wpdb->esc_like( stripslashes( $args['email'] ) ) . $wild;
			$query_where .= $wpdb->prepare( "u.user_email LIKE %s", $email_value );
		}

		if ( ! empty( $args['search'] ) ) {
			$join_usermeta = true;
			$search_value  = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "u.ID IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "u.display_name LIKE %s", $search_value );
				$search       .= $wpdb->prepare( " OR u.user_email LIKE %s", $search_value );
				$search       .= $wpdb->prepare( " OR u.user_login LIKE %s", $search_value );
				$search       .= $wpdb->prepare( " OR ( um.meta_key IN ('first_name', 'last_name') AND um.meta_value LIKE %s )", $search_value );
			}

			if ( ! empty( $search ) ) {
				$query_where .= empty( $query_where ) ? ' WHERE ' : ' AND ';
				$query_where .= '(' . $search . ')';
			}
		}

		if ( ! empty( $args['course_enrolment_date'] ) ) {
			$subquery_where .= ' AND ';

			$course_enrolment_date = esc_attr( $args['course_enrolment_date'] );
			$course_enrolment_date = date( 'Y-m-d H:i:s', strtotime( $course_enrolment_date ) );

			$subquery_where .= "uc.course_enrolment_date {$date_compare} '{$course_enrolment_date}'";
		}

		if ( ! empty( $args['progress'] ) ) {
			$subquery_where .= ' AND ';

			$course_progress = esc_attr( $args['progress'] );

			$subquery_where .= "uc.course_progress {$progress_compare} '{$course_progress}'";
		}

		if ( ! empty( $args['start_date'] ) ) {
			$subquery_where .= ' AND ';

			$start_date = esc_attr( $args['start_date'] );
			$start_date = date( 'Y-m-d H:i:s', $start_date );

			$subquery_where .= "uc.{$date_column} >= '{$start_date}'";
		}

		if ( ! empty( $args['end_date'] ) ) {
			$subquery_where .= ' AND ';

			$end_date = esc_attr( $args['end_date'] );
			$end_date = date( 'Y-m-d H:i:s', $end_date );

			$subquery_where .= "uc.{$date_column} <= '{$end_date}'";
		}

		switch ( $args['orderby'] ) {
			case 'id' :
			case 'user_id':
				$orderby = "u.ID";
				break;

			case 'name' :
				$orderby = "u.display_name";
				break;

			case 'first_name' :
			case 'last_name' :
				$orderby = "um.meta_value";
				break;

			case 'progress' :
				$orderby = "uc.course_progress";
				break;

			case 'enrollment_date' :
			case 'enrollment' :
				$orderby = "uc.course_enrollment_date";
				break;

			default :
				$orderby = array_key_exists( $args['orderby'], $this->get_user_columns() ) ? "u.{$args['orderby']}" : "u.ID";
				break;
		}

		$subquery = "SELECT 1 FROM {$this->table_name} uc WHERE uc.user_id = u.ID{$subquery_where}";

		if ( $join_usermeta ) {
			if ( $count ) {
				if ( $needs_subquery ) {
					$query_where = empty( $query_where ) ? ' WHERE ' : $query_where . ' AND ';

					$query = "SELECT COUNT(DISTINCT u.ID) FROM {$users_table} AS u
					  	      INNER JOIN {$usermeta_table} AS um ON u.ID = um.user_id
					  	      {$query_where}EXISTS( {$subquery} )";
				} else {
					$query = "SELECT COUNT(DISTINCT u.ID) FROM {$users_table} AS u
					  	      INNER JOIN {$usermeta_table} AS um ON u.ID = um.user_id
					  	      INNER JOIN {$this->table_name} AS uc ON u.ID = uc.user_id
					  	      {$query_where}";
				}

				return $wpdb->get_var( $query );
			}

			$select_clause = $ids_only ? 'u.ID' : 'u.*';

			if ( $needs_subquery ) {
				$query_where = empty( $query_where ) ? ' WHERE ' : $query_where . ' AND ';

				$query = "SELECT DISTINCT {$select_clause} FROM {$users_table} AS u
					      INNER JOIN {$usermeta_table} AS um ON u.ID = um.user_id
					      {$query_where}EXISTS( {$subquery} )
					      ORDER BY {$orderby} {$order}
					      LIMIT {$offset}, {$number}";
			} else {
				$query = "SELECT DISTINCT {$select_clause} FROM {$users_table} AS u
					      INNER JOIN {$usermeta_table} AS um ON u.ID = um.user_id
					      INNER JOIN {$this->table_name} AS uc ON u.ID = uc.user_id
					      {$query_where}
					      ORDER BY {$orderby} {$order}
					      LIMIT {$offset}, {$number}";
			}
		} else {
			if ( $count ) {
				if ( $needs_subquery ) {
					$query_where = empty( $query_where ) ? ' WHERE ' : $query_where . ' AND ';

					$query = "SELECT COUNT(u.ID) FROM {$users_table} AS u
						      {$query_where}EXISTS( {$subquery} )";
				} else {
					$query = "SELECT COUNT(u.ID) FROM {$users_table} AS u
							  INNER JOIN {$this->table_name} AS uc ON u.ID = uc.user_id
						      {$query_where}";
				}

				return $wpdb->get_var( $query );
			}

			$select_clause = $ids_only ? 'u.ID' : '*';

			if ( $needs_subquery ) {
				$query_where = empty( $query_where ) ? ' WHERE ' : $query_where . ' AND ';

				$query = "SELECT {$select_clause} FROM {$users_table} AS u
					      {$query_where}EXISTS( {$subquery} )
					      ORDER BY {$orderby} {$order}
					      LIMIT {$offset}, {$number}";
			} else {
				$query = "SELECT {$select_clause} FROM {$users_table} AS u
						  INNER JOIN {$this->table_name} AS uc ON u.ID = uc.user_id
					      {$query_where}
					      ORDER BY {$orderby} {$order}
					      LIMIT {$offset}, {$number}";
			}
		}

		return $wpdb->get_results( $query, $ids_only ? ARRAY_A : OBJECT );
	}
}
