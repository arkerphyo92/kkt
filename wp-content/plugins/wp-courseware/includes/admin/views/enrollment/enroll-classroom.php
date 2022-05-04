<script type="text/x-template" id="wpcw-enroll-classroom">
    <div id="wpcw-enroll-classroom-modal" class="wpcw-enroll-classroom-modal wpcw-modal wpcw-mfp-hide">
        <div class="modal-title">
            <h1><?php esc_html_e( 'Enroll Classroom', 'wp-courseware' ); ?></h1>
        </div>

        <div class="modal-body">
            <wpcw-notices></wpcw-notices>


	        <wpcw-form-field classes="first wpcw-enroll-classroom-field">
		        <p style="margin-top: 0;"><?php esc_html_e( 'Enroll students from this classroom into selected courses.', 'wp-courseware' ); ?></p>
	            <select id="wpcw-courses-select" data-placeholder="<?php esc_html_e( 'Select Courses', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
            </wpcw-form-field>

            <wpcw-form-field>
                <button type="submit" class="button button-primary button-large" :class="{ 'disabled' : loading }" @click.prevent="enrollClassroom" @disabled="loading">
                    <i class="wpcw-fa left" :class="{ 'wpcw-fa-user-plus' : ! loading, 'wpcw-fa-circle-o-notch wpcw-fa-spin' : loading }" aria-hidden="true"></i>
                    {{ loading ? '<?php esc_html_e( 'Enrolling Classroom...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Enroll Classroom', 'wp-courseware' ); ?>' }}
                </button>
            </wpcw-form-field>
        </div>
    </div>
</script>
