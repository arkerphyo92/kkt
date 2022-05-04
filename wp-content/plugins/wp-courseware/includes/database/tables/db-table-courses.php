<?php
/**
 * WP Courseware Database Table Courses.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Courses.
 *
 * @since 4.3.0
 */
final class DB_Table_Courses extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_courses';

	/**
	 * @var int Database Table version
	 * @since 4.3.0
	 */
	protected $version = 480;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.3.0
	 */
	protected function set_schema() {
		$this->schema = "course_id int(11) unsigned NOT NULL AUTO_INCREMENT,
						 course_post_id bigint(20) unsigned NOT NULL DEFAULT '0',
			             course_title text NOT NULL,
			             course_desc longtext NOT NULL,
			             course_author bigint(20) unsigned NOT NULL default '0',
			             course_opt_completion_wall varchar(20) NOT NULL,
			             course_opt_use_certificate varchar(20) NOT NULL DEFAULT 'no_certs',
			             course_opt_user_access varchar(20) NOT NULL,
			             course_unit_count int(11) unsigned NULL DEFAULT '0',
			             course_from_name varchar(150) NOT NULL,
			             course_from_email varchar(150) NOT NULL,
			             course_to_email varchar(150) NOT NULL,
			             course_opt_prerequisites longtext NOT NULL,
			             course_message_unit_complete text NULL,
			             course_message_course_complete text NULL,
			             course_message_unit_not_logged_in text NULL,
			             course_message_unit_pending text NULL,
			             course_message_unit_no_access text NULL,
			             course_message_prerequisite_not_met text NULL,
			             course_message_unit_not_yet text NULL,
			             course_message_unit_not_yet_dripfeed text NULL,
			             course_message_quiz_open_grading_blocking text NULL,
			             course_message_quiz_open_grading_non_blocking text NULL,
			             email_complete_module_option_admin varchar(20) NOT NULL,
			             email_complete_module_option varchar(20) NOT NULL,
			             email_complete_module_subject varchar(300) NOT NULL,
			             email_complete_module_body text NULL,
			             email_complete_course_option_admin varchar(20) NOT NULL,
			             email_complete_course_option varchar(20) NOT NULL,
			             email_complete_course_subject varchar(300) NOT NULL,
			             email_complete_course_body text NULL,
			             email_quiz_grade_option varchar(20) NOT NULL,
			             email_quiz_grade_subject varchar(300) NOT NULL,
			             email_quiz_grade_body text NULL,
			             email_complete_course_grade_summary_subject varchar(300) NOT NULL,
			             email_complete_course_grade_summary_body text NULL,
			             email_complete_unit_option_admin varchar(20) NOT NULL DEFAULT 'no_email',
			             email_complete_unit_option varchar(20) NOT NULL DEFAULT 'no_email',
			             email_complete_unit_subject varchar(300) NOT NULL,
			             email_complete_unit_body text NULL,
			             email_unit_unlocked_subject varchar(300) NOT NULL,
			             email_unit_unlocked_body text NULL,
			             cert_signature_type varchar(20) NOT NULL DEFAULT 'text',
			             cert_sig_text varchar(300) NOT NULL,
			             cert_sig_image_url varchar(300) NOT NULL DEFAULT  '',
			             cert_logo_enabled varchar(20) NOT NULL DEFAULT 'no_cert_logo',
			             cert_logo_url varchar(300) NOT NULL DEFAULT '',
			             cert_background_type varchar(20) NOT NULL DEFAULT 'use_default',
			             cert_background_custom_url varchar(300) NOT NULL DEFAULT  '',
			             payments_type varchar(20) NOT NULL DEFAULT 'free',
			             payments_price varchar(100) NOT NULL DEFAULT '0.00',
			             payments_interval varchar(20) NOT NULL DEFAULT 'month',
			             course_bundles longtext NOT NULL,
			             installments_enabled varchar(20) NOT NULL DEFAULT 'no',
			             installments_number bigint(20) unsigned NOT NULL DEFAULT '2',
			             installments_amount varchar(100) NOT NULL DEFAULT '0.00',
			             installments_interval varchar(20) NOT NULL DEFAULT 'month',
						 unit_advancement varchar(20) NOT NULL DEFAULT 'manual',
						 course_access_expiration varchar(20) NOT NULL DEFAULT 'none',
						 cae_specific_date datetime DEFAULT '0000-00-00 00:00:00',
						 cae_interval_date_int_expire int(11) NOT NULL DEFAULT '1',
						 cae_interval_date_string_expire varchar(20) NOT NULL DEFAULT 'hour',
						 cae_reset_on_course_expire varchar(20) NOT NULL DEFAULT 'no',
						 certificate_template int(11) NOT NULL DEFAULT '0',
			             PRIMARY KEY  (course_id),
			             KEY course_post_id (course_post_id)";
	}

	/**
	 * Get Upgrades.
	 *
	 * @since 4.3.0
	 */
	protected function get_upgrades() {
		return array(
			'430' => 'upgrade_to_430',
			'432' => 'upgrade_to_432',
			'440' => 'upgrade_to_440',
			'450' => 'upgrade_to_450',
			'460' => 'upgrade_to_460',
			'462' => 'upgrade_to_462',
			'467' => 'upgrade_to_467',
			'480' => 'upgrade_to_480',
		);
	}

	/**
	 * Upgrade to version 4.3.0
	 *
	 * @since 4.3.0
	 */
	protected function upgrade_to_430() {
		global $wpdb;

		$course_author     = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_author'" );
		$payments_type     = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'payments_type'" );
		$payments_price    = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'payments_price'" );
		$payments_interval = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'payments_interval'" );

		if ( ! $course_author ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `course_author` bigint(20) unsigned NOT NULL DEFAULT '0'" );
		}

		if ( ! $payments_type ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `payments_type` varchar(20) NOT NULL DEFAULT 'free'" );
		}

		if ( ! $payments_price ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `payments_price` varchar(100) NOT NULL DEFAULT '0.00'" );
		}

		if ( ! $payments_interval ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `payments_interval` varchar(100) NOT NULL DEFAULT 'month'" );
		}
	}

	/**
	 * Upgrade to version 4.3.2
	 *
	 * @since 4.3.2
	 */
	protected function upgrade_to_432() {
		global $wpdb;

		$course_author = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_author'" );

		if ( ! $course_author ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `course_author` bigint(20) unsigned NOT NULL DEFAULT '0'" );
		}
	}

	/**
	 * Upgrade to version 4.4.0
	 *
	 * @since 4.4.0
	 */
	protected function upgrade_to_440() {
		global $wpdb;

		$course_post_id = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_post_id'" );

		if ( ! $course_post_id ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `course_post_id` bigint(20) unsigned NOT NULL DEFAULT '0'" );
			$wpdb->query( "ALTER TABLE $this->table_name ADD INDEX `course_post_id` (`course_post_id`)" );
		}

		$wpdb->query( "ALTER TABLE $this->table_name MODIFY `course_desc` longtext NOT NULL" );
	}

	/**
	 * Upgrade to version 4.5.0
	 *
	 * @since 4.5.0
	 */
	protected function upgrade_to_450() {
		maybe_convert_table_to_utf8mb4( $this->table_name );
	}

	/**
	 * Upgrade to version 4.6.0
	 *
	 * @since 4.5.0
	 */
	protected function upgrade_to_460() {
		global $wpdb;

		// Installments.
		$installments_enabled  = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'installments_enabled'" );
		$installments_number   = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'installments_number'" );
		$installments_amount   = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'installments_amount'" );
		$installments_interval = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'installments_interval'" );

		if ( ! $installments_enabled ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `installments_enabled` varchar(20) NOT NULL DEFAULT 'no'" );
		}

		if ( ! $installments_number ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `installments_number` bigint(20) unsigned NOT NULL DEFAULT '2'" );
		}

		if ( ! $installments_amount ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `installments_amount` varchar(100) NOT NULL DEFAULT '0.00'" );
		}

		if ( ! $installments_interval ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `installments_interval` varchar(20) NOT NULL DEFAULT 'month'" );
		}

		// Unit Complete Emails.
		$email_uc_option_admin = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'email_complete_unit_option_admin'" );
		$email_uc_option       = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'email_complete_unit_option'" );
		$email_uc_subject      = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'email_complete_unit_subject'" );
		$email_uc_body         = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'email_complete_unit_body'" );

		if ( ! $email_uc_option_admin ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `email_complete_unit_option_admin` varchar(20) NOT NULL DEFAULT 'no_email'" );
		}

		if ( ! $email_uc_option ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `email_complete_unit_option` varchar(20) NOT NULL DEFAULT 'no_email'" );
		}

		if ( ! $email_uc_subject ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `email_complete_unit_subject` varchar(20) NOT NULL DEFAULT 'testing'" );
		}

		if ( $email_uc_subject ) {
			$email_uc_subject_text = wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_SUBJECT' );
			$wpdb->query( "UPDATE $this->table_name SET `email_complete_unit_subject` = '{$email_uc_subject_text}'" );
		}

		if ( ! $email_uc_body ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `email_complete_unit_body` text NULL" );
		}

		if ( $email_uc_body ) {
			$email_uc_body_text = wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_BODY' );
			$wpdb->query( "UPDATE $this->table_name SET `email_complete_unit_body` = '{$email_uc_body_text}'" );
		}

		// Course Bundles.
		$course_bundles = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_bundles'" );

		if ( ! $course_bundles ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `course_bundles` longtext NOT NULL" );
		}
	}

	/**
	 * Upgrade to version 4.6.2
	 *
	 * @since 4.6.2
	 */
	protected function upgrade_to_462() {
		global $wpdb;

		$unit_advancement = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'unit_advancement'" );

		if ( ! $unit_advancement ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `unit_advancement` varchar(20) NOT NULL DEFAULT 'manual'" );
		}
	}

	/**
	 * Upgrade to version 4.6.7
	 *
	 * @since 4.6.7
	 */
	protected function upgrade_to_467() {
		global $wpdb;

		$course_title = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_title'" );

		if ( $course_title ) {
			$wpdb->query( "ALTER TABLE $this->table_name MODIFY COLUMN `course_title` text NOT NULL" );
		}
	}

	/**
	 * Upgrade to version 4.8.0
	 *
	 * @since 4.8.0
	 */
	protected function upgrade_to_480() {
		global $wpdb;

		$course_access_expiration = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'course_access_expiration'" );
		$cae_specific_date = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'cae_specific_date'" );
		$cae_interval_date_int_expire = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'cae_interval_date_int_expire'" );
		$cae_interval_date_string_expire = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'cae_interval_date_string_expire'" );
		$cae_reset_on_course_expire = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'cae_reset_on_course_expire'" );
		$certificate_template = $wpdb->query( "SHOW COLUMNS FROM $this->table_name LIKE 'certificate_template'" );

		if ( ! $course_access_expiration ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `course_access_expiration` varchar(20) NOT NULL DEFAULT 'none'" );
		}

		if ( ! $cae_specific_date ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `cae_specific_date` datetime DEFAULT '0000-00-00 00:00:00'" );
		}

		if ( ! $cae_interval_date_int_expire ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `cae_interval_date_int_expire` int(11) NOT NULL DEFAULT '1'" );
		}

		if ( ! $cae_interval_date_string_expire ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `cae_interval_date_string_expire` varchar(20) NOT NULL DEFAULT 'hour'" );
		}

		if ( ! $cae_reset_on_course_expire ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `cae_reset_on_course_expire` varchar(20) NOT NULL DEFAULT 'no'" );
		}

		if ( ! $certificate_template ) {
			$wpdb->query( "ALTER TABLE $this->table_name ADD `certificate_template` int(11) NOT NULL DEFAULT '0'" );
		}

	}
}
