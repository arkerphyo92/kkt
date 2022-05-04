<?php
/**
 * WP Courseware Units Page.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.1.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Models\Course;
use WPCW\Models\Module;
use WPCW\Models\Unit;
use WP_Query;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Page_Units.
 *
 * @since 4.1.0
 */
class Page_Units extends Page {

	/**
	 * @var string Post Type Slug.
	 * @since 4.4.0
	 */
	protected $post_type = 'course_unit';

	/**
	 * Units - Setup.
	 *
	 * @since 4.1.0
	 */
	protected function setup() {
		add_action( 'admin_head', array( $this, 'hide_post_type_menu' ) );
		add_action( 'admin_head', array( $this, 'hightlight_submenu_add_edit' ) );
		add_action( 'admin_head', array( $this, 'add_icon_to_title' ) );

		// Action Buttons
		add_action( 'admin_head-edit.php', array( $this, 'add_action_buttons' ) );
		add_action( 'admin_head-post.php', array( $this, 'add_single_action_buttons' ) );
		add_action( 'admin_head-post-new.php', array( $this, 'add_new_action_buttons' ) );

		// Course Columns
		add_filter( 'manage_edit-course_unit_columns', array( $this, 'custom_columns' ) );
		add_filter( 'manage_edit-course_unit_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'manage_course_unit_posts_custom_column', array( $this, 'manage_custom_columns' ), 10, 2 );

		// Unit Filters
		add_action( 'manage_posts_extra_tablenav', array( $this, 'filters' ) );
		add_filter( 'pre_get_posts', array( $this, 'filter_by_course_query' ) );
		add_filter( 'pre_get_posts', array( $this, 'filter_by_module_query' ) );
		add_filter( 'posts_orderby', array( $this, 'filter_orderby_column' ), 10, 2 );
	}

	/**
	 * Hide Course Post Type Menu.
	 *
	 * @since 4.4.0
	 */
	public function hide_post_type_menu() {
		if ( empty( $this->admin ) ) {
			return;
		}

		$this->admin->hide_top_menu( 'edit.php?post_type=' . $this->post_type );
	}

	/**
	 * Highlight Submenu on Post Type Add / Edit
	 *
	 * @since 4.1.0
	 */
	public function hightlight_submenu_add_edit() {
		global $current_screen, $parent_file, $submenu_file;

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( $current_screen->post_type !== 'course_unit' ) {
			return;
		}

		$parent_file  = $this->admin->get_slug();
		$submenu_file = $this->get_slug();
	}

	/**
	 * Add Icon to Title.
	 *
	 * @since 4.3.0
	 */
	public function add_icon_to_title() {
		global $current_screen;

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( $current_screen->post_type !== 'course_unit' ) {
			return;
		}

		echo
			'<style type="text/css">
                .wrap h1.wp-heading-inline {
                    position: relative;
                    padding-top: 4px;
                    padding-left: 50px;
                }
                .wrap h1.wp-heading-inline:before {
                    background-image: url("' . wpcw_image_file( 'wp-courseware-icon.svg' ) . '");
                    background-size: 40px 40px;
                    content: "";
                    display: inline-block;
                    position: absolute;
                    top: -2px;
                    left: 0;
                    width: 40px;
                    height: 40px;
                }
            </style>';
	}

	/**
	 * Add Action Buttons.
	 *
	 * @since 4.3.0
	 */
	public function add_action_buttons() {
		global $current_screen;

		if ( 'course_unit' !== $current_screen->post_type ) {
			return;
		}

		$action_buttons = $this->get_action_buttons();

		if ( empty( $action_buttons ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			( function ( $ ) {
				$( document ).ready( function () {
					$( '<?php echo $action_buttons; ?>' ).insertAfter( '.wrap a.page-title-action' );
				} )
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Add Single Action Buttons.
	 *
	 * @since 4.3.0
	 */
	public function add_single_action_buttons() {
		global $current_screen;

		if ( 'course_unit' !== $current_screen->post_type ) {
			return;
		}

		$single_action_buttons = $this->get_single_action_buttons();

		if ( empty( $single_action_buttons ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			( function ( $ ) {
				$( document ).ready( function () {
					$( '<?php echo $single_action_buttons; ?>' ).insertAfter( '.wrap a.page-title-action' );
				} )
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Add New Action Buttons.
	 *
	 * @since 4.3.0
	 */
	public function add_new_action_buttons() {
		global $current_screen;

		if ( 'course_unit' !== $current_screen->post_type ) {
			return;
		}

		$single_action_buttons = $this->get_single_action_buttons();

		if ( empty( $single_action_buttons ) ) {
			return;
		}

		$single_action_buttons = sprintf( '<span class="wpcw-single-action-buttons" style="display: inline-block;margin-left: 5px;">%s</span>', $single_action_buttons );
		?>
		<script type="text/javascript">
			( function ( $ ) {
				$( document ).ready( function () {
					$( '<?php echo $single_action_buttons; ?>' ).insertAfter( '.wrap h1.wp-heading-inline' );
				} )
			} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Get Unit Page Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'page' => 'wpcw-modules' ), admin_url( 'admin.php' ) ),
			esc_html__( 'View Modules', 'wp-courseware' )
		);

		$actions .= sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			add_query_arg( array( 'post_type' => 'wpcw_course' ), admin_url( 'edit.php' ) ),
			esc_html__( 'View Courses', 'wp-courseware' )
		);

		return apply_filters( 'wpcw_admin_page_units_action_buttons', $actions );
	}

	/**
	 * Get Sinle Unit Page Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_single_action_buttons() {
		$actions = sprintf(
			'<a class="page-title-action" href="%s">%s</a>',
			$this->get_slug(),
			esc_html__( 'Back to Units', 'wp-courseware' )
		);

		return apply_filters( 'wpcw_admin_page_units_single_action_buttons', $actions );
	}

	/**
	 * Get Unit.
	 *
	 * @since 4.4.0
	 *
	 * @param int $post_id The post id.
	 *
	 * @return Unit|false The unit object of false.
	 */
	public function get_unit( $post_id = 0 ) {
		global $wp_query;

		if ( empty( $wp_query->posts ) ) {
			return false;
		}

		$post_ids       = wp_list_pluck( $wp_query->posts, 'ID' );
		$found_post_key = array_search( $post_id, $post_ids );
		$found_post     = isset( $wp_query->posts[ $found_post_key ] ) ? $wp_query->posts[ $found_post_key ] : null;

		if ( is_null( $found_post ) ) {
			return false;
		}

		return new Unit( $found_post );
	}

	/**
	 * Course Unit Custom Columns
	 *
	 * @since 4.4.0
	 *
	 * @param array $columns The array of columns.
	 *
	 * @return array $columns The array of columns.
	 */
	public function custom_columns( $columns ) {
		$custom_columns = array(
			'cb'                            => '<input type="checkbox" />',
			'title'                         => $columns['title'],
			'course'                        => esc_html__( 'Course', 'wp-courseware' ),
			'modules'                        => esc_html__( 'Module', 'wp-courseware' ),
			'unit_teaser'                   => esc_html__( 'Is Teaser?', 'wp-courseware' ),
			'taxonomy-course_unit_category' => $columns['taxonomy-course_unit_category'],
			'taxonomy-course_unit_tag'      => $columns['taxonomy-course_unit_tag'],
		);

		if ( isset( $columns['comments'] ) ) {
			$custom_columns['comments'] = $columns['comments'];
		}

		$custom_columns['date'] = $columns['date'];

		/**
		 * Filter: Unit Custom Columns.
		 *
		 * @since 4.4.0
		 *
		 * @param array $custom_columns The custom columns.
		 *
		 * @return array $custom_columns The course custom columns.
		 */
		return apply_filters( 'wpcw_unit_custom_columns', $custom_columns );
	}

	/**
	 * Course Unit Sortable Columns.
	 *
	 * @since 4.4.0
	 *
	 * @param array $sortable_columns The array of sortable columns.
	 *
	 * @param array $sortable_columns The array of sortable columns.
	 */
	public function sortable_columns( $sortable_columns ) {
		$sortable_columns['course']      = 'parent_course_id';
		$sortable_columns['modules']      = 'parent_module_id';
		$sortable_columns['unit_teaser'] = 'unit_teaser';

		/**
		 * Filter: Unit Custom Sortable Columns.
		 *
		 * @since 4.4.0
		 *
		 * @param array $sortable_columns The custom sortable columns.
		 *
		 * @return array $sortable_columns The custom sortable columns.
		 */
		return apply_filters( 'wpcw_units_custom_sortable_columns', $sortable_columns );
	}

	/**
	 * Manage Custom Columns.
	 *
	 * @since 4.4.0
	 *
	 * @param string $column The column slug string.
	 * @param int    $post_id The post id.
	 */
	public function manage_custom_columns( $column, $post_id ) {
		global $post;

		if ( $this->post_type !== $post->post_type ) {
			return $actions;
		}

		// Unit Object.
		$unit = $this->get_unit( $post_id );

		if ( $unit && $unit instanceof Unit ) {
			switch ( $column ) {
				case 'course' :
					$course = $unit->get_course();
					if ( $course->exists() ) {
						printf( '<a target="_blank" href="%s">%s</a>', $course->get_edit_url(), $course->get_course_title() );
					} else {
						echo __( 'N/A', 'wp-courseware' );
					}
					break;
				case 'modules' :
					$module = $unit->get_module();
					if ( $module->exists() ) {
						printf( '<a target="_blank" href="%s">%s</a>', $module->get_edit_url(), $module->get_module_title() );
					} else {
						echo __( 'N/A', 'wp-courseware' );
					}
					break;
				case 'unit_teaser' :
					printf(
						'<div class="unit-preview-column-label unit-preview-column-label-%s">%s</div>',
						$unit->unit_teaser ? 'yes' : 'no',
						$unit->unit_teaser ? '<span class="wpcw_checkmark"></span>' : '<span class="wpcw_circle"></span>'
					);
					break;
			}

			/**
			 * Action: Units Manage Custom Column
			 *
			 * @since 4.4.0
			 *
			 * @param Course       $course The course object.
			 * @param Page_Courses $this The page courses object.
			 */
			do_action( "wpcw_units_manage_custom_column_{$column}", $unit, $this );
		}
	}

	/**
	 * Courses Dropdown.
	 *
	 * @since 4.4.0
	 */
	public function courses_dropdown() {
		/**
		 * Filters whether to remove the 'Courses' drop-down from the post list table.
		 *
		 * @since 4.4.0
		 *
		 * @param bool $disable Whether to disable the courses drop-down. Default false.
		 */
		if ( apply_filters( 'wpcw_units_disable_filter_by_courses_dropdown', false ) ) {
			return;
		}

		echo wpcw()->courses->get_courses_filter_dropdown();
	}

	/**
	 * Modules Dropdown.
	 *
	 * @since 4.4.0
	 */
	public function modules_dropdown() {
		/**
		 * Filters whether to remove the 'Modules' drop-down from the post list table.
		 *
		 * @since 4.4.0
		 *
		 * @param bool $disable Whether to disable the modules drop-down. Default false.
		 */
		if ( apply_filters( 'wpcw_units_disable_filter_by_modules_dropdown', false ) ) {
			return;
		}

		echo wpcw()->modules->get_modules_filter_dropdown();
	}

	/**
	 * Unit Filters.
	 *
	 * @since 4.4.0
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'
	 */
	public function filters( $which ) {
		global $typenow;

		if ( $typenow === $this->post_type ) {
			if ( 'top' !== $which ) {
				return;
			}
			?>
			<div class="alignleft actions"><?php
				ob_start();
				$this->courses_dropdown();
				$this->modules_dropdown();
				$output = ob_get_clean();

				if ( ! empty( $output ) ) {
					echo $output;
					printf( '<button class="button" id="courses-query-submit" name="filter_action" value="filter-units-by-course" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>', esc_html__( 'Filter', 'wp-courseware' ) );
					printf( '<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>', $this->get_url(), esc_html__( 'Reset', 'wp-courseware' ) );
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * Units Filter by Course Query.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Query $wp_query The WP_Query object.
	 */
	public function filter_by_course_query( $wp_query ) {
		global $pagenow, $typenow;

		if ( ! $wp_query->is_admin ) {
			return;
		}

		if ( 'edit.php' !== $pagenow || $this->post_type !== $typenow ) {
			return;
		}

		$course_id = ! empty( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : '';

		if ( empty( $course_id ) ) {
			return;
		}

		// Get Course.
		$course = new Course( absint( $course_id ) );

		if ( $course->exists() ) {
			$units         = array();
			$units_objects = $course->get_units();

			if ( $units_objects ) {
				/** @var Unit $unit_object */
				foreach ( $units_objects as $unit_object ) {
					$units[] = $unit_object->get_id();
				}
			}

			if ( empty( $units ) ) {
				$units = array( 0 );
			}

			$wp_query->set( 'post__in', $units );
		}
	}

	/**
	 * Units Filter by Module Query.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_Query $wp_query The WP_Query object.
	 */
	public function filter_by_module_query( $wp_query ) {
		global $pagenow, $typenow;

		if ( ! $wp_query->is_admin ) {
			return;
		}

		if ( 'edit.php' !== $pagenow || $this->post_type !== $typenow ) {
			return;
		}

		$module_id = ! empty( $_GET['module_id'] ) ? absint( $_GET['module_id'] ) : '';

		if ( empty( $module_id ) ) {
			return;
		}

		// Get Module.
		$module = new Module( absint( $module_id ) );

		if ( $module->exists() ) {
			$units         = array();
			$units_objects = $module->get_units();

			if ( $units_objects ) {
				/** @var Unit $unit_object */
				foreach ( $units_objects as $unit_object ) {
					$units[] = $unit_object->get_id();
				}
			}

			if ( empty( $units ) ) {
				$units = array( 0 );
			}

			$wp_query->set( 'post__in', $units );
		}
	}

	/**
	 * Filter Orderby Column
	 *
	 * @param string   $orderby The orderby string.
	 * @param WP_Query $wp_query The WP_Query object.
	 *
	 * @return string $orderby The orderby string.
	 */
	public function filter_orderby_column( $orderby, $wp_query ) {
		global $pagenow, $typenow, $wpcwdb;

		if ( ! $wp_query->is_admin ) {
			return $orderby;
		}

		if ( 'edit.php' !== $pagenow || $this->post_type !== $typenow ) {
			return $orderby;
		}

		$order    = $wp_query->get( 'order' );
		$order_by = $wp_query->get( 'orderby' );

		if ( in_array( $order_by, array( 'parent_course_id', 'parent_module_id', 'unit_teaser' ) ) ) {
			$orderby = "{$wpcwdb->units_meta}.{$order_by} {$order}";
		}

		return $orderby;
	}

	/**
	 * Get Units Page Menu Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		return esc_html__( 'Units', 'wp-courseware' );
	}

	/**
	 * Get Units Page Title.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		return esc_html__( 'Units', 'wp-courseware' );
	}

	/**
	 * Get Units Page Capability.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_capability() {
		return apply_filters( 'wpcw_admin_page_units_capability', 'view_wpcw_courses' );
	}

	/**
	 * Get Units Page Slug.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return esc_url( add_query_arg( array( 'post_type' => $this->post_type ), 'edit.php' ) );
	}

	/**
	 * Get Admin Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The admin url.
	 */
	public function get_url() {
		return admin_url( $this->get_slug() );
	}

	/**
	 * Get Units Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return null
	 */
	protected function get_callback() {
		return null;
	}

	/**
	 * Get Units Page hook.
	 */
	public function get_hook() {
		return '';
	}
}
