<?php
/**
 * @since 1.3.5
 */
/**
 * Return number of answer for a question
 * @param  int $question_id Question ID ( if null get ID of current post )
 * @return int      Number of answer
 */
function dwqa_question_answers_count( $question_id = null ) {
	global $wpdb;

	if ( ! $question_id ) {
		global $post;
		$question_id = $post->ID;
	}

	$answer_count = wp_cache_get( 'dwqa_answer_count_for_' . $question_id );

	if ( !$answer_count ) {

		$args = array(
			'post_type' => 'dwqa-answer',
			'post_parent' => $question_id,
			'post_per_page' => '-1',
			'post_status' => array('publish')
		);

		if ( dwqa_current_user_can( 'edit_question', $question_id ) || dwqa_current_user_can( 'manage_question' ) ) {
			$args['post_status'][] = 'private';
		}
		$answer = new WP_Query($args);
		$answer_count = $answer->found_posts;

		wp_cache_set( 'dwqa_answer_count_for_' . $question_id, $answer_count, '', 15*60 );
	}

	return $answer_count;
}

function dwqa_is_answer_flag( $post_id ) {
	if ( dwqa_is_user_flag( $post_id ) ) {
		return true;
	} else {
		$flag = get_post_meta( $post_id, '_flag', true );
		if ( empty( $flag ) || ! is_array( $flag ) ) {
			return false;
		}
		$flag = unserialize( $flag );
		$flag_point = array_sum( $flag );
		if ( $flag_point > 5 ) {
			return true;
		}
	}
	return false; //showing
}

function dwqa_is_the_best_answer( $answer_id = false ) {
	if ( ! $answer_id ) {
		$answer_id = get_the_ID();
	}
	$question_id = dwqa_get_question_from_answer_id( $answer_id );
	$best_answer = dwqa_get_the_best_answer( $question_id );
	if ( $best_answer && $best_answer == $answer_id ) {
		return true;
	}
	return false;
}

function dwqa_get_the_best_answer( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	if ( 'dwqa-question' != get_post_type( $question_id ) ) {
		return false;
	}
	global $dwqa, $wpdb;

	$user_vote = get_post_meta( $question_id, '_dwqa_best_answer', true );

	if ( $user_vote && get_post( $user_vote ) ) {
		return $user_vote;
	}

	$answer_id = get_transient( 'dwqa-best-answer-for-' . $question_id );
	if ( ! $answer_id ) {
		$answers = get_posts( array(
			'post_type' => $dwqa->answer->get_slug(),
			'posts_per_page' => 1,
			'meta_key' => '_dwqa_votes',
			'post_parent' => $question_id,
			'fields' => 'ids',
			'orderby' => 'meta_value_num',
			'order' => 'DESC'
		) );
		$answer_id = ! empty( $answers ) ? $answers[0] : false;
		set_transient( 'dwqa-best-answer-for-'.$question_id, $answer_id, 21600 );
	}

	if ( $answer_id && ( int ) dwqa_vote_count( $answer_id ) > 2 ) {
		return $answer_id;
	}
	return false;
}

/**
 * Draft answer
 */

