<script type="text/x-template" id="wpcw-form">
    <form class="wpcw-form" @submit.prevent="save" autocomplete="nope">
        <table class="wpcw-form-table">
            <tbody>
                <slot></slot>
                <tr class="wpcw-form-row" valign="top">
                    <th colspan="2">
                        <div class="wpcw-form-submit">
                            <button type="submit" class="button button-primary" :class="formButtonClass" @disabled="isLoading">
                                <i v-if="isLoading" class="wpcw-fa wpcw-fa-circle-notch wpcw-fa-spin left" aria-hidden="true"></i>
                                {{ formButtonText }}
                            </button>
                        </div>
                    </th>
                </tr>
            </tbody>
        </table>
    </form>
</script>