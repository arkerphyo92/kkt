<?php
/**
 * Image Input Field Component.
 *
 * @since 4.4.0
 */
?>
<script type="text/x-template" id="wpcw-field-image">
    <div class="input-with-end-button" :class="inputclass">
        <input type="text" :name="name" :placeholder="placeholder" :class="inputclass" v-model.trim="image"/>
        <button class="button button-primary end-button" @click.prevent="displayMediaUploader">
            <i class="wpcw-fas wpcw-fa-image left"></i>
            <span class="button-text" v-html="inputbutton"></span>
        </button>
    </div>
</script>