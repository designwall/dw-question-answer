<?php  
/**
 * Use transient api to imporve speed of DW Question Answer
 */

add_action( 'dwqa_add_answer', 'dwqa_update_transient_when_add_answer', 10, 2 );
function dwqa_update_transient_when_add_answer( $answer_id, $question_id ) {
	// Update cache for latest answer of this question 
	$answer = get_post( $answer_id );
	set_transient( 'dwqa_latest_answer_for_' . $question_id, $answer, 60*60*6 );
	delete_transient( 'dwqa_answer_count_for_' . $question_id );
}


add_action( 'dwqa_delete_answer', 'dwqa_update_transient_when_remove_answer', 10, 2 );
function dwqa_update_transient_when_remove_answer( $answer_id, $question_id ) {
	// Remove Cached Latest Answer
	delete_transient( 'dwqa_latest_answer_for_' . $question_id );
	delete_transient( 'dwqa_answer_count_for_' . $question_id );
}

?>