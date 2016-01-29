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

	$answer_count = get_transient( 'dwqa_answer_count_for_' . $question_id );

	if ( false === $answer_count ) {
		$sql = "SELECT COUNT( DISTINCT `P`.ID ) FROM {$wpdb->postmeta} PM JOIN {$wpdb->posts} P ON `PM`.post_id = `P`.ID WHERE `PM`.meta_key = '_question' AND meta_value = {$question_id} AND `P`.post_type = 'dwqa-answer' AND `P`.post_status = 'publish'";
		$sql .= " AND ( `P`.post_status = 'publish' ";
		if ( dwqa_current_user_can( 'edit_question', $question_id ) ) {
			$sql .= " OR `P`.post_status = 'private'";
		}
		$sql .= " )";
		$answer_count = $wpdb->get_var( $sql );

		set_transient( 'dwqa_answer_count_for_' . $question_id, $answer_count, 15*60 );
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

function dwqa_is_the_best_answer( $answer_id, $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

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
			'meta_query' 		=> array(
				'relation' => 'AND',
				array(
					'key' => '_question',
					'value' => $question_id . '',
					'compare' => '=',
				)
			),
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
	   'meta_query' => array(
			array(
				'key' => '_question',
				'value' => array( $question_id ),
				'compare' => 'IN',
			),
		),
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
		'meta_query' => array(
			array(
				'key' => '_question',
				'value' => array( $question_id ),
				'compare' => 'IN',
			),
		),
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
		'meta_query' => array(
			array(
				'key'	=> '_question',
				'value' => $question_id,
			),
		),
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

	return get_post_meta( $answer_id, '_question', true );
}

class DWQA_Posts_Answer extends DWQA_Posts_Base {

	public function __construct() {
		parent::__construct( 'dwqa-answer', array(
			'plural' => __( 'Answers', 'dwqa' ),
			'singular' => __( 'Answer', 'dwqa' ),
			'menu' => __( 'Answers', 'dwqa' ),
		) );


		add_action( 'manage_' . $this->get_slug() . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
		add_action( 'post_row_actions', array( $this, 'unset_old_actions' ) );

		// add answer
		add_action( 'wp_loaded', array( $this, 'insert') );
		add_action( 'wp_loaded', array( $this, 'update' ) );
		// Ajax remove Answer
		add_action( 'wp_ajax_dwqa_delete_answer', array( $this, 'delete' ) );
		// Ajax flag answer spam
		add_action( 'wp_ajax_dwqa-action-flag-answer', array( $this, 'flag' ) );
		//Ajax vote best answer
		add_action( 'wp_ajax_dwqa-vote-best-answer', array( $this, 'vote_best_answer' ) );
		add_action( 'wp_ajax_dwqa-unvote-best-answer', array( $this, 'unvote_best_answer' ) );
		//Cache
		add_action( 'dwqa_add_answer', array( $this, 'update_transient_when_add_answer' ), 10, 2 );
		add_action( 'dwqa_delete_answer', array( $this, 'update_transient_when_remove_answer' ), 10, 2 );

		// Prepare answers content
		add_filter( 'dwqa_prepare_answer_content', array( $this, 'pre_content_kses' ), 10 );
		add_filter( 'dwqa_prepare_answer_content', array( $this, 'pre_content_filter' ), 20 );
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
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->get_slug() ) {
			$defaults = array(
				'cb'            => '<input type="checkbox">',
				'info'          => __( 'Answer', 'dwqa' ),
				'author'        => __( 'Author', 'dwqa' ),
				'comment'       => '<span><span class="vers"><div title="Comments" class="comment-grey-bubble"></div></span></span>',
				'dwqa-question' => __( 'In Response To', 'dwqa' ),
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
					__( 'Submitted', 'dwqa' ),
					__( 'on', 'dwqa' ),
					get_permalink(),
					date( 'M d Y', get_post_time( 'U', false, $answer ) ),
					( time() - get_post_time( 'U', false, $answer ) ) > 60 * 60 * 24 * 2 ? '' : ' at ' . human_time_diff( get_post_time( 'U', false, $answer ) ) . ' ago',
					substr( get_the_content(), 0 , 140 ) . ' ...',
					$this->row_actions( $actions )
				);
				break;
			case 'dwqa-question':
				$question_id = get_post_meta( $post_ID, '_question', true );
				if ( $question_id ) {
					$question = get_post( $question_id );
					echo '<a href="' . get_permalink( $question_id ) . '" >' . $question->post_title . '</a><br>';
				}
				break;
		}
	}

	public function insert() {
		global $dwqa_options;
		if ( ! isset( $_POST['dwqa-action'] ) || ! isset( $_POST['submit-answer'] ) ) {
			return false;
		}

		if ( 'add-answer' !== $_POST['dwqa-action'] ) {
			return false;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( esc_html(  $_POST['_wpnonce'] ), '_dwqa_add_new_answer' ) ) {
			dwqa_add_notice( __( '&quot;Helllo&quot;, Are you cheating huh?.', 'dwqa' ), 'error' );
		}

		if ( $_POST['submit-answer'] == __( 'Delete draft', 'dwqa' ) ) {
			$draft = isset( $_POST['answer-id'] ) ? intval( $_POST['answer-id'] ) : 0;
			if ( $draft )
				wp_delete_post( $draft );
		}

		if ( empty( $_POST['answer-content'] ) ) {
			dwqa_add_notice( __( 'Answer content is empty', 'dwqa' ), 'error' );
		}
		if ( empty( $_POST['question_id'] ) ) {
			dwqa_add_notice( __( 'Question is empty', 'dwqa' ), 'error' );
		}

		if ( !dwqa_current_user_can( 'post_answer' ) ) {
			dwqa_add_notice( __( 'You do not have permission to submit question.', 'dwqa' ), 'error' );
		}

		if ( !dwqa_valid_captcha( 'single-question' ) ) {
			dwqa_add_notice( __( 'Captcha is not correct', 'dwqa' ), 'error' );
		}

		$user_id = 0;
		$is_anonymous = false;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$is_anonymous = true;
			if ( isset( $_POST['user-email'] ) && is_email( $_POST['user-email'] ) ) {
				$post_author_email = sanitize_email( $_POST['user-email'] );
			}
		}

		$question_id = intval( $_POST['question_id'] );

		$answer_title = __( 'Answer for ', 'dwqa' ) . get_post_field( 'post_title', $question_id );
		$answ_content = apply_filters( 'dwqa_prepare_answer_content', $_POST['answer-content'] );

		$answers = array(
			'comment_status' => 'open',
			'post_author'    => $user_id,
			'post_content'   => $answ_content,
			'post_title'     => $answer_title,
			'post_type'      => $this->get_slug(),
			'post_parent'	 => $question_id,
		);

		$answers['post_status'] = isset( $_POST['save-draft'] ) 
									? 'draft' 
										: ( isset( $_POST['dwqa-status'] ) && $_POST['dwqa-status'] ? $_POST['dwqa-status'] : 'publish' );

		do_action( 'dwqa_prepare_add_answer' );

		if ( dwqa_count_notices( 'error' ) > 0 ) {
			return false;
		}

		$answer_id = wp_insert_post( $answers );

		if ( !is_wp_error( $answer_id ) ) {
			if ( user_can( $user_id, 'edit_posts' ) && $answers['post_status'] != 'draft' ) {
				$answer_count = get_post_meta( $question_id, '_dwqa_answers_count', true );
				update_post_meta( $question_id, '_dwqa_answers_count', (int) $answer_count + 1 );
				update_post_meta( $question_id, '_dwqa_status', 'answered' );
				update_post_meta( $question_id, '_dwqa_answered_time', time() );
				update_post_meta( $answer_id, '_dwqa_votes', 0 );
			}
			update_post_meta( $answer_id, '_question', $question_id  );

			if ( $is_anonymous ) {
				update_post_meta( $answer_id, '_dwqa_is_anonymous', true );

				if ( isset( $post_author_email ) && is_email( $post_author_email ) ) {
					update_post_meta( $answer_id, '_dwqa_anonymous_email', $post_author_email );
				}
			} else {
				add_post_meta( $question_id, '_dwqa_followers', get_current_user_id() );
			}

			do_action( 'dwqa_add_answer', $answer_id, $question_id );
		} else {
			dwqa_add_wp_error_message( $answer_id );
		}
	}

	public function update() {
		if ( isset( $_POST['dwqa-edit-answer-submit'] ) ) {
			if ( !dwqa_current_user_can( 'edit_answer' ) ) {
				dwqa_add_notice( __( "You do not have permission to edit answer.", 'dwqa' ), 'error' );
			}

			if ( !isset( $_POST['_wpnonce'] ) && !wp_verify_nonce( esc_html( $_POST['_wpnonce'] ), '_dwqa_edit_answer' ) ) {
				dwqa_add_notice( __( 'Hello, Are you cheating huh?', 'dwqa' ), 'error' );
			}

			$answer_content = apply_filters( 'dwqa_prepare_edit_answer_content', $_POST['answer_content'] );
			if ( empty( $answer_content ) ) {
				dwqa_add_notice( __( 'You must enter a valid answer content.', 'dwqa' ), 'error' );
			}

			$answer_id = isset( $_POST['answer_id'] ) ? $_POST['answer_id'] : false;

			if ( !$answer_id ) {
				dwqa_add_notice( __( 'Answer is missing.', 'dwqa' ), 'error' );
			}

			if ( 'dwqa-answer' !== get_post_type( $answer_id ) ) {
				dwqa_add_notice( __( 'This post is not answer.', 'dwqa' ), 'error' );
			}

			do_action( 'dwqa_prepare_update_question', $answer_id );

			if ( dwqa_count_notices( 'error' ) > 0 ) {
				return false;
			}

			$args = array(
				'ID' => $answer_id,
				'post_content' => $answer_content
			);

			$new_answer_id = wp_update_post( $args );

			if ( !is_wp_error( $new_answer_id ) ) {
				$old_post = get_post( $answer_id  );
				$new_post = get_post( $new_answer_id );
				do_action( 'dwqa_update_answer', $new_answer_id, $old_post, $new_post );
				$question_id = get_post_meta( $new_answer_id, '_question', true );
				wp_safe_redirect( get_permalink( $question_id ) . '#answer-' . $new_answer_id );
			} else {
				dwqa_add_wp_error_message( $new_answer_id );
				return false;
			}
			exit();
		}
	}

	function delete() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], '_dwqa_action_remove_answer_nonce' ) || 'dwqa_delete_answer' !== $_GET['action'] ) {
			wp_die( __( 'Are you cheating huh?', 'dwqa' ) );
		}

		if ( ! isset( $_GET['answer_id'] ) ) {
			wp_die( __( 'Answer is missing.', 'dwqa' ), 'error' );
		}

		if ( 'dwqa-answer' !== get_post_type( $_GET['answer_id'] ) ) {
			wp_die( __( 'This post is not answer.', 'dwqa' ) );
		}

		if ( !dwqa_current_user_can( 'delete_answer' ) ) {
			wp_die( __( 'You do not have permission to delete this post.', 'dwqa' ) );
		}

		do_action( 'dwqa_prepare_delete_answer', $_GET['answer_id'] );

		$question_id = get_post_meta( $_GET['answer_id'], '_question', true );
		
		$id = wp_delete_post( $_GET['answer_id'] );

		if ( is_wp_error( $id ) ) {
			wp_die( $id->get_error_message() );
		}

		$answer_count = get_post_meta( $question_id, '_dwqa_answers_count', true );
		update_post_meta( $question_id, '_dwqa_answers_count', (int) $answer_count - 1 );

		do_action( 'dwqa_delete_answer', $_GET['answer_id'], $question_id );

		wp_redirect( get_permalink( $question_id ) );
		exit();
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

	/**
	 * Flag as spam answer
	 */
	public function flag() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_flag_answer_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
		}
		if ( ! isset( $_POST['answer_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing id of answer', 'dwqa' ) ) );
		}
		global $current_user;
		$answer_id = intval( $_POST['answer_id'] );
		$flag = get_post_meta( $answer_id, '_flag', true );
		if ( ! $flag ) {
			$flag = array();
		} else {
			$flag = unserialize( $flag );
		}
		// _flag[ user_id => flag_bool , ...]
		$flag_score = 0;
		if ( dwqa_is_user_flag( $answer_id, $current_user->ID ) ) {
			//unflag
			$flag[$current_user->ID] = $flag_score = 0;
		} else {
			$flag[$current_user->ID] = $flag_score = 1;

		}
		$flag = serialize( $flag );
		update_post_meta( $answer_id, '_flag', $flag );
		wp_send_json_success( array( 'status' => $flag_score ) );
	}

	public function vote_best_answer() {
		global $current_user;
		check_ajax_referer( '_dwqa_vote_best_answer', 'nonce' );
		if ( ! isset( $_POST['answer'] ) ) {
			exit( 0 );
		}
		$answer_id = intval( $_POST['answer'] );
		$q = get_post_meta( $answer_id, '_question', true );
		$question = get_post( $q );
		if ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
			do_action( 'dwqa_vote_best_answer', $answer_id );
			update_post_meta( $q, '_dwqa_best_answer', $answer_id );
		}
	}

	public function unvote_best_answer() {
		global $current_user;
		check_ajax_referer( '_dwqa_vote_best_answer', 'nonce' );
		if ( ! isset( $_POST['answer'] ) ) {
			exit( 0 );
		}
		$answer_id = intval( $_POST['answer'] );
		$q = get_post_meta( $answer_id, '_question', true );
		$question = get_post( $q );
		if ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
			do_action( 'dwqa_unvote_best_answer', $answer_id );
			delete_post_meta( $q, '_dwqa_best_answer' );
		}

	}
}

?>
