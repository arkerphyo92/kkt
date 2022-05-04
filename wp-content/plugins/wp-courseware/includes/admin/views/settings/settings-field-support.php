<script type="text/x-template" id="wpcw-settings-field-support">
	<div class="wpcw-support-field-support">
		<h2><?php esc_html_e( 'WP Courseware Support', 'wp-courseware' ); ?></h2>

		<p><?php printf( __( 'Below are support materials that can help answer questions about WP Courseware. Can\'t find what you\'re looking for? Submit a support request via the <a target="_blank" href="%s">member portal.</a>', 'wp-courseware' ), wpcw()->get_member_portal_support_url() ); ?></p>

		<p>
			<a href="#" class="button button-primary" :class="{ 'disabled' : loading }" @click.prevent="openBeacon" @disabled="loading">
				<i class="wpcw-fa" :class="{ 'wpcw-fa-circle-notch wpcw-fa-spin' : loading, 'wpcw-fa-search': ! loading }" aria-hidden="true"></i>
				<?php esc_html_e( 'Search Documentation', 'wp-courseware' ); ?>
			</a>
			<a href="<?php echo esc_url_raw( add_query_arg( array( 'page' => 'wpcw-tools', 'tab' => 'system' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary"><i
					class="wpcw-fas wpcw-fa-wrench"></i> <?php esc_html_e( 'View System Info', 'wp-courseware' ); ?></a>
		</p>

		<table class="wpcw-admin-table wpcw-admin-support-table widefat">
			<thead>
			<tr>
				<th colspan="2" data-export-label="<?php esc_html_e( 'WP Courseware Requirements', 'wp-courseware' ); ?>"><?php esc_html_e( 'WP Courseware Requirements', 'wp-courseware' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td class="label"><?php esc_html_e( 'WordPress Version', 'wp-courseware' ); ?></td>
				<td class="stat"><?php echo ( version_compare( get_bloginfo( 'version' ), '4.8.0', '>=' ) ) ? '<span class="met"><i class="wpcw-fas wpcw-fa-check-circle"></i></span>' : '<span class="not-met"><i class="wpcw-fas wpcw-fa-times-circle"></i></span>'; ?><?php echo get_bloginfo( 'version' ); ?></td>
			</tr>
			<tr>
				<td class="label"><?php esc_html_e( 'PHP Version', 'wp-courseware' ); ?></td>
				<td class="stat"><?php echo ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) ? '<span class="met"><i class="wpcw-fas wpcw-fa-check-circle"></i></span>' : '<span class="not-met"><i class="wpcw-fas wpcw-fa-times-circle"></i></span>'; ?><?php echo PHP_VERSION; ?></td>
			</tr>
			</tbody>
		</table>

		<h2 class="doc-main-heading"><?php esc_html_e( 'WP Courseware Shortcodes', 'wp-courseware' ); ?></h2>

		<p><?php esc_html_e( 'Below are the primary shortcodes WP Courseware uses with documentation and examples for each.', 'wp-courseware' ); ?></p>

		<div class="doc-nav">
			<a class="doc-scroll button" :class="activeButtonClass( 'course' )" href="#" @click.prevent="updateDocTab( 'course' )">
				<i class="wpcw-fa wpcw-fa-tasks left" aria-hidden="true"></i> <?php esc_html_e( 'Course', 'wp-courseware' ); ?>
			</a>
			<a class="doc-scroll button" :class="activeButtonClass( 'course-progress' )" href="#" @click.prevent="updateDocTab( 'course-progress' )">
				<i class="wpcw-fa wpcw-fa-check-square left" aria-hidden="true"></i> <?php esc_html_e( 'Course Progress', 'wp-courseware' ); ?>
			</a>
			<a class="doc-scroll button" :class="activeButtonClass( 'course-progress-bar' )" href="#" @click.prevent="updateDocTab( 'course-progress-bar' )">
				<i class="wpcw-fa wpcw-fa-percentage left" aria-hidden="true"></i> <?php esc_html_e( 'Course Progress Bar', 'wp-courseware' ); ?>
			</a>
			<a class="doc-scroll button" :class="activeButtonClass( 'course-enrollment' )" href="#" @click.prevent="updateDocTab( 'course-enrollment' )">
				<i class="wpcw-fa wpcw-fa-user-plus left" aria-hidden="true"></i> <?php esc_html_e( 'Course Enrollment', 'wp-courseware' ); ?>
			</a>
		</div>

		<div v-if="activeDocTab( 'course' )" class="doc-tab">
			<h2 class="doc-heading"><?php esc_html_e( 'Course Shortcode', 'wp-courseware' ); ?></h2>

			<p><?php _e( 'To show the course progress, you can use the <code>[wpcourse]</code> shortcode. Here\'s a summary of the shortcode parameters for <code>[wpcourse]</code>:', 'wp-courseware' ) ?></p>

			<h3 class="doc-sub-heading"><?php _e( 'Parameters:', 'wp-courseware' ); ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'course', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Required)</em> The ID of the course to show.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'show_title', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show the course title. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'show_desc', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show the course description. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'module', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> The number of the module to show from the specified course.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'module_desc', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show the module descriptions. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'user_quiz_grade', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show quiz grade for unit if unit conatins quiz. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>
			</dl>

			<h3 class="doc-sub-heading"><?php _e( 'Here are some examples of how <code>[wpcourse]</code> shortcode works:', 'wp-courseware' ) ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'Example 1: <code>[wpcourse course="2" module_desc="false" show_title="false" show_desc="false" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows course 2, just with module and unit titles. Do not show course title, course description or module descriptions.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 2: <code>[wpcourse course="2" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Exactly the same output as example 1.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 3: <code>[wpcourse course="1" module="4" module_desc="true" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows module 4 from course 1, with module titles and descriptions, and unit titles.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 4: <code>[wpcourse course="1" module_desc="true" show_title="true" show_desc="true" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows course 1, with course title, course description, module title, module description and unit titles.', 'wp-courseware' ) ?></dd>
			</dl>
		</div>

		<div v-if="activeDocTab( 'course-progress' )" class="doc-tab">

			<h2 class="doc-heading"><?php esc_html_e( 'Course Progress Shortcode', 'wp-courseware' ); ?></h2>

			<p><?php _e( 'The <code>[wpcourse_progress]</code> shortcode creates a summary table of all courses that a user is signed up to, along with their progress for each course, and their grade so far. To be able to see their progress, a user needs to be logged in. If the user is not logged in, then a message saying that the user needs to be logged in will be shown.', 'wp-courseware' ); ?>
			<p><?php _e( 'Here\'s a summary of the shortcode parameters for <code>[wpcourse_progress]</code>:', 'wp-courseware' ); ?></p>

			<h3 class="doc-sub-heading"><?php _e( 'Parameters:', 'wp-courseware' ); ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'courses', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> A comma-separated list of course IDs to show in the progress. If this is not specified, then all courses that the user is signed up to will be shown.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'course_desc', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, then show the course description. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'course_prerequisites', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, then show a table of the course prerequisites. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'user_progress', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, then show a progress bar of the user\'s current progress for each course they are signed up to. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>true</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'user_grade', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, then show the user\'s average grade so far for each course they are signed up to. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>true</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'user_quiz_grade', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show quiz grade for unit if unit conatins quiz. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'certificate', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show certificate button if certificates are enabled and course is complete. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>
			</dl>

			<h3 class="doc-sub-heading"><?php _e( 'Here are some examples of how <code>[wpcourse_progress]</code> shortcode works:', 'wp-courseware' ) ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'Example 1: <code>[wpcourse_progress user_progress="true" user_grade="true" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows all courses a user is signed up to, along with their progress and cumulative grade so far for each course.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 2: <code>[wpcourse_progress /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Does exactly the same as example 1, using the default parameter values.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 3: <code>[wpcourse_progress user_progress="false" user_grade="true" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows all courses a user is signed up to and their cumulative grade so far for each course, but the progress bar for each course is hidden.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 4: <code>[wpcourse_progress user_progress="false" user_grade="false" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows all courses a user is signed up to, but their progress and cumulative grades for each course are hidden.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 5: <code>[wpcourse_progress courses="1,2" user_progress="true" user_grade="true" user_quiz_grade="true" certificate="true" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Only shows courses with IDs of 1 and 2 if the user is signed to them. If the user is not signed up to any of those courses, then that course is not shown. Their progress and cumulative grade so far for each course is also shown. Quiz grades are shown if quiz is applicable. Certificate button will be displayed.', 'wp-courseware' ) ?></dd>
			</dl>
		</div>

		<div v-if="activeDocTab( 'course-progress-bar' )" class="doc-tab">

			<h2 class="doc-heading"><?php esc_html_e( 'Course Progress Bar Shortcode', 'wp-courseware' ); ?></h2>

			<p><?php _e( 'The <code>[wpcourse_progress_bar]</code> shortcode creates a percentage progress bar of the users progress so far on a given course. To be able to see the progress bar, a user needs to be logged in and have access to the course. If the user is not logged in, the progress bar will not show.', 'wp-courseware' ); ?>
			<p><?php _e( 'Here\'s a summary of the shortcode parameters for <code>[wpcourse_progress_bar]</code>:', 'wp-courseware' ); ?></p>

			<h3 class="doc-sub-heading"><?php _e( 'Parameters:', 'wp-courseware' ); ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'course', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Required)</em> A course ID to show the users current progress. If this is not specified, then nothing will show.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'show_title', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show the course title. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'show_desc', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If true, show the course description. (It can be <b>true</b> or <b>false</b>. By default, it\'s <b>false</b>).', 'wp-courseware' ) ?></dd>
			</dl>

			<h3 class="doc-sub-heading"><?php _e( 'Here are some examples of how <code>[wpcourse_progress_bar]</code> shortcode works:', 'wp-courseware' ) ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'Example 1: <code>[wpcourse_progress_bar course="1" show_title="false" show_desc="false" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows the progress bar of the current user for course id 1. Do not show the course title and course description.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 2: <code>[wpcourse_progress_bar course="1" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Exactly the same output as example 1.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'Example 3: <code>[wpcourse_progress_bar course="1" show_title="true" show_desc="true" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows the progress bar of the current user for course id 1. Show the course title and course description.', 'wp-courseware' ) ?></dd>
			</dl>
		</div>

		<div v-if="activeDocTab( 'course-enrollment' )" class="doc-tab">
			<h2 class="doc-heading"><?php esc_html_e( 'Course Enrollment Shortcode', 'wp-courseware' ); ?></h2>

			<p><?php _e( 'The <code>[wpcourse_enroll]</code> shortcode will allow you to create a course enrollment button for your courses.', 'wp-courseware' ); ?>
				<?php _e( 'The shortcode will work for both users who are logged and users who are registering. If a user is logged in and clicks the button
				they will automatically be enrolled into the specified course(s). If a user is not logged in and clicks the button they will be immediately taken to the
				registration page, and upon registration they will be enrolled into the specified course(s).', 'wp-courseware' ); ?></p>

			<p><?php _e( 'Here\'s a summary of the shortcode parameters for <code>[wpcourse_enroll]</code>:', 'wp-courseware' ); ?></p>

			<h3 class="doc-sub-heading"><?php _e( 'Parameters:', 'wp-courseware' ); ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'courses', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Required)</em> A comma-separated list of course IDs that the user will be enrolled into. If this is not specified, then the button will not appear.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'enroll_text', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> This is simply the text that will appear on the button. If the parameter is not specified, "Enroll Now" will be displayed as default.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'purchase_text', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> This is simply the text that will appear on the button when the course can be purchased. If the parameter is not specified, "Purchase" will be displayed as default.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'display_messages', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If set to "true" and the user is already enrolled, a message will be display instead of an enroll/purchase button.', 'wp-courseware' ) ?></dd>

				<dt><?php _e( 'redirect', 'wp-courseware' ) ?></dt>
				<dd><?php _e( '<em>(Optional)</em> If set to "true" the user will be immediately redirected to the first available unit in the course.', 'wp-courseware' ) ?></dd>
			</dl>

			<h3 class="doc-sub-heading"><?php _e( 'Here is an example of how <code>[wpcourse_enroll]</code> shortcode works:', 'wp-courseware' ) ?></h3>

			<dl class="doc-params">
				<dt><?php _e( 'Example 1: <code>[wpcourse_enroll courses="1,2,5,10" enroll_text="Enroll Today!" /]</code>', 'wp-courseware' ) ?></dt>
				<dd><?php _e( 'Shows a button that will allow a user to enroll into the specified courses. If a logged on user is already enrolled into a course that was specified, they will only be enrolled
						into the courses that apply. If the user is enrolled into all the courses a message will display that they are already enrolled. The actual button will display "Enroll Today!".', 'wp-courseware' ) ?></dd>
			</dl>
		</div>
	</div>
</script>
