<?php
/**
 * Order Items Table Component
 *
 * @since 4.3.0
 */
$coupons_enabled    = wpcw_coupons_enabled();
$taxes_enabled      = wpcw_taxes_enabled();
$column_length      = ( $taxes_enabled ) ? '4' : '3';
$full_column_length = ( $taxes_enabled ) ? '6' : '5';
?>
<script type="text/x-template" id="wpcw-order-items-table">
	<div class="wpcw-order-items-table-wrapper">
		<table class="wpcw-order-items-table" cellpadding="0" cellspacing="0">
			<thead>
			<tr>
				<th class="wpcw-order-item-column-course"><?php esc_html_e( 'Course', 'wp-courseware' ); ?></th>
				<th class="wpcw-order-item-column-price"><?php esc_html_e( 'Cost', 'wp-courseware' ); ?></th>
				<?php if ( $taxes_enabled ) { ?>
					<th class="wpcw-order-item-column-tax"><?php esc_html_e( 'Tax', 'wp-courseware' ); ?></th>
				<?php } ?>
				<th class="wpcw-order-item-column-quantity"><?php esc_html_e( 'Quantity', 'wp-courseware' ); ?></th>
				<th class="wpcw-order-item-column-total"><?php esc_html_e( 'Total', 'wp-courseware' ); ?></th>
				<th class="wpcw-order-item-column-action"></th>
			</tr>
			</thead>
			<tbody>
			<tr v-if="hasItems" v-for="(item, index) in items" :key="item.id" class="wpcw-order-item-row">
				<td class="wpcw-order-item-column-course"><a target="_blank" :href="item.course_url">{{ item.title }}</a></td>
				<td class="wpcw-order-item-column-price" v-html="item.amount"></td>
				<?php if ( $taxes_enabled ) { ?>
					<td class="wpcw-order-item-column-tax" v-html="item.amount_tax"></td>
				<?php } ?>
				<td class="wpcw-order-item-column-quantity">
					<small class="times">×</small>
					{{ item.qty }}
				</td>
				<td class="wpcw-order-item-column-total" v-html="item.subtotal"></td>
				<td class="wpcw-order-item-column-action action-delete"><a v-if="editable" href="#" @click.prevent="deleteItem( item.id )"><i class="wpcw-fas wpcw-fa-times"></i></a></td>
			</tr>
			</tbody>
			<tfoot>
			<tr class="wpcw-order-items-row-sub-total">
				<td class="wpcw-order-items-column-sub-total-label" width="300" colspan="<?php echo $column_length; ?>">
					<strong><?php esc_html_e( 'Subtotal:', 'wp-courseware' ); ?></strong>
				</td>
				<td class="wpcw-order-items-column-sub-total" width="100" v-html="subTotal"></td>
				<td class="wpcw-order-items-column-action"></td>
			</tr>
			<tr class="wpcw-order-items-row-border">
				<td colspan="2"></td>
				<td colspan="3" style="border-bottom: 1px solid #f8f8f8;"></td>
			</tr>
			<?php if ( $coupons_enabled ) { ?>
				<tr v-if="coupons.length" class="wpcw-order-items-row-discounts">
					<td class="wpcw-order-items-column-discounts-label" width="300" colspan="<?php echo $column_length; ?>">
						<span class="coupons">
							<span class="coupons-label"><?php esc_html_e( 'Applied Coupon(s)', 'wp-courseware' ); ?></span>
							<span class="coupons-codes">
								<abbr v-for="( coupon, index ) in coupons" :key="index" class="wpcw-tooltip wpcw-tooltip-left" :title="coupon.coupon_amount" rel="wpcw-tooltip"><a class="coupon-code code" :href="coupon.edit_url" v-html="coupon.code" target="_blank"></a></abbr>
							</span>
						</span>
						<span class="label-text"><strong><?php esc_html_e( 'Discount:', 'wp-courseware' ); ?></strong></span>
					</td>
					<td class="wpcw-order-items-column-discounts-total" width="100" v-html="discounts"></td>
					<td class="wpcw-order-items-column-action"></td>
				</tr>
				<tr v-if="coupons.length" class="wpcw-order-items-row-border">
					<td colspan="2"></td>
					<td colspan="3" style="border-bottom: 1px solid #f8f8f8;"></td>
				</tr>
			<?php } ?>
			<?php if ( $taxes_enabled ) { ?>
				<tr class="wpcw-order-items-row-tax-total">
					<td class="wpcw-order-items-column-tax-total-label" width="300" colspan="<?php echo $column_length; ?>">
						<strong><?php esc_html_e( 'Tax Total:', 'wp-courseware' ); ?></strong>
					</td>
					<td class="wpcw-order-items-column-tax-total" width="100" v-html="taxTotal"></td>
					<td class="wpcw-order-items-column-action"></td>
				</tr>
				<tr class="wpcw-order-items-row-border">
					<td colspan="2"></td>
					<td colspan="3" style="border-bottom: 1px solid #f8f8f8;"></td>
				</tr>
			<?php } ?>
			<tr class="wpcw-order-items-row-total">
				<td class="wpcw-order-items-column-total-label" colspan="<?php echo $column_length; ?>">
					<strong><?php esc_html_e( 'Total:', 'wp-courseware' ); ?></strong>
				</td>
				<td class="wpcw-order-items-column-total" v-html="total"></td>
				<td class="wpcw-order-items-column-action"></td>
			</tr>
			<tr class="wpcw-order-items-row-actions">
				<td class="wpcw-order-items-column-actions" colspan="<?php echo $full_column_length; ?>">
					<button v-if="editable" class="button button-secondary" type="button" @click.prevent="openModal">
						<i class="wpcw-fas wpcw-fa-plus left"></i>
						<?php esc_html_e( 'Add Course(s)', 'wp-courseware' ); ?>
					</button>

					<a v-if="refundable" href="#" id="order-refund" class="button button-secondary order-refund" @click.prevent="refundOrder">
						<i class="wpcw-fas wpcw-fa-exchange-alt"></i> <?php esc_html_e( 'Refund', 'wp-courseware' ); ?>
					</a>

					<span v-if="! editable" class="message">
                        <abbr class="wpcw-tooltip wpcw-tooltip-left" title="<?php esc_html_e( 'To edit this order, change the status back to "pending"', 'wp-courseware' ); ?>" rel="wpcw-tooltip"><i
		                        class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
						<?php esc_html_e( 'This order is no longer editable.', 'wp-courseware' ); ?>
                    </span>
				</td>
			</tr>
			</tfoot>
		</table>

		<div id="wpcw-order-items-add-courses-modal" class="wpcw-order-items-add-courses-modal wpcw-modal wpcw-mfp-hide">
			<div class="modal-title">
				<h1><?php esc_html_e( 'Add Course(s)', 'wp-courseware' ); ?></h1>
			</div>

			<div class="modal-body">
				<wpcw-form-field classes="wpcw-order-items-add-courses">
					<select id="wpcw-courses-select" data-placeholder="<?php esc_html_e( 'Search for a course...', 'wp-courseware' ); ?>" data-allow_clear="true"></select>
				</wpcw-form-field>

				<wpcw-form-field classes="wpcw-order-items-add-courses-button">
					<button type="submit" class="button button-primary button-large" :class="{ 'disabled' : adding || ! hasNewItems }" @click.prevent="addCourses" @disabled="adding || ! hasNewItems">
						<i class="wpcw-fas left" :class="{ 'wpcw-fa-plus' : ! adding, 'wpcw-fa-circle-o-notch wpcw-fa-spin' : adding }" aria-hidden="true"></i>
						{{ adding ? '<?php esc_html_e( 'Adding Courses...', 'wp-courseware' ); ?>' : '<?php esc_html_e( 'Add Course(s)', 'wp-courseware' ); ?>' }}
					</button>
					<button type="submit" class="button button-secondary button-large" @click.prevent="closeModal">
						<i class="wpcw-fas wpcw-fa-times left"></i>
						<?php esc_html_e( 'Cancel', 'wp-courseware' ); ?>
					</button>
				</wpcw-form-field>
			</div>
		</div>
	</div>
</script>
