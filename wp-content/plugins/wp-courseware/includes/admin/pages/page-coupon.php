<?php
/**
 * WP Courseware Coupon Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.5.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Fields;
use WPCW\Models\Coupon;
use WP_Error;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Order.
 *
 * @since 4.5.0
 */
class Page_Coupon extends Page {

	/**
	 * @var Coupon The coupon object.
	 * @since 4.5.0
	 */
	protected $coupon;

	/**
	 * @var string The coupon action.
	 * @since 4.5.0
	 */
	protected $action = 'new';

	/**
	 * @var Fields The fields api.
	 * @since 4.5.0
	 */
	public $fields;

	/**
	 * Coupon Page Hooks.
	 *
	 * @since 4.5.0
	 */
	public function hooks() {
		parent::hooks();

		// Meta Boxes.
		add_filter( 'wpcw_fields_field_args', array( $this, 'coupon_field_value' ), 10, 2 );
	}

	/** Page Methods -------------------------------------------- */

	/**
	 * Coupon Page Load.
	 *
	 * @since 4.5.0
	 */
	public function load() {
		$coupon_id = wpcw_post_var( 'coupon_id' ) ? wpcw_post_var( 'coupon_id' ) : wpcw_get_var( 'coupon_id' );

		if ( 0 !== absint( $coupon_id ) && 'new' !== $coupon_id ) {
			$this->coupon = new Coupon( absint( $coupon_id ) );
			$this->action = 'edit';
		}

		if ( empty( $this->coupon ) ) {
			$this->coupon = new Coupon();
			$this->action = 'new';
		}

		// Initiate the fields api.
		if ( empty( $this->fields ) ) {
			$this->fields = new Fields();
		}

		// Setup Tabs.
		$this->tabs();
	}

	/**
	 * Highlight Coupons Parent Submenu.
	 *
	 * @since 4.5.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-coupons';
	}

	/**
	 * Get Coupon Menu Title.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Coupon', 'wp-courseware' );
	}

	/**
	 * Get Coupon Page Title.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Edit Coupon', 'wp-courseware' );
	}

	/**
	 * Get Coupon Page Capability.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_coupons_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Coupon Page Slug.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-coupon';
	}

	/**
	 * Is Coupon Page Hidden?
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}

	/**
	 * Get Coupon Action Buttons.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = '';

		if ( 'edit' === $this->action ) {
			$actions .= sprintf(
				'<a class="page-title-action" href="%s">%s</a>',
				add_query_arg( array( 'page' => 'wpcw-coupon' ), admin_url( 'admin.php' ) ),
				esc_html__( 'Add New', 'wp-courseware' )
			);
		}

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-coupons' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Back to Coupons', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-orders' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Orders', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Get Coupon Post Url.
	 *
	 * @since 4.5.0
	 *
	 * @return string The coupon url for the form post method.
	 */
	protected function get_coupon_post_url() {
		$post_args = array();

		if ( $this->coupon->get_id() ) {
			$post_args['coupon_id'] = $this->coupon->get_id();
		}

		if ( ! empty( $_POST['wpcw_tabs_active_tab'] ) ) {
			$post_args['tab'] = wpcw_clean( $_POST['wpcw_tabs_active_tab'] );
		}

		return esc_url_raw( add_query_arg( $post_args, $this->get_url() ) );
	}

	/**
	 * Get Coupon Delete Action Url.
	 *
	 * @since 4.5.0
	 *
	 * @return string The delete action url.
	 */
	protected function get_coupon_delete_action_url() {
		return wp_nonce_url( add_query_arg( array(
			'page'      => 'wpcw-coupons',
			'coupon_id' => $this->coupon->get_id(),
			'action'    => 'delete',
		), admin_url( 'admin.php' ) ), 'coupons-nonce' );
	}

	/**
	 * Get Coupon Views.
	 *
	 * @since 4.5.0
	 *
	 * @return array The views that need to be included.
	 */
	public function get_views() {
		$common_views = $this->get_common_views();

		$page_views = array();

		return apply_filters( 'wpcw_coupon_page_views', array_merge( $common_views, $page_views ) );
	}

