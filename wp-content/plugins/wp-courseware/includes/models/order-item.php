<?php
/**
 * WP Courseware Order Item Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.3.0
 */

namespace WPCW\Models;

use WPCW\Database\DB_Order_Items;
use WPCW\Database\DB_Order_Item_Meta;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Item.
 *
 * @since 4.3.0
 *
 * @property int    $order_item_id
 * @property int    $order_id
 * @property Order  $order
 * @property int    $course_id
 * @property Course $course
 * @property string $order_item_title
 * @property int    $order_item_index
 * @property string $type
 * @property int    $qty
 * @property string $amount
 * @property string $subtotal
 * @property string $discount
 * @property string $tax
 * @property string $total
 * @property bool   $use_installments
 * @property bool   $is_recurring
 */
class Order_Item extends Model {

	/**
	 * @var DB_Order_Items The order items database.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var DB_Order_Item_Meta The order items meta database.
	 * @since 4.3.0
	 */
	protected $meta_db;

	/**
	 * @var int Order Item Id.
	 * @since 4.3.0
	 */
	public $order_item_id;

	/**
	 * @var int Order Id.
	 * @since 4.3.0
	 */
	public $order_id;

	/**
	 * @var Order The order object.
	 * @since 4.3.0
	 */
	public $order;

	/**
	 * @var int Course Id.
	 * @since 4.3.0
	 */
	public $course_id;

	/**
	 * @var int Course Post Id.
	 * @since 4.6.3
	 */
	public $course_post_id;

	/**
	 * @var Course The course object.
	 * @since 4.3.0
	 */
	public $course;

	/**
	 * @var string Order Item Title.
	 * @since 4.3.0
	 */
	public $order_item_title;

	/**
	 * @var int Order Item Index.
	 * @since 4.3.0
	 */
	public $order_item_index;

	/**
	 * @var string Order Item Type.
	 * @since 4.3.0
	 */
	public $type;

	/**
	 * @var int Order Item Quantity.
	 * @since 4.3.0
	 */
	public $qty;

	/**
	 * @var string Order Item Amount.
	 * @since 4.3.0
	 */
	public $amount;

	/**
	 * @var string Order Item Sub-Total.
	 * @since 4.3.0
	 */
	public $subtotal;

	/**
	 * @var string Order Item Discount.
	 * @since 4.3.0
	 */
	public $discount;

	/**
	 * @var string Order Item Tax.
	 * @since 4.3.0
	 */
	public $tax;

	/**
	 * @var string Order Item Total.
	 * @since 4.3.0
	 */
	public $total;

	/**
	 * @var bool Use Installments for Order Item?
	 * @since 4.6.0
	 */
	public $use_installments;

	/**
	 * @var bool Order Item is recurring?
	 * @since 4.3.0
	 */
	public $is_recurring;

	/**
	 * Order Item Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db      = new DB_Order_Items();
		$this->meta_db = new DB_Order_Item_Meta();
		parent::__construct( $data );
	}

	/**
	 * Get Order Item Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int|void
	 */
	public function get_id() {
		return absint( $this->get_order_item_id() );
	}

	/**
	 * Get Order Item Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int $order_item_id The order item id.
	 */
	public function get_order_item_id() {
		return $this->order_item_id;
	}

	/**
	 * Get Order Item Assigned Order Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int $order_id The order item order id.
	 */
	public function get_order_id() {
		return absint( $this->order_id );
	}

	/**
	 * Get Order.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|Order The related order object.
	 */
	public function get_order() {
		if ( ! $this->get_order_id() ) {
			return false;
		}

		if ( empty( $this->order ) ) {
			$this->order = new Order( $this->get_order_id() );
		}

		return $this->order;
	}

	/**
	 * Get Order Item Course Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int $course_id The order item course id.
	 */
	public function get_course_id() {
		return absint( $this->course_id );
	}

	/**
	 * Get Order Item Course Post Id.
	 *
	 * @since 4.6.3
	 *
	 * @return int $course_id The order item course id.
	 */
	public function get_course_post_id() {
		global $wpdb, $wpcwdb;

		if ( empty( $this->course_post_id ) ) {
			$this->course_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT course_post_id FROM {$wpcwdb->courses} WHERE course_id = %d", $this->get_course_id() ) );
		}

		return $this->course_post_id;
	}

	/**
	 * Get Course.
	 *
	 * @since 4.3.0
	 *
	 * @return bool|Course The course object.
	 */
	public function get_course() {
		if ( ! $this->get_course_id() ) {
			return false;
		}

		if ( empty( $this->course ) ) {
			$this->course = new Course( $this->get_course_id() );

			if ( $this->use_installments() ) {
				$this->course->set_prop( 'charge_installments', true );
			}
		}

		return $this->course;
	}

	/**
	 * Get Order Item Course Url.
	 *
	 * @since 4.3.0
	 *
	 * @return string The course url.
	 */
	public function get_course_url() {
		return esc_url_raw( add_query_arg( array( 'post' => $this->get_course_post_id(), 'action' => 'edit' ), admin_url( 'post.php' ) ) );
	}

