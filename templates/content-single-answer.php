<?php
/**
 * The template for displaying single answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<div class="dwqa-answer-item">
	<div class="dwqa-answer-vote" data-nonce="<?php echo wp_create_nonce( '_dwqa_answer_vote_nonce' ) ?>" data-post="<?php the_ID(); ?>">
		<span class="dwqa-vote-count"><?php echo dwqa_vote_count() ?></span>
		<a class="dwqa-vote dwqa-vote-up" href="#"><?php _e( 'Vote Up', 'dwqa' ); ?></a>
		<a class="dwqa-vote dwqa-vote-down" href="#"><?php _e( 'Vote Down', 'dwqa' ); ?></a>
	</div>
	<div class="dwqa-answer-meta">
		<?php $user_id = get_post_field( 'post_author', get_the_ID() ) ? get_post_field( 'post_author', get_the_ID() ) : 0 ?>
		<?php printf( __( '<span><a href="%s">%s%s</a> %s answered %s ago</span>', 'dwqa' ), dwqa_get_author_link( $user_id ), get_avatar( $user_id, 48 ), dwqa_get_author(), dwqa_print_user_badge( $user_id ), human_time_diff( get_post_time( 'U' ) ) ) ?>
		<span class="dwqa-answer-actions"><?php dwqa_answer_button_action(); ?></span>
	</div>
	<div class="dwqa-answer-content"><?php the_content(); ?></div>
	<?php comments_template(); ?>
</div>
