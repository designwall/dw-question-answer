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

function dwqa_add_answer() {
	global $dwqa;
	_deprecated_function( __FUNCTION__, '1.3.4', 'DWQA_Answer::insert' );
	$dwqa->insert();
}

class Walker_Category_DWQA {
	public function __construct() {
		_deprecated_function( __FUNCTION__, '1.3.4', 'DWQA_Walker_Category' );
		new DWQA_Walker_Category();
	}
}

class Walker_Tag_DWQA {
	public function __construct() {
		_deprecated_function( __FUNCTION__, '1.3.4', 'DWQA_Walker_Tag' );
		new DWQA_Walker_Tag();
	}
}

?>