<?php
/**
 * The template for displaying answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */
?>
<div class="dwqa-answers">
	<?php do_action( 'dwqa_before_answers' ) ?>
	<?php if ( dwqa_has_answers() ) : ?>
	<div class="dwqa-answers-title"><?php printf( __( '%s Answers', 'dw-question-answer' ), dwqa_question_answers_count( get_the_ID() ) ) ?></div>
	<div class="dwqa-answers-list">
		<?php do_action( 'dwqa_before_answers_list' ) ?>
			<?php while ( dwqa_has_answers() ) : dwqa_the_answers(); ?>
				<?php $question_id = dwqa_get_post_parent_id( get_the_ID() ); ?>
				<?php if ( ( 'private' == get_post_status() && ( dwqa_current_user_can( 'edit_answer', get_the_ID() ) || dwqa_current_user_can( 'edit_question', $question_id ) ) ) || 'publish' == get_post_status() ) : ?>
					<?php dwqa_load_template( 'content', 'single-answer' ); ?>
				<?php endif; ?>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		<?php do_action( 'dwqa_after_answers_list' ) ?>
	</div>
	<?php endif; ?>
	<?php if ( dwqa_current_user_can( 'post_answer' ) && !dwqa_is_closed( get_the_ID() ) ) : ?>
		<?php dwqa_load_template( 'answer', 'submit-form' ) ?>
	<?php endif; ?>
	<?php do_action( 'dwqa_after_answers' ); ?>
</div>
