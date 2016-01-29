<?php if ( ( 'dwqa-question' == get_post_type() && dwqa_is_captcha_enable_in_single_question() ) || ( dwqa_is_ask_form() && dwqa_is_captcha_enable_in_submit_question() ) ) : ?>
<input type="text" name="dwqa-captcha" id="captcha_register" class="form-control" placeholder="<?php _e( 'Type the code shown', 'dwqa' ); ?>" />
<img src="<?php echo DWQA_URI . 'inc/helper/captcha.php'; ?>" />
<?php endif; ?>