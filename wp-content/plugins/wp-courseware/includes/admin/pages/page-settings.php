<?php
/**
 * WP Courseware Settings Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Emails\Email;
use WPCW\Gateways\Gateway;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Settings.
 *
 * @since 4.1.0
 */
class Page_Settings extends Page {

	/**
	 * @var array Pages Holds an array of pages.
	 * @since 4.3.0
	 */
	protected $pages = array();

	/**
	 * @var Email The email object.
	 * @since 4.3.0
	 */
	protected $email;

	/**
	 * @var Gateway The gateway object.
	 * @since 4.3.0
	 */
	protected $gateway;

	/**
	 * @var array The site permalinks.
	 * @since 4.4.0
	 */
	protected $permalinks;

	/**
	 * Get Settings Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Settings', 'wp-courseware' );
	}

	/**
	 * Get Settings Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Settings', 'wp-courseware' );
	}

	/**
	 * Get Settings Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_settings_page_capability', 'manage_wpcw_settings' );
	}

	/**
	 * Get Settings Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'wpcw-settings';
	}

	/**
	 * Setup Settings Page.
	 *
	 * @since 4.3.0
	 */
	public function setup() {
		if ( $email_slug = wpcw_get_var( 'email' ) ) {
			$this->email = wpcw()->emails->get_email( $email_slug );
		}

		if ( $gateway_slug = wpcw_get_var( 'gateway' ) ) {
			$this->gateway = wpcw()->gateways->get_gateway( $gateway_slug );
		}
	}

	/**
	 * Get Views.
	 *
	 * @since 4.3.0
	 *
	 * @return array The views that need to be included.
	 */
	public function get_views() {
		$views = array();

		if ( ! empty( $this->email ) ) {
			foreach ( $this->email->get_settings_fields() as $field ) {
				if ( isset( $field['component'] ) && $field['component'] && ! empty( $field['views'] ) ) {
					foreach ( $field['views'] as $view ) {
						$views[ $view ] = $view;
					}
				}
			}
		}

		$views['settings/settings-email-actions'] = 'settings/settings-email-actions';

		return $views;
	}

