<script type="text/x-template" id="wpcw-tools-field-log">
    <div class="wpcw-tools-field-system-log">
        <h2><?php esc_html_e( 'System Log', 'wp-courseware' ); ?></h2>
        <div class="wpcw-message info">
            <p><?php esc_html_e( 'You can download the system log or copy and paste the information below. This log will be deleted every 24 hours.', 'wp-courseware' ); ?></p>
            <div class="actions">
                <button type="submit" class="button button-secondary" :class="{ 'disabled' : loading || downloading }" @click="downloadLog" @disabled="loading">
                    <i class="wpcw-fas wpcw-fa-download"></i>
                    {{ downloading ? '<?php esc_html_e( 'Downloading System Log...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Download System Log', 'wp-courseware' ); ?>' }}
                    <span v-if="downloading" class="wpcw-spinner spinner is-active right"></span>
                </button>
                <button type="submit" class="button button-secondary" :class="{ 'disabled' : loading || deleting }" @click="deleteLog" @disabled="loading || deleting">
                    <i class="wpcw-fas wpcw-fa-trash"></i>
                    {{ deleting ? '<?php esc_html_e( 'Deleting System Log...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Delete System Log', 'wp-courseware' ); ?>' }}
                    <span v-if="deleting" class="wpcw-spinner spinner is-active right"></span>
                </button>
            </div>
        </div>
        <textarea class="wpcw-system-log" v-model="systemLog" placeholder="<?php esc_html_e( 'Loading System Log...', 'wp-courseware' ); ?>" @click="selectLog"></textarea>
    </div>
</script>