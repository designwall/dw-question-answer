<?php
/**
 * The template for displaying answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<?php do_action( 'dwqa_before_comment_form' ) ?>
<div class="dwqa-comments">
	<?php do_action( 'dwqa_before_comment_list' ); ?>
	<div class="dwqa-comments-list">
		<?php wp_list_comments( array( 'callback' => 'dwqa_question_comment_callback' ) ); ?>

		<?php if ( ! dwqa_is_closed( get_the_ID() ) && dwqa_current_user_can( 'post_comment' ) ) : ?>
			<?php
				$args = array(
					'comment_field' => '<textarea id="comment" name="comment" aria-required="true" placeholder="' . __( 'Write a comment ...', 'dwqa' ).'"></textarea>',
					'comment_notes_before' => '',
					'logged_in_as' => '',
					'comment_notes_after' => '',
					'id_form' => 'comment_form_' . get_the_ID(),
				);
			?>
			<?php dwqa_comment_form( $args ); ?>
		<?php endif; ?>

	</div>
	<?php do_action( 'dqwa_after_comment_list' ); ?>
</div>
<?php do_action( 'dwqa_after_comment_form' ); ?>
