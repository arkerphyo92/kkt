<?php
/**
 * WP Courseware Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.3.0
 */

namespace WPCW\Models;

use stdClass;
use WPCW\Database\DB;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Model.
 *
 * @since 4.3.0
 */
abstract class Model {

	/**
	 * @var stdClass
	 * @since 4.1.0
	 */
	protected $data;

	/**
	 * @var DB The database object.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * Model Constructor.
	 *
	 * @since 4.1.0
	 *
	 * @param array|int|Model $data The data array.
	 */
	public function __construct( $data = array() ) {
		$this->data = new stdClass();

		if ( empty( $data ) ) {
			return;
		}

		if ( is_numeric( $data ) ) {
			$this->setup( $data );
		} elseif ( is_object( $data ) || is_array( $data ) ) {
			$this->set_data( $data );
		}
	}

	/**
	 * Get Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The id of the object.
	 */
	abstract public function get_id();

	/**
	 * Create Model Object.
	 *
	 * @since 4.3.0
	 *
	 * @param array $data The data to insert upon creation.
	 *
	 * @return int|bool $object_id The object id or false otherwise.
	 */
	public function create( $data = array() ) {
		if ( empty( $this->db ) || ! $this->db->get_primary_key() ) {
			return;
		}

		$defaults = $this->get_defaults();

		$data = wp_parse_args( $data, $defaults );

		$type = strtolower( get_class( $this ) );

		if ( $object_id = $this->db->insert( $data, $type ) ) {
			$this->set_data( $data );
			$this->set_prop( $this->db->get_primary_key(), $object_id );
		}

		return $object_id;
	}

	/**
	 * Setup Model Object.
	 *
	 * @since 4.3.0
	 *
	 * @param int $data The data id.
	 */
	public function setup( $data ) {
		if ( 0 === absint( $data ) ) {
			return;
		}

		$data_object = $this->db->get( $data );

		if ( ! $data_object ) {
			return;
		}

		if ( $data_object && is_object( $data_object ) ) {
			$this->set_data( $data_object );
		}
	}

	/**
	 * Model Exists.
	 *
	 * @since 4.4.0
	 *
	 * @return bool Return true if the ID exists, false otherwise.
	 */
	public function exists() {
		return (bool) $this->get_id();
	}

	/**
	 * Save Model
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

		$data = wp_unslash( $data );

		$this->db->update( $this->get_id(), $data );

		return $this->get_id();
	}

	/**
	 * Update Model.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if successfull, False on failure
	 */
	public function update() {
		return $this->save();
	}

	/**
	 * Delete Model.
	 *
	 * @since 4.4.0
	 *
	 * @return True on success, false on failure.
	 */
	public function delete() {
		return $this->db->delete( $this->get_id() );
	}

	/**
	 * Set Model Data.
	 *
	 * @since 4.1.0
	 *
	 * @param object $values The model data values.
	 */
	public function set_data( $values ) {
		foreach ( $values as $key => $value ) {
			if ( $this->property_exists( $key ) ) {
				$this->$key       = $value;
				$this->data->$key = $value;
			}
		}
	}

	/**
	 * Set Model Property.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The property key.
	 * @param mixed  $value The property value.a
	 */
	public function set_prop( $key, $value = null ) {
		if ( $this->property_exists( $key ) && ! is_null( $value ) ) {
			$this->$key       = $value;
			$this->data->$key = $value;
		}
	}

	/**
	 * Set Model Properties.
	 *
	 * @since 4.3.0
	 *
	 * @param array $props Key value pairs to set.
	 *
	 * @return bool
	 */
	public function set_props( $props = array() ) {
		if ( empty( $props ) ) {
			return false;
		}

		foreach ( $props as $key => $value ) {
			if ( $this->property_exists( $key ) && ! is_null( $value ) ) {
				$this->$key       = $value;
				$this->data->$key = $value;
			}
		}

		return true;
	}

	/**
	 * Get Model Data.
	 *
	 * @since 4.1.0
	 *
	 * @return stdClass
	 */
	public function get_data( $array = false ) {
		return $array ? $this->to_array() : $this->data;
	}

	/**
	 * Check if the property exists.
	 *
	 * @since 4.1.0
	 *
	 * @return bool|null True if the property exists
	 */
	public function property_exists( $key ) {
		return property_exists( get_called_class(), $key );
	}

	/**
	 * Return an array representation.
	 *
	 * @since 4.1.0
	 *
	 * @return array Array representation.
	 */
	public function to_array() {
		return get_object_vars( $this->data );
	}

	/**
	 * Get Model Defaults.
	 *
	 * @since 4.3.0
	 *
	 * @return array The model defaults.
	 */
	public function get_defaults() {
		return array();
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

		if ( is_array( $message ) ) {
			$message = print_r( $message, true );
		}

		$log_entry = "\n" . '====Start ' . get_called_class() . ' Log====' . "\n" . $message . "\n" . '====End ' . get_called_class() . ' Log====' . "\n";

		wpcw_log( $log_entry );
		wpcw_file_log( array( 'message' => $log_entry ) );
	}
}