	/**
	 * Coupon Page Display.
	 *
	 * @since 4.5.0
	 *
	 * @return mixed
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_coupon_display_bottom', $this );
		?>
		<form id="wpcw-coupon-form" class="wpcw-form" action="<?php echo $this->get_coupon_post_url(); ?>" method="post">
			<?php wp_nonce_field( 'wpcw_save_coupon', 'wpcw_coupon_nonce' ); ?>
			<?php if ( $this->coupon->get_id() ) { ?>
				<input type="hidden" name="coupon_id" value="<?php echo $this->coupon->get_id(); ?>"/>
			<?php } else { ?>
				<input type="hidden" name="coupon_id" value="new"/>
			<?php } ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div id="titlediv">
							<div id="titlewrap">
								<label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Coupon Code', 'wp-courseware' ); ?></label>
								<input type="text" name="code" size="30" value="<?php echo $this->coupon->get_code(); ?>" placeholder="<?php esc_html_e( 'Coupon Code', 'wp-courseware' ); ?>" id="title" spellcheck="true"
								       autocomplete="off"/>
							</div>
						</div>
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables ui-sortable">
							<div id="wpcw-coupon-actions" class="postbox">
								<button type="button" class="handlediv" aria-expanded="true">
									<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Publish', 'wp-courseware' ); ?></span>
									<span class="toggle-indicator" aria-hidden="true"></span>
								</button>
								<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Publish', 'wp-courseware' ); ?></span></h2>
								<div class="inside">
									<ul class="wpcw-coupon-actions-list submitbox">
										<?php if ( $coupon_actions = $this->get_coupon_actions() ) { ?>
											<li class="wpcw-primary-actions wide">
												<select name="wpcw_coupon_action">
													<option value=""><?php esc_html_e( 'Choose an action...', 'wp-courseware' ); ?></option>
													<?php foreach ( $coupon_actions as $action => $label ) { ?>
														<option value="<?php echo $action; ?>"><?php echo $label; ?></option>
													<?php } ?>
												</select>
												<button class="button wpcw-reload"><span><?php esc_html_e( 'Apply', 'wp-courseware' ); ?></span></button>
											</li>
										<?php } ?>
										<li class="wide">
											<?php if ( 'edit' === $this->action && $this->coupon->get_id() ) { ?>
												<div class="wpcw-coupon-delete-action">
													<a class="submitdelete deletion wpcw_delete_item"
													   href="<?php echo esc_url_raw( $this->get_coupon_delete_action_url() ); ?>"
													   title="<?php _e( "Are you sure you want to delete this coupon?\n\nThis CANNOT be undone!", 'wp-courseware' ); ?>">
														<?php esc_html_e( 'Delete', 'wp-courseware' ); ?>
													</a>
												</div>
												<button type="submit" class="button button-primary wpcw-update-coupon wpcw-save-coupon" name="wpcw_coupon_submit">
													<?php esc_html_e( 'Update', 'wp-courseware' ); ?>
												</button>
											<?php } else { ?>
												<button type="submit" class="button button-primary wpcw-update-coupon wpcw-save-coupon" name="wpcw_coupon_submit">
													<?php esc_html_e( 'Publish', 'wp-courseware' ); ?>
												</button>
											<?php } ?>
										</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div id="postbox-container-2" class="postbox-container">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable">
							<div id="wpcw-coupon-details-metabox" class="postbox">
								<button type="button" class="handlediv" aria-expanded="true">
									<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Coupon Details', 'wp-courseware' ); ?></span>
									<span class="toggle-indicator" aria-hidden="true"></span>
								</button>
								<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Coupon Details', 'wp-courseware' ); ?></span></h2>
								<div class="inside">
									<?php do_action( 'wpcw_coupon_details_metabox_before' ); ?>
									<?php $this->field_views(); ?>
									<div id="wpcw-coupon-details" class="panel wpcw-coupon-data">
										<?php $this->display_tabs(); ?>
									</div>
									<?php do_action( 'wpcw_coupon_details_metabox_after' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
		<?php

		do_action( 'wpcw_admin_page_coupon_display_bottom', $this );
	}

	/** Process Methods -------------------------------------------- */

