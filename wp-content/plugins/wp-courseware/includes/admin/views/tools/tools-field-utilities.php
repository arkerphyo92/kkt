<script type="text/x-template" id="wpcw-tools-field-utilities">
    <div class="wpcw-tools-field-utilities">
        <h2><?php esc_html_e( 'System Utilities', 'wp-courseware' ); ?></h2>
        <table class="wpcw-tools-table widefat" cellspacing="0">
            <tbody>
            <tr class="wpcw-clear-transients-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Clear Transients', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This tool will clear the product/shop transients cache.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-large"
                            @click.prevent="clearTransients"
                            :class="{ 'disabled' : transientLoading }"
                            :disabled="transientLoading">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : transientLoading, 'wpcw-fa-trash-alt' : ! transientLoading }"></i>
						<?php esc_html_e( 'Clear Transients', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-delete-orphaned-question-tags-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Delete Orphaned Question Tags', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This tool will delete all question tags that have a count of zero.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="deleteOrphanedQuestionTags"
                            :class="{ 'disabled' : questionTagsLoading }"
                            :disabled="questionTagsLoading">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : questionTagsLoading, 'wpcw-fa-trash-alt' : ! questionTagsLoading }"></i>
						<?php esc_html_e( 'Delete Orphaned Question Tags', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-delete-orphaned-units-utility">
	            <th>
		            <strong class="name"><?php esc_html_e( 'Delete Orphaned Units', 'wp-courseware' ); ?></strong>
		            <p class="description"><?php esc_html_e( 'This tool will delete all units that are oprhaned and are disrupting course progress.', 'wp-courseware' ); ?></p>
	            </th>
	            <td class="run-tool">
		            <button class="button button-secondary button-large"
		                    @click.prevent="deleteOrphanedUnits"
		                    :class="{ 'disabled' : orphanedUnitsLoading }"
		                    :disabled="orphanedUnitsLoading">
			            <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : orphanedUnitsLoading, 'wpcw-fa-trash-alt' : ! orphanedUnitsLoading }"></i>
			            <?php esc_html_e( 'Delete Orphaned Units', 'wp-courseware' ); ?>
		            </button>
	            </td>
            </tr>
            <tr class="wpcw-delete-orphaned-question-tags-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Force Upgrade Courses', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This tool will upgrade all courses so they will show up in the Courses table.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="manuallyUpgradeCourses"
                            :class="{ 'disabled' : upgrading }"
                            :disabled="upgrading">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : upgrading, 'wpcw-fa-database' : ! upgrading }"></i>
						<?php esc_html_e( 'Force Upgrade Courses', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-reset-roles-and-capabilities-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Reset WP Courseware Roles & Capabilities', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This will reset your roles and capabilities for WP Courseware usage.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="manuallyResetRoles"
                            :class="{ 'disabled' : rolesReseting }"
                            :disabled="rolesReseting">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : rolesReseting, 'wpcw-fa-retweet' : ! rolesReseting }"></i>
			            <?php esc_html_e( 'Reset Roles & Capabilities', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-reset-usage-tracking-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Reset Usage Tracking Settings', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This will reset your usage tracking settings, causing it to show the opt-in banner again and not send any data.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="manuallyResetTracker"
                            :class="{ 'disabled' : reseting }"
                            :disabled="reseting">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : reseting, 'wpcw-fa-retweet' : ! reseting }"></i>
						<?php esc_html_e( 'Reset Usage Tracking', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-manually-send-data-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Manually Send Usage Tracking Data', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This is for sending usage tracking data manually so we can help troubleshoot issues. Internal use only.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="manuallySendTracker"
                            :class="{ 'disabled' : sending }"
                            :disabled="sending">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : sending, 'wpcw-fa-share-square' : ! sending }"></i>
						<?php esc_html_e( 'Send Usage Tracking Data', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-manually-run-updater-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Manually Run Updater', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This is a utility so we can help troubleshoot updater issues. Internal use only.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="manuallyRunUpdater"
                            :class="{ 'disabled' : running }"
                            :disabled="running">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : running, 'wpcw-fa-database' : ! running }"></i>
			            <?php esc_html_e( 'Manually Run Updater', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>
            <tr class="wpcw-manually-kill-updaters-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Manually Stop Updater Process', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This is a utility so we can help troubleshoot updater issues. Internal use only.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="manuallyKillUpdater"
                            :class="{ 'disabled' : killing }"
                            :disabled="killing">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : killing, 'wpcw-fa-ban' : ! killing }"></i>
			            <?php esc_html_e( 'Manually Stop Updater', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>


            <tr class="wpcw-fix-database-utility">
                <th>
                    <strong class="name"><?php esc_html_e( 'Fix Database', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This is an internal utility so we can help troubleshoot database issues. Internal use only.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="fixDatabase"
                            :class="{ 'disabled' : fixing }"
                            :disabled="fixing">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : fixing, 'wpcw-fa-wrench' : ! fixing }"></i>
			            <?php esc_html_e( 'Fix Database', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>

			<tr class="wpcw-fix-order-discounts-utility">
                <th>
				
                    <strong class="name"><?php esc_html_e( 'Fix Order Discounts and Totals', 'wp-courseware' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'This will fix installment and subscription orders that incorrectly display a discount amount and PayPal orders that display a total that does not include tax.', 'wp-courseware' ); ?></p>
                </th>
                <td class="run-tool">
                    <button class="button button-secondary button-large"
                            @click.prevent="fixOrderDiscounts"
                            :class="{ 'disabled' : fixing }"
                            :disabled="fixing">
                        <i class="wpcw-fas left" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : fixing, 'wpcw-fa-wrench' : ! fixing }"></i>
			            <?php esc_html_e( 'Fix Order Details', 'wp-courseware' ); ?>
                    </button>
                </td>
            </tr>


            </tbody>
        </table>
    </div>
</script>
