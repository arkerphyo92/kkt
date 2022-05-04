<?php
/**
 * Email - Footer
 *
 * This template can be overridden by copying it to yourtheme/wp-courseware/emails/email-footer.php.
 *
 * @package WPCW
 * @subpackage Templates\Emails
 * @version 4.3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top">
                                    <!-- Footer -->
                                    <table border="0" cellpadding="10" cellspacing="0" width="600" id="template_footer">
                                        <tr>
                                            <td valign="top">
                                                <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td colspan="2" valign="middle" id="credit">
                                                            <?php
                                                            /**
                                                             * Action: Email Footer Text Html
                                                             *
                                                             * @since 4.3.0
                                                             *
                                                             * @hooked \WPCW\Controllers\Emails->email_footer_text_html() Output the email footer
                                                             *
                                                             * @param \WPCW\Emails\Email The email object.
                                                             */
                                                            do_action( 'wpcw_email_footer_text_html', $email );?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Footer -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>
