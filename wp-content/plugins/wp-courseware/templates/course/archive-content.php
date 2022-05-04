<?php
/**
 * Course Archive Content.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/course/archive-content.php.
 *
 * @package WPCW
 * @subpackage Templates
 * @version 4.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Enrollment button.
$enrollment_button = wpcw_get_course_enrollment_button();

/**
 * Hook: Before Course Archive Content
 *
 * @since 4.4.0
 */
do_action( 'wpcw_course_before_archive_content' ); ?>
	<div class="wpcw-course-archive-content">
		<div class="wpcw-course-desc"><?php echo wpcw_get_course_desc(); ?></div>
		<?php if ( $enrollment_button ) : ?>
			<div class="wpcw-course-enrollment-button"><?php echo $enrollment_button; ?></div>
		<?php endif ?>
	</div>
<?php
/**
 * Hook: After Course Archive Content
 *
 * @since 4.4.0
 */
do_action( 'wpcw_course_after_archive_content' );
