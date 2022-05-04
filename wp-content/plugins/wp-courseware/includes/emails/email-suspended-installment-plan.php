<?php
/**
 * WP Courseware Email - Suspended Installment Plan
 *
 * @package WPCW
 * @subpackage Emails
 * @since 4.6.0
 */
namespace WPCW\Emails;

use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Email_Suspended_Installment_Plan.
 *
 * @since 4.6.0
 */
class Email_Suspended_Installment_Plan extends Email {

	/**
	 * @var Subscription The subscription object.
	 * @since 4.6.0
	 */
	public $object;

	/**
	 * @var string The email object type.
	 * @since 4.6.0
	 */
	public $object_type = 'subscription';

	/**
	 * Email New Order constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {
		$this->id             = 'email_suspended_installment_plan';
		$this->title          = esc_html__( 'Suspended Installment Plan', 'wp-courseware' );
		$this->description    = esc_html__( 'Suspended installment plan emails are sent to chosen recipient(s) when a students installment plan is suspended (either by the administrator or student).', 'wp-courseware' );
		$this->template_html  = 'emails/admin-suspended-installment-plan.php';
		$this->template_plain = 'emails/plain/admin-suspended-installment-plan.php';

		parent::__construct();
	}

	/**
	 * Load Email.
	 *
	 * @since 4.6.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger this email.
		add_action( 'wpcw_subscription_status_suspended', array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.6.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( '[{site_title}] Installment Plan Suspended', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.6.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'Installment Plan Suspended', 'wp-courseware' );
	}

	/**
	 * Get Default Email Content - Html.
	 *
	 * @since 4.6.0
	 *
	 * @return string The default email html text content.
	 */
	public function get_default_content_html() {
		ob_start();
		?>
		<p>Hello,</p>

		<p>A installment plan has been suspended. Below are the details:</p>

		<p>{subscription_details_table}</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Default Email Content - Plain.
	 *
	 * @since 4.6.0
	 *
	 * @return string The default email html text content.
	 */
	public function get_default_content_plain() {
		$plain_text = "Hello,\r\n";
		$plain_text .= "An installment plan has been suspended. Below are the details:\r\n";
		$plain_text .= "{subscription_details_table}\r\n";

		return $plain_text;
	}

	/**
	 * Get Suspended Subscription Email Merge Tags.
	 *
	 * @since 4.6.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$suspended_installment_plan_merge_tags = array(
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

		$merge_tags = array_merge( $merge_tags, $suspended_installment_plan_merge_tags );

		return apply_filters( 'wpcw_email_suspended_installment_plan_merge_tags', $merge_tags );
	}

	/**
	 * Trigger Email Suspended Subscription.
	 *
	 * @since 4.6.0
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

		if ( $student_email = $this->object->get_student_email() ) {
			$this->recipient = $student_email;
		}

		if ( $this->object instanceof Subscription && ! $this->object->is_installment_plan() ) {
			return;
		}

		$this->setup();

		return $this->trigger_send();
	}
}
