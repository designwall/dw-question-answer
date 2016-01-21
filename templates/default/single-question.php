<?php
/**
 *  Template use for display a single question
 *
 *  @since  DW Question Answer 1.0
 */
	global $current_user, $post;
?>
<div class="dwqa-single-question">
<?php if ( have_posts() ) : ?>
	<?php do_action( 'dwqa_before_single_question' ) ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php dwqa_load_template( 'content', 'single-question' ) ?>
		<?php dwqa_load_template( 'content', 'answers' ) ?>
	<?php endwhile; ?>
	<?php do_action( 'dwqa_after_single_question' ) ?>
<?php endif; ?>
</div>