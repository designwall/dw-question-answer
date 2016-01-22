<?php
/**
 * The template for displaying single questions
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<article class="dwqa-question">
	<div class="dwqa-question-vote">
		<span class="dwqa-vote-count">0</span>
		<a class="dwqa-vote dwqa-vote-up" href="#"><?php _e( 'Vote Up', 'dwqa' ); ?></a>
		<a class="dwqa-vote dwqa-vote-down" href="#"><?php _e( 'Vote Down', 'dwqa' ); ?></a>
	</div>
	<div class="dwqa-question-meta">
		<?php echo dwqa_get_latest_action_date(); ?>
		<span class="pull-right"><a href="#">Follow</a> <a href="#">Edit</a> <a href="#">Delete</a></span>
	</div>
	<div class="dwqa-question-content"><?php the_content(); ?></div>
	<footer class="dwqa-question-footer">
		<div class="dwqa-question-meta">Question Tagged: <a href="#">Abc</a>, <a href="#">Xyz</a></div>
	</footer>
	<?php comments_template(); ?>
</article>
<?php // dwqa_load_template( 'content', 'question-comments' ) ?>
