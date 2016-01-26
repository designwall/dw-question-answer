<?php
/**
 * The template for editing question and answer
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>
<form method="post">
	<?php if ( 'dwqa-question' == get_post_type() ) : ?>
	<?php $title = get_the_title(); ?>
	<p>
		<label><?php _e( 'Title', 'dwqa' ) ?></label>
		<input type="text" name="question_title" value="<?php echo $title ?>" tabindex="1">
	</p>
	<?php endif; ?>
	<p>
		<?php $content = get_post_field( 'post_content', get_the_ID() ); ?>
		<label><?php _e( 'Content', 'dwqa' ) ?></label>
		<?php dwqa_init_tinymce_editor( array( 'content' => $content, 'textarea_name' => get_post_type() . '_content' ) ) ?>
	</p>
	<input type="submit" name="dwqa-submit" value="<?php _e( 'Save Changes', 'dwqa' ) ?>" >
</form>