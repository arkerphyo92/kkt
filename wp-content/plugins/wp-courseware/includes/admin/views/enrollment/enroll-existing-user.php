<script type="text/x-template" id="wpcw-enroll-existing-user">
    <div id="wpcw-enroll-existing-user-modal" class="wpcw-enroll-existing-user-modal wpcw-modal wpcw-mfp-hide">
        <div class="modal-title">
            <h1><?php esc_html_e( 'Enroll Students', 'wp-courseware' ); ?></h1>
        </div>

        <div class="modal-body">
            <wpcw-notices></wpcw-notices>

            <wpcw-form-field classes="first wpcw-enroll-existing-users-field">
                <input type="hidden" v-model="users"/>
                <select id="enroll-existing-user-select" class="enroll-existing-user-select" data-placeholder="<?php esc_html_e( 'Select Students', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
            </wpcw-form-field>

            <wpcw-form-field>
                <button type="submit" class="button button-primary button-large" :class="{ 'disabled' : loading }" @click.prevent="enrollUsers" @disabled="loading">
                    <i class="wpcw-fa left" :class="{ 'wpcw-fa-user-plus' : ! loading, 'wpcw-fa-circle-o-notch wpcw-fa-spin' : loading }" aria-hidden="true"></i>
                    {{ loading ? '<?php esc_html_e( 'Enrolling Students...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Enroll Students', 'wp-courseware' ); ?>' }}
                </button>
            </wpcw-form-field>
        </div>
    </div>
</script>
