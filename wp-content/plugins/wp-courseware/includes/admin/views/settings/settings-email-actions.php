<?php
/**
 * Styles Settings Email Preview.
 *
 * @since 4.3.0
 */

/** @var \WPCW\Emails\Email $email */
$email = $this->email;

// Check for email.
if ( empty( $email ) ) {
	return;
}
?>
<script type="text/x-template" id="wpcw-settings-email-actions">
    <div class="wpcw-email-actions">
		<?php if ( $email->get_object_type() ) { ?>
            <div class="wpcw-form-field email-object-type-select">
				<?php if ( 'order' === $email->get_object_type() ) { ?>
                    <select id="wpcw-email-orders-select" data-placeholder="<?php esc_html_e( 'Select an Order...', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
				<?php } elseif ( 'subscription' === $email->get_object_type() ) { ?>
                    <select id="wpcw-email-subscriptions-select" data-placeholder="<?php esc_html_e( 'Select a Subscription...', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
				<?php } elseif ( 'student' === $email->get_object_type() ) { ?>
                    <select id="wpcw-email-students-select" data-placeholder="<?php esc_html_e( 'Select Student...', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
				<?php } elseif ( 'course' === $email->get_object_type() ) { ?>
                    <select id="wpcw-email-courses-select" data-placeholder="<?php esc_html_e( 'Select a Course...', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
				<?php } ?>
            </div>
		<?php } ?>
        <a target="_blank" :href="viewurl" class="button-secondary">
            <i class="wpcw-fas wpcw-fa-eye left"></i>
			<?php esc_html_e( 'View Email', 'wp-courseware' ); ?>
        </a>
        <a target="_blank" :href="sendurl" class="button-secondary">
            <i class="wpcw-fas wpcw-fa-envelope left"></i>
			<?php esc_html_e( 'Send Test Email', 'wp-courseware' ); ?>
        </a>
    </div>
</script>