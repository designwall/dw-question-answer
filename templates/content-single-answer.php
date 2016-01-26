<?php
/**
 * The template for displaying single answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<div class="dwqa-answer-item">
	<div class="dwqa-answer-vote">
		<span class="dwqa-vote-count">0</span>
		<a class="dwqa-vote dwqa-vote-up" href="#"><?php _e( 'Vote Up', 'dwqa' ); ?></a>
		<a class="dwqa-vote dwqa-vote-down" href="#"><?php _e( 'Vote Down', 'dwqa' ); ?></a>
	</div>
	<div class="dwqa-answer-meta">
		<?php echo dwqa_get_latest_action_date(); ?>
		<span class="dwqa-answer-actions"><?php dwqa_answer_button_action(); ?></span>
	</div>
	<div class="dwqa-answer-content"><?php the_content(); ?></div>
	<?php comments_template(); ?>
</div>