function dwqa_user_get_draft( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	if ( ! $question_id || 'dwqa-question' != get_post_type( $question_id ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}
	global $current_user;
	$args = array(
   		'post_type' => 'dwqa-answer',
   		'post_parent' => $question_id,
		'post_status' => 'draft',
	);

	if ( ! current_user_can( 'edit_posts' ) ) {
		$args['author'] = $current_user->ID;
	}

	$answers = get_posts( $args );

	if ( ! empty( $answers ) ) {
		return $answers;
	}
	return false;
}


function dwqa_get_drafts( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	if ( ! $question_id || 'dwqa-question' != get_post_type( $question_id ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}
	global $current_user;

	$answers = get_posts(  array(
		'post_type' => 'dwqa-answer',
		'posts_per_page' => 40,
		'post_parent' => $question_id,
		'post_status' => 'draft',
	) );

	if ( ! empty( $answers ) ) {
		return $answers;
	}
	return false;
}

/**
 * Update answers count for question when new answer was added
 * @param  int $answer_id   new answer id
 * @param  int $question_id question id
 */
function dwqa_question_answer_count( $question_id ) {
	return dwqa_question_answer_count_by_status( $question_id, array( 'publish', 'private') );
}

function dwqa_question_answer_count_by_status( $question_id, $status = 'publish' ) {
	$query = new WP_Query( array(
		'post_type' => 'dwqa-answer',
		'post_status' => $status,
		'post_parent' => $question_id,
		'fields' => 'ids'
	) );
	return $query->found_posts;
}

/**
* Get question id from answer id
*
* @param int $answer_id
* @return int
* @since 1.4.0
*/
function dwqa_get_question_from_answer_id( $answer_id = false ) {
	if ( !$answer_id ) {
		$answer_id = get_the_ID();
	}

	return dwqa_get_post_parent_id( $answer_id );
}

class DWQA_Posts_Answer extends DWQA_Posts_Base {

	public function __construct() {
		parent::__construct( 'dwqa-answer', array(
			'plural' => __( 'Answers', 'dw-question-answer' ),
			'singular' => __( 'Answer', 'dw-question-answer' ),
			'menu' => __( 'Answers', 'dw-question-answer' ),
		) );


		add_action( 'manage_' . $this->get_slug() . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
		add_action( 'post_row_actions', array( $this, 'unset_old_actions' ) );
		add_action( 'add_meta_boxes', array( $this, 'question_metabox' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'save_metabox_post_data' ), 10, 2 );
		
		//Cache
		add_action( 'dwqa_add_answer', array( $this, 'update_transient_when_add_answer' ), 10, 2 );
		add_action( 'dwqa_delete_answer', array( $this, 'update_transient_when_remove_answer' ), 10, 2 );

		// Prepare answers content
		add_filter( 'dwqa_prepare_answer_content', array( $this, 'pre_content_kses' ), 10 );
		add_filter( 'dwqa_prepare_answer_content', array( $this, 'pre_content_filter' ), 20 );

		// prepare edit content
		add_filter( 'dwqa_prepare_edit_answer_content', array( $this, 'pre_content_kses' ), 10 );
		add_filter( 'dwqa_prepare_edit_answer_content', array( $this, 'pre_content_filter' ), 20 );
	}

	// Remove default menu and change it to submenu of questions
	public function set_show_in_menu() {
		global $dwqa;
		return 'edit.php?post_type=' . $dwqa->question->get_slug();
	}

	public function set_supports() {
		return array(
			'title', 'editor', 'comments',
			'custom-fields', 'author', 'page-attributes',
		);
	}

	public function set_has_archive() {
		return false;
	}

	public function columns_head( $defaults ) {
		if ( isset( $_GET['post_type'] ) && sanitize_text_field( $_GET['post_type'] ) == $this->get_slug() ) {
			$defaults = array(
				'cb'            => '<input type="checkbox">',
				'info'          => __( 'Answer', 'dw-question-answer' ),
				'author'        => __( 'Author', 'dw-question-answer' ),
				'comment'       => '<span><span class="vers"><div title="Comments" class="comment-grey-bubble"></div></span></span>',
				'dwqa-question' => __( 'In Response To', 'dw-question-answer' ),
			);
		}
		return $defaults;
	}

	public function unset_old_actions( $actions ) {
		global $post;

		if ( $post->post_type == 'dwqa-answer' ) {
			$actions = array();
		}

		return $actions;
	}

	public function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( ! $action_count )
			return '';

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		return $out;
	}

	public function columns_content( $column_name, $post_ID ) {
		$answer = get_post( $post_ID );
		switch ( $column_name ) {
			case 'comment' :
				$comment_count = get_comment_count( $post_ID );
				echo '<a href="'.admin_url( 'edit-comments.php?p='.$post_ID ).'"  class="post-com-count"><span class="comment-count">'.$comment_count['approved'].'</span></a>';
				break;
			case 'info':
				//Build row actions
				$actions = array(
					'edit'      => sprintf( '<a href="%s">%s</a>', get_edit_post_link( $post_ID ), __( 'Edit', 'edd-dw-membersip' ) ),
					'delete'    => sprintf( '<a href="%s">%s</a>', get_delete_post_link( $post_ID ), __( 'Delete', 'edd-dw-membersip' ) ),
					'view'      => sprintf( '<a href="%s">%s</a>', get_permalink( $post_ID ), __( 'View', 'edd-dw-membersip' ) )
				);
				printf(
					'%s %s <a href="%s">%s %s</a> <br /> %s %s',
					__( 'Submitted', 'dw-question-answer' ),
					__( 'on', 'dw-question-answer' ),
					get_permalink(),
					date( 'M d Y', get_post_time( 'U', true, $answer ) ),
					( time() - get_post_time( 'U', true, $answer ) ) > 60 * 60 * 24 * 2 ? '' : ' at ' . human_time_diff( get_post_time( 'U', true, $answer ) ) . ' ago',
					substr( get_the_content(), 0 , 140 ) . ' ...',
					$this->row_actions( $actions )
				);
				break;
			case 'dwqa-question':
				$question_id = dwqa_get_post_parent_id( $post_ID );
				if ( $question_id ) {
					$question = get_post( $question_id );
					echo '<a href="' . get_permalink( $question_id ) . '" >' . $question->post_title . '</a><br>';
				}
				break;
		}
	}

	//Cache
	public function update_transient_when_add_answer( $answer_id, $question_id ) {
		// Update cache for latest answer of this question
		$answer = get_post( $answer_id );
		set_transient( 'dwqa_latest_answer_for_' . $question_id, $answer, 15*60 );
		delete_transient( 'dwqa_answer_count_for_' . $question_id );
	}

	public function update_transient_when_remove_answer( $answer_id, $question_id ) {
		// Remove Cached Latest Answer
		delete_transient( 'dwqa_latest_answer_for_' . $question_id );
		delete_transient( 'dwqa_answer_count_for_' . $question_id );
	}

	public function question_metabox() {
		add_meta_box(
			'dwqa-answer-question-metabox',
			__( 'Question ID', 'dw-question-answer' ),
			array( $this, 'question_metabox_output' ),
			'dwqa-answer',
			'side'
		);
	}

	public function question_metabox_output( $post ) {
		$question = $post->post_parent ? $post->post_parent : 0;
		?>
		<p>
			<strong><?php _e( 'ID', 'dw-question-answer' ) ?></strong>
		</p>
		<p>
			<label class="screen-reader-text"><?php _e( 'ID', 'dw-question-answer' ) ?></label>
			<input name="_question" type="text" size="4" id="_question" value="<?php echo (int) $question ?>">
		</p>
		<?php
	}

	public function save_metabox_post_data( $data, $postarr ) {
		// only for admin
		if(!is_admin() || !current_user_can( 'edit_posts' )){
			return $data;
		}

		if ( 'dwqa-answer' !== $data['post_type'] ) {
			return $data;
		}

		if ( !isset( $_POST['_question'] ) || empty( $_POST['_question'] ) ) {
			return $data;
		}

		$data['post_parent'] = intval($_POST['_question']);
		
		return $data;
	}
}

?>
