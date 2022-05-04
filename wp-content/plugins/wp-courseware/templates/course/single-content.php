<?php
/**
 * Course Single Conent.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/course/single-content.php.
 *
 * @package WPCW
 * @subpackage Templates
 * @version 4.4.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( WPCW_check_course_expiration( get_the_ID() ) ){
	
	?>
	<p class="wpcw_expired_notification"><?php echo __( 'This course is expired.', 'wp-courseware' ); ?></p>
	<?php

} else {

	// Enrollment Button.
	$enrollment_button = wpcw_get_course_enrollment_button();

	/**
	 * Hook: Before Course Single Content
	 *
	 * @since 4.4.0
	 */
	do_action( 'wpcw_course_before_single_content' );
	?>
		<div class="wpcw-course-single-content">
			<div class="wpcw-course-desc"><?php echo wpcw_get_course_desc(); ?></div>
			<?php if ( $enrollment_button ) { ?>
				<div class="wpcw-course-enrollment-button"><?php echo $enrollment_button; ?></div>
			<?php } ?>
			<div class="wpcw-course-outline"><?php echo wpcw_get_course_outline(); ?></div>
		</div>
	<?php
	/**
	 * Hook: After course content
	 *
	 * @since 4.4.0
	 */
	do_action( 'wpcw_course_after_single_content' );

}
