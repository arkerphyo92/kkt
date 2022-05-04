<script type="text/x-template" id="wpcw-tab">
	<section v-show="isActive" :aria-hidden="! isActive" class="wpcw-tab-panel" :id="hash" role="tabpanel">
		<slot></slot>
	</section>
</script>
