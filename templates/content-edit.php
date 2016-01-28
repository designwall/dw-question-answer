<?php
/**
 * The template for editing question and answer
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>

<?php 
$edit_id = isset( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ? $_GET['edit'] : false;
if ( !$edit_id ) return;
$type = 'dwqa-question' == get_post_type( $edit_id ) ? 'question' : 'answer';
?>
<?php do_action( 'dwqa_before_edit_form' ); ?>
<form method="post">
	<?php if ( 'dwqa-question' == get_post_type( $edit_id ) ) : ?>
	<?php $title = dwqa_question_get_edit_title( $edit_id ) ?>
	<p>
		<label><?php _e( 'Title', 'dwqa' ) ?></label>
		<input type="text" name="question_title" value="<?php echo $title ?>" tabindex="1">
	</p>
	<?php endif; ?>
	<?php $content = call_user_func( 'dwqa_' . $type . '_get_edit_content', $edit_id ); ?>
	<?php dwqa_init_tinymce_editor( array( 'content' => $content, 'textarea_name' => $type . '_content' ) ) ?>
	<?php if ( 'dwqa-question' == get_post_type( $edit_id ) ) : ?>
	<p>
		<?php $category = wp_get_post_terms( $edit_id, 'dwqa-question_category' ); ?>
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
		<input type="text" class="" name="question-tag" value="<?php echo dwqa_get_tag_list(); ?>" >
	</p>
	<?php endif; ?>
	<input type="hidden" name="<?php echo $type ?>_id" value="<?php echo $edit_id ?>">
	<?php wp_nonce_field( '_dwqa_edit_' . $type ) ?>
	<input type="submit" name="dwqa-edit-<?php echo $type ?>-submit" value="<?php _e( 'Save Changes', 'dwqa' ) ?>" >
</form>
<?php do_action( 'dwqa_after_edit_form' ); ?>