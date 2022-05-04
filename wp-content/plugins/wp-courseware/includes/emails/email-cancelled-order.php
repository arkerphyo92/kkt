<?php
/**
 * WP Courseware Email - Cancelled Order
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
 * Class Email_Cancelled_Order.
 *
 * @since 4.3.0
 */
class Email_Cancelled_Order extends Email {

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string The order object type.
	 * @since 4.3.0
	 */
	public $object_type = 'order';

	/**
	 * Email New Order constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id             = 'email_cancelled_order';
		$this->title          = esc_html__( 'Cancelled Order', 'wp-courseware' );
		$this->description    = esc_html__( 'Cancelled order emails are sent to chosen recipient(s) when orders have been marked cancelled (if they were previously processing or on-hold).', 'wp-courseware' );
		$this->template_html  = 'emails/admin-cancelled-order.php';
		$this->template_plain = 'emails/plain/admin-cancelled-order.php';
		$this->object_type    = 'order';

		parent::__construct();
	}

	/**
	 * Load Email Cacnelled Order.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger the email.
		add_action( 'wpcw_order_status_pending_to_cancelled', array( $this, 'trigger' ), 10, 2 );
		add_action( 'wpcw_order_status_processing_to_cancelled', array( $this, 'trigger' ), 10, 2 );
		add_action( 'wpcw_order_status_on-hold_to_cancelled', array( $this, 'trigger' ), 10, 2 );
		add_action( 'wpcw_order_status_cancelled', array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( '[{site_title}] Cancelled order ({order_number}) - {order_date}', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'Cancelled Order', 'wp-courseware' );
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

		<p>An order has been cancelled by <strong>{student_name}</strong>. Below are the details:</p>

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
		$plain_text .= "An order has been cancelled by '{student_name}. Below are the details:\r\n";
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

		if ( 'email_cancelled_order' === $this->get_id() && ( $this->object instanceof Order ) && $this->object->get_student_email() && ( $this->object->get_student_first_name() || $this->object->get_student_last_name() ) ) {
			$headers .= 'Reply-to: ' . $this->object->get_student_full_name() . ' <' . $this->object->get_student_email() . ">\r\n";
		}

		return $headers;
	}

	/**
	 * Get Cancelled Order Email Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$cancelled_order_merge_tags = array(
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

		$merge_tags = array_merge( $merge_tags, $cancelled_order_merge_tags );

		return apply_filters( 'wpcw_email_cancelled_order_merge_tags', $merge_tags );
	}

	/**
	 * Trigger Email Cancelled Order.
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

		$this->setup();

		return $this->trigger_send();
	}
}
