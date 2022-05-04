<?php
/**
 * WP Courseware - Admin Notices.
 *
 * @package WPCW
 * @subpackage Admin
 * @since 4.3.0
 */
namespace WPCW\Admin;

/**
 * Class Notices
 *
 * Handles displaying admin notices in WordPress after a page redirect or reload.
 *
 * @since 4.3.0
 */
class Notices implements \Countable, \IteratorAggregate {

	/**
	 * @var array Notice Types.
	 * @since 4.3.0
	 */
	protected $types = array(
		'success',
		'info',
		'warning',
		'error',
	);

	/**
	 * @var array Queue of notices stored on the current page load.
	 * @since 4.3.0
	 */
	protected $notices = [];

	/**
	 * @var string Transient Name.
	 * @since 4.3.0
	 */
	protected $transient;

	/**
	 * Notices constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param string $transient The transient name.
	 */
	public function __construct( $transient ) {
		$this->transient = $transient;
	}

	/**
	 * Add Hooks.
	 *
	 * @since 4.3.0
	 */
	public function add_hooks() {
		add_action( 'admin_notices', array( $this, 'display' ) );
		add_action( 'shutdown', array( $this, 'save' ) );
	}

	/**
	 * Add a Notice to the queue.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The key.
	 * @param string $message The message.
	 * @param string $type The type.
	 */
	public function add( $message, $type = 'info' ) {
		$this->notices[] = [
			'message' => $message,
			'type'    => in_array( $type, $this->types ) ? $type : 'info',
		];
	}

	/**
	 * Purge all notices from queue.
	 *
	 * @since 4.3.0
	 */
	public function purge() {
		$this->notices = [];
	}

	/**
	 * Count number of notices in the queue.
	 *
	 * @since 4.3.0
	 *
	 * @return int The number of notices in the queue.
	 */
	public function count() {
		return count( $this->notices );
	}

	/**
	 * Get array iterator for notices
	 *
	 * @since 4.3.0
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator( $this->notices );
	}

	/**
	 * Set transient.
	 *
	 * @since 4.3.0
	 */
	public function save() {
		if ( $this->count() ) {
			set_transient( $this->transient, $this->notices, 10 );
		}
	}

	/**
	 * Get transient.
	 *
	 * @since 4.3.0
	 */
	public function get() {
		return get_transient( $this->transient );
	}

	/**
	 * Delete transient.
	 *
	 * @since 4.3.0
	 */
	public function delete() {
		delete_transient( $this->transient );
	}

	/**
	 * Display all notices from previous page load.
	 *
	 * @since 4.3.0
	 */
	public function display() {
		$notices = $this->get();

		if ( $notices && is_array( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( isset( $notice['message'], $notice['type'] ) ) {
					wpcw_admin_notice( $notice['message'], $notice['type'] );
				}
			}

			$this->delete();
		}
	}
}