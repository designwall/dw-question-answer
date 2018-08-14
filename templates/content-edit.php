<?php
/**
 * The template for editing question and answer
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */
?>

<?php
$comment_id = isset( $_GET['comment_edit'] ) && is_numeric( $_GET['comment_edit'] ) ? intval( $_GET['comment_edit'] ) : false;
$edit_id = isset( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ? intval( $_GET['edit'] ) : ( $comment_id ? $comment_id : false );
if ( !$edit_id ) return;
$type = $comment_id ? 'comment' : ( 'dwqa-question' == get_post_type( $edit_id ) ? 'question' : 'answer' );
?>
<?php do_action( 'dwqa_before_edit_form' ); ?>
<form method="post" class="dwqa-content-edit-form" enctype="multipart/form-data">
	<?php if ( 'dwqa-question' == get_post_type( $edit_id ) ) : ?>
	<?php $title = dwqa_question_get_edit_title( $edit_id ) ?>
	<p>
		<label for="question_title"><?php _e( 'Title', 'dw-question-answer' ) ?></label>
		<input type="text" name="question_title" value="<?php echo $title ?>" tabindex="1">
	</p>
	<?php endif; ?>
	<?php $content = call_user_func( 'dwqa_' . $type . '_get_edit_content', $edit_id ); ?>
	<p><?php dwqa_init_tinymce_editor( array( 'content' => $content, 'textarea_name' => $type . '_content', 'wpautop' => true ) ) ?></p>
	<?php if ( 'dwqa-question' == get_post_type( $edit_id ) ) : ?>
	<p>
		<label for="question-category"><?php _e( 'Category', 'dw-question-answer' ) ?></label>
		<?php $category = wp_get_post_terms( $edit_id, 'dwqa-question_category' ); ?>
		<?php
			wp_dropdown_categories( array(
				'name'          => 'question-category',
				'id'            => 'question-category',
				'taxonomy'      => 'dwqa-question_category',
				'show_option_none' => __( 'Select question category', 'dw-question-answer' ),
				'hide_empty'    => 0,
				'quicktags'     => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' ),
				'selected'      => isset( $category[0]->term_id ) ? $category[0]->term_id : false,
			) );
		?>
	</p>
	<p>
		<label for="question-tag"><?php _e( 'Tag', 'dw-question-answer' ) ?></label>
		<input type="text" class="" name="question-tag" value="<?php dwqa_get_tag_list( get_the_ID(), true ); ?>" >
	</p>
	<?php endif; ?>
	<?php do_action('dwqa_after_show_content_edit', $edit_id); ?>
	<?php do_action( 'dwqa_before_edit_submit_button' ) ?>
	<input type="hidden" name="<?php echo $type ?>_id" value="<?php echo $edit_id ?>">
	<?php wp_nonce_field( '_dwqa_edit_' . $type ) ?>
	<input type="submit" name="dwqa-edit-<?php echo $type ?>-submit" value="<?php _e( 'Save Changes', 'dw-question-answer' ) ?>" >
</form>
<?php do_action( 'dwqa_after_edit_form' ); ?>
