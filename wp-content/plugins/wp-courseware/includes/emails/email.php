<?php
/**
 * WP Courseware Email.
 *
 * The base email class for all other email classes to inherit.
 *
 * In an effort to not re-invent the wheel part of this code
 * was copied and modified from the open source WooCommerce
 * created by WooThemes / Automattic for use in WP Courseware.
 *
 * Credit is given where its due.
 *
 * @link https://github.com/woocommerce/woocommerce
 *
 * @package WPCW
 * @subpackage Emails
 * @since 4.3.0
 */
namespace WPCW\Emails;

use WPCW\Models\Course;
use WPCW\Models\Order;
use WPCW\Models\Student;
use WPCW\Models\Subscription;

// Exit if accessed directly
defined( 'ABSPATH' ) || die;

/**
 * Class Email.
 *
 * @since 4.3.0
 */
abstract class Email {

	/**
	 * @var string Email Unique Identifier.
	 * @since 4.3.0
	 */
	public $id;

	/**
	 * @var string Email Title.
	 * @since 4.3.0
	 */
	public $title;

	/**
	 * @var string Email Recipeients.
	 * @since 4.3.0
	 */
	public $recipient;

	/**
	 * @var string Is email enabled? 'Yes' if enabled.
	 * @since 4.3.0
	 */
	public $enabled = 'yes';

	/**
	 * @var string Email Description.
	 * @since 4.3.0
	 */
	public $description;

	/**
	 * @var string HTML Email Template Path.
	 * @since 4.3.0
	 */
	public $template_html;

	/**
	 * @var string Plain Text Email Template Path.
	 * @since 4.3.0
	 */
	public $template_plain;

	/**
	 * @var string The template base path.
	 * @since 4.3.0
	 */
	public $template_base;

	/**
	 * @var stirng Email content type.
	 * @since 4.3.0
	 */
	public $type;

	/**
	 * @var bool True when the email is sending.
	 * @since 4.3.0
	 */
	public $sending;

	/**
	 * @var mixed Object for this email. Could be a course, module, unit, or order.
	 * @since 4.3.0
	 */
	public $object;

	/**
	 * @var string Object type string.
	 * @since 4.3.0
	 */
	public $object_type = 'order';

	/**
	 * @var array Email merge tags.
	 * @since 4.3.0
	 */
	public $merge_tags = array();

	/**
	 * @var bool Is student email? Default is false.
	 * @since 4.3.0
	 */
	protected $student_email = false;

	/**
	 * @var bool Is email being testing?
	 * @since 4.3.0
	 */
	protected $testing = false;

	/**
	 * @var array $plain_search List of preg* regular expression patterns to search for, used in conjunction with $plain_replace.
	 * @since 4.3.0
	 * @link https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
	 */
	public $plain_search = array(
		"/\r/",                                                  // Non-legal carriage return
		'/&(nbsp|#0*160);/i',                                    // Non-breaking space
		'/&(quot|rdquo|ldquo|#0*8220|#0*8221|#0*147|#0*148);/i', // Double quotes
		'/&(apos|rsquo|lsquo|#0*8216|#0*8217);/i',               // Single quotes
		'/&gt;/i',                                               // Greater-than
		'/&lt;/i',                                               // Less-than
		'/&#0*38;/i',                                            // Ampersand
		'/&amp;/i',                                              // Ampersand
		'/&(copy|#0*169);/i',                                    // Copyright
		'/&(trade|#0*8482|#0*153);/i',                           // Trademark
		'/&(reg|#0*174);/i',                                     // Registered
		'/&(mdash|#0*151|#0*8212);/i',                           // mdash
		'/&(ndash|minus|#0*8211|#0*8722);/i',                    // ndash
		'/&(bull|#0*149|#0*8226|#0*9679);/i',                    // Bullet
		'/&(pound|#0*163);/i',                                   // Pound sign
		'/&(euro|#0*8364);/i',                                   // Euro sign
		'/&(dollar|#0*36);/i',                                   // Dollar sign
		'/&[^&\s;]+;/i',                                         // Unknown/unhandled entities
		'/[ ]{2,}/',                                             // Runs of spaces, post-handling
	);

