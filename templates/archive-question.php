<?php
/**
 * The template for displaying question archive pages
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<?php get_header( 'dwqa' ); ?>
<div class="dwqa-questions-archive">
	<?php do_action( 'dwqa_before_questions_archive' ) ?>
		<div class="dwqa-questions-list">
		<?php if ( have_posts() ) : ?>
			<?php do_action( 'dwqa_before_questions_list' ) ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php dwqa_load_template( 'content', 'question' ) ?>
			<?php endwhile; ?>
			<?php do_action( 'dwqa_after_questions_list' ) ?>
		<?php else : ?>
			<?php dwqa_load_template( 'content', 'none' ) ?>
		<?php endif; ?>
		</div>
	<?php do_action( 'dwqa_after_questions_archive' ); ?>
</div>
<?php get_footer( 'dwqa' ); ?>
