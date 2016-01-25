<?php
/**
 * The template for displaying answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>

<?php
global $wp_query;
$answers = $wp_query->dwqa_answers;
?>

<div class="dwqa-answers">
	<?php do_action( 'dwqa_before_answers' ) ?>
	<div class="dwqa-answers-title"><?php _e( '2 Answers', 'dwqa' ) ?></div>
	<div class="dwqa-answers-list">
	<?php do_action( 'dwqa_before_answers_list' ) ?>
	<?php if ( $answers->have_posts() ) : ?>
		<?php while ( $answers->have_posts() ) : $answers->the_post(); ?>
			<?php $question_id = get_post_meta( get_the_ID(), '_question', true ) ?>
			<?php if ( ( 'private' == get_post_status() && ( dwqa_current_user_can( 'edit_answer' ) || dwqa_current_user_can( 'edit_question', $question_id ) ) ) || 'publish' == get_post_status() ) : ?>
				<?php dwqa_load_template( 'content', 'single-answer' ); ?>
			<?php endif; ?>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<?php dwqa_load_template( 'content', 'none' ) ?>
	<?php endif; ?>
	<?php do_action( 'dwqa_after_answers_list' ) ?>
	</div>
	<?php dwqa_load_template( 'answer', 'submit-form' ) ?>
	<?php do_action( 'dwqa_after_answers' ); ?>
</div>
