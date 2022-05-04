<?php
/**
 * WP Courseware Student Page.
 *
 * @since 4.3.0
 * @subpackage Admin\Pages
 * @package WPCW
 */

namespace WPCW\Admin\Pages;

use WPCW\Models\Note;
use WPCW\Models\Order;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Order.
 *
 * @since 4.3.0
 */
class Page_Order extends Page {

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	protected $order;

	/**
	 * Orders Page Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		$order_id = wpcw_post_var( 'order_id' ) ? wpcw_post_var( 'order_id' ) : wpcw_get_var( 'order_id' );

		if ( $order_id ) {
			$this->order = new Order( $order_id );
		}

		if ( empty( $this->order ) ) {
			$this->order = new Order();
			$this->order->create( array( 'created_via' => 'admin' ) );
			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'order_id' => $this->order->get_order_id() ), $this->get_url() ) ) );
			exit;
		}
	}

	/**
	 * Highlight Orders Parent Submenu.
	 *
	 * @since 4.3.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = 'wpcw';
		$submenu_file = 'wpcw-orders';
	}

	/**
	 * Get Order Menu Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Order', 'wp-courseware' );
	}

	/**
	 * Get Order Page Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Edit Order', 'wp-courseware' );
	}

	/**
	 * Get Order Page Capability.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_orders_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Order Page Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-order';
	}

	/**
	 * Is Order Page Hidden?
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_hidden() {
		return true;
	}

	/**
	 * Get Order Action Buttons.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-orders' ), admin_url( 'admin.php' ) ),
			esc_html__( 'Back to Orders', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-subscriptions' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Subscriptions', 'wp-courseware' )
		);

		return $actions;
	}

	/**
	 * Get Order Post Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The order url for the form post method.
	 */
	protected function get_order_post_url() {
		return esc_url_raw( add_query_arg( array( 'order_id' => $this->order->get_order_id() ), $this->get_url() ) );
	}

	/**
	 * Get Delete Action Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The delete action url.
	 */
	protected function get_order_delete_action_url() {
		return wp_nonce_url( add_query_arg( array(
			'page'     => 'wpcw-orders',
			'order_id' => $this->order->get_order_id(),
			'action'   => 'delete',
		), admin_url( 'admin.php' ) ), 'orders-nonce' );
	}

