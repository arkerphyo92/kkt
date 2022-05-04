<?php
/**
 * Display Courses.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/courses.php.
 *
 * @package WPCW
 * @subpackage Templates
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<div class="wpcw-courses">
	<?php
	/** @var \WPCW\Models\Course $course */
	foreach ( $courses as $course ) {
		if ( WPCW_check_course_expiration( $course->course_post_id ) ) {
			continue;
		}
		?>
		<div class="wpcw-course">
			<?php if ( $atts['show_image'] ) { ?>
				<div class="wpcw-course-thumbnail"><?php echo $course->get_thumbnail_image(); ?></div>
			<?php } ?>

			<h3 class="wpcw-course-title">
				<a href="<?php echo $course->get_permalink(); ?>"><?php echo $course->get_course_title(); ?></a>
			</h3>

			<?php if ( $atts['show_desc'] ) { ?>
				<div class="wpcw-course-desc"><?php echo wpautop( $course->get_course_desc() ); ?></div>
			<?php } ?>

			<?php if ( $atts['show_button'] && ( $enrollment_button = $course->get_enrollment_button() ) ) { ?>
				<div class="wpcw-course-enrollment-button"><?php echo $enrollment_button; ?></div>
			<?php } ?>
		</div>
	<?php } ?>
</div>
