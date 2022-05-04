<?php
/**
 * Field Datepicker
 *
 * @since 4.5.0
 */
?>
<script type="text/x-template" id="wpcw-field-date-picker">
    <div class="wpcw-field-datepicker">
	    <input type="text"
	           v-model="date"
	           :class="`wpcw-date-picker size-` + size + ` ` + classes"
	           :name="name"
	           :maxlength="maxlength"
	           :pattern="pattern">
    </div>
</script>
