<?php
/**
 * WP Courseware Frontend Templates.
 *
 * @package WPCW
 * @since 1.0.0
 */

/**
 * Intercept the code that chooses what template to show for the unit.
 *
 * @since 1.0.0
 *
 * @param string $template The current template.
 *
 * @return string The path of the template file to use.
 */
function WPCW_templates_units_filterTemplateForUnit( $template ) {
	global $post;

	// What type of post are we showing? Only interested in course units.
	if ( 'course_unit' != $post->post_type ) {
		return $template;
	}

	// Now we know we have a course unit, we need to see if there's a post template associated with it.
	$templateFile = get_post_meta( $post->ID, WPCW_TEMPLATE_META_ID, true );
	if ( ! $templateFile || 'no-template.php' === $templateFile ) {
		return $template;
	}

	// If there's a tpl in a (child theme or theme with no child)
	// else
	// If there's a tpl in the parent of the current child theme
	if ( file_exists( trailingslashit( STYLESHEETPATH ) . $templateFile ) ) {
		return STYLESHEETPATH . DIRECTORY_SEPARATOR . $templateFile;
	} else if ( file_exists( TEMPLATEPATH . DIRECTORY_SEPARATOR . $templateFile ) ) {
		return TEMPLATEPATH . DIRECTORY_SEPARATOR . $templateFile;
	}

	return $template;
}
