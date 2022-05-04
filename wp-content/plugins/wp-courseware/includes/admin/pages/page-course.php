<?php
/**
 * WP Courseware Course Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */

namespace WPCW\Admin\Pages;

use WPCW\Admin\Fields;
use WPCW\Models\Course;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Course.
 *
 * @since 4.3.0
 */
class Page_Course extends Page {

	/**
	 * @var int The post id.
	 * @since 4.4.0
	 */
	public $post_id;

	/**
	 * @var string The post type slug.
	 * @since 4.4.0
	 */
	public $post_type = 'wpcw_course';

	/**
	 * @var Course The course object.
	 * @since 4.4.0
	 */
	public $course;

	/**
	 * @var Fields The fields api.
	 * @since 4.4.0
	 */
	public $fields;

	/**
	 * Course Page Hooks.
	 *
	 * @since 4.4.0
	 */
	public function hooks() {
		// Hooks.
		parent::hooks();

		// Disable Menu.
		$this->disable = true;

		// Post.
		add_action( 'load-post-new.php', array( $this, 'load_new' ) );
		add_action( 'load-post.php', array( $this, 'load_edit' ) );

		// Action Buttons
		add_action( 'admin_head-post.php', array( $this, 'add_action_buttons' ) );
		add_action( 'admin_head-post-new.php', array( $this, 'add_new_action_buttons' ) );

		// Tabs.
		add_filter( 'redirect_post_location', array( $this, 'set_active_tab' ), 10, 2 );

		// Meta Boxes.
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_filter( 'wpcw_fields_field_args', array( $this, 'course_field_value' ), 10, 2 );

		// Submitbox Meta Boxes.
		add_action( 'post_submitbox_misc_actions', array( $this, 'shortcode_meta_box' ) );

		// Save.
		add_action( "save_post_{$this->post_type}", array( $this, 'save' ), 10, 3 );

		// Disable Tabs.
		add_filter( 'wpcw_course_tabs', array( $this, 'disable_tabs' ) );
	}

	/** Post Methods ---------------------------------------------- */

	/**
	 * Get Post Id.
	 *
	 * @since 4.4.0
	 *
	 * @return int|null The post id if set.
	 */
	protected function get_post_id() {
		$post_id = isset( $_REQUEST['post'] )
			? absint( $_REQUEST['post'] )
			: null;

		$post_id = ! empty( $post_id )
			? $post_id
			: ( isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : null );

		$post_id = ! empty( $post_id )
			? $post_id
			: ( isset( $_REQUEST['post_ID'] ) ? absint( $_REQUEST['post_ID'] ) : null );

		return $post_id;
	}

	/**
	 * Load Course New Screen.
	 *
	 * @since 4.4.0
	 */
	public function load_new() {
		if ( ! $this->is_page_request() ) {
			return;
		}

		// Initiate the Fields Api.
		$this->fields = new Fields();

		// Setup Tabs.
		$this->tabs();
	}

	/**
	 * Load Post Edit Screen.
	 *
	 * @since 4.4.0
	 */
	public function load_edit() {
		if ( ! $this->is_page_request() ) {
			return;
		}

		// Post Id.
		$post_id = $this->get_post_id();

		// Setup Course.
		$this->setup_course( $post_id );

		// Initiate the fields api.
		$this->fields = new Fields();

		// Setup Tabs.
		$this->tabs();
	}

	/**
	 * Setup Course.
	 *
	 * @since 4.4.0
	 *
	 * @param int $post_id The post id.
	 */
	protected function setup_course( $post_id = 0 ) {
		$this->course = new Course( $post_id, true );

		// Course Data.
		$course_data = $this->course->get_data( true );

		// Create New Course, if one doesn't exist.
		if ( 0 !== $post_id && empty( $course_data ) ) {
			$this->course->create( array( 'course_post_id' => $post_id ), false );
		}
	}

	/**
	 * Save Course.
	 *
	 * @since 4.4.0
	 *
	 * @param int      $post_id The post id.
	 * @param \WP_Post $post The WP_Post object.
	 * @param bool     $update Whether this is an existing post being updated or not.
	 */
	public function save( $post_id, $post, $update ) {
		global $pagenow;

		// Check.
		if ( empty( $post_id ) || empty( $post ) ) {
			return;
		}

		// Check for page request.
		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php', 'admin-ajax.php' ) ) ) {
			return;
		}

		// Setup Course.
		if ( empty( $this->course ) ) {
			$this->setup_course( $post_id );
		}

		// Auto Draft Status
		if ( 'auto-draft' !== get_post_status( $post ) ) {
			$this->course->maybe_enroll_author();
		}

		// Revisions.
		if ( is_int( wp_is_post_revision( $post ) ) ) {
			return;
		}

		// Ajax Save - Hearbeat & Quick Edit.
		if ( 'admin-ajax.php' === $pagenow && ( ( isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) || ! empty( $_POST['_inline_edit'] ) ) ) {
			$this->course->set_prop( 'course_title', $post->post_title );
			$this->course->set_prop( 'course_author', absint( $post->post_author ) );
			$this->course->save( false );
		}

