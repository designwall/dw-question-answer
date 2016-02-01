<?php
/**
 * The template for displaying question archive pages
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<div class="dwqa-questions-archive">
	<?php do_action( 'dwqa_before_questions_archive' ) ?>
		<div class="dwqa-questions-list">

		<?php do_action( 'dwqa_before_question_stickies' ); ?>
		<?php if ( dwqa_has_question_stickies() ) : ?>
			<?php while( dwqa_has_question_stickies() ) : dwqa_the_sticky() ?>
				<?php dwqa_load_template( 'content', 'question' ) ?>
			<?php endwhile; ?>
		<?php endif; ?>
		<?php do_action( 'dwqa_after_question_stickies' ); ?>

		<?php do_action( 'dwqa_before_questions_list' ) ?>
		<?php if ( dwqa_has_question() ) : ?>
			<?php while ( dwqa_has_question() ) : dwqa_the_question(); ?>
				<?php if ( ( ( 'private' == get_post_status() || 'pending' == get_post_status() ) && ( dwqa_current_user_can( 'edit_answer' ) || dwqa_current_user_can( 'edit_question', $question_id ) ) ) || 'publish' == get_post_status() ) : ?>
					<?php dwqa_load_template( 'content', 'question' ) ?>
				<?php endif; ?>
			<?php endwhile; ?>
			<?php dwqa_question_paginate_link() //the_posts_pagination( array( 'mid_size' => 4 ) ); ?>
		<?php else : ?>
			<?php dwqa_load_template( 'content', 'none' ) ?>
		<?php endif; ?>
		<?php do_action( 'dwqa_after_questions_list' ) ?>
		</div>
	<?php do_action( 'dwqa_after_questions_archive' ); ?>
</div>
