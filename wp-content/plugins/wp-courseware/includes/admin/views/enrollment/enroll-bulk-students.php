<script type="text/x-template" id="wpcw-enroll-bulk-students">
    <div id="wpcw-enroll-bulk-students-modal" class="wpcw-enroll-bulk-students-modal wpcw-modal wpcw-mfp-hide">
        <div class="modal-title">
            <h1><?php esc_html_e( 'Enroll Students', 'wp-courseware' ); ?></h1>
        </div>

        <div class="modal-body">
            <wpcw-notices></wpcw-notices>

            <wpcw-form-field classes="first wpcw-bulk-enroll-students-field">
                <label for="wpcw-students-select"><?php esc_html_e( 'Students', 'wp-courseware' ); ?></label>
                <select id="wpcw-students-select" data-placeholder="<?php esc_html_e( 'Select Students', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
            </wpcw-form-field>

            <wpcw-form-field classes="wpcw-bulk-enroll-courses-field">
                <label for="wpcw-courses-select"><?php esc_html_e( 'Courses', 'wp-courseware' ); ?></label>
                <select id="wpcw-courses-select" data-placeholder="<?php esc_html_e( 'Select Courses', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
            </wpcw-form-field>

            <wpcw-form-field>
                <button type="submit" class="button button-primary button-large" :class="{ 'disabled' : loading }" @click.prevent="enrollStudents" @disabled="loading">
                    <i class="wpcw-fa left" :class="{ 'wpcw-fa-user-plus' : ! loading, 'wpcw-fa-circle-o-notch wpcw-fa-spin' : loading }" aria-hidden="true"></i>
                    {{ loading ? '<?php esc_html_e( 'Enrolling Students...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Enroll Students', 'wp-courseware' ); ?>' }}
                </button>
            </wpcw-form-field>
        </div>
    </div>
</script>
