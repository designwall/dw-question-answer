<?php  

function dwqa_get_latest_action_date( $question = false, $before = '<span>', $after = '</span>' ){
	if ( ! $question ) {
		$question = get_the_ID();
	}
	global $post;

	$message = '';

	$latest_answer = dwqa_get_latest_answer( $question );
	$last_activity_date = $latest_answer ? $latest_answer->post_date : get_post_field( 'post_date', $question );
	$post_id = $latest_answer ? $latest_answer->ID : $question;
	$author_id = $post->post_author;
	if ( $author_id == 0 || dwqa_is_anonymous( $post_id ) ) {
		$anonymous_name = get_post_meta( $post_id, '_dwqa_anonymous_name', true );
		if ( $anonymous_name ) {
			$author_link = $anonymous_name . ' ';
		} else {
			$author_link = __( 'Anonymous', 'dwqa' )  . ' ';
		}
	} else {
		$display_name = get_the_author_meta( 'display_name', $author_id );
		$author_url = get_author_posts_url( $author_id );
		$author_avatar = wp_cache_get( 'avatar_of_' . $author_id, 'dwqa' );
		if ( false === $author_avatar ) {
			$author_avatar = get_avatar( $author_id, 12 );
			wp_cache_set( 'avatar_of_'. $author_id, $author_avatar, 'dwqa', 60*60*24*7 );
		}
		$author_link = sprintf(
			'<span class="dwqa-author"><span class="dwqa-user-avatar">%4$s</span> <a href="%1$s" title="%2$s" rel="author">%3$s</a></span>',
			$author_url,
			esc_attr( sprintf( __( 'Posts by %s' ), $display_name ) ),
			$display_name,
			$author_avatar
		);
	}
	
	if ( $last_activity_date && $post->last_activity_type == 'answer' ) {
		$date = dwqa_human_time_diff( strtotime( $last_activity_date ), false, get_option( 'date_format' ) );
		return sprintf( __( '%s answered <span class="dwqa-date">%s</span>', 'dwqa' ), $author_link, $date );
	}
	return sprintf( __( '%s asked <span class="dwqa-date">%s</span>', 'dwqa' ), $author_link, get_the_date() );
}


function dwqa_prepare_archive_posts() {
	global $wp_query,$dwqa_general_settings;
	
	$posts_per_page = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : 5;
	$query = array(
		'post_type' => 'dwqa-question',
		'posts_per_page' => $posts_per_page,
		'orderby'	=> 'modified',
	);
	if ( is_tax( 'dwqa-question_category' ) ) {
		$query['dwqa-question_category'] = get_query_var( 'dwqa-question_category' );
	} 
	if ( is_tax( 'dwqa-question_tag' ) ) {
		$query['dwqa-question_tag'] = get_query_var( 'dwqa-question_tag' );
	} 
	$paged = get_query_var( 'paged' );
	$query['paged'] = $paged ? $paged : 1; 
	$sticky_questions = get_option( 'dwqa_sticky_questions' );

	if ( $sticky_questions ) {
		$query['post__not_in'] = $sticky_questions;
	}
	if ( is_user_logged_in() ) {
		$query['post_status'] = array( 'publish', 'private', 'pending' );
	}
	global $dwqa_filter;
	query_posts( $query );
}
add_action( 'dwqa-prepare-archive-posts', 'dwqa_prepare_archive_posts' );

function dwqa_after_archive_posts() {
	wp_reset_query();
	wp_reset_postdata();
}
add_action( 'dwqa-after-archive-posts', 'dwqa_after_archive_posts' );


?>
