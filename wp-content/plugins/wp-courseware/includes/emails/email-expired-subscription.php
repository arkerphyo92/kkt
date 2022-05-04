<?php
/**
 * WP Courseware Email - Expired Subscription
 *
 * @package WPCW
 * @subpackage Emails
 * @since 4.3.0
 */
namespace WPCW\Emails;

use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Email_Expired_Subscription.
 *
 * @since 4.3.0
 */
class Email_Expired_Subscription extends Email {

	/**
	 * @var Subscription The subscription object.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string The email object type.
	 * @since 4.3.0
	 */
	public $object_type = 'subscription';

	/**
	 * Email New Order constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id             = 'email_expired_subscription';
		$this->title          = esc_html__( 'Expired Subscription', 'wp-courseware' );
		$this->description    = esc_html__( 'Expired order emails are sent to chosen recipient(s) when a students subscription is expired (either by the administrator or student).', 'wp-courseware' );
		$this->template_html  = 'emails/admin-expired-subscription.php';
		$this->template_plain = 'emails/plain/admin-expired-subscription.php';

		parent::__construct();
	}

	/**
	 * Load Email Expired Subscription.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger the email.
		add_action( 'wpcw_subscription_status_expired', array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( '[{site_title}] Subscription Expired', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'Subscription Expired', 'wp-courseware' );
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

		<p>A subscription has expired. Below are the details:</p>

		<p>{subscription_details_table}</p>
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
		$plain_text .= "A subscription has expired. Below are the details:\r\n";
		$plain_text .= "{subscription_details_table}\r\n";

		return $plain_text;
	}

	/**
	 * Get Expired Subscription Email Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$expired_subscription_merge_tags = array(
			'{subscription_id}'            => array(
				'title' => esc_html__( 'Subscription ID', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'subscription_id', 'subscription' ),
			),
			'{subscription_date}'          => array(
				'title' => esc_html__( 'Subscription Date', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'subscription_date', 'subscription' ),
			),
			'{subscription_url}'           => array(
				'title' => esc_html__( 'Subscription Url', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'subscription_url', 'subscription' ),
			),
			'{subscription_details_table}' => array(
				'title' => esc_html__( 'Subscription Details', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'subscription_details_table', 'subscription' ),
			),
			'{student_name}'               => array(
				'title' => esc_html__( 'Student Name', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_name', 'subscription' ),
			),
			'{student_email}'              => array(
				'title' => esc_html__( 'Student Email', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_email', 'subscription' ),
			),
		);

		$merge_tags = array_merge( $merge_tags, $expired_subscription_merge_tags );

		return apply_filters( 'wpcw_email_expired_subscription_merge_tags', $merge_tags );
	}

	/**
	 * Trigger Email Expired Subscription.
	 *
	 * @since 4.3.0
	 *
	 * @param int          $subscription_id The subscription id.
	 * @param Subscription $subscription The subscription object.
	 */
	public function trigger( $subscription_id, $subscription = false ) {
		if ( $subscription_id && ! $subscription instanceof Subscription ) {
			$subscription = wpcw_get_subscription( $subscription_id );
		}

		if ( $subscription instanceof Subscription ) {
			$this->object = $subscription;
		}

		if ( $this->object instanceof Subscription && $this->object->is_installment_plan() ) {
			return;
		}

		$this->setup();

		return $this->trigger_send();
	}
}
