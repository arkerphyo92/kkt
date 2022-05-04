<?php
/**
 * WP Courseware Log Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.1.0
 */
namespace WPCW\Models;

use WPCW\Database\DB_Logs;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Log.
 *
 * @since 4.1.0
 *
 * @property int $log_id
 * @property int $object_id
 * @property string $object_type
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string $date_created
 */
class Log extends Model {

	/**
	 * @var DB_Logs The Logs Database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var int The Log Id.
	 * @since 4.3.0
	 */
	public $log_id;

	/**
	 * @var int The Log Object Id.
	 * @since 4.3.0
	 */
	public $object_id;

	/**
	 * @var string The Log Object Type.
	 * @since 4.3.0
	 */
	public $object_type;

	/**
	 * @var string The Log Type.
	 * @since 4.3.0
	 */
	public $type;

	/**
	 * @var string The Log Title.
	 * @since 4.3.0
	 */
	public $title;

	/**
	 * @var string The Log Message.
	 * @since 4.3.0
	 */
	public $message;

	/**
	 * @var string The Log Date Created.
	 * @since 4.3.0
	 */
	public $date_created;

	/**
	 * Log Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Logs();
		parent::__construct( $data );
	}

	/**
	 * Get Log Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int|void
	 */
	public function get_id() {
		return absint( $this->get_log_id() );
	}

	/**
	 * Get Log Id - Expanded Name.
	 *
	 * @since 4.3.0
	 *
	 * @return int The log id.
	 */
	public function get_log_id() {
		return absint( $this->log_id );
	}

	/**
	 * Get Log Object Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The log object id.
	 */
	public function get_object_id() {
		return absint( $this->object_id );
	}

	/**
	 * Get Log Object Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string the log object type.
	 */
	public function get_object_type() {
		return esc_attr( $this->object_type );
	}

	/**
	 * Get Log Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string The log type.
	 */
	public function get_type() {
		return esc_attr( $this->type );
	}

	/**
	 * Get Log Title
	 *
	 * @since 4.3.0
	 *
	 * @return string The log title.
	 */
	public function get_title() {
		return esc_html( $this->title );
	}

	/**
	 * Get Log Message.
	 *
	 * @since 4.3.0
	 *
	 * @return string The log message.
	 */
	public function get_message() {
		return wp_kses_post( $this->message );
	}

	/**
	 * Get Log Date Created.
	 *
	 * @since 4.3.0
	 *
	 * @return string The log date created.
	 */
	public function get_date_created() {
		return esc_attr( $this->date_created );
	}
}