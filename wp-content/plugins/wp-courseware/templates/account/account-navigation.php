<?php
/**
 * Student Account - Navigation.
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/account/account-navigation.php.
 *
 * @package WPCW
 * @subpackage Templates\Account
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Action: Before Student Account Navigation.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_before_student_account_navigation' );
?>
    <nav class="wpcw-student-account-navigation">
        <ul>
			<?php foreach ( wpcw_get_student_account_menu_items() as $endpoint => $label ) : ?>
                <li class="<?php echo wpcw_get_student_account_menu_item_classes( $endpoint ); ?>">
                    <a href="<?php echo esc_url( wpcw_get_student_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
                </li>
			<?php endforeach; ?>
        </ul>
    </nav>
<?php
/**
 * Action: After Student Account Navigation.
 *
 * @since 4.3.0
 */
do_action( 'wpcw_after_student_account_navigation' );