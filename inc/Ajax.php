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
	}

	public function delete_comment() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), '_dwqa_delete_comment' ) ) {
			wp_die( __( 'Are you cheating huh?', 'dwqa' ) );
		}

		if ( !dwqa_current_user_can( 'delete_comment' ) ) {
			wp_die( __( 'You do not have permission to edit comment.', 'dwqa' ) );
		}

		if ( ! isset( $_GET['comment_id'] ) ) {
			wp_die( __( 'Comment ID must be showed.', 'dwqa' ) );
		}

		wp_delete_comment( intval( $_GET['comment_id'] ) );
		$comment = get_comment( $_GET['comment_id'] );
		exit( wp_safe_redirect( dwqa_get_question_link( $comment->comment_post_ID ) ) );
	}

	function delete_answer() {
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

	public function flag_answer() {
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

	public function delete_question() {
		global $dwqa_general_settings;
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], '_dwqa_action_remove_question_nonce' ) || 'dwqa_delete_question' !== $_GET['action'] ) {
			wp_die( __( 'Are you cheating huh?', 'dwqa' ) );
		}

		if ( ! isset( $_GET['question_id'] ) ) {
			wp_die( __( 'Question is missing.', 'dwqa' ), 'error' );
		}

		if ( 'dwqa-question' !== get_post_type( $_GET['question_id'] ) ) {
			wp_die( __( 'This post is not question.', 'dwqa' ) );
		}

		if ( !dwqa_current_user_can( 'delete_answer' ) ) {
			wp_die( __( 'You do not have permission to delete this post.', 'dwqa' ) );
		}

		do_action( 'before_delete_post', $_GET['question_id'] );
		
		$id = wp_delete_post( $_GET['question_id'] );

		if ( is_wp_error( $id ) ) {
			wp_die( $id->get_error_message() );
		}

		do_action( 'dwqa_delete_question', $_GET['question_id'] );

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
				'message'   => __( 'You do not have permission to edit question status', 'dwqa' )
			) );
		}
	}
}