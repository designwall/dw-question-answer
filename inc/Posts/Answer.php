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

class DWQA_Posts_Answer extends DWQA_Posts_Base {

	public function __construct() {
		parent::__construct( 'dwqa-answer', array(
			'plural' => __( 'Answers', 'dwqa' ),
			'singular' => __( 'Answer', 'dwqa' ),
			'menu' => __( 'All answers', 'dwqa' ),
		) );


		add_action( 'manage_' . $this->get_slug() . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

		// Ajax add answer
		add_action( 'wp_ajax_dwqa-add-answer', array( $this, 'insert') );
		add_action( 'wp_ajax_nopriv_dwqa-add-answer', array( $this, 'insert') );
		// Ajax remove Answer
		add_action( 'wp_ajax_dwqa-action-remove-answer', array( $this, 'remove_answer' ) );
		// Ajax flag answer spam
		add_action( 'wp_ajax_dwqa-action-flag-answer', array( $this, 'flag' ) );
		//Ajax vote best answer
		add_action( 'wp_ajax_dwqa-vote-best-answer', array( $this, 'vote_best_answer' ) );
		add_action( 'wp_ajax_dwqa-unvote-best-answer', array( $this, 'unvote_best_answer' ) );
		//Cache
		add_action( 'dwqa_add_answer', array( $this, 'update_transient_when_add_answer' ), 10, 2 );
		add_action( 'dwqa_delete_answer', array( $this, 'update_transient_when_remove_answer' ), 10, 2 );
		//Prepare answers for single questions
		add_action( 'the_posts', array( $this, 'prepare_answers' ), 10, 2 );

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
		$dwqa_add_answer_errors = new WP_Error();
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( esc_html(  $_POST['_wpnonce'] ), '_dwqa_add_new_answer' ) ) {
			$dwqa_add_answer_errors->add( 'answer_question', '"Helllo", Are you cheating huh?.' );
		}

		if ( $_POST['submit-answer'] == __( 'Delete draft', 'dwqa' ) ) {
			$draft = isset( $_POST['answer-id'] ) ? intval( $_POST['answer-id'] ) : 0;
			if ( $draft ) 
				wp_delete_post( $draft );
			return false;
		}

		if ( empty( $_POST['answer-content'] ) ||  empty( $_POST['question'] ) ) {
			if ( empty( $_POST['answer-content'] ) ) {
				$dwqa_add_answer_errors->add( 'answer_question','answer content is empty' );
			} 
			if ( empty( $_POST['question'] ) ) {
				$dwqa_add_answer_errors->add( 'answer_question','question is empty' );
			}
		} else {

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

			$question_id = intval( $_POST['question'] );
			$question = get_post( $question_id );

			$answer_title = __( 'Answer for ', 'dwqa' ) . $question->post_title;
			$answ_content = apply_filters( 'dwqa_prepare_answer_content', $_POST['answer-content'] );
			
			$post_status = ( isset( $_POST['private-message'] ) && esc_html( $_POST['private-message'] ) ) ? 'private' : 'publish';
			$answers = array(
				'comment_status' => 'open',
				'post_author'    => $user_id,
				'post_content'   => $answ_content,
				'post_status'    => $post_status,
				'post_title'     => $answer_title,
				'post_type'      => $this->get_slug(),
			);
			if ( $_POST['submit-answer'] == __( 'Save draft','dwqa' ) ) {
				$answers['post_status'] = 'draft';
			} else if ( isset( $_POST['privacy'] ) && 'private' == $_POST['privacy'] ) {
				$answers['post_status'] = 'private';
			}

			switch ( $_POST['dwqa-action'] ) {
				case 'add-answer':
					$valid_captcha = dwqa_valid_captcha( 'single-question' );

					if ( $valid_captcha ) {
						if ( dwqa_current_user_can( 'post_answer' ) ) {
							$answer_id = wp_insert_post( $answers, true );
						} else {
							$answer_id = new WP_Error( 'permission', __( 'You do not have permission to submit question.', 'dwqa' ) );
						}
						
						if ( ! is_wp_error( $answer_id ) ) {
							//Send email alert for author of question about this answer
							$question_author = $question->post_author;

							if ( user_can( $answers['post_author'], 'edit_posts' ) && $answers['post_status'] != 'draft' ) {
								update_post_meta( $question_id, '_dwqa_status', 'answered' );
								update_post_meta( $question_id, '_dwqa_answered_time', time() );
							}
							update_post_meta( $answer_id, '_question', $question_id  );

							if ( $is_anonymous ) {
								update_post_meta( $answer_id, '_dwqa_is_anonymous', true );
								if ( isset( $post_author_email ) && is_email( $post_author_email ) ) {
									update_post_meta( $answer_id, '_dwqa_anonymous_email', $post_author_email );
								}
							}
							do_action( 'dwqa_add_answer', $answer_id, $question_id );
							// wp_redirect( get_permalink( $question_id ) );
							// wp_send_json_success( array( 'url' => get_permalink( $question_id ) ) );
							// return true;
						} else {
							$dwqa_add_answer_errors = $answer_id;
						}
					} else {
						$dwqa_add_answer_errors->add( 'in_valid_captcha', __( 'Captcha is not correct','dwqa' ) );
					}
					
					break;
				case 'update-answer':
					if ( ! isset( $_POST['answer-id'] ) ) {
						$dwqa_add_answer_errors->add( 'missing-content', __( 'Answer is missing', 'dwqa' ) );
						break;
					}

					$answer_id = intval( $_POST['answer-id'] );
					$answer_author = get_post_field( 'post_author', $answer_id  );
					
					global $current_user;

					if ( ! ( dwqa_current_user_can( 'edit_answer' ) || ( is_user_logged_in() && $answer_author == $current_user->ID ) ) ) {
						$dwqa_add_answer_errors->add( 'permission-denided', __( 'You do not have permission to edit this post', 'dwqa' ) );
						break;
					}
					if ( get_post_type( $answer_id  ) != 'dwqa-answer' ) {
						$dwqa_add_answer_errors->add( 'posttype-error', __( 'This post is not an answer', 'dwqa' ) );
						break;
					}

					$answer_update = array(
						'ID'    => $answer_id,
						'post_content'   => $answ_content,
					);
					$post_status = get_post_status( $answer_id );

					if ( ( $post_status == 'draft' && strtolower( $_POST['submit-answer'] ) == 'publish' ) || ( $post_status != 'draft' && strtolower( $_POST['submit-answer'] ) == 'update' ) ) {
						$answer_update['post_status'] = isset( $_POST['privacy'] ) && 'private' == esc_html( $_POST['privacy'] ) ? 'private' : 'publish';
						update_post_meta( $question_id, '_dwqa_status', 're-open' );
					} 
					$old_post = get_post( $answer_id  );
					$answer_id = wp_update_post( $answer_update );
					$new_post = get_post( $answer_id );
					do_action( 'dwqa_update_answer', $answer_id, $old_post, $new_post );
					if ( $answer_id ) {
						wp_safe_redirect( get_permalink( $question_id ) );
						exit(0);
					}
					break;
			}
		}
		$url = get_permalink( $question_id );
		$error_messages = $dwqa_add_answer_errors->get_error_messages();
		foreach ( $error_messages as $value ) {
			$url = esc_url( add_query_arg( 'errors', urlencode( $value ), $url ) );
		}
		wp_safe_redirect( $url );
		exit(0);
	}

