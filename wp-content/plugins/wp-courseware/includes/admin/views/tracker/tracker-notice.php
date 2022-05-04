<script type="text/x-template" id="wpcw-tracker-notice">
    <div class="wpcw-admin-notice notice is-dismissible" :class="{ 'notice-error' : hasError, 'notice-info' : ! hasError && ! allowed, 'notice-success' : allowed }">
        <div class="wpcw-admin-notice-inner">
            <div v-if="! hasSubmitted">
                <span class="wpcw-tracker-message"><?php esc_html_e( 'Allow WP Courseware to anonymously track how this plugin is used so we can keep making it the best L.M.S. plugin on the planet! No sensitive data is tracked.', 'wp-courseware' ); ?></span>
                <span v-if="hasError" class="wpcw-tracker-error" v-html="error"></span>
                <button id="wpcw-tracker-allow"
                        class="button-primary wpcw-tracking-button"
                        :class="{ 'disabled' : allowLoading }"
                        @click.prevent="allowTracking"
                        :disabled="allowLoading">
                    <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : allowLoading, 'wpcw-fa-check-circle' : ! allowLoading }"></i>
                    {{ allowLoading ? '<?php esc_html_e( 'Allowing...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Allow', 'wp-courseware' ); ?>' }}
                </button>
                <button id="wpcw-tracker-disallow"
                        class="button-secondary wpcw-tracking-button"
                        :class="{ 'disabled' : disallowLoading }"
                        @click.prevent="disallowTracking"
                        :disabled="disallowLoading">
                    <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : disallowLoading, 'wpcw-fa-ban' : ! disallowLoading }"></i>
                    {{ disallowLoading ? '<?php esc_html_e( 'Disallowing...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Do Not Allow', 'wp-courseware' ); ?>' }}
                </button>
            </div>
            <div v-if="allowed">
                <span class="wpcw-tracker-message-allowed"><?php esc_html_e( 'Thank you! We appreciate the support and we will use this anonymous data to make the best L.M.S plugin on the planet!', 'wp-courseware' ); ?></span>
            </div>
            <div v-if="disallowed">
                <span class="wpcw-tracker-message-disallowed"><?php esc_html_e( 'Thanks for letting us know! No anonymous data will be tracked. Thanks again for choosing WP Courseware to create your online courses!', 'wp-courseware' ); ?></span>
            </div>
        </div>
    </div>
</script>