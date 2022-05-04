<?php
/**
 * WP Courseware Question Model.
 *
 * @package WPCW
 * @subpackage Models
 * @since 4.3.0
 */

namespace WPCW\Models;

use WPCW\Database\DB_Questions;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Question.
 *
 * @since 4.1.0
 *
 * @property int $question_id
 * @property int $question_author
 * @property string $question_type
 * @property string $question_question
 * @property string $question_answers
 * @property string $question_data_answers
 * @property string $question_correct_answer
 * @property string $question_answer_type
 * @property string $question_answer_hint
 * @property string $question_answer_explanation
 * @property string $question_image
 * @property string $question_answer_file_types
 * @property int $question_usage_count
 * @property int $question_expanded_count
 * @property int $question_multi_random_enable
 * @property int $question_multi_random_count
 * @property int $question_multi_checkboxes_enable
 */
class Question extends Model {

	/**
	 * @var DB_Questions The questions database table.
	 * @since 4.3.0
	 */
	protected $db;

	/**
	 * @var int Question Id.
	 * @since 4.2.0
	 */
	protected $question_id;

	/**
	 * @var int Question Author.
	 * @since 4.2.0
	 */
	protected $question_author = 0;

	/**
	 * @var string Question Type.
	 * @since 4.2.0
	 */
	protected $question_type = 'multi';

	/**
	 * @var string Question Title.
	 * @since 4.2.0
	 */
	protected $question_question = '';

	/**
	 * @var string Question Answers.
	 * @since 4.2.0
	 */
	protected $question_answers = '';

	/**
	 * @var string Question Data Answers.
	 * @since 4.2.0
	 */
	protected $question_data_answers = '';

	/**
	 * @var string Question Correct Answer.
	 * @since 4.2.0
	 */
	protected $question_correct_answer = '';

	/**
	 * @var string Question Answer Type.
	 * @since 4.2.0
	 */
	protected $question_answer_type = '';

	/**
	 * @var string Question Answer Hint.
	 * @since 4.2.0
	 */
	protected $question_answer_hint = '';

	/**
	 * @var string Question Answer Explanation.
	 * @since 4.2.0
	 */
	protected $question_answer_explanation = '';

	/**
	 * @var string Question Image.
	 * @since 4.2.0
	 */
	protected $question_image = '';

	/**
	 * @var string Question Answer File Types.
	 * @since 4.2.0
	 */
	protected $question_answer_file_types = '';

	/**
	 * @var int Question Usage Count.
	 * @since 4.2.0
	 */
	protected $question_usage_count = 0;

	/**
	 * @var int Question Expanded Count.
	 * @since 4.2.0
	 */
	protected $question_expanded_count = 1;

	/**
	 * @var int Question Multi Random Enable.
	 * @since 4.2.0
	 */
	protected $question_multi_random_enable = 0;

	/**
	 * @var int Question Multi Random Count.
	 * @since 4.2.0
	 */
	protected $question_multi_random_count = 5;

	/**
	 * @var int Question Multi Checkboxes Enable.
	 * @since 4.6.4
	 */
	protected $question_multi_checkboxes_enable = 0;

	/**
	 * Question Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @param array|int|Model $data The model data.
	 */
	public function __construct( $data = array() ) {
		$this->db = new DB_Questions();
		parent::__construct( $data );
	}

	/**
	 * Get Question Id.
	 *
	 * @since 4.3.0
	 *
	 * @return int The question id.
	 */
	public function get_id() {
		return absint( $this->get_question_id() );
	}

	/**
	 * Get Question Id.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_question_id() {
		return absint( $this->question_id );
	}

	/**
	 * Get Question Author.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_question_author() {
		return absint( $this->question_author );
	}

	/**
	 * Get Question Type.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_type() {
		return $this->question_type;
	}

	/**
	 * Get Question Title - Back Compat.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_question() {
		return $this->get_question_title();
	}

	/**
	 * Get Question Title.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_title() {
		return $this->question_question;
	}

	/**
	 * Get Question Answers.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_answers() {
		return $this->question_answers;
	}

	/**
	 * Get Question Data Answers.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_data_answers() {
		return $this->question_data_answers;
	}

	/**
	 * Get Question Correct Answers.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_correct_answer() {
		return $this->question_correct_answer;
	}

	/**
	 * Get Question Answer Type.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_answer_type() {
		return $this->question_answer_type;
	}

	/**
	 * Get Question Answer Hint.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_answer_hint() {
		return $this->question_answer_hint;
	}

	/**
	 * Get Question Answer Explanation.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_answer_explanation() {
		return $this->question_answer_explanation;
	}

	/**
	 * Get Question Image.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_image() {
		return $this->question_image;
	}

	/**
	 * Get Question Answer File Types.
	 *
	 * @since 4.2.0
	 *
	 * @return string
	 */
	public function get_question_answer_file_types() {
		return $this->question_answer_file_types;
	}

	/**
	 * Get Question Usage Count.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_question_usage_count() {
		return $this->question_usage_count;
	}

	/**
	 * Get Question Expanded Count.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_question_expanded_count() {
		return $this->question_expanded_count;
	}

	/**
	 * Get Question Multi Random Enable.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_question_multi_random_enable() {
		return $this->question_multi_random_enable;
	}

	/**
	 * Get Question Multi Random Count.
	 *
	 * @since 4.2.0
	 *
	 * @return int
	 */
	public function get_question_multi_random_count() {
		return $this->question_multi_random_count;
	}

	/**
	 * Get Question Multi Checkboxes Enable.
	 *
	 * @since 4.6.4
	 *
	 * @return int
	 */
	public function get_question_multi_checkboxes_enable() {
		return $this->question_multi_checkboxes_enable;
	}
}
