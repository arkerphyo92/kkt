<?php
/**
 * Field Courses Select Component.
 *
 * @since 4.5.0
 */
?>
<script type="text/x-template" id="wpcw-field-course-instructor">
	<div class="wpcw-field-course-instructor-component">
		<div v-if="notices" class="wpcw-instructor-notices" v-html="notices"></div>
		<select :id="`wpcw-field-course-instructor-select-` + id"
		        class="wpcw-field-course-instructor-select"
		        :data-placeholder="placeholder"
		        data-allow_clear="true"
		        :name="name"
		        style="width:100%;">
			<option v-if="instructorSelected" :value="instructorSelected.id" selected="selected">{{ instructorSelected.name }}</option>
		</select>
		<div v-show="displaySaveButton" class="checkbox-wrapper">
			<input type="checkbox" id="updateQuestions" v-model="updateQuestions" true-value="yes" false-value="no">
			<label for="updateQuestions"><?php esc_html_e( 'Update all quiz questions and tags to new instructor?', 'wp-courseware' ); ?>&nbsp;&nbsp;<abbr rel="wpcw-tooltip" class="wpcw-tooltip" title="When checked the instructor / author for all quiz questions in this course will be updated. Sometimes this may not be desireable when questions are shared between quizzes."><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
			</label>
		</div>
		<button v-show="displaySaveButton" class="button-primary" :class="{ 'disabled' : loading }" @click.prevent="updateCourseInstructor" @disabled="loading">
			<i class="wpcw-fas right" :class="{ 'wpcw-fa-spinner wpcw-fa-spin' : loading, 'wpcw-fa-retweet' : ! loading }" aria-hidden="true"></i>
			{{ loading ? '<?php esc_html_e( 'Updating Course Instructor....', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Update Course Instructor', 'wp-courseware' ); ?>' }}
		</button>
	</div>
</script>
