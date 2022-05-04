<?php
/**
 * WP Courseware Database.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WPCW\Database\Tables\DB_Table;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB.
 *
 * @since 4.3.0
 */
final class Database {

	/**
	 * @var array The array of created tables.
	 * @since 4.3.0
	 */
	private $tables;

	/**
	 * @var array The array of table names.
	 * @since 4.6.3
	 */
	private $table_names;

	/**
	 * Database constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->setup_tables();
		$this->setup_table_names();
		$this->back_compat();
	}

	/**
	 * Load Database.
	 *
	 * @since 4.6.3
	 */
	public function load() {
		add_action( 'switch_blog', array( $this, 'switch_blog' ) );
	}

	/**
	 * Multisite Compatability.
	 *
	 * Hooked to the "switch_blog" action.
	 *
	 * @since 4.6.3
	 */
	public function switch_blog() {
		$this->setup_table_names();
		$this->back_compat();
	}

	/**
	 * Setup Database Tables.
	 *
	 * @since 4.3.0
	 */
	private function setup_tables() {
		foreach ( $this->get_tables() as $table_key => $table_class_name ) {
			$table_class = "\\WPCW\\Database\\Tables\\$table_class_name";
			if ( class_exists( $table_class ) ) {
				$this->tables[ $table_key ] = new $table_class;
			}
		}
	}

	/**
	 * Setup Table Names.
	 *
	 * @since 4.6.3
	 */
	private function setup_table_names() {
		global $wpdb;

		if ( ! empty( $this->tables ) ) {
			foreach ( $this->tables as $key => $table ) {
				if ( $table instanceof DB_Table ) {
					$name = $table->get_name();
					if ( property_exists( $wpdb, $name ) ) {
						$this->table_names[ $key ] = $wpdb->{$name};
					}
				}
			}
		}
	}

	/**
	 * Fix Tables.
	 *
	 * @since 4.4.4
	 */
	public function fix_tables() {
		if ( ! empty( $this->tables ) ) {
			/** @var DB_Table $table */
			foreach ( $this->tables as $table ) {
				$table->maybe_fix();
			}
		}
	}

	/**
	 * Fix erroneous backslashes in email bodies as part of the "Fix Database" action
	 *
	 * @since 4.6.10
	 */
	public function fix_email_backslashes() {
		global $wpcwdb, $wpdb;
		
		$sql = "SELECT * FROM " . $wpcwdb->courses;
		$results = $wpdb->get_results( $sql );

		if( is_array( $results ) ) {
			foreach( $results as $index => $result ) {				
				foreach( $result as $key => $value ) {

					if( is_string( $key ) && (1 === preg_match( "/^email_/", $key ) || 1 === preg_match( "/^course_message_/", $key )) ) {
						if( is_string( $value ) && 1 === preg_match( "/\\\\{2,}/", $value ) ) {

							$new_value = stripslashes( $value );
							$new_value = stripslashes( preg_replace( "/\\\\{2,}/", "", $new_value ) );

							$sql = $wpdb->prepare( "
							UPDATE " . $wpcwdb->courses . " 
							SET " . $key . " = %s
							WHERE course_id = %d",
							$new_value, $result->course_id );

							$wpdb->query( $sql );
						}
					}
				}
			}
		}
	}

	/**
	 * Fix database records in which installment payments were incorrectly marked as having a discount applied
	 *
	 * @since 4.6.8
	 * @return int The number of orders that were fixed
	 */
	public function fix_discounts() {
		global $wpcwdb;
		global $wpdb;

		$sql = "
		SELECT	order_id, order_parent_id, subscription_id, subtotal, discounts, tax, total
		FROM	" . $wpcwdb->orders . "
		WHERE	order_parent_id > 0 AND subscription_id > 0 AND discounts > 0
		";
		$orders = $wpdb->get_results( $sql );
		$return_value = (is_array( $orders )) ? count( $orders ) : 0;

		// If we have matching orders, fix them
		if( is_array( $orders ) && count( $orders ) > 0 ) {
			foreach( $orders as $order )
			{
				$order_id = (int) $order->order_id;
				$subtotal = $order->subtotal;
				$discounts = $order->discounts;
				$tax = $order->tax;
				$total = $order->total;

				// Only fix this entry if the total is not already equal to (subtotal + tax)
				if( $total != ($subtotal + $tax) )
				{
					$discounts = 0;
					$total = $subtotal + $tax;

					$sql = $wpdb->prepare( "
					UPDATE	" . $wpcwdb->orders . "
					SET		discounts = %d,
							total = %01.2f
					WHERE	order_id = %d",
					$discounts, $total, $order_id );
					$wpdb->query( $sql );
				}
			}
		}

		// Do the same for incorrect tax amount display
		$sql = "
		SELECT	order_id, order_parent_id, subscription_id, subtotal, discounts, tax, total
		FROM	" . $wpcwdb->orders . "
		WHERE	order_parent_id > 0 AND subscription_id > 0 AND tax > 0
		";
		$tax_orders = $wpdb->get_results( $sql );
		// $return_value += (is_array( $tax_orders )) ? count( $tax_orders ) : 0;

		if( is_array( $tax_orders ) && count( $tax_orders ) > 0 ) {
			foreach( $tax_orders as $order )
			{
				$order_id = (int) $order->order_id;
				$subtotal = $order->subtotal;
				$discounts = $order->discounts;
				$tax = $order->tax;
				$total = $order->total;

				// Only fix this entry if the total is not already equal to (subtotal + tax + discounts)
				if( $total != ($subtotal + $tax + $discounts) )
				{
					$total = $subtotal + $tax + $discounts;

					$sql = $wpdb->prepare( "
					UPDATE	" . $wpcwdb->orders . "
					SET		total = %01.2f
					WHERE	order_id = %d",
					$total, $order_id );
					$wpdb->query( $sql );
					$return_value++;
				}
			}
		}

		return $return_value;
	}

