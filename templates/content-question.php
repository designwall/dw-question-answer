<?php
/**
 * The template for displaying question content
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<article class="dwqa-question">
	<header class="dwqa-question-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></header>
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
</article>
