<?php
/**
 * The template for displaying single questions
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<article class="dwqa-question-item">
	<div class="dwqa-question-vote">
		<span class="dwqa-vote-count">0</span>
		<a class="dwqa-vote dwqa-vote-up" href="#"><?php _e( 'Vote Up', 'dwqa' ); ?></a>
		<a class="dwqa-vote dwqa-vote-down" href="#"><?php _e( 'Vote Down', 'dwqa' ); ?></a>
	</div>
	<div class="dwqa-question-meta">
		<?php echo dwqa_get_latest_action_date(); ?>
		<span class="dwqa-question-actions"><?php dwqa_question_button_action() ?></span>
	</div>
	<div class="dwqa-question-content"><?php the_content(); ?></div>
	<footer class="dwqa-question-footer">
		<div class="dwqa-question-meta"><?php echo get_the_term_list( get_the_ID(), 'dwqa-question_tag', __( 'Question Tagged: ', 'dwqa' ) , ', ' ); ?></div>
	</footer>
	<?php comments_template(); ?>
</article>
