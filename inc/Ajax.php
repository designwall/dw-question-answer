<?php

class DWQA_Ajax {
	public function __construct() {
		// comment
		add_action( 'wp_ajax_dwqa-action-delete-comment', array( $this, 'delete_comment' ) );

		// Ajax remove Answer
		add_action( 'wp_ajax_dwqa_delete_answer', array( $this, 'delete_answer' ) );

		// Ajax flag answer spam
		add_action( 'wp_ajax_dwqa-action-flag-answer', array( $this, 'flag_answer' ) );

		//Ajax vote best answer
		add_action( 'wp_ajax_dwqa-vote-best-answer', array( $this, 'vote_best_answer' ) );
		add_action( 'wp_ajax_dwqa-unvote-best-answer', array( $this, 'unvote_best_answer' ) );

		//Question
		add_action( 'wp_ajax_dwqa_delete_question', array( $this, 'delete_question' ) );
		add_action( 'wp_ajax_dwqa-update-question-status', array( $this, 'update_status' ) );

		// Ajax search and suggest question
		add_action( 'wp_ajax_dwqa-auto-suggest-search-result', array( $this, 'auto_suggest_for_seach' ) );
		add_action( 'wp_ajax_nopriv_dwqa-auto-suggest-search-result', array( $this, 'auto_suggest_for_seach' ) );
	}

