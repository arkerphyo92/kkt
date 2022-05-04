<?php
/**
 * Settings Field - Course Permalinks.
 *
 * @since 4.4.0
 */
$course_page_id = wpcw_get_page_id( 'courses' );
$base_slug      = urldecode( ( $course_page_id > 0 && get_post( $course_page_id ) ) ? get_page_uri( $course_page_id ) : _x( 'courses', 'default-slug', 'wp-courseware' ) );
$course_base    = _x( 'course', 'default-slug', 'woocommerce' );

$structures = array(
	0 => '',
	1 => '/' . trailingslashit( $base_slug ),
);
?>
<script type="text/x-template" id="wpcw-settings-field-course-permalinks">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="course_permalink_0">
                            <input type="radio" id="course_permalink_0" name="course_permalink" value="<?php echo esc_attr( $structures[0] ); ?>" v-model="coursePermalink">
                            <span class="radio-label"><?php esc_html_e( 'Default', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The course permalink structure with a base of <code>/course/</code>.', 'wp-courseware' ); ?>" rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $course_base ); ?>/sample-course/</code>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="course_permalink_1">
                            <input type="radio" id="course_permalink_1" name="course_permalink" value="<?php echo esc_attr( $structures[1] ); ?>" v-model="coursePermalink">
                            <span class="radio-label"><?php esc_html_e( 'Courses Base', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The course permalink structure with a base of <code>/courses/</code>.', 'wp-courseware' ); ?>" rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-course/</code>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <wpcw-form-row classes="wpcw-permalink-row">
            <div slot="label">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="course_permalink_3">
                            <input type="radio" id="course_permalink_3" name="course_permalink" value="custom" v-model="coursePermalink">
                            <span class="radio-label"><?php esc_html_e( 'Custom Base', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
                <abbr class="wpcw-tooltip" title="<?php _e( 'The course permalink structure with a custom base. A base must be set or WordPress will use default instead.', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <input id="course_permalink_structure"
                           name="course_permalink_structure"
                           type="text"
                           v-model="coursePermalinkStructure"
                           class="size-large code"
                           :class="{ 'disabled' : coursePermalink !== 'custom' }"
                           placeholder="<?php esc_html_e( 'Enter a custom base', 'wp-courseware' ); ?>" :readonly="coursePermalink !== 'custom'"/>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>
