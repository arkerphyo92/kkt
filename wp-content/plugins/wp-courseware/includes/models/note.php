<?php
/**
 * WP Courseware Note Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.1.0
 */
namespace WPCW\Models;

use WPCW\Database\DB_Notes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Note.
 *
 * @since 4.1.0
 *
 * @property int $id
 * @property int $object_id
 * @property string $object_type
 * @property int $user_id
 * @property string $content
 * @property string $date_created
 * @property int $is_public
 */
class Note extends Model {

	/**
	 * @var DB_Notes The notes database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var int The Note Id.
	 * @since 4.3.0
	 */
	public $id;

	/**
	 * @var int The Note Object Id.
	 * @since 4.3.0
	 */
	public $object_id;

	/**
	 * @var string The Note Object Type.
	 * @since 4.3.0
	 */
	public $object_type;

	/**
	 * @var int The Note User Id.
	 * @since 4.3.0
	 */
	public $user_id;

	/**
	 * @var string The Note Content.
	 * @since 4.3.0
	 */
	public $content;

	/**
	 * @var string The Note Created Date.
	 * @since 4.3.0
	 */
	public $date_created;

	/**
	 * @var bool Is not public?
	 * @since 4.3.0
	 */
	public $is_public;

	/**
	 * Note Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Notes();
		parent::__construct( $data );
	}

	/**
	 * Create Note.
	 *
	 * @since 4.3.0
	 *
	 * @param int $object_id Optional. The object id.
	 * @param string $object_type The object type.
	 * @param array $data Additional note data.
	 *
	 * @return int|bool The note id or false otherwise.
	 */
	public function create( $object_id = 0, $object_type = '', $data = array() ) {
		$defaults = array(
			'object_id'    => absint( $object_id ),
			'object_type'  => esc_attr( $object_type ),
			'date_created' => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( $note_id = $this->db->insert_note( $data ) ) {
			$this->set_prop( 'id', $note_id );
		}

		return $note_id;
	}

	/**
	 * Get Note Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The note id.
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Get Note Object Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The note objec tid.
	 */
	public function get_object_id() {
		return absint( $this->object_id );
	}

	/**
	 * Get Note Object Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string The note object type.
	 */
	public function get_object_type() {
		return esc_attr( $this->object_type );
	}

	/**
	 * Get Note User Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The note user id.
	 */
	public function get_user_id() {
		return absint( $this->user_id );
	}

	/**
	 * Get Note Content.
	 *
	 * @since 4.3.0
	 *
	 * @return string The note content
	 */
	public function get_content() {
		return wp_kses_post( $this->content );
	}

	/**
	 * Get Note Date Created.
	 *
	 * @since 4.3.0
	 *
	 * @return string The note date created.
	 */
	public function get_date_created() {
		return esc_attr( $this->date_created );
	}

	/**
	 * Is public?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if the note is public, false otherwise.
	 */
	public function is_public() {
		return (bool) $this->is_public;
	}

	/**
	 * Save Note
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if successfull, False on failure
	 */
	public function save() {
		$data = $this->get_data( true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		$this->db->update_note( $this->get_id(), $data );

		return $this->get_id();
	}

	/**
	 * Delete Note.
	 *
	 * @since 4.3.0
	 */
	public function delete() {
		if ( ! $this->get_id() ) {
			return false;
		}

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return false;
		}

		return $this->db->delete( $this->get_id() );
	}
}