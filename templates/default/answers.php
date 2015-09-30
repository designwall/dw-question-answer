<?php  
global $post, $dwqa_options, $wp_query;
$current_user = wp_get_current_user();
$question_id = get_the_ID();
$draft_answers = dwqa_user_get_draft( $question_id );
$answers =  $wp_query->dwqa_answers;

if (  $answers->have_posts() ) {
?>
	<h3 class="dwqa-headline">
	<?php 
		printf( '<span class="answer-count"><span class="digit">%d</span> %s</span>',
			$answers->found_posts,
			_n( 'answer', 'answers', $answers->found_posts, 'dwqa' )
		);
	?>
	</h3>
<?php
}
if ( $answers->found_posts > 0 || ! empty( $draft_answers ) ) { 
// Display answers
?>
	<div class="dwqa-list-answers">
	<?php
	// Display best answer
	if ( $answers->best_answer ) {
		$post = get_post( $answers->best_answer ); setup_postdata( $post );
		dwqa_load_template( 'content', 'answer' );
		wp_reset_postdata();
	}
	// Display other answers
	global $position; $position = 1;
	while ( $answers->have_posts() ) { $answers->the_post();
		$answer = $post;
		if ( ( $answer->post_status == 'private' && ( dwqa_current_user_can( 'edit_answer', $answer->ID ) || dwqa_current_user_can( 'edit_question', $question_id ) ) ) || $answer->post_status == 'publish' ) {
				dwqa_load_template( 'content', 'answer' );
		}
		$position++;
	} 
	wp_reset_postdata(); // We do not replace the main query so reset query is no need
	unset( $position ); // Remove global variable

	//Drafts
	if ( current_user_can( 'edit_posts' ) && ! empty( $draft_answers ) ) {
		foreach ($draft_answers as $draft) {
			$post = get_post( $draft );
			setup_postdata( $post );
			dwqa_load_template( 'content', 'answer' );
		}
		wp_reset_postdata();
	} 

	// Answer Page Naving
	if ( $answers->max_num_pages > 1 ) {
		$question_url = get_permalink();
		$ans_cur_page = isset( $_GET['ans-page'] ) ? intval( $_GET['ans-page'] ) : 1;

		echo '<h3 class="dwqa-answers-page-navigation-head">'.sprintf( __( 'Answer page %d', 'dwqa' ), $ans_cur_page ).'</h3>';
		echo '<div class="dwqa-answers-page-navigation">';
		echo paginate_links( array(
			'base' => esc_url( add_query_arg( 'ans-page', '%#%', $question_url ) ),
			'format'             => '',
			'current' => $ans_cur_page,
			'total' => $answers->max_num_pages,
		) );
		echo '</div>';
	}
	?>

	</div>
	
	<?php 
} else {
	if ( ! dwqa_current_user_can( 'read_answer' ) ) {
		echo '<div class="alert">'.__( 'You do not have permission to view answers','dwqa' ).'</div>';
	}
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
