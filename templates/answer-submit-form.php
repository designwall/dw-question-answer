<?php
/**
 * The template for displaying answer submit form
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>

<div class="dwqa-answer-submit-form">
	<?php do_action( 'dwqa_before_answer_submit_form' ); ?>
	<form name="dwqa-answer-form" id="dwqa-answer-form" method="post">
		<textarea rows="2" id="dwqa-answer-question-editor" name="answer-content"></textarea>
		<input type="checkbox" name="private" value="1">
		<input type="submit" name="submit-answer" class="dwqa-btn dwqa-btn-primary" value="<?php _e( 'Add answer', 'dwqa' ) ?>">
		<input type="hidden" name="question_id" value="<?php the_ID(); ?>">
		<input type="hidden" name="dwqa-action" value="add-answer">
		<?php wp_nonce_field( '_dwqa_add_new_answer' ) ?>
	</form>
	<?php do_action( 'dwqa_after_answer_submit_form' ); ?>
</div>
