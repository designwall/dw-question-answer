<?php  
$start = microtime(true);
global $current_user, $post, $dwqa_options;
$ans_cur_page = isset( $_GET['ans-page'] ) ? intval( $_GET['ans-page'] ) : 1;
$question_id = $post->ID;
$question = $post;
$best_answer_id = dwqa_get_the_best_answer( $question_id );
$draft_answer_id = dwqa_user_get_draft( $question_id );
// get all answers for this posts
$status = dwqa_current_user_can( 'edit_question', $question_id ) ? array( 'publish', 'private' ) : 'publish';
$args = array(
	'post_type' 		=> 'dwqa-answer',
	'order'      		=> 'ASC',
	'page'				=> $ans_cur_page,
	'paged'				=> $ans_cur_page,
	'meta_query' 		=> array(
		array(
			'key' => '_question',
			'value' => array( $question_id ),
			'compare' => 'IN',
		),
	),
	'post_status' => $status,
);
$answers = new WP_Query( $args );

if ( $answers->found_posts > 0 || ! empty( $draft_answer_id ) ) { 
// Display answers
?>
	<h3 class="dwqa-headline">
	<?php 
		printf( '<span class="answer-count"><span class="digit">%d</span> %s</span>',
			$answers->found_posts,
			_n( 'answer', 'answers', $answers->found_posts, 'dwqa' )
		);
	?>
	</h3>
	
	<div class="dwqa-list-answers">
	<?php
	// Display best answer
	if ( $best_answer_id ) {
		$post = get_post( $best_answer_id );
		setup_postdata( $post );
		dwqa_load_template( 'content', 'answer' );
	}
	// Display other answers
	global $position; $position = 1;
	while ( $answers->have_posts() ) { $answers->the_post();
		$answer = $post;
		if ( $best_answer_id && $best_answer_id == $answer->ID ) {
			continue;
		}
		if ( ( $answer->post_status == 'private' && ( dwqa_current_user_can( 'edit_answer', $answer->ID ) || dwqa_current_user_can( 'edit_question', $question_id ) ) ) || $answer->post_status == 'publish' ) {
				dwqa_load_template( 'content', 'answer' );
		}
		$position++;
	} 
	unset( $position );
	//Drafts
	if ( current_user_can( 'edit_posts' ) && $draft_answer_id ) {
		$post = get_post( $draft_answer_id );
		setup_postdata( $post );
		dwqa_load_template( 'content', 'answer' );
	} 
	?>
	</div>

	<?php 
} else {
	if ( ! dwqa_current_user_can( 'read_answer' ) ) {
		echo '<div class="alert">'.__( 'You do not have permission to view answers','dwqa' ).'</div>';
	}
}
wp_reset_query(); //End answer listing

// Answer Page Naving
if ( $answers->max_num_pages > 1 ) {
	$question_url = get_permalink( $question_id );
	echo '<h3 class="dwqa-answers-page-navigation-head">'.sprintf( __( 'Answer page %d', 'dwqa' ), $ans_cur_page ).'</h3>';
	echo '<ul class="dwqa-answers-page-navigation">';
	for ( $i = 1; $i <= $answers->max_num_pages; $i++ ) { 
		echo '<li class="'.( $ans_cur_page == $i ? 'active' : '' ).'"><a href="'.add_query_arg( 'ans-page', $i, $question_url ).'">'.$i.'</a></li>';
	}
	echo '<ul>';
}

//Create answer form
if ( dwqa_is_closed( $question_id ) ) {
	echo '<p class="alert">'.__( 'This question is now closed','dwqa' ).'</p>';
	return false;
}

if ( dwqa_current_user_can( 'post_answer' ) ) {
	dwqa_load_template( 'answer', 'submit-form' );
} else { ?>
	<?php if ( is_user_logged_in() ) { ?>
		<div class="alert"><?php _e( 'You do not have permission to submit answer.','dwqa' ) ?></div>
	<?php } else { ?>
	<h3 class="dwqa-title">
		<?php 
			printf( '%1$s <a href="%2$s" title="%3$s">%3$s</a> %4$s', __( 'Please login or', 'dwqa' ), wp_registration_url(), __( 'Register', 'dwqa' ), __( 'to Submit Answer', 'dwqa' ) );
		?>
	</h3>
	<div class="login-box">
		<?php wp_login_form( array( 'redirect'  => get_post_permalink( $question_id ) ) ); ?>
	</div>
	<?php
	}
}

$time_elapsed_us = microtime(true) - $start;
var_dump( $time_elapsed_us );
