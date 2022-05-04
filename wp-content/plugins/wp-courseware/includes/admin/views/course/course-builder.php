<?php
/**
 * Course Builder.
 *
 * @since 4.4.0
 */
?>
<script type="text/x-template" id="wpcw-course-builder">
	<div class="wpcw-course-builder">
		<div v-if="! loaded" class="wpcw-course-builder-loader">
			<div class="wpcw-course-builder-loading">
				<div class="line-heading"></div>
				<div class="line-sm"></div>
				<div class="line-xs"></div>
				<div class="line-df"></div>
				<div class="line-lgx"></div>
				<div class="line-lg"></div>
				<div class="line-df"></div>
				<div class="line-lg"></div>
				<div class="line-lgx"></div>
			</div>
		</div>

		<div id="wpcw-course-builder-edit-module-modal" class="wpcw-course-builder-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<h2><?php esc_html_e( 'Edit Module:', 'wp-courseware' ); ?> <span class="modal-item-title">{{ module.title }}</span></h2>
			</div>

			<div class="modal-body">
				<div v-show="module.loading" class="wpcw-form-items-loading"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i></div>
				<div v-show="! module.loading">
					<div class="wpcw-form-field first">
						<label for="wpcw-edit-module-title"><?php esc_html_e( 'Module Title', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<input ref="editModuleTitle" id="wpcw-edit-module-title"
						       type="text"
						       v-model="module.title"
						       placeholder="<?php esc_html_e( 'Module Title', 'wp-courseware' ); ?>"
						       @keyup.enter="updateModule( module.id )"/>
					</div>
					<div class="wpcw-form-field">
						<label for="wpcw-edit-module-description"><?php esc_html_e( 'Module Description', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<textarea ref="editModuleDesc" id="wpcw-edit-module-description"
						          v-model="module.desc"
						          placeholder="<?php esc_html_e( 'Module Description', 'wp-courseware' ); ?>"
						          @keyup.enter="updateModule( module.id )"></textarea>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button class="modal-action button-primary"
				        :class="{ 'disabled' : ! module.id || ! module.title || ! module.desc || module.updating }"
				        :disabled="! module.id || ! module.title || ! module.desc || module.updating"
				        @click.prevent="updateModule( module.id )">
					<i v-if="module.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					<i v-if="! module.updating" class="wpcw-fas wpcw-fa-check-circle left"></i>
					{{ module.updating ? '<?php esc_html_e( 'Updating Module...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Update Module', 'wp-courseware' ); ?>' }}
				</button>
				<button class="modal-action button-secondary remove-action"
				        :class="{ 'disabled' : ! module.id }"
				        :disabled="! module.id"
				        @click.prevent="deleteModule( module.id )"><i class="wpcw-fas wpcw-fa-trash"></i></button>
			</div>
		</div>

		<div id="wpcw-course-builder-edit-unit-modal" class="wpcw-course-builder-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<h2>
					<span v-show="unit.teaser == 1" class="unit-teaser-icon"><i class="wpcw-fas wpcw-fa-eye"></i></span>
					<?php esc_html_e( 'Edit Unit:', 'wp-courseware' ); ?> <span class="modal-item-title">{{ unit.title }}</span>
				</h2>
			</div>

			<div class="modal-body">
				<div v-if="unit.loading" class="wpcw-form-items-loading"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i></div>
				<div v-if="! unit.loading">
					<div class="wpcw-form-field first">
						<label for="wpcw-edit-unit-title"><?php esc_html_e( 'Unit Title', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<input ref="editUnitTitle" id="wpcw-edit-unit-title"
						       type="text"
						       v-model="unit.title"
						       placeholder="<?php esc_html_e( 'Unit Title', 'wp-courseware' ); ?>"
						       @keyup.enter="updateUnit( unit.id, unit.module_id )"/>
					</div>
					<div class="wpcw-form-field">
						<label for="wpcw-edit-unit-description"><?php esc_html_e( 'Unit Content', 'wp-courseware' ); ?></label>
						<textarea ref="editUnitDesc" id="wpcw-edit-unit-description"
						          v-model="unit.desc"
						          placeholder="<?php esc_html_e( 'Unit Content', 'wp-courseware' ); ?>"
						          @keyup.enter="updateUnit( unit.id, unit.module_id )"></textarea>
					</div>
					<div class="wpcw-form-field-float-wrapper">
						<div class="wpcw-form-field-float-left">
							<div class="wpcw-form-field left">
								<div class="wpcw-unit-drip">
									<div class="wpcw-form-field-title"><?php esc_html_e( 'Unit Drip', 'wp-courseware' ); ?></div>
									<div class="wpcw-unit-drip-section">
										<label for="wpcw-edit-unit-drip-type"><?php esc_html_e( 'When should this unit become available?', 'wp-courseware' ); ?></label>
										<select id="wpcw-edit-unit-drip-type" v-model="unit.drip.type">
											<option value="" selected="selected"><?php echo '--- ' . esc_html__( 'No Delay', 'wp-courseware' ) . ' ---'; ?></option>
											<option value="drip_specific"><?php esc_html_e( 'On a specific date', 'wp-courseware' ); ?></option>
											<option value="drip_interval"><?php esc_html_e( 'A specific interval after the course start date', 'wp-courseware' ); ?></option>
										</select>
									</div>
									<div v-show="unit.drip.type === 'drip_specific'" class="wpcw-unit-drip-specific wpcw_datepicker_wrapper">
										<label for="wpcw-edit-unit-drip-specific"><?php esc_html_e( 'Select the date on which this unit should become available...', 'wp-courseware' ); ?></label>
										<input id="wpcw-edit-unit-drip-specific" type="text" class="wpcw_datepicker_vis_edit" v-model="unit.drip.date">
									</div>
									<div v-show="unit.drip.type === 'drip_interval'" class="wpcw-unit-drip-interval">
										<label for="wpcw-edit-unit-drip-interval"><?php esc_html_e( 'How long after the user is enrolled should this unit become available?', 'wp-courseware' ); ?></label>
										<input id="wpcw-edit-unit-drip-interval" type="number" class="wpcw-number" v-model="unit.drip.interval">
										<select id="wpcw-edit-unit-drip-interval-type" v-model="unit.drip.interval_type">
											<option value="interval_hours"><?php esc_html_e( 'Hour(s)', 'wp-courseware' ); ?></option>
											<option value="interval_days"><?php esc_html_e( 'Days(s)', 'wp-courseware' ); ?></option>
											<option value="interval_weeks"><?php esc_html_e( 'Weeks(s)', 'wp-courseware' ); ?></option>
											<option value="interval_months"><?php esc_html_e( 'Months(s)', 'wp-courseware' ); ?></option>
											<option value="interval_years"><?php esc_html_e( 'Years(s)', 'wp-courseware' ); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="wpcw-form-field-float-right">
							<div class="wpcw-form-field right">
								<div class="wpcw-unit-teaser">
									<div class="wpcw-form-field-title"><?php esc_html_e( 'Unit Teaser / Preview', 'wp-courseware' ); ?></div>
									<label for="wpcw-edit-unit-teaser-checkbox">
										<input id="wpcw-edit-unit-teaser-checkbox" class="wpcw-unit-teaser-checkbox" type="checkbox" v-model="unit.teaser" true-value="1" false-value="0">
										<span class="wpcw-unit-teaser-checkbox-label"><?php esc_html_e( 'Teaser / Preview Unit', 'wp-courseware' ); ?></span>
									</label>
									<p><?php _e( 'Check the box above to allow this Unit to be accessed as a <strong>Teaser</strong> or <strong>Free</strong> Unit.', 'wp-courseware' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button class="modal-action button-primary"
				        :class="{ 'disabled' : ! unit.id || ! unit.title || unit.updating }"
				        :disabled="! unit.id || ! unit.title || unit.updating"
				        @click.prevent="updateUnit( unit.id, unit.module_id )">
					<i v-if="unit.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					<i v-if="! unit.updating" class="wpcw-fas wpcw-fa-check-circle left"></i>
					{{ unit.updating ? '<?php esc_html_e( 'Updating Unit...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Update Unit', 'wp-courseware' ); ?>' }}
				</button>
				<button class="modal-action button-secondary remove-action"
				        :class="{ 'disabled' : ! unit.id }"
				        :disabled="! unit.id"
				        @click.prevent="deleteUnit( unit.id, unit.module_id )">
					<i class="wpcw-fas wpcw-fa-trash"></i>
				</button>
			</div>
		</div>

		<div id="wpcw-course-builder-edit-quiz-modal" class="wpcw-course-builder-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<h2><?php esc_html_e( 'Edit Quiz:', 'wp-courseware' ); ?> <span class="modal-item-title">{{ quiz.title }}</span></h2>
			</div>

			<div class="modal-body">
				<div v-if="quiz.loading" class="wpcw-form-items-loading"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i></div>
				<div v-if="! quiz.loading">
					<div class="wpcw-form-field first">
						<label for="wpcw-edit-quiz-title"><?php esc_html_e( 'Quiz Title', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<input ref="editQuizTitle" id="wpcw-edit-quiz-title"
						       type="text"
						       v-model="quiz.title"
						       placeholder="<?php esc_html_e( 'Quiz Title', 'wp-courseware' ); ?>"
						       @keyup.enter="updateQuiz( quiz.id, quiz.unit_id, quiz.module_id )"/>
					</div>
					<div class="wpcw-form-field">
						<label for="wpcw-edit-quiz-description"><?php esc_html_e( 'Quiz Description', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<textarea ref="editQuizDesc" id="wpcw-edit-quiz-description"
						          v-model="quiz.desc"
						          placeholder="<?php esc_html_e( 'Quiz Description', 'wp-courseware' ); ?>"
						          @keyup.enter="updateQuiz( quiz.id, quiz.unit_id, quiz.module_id )"></textarea>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button class="modal-action button-primary"
				        :class="{ 'disabled' : ! quiz.id || ! quiz.title || ! quiz.desc || quiz.updating }"
				        :disabled="! quiz.id || ! quiz.title || ! quiz.desc || quiz.updating"
				        @click.prevent="updateQuiz( quiz.id, quiz.unit_id, quiz.module_id )">
					<i v-if="quiz.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					<i v-if="! quiz.updating" class="wpcw-fas wpcw-fa-check-circle left"></i>
					{{ quiz.updating ? '<?php esc_html_e( 'Updating Quiz...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Update Quiz', 'wp-courseware' ); ?>' }}
				</button>
				<button class="modal-action button-secondary remove-action"
				        :class="{ 'disabled' : ! quiz.id }"
				        :disabled="! quiz.id"
				        @click.prevent="deleteQuiz( quiz.id, quiz.unit_id, quiz.module_id )">
					<i class="wpcw-fas wpcw-fa-trash"></i>
				</button>
			</div>
		</div>

		<div id="wpcw-course-builder-module-modal" class="wpcw-course-builder-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<a class="modal-tab"
				   href="#"
				   :class="{ 'modal-tab-active' : module.add }"
				   @click.prevent="actionAdd( 'module' )">
					<?php esc_html_e( 'Add Module', 'wp-courseware' ); ?>
				</a>
			</div>

			<div class="modal-body">
				<div class="add modal-tab-content" :class="{ 'modal-tab-content-active' : module.add }">
					<div class="wpcw-form-field first">
						<label for="wpcw-add-module-title"><?php esc_html_e( 'Module Title', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<input ref="addModuleTitle"
						       id="wpcw-add-module-title"
						       type="text"
						       v-model="module.title"
						       placeholder="<?php esc_html_e( 'Module Title', 'wp-courseware' ); ?>"/>
					</div>
					<div class="wpcw-form-field">
						<label for="wpcw-module-description"><?php esc_html_e( 'Module Description', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<textarea ref="addModuleDesc"
						          id="wpcw-module-description"
						          v-model="module.desc"
						          placeholder="<?php esc_html_e( 'Module Description', 'wp-courseware' ); ?>"></textarea>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button v-show="module.add"
				        class="modal-action button-primary"
				        :class="{ 'disabled' : ! module.title || ! module.desc || module.updating }"
				        :disabled="! module.title || ! module.desc || module.updating"
				        @click.prevent="insertModule()">
					<i v-if="module.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					<i v-if="! module.updating" class="wpcw-fas wpcw-fa-check-circle left"></i>
					{{ module.updating ? '<?php esc_html_e( 'Adding Module...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Add Module', 'wp-courseware' ); ?>' }}
				</button>
			</div>
		</div>

		<div id="wpcw-course-builder-unit-modal" class="wpcw-course-builder-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<a class="modal-tab" :class="{ 'modal-tab-active' : unit.add }" href="#" @click.prevent="actionAdd( 'unit' )"><?php esc_html_e( 'Add New Unit', 'wp-courseware' ); ?></a>
				<a class="modal-tab" :class="{ 'modal-tab-active' : ! unit.add }" href="#" @click.prevent="actionSelect( 'unit' )"><?php esc_html_e( 'Select Existing Unit', 'wp-courseware' ); ?></a>
			</div>

			<div class="modal-body">
				<div class="add modal-tab-content" :class="{ 'modal-tab-content-active' : unit.add }">
					<div class="wpcw-form-field first">
						<label for="wpcw-add-unit-title"><?php esc_html_e( 'Unit Title', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<input ref="addUnitTitle"
						       id="wpcw-add-unit-title"
						       type="text"
						       v-model="unit.title"
						       placeholder="<?php esc_html_e( 'Unit Title', 'wp-courseware' ); ?>"/>
					</div>
					<div class="wpcw-form-field">
						<label for="wpcw-unit-description"><?php esc_html_e( 'Unit Content', 'wp-courseware' ); ?></label>
						<textarea ref="addUnitContent"
						          id="wpcw-unit-description"
						          v-model="unit.desc"
						          placeholder="<?php esc_html_e( 'Unit Content', 'wp-courseware' ); ?>"></textarea>
					</div>
					<div class="wpcw-form-field-float-wrapper">
						<div class="wpcw-form-field-float-left">
							<div class="wpcw-form-field left">
								<div class="wpcw-unit-drip">
									<div class="wpcw-form-field-title"><?php esc_html_e( 'Unit Drip', 'wp-courseware' ); ?></div>
									<div class="wpcw-unit-drip-section">
										<label for="wpcw-add-unit-drip-type"><?php esc_html_e( 'When should this unit become available?', 'wp-courseware' ); ?></label>
										<select id="wpcw-add-unit-drip-type" v-model="unit.drip.type">
											<option value="" selected="selected"><?php echo '--- ' . esc_html__( 'No Delay', 'wp-courseware' ) . ' ---'; ?></option>
											<option value="drip_specific"><?php esc_html_e( 'On a specific date', 'wp-courseware' ); ?></option>
											<option value="drip_interval"><?php esc_html_e( 'A specific interval after the course start date', 'wp-courseware' ); ?></option>
										</select>
									</div>
									<div v-show="unit.drip.type === 'drip_specific'" class="wpcw-unit-drip-specific wpcw_datepicker_wrapper">
										<label for="wpcw-add-unit-drip-specific"><?php esc_html_e( 'Select the date on which this unit should become available...', 'wp-courseware' ); ?></label>
										<input id="wpcw-add-unit-drip-specific" type="text" class="wpcw_datepicker_vis_add" v-model="unit.drip.date">
									</div>
									<div v-show="unit.drip.type === 'drip_interval'" class="wpcw-unit-drip-interval">
										<label for="wpcw-add-unit-drip-interval"><?php esc_html_e( 'How long after the user is enrolled should this unit become available?', 'wp-courseware' ); ?></label>
										<input id="wpcw-add-unit-drip-interval" type="number" class="wpcw-number" v-model="unit.drip.interval">
										<select id="wpcw-add-unit-drip-interval-type" v-model="unit.drip.interval_type">
											<option value="interval_hours"><?php esc_html_e( 'Hour(s)', 'wp-courseware' ); ?></option>
											<option value="interval_days"><?php esc_html_e( 'Days(s)', 'wp-courseware' ); ?></option>
											<option value="interval_weeks"><?php esc_html_e( 'Weeks(s)', 'wp-courseware' ); ?></option>
											<option value="interval_months"><?php esc_html_e( 'Months(s)', 'wp-courseware' ); ?></option>
											<option value="interval_years"><?php esc_html_e( 'Years(s)', 'wp-courseware' ); ?></option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="wpcw-form-field-float-right">
							<div class="wpcw-form-field right">
								<div class="wpcw-unit-teaser">
									<div class="wpcw-form-field-title"><?php esc_html_e( 'Unit Teaser / Preview', 'wp-courseware' ); ?></div>
									<label for="wpcw-add-unit-teaser-checkbox">
										<input id="wpcw-add-unit-teaser-checkbox" class="wpcw-unit-teaser-checkbox" type="checkbox" v-model="unit.teaser" true-value="1" false-value="0">
										<span class="wpcw-unit-teaser-checkbox-label"><?php esc_html_e( 'Teaser / Preview Unit', 'wp-courseware' ); ?></span>
									</label>
									<p><?php _e( 'Check the box above to allow this Unit to be accessed as a <strong>Teaser</strong> or <strong>Free</strong> Unit.', 'wp-courseware' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="select modal-tab-content" :class="{ 'modal-tab-content-active' : ! unit.add }">
					<div class="wpcw-form-field first">
						<input id="wpcw-units-search"
						       class="wpcw-search-input"
						       type="text"
						       placeholder="<?php esc_html_e( 'Type and hit enter to search units', 'wp-courseware' ); ?>"
						       v-model="unitsSearch" @keyup.enter.delete.esc="searchUnits"/>
					</div>
					<div v-if="unit.loading" class="wpcw-form-items-loading"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i></div>
					<div v-if="! unit.loading">
						<div v-if="hasUnits" class="wpcw-form-field last">
							<div class="wpcw-form-items-select-actions">
								<a class="select" href="#" @click.prevent="selectAllUnits"><?php esc_html_e( 'Select All', 'wp-courseware' ); ?></a>
								<span class="sep">|</span>
								<a class="unselect" href="#" @click.prevent="unselectAllUnits"><?php esc_html_e( 'Un-Select All', 'wp-courseware' ); ?></a>
								<span class="sep">|</span>
								<span class="count"><strong>{{ checkedUnitsCount }}</strong> of <strong>{{ units.length }}</strong> Units Selected.</span>
							</div>
							<ul class="wpcw-form-items">
								<li v-for="unitItem in units">
									<div class="checkbox">
										<label :for="'unit-' + unitItem.id">
											<input :id="'unit-' + unitItem.id" type="checkbox" v-model="checkedUnits" :value="unitItem.id"/>
											<span class="checkbox-label">{{ unitItem.title }} <strong>(#{{ unitItem.id }})</strong></span>
										</label>
									</div>
								</li>
							</ul>
						</div>
						<div v-else class="wpcw-form-field">
							<div class="wpcw-no-items-found"><?php esc_html_e( 'No unassigned units are available to select.', 'wp-courseware' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button v-if="unit.add"
				        class="modal-action button-primary"
				        :class="{ 'disabled' : ! unit.title || unit.updating }"
				        :disabled="! unit.title || unit.updating"
				        @click.prevent="insertUnit( unit.module_id )">
					<i v-if="unit.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					<i v-if="! unit.updating" class="wpcw-fas wpcw-fa-check-circle left"></i>
					{{ unit.updating ? '<?php esc_html_e( 'Adding Unit...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Add Unit', 'wp-courseware' ); ?>' }}
				</button>
				<button v-if="! unit.add"
				        class="modal-action button-primary"
				        :class="{ 'disabled' : ! hasCheckedUnits || unit.updating }"
				        :disabled="! hasCheckedUnits || unit.updating"
				        @click.prevent="insertUnits( unit.module_id )">
					<i v-if="unit.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					<i v-if="! unit.updating" class="wpcw-fas wpcw-fa-plus-circle"></i>
					{{ unit.updating ? '<?php esc_html_e( 'Inserting Units...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Insert Units', 'wp-courseware' ); ?>' }} ({{ checkedUnitsCount }})
				</button>
			</div>
		</div>

		<div id="wpcw-course-builder-quiz-modal" class="wpcw-course-builder-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<a class="modal-tab" href="#" :class="{ 'modal-tab-active' : quiz.add }" @click.prevent="actionAdd( 'quiz' )"><?php esc_html_e( 'Add New Quiz', 'wp-courseware' ); ?></a>
				<a class="modal-tab" :class="{ 'modal-tab-active' : ! quiz.add }" href="#" @click.prevent="actionSelect( 'quiz' )"><?php esc_html_e( 'Select Existing Quiz', 'wp-courseware' ); ?></a>
			</div>

			<div class="modal-body">
				<div class="add modal-tab-content" :class="{ 'modal-tab-content-active' : quiz.add }">
					<div class="wpcw-form-field first">
						<label for="wpcw-add-quiz-title"><?php esc_html_e( 'Quiz Title', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<input ref="addQuizTitle"
						       id="wpcw-add-quiz-title"
						       type="text"
						       v-model="quiz.title"
						       placeholder="<?php esc_html_e( 'Quiz Title', 'wp-courseware' ); ?>"/>
					</div>
					<div class="wpcw-form-field">
						<label for="wpcw-quiz-description"><?php esc_html_e( 'Quiz Description', 'wp-courseware' ); ?> <span class="req">*</span></label>
						<textarea ref="addQuizDesc"
						          id="wpcw-quiz-description"
						          v-model="quiz.desc"
						          placeholder="<?php esc_html_e( 'Quiz Description', 'wp-courseware' ); ?>"></textarea>
					</div>
				</div>
				<div class="select modal-tab-content" :class="{ 'modal-tab-content-active' : ! quiz.add }">
					<div class="wpcw-form-field first">
						<input id="wpcw-quizzes-search"
						       class="wpcw-search-input"
						       type="text"
						       placeholder="<?php esc_html_e( 'Type and hit enter to search quizzes', 'wp-courseware' ); ?>"
						       v-model="quizSearch" @keyup.enter.delete.esc="searchQuizzes"/>
					</div>
					<div v-if="quiz.loading" class="wpcw-form-items-loading"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i></div>
					<div v-if="! quiz.loading">
						<div v-if="hasQuizzes" class="wpcw-form-field last">
							<ul class="wpcw-form-items">
								<li v-for="quizItem in quizzes">
									<div class="checkbox">
										<label :for="'quiz-' + quizItem.id">
											<input :id="'quiz-' + quizItem.id" type="radio" v-model="selectedQuiz" :value="quizItem.id"/>
											<span class="radio-label">{{ quizItem.title }} <strong>(#{{ quizItem.id }})</strong></span>
										</label>
									</div>
								</li>
							</ul>
						</div>
						<div v-else class="wpcw-form-field">
							<div class="wpcw-no-items-found"><?php esc_html_e( 'No unassigned quizzes are available to select.', 'wp-courseware' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button v-if="quiz.add"
				        class="modal-action button-primary"
				        :class="{ 'disabled' : ! quiz.title || ! quiz.desc || quiz.adding }"
				        :disabled="! quiz.title || ! quiz.desc || quiz.adding"
				        @click.prevent="insertQuiz( quiz.unit_id, quiz.module_id )">
					<i v-if="! quiz.updating" class="wpcw-fas wpcw-fa-plus-circle"></i>
					<i v-if="quiz.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					{{ quiz.updating ? '<?php esc_html_e( 'Adding Quiz...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Add Quiz', 'wp-courseware' ); ?>' }}
				</button>
				<button v-if="! quiz.add"
				        class="modal-action button-primary"
				        :class="{ 'disabled' : ! selectedQuiz || quiz.adding }"
				        :disabled="! selectedQuiz || quiz.adding"
				        @click.prevent="insertQuizzes( quiz.unit_id, quiz.module_id )">
					<i v-if="! quiz.updating" class="wpcw-fas wpcw-fa-plus-circle"></i>
					<i v-if="quiz.updating" class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin left"></i>
					{{ quiz.updating ? '<?php esc_html_e( 'Adding Quiz...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Add Quiz', 'wp-courseware' ); ?>' }}
				</button>
			</div>
		</div>

		<div v-show="loaded && ! builderError">
			<div class="wpcw-course-builder-actions">
                <span class="wpcw-course-builder-status">
                    <span v-if="! loading" class="wpcw-course-builder-updated"><i class="wpcw-fas wpcw-fa-check-circle"></i></span>
                    <span v-if="loading" class="wpcw-course-builder-loading"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i> <?php esc_html_e( 'Saving...', 'wp-courseware' ); ?></span>
                    <span class="wpcw-course-builder-count">{{ builderModulesCount }} <?php esc_html_e( 'Items', 'wp-courseware' ); ?></span>
                </span>
				<a id="wpcw-course-builder-add-module" class="button-primary wpcw-course-builder-add-module" href="#" @click.prevent="addModule">
					<i class="wpcw-fas wpcw-fa-plus"></i> <?php esc_html_e( 'Add Module', 'wp-courseware' ); ?>
				</a>
				<button type="button"
				        aria-expanded="true"
				        class="handlediv wpcw-course-builder-collapse-expand"
				        :class="{ 'wpcw-builder-collapsed' : collapsed }"
				        @click.prevent="toggleCollapseExpand">
					<span class="screen-reader-text"><?php esc_html__( 'Toggle Accordion: Course Complete', 'wp-courseware' ); ?></span>
					<span aria-hidden="true" class="toggle-indicator"></span>
				</button>
				<a id="wpcw-course-builder-refresh" class="wpcw-builder-refresh" href="#" @click.prevent="refreshBuilder"><i class="wpcw-fas wpcw-fa-sync"></i></a>
			</div>

			<div id="wpcw-course-builder-sortables" class="wpcw-course-builder-sortables">
				<div v-if="! hasBuilderModules" class="builder-section-no-items">
					<?php esc_html_e( 'There are no modules in this course.', 'wp-courseware' ); ?>
					<a class="add" href="#" @click.prevent="addModule"><i class="wpcw-fas wpcw-fa-plus"></i> <?php esc_html_e( 'Add Module', 'wp-courseware' ); ?></a>
				</div>

				<div v-for="(module, index) in builderModules"
				     :key="module.id"
				     :id="'module-' + module.id"
				     :data-id="module.id"
				     class="builder-module builder-row collapsed">
					<div class="builder-section has-collapse" @click.self="toggleModule( module.id )">
						<span class="builder-module-handle builder-section-handle"><i class="wpcw-fas wpcw-fa-bars"></i></span>

						<span class="builder-section-title">
                            <a :href="module.edit" target="_blank"><?php esc_html_e( 'Module', 'wp-courseware' ); ?> {{ module.number }} - {{ module.title }}</a>
                            <span class="id-label">(<?php esc_html_e( 'ID', 'wp-courseware' ); ?>: {{module.id}})</span>
                        </span>

						<span class="builder-section-count">{{ moduleUnitsCount( module.id ) }} <?php esc_html_e( 'Units', 'wp-courseware' ); ?></span>

						<span class="builder-section-actions">
                            <a class="add" href="#" @click.prevent="addUnit( module.id )"><i class="wpcw-fas wpcw-fa-plus"></i> <?php esc_html_e( 'Add Unit', 'wp-courseware' ); ?></a>
                            <a class="edit" href="#" @click.prevent="editModule( module.id )"><i class="wpcw-fas wpcw-fa-edit"></i></a>
                            <a class="edit" target="_blank" :href="module.edit"><i class="wpcw-fas wpcw-fa-link"></i></a>
                            <a class="delete" href="#" @click.prevent="deleteModule( module.id )"><i class="wpcw-fas wpcw-fa-trash-alt"></i></a>
                        </span>

						<button type="button" aria-label="Toggle Module" aria-expanded="true" class="handlediv builder-section-collapse" @click.prevent="toggleModule( module.id )">
							<span class="screen-reader-text"><?php esc_html__( 'Toggle Accordion: Course Complete', 'wp-courseware' ); ?></span>
							<span aria-hidden="true" class="toggle-indicator"></span>
						</button>
					</div>

					<div class="builder-section-content builder-draggable-units" style="display: none;">
						<div v-if="! hasBuilderUnits( module.id )" class="builder-section-no-items">
							<?php esc_html_e( 'There are no units assigned to this module.', 'wp-courseware' ); ?>
							<a class="add" href="#" @click.prevent="addUnit( module.id )"><i class="wpcw-fas wpcw-fa-plus"></i> <?php esc_html_e( 'Add Unit', 'wp-courseware' ); ?></a>
						</div>

						<div v-for="(unit, index) in module.units"
						     :key="unit.id"
						     :id="'unit-' + unit.id"
						     :data-id="unit.id"
						     :data-module-id="module.id"
						     class="builder-unit builder-row collapsed">
							<div class="builder-section has-collapse" @click.self="toggleUnit( unit.id )">
								<span class="builder-unit-handle builder-section-handle"><i class="wpcw-fas wpcw-fa-bars"></i></span>

								<span class="builder-section-title">
                                    <a :href="unit.edit" target="_blank">{{ unit.title }}</a>
                                    <span class="id-label">(<?php esc_html_e( 'ID', 'wp-courseware' ); ?>: {{unit.id}})</span>
                                </span>

								<a v-show="unit.teaser == 1" target="_blank" :href="unit.view" class="builder-unit-teaser-icon">
									<abbr rel="wpcw-tooltip" class="wpcw-tooltip" title="<?php esc_html_e( 'Teaser / Preview Unit', 'wp-courseware' ); ?>"><i class="wpcw-fas wpcw-fa-eye"></i></abbr>
								</a>

								<span class="builder-section-count">{{ unitQuizzesCount( module.id, unit.id ) }} <?php esc_html_e( 'Quizzes', 'wp-courseware' ); ?></span>

								<span class="builder-section-actions">
                                    <a v-if="! hasBuilderQuizzes( module.id, unit.id )" class="add" href="#" @click.prevent="addQuiz( unit.id, module.id )"><i
		                                    class="wpcw-fas wpcw-fa-plus"></i> <?php esc_html_e( 'Add Quiz', 'wp-courseware' ); ?></a>
                                    <a class="edit" href="#" @click.prevent="editUnit( unit.id, module.id )"><i class="wpcw-fas wpcw-fa-edit"></i></a>
                                    <a class="edit" target="_blank" :href="unit.edit"><i class="wpcw-fas wpcw-fa-link"></i></a>
                                    <a class="delete" href="#" @click.prevent="deleteUnit( unit.id, module.id )"><i class="wpcw-fas wpcw-fa-trash-alt"></i></a>
                                </span>

								<button type="button" aria-label="Toggle Unit" aria-expanded="true" class="handlediv builder-section-collapse" @click.prevent="toggleUnit( unit.id )">
									<span class="screen-reader-text"><?php esc_html__( 'Toggle Accordion: Course Complete', 'wp-courseware' ); ?></span>
									<span aria-hidden="true" class="toggle-indicator"></span>
								</button>
							</div>

							<div class="builder-section-content builder-draggable-quizzes" style="display: none;">
								<div v-if="! hasBuilderQuizzes( module.id, unit.id )" class="builder-section-no-items">
									<?php esc_html_e( 'There are no quizzes assigned to this unit.', 'wp-courseware' ); ?>
									<a class="add" href="#" @click.prevent="addQuiz( unit.id, module.id )">
										<i class="wpcw-fas wpcw-fa-plus"></i> <?php esc_html_e( 'Add Quiz', 'wp-courseware' ); ?>
									</a>
								</div>

								<div v-for="(quiz, index) in unit.quizzes"
								     :key="quiz.id"
								     :id="'quiz' + quiz.id"
								     :data-id="quiz.id"
								     :data-module-id="module.id"
								     :data-unit-id="unit.id"
								     class="builder-quiz builder-row">
									<div class="builder-section">
										<span class="builder-quiz-handle builder-section-handle"><i class="wpcw-fas wpcw-fa-bars"></i></span>

										<span class="builder-section-title">
                                            <a :href="quiz.edit" target="_blank">{{ quiz.title }}</a>
                                            <span class="id-label">(<?php esc_html_e( 'ID', 'wp-courseware' ); ?>: {{quiz.id}})</span>
                                        </span>

										<span class="builder-section-actions">
                                            <a class="edit" href="#" @click.prevent="editQuiz( quiz.id, unit.id, module.id )"><i class="wpcw-fas wpcw-fa-edit"></i></a>
                                            <a class="edit" target="_blank" :href="quiz.edit"><i class="wpcw-fas wpcw-fa-link"></i></a>
                                            <a class="delete" href="#" @click.prevent="deleteQuiz( quiz.id, unit.id, module.id )"><i class="wpcw-fas wpcw-fa-trash-alt"></i></a>
                                        </span>
									</div>
								</div>
							</div>
						</div>

						<div v-if="hasBuilderUnits( module.id )" class="builder-section-bottom-actions">
							<button class="button button-primary" @click.prevent="addUnit( module.id )"><i class="wpcw-fas wpcw-fa-plus left"></i> <?php esc_html_e( 'Add Unit', 'wp-courseware' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>
