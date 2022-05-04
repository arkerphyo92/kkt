<?php
/**
 * Classroom Send Email Vue Template.
 *
 * @note See page-course-classroom.php for inclusion.
 *
 * @since 4.1.0
 */
?>
<script type="text/x-template" id="wpcw-classroom-send-email">
    <div id="wpcw-classroom-send-email-modal" class="wpcw-classroom-send-email-modal wpcw-modal wpcw-mfp-hide">
        <div class="modal-title">
            <h1>{{ single ? '<?php _e( 'Email:', 'wp-courseware' ); ?> ' + studentName : '<?php esc_html_e( 'Email Classroom Students', 'wp-courseware' ); ?>' }}</h1>
        </div>

        <div class="modal-body">
            <wpcw-modal-notices></wpcw-modal-notices>

            <wpcw-form-field classes="first">
                <label for="emailSubject" class="label"><?php esc_html_e( 'Email Subject', 'wp-courseware' ); ?></label>
                <input type="text" id="emailSubject" v-model="emailSubject" placeholder="<?php esc_html_e( 'Email Subject', 'wp-courseware' ); ?>"/>
            </wpcw-form-field>

            <wpcw-form-field>
                <label for="emailMessage" class="label"><?php esc_html_e( 'Email Message', 'wp-courseware' ); ?></label>
	            <textarea ref="emailMessage" id="wpcw-email-message" class="wpcw-wp-editor-textarea" v-model="emailMessage" placeholder="<?php esc_html_e( 'Email Content', 'wp-courseware' ); ?>"></textarea>
            </wpcw-form-field>

            <wpcw-form-field>
                <button type="button" class="button button-primary" :class="{ 'disabled' : loading }" @click.prevent="sendEmail" @disabled="loading">
                    <i v-if="! loading" class="wpcw-fa wpcw-fa-paper-plane left" aria-hidden="true"></i>
                    <i v-if="loading" class="wpcw-fa wpcw-fa-circle-o-notch wpcw-fa-spin left" aria-hidden="true"></i>
                    {{ loading ? '<?php esc_html_e( 'Sending Email...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Send Email', 'wp-courseware' ); ?>' }}
                </button>
            </wpcw-form-field>
        </div>
    </div>
</script>
