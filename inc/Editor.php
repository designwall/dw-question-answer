<?php  

function dwqa_init_tinymce_editor( $args = array() ) {
	global $dwqa;
	$dwqa->editor->display( $args );
}

function dwqa_paste_srtip_disable( $mceInit ){
	$mceInit['paste_strip_class_attributes'] = 'none';
	return $mceInit;
}

class DWQA_Editor {

	public function __construct() {

		add_action( 'init', array( $this, 'tinymce_addbuttons' ) );
		//Ajaxs
		add_action( 'wp_ajax_dwqa-editor-update-answer-init', array( $this, 'ajax_create_update_answer_editor' ) );
		add_action( 'wp_ajax_dwqa-editor-update-question-init', array( $this, 'ajax_create_update_question_editor' ) );
	}
	
	public function tinymce_addbuttons() {
		if ( get_user_option( 'rich_editing' ) == 'true' && ! is_admin() ) {
			add_filter( 'mce_external_plugins', array( $this, 'add_custom_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'register_custom_button' ) );
		}
	}

	public function register_custom_button( $buttons ) {
		array_push( $buttons, '|', 'dwqaCodeEmbed' );
		return $buttons;
	} 

	public function add_custom_tinymce_plugin( $plugin_array ) {
		global $dwqa_options;
		if ( is_singular( 'dwqa-question' ) || ( $dwqa_options['pages']['submit-question'] && is_page( $dwqa_options['pages']['submit-question'] ) ) ) {
			$plugin_array['dwqaCodeEmbed'] = DWQA_URI . 'assets/js/code-edit-button.js';
		}
		return $plugin_array;
	}
	public function display( $args ) {
		extract( wp_parse_args( $args, array(
				'content'       => '',
				'id'            => 'dwqa-custom-content-editor',
				'textarea_name' => 'custom-content',
				'rows'          => 5,
				'wpautop'       => false,
				'media_buttons' => false,
		) ) );

		$dwqa_tinymce_css = apply_filters( 'dwqa_editor_style', DWQA_URI . 'assets/css/tinymce.css' );
		$toolbar1 = apply_filters( 'dwqa_tinymce_toolbar1', 'bold,italic,underline,|,' . 'bullist,numlist,blockquote,|,' . 'link,unlink,|,' . 'image,code,|,'. 'spellchecker,fullscreen,dwqaCodeEmbed,|,' );
		wp_editor( $content, $id, array(
			'wpautop'       => $wpautop,
			'media_buttons' => $media_buttons,
			'textarea_name' => $textarea_name,
			'textarea_rows' => $rows,
			'tinymce' => array(
					'toolbar1' => $toolbar1,
					'toolbar2'   => '',
					'content_css' => $dwqa_tinymce_css
			),
			'quicktags'     => false,
		) );
	}

	public function toolbar_buttons() {

	}

	public function ajax_create_update_answer_editor() {

		if ( ! isset( $_POST['answer_id'] ) || ! isset( $_POST['question'] ) ) {
			return false;
		}
		extract( $_POST );

		ob_start();
		?>
		<form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-add-answer' ); ?>" method="post">
			<?php wp_nonce_field( '_dwqa_add_new_answer' ); ?>

			<?php if ( 'draft' == get_post_status( $answer_id ) && current_user_can( 'manage_options' ) ) { 
			?>
			<input type="hidden" name="dwqa-action-draft" value="true" >
			<?php } ?> 
			<input type="hidden" name="dwqa-action" value="update-answer" >
			<input type="hidden" name="answer-id" value="<?php echo $answer_id; ?>">
			<input type="hidden" name="question" value="<?php echo $question; ?>">
			<?php 
				$answer = get_post( $answer_id );
				$answer_content = get_post_field( 'post_content', $answer_id );
				$answer_content = apply_filters( 'dwqa_prepare_edit_answer_content', $answer_content );
				add_filter( 'dwqa_prepare_edit_answer_content', 'wpautop' );
				dwqa_init_tinymce_editor( array(
					'content'       => $answer_content, 
					'textarea_name' => 'answer-content',
					'wpautop'       => false,
				) ); 
			?>
			<p class="dwqa-answer-form-btn">
				<input type="submit" name="submit-answer" class="dwqa-btn dwqa-btn-default" value="<?php _e( 'Update','dwqa' ) ?>">
				<a type="button" class="answer-edit-cancel dwqa-btn dwqa-btn-link" ><?php _e( 'Cancel','dwqa' ) ?></a>
				<?php if ( 'draft' == get_post_status( $answer_id ) && current_user_can( 'manage_options' ) ) { 
				?>
				<input type="submit" name="submit-answer" class="btn btn-primary btn-small" value="<?php _e( 'Publish','dwqa' ) ?>">
				<?php } ?>
			</p>
			<div class="dwqa-privacy">
				<input type="hidden" name="privacy" value="<?php echo $answer->post_status ?>">
				<span class="dwqa-change-privacy">
					<div class="dwqa-btn-group">
						<button type="button" class="dropdown-toggle" ><span><?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> '.__( 'Private','dwqa' ) : '<i class="fa fa-globe"></i> '.__( 'Public','dwqa' ); ?></span> <i class="fa fa-caret-down"></i></button>
						<div class="dwqa-dropdown-menu">
							<div class="dwqa-dropdown-caret">
								<span class="dwqa-caret-outer"></span>
								<span class="dwqa-caret-inner"></span>
							</div>
							<ul role="menu">
								<li data-privacy="publish" <?php if ( $answer->post_status == 'publish' ) { echo 'class="current"'; } ?> title="<?php _e( 'Everyone can see','dwqa' ); ?>"><a href="#"><i class="fa fa-globe"></i> <?php _e( 'Public','dwqa' ); ?></a></li>
								<li data-privacy="private"  <?php if ( $answer->post_status == 'private' ) { echo 'class="current"'; } ?>  title="<?php _e( 'Only Author and Administrator can see','dwqa' ); ?>" ><a href="#"><i class="fa fa-lock"></i> <?php _e( 'Private','dwqa' ) ?></a></li>
							</ul>
						</div>
					</div>
				</span>
			</div>
		</form>
		<?php
		$editor = ob_get_contents();
		ob_end_clean();
		wp_send_json_success( array( 'editor' => $editor ) );
	}

	public function ajax_create_update_question_editor() {

		if ( ! isset( $_POST['question'] ) ) {
			return false;
		}
		extract( $_POST );

		ob_start();
		?>
		<form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-update-question' ); ?>" method="post">
			<?php wp_nonce_field( '_dwqa_update_question' ); ?>

			<?php if ( 'draft' == get_post_status( $question ) && dwqa_current_user_can( 'edit_question' ) ) {  ?>
			<input type="hidden" name="dwqa-action-draft" value="true" >
			<?php } ?> 
			<input type="hidden" name="dwqa-action" value="update-question" >
			<input type="hidden" name="question" value="<?php echo $question; ?>">
			<?php $question = get_post( $question ); ?>
			<input type="text" style="width:100%" name="dwqa-question-title" id="dwqa-question-title" value="<?php echo $question->post_title; ?>">
			<?php
				$question_content = apply_filters( 'dwqa_prepare_edit_question_content', $question->post_content );
				add_filter( 'dwqa_prepare_edit_question_content', 'wpautop' );
				dwqa_init_tinymce_editor( array(
					'content'       => $question_content, 
					'textarea_name' => 'dwqa-question-content',
					'wpautop'       => false,
				) ); 
			?>
			<p class="dwqa-question-form-btn">
				<input type="submit" name="submit-question" class="dwqa-btn dwqa-btn-default" value="<?php _e( 'Update','dwqa' ) ?>">
				<a type="button" class="question-edit-cancel dwqa-btn dwqa-btn-link" ><?php _e( 'Cancel','dwqa' ) ?></a>
				<?php if ( 'draft' == get_post_status( $question ) && current_user_can( 'manage_options' ) ) { 
				?>
				<input type="submit" name="submit-question" class="btn btn-primary btn-small" value="<?php _e( 'Publish','dwqa' ) ?>">
				<?php } ?>
			</p>
		</form>
		<?php
		$editor = ob_get_contents();
		ob_end_clean();
		wp_send_json_success( array( 'editor' => $editor ) );
	}
}

?>