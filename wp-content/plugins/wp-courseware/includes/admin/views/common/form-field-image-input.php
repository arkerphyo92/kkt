<?php
/**
 * Image Input Form Field Component.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-form-field-image-input">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-form-row-image-input">
            <div slot="label">
                <h3>{{ label }}</h3>
                <abbr v-show="tip" class="wpcw-tooltip" :title="tip" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
            </div>
            <div slot="input">
                <wpcw-form-field classes="wpcw-field-image-input">
                    <div class="input-with-end-button">
                        <input type="text" :name="name" :placeholder="placeholder" v-model.trim="image"/>
                        <input type="hidden" :name="image_key" v-model.trim="imageKeyValue"/>
                        <button class="button button-primary end-button" @click.prevent="displayMediaUploader">
                            <i class="wpcw-fas wpcw-fa-image left"></i>
                            {{ button }}
                        </button>
                    </div>
                    <span v-show="desc" class="desc" v-html="desc"></span>
                </wpcw-form-field>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>