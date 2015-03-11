<?php  

//Update question status when have new comment
function dwqa_reopen_question_have_new_comment( $comment_ID ){
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
add_action( 'wp_insert_comment', 'dwqa_reopen_question_have_new_comment' );

//Auto close question when question was resolved longtime
function dwqa_schedule_events() {
	if ( ! wp_next_scheduled( 'dwqa_hourly_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'dwqa_hourly_event' );
	}
}
add_action( 'wp', 'dwqa_schedule_events' );

function dwqa_do_this_hourly() {
	$closed_questions = wp_cache_get( 'dwqa-closed-question' );
	if ( false == $closed_questions ) {
		global $wpdb;
		$query = "SELECT `{$wpdb->posts}`.ID FROM `{$wpdb->posts}` JOIN `{$wpdb->postmeta}` ON `{$wpdb->posts}`.ID = `{$wpdb->postmeta}`.post_id WHERE 1=1 AND `{$wpdb->postmeta}`.meta_key = '_dwqa_status' AND `{$wpdb->postmeta}`.meta_value = 'closed' AND `{$wpdb->posts}`.post_status = 'publish' AND `{$wpdb->posts}`.post_type = 'dwqa-question'";
		$closed_questions = $wpdb->get_results( $query );
		
		wp_cache_set( 'dwqa-closed-question', $closed_questions );
	}

	if ( ! empty( $closed_questions ) ) {
		foreach ( $closed_questions as $q ) {
			$resolved_time = get_post_meta( $q->ID, '_dwqa_resolved_time', true );
			if ( dwqa_is_resolved( $q->ID ) && ( time() - $resolved_time > (3 * 24 * 60 * 60 ) ) ) {
				update_post_meta( $q->ID, '_dwqa_status', 'resolved' );
			}
		}
	} 
}
add_action( 'dwqa_hourly_event', 'dwqa_do_this_hourly' );


?>