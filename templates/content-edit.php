<?php
/**
 * The template for editing question and answer
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>

<?php $type = 'dwqa-question' == get_post_type() ? 'question' : 'answer'; ?>
<form method="post">
	<?php if ( 'dwqa-question' == get_post_type() ) : ?>
	<?php $title = dwqa_question_get_edit_title() ?>
	<p>
		<label><?php _e( 'Title', 'dwqa' ) ?></label>
		<input type="text" name="question_title" value="<?php echo $title ?>" tabindex="1">
	</p>
	<?php endif; ?>
	<p>
		<?php $content = call_user_func( 'dwqa_' . $type . '_get_edit_content' ); ?>
		<?php dwqa_init_tinymce_editor( array( 'content' => $content, 'textarea_name' => $type . '_content' ) ) ?>
	</p>
	<p>
		<?php $category = wp_get_post_terms( get_the_ID(), 'dwqa-question_category' ); ?>
		<?php 
			wp_dropdown_categories( array( 
				'name'          => 'question-category',
				'id'            => 'question-category',
				'taxonomy'      => 'dwqa-question_category',
				'show_option_none' => __( 'Select question category', 'dwqa' ),
				'hide_empty'    => 0,
				'quicktags'     => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' ),
				'selected'      => isset( $category[0]->term_id ) ? $category[0]->term_id : false,
			) );
		?>
	</p>
	<p>
	
	</p>
	<input type="submit" name="dwqa-submit" value="<?php _e( 'Save Changes', 'dwqa' ) ?>" >
</form>