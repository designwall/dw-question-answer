<?php  

function dwqa_submit_question_form(){
    _deprecated_function( __FUNCTION__, '1.2.6', 'dwqa_load_template( "submit-question", "form" )' );
    dwqa_load_template( 'submit-question', 'form' );
}

function dwqa_user_question_number( $user_id ) {
    _deprecated_function( __FUNCTION__, '1.3.2', 'dwqa_user_question_count( "user_id" )' );
    dwqa_user_question_count( $user_id );
}

function dwqa_user_answer_number( $user_id ) {
    _deprecated_function( __FUNCTION__, '1.3.2', 'dwqa_user_answer_count( "user_id" )' );
	dwqa_user_answer_count( $user_id );
}

?>