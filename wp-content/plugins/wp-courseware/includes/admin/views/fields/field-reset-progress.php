<?php
/**
 * Field: Reset Progress.
 *
 * @since 4.4.0
 */
?>
<script type="text/x-template" id="wpcw-field-reset-progress">
    <div>
        <div v-if="notices" class="wpcw-grant-notices" v-html="notices"></div>
        <a href="#" class="button-primary" :class="{ 'disabled' : loading }" @click.prevent="resetProgress" @disabled="loading">
            {{ loading ? '<?php esc_html_e( 'Reseting All Users to the start...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Reset All Users on this Course to the start', 'wp-courseware' ); ?>' }}
            <i v-if="loading" class="wpcw-fas" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : loading }" aria-hidden="true"></i>
        </a>
    </div>
</script>
