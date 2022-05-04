<?php
/**
 * Image Input Form Field Color Picker.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-form-field-color-picker">
    <wpcw-form-table>
        <wpcw-form-row classes="wpcw-form-row-color-picker">
            <div slot="label">
                <h3>{{ label }}</h3>
                <abbr v-show="tip" class="wpcw-tooltip" :title="tip" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
            </div>
            <div slot="input">
                <wpcw-form-field classes="wpcw-field-color-picker">
                    <input type="text" v-model="color" :id="id" :name="name" class="wpcw-color-picker" :data-default-color="defaultcolor"/>
                </wpcw-form-field>
                <span v-show="desc" class="desc">{{ desc }}</span>
            </div>
        </wpcw-form-row>
    </wpcw-form-table>
</script>