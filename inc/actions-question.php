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


function dwqa_prepare_archive_posts(){
    global $wp_query,$dwqa_general_settings;
    
    $posts_per_page = isset($dwqa_general_settings['posts-per-page']) ?  $dwqa_general_settings['posts-per-page'] : 5;
    $query = array(
        'post_type' => 'dwqa-question',
        'posts_per_page' => $posts_per_page
    );
    if( is_tax('dwqa-question_category') ) {
        $query['dwqa-question_category'] = get_query_var('dwqa-question_category');
    } 
    if( is_tax('dwqa-question_tag') ) {
        $query['dwqa-question_tag'] = get_query_var('dwqa-question_tag');
    } 
    $paged = get_query_var( 'paged' );
    $query['paged'] = $paged ? $paged : 1; 
    $sticky_questions = get_option( 'dwqa_sticky_questions' );

    if( $sticky_questions ) {
        $query['post__not_in'] = $sticky_questions;
    }
    global $dwqa_filter;
    add_filter( 'posts_join', array( $dwqa_filter, 'join_filter_default') );
    add_filter( 'posts_orderby', array( $dwqa_filter, 'order_filter_default')  );
    query_posts( $query );
    remove_filter( 'posts_join', array( $dwqa_filter, 'join_filter_default') );
    remove_filter( 'posts_orderby', array( $dwqa_filter, 'order_filter_default')  );
}
add_action( 'dwqa-prepare-archive-posts', 'dwqa_prepare_archive_posts' );
?>