<div id="dwqa-add-answers" class="dwqa-answer-form">
	<h3 class="dwqa-headline"><?php _e( 'Answer this Question', 'dwqa' ); ?></h3>
	<?php  
	if ( isset( $_GET['errors'] ) ) {
		echo '<p class="alert">';
		echo urldecode( esc_url( $_GET['errors'] ) ) . '<br>';
		echo '</p>';
	}
	?>
	<form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-add-answer' ); ?>" name="dwqa-answer-question-form" id="dwqa-answer-question-form" method="post">
	<?php 

	add_filter( 'tiny_mce_before_init', 'dwqa_paste_srtip_disable' );
	$editor = array( 
		'wpautop'       => false,
		'id'            => 'dwqa-answer-question-editor',
		'textarea_name' => 'answer-content',
		'rows'          => 2,
	);
	?>
	<?php dwqa_init_tinymce_editor( $editor ); ?>
	<?php do_action( 'dwqa_submit_answer_ui', get_the_ID() ); ?>
	
	<script type="text/javascript">
	 var RecaptchaOptions = {
		theme : 'clean'
	 };
	 </script>
	<?php  
	global  $dwqa_general_settings;
	if ( dwqa_is_captcha_enable_in_single_question() ) {
		$public_key = isset( $dwqa_general_settings['captcha-google-public-key'] ) ?  $dwqa_general_settings['captcha-google-public-key'] : '';
		echo '<div class="google-recaptcha">';
		echo recaptcha_get_html( $public_key );
		echo '<br></div>';
	}

	?>
		<div class="form-buttons">
			<input type="submit" name="submit-answer" id="submit-answer" value="<?php _e( 'Add answer','dwqa' ); ?>" class="dwqa-btn dwqa-btn-primary" />

			<?php if ( current_user_can( 'edit_posts' ) ) { ?>
			<input type="submit" name="submit-answer" id="save-draft-answer" value="<?php _e( 'Save draft','dwqa' ); ?>" class="dwqa-btn dwqa-btn-default" />
			<?php } ?>
		</div>
		<div class="dwqa-privacy">
			<input type="hidden" name="privacy" value="publish">
			<span class="dwqa-change-privacy">
				<div class="dwqa-btn-group">
					<button type="button" class="dropdown-toggle" ><span><?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> '.__( 'Private','dwqa' ) : '<i class="fa fa-globe"></i> '.__( 'Public','dwqa' ); ?></span> <i class="fa fa-caret-down"></i></button>
					<div class="dwqa-dropdown-menu">
						<div class="dwqa-dropdown-caret">
							<span class="dwqa-caret-outer"></span>
							<span class="dwqa-caret-inner"></span>
						</div>
						<ul role="menu">
							<li data-privacy="publish" class="current" title="<?php _e( 'Everyone can see','dwqa' ); ?>"><a href="#"><i class="fa fa-globe"></i> <?php _e( 'Public','dwqa' ); ?></a></li>
							<li data-privacy="private" title="<?php _e( 'Only Author and Administrator can see','dwqa' ); ?>"><a href="#"><i class="fa fa-lock"></i> <?php _e( 'Private','dwqa' ) ?></a></li>
						</ul>
					</div>
				</div>
			</span>
		</div>
	</form>
</div>