<script type="text/x-template" id="wpcw-student-new">
    <div class="metabox-holder wpcw-student-new">
        <wpcw-notices></wpcw-notices>

        <p><?php esc_html_e( 'Students are WordPress users enrolled in one or more course. Use the form below to add a new or existing user and enroll them in one or more course.', 'wp-courseware' ); ?></p>

        <form class="wpcw-form wpcw-student-new-form" @submit.prevent="addStudent">
            <table class="wpcw-form-table">
                <tbody>
                <tr valign="top" class="wpcw-form-row">
                    <td scope="row" valign="top" class="input-cell" colspan="2">
                        <div class="input-cell-content">
                            <wpcw-form-field>
                                <span class="radio">
                                    <label for="addNewUser">
                                        <input type="radio" id="addNewUser" value="new" v-model="addMethod">
                                        <span class="radio-label"><?php esc_html_e( 'Add New User', 'wp-courseware' ); ?></span>
                                    </label>
                                </span>
                                    <span class="radio">
                                    <label for="addExistingUser">
                                        <input type="radio" id="addExistingUser" value="existing" v-model="addMethod">
                                        <span class="radio-label"><?php esc_html_e( 'Add Existing User', 'wp-courseware' ); ?></span>
                                    </label>
                                </span>
                            </wpcw-form-field>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>

            <table class="wpcw-form-table" v-if="addMethod === 'new'">
                <tbody>
                <tr valign="top" class="wpcw-form-row">
                    <td scope="row" valign="top" class="input-cell" colspan="2">
                        <div class="input-cell-content">
                            <h2 class="wpcw-form-section-title"><?php esc_html_e( 'Add New User', 'wp-courseware' ); ?></h2>
                        </div>
                    </td>
                </tr>
                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'Username <span class="req">*</span>', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <input type="text" v-model="userName" placeholder="<?php esc_html_e( 'Username', 'wp-courseware' ); ?>"/>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>

                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'Email <span class="req">*</span>', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <input type="text" v-model="email" placeholder="<?php esc_html_e( 'Email', 'wp-courseware' ); ?>"/>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>

                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'First Name <span class="req">*</span>', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <input type="text" v-model="firstName" placeholder="<?php esc_html_e( 'First Name', 'wp-courseware' ); ?>"/>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>

                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'Last Name <span class="req">*</span>', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <input type="text" v-model="lastName" placeholder="<?php esc_html_e( 'Last Name', 'wp-courseware' ); ?>"/>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>

                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'Password <span class="req">*</span>', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <div class="wpcw-user-password">
                                <input :type="passwordType"
                                       id="password"
                                       class="password-field"
                                       autocomplete="off"
                                       v-model="password"
                                       data-pw="<?php echo wp_generate_password( 24 ); ?>"/>

                                <button type="button" class="button" @click.prevent="togglePassword">
                                    <i class="wpcw-fa left" :class="{ 'wpcw-fa-eye-slash' : showPassword, 'wpcw-fa-eye' : ! showPassword }" aria-hidden="true"></i>
                                    <span class="text">{{ showPassword ? '<?php esc_html_e( 'Hide', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Show', 'wp-courseware' ); ?>' }}</span>
                                </button>

                                <div id="pass-strength-result"></div>
                            </div>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>

                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php esc_html_e( 'Send User Notification', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <span class="checkbox">
                                <label for="notification">
                                    <input type="checkbox" id="notification" v-model="notification">
                                    <span class="checkbox-label"><?php esc_html_e( 'Send the new user an email about their account.', 'wp-courseware' ); ?></span>
                                </label>
                            </span>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>
                </tbody>
            </table>

            <table class="wpcw-form-table" v-if="addMethod === 'existing'">
                <tbody>
                <tr valign="top" class="wpcw-form-row">
                    <td scope="row" valign="top" class="input-cell" colspan="2">
                        <div class="input-cell-content">
                            <h2 class="wpcw-form-section-title"><?php esc_html_e( 'Add Existing User', 'wp-courseware' ); ?></h2>
                        </div>
                    </td>
                </tr>
                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'Select Existing User <span class="req">*</span>', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <select id="wpcw-select-user" data-placeholder="<?php esc_html_e( 'Select Existing User', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>
                </tbody>
            </table>

            <table class="wpcw-form-table">
                <tbody>
                <tr valign="top" class="wpcw-form-row">
                    <td scope="row" valign="top" class="input-cell" colspan="2">
                        <div class="input-cell-content">
                            <h2 class="wpcw-form-section-title"><?php esc_html_e( 'Course Enrollment', 'wp-courseware' ); ?></h2>
                        </div>
                    </td>
                </tr>
                <wpcw-form-row>
                    <div slot="label">
                        <h3><?php _e( 'Select Courses', 'wp-courseware' ); ?></h3>
                    </div>
                    <div slot="input">
                        <wpcw-form-field>
                            <select id="wpcw-courses-select" data-placeholder="<?php esc_html_e( 'Select Courses', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
                        </wpcw-form-field>
                    </div>
                </wpcw-form-row>
                </tbody>
            </table>

            <table class="wpcw-form-table">
                <tbody>
                <tr class="wpcw-form-row" valign="top">
                    <th colspan="2">
                        <div class="wpcw-form-submit">
                            <button type="submit" class="button button-primary" :class="{ 'disabled' : isLoading }" @disabled="isLoading">
                                <i v-if="isLoading" class="wpcw-fa wpcw-fa-circle-notch wpcw-fa-spin left" aria-hidden="true"></i>
                                {{ isLoading ? '<?php esc_html_e( 'Adding Student', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Add Student', 'wp-courseware' ); ?>' }}
                            </button>
                        </div>
                    </th>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</script>