	/**
	 * @var array $plain_replace List of pattern replacements corresponding to patterns searched.
	 * @since 4.3.0
	 */
	public $plain_replace = array(
		'',     // Non-legal carriage return
		' ',    // Non-breaking space
		'"',    // Double quotes
		"'",    // Single quotes
		'>',    // Greater-than
		'<',    // Less-than
		'&',    // Ampersand
		'&',    // Ampersand
		'(c)',  // Copyright
		'(tm)', // Trademark
		'(R)',  // Registered
		'--',   // mdash
		'-',    // ndash
		'*',    // Bullet
		'£',    // Pound sign
		'EUR',  // Euro sign. € ?
		'$',    // Dollar sign
		'',     // Unknown/unhandled entities
		' ',    // Runs of spaces, post-handling
	);

	/**
	 * Email constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		if ( empty( $this->id ) ) {
			return;
		}

		if ( is_null( $this->template_base ) ) {
			$this->template_base = apply_filters( 'wpcw_email_templates_path', trailingslashit( WPCW_TEMPLATES_PATH . 'emails' ) );
		}
	}

	/**
	 * Load Email.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		add_action( 'phpmailer_init', array( $this, 'handle_multipart' ) );
	}

	/**
	 * Setup Email.
	 *
	 * @since 4.3.0
	 */
	public function setup() {
		$this->type    = $this->get_setting( $this->id . '_type' );
		$this->enabled = $this->get_setting( $this->id . '_enabled', $this->enabled );

		if ( ! $this->is_student_email() ) {
			$this->recipient = $this->get_setting( $this->id . '_recipient', $this->get_site_admin_email() );
		}

		$this->merge_tags = $this->get_merge_tags();
	}

