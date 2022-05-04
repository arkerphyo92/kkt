<?php
/**
 * Page Form Field Component.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-form-field-page">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-form-row-page">
            <div slot="label">
                <h3>{{ label }}</h3>
                <abbr v-show="tip" class="wpcw-tooltip" :title="tip" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
            </div>
            <div slot="input">
                <wpcw-form-field classes="wpcw-field-page">
                    <select :id="getPageSelectId" :name="name" v-model="pageSelect" :placeholder="placeholder">
                        <option value="" class="no-value">{{ blank }}</option>
                        <option v-for="(page, index) in getPages" :value="page.id" :key="index">{{ page.title }}</option>
                    </select>
                    <button v-if="showCreateButton" type="button" class="button button-secondary" :class="{ 'disabled' : loading }" @click.prevent="createPage()" @disabled="loading">
                        <i :class="{ 'wpcw-fa wpcw-fa-circle-notch wpcw-fa-spin' : loading, 'wpcw-fas wpcw-fa-plus-square left' : ! loading }" aria-hidden="true"></i>
                        {{ loading ? '<?php esc_html_e( 'Creating...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Create', 'wp-courseware' ); ?>' }}
                    </button>
                </wpcw-form-field>
                <span v-show="desc" class="desc">{{ desc }}</span>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>