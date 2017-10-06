<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'buddypress' ) ) {
	function buddypress() {
		return isset( $GLOBALS['bp'] ) ? $GLOBALS['bp'] : false;
	}
}

function dp_dwqa_screen_questions() {
	add_action( 'bp_template_content', 'bp_dwqa_question_content' );
	bp_core_load_template( apply_filters( 'bp_dwqa_screen_question', 'members/single/plugins' ) );
}
function dp_dwqa_screen_answers() {
	add_action( 'bp_template_content', 'bp_dwqa_answer_content' );
	bp_core_load_template( apply_filters( 'bp_dwqa_screen_question', 'members/single/plugins' ) );
}
function dp_dwqa_screen_comments() {
	add_action( 'bp_template_content', 'bp_dwqa_comment_content' );
	bp_core_load_template( apply_filters( 'bp_dwqa_screen_question', 'members/single/plugins' ) );
}

//question
function bp_dwqa_question_content() {
	add_filter('dwqa_prepare_archive_posts', 'dp_dwqa_question_filter_query',12);
	remove_action( 'dwqa_before_questions_archive', 'dwqa_archive_question_filter_layout', 12 );
	include(DWQA_DIR .'templates/bp-archive-question.php');
}
function dp_dwqa_question_filter_query($query){
	$current_user_id = get_current_user_id();
	$query['author'] = $current_user_id;
	return $query;
}

//answer
function bp_dwqa_answer_content() {
	add_filter('dwqa_prepare_archive_posts', 'dp_dwqa_answer_filter_query',12);
	remove_action( 'dwqa_before_questions_archive', 'dwqa_archive_question_filter_layout', 12 );
	include(DWQA_DIR .'templates/bp-archive-question.php');	
}
function dp_dwqa_answer_filter_query($query){
	$current_user_id = get_current_user_id();
	$query['author'] = $current_user_id;
	return $query;
}
