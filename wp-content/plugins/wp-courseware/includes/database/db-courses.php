<?php
/**
 * WP Courseware DB Courses.
 *
 * @package WPCW
 * @subpackage Database
 * @since 4.3.0
 */
namespace WPCW\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Courses.
 *
 * @since 4.3.0
 */
class DB_Courses extends DB {

	/**
	 * Courses Database Constructor.
	 *
	 * Intiate the table name, version, and primary key.
	 *
	 * @since 4.1.0
	 */
	public function __construct() {
		// Define Table Name and Primary Key
		$this->table_name  = wpcw()->database->get_table_name( 'courses' );
		$this->primary_key = 'course_id';
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
			'course_id'                                   => '%d',
			'course_post_id'                              => '%d',
			'course_title'                                => '%s',
			'course_desc'                                 => '%s',
			'course_author'                               => '%d',
			'course_opt_completion_wall'                  => '%s',
			'course_opt_use_certificate'                  => '%s',
			'course_opt_user_access'                      => '%s',
			'course_unit_count'                           => '%d',
			'course_from_name'                            => '%s',
			'course_from_email'                           => '%s',
			'course_to_email'                             => '%s',
			'course_opt_prerequisites'                    => '%s',
			'course_message_unit_complete'                => '%s',
			'course_message_course_complete'              => '%s',
			'course_message_unit_not_logged_in'           => '%s',
			'course_message_unit_pending'                 => '%s',
			'course_message_unit_no_access'               => '%s',
			'course_message_prerequisite_not_met'         => '%s',
			'course_message_unit_not_yet'                 => '%s',
			'course_message_unit_not_yet_dripfeed'        => '%s',
			'course_message_quiz_open_grading_blocking'   => '%s',
			'course_message_quiz_open_grading_non_blocking' => '%s',
			'email_complete_module_option_admin'          => '%s',
			'email_complete_module_option'                => '%s',
			'email_complete_module_subject'               => '%s',
			'email_complete_module_body'                  => '%s',
			'email_complete_course_option_admin'          => '%s',
			'email_complete_course_option'                => '%s',
			'email_complete_course_subject'               => '%s',
			'email_complete_course_body'                  => '%s',
			'email_quiz_grade_option'                     => '%s',
			'email_quiz_grade_subject'                    => '%s',
			'email_quiz_grade_body'                       => '%s',
			'email_complete_course_grade_summary_subject' => '%s',
			'email_complete_course_grade_summary_body'    => '%s',
			'email_complete_unit_option_admin'            => '%s',
			'email_complete_unit_option'                  => '%s',
			'email_complete_unit_subject'                 => '%s',
			'email_complete_unit_body'                    => '%s',
			'email_unit_unlocked_subject'                 => '%s',
			'email_unit_unlocked_body'                    => '%s',
			'cert_signature_type'                         => '%s',
			'cert_sig_text'                               => '%s',
			'cert_sig_image_url'                          => '%s',
			'cert_logo_enabled'                           => '%s',
			'cert_logo_url'                               => '%s',
			'cert_background_type'                        => '%s',
			'cert_background_custom_url'                  => '%s',
			'payments_type'                               => '%s',
			'payments_price'                              => '%s',
			'payments_interval'                           => '%s',
			'course_bundles'                              => '%s',
			'installments_enabled'                        => '%s',
			'installments_number'                         => '%d',
			'installments_amount'                         => '%s',
			'installments_interval'                       => '%s',
			'unit_advancement'                            => '%s',
			'course_access_expiration'                    => '%s',
			'cae_specific_date'                           => '%s',
			'cae_interval_date_int_expire'                => '%d',
			'cae_interval_date_string_expire'             => '%s',
			'cae_reset_on_course_expire'                  => '%s',
			'certificate_template'                        => '%d',
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
			'course_author'                               => get_current_user_id(),
			'course_unit_count'                           => 0,

			// Add basic Email Template to defaults when creating a new course.
			'email_complete_course_subject'               => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_COURSE_SUBJECT' ),
			'email_complete_module_subject'               => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_MODULE_SUBJECT' ),
			'email_complete_unit_subject'                 => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_SUBJECT' ),
			'email_quiz_grade_subject'                    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_QUIZ_GRADE_SUBJECT' ),
			'email_complete_course_grade_summary_subject' => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_SUBJECT' ),
			'email_unit_unlocked_subject'                 => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_UNIT_UNLOCKED_SUBJECT' ),

			// Email bodies
			'email_complete_course_body'                  => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_COURSE_BODY' ),
			'email_complete_module_body'                  => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_MODULE_BODY' ),
			'email_complete_unit_body'                    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_BODY' ),
			'email_quiz_grade_body'                       => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_QUIZ_GRADE_BODY' ),
			'email_complete_course_grade_summary_body'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_BODY' ),
			'email_unit_unlocked_body'                    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_UNIT_UNLOCKED_BODY' ),

			// Email address details
			'course_from_name'                            => wpcw_course_get_default_email_from_name(),
			'course_from_email'                           => wpcw_course_get_default_email_from_email(),
			'course_to_email'                             => wpcw_course_get_default_email_to_email(),

			// Completion wall default (blocking mode)
			'course_opt_completion_wall'                  => 'completion_wall',
			'course_opt_user_access'                      => 'default_show',

			// Email notification defaults (yes to send email)
			'email_complete_course_option_admin'          => 'send_email',
			'email_complete_course_option'                => 'send_email',
			'email_complete_module_option_admin'          => 'send_email',
			'email_complete_module_option'                => 'send_email',
			'email_complete_unit_option_admin'            => 'no_email',
			'email_complete_unit_option'                  => 'no_email',
			'email_quiz_grade_option'                     => 'send_email',

			// Certificate defaults
			'course_opt_use_certificate'                  => 'no_certs',
			'cert_signature_type'                         => 'text',
			'cert_sig_text'                               => get_bloginfo( 'name' ),
			'cert_sig_image_url'                          => '',
			'cert_logo_enabled'                           => 'no_cert_logo',
			'cert_logo_url'                               => '',
			'cert_background_type'                        => 'use_default',

			// User Messages
			'course_message_unit_not_yet'                 => __( 'You need to complete the previous unit first.', 'wp-courseware' ),
			'course_message_unit_pending'                 => __( 'Have you completed this unit? Then mark this unit as completed.', 'wp-courseware' ),
			'course_message_unit_complete'                => __( 'You have now completed this unit.', 'wp-courseware' ),
			'course_message_course_complete'              => __( 'You have now completed the whole course. Congratulations!', 'wp-courseware' ),
			'course_message_unit_no_access'               => __( 'Sorry, but you\'re not allowed to access this course.', 'wp-courseware' ),
			'course_message_prerequisite_not_met'         => __( 'This course can not be accessed until the prerequisites for this course are complete.', 'wp-courseware' ),
			'course_message_unit_not_logged_in'           => __( 'You cannot view this unit as you\'re not logged in yet.', 'wp-courseware' ),
			'course_message_unit_not_yet_dripfeed'        => __( 'This unit isn\'t available just yet. Please check back in about {UNIT_UNLOCKED_TIME}.', 'wp-courseware' ),

			// User Messages - quizzes
			'course_message_quiz_open_grading_blocking'   => __( 'Your quiz has been submitted for grading by the course instructor. Once your grade has been entered, you will be able to access the next unit.', 'wp-courseware' ),
			'course_message_quiz_open_grading_non_blocking' => __( 'Your quiz has been submitted for grading by the course instructor. You have now completed this unit.', 'wp-courseware' ),

			// Payments
			'payments_type'                               => 'free',
			'payments_price'                              => '0.00',
			'payments_interval'                           => 'month',

			// Course Bundles
			'course_bundles'                              => '',

			// Installments
			'installments_enabled'                        => 'no',
			'installments_number'                         => 2,
			'installments_amount'                         => '0.00',
			'installments_interval'                       => 'month',

			// Course Post Id.
			'course_post_id'                              => 0,

			// Unit Advancement.
			'unit_advancement'                            => 'manual',

			// Course Access Expiration.
			'course_access_expiration'                    => 'none',
			'cae_specific_date'                           => '0000-00-00 00:00:00',
			'cae_interval_date_int_expire'                => 1,
			'cae_interval_date_string_expire'             => 'hour',
			'cae_reset_on_course_expire'                  => 'no',

			// Select Certificate
			'certificate_template'                        => 0,
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

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
			 LEFT JOIN {$wpdb->posts} on {$this->table_name}.course_post_id = {$wpdb->posts}.ID
			 WHERE {$this->primary_key} = %s LIMIT 1;",
				$id
			)
		);
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

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.course_post_id = {$wpdb->posts}.ID
			 WHERE {$column} = '%s' LIMIT 1;",
				$column_value
			)
		);
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

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$column} FROM {$this->table_name}
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.course_post_id = {$wpdb->posts}.ID
			 WHERE {$this->primary_key} = %s LIMIT 1;",
				$id
			)
		);
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

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$column} FROM {$this->table_name}
			 INNER JOIN {$wpdb->posts} on {$this->table_name}.course_post_id = {$wpdb->posts}.ID
			 WHERE {$column_where} = %s LIMIT 1;",
				$column_value
			)
		);
	}

	/**
	 * Get Courses.
	 *
	 * @since 4.1.0
	 *
	 * @param array $args An array of query arguments.
	 * @param bool  $count Optional. Return only the total number of results.
	 *
	 * @return array Array of courses.
	 */
	public function get_courses( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'         => 20,
			'offset'         => 0,
			'course_id'      => 0,
			'course_post_id' => 0,
			'course_author'  => '',
			'course_status'  => 'publish',
			'status'         => '',
			'order'          => 'DESC',
			'orderby'        => 'date',
			'search'         => '',
			'fields'         => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$courses = array();

		$courses_prefix = 'c';
		$posts_prefix   = 'p';

		$fields  = '';
		$join    = '';
		$where   = '';
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] );
		$wild    = '%';

		if ( 'ids' === $args['fields'] ) {
			$fields = "{$this->primary_key}";
		} else {
			$fields = $this->parse_fields( $args['fields'], $courses_prefix, $this->get_post_columns(), $posts_prefix );
		}

		$join = "{$courses_prefix} LEFT JOIN {$wpdb->posts} p ON {$courses_prefix}.course_post_id = p.ID";

		if ( ! empty( $args['course_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_id'] ) ) {
				$course_ids = implode( ',', array_map( 'intval', $args['course_id'] ) );
			} else {
				$course_ids = intval( $args['course_id'] );
			}

			$where .= "{$courses_prefix}.course_id IN( {$course_ids} )";
		}

		if ( ! empty( $args['course_post_id'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			if ( is_array( $args['course_post_id'] ) ) {
				$course_post_ids = implode( ',', array_map( 'intval', $args['course_post_id'] ) );
			} else {
				$course_post_ids = intval( $args['course_post_id'] );
			}

			$where .= "{$posts_prefix}.ID IN( {$course_post_ids} )";
		}

		if ( ! empty( $args['course_author'] ) ) {
			$where .= empty( $where ) ? ' WHERE ' : ' AND ';

			$course_author = intval( $args['course_author'] );

			$where .= "{$courses_prefix}.course_author = {$course_author}";
		}

		if ( ! empty( $args['status'] ) ) {
			$args['course_status'] = $args['status'];
		}

		if ( ! empty( $args['course_status'] ) ) {
			if ( is_array( $args['course_status'] ) ) {
				$where        .= empty( $where ) ? ' WHERE ' : ' AND ';
				$course_status = implode( "','", array_map( 'esc_attr', $args['course_status'] ) );
				$where        .= "{$posts_prefix}.post_status IN( '{$course_status}' )";
			} else {
				$course_status = esc_attr( $args['course_status'] );
				if ( 'all' === $course_status ) {
					$where .= empty( $where ) ? ' WHERE ' : ' AND ';
					$where .= "{$posts_prefix}.post_status != 'auto-draft'";
				}
				if ( 'all' !== $course_status ) {
					$course_status = explode( ',', $course_status );
					$course_status = implode( "','", array_map( 'esc_attr', $course_status ) );
					$where        .= empty( $where ) ? ' WHERE ' : ' AND ';
					$where        .= "{$posts_prefix}.post_status IN( '{$course_status}' )";
				}
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$search_value = $args['search'];

			if ( is_numeric( $search_value ) ) {
				$search = $wpdb->prepare( "{$courses_prefix}.course_id IN( %s )", $search_value );
			} elseif ( is_string( $search_value ) ) {
				$search_value = $wild . $wpdb->esc_like( stripslashes( $search_value ) ) . $wild;
				$search       = $wpdb->prepare( "{$courses_prefix}.course_title LIKE %s", $search_value );
			}

			if ( ! empty( $search ) ) {
				$where .= empty( $where ) ? ' WHERE ' : ' AND ';
				$where .= $search;
			}
		}

		switch ( $args['orderby'] ) {
			case 'course_id':
			case 'id':
				$orderby = "{$courses_prefix}.course_id";
				break;

			case 'course_post_id':
				$orderby = "{$posts_prefix}.ID";
				break;

			case 'course_title':
			case 'title':
				$orderby = "{$courses_prefix}.course_title";
				break;

			case 'course_date':
			case 'post_date':
			case 'date':
				$orderby = "{$posts_prefix}.post_date";
				break;

			default:
				if ( is_array( $args['orderby'] ) ) {
					$orderbys = array();
					foreach ( $args['orderby'] as $ordercondition ) {
						if ( array_key_exists( $ordercondition, $this->get_columns() ) ) {
							$orderbys[] = "{$courses_prefix}.{$ordercondition}";
						}
						if ( array_key_exists( $ordercondition, $this->get_post_columns() ) ) {
							$orderbys[] = "{$posts_prefix}.{$ordercondition}";
						}
					}
					$orderby = ! empty( $orderbys ) ? implode( ',', $orderbys ) : "{$courses_prefix}.{$this->primary_key}";
				} elseif ( array_key_exists( $args['orderby'], $this->get_post_columns() ) ) {
					$orderby = "{$posts_prefix}.{$args['orderby']}";
				} elseif ( array_key_exists( $args['orderby'], $this->get_columns() ) ) {
					$orderby = "{$courses_prefix}.{$args['orderby']}";
				} else {
					$orderby = "{$courses_prefix}.{$this->primary_key}";
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
	 * Insert Course.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The courose data.
	 *
	 * @return int|bool The course id or false if an error occurred.
	 */
	public function insert_course( $data = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		return $this->insert( $data, 'course' );
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
					} elseif ( 'course_desc' == $key || ( strpos( $key, 'email_' ) !== false ) || ( strpos( $key, 'course_message_' ) !== false ) ) {
						$data[ $key ] = wp_kses_post( $data[ $key ] );
					} elseif ( is_array( $data[ $key ] ) ) {
						$data[ $key ] = maybe_serialize( $data[ $key ] );
					} else {
						$data[ $key ] = sanitize_text_field( $data[ $key ] );
					}
					break;
				case '%d':
					if ( is_bool( $data[ $key ] ) ) {
						$data[ $key ] = (bool) $data[ $key ];
					} elseif ( ! is_numeric( $data[ $key ] ) || (int) $data[ $key ] !== absint( $data[ $key ] ) ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = absint( $data[ $key ] );
					}
					break;
				case '%f':
					// Convert what was given to a float
					$value = floatval( $data[ $key ] );
					if ( ! is_float( $value ) ) {
						$data[ $key ] = $default_values[ $key ];
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