	/**
	 * Process Order.
	 *
	 * @since 4.3.0
	 */
	public function process() {
		if ( empty( $_POST['wpcw_order_nonce'] ) || ! wp_verify_nonce( $_POST['wpcw_order_nonce'], 'wpcw_save_order' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			return;
		}

		$order_action = ! empty( $_POST['wpcw_order_action'] ) ? $_POST['wpcw_order_action'] : '';

		$order_id = wpcw_post_var( 'order_id' ) ? wpcw_post_var( 'order_id' ) : wpcw_get_var( 'order_id' );

		$this->process_actions( $order_action, $order_id );

		if ( $order_id = wpcw()->orders->process_order( $_POST ) ) {
			if ( ! $order_action ) {
				wpcw_add_admin_notice_success( esc_html__( 'Order Updated!', 'wp-courseware' ) );
			}
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'order_id' => $order_id ), $this->get_url() ) ) );
		exit;
	}

	/**
	 * Process Order Actions.
	 *
	 * @since 4.3.0
	 *
	 * @param string $order_action The order action.
	 * @param int $order_id The order id.
	 */
	public function process_actions( $order_action, $order_id ) {
		if ( ! array_key_exists( $order_action, $this->get_order_actions() ) ) {
			return;
		}

		switch ( $order_action ) {
			case 'email-student-invoice' :
				wpcw()->emails->send_student_invoice( $order_id );
				break;
			case 'email-new-order' :
				wpcw()->emails->send_new_order_email( $order_id );
				break;
		}

		/**
		 * Action: Process Order Actions.
		 *
		 * @since 4.3.0
		 *
		 * @param string $order_action The order action.
		 * @param int $order_id The order id.
		 * @param Page_Order The order page object.
		 */
		do_action( 'wpcw_order_process_actions', $order_action, $order_id, $this );
	}

	/**
	 * Get Views.
	 *
	 * @since 4.3.0
	 *
	 * @return array The views that need to be included.
	 */
	public function get_views() {
		$common_views = $this->get_common_views();

		$page_views = array(
			'orders/order-items-table',
		);

		return apply_filters( 'wpcw_orders_page_views', array_merge( $common_views, $page_views ) );
	}

	/**
	 * Get Order Actions.
	 *
	 * @since 4.3.0
	 *
	 * @return array $order_actions The array of order actions.
	 */
	public function get_order_actions() {
		return apply_filters( 'wpcw_order_actions', array(
			'email-student-invoice' => esc_html__( 'Email Invoice / Order Details to Student', 'wp-courseware' ),
			'email-new-order'       => esc_html__( 'Resend New Order Notification', 'wp-courseware' ),
		) );
	}

	/**
	 * Student Page Display.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed
	 */
	protected function display() {
		do_action( 'wpcw_admin_page_order_display_bottom', $this );
		?>
		<form class="wpcw-form" action="<?php echo $this->get_order_post_url(); ?>" method="post">
			<?php wp_nonce_field( 'wpcw_save_order', 'wpcw_order_nonce' ); ?>
			<input type="hidden" name="order_id" value="<?php echo $this->order->get_order_id(); ?>"/>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables ui-sortable">
							<div id="wpcw-order-actions" class="postbox">
								<button type="button" class="handlediv" aria-expanded="true">
									<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Order Notes', 'wp-courseware' ); ?></span>
									<span class="toggle-indicator" aria-hidden="true"></span>
								</button>

								<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Order Actions', 'wp-courseware' ); ?></span></h2>

								<div class="inside">
									<ul class="wpcw-order-actions-list submitbox">
										<?php if ( $order_actions = $this->get_order_actions() ) { ?>
											<li class="wpcw-primary-actions wide">
												<select name="wpcw_order_action">
													<option value=""><?php esc_html_e( 'Choose an action...', 'wp-courseware' ); ?></option>
													<?php foreach ( $order_actions as $action => $label ) { ?>
														<option value="<?php echo $action; ?>"><?php echo $label; ?></option>
													<?php } ?>
												</select>
												<button class="button wpcw-reload"><span><?php esc_html_e( 'Apply', 'wp-courseware' ); ?></span></button>
											</li>
										<?php } ?>
										<li class="wide">
											<div class="wpcw-order-delete-action">
												<a class="submitdelete deletion wpcw_delete_item"
												   href="<?php echo esc_url_raw( $this->get_order_delete_action_url() ); ?>"
												   title="<?php _e( "Are you sure you want to delete this order?\n\nThis CANNOT be undone!", 'wp-courseware' ); ?>">
													<?php esc_html_e( 'Delete', 'wp-courseware' ); ?>
												</a>
											</div>
											<button type="submit" class="button button-primary wpcw-update-order wpcw-save-order" name="wpcw_order_submit">
												<?php esc_html_e( 'Update', 'wp-courseware' ); ?>
											</button>
										</li>
									</ul>
								</div>
							</div>

							<?php if ( $notes = $this->order->get_notes() ) { ?>
								<div id="wpcw-notes" class="postbox">
									<button type="button" class="handlediv" aria-expanded="true">
										<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Order Notes', 'wp-courseware' ); ?></span>
										<span class="toggle-indicator" aria-hidden="true"></span>
									</button>

									<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Order Notes', 'wp-courseware' ); ?></span></h2>

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
															<?php printf( __( 'Added: %s', 'wp-courseware' ), wpcw_format_datetime( $note->get_date_created(), 'F j, Y @ g:i:s a' ) ); ?>
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
							<div id="wpcw-order-details-metabox" class="postbox">
								<button type="button" class="handlediv" aria-expanded="true">
									<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Order Details', 'wp-courseware' ); ?></span>
									<span class="toggle-indicator" aria-hidden="true"></span>
								</button>
								<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Order Details', 'wp-courseware' ); ?></span></h2>
								<div class="inside">
									<div id="wpcw-order-details" class="panel wpcw-order-data">
										<h2 class="wpcw-order-data-heading"><?php printf( __( 'Order #%s', 'wp-courseware' ), $this->order->get_order_id() ); ?></h2>

										<?php if ( $student_ip_address = $this->order->get_student_ip_address() ) { ?>
											<p class="wpcw-order-data-meta order-student-ip-address"><?php printf( __( 'Student IP: <span class="wpcw-order-data-meta-customer-ip">%s</span>', 'wp-courseware' ), $student_ip_address ); ?></p>
										<?php } ?>

										<?php if ( $order_key = $this->order->get_order_key() ) { ?>
											<p class="wpcw-order-data-meta order-key"><?php printf( __( 'Order Key: <span class="wpcw-order-data-meta-order-key">%s</span>', 'wp-courseware' ), $order_key ); ?></p>
										<?php } ?>

										<?php if ( ( $order_transaction_id = $this->order->get_transaction_id() ) && 'multiple' !== $order_transaction_id ) { ?>
											<p class="wpcw-order-data-meta order-key"><?php printf( __( 'Transaction ID: <span class="wpcw-order-data-meta-order-key">%s</span>', 'wp-courseware' ), apply_filters( 'wpcw_order_transaction_link_' . $this->order->get_payment_method(), $order_transaction_id, $this->order ) ); ?></p>
										<?php } ?>

										<div class="wpcw-form-fields">
											<div class="wpcw-form-field">
												<label for="order_date"><?php esc_html_e( 'Date Created:', 'wp-courseware' ) ?></label>
												<input type="text"
												       class="wpcw-date-picker"
												       name="date_created"
												       maxlength="10"
												       value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $this->order->get_date_created() ) ) ); ?>"
												       pattern="<?php echo esc_attr( apply_filters( 'wpcw_date_created_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>"/>
												@
												<input type="number"
												       class="hour"
												       placeholder="<?php esc_attr_e( 'h', 'wp-courseware' ) ?>"
												       name="date_created_hour"
												       min="0"
												       max="23"
												       step="1"
												       value="<?php echo esc_attr( date_i18n( 'H', strtotime( $this->order->get_date_created() ) ) ); ?>"
												       pattern="([01]?[0-9]{1}|2[0-3]{1})"/> :
												<input type="number"
												       class="minute"
												       placeholder="<?php esc_attr_e( 'm', 'wp-courseware' ) ?>"
												       name="date_created_minute"
												       min="0"
												       max="59"
												       step="1"
												       value="<?php echo esc_attr( date_i18n( 'i', strtotime( $this->order->get_date_created() ) ) ); ?>"
												       pattern="[0-5]{1}[0-9]{1}"/>
												<input type="hidden"
												       name="date_created_second"
												       value="<?php echo esc_attr( date_i18n( 's', strtotime( $this->order->get_date_created() ) ) ); ?>"/>
											</div>
											<div class="wpcw-form-field">
												<label for="order_status"><?php esc_html_e( 'Status:', 'wp-courseware' ); ?></label>
												<select class="select-field-wpcwselect2"
												        name="order_status"
												        data-placeholder="<?php esc_html_e( 'Select a Status', 'wp-courseware' ); ?>"
												        data-allow_clear="false">
													<option value=""><?php esc_html_e( 'Select a Status', 'wp-courseware' ); ?></option>
													<?php foreach ( wpcw()->orders->get_order_statuses() as $status => $status_label ) { ?>
														<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $this->order->get_order_status(), true ); ?>><?php echo esc_html( $status_label ) ?></option>
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
													<?php if ( ( $student_id = $this->order->get_student_id() ) ) {
														$student_user = get_user_by( 'id', $student_id ); ?>
														<?php if ( $student_user ) { ?>
															<option value="<?php echo absint( $student_id ); ?>" selected="selected"><?php echo esc_attr( $student_user->display_name ); ?></option>
														<?php } ?>
													<?php } ?>
												</select>
											</div>
											<div class="wpcw-form-field">
												<label for="payment_method"><?php esc_html_e( 'Payment Method:', 'wp-courseware' ); ?></label>
												<select id="wpcw-order-payment-method-select"
												        class="select-field-wpcwselect2"
												        name="payment_method"
												        data-placeholder="<?php esc_html_e( 'Select a Payment Method', 'wp-courseware' ); ?>"
												        data-allow_clear="true">
													<option value=""><?php esc_html_e( 'Select a Payment Method', 'wp-courseware' ); ?></option>
													<?php foreach ( wpcw()->checkout->get_payment_methods() as $method => $method_label ) { ?>
														<option value="<?php echo esc_attr( $method ); ?>" <?php selected( $method, $this->order->get_payment_method(), true ); ?>><?php echo esc_html( $method_label ); ?></option>
													<?php } ?>
												</select>
											</div>
											<div class="wpcw-form-field">
												<label for="transaction_id"><?php esc_html_e( 'Payment Transaction ID:', 'wp-courseware' ); ?></label>
												<input type="text" name="transaction_id"
												       placeholder="<?php esc_attr_e( 'Payment Transaction ID', 'wp-courseware' ); ?>"
												       value="<?php echo esc_attr( $this->order->get_transaction_id() ); ?>">
											</div>
											<div class="clear"></div>
										</div>
									</div>
								</div>
							</div>

							<div id="wpcw-order-items-metabox" class="postbox">
								<button type="button" class="handlediv" aria-expanded="true">
									<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Order Items', 'wp-courseware' ); ?></span>
									<span class="toggle-indicator" aria-hidden="true"></span>
								</button>
								<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Order Items', 'wp-courseware' ); ?></span></h2>
								<div class="inside">
									<div id="wpcw-order-items">
										<h2 class="wpcw-order-items-heading"><?php esc_html_e( 'Order Items', 'wp-courseware' ); ?></h2>
										<div class="wpcw-order-items-table-wrapper">
											<wpcw-order-items-table
												orderid="<?php echo $this->order->get_order_id(); ?>"
												ordersubtotal="<?php echo $this->order->get_subtotal( true ); ?>"
												ordertotal="<?php echo $this->order->get_total( true ); ?>"
												orderdiscount="<?php echo $this->order->get_discounts( true ); ?>"
												ordertax="<?php echo $this->order->get_tax( true ); ?>"
												orderitems="<?php echo $this->order->get_order_items_data( true ); ?>"
												ordercoupons="<?php echo $this->order->get_applied_coupons_data( true ); ?>"
												editable="<?php echo $this->order->is_editable(); ?>"
												refundable="<?php echo $this->order->is_refundable(); ?>"></wpcw-order-items-table>
										</div>
									</div>
								</div>
							</div>

							<?php if ( $parent_order = $this->order->get_order_parent() ) { ?>
								<div id="wpcw-related-orders-metabox" class="postbox">
									<button type="button" class="handlediv" aria-expanded="true">
										<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Parent Order', 'wp-courseware' ); ?></span>
										<span class="toggle-indicator" aria-hidden="true"></span>
									</button>

									<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Parent Order', 'wp-courseware' ); ?></span></h2>

									<div class="inside">
										<div id="wpcw-related-orders-table-wrapper">
											<h2 class="wpcw-related-orders-heading"><?php esc_html_e( 'Parent Order', 'wp-courseware' ); ?></h2>
											<table class="wpcw-related-orders-table">
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
													<td class="number">
														<a href="<?php echo $parent_order->get_order_edit_url(); ?>">
															<?php printf( '#%s', $parent_order->get_order_number() ); ?>
														</a>
													</td>
													<td class="type"><?php echo wpcw_get_order_type_name( $parent_order->get_order_type() ); ?></td>
													<td class="date">
														<abbr title="<?php echo $parent_order->get_date_created( true ); ?>">
															<?php echo $parent_order->get_date_created( true ); ?>
														</abbr>
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

							<?php if ( $subscriptions = $this->order->get_subscriptions() ) { ?>
								<div id="wpcw-subscriptions-metabox" class="postbox">
									<button type="button" class="handlediv" aria-expanded="true">
										<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Subscriptions', 'wp-courseware' ); ?></span>
										<span class="toggle-indicator" aria-hidden="true"></span>
									</button>

									<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Subscriptions', 'wp-courseware' ); ?></span></h2>

									<div class="inside">
										<div id="wpcw-subscriptions-table-wrapper">
											<h2 class="wpcw-subscriptions-heading"><?php esc_html_e( 'Subscriptions', 'wp-courseware' ); ?></h2>
											<table cellpadding="0" cellspacing="0" class="wpcw-subscriptions-table">
												<thead>
												<tr>
													<th class="number"><?php esc_html_e( 'ID', 'wp-courseware' ); ?></th>
													<th class="student"><?php esc_html_e( 'Student', 'wp-courseware' ); ?></th>
													<th class="course"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
													<th class="date"><?php esc_html_e( 'Date', 'wp-courseware' ); ?></th>
													<th class="status"><?php esc_html_e( 'Status', 'wp-courseware' ); ?></th>
													<th class="total"><?php esc_html_e( 'Amount', 'wp-courseware' ); ?></th>
												</tr>
												</thead>
												<tbody>
												<?php /** @var Subscription $subscription */ ?>
												<?php foreach ( $subscriptions as $subscription ) { ?>
													<tr>
														<td class="number"><a href="<?php echo $subscription->get_edit_url(); ?>"><?php printf( '#%s', $subscription->get_id() ); ?></a></td>
														<td class="student"><a href="<?php echo $subscription->get_student_edit_url(); ?>"><?php echo $subscription->get_student_name(); ?></a></td>
														<td class="course"><a href="<?php echo $subscription->get_course_edit_url(); ?>"><?php echo $subscription->get_course_title(); ?></a></td>
														<td class="date"><abbr title="<?php echo $subscription->get_created( true ); ?>"><?php echo $subscription->get_created( true ); ?></abbr></td>
														<td class="status">
															<mark class="mark-status status-<?php echo $subscription->get_status(); ?>">
																<?php echo wpcw_get_subscription_status_name( $subscription->get_status() ); ?>
															</mark>
														</td>
														<td class="total"><?php echo $subscription->is_installment_plan() ? $subscription->get_installment_plan_label() : $subscription->get_recurring_amount( true ); ?></td>
													</tr>
												<?php } ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							<?php } ?>

							<?php if ( $related_orders = $this->order->get_related_orders() ) { ?>
								<div id="wpcw-related-orders-metabox" class="postbox">
									<button type="button" class="handlediv" aria-expanded="true">
										<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel: Related Orders', 'wp-courseware' ); ?></span>
										<span class="toggle-indicator" aria-hidden="true"></span>
									</button>

									<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Related Orders', 'wp-courseware' ); ?></span></h2>

									<div class="inside">
										<div id="wpcw-related-orders-table-wrapper">
											<h2 class="wpcw-related-orders-heading"><?php esc_html_e( 'Related Orders', 'wp-courseware' ); ?></h2>
											<table class="wpcw-related-orders-table">
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
												<?php /** @var Order $related_order */ ?>
												<?php foreach ( $related_orders as $related_order ) { ?>
													<tr>
														<td class="number">
															<a href="<?php echo $related_order->get_order_edit_url(); ?>">
																<?php printf( '#%s', $related_order->get_order_number() ); ?>
															</a>
														</td>
														<td class="type"><?php echo wpcw_get_order_type_name( $related_order->get_order_type() ); ?></td>
														<td class="date">
															<abbr title="<?php echo $related_order->get_date_created( true ); ?>">
																<?php echo $related_order->get_date_created( true ); ?>
															</abbr>
														</td>
														<td class="status">
															<mark class="mark-status status-<?php echo $related_order->get_order_status(); ?>">
																<?php echo wpcw_get_order_status_name( $related_order->get_order_status() ); ?>
															</mark>
														</td>
														<td class="total"><?php echo $related_order->get_total( true ); ?></td>
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
		do_action( 'wpcw_admin_page_order_display_bottom', $this );
	}
}
