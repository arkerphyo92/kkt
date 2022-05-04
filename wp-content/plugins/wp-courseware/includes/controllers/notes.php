<?php
/**
 * WP Courseware Notes.
 *
 * @package WPCW
 * @subpackage Controllers
 * @since 4.3.0
 */
namespace WPCW\Controllers;

use WPCW\Database\DB_Notes;
use WPCW\Models\Note;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Notes.
 *
 * @since 4.3.0
 */
class Notes extends Controller {

	/**
	 * @var DB_Notes The notes database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Notes Constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->db = new DB_Notes();
	}

	/**
	 * Notes Load.
	 *
	 * @since 4.3.0
	 */
	public function load() { /* Do nothing for now */ }

	/**
	 * Get Notes.
	 *
	 * @param array $args Optional. Valid Query Arguments.
	 * @param bool  $raw Optional. Retrieve the raw database data.
	 *
	 * @return array Array of Note objects.
	 */
	public function get_notes( $args = array(), $raw = false ) {
		$notes   = array();
		$results = $this->db->get_notes( $args );

		if ( $raw ) {
			return $results;
		}

		foreach ( $results as $result ) {
			$notes[] = new Note( $result );
		}

		return $notes;
	}

	/**
	 * Delete Notes.
	 *
	 * @since 4.3.0
	 *
	 * @param array $note_ids The note ids to delete.
	 */
	public function delete_notes( $note_ids = array() ) {
		if ( empty( $note_ids ) ) {
			return false;
		}

		foreach ( $note_ids as $note_id ) {
			$note = new Note( absint( $note_id ) );
			if ( $note->get_id() ) {
				$order->delete();
			}
		}
	}

	/**
	 * Delete Notes by Object Id.
	 *
	 * @since 4.3.0
	 *
	 * @param int $object_id The object id.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_notes_by_object_id( $object_id = 0 ) {
		if ( empty( $object_id ) ) {
			return false;
		}

		return $this->db->delete_notes_by_object_id( $object_id );
	}
}