	/**
	 * Get Tabs.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|void
	 */
	public function get_tabs() {
		return apply_filters( 'wpcw_admin_settings_tabs', array(
			'courses'  => apply_filters( 'wpcw_admin_settings_tab_courses', array(
				'label'    => esc_html__( 'Courses', 'wp-courseware' ),
				'sections' => array(
					'general'    => array(
						'label'   => esc_html__( 'General', 'wp-courseware' ),
						'form'    => true,
						'default' => true,
						'fields'  => wpcw()->courses->get_general_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'membership'    => array(
						'label'   => esc_html__( 'Membership', 'wp-courseware' ),
						'form'    => true,
						'default' => true,
						'fields'  => wpcw()->courses->get_membership_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'permalinks' => array(
						'label'   => esc_html__( 'Permalinks', 'wp-courseware' ),
						'form'    => true,
						'default' => false,
						'fields'  => wpcw()->courses->get_permalinks_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'taxonomies' => array(
						'label'   => esc_html__( 'Taxonomies', 'wp-courseware' ),
						'form'    => true,
						'default' => false,
						'fields'  => wpcw()->courses->get_taxonomies_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
				),
			) ),
			'units'    => apply_filters( 'wpcw_admin_settings_tab_units', array(
				'label'    => esc_html__( 'Units', 'wp-courseware' ),
				'sections' => array(
					'general'    => array(
						'label'   => esc_html__( 'General', 'wp-courseware' ),
						'form'    => true,
						'default' => true,
						'fields'  => wpcw()->units->get_general_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'permalinks' => array(
						'label'   => esc_html__( 'Permalinks', 'wp-courseware' ),
						'form'    => true,
						'default' => false,
						'fields'  => wpcw()->units->get_permalinks_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'taxonomies' => array(
						'label'   => esc_html__( 'Taxonomies', 'wp-courseware' ),
						'form'    => true,
						'default' => false,
						'fields'  => wpcw()->units->get_taxonomies_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
				),
			) ),
			'checkout' => apply_filters( 'wpcw_admin_settings_tab_checkout', array(
				'label'    => esc_html__( 'Checkout', 'wp-courseware' ),
				'sections' => array(
					'pages'    => array(
						'label'   => esc_html__( 'Pages', 'wp-courseware' ),
						'form'    => true,
						'default' => true,
						'fields'  => wpcw()->checkout->get_pages_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'currency' => array(
						'label'  => esc_html__( 'Currency', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->checkout->get_currency_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'gateways' => array(
						'label'  => esc_html__( 'Payment Gateways', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->checkout->get_payment_gateways_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'taxes'    => array(
						'label'  => esc_html__( 'Taxes', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->checkout->get_taxes_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'coupons'  => array(
						'label'  => esc_html__( 'Coupons', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->checkout->get_coupons_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'process'  => array(
						'label'  => esc_html__( 'Checkout Process', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->checkout->get_process_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'privacy'  => array(
						'label'  => esc_html__( 'Privacy', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->checkout->get_privacy_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
				),
			) ),
			'students' => apply_filters( 'wpcw_admin_settings_tab_students', array(
				'label'  => esc_html__( 'Students', 'wp-courseware' ),
				'form'   => true,
				'fields' => wpcw()->students->get_settings_fields(),
				'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
			) ),
			'emails'   => apply_filters( 'wpcw_admin_settings_tab_emails', array(
				'label'    => esc_html__( 'Emails', 'wp-courseware' ),
				'sections' => array(
					'notifications' => array(
						'label'   => esc_html__( 'Email Notifications', 'wp-courseware' ),
						'default' => true,
						'form'    => true,
						'fields'  => wpcw()->emails->get_notifications_section_settings_fields(),
						'submit'  => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'sender'        => array(
						'label'  => esc_html__( 'Email Sender', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->emails->get_sender_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
					'template'      => array(
						'label'  => esc_html__( 'Email Template', 'wp-courseware' ),
						'form'   => true,
						'fields' => wpcw()->emails->get_template_section_settings_fields(),
						'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
					),
				),
			) ),
			'style'    => apply_filters( 'wpcw_admin_settings_tab_style', array(
				'label'  => esc_html__( 'Style', 'wp-courseware' ),
				'form'   => true,
				'fields' => wpcw()->styles->get_settings_fields(),
				'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
			) ),
			'addons'   => apply_filters( 'wpcw_admin_settings_tab_addons', array(
				'label'  => esc_html__( 'Add-Ons', 'wp-courseware' ),
				'form'   => true,
				'fields' => array(),
				'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
			) ),
			'license'  => apply_filters( 'wpcw_admin_settings_tab_license', array(
				'label'  => esc_html__( 'License', 'wp-courseware' ),
				'form'   => true,
				'submit' => esc_html__( 'Save Settings', 'wp-courseware' ),
				'fields' => array_merge( wpcw()->license->get_settings_fields(), wpcw()->tracker->get_settings_fields() ),
			) ),
			'support'  => apply_filters( 'wpcw_admin_settings_tab_support', array(
				'label'  => esc_html__( 'Support', 'wp-courseware' ),
				'form'   => false,
				'fields' => array(
					array(
						'key'       => 'support',
						'type'      => 'support',
						'component' => true,
						'views'     => array( 'settings/settings-field-support' ),
					),
				),
			) ),
			'products' => apply_filters( 'wpcw_admin_settings_tab_products', array(
				'label'  => esc_html__( 'Other Products', 'wp-courseware' ),
				'form'   => false,
				'fields' => array(
					array(
						'key'       => 'products',
						'type'      => 'products',
						'component' => true,
						'views'     => array( 'settings/settings-field-products' ),
					),
				),
			) ),
		) );
	}

	/**
	 * Get Tab Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array|mixed The page fields.
	 */
	public function get_tab_fields() {
		$fields = parent::get_tab_fields();

		// Temporary solution to third tier settings.
		if ( ! empty( $this->email ) ) {
			foreach ( $fields as $key => $field ) {
				if ( 'emails_table' === $this->get_field_type( $field ) ) {
					$fields[ $key ]['settings'] = $this->email->get_settings_fields();
				}
			}
		} else {
			foreach ( $fields as $key => $field ) {
				if ( 'emails_table' === $this->get_field_type( $field ) ) {
					$fields[ $key ]['settings'] = array();
				}
			}
		}

		if ( ! empty( $this->gateway ) ) {
			foreach ( $fields as $key => $field ) {
				if ( 'payment_gateways' === $this->get_field_type( $field ) ) {
					$fields[ $key ]['settings'] = $this->gateway->get_settings_fields();
				}
			}
		} else {
			foreach ( $fields as $key => $field ) {
				if ( 'payment_gateways' === $this->get_field_type( $field ) ) {
					$fields[ $key ]['settings'] = array();
				}
			}
		}

		return $fields;
	}

	/**
	 * Process Settings Page.
	 *
	 * @since 4.3.0
	 */
	public function process() {
		if ( ! $this->can_process_form() ) {
			return;
		}

		$this->save_settings();

		wpcw()->settings->load_settings();

		wpcw_add_admin_notice_success( esc_html__( 'Settings Saved Successfully!', 'wp-courseware' ) );

		wp_safe_redirect( $_SERVER['REQUEST_URI'] );
		exit;
	}

	/**
	 * Settings Page Display.
	 *
	 * @since 4.1.0
	 */
	protected function display() {
		/**
		 * Action: Admin Settings Berfore Dispaly.
		 *
		 * @since 4.5.1
		 *
		 * @param Page_Settings $this The page settings object.
		 */
		do_action( 'wpcw_admin_settings_before_display', $this );

		echo '<div id="wpcw-settings">';

		echo '<wpcw-notices>' . do_action( 'wpcw_admin_notices' ) . '</wpcw-notices>';

		/**
		 * Action: Admin Settings Before.
		 *
		 * @since 4.5.1
		 *
		 * @param Page_Settings $this The page settings object.
		 */
		do_action( 'wpcw_admin_settings_before', $this );

		$this->get_tabs_navigation();
		$this->get_tab_content();

		/**
		 * Action: Admin Settings After.
		 *
		 * @since 4.5.1
		 *
		 * @param Page_Settings $this The page settings object.
		 */
		do_action( 'wpcw_admin_settings_after', $this );

		echo '</div>';

		/**
		 * Action: Admin Settings After Display.
		 *
		 * @since 4.5.1
		 *
		 * @param Page_Settings $this The page settings object.
		 */
		do_action( 'wpcw_admin_settings_after_display', $this );
	}

	/**
	 * Field: Course Permalinks
	 *
	 * @since 4.4.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The licenes field html.
	 */
	public function generate_course_permalinks_field_html( $key, $field ) {
		if ( empty( $this->permalinks ) ) {
			$this->permalinks = wpcw_get_permalink_structure();
		}

		$permalink = $this->get_setting( 'course_permalink' );
		$structure = $this->get_setting( 'course_permalink_structure' );

		return sprintf( '<wpcw-settings-field-course-permalinks permalink="%s" structure="%s"></wpcw-settings-field-course-permalinks>', $permalink, $structure );
	}

	/**
	 * Field: Unit Permalinks
	 *
	 * @since 4.4.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The licenes field html.
	 */
	public function generate_unit_permalinks_field_html( $key, $field ) {
		if ( empty( $this->permalinks ) ) {
			$this->permalinks = wpcw_get_permalink_structure();
		}

		$permalink = $this->get_setting( 'unit_permalink' );
		$structure = $this->get_setting( 'unit_permalink_structure' );

		return sprintf( '<wpcw-settings-field-unit-permalinks permalink="%s" structure="%s"></wpcw-settings-field-unit-permalinks>', $permalink, $structure );
	}

	/**
	 * Validate Field: Text
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @since 4.4.0
	 *
	 * @param string      $key The field key.
	 * @param string|null $value The field value.
	 *
	 * @return string The properly sanitized and escaped value.
	 */
	public function validate_text_field( $key, $value ) {
		$permalink_fields = array( 'course_category_base', 'course_tag_base', 'unit_category_base', 'unit_tag_base' );

		if ( in_array( $key, $permalink_fields ) ) {
			return wpcw_sanitize_permalink( wp_unslash( $value ) );
		}

		return parent::validate_text_field( $key, $value );
	}

	/**
	 * Field: License
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The licenes field html.
	 */
	public function generate_license_field_html( $key, $field ) {
		$licensekey    = $this->get_setting( 'licensekey' );
		$licensestatus = $this->get_setting( 'licensestatus' );

		return sprintf( '<wpcw-settings-field-license lkey="%s" lstatus="%s"></wpcw-settings-field-license>', $licensekey, $licensestatus );
	}

	/**
	 * Field: Affiliates
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The affiliates field html.
	 */
	public function generate_affiliates_field_html( $key, $field ) {
		$powered_by   = $this->get_setting( 'show_powered_by' );
		$affiliate_id = $this->get_setting( 'affiliate_id' );

		return sprintf( '<wpcw-settings-field-affiliates poweredby="%s" affid="%s"></wpcw-settings-field-affiliates>', $powered_by, $affiliate_id );
	}

	/**
	 * Field: Restrcited
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The affiliates field html.
	 */
	public function generate_restricted_field_html( $key, $field ) {

		ob_start();
		// setup the editor settings
		$editor_settings = wp_parse_args( array(), array( 'textarea_rows' => 10, 'editor_class' => 'supports-merge-tags wpcw-field-wysywig-textarea' ) );
		?>
		<div id="wpcw-course-settings">
			<div class="meta-box-sortables">
				<div id="wpcw-course-settings-metabox" class="postbox">
					<div class="wpcw-metabox-fields">
						<div class="wpcw-field wpcw-field-accordion wpcw-field-top-label wpcw-field-clear">
							<h3 class="accordion-label"><?php echo $field['label']; ?></h3>
							<abbr class="wpcw-tooltip" title="<?php echo $field['tip']; ?>" rel="wpcw-tooltip">
								<i class="wpcw-fas wpcw-fa-info-circle restricted-top-tooltip"></i>
							</abbr>
							<div class="wpcw-field-wrapper">
								<div class="wpcw-accordion">
		<?php
		// Iterate the configured structure to display the admin inputs
		foreach( $field['fields'] as $key => $field_array ) {

			$title = $field_array['title'];
			$tip = $field_array['tip'];
			$content_key = $field_array['key'];
			$content = get_option( $content_key, $field_array['default'] );

			$accordion_toggle         = sprintf(
				'<button type="button" class="handlediv restricted-content-button" aria-expanded="false">
					<span class="screen-reader-text">%s: %s</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>',
				esc_html__( 'Toggle Accordion', 'wp-courseware' ),
				$title
			);

			echo '
					<div class="wpcw-accordion-item">
						<a href="javascript:void(0);" class="wpcw-accordion-toggle">
							<strong>' . $title . '</strong>
							<abbr class="wpcw-tooltip" title="' . $tip . '" rel="wpcw-tooltip">
								<i class="wpcw-fas wpcw-fa-info-circle"></i>
							</abbr>
							' . $accordion_toggle . '
						</a>
						<div class="wpcw-accordion-content wpcw-editor-container">
			';
			wp_editor( stripslashes( $content ), $content_key, $editor_settings );
			echo '
						</div>
					</div>
			';
		}
		?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php

		return ob_get_clean();

	}

	/**
	 * Field: Styles
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The styles field html.
	 */
	public function generate_styles_field_html( $key, $field ) {
		$css    = $this->get_setting( 'use_default_css' );
		$colors = $this->get_setting( 'customize_colors' );

		return sprintf( '<wpcw-settings-field-styles usecss="%s" customizecolors="%s"></wpcw-settings-field-styles>', $css, $colors );
	}

	/**
	 * Field: Emails Table.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The styles field html.
	 */
	public function generate_emails_table_field_html( $key, $field ) {
		ob_start();

		if ( ! empty( $this->email ) ) {
			echo $this->email_single_html( $key, $field );
		} else {
			echo $this->email_table_html( $key, $field );
		}

		return ob_get_clean();
	}

	/**
	 * Email Single HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The email single html.
	 */
	public function email_single_html( $key, $field ) {
		$merge_tags = $this->email->get_merge_tags();

		if ( empty( $merge_tags ) ) {
			return;
		}

		ob_start();

		?>
		<div id="wpcw-emails" class="wpcw-emails wpcw-emails-single">
			<div class="wpcw-email-fields">
				<div class="wpcw-field-row wpcw-field-row-heading wpcw-field-row-heading-email wpcw-field-clear">
					<h2 class="wpcw-field-heading">
						<a class="crumb-link" href="<?php echo $this->get_current_tab_section_url(); ?>"><?php esc_html_e( 'Email Notifications', 'wp-courseware' ); ?></a>
						<span class="crumb-separator"><i class="wpcw-fas wpcw-fa-chevron-right"></i></span>
						<?php echo $this->email->get_title(); ?>
					</h2>
					<p><?php echo $this->email->get_description(); ?></p>
				</div>
				<?php echo $this->generate_fields_html( $this->email->get_settings_fields() ); ?>
			</div>
			<div class="card wpcw-card wpcw-email-merge-tags-card">
				<h2><?php esc_html_e( 'Email Merge Tags', 'wp-courseware' ); ?></h2>
				<?php foreach ( $this->email->get_merge_tags() as $merge_tag => $merge_tag_details ) { ?>
					<div class="merge-tag">
						<?php if ( isset( $merge_tag_details['title'] ) ) { ?>
							<abbr class="wpcw-tooltip" title="<?php echo $merge_tag_details['title']; ?> " rel="wpcw-tooltip"><i class="wpcw-fas info-circle"></i></abbr>
						<?php } ?>
						<code><?php echo $merge_tag; ?></code>
					</div>
				<?php } ?>

				<wpcw-settings-email-actions objecttype="<?php echo $this->email->get_object_type(); ?>"
				                             viewurl="<?php echo $this->email->get_preview_url(); ?>"
				                             sendurl="<?php echo $this->email->get_send_test_url(); ?>"></wpcw-settings-email-actions>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Email Table HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The email single html.
	 */
	public function email_table_html( $key, $field ) {
		$email_table_columns = apply_filters( 'wpcw_emails_table_columns', array(
			'status'       => '',
			'name'         => esc_html__( 'Email', 'wp-courseware' ),
			'content_type' => esc_html__( 'Content type', 'wp-courseware' ),
			'recipient'    => esc_html__( 'Recipient(s)', 'wp-courseware' ),
			'actions'      => '',
		) );

		$emails = wpcw()->emails->get_emails();

		if ( empty( $emails ) ) {
			return wpcw_admin_notice_error( esc_html__( 'There are no emails defined for WP Courseware.', 'wp-courseware' ), false );
		}

		ob_start();

		?>
		<div class="wpcw-emails">
			<div class="wpcw-field-row wpcw-field-row-heading wpcw-field-clear">
				<h2 class="wpcw-field-heading"><?php esc_html_e( 'Email Notifications', 'wp-courseware' ); ?></h2>
				<p><?php esc_html_e( 'Email notifications sent from WP Courseware are listed below.', 'wp-courseware' ); ?></p>
			</div>
			<div class="wpcw-field-row wpcw-field-row-emails-table wpcw-field-clear">
				<table class="form-table">
					<tbody>
					<tr valign="top">
						<td class="wpcw-emails-wrapper" colspan="2">
							<table class="wpcw-emails-table widefat" cellspacing="0">
								<thead>
								<tr>
									<?php foreach ( $email_table_columns as $column_key => $column ) { ?>
										<th class="wpcw-email-settings-table-<?php echo esc_attr( $column_key ); ?>"><?php echo esc_html( $column ); ?></th>
									<?php } ?>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $emails as $email_slug => $email ) { ?>
									<?php if ( $email instanceof Email ) {
										$email->setup();
										?>
										<tr>
											<?php foreach ( $email_table_columns as $column_key => $column ) {
												$email_url = esc_url( add_query_arg( array( 'email' => $email_slug ), $this->get_current_tab_section_url() ) );
												?>
												<td class="wpcw-email-settings-table-<?php echo esc_attr( $column_key ); ?>">
													<?php
													switch ( $column_key ) {
														case 'name' :
															printf( '<a class="wpcw-email-title" href="%s">%s</a>', $email_url, $email->get_title() );
															if ( $email_description = $email->get_description() ) {
																printf( '<abbr class="wpcw-tooltip" title="%s" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>', esc_html( $email_description ) );
															}
															break;
														case 'recipient' :
															echo ( $email->is_student_email() ) ? esc_html__( 'Student', 'wp-courseware' ) : $email->get_recipient();
															break;
														case 'status' :
															if ( $email->is_enabled() ) {
																echo '<span class="email-status-enabled status"><i class="wpcw-fas wpcw-fa-check-circle"></i></span>';
															} else {
																echo '<span class="email-status-disabled status"><i class="wpcw-fas wpcw-fa-times-circle"></i></span>';
															}
															break;
														case 'content_type' :
															echo esc_html( $email->get_type() );
															break;
														case 'actions' :
															printf( '<a href="%s" class="button button-primary"><i class="wpcw-fas wpcw-fa-cog left"></i> %s</a>', $email_url, esc_html__( 'Configure', 'wp-courseware' ) );
															break;
														default :
															do_action( "wpcw_email_setting_column_{$column_key}", $email );
															break;
													}
													?>
												</td>
											<?php } ?>
										</tr>
									<?php } ?>
								<?php } ?>
								</tbody>
							</table>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Field: Email Content
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The styles field html.
	 */
	public function generate_email_content_field_html( $key, $field ) {
		if ( empty( $this->email ) ) {
			return;
		}

		wp_enqueue_editor();

		$field_key   = $this->get_field_key( $key );
		$field_type  = $this->get_field_type( $field );
		$field_title = $this->get_field_title( $field );
		$field_desc  = $this->get_field_desc( $field );
		$field_tip   = $this->get_field_desc_tip( $field );

		$this->email->setup();

		$email_id            = $this->email->get_id();
		$email_type          = $this->email->get_type();
		$email_content_plain = $this->email->get_content_plain();
		$email_content_html  = $this->email->get_content_html();

		return sprintf(
			'<wpcw-settings-field-email-content id="%s" type="%s" label="%s" desc="%s" tip="%s" plain="%s"></wpcw-settings-field-email-content>',
			esc_attr( $email_id ),
			esc_attr( $email_type ),
			esc_html( $field_title ),
			wp_kses_post( $field_desc ),
			wp_kses_post( $field_tip ),
			wp_kses_post( $email_content_plain )
		);
	}

	/**
	 * Field: Payment Gateways.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The field key.
	 * @param array  $field The field array data.
	 *
	 * @return string The html corresponding to the field.
	 */
	public function generate_payment_gateways_field_html( $key, $field ) {
		ob_start();

		if ( ! empty( $this->gateway ) ) {
			echo $this->gateway_single_html( $key, $field );
		} else {
			echo $this->gateway_table_html( $key, $field );
		}

		return ob_get_clean();
	}

	/**
	 * Gateway Single HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The email single html.
	 */
	public function gateway_single_html( $key, $field ) {
		ob_start();

		$this->gateway->setup();

		?>
		<div class="wpcw-gateways wpcw-gateways-single">
			<div class="wpcw-gateway-fields">
				<div class="wpcw-field-row wpcw-field-row-heading wpcw-field-row-heading-gateway wpcw-field-clear">
					<h2 class="wpcw-field-heading">
						<a class="crumb-link" href="<?php echo $this->get_current_tab_section_url(); ?>"><?php esc_html_e( 'Payment Gateways', 'wp-courseware' ); ?></a>
						<span class="crumb-separator"><i class="wpcw-fas wpcw-fa-chevron-right"></i></span>
						<?php echo $this->gateway->get_method_title(); ?>
					</h2>
					<p><?php echo $this->gateway->get_method_description(); ?></p>
				</div>
				<?php if ( $this->gateway->supports_currency() ) { ?>
					<?php echo $this->generate_fields_html( $this->gateway->get_settings_fields() ); ?>
				<?php } else { ?>
					<?php wpcw_admin_notice( sprintf( __( '<strong>Gateway Disabled</strong>: The %s gateway does not support either the country or the currency that is currently set.', 'wp-courseware' ), $this->gateway->get_title() ), 'error', true, true, true ); ?>
				<?php } ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Gateway Table HTML.
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The email single html.
	 */
	public function gateway_table_html( $key, $field ) {
		$gateways_table_columns = apply_filters( 'wpcw_gateways_table_columns', array(
			'sort'    => '',
			'title'   => esc_html__( 'Gateway', 'wp-courseware' ),
			'id'      => esc_html__( 'Gateway ID', 'wp-courseware' ),
			'status'  => esc_html__( 'Enabled', 'wp-courseware' ),
			'actions' => '',
		) );

		$gateways = wpcw()->gateways->get_gateways();

		if ( empty( $gateways ) ) {
			return wpcw_admin_notice_error( esc_html__( 'There are no payment gateways defined for WP Courseware.', 'wp-courseware' ), false );
		}

		ob_start();

		?>
		<div class="wpcw-gateways">
			<div class="wpcw-field-row wpcw-field-row-heading wpcw-field-clear">
				<h2 class="wpcw-field-heading"><?php esc_html_e( 'Payment Gateways', 'wp-courseware' ); ?></h2>
				<p><?php esc_html_e( 'Installed gateways are listed below. Drag and drop gateways to control their display order on the frontend.', 'wp-courseware' ); ?></p>
			</div>
			<div class="wpcw-field-row wpcw-field-row-gateways-table wpcw-field-clear">
				<table class="form-table">
					<tbody>
					<tr valign="top">
						<td class="wpcw-gateways-wrapper" colspan="2">
							<table class="wpcw-gateways-table widefat" cellspacing="0">
								<thead>
								<tr>
									<?php foreach ( $gateways_table_columns as $column_key => $column ) { ?>
										<th class="wpcw-gateway-settings-table-<?php echo esc_attr( $column_key ); ?>"><?php echo $column; ?></th>
									<?php } ?>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $gateways as $gateway_slug => $gateway ) { ?>
									<?php if ( $gateway instanceof Gateway ) {
										$gateway->setup(); ?>
										<tr>
											<?php foreach ( $gateways_table_columns as $column_key => $column ) {
												$gateway_url = esc_url( add_query_arg( array( 'gateway' => $gateway_slug ), $this->get_current_tab_section_url() ) );
												?>
												<td class="wpcw-gateway-settings-table-<?php echo esc_attr( $column_key ); ?> <?php echo esc_attr( $column_key ); ?>">
													<?php
													switch ( $column_key ) {
														case 'sort' :
															printf( '<span class="gateway-sort"><span class="dashicons dashicons-menu"></span><input type="hidden" name="payment_gateways_order[]" value="%s"></span>', $gateway->get_slug() );
															break;
														case 'title' :
															printf( '<a class="wpcw-gateway-title" href="%s">%s</a>', $gateway_url, $gateway->get_method_title() );
															if ( $gateway_desc = $gateway->get_method_description() ) {
																printf( '<abbr class="wpcw-tooltip" title="%s" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>', esc_html( $gateway_desc ) );
															}
															break;
														case 'status' :
															if ( $gateway->is_available() ) {
																echo '<span class="gateway-status-enabled status"><i class="wpcw-fas wpcw-fa-check-circle"></i></span>';
															} else {
																echo '<span class="gateway-status-disabled status"><i class="wpcw-fas wpcw-fa-times-circle"></i></span>';
															}
															break;
														case 'id' :
															echo esc_attr( $gateway->get_slug() );
															break;
														case 'actions' :
															printf( '<a href="%s" class="button button-primary"><i class="wpcw-fas wpcw-fa-cog left"></i> %s</a>', $gateway_url, esc_html__( 'Configure', 'wp-courseware' ) );
															break;
														default :
															do_action( "wpcw_gateway_setting_column_{$column_key}", $gateway );
															break;
													}
													?>
												</td>
											<?php } ?>
										</tr>
									<?php } ?>
								<?php } ?>
								</tbody>
							</table>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Field: Support
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The support field html.
	 */
	public function generate_support_field_html() {
		return '<wpcw-settings-field-support></wpcw-settings-field-support>';
	}

	/**
	 * Field Callback: Products
	 *
	 * @since 4.3.0
	 *
	 * @param string $key The current field key.
	 * @param array  $field The current field data.
	 *
	 * @return string The products field html.
	 */
	public function generate_products_field_html() {
		return '<wpcw-settings-field-products></wpcw-settings-field-products>';
	}
}
