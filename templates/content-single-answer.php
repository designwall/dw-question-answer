<?php
/**
 * The template for displaying single answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */
?>
<div class="<?php echo dwqa_post_class() ?>"
     itemprop="suggestedAnswer<?php if ( dwqa_is_the_best_answer() ) : ?> acceptedAnswer<?php endif; ?>" itemscope
     itemtype="http://schema.org/Answer">
    <aside class="dwqa-answer-vote" data-nonce="<?php echo wp_create_nonce( '_dwqa_answer_vote_nonce' ) ?>"
           data-post="<?php the_ID(); ?>">

        <span class="dwqa-vote-count" itemprop="upvoteCount"><?php echo dwqa_vote_count() ?></span>
        <a class="dwqa-vote dwqa-vote-up" href="#"><?php _e( 'Vote Up', 'dwqa' ); ?></a>
        <a class="dwqa-vote dwqa-vote-down" href="#"><?php _e( 'Vote Down', 'dwqa' ); ?></a>

    </aside>
	<?php if ( dwqa_current_user_can( 'edit_question', dwqa_get_question_from_answer_id() ) ) : ?>
		<?php $action = dwqa_is_the_best_answer() ? 'dwqa-unvote-best-answer' : 'dwqa-vote-best-answer'; ?>
        <aside>
            <a class="dwqa-pick-best-answer" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array(
				'answer' => get_the_ID(),
				'action' => $action
			), admin_url( 'admin-ajax.php' ) ), '_dwqa_vote_best_answer' ) ) ?>"><?php _e( 'Best Answer', 'dwqa' ) ?></a>
        </aside>
	<?php elseif ( dwqa_is_the_best_answer() ) : ?>
        <aside><span class="dwqa-pick-best-answer"><?php _e( 'Best Answer', 'dwqa' ) ?></span></aside>

	<?php endif; ?>
    <aside class="dwqa-answer-meta">
		<?php $user_id = get_post_field( 'post_author', get_the_ID() ) ? get_post_field( 'post_author', get_the_ID() ) : 0 ?>
		<?php printf( __( '<span><a href="%s"><span itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name">%s%s</span></span></a> %s answered %s ago</span>', 'dwqa' ), dwqa_get_author_link( $user_id ), get_avatar( $user_id, 48 ), get_the_author(), dwqa_print_user_badge( $user_id ), human_time_diff( get_post_time( 'U', true ) ) ) ?>
		<?php if ( 'private' == get_post_status() ) : ?>
            <span><?php _e( '&nbsp;&bull;&nbsp;', 'dwqa' ); ?></span>
            <span><?php _e( 'Private', 'dwqa' ) ?></span>
		<?php endif; ?>
        <span class="dwqa-answer-actions"><?php dwqa_answer_button_action(); ?></span>
    </aside>
    <span class="dwqa-answer-content" itemprop="text"><?php the_content(); ?>
</span>
</div>
<?php do_action( 'dwqa_after_show_content_answer', get_the_ID() ); ?>
<?php comments_template(); ?>
</div>

<!-- TODO Add parentItem schema attribute -->