	function remove_answer() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( esc_html( $_POST['wpnonce'] ), '_dwqa_action_remove_answer_nonce' ) || ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
		}
		if ( isset( $_POST['answer_id'] ) ) {
			$answer_id = intval( $_POST['answer_id'] );
		} else {
			wp_send_json_error( array( 'message' => __( 'Missing answer ID', 'dwqa' ) ) );
		}

		global $current_user;
		$answer_author = get_post_field( 'post_author', $answer_id );

		if ( ! ( dwqa_current_user_can( 'delete_answer' ) || ( is_user_logged_in() && $answer_author == $current_user->ID ) ) ) {
			wp_send_json_error( array(
				'message'   => __( 'You do not have permission to edit this post', 'dwqa' )
			) );
		}
		if ( get_post_type( $answer_id ) != 'dwqa-answer' ) {
			wp_send_json_error( array(
				'message'   => __( 'This post is not an answer', 'dwqa' )
			) );
		}
		$question_id = get_post_meta( $answer_id, '_question', true );

		do_action( 'dwqa_delete_answer', $answer_id, $question_id );

		wp_delete_post( $answer_id );

		wp_send_json_success( array( 'question' => $question_id, 'answer' => $answer_id ) );
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

	public function prepare_answers( $posts, $query ) {
		global $dwqa;
		$query->test = 'rambu';

		if ( is_main_query() && $query->is_single() && $query->query_vars['post_type'] == $dwqa->question->get_slug() ) {
			$question = $posts[0];
			$ans_cur_page = isset( $_GET['ans-page'] ) ? intval( $_GET['ans-page'] ) : 1;
			// We will include the all answers of this question here;
			$args = array(
				'post_type' 		=> 'dwqa-answer',
				'posts_per_page'    => get_option( 'posts_per_page' ),
				'order'      		=> 'ASC',
				'paged'				=> $ans_cur_page,
				'meta_query' 		=> array(
					array(
						'key' => '_question',
						'value' => $question->ID
					),
				),
				'post_status' => array( 'publish', 'private', 'draft' ),
				'perm' => 'readable',
			);
			$query->dwqa_answers = new WP_Query( $args );
			$query->dwqa_answers->best_answer = dwqa_get_the_best_answer( $question->ID );
		}
		return $posts;
	}
}

?>