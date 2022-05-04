<?php
/**
 * WP Courseware Emails.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.6.3
 */
namespace WPCW\Core;

use WPCW\Emails\Email;
use WPCW\Emails\Email_New_Order;
use WPCW\Emails\Email_Student_Invoice;
use WPCW\Models\Order;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Emails.
 *
 * @since 4.3.0
 */
class Emails {

	/**
	 * @var array Emails array.
	 * @since 4.3.0
	 */
	protected $emails = array();

	/**
	 * Emails constructor.
	 *
	 * @since 4.6.3
	 */
	public function __construct() {
		$this->register_emails();
	}

	/**
	 * Emails Load.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Replace Header and Footer in emails.
		add_action( 'wpcw_email_header', array( $this, 'email_header' ), 10, 2 );
		add_action( 'wpcw_email_footer', array( $this, 'email_footer' ) );

		// Email Footer Text
		add_action( 'wpcw_email_footer_text_html', array( $this, 'email_footer_text_html' ) );
		add_action( 'wpcw_email_footer_text_plain', array( $this, 'email_footer_text_plain' ) );

		// Replace Footer Text.
		add_filter( 'wpcw_email_footer_text', array( $this, 'email_footer_replace_site_title' ) );

		// View & Send Test Email
		add_action( 'wp', array( $this, 'preview_email' ) );
		add_action( 'wp', array( $this, 'send_test_email' ) );

		/**
		 * Action: Hook into emails.
		 *
		 * @since 4.3.0
		 *
		 * @param Emails The emails controller object.
		 */
		do_action( 'wpcw_emails', $this );
	}

	/**
	 * Register Emails.
	 *
	 * @since 4.3.0
	 */
	public function register_emails() {
		$email_classes = array(
			'Email_New_Order',
			'Email_Cancelled_Order',
			'Email_Failed_Order',
			'Email_Completed_Order',
			'Email_Refunded_Order',
			'Email_Student_Invoice',
			'Email_New_Account',
			'Email_Reset_Password',
			'Email_New_Renewal_Order',
			'Email_Completed_Renewal_Order',
			'Email_Cancelled_Subscription',
			'Email_Expired_Subscription',
			'Email_Suspended_Subscription',
			'Email_New_Installment_Order',
			'Email_Completed_Installment_Order',
			'Email_Cancelled_Installment_Plan',
			'Email_Suspended_Installment_Plan',
			'Email_Completed_Installment_Plan',
		);

		foreach ( $email_classes as $email_class ) {
			$class_name = "\\WPCW\\Emails\\{$email_class}";
			if ( class_exists( $class_name ) ) {
				$email = new $class_name();
				if ( $email instanceof Email ) {
					$this->emails[ $email->get_slug() ] = $email;
					$this->emails[ $email->get_slug() ]->load();
				}
			}
		}

		$this->emails = apply_filters( 'wpcw_emails', $this->emails );
	}

	/**
	 * Get Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The settings fields.
	 */
	public function get_settings_fields() {
		$notifications_section_settings = $this->get_notifications_section_settings_fields();
		$sender_section_settings        = $this->get_sender_section_settings_fields();
		$template_section_settings      = $this->get_template_section_settings_fields();

		$settings_fields = array_merge( $notifications_section_settings, $sender_section_settings, $template_section_settings );

		return apply_filters( 'wpcw_emails_settings_fields', $settings_fields );
	}

	/**
	 * Get Notifications Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The notifications section settings fields.
	 */
	public function get_notifications_section_settings_fields() {
		return apply_filters( 'wpcw_notifications_section_settings_fields', array(
			array(
				'type'      => 'emails_table',
				'key'       => 'emails_table',
				'component' => true,
				'wrapper'   => false,
				'settings'  => $this->get_emails_settings(),
			),
		) );
	}

	/**
	 * Get Sender Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The sender section settings fields.
	 */
	public function get_sender_section_settings_fields() {
		return apply_filters( 'wpcw_sender_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'email_sender_section_heading',
				'title' => esc_html__( 'Email Sender', 'wp-courseware' ),
				'desc'  => esc_html__( 'This section lets you customize who the emails are sent from.', 'wp-courseware' ),
			),
			array(
				'type'        => 'text',
				'key'         => 'email_from_name',
				'title'       => esc_html__( '"From" name', 'wp-courseware' ),
				'placeholder' => esc_html__( 'From name', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'How the sender name appears in outgoing emails.', 'wp-courseware' ),
			),
			array(
				'type'        => 'text',
				'key'         => 'email_from_address',
				'title'       => esc_html__( '"From" address', 'wp-courseware' ),
				'placeholder' => esc_html__( 'From address', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'How the sender email appears in outgoing emails.', 'wp-courseware' ),
			),
		) );
	}

	/**
	 * Get Template Section Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The template section settings fields.
	 */
	public function get_template_section_settings_fields() {
		return apply_filters( 'wpcw_template_section_settings_fields', array(
			array(
				'type'  => 'heading',
				'key'   => 'email_template_section_heading',
				'title' => esc_html__( 'Email Template', 'wp-courseware' ),
				'desc'  => esc_html__( 'This section lets you customize the emails look and feel.', 'wp-courseware' ),
			),
			array(
				'type'        => 'imageinput',
				'key'         => 'email_header_image',
				'image_key'   => 'email_header_image_id',
				'title'       => esc_html__( 'Header Image', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Header Image', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'Url to an image you want to show in the email header.', 'wp-courseware' ),
				'component'   => true,
				'settings'    => array(
					array(
						'key'     => 'email_header_image',
						'type'    => 'imageinput',
						'default' => '',
					),
					array(
						'key'     => 'email_header_image_id',
						'type'    => 'number',
						'default' => 0,
					),
				),
			),
			array(
				'type'        => 'textarea',
				'key'         => 'email_footer_text',
				'title'       => esc_html__( 'Footer Text', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Footer Text', 'wp-courseware' ),
				'desc_tip'    => esc_html__( 'The text to appear in the footer of the emails.', 'wp-courseware' ),
				'default'     => '{site_title}',
				'size'        => 'textarea-small',
			),
			array(
				'type'     => 'color',
				'key'      => 'email_base_color',
				'title'    => esc_html__( 'Base Color', 'wp-courseware' ),
				/* translators: %s: default color */
				'desc_tip' => sprintf( __( 'The base color for the email template. Default is %s', 'wp-courseware' ), '<code>#008ec2</code>' ),
				'default'  => '#008ec2',
			),
			array(
				'type'     => 'color',
				'key'      => 'email_background_color',
				'title'    => esc_html__( 'Background Color', 'wp-courseware' ),
				/* translators: %s: default color */
				'desc_tip' => sprintf( __( 'The background color for the email template. Default is %s', 'wp-courseware' ), '<code>#f7f7f7</code>' ),
				'default'  => '#f7f7f7',
			),
			array(
				'type'     => 'color',
				'key'      => 'email_body_background_color',
				'title'    => esc_html__( 'Body Background Color', 'wp-courseware' ),
				/* translators: %s: default color */
				'desc_tip' => sprintf( __( 'The main body background color for the email template. Default is %s', 'wp-courseware' ), '<code>#ffffff</code>' ),
				'default'  => '#ffffff',
			),
			array(
				'type'     => 'color',
				'key'      => 'email_heading_text_color',
				'title'    => esc_html__( 'Heading Text Color', 'wp-courseware' ),
				/* translators: %s: default color */
				'desc_tip' => sprintf( __( 'The heading text color for the email template. Default is %s', 'wp-courseware' ), '<code>#3c3c3c</code>' ),
				'default'  => '#ffffff',
			),
			array(
				'type'     => 'color',
				'key'      => 'email_body_text_color',
				'title'    => esc_html__( 'Body Text Color', 'wp-courseware' ),
				/* translators: %s: default color */
				'desc_tip' => sprintf( __( 'The main body text color for the email template. Default is %s', 'wp-courseware' ), '<code>#3c3c3c</code>' ),
				'default'  => '#3c3c3c',
			),
		) );
	}

	/**
	 * Get Emails.
	 *
	 * @since 4.3.0
	 *
	 * @return array|mixed|void
	 */
	public function get_emails() {
		return $this->emails;
	}

	/**
	 * Get Emails Settings.
	 *
	 * @since 4.3.0
	 */
	public function get_emails_settings() {
		$settings = array();

		if ( empty( $this->emails ) ) {
			return $settings;
		}

		foreach ( $this->emails as $email ) {
			if ( $email instanceof Email ) {
				$email_settings = $email->get_settings_fields();
				if ( ! empty( $email_settings ) ) {
					foreach ( $email_settings as $email_setting ) {
						$settings[] = $email_setting;
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Get Email.
	 *
	 * @since 4.3.0
	 *
	 * @param string $slug The email slug.
	 *
	 * @return Email|null The email object.
	 */
	public function get_email( $slug ) {
		return isset( $this->emails[ $slug ] ) ? $this->emails[ $slug ] : null;
	}

	/**
	 * Get Email Header.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $email_heading heading for the email
	 * @param Email The email object.
	 */
	public function email_header( $email_heading, Email $email ) {
		wpcw_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}

	/**
	 * Email Footer.
	 *
	 * @since 4.3.0
	 *
	 * @param Email The email object.
	 */
	public function email_footer( Email $email ) {
		wpcw_get_template( 'emails/email-footer.php', array( 'email' => $email ) );
	}

	/**
	 * Email Footer - Plain Text.
	 *
	 * @since 4.3.0
	 *
	 * @param Email The email object.
	 */
	public function email_footer_text_plain( Email $email ) {
		echo $email->get_footer_text_plain();
	}

	/**
	 * Email Footer - Html
	 *
	 * @since 4.3.0
	 *
	 * @param Email The email object.
	 */
	public function email_footer_text_html( Email $email ) {
		echo $email->get_footer_text_html();
	}

	/**
	 * Filter callback to replace {site_title} in email footer.
	 *
	 * @since 4.3.0
	 *
	 * @param string $string Email footer text.
	 *
	 * @return string Email footer text with any replacements done.
	 */
	public function email_footer_replace_site_title( $string ) {
		return str_replace( '{site_title}', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $string );
	}

	/**
	 * Get Email Preview Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email_id The email id.
	 *
	 * @return string The email test url.
	 */
	public function get_email_preview_url( $email_id ) {
		return esc_url( add_query_arg( array( 'page' => 'wpcw_preview_email', 'id' => $email_id ), site_url( '/' ) ) );
	}

	/**
	 * Preview Test Email.
	 *
	 * @since 4.3.0
	 *
	 * @param object $wp The WordPress object.
	 */
	public function preview_email( $wp ) {
		if ( ! array_key_exists( 'page', $wp->query_vars ) || $wp->query_vars['page'] != 'wpcw_preview_email' ) {
			return;
		}

		$email_id = isset( $_GET['id'] ) ? esc_attr( $_GET['id'] ) : '';

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || empty( $email_id ) ) {
			return;
		}

		$email = $this->get_email( $email_id );
		echo $email->get_preview();

		die();
	}

	/**
	 * Get Email Test Url.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email_id The email id.
	 *
	 * @return string The email test url.
	 */
	public function get_send_test_email_url( $email_id ) {
		return esc_url( add_query_arg( array( 'page' => 'wpcw_send_test_email', 'id' => $email_id ), site_url( '/' ) ) );
	}

	/**
	 * Send Test Email
	 *
	 * @since 4.3.0
	 *
	 * @param object $wp The WordPress object.
	 */
	public function send_test_email( $wp ) {
		if ( ! array_key_exists( 'page', $wp->query_vars ) || $wp->query_vars['page'] != 'wpcw_send_test_email' ) {
			return;
		}

		$email_id = isset( $_GET['id'] ) ? esc_attr( $_GET['id'] ) : '';

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || empty( $email_id ) ) {
			return;
		}

		$email = $this->get_email( $email_id );
		$email->send_test_email();

		wp_die( sprintf( __( 'A test of the <strong>%s</strong> email has been sent.', 'wp-courseware' ), $email->get_title() ), esc_html__( 'WP Courseware - Send Test Email', 'wp-courseware' ) );
	}

	/**
	 * Send Student Invoice.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 */
	public function send_student_invoice( $order_id ) {
		/** @var Email_Student_Invoice $email */
		$email = $this->get_email( 'student-invoice' );
		if ( $email->trigger( $order_id ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'Student Invoice / Order Details email sent successfully!', 'wp-courseware' ) );
		} else {
			wpcw_add_admin_notice_error( esc_html__( 'There was an error when sending the Student Invoice / Order Details email. Please check your details and try again.', 'wp-courseware' ) );
		}
	}

	/**
	 * Send New Order Email.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 */
	public function send_new_order_email( $order_id ) {
		/** @var Email_New_Order $email */
		$email = $this->get_email( 'new-order' );

		if ( $email->trigger( $order_id ) ) {
			wpcw_add_admin_notice_success( esc_html__( 'New Order email sent successfully!', 'wp-courseware' ) );
		} else {
			wpcw_add_admin_notice_error( esc_html__( 'There was an error when sending the New Order email. Please check your details and try again.', 'wp-courseware' ) );
		}
	}
}
