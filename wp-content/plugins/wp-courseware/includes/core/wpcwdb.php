<?php
/**
 * WP Courseware Legacy Database Mapping.
 *
 * This class exists to maintain backwards compatability
 * for the global $wpcwdb and access to its properties.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class WPCWDB.
 *
 * Class structure exists to maintain
 * backwards compatability.
 *
 * @since 4.3.0
 */
class WPCWDB {

	/**
	 * @var string Courses Table Name.
	 * @since 4.3.0
	 */
	public $courses;

	/**
	 * @var string Course Meta Table Name.
	 * @since 4.3.0
	 */
	public $coursemeta;

	/**
	 * @var string Modules Table Name.
	 * @since 4.3.0
	 */
	public $modules;

	/**
	 * @var string Units Meta Table Name.
	 * @since 4.3.0
	 */
	public $units_meta;

	/**
	 * @var string User Courses Table Name.
	 * @since 4.3.0
	 */
	public $user_courses;

	/**
	 * @var string User Progress Table Name.
	 * @since 4.3.0
	 */
	public $user_progress;

	/**
	 * @var string User Progress Quiz Table Name.
	 * @since 4.3.0
	 */
	public $user_progress_quiz;

	/**
	 * @var string Quizzes Table Name.
	 * @since 4.3.0
	 */
	public $quiz;

	/**
	 * @var string Quizzes Feedback Table Name.
	 * @since 4.3.0
	 */
	public $quiz_feedback;

	/**
	 * @var string Quizzes Question Mapping Table.
	 * @since 4.3.0
	 */
	public $quiz_qs_mapping;

	/**
	 * @var string Questions Table Name.
	 * @since 4.3.0
	 */
	public $quiz_qs;

	/**
	 * @var string Question Tags Table Name.
	 * @since 4.3.0
	 */
	public $question_tags;

	/**
	 * @var string Question Tag Mapping Table Name.
	 * @since 4.3.0
	 */
	public $question_tag_mapping;

	/**
	 * @var string Question Locks Table.
	 * @since 4.3.0
	 */
	public $question_rand_lock;

	/**
	 * @var string Map Member Levels Table Name.
	 * @since 4.3.0
	 */
	public $map_member_levels;

	/**
	 * @var sring Certificates Table Name.
	 * @since 4.3.0
	 */
	public $certificates;

	/**
	 * @var string Queue Dripfeed Table Name.
	 * @since 4.3.0
	 */
	public $queue_dripfeed;

	/**
	 * @var string Orders Table Name.
	 * @since 4.3.0
	 */
	public $orders;

	/**
	 * @var string Orders Meta Table Name.
	 * @since 4.3.0
	 */
	public $ordermeta;

	/**
	 * @var string Order Items.
	 * @since 4.3.0
	 */
	public $order_items;

	/**
	 * @var string Order Item Meta.
	 * @since 4.3.0
	 */
	public $order_itemmeta;

	/**
	 * @var string Subscriptions Table Name.
	 * @since 4.5.0
	 */
	public $subscriptions;

	/**
	 * @var string Coupons Table Name.
	 * @since 4.5.0
	 */
	public $coupons;

	/**
	 * @var string Coupon Meta Table Name.
	 * @since 4.5.0
	 */
	public $couponmeta;

	/**
	 * @var string Logs Table Name.
	 * @since 4.5.0
	 */
	public $logs;

	/**
	 * @var string Notes Table Name.
	 * @since 4.5.0
	 */
	public $notes;

	/**
	 * @var string Sessions Table Name.
	 * @since 4.5.0
	 */
	public $sessions;
}
