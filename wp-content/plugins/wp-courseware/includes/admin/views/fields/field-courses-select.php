<?php
/**
 * Field Courses Select Component.
 *
 * @since 4.5.0
 */
?>
<script type="text/x-template" id="wpcw-field-courses-select">
	<div class="wpcw-field-courses-select-component">
		<select :id="`wpcw-field-courses-select-` + id"
		        class="wpcw-field-courses-select"
		        :data-placeholder="placeholder"
		        data-allow_clear="true"
		        :name="name"
		        style="width:100%;"
		        multiple="multiple">
		</select>
	</div>
</script>
