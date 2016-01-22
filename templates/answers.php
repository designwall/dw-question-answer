<?php
/**
 * The template for displaying answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<div class="dwqa-answers">
	<?php do_action( 'dwqa_before_answers' ) ?>
		<div class="dwqa-answers-list">
		<?php do_action( 'dwqa_before_answers_list' ) ?>
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php dwqa_load_template( 'content', 'single-answer' ) ?>
				<?php comments_template() ?>
			<?php endwhile; ?>
		<?php else : ?>
			<?php dwqa_load_template( 'content', 'none' ) ?>
		<?php endif; ?>
		</div>
		<?php do_action( 'dwqa_after_answers_list' ) ?>
	<?php do_action( 'dwqa_after_answers' ); ?>
</div>
