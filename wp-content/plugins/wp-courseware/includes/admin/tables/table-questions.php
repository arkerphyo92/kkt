<?php
/**
 * WP Courseware Quesitons Table.
 *
 * @package WPCW
 * @subpackage Admin\Tables
 * @since 4.2.0
 */
namespace WPCW\Admin\Tables;

use WPCW\Admin\Pages\Page_Questions;
use WPCW\Models\Question;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Questions Table.
 *
 * @since 4.2.0
 */
class Table_Questions extends Table {

	/**
	 * @var Page_Questions The Questions Admin Page.
	 * @since 4.2.0
	 */
	protected $page;

	/**
	 * Questions Table Constructor.
	 *
	 * @since 4.2.0
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'question',
			'plural'   => 'questions',
		) );

		parent::__construct( $args );

		$this->process_actions();

		$this->total = $this->get_questions_count();
	}

	/**
	 * Get Table Columns
	 *
	 * @since 4.2.0
	 *
	 * @return array $columns An array of columns displayed in the table.
	 */
	public function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'title'   => esc_html__( 'Question Title', 'wp-courseware' ),
			'type'    => esc_html__( 'Question Type', 'wp-courseware' ),
			'quizzes' => esc_html__( 'Associated Quizzes', 'wp-courseware' ),
			'tags'    => esc_html__( 'Question Tags', 'wp-courseware' ),
		);

		return $columns;
	}

	/**
	 * Get Sortable Columns.
	 *
	 * @since 4.2.0
	 *
	 * @return array $columns An array of sortable displayed in the table.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title' => array( 'title', false ),
			'type'  => array( 'type', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Column Default.
	 *
	 * Render a column when no column specific method exists.
	 *
	 * @param Question $question
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $question, $column_name ) {
		switch ( $column_name ) {
			default:
				$value = isset( $question->$column_name ) ? $question->$column_name : '';
				break;
		}

		return $value;
	}

	/**
	 * Column Checkbox.
	 *
	 * @since 4.2.0
	 *
	 * @param Question $question The question object.
	 *
	 * @return string Displays a checkbox.
	 */
	public function column_cb( $question ) {
		return '<input type="checkbox" name="question_id[]" value="' . absint( $question->get_question_id() ) . '" />';
	}

	/**
	 * Column ID.
	 *
	 * @since 4.2.0
	 *
	 * @param Question $question The question object.
	 *
	 * @return int
	 */
	public function column_id( $question ) {
		return $question->get_question_id();
	}

	/**
	 * Column Title.
	 *
	 * @since 4.2.0
	 *
	 * @param Question $question
	 *
	 * @return string
	 */
	public function column_title( $question ) {
		$row_actions = array();

		$question_id = $question->get_question_id();

		$base_query_args = array(
			'page'        => $this->page->get_slug(),
			'question_id' => $question_id,
		);

		$title = $question->get_question_title();

		$edit_question_url   = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'page' => 'WPCW_showPage_ModifyQuestion' ) ), admin_url( 'admin.php' ) ) );
		$delete_question_url = esc_url_raw( add_query_arg( array_merge( $base_query_args, array( 'action' => 'delete' ) ), admin_url( 'admin.php' ) ) );

		$value = sprintf( '<a class="row-title" href="%s">%s</a>', $edit_question_url, $title );

		$row_actions['question_id'] = sprintf( __( 'ID: %s', 'wp-courseware' ), $question_id );

		$row_actions['edit_question'] = $this->get_row_action_link(
			esc_html__( 'Edit', 'wp-courseware' ),
			array(),
			array( 'base_uri' => $edit_question_url )
		);

		$row_actions['delete'] = $this->get_row_action_link(
			esc_html__( 'Delete', 'wp-courseware' ),
			array(),
			array(
				'base_uri' => $delete_question_url,
				'class'    => 'wpcw_delete_item',
				'title'    => __( "Are you sure you want to delete the this question?\n\nThis CANNOT be undone!", 'wp-courseware' ),
				'nonce'    => 'question-nonce',
			)
		);

		$value .= sprintf( '<div class="row-actions">%s</div>', $this->row_actions( $row_actions, true ) );

		return $value;
	}

	/**
	 * Column Type.
	 *
	 * @since 4.2.0
	 *
	 * @param Question $question The question object.
	 *
	 * @return int
	 */
	public function column_type( $question ) {
		return wpcw()->questions->get_question_type_name( $question->get_question_type() );
	}

	/**
	 * Column Quizzes.
	 *
	 * @since 4.2.0
	 *
	 * @param Question $question The question object.
	 *
	 * @return int
	 */
	public function column_quizzes( $question ) {
		return $question->get_question_usage_count();
	}

	/**
	 * Column Tags.
	 *
	 * @since 4.2.0
	 *
	 * @param Question $question The question object.
	 *
	 * @return int
	 */
	public function column_tags( $question ) {
		$question_id = $question->get_question_id();

		$tags = WPCW_questions_tags_getTagsForQuestion( $question_id );

		if ( $tags ) {
			return sprintf(
				'<span class="wpcw_quiz_details_question_tags" data-questionid="%d" id="wpcw_quiz_details_question_tags_%d">%s</span>',
				$question_id,
				$question_id,
				WPCW_questions_tags_render( $question_id, $tags )
			);
		}

		return esc_html__( 'N/A', 'wp-courseware' );
	}

	/**
	 * Get Bulk Actions.
	 *
	 * @since 4.2.0
	 *
	 * @return array $actions The bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => esc_html__( 'Delete', 'wp-courseware' ),
		);

		return $actions;
	}

	/**
	 * Process Actions.
	 *
	 * @since 4.2.0
	 */
	public function process_actions() {
		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-questions' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'question-nonce' ) ) {
			return;
		}

		if ( ! current_user_can( $this->page->get_capability() ) ) {
			return;
		}

		// Current ACtion.
		$current_action = $this->current_action();

		// Change Current Action to hanle tag actions.
		if ( empty( $current_action ) && ! empty( $_GET['wpcw_bulk_action_actions'] ) ) {
			$current_action = 'tags';
		}

		// Filter Action.
		if ( empty( $current_action ) && ! empty( $_GET['filter_action'] ) ) {
			$current_action = esc_attr( $_GET['filter_action'] );
		}

		// Search Action.
		if ( empty( $current_action ) && ! empty( $_GET['s'] ) ) {
			$current_action = 'search';
		}

		// Process Actions.
		switch ( $current_action ) {
			case 'bulk-delete' :
				$this->process_action_bulk_delete();
				break;
			case 'delete' :
				$this->process_action_delete();
				break;
			case 'tags' :
				$this->process_action_tags();
				break;
			case 'filter-question-tags' :
				$this->process_action_filter_question_tags();
				break;
			case 'search' :
				$this->process_action_search();
				break;
		}

		/**
		 * Action: Process Question Actions.
		 *
		 * @since 4.3.0
		 */
		do_action( 'wpcw_questions_table_process_action', $current_action, $this );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Delete Question.
	 *
	 * @since 4.3.0
	 */
	public function process_action_delete() {
		$question_id = wpcw_get_var( 'question_id' );

		if ( $question = wpcw()->questions->delete_question( $question_id ) ) {
			$message = sprintf( __( 'Question <strong>#%s</strong> deleted successfully.', 'wp-courseware' ), $question->get_question_id() );
			wpcw_add_admin_notice_success( $message );
		}

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Bulk Delete.
	 *
	 * @since 4.3.0
	 */
	public function process_action_bulk_delete() {
		$ids = isset( $_GET['question_id'] ) ? $_GET['question_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) || ( count( $ids ) === 1 && ! $ids[0] ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			wpcw()->questions->delete_question( $id );
		}

		wpcw_add_admin_notice_success( esc_html__( 'Questions deleted successfully!', 'wp-courseware' ) );

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Tags.
	 *
	 * @since 4.3.0
	 */
	public function process_action_tags() {
		wpcw()->questions->process_action_tags();

		wp_safe_redirect( $this->page->get_url() );
		exit;
	}

	/**
	 * Process Action: Filter Question Tags.
	 *
	 * @since 4.3.0
	 */
	public function process_action_filter_question_tags() {
		$question_tag = ! empty( $_GET['question_tag'] ) ? esc_attr( $_GET['question_tag'] ) : '';

		$url = $this->page->get_url();

		if ( ! empty( $question_tag ) ) {
			$url = add_query_arg( array( 'question_tag' => $question_tag ), $url );
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 4.2.0
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( 'bottom' === $which ) { ?>
				<?php $this->tags_bulk_action_dropdown(); ?>
			<?php } ?>

			<?php if ( $this->has_items() ): ?>
                <div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
                </div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
            <br class="clear"/>
        </div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 4.2.0
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>
        <div class="alignleft actions"><?php
		ob_start();

		$this->tags_dropdown();

		$output = ob_get_clean();

		if ( ! empty( $output ) ) {
			echo $output;
			printf(
				'<button class="button" id="quizzes-query-submit" name="filter_action" value="filter-question-tags" type="submit"><i class="wpcw-fa wpcw-fa-filter" aria-hidden="true"></i> %s</button>',
				esc_html__( 'Filter', 'wp-courseware' )
			);
			printf(
				'<a class="button tablenav-button" href="%s"><i class="wpcw-fas wpcw-fa-retweet"></i> %s</a>',
				$this->page->get_url(),
				esc_html__( 'Reset', 'wp-courseware' )
			);
		}
		?></div><?php
	}

	/**
	 * Displays a Tags drop-down for filtering on the Questions Table.
	 *
	 * @since 4.2.0
	 */
	protected function tags_dropdown() {
		/**
		 * Filters whether to remove the 'Filters' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool $disable Whether to disable the categories drop-down. Default false.
		 */
		if ( false !== apply_filters( 'wpcw_questions_disable_filter_by_tags_dropdown', false ) ) {
			return;
		}

		/**
		 * Output the Courses Dropdown.
		 *
		 * @since 4.2.0
		 */
		echo wpcw()->questions->get_tags_filter_by_dropdown();
	}

	/**
	 * Displays a Tags Bulk drop-down for filtering on the Questions Table.
	 *
	 * @since 4.2.0
	 */
	protected function tags_bulk_action_dropdown() {
		/**
		 * Filters whether to remove the 'Filters' drop-down from the post list table.
		 *
		 * @since 4.2.0
		 *
		 * @param bool $disable Whether to disable the categories drop-down. Default false.
		 */
		if ( false !== apply_filters( 'wpcw_questions_disable_tags_bulk_action_dropdown', false ) ) {
			return;
		}

		/**
		 * Output the Tags Bulk Action Form Dropdown.
		 *
		 * @since 4.2.0
		 */
		echo WPCW_showPage_QuestionPool_actionForm( 'new' );
	}

	/**
	 * Get Views.
	 *
	 * @since 4.2.0
	 *
	 * @return array
	 */
	protected function get_views() {
		$current = isset( $_GET['status'] ) ? $_GET['status'] : '';

		$total = sprintf( '&nbsp;<span class="count">(%s)</span>', $this->total );

		$views = array(
			'all' => sprintf(
				'<a href="%s" %s>%s</a>',
				esc_url( remove_query_arg( 'status', $this->page->get_url() ) ),
				( 'all' === $current || '' === $current ) ? ' class="current"' : '',
				esc_html__( 'All', 'wp-courseware' ) . $total
			),
		);

		return $views;
	}

	/**
	 * Get Question Query Args.
	 *
	 * @since 4.3.0
	 *
	 * @return array
	 */
	public function get_questions_args() {
		$page    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$search  = isset( $_GET['s'] ) ? $_GET['s'] : '';
		$order   = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'question_id';
		$tag     = isset( $_GET['question_tag'] ) ? $_GET['question_tag'] : '';
		$author  = 0;

		// Check if admin
		if ( ! current_user_can( 'manage_wpcw_settings' ) ) {
			$author = get_current_user_id();
		}

		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$args = array(
			'number'          => $per_page,
			'offset'          => $per_page * ( $page - 1 ),
			'question_author' => absint( $author ),
			'question_tag'    => absint( $tag ),
			'search'          => $search,
			'orderby'         => sanitize_text_field( $orderby ),
			'order'           => sanitize_text_field( $order ),
		);

		return $args;
	}

	/**
	 * Get Questions Count.
	 *
	 * @since 4.2.0
	 *
	 * @return int The questions count.
	 */
	public function get_questions_count() {
		$this->count = wpcw()->questions->get_questions_count( $this->get_questions_args() );

		return $this->count;
	}

	/**
	 * Get Questions Data.
	 *
	 * @since 4.2.0
	 *
	 * @return array $data Questions data.
	 */
	public function get_questions_data() {
		return wpcw()->questions->get_questions( $this->get_questions_args() );
	}

	/**
	 * Prepare Items.
	 *
	 * @since 4.2.0
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( $this->per_page_option, $this->per_page );

		$data = $this->get_questions_data();

		$current_page = $this->get_pagenum();

		$total_items = $this->count;

		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	/**
	 * Text displayed when no user data is available
	 *
	 * @since 4.2.0
	 */
	public function no_items() {
		esc_html_e( 'Sorry, no questions have been created.', 'wp-courseware' );
	}
}
