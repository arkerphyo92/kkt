<?php
/**
 * WP Courseware Widget - Course Progress Bar.
 *
 * Shows the current progress bar of the user in the training course.
 *
 * @package WPCW
 * @subpackage Widgets
 * @since 4.6.0
 */
namespace WPCW\Widgets;

/**
 * Widget Course Progress Bar.
 *
 * Shows the current progress bar of the user in the training course.
 *
 * @since 4.6.0
 */
class Widget_Course_Progress_Bar extends Widget {

	/**
	 * Widget_Course_Progress constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {
		parent::__construct(
			'wpcw_course_progress_bar',
			esc_html__( 'WPCW Course Progress Bar', 'wp-courseware' ),
			array(
				'classname'   => 'wpcw_course_progress_bar',
				'description' => __( 'A widget that shows the progress bar of the user using the selected training course.', 'wp-courseware' )
			),
			array(
				'width'   => 420,
				'height'  => 350,
				'id_base' => 'wpcw_course_progress_bar',
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
			'show_title'    => ( isset( $instance['option_show_course_title'] ) && $instance['option_show_course_title'] == 'on' ? true : false ),
			'show_desc'     => ( isset( $instance['option_show_course_desc'] ) && $instance['option_show_course_desc'] == 'on' ? true : false ),
			'only_on_units' => ( isset( $instance['option_show_only_on_units'] ) && $instance['option_show_only_on_units'] == 'on' ? true : false ),

			// Widget mode - helps us work out what to do when rendering the page.
			'widget_mode'   => true,
		);

		// Don't do anything if we're not on a unit page
		global $post;

		$course    = '';
		$title     = apply_filters( 'widget_title', $instance['title'] );
		$course_id = $instance['option_course'];

		$student_id = get_current_user_id();

		if ( $args['only_on_units'] && $post ) {
			if ( 'course_unit' !== get_post_type( $post->ID ) ) {
				return;
			}
		}

		if ( 'current' === $course_id && $post ) {
			if ( is_post_type_archive() ) {
				return;
			}

			// Check Course.
			if ( 'wpcw_course' === get_post_type( $post->ID ) ) {
				$course    = wpcw_get_course( $post );
				$course_id = $course->get_course_id();
			}

			// Check Unit.
			if ( 'course_unit' === get_post_type( $post->ID ) ) {
				$unit      = wpcw_get_unit( $post );
				$course_id = $unit->get_parent_course_id();
			}
		}

		if ( ! $course_id || ! $student_id ) {
			return;
		}

		$course = wpcw_get_course( absint( $course_id ) );

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		echo '<div class="wpcw_widget_progress_bar">';

		if ( $args['show_title'] ) {
			echo apply_filters( 'wpcw_widget_course_progress_bar_title', sprintf( '<h3>%s</h3>', $course->get_course_title() ) );
		}

		if ( $args['show_desc'] ) {
			echo apply_filters( 'wpcw_widget_course_progress_bar_desc', wpautop( $course->get_course_desc() ) );
		}

		echo wpcw()->students->get_student_progress_bar( $student_id, $course_id, true );

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

		$instance['title']                     = strip_tags( $new_instance['title'] );
		$instance['option_show_course_title']  = WPCW_arrays_getValue( $new_instance, 'option_show_course_title' );
		$instance['option_show_course_desc']   = WPCW_arrays_getValue( $new_instance, 'option_show_course_desc' );
		$instance['option_show_only_on_units'] = WPCW_arrays_getValue( $new_instance, 'option_show_only_on_units' );
		$instance['option_course']             = strip_tags( WPCW_arrays_getValue( $new_instance, 'option_course' ) );

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
		$option_show_course_title  = ( WPCW_arrays_getValue( $instance, 'option_show_course_title' ) == 'on' ? 'checked="checked"' : '' );
		$option_show_course_desc   = ( WPCW_arrays_getValue( $instance, 'option_show_course_desc' ) == 'on' ? 'checked="checked"' : '' );
		$option_show_only_on_units = ( WPCW_arrays_getValue( $instance, 'option_show_only_on_units' ) == 'on' ? 'checked="checked"' : '' );
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
				'current' => esc_html__( 'Show User\'s Current Course', 'wp-courseware' ),
			);

			// Blend lists together
			$mainCourseList = WPCW_courses_getCourseList();

			if ( $mainCourseList ) {
				$courseList = $courseList + $mainCourseList;
			}

			echo $this->create_dropdown( $this->get_field_name( 'option_course' ), $courseList, $option_course, $this->get_field_id( 'option_course' ) );
			?>
			<br/>
			<small><?php _e( '(Required) Choose whether to display a specific course progress bar to the user or to display the course progress bar associated with the unit that the user is currently viewing.', 'wp-courseware' ); ?></small>
		</p>

		<p>
			<b style="display: block; padding-bottom: 3px;"><label
					for="<?php echo $this->get_field_id( 'option_show_module_desc' ); ?>"><?php _e( 'More Options:', 'wp-courseware' ); ?></label></b>
			<input id="<?php echo $this->get_field_id( 'option_show_course_title' ); ?>" name="<?php echo $this->get_field_name( 'option_show_course_title' ); ?>"
			       type="checkbox" <?php echo $option_show_course_title; ?> /> <?php _e( 'Show Course Title', 'wp-courseware' ); ?><br/>
			<input id="<?php echo $this->get_field_id( 'option_show_course_desc' ); ?>" name="<?php echo $this->get_field_name( 'option_show_course_desc' ); ?>"
			       type="checkbox" <?php echo $option_show_course_desc; ?> /> <?php _e( 'Show Course Description', 'wp-courseware' ); ?><br/>

			<input id="<?php echo $this->get_field_id( 'option_show_only_on_units' ); ?>" name="<?php echo $this->get_field_name( 'option_show_only_on_units' ); ?>"
			       type="checkbox" <?php echo $option_show_only_on_units; ?> /> <?php _e( 'Only display this widget when showing a course unit.', 'wp-courseware' ); ?>
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