	/**
	 * Handle Multipart Email.
	 *
	 * @since 4.3.0
	 *
	 * @param \PHPMailer $mailer The php mailer object.
	 *
	 * @return \PHPMailer $mailer The modified php mailer object.
	 */
	public function handle_multipart( $mailer ) {
		if ( $this->sending && 'multipart' === $this->get_type() ) {
			$mailer->AltBody = wordwrap( preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) ) );
			$this->sending   = false;
		}

		return $mailer;
	}

	/**
	 * Get Email Id.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email id.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get Email Object.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed The email object type.
	 */
	public function get_object() {
		return $this->object;
	}

	/**
	 * Get Object Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string The object type identifier.
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get Email Settings Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array The email settings array.
	 */
	public function get_settings_fields() {
		$settings_fields = array();

		$settings_fields[] = array(
			'type'     => 'checkbox',
			'key'      => $this->id . '_enabled',
			'title'    => esc_html__( 'Enable/Disable', 'wp-courseware' ),
			'label'    => esc_html__( 'Enable this email notification', 'wp-courseware' ),
			'desc_tip' => esc_html__( 'When enabled, it will send a specific point in time.', 'wp-courseware' ),
			'default'  => $this->enabled,
		);

		if ( ! $this->is_student_email() ) {
			$settings_fields[] = array(
				'type'        => 'text',
				'key'         => $this->id . '_recipient',
				'title'       => esc_html__( 'Email Recipient(s)', 'wp-courseware' ),
				'placeholder' => esc_html__( 'Recipient(s)', 'wp-courseware' ),
				'default'     => $this->get_site_admin_email(),
				'desc_tip'    => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'wp-courseware' ), '<code>' . $this->get_site_admin_email() . '</code>' ),
			);
		}

		$settings_fields[] = array(
			'type'        => 'text',
			'key'         => $this->id . '_subject',
			'title'       => esc_html__( 'Email Subject', 'wp-courseware' ),
			/* translators: %s: list of merge tags */
			'desc_tip'    => sprintf( __( 'Available merge tags: %s', 'wp-courseware' ), '<code>' . implode( '</code>, <code>', array_keys( $this->get_merge_tags() ) ) . '</code>' ),
			'placeholder' => $this->get_default_subject(),
			'default'     => '',
		);

		$settings_fields[] = array(
			'type'        => 'text',
			'key'         => $this->id . '_heading',
			'title'       => esc_html__( 'Email Heading', 'wp-courseware' ),
			/* translators: %s: list of merge tags */
			'desc_tip'    => sprintf( __( 'Available merge tags: %s', 'wp-courseware' ), '<code>' . implode( '</code>, <code>', array_keys( $this->get_merge_tags() ) ) . '</code>' ),
			'placeholder' => $this->get_default_heading(),
			'default'     => '',
		);

		$settings_fields[] = array(
			'type'     => 'select',
			'key'      => $this->id . '_type',
			'title'    => esc_html__( 'Email Content-Type', 'wp-courseware' ),
			'desc_tip' => esc_html__( 'The email content type.', 'wp-courseware' ),
			'options'  => $this->get_content_types(),
			'default'  => 'html',
		);

		$settings_fields[] = array(
			'type'      => 'email_content',
			'key'       => $this->id . '_content',
			'title'     => esc_html__( 'Email Content', 'wp-courseware' ),
			'desc_tip'  => esc_html__( 'The content of the email depending on the content type above.', 'wp-courseware' ),
			'component' => true,
			'views'     => array( 'settings/settings-field-email-content' ),
			'settings'  => array(
				array(
					'key'     => $this->id . '_content_plain',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'key'     => $this->id . '_content_html',
					'type'    => 'text',
					'default' => '',
				),
			),
		);

		$settings_fields = apply_filters( 'wpcw_email_settings_fields', $settings_fields );

		return apply_filters( "wpcw_email_{$this->id}_settings_fields", $settings_fields );
	}

	/**
	 * Get Email Setting.
	 *
	 * @since 4.3.0
	 *
	 * @param string     $key The setting key.
	 * @param mixed|null $default_value The setting default value.
	 *
	 * @return mixed The setting by key.
	 */
	public function get_setting( $key, $default_value = null ) {
		$value = wpcw_get_setting( $key, $default_value );

		return apply_filters( 'wpcw_email_get_setting', $value, $this, $value, $key, $default_value );
	}

	/**
	 * Is Email Enabled?
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|void
	 */
	public function is_enabled() {
		return apply_filters( 'wpcw_email_enabled_' . $this->id, 'yes' === $this->enabled );
	}

	/**
	 * Get Email Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email type.
	 */
	public function get_type() {
		return $this->type && class_exists( 'DOMDocument' ) ? $this->type : 'plain';
	}

	/**
	 * Get Email Content Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email content type.
	 */
	public function get_content_type() {
		switch ( $this->get_type() ) {
			case 'html' :
				return 'text/html';
			case 'multipart' :
				return 'multipart/alternative';
			default :
				return 'text/plain';
		}
	}

	/**
	 * Get Site Title.
	 *
	 * @since 4.3.0
	 */
	public function get_site_title() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get Site Url.
	 *
	 * @since 4.3.0
	 */
	public function get_site_url() {
		return esc_url_raw( site_url( '/' ) );
	}

	/**
	 * Get Site Admin Email.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void
	 */
	public function get_site_admin_email() {
		return esc_attr( get_option( 'admin_email' ) );
	}

	/**
	 * Get Email Content Types.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_content_types() {
		$types = array( 'plain' => esc_html__( 'Plain text', 'wp-courseware' ) );

		if ( class_exists( 'DOMDocument' ) ) {
			$types['html']      = esc_html__( 'HTML', 'wp-courseware' );
			$types['multipart'] = esc_html__( 'Multipart', 'wp-courseware' );
		}

		return apply_filters( 'wpcw_email_content_types', $types );
	}

	/**
	 * Get Default Subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default subject line.
	 */
	abstract public function get_default_subject();

	/**
	 * Get Default Heading.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default heading.
	 */
	abstract public function get_default_heading();

	/**
	 * Get Email Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_slug() {
		$slug = $this->id;
		$slug = str_replace( '_', '-', $slug );
		$slug = str_replace( 'email-', '', $slug );

		return apply_filters( "wpcw_email_{$this->id}_slug", $slug );
	}

	/**
	 * Get Email Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email title.
	 */
	public function get_title() {
		return apply_filters( 'wpcw_email_title', $this->title, $this );
	}

	/**
	 * Get Email Description.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'wpcw_email_description', $this->description, $this );
	}

	/**
	 * Is Student Email?
	 *
	 * @since 4.3.0
	 *
	 * @return bool
	 */
	public function is_student_email() {
		return $this->student_email;
	}

	/**
	 * Get Valid Recipients.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email recipients.
	 */
	public function get_recipient() {
		$recipient  = apply_filters( 'wpcw_email_recipient_' . $this->id, $this->recipient );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );

		return implode( ', ', $recipients );
	}

	/**
	 * Get email subject.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'wpcw_email_subject_' . $this->id, $this->format_string( $this->get_setting( $this->id . '_subject', $this->get_default_subject() ) ), $this->object );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'wpcw_email_heading_' . $this->id, $this->format_string( $this->get_setting( $this->id . '_heading', $this->get_default_heading() ) ), $this->object );
	}

	/**
	 * Get Merge Tags.
	 *
	 * @since 4.3.0
	 *
	 * @return array Email merge tags.
	 */
	public function get_merge_tags() {
		return apply_filters( 'wpcw_email_default_merge_tas', array(
			'{site_title}'       => array(
				'title' => esc_html__( 'Site Title', 'wp-courseware' ),
				'value' => $this->get_site_title(),
			),
			'{site_url}'         => array(
				'title' => esc_html__( 'Site Url', 'wp-courseware' ),
				'value' => $this->get_site_url(),
			),
			'{site_admin_email}' => array(
				'title' => esc_html__( 'Admin Email', 'wp-courseware' ),
				'value' => $this->get_site_admin_email(),
			),
		) );
	}

	/**
	 * Get Email Content - Html.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email html text content.
	 */
	public function get_content_html() {
		return $this->get_setting( $this->id . '_content_html', $this->get_default_content_html() );
	}

	/**
	 * Get Default Email Content - Html.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default email html text content.
	 */
	public function get_default_content_html() {
		return '';
	}

	/**
	 * Get Email Content - Plain Text.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email plain text content.
	 */
	public function get_content_plain() {
		return $this->get_setting( $this->id . '_content_plain', $this->get_default_content_plain() );
	}

	/**
	 * Get Default Email Content - Plain Text.
	 *
	 * @since 4.3.0
	 *
	 * @return string The default email plain text content.
	 */
	public function get_default_content_plain() {
		return '';
	}

	/**
	 * Get Raw Email Content.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email content.
	 */
	public function get_content_raw() {
		$this->sending = true;

		if ( 'plain' === $this->get_type() ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, wp_strip_all_tags( $this->format_string( $this->get_content_plain() ) ) );
		} else {
			$email_content = wpautop( wp_kses_post( wptexturize( $this->format_string( $this->get_content_html() ) ) ) );
		}

		return wordwrap( $email_content, 70 );
	}

	/**
	 * Get Content.
	 *
	 * @since 4.3.0
	 *
	 * @return string The content template.
	 */
	public function get_content() {
		$template = 'html' === $this->get_type() ? $this->template_html : $this->template_plain;

		$content = wpcw_get_template_html( $template, array(
			'object'  => $this->object,
			'heading' => $this->get_heading(),
			'content' => $this->get_content_raw(),
			'email'   => $this,
		) );

		return $content;
	}

	/**
	 * Get Template base.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|string|void
	 */
	public function get_template_base() {
		return $this->template_base;
	}

	/**
	 * Get Theme Template File.
	 *
	 * @since 4.3.0
	 *
	 * @param string $template The theme email template.
	 *
	 * @return string
	 */
	public function get_theme_template_file( $template ) {
		return wpcw_get_theme_template_file( $template );
	}

	/**
	 * Format email string.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $string Text to replace merge_tags in.
	 *
	 * @return string
	 */
	public function format_string( $string ) {
		$find    = array_keys( $this->merge_tags );
		$replace = wpcw_array_column( $this->merge_tags, 'value' );

		return apply_filters( 'wpcw_email_format_string', str_replace( $find, $replace, $string ), $this );
	}

	/**
	 * Apply inline styles to dynamic content.
	 *
	 * @since 4.3.0
	 *
	 * @param string The content of the email.
	 *
	 * @return string The modified content of the email.
	 */
	public function style_inline( $content ) {
		if ( in_array( $this->get_content_type(), array( 'text/html', 'multipart/alternative' ) ) && class_exists( 'DOMDocument' ) ) {
			ob_start();
			wpcw_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'wpcw_email_styles', ob_get_clean() );

			if ( $this->supports_emogrifier() ) {
				$emogrifier_class = '\\Pelago\\Emogrifier';

				if ( ! class_exists( $emogrifier_class ) ) {
					include_once( WPCW_LIB_PATH . 'emogrifier.php' );
				}

				// apply CSS styles inline for picky email clients
				try {
					$emogrifier = new $emogrifier_class( $content, $css );
					$content    = $emogrifier->emogrify();
				} catch ( Exception $e ) {
					$e->getMessage();
				}
			} else {
				$content = '<style type="text/css">' . $css . '</style>' . $content;
			}
		}

		return $content;
	}

	/**
	 * Return if emogrifier library is supported.
	 *
	 * @since 4.6.2
	 *
	 * @return bool
	 */
	protected function supports_emogrifier() {
		return class_exists( 'DOMDocument' ) && version_compare( PHP_VERSION, '5.5', '>=' );
	}

	/**
	 * Get Email Header.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_headers() {
		$header = "Content-Type: " . $this->get_content_type() . "\r\n";

		return apply_filters( 'wpcw_email_headers', $header, $this->id, $this->object );
	}

	/**
	 * Get Email Attachments.
	 *
	 * @since 4.3.0
	 *
	 * @return array The array of email attachments.
	 */
	public function get_attachments() {
		return apply_filters( 'wpcw_email_attachments', array(), $this->id, $this->object );
	}

	/**
	 * Get From Name.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		$from_name = apply_filters( 'wpcw_email_from_name', $this->get_setting( 'email_from_name' ), $this );

		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get From Address.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_from_address() {
		$from_address = apply_filters( 'wpcw_email_from_address', $this->get_setting( 'email_from_address', $this->get_site_admin_email() ), $this );

		return sanitize_email( $from_address );
	}

	/**
	 * Get Footer Text.
	 *
	 * @since 4.3.0
	 *
	 * @return string The email footer text.
	 */
	public function get_footer_text() {
		/**
		 * Filter: Email Footer Text.
		 *
		 * @since 4.3.0
		 *
		 * @param string Email Footer Text.
		 */
		return apply_filters( 'wpcw_email_footer_text', $this->get_setting( 'email_footer_text' ) );
	}

	/**
	 * Get Footer Text Html.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_footer_text_html() {
		return wpautop( wp_kses_post( wptexturize( $this->get_footer_text() ) ) );
	}

	/**
	 * Get Footer Text Plain.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_footer_text_plain() {
		return wp_strip_all_tags( esc_html( $this->get_footer_text() ) );
	}

	/**
	 * Wrap Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email_heading The email heading.
	 * @param string $message The email message.
	 * @param bool   $plain_text Is this email plain text.
	 *
	 * @return string $message The email message.
	 */
	public function wrap_message( $email_heading, $message, $plain_text = false ) {
		ob_start();

		/**
		 * Email Header.
		 *
		 * @since 4.3.0
		 *
		 * @param string $email_heading The email heading.
		 * @param Email The email object.
		 */
		do_action( 'wpcw_email_header', $email_heading, $this );

		echo wpautop( wptexturize( $message ) );

		/**
		 * Email Footer.
		 *
		 * @since 4.3.0
		 *
		 * @param Email The email object.
		 */
		do_action( 'wpcw_email_footer', $this );

		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Send Email.
	 *
	 * @since 4.3.0
	 *
	 * @param string $to The "to" email address.
	 * @param string $subject The "subject" of the email.
	 * @param string $message The "message" of the email.
	 * @param string $headers The string of headers to be sent with the email.
	 * @param string $attachments The string of attachments to be send with the email.
	 *
	 * @return bool True if successful.
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		$message = apply_filters( 'wpcw_mail_content', $this->style_inline( $message ) );
		$return  = wp_mail( $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		return $return;
	}

	/**
	 * Trigger Email.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if successfully triggered.
	 */
	public function trigger_send() {
		$triggered = false;

		$this->log( sprintf( 'Triggered Email: %s', $this->get_title() ) );

		if ( $this->testing && ! $this->get_recipient() ) {
			$this->recipient = $this->get_site_admin_email();
		}

		$this->log( sprintf( 'Sending Email To: %s', $this->get_recipient() ) );

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->log( 'Email Enabled. Sending...' );
			$triggered = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		return $triggered;
	}

	/**
	 * Setup Testing Information.
	 *
	 * @since 4.3.0
	 */
	public function setup_testing_info() {
		$this->testing = true;

		switch ( $this->get_object_type() ) {
			case 'order' :
				$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : false;
				$order    = ( $order_id ) ? wpcw_get_order( $order_id ) : wpcw_get_test_order();
				if ( $this->is_student_email() ) {
					$this->recipient = $order->get_student_email();
				}
				$this->object = $order;
				break;
			case 'subscription' :
				$subscription_id = isset( $_GET['subscription_id'] ) ? absint( $_GET['subscription_id'] ) : false;
				$subscription    = ( $subscription_id ) ? wpcw_get_subscription( $subscription_id ) : wpcw_get_test_subscription();
				if ( $this->is_student_email() ) {
					$this->recipient = $subscription->get_student()->get_email();
				}
				$this->object = $subscription;
				break;
			case 'student' :
				$student_id               = isset( $_GET['student_id'] ) ? absint( $_GET['student_id'] ) : false;
				$student                  = ( $student_id ) ? wpcw_get_student( $student_id ) : wpcw_get_test_student();
				$password_generated       = wp_generate_password();
				$this->object             = $student;
				$this->student_pass       = $password_generated;
				$this->student_login      = stripslashes( $student->get_user_login() );
				$this->student_email      = stripslashes( $student->get_user_email() );
				$this->password_generated = '';
				$this->recipient          = $student->get_email();
				break;
			case 'course' :
				$course_id    = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : false;
				$course       = ( $course_id ) ? wpcw_get_course( $course_id ) : wpcw_get_test_course();
				$this->object = $course;
				break;
		}
	}

	/**
	 * Get Preview Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The preview email url.
	 */
	public function get_preview_url() {
		return wpcw()->emails->get_email_preview_url( $this->get_slug() );
	}

	/**
	 * Preview Email.
	 *
	 * @since 4.3.0
	 *
	 * @return string The html email preview.
	 */
	public function get_preview() {
		$this->setup_testing_info();
		$this->setup();

		return apply_filters( 'wpcw_preview_email_content_' . $this->get_id(), $this->style_inline( $this->get_content() ) );
	}

	/**
	 * Get Send Test Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The test test email url.
	 */
	public function get_send_test_url() {
		return wpcw()->emails->get_send_test_email_url( $this->get_slug() );
	}

	/**
	 * Preview Email.
	 *
	 * @since 4.3.0
	 *
	 * @return string The html email preview.
	 */
	public function send_test_email() {
		$this->setup_testing_info();
		$this->setup();

		return $this->trigger_send();
	}

	/**
	 * Get Merge Tag Value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $tag The merge tag name.
	 * @param string $type The type of object to retrieve the value.
	 *
	 * @return mixed
	 */
	public function get_merge_tag_value( $tag, $type = 'site' ) {
		$value = '';
		$tag   = str_replace( array( '{', '}' ), '', $tag );

		switch ( $type ) {
			case 'site' :
				$value = $this->get_site_merge_tag_value( $tag );
				break;
			case 'order' :
				$value = $this->get_order_merge_tag_value( $tag );
				break;
			case 'subscription' :
				$value = $this->get_subscription_merge_tag_value( $tag );
				break;
			case 'student' :
				$value = $this->get_student_merge_tag_value( $tag );
				break;
			case 'course' :
				break;
		}

		return apply_filters( "wpcw_email_merge_tag_{$tag}_value", $value, $tag, $type, $this );
	}

	/**
	 * Get Site Merge Tag Value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $tag The merge tag.
	 *
	 * @return mixed $value The value of the merge tag.
	 */
	public function get_site_merge_tag_value( $tag ) {
		$value = '';

		switch ( $tag ) {
			case 'site_title' :
				$value = $this->get_site_title();
				break;
			case 'site_url' :
				$value = $this->get_site_url();
				break;
			case 'site_admin_email' :
				$value = $this->get_site_admin_email();
				break;
			case 'reset_password_url' :
				$value = $this->get_reset_password_url();
				break;
		}

		return apply_filters( "wpcw_email_site_merge_tag_{$tag}_value", $value, $tag, $this );
	}

	/**
	 * Get Order Merge Tag Value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $tag The merge tag.
	 *
	 * @return mixed $value The value of the merge tag.
	 */
	public function get_order_merge_tag_value( $tag ) {
		$value = '';

		/** @var Order $order */
		$order = $this->object;

		if ( empty( $order ) || ! $order instanceof Order || ! $order->get_id() ) {
			return $value;
		}

		switch ( $tag ) {
			case 'order_id' :
				$value = $order->get_order_id();
				break;
			case 'order_date' :
				$value = $order->get_date_created( true );
				break;
			case 'order_number' :
				$value = $order->get_order_number();
				break;
			case 'order_items_table' :
				$value = $this->get_order_items_table( $order );
				break;
			case 'order_items_refunded_table' :
				$value = $this->get_order_items_refunded_table( $order );
				break;
			case 'order_url' :
				$value = $order->get_view_order_url();
				break;
			case 'student_name' :
				$value = $order->get_student_full_name();
				break;
			case 'student_email' :
				$value = $order->get_student_email();
				break;
		}

		return apply_filters( "wpcw_email_order_merge_tag_{$tag}_value", $value, $tag, $order, $this );
	}

	/**
	 * Get Subscription Merge Tag Value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $tag The merge tag.
	 *
	 * @return mixed $value The value of the merge tag.
	 */
	public function get_subscription_merge_tag_value( $tag ) {
		$value = '';

		/** @var Subscription $subscription */
		$subscription = $this->object;

		if ( empty( $subscription ) || ! $subscription instanceof Subscription || ! $subscription->get_id() ) {
			return $value;
		}

		/** @var Student $student */
		$student = $subscription->get_student();

		switch ( $tag ) {
			case 'subscription_id' :
				$value = $subscription->get_id();
				break;
			case 'subscription_date' :
				$value = $subscription->get_created( true );
				break;
			case 'subscription_url' :
				$value = $subscription->get_view_url();
				break;
			case 'student_name' :
				$value = $student->get_full_name();
				break;
			case 'student_email' :
				$value = $student->get_email();
				break;
			case 'subscription_details_table' :
				$value = $this->get_subscription_details_table( $subscription );
				break;
		}

		return apply_filters( "wpcw_email_subscription_merge_tag_{$tag}_value", $value, $tag, $subscription, $this );
	}

	/**
	 * Get Student Merge Tag Value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $tag The merge tag.
	 *
	 * @return mixed $value The value of the merge tag.
	 */
	public function get_student_merge_tag_value( $tag ) {
		$value = '';

		/** @var Student $student */
		$student = $this->object;

		if ( empty( $student ) || ! $student instanceof Student || ! $student->get_id() ) {
			return $value;
		}

		switch ( $tag ) {
			case 'student_username' :
				$value = $student->get_user_login();
				break;
			case 'student_name' :
				$value = $student->get_full_name();
				break;
			case 'student_email' :
				$value = $student->get_email();
				break;
			case 'new_account_details' :
				$value = $this->get_new_account_details();
				break;
		}

		return apply_filters( "wpcw_email_student_merge_tag_{$tag}_value", $value, $tag, $student, $this );
	}

	/**
	 * Get Course Merge Tag Value.
	 *
	 * @since 4.3.0
	 *
	 * @param string $tag The merge tag.
	 *
	 * @return mixed $value The value of the merge tag.
	 */
	public function get_course_merge_tag_value( $tag ) {
		$value = '';

		/** @var Course $course */
		$course = $this->object;

		if ( empty( $course ) || ! $course instanceof Course || ! $course->get_id() ) {
			return $value;
		}

		switch ( $tag ) {
			case 'course_title' :
				$value = $course->get_course_title();
				break;
			case 'course_desc' :
				$value = $course->get_course_desc();
				break;
		}

		return apply_filters( "wpcw_email_course_merge_tag_{$tag}_value", $value, $tag, $course, $this );
	}

	/**
	 * Get New Account Details.
	 *
	 * @since 4.3.0
	 *
	 * @return string The new account details string.
	 */
	public function get_new_account_details() { /* Override in child class */ }

	/**
	 * Get Reset Password Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The reset passwor url.
	 */
	public function get_reset_password_url() { /* Override in child class */ }

	/**
	 * Get Email Order Items Table.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return string The order items table.
	 */
	public function get_order_items_table( $order ) {
		if ( 'plain' === $this->get_type() ) {
			$template = wpcw_get_template_html( 'emails/plain/email-order-items-table.php', array( 'order' => $order, 'admin_email' => ( ! $this->is_student_email() ), 'email' => $this ) );
		} else {
			$template = wpcw_get_template_html( 'emails/email-order-items-table.php', array( 'order' => $order, 'admin_email' => ( ! $this->is_student_email() ), 'email' => $this ) );
		}

		return $template;
	}

	/**
	 * Get Email Order Refunded Table.
	 *
	 * @since 4.3.0
	 *
	 * @param Order $order The order object.
	 *
	 * @return string The order items table.
	 */
	public function get_order_items_refunded_table( $order ) {
		if ( 'plain' === $this->get_type() ) {
			$template = wpcw_get_template_html( 'emails/plain/email-order-items-refunded-table.php', array( 'order' => $order, 'admin_email' => ( ! $this->is_student_email() ), 'email' => $this ) );
		} else {
			$template = wpcw_get_template_html( 'emails/email-order-items-refunded-table.php', array( 'order' => $order, 'admin_email' => ( ! $this->is_student_email() ), 'email' => $this ) );
		}

		return $template;
	}

	/**
	 * Get Email Subscription Details Table.
	 *
	 * @since 4.3.0
	 *
	 * @param Subscription $subscription The subscription object.
	 *
	 * @return string The subscription table.
	 */
	public function get_subscription_details_table( $subscription ) {
		if ( 'plain' === $this->get_type() ) {
			$template = wpcw_get_template_html( 'emails/plain/email-subscription-details-table.php', array(
				'subscription' => $subscription,
				'admin_email'  => ( ! $this->is_student_email() ),
				'email'        => $this,
			) );
		} else {
			$template = wpcw_get_template_html( 'emails/email-subscription-details-table.php', array(
				'subscription' => $subscription,
				'admin_email'  => ( ! $this->is_student_email() ),
				'email'        => $this,
			) );
		}

		return $template;
	}

	/**
	 * Log Email Message.
	 *
	 * @since 4.3.0
	 *
	 * @param string $message The log message.
	 */
	public function log( $message = '' ) {
		if ( empty( $message ) ) {
			return;
		}

		$log_entry = "\n" . '====Start Email Log====' . "\n" . $message . "\n" . '====End Email Log====' . "\n";

		wpcw_log( $log_entry );
	}
}
