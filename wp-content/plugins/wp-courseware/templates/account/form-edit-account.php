<?php
/**
 * Account Form - Edit Student Account.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/form-edit-account.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 *
 * Variables available in this template:
 * ---------------------------------------------------
 * @var WP_User $student The student user object.
 * @var array $address The address fields of the student.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

do_action( 'wpcw_before_edit_student_account_form' ); ?>

<form class="wpcw-form wpcw-form-edit-account" action="" method="post">

	<?php do_action( 'wpcw_edit_student_account_form_start' ); ?>

    <div class="wpcw-student-account-account-details">
        <h2><?php esc_html_e( 'Account Details', 'wp-courseware' ); ?></h2>

        <p class="wpcw-form-row wpcw-form-row-first">
            <label for="account_first_name"><?php esc_html_e( 'First name', 'wp-courseware' ); ?> <span class="required">*</span></label>
            <input type="text" class="wpcw-input-text" name="account_first_name" id="account_first_name" value="<?php echo esc_attr( $student->first_name ); ?>"/>
        </p>

        <p class="wpcw-form-row wpcw-form-row-last">
            <label for="account_last_name"><?php esc_html_e( 'Last name', 'wp-courseware' ); ?> <span class="required">*</span></label>
            <input type="text" class="wpcw-input-text" name="account_last_name" id="account_last_name" value="<?php echo esc_attr( $student->last_name ); ?>"/>
        </p>

        <div class="wpcw-clear"></div>

        <p class="wpcw-form-row wpcw-form-row-wide">
            <label for="account_email"><?php esc_html_e( 'Email address', 'wp-courseware' ); ?> <span class="required">*</span></label>
            <input type="email" class="wpcw-input-text" name="account_email" id="account_email" value="<?php echo esc_attr( $student->user_email ); ?>"/>
        </p>
    </div>

    <div class="wpcw-student-account-password-fields">
        <h3><?php esc_html_e( 'Change Password', 'wp-courseware' ); ?></h3>

        <p class="wpcw-form-row wpcw-form-row-wide">
            <label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'wp-courseware' ); ?></label>
            <input type="password" class="wpcw-input-text" placeholder="<?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'wp-courseware' ); ?>" name="password_current"
                   id="password_current"/>
        </p>

        <p class="wpcw-form-row wpcw-form-row-wide">
            <label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'wp-courseware' ); ?></label>
            <input type="password" class="wpcw-input-text" placeholder="<?php esc_html_e( 'New password (leave blank to leave unchanged)', 'wp-courseware' ); ?>" name="password_1" id="password_1"/>
        </p>

        <p class="wpcw-form-row wpcw-form-row-wide">
            <label for="password_2"><?php esc_html_e( 'Confirm new password', 'wp-courseware' ); ?></label>
            <input type="password" class="wpcw-input-text" placeholder="<?php esc_html_e( 'Confirm new password', 'wp-courseware' ); ?>" name="password_2" id="password_2"/>
        </p>

        <div class="wpcw-clear"></div>
    </div>

	<?php do_action( 'wpcw_edit_student_account_form' ); ?>

	<?php if ( ! empty( $address ) ) { ?>
        <div class="wpcw-student-account-billing-fields">
            <h3><?php esc_html_e( 'Billing Details', 'wp-courseware' ); ?></h3>

			<?php
			foreach ( $address as $key => $field ) {
				if ( isset( $field['country_field'], $address[ $field['country_field'] ] ) ) {
					$field['country'] = wpcw_get_post_data_by_key( $field['country_field'], $address[ $field['country_field'] ]['value'] );
				}

				wpcw_form_field( $key, $field, wpcw_get_post_data_by_key( $key, $field['value'] ) );
			}
			?>

            <div class="wpcw-clear"></div>
        </div>
	<?php } ?>

    <div class="wpcw-student-account-submit-field">
        <p>
			<?php wp_nonce_field( 'wpcw-account-details', 'wpcw-account-details-nonce' ); ?>
            <input type="hidden" name="action" value="account_details"/>
            <button type="submit" class="button" name="account_details" value="<?php esc_attr_e( 'Save changes', 'wp-courseware' ); ?>">
				<?php esc_html_e( 'Save Changes', 'wp-courseware' ); ?>
            </button>
        </p>
    </div>

	<?php do_action( 'wpcw_edit_student_account_form_end' ); ?>
</form>

<?php do_action( 'wpcw_after_edit_student_account_form' ); ?>
