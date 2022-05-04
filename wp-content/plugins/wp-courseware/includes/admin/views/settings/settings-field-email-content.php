<?php
/**
 * Email Content Settings Field Component.
 *
 * @since 4.3.0
 */
$email_slug = isset( $_GET['email'] ) ? esc_attr( $_GET['email'] ) : '';
$email      = wpcw()->emails->get_email( esc_attr( $email_slug ) );

if ( is_null( $email ) ) {
	return;
}

$email->setup();
?>
<script type="text/x-template" id="wpcw-settings-field-email-content">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-form-row-email-content">
            <div slot="label">
                <h3>{{ label }}</h3>
                <abbr v-show="tip" class="wpcw-tooltip" :title="tip" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
            </div>
        </wpcw-form-row>
        <tr valign="top" class="wpcw-form-row wpcw-form-row-email-content">
            <td colspan="2">
                <wpcw-form-field v-show="isPlain" classes="wpcw-form-field-email-content wpcw-form-field-email-content-plain">
                    <textarea id="<?php echo $email->get_id() . '_content_plain'; ?>" name="<?php echo $email->get_id() . '_content_plain'; ?>" v-model="contentPlain"></textarea>
                </wpcw-form-field>

                <wpcw-form-field v-show="isHtml" classes="wpcw-form-field-email-content wpcw-form-field-email-content-html">
					<?php wp_editor( $email->get_content_html(), $email->get_id() . '_content_html', array( 'media_buttons' => true, 'textarea_rows' => 20 ) ); ?>
                </wpcw-form-field>
            </td>
        </tr>
    </wpcw-form-table>
</script>