	/**
	 * Process Coupon.
	 *
	 * @since 4.5.0
	 */
	public function process() {
		if ( empty( $_POST['wpcw_coupon_nonce'] ) || ! wp_verify_nonce( $_POST['wpcw_coupon_nonce'], 'wpcw_save_coupon' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return;
		}

		$this->coupon_actions();
		$this->save_coupon();
	}

	/**
	 * Save Coupon.
	 *
	 * @since 4.5.0
	 */
	protected function save_coupon() {
		$data = $this->get_post_data();

		if ( empty( $data ) ) {
			return;
		}

		// Coupon Id.
		$coupon_id = wpcw_array_var( $data, 'coupon_id' );

		// Active Tab.
		$active_tab = wpcw_array_var( $data, 'wpcw_tabs_active_tab' );

		// Setup Coupon.
		if ( 'new' === $coupon_id || 0 === $coupon_id ) {
			unset( $data['coupon_id'] );
			$this->coupon = new Coupon( $data );
			$this->action = 'new';
		} else {
			$this->coupon = new Coupon( absint( $coupon_id ) );
			$this->action = 'edit';
		}

		// Fields.
		$this->fields = new Fields();

		// Fields and Data.
		$coupon_code   = $this->coupon->get_code();
		$coupon_data   = $this->coupon->get_data( true );
		$coupon_fields = $this->get_all_fields();
		$coupon_meta   = array();
		$coupon_errors = new WP_Error();
		$redirect_args = array();

		// Check Code
		if ( ! $coupon_code ) {
			$coupon_errors->add( 'coupon-code-required', esc_html__( 'Coupon Code is required.', 'wp-courseware' ) );
		}

		// Check Duplicate Code.
		if ( 'new' === $this->action ) {
			$id_by_code = wpcw_get_coupon_id_by_code( $coupon_code );
			if ( $id_by_code ) {
				$coupon_errors->add( 'duplicate-coupon-code', esc_html__( 'Coupon code already exists. Please enter a different coupon.', 'wp-courseware' ) );
				$this->coupon->set_prop( 'code', '' );
				unset( $data['code'] );
			}
		} else {
			if ( ! empty( $data['code'] ) ) {
				$old_code = wpcw_format_coupon_code( $this->coupon->get_code() );
				$new_code = wpcw_format_coupon_code( $data['code'] );
				if ( $old_code !== $new_code ) {
					$id_by_code = wpcw_get_coupon_id_by_code( $new_code );
					if ( $id_by_code ) {
						$coupon_errors->add( 'duplicate-coupon-code', esc_html__( 'Coupon code already exists. Please enter a different coupon.', 'wp-courseware' ) );
					} else {
						$this->coupon->set_prop( 'code', $new_code );
					}
				}
			}
		}

		// Course Fields.
		if ( $coupon_fields ) {
			foreach ( $coupon_fields as $field_id => $field ) {
				$field_name    = $this->fields->get_field_name( $field );
				$field_value   = $this->fields->get_field_value( $field );
				$field_req     = $this->fields->get_field_is_required( $field );
				$field_req_msg = $this->fields->get_field_required_message( $field );

				if ( $this->fields->ignore_field( $field ) ) {
					continue;
				}

				if ( ! $field_value && $field_req && $field_req_msg ) {
					$coupon_errors->add( "required-field-{$field_id}", $field_req_msg );
					continue;
				}

				// Specific Sanitization
				switch ( $field_name ) {
					case 'code' :
						$field_value = wpcw_format_coupon_code( $field_value );
						break;
					case 'amount' :
					case 'minimum_amount' :
					case 'maximum_amount' :
						$field_value = wpcw_format_decimal( $field_value );
						break;
					case 'course_ids' :
					case 'exclude_course_ids':
						$field_value = ! empty( $field_value ) ? array_filter( array_map( 'intval', (array) $field_value ) ) : array();
						break;
					case 'usage_limit' :
					case 'usage_limit_per_user' :
						$field_value = absint( $field_value );
						break;
					case 'date_created' :
						if ( ! ( $date_created = wpcw_array_var( $data, 'date_created' ) ) ) {
							$field_value = current_time( 'timestamp', true );
						} else {
							$date_created_hour   = wpcw_array_var( $data, 'date_created_hour' );
							$date_created_minute = wpcw_array_var( $data, 'date_created_minute' );
							$date_created_second = wpcw_array_var( $data, 'date_created_second' );
							if ( $date_created && $date_created_hour && $date_created_minute && $date_created_second ) {
								$field_value = gmdate( 'Y-m-d H:i:s', strtotime( $date_created . ' ' . (int) $date_created_hour . ':' . (int) $date_created_minute . ':' . (int) $date_created_second ) );
							}
						}
						break;
					default:
						$field_value = wpcw_clean( $field_value );
						break;
				}

				if ( property_exists( $this->coupon, $field_name ) ) {
					$this->coupon->set_prop( $field_name, $field_value );
				} else {
					$coupon_meta[ $field_name ] = $field_value;
				}
			}
		}

		// Check for Errors.
		$coupon_errors_msgs = $coupon_errors->get_error_messages();
		if ( ! empty( $coupon_errors_msgs ) ) {
			foreach ( $coupon_errors_msgs as $coupon_error ) {
				wpcw_display_admin_notice( $coupon_error, 'error' );
			}
			return;
		}

		// Save Coupon.
		if ( 'new' === $this->action ) {
			$coupon_data = $this->coupon->get_data();
			$coupon_id   = $this->coupon->create( $coupon_data );
		} else {
			$coupon_id = $this->coupon->save();
		}

		// Save Meta Data.
		if ( ! empty( $coupon_meta ) ) {
			foreach ( $coupon_meta as $meta_key => $meta_value ) {
				$this->coupon->add_meta( $meta_key, $meta_value, true );
			}
		}

		// Add Success Notice.
		if ( $coupon_id ) {
			if ( 'new' === $this->action ) {
				wpcw_add_admin_notice_success( esc_html__( 'Coupon Added!', 'wp-courseware' ) );
			} else {
				wpcw_add_admin_notice_success( esc_html__( 'Coupon Updated!', 'wp-courseware' ) );
			}
		}

		// Active Tab.
		if ( $active_tab ) {
			$redirect_args['tab'] = wpcw_clean( $active_tab );
		}

		// Coupon Id.
		if ( $coupon_id ) {
			$redirect_args['coupon_id'] = absint( $coupon_id );
		}

		// Redirect.
		wp_safe_redirect( add_query_arg( $redirect_args, $this->get_url() ) );
		exit;
	}

	/** Actions Methods -------------------------------------------- */

	/**
	 * Get Coupon Actions.
	 *
	 * @since 4.5.0
	 *
	 * @return array $coupon_actions The array of coupon actions.
	 */
	public function get_coupon_actions() {
		return apply_filters( 'wpcw_coupon_actions', array() );
	}

	/**
	 * Process Coupon Actions.
	 *
	 * @since 4.5.0
	 */
	public function coupon_actions() {
		$coupon_id     = wpcw_post_var( 'coupon_id' ) ? wpcw_post_var( 'coupon_id' ) : wpcw_get_var( 'coupon_id' );
		$coupon_action = ! empty( $_POST['wpcw_coupon_action'] ) ? $_POST['wpcw_coupon_action'] : '';

		if ( ! $coupon_id || ! array_key_exists( $coupon_action, $this->get_coupon_actions() ) ) {
			return;
		}

		switch ( $coupon_action ) {
			default:
				break;
		}

		/**
		 * Action: Process Coupon Actions.
		 *
		 * @since 4.5.0
		 *
		 * @param string $coupon_action The coupon action.
		 * @param int    $coupon_id The coupon id.
		 * @param Page_Coupon The coupon page object.
		 */
		do_action( 'wpcw_coupon_process_actions', $coupon_action, $coupon_id, $this );
	}

	/** Fields Methods -------------------------------------------- */

	/**
	 * Get General Fields.
	 *
	 * @since 4.5.0
	 *
	 * @return array The array of general fields.
	 */
	public function get_general_fields() {
		return apply_filters( 'wpcw_coupon_general_fields', array(
			'type'       => array(
				'id'      => 'type',
				'name'    => 'type',
				'type'    => 'radio',
				'label'   => esc_html__( 'Discount Type', 'wp-courseware' ),
				'desc'    => esc_html__( 'The type of discount for this coupon.', 'wp-courseware' ),
				'tip'     => esc_html__( 'The type of discount for this coupon.', 'wp-courseware' ),
				'default' => 'percentage',
				'options' => wpcw()->coupons->get_types( true ),
			),
			'amount'     => array(
				'id'          => 'amount',
				'name'        => 'amount',
				'type'        => 'text',
				'label'       => esc_html__( 'Coupon Amount', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Coupon Amount', 'wp-courseware' ),
				'desc'        => esc_html__( 'Fixed value or percentage, depending on discount type you choose above. Entered without a currency unit or a percent sign, which are added automatically, e.g., Enter ’10’ for $10 or 10%.', 'wp-courseware' ),
				'tip'         => esc_html__( 'Fixed value or percentage, depending on discount type you choose above. Entered without a currency unit or a percent sign, which are added automatically, e.g., Enter ’10’ for $10 or 10%.', 'wp-courseware' ),
				'req'         => true,
				'req_msg'     => esc_html__( 'Coupon amount is required.', 'wp-courseware' ),
			),
			'start_date' => array(
				'id'          => 'start_date',
				'name'        => 'start_date',
				'type'        => 'datepicker',
				'label'       => esc_html__( 'Start Date', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Start Date', 'wp-courseware' ),
				'desc'        => esc_html__( 'Date the coupon should be valid and avilable for use.', 'wp-courseware' ),
				'tip'         => esc_html__( 'Date the coupon should be valid and avilable for use.', 'wp-courseware' ),
				'default'     => esc_attr( date_i18n( 'Y-m-d' ) )
			),
			'end_date'   => array(
				'id'          => 'end_date',
				'name'        => 'end_date',
				'type'        => 'datepicker',
				'label'       => esc_html__( 'Expiry Date', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Expiry Date', 'wp-courseware' ),
				'desc'        => esc_html__( 'Date the coupon should expire and can no longer be used.', 'wp-courseware' ),
				'tip'         => esc_html__( 'Date the coupon should expire and can no longer be used.', 'wp-courseware' ),
				'default'     => esc_attr( date_i18n( 'Y-m-d', strtotime( '+1 week' ) ) ),
				'req'         => true,
				'req_msg'     => esc_html__( 'Coupon code expiry date is required.', 'wp-courseware' ),
			),
		) );
	}

	/**
	 * Get Usage Restriction Fields.
	 *
	 * @since 4.5.0
	 *
	 * @return array The array of usage restriction fields.
	 */
	public function get_usage_restriction_fields() {
		return apply_filters( 'wpcw_coupon_usage_restriction_fields', array(
			'minimum_amount'     => array(
				'id'          => 'minimum_amount',
				'name'        => 'minimum_amount',
				'type'        => 'text',
				'label'       => esc_html__( 'Minimum Spend', 'wp-courseware' ),
				'placeholder' => esc_html__( 'No minimum', 'wp-courseware' ),
				'desc'        => esc_html__( 'This field allows you to set the minimum spend (subtotal) allowed to use the coupon.', 'wp-courseware' ),
				'tip'         => esc_html__( 'Allows you to set the minimum subtotal needed to use the coupon. Note: The sum of the cart subtotal + tax is used to determine the minimum amount.', 'wp-courseware' ),
			),
			'maximum_amount'     => array(
				'id'          => 'maximum_amount',
				'name'        => 'maximum_amount',
				'type'        => 'text',
				'label'       => esc_html__( 'Maximum Spend', 'wp-courseware' ),
				'placeholder' => esc_html__( 'No maximum', 'wp-courseware' ),
				'desc'        => esc_html__( 'This field allows you to set the maximum spend (subtotal) allowed to use the coupon.', 'wp-courseware' ),
				'tip'         => esc_html__( 'Allows you to set the maximum subtotal allowed when using the coupon.', 'wp-courseware' ),
			),
			'individual_use'     => array(
				'id'     => 'individual_use',
				'name'   => 'individual_use',
				'type'   => 'truefalse',
				'label'  => esc_html__( 'Individual Use', 'wp-courseware' ),
				'clabel' => esc_html__( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'wp-courseware' ),
				'desc'   => esc_html__( 'When checked this coupon cannot be used in conjunction with other coupons.', 'wp-courseware' ),
				'tip'    => esc_html__( 'When checked this coupon cannot be used in conjunction with other coupons.', 'wp-courseware' ),
			),
			'course_ids'         => array(
				'id'    => 'course_ids',
				'name'  => 'course_ids',
				'type'  => 'coursesselect',
				'label' => esc_html__( 'Courses', 'wp-courseware' ),
				'desc'  => esc_html__( 'Courses that need to be in the cart for the discount to applied.', 'wp-courseware' ),
				'tip'   => esc_html__( 'Courses that need to be in the cart for the discount to applied.', 'wp-courseware' ),
			),
			'exclude_course_ids' => array(
				'id'    => 'exclude_course_ids',
				'name'  => 'exclude_course_ids',
				'type'  => 'coursesselect',
				'label' => esc_html__( 'Exclude Courses', 'wp-courseware' ),
				'desc'  => esc_html__( 'Courses that the coupon will not be applied to, or that cannot be in the cart for the "Fixed cart discount" to be applied.', 'wp-courseware' ),
				'tip'   => esc_html__( 'Courses that the coupon will not be applied to, or that cannot be in the cart for the "Fixed cart discount" to be applied.', 'wp-courseware' ),
			),
		) );
	}

	/**
	 * Get Usage Limit Fields.
	 *
	 * @since 4.5.0
	 *
	 * @return array The array of usage limit fields.
	 */
	public function get_usage_limit_fields() {
		return apply_filters( 'wpcw_coupon_usage_limit_fields', array(
			'usage_limit'          => array(
				'id'          => 'usage_limit',
				'name'        => 'usage_limit',
				'type'        => 'number',
				'label'       => esc_html__( 'Limit per coupon', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Unlimited usage', 'wp-courseware' ),
				'desc'        => esc_html__( 'How many times a coupon can be used by all customers before being invalid.', 'wp-courseware' ),
				'tip'         => esc_html__( 'How many times a coupon can be used by all customers before being invalid.', 'wp-courseware' ),
			),
			'usage_limit_per_user' => array(
				'id'          => 'usage_limit_per_user',
				'name'        => 'usage_limit_per_user',
				'type'        => 'number',
				'label'       => esc_html__( 'Limit per user', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Unlimited usage', 'wp-courseware' ),
				'desc'        => esc_html__( 'How many times this coupon can be used by an individual user. Uses user ID for logged in users.', 'wp-courseware' ),
				'tip'         => esc_html__( 'How many times this coupon can be used by an individual user. Uses user ID for logged in users.', 'wp-courseware' ),
			),
		) );
	}

	/**
	 * Get Coupon Field Value.
	 *
	 * @since 4.5.0
	 *
	 * @param array $field The field data.
	 *
	 * @return mixed $field_value The field value.
	 */
	public function get_coupon_field_value( $field ) {
		$field_type  = $this->fields->get_field_type( $field );
		$field_name  = $this->fields->get_field_name( $field );
		$field_value = $this->fields->get_field_value( $field );

		if ( is_callable( array( $this->coupon, "get_{$field_name}" ) ) ) {
			$field_value = $this->coupon->{"get_{$field_name}"}();
		}

		if ( is_null( $field_value ) ) {
			$field_value = $this->coupon->get_meta( $field_name, true );
		}

		return $field_value;
	}

	/**
	 * Course Field Value.
	 *
	 * @since 4.5.0
	 *
	 * @param array  $field The field data.
	 * @param Fields $fields The fields object.
	 *
	 * @return array $field The field data.
	 */
	public function coupon_field_value( $field, $fields ) {
		if ( ! $this->is_current_page() ) {
			return $field;
		}

		$field['value'] = $this->get_coupon_field_value( $field );

		return $field;
	}

	/**
	 * Get All Fields.
	 *
	 * @since 4.5.0
	 *
	 * @return array $fields The fields.
	 */
	public function get_all_fields() {
		$fields = array();
		$tabs   = $this->get_tabs();

		if ( empty( $tabs ) ) {
			return $fields;
		}

		// Go 3 Levels Deep.
		foreach ( $tabs as $tab ) {
			if ( ! empty( $tab['fields'] ) ) {
				$fields = array_merge( $fields, $tab['fields'] );
				foreach ( $tab['fields'] as $field ) {
					if ( ! empty( $field['fields'] ) ) {
						$fields = array_merge( $fields, $field['fields'] );
						foreach ( $field['fields'] as $sub_field ) {
							if ( ! empty( $sub_field['fields'] ) ) {
								$fields = array_merge( $fields, $sub_field['fields'] );
							}
						}
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Field Views.
	 *
	 * @since 4.5.0
	 */
	public function field_views() {
		$views  = array();
		$fields = $this->get_all_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$type           = $this->fields->get_field_type( $field );
				$views[ $type ] = $this->fields->get_field_views( $field );
			}
		}

		if ( ! empty( $views ) ) {
			foreach ( $views as $view ) {
				echo $view;
			}
		}
	}

	/** Tab Methods ---------------------------------------------- */

	/**
	 * Get Page Course Tabs.
	 *
	 * @since 4.4.0
	 *
	 * @return mixed|void
	 */
	public function get_tabs() {
		return apply_filters( 'wpcw_course_tabs', array(
			'general'      => array(
				'default' => true,
				'id'      => 'general',
				'label'   => esc_html__( 'General', 'wp-courseware' ),
				'icon'    => '<i class="wpcw-fas wpcw-fa-ticket-alt"></i>',
				'fields'  => $this->get_general_fields(),
			),
			'restrictions' => array(
				'default' => false,
				'id'      => 'restrictions',
				'label'   => esc_html__( 'Usage Restrictions', 'wp-courseware' ),
				'icon'    => '<i class="wpcw-fas wpcw-fa-ban"></i>',
				'fields'  => $this->get_usage_restriction_fields(),
			),
			'limits'       => array(
				'default' => false,
				'id'      => 'limits',
				'label'   => esc_html__( 'Usage Limits', 'wp-courseware' ),
				'icon'    => '<i class="wpcw-fas wpcw-fa-minus-circle"></i>',
				'fields'  => $this->get_usage_limit_fields(),
			)
		) );
	}

	/**
	 * Display Tabs.
	 *
	 * @since 4.5.0
	 */
	public function display_tabs() {
		if ( empty( $this->tabs ) ) {
			return;
		}

		echo '<div class="wpcw-tabs">';
		echo '<input id="wpcw-tabs-active-tab" type="hidden" name="wpcw_tabs_active_tab" value="' . $this->get_active_tab() . '">';
		$this->get_tabs_navigation();
		$this->get_tab_content();
		echo '</div>';
	}

	/**
	 * Get Tabs Navigation.
	 *
	 * @since 4.5.0
	 *
	 * @param array $tabs The tabs that should be displayed.
	 */
	public function get_tabs_navigation() {
		if ( empty( $this->tabs ) ) {
			return;
		}
		?>
		<ul role="tablist" class="wpcw-tabs-nav">
			<?php foreach ( $this->tabs as $tab_id => $tab ) { ?>
				<li role="presentation"
				    data-id="<?php echo esc_attr( $tab_id ); ?>"
				    data-default="<?php echo $this->is_active_tab( $tab_id ) ? 'yes' : 'no'; ?>"
				    :aria-selected="activeTab === '<?php echo esc_attr( $tab_id ); ?>' ? true : false"
				    class="wpcw-tab-title"
				    :class="{ 'is-active' : activeTab === '<?php echo esc_attr( $tab_id ); ?>' }">
					<a aria-controls="#<?php echo esc_attr( $tab_id ); ?>"
					   href="#<?php echo esc_attr( $tab_id ); ?>"
					   class="wpcw-tab"
					   @click.prevent="selectTab( '<?php echo esc_attr( $tab_id ); ?>', $event )">
						<?php echo $this->get_tab_icon( $tab ); ?><span class="wpcw-tab-label"><?php echo $this->get_tab_label( $tab ); ?></span>
					</a>
				</li>
			<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Get Tab Content.
	 *
	 * @since 4.5.0
	 */
	public function get_tab_content() {
		if ( empty( $this->tabs ) ) {
			return;
		}
		?>
		<div class="wpcw-tabs-panels">
			<?php foreach ( $this->tabs as $tab_id => $tab ) {
				$fields = ! empty( $tab['fields'] ) ? $tab['fields'] : array();
				?>
				<section v-show="'<?php echo esc_attr( $tab_id ); ?>' === activeTab"
				         role="tabpanel"
				         id="<?php echo esc_attr( $tab_id ); ?>"
				         class="wpcw-tab-panel wpcw-tab-panel-<?php echo esc_attr( $tab_id ); ?>"
				         :class="{ 'is-active' : activeTab === '<?php echo esc_attr( $tab_id ); ?>' }">
					<?php if ( ! empty( $fields ) ) { ?>
						<div class="wpcw-metabox-fields">
							<?php foreach ( $fields as $field ) { ?>
								<?php echo $this->fields->render_field( $field ); ?>
							<?php } ?>
						</div>
					<?php } ?>
				</section>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Get Tab Id.
	 *
	 * @since 4.5.0
	 *
	 * @param array $tab The tab data.
	 *
	 * @return string The tab id.
	 */
	public function get_tab_id( $tab ) {
		return isset( $tab['id'] ) ? wpcw_sanitize_key( $tab['id'] ) : '';
	}

	/**
	 * Get Tab Icon.
	 *
	 * @since 4.5.0
	 *
	 * @param string $tab The tab icon.
	 */
	public function get_tab_icon( $tab ) {
		$default_icon = ( isset( $tab['icon'] ) && 'disabled' === $tab['icon'] ) ? '' : '<i class="wpcw-fas wpcw-fa-tasks left"></i>';

		return apply_filters( 'wpcw_coupon_tab_icon', ( isset( $tab['icon'] ) && 'disabled' !== $tab['icon'] ) ? wp_kses_post( $tab['icon'] ) : $default_icon, $tab );
	}

	/**
	 * Get Active Tab.
	 *
	 * @since 4.5.0
	 *
	 * @return string The active tab slug.
	 */
	public function get_active_tab() {
		return isset( $this->tab['slug'] ) ? $this->tab['slug'] : '';
	}

	/**
	 * Is Active Tab?
	 *
	 * @since 4.5.0
	 *
	 * @param string $slug The current tab slug.
	 *
	 * @return string The active class or blank.
	 */
	public function is_active_tab( $slug ) {
		return ( $slug === $this->get_active_tab() ) ? true : false;
	}

	/**
	 * Set Active Tab.
	 *
	 * @since 4.4.0
	 *
	 * @param string $location The location URL.
	 * @param int    $post_id The post ID.
	 *
	 * @return string The url after redirect.
	 */
	public function set_active_tab( $location, $post_id ) {
		if ( ! empty( $_POST['wpcw_tabs_active_tab'] ) ) {
			$location = add_query_arg( 'tab', wpcw_clean( $_POST['wpcw_tabs_active_tab'] ), $location );
		}

		return $location;
	}
}