	/**
	 * Get Order Item Title.
	 *
	 * @since 4.3.0
	 *
	 * @return string $order_item_title The order item title.
	 */
	public function get_order_item_title() {
		return esc_attr( $this->order_item_title );
	}

	/**
	 * Get Order Item Index.
	 *
	 * @since 4.3.0
	 *
	 * @return int $order_item_index The order item index.
	 */
	public function get_order_item_index() {
		return absint( $this->order_item_index );
	}

	/**
	 * Get Order Item Type.
	 *
	 * @since 4.3.0
	 *
	 * @return string|void The order item type.
	 */
	public function get_type() {
		return ! empty( $this->type ) ? esc_attr( $this->type ) : 'course';
	}

	/**
	 * Get Order Item Quantity.
	 *
	 * @since 4.3.0
	 *
	 * @return int $qty The order item quantity.
	 */
	public function get_qty() {
		return absint( $this->qty );
	}

	/**
	 * Get Order Item Amount.
	 *
	 * @since 4.3.0
	 *
	 * @return string $amount The order item amount.
	 */
	public function get_amount() {
		return esc_attr( $this->amount );
	}

	/**
	 * Get Order Item Amount Tax.
	 *
	 * @since 4.5.2
	 *
	 * @return string $amount_tax The order item amount tax.
	 */
	public function get_amount_tax() {
		return wpcw_calculate_tax_amount( $this->get_amount() );
	}

	/**
	 * Get Order Item Subtotal.
	 *
	 * @since 4.3.0
	 *
	 * @return string $subtotal The order item discount.
	 */
	public function get_subtotal() {
		return esc_attr( $this->subtotal );
	}

	/**
	 * Get Order Item Subtotal Tax.
	 *
	 * @since 4.5.2
	 *
	 * @return string $subtotal_tax The order item subtotal tax.
	 */
	public function get_subtotal_tax() {
		return esc_attr( $this->get_meta( '_subtotal_tax', true ) );
	}

	/**
	 * Get Order Item Discount.
	 *
	 * @since 4.3.0
	 *
	 * @return string $discount The order item discount.
	 */
	public function get_discount() {
		return esc_attr( $this->discount );
	}

	/**
	 * Get Order Item Discount Tax.
	 *
	 * @since 4.5.2
	 *
	 * @return string $discount_tax The order item discount tax.
	 */
	public function get_discount_tax() {
		return esc_attr( $this->get_meta( '_discount_tax', true ) );
	}

	/**
	 * Get Order Item Tax.
	 *
	 * @since 4.3.0
	 *
	 * @return string $tax The order item tax.
	 */
	public function get_tax() {
		return esc_attr( $this->tax );
	}

	/**
	 * Get Order Item Total.
	 *
	 * @since 4.3.0
	 *
	 * @return string $total The order item total.
	 */
	public function get_total() {
		return esc_attr( $this->total );
	}

	/**
	 * Get Order Item Is Recurring.
	 *
	 * @since 4.3.0
	 *
	 * @return bool $is_recurring If the order item is recurring.
	 */
	public function get_is_recurring() {
		return (bool) $this->is_recurring ? true : false;
	}

	/**
	 * Get Order Item Use Installments.
	 *
	 * @since 4.6.0
	 *
	 * @return bool $is_recurring If the order item is recurring.
	 */
	public function get_use_installments() {
		return (bool) $this->use_installments ? true : false;
	}

	/**
	 * Use Installments?
	 *
	 * @since 4.6.0
	 *
	 * @return bool $is_recurring If the order item is recurring.
	 */
	public function use_installments() {
		return $this->get_use_installments();
	}

	/**
	 * Create Order Item Object.
	 *
	 * @since 4.5.2
	 *
	 * @param array $data The data to insert upon creation.
	 *
	 * @return int|bool $object_id The object id or false otherwise.
	 */
	public function create( $data = array() ) {
		$order_item_id = parent::create( $data );

		$this->update_meta( '_discount_tax', ! empty( $data['discount_tax'] ) ? $data['discount_tax'] : 0.00 );
		$this->update_meta( '_subtotal_tax', ! empty( $data['subtotal_tax'] ) ? $data['subtotal_tax'] : 0.00 );

		return $order_item_id;
	}

	/**
	 * Get a Order Item Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key The meta key.
	 * @param bool   $single Whether to return a single value.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return $this->meta_db->get_meta( $this->get_order_item_id(), $meta_key, $single );
	}

	/**
	 * Add Order Item Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed  $meta_value Metadata value.
	 * @param bool   $unique Optional, default is false. Whether the same key should not be added.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return $this->meta_db->add_meta( $this->get_order_item_id(), $meta_key, $meta_value, $unique );
	}

	/**
	 * Update Order Item Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata key.
	 * @param mixed  $meta_value Metadata value.
	 * @param mixed  $prev_value Optional. Previous value to check before removing.
	 *
	 * @return bool False on failure, true if success.
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return $this->meta_db->update_meta( $this->get_order_item_id(), $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete Order Item Meta Field.
	 *
	 * @since 4.3.0
	 *
	 * @param string $meta_key Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value.
	 *
	 * @return bool False for failure. True for success.
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return $this->meta_db->delete_meta( $this->get_order_item_id(), $meta_key, $meta_value );
	}
}
