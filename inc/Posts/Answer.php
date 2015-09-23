<?php  

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

		//Cache
		add_action( 'dwqa_add_answer', array( $this, 'update_transient_when_add_answer' ), 10, 2 );
		add_action( 'dwqa_delete_answer', array( $this, 'update_transient_when_remove_answer' ), 10, 2 );
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
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->slug ) {
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
		global $post_submit_filter, $dwqa_options;
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

			$answ_content = wp_kses( $_POST['answer-content'], $post_submit_filter );
			$answ_content = dwqa_pre_content_filter( $answ_content );
			
			$post_status = ( isset( $_POST['private-message'] ) && esc_html( $_POST['private-message'] ) ) ? 'private' : 'publish';
			$answers = array(
				'comment_status' => 'open',
				'post_author'    => $user_id,
				'post_content'   => $answ_content,
				'post_status'    => $post_status,
				'post_title'     => $answer_title,
				'post_type'      => 'dwqa-answer',
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
							wp_redirect( get_permalink( $question_id ) );
							return true;
						} else {
							$dwqa_add_answer_errors = $answer_id;
						}
					} else {
						$dwqa_add_answer_errors->add( 'in_valid_captcha', __( 'Captcha is not correct','dwqa' ) );
					}
					
					break;
				case 'update-answer':
					if ( ! isset( $_POST['answer-id'] ) ) {
						wp_send_json_error( array(
							'message'   => __( 'Answer is missing', 'dwqa' )
						) );
					}

					$answer_id = intval( $_POST['answer-id'] );
					$answer_author = get_post_field( 'post_author', $answer_id  );
					
					global $current_user;

					if ( ! ( dwqa_current_user_can( 'edit_answer' ) || ( is_user_logged_in() && $answer_author == $current_user->ID ) ) ) {
						wp_send_json_error( array(
							'message'   => __( 'You do not have permission to edit this post', 'dwqa' )
						) );
					}
					if ( get_post_type( $answer_id  ) != 'dwqa-answer' ) {
						wp_send_json_error( array(
							'message'   => __( 'This post is not an answer', 'dwqa' )
						) );
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
						return true;
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
	}

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
}

?>