<?php
/**
 * Field: Bulk Grant Access
 *
 * @since 4.4.0
 */
?>
<script type="text/x-template" id="wpcw-field-bulk-grant-access">
    <div>
        <div v-if="notices" class="wpcw-grant-notices" v-html="notices"></div>
		<?php if ( current_user_can( 'manage_wpcw_settings' ) ) { ?>
            <a href="#" class="button-primary" :class="{ 'disabled' : loading }" @click.prevent="bulkGrantAll" @disabled="loading">
                {{ loading ? '<?php esc_html_e( 'Granting Access to All Existing Users...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'All Existing Users (including Administrators)', 'wp-courseware' ); ?>' }}
                <i v-if="loading" class="wpcw-fas" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : loading, 'wpcw-fa-unlock-alt' : ! loading }" aria-hidden="true"></i>
            </a>
            <a href="#" class="button-primary" :class="{ 'disabled' : loadingAlt }" @click.prevent="bulkGrantAdmins" @disabled="loadingAlt">
                {{ loadingAlt ? '<?php esc_html_e( 'Granting Access to Only All Existing Administrators...', 'wp-courseware' ); ?>' :
                '<?php esc_html_e( 'Only All Existing Administrators', 'wp-courseware' ); ?>' }}
                <i v-if="loadingAlt" class="wpcw-fas" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : loading, 'wpcw-fa-unlock-alt' : ! loadingAlt }" aria-hidden="true"></i>
            </a>
		<?php } else { ?>
            <a href="#" class="button-primary" :class="{ 'disabled' : loading }" @click.prevent="bulkGrantSubscribers" @disabled="loading">
                <i class="wpcw-fas" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : loading, 'wpcw-fa-unlock-alt' : ! loading }" aria-hidden="true"></i>
                {{ loading ? '<?php esc_html_e( 'Granting Access to All Existing Users...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'All Existing Users (including Administrators)', 'wp-courseware' ); ?>' }}
            </a>
		<?php } ?>
        <div v-if="messages" class="wpcw-grant-messages" v-html="messages"></div>
    </div>
</script>
