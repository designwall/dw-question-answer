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
	<div class="dwqa-answers-list">
	<?php do_action( 'dwqa_before_answers_list' ) ?>
	<?php if ( $answers->have_posts() ) : ?>
		<?php while ( $answers->have_posts() ) : $answers->the_post(); ?>
			<?php dwqa_load_template( 'content', 'single-answer' ) ?>
			<?php comments_template() ?>
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
