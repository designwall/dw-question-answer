<?php  

class DWQA_Posts_Comment {
	public function __construct() {
		add_filter( 'comment_post_redirect', array( $this, 'hook_redirect_comment_for_answer'), 10, 2 );

		// Comment form template for DW Question Answer
		remove_action( 'comment_form', 'wp_comment_form_unfiltered_html_nonce' );
		add_action( 'comment_form', array( $this, 'wp_comment_form_unfiltered_html_nonce' ) );
		add_action( 'wp_ajax_dwqa-action-update-comment', array( $this, 'update' ) );
		add_action( 'wp_ajax_dwqa-action-delete-comment', array( $this, 'delete' ) );
		// Filter
		add_filter( 'comment_id_fields', array( $this, 'comment_form_id_fields_filter' ), 10, 3 );
		add_filter( 'get_comment_text', array( $this, 'sanitizie_comment' ), 10, 2 );
		// Ajax insert comment
		add_action( 'wp_ajax_dwqa-comment-action-add', array( $this, 'insert' ) );
		add_action( 'wp_ajax_nopriv_dwqa-comment-action-add', array( $this, 'insert' ) );

		add_action( 'wp_ajax_dwqa-get-comments', array( $this, 'get_comments' ) );
		add_action( 'wp_ajax_nopriv_dwqa-get-comments', array( $this, 'get_comments' ) );
		add_filter( 'get_comment', array( $this, 'comment_author_link_anonymous' ) );

		add_action( 'wp_insert_comment', array( $this, 'reopen_question_have_new_comment' ) );

		// Prepare comment content
		add_filter( 'dwqa_pre_comment_content', 'wp_kses_data', 10 );
	}
	/**
	 * Change redirect link when comment for answer finished
	 * @param  string $location Old redirect link
	 * @param  object $comment  Comment Object
	 * @return string           New redirect link
	 */
	public function hook_redirect_comment_for_answer( $location, $comment ) {

		if ( 'dwqa-answer' == get_post_type( $comment->comment_post_ID ) ) {
			$question = get_post_meta( $comment->comment_post_ID, '_question', true );
			if ( $question ) {
				return get_post_permalink( $question ).'#'.'answer-' . $comment->comment_post_ID . '&comment='.$comment->comment_ID;
			}
		}
		return $location;
	}

	/**
	 * Displays form token for unfiltered comments. Override wp_comment_form_unfiltered_html_nonce custom for dwqa
	 *
	 * Backported to 2.0.10.
	 *
	 * @since 2.1.3
	 * @uses $post Gets the ID of the current post for the token
	 */
	public function wp_comment_form_unfiltered_html_nonce() {
		$post = get_post();
		$post_id = $post ? $post->ID : 0;

		if ( current_user_can( 'unfiltered_html' ) 
				&& 'dwqa-answer' != get_post_type( $post_id )  ) {
			wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_disabled', false );
			echo "<script>( function() {if ( window===window.parent ) {document.getElementById( '_wp_unfiltered_html_comment_disabled' ).name='_wp_unfiltered_html_comment';}} )();</script>\n";
		} elseif ( current_user_can( 'unfiltered_html' ) 
						&& 'dwqa-answer' == get_post_type( $post_id ) ) {
							
			wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_answer_disabled', false );
			echo "<script>( function() {if ( window===window.parent ) {document.getElementById( '_wp_unfiltered_html_comment_answer_disabled' ).name='_wp_unfiltered_html_comment';}} )();</script>\n";
		}
	}

