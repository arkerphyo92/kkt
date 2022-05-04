<?php
/**
 * WP Courseware DB Questions.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.2.0
 */

namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Questions.
 *
 * @since 4.2.0
 */
class DB_Questions extends DB {

	/**
	 * Questions Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'quizzes_questions' );
		$this->primary_key = 'question_id';
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
			'question_id'                      => '%d',
			'question_author'                  => '%d',
			'question_type'                    => '%s',
			'question_question'                => '%s',
			'question_answers'                 => '%s',
			'question_data_answers'            => '%s',
			'question_correct_answer'          => '%s',
			'question_answer_type'             => '%s',
			'question_answer_hint'             => '%s',
			'question_answer_explanation'      => '%s',
			'question_image'                   => '%s',
			'question_answer_file_types'       => '%s',
			'question_usage_count'             => '%d',
			'question_expanded_count'          => '%d',
			'question_multi_random_enable'     => '%d',
			'question_multi_random_count'      => '%d',
			'question_multi_checkboxes_enable' => '%d',
		);
	}

	/**
	 * Get Questions.
	 *
	 * @since 4.2.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool $count Optional. Return only the total number of results.
	 *
	 * @return array Array of modules.
	 */
	public function get_questions( $args = array(), $count = false, $join_courses = true ) {
		global $wpdb;

		$defaults = array(
			'number'           => 20,
			'offset'           => 0,
			'question_id'      => 0,
			'question_author'  => 0,
			'question_tag'     => '',
			'order'            => 'DESC',
			'orderby'          => 'question_id',
			'search'           => '',
			'fields'           => '',
			'random_selection' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$questions = array();

		$quesitons_prefix = 'qt';

		$fields  = '';
		$join    = '';
		$where   = '';
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] );
		$wild    = '%';

		if ( 'ids' === $args['fields'] ) {
			$fields = "$quesitons_prefix.{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'], $quesitons_prefix );
		}

		if ( ! empty( $args['question_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['question_id'] ) ) {
				$questions = implode( ',', array_map( 'intval', $args['question_id'] ) );
			} else {
				$questions = intval( $args['question_id'] );
			}

			$where .= "{$quesitons_prefix}.question_id IN( {$questions} )";
		}

		if ( ! empty( $args['question_author'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$question_author = intval( $args['question_author'] );

			$where .= "{$quesitons_prefix}.question_author = {$question_author}";
		}

		if ( ! empty( $args['question_tag'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$tags_mapping_table = $wpdb->prefix . 'wpcw_question_tags_map';

			$join = " LEFT JOIN {$tags_mapping_table} qtm ON qtm.question_id = {$quesitons_prefix}.question_id";

			$where .= $wpdb->prepare( "qtm.tag_id = %d", $args['question_tag'] );
			$where .= " AND {$quesitons_prefix}.question_question IS NOT NULL";
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "{$quesitons_prefix}.question_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "{$quesitons_prefix}.question_question LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		if ( ! $args['random_selection'] ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';
			$where .= "{$quesitons_prefix}.question_type != 'random_selection' ";
		}

		switch ( $args['orderby'] ) {
			case 'id' :
				$orderby = "{$quesitons_prefix}.question_id";
				break;

			case 'title' :
				$orderby = "{$quesitons_prefix}.question_question";
				break;

			case 'type' :
				$orderby = "{$quesitons_prefix}.question_type";
				break;

			case 'question_author' :
				$orderby = 'u.user_login';
				$join    = " INNER JOIN {$wpdb->users} u ON {$quesitons_prefix}.question_author = u.ID";
				break;

			default :
				$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? "{$quesitons_prefix}.{$args['orderby']}" : "{$quesitons_prefix}.{$this->primary_key}";
				break;
		}

		$args['orderby'] = $orderby;
		$args['order']   = $order;

		if ( $count ) {
			$prepared_sql = "SELECT COUNT( {$quesitons_prefix}.{$this->primary_key} ) FROM {$this->table_name} qt {$join} {$where};";

			$results = $wpdb->get_var( $prepared_sql );
		} else {
			$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

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
				$prepared_sql = "SELECT {$distinct}COUNT( {$this->primary_key} ) FROM {$this->table_name} qt {$clauses['join']} {$clauses['where']}{$groupby};";

				$results = $wpdb->get_var( $prepared_sql );
			} else {
				$prepared_sql = $wpdb->prepare(
					"SELECT {$distinct}{$clauses['fields']} FROM {$this->table_name} qt {$clauses['join']} {$clauses['where']}{$groupby} ORDER BY {$clauses['orderby']} {$clauses['order']} LIMIT %d, %d;",
					absint( $args['offset'] ),
					absint( $args['number'] )
				);

				$results = $wpdb->get_results( $prepared_sql );
			}
		}

		return $results;
	}

	/**
	 * Get Question Tags.
	 *
	 * @since 4.2.0
	 *
	 * @param int $id The Question Id.
	 */
	public function get_question_tags( $id ) {
		global $wpdb;

		$tags_table         = $wpdb->prefix . 'wpcw_question_tags';
		$tags_mapping_table = $wpdb->prefix . 'wpcw_question_tags_map';

		return $wpdb->get_results( $wpdb->prepare( "
			SELECT qt.* FROM {$tags_mapping_table} qtm
			LEFT JOIN {$tags_table} qt ON qtm.tag_id = qt.question_tag_id
			WHERE question_id = %d
			ORDER BY question_tag_name ASC
		", $id ) );
	}
}
