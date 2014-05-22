<?php  

function dwqa_get_latest_action_date( $question = false ){
	if( !$question ) {
		$question = get_the_ID();
	}
	$latest_answer = dwqa_get_latest_answer( $question );
	if( $latest_answer ) {
		$date = dwqa_human_time_diff( strtotime( $latest_answer->post_date ), false, get_option( 'date_format' ) );
		return sprintf( __('answered %s','dwqa'), $date);
	}
	return sprintf( __('asked %s','dwqa'), get_the_date());
}


?>