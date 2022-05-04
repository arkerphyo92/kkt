<?php
/**
 * WP Courseware Email - New Renewal Order
 *
 * @package WPCW
 * @subpackage Emails
 * @since 4.3.0
 */
namespace WPCW\Emails;

use WPCW\Models\Order;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Email_New_Renewal_Order.
 *
 * @since 4.3.0
 */
class Email_New_Renewal_Order extends Email {

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string The email objec type.
	 * @since 4.3.0
	 */
	public $object_type = 'order';

	/**
	 * Email New Order constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id             = 'email_new_renewal_order';
		$this->title          = esc_html__( 'New Renewal Order', 'wp-courseware' );
		$this->description    = esc_html__( 'New renewal order emails are sent to chosen recipient(s) when a subscription renewal payment is processed.', 'wp-courseware' );
		$this->template_html  = 'emails/admin-new-renewal-order.php';
		$this->template_plain = 'emails/plain/admin-new-renewal-order.php';

		parent::__construct();
	}

	/**
	 * Load Email New Renewal Order.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger this email.
		add_action( 'wpcw_order_status_completed', array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( '[{site_title}] New subscription renewal order ({order_number}) - {order_date}', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'New Subscription Renewal Order', 'wp-courseware' );
	}

	/**
	 * Get Default Email Content - Html.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default email html text content.
	 */
	public function get_default_content_html() {
		ob_start();
		?>
		<p>Hello,</p>

		<p>A new subscription renewal order has been processed for <strong>{student_name}</strong>. Below are the details:</p>

		<p>{order_items_table}</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Default Email Content - Plain.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default email html text content.
	 */
	public function get_default_content_plain() {
		$plain_text = "Hello,\r\n";
		$plain_text .= "A new subscription renewal order has been processed for '{student_name}. Below are the details:\r\n";
		$plain_text .= "{order_items_table}\r\n";

		return $plain_text;
	}

	/**
	 * Get Email Headers.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_headers() {
		$headers = parent::get_headers();

		if ( 'email_new_renewal_order' === $this->get_id() && ( $this->object instanceof Order ) && $this->object->get_student_email() && ( $this->object->get_student_first_name() || $this->object->get_student_last_name() ) ) {
			$headers .= 'Reply-to: ' . $this->object->get_student_full_name() . ' <' . $this->object->get_student_email() . ">\r\n";
		}

		return $headers;
	}

	/**
	 *Get New Renewal Order Email Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$new_renewal_order_merge_tags = array(
			'{order_id}'          => array(
				'title' => esc_html__( 'Order ID', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_id', 'order' ),
			),
			'{order_date}'        => array(
				'title' => esc_html__( 'Order Date', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_date', 'order' ),
			),
			'{order_number}'      => array(
				'title' => esc_html__( 'Order Number', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_number', 'order' ),
			),
			'{order_items_table}' => array(
				'title' => esc_html__( 'Order Items Table', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_items_table', 'order' ),
			),
			'{order_url}'         => array(
				'title' => esc_html__( 'Order Url', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_url', 'order' ),
			),
			'{student_name}'      => array(
				'title' => esc_html__( 'Student Name', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_name', 'order' ),
			),
			'{student_email}'     => array(
				'title' => esc_html__( 'Student Email', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_email', 'order' ),
			),
		);

		$merge_tags = array_merge( $merge_tags, $new_renewal_order_merge_tags );

		return apply_filters( 'wpcw_email_new_renewal_order_merge_tags', $merge_tags );
	}

	/**
	 * Trigger Email New Renewal Order.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $order_id The order id.
	 * @param Order $order The order object.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! $order instanceof Order ) {
			$order = wpcw_get_order( $order_id );
		}

		if ( $order instanceof Order ) {
			$this->object = $order;
		}

		if ( $this->object instanceof Order && ! $this->object->is_subscription_renewal_payment() ) {
			return;
		}

		$this->setup();

		return $this->trigger_send();
	}
}