	/**
	 * Database Backwards Compat.
	 *
	 * @since 4.3.0
	 */
	private function back_compat() {
		global $wpcwdb;

		// old_reference => new_reference
		$map = array(
			'courses'              => 'courses',
			'coursemeta'           => 'coursemeta',
			'modules'              => 'modules',
			'units_meta'           => 'units',
			'user_courses'         => 'user_courses',
			'user_progress'        => 'user_progress',
			'user_progress_quiz'   => 'user_progress_quizzes',
			'quiz'                 => 'quizzes',
			'quiz_feedback'        => 'quizzes_feedback',
			'quiz_qs'              => 'quizzes_questions',
			'quiz_qs_mapping'      => 'quizzes_questions_map',
			'question_tags'        => 'question_tags',
			'question_tag_mapping' => 'question_tags_map',
			'question_rand_lock'   => 'question_random_lock',
			'map_member_levels'    => 'member_levels',
			'certificates'         => 'certificates',
			'queue_dripfeed'       => 'queue_dripfeed',
			'orders'               => 'orders',
			'ordermeta'            => 'ordermeta',
			'order_items'          => 'order_items',
			'order_itemmeta'       => 'order_itemmeta',
			'subscriptions'        => 'subscriptions',
			'coupons'              => 'coupons',
			'couponmeta'           => 'couponmeta',
			'logs'                 => 'logs',
			'notes'                => 'notes',
			'sessions'             => 'sessions',
		);

		foreach ( $map as $old_reference => $new_reference ) {
			if ( property_exists( $wpcwdb, $old_reference ) ) {
				$wpcwdb->{$old_reference} = $this->get_table_name( $new_reference );
			}
		}
	}

	/**
	 * Get Database Tables.
	 *
	 * @since 4.3.0
	 *
	 * @return array The defined table names and their classes.
	 */
	private function get_tables() {
		return array(
			'certificates'          => 'DB_Table_Certificates',
			'coursemeta'            => 'DB_Table_Course_Meta',
			'courses'               => 'DB_Table_Courses',
			'couponmeta'            => 'DB_Table_Coupon_Meta',
			'coupons'               => 'DB_Table_Coupons',
			'logs'                  => 'DB_Table_Logs',
			'member_levels'         => 'DB_Table_Member_Levels',
			'modules'               => 'DB_Table_Modules',
			'notes'                 => 'DB_Table_Notes',
			'order_itemmeta'        => 'DB_Table_Order_Item_Meta',
			'order_items'           => 'DB_Table_Order_Items',
			'ordermeta'             => 'DB_Table_Order_Meta',
			'orders'                => 'DB_Table_Orders',
			'question_random_lock'  => 'DB_Table_Question_Random_Lock',
			'question_tags'         => 'DB_Table_Question_Tags',
			'question_tags_map'     => 'DB_Table_Question_Tags_Map',
			'queue_dripfeed'        => 'DB_Table_Queue_Dripfeed',
			'quizzes'               => 'DB_Table_Quizzes',
			'quizzes_feedback'      => 'DB_Table_Quizzes_Feedback',
			'quizzes_questions'     => 'DB_Table_Quizzes_Questions',
			'quizzes_questions_map' => 'DB_Table_Quizzes_Questions_Map',
			'subscriptions'         => 'DB_Table_Subscriptions',
			'sessions'              => 'DB_Table_Sessions',
			'units'                 => 'DB_Table_Units',
			'user_courses'          => 'DB_Table_User_Courses',
			'user_progress'         => 'DB_Table_User_Progress',
			'user_progress_quizzes' => 'DB_Table_User_Progress_Quizzes',
		);
	}

	/**
	 * Get Database Table Names.
	 *
	 * @since 4.6.3
	 *
	 * @return array The array of database table names.
	 */
	public function get_table_names() {
		return $this->table_names;
	}

	/**
	 * Get Database Table Name.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The database table key.
	 *
	 * @return string $table_name The database table name.
	 */
	public function get_table_name( $key = '' ) {
		return isset( $this->table_names[ $key ] ) ? $this->table_names[ $key ] : '';
	}

	/**
	 * Drop Tables.
	 *
	 * @since 4.4.0
	 */
	public function drop_tables() {
		global $wpdb;

		foreach ( $this->get_tables() as $table_key => $table_class_name ) {
			if ( $table_name = $this->get_table_name( $table_key ) ) {
				$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
			}
		}
	}
}
