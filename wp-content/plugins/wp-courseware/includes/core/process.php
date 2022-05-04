<?php
/**
 * WP Courseware Background Process.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use stdClass;
use WPCW\Library\Background_Process;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Process.
 *
 * @since 4.4.0
 */
class Process extends Background_Process {

	/**
	 * Process constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param string $action The action prefix.
	 */
	public function __construct( $action = 'wpcw_background_process', $prefix = 'wpcw' ) {
		$this->prefix = $prefix;
		$this->action = $action;

		parent::__construct();
	}

	/**
	 * Get batch
	 *
	 * @since 4.4.0
	 *
	 * @return stdClass Return the first batch from the queue
	 */
	protected function get_batch() {
		global $wpdb;

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		$query = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE {$column} LIKE %s
			ORDER BY {$key_column} ASC
			LIMIT 1
		", $key ) );

		$batch       = new stdClass();
		$batch->key  = $query->$column;
		$batch->data = array_filter( (array) maybe_unserialize( $query->$value_column ) );

		return $batch;
	}

	/**
	 * Schedule cron healthcheck
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $schedules Schedules.
	 *
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {
		$interval = apply_filters( $this->identifier . '_cron_interval', 5 );

		if ( property_exists( $this, 'cron_interval' ) ) {
			$interval = apply_filters( $this->identifier . '_cron_interval', $this->cron_interval );
		}

		// Adds every 5 minutes to the existing schedules.
		$schedules[ $this->identifier . '_cron_interval' ] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			/* translators: %d: interval */
			'display'  => sprintf( __( 'Every %d Minutes', 'wp-courseware' ), $interval ),
		);

		return $schedules;
	}

	/**
	 * Delete all batches.
	 *
	 * @since 4.4.0
	 *
	 * @return Process The process object.
	 */
	public function delete_all_batches() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) ); // @codingStandardsIgnoreLine.

		return $this;
	}

	/**
	 * Kill process.
	 *
	 * Stop processing queue items, clear cronjob and delete all batches.
	 *
	 * @since 4.4.0
	 */
	public function kill_process() {
		if ( ! $this->is_queue_empty() ) {
			$this->delete_all_batches();
			wp_clear_scheduled_hook( $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater running?
	 *
	 * @since 4.4.3
	 *
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $callback Process Callback Function.
	 *
	 * @return mixed
	 */
	protected function task( $callback ) {
		wpcw_maybe_define_constant( 'WPCW_PROCESSING', $this->action );

		$result = false;

		if ( is_array( $callback ) ) {
			$callback_string = sprintf( '%s->%s', get_class( $callback[0] ), $callback[1] );
		} else {
			$callback_string = $callback;
		}

		if ( is_callable( $callback ) ) {
			$this->log( sprintf( 'Running %s callback', $callback_string ) );
			$result = (bool) call_user_func( $callback );
			if ( $result ) {
				$this->log( sprintf( '%s callback needs to run again', $callback_string ) );
			} else {
				$this->log( sprintf( 'Finished running %s callback', $callback_string ) );
			}
		} else {
			$this->log( sprintf( 'Could not find %s callback', $callback_string ) );
		}

		return $result ? $callback_string : false;
	}

	/**
	 * Complete.
	 *
	 * @since 4.4.3
	 */
	protected function complete() {
		if ( 'wpcw_upgrader' === $this->action ) {
			$this->log( 'Updating Complete' );
			wpcw()->install->update_db_version();
		} else {
			$this->log( 'Processing Complete' );
		}

		parent::complete();
	}

	/**
	 * Log Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The log message.
	 */
	public function log( $message = '' ) {
		if ( empty( $message ) || ! defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {
			return;
		}

		$log_entry = "\n" . '====Start ' . get_called_class() . ' Log====' . "\n" . $message . "\n" . '====End ' . get_called_class() . ' Log====' . "\n";

		wpcw_log( $log_entry );
		wpcw_file_log( array( 'message' => $log_entry ) );
	}
}
