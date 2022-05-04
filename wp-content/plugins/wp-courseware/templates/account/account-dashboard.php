<?php
/**
 * Student Account - Dashboard
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account-dashboard.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit; ?>
<p>
    <?php
    /* translators: 1: user display name 2: logout url */
    printf(
        __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'wp-courseware' ),
        '<strong>' . esc_html( $student->display_name ) . '</strong>',
        esc_url( wpcw_logout_url( wpcw_get_page_permalink( 'account' ) ) )
    );
    ?>
</p>
<p>
    <?php
    if ( wpcw_is_ecommerce_enabled() ) {
	    printf(
		    __( 'From your student account dashboard you can view your <a href="%1$s">courses</a>, <a href="%2$s">orders</a> and <a href="%3$s">edit your password and account details</a>.', 'wp-courseware' ),
		    esc_url( wpcw_get_student_account_endpoint_url( 'courses' ) ),
		    esc_url( wpcw_get_student_account_endpoint_url( 'orders' ) ),
		    esc_url( wpcw_get_student_account_endpoint_url( 'edit-account' ) )
	    );
    } else {
	    printf(
		    __( 'From your student account dashboard you can view your <a href="%1$s">courses</a> and <a href="%2$s">edit your password and account details</a>.', 'wp-courseware' ),
		    esc_url( wpcw_get_student_account_endpoint_url( 'courses' ) ),
		    esc_url( wpcw_get_student_account_endpoint_url( 'edit-account' ) )
	    );
    }
    ?>
</p>
<?php
/**
 * Student Account Dashboard
 *
 * @since 4.3.0
 */
do_action( 'wpcw_student_account_dashboard' );
