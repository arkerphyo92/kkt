<?php
/**
 * Affiliates Settings Field Component.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-settings-field-affiliates">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-form-row-radio">
            <div slot="label">
                <h3><?php esc_html_e( 'Show Affiliate Link?', 'wp-courseware' ); ?></h3>
                <abbr class="wpcw-tooltip" title="<?php _e( 'Do you want to show a <code>Powered by WP Courseware</code> affiliate link at the bottom of the course outlines?', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field classes="wpcw-field-radio">
                    <span class="radio">
                        <label for="poweredbylinkyes">
                            <input type="radio" id="poweredbylinkyes" name="show_powered_by" value="yes" v-model="poweredByLink">
                            <span class="radio-label"><?php esc_html_e( 'Yes', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                    <span class="radio">
                        <label for="poweredbylinkno">
                            <input type="radio" id="poweredbylinkno" name="show_powered_by" value="no" v-model="poweredByLink">
                            <span class=" radio-label"><?php esc_html_e( 'No', 'wp-courseware' ); ?></span>
                        </label>
                    </span>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>
        <wpcw-form-row v-show="'yes' === poweredByLink" classes="wpcw-form-row-text">
            <div slot="label">
                <h3><?php esc_html_e( 'Affiliate ID', 'wp-courseware' ); ?></h3>
                <abbr class="wpcw-tooltip"
                      title="<?php _e( 'Provide your Affiliate ID, which will turn the <code>Powered by WP Courseware</code> into an affiliate link that earns you a percentage of every sale!', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>
            <div slot="input">
                <wpcw-form-field classes="wpcw-field-text">
                    <input type="text" class="form-field" name="affiliate_id" v-model="affiliateId" placeholder="<?php esc_html_e( 'Affiliate ID', 'wp-courseware' ); ?>"/>
                    <span class="desc"><?php printf( __( 'Login to the <a target="_blank" href="%s">member portal</a> to get your Affiliate ID.', 'wp-courseware' ), wpcw()->get_member_portal_url() ); ?></span>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>