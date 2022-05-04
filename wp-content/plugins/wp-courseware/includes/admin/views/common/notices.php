<script type="text/x-template" id="wpcw-notices">
    <div v-if="hasNotices" class="wpcw-notices">
        <div v-for="(notice, index) in notices" :key="notice.id" class="wpcw-notice notice is-dismissible" :class="noticeType( notice )">
            <p v-html="notice.message"></p>
            <button type="button" class="notice-dismiss" @click.prevent="removeNotice( index )">
                <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'wp-courseware' ); ?></span>
            </button>
        </div>
        <slot></slot>
    </div>
</script>