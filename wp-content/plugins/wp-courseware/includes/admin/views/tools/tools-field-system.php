<script type="text/x-template" id="wpcw-tools-field-system">
    <div class="wpcw-tools-field-system">
        <h2><?php esc_html_e( 'System Information', 'wp-courseware' ); ?></h2>
        <div class="wpcw-message info">
            <p><?php esc_html_e( 'Please download the system info report or copy and paste the information below in the ticket when contacting support.', 'wp-courseware' ); ?></p>
            <div class="actions">
                <button type="submit" class="button button-secondary" :class="{ 'disabled' : loading || downloading }" @click="downloadReport" @disabled="loading">
                    <i class="wpcw-fa wpcw-fa-download"></i>
                    {{ downloading ? '<?php esc_html_e( 'Downloading Report...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Download System Info Report', 'wp-courseware' ); ?>' }}
                    <span v-if="downloading" class="wpcw-spinner spinner is-active right"></span>
                </button>
            </div>
        </div>
        <textarea class="wpcw-system-report" v-model="systemReport" placeholder="<?php esc_html_e( 'Loading System Report...', 'wp-courseware' ); ?>" @click="selectReport"></textarea>
    </div>
</script>