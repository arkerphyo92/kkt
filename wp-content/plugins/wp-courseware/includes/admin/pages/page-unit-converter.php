<?php
/**
 * WP Courseware Unit Converter Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */

namespace WPCW\Admin\Pages;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Unit_Converter.
 *
 * @since 4.1.0
 */
class Page_Unit_Converter extends Page {

	/**
	 * @var string The process action.
	 * @since 4.6.4
	 */
	protected $action;

	/**
	 * @var \WP_Post The post object.
	 * @since 4.6.4
	 */
	protected $post;

	/**
	 * @var bool Processed flag.
	 * @since 4.6.4
	 */
	protected $processed = false;

	/**
	 * @var bool Converted flag.
	 * @since 4.6.4
	 */
	protected $converted = false;

	/**
	 * @var bool Cancelled Flag.
	 * @since 4.6.4
	 */
	protected $cancelled = false;

	/**
	 * Get Unit Converter Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Convert to Course Unit', 'wp-courseware' );
	}

	/**
	 * Get Unit Converter Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Convert to Course Unit', 'wp-courseware' );
	}

	/**
	 * Get Unit Converter Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_units_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Unit Converter Page Slug.
	 *
	 * @since 4.6.4
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-unit-converter';
	}

	/**
	 * Setup Page.
	 *
	 * @since 4.6.4
	 */
	public function setup() {
		if ( $post_id = wpcw_get_var( 'postid' ) ) {
			$this->post = get_post( $post_id );
		}

		if ( $action = wpcw_get_var( 'action' ) ) {
			$this->action = strtolower( esc_attr( $action ) );
		}

		if ( $converted = wpcw_get_var( 'converted' ) ) {
			$this->converted = 'yes' === $converted ? true : false;
			$this->processed = true;
		}

		if ( $cancelled = wpcw_get_var( 'cancelled' ) ) {
			$this->cancelled = 'yes' === $cancelled ? true : false;
			$this->processed = true;
		}
	}


	/**
	 * Process Page.
	 *
	 * @since 4.6.4
	 */
	public function process() {
		if ( empty( $this->action ) ) {
			return;
		}

		if ( 'convert' === $this->action ) {
			if ( $this->convert() ) {
				wpcw_add_admin_notice_success( esc_html__( 'Conversion to a Course Unit was successful!', 'wp-courseware' ) );
				wp_safe_redirect( add_query_arg( array( 'postid' => $this->post->ID, 'converted' => 'yes' ), $this->get_url() ) );
			} else {
				wp_safe_redirect( add_query_arg( array( 'postid' => $this->post->ID ), $this->get_url() ) );
			}
			exit;
		}

		if ( 'cancel' === $this->action ) {
			wpcw_add_admin_notice_error( esc_html__( 'Conversion to the Course Unit was cancelled!', 'wp-courseware' ) );
			wp_safe_redirect( add_query_arg( array( 'postid' => $this->post->ID, 'cancelled' => 'yes' ), $this->get_url() ) );
			exit;
		}
	}

	/**
	 * Page - Display.
	 *
	 * @since 4.6.4
	 *
	 * @return mixed
	 */
	public function display() {
		if ( empty( $this->post ) ) {
			wpcw_admin_notice_error( esc_html__( 'Sorry, but the specified page/post does not appear to exist.', 'wp-courseware' ) );
			return;
		}

		do_action( 'wpcw_admin_notices' );

		if ( wpcw()->units->post_type_slug !== $this->post->post_type && ! $this->processed ) {
			?>
			<p><?php printf( __( 'Are you sure you wish to convert the %s <strong>[%s]</strong> to a Course Unit?', 'wp-courseware' ), $this->post->post_type, $this->post->post_title ); ?></p>
			<a class="button-primary" href="<?php echo $this->get_action_url( 'convert' ); ?>"><i class="wpcw-fas wpcw-fa-check left"></i> <?php esc_html_e( 'Yes, convert it!', 'wp-courseware' ); ?></a>
			<a class="button-secondary" href="<?php echo $this->get_action_url( 'cancel' ); ?>"><i class="wpcw-fas wpcw-fa-ban left"></i> <?php esc_html_e( 'No, dont\'t convert it!', 'wp-courseware' ); ?></a>
			<?php
		}

		if ( $this->converted ) {
			?>
			<p><?php printf( __( 'The %s <strong>[%s]</strong> has been converted to a Course Unit!', 'wp-courseware' ), $this->post->post_type, $this->post->post_title ); ?></p>
			<a class="button-primary" href="<?php echo get_edit_post_link( $this->post ); ?>"><i class="wpcw-fas wpcw-fa-edit left"></i> <?php esc_html_e( 'Edit Course Unit', 'wp-courseware' ); ?></a>
			<?php
		}

		if ( $this->cancelled ) {
			?>
			<p><?php printf( __( 'The %s <strong>[%s]</strong> has not been converted to a Course Unit. Please click below to return to the %s!', 'wp-courseware' ), $this->post->post_type, $this->post->post_title, $this->post->post_type ); ?></p>
			<a class="button-primary" href="<?php echo get_edit_post_link( $this->post ); ?>"><i class="wpcw-fas wpcw-fa-arrow-left left"></i> <?php printf( esc_html__( 'Back to %s', 'wp-courseware' ), ucfirst( $this->post->post_type ) ); ?>
			</a>
			<?php
		}
	}

	/**
	 * Get Action Url.
	 *
	 * @since 4.6.4
	 *
	 * @param string $action The action slug.
	 *
	 * @return string The action url.
	 */
	protected function get_action_url( $action = '' ) {
		return add_query_arg( array(
			'postid' => absint( $this->post->ID ),
			'action' => ! empty( $action ) ? $action : $this->action
		), $this->get_url() );
	}

	/**
	 * Convert Page/Post to Course Unit.
	 *
	 * @since 4.6.4
	 *
	 * @throws \Exception
	 *
	 * @return bool True if successfully converted. False otherwise.
	 */
	protected function convert() {
		try {
			$updated_post = wp_update_post( array(
				'ID'        => $this->post->ID,
				'post_type' => wpcw()->units->post_type_slug,
			), true );

			if ( is_wp_error( $updated_post ) ) {
				throw new \Exception( $updated_post->get_error_message() );
			}

			wp_delete_object_term_relationships( $this->post->ID, array( 'category', 'post_tag' ) );
		} catch ( \Exception $exception ) {
			wpcw_add_admin_notice_error( $exception->getMessage() );
			return false;
		}

		return true;
	}

	/**
	 * Is Unit Converter Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}
}
