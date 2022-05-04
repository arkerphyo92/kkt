<?php
/**
 * Course Archive Excerpt.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/course/archive-excerpt.php.
 *
 * @package WPCW
 * @subpackage Templates
 * @version 4.4.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Course Enrollment Button.
$enrollment_button = wpcw_get_course_enrollment_button();

/**
 * Hook: Before Course Archive Excerpt
 *
 * @since 4.4.3
 */
do_action( 'wpcw_course_before_archive_excerpt' );

// The excerpt.
the_excerpt();

if ( $enrollment_button ) { ?>
	<div class="wpcw-course-enrollment-button"><?php echo $enrollment_button; ?></div>
<?php }

/**
 * Hook: After Course Archive Excerpt
 *
 * @since 4.4.3
 */
do_action( 'wpcw_course_after_archive_excerpt' );
