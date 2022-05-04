<?php
/**
 * WP Courseware Email - Refunded Order.
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
 * Class Email_Refunded_Order.
 *
 * @since 4.3.0
 */
class Email_Refunded_Order extends Email {

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string The email object type.
	 * @since 4.3.0
	 */
	public $object_type = 'order';

	/**
	 * Email New Order constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id             = 'email_refunded_order';
		$this->student_email  = true;
		$this->title          = esc_html__( 'Refunded Order', 'wp-courseware' );
		$this->description    = esc_html__( 'Order refunded emails are sent to customers when their orders are refunded.', 'wp-courseware' );
		$this->template_html  = 'emails/student-refunded-order.php';
		$this->template_plain = 'emails/plain/student-refunded-order.php';

		parent::__construct();
	}

	/**
	 * Load Email Refunded Order.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger this email.
		add_action( 'wpcw_order_status_refunded', array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( 'Your {site_title} order from {order_date} has been refunded', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'Order Refunded', 'wp-courseware' );
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
		<p>Hi {student_name},</p>

		<p>Your order at <strong>{site_title}</strong> has been refunded. Below are the details of your refund:</p>

		<p>{order_items_refunded_table}</p>
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
		$plain_text = "Hi {student_name},\r\n";
		$plain_text .= "Your order at '{site_title}' has been completed. Below are the details or your order:\r\n";
		$plain_text .= "{order_items_refunded_table}\r\n";
		$plain_text .= "Also, you can copy and paste the link below into your browser to see your order details in your account.\r\n";
		$plain_text .= "{order_url}\r\n";

		return $plain_text;
	}

	/**
	 * Get Refunded Order Email Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$refunded_order_merge_tags = array(
			'{order_id}'                   => array(
				'title' => esc_html__( 'Order ID', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_id', 'order' ),
			),
			'{order_date}'                 => array(
				'title' => esc_html__( 'Order Date', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_date', 'order' ),
			),
			'{order_number}'               => array(
				'title' => esc_html__( 'Order Number', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_number', 'order' ),
			),
			'{order_items_table}'          => array(
				'title' => esc_html__( 'Order Items Table', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_items_table', 'order' ),
			),
			'{order_items_refunded_table}' => array(
				'title' => esc_html__( 'Order Items Refund Table', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_items_refunded_table', 'order' ),
			),
			'{order_url}'                  => array(
				'title' => esc_html__( 'Order Url', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'order_url', 'order' ),
			),
			'{student_name}'               => array(
				'title' => esc_html__( 'Student Name', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_name', 'order' ),
			),
			'{student_email}'              => array(
				'title' => esc_html__( 'Student Email', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_email', 'order' ),
			),
		);

		$merge_tags = array_merge( $merge_tags, $refunded_order_merge_tags );

		return apply_filters( 'wpcw_email_refunded_order_merge_tags', $merge_tags );
	}

	/**
	 * Trigger Email Refunded Order.
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

		if ( $student_email = $this->object->get_student_email() ) {
			$this->recipient = $student_email;
		}

		$this->setup();

		return $this->trigger_send();
	}
}
