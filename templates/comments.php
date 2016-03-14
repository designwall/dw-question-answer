<?php
/**
 * The template for displaying comments form
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */
?>

<?php if ( comments_open() ) : ?>
<div class="dwqa-comments">
	<?php do_action( 'dwqa_before_comments' ) ?>
	<div class="dwqa-comments-list">
		<?php do_action( 'dwqa_before_comments_list' ); ?>
		<?php if ( have_comments() ) : ?>
		<?php wp_list_comments( array( 'callback' => 'dwqa_question_comment_callback' ) ); ?>
		<?php endif; ?>
		<?php do_action( 'dqwa_after_comments_list' ); ?>
	</div>
	<?php if ( ! dwqa_is_closed( get_the_ID() ) && dwqa_current_user_can( 'post_comment' ) ) : ?>
		<?php
			$args = array(
				'id_form' => 'comment_form_' . get_the_ID(),
			);
		?>
		<?php dwqa_comment_form( $args ); ?>
	<?php endif; ?>
	<?php do_action( 'dwqa_after_comments' ); ?>
</div>
<?php endif; ?>
