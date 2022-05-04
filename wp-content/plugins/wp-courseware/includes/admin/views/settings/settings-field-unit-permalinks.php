<?php
/**
 * Settings Field - Unit Permalinks.
 *
 * @since 4.4.0
 */
// Courses
$course_page_id = wpcw_get_page_id( 'courses' );
$courses_base   = urldecode( ( $course_page_id > 0 && get_post( $course_page_id ) ) ? get_page_uri( $course_page_id ) : _x( 'courses', 'default-slug', 'wp-courseware' ) );
$course_base    = _x( 'course', 'default-slug', 'woocommerce' );

// Structures
$structures = array(
	0 => '/' . trailingslashit( '%module_number%' ),
	1 => '/' . trailingslashit( $course_base ) . trailingslashit( '%course%' ) . trailingslashit( '%module_number%' ),
	2 => '/' . trailingslashit( $courses_base ) . trailingslashit( '%course%' ) . trailingslashit( '%module_number%' ),
);
?>
<script type="text/x-template" id="wpcw-settings-field-unit-permalinks">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="unit_permalink_0">
                            <input type="radio" id="unit_permalink_0" name="unit_permalink" value="<?php echo esc_attr( $structures[0] ); ?>" v-model="unitPermalink">
                            <span class="radio-label"><?php esc_html_e( 'Default', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The default unit permalink structure of <code>/module-number/sample-unit/</code>', 'wp-courseware' ); ?>" rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <code class="default-example"><?php echo esc_html( home_url() ); ?>/module-number/sample-unit/</code>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="unit_permalink_1">
                            <input type="radio" id="unit_permalink_1" name="unit_permalink" value="<?php echo esc_attr( $structures[1] ); ?>" v-model="unitPermalink">
                            <span class="radio-label"><?php esc_html_e( 'Course Base', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The unit permalink structure with the base of <code>/course/sample-course/module-number/</code>', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <code class="default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $course_base ); ?>/sample-course/module-number/sample-unit/</code>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="unit_permalink_2">
                            <input type="radio" id="unit_permalink_2" name="unit_permalink" value="<?php echo esc_attr( $structures[2] ); ?>" v-model="unitPermalink">
                            <span class="radio-label"><?php esc_html_e( 'Courses Base', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The unit permalink structure with the base of <code>/courses/sample-course/module-number/</code>', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <code class="default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $courses_base ); ?>/sample-course/module-number/sample-unit/</code>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="unit_permalink_3">
                            <input type="radio" id="unit_permalink_3" name="unit_permalink" value="custom" v-model="unitPermalink">
                            <span class="radio-label"><?php esc_html_e( 'Custom Base', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The unit permalink structure with a custom base. A base must be set or WordPress will use default instead.', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <input id="unit_permalink_structure"
                           name="unit_permalink_structure"
                           type="text"
                           v-model="unitPermalinkStructure"
                           class="size-large code"
                           :class="{ 'disabled' : unitPermalink !== 'custom' }"
                           placeholder="<?php esc_html_e( 'Enter a custom base', 'wp-courseware' ); ?>" :readonly="unitPermalink !== 'custom'"/>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>