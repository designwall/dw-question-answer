<?php
/**
 * The template for displaying single questions
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<article class="dwqa-question">
	<header class="dwqa-question-title"><?php the_title(); ?></header>
	<div class="dwqa-question-meta">
		<?php echo dwqa_get_latest_action_date(); ?>
	</div>
	<div class="dwqa-question-stats">
		<span class="dwqa-views-count">
			<?php $views_count = dwqa_question_views_count() ?>
			<?php printf( __( '<strong>%1$s</strong> views', 'dwqa' ), $views_count ); ?>
		</span>
		<span class="dwqa-answers-count">
			<?php $answers_count = dwqa_question_answers_count(); ?>
			<?php printf( __( '<strong>%1$s</strong> answers', 'dwqa' ), $answers_count ); ?>
		</span>
		<span class="dwqa-votes-count">
			<?php $vote_count = dwqa_vote_count() ?>
			<?php printf( __( '<strong>%1$s</strong> votes', 'dwqa' ), $vote_count ); ?>
		</span>
	</div>
	<div class="dwqa-question-content"><?php the_content(); ?></div>
	<footer class="dwqa-question-footer">
		<?php // Question Tags ?>
	</footer>
</article>
