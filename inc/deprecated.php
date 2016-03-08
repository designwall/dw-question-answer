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

function dwqa_require_field_submit_question() {
	_deprecated_function( __FUNCTION__, '1.4.2', '' );
}

function dwqa_require_field_submit_answer() {
	_deprecated_function( __FUNCTION__, '1.4.2', '' );
}

function dwqa_single_postclass() {
	_deprecated_function( __FUNCTION__, '1.4.2', '' );
}

function dwqa_paged_query() {
	_deprecated_function( __FUNCTION__, '1.4.2', '' );
}

function dwqa_title( $title ) {
	_deprecated_function( __FUNCTION__, '1.4.2.1', '' );
}

function dwqa_get_author( $post_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.4.2.3', '' );
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