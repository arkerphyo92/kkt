<?php
/**
 * WP Courseware Subscription Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.3.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Subscription.
 *
 * @since 4.3.0
 */
class Page_Subscription extends Page {

	/**
	 * @var Subscription The subscrition object.
	 * @since 4.3.0
	 */
	protected $subscription;

	/**
	 * Subscriptions Page Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		$subscription_id = wpcw_post_var( 'id' ) ? wpcw_post_var( 'id' ) : wpcw_get_var( 'id' );

		if ( $subscription_id ) {
			$this->subscription = new Subscription( $subscription_id );
		}

		if ( empty( $this->subscription ) ) {
			$this->subscription = new Subscription();
			$subscription_id    = $this->subscription->create();
			$subscription_url   = esc_url_raw( add_query_arg( array( 'id' => $subscription_id ), $this->get_url() ) );
			wp_safe_redirect( $subscription_url );
			exit;
		}
	}

	/**
	 * Highlight Submenu.
	 *
	 * @since 4.3.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-subscriptions';
	}

	/**
	 * Get Subscription Menu Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Subscription', 'wp-courseware' );
	}

	/**
	 * Get Subscription Page Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Edit Subscription', 'wp-courseware' );
	}

	/**
	 * Get Subscription Page Capability.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_subscription_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Subscription Page Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-subscription';
	}

	/**
	 * Is Subscription Page Hidden?
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}

	/**
	 * Get Subscription Action Buttons.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-subscriptions' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Back to Subscriptions', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-orders' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Orders', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Get Subscription Post Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The subscription url for the form post method.
	 */
	protected function get_subscription_post_url() {
		return esc_url_raw( add_query_arg( array( 'id' => $this->subscription->get_id() ), $this->get_url() ) );
	}

	/**
	 * Get Delete Action Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The delete action url.
	 */
	protected function get_subscription_delete_action_url() {
		return wp_nonce_url( add_query_arg( array(
			'page'   => 'wpcw-subscriptions',
			'id'     => $this->subscription->get_id(),
			'action' => 'delete',
		), admin_url( 'admin.php' ) ), 'subscriptions-nonce' );
	}

