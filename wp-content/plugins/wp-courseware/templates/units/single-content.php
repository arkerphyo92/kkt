<?php
/**
 * Unit Single Conent.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/unit/single-content.php.
 *
 * @package WPCW
 * @subpackage Templates
 * @version 4.9.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

global $post;
$flag = false;
$wpcw_divi_layout = '';

$fe = new WPCW_UnitFrontend( $post );

// Check if user is admin or teacher.
$is_admin_or_teacher = $fe->check_is_admin_or_teacher();
$user_id             = get_current_user_id();
$is_admin            = user_can( $user_id, apply_filters( 'wpcw_units_accessible_admin_capability', 'manage_wpcw_settings' ) );

if ( $is_admin_or_teacher === true ) {
	$has_access = true;
	$flag       = true;
} else {
	$has_access = false;
}

if ( function_exists( 'et_get_theme_version' ) || class_exists( 'FLThemeBuilder' ) ) {
	$wpcw_divi_layout = 'wpcw_divi_layout';
}

?>
<div class="wpcw-unit-wrapper <?php echo $wpcw_divi_layout; ?>">
<?php
// Ensure we're only showing a course unit, a single item.
if ( ! is_single() || 'course_unit' != get_post_type() || ! WPCW_units_getAssociatedParentData( $post->ID ) ) {
	return the_content();
}

// Divi Builder Check.
if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
	return the_content();
}

if ( post_password_required() && $has_access === false ) {
	echo get_the_password_form();
	return;
}

	// Run completed quiz/incomplete unit check
	WPCW_quiz_complete_unit_incomplete_fix();

	/**
	 * Hook: Before Unit Single Content
	 *
	 * @since 4.9.0
	 */
	do_action( 'wpcw_unit_before_single_content' );

if ( WPCW_check_course_expiration( get_the_ID() ) && $has_access === false ) { ?>
		<p class="wpcw_expired_notification"><?php echo __( 'This course is expired.', 'wp-courseware' ); ?></p>
		<?php
} else {
	?>
		<div class="wpcw-unit-single-content">
			<div class="wpcw-unit-desc">
			<?php

			// If user is not logged in and is unit teaser.
			if ( ! $fe->check_user_isUserLoggedIn() && $fe->check_is_unit_teaser() ) {
				$flag = true;
				$has_access === true;
				the_content();
				$fe->render_detailsForContent( $content = '', $justGetCompletionAndQuizData = false );
				return;
			}

			// Ensure we're logged in
			if ( ! $fe->check_user_isUserLoggedIn() ) {
				echo $fe->message_user_notLoggedIn();
				return;
			}

			// If user is not logged in and is unit teaser.
			if ( ! $fe->check_user_canUserAccessCourse() && $fe->check_is_unit_teaser() ) {
				$flag = true;
				$has_access === true;
			}

			// User not allowed access to content, so certainly can't say they've done this unit.
			if ( ! $fe->check_user_canUserAccessCourse() && $has_access === false ) {
				echo $fe->message_user_cannotAccessCourse();
				return;
			}

			// Is user allowed to access this unit yet?
			if ( ! $fe->check_user_canUserAccessUnit() && $has_access === false ) {
				$navigationBox = $fe->render_navigation_getNavigationBox();
				// Show the navigation box AFTErR the cannot progress message.
				echo $fe->message_user_cannotAccessUnit() . $navigationBox;
				return;
			}

			// Has user completed course prerequisites.
			if ( ! $fe->check_user_hasCompletedCoursePrerequisites() && $has_access === false ) {
				// on a unit that we're not able to complete just yet.
				$navigationBox = $fe->render_detailsForNvigationBox( $content = '', $justGetCompletionAndQuizData = false );

				// Show navigation box after the cannot process message.
				echo $fe->message_user_hasNotCompletedCoursePrerequisites() . $navigationBox;
				return;
			}
			$lockDetails = array(
				'content_locked' => false,
				'unlock_date'    => false,
			);

			$lockDetails = $fe->render_completionBox_contentLockedDueToDripfeed( $content = '', $justGetCompletionAndQuizData = false );
			if ( $lockDetails['content_locked'] === false || ( $flag === true && $has_access == true ) ) {
				$et_pb_post_content = get_post_meta( $post->ID, '_et_builder_module_features_cache', true );
				$et_pb_post_content = maybe_unserialize( $et_pb_post_content );
				if ( isset( $et_pb_post_content ) && ! empty( $et_pb_post_content ) ) {
					foreach ( $et_pb_post_content[1] as $key => $value ) {
						if ( 'et_pb_post_content_' == substr( $key, 0, 19 ) ) {
							// Don't render content.
						}
					}
				} else {
					the_content();
				}
			}

			$fe->render_detailsForContent( $content = '', $justGetCompletionAndQuizData = false );
			?>
			</div>
			<div class="wpcw-unit-completebox">
			<?php echo $fe->render_detailsForCompletebox( $content = '', $justGetCompletionAndQuizData = false ); ?>
			</div>
			<div class="wpcw-unit-navigation">
				<?php echo $fe->render_detailsForNvigationBox( $content = '', $justGetCompletionAndQuizData = false ); ?>
			</div>
		</div>
		<?php
		/**
		 * Hook: After unit content
		 *
		 * @since 4.9.0
		 */
		do_action( 'wpcw_unit_after_single_content' );
}
?>
</div>
