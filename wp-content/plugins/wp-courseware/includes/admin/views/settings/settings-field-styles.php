<?php
/**
 * Styles Settings Field Component.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-settings-field-styles">
    <wpcw-form-table>
        <wpcw-form-row>
            <div slot="label">
                <h3><?php esc_html_e( 'Use Default CSS?', 'wp-courseware' ); ?></h3>
                <abbr class="wpcw-tooltip"
                      title="<?php _e( 'If you want to style your training course material yourself, you can disable the default stylesheet. If in doubt, select <code>Yes</code>.', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="usedefaultcssyes">
                            <input type="radio" id="usedefaultcssyes" name="use_default_css" value="yes" v-model="useDefaultCss">
                            <span class="radio-label"><?php esc_html_e( 'Yes', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                    <span class="radio">
                        <label for="usedefaultcssno">
                            <input type="radio" id="usedefaultcssno" name="use_default_css" value="no" v-model="useDefaultCss">
                            <span class="radio-label"><?php esc_html_e( 'No', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <wpcw-form-row v-show="useDefaultCss === 'yes'">
            <div slot="label">
                <h3><?php esc_html_e( 'Customize Colors?', 'wp-courseware' ); ?></h3>
                <abbr class="wpcw-tooltip"
                      title="<?php _e( 'This allows you to customize the style colors of your training material without knowing css. If in doubt, select <code>Yes</code>.', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field>
                    <span class="radio">
                        <label for="customizeColorsYes">
                            <input type="radio" id="customizeColorsYes" name="customize_colors" value="yes" v-model="customizeColors">
                            <span class="radio-label"><?php esc_html_e( 'Yes', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                    <span class="radio">
                        <label for="customizeColorsNo">
                            <input type="radio" id="customizeColorsNo" name="customize_colors" value="no" v-model="customizeColors">
                            <span class="radio-label"><?php esc_html_e( 'No', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>

        <tr v-show="showColorsField" valign="top" class="wpcw-form-row last">
            <td scope="row" valign="top" class="input-cell" colspan="2">
                <div class="input-cell-content">
                    <wpcw-settings-field-style-colors defaultColors="<?php echo wpcw()->styles->get_colors_json(); ?>"></wpcw-settings-field-style-colors>
                </div>
            </td>
        </tr>
    </wpcw-form-table>
</script>