	/**
	 * Process Subscription.
	 *
	 * @since 4.3.0
	 */
	public function process() {
		if ( empty( $_POST['wpcw_subscription_nonce'] ) || ! wp_verify_nonce( $_POST['wpcw_subscription_nonce'], 'wpcw_save_subscription' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return;
		}

		$subscription_id = wpcw_post_var( 'id' ) ? wpcw_post_var( 'id' ) : wpcw_get_var( 'id' );

		if ( $subscription_id = wpcw()->subscriptions->process_subscription( $_POST ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Subscription Updated!', 'wp-courseware' ) );
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'id' => $subscription_id ), $this->get_url() ) ) );
		exit;
	}

	/**
	 * Get Subscriptions Actions.
	 *
	 * @since 4.3.0
	 *
	 * @return array $subscription_actions The array of subscription actions.
	 */
	public function get_subscription_actions() {
		return apply_filters( 'wpcw_subscription_actions', array() );
	}

	/**
	 * Subscription Page Display.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_subscription_display_bottom', $this );
		?>
        <form class="wpcw-form" action="<?php echo $this->get_subscription_post_url(); ?>" method="post">
			<?php wp_nonce_field( 'wpcw_save_subscription', 'wpcw_subscription_nonce' ); ?>
            <input type="hidden" name="subscription_id" value="<?php echo $this->subscription->get_id(); ?>"/>

			<?php if ( ! $this->subscription->is_setup() ) { ?>
				<?php wpcw_admin_notice( __( '<strong>Note:</strong> This tool allows you to create a new subscription. It will not create a payment profile in your merchant processor such as PayPal or Stripe. Payment profiles in the merchant processor must be created through your merchant portal. Once created in the merchant portal, details such as transaction ID and billing profile id, can be entered here.', 'wp-courseware' ), 'info', true, true, true ); ?>
			<?php } ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">

                    <div id="postbox-container-1" class="postbox-container">
                        <div id="side-sortables" class="meta-box-sortables ui-sortable">
                            <div id="wpcw-subscription-actions" class="postbox">
                                <button type="button" class="handlediv" aria-expanded="true">
                                    <span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Subscription Actions', 'wp-courseware' ); ?></span>
                                    <span class="toggle-indicator" aria-hidden="true"></span>
                                </button>

                                <h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Subscription Actions', 'wp-courseware' ); ?></span></h2>

                                <div class="inside">
                                    <ul class="wpcw-subscription-actions-list submitbox">
										<?php if ( $subscription_actions = $this->get_subscription_actions() ) { ?>
                                            <li class="wpcw-primary-actions wide">
                                                <select name="wpcw_subscription_action">
                                                    <option value=""><?php esc_html_e( 'Choose an action...', 'wp-courseware' ); ?></option>
													<?php foreach ( $subscription_actions as $action => $label ) { ?>
                                                        <option value="<?php echo $action; ?>"><?php echo $label; ?></option>
													<?php } ?>
                                                </select>
                                                <button class="button wpcw-reload"><span><?php esc_html_e( 'Apply', 'wp-courseware' ); ?></span></button>
                                            </li>
										<?php } ?>
                                        <li class="wide">
                                            <div class="wpcw-subscription-delete-action">
                                                <a class="submitdelete deletion wpcw_delete_item"
                                                   href="<?php echo esc_url_raw( $this->get_subscription_delete_action_url() ); ?>"
                                                   title="<?php _e( "Are you sure you want to delete this subscription?\n\nThis CANNOT be undone!", 'wp-courseware' ); ?>">
													<?php esc_html_e( 'Delete', 'wp-courseware' ); ?>
                                                </a>
                                            </div>
                                            <button type="submit" class="button button-primary wpcw-update-subscription wpcw-save-subscription" name="wpcw_subscription_submit">
												<?php esc_html_e( 'Update', 'wp-courseware' ); ?>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

							<?php if ( $notes = $this->subscription->get_notes() ) { ?>
                                <div id="wpcw-notes" class="postbox">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Subscription Notes', 'wp-courseware' ); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>

                                    <h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Subscription Notes', 'wp-courseware' ); ?></span></h2>

                                    <div class="inside">
                                        <ul class="wpcw-notes">
											<?php
											/** @var Note $note */
											foreach ( $notes as $note ) { ?>
                                                <li rel="<?php echo $note->get_id(); ?>" class="wpcw-note wpcw-note-<?php echo ( ! $note->is_public() ) ? 'system' : 'student'; ?> ">
                                                    <div class="wpcw-note-content">
                                                        <p><?php echo wp_kses_post( $note->get_content() ); ?></p>
                                                    </div>
                                                    <p class="wpcw-note-meta">
                                                        <abbr class="wpcw-note-date" title="<?php echo wpcw_format_datetime( $note->get_date_created(), 'Y-m-d H:i:s' ); ?>">
															<?php printf( __( 'Added: %s', 'wp-courseware' ), wpcw_format_datetime( $note->get_date_created(), 'F j, Y @ g:i a' ) ); ?>
                                                        </abbr>
                                                    </p>
                                                </li>
											<?php } ?>
                                        </ul>
                                    </div>
                                </div>
							<?php } ?>
                        </div>
                    </div>

                    <div id="postbox-container-2" class="postbox-container">
                        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                            <div id="wpcw-subscription-details-metabox" class="postbox">
                                <button type="button" class="handlediv" aria-expanded="true">
                                    <span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Subscription Details', 'wp-courseware' ); ?></span>
                                    <span class="toggle-indicator" aria-hidden="true"></span>
                                </button>
                                <h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Subscription Details', 'wp-courseware' ); ?></span></h2>
                                <div class="inside">
                                    <div id="wpcw-subscription-details" class="panel wpcw-subscription-data">
                                        <h2 class="wpcw-subscription-data-heading">
											<?php printf( __( 'Subscription #%s', 'wp-courseware' ), $this->subscription->get_id() ); ?>
                                        </h2>

										<?php if ( $this->subscription->can_cancel() ) { ?>
                                            <div class="wpcw-subscription-cancel-wrapper">
                                                <a href="<?php echo $this->subscription->get_admin_cancel_url(); ?>" id="wpcw-cancel-subscription" class="button">
                                                    <i class="wpcw-fas wpcw-fa-times-circle"></i>&nbsp;&nbsp;
													<?php esc_html_e( 'Cancel Subscription', 'wp-courseware' ); ?>
                                                </a>
                                            </div>
										<?php } ?>

										<?php if ( ( $subscrption_profile_id = $this->subscription->get_profile_id() ) && $this->subscription->get_method() ) { ?>
                                            <p class="wpcw-subscription-data-meta subscription-profile-id">
												<?php printf( __( 'Profile ID: <span>%s</span>', 'wp-courseware' ), apply_filters( 'wpcw_subscription_profile_link_' . $this->subscription->get_method(), $subscrption_profile_id, $this->subscription ) ); ?>
                                            </p>
										<?php } ?>

										<?php if ( ( $subscrption_transaction_id = $this->subscription->get_transaction_id() ) && $this->subscription->get_method() ) { ?>
                                            <p class="wpcw-subscription-data-meta subscription-transaction-id">
												<?php printf( __( 'Transaction ID: <span>%s</span>', 'wp-courseware' ), apply_filters( 'wpcw_subscription_transaction_link_' . $this->subscription->get_method(), $subscrption_transaction_id, $this->subscription ) ); ?>
                                            </p>
										<?php } ?>

                                        <div class="wpcw-form-fields">
                                            <div class="wpcw-form-field">
                                                <label for="initial_amount"><?php esc_html_e( 'Billing Amount:', 'wp-courseware' ); ?></label>
                                                <input type="text"
                                                       name="initial_amount"
                                                       placeholder="<?php esc_attr_e( 'Amount', 'wp-courseware' ); ?>"
                                                       value="<?php echo esc_attr( $this->subscription->get_initial_amount() ); ?>">
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="initial_amount"><?php esc_html_e( 'Billing Interval:', 'wp-courseware' ); ?></label>
                                                <select class="select-field-wpcwselect2"
                                                        name="period"
                                                        data-placeholder="<?php esc_html_e( 'Select an Interval', 'wp-courseware' ); ?>"
                                                        data-allow-clear="false">
                                                    <option value=""><?php esc_html_e( 'Select an Interval', 'wp-courseware' ); ?></option>
													<?php foreach ( wpcw()->subscriptions->get_periods() as $period => $period_label ) { ?>
                                                        <option value="<?php echo esc_attr( $period ); ?>" <?php selected( $period, $this->subscription->get_period(), true ); ?>><?php echo esc_html( $period_label ) ?></option>
													<?php } ?>
                                                </select>
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="student_id"><?php esc_html_e( 'Student:', 'wp-courseware' ); ?></label>
                                                <select id="wpcw-student-select"
                                                        name="student_id"
                                                        data-placeholder="<?php esc_html_e( 'Select a Student', 'wp-courseware' ); ?>"
                                                        data-allow_clear="true">s
                                                    <option value=""><?php esc_html_e( 'Select a Student', 'wp-courseware' ); ?></option>
													<?php if ( ( $student = $this->subscription->get_student() ) ) { ?>
                                                        <option value="<?php echo absint( $student->get_ID() ); ?>" selected="selected">
															<?php echo esc_attr( $student->get_display_name() ); ?>
                                                        </option>
													<?php } ?>
                                                </select>
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="course_id"><?php esc_html_e( 'Course:', 'wp-courseware' ); ?></label>
                                                <select id="wpcw-course-select"
                                                        name="course_id"
                                                        data-placeholder="<?php esc_html_e( 'Select a Course', 'wp-courseware' ); ?>"
                                                        data-allow_clear="true">s
                                                    <option value=""><?php esc_html_e( 'Select a Course', 'wp-courseware' ); ?></option>
													<?php if ( ( $course = $this->subscription->get_course() ) ) { ?>
                                                        <option value="<?php echo absint( $course->get_course_id() ); ?>" selected="selected">
															<?php echo $course->get_course_title(); ?>
                                                        </option>
													<?php } ?>
                                                </select>
                                            </div>

											<?php if ( ( $order = $this->subscription->get_order() ) ) { ?>
                                                <div class="wpcw-form-field">
                                                    <label for="order_id"><?php esc_html_e( 'Order:', 'wp-courseware' ); ?></label>
                                                    <input disabled="disabled" class="disabled" type="text" id="order_id" name="order_id" value="<?php echo absint( $order->get_order_id() ); ?>"/>
                                                </div>
											<?php } else { ?>
                                                <div class="wpcw-form-field">
                                                    <label for="wpcw-order-action"><?php esc_html_e( 'Order:', 'wp-courseware' ); ?></label>
                                                    <select id="wpcw-order-action"
                                                            name="order_action"
                                                            class="select-field-wpcwselect2"
                                                            data-placeholder="<?php esc_html_e( 'Select a Order', 'wp-courseware' ); ?>"
                                                            data-allow_clear="false">
                                                        <option value="new"><?php esc_html_e( 'Create New Order', 'wp-courseware' ); ?></option>
                                                        <option value="existing"><?php esc_html_e( 'Enter Existing Order ID', 'wp-courseware' ); ?></option>
                                                    </select>
                                                </div>
                                                <div class="wpcw-form-field hidden" id="order-id-field">
                                                    <label for="order_id"><?php esc_html_e( 'Existing Order ID:', 'wp-courseware' ); ?></label>
                                                    <input type="text" placeholder="<?php esc_html_e( 'Existing Order ID', 'wp-courseware' ); ?>" id="order_id" name="order_id" value=""/>
                                                </div>
											<?php } ?>

                                            <div class="wpcw-form-field">
                                                <label for="payment_method"><?php esc_html_e( 'Payment Method:', 'wp-courseware' ); ?></label>
                                                <select id="wpcw-order-payment-method-select"
                                                        class="select-field-wpcwselect2"
                                                        name="method"
                                                        data-placeholder="<?php esc_html_e( 'Select a Payment Method', 'wp-courseware' ); ?>"
                                                        data-allow_clear="true">
                                                    <option value=""><?php esc_html_e( 'Select a Payment Method', 'wp-courseware' ); ?></option>
													<?php foreach ( wpcw()->checkout->get_payment_methods() as $method => $method_label ) { ?>
                                                        <option value="<?php echo esc_attr( $method ); ?>" <?php selected( $method, $this->subscription->get_method(), true ); ?>><?php echo esc_html( $method_label ); ?></option>
													<?php } ?>
                                                </select>
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="subscription_status"><?php esc_html_e( 'Status:', 'wp-courseware' ); ?></label>
                                                <select class="select-field-wpcwselect2"
                                                        name="status"
                                                        data-placeholder="<?php esc_html_e( 'Select a Status', 'wp-courseware' ); ?>"
                                                        data-allow_clear="false">
                                                    <option value=""><?php esc_html_e( 'Select a Status', 'wp-courseware' ); ?></option>
													<?php foreach ( wpcw()->subscriptions->get_statuses() as $status => $status_label ) { ?>
                                                        <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $this->subscription->get_status(), true ); ?>><?php echo esc_html( $status_label ) ?></option>
													<?php } ?>
                                                </select>
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="transaction_id"><?php esc_html_e( 'Transaction ID:', 'wp-courseware' ); ?></label>
                                                <input type="text"
                                                       name="transaction_id"
                                                       placeholder="<?php esc_attr_e( 'Transaction ID', 'wp-courseware' ); ?>"
                                                       value="<?php echo esc_attr( $this->subscription->get_transaction_id() ); ?>">
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="transaction_id"><?php esc_html_e( 'Profile ID:', 'wp-courseware' ); ?></label>
                                                <input type="text"
                                                       name="profile_id"
                                                       placeholder="<?php esc_attr_e( 'Profile ID', 'wp-courseware' ); ?>"
                                                       value="<?php echo esc_attr( $this->subscription->get_profile_id() ); ?>">
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="subscription_date"><?php esc_html_e( 'Date Created:', 'wp-courseware' ) ?></label>
                                                <input type="text"
                                                       class="wpcw-date-picker"
                                                       name="created"
                                                       maxlength="10"
                                                       value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $this->subscription->get_created() ) ) ); ?>"
                                                       pattern="<?php echo esc_attr( apply_filters( 'wpcw_subscription_created_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>"/>
                                                @
                                                <input type="number"
                                                       class="hour"
                                                       placeholder="<?php esc_attr_e( 'h', 'wp-courseware' ) ?>"
                                                       name="created_hour"
                                                       min="0"
                                                       max="23"
                                                       step="1"
                                                       value="<?php echo esc_attr( date_i18n( 'H', strtotime( $this->subscription->get_created() ) ) ); ?>"
                                                       pattern="([01]?[0-9]{1}|2[0-3]{1})"/> :
                                                <input type="number"
                                                       class="minute"
                                                       placeholder="<?php esc_attr_e( 'm', 'wp-courseware' ) ?>"
                                                       name="created_minute"
                                                       min="0"
                                                       max="59"
                                                       step="1"
                                                       value="<?php echo esc_attr( date_i18n( 'i', strtotime( $this->subscription->get_created() ) ) ); ?>"
                                                       pattern="[0-5]{1}[0-9]{1}"/>
                                                <input type="hidden"
                                                       name="created_second"
                                                       value="<?php echo esc_attr( date_i18n( 's', strtotime( $this->subscription->get_created() ) ) ); ?>"/>
                                            </div>

                                            <div class="wpcw-form-field">
                                                <label for="subscription_date"><?php esc_html_e( 'Expiration Date:', 'wp-courseware' ) ?></label>
                                                <input type="text"
                                                       class="wpcw-date-picker"
                                                       name="expiration"
                                                       maxlength="10"
                                                       value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $this->subscription->get_expiration() ) ) ); ?>"
                                                       pattern="<?php echo esc_attr( apply_filters( 'wpcw_subscription_created_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>"/>
                                                @
                                                <input type="number"
                                                       class="hour"
                                                       placeholder="<?php esc_attr_e( 'h', 'wp-courseware' ) ?>"
                                                       name="expiration_hour"
                                                       min="0"
                                                       max="23"
                                                       step="1"
                                                       value="<?php echo esc_attr( date_i18n( 'H', strtotime( $this->subscription->get_expiration() ) ) ); ?>"
                                                       pattern="([01]?[0-9]{1}|2[0-3]{1})"/> :
                                                <input type="number"
                                                       class="minute"
                                                       placeholder="<?php esc_attr_e( 'm', 'wp-courseware' ) ?>"
                                                       name="expiration_minute"
                                                       min="0"
                                                       max="59"
                                                       step="1"
                                                       value="<?php echo esc_attr( date_i18n( 'i', strtotime( $this->subscription->get_expiration() ) ) ); ?>"
                                                       pattern="[0-5]{1}[0-9]{1}"/>
                                                <input type="hidden"
                                                       name="expiration_second"
                                                       value="<?php echo esc_attr( date_i18n( 's', strtotime( $this->subscription->get_expiration() ) ) ); ?>"/>
                                            </div>

                                            <div class="clear"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php if ( $parent_order = $this->subscription->get_order() ) { ?>
                                <div id="wpcw-related-order-metabox" class="postbox">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Parent Order', 'wp-courseware' ); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>

                                    <h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Parent Order', 'wp-courseware' ); ?></span></h2>

                                    <div class="inside">
                                        <div id="wpcw-related-order-table-wrapper">
                                            <h2 class="wpcw-related-order-heading"><?php esc_html_e( 'Parent Order', 'wp-courseware' ); ?></h2>
                                            <table class="wpcw-related-order-table">
                                                <thead>
                                                <tr>
                                                    <th class="number"><?php esc_html_e( 'Number', 'wp-courseware' ); ?></th>
                                                    <th class="type"><?php esc_html_e( 'Type', 'wp-courseware' ); ?></th>
                                                    <th class="date"><?php esc_html_e( 'Date', 'wp-courseware' ); ?></th>
                                                    <th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
                                                    <th class="total"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td class="number"><a href="<?php echo $parent_order->get_order_edit_url(); ?>"><?php printf( '#%s', $parent_order->get_order_number() ); ?></a>
                                                    </td>
                                                    <td class="type"><?php echo wpcw_get_order_type_name( $parent_order->get_order_type() ); ?></td>
                                                    <td class="date"><abbr title="<?php echo $parent_order->get_date_created( true ); ?>"><?php echo $parent_order->get_date_created( true ); ?></abbr>
                                                    </td>
                                                    <td class="status">
                                                        <mark class="mark-status status-<?php echo $parent_order->get_order_status(); ?>">
															<?php echo wpcw_get_order_status_name( $parent_order->get_order_status() ); ?>
                                                        </mark>
                                                    </td>
                                                    <td class="total"><?php echo $parent_order->get_total( true ); ?></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
							<?php } ?>

							<?php if ( $payments = $this->subscription->get_payments() ) { ?>
                                <div id="wpcw-payments-metabox" class="postbox">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Payments', 'wp-courseware' ); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>

                                    <h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Payment', 'wp-courseware' ); ?></span></h2>

                                    <div class="inside">
                                        <div id="wpcw-payments-table-wrapper">
                                            <h2 class="wpcw-payments-heading"><?php esc_html_e( 'Payments', 'wp-courseware' ); ?></h2>
                                            <table class="wpcw-payments-table">
                                                <thead>
                                                <tr>
                                                    <th class="number"><?php esc_html_e( 'Number', 'wp-courseware' ); ?></th>
                                                    <th class="type"><?php esc_html_e( 'Type', 'wp-courseware' ); ?></th>
                                                    <th class="date"><?php esc_html_e( 'Date', 'wp-courseware' ); ?></th>
                                                    <th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
                                                    <th class="total"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></th>
                                                </tr>
                                                </thead>
                                                <tbody>
												<?php /** @var Order $payment */ ?>
												<?php foreach ( $payments as $payment ) { ?>
                                                    <tr>
                                                        <td class="number"><a href="<?php echo $payment->get_order_edit_url(); ?>"><?php printf( '#%s', $payment->get_order_number() ); ?></a></td>
                                                        <td class="type"><?php echo wpcw_get_order_type_name( $payment->get_order_type() ); ?></td>
                                                        <td class="date"><abbr title="<?php echo $payment->get_date_created( true ); ?>"><?php echo $payment->get_date_created( true ); ?></abbr></td>
                                                        <td class="status">
                                                            <mark class="mark-status status-<?php echo $payment->get_order_status(); ?>">
																<?php echo wpcw_get_order_status_name( $payment->get_order_status() ); ?>
                                                            </mark>
                                                        </td>
                                                        <td class="total"><?php echo $payment->get_total( true ); ?></td>
                                                    </tr>
												<?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
							<?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
		<?php
		do_action( 'wpcw_admin_page_subscription_display_bottom', $this );
	}
}