		// Check the nonce.
		if ( empty( $_POST['wpcw_course_save_nonce'] ) || ! wp_verify_nonce( $_POST['wpcw_course_save_nonce'], 'wpcw_course_save' ) ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/**
		 * Action: Before Save.
		 *
		 * @since 4.4.0
		 *
		 * @param Course The course object.
		 * @param int $post_id The post id.
		 * @param \WP_Post $post The post object.
		 */
		do_action( 'wpcw_course_before_save', $this->course, $post_id, $post );

		// Course Title.
		if ( $post->post_title ) {
			$this->course->set_prop( 'course_title', $post->post_title );
		}

		// Course Author
		if ( $post->post_author ) {
			$this->course->set_prop( 'course_author', absint( $post->post_author ) );
		}

		// Fields and Data.
		$course_data   = $this->course->get_data( true );
		$course_fields = $this->get_all_fields();

		// Course Fields.
		if ( $course_fields ) {
			foreach ( $course_fields as $field_id => $field ) {
				if ( $this->fields->ignore_field( $field ) ) {
					continue;
				}

				$field_name  = $this->fields->get_field_name( $field );
				$field_value = $this->fields->get_field_value( $field );

				if ( property_exists( $this->course, $field_name ) ) {
					$this->course->set_prop( $field_name, $field_value );
				} else {
					if ( $update ) {
						$this->course->update_meta( $field_name, $field_value );
					} else {
						$this->course->add_meta( $field_name, $field_value, true );
					}
				}
			}
		}

		update_post_meta( $post_id, 'is_course_expired', 'false' );

		// Save Course.
		$this->course->save( false );

		// Course Content / Description.
		$description = $this->course->get_course_desc();
		remove_action( "save_post_{$this->post_type}", array( $this, 'save' ), 10, 3 );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_kses_post( $description ),
			)
		);
		add_action( "save_post_{$this->post_type}", array( $this, 'save' ), 10, 3 );

		/**
		 * Action: After Save.
		 *
		 * @since 4.4.0
		 *
		 * @param Course The course object.
		 * @param int $post_id The post id.
		 * @param \WP_Post $post The post object.
		 */
		do_action( 'wpcw_course_after_save', $this->course, $post_id, $post );
	}

	/** Page Methods ---------------------------------------------- */

	/**
	 * Course Meta Boxes.
	 *
	 * @since 4.4.0
	 */
	public function meta_boxes() {
		add_meta_box( 'wpcw-course-builder-metabox', esc_html__( 'Course Builder', 'wp-courseware' ), array( $this, 'metabox_course_builder_output' ), $this->post_type, 'normal', 'high' );
		add_meta_box( 'wpcw-course-settings-metabox', esc_html__( 'Course Settings', 'wp-courseware' ), array( $this, 'metabox_course_settings_output' ), $this->post_type, 'normal', 'high' );
	}

	/**
	 * Metabox Output: Course Builder
	 *
	 * @since 4.4.0
	 */
	public function metabox_course_builder_output() {
		do_action( 'wpcw_course_builder_metabox_before' );
		echo $this->get_view( 'course/course-builder' );
		?>
		<div id="wpcw-course-builder-wrapper">
			<wpcw-course-builder courseid="<?php echo $this->course->get_course_id(); ?>" legacybuilderurl="<?php echo $this->course->get_legacy_course_builder_url(); ?>"></wpcw-course-builder>
			<div id="wpcw-hidden-wp-editor" style="display: none;"><?php wp_editor( '', 'wpcw_modal_content' ); ?></div>
		</div>
		<?php
		do_action( 'wpcw_course_builder_metabox_after' );
	}

	/**
	 * Metabox Output: Course Settings.
	 *
	 * @since 4.4.0
	 */
	public function metabox_course_settings_output() {
		do_action( 'wpcw_course_settings_metabox_before' );
		$this->field_views();
		?>
		<div id="wpcw-course-settings">
			<?php $this->display_tabs(); ?>
		</div>
		<?php
		do_action( 'wpcw_course_settings_metabox_before' );
	}

	/**
	 * Shortcode Metabox.
	 *
	 * This will display the shortcode for this course.
	 *
	 * @since 4.4.0
	 */
	public function shortcode_meta_box() {
		if ( ! $this->is_page_request() ) {
			return;
		}

		$course_outline_shortcode     = htmlentities( '[wpcourse course="' . $this->course->get_course_id() . '"]' );
		$course_outline_shortcode_tip = __( '<code>Course Outline Shortcode</code> - You can use this shortcode to display the course outline on any page.', 'wp-courseware' );

		$course_progress_shortcode     = htmlentities( '[wpcourse_progress courses="' . $this->course->get_course_id() . '"]' );
		$course_progress_shortcode_tip = __( '<code>Course Progress Shortcode</code> - You can use this shortcode to display the course progress on any page.', 'wp-courseware' );

		$course_progress_bar_shortcode     = htmlentities( '[wpcourse_progress_bar course="' . $this->course->get_course_id() . '"]' );
		$course_progress_bar_shortcode_tip = __( '<code>Course Progress Bar Shortcode</code> - You can use this shortcode to display the course progress bar on any page.', 'wp-courseware' );

		$support_link = add_query_arg(
			array(
				'page' => 'wpcw-settings',
				'tab'  => 'support',
			),
			admin_url( 'admin.php' )
		);

		?>
		<div class="wpcw-course-shortcode-metabox-wrap">
			<div class="wpcw-course-shortcode-metabox-label"><i class="wpcw-fas wpcw-fa-code left"></i> <?php esc_html_e( 'Course Shortcodes', 'wp-courseware' ); ?></div>
			<div class="wpcw-courses-shortcode wpcw-courses-course-outline-shortcode">
				<abbr class="wpcw-tooltip" title="<?php echo $course_outline_shortcode_tip; ?>" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
				<input onClick="this.setSelectionRange(0, this.value.length)"
					   type="text"
					   name="wpcw-courses-course-outline-shortcode-input"
					   id="wpcw-courses-course-outline-shortcode-input"
					   class="wpcw-courses-course-outline-shortcode-input" readonly value="<?php echo $course_outline_shortcode; ?>"/>
				<a href="<?php echo $support_link; ?>" target="_blank"><i class="wpcw-fas wpcw-fa-external-link-alt"></i></a>
			</div>
			<div class="wpcw-courses-shortcode wpcw-courses-course-progress-shortcode">
				<abbr class="wpcw-tooltip" title="<?php echo $course_progress_shortcode_tip; ?>" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
				<input onClick="this.setSelectionRange(0, this.value.length)"
					   type="text"
					   name="wpcw-courses-course-progress-shortcode-input"
					   id="wpcw-courses-course-progress-shortcode-input"
					   class="wpcw-courses-course-progress-shortcode-input" readonly value="<?php echo $course_progress_shortcode; ?>"/>
				<a href="<?php echo $support_link; ?>" target="_blank"><i class="wpcw-fas wpcw-fa-external-link-alt"></i></a>
			</div>
			<div class="wpcw-courses-shortcode wpcw-courses-course-progress-bar-shortcode">
				<abbr class="wpcw-tooltip" title="<?php echo $course_progress_bar_shortcode_tip; ?>" rel="wpcw-tooltip"><i class="wpcw-fas wpcw-fa-info-circle"></i></abbr>
				<input onClick="this.setSelectionRange(0, this.value.length)"
					   type="text"
					   name="wpcw-courses-course-progress-bar-shortcode-input"
					   id="wpcw-courses-course-progress-bar-shortcode-input"
					   class="wpcw-courses-course-progress-bar-shortcode-input" readonly value="<?php echo $course_progress_bar_shortcode; ?>"/>
				<a href="<?php echo $support_link; ?>" target="_blank"><i class="wpcw-fas wpcw-fa-external-link-alt"></i></a>
			</div>
		</div>
		<?php
	}

	/** Fields Methods -------------------------------------------- */

	/**
	 * Get General Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of description fields.
	 */
	public function get_general_fields() {
		/**
		 * Filter: Course General Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course general fields.
		 * @param Course $this The course object.
		 *
		 * @return array The course general fields.
		 */
		return apply_filters(
			'wpcw_course_general_fields',
			array(
				'course_opt_user_access'     => array(
					'id'      => 'course_opt_user_access',
					'name'    => 'course_opt_user_access',
					'type'    => 'radio',
					'label'   => esc_html__( 'Course Access', 'wp-courseware' ),
					'desc'    => esc_html__( 'This setting allows you to set how users can access this course. Users can either be given access automatically as soon as the user is created, or you can manually give them access. You can always manually remove access if you wish.', 'wp-courseware' ),
					'tip'     => esc_html__( 'Course Access', 'wp-courseware' ),
					'default' => 'default_show',
					'options' => array(
						'default_show' => __( '<strong>Automatic</strong> - All newly created users will be given access this course.', 'wp-courseware' ),
						'default_hide' => __( '<strong>Manual</strong> - Users can only access course if you grant them access.', 'wp-courseware' ),
						'closed'       => __( '<strong>Closed</strong> - Users that are currently enrolled can still access, but future enrollment or purchase is disabled.', 'wp-courseware' ),
					),
				),
				'course_opt_completion_wall' => array(
					'id'      => 'course_opt_completion_wall',
					'name'    => 'course_opt_completion_wall',
					'type'    => 'radio',
					'label'   => esc_html__( 'Unit Visibility', 'wp-courseware' ),
					'desc'    => esc_html__( 'Can a user see all possible course units? Or must they complete previous units before seeing the next unit?', 'wp-courseware' ),
					'tip'     => esc_html__( 'Unit Visibility', 'wp-courseware' ),
					'default' => 'completion_wall',
					'options' => array(
						'completion_wall' => __( '<strong>Only Completed/Next Units Visible</strong> - Only show units that have been completed, plus the next unit that the user can start.', 'wp-courseware' ),
						'all_visible'     => __( '<strong>All Units Visible</strong> - All units are visible regardless of completion progress.', 'wp-courseware' ),
					),
				),
				'unit_advancement'           => array(
					'id'      => 'unit_advancement',
					'name'    => 'unit_advancement',
					'type'    => 'radio',
					'label'   => esc_html__( 'Unit Advancement', 'wp-courseware' ),
					'desc'    => esc_html__( 'This setting allows you to set how users advance to the next unit. Users can either advance to the next unit manually by clicking the "Next Unit" link or automatically when a Unit is marked as complete.', 'wp-courseware' ),
					'tip'     => esc_html__( 'Unit Advancement', 'wp-courseware' ),
					'default' => 'manual',
					'options' => array(
						'manual'    => __( '<strong>Manual</strong> - Advance manually to next unit by clicking "Next Unit" link.', 'wp-courseware' ),
						'automatic' => __( '<strong>Automatic</strong> - Advance automatically when Unit is marked as complete.', 'wp-courseware' ),
					),
				),
			),
			$this
		);
	}

	/**
	 * Get Description Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of description fields.
	 */
	public function get_description_fields() {
		/**
		 * Filter: Course Description Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course description fields.
		 * @param Course $this The course object.
		 *
		 * @return array The course description fields.
		 */
		return apply_filters(
			'wpcw_course_description_fields',
			array(
				'course_desc' => array(
					'id'    => 'course_desc',
					'name'  => 'course_desc',
					'type'  => 'wysiwyg',
					'label' => esc_html__( 'Course Description', 'wp-courseware' ),
					'desc'  => esc_html__( 'The description of this course. Your students will be able to see this course description.', 'wp-courseware' ),
					'tip'   => esc_html__( 'The course description.', 'wp-courseware' ),
				),
			),
			$this
		);
	}

	/**
	 * Get Payments Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of payments fields.
	 */
	public function get_payments_fields() {
		/**
		 * Filter: Course Payments Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course payments fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course payments fields.
		 */
		return apply_filters(
			'wpcw_course_payments_fields',
			array(
				'payments_type'        => array(
					'id'      => 'payments_type',
					'name'    => 'payments_type',
					'type'    => 'radio',
					'label'   => esc_html__( 'Payments Type', 'wp-courseware' ),
					'desc'    => esc_html__( 'The course payments type.', 'wp-courseware' ),
					'tip'     => esc_html__( 'The course payments type.', 'wp-courseware' ),
					'default' => 'free',
					'options' => array(
						'free'         => __( '<strong>Free</strong> - No payment to enroll in course.', 'wp-courseware' ),
						'one-time'     => __( '<strong>One-Time Purchase</strong> - A single payment to enroll in course.', 'wp-courseware' ),
						'subscription' => __( '<strong>Subscription</strong> - Monthly or Annual billing interval for continued enrollment in course.', 'wp-courseware' ),
					),
				),
				'payments_price'       => array(
					'id'          => 'payments_price',
					'name'        => 'payments_price',
					'type'        => 'money',
					'label'       => esc_html__( 'Payments Price', 'wp-courseware' ),
					'desc'        => esc_html__( 'The payment price for the course.', 'wp-courseware' ),
					'tip'         => esc_html__( 'The payment price for the course.', 'wp-courseware' ),
					'default'     => '0.00',
					'placeholder' => '0.00',
					'condition'   => array(
						'field' => 'payments_type',
						'value' => array( 'one-time', 'subscription' ),
					),
				),
				'payments_interval'    => array(
					'id'        => 'payments_interval',
					'name'      => 'payments_interval',
					'type'      => 'radio',
					'label'     => esc_html__( 'Payments Interval', 'wp-courseware' ),
					'desc'      => esc_html__( 'The payment interval for the course.', 'wp-courseware' ),
					'tip'       => esc_html__( 'The payment interval for the course.', 'wp-courseware' ),
					'default'   => 'month',
					'options'   => array(
						'day'       => __( '<strong>Daily</strong> - Subscription is billed daily until cancelled.', 'wp-courseware' ),
						'week'      => __( '<strong>Weekly</strong> - Subscription is billed weekly until cancelled.', 'wp-courseware' ),
						'month'     => __( '<strong>Monthly</strong> - Subscription is billed monthly until cancelled.', 'wp-courseware' ),
						'quarter'   => __( '<strong>Quarterly</strong> - Subscription is billed quarterly until cancelled.', 'wp-courseware' ),
						'semi-year' => __( '<strong>Sem-Yearly</strong> - Subscription is billed semi-yearly until cancelled.', 'wp-courseware' ),
						'year'      => __( '<strong>Yearly</strong> - Subscription is billed yearly until cancelled.', 'wp-courseware' ),
					),
					'condition' => array(
						'field' => 'payments_type',
						'value' => 'subscription',
					),
				),
				'installments_enabled' => array(
					'id'        => 'installments_enabled',
					'name'      => 'installments_enabled',
					'type'      => 'radio',
					'label'     => esc_html__( 'Installments Enabled?', 'wp-courseware' ),
					'desc'      => esc_html__( 'Offer the ability to pay installments to access the course. This option will be displayed as a purchase option next to the payments price.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Offer the ability to pay installments to access the course. This option will be displayed as a purchase option next to the payments price.', 'wp-courseware' ),
					'default'   => 'no',
					'options'   => array(
						'yes' => __( '<strong>Yes</strong> - Enable installment payments.', 'wp-courseware' ),
						'no'  => __( '<strong>No</strong> - Disable installment payments.', 'wp-courseware' ),
					),
					'condition' => array(
						'field' => 'payments_type',
						'value' => array( 'one-time' ),
					),
				),
				'installments'         => array(
					'id'        => 'installments',
					'name'      => 'installments',
					'type'      => 'installments',
					'label'     => esc_html__( 'Installment Payments', 'wp-courseware' ),
					'desc'      => esc_html__( 'Use the fields above to configure installment payments.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Use the fields to the right to configure installment payments.', 'wp-courseware' ),
					'ignore'    => true,
					'fields'    => array(
						'installments_number'   => array(
							'id'      => 'installments_number',
							'name'    => 'installments_number',
							'type'    => 'number',
							'label'   => esc_html__( 'Number of Installments', 'wp-courseware' ),
							'desc'    => esc_html__( 'Please enter the number of installments needed to purchase this course.', 'wp-courseware' ),
							'tip'     => esc_html__( 'Please enter the number of installments needed to purchase this course.', 'wp-courseware' ),
							'default' => 2,
							'min'     => 2,
							'size'    => 'small',
						),
						'installments_amount'   => array(
							'id'          => 'installments_amount',
							'name'        => 'installments_amount',
							'label'       => esc_html__( 'Installment Amount', 'wp-courseware' ),
							'desc'        => esc_html__( 'Enter the amount for each installment. Should be an amount greater than $0.00.', 'wp-courseware' ),
							'tip'         => esc_html__( 'Enter the amount for each installment. Should be an amount greater than $0.00.', 'wp-courseware' ),
							'type'        => 'money',
							'default'     => '0.00',
							'placeholder' => '0.00',
						),
						'installments_interval' => array(
							'id'      => 'installments_interval',
							'name'    => 'installments_interval',
							'type'    => 'radio',
							'label'   => esc_html__( 'Installment Interval', 'wp-courseware' ),
							'desc'    => esc_html__( 'How often should each installment be billed?', 'wp-courseware' ),
							'tip'     => esc_html__( 'How often should each installment be billed?', 'wp-courseware' ),
							'default' => 'month',
							'options' => array(
								'day'       => __( '<strong>Daily</strong> - Installment is billed daily until paid in full.', 'wp-courseware' ),
								'week'      => __( '<strong>Weekly</strong> - Installment is billed weekly until paid in full.', 'wp-courseware' ),
								'month'     => __( '<strong>Monthly</strong> - Installment is billed monthly until paid in full.', 'wp-courseware' ),
								'quarter'   => __( '<strong>Quarterly</strong> - Installment is billed quarterly until paid in full.', 'wp-courseware' ),
								'semi-year' => __( '<strong>Sem-Yearly</strong> - Installment is billed semi-yearly until paid in full.', 'wp-courseware' ),
								'year'      => __( '<strong>Yearly</strong> - Installment is billed yearly until paid in full.', 'wp-courseware' ),
							),
						),
					),
					'condition' => array(
						'field' => array( 'payments_type', 'installments_enabled' ),
						'value' => array( 'one-time', 'yes' ),
					),
				),
			),
			$this
		);
	}

	/**
	 * Get Message Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of message fields.
	 */
	public function get_messages_fields() {
		/**
		 * Filter: Course Messages Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course messages fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course messages fields.
		 */
		return apply_filters(
			'wpcw_course_messages_fields',
			array(
				'course_messages' => array(
					'id'     => 'course_messages',
					'name'   => 'course_messages',
					'type'   => 'accordion',
					'label'  => esc_html__( 'Course Messages', 'wp-courseware' ),
					'tip'    => esc_html__( 'Course Messages', 'wp-courseware' ),
					'fields' => array(
						'course_message_unit_complete'   => array(
							'id'         => 'course_message_unit_complete',
							'name'       => 'course_message_unit_complete',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Unit Complete', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee once they\'ve completed a unit, which is displayed at the bottom of the unit page. HTML is OK.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee once they\'ve completed a unit, which is displayed at the bottom of the unit page. HTML is OK.', 'wp-courseware' ),
							'default'    => __( 'You have now completed this unit.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_course_complete' => array(
							'id'         => 'course_message_course_complete',
							'name'       => 'course_message_course_complete',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Course Complete', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee once they\'ve completed the whole course, which is displayed at the bottom of the unit page. HTML is OK.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee once they\'ve completed the whole course, which is displayed at the bottom of the unit page. HTML is OK.', 'wp-courseware' ),
							'default'    => __( 'You have now completed the whole course. Congratulations!', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_unit_pending'    => array(
							'id'         => 'course_message_unit_pending',
							'name'       => 'course_message_unit_pending',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Unit Pending', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee when they\'ve yet to complete a unit. This message is displayed at the bottom of the unit page, along with a button that says "Mark as completed". HTML is OK.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee when they\'ve yet to complete a unit. This message is displayed at the bottom of the unit page, along with a button that says "Mark as completed". HTML is OK.', 'wp-courseware' ),
							'default'    => __( 'Have you completed this unit? Then mark this unit as completed.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_prerequisite_not_met' => array(
							'id'         => 'course_message_prerequisite_not_met',
							'name'       => 'course_message_prerequisite_not_met',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Prerequisite not met', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a student that attempts to access a course that has one or more prerequisites that have not been met.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a student that attempts to access a course that has one or more prerequisites that have not been met.', 'wp-courseware' ),
							'default'    => __( 'This course can not be accessed until the prerequisites for this course are complete.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_unit_no_access'  => array(
							'id'         => 'course_message_unit_no_access',
							'name'       => 'course_message_unit_no_access',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Access Denied', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee they are not allowed to access a unit, because they are not allowed to access the whole course.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee they are not allowed to access a unit, because they are not allowed to access the whole course.', 'wp-courseware' ),
							'default'    => __( 'Sorry, but you\'re not allowed to access this course.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_unit_not_yet'    => array(
							'id'         => 'course_message_unit_not_yet',
							'name'       => 'course_message_unit_not_yet',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Not Yet Available', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee they are not allowed to access a unit yet, because they need to complete a previous unit.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee they are not allowed to access a unit yet, because they need to complete a previous unit.', 'wp-courseware' ),
							'default'    => __( 'You need to complete the previous unit first.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_unit_not_yet_dripfeed' => array(
							'id'         => 'course_message_unit_not_yet_dripfeed',
							'name'       => 'course_message_unit_not_yet_dripfeed',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Not Unlocked Yet', 'wp-courseware' ),
							'desc'       => __( 'The message shown to a trainee they are not allowed to access a unit yet, because the unit is locked due to a drip feed setting. Use the variable <code>{UNIT_UNLOCKED_TIME}</code> to insert the approximate days and hours when the unit will be unlocked.', 'wp-courseware' ),
							'tip'        => __( 'The message shown to a trainee they are not allowed to access a unit yet, because the unit is locked due to a drip feed setting. Use the variable <code>{UNIT_UNLOCKED_TIME}</code> to insert the approximate days and hours when the unit will be unlocked.', 'wp-courseware' ),
							'default'    => __( 'This unit isn\'t available just yet. Please check back in about {UNIT_UNLOCKED_TIME}.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_unit_not_logged_in' => array(
							'id'         => 'course_message_unit_not_logged_in',
							'name'       => 'course_message_unit_not_logged_in',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Not Logged In', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee they are not logged in, and therefore cannot access the unit.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee they are not logged in, and therefore cannot access the unit.', 'wp-courseware' ),
							'default'    => __( 'You cannot view this unit as you\'re not logged in yet.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_quiz_open_grading_blocking' => array(
							'id'         => 'course_message_quiz_open_grading_blocking',
							'name'       => 'course_message_quiz_open_grading_blocking',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Open-Question Submitted - Blocking Mode', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee they have submitted an answer to an open-ended or upload question, and you need to grade their answer before they continue.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee they have submitted an answer to an open-ended or upload question, and you need to grade their answer before they continue.', 'wp-courseware' ),
							'default'    => __( 'Your quiz has been submitted for grading by the course instructor. Once your grade has been entered, you will be able to access the next unit.', 'wp-courseware' ),
							'hide_label' => true,
						),
						'course_message_quiz_open_grading_non_blocking' => array(
							'id'         => 'course_message_quiz_open_grading_non_blocking',
							'name'       => 'course_message_quiz_open_grading_non_blocking',
							'type'       => 'wysiwyg',
							'label'      => esc_html__( 'Open-Question Submitted - Non-Blocking', 'wp-courseware' ),
							'desc'       => esc_html__( 'The message shown to a trainee they have submitted an answer to an open-ended or upload question, and you need to grade their answer, but they can continue anyway.', 'wp-courseware' ),
							'tip'        => esc_html__( 'The message shown to a trainee they have submitted an answer to an open-ended or upload question, and you need to grade their answer, but they can continue anyway.', 'wp-courseware' ),
							'default'    => __( 'Your quiz has been submitted for grading by the course instructor. You have now completed this unit.', 'wp-courseware' ),
							'hide_label' => true,
						),
					),
					'ignore' => true,
				),
			),
			$this
		);
	}

	/**
	 * Get Emails Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of email fields.
	 */
	public function get_emails_fields() {
		/**
		 * Filter: Course Emails Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course emails fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course emails fields.
		 */
		return apply_filters(
			'wpcw_course_emails_fields',
			array(
				'course_from_email' => array(
					'id'      => 'course_from_email',
					'name'    => 'course_from_email',
					'type'    => 'text',
					'label'   => esc_html__( 'From Address', 'wp-courseware' ),
					'desc'    => esc_html__( 'The email address that the email notifications should be from. Depending on your server\'s spam-protection set up, this may not appear in the outgoing emails.', 'wp-courseware' ),
					'tip'     => esc_html__( 'The email address that the email notifications should be from.', 'wp-courseware' ),
					'default' => $this->get_from_email(),
				),
				'course_from_name'  => array(
					'id'      => 'course_from_name',
					'name'    => 'course_from_name',
					'type'    => 'text',
					'label'   => esc_html__( 'From Name', 'wp-courseware' ),
					'desc'    => esc_html__( 'The name used on the email notifications, which are sent to you and your students. Depending on your server\'s spam-protection set up, this may not appear in the outgoing emails.', 'wp-courseware' ),
					'tip'     => esc_html__( 'The name used on the email notifications, which are sent to you and your students.', 'wp-courseware' ),
					'default' => get_bloginfo( 'name' ),
				),
				'course_to_email'   => array(
					'id'      => 'course_to_email',
					'name'    => 'course_to_email',
					'type'    => 'text',
					'label'   => esc_html__( 'Notify Admin Email', 'wp-courseware' ),
					'desc'    => esc_html__( 'The email address to send admin notifications to.', 'wp-courseware' ),
					'tip'     => esc_html__( 'The email address to send admin notifications to.', 'wp-courseware' ),
					'default' => $this->get_to_email(),
				),
				'course_emails'     => array(
					'id'     => 'course_emails',
					'name'   => 'course_emails',
					'type'   => 'accordion',
					'label'  => esc_html__( 'Course Emails', 'wp-courseware' ),
					'tip'    => esc_html__( 'Course Emails', 'wp-courseware' ),
					'fields' => array(
						'course_complete_email' => array(
							'id'     => 'course_complete_email',
							'name'   => 'course_complete_email',
							'type'   => 'accordion_item',
							'label'  => esc_html__( 'Course Complete Email', 'wp-courseware' ),
							'tip'    => esc_html__( 'The email sent to and administrator or user when the student has completed the whole course.', 'wp-courseware' ),
							'fields' => array(
								'email_complete_course_option_admin' => array(
									'id'      => 'email_complete_course_option_admin',
									'name'    => 'email_complete_course_option_admin',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify Admin?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the administrator an email when a student has completed the whole course.', 'wp-courseware' ),
									'default' => 'send_email',
									'options' => array(
										'send_email' => __( '<strong>Send me an email</strong> - when one of your trainees has completed the whole course.', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t send me an email</strong> - when one of your trainees has completed the whole course.', 'wp-courseware' ),
									),
								),
								'email_complete_course_option' => array(
									'id'      => 'email_complete_course_option',
									'name'    => 'email_complete_course_option',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify Student?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the student an email when the whole course has been completed.', 'wp-courseware' ),
									'default' => 'send_email',
									'options' => array(
										'send_email' => __( '<strong>Send Email</strong> - to user when the whole course has been completed.', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t Send Email</strong> - to user when the whole course has been completed.', 'wp-courseware' ),
									),
								),
								'email_complete_course_subject' => array(
									'id'         => 'email_complete_course_subject',
									'name'       => 'email_complete_course_subject',
									'type'       => 'text',
									'size'       => 'large',
									'label'      => esc_html__( 'Email Subject', 'wp-courseware' ),
									'desc'       => esc_html__( 'The subject line for the email sent to a user when they complete the whole course.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The subject line for the email sent to a user when they complete the whole course.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_COURSE_SUBJECT' ),
									'merge_tags' => wpcw()->courses->get_course_complete_email_merge_tags(),
								),
								'email_complete_course_body'   => array(
									'id'         => 'email_complete_course_body',
									'name'       => 'email_complete_course_body',
									'type'       => 'wysiwyg',
									'label'      => esc_html__( 'Email Body', 'wp-courseware' ),
									'desc'       => esc_html__( 'The template body for the email sent to a user when they complete the whole course.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The template body for the email sent to a user when they complete the whole course.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_COURSE_BODY' ),
									'merge_tags' => wpcw()->courses->get_course_complete_email_merge_tags(),
								),
							),
							'ignore' => true,
						),
						'module_complete_email' => array(
							'id'     => 'module_complete_email',
							'name'   => 'module_complete_email',
							'type'   => 'accordion_item',
							'label'  => esc_html__( 'Module Complete Email', 'wp-courseware' ),
							'tip'    => esc_html__( 'The email sent to you or the student when a module has been completed.', 'wp-courseware' ),
							'fields' => array(
								'email_complete_module_option_admin' => array(
									'id'      => 'email_complete_module_option_admin',
									'name'    => 'email_complete_module_option_admin',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify You?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the you an email when a module has been completed.', 'wp-courseware' ),
									'default' => 'send_email',
									'options' => array(
										'send_email' => __( '<strong>Send me an email</strong> - when one of your students has completed a module.', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t send me an email</strong> - when one of your students has completed a module.', 'wp-courseware' ),
									),
								),
								'email_complete_module_option' => array(
									'id'      => 'email_complete_module_option',
									'name'    => 'email_complete_module_option',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify Student?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the student an email when a module has been completed.', 'wp-courseware' ),
									'default' => 'send_email',
									'options' => array(
										'send_email' => __( '<strong>Send Email</strong> - to student when module has been completed.', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t Send Email</strong> - to student when module has been completed.', 'wp-courseware' ),
									),
								),
								'email_complete_module_subject' => array(
									'id'         => 'email_complete_module_subject',
									'name'       => 'email_complete_module_subject',
									'type'       => 'text',
									'size'       => 'large',
									'label'      => esc_html__( 'Email Subject', 'wp-courseware' ),
									'desc'       => esc_html__( 'The subject line for the email sent to a user when they complete a module.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The subject line for the email sent to a user when they complete a module.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_MODULE_SUBJECT' ),
									'merge_tags' => wpcw()->courses->get_email_merge_tags(),
								),
								'email_complete_module_body'   => array(
									'id'         => 'email_complete_module_body',
									'name'       => 'email_complete_module_body',
									'type'       => 'wysiwyg',
									'label'      => esc_html__( 'Email Body', 'wp-courseware' ),
									'desc'       => esc_html__( 'The template body for the email sent to a user when they complete a module.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The template body for the email sent to a user when they complete a module.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_MODULE_BODY' ),
									'merge_tags' => wpcw()->courses->get_email_merge_tags(),
								),
							),
							'ignore' => true,
						),
						'unit_complete_email'   => array(
							'id'     => 'unit_complete_email',
							'name'   => 'unit_complete_email',
							'type'   => 'accordion_item',
							'label'  => esc_html__( 'Unit Complete Email', 'wp-courseware' ),
							'tip'    => esc_html__( 'The email sent to and administrator or user when the student has completed a unit in the course.', 'wp-courseware' ),
							'fields' => array(
								'email_complete_unit_option_admin' => array(
									'id'      => 'email_complete_unit_option_admin',
									'name'    => 'email_complete_unit_option_admin',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify Admin?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the administrator an email when a student has completed a unit in the course.', 'wp-courseware' ),
									'default' => 'no_email',
									'options' => array(
										'send_email' => __( '<strong>Send me an email</strong> - when one of your students has completed a unit in the course.', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t send me an email</strong> - when one of your students has completed a unit in the course.', 'wp-courseware' ),
									),
								),
								'email_complete_unit_option' => array(
									'id'      => 'email_complete_unit_option',
									'name'    => 'email_complete_unit_option',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify Student?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the student an email when a unit in the course has been completed.', 'wp-courseware' ),
									'default' => 'no_email',
									'options' => array(
										'send_email' => __( '<strong>Send Email</strong> - to student when a unit in the course has been completed.', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t Send Email</strong> - to user when a unit in the course has been completed.', 'wp-courseware' ),
									),
								),
								'email_complete_unit_subject' => array(
									'id'         => 'email_complete_unit_subject',
									'name'       => 'email_complete_unit_subject',
									'type'       => 'text',
									'size'       => 'large',
									'label'      => esc_html__( 'Email Subject', 'wp-courseware' ),
									'desc'       => esc_html__( 'The subject line for the email sent to a user when they complete a unit in the course.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The subject line for the email sent to a user when they complete a unit in the course.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_SUBJECT' ),
									'merge_tags' => wpcw()->courses->get_email_merge_tags(),
								),
								'email_complete_unit_body' => array(
									'id'         => 'email_complete_unit_body',
									'name'       => 'email_complete_unit_body',
									'type'       => 'wysiwyg',
									'label'      => esc_html__( 'Email Body', 'wp-courseware' ),
									'desc'       => esc_html__( 'The template body for the email sent to a user when they complete a unit in the course.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The template body for the email sent to a user when they complete a unit in the course.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COMPLETE_UNIT_BODY' ),
									'merge_tags' => wpcw()->courses->get_email_merge_tags(),
								),
							),
							'ignore' => true,
						),
						'unit_unlocked_email'   => array(
							'id'     => 'unit_unlocked_email',
							'name'   => 'unit_unlocked_email',
							'type'   => 'accordion_item',
							'label'  => esc_html__( 'Unit Unlocked Email', 'wp-courseware' ),
							'tip'    => esc_html__( 'The email sent to a user when a unit that\'s being drip fed is unlocked and available to access.', 'wp-courseware' ),
							'fields' => array(
								'email_unit_unlocked_subject' => array(
									'id'         => 'email_unit_unlocked_subject',
									'name'       => 'email_unit_unlocked_subject',
									'type'       => 'text',
									'size'       => 'large',
									'label'      => esc_html__( 'Email Subject', 'wp-courseware' ),
									'desc'       => esc_html__( 'The subject line for the email sent to a user when a unit that\'s being drip fed is unlocked and available for them to access.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The subject line for the email sent to a user when a unit that\'s being drip fed is unlocked and available for them to access.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_UNIT_UNLOCKED_SUBJECT' ),
									'merge_tags' => wpcw()->courses->get_email_merge_tags(),
								),
								'email_unit_unlocked_body' => array(
									'id'         => 'email_unit_unlocked_body',
									'name'       => 'email_unit_unlocked_body',
									'type'       => 'wysiwyg',
									'label'      => esc_html__( 'Email Body', 'wp-courseware' ),
									'desc'       => esc_html__( 'The template body for the email sent to a user when a unit that\'s being drip fed is unlocked and available for them to access.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The template body for the email sent to a user when a unit that\'s being drip fed is unlocked and available for them to access.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_UNIT_UNLOCKED_BODY' ),
									'merge_tags' => wpcw()->courses->get_email_merge_tags(),
								),
							),
							'ignore' => true,
						),
						'quiz_graded_email'     => array(
							'id'     => 'quiz_graded_email',
							'name'   => 'quiz_graded_email',
							'type'   => 'accordion_item',
							'label'  => esc_html__( 'Quiz Graded Email', 'wp-courseware' ),
							'tip'    => esc_html__( 'The email sent to the student after a quiz is graded ( automatically or by the instructor ).', 'wp-courseware' ),
							'fields' => array(
								'email_quiz_grade_option'  => array(
									'id'      => 'email_quiz_grade_option',
									'name'    => 'email_quiz_grade_option',
									'type'    => 'radio',
									'label'   => esc_html__( 'Notify Student?', 'wp-courseware' ),
									'tip'     => esc_html__( 'When enabled this will send the student and email after a quiz is graded ( automatically or by the instructor ).', 'wp-courseware' ),
									'default' => 'send_email',
									'options' => array(
										'send_email' => __( '<strong>Send Email</strong> - to student after a quiz is graded ( automatically or by the instructor ).', 'wp-courseware' ),
										'no_email'   => __( '<strong>Don\'t Send Email</strong> - to student after a quiz is graded ( automatically or by the instructor ).', 'wp-courseware' ),
									),
								),
								'email_quiz_grade_subject' => array(
									'id'         => 'email_quiz_grade_subject',
									'name'       => 'email_quiz_grade_subject',
									'type'       => 'text',
									'size'       => 'large',
									'label'      => esc_html__( 'Email Subject', 'wp-courseware' ),
									'desc'       => esc_html__( 'The subject line for the email sent to a student when they receive a grade for a quiz.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The subject line for the email sent to a student when they receive a grade for a quiz.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_QUIZ_GRADE_SUBJECT' ),
									'merge_tags' => wpcw()->courses->get_quiz_email_merge_tags(),
								),
								'email_quiz_grade_body'    => array(
									'id'         => 'email_quiz_grade_body',
									'name'       => 'email_quiz_grade_body',
									'type'       => 'wysiwyg',
									'label'      => esc_html__( 'Email Body', 'wp-courseware' ),
									'desc'       => esc_html__( 'The template body for the email sent to a student when they receive a grade for a quiz.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The template body for the email sent to a student when they receive a grade for a quiz.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_QUIZ_GRADE_BODY' ),
									'merge_tags' => wpcw()->courses->get_quiz_email_merge_tags(),
								),
							),
							'ignore' => true,
						),
						'final_summary_email'   => array(
							'id'     => 'final_summary_email',
							'name'   => 'final_summary_email',
							'type'   => 'accordion_item',
							'label'  => esc_html__( 'Final Summary Email', 'wp-courseware' ),
							'tip'    => esc_html__( 'The email sent to a student when they receive their grade summary at the end of the course..', 'wp-courseware' ),
							'fields' => array(
								'email_complete_course_grade_summary_subject' => array(
									'id'         => 'email_complete_course_grade_summary_subject',
									'name'       => 'email_complete_course_grade_summary_subject',
									'type'       => 'text',
									'size'       => 'large',
									'label'      => esc_html__( 'Email Subject', 'wp-courseware' ),
									'desc'       => esc_html__( 'The subject line for the email sent to a student when they receive their grade summary at the end of the course.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The subject line for the email sent to a student when they receive their grade summary at the end of the course.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_SUBJECT' ),
									'merge_tags' => wpcw()->courses->get_final_summary_email_merge_tags(),
								),
								'email_complete_course_grade_summary_body'    => array(
									'id'         => 'email_complete_course_grade_summary_body',
									'name'       => 'email_complete_course_grade_summary_body',
									'type'       => 'wysiwyg',
									'label'      => esc_html__( 'Email Body', 'wp-courseware' ),
									'desc'       => esc_html__( 'The template body for the email sent to a user when they receive their grade summary at the end of the course.', 'wp-courseware' ),
									'tip'        => esc_html__( 'The template body for the email sent to a user when they receive their grade summary at the end of the course.', 'wp-courseware' ),
									'default'    => wpcw_get_email_template_text( 'EMAIL_TEMPLATE_COURSE_SUMMARY_WITH_GRADE_BODY' ),
									'merge_tags' => wpcw()->courses->get_final_summary_email_merge_tags(),
								),
							),
							'ignore' => true,
						),
					),
					'ignore' => true,
				),
			),
			$this
		);
	}

	/**
	 * Get Certificates Fields.
	 *
	 * @since 4.4.40
	 *
	 * @return array The array of certificates fields.
	 */
	public function get_certificates_fields() {
		$certificate_preview = ! empty( $this->course )
			? sprintf(
				'<a href="%s" target="_blank" class="button-primary">%s</a>',
				add_query_arg(
					array(
						'page'                    => 'wpcw_pdf_create_certificate',
						'certificate'             => 'preview',
						'course_id'               => $this->course->get_id(),
						'certificate_template_id' => $this->course->certificate_template,
					),
					esc_url( home_url( '/' ) )
				),
				esc_html__( 'Preview Certificate', 'wp-courseware' )
			)
			: __( 'Please save the course details to preview your certificate.', 'wp-courseware' );

		$args_certificates  = array(
			'post_type'      => 'wpcw_certificates',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$current_user = wp_get_current_user();

		if ( ! user_can( $current_user->ID, 'manage_wpcw_settings' ) ) {
			$args_certificates['author'] = $current_user->ID ;
		}

		$query_certificates = new \WP_Query( $args_certificates );

		$count_certificates = $query_certificates->found_posts;

		$certificate_list_arr = array( 0 => 'Legacy Certificate' );

		if ( 0 < $count_certificates ) {
			foreach ( $query_certificates->posts as $certificate_id ) {
				$certificate_list_arr += array( $certificate_id => get_the_title( $certificate_id ) );
			}
		}
		/**
		 * Filter: Course Certificates Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course certificates fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course certificates fields.
		 */
		return apply_filters(
			'wpcw_course_certificates_fields',
			array(
				'course_opt_use_certificate' => array(
					'id'      => 'course_opt_use_certificate',
					'name'    => 'course_opt_use_certificate',
					'type'    => 'radio',
					'label'   => esc_html__( 'Enable certificates?', 'wp-courseware' ),
					'tip'     => esc_html__( 'Enable certificates?', 'wp-courseware' ),
					'default' => 'no_certs',
					'options' => array(
						'use_certs' => __( '<strong>Yes</strong> - generate a PDF certificate when user completes this course.', 'wp-courseware' ),
						'no_certs'  => __( '<strong>No</strong> - don\'t generate a PDF certificate when user completes this course.', 'wp-courseware' ),
					),
				),
				'certificate_template'       => array(
					'id'        => 'certificate_template',
					'name'      => 'certificate_template',
					'type'      => 'select',
					'label'     => esc_html__( 'Select Certificate', 'wp-courseware' ),
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
					'condition' => array(
						'field' => 'course_opt_use_certificate',
						'value' => 'use_certs',
					),
					'default'   => 0,
					'options'   => $certificate_list_arr,
				),
				'cert_signature_type'        => array(
					'id'        => 'cert_signature_type',
					'name'      => 'cert_signature_type',
					'type'      => 'radio',
					'label'     => esc_html__( 'Signature Type', 'wp-courseware' ),
					'tip'       => esc_html__( 'The type of signature to be used on the certificate.', 'wp-courseware' ),
					'default'   => 'text',
					'options'   => array(
						'text'  => __( '<strong>Text</strong> - Just use text for the signature.', 'wp-courseware' ),
						'image' => __( '<strong>Image File</strong> - Use an image for the signature.', 'wp-courseware' ),
					),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate' ),
						'value' => array( 0, 'use_certs' ),
					),
				),
				'cert_sig_text'              => array(
					'id'        => 'cert_sig_text',
					'name'      => 'cert_sig_text',
					'type'      => 'text',
					'label'     => esc_html__( 'Signature Text', 'wp-courseware' ),
					'desc'      => esc_html__( 'The name to use for the signature area.', 'wp-courseware' ),
					'tip'       => esc_html__( 'The name to use for the signature area.', 'wp-courseware' ),
					'default'   => get_bloginfo( 'name' ),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate', 'cert_signature_type' ),
						'value' => array( 0, 'use_certs', 'text' ),
					),
				),
				'cert_sig_image_url'         => array(
					'id'        => 'cert_sig_image_url',
					'name'      => 'cert_sig_image_url',
					'type'      => 'image',
					'label'     => esc_html__( 'Signature Image', 'wp-courseware' ),
					'tip'       => __( 'Use an signature image that is <code>340 x 80</code> pixels in size.', 'wp-courseware' ),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate', 'cert_signature_type' ),
						'value' => array( 0, 'use_certs', 'image' ),
					),
				),
				'cert_logo_enabled'          => array(
					'id'        => 'cert_logo_enabled',
					'name'      => 'cert_logo_enabled',
					'type'      => 'radio',
					'label'     => esc_html__( 'Logo', 'wp-courseware' ),
					'tip'       => esc_html__( 'Is the Logo enabled?', 'wp-courseware' ),
					'default'   => 'no_cert_logo',
					'options'   => array(
						'cert_logo'    => __( '<strong>Yes</strong> - Use your logo on the certificate.', 'wp-courseware' ),
						'no_cert_logo' => __( '<strong>No</strong> - Don\'t show a logo on the certificate.', 'wp-courseware' ),
					),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate' ),
						'value' => array( 0, 'use_certs' ),
					),
				),
				'cert_logo_url'              => array(
					'id'        => 'cert_logo_url',
					'name'      => 'cert_logo_url',
					'type'      => 'image',
					'label'     => esc_html__( 'Logo Image', 'wp-courseware' ),
					'tip'       => __( 'Use a logo image that is <code>320 x 240</code> pixels in size.', 'wp-courseware' ),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate', 'cert_logo_enabled' ),
						'value' => array( 0, 'use_certs', 'cert_logo' ),
					),
				),
				'cert_background_type'       => array(
					'id'        => 'cert_background_type',
					'name'      => 'cert_background_type',
					'type'      => 'radio',
					'label'     => esc_html__( 'Background', 'wp-courseware' ),
					'tip'       => esc_html__( 'The type of background that will go on the certificate.', 'wp-courseware' ),
					'default'   => 'use_default',
					'options'   => array(
						'use_default' => __( '<strong>Built-in</strong> - Use the built-in certificate background.', 'wp-courseware' ),
						'use_custom'  => __( '<strong>Custom</strong> - Use your own certificate background.', 'wp-courseware' ),
					),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate' ),
						'value' => array( 0, 'use_certs' ),
					),
				),
				'cert_background_custom_url' => array(
					'id'        => 'cert_background_custom_url',
					'name'      => 'cert_background_custom_url',
					'type'      => 'image',
					'label'     => esc_html__( 'Background Image', 'wp-courseware' ),
					'tip'       => __( 'Use a background image that is <code>3508 x 2480</code> pixels in size.', 'wp-courseware' ),
					'condition' => array(
						'field' => array( 'certificate_template', 'course_opt_use_certificate', 'cert_background_type' ),
						'value' => array( 0, 'use_certs', 'use_custom' ),
					),
				),
				'certificate_preview'        => array(
					'id'        => 'certificate_preview',
					'name'      => 'certificate_preview',
					'type'      => 'html',
					'label'     => esc_html__( 'Preview Certificate', 'wp-courseware' ),
					'desc'      => esc_html__( 'After saving the course settings, you can preview the certificate using the button above. The preview opens in a new window.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Preview Certificate', 'wp-courseware' ),
					'html'      => $certificate_preview,
					'condition' => array(
						'field' => 'course_opt_use_certificate',
						'value' => 'use_certs',
					),
					'ignore'    => true,
				),
			),
			$this
		);
	}

	/**
	 * Get Prerequisite Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of prerequisite fields.
	 */
	public function get_prerequisite_fields() {
		/**
		 * Filter: Course Prerequisite Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course prerequisite fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course prerequisite fields.
		 */
		return apply_filters(
			'wpcw_course_prerequisite_fields',
			array(
				'course_opt_prerequisites' => array(
					'id'        => 'course_opt_prerequisites',
					'name'      => 'course_opt_prerequisites',
					'type'      => 'prerequisites',
					'label'     => esc_html__( 'Course Prerequisites', 'wp-courseware' ),
					'desc'      => esc_html__( 'Select the courses that must be completed in order to access this course.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Select the courses that must be completed in order to access this course.', 'wp-courseware' ),
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
				),
			),
			$this
		);
	}

	/**
	 * Get Course Bundles Fields.
	 *
	 * @since 4.6.0
	 *
	 * @return array The array of prerequisite fields.
	 */
	public function get_course_bundles_fields() {
		/**
		 * Filter: Course Bundles Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course bundles fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course bundles fields.
		 */
		return apply_filters(
			'wpcw_course_bundles_fields',
			array(
				'course_bundles' => array(
					'id'        => 'course_bundles',
					'name'      => 'course_bundles',
					'type'      => 'coursebundles',
					'label'     => esc_html__( 'Course Bundles', 'wp-courseware' ),
					'desc'      => esc_html__( 'Select the courses that when purchased will be bundled with this course.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Select the courses that when purchased will be bundled with this course.', 'wp-courseware' ),
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
				),
			),
			$this
		);
	}

	/**
	 * Get Instructor Fields.
	 *
	 * @since 4.5.2
	 *
	 * @return array The array of instructor fields.
	 */
	public function get_instructor_fields() {
		/**
		 * Filter: Course Instructor Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course instructor fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course instructor fields.
		 */
		return apply_filters(
			'wpcw_course_instructor_fields',
			array(
				'course_instructor' => array(
					'id'            => 'course_instructor',
					'name'          => 'course_instructor',
					'type'          => 'courseinstructor',
					'label'         => esc_html__( 'Course Instructor', 'wp-courseware' ),
					'desc'          => esc_html__( 'Select the user that is the instructor of the course.', 'wp-courseware' ),
					'tip'           => esc_html__( 'Select the user that is the instructor of the course.', 'wp-courseware' ),
					'ignore'        => true,
					'course_id'     => ! empty( $this->course ) ? $this->course->get_id() : 0,
					'instructor_id' => ! empty( $this->course ) ? $this->course->get_course_instructor() : 0,
				),
			),
			$this
		);
	}

	/**
	 * Get Tools Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array The array of tools fields.
	 */
	public function get_tools_fields() {
		/**
		 * Filter: Course Tools Fields.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course tools fields.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course tools fields.
		 */
		return apply_filters(
			'wpcw_course_tools_fields',
			array(
				'course_bulk_grant_access'        => array(
					'id'        => 'course_bulk_grant_access',
					'name'      => 'course_bulk_grant_access',
					'type'      => 'bulkgrantaccess',
					'label'     => esc_html__( 'Bulk Grant Access', 'wp-courseware' ),
					'desc'      => esc_html__( 'You can use the buttons above to grant all users access to this course. Depending on how many users you have, this may be a slow process.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Bulk Grant Access', 'wp-courseware' ),
					'ignore'    => true,
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
				),
				'course_reset_progress'           => array(
					'id'        => 'course_reset_progress',
					'name'      => 'course_reset_progress',
					'type'      => 'resetprogress',
					'label'     => esc_html__( 'Reset Progress', 'wp-courseware' ),
					'desc'      => esc_html__( 'This button will reset all users who can access this course back to the beginning of the course. This deletes all grade data too.', 'wp-courseware' ),
					'tip'       => esc_html__( 'Reset Progress', 'wp-courseware' ),
					'ignore'    => true,
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
				),
				'course_access_expiration'        => array(
					'id'        => 'course_access_expiration',
					'name'      => 'course_access_expiration',
					'type'      => 'select',
					'label'     => esc_html__( 'Course Access Expiration', 'wp-courseware' ),
					'tip'       => esc_html__( 'Course Access Expiration', 'wp-courseware' ),
					'default'   => 'none',
					'options'   => array(
						'none'              => __( '--No course access expiration--', 'wp-courseware' ),
						'specific_date'     => __( 'Access to this course should expire on a specific date for all students.', 'wp-courseware' ),
						'specific_interval' => __( 'Access to this course should expire at a specific interval from a student’s unique enrollment date.', 'wp-courseware' ),
					),
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
				),
				'cae_specific_date'               => array(
					'id'        => 'cae_specific_date',
					'name'      => 'cae_specific_date',
					'type'      => 'text',
					'before'    => '<span>Choose the date on which access to this course should expires for all students:</span>',
					'after'     => '<input type="text" id="cae-specific-date-clone" class="" placeholder="--Choose Date--" autocomplete="off">',
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
					'condition' => array(
						'field' => 'course_access_expiration',
						'value' => array( 'specific_date' ),
					),
				),
				'cae_interval_date_int_expire'    => array(
					'id'        => 'cae_interval_date_int_expire',
					'name'      => 'cae_interval_date_int_expire',
					'type'      => 'number',
					'before'    => "<span>Select the interval after a student's unique enrollment date at which course access should expire:</span>",
					'default'   => 1,
					'min'       => 1,
					'size'      => 'small',
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
					'condition' => array(
						'field' => 'course_access_expiration',
						'value' => array( 'specific_interval' ),
					),
				),
				'cae_interval_date_string_expire' => array(
					'id'        => 'cae_interval_date_string_expire',
					'name'      => 'cae_interval_date_string_expire',
					'type'      => 'select',
					'default'   => 'hour',
					'options'   => array(
						'hour'  => __( 'Hour(s)', 'wp-courseware' ),
						'day'   => __( 'Day(s)', 'wp-courseware' ),
						'week'  => __( 'Week(s)', 'wp-courseware' ),
						'month' => __( 'Month(s)', 'wp-courseware' ),
						'year'  => __( 'Year(s)', 'wp-courseware' ),
					),
					'course_id' => ! empty( $this->course ) ? $this->course->get_id() : 0,
					'condition' => array(
						'field' => 'course_access_expiration',
						'value' => array( 'specific_interval' ),
					),
				),
				'cae_reset_on_course_expire'      => array(
					'id'        => 'cae_reset_on_course_expire',
					'name'      => 'cae_reset_on_course_expire',
					'type'      => 'radio',
					'before'    => '<span>Should course progress be reset when course access expires?</span>',
					'default'   => 'no',
					'options'   => array(
						'yes' => __( 'Yes', 'wp-courseware' ),
						'no'  => __( 'No', 'wp-courseware' ),
					),
					'condition' => array(
						'field' => 'course_access_expiration',
						'value' => array( 'specific_date', 'specific_interval' ),
					),
				),

			),
			$this
		);
	}

	/**
	 * Get All Fields.
	 *
	 * @since 4.4.0
	 *
	 * @return array $fields The fields.
	 */
	public function get_all_fields() {
		$fields = array();
		$tabs   = $this->get_tabs();

		if ( empty( $tabs ) ) {
			return $fields;
		}

		// Go 3 Levels Deep.
		foreach ( $tabs as $tab ) {
			if ( ! empty( $tab['fields'] ) ) {
				$fields = array_merge( $fields, $tab['fields'] );
				foreach ( $tab['fields'] as $field ) {
					if ( ! empty( $field['fields'] ) ) {
						$fields = array_merge( $fields, $field['fields'] );
						foreach ( $field['fields'] as $sub_field ) {
							if ( ! empty( $sub_field['fields'] ) ) {
								$fields = array_merge( $fields, $sub_field['fields'] );
							}
						}
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Get Course Field Value.
	 *
	 * @since 4.4.0
	 *
	 * @param array $field The field data.
	 *
	 * @return mixed.
	 */
	public function get_course_field_value( $field ) {
		$post_id = $this->get_post_id();

		$field_value = $this->fields->get_field_value( $field );
		$field_name  = $this->fields->get_field_name( $field );

		$course_status = $this->course->get_course_status();

		if ( 'auto-draft' === $course_status ) {
			return $field_value;
		}

		if ( is_callable( array( $this->course, "get_{$field_name}" ) ) ) {
			$field_value = $this->course->{"get_{$field_name}"}();
		}

		if ( is_null( $field_value ) || ! $field_value ) {
			$field_value = $this->course->get_meta( $field_name, true );

			if ( ! $field_value ) {
				$field_value = get_post_meta( $post_id, $field_name, true );
			}
		}

		return $field_value;
	}

	/**
	 * Course Field Value.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $field The field data.
	 * @param Fields $fields The fields object.
	 *
	 * @return array $field The field data.
	 */
	public function course_field_value( $field, $fields ) {
		if ( ! $this->is_page_request() ) {
			return $field;
		}

		$field['value'] = $this->get_course_field_value( $field );

		return $field;
	}

	/**
	 * Field Views.
	 *
	 * @since 4.4.0
	 */
	public function field_views() {
		$views  = array();
		$fields = $this->get_all_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$type           = $this->fields->get_field_type( $field );
				$views[ $type ] = $this->fields->get_field_views( $field );
			}
		}

		if ( ! empty( $views ) ) {
			foreach ( $views as $view ) {
				echo $view;
			}
		}
	}

	/** Tab Methods ---------------------------------------------- */

	/**
	 * Get Page Course Tabs.
	 *
	 * @since 4.4.0
	 *
	 * @return mixed|void
	 */
	public function get_tabs() {
		/**
		 * Filter: Course Tabs.
		 *
		 * @since 4.6.1
		 *
		 * @param array The course tabs.
		 * @param Page_Course $this The course page object.
		 *
		 * @return array The course tabs.
		 */
		return apply_filters(
			'wpcw_course_tabs',
			array(
				'general'       => array(
					'default' => true,
					'id'      => 'description',
					'label'   => esc_html__( 'General', 'wp-courseware' ),
					'icon'    => '<i class="wpcw-fas wpcw-fa-cog"></i>',
					'fields'  => $this->get_general_fields(),
				),
				'description'   => array(
					'id'     => 'description',
					'label'  => esc_html__( 'Description', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-edit"></i>',
					'fields' => $this->get_description_fields(),
				),
				'payments'      => array(
					'id'     => 'payments',
					'label'  => esc_html__( 'Payments', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-shopping-cart"></i>',
					'fields' => $this->get_payments_fields(),
				),
				'messages'      => array(
					'id'     => 'messages',
					'label'  => esc_html__( 'Messages', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-comment-alt"></i>',
					'fields' => $this->get_messages_fields(),
				),
				'emails'        => array(
					'id'     => 'emails',
					'label'  => esc_html__( 'Emails', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-envelope"></i>',
					'fields' => $this->get_emails_fields(),
				),
				'certificates'  => array(
					'id'     => 'certificates',
					'label'  => esc_html__( 'Certificates', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-certificate"></i>',
					'fields' => $this->get_certificates_fields(),
				),
				'prerequisites' => array(
					'id'     => 'prerequisites',
					'label'  => esc_html__( 'Prerequisites', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-clipboard-check"></i>',
					'fields' => $this->get_prerequisite_fields(),
				),
				'bundles'       => array(
					'id'     => 'bundles',
					'label'  => esc_html__( 'Bundles', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-folder"></i>',
					'fields' => $this->get_course_bundles_fields(),
				),
				'instructor'    => array(
					'id'     => 'instructor',
					'label'  => esc_html__( 'Instructor', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-chalkboard-teacher"></i>',
					'fields' => $this->get_instructor_fields(),
				),
				'tools'         => array(
					'id'     => 'tools',
					'label'  => esc_html__( 'Tools', 'wp-courseware' ),
					'icon'   => '<i class="wpcw-fas wpcw-fa-wrench"></i>',
					'fields' => $this->get_tools_fields(),
				),
			),
			$this
		);
	}

	/**
	 * Disable Tabs.
	 *
	 * @since 4.5.2
	 */
	public function disable_tabs( $tabs ) {
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			unset( $tabs['instructor'] );
		}

		return $tabs;
	}

	/**
	 * Display Tabs.
	 *
	 * @since 4.4.0
	 */
	public function display_tabs() {
		if ( empty( $this->tabs ) ) {
			return;
		}

		echo '<div class="wpcw-tabs">';
		echo '<input id="wpcw-tabs-active-tab" type="hidden" name="wpcw_tabs_active_tab" value="' . $this->get_active_tab() . '">';
		wp_nonce_field( 'wpcw_course_save', 'wpcw_course_save_nonce' );
		$this->get_tabs_navigation();
		$this->get_tab_content();
		echo '</div>';
	}

	/**
	 * Get Tabs Navigation.
	 *
	 * @since 4.4.0
	 *
	 * @param array $tabs The tabs that should be displayed.
	 */
	public function get_tabs_navigation() {
		if ( empty( $this->tabs ) ) {
			return;
		}
		?>
		<ul role="tablist" class="wpcw-tabs-nav">
			<?php foreach ( $this->tabs as $tab_id => $tab ) { ?>
				<li role="presentation"
					id="wpcw-tab-<?php echo esc_attr( $tab_id ); ?>"
					data-id="<?php echo esc_attr( $tab_id ); ?>"
					data-default="<?php echo $this->is_active_tab( $tab_id ) ? 'yes' : 'no'; ?>"
					:aria-selected="activeTab === '<?php echo esc_attr( $tab_id ); ?>' ? true : false"
					class="wpcw-tab-title"
					:class="{ 'is-active' : activeTab === '<?php echo esc_attr( $tab_id ); ?>' }">
					<a aria-controls="#<?php echo esc_attr( $tab_id ); ?>"
					   id="wpcw-tab-link-<?php echo esc_attr( $tab_id ); ?>"
					   href="#<?php echo esc_attr( $tab_id ); ?>"
					   class="wpcw-tab"
					   @click.prevent="selectTab( '<?php echo esc_attr( $tab_id ); ?>', $event )">
						<?php echo $this->get_tab_icon( $tab ); ?><span class="wpcw-tab-label"><?php echo $this->get_tab_label( $tab ); ?></span>
					</a>
				</li>
			<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Get Tab Content.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_content() {
		if ( empty( $this->tabs ) ) {
			return;
		}
		?>
		<div class="wpcw-tabs-panels">
			<?php
			foreach ( $this->tabs as $tab_id => $tab ) {
				$fields = ! empty( $tab['fields'] ) ? $tab['fields'] : array();
				?>
				<section v-show="'<?php echo esc_attr( $tab_id ); ?>' === activeTab"
						 role="tabpanel"
						 id="<?php echo esc_attr( $tab_id ); ?>"
						 class="wpcw-tab-panel wpcw-tab-panel-<?php echo esc_attr( $tab_id ); ?>"
						 :class="{ 'is-active' : activeTab === '<?php echo esc_attr( $tab_id ); ?>' }">
					<?php if ( ! empty( $fields ) ) { ?>
						<div class="wpcw-metabox-fields">
							<?php foreach ( $fields as $field ) { ?>
								<?php echo $this->fields->render_field( $field ); ?>
							<?php } ?>
						</div>
					<?php } ?>
				</section>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Get Tab Id.
	 *
	 * @since 4.4.0
	 *
	 * @param array $tab The tab data.
	 *
	 * @return string The tab id.
	 */
	public function get_tab_id( $tab ) {
		return isset( $tab['id'] ) ? wpcw_sanitize_key( $tab['id'] ) : '';
	}

	/**
	 * Get Tab Icon.
	 *
	 * @since 4.4.0
	 *
	 * @param string $tab The tab icon.
	 */
	public function get_tab_icon( $tab ) {
		$default_icon = ( isset( $tab['icon'] ) && 'disabled' === $tab['icon'] ) ? '' : '<i class="wpcw-fas wpcw-fa-tasks left"></i>';

		return apply_filters( 'wpcw_course_tab_icon', ( isset( $tab['icon'] ) && 'disabled' !== $tab['icon'] ) ? wp_kses_post( $tab['icon'] ) : $default_icon, $tab );
	}

	/**
	 * Get Active Tab.
	 *
	 * @since 4.4.0
	 *
	 * @return string The active tab slug.
	 */
	public function get_active_tab() {
		return isset( $this->tab['slug'] ) ? $this->tab['slug'] : '';
	}

	/**
	 * Is Active Tab?
	 *
	 * @since 4.4.0
	 *
	 * @param string $slug The current tab slug.
	 *
	 * @return string The active class or blank.
	 */
	public function is_active_tab( $slug ) {
		return ( $slug === $this->get_active_tab() ) ? true : false;
	}

	/**
	 * Set Active Tab.
	 *
	 * @since 4.4.0
	 *
	 * @param string $location The location URL.
	 * @param int    $post_id The post ID.
	 *
	 * @return string The url after redirect.
	 */
	public function set_active_tab( $location, $post_id ) {
		if ( $this->post_type === get_post_type( $post_id ) && ! empty( $_POST['wpcw_tabs_active_tab'] ) ) {
			$location = add_query_arg( 'tab', wpcw_clean( $_POST['wpcw_tabs_active_tab'] ), $location );
		}

		return $location;
	}

	/** Page Methods ---------------------------------------------- */

	/**
	 * Page Id.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'wpcw-course';
	}

	/**
	 * Get Course Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return ( $this->course && $this->course instanceof Course ) ? $this->get_edit_url() : $this->get_add_new_url();
	}

	/**
	 * Get Course Page Url Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The admin url.
	 */
	public function get_url() {
		return admin_url( $this->get_slug() );
	}

	/**
	 * Get Edit Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The edit course url.
	 */
	public function get_edit_url() {
		return esc_url(
			add_query_arg(
				array(
					'post'   => $this->course->get_course_post_id(),
					'action' => 'edit',
				),
				'post.php'
			)
		);
	}

	/**
	 * Get New Url.
	 *
	 * @since 4.4.0
	 *
	 * @return string The new course url.
	 */
	public function get_add_new_url() {
		return esc_url( add_query_arg( array( 'post_type' => $this->post_type ), 'post-new.php' ) );
	}

	/**
	 * Is Page Request?
	 *
	 * Checks if we are on the current page.
	 *
	 * @since 4.4.0
	 *
	 * @return bool True if is valid request, false otherwise.
	 */
	protected function is_page_request() {
		global $typenow;

		if ( ! $typenow || $this->post_type !== $typenow ) {
			return false;
		}

		return true;
	}

	/**
	 * Get From Email.
	 *
	 * @since 4.4.0
	 *
	 * @return string The from email address.
	 */
	protected function get_from_email() {
		$admin_email  = get_bloginfo( 'admin_email' );
		$current_user = wp_get_current_user();

		return ( $current_user->user_email != $admin_email ) ? $current_user->user_email : $admin_email;
	}

	/**
	 * Get To Email.
	 *
	 * @since 4.4.0
	 *
	 * @return string The to email address.
	 */
	protected function get_to_email() {
		$admin_email  = get_bloginfo( 'admin_email' );
		$current_user = wp_get_current_user();

		return ( $current_user->user_email != $admin_email ) ? $current_user->user_email : $admin_email;
	}

	/**
	 * Page - Highlight Submenu.
	 *
	 * @since 4.4.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = $this->admin->get_slug();
		$submenu_file = $this->get_slug();
	}

	/**
	 * Add Single Action Buttons.
	 *
	 * @since 4.4.0
	 */
	public function add_action_buttons() {
		global $current_screen;

		if ( $this->post_type !== $current_screen->post_type ) {
			return;
		}

		$action_buttons = $this->get_action_buttons();

		if ( empty( $action_buttons ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			( function( $ ) {
				$( document ).ready( function() {
					$( '<?php echo $action_buttons; ?>' ).insertAfter( '.wrap a.page-title-action' );
				} );
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Add New Action Buttons.
	 *
	 * @since 4.4.0
	 */
	public function add_new_action_buttons() {
		global $current_screen;

		if ( $this->post_type !== $current_screen->post_type ) {
			return;
		}

		$action_buttons = $this->get_action_buttons();

		if ( empty( $action_buttons ) ) {
			return;
		}

		$action_buttons = sprintf( '<span class="wpcw-single-action-buttons" style="display: inline-block;margin-left: 5px;">%s</span>', $action_buttons );
		?>
		<script type="text/javascript">
			( function( $ ) {
				$( document ).ready( function() {
					$( '<?php echo $action_buttons; ?>' ).insertAfter( '.wrap h1.wp-heading-inline' );
				} );
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Get Sinle Course Page Action Buttons.
	 *
	 * @since 4.4.0
	 *
	 * @return string The single action buttons.
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			esc_url( add_query_arg( array( 'post_type' => $this->post_type ), 'edit.php' ) ),
			esc_html__( 'Back to Courses', 'wp-courseware' )
		);

		if ( ! empty( $this->course ) ) {
			$actions .= sprintf(
				'<a class="page-title-action" href="%s">%s</a>',
				$this->course->get_classroom_url(),
				esc_html__( 'View Classroom', 'wp-courseware' )
			);

			$actions .= sprintf(
				'<a class="page-title-action" href="%s">%s</a>',
				$this->course->get_gradebook_url(),
				esc_html__( 'View Gradebook', 'wp-courseware' )
			);
		}

		return apply_filters( 'wpcw_admin_page_courses_single_action_buttons', $actions );
	}
}
