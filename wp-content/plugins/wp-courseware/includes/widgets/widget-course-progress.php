<?php
/**
 * WP Courseware Widget - Course Progress.
 *
 * Shows the current progress of the user in the training course.
 *
 * @package WPCW
 * @subpackage Widgets
 * @since 4.6.0
 */
namespace WPCW\Widgets;

/**
 * Widget Course Progress.
 *
 * Shows the current progress of the user in the training course.
 *
 * @since 4.6.0
 */
class Widget_Course_Progress extends Widget {

	/**
	 * Widget_Course_Progress constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {
		parent::__construct(
			'wpcw_course_progress',
			esc_html__( 'WPCW Course Progress', 'wp-courseware' ),
			array(
				'classname'   => 'wpcw_course_progress',
				'description' => __( 'A widget that shows the current progress of the user through the selected training course.', 'wp-courseware' )
			),
			array(
				'width'   => 420,
				'height'  => 350,
				'id_base' => 'wpcw_course_progress',
			)
		);
	}

	/**
	 * Render Widget.
	 *
	 * Method that renders the course progress.
	 *
	 * @since 4.6.0
	 *
	 * @param array $args The widget args.
	 * @param array $instance The widget instance.
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		// Turn options from widget into options for getting course details.
		$args = array(
			'show_title'            => ( $instance['option_show_course_title'] == 'on' ? 'true' : 'false' ),
			'show_desc'             => ( $instance['option_show_course_desc'] == 'on' ? 'true' : 'false' ),
			'module_desc'           => ( $instance['option_show_module_desc'] == 'on' ? 'true' : 'false' ),
			'only_on_units'         => ( $instance['option_show_only_on_units'] == 'on' ? 'true' : 'false' ),

			// Handle widget showing/hiding capability.
			'show_modules_next'     => trim( $instance['option_show_modules_next'] ),
			'show_modules_previous' => trim( $instance['option_show_modules_previous'] ),
			'toggle_modules'        => trim( $instance['option_toggle_modules'] ),

			// This enables the toggle mode for the widget
			'show_toggle_col'       => true,

			// Widget mode - helps us work out what to do when rendering the page.
			'widget_mode'           => true,
		);

		// Don't do anything if we're not on a unit page
		global $post;

		if ( $args['only_on_units'] == 'true' && $post ) {
			if ( 'course_unit' != get_post_type( $post->ID ) ) {
				return;
			}
		}

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$courseID = absint( $instance['option_course'] );

		echo '<div class="wpcw_widget_progress">';
		echo WPCW_courses_renderCourseList( $courseID, $args );
		echo '</div>';

		echo $after_widget;
	}

	/**
	 * Update Widget.
	 *
	 * Method called when data is being saved for this widget.
	 *
	 * @since 4.6.0
	 *
	 * @param array $new_instance The widget new instance.
	 * @param array $old_instance The widget old instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']                    = strip_tags( $new_instance['title'] );
		$instance['option_show_course_title'] = WPCW_arrays_getValue( $new_instance, 'option_show_course_title' );
		$instance['option_show_course_desc']  = WPCW_arrays_getValue( $new_instance, 'option_show_course_desc' );
		$instance['option_show_module_desc']  = WPCW_arrays_getValue( $new_instance, 'option_show_module_desc' );
		$instance['option_course']            = strip_tags( WPCW_arrays_getValue( $new_instance, 'option_course' ) );
		$instance['option_module']            = strip_tags( WPCW_arrays_getValue( $new_instance, 'option_module' ) );

		// Module visibility Toggling
		$instance['option_toggle_modules']        = esc_attr( WPCW_arrays_getValue( $new_instance, 'option_toggle_modules' ) );
		$instance['option_show_modules_previous'] = esc_attr( WPCW_arrays_getValue( $new_instance, 'option_show_modules_previous' ) );
		$instance['option_show_modules_next']     = esc_attr( WPCW_arrays_getValue( $new_instance, 'option_show_modules_next' ) );
		$instance['option_show_only_on_units']    = esc_attr( WPCW_arrays_getValue( $new_instance, 'option_show_only_on_units' ) );

		return $instance;
	}

	/**
	 * Shows the configuration form for the widget.
	 *
	 * @param array $instance
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		// Create a default title if there is one.
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
		} else {
			$title = esc_html__( 'Current User Progress', 'wp-courseware' );
		}

		$option_course             = esc_attr( WPCW_arrays_getValue( $instance, 'option_course' ) );
		$option_module             = esc_attr( WPCW_arrays_getValue( $instance, 'option_module' ) );
		$option_show_course_title  = ( WPCW_arrays_getValue( $instance, 'option_show_course_title' ) == 'on' ? 'checked="checked"' : '' );
		$option_show_course_desc   = ( WPCW_arrays_getValue( $instance, 'option_show_course_desc' ) == 'on' ? 'checked="checked"' : '' );
		$option_show_module_desc   = ( WPCW_arrays_getValue( $instance, 'option_show_module_desc' ) == 'on' ? 'checked="checked"' : '' );
		$option_show_only_on_units = ( WPCW_arrays_getValue( $instance, 'option_show_only_on_units' ) == 'on' ? 'checked="checked"' : '' );

		// Module visibility Toggling
		$option_toggle_modules        = esc_attr( WPCW_arrays_getValue( $instance, 'option_toggle_modules' ) );
		$option_show_modules_previous = esc_attr( WPCW_arrays_getValue( $instance, 'option_show_modules_previous' ) );
		$option_show_modules_next     = esc_attr( WPCW_arrays_getValue( $instance, 'option_show_modules_next' ) );


		// Generate dropdowns for the previous/next options
		$optionsList_previous = array(
			'all'  => esc_html__( 'All previous modules', 'wp-courseware' ),
			'none' => esc_html__( 'None', 'wp-courseware' ),
		);

		for ( $i = 1; $i <= 20; $i ++ ) {
			$optionsList_previous[ $i ] = sprintf( _n( 'Show just 1 previous module', 'Show %d previous modules', $i, 'wp-courseware' ), $i );
		}

		$optionsList_next = array(
			'all'  => esc_html__( 'All subsequent modules', 'wp-courseware' ),
			'none' => esc_html__( 'None', 'wp-courseware' ),
		);

		for ( $i = 1; $i <= 20; $i ++ ) {
			$optionsList_next[ $i ] = sprintf( _n( 'Show just 1 subsequent module', 'Show %d subsequent modules', $i, 'wp-courseware' ), $i );
		}
		?>
		<p>
			<b><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wp-courseware' ); ?></label></b>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
			<small><?php _e( '(Optional) Leave blank for no title.', 'wp-courseware' ); ?></small>
		</p>

		<p>
			<b style="display: block; padding-bottom: 3px;"><label for="<?php echo $this->get_field_id( 'option_course' ); ?>"><?php _e( 'Course To Show:', 'wp-courseware' ); ?></label></b>
			<?php
			$courseList = array(
				''        => esc_html__( '-- Select a Training Course --', 'wp-courseware' ),
				'current' => esc_html__( "Show User's Current Course", 'wp-courseware' ),
			);

			// Blend lists together
			$mainCourseList = WPCW_courses_getCourseList();

			if ( $mainCourseList ) {
				$courseList = $courseList + $mainCourseList;
			}

			echo $this->create_dropdown( $this->get_field_name( 'option_course' ), $courseList, $option_course, $this->get_field_id( 'option_course' ) );
			?>
			<br/>
			<small><?php _e( '(Required) Choose whether to display a specific course to the user or to display the course associated with the unit that the user is currently viewing.', 'wp-courseware' ); ?></small>
		</p>

		<p>
			<b style="display: block; padding-bottom: 3px;"><label><?php _e( 'Show/Hide Modules:', 'wp-courseware' ); ?></label></b>
			<small><?php _e( 'Here you can control how many modules to show before and after the current module to save space.', 'wp-courseware' ); ?></small>
		</p>

		<table>
			<tr>
				<td><label for="<?php echo $this->get_field_id( 'option_show_modules_previous' ); ?>"><?php _e( 'Previous modules to display:', 'wp-courseware' ); ?></label></td>
				<td><label for="<?php echo $this->get_field_id( 'option_show_modules_next' ); ?>"><?php _e( 'Subsequent modules to display:', 'wp-courseware' ); ?></label></td>
			</tr>

			<tr>
				<td>
					<?php
					echo $this->create_dropdown( $this->get_field_name( 'option_show_modules_previous' ), $optionsList_previous, $option_show_modules_previous, $this->get_field_id( 'option_show_modules_previous' ) );
					?>
				</td>

				<td>
					<?php
					echo $this->create_dropdown( $this->get_field_name( 'option_show_modules_next' ), $optionsList_next, $option_show_modules_next, $this->get_field_id( 'option_show_modules_next' ) );
					?>
				</td>
			</tr>
		</table><br/>

		<p>
			<b style="display: block; padding-bottom: 3px;"><label
					for="<?php echo $this->get_field_id( 'option_toggle_modules' ); ?>"><?php _e( 'Expand/Contract Modules:', 'wp-courseware' ); ?></label></b>
			<?php
			echo $this->create_dropdown( $this->get_field_name( 'option_toggle_modules' ),
				array(
					'expand_all'               => esc_html__( 'Expand all modules', 'wp-courseware' ),
					'contract_all_but_current' => esc_html__( 'Contract all except current module', 'wp-courseware' ),
					'contract_all'             => esc_html__( 'Contract all modules', 'wp-courseware' ),
				),
				$option_toggle_modules, $this->get_field_id( 'option_toggle_modules' ) );
			?>
			<br/>
			<small><?php _e( 'You can save sidebar space by contracting  modules in the widget to just show the module title.', 'wp-courseware' ); ?></small>
		</p>

		<p>
			<b style="display: block; padding-bottom: 3px;"><label
					for="<?php echo $this->get_field_id( 'option_show_module_desc' ); ?>"><?php _e( 'More Options:', 'wp-courseware' ); ?></label></b>
			<input id="<?php echo $this->get_field_id( 'option_show_course_title' ); ?>" name="<?php echo $this->get_field_name( 'option_show_course_title' ); ?>"
			       type="checkbox" <?php echo $option_show_course_title; ?> /> <?php _e( 'Show Course Title', 'wp-courseware' ); ?><br/>
			<input id="<?php echo $this->get_field_id( 'option_show_course_desc' ); ?>" name="<?php echo $this->get_field_name( 'option_show_course_desc' ); ?>"
			       type="checkbox" <?php echo $option_show_course_desc; ?> /> <?php _e( 'Show Course Description', 'wp-courseware' ); ?><br/>
			<input id="<?php echo $this->get_field_id( 'option_show_module_desc' ); ?>" name="<?php echo $this->get_field_name( 'option_show_module_desc' ); ?>"
			       type="checkbox" <?php echo $option_show_module_desc; ?> /> <?php _e( 'Show Module Descriptions', 'wp-courseware' ); ?><br/>

			<input id="<?php echo $this->get_field_id( 'option_show_only_on_units' ); ?>" name="<?php echo $this->get_field_name( 'option_show_only_on_units' ); ?>"
			       type="checkbox" <?php echo $option_show_only_on_units; ?> /> <?php _e( 'Only display this widget when showing a course unit', 'wp-courseware' ); ?>
		</p>
		<?php
	}

	/**
	 * Create a dropdown box using the list of values provided and select a value if $selected is specified.
	 *
	 * @since 4.6.0
	 *
	 * @param $name String The name of the drop down box.
	 * @param $values String  The values to use for the drop down box.
	 * @param $selected String  If specified, the value of the drop down box to mark as selected.
	 * @param $cssid String The CSS ID of the drop down list.
	 * @param $cssclass String The CSS class for the drop down list.
	 *
	 * @return String The HTML for the select box.
	 */
	public function create_dropdown( $name, $values, $selected, $cssid = false, $cssclass = false ) {
		if ( ! $values ) {
			return false;
		}

		$selectedhtml = 'selected="selected" ';

		// CSS Attributes
		$css_attrib = false;
		if ( $cssid ) {
			$css_attrib = "id=\"$cssid\" ";
		}
		if ( $cssclass ) {
			$css_attrib .= "class=\"$cssclass\" ";
		}

		$html = sprintf( '<select name="%s" %s>', $name, $css_attrib );

		foreach ( $values as $key => $label ) {
			$html .= sprintf( '<option value="%s" %s>%s&nbsp;&nbsp;</option>', $key, ( $key == $selected ? $selectedhtml : '' ), $label );
		}

		return $html . '</select>';
	}
}
