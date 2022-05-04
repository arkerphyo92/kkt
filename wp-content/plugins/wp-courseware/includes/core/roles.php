<?php
/**
 * WP Courseware Roles.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

use WP_Roles;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Roles.
 *
 * @since 4.3.0
 */
final class Roles {

	/**
	 * Load Roles Class.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'admin_init', array( $this, 'check_roles' ) );
		add_filter( 'editable_roles', array( $this, 'editable_roles' ) );
	}

	/**
	 * Change the dropdown of editable roles when adding a new user.
	 *
	 * @since 4.3.0
	 *
	 * @param array $roles The editable roles.
	 *
	 * @return array $roles The modified editable roles.
	 */
	public function editable_roles( $roles ) {
		global $pagenow;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return $roles;
		}

		// Check for permissions.
		if ( current_user_can( 'manage_wpcw_settings' ) ) {
			return $roles;
		}

		// Check screen
		if ( 'user-new.php' !== $pagenow ) {
			return $roles;
		}

		// Allowed Roles
		$allowed_roles = array( 'subscriber' );

		foreach ( $roles as $role_key => $role ) {
			if ( ! in_array( $role_key, $allowed_roles ) ) {
				unset( $roles[ $role_key ] );
			}
		}

		return $roles;
	}

	/**
	 * Check Roles.
	 *
	 * Check to see if roles have been created, if not create them.
	 *
	 * @since 4.3.0
	 */
	public function check_roles() {
		global $wp_roles;

		if ( ! is_object( $wp_roles ) ) {
			return;
		}

		if ( empty( $wp_roles->roles ) || ! array_key_exists( 'wpcw_instructor', $wp_roles->roles ) ) {
			$this->add_roles();
			$this->add_caps();
		}
	}

	/**
	 * Add new roles with default WP caps
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function add_roles() {
		add_role( 'wpcw_instructor', esc_html__( 'Instructor', 'wp-courseware' ), array(
			'read'         => true,
			'edit_posts'   => false,
			'upload_files' => true,
			'delete_posts' => false,
		) );
	}

	/**
	 * Remove Roles.
	 *
	 * @since 4.4.0
	 */
	public function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		remove_role( 'wpcw_instructor' );
	}

	/**
	 * Add Role Capabilities.
	 *
	 * @since 4.3.0
	 *
	 * @global WP_Roles
	 *
	 * @return  void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			// Administrator Capabilities
			$wp_roles->add_cap( 'administrator', 'view_wpcw_courses' );
			$wp_roles->add_cap( 'administrator', 'manage_wpcw_settings' );

			// Administrator Core Capabilities
			$admin_caps = $this->get_admin_core_caps();
			foreach ( $admin_caps as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}

			// Custom Instructor Capabitlities
			$wp_roles->add_cap( 'wpcw_instructor', 'view_wpcw_courses' );
			$wp_roles->add_cap( 'wpcw_instructor', 'list_users' );
			$wp_roles->add_cap( 'wpcw_instructor', 'create_users' );

			// Instructor Core Capabilities
			$instructor_caps = $this->get_instructor_core_caps();
			foreach ( $instructor_caps as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'wpcw_instructor', $cap );
				}
			}
		}
	}

	/**
	 * Get Capability Types.
	 *
	 * @since 4.4.0
	 *
	 * @return array The capability types.
	 */
	protected function get_cap_types() {
		return array(
			'wpcw_course',
			'wpcw_course_unit',
		);
	}

	/**
	 * Get Administrator Core Capabilities.
	 *
	 * @since 4.3.0
	 *
	 * @return array $capabilities Core capabilities.
	 */
	public function get_admin_core_caps() {
		$capabilities = array();

		// Capability Types.
		$capability_types = $this->get_cap_types();

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",

				// Custom
				"import_{$capability_type}s",
			);
		}

		return $capabilities;
	}

	/**
	 * Get Instructor Core Capabilities.
	 *
	 * @since 4.3.0
	 *
	 * @return array $capabilities Core capabilities.
	 */
	public function get_instructor_core_caps() {
		$capabilities = array();

		// Capability Types.
		$capability_types = $this->get_cap_types();

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"publish_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",

				// Custom
				"import_{$capability_type}s",
			);
		}

		return $capabilities;
	}

	/**
	 * Remove Core Capabilities.
	 *
	 * @since 4.3.0
	 *
	 * @global WP_Roles
	 *
	 * @return void
	 */
	public function remove_caps() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			// Administrator Capabilities
			$wp_roles->remove_cap( 'administrator', 'view_wpcw_courses' );
			$wp_roles->remove_cap( 'administrator', 'manage_wpcw_settings' );

			// Admin Core Capabilities.
			$admin_caps = $this->get_admin_core_caps();
			foreach ( $admin_caps as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'administrator', $cap );
				}
			}

			// Custom Instructor Capabilities
			$wp_roles->remove_cap( 'wpcw_instructor', 'view_wpcw_courses' );
			$wp_roles->remove_cap( 'wpcw_instructor', 'list_users' );
			$wp_roles->remove_cap( 'wpcw_instructor', 'create_users' );

			// Instructor Core Capabilities
			$instructor_caps = $this->get_instructor_core_caps();
			foreach ( $instructor_caps as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'wpcw_instructor', $cap );
				}
			}
		}
	}

	/**
	 * Reset Roles and Caps.
	 *
	 * @since 4.4.1
	 */
	public function reset_roles_caps() {
		// Remove
		$this->remove_roles();
		$this->remove_caps();

		// Add
		$this->add_roles();
		$this->add_caps();
	}
}