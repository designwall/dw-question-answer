<?php  

function dwqa_get_latest_action_date( $question = false ){
	if( !$question ) {
		$question = get_the_ID();
	}
	$latest_answer = dwqa_get_latest_answer( $question );
	if( $latest_answer ) {
		global $post;
		$post = $latest_answer;
		setup_postdata( $post );
		$date = apply_filters( 'get_the_date', $latest_answer->post_date, get_option( 'date_format' ) );
		wp_reset_postdata();
		return sprintf( __('answered %s by','dwqa'), $date);
	}
	return sprintf( __('asked %s by','dwqa'), get_the_date());
}


?>