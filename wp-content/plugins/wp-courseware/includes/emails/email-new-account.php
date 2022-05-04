<?php
/**
 * WP Courseware Email - New Account.
 *
 * @package WPCW
 * @subpackage Emails
 * @since 4.3.0
 */
namespace WPCW\Emails;

use WPCW\Models\Order;
use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Email_New_Account.
 *
 * @since 4.3.0
 */
class Email_New_Account extends Email {

	/**
	 * @var Student The student object.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string The student object type.
	 * @since 4.3.0
	 */
	public $object_type = 'student';

	/**
	 * @var string Student Login.
	 * @since 4.3.0
	 */
	public $student_login;

	/**
	 * @var string Student Email.
	 * @since 4.3.0
	 */
	public $student_email;

	/**
	 * @var string Student Password.
	 * @since 4.3.0
	 */
	public $student_pass;

	/**
	 * @var bool Was the password generated?
	 * @since 4.3.0
	 */
	public $password_generated;

	/**
	 * Email New Order constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id             = 'email_new_account';
		$this->student_email  = true;
		$this->title          = esc_html__( 'New Account', 'wp-courseware' );
		$this->description    = esc_html__( 'Student "new account" emails are sent to the student when a student signs up via checkout or account pages.', 'wp-courseware' );
		$this->template_html  = 'emails/student-new-account.php';
		$this->template_plain = 'emails/plain/student-new-account.php';

		parent::__construct();
	}

	/**
	 * Load Email New Account Email.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger this email.
		add_action( 'wpcw_created_student', array( $this, 'trigger' ), 10, 3 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( 'Your Account on {site_title}', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'Account Details', 'wp-courseware' );
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

		<p>Your account has been successfully created on <strong>{site_title}</strong>.</p>

		<p>Below are your account details:</p>

		<p>{new_account_details}</p>
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
		$plain_text .= "Your account have been created on '{site_title}'.\r\n";
		$plain_text .= "Below are your account details:\r\n";
		$plain_text .= "{new_account_details}\r\n";

		return $plain_text;
	}

	/**
	 * Get New Account Email Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$new_account_merge_tags = array(
			'{student_name}'        => array(
				'title' => esc_html__( 'Student Name', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_name', 'student' ),
			),
			'{student_email}'       => array(
				'title' => esc_html__( 'Student Email', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_email', 'student' ),
			),
			'{new_account_details}' => array(
				'title' => esc_html__( 'New Account Details', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'new_account_details', 'student' ),
			),
		);

		$merge_tags = array_merge( $merge_tags, $new_account_merge_tags );

		return apply_filters( 'wpcw_email_new_account_merge_tags', $merge_tags );
	}

	/**
	 * Trigger New Account Email.
	 *
	 * @since 4.3.0
	 *
	 * @param int   $student_id The student id.
	 * @param array $new_student_data The new student data.
	 * @param bool  $password_generated Was the password generated?
	 *
	 * @return bool True if successfully triggered.
	 */
	public function trigger( $student_id, $new_student_data = array(), $password_generated = false ) {
		if ( $student_id ) {
			$this->object             = new Student( $student_id );
			$this->student_pass       = isset( $new_student_data['user_pass'] ) ? $new_student_data['user_pass'] : '';
			$this->student_login      = stripslashes( $this->object->get_user_login() );
			$this->student_email      = stripslashes( $this->object->get_user_email() );
			$this->password_generated = $password_generated;
			$this->recipient          = $this->student_email;
		}

		$this->setup();

		return $this->trigger_send();
	}

	/**
	 * Get New Account Details.
	 *
	 * @since 4.3.0
	 *
	 * @return string The new account details string.
	 *
	 * @return string $account_details The new account details.
	 */
	public function get_new_account_details() {
		$account_details = '';

		if ( empty( $this->student_login ) ) {
			return $account_details;
		}

		$template = 'html' === $this->get_type() ? 'emails/student-new-account-details.php' : 'emails/student-new-account-details.php';

		$account_details = wpcw_get_template_html( $template, array(
			'student_login'      => $this->student_login,
			'student_pass'       => $this->student_pass,
			'student_email'      => $this->student_email,
			'site_title'         => $this->get_site_title(),
			'password_generated' => $this->password_generated,
			'email'              => $this,
		) );

		return $account_details;
	}
}
