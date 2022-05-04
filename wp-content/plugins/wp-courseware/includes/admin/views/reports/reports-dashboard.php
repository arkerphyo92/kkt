<?php
/**
 * Vue Component: Reports Dashboard.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-reports-dashboard">
    <div class="wpcw-reports-dashboard">
        <div v-if="! loaded" class="loading-reports"><i class="wpcw-fas wpcw-fa-spinner wpcw-fa-spin"></i></div>
        <div v-if="loaded" class="wpcw-reports-dashboard-loaded">
            <div class="report-today left-column">
                <table>
                    <thead>
                    <tr>
                        <td colspan="2" class="heading"><?php esc_html_e( 'Today', 'wp-courseware' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="reportExists( sales.today )">
                        <td class="sales"><?php esc_html_e( 'Sales', 'wp-courseware' ); ?></td>
                        <td class="sales-total" v-html="sales.today"></td>
                    </tr>
                    <tr v-if="reportExists( orders.today )">
                        <td class="orders"><?php echo esc_html_e( 'Orders', 'wp-courseware' ); ?></td>
                        <td class="orders-total" v-html="orders.today"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="report-current-month right-column">
                <table>
                    <thead>
                    <tr>
                        <td colspan="2" class="heading"><?php esc_html_e( 'Current Month', 'wp-courseware' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="reportExists( sales.this_month )">
                        <td class="sales"><?php esc_html_e( 'Sales', 'wp-courseware' ); ?></td>
                        <td class="sales-total" v-html="sales.this_month"></td>
                    </tr>
                    <tr v-if="reportExists( orders.this_month )">
                        <td class="orders"><?php echo esc_html_e( 'Orders', 'wp-courseware' ); ?></td>
                        <td class="orders-total" v-html="orders.this_month"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="clear"></div>

            <div class="report-last-month left-column">
                <table>
                    <thead>
                    <tr>
                        <td colspan="2" class="heading"><?php esc_html_e( 'Last Month', 'wp-courseware' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="reportExists( sales.last_month )">
                        <td class="sales"><?php esc_html_e( 'Sales', 'wp-courseware' ); ?></td>
                        <td class="sales-total" v-html="sales.last_month"></td>
                    </tr>
                    <tr v-if="reportExists( orders.last_month )">
                        <td class="orders"><?php echo esc_html_e( 'Orders', 'wp-courseware' ); ?></td>
                        <td class="orders-total" v-html="orders.last_month"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="report-total right-column">
                <table>
                    <thead>
                    <tr>
                        <td colspan="2" class="heading"><?php esc_html_e( 'Total', 'wp-courseware' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="reportExists( sales.total )">
                        <td class="sales"><?php esc_html_e( 'Total Sales', 'wp-courseware' ); ?></td>
                        <td class="sales-total" v-html="sales.total"></td>
                    </tr>
                    <tr v-if="reportExists( orders.total )">
                        <td class="orders"><?php echo esc_html_e( 'Total Orders', 'wp-courseware' ); ?></td>
                        <td class="orders-total" v-html="orders.total"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="clear"></div>

            <div class="report-subscriptions left-column">
                <table>
                    <thead>
                    <tr>
                        <td colspan="2" class="heading"><?php esc_html_e( 'Active Subscriptions', 'wp-courseware' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="reportExists( subscriptions.this_month )">
                        <td class="subs"><?php esc_html_e( 'This Month', 'wp-courseware' ); ?></td>
                        <td class="subs-total" v-html="subscriptions.this_month"></td>
                    </tr>
                    <tr v-if="reportExists( subscriptions.this_year )">
                        <td class="subs"><?php esc_html_e( 'This Year', 'wp-courseware' ); ?></td>
                        <td class="subs-total" v-html="subscriptions.this_year"></td>
                    </tr>
                    <tr v-if="reportExists( subscriptions.total )">
                        <td class="subs"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></td>
                        <td class="subs-total" v-html="subscriptions.total"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="report-students right-column">
                <table>
                    <thead>
                    <tr>
                        <td colspan="2" class="heading"><?php esc_html_e( 'Enrolled Students', 'wp-courseware' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="reportExists( students.this_month )">
                        <td class="students"><?php esc_html_e( 'This Month', 'wp-courseware' ); ?></td>
                        <td class="students-total" v-html="students.this_month"></td>
                    </tr>
                    <tr v-if="reportExists( students.this_year )">
                        <td class="students"><?php esc_html_e( 'This Year', 'wp-courseware' ); ?></td>
                        <td class="students-total" v-html="students.this_year"></td>
                    </tr>
                    <tr v-if="reportExists( students.total )">
                        <td class="students"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></td>
                        <td class="students-total" v-html="students.total"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="clear"></div>

            {{ custom ? custom : '' }}

            <a href="#" class="refresh-reports" @click.prevent="refreshReports"><i class="wpcw-fas wpcw-fa-sync-alt"></i> <?php esc_html_e( 'Refresh Reports', 'wp-courseware' ); ?></a>
        </div>
    </div>
</script>
