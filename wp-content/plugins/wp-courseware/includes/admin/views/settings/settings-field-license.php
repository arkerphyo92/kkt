<?php
/**
 * License Settings Field Component.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-settings-field-license">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-form-row-text">
            <div slot="label">
                <h3><?php esc_html_e( 'License Activation', 'wp-courseware' ); ?></h3>
                <abbr class="wpcw-tooltip" title="<?php esc_html_e( 'Enter your license key to recieve automatic updates and support.', 'wp-courseware' ); ?>"
                      rel="wpcw-tooltip">
                    <i class="wpcw-fas wpcw-fa-info-circle"></i>
                </abbr>
            </div>

            <div slot="input">
                <wpcw-form-field>
                    <input type="text"
                           placeholder="<?php esc_html_e( 'License Key', 'wp-courseware' ); ?>"
                           id="wpcw-license-key"
                           class="license-field text-password"
                           name="licensekey"
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false"
                           :class="licenseUpdating"
                           v-model="licenseKey"/>

                    <input type="hidden"
                           id="wpcw-license-status"
                           class="license-field-hidden"
                           name="licensestatus"
                           v-model="licenseStatus"/>

                    <button v-if="! licenseValid" class="button button-primary license-button" :class="{ 'disabled' : activating }" @click.prevent="activateLicense" @disabled="activating">
                        <i class="wpcw-fas" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : activating, 'wpcw-fa-check-circle' : ! activating }" aria-hidden="true"></i>
                        {{ activating ? '<?php esc_html_e( 'Activating...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Activate', 'wp-courseware' ); ?>' }}
                    </button>

                    <button v-if="licenseValid" class="button button-secondary license-button" :class="{ 'disabled' : deactivating }" @click.prevent="deactivateLicense" @disabled="deactivating">
                        <i class="wpcw-fas" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : deactivating, 'wpcw-fa-times-circle' : ! deactivating }" aria-hidden="true"></i>
                        {{ deactivating ? '<?php esc_html_e( 'Deactivating...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Deactivate', 'wp-courseware' ); ?>' }}
                    </button>

                    <span class="desc"><?php printf( __( 'You can find your license key on your <a href="%s" target="_blank">member portal licenses page</a>.', 'wp-courseware' ), wpcw()->get_member_portal_license_url() ); ?></span>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>