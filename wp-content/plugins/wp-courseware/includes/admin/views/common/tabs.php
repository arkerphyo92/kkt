<script type="text/x-template" id="wpcw-tabs">
    <div class="wpcw-tabs">
        <ul role="tablist" class="wpcw-tabs-nav">
            <li v-for="(tab, i) in tabs" :key="i" :class="{ 'is-active': tab.isActive, 'is-disabled': tab.isDisabled }" class="wpcw-tab-title" role="presentation" :aria-selected="tab.isActive" v-show="tab.isVisible">
                <a :aria-controls="tab.hash" @click="selectTab(tab.hash, $event)" :href="tab.hash" class="wpcw-tab" role="tab">
                    <i v-if="tab.icon" :class="tab.icon"></i>
                    <span v-if="tab.label" class="tab-label" v-html="tab.label"></span>
                </a>
            </li>
        </ul>
        <div class="wpcw-tabs-panels">
            <slot></slot>
        </div>
    </div>
</script>
