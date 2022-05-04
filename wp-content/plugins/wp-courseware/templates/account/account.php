<?php
/**
 * Student Account.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Print Notices.
wpcw_print_notices();

/**
 * Action: Student Account Navigation.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_student_account_navigation' ); ?>

<div class="wpcw-student-account-content">
	<?php
	/**
	 * Action: Student Account Content.
	 *
	 * @since 4.3.0
	 */
	do_action( 'wpcw_student_account_content' );
	?>
</div>