	public function delete_comment() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), '_dwqa_delete_comment' ) ) {
			wp_die( __( 'Are you cheating huh?', 'dw-question-answer' ) );
		}

		if ( !dwqa_current_user_can( 'delete_comment' ) ) {
			wp_die( __( 'You do not have permission to edit comment.', 'dw-question-answer' ) );
		}

		if ( ! isset( $_GET['comment_id'] ) ) {
			wp_die( __( 'Comment ID must be showed.', 'dw-question-answer' ) );
		}

		wp_delete_comment( intval( $_GET['comment_id'] ) );
		$comment = get_comment( $_GET['comment_id'] );
		exit( wp_safe_redirect( dwqa_get_question_link( $comment->comment_post_ID ) ) );
	}

	function delete_answer() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), '_dwqa_action_remove_answer_nonce' ) || 'dwqa_delete_answer' !== $_GET['action'] ) {
			wp_die( __( 'Are you cheating huh?', 'dw-question-answer' ) );
		}

		if ( ! isset( $_GET['answer_id'] ) ) {
			wp_die( __( 'Answer is missing.', 'dw-question-answer' ), 'error' );
		}

		$answer_id = absint( $_GET['answer_id'] );

		if ( 'dwqa-answer' !== get_post_type( $answer_id ) ) {
			wp_die( __( 'This post is not answer.', 'dw-question-answer' ) );
		}

		if ( !dwqa_current_user_can( 'delete_answer', $answer_id ) && !dwqa_current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have permission to delete this post.', 'dw-question-answer' ) );
		}

		do_action( 'dwqa_prepare_delete_answer', $answer_id );

		$question_id = dwqa_get_post_parent_id( $answer_id );
		
		$id = wp_trash_post( $answer_id );

		if ( is_wp_error( $id ) ) {
			wp_die( $id->get_error_message() );
		}

		$answer_count = get_post_meta( $question_id, '_dwqa_answers_count', true );
		$new_answer_count = (int) $answer_count - 1;
		if ( (int) $new_answer_count < 0 ) {
			$new_answer_count = intval( 0 );
		}
		update_post_meta( $question_id, '_dwqa_answers_count', $new_answer_count );

		do_action( 'dwqa_delete_answer', $answer_id, $question_id );

		wp_redirect( get_permalink( $question_id ) );
		die();
	}

	public function flag_answer() {
		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_flag_answer_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dw-question-answer' ) ) );
		}
		if ( ! isset( $_POST['answer_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing id of answer', 'dw-question-answer' ) ) );
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
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], '_dwqa_vote_best_answer' ) ) {
			wp_die( __( 'Are you cheating huh?', 'dw-question-answer' ) );
		}
		if ( ! isset( $_GET['answer'] ) ) {
			exit( 0 );
		}
		$answer_id = intval( $_GET['answer'] );

		$question_id = dwqa_get_post_parent_id( $answer_id );
		$question = get_post( $question_id );

		if ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
			do_action( 'dwqa_vote_best_answer', $answer_id );
			update_post_meta( $question_id, '_dwqa_best_answer', $answer_id );
		}

		wp_redirect( get_permalink( $question_id ) );
		exit;
	}

	public function unvote_best_answer() {
		global $current_user;
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], '_dwqa_vote_best_answer' ) ) {
			wp_die( __( 'Are you cheating huh?', 'dw-question-answer' ) );
		}
		if ( ! isset( $_GET['answer'] ) ) {
			exit( 0 );
		}
		$answer_id = intval( $_GET['answer'] );
		$question_id = dwqa_get_post_parent_id( $answer_id );
		$question = get_post( $question_id );
		if ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
			do_action( 'dwqa_unvote_best_answer', $answer_id );
			delete_post_meta( $question_id, '_dwqa_best_answer' );
		}
		wp_redirect( get_permalink( $question_id ) );
		exit;
	}

	public function delete_question() {
		global $dwqa_general_settings;
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), '_dwqa_action_remove_question_nonce' ) || 'dwqa_delete_question' !== $_GET['action'] ) {
			wp_die( __( 'Are you cheating huh?', 'dw-question-answer' ) );
		}

		if ( ! isset( $_GET['question_id'] ) ) {
			wp_die( __( 'Question is missing.', 'dw-question-answer' ), 'error' );
		}

		if ( 'dwqa-question' !== get_post_type( intval( $_GET['question_id'] ) ) ) {
			wp_die( __( 'This post is not question.', 'dw-question-answer' ) );
		}

		if ( !dwqa_current_user_can( 'delete_answer' ) ) {
			wp_die( __( 'You do not have permission to delete this post.', 'dw-question-answer' ) );
		}

		do_action( 'before_delete_post', intval( $_GET['question_id'] ) );
		
		$id = wp_delete_post( intval( $_GET['question_id'] ) );

		if ( is_wp_error( $id ) ) {
			wp_die( $id->get_error_message() );
		}

		do_action( 'dwqa_delete_question', intval( $_GET['question_id'] ) );

		$url = home_url();
		if ( isset( $dwqa_general_settings['pages']['archive-question'] ) ) {
			$url = get_permalink( $dwqa_general_settings['pages']['archive-question'] );
		}

		wp_redirect( $url );
		exit();
	}

	public function update_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_dwqa_update_question_status_nonce' ) ) {
		}
		if ( ! isset( $_POST['question'] ) ) {
			wp_die( 0 );
		}
		if ( ! isset( $_POST['status'] ) || ! in_array( sanitize_text_field( $_POST['status'] ), array( 'open', 're-open', 'resolved', 'closed', 'pending' ) ) ) {
			wp_die( 0 );
		}

		global $current_user;
		$question_id = intval( $_POST['question'] );
		$question = get_post( $question_id );

		if ( dwqa_current_user_can( 'edit_question' ) || $current_user->ID == $question->post_author ) {
			$status = sanitize_text_field( $_POST['status'] );
			update_post_meta( $question_id, '_dwqa_status', $status );
			if ( $status == 'resolved' ) {
				update_post_meta( $question_id, '_dwqa_resolved_time', time() );
			}
		} else {
			wp_send_json_error( array(
				'message'   => __( 'You do not have permission to edit question status', 'dw-question-answer' )
			) );
		}
	}

	public function auto_suggest_for_seach(){
		if ( ! isset( $_POST['nonce'])  ) {
			wp_send_json_error( array( array( 
				'error' => 'sercurity',
				'message' => __( 'Are you cheating huh?', 'dw-question-answer' ) 
			) ) );
		}
		check_ajax_referer( '_dwqa_filter_nonce', 'nonce' );

		if ( ! isset( $_POST['title'] ) ) {
			wp_send_json_error( array( array( 
				'error' => 'empty title',
				'message' => __( 'Not Found!!!', 'dw-question-answer' ), 
			) ) );
		}

		$status = 'publish';
		if ( is_user_logged_in() ) {
			$status = array( 'publish', 'private' );
		}

		$search = sanitize_text_field( $_POST['title'] );
		$args_query = array(
			'post_type'			=> 'dwqa-question',
			'posts_per_page'	=> 6,
			'post_status'		=> $status,
		);
		preg_match_all( '/#\S*\w/i', $search, $matches );
		if ( $matches && is_array( $matches ) && count( $matches ) > 0 && count( $matches[0] ) > 0 ) {
			$args_query['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_tag',
				'field' => 'slug',
				'terms' => $matches[0],
				'operator'  => 'IN',
			);
			$search = preg_replace( '/#\S*\w/i', '', $search );
		}
		$args_query['s'] = $search;
		$args_query = apply_filters( 'dwqa_prepare_search_query_args', $args_query );
		$query = new WP_Query( $args_query );
		if ( ! $query->have_posts() ) {
			global $current_search;
			$current_search = $search;
			add_filter( 'posts_where' , array( $this, 'posts_where_suggest' ) );
			unset( $args_query['s'] );
			$query = new WP_Query( $args_query );
			remove_filter( 'posts_where' , array( $this, 'posts_where_suggest') );
		}
		$results = array();
		if ( $query->have_posts() ) {
			$html = '';
			while ( $query->have_posts() ) {
				$query->the_post();
				$results[] = array(
					'title' => get_post_field( 'post_title', get_the_ID() ),
					'url' => get_permalink( get_the_ID() )
				);
			}
			wp_reset_query();
			wp_send_json_success( $results );
		} else {
			wp_reset_query();
			wp_send_json_error( array( array( 'error' => 'not found', 'message' => __( 'Not Found!!!', 'dw-question-answer' ) ) ) );
		}
	}

	public function posts_where_suggest( $where ) {
		global $current_search;
		$first = true;
		$s = explode( ' ', $current_search );
		if ( count( $s ) > 0 ) {
			$where .= ' AND (';
			foreach ( $s as $w ) {
				if ( ! $first ) {
					$where .= ' OR ';
				}
				$where .= "post_title REGEXP '".preg_quote( $w )."'";
				$first = false;
			}
			$where .= ' ) ';
		}
		return $where;
	}
}