	public function update() {
		global $post_submit_filter;

		if ( ! isset( $_POST['comment_id']) ) {
			wp_send_json_error( array(
				'message'	=> __( 'Comment is missing', 'dwqa' )
			) );
		}
		$comment_id = intval( $_POST['comment_id'] );
		$comment_content = isset( $_POST['comment'] ) ? esc_html( $_POST['comment'] ) : '';
		$comment_content = apply_filters( 'dwqa_pre_update_comment_content', $comment_content );

		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_comment_edit_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
		}
		if ( strlen( $comment_content ) <= 0 || ! isset( $comment_id ) || ( int )$comment_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Comment content must not be empty.', 'dwqa' ) ) );
		} else {
			$commentarr = array(
				'comment_ID'        => $comment_id,
				'comment_content'   => $comment_content,
			);
			
			wp_update_comment( $commentarr );
			wp_send_json_success();
		}
	}

	/**
	 * AJAX:Remove comment of quest/answer away from database
	 */
	public function delete() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_comment_delete_nonce' ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Are you cheating huh?', 'dwqa' )  
			) );
		}
		if ( ! isset( $_POST['comment_id'] ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Comment ID must be showed.', 'dwqa' )  
			) );
		}

		wp_delete_comment( intval( $_POST['comment_id'] ) );
		wp_send_json_success();
	}

	// We have many comment fields on single question page, so each of them need to be unique in ID
	public function comment_form_id_fields_filter( $result, $id, $replytoid ) {
		if ( 'dwqa-answer' == get_post_type( $id ) ) {
			$result = str_replace( "id='comment_post_ID'", "id='comment_post_".$id."_ID'", $result );
			$result = str_replace( "id='comment_parent'", "id='comment_".$id."_parent'", $result );
		}
		return $result;
	}

	public function insert() {
		global $current_user;
		if ( ! dwqa_current_user_can( 'post_comment' ) ) {
			wp_send_json_error( array(
				'message'   => __( 'You can\'t post comment', 'dwqa' )
			) );
		}
		if ( ! isset( $_POST['comment_post_ID'] ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Please enter your comment content', 'dwqa' )
			) );
		}
		$comment_content = isset( $_POST['content'] ) ? $_POST['content'] : '';
		$comment_content = apply_filters( 'dwqa_pre_comment_content', $comment_content );

		$args = array(
			'comment_post_ID'   => intval( $_POST['comment_post_ID'] ),
			'comment_content'   => $comment_content,
			'comment_parent'    => isset( $_POST['comment_parent']) ? intval( $_POST['comment_parent'] ) : 0,
		);
		if ( is_user_logged_in() ) {
			$args['user_id'] = $current_user->ID;
			$args['comment_author'] = $current_user->display_name;
		} else {
			if ( ! isset( $_POST['email'] ) || ! sanitize_email( $_POST['email'] ) ) {
				wp_send_json_error( array(
					'message'   => __( 'Missing email information','dwqa' )
				) );
			}
			if ( ! isset( $_POST['name'] ) || ! sanitize_text_field( $_POST['name'] ) ) {
				wp_send_json_error( array(
					'message'   => __( 'Missing name information','dwqa' )
				) );
			}
			$args['comment_author'] = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : 'anonymous';
			$args['comment_author_email'] = sanitize_email(  $_POST['email'] );
			$args['comment_author_url'] = isset( $_POST['url'] ) ? esc_url( $_POST['url'] ) : '';
			$args['user_id']    = -1;
		}

		$comment_id = wp_insert_comment( $args );  

		global $comment;
		$comment = get_comment( $comment_id );
		ob_start();
		$args = array( 
			'walker' => null, 
			'max_depth' => '', 
			'style' => 'ol', 
			'callback' => null, 
			'end-callback' => null, 
			'type' => 'all',
			'page' => '', 
			'per_page' => '', 
			'avatar_size' => 32, 
			'reverse_top_level' => null, 
			'reverse_children' => '', 
		);
		dwqa_question_comment_callback( $comment, $args, 0 );
		echo '</li>';
		$comment_html = ob_get_contents();
		ob_end_clean();

		$client_id = isset( $_POST['clientId'] ) ? sanitize_text_field( $_POST['clientId'] ) : false;
		do_action( 'dwqa_add_comment', $comment_id, $client_id );
		
		wp_send_json_success( array( 'html' => $comment_html ) );
	}

	public function sanitizie_comment( $content, $comment ) {
		$post_type = get_post_type( $comment->comment_post_ID );
		if ( $post_type == 'dwqa-question' || $post_type == 'dwqa-answer' ) {
			$content = str_replace( esc_html( '<br>' ), '<br>', esc_html( $content ) );
			$content = make_clickable( $content );
			$content = preg_replace( '/( <a[^>]* )( > )/', '$1 target="_blank" $2', $content );
		}
		return $content;
	}


	public function get_comments() {
		if ( isset( $_GET['post'] ) ) {
			$comments = get_comments( array(
				'post_id' => intval( $_GET['post'] ),
				'status' => 'approve',
			) );
			
			wp_list_comments( array( 
				'style' => 'ol',
				'callback'  => 'dwqa_question_comment_callback',
			), $comments ); 
		}
		exit( 0 );
	}

	public function comment_author_link_anonymous( $comment ) {
		// global $current_comment;
		if ( $comment->user_id <= 0 && ( get_post_type( $comment->comment_post_ID ) == 'dwqa-question' || get_post_type( $comment->comment_post_ID ) == 'dwqa-answer' ) ) {
			$comment->comment_author = __( 'Anonymous','dwqa' );
		}
		return $comment;
	}

	//Update question status when have new comment
	public function reopen_question_have_new_comment( $comment_ID ){
		$comment = get_comment( $comment_ID );
		$comment_post_type = get_post_type( $comment->comment_post_ID );
		$question = false;
		if ( 'dwqa-answer' == $comment_post_type ) {
			$question = get_post_meta( $comment->comment_post_ID, '_question', true );
		} elseif ( 'dwqa-question' == $comment_post_type ) {
			$question = $comment->comment_post_ID;
		}

		if ( $question ) {
			$question_status = get_post_meta( $question, '_dwqa_status', true );
			if ( ! user_can( $comment->user_id, 'edit_posts' ) ) {
				if ( 'resolved' == $question_status ) {
					update_post_meta( $question, '_dwqa_status', 're-open' );
				}
			}
		}
	}
}
?>