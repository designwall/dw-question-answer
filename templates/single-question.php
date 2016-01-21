<?php
/**
 * The template for displaying all single questions
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<div class="dwqa-single-question">
<?php if ( have_posts() ) : ?>
	<?php do_action( 'dwqa_before_single_question' ) ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php dwqa_load_template( 'content', 'single-question' ) ?>
	<?php endwhile; ?>
	<?php do_action( 'dwqa_after_single_question' ) ?>
<?php endif; ?>
</div>
