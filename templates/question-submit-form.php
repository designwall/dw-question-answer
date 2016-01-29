<?php
/**
 * The template for displaying single answers
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>

<?php do_action( 'dwqa_before_question_submit_form' ); ?>
<form method="post" class="dwqa-content-edit-form">
	<p>
		<label for="question_title"><?php _e( 'Title', 'dwqa' ) ?></label>
		<?php $title = isset( $_POST['question-title'] ) ? $_POST['question-title'] : ''; ?>
		<input type="text" data-nonce="<?php echo wp_create_nonce( '_dwqa_filter_nonce' ) ?>" id="question-title" name="question-title" value="<?php echo $title ?>" tabindex="1">
	</p>
	<?php $content = isset( $_POST['question-content'] ) ? $_POST['question-content'] : ''; ?>
	<p><?php dwqa_init_tinymce_editor( array( 'content' => $content, 'textarea_name' => 'question-content', 'id' => 'question-content' ) ) ?></p>
	<p>
		<label for="question-status"><?php _e( 'Status', 'dwqa' ) ?></label>
		<select class="dwqa-select" id="question-status" name="question-status">
			<optgroup label="<?php _e( 'Who can see this?', 'dwqa' ) ?>">
				<option value="publish"><?php _e( 'Public', 'dwqa' ) ?></option>
				<option value="private"><?php _e( 'Only Me &amp; Admin', 'dwqa' ) ?></option>
			</optgroup>
		</select>
	</p>
	<p>
		<label for="question-category"><?php _e( 'Category', 'dwqa' ) ?></label>
		<?php
			wp_dropdown_categories( array(
				'name'          => 'question-category',
				'id'            => 'question-category',
				'taxonomy'      => 'dwqa-question_category',
				'show_option_none' => __( 'Select question category', 'dwqa' ),
				'hide_empty'    => 0,
				'quicktags'     => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' ),
				'selected'      => isset( $_POST['question-category'] ) ? $_POST['question-category'] : false,
			) );
		?>
	</p>
	<p>
		<label for="question-tag"><?php _e( 'Tag', 'dwqa' ) ?></label>
		<?php $tags = isset( $_POST['question-tag'] ) ? $_POST['question-tag'] : ''; ?>
		<input type="text" class="" name="question-tag" value="<?php echo $tags ?>" >
	</p>
	<div class="question-signin">
		<?php do_action( 'dwqa_submit_question_ui' ); ?>
	</div>
	<?php wp_nonce_field( '_dwqa_submit_question' ) ?>
	<?php dwqa_load_template( 'captcha', 'form' ); ?>
	<input type="submit" name="dwqa-question-submit" value="<?php _e( 'Submit', 'dwqa' ) ?>" >
</form>
<?php do_action( 'dwqa_after_question_submit_form' ); ?>