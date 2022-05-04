<?php
/**
 * WP Courseware Email - Student - Reset Password
 *
 * @package WPCW
 * @subpackage Emails
 * @since 4.3.0
 */
namespace WPCW\Emails;

use WPCW\Models\Student;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Email_Reset_Password.
 *
 * @since 4.3.0
 */
class Email_Reset_Password extends Email {

	/**
	 * @var Student The student object.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string Email object type.
	 * @since 4.3.0
	 */
	public $object_type = 'student';

	/**
	 * @var string Student login.
	 * @since 4.3.0
	 */
	public $student_login;

	/**
	 * @var string Student email.
	 * @since 4.3.0
	 */
	public $student_email;

	/**
	 * @var string Reset password key.
	 * @since 4.3.0
	 */
	public $reset_key;

	/**
	 * Email Reset Password constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->id             = 'email_reset_password';
		$this->student_email  = true;
		$this->title          = esc_html__( 'Reset password', 'wp-courseware' );
		$this->description    = esc_html__( 'Student "reset password" emails are sent when students reset their passwords.', 'wp-courseware' );
		$this->template_html  = 'emails/student-reset-password.php';
		$this->template_plain = 'emails/plain/student-reset-password.php';

		parent::__construct();
	}

	/**
	 * Load Reset Password Email.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		parent::load();

		// Actions to trigger email.
		add_action( 'wpcw_reset_password', array( $this, 'trigger' ), 10, 2 );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	public function get_default_subject() {
		return esc_html__( 'Reset password for {site_title}', 'wp-courseware' );
	}

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading() {
		return esc_html__( 'Reset Password', 'wp-courseware' );
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

		<p>Someone requested that the password be reset for the following account:</p>

		<p>Username: {student_username}</p>

		<p>If this was a mistake, just ignore this email and nothing will happen.</p>

		<p>To reset your password, visit the following address:</p>

		<p><a class="link" href="{reset_password_url}">Click here to reset your password</a></p>

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
		$plain_text .= "Someone requested that the password be reset for the following account:\r\n";
		$plain_text .= "Username: {student_username}\r\n";
		$plain_text .= "If this was a mistake, just ignore this email and nothing will happen.\r\n";
		$plain_text .= "To reset your password, visit the following address.\r\n";
		$plain_text .= "{reset_password_url}\r\n";

		return $plain_text;
	}

	/**
	 * Get Reset Password Email Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email merge tags.
	 */
	public function get_merge_tags() {
		$merge_tags = parent::get_merge_tags();

		$reset_password_merge_tags = array(
			'{student_name}'       => array(
				'title' => esc_html__( 'Student Name', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_name', 'student' ),
			),
			'{student_email}'      => array(
				'title' => esc_html__( 'Student Email', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_email', 'student' ),
			),
			'{student_username}'   => array(
				'title' => esc_html__( 'Student Username', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'student_username', 'student' ),
			),
			'{reset_password_url}' => array(
				'title' => esc_html__( 'Reset Password Url', 'wp-courseware' ),
				'value' => $this->get_merge_tag_value( 'reset_password_url', 'site' ),
			),
		);

		$merge_tags = array_merge( $merge_tags, $reset_password_merge_tags );

		return apply_filters( 'wpcw_email_reset_password_merge_tags', $merge_tags );
	}

	/**
	 * Trigger Reset Password Email.
	 *
	 * @since 4.3.0
	 *
	 * @param int    $student_login The student login.
	 * @param string $reset_key The reset key.
	 */
	public function trigger( $student_login = '', $reset_key = '' ) {
		if ( $student_login && $reset_key ) {
			if ( $user = get_user_by( 'login', $student_login ) ) {
				$this->object = new Student();
				$this->object->set_data( $user->data );
				$this->object->prime_meta_fields();
				$this->student_login = $this->object->get_user_login();
				$this->reset_key     = $reset_key;
				$this->student_email = stripslashes( $this->object->get_user_email() );
				$this->recipient     = $this->student_email;
			}
		}

		$this->setup();

		return $this->trigger_send();
	}

	/**
	 * Get Reset Password Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The reset passwor url.
	 */
	public function get_reset_password_url() {
		$reset_password_url = '';

		if ( empty( $this->student_login ) || empty( $this->reset_key ) ) {
			return $reset_password_url;
		}

		$reset_password_url = add_query_arg( array(
			'key'   => $this->reset_key,
			'login' => rawurlencode( $this->student_login ),
		), wpcw_get_endpoint_url( 'lost-password', '', wpcw_get_page_permalink( 'account' ) ) );

		return $reset_password_url;
	}
}
