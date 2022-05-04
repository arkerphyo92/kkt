<?php
/**
 * WP Courseware Database Table Quizzes Feedback.
 *
 * @package WPCW
 * @subpackage Database\Tables
 * @since 4.3.0
 */
namespace WPCW\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class DB_Table_Quizzes_Feedback.
 *
 * @since 4.3.0
 */
final class DB_Table_Quizzes_Feedback extends DB_Table {

	/**
	 * @var string Table name
	 * @since 4.3.0
	 */
	protected $name = 'wpcw_quizzes_feedback';

	/**
	 * @var int Database Table version
	 * @since 4.3.0
	 */
	protected $version = 450;

	/**
	 * Setup the database schema.
	 *
	 * @since 4.3.0
	 */
	protected function set_schema() {
		$this->schema = "qfeedback_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  				         qfeedback_tag_id bigint(20) unsigned NOT NULL,
  				         qfeedback_quiz_id int(1) unsigned NOT NULL,
  				         qfeedback_summary varchar(300) NOT NULL,
  				         qfeedback_score_type varchar(20) NOT NULL DEFAULT 'below',
  				         qfeedback_score_grade int(11) unsigned NOT NULL DEFAULT '50',
  				         qfeedback_message text NOT NULL,
  				         PRIMARY KEY  (qfeedback_id)";
	}

	/**
	 * Get Upgrades.
	 *
	 * @since 4.5.0
	 */
	protected function get_upgrades() {
		return array(
			'450' => 'upgrade_to_450',
		);
	}

	/**
	 * Upgrade to version 4.5.0
	 *
	 * @since 4.5.0
	 */
	protected function upgrade_to_450() {
		maybe_convert_table_to_utf8mb4( $this->table_name );
	}
}
