<?php
/**
 * Email - Header
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/email-header.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>"/>
    <title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr' ?>">
    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
        <tr>
            <td align="center" valign="top">
                <div id="template_header_image">
					<?php
					if ( $img = wpcw()->settings->get_setting( 'email_header_image' ) ) {
						echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . get_bloginfo( 'name', 'display' ) . '" /></p>';
					}
					?>
                </div>
                <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_container">
                    <tr>
                        <td align="center" valign="top">
                            <!-- Header -->
                            <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_header">
                                <tr>
                                    <td id="header_wrapper">
                                        <h1><?php echo $email_heading; ?></h1>
                                    </td>
                                </tr>
                            </table>
                            <!-- End Header -->
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <!-- Body -->
                            <table border="0" cellpadding="0" cellspacing="0" width="600" id="template_body">
                                <tr>
                                    <td valign="top" id="body_content">
                                        <!-- Content -->
                                        <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                            <tr>
                                                <td valign="top">
                                                    <div id="body_content_inner">
