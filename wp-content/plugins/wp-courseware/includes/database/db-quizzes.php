<?php
/**
 * WP Courseware DB Quizzes.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.2.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Quizzes.
 *
 * @since 4.3.0
 */
class DB_Quizzes extends DB {

	/**
	 * Quizzes Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'quizzes' );
		$this->primary_key = 'quiz_id';
	}

	/**
	 * Get Columns.
	 *
	 * @since 4.2.0
	 *
	 * @return array The array of columns.
	 */
	public function get_columns() {
		return array(
			'quiz_id'                          => '%d',
			'quiz_title'                       => '%s',
			'quiz_desc'                        => '%s',
			'quiz_author'                      => '%d',
			'parent_unit_id'                   => '%d',
			'parent_course_id'                 => '%d',
			'quiz_type'                        => '%s',
			'quiz_pass_mark'                   => '%d',
			'quiz_show_answers'                => '%s',
			'quiz_show_survey_responses'       => '%s',
			'quiz_attempts_allowed'            => '%d',
			'show_answers_settings'            => '%s',
			'quiz_paginate_questions'          => '%s',
			'quiz_paginate_questions_settings' => '%s',
			'quiz_timer_mode'                  => '%s',
			'quiz_timer_mode_limit'            => '%d',
			'quiz_results_downloadable'        => '%s',
			'quiz_results_by_tag'              => '%s',
			'quiz_results_by_timer'            => '%s',
			'quiz_recommended_score'           => '%s',
			'show_recommended_percentage'      => '%d',
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
			'quiz_title'                       => '',
			'quiz_desc'                        => '',
			'quiz_author'                      => get_current_user_id(),
			'parent_unit_id'                   => 0,
			'parent_course_id'                 => 0,
			'quiz_type'                        => 'quiz_noblock',
			'quiz_pass_mark'                   => 50,
			'quiz_show_answers'                => 'show_answers',
			'quiz_show_survey_responses'       => 'no_responses',
			'quiz_attempts_allowed'            => -1,
			'show_answers_settings'            => array(
				'show_correct_answer'         => 'on',
				'show_user_answer'            => 'on',
				'show_explanation'            => 'on',
				'mark_answers'                => 'on',
				'show_results_later'          => 'on',
				'show_other_possible_answers' => 'off',
			),
			'quiz_paginate_questions'          => 'no_paging',
			'quiz_paginate_questions_settings' => array(
				'allow_review_before_submission' => 'on',
				'allow_students_to_answer_later' => 'on',
				'allow_nav_previous_questions'   => 'on',
			),
			'quiz_timer_mode'                  => 'no_timer',
			'quiz_timer_mode_limit'            => 15,
			'quiz_results_downloadable'        => 'on',
			'quiz_results_by_tag'              => 'on',
			'quiz_results_by_timer'            => 'on',
			'quiz_recommended_score'           => 'no_recommended',
			'show_recommended_percentage'      => 50,
		);
	}

	/**
	 * Get Quizzes.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 *
	 * @return array Array of quizzes.
	 */
	public function get_quizzes( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'quiz_id'          => 0,
			'quiz_author'      => 0,
			'course_id'        => false,
			'unit_id'          => false,
			'parent_course_id' => false,
			'parent_unit_id'   => false,
			'order'            => 'DESC',
			'orderby'          => 'quiz_id',
			'search'           => '',
			'fields'           => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$quizzes = array();

		$quizzes_prefix = 'q';

		$fields  = '';
		$join    = '';
		$where   = '';
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] );
		$wild    = '%';

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'] );
		}

		if ( ! empty( $args['quiz_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['quiz_id'] ) ) {
				$quizzes = implode( ',', array_map( 'intval', $args['quiz_id'] ) );
			} else {
				$quizzes = intval( $args['quiz_id'] );
			}

			$where .= "quiz_id IN( {$quizzes} )";
		}

		if ( false !== $args['course_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_id'] ) ) {
				$courses = implode( ',', array_map( 'intval', $args['course_id'] ) );
			} else {
				$courses = intval( $args['course_id'] );
			}

			$where .= "parent_course_id IN( {$courses} )";
		}

		if ( false !== $args['parent_course_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['parent_course_id'] ) ) {
				$courses = implode( ',', array_map( 'intval', $args['parent_course_id'] ) );
			} else {
				$courses = intval( $args['parent_course_id'] );
			}

			$where .= "parent_course_id IN( {$courses} )";
		}

		if ( false !== $args['unit_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['unit_id'] ) ) {
				$units = implode( ',', array_map( 'intval', $args['unit_id'] ) );
			} else {
				$units = intval( $args['unit_id'] );
			}

			$where .= "parent_unit_id IN( {$units} )";
		}

		if ( false !== $args['parent_unit_id'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['parent_unit_id'] ) ) {
				$units = implode( ',', array_map( 'intval', $args['parent_unit_id'] ) );
			} else {
				$units = intval( $args['parent_unit_id'] );
			}

			$where .= "parent_unit_id IN( {$units} )";
		}

		if ( ! empty( $args['quiz_author'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$quiz_author = intval( $args['quiz_author'] );

			$where .= "quiz_author = {$quiz_author}";
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "quiz_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "quiz_title LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = 'quiz_id';
				break;

			case 'title' :
				$orderby = 'quiz_title';
				break;

			case 'course_id' :
				$orderby = 'parent_course_id';
				break;

			case 'unit_id' :
				$orderby = 'parent_unit_id';
				break;

			case 'course' :
				$course_table = $wpdb->prefix . 'wpcw_courses';
				$orderby      = 'ct.course_title';
				$join         = " INNER JOIN {$course_table} ct ON parent_course_id = ct.course_id";
				break;

			case 'unit' :
				$units_table = $wpdb->posts;
				$orderby     = 'ut.post_title';
				$join        = " INNER JOIN {$units_table} ut ON parent_unit_id = ut.ID";
				break;

			case 'type' :
				$orderby = 'quiz_type';
				break;

			case 'quiz_author' :
				$orderby = 'u.user_login';
				$join    = " INNER JOIN {$wpdb->users} u ON quiz_author = u.ID";
				break;

			default :
				$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? "{$args['orderby']}" : "{$this->primary_key}";
				break;
		}

		$args['orderby'] = $orderby;
		$args['order']   = $order;

		$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

		$results = $this->get_results( $clauses, $args );

		return $results;
	}

	/**
	 * Get Quiz Course Title.
	 *
	 * @since 4.2.0
	 *
	 * @param int $course_id The course id.
	 *
	 * @return string $course_title The course title.
	 */
	public function get_quiz_course_title( $course_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wpcw_courses';

		$title = $wpdb->get_var( $wpdb->prepare( "SELECT course_title FROM {$table} WHERE course_id = %d;", $course_id ) );

		return $title;
	}

	/**
	 * Get Quiz Unit Title.
	 *
	 * @since 4.2.0
	 *
	 * @param int $course_id The unit id.
	 *
	 * @return string $course_title The course title.
	 */
	public function get_quiz_unit_title( $unit_id ) {
		return get_the_title( $unit_id );
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
					} elseif ( 'quiz_desc' == $key ) {
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