<script type="text/javascript">
 var RecaptchaOptions = {
	theme : 'clean'
 };
 </script>
<?php  
global $dwqa_general_settings, $dwqa_options;
if ( is_singular( 'dwqa-question' ) && dwqa_is_captcha_enable_in_single_question() 
	|| 
	isset( $dwqa_options['pages']['submit-question'] ) && is_page( $dwqa_options['pages']['submit-question'] ) && dwqa_is_captcha_enable_in_submit_question() ) {
	$public_key = isset( $dwqa_general_settings['captcha-google-public-key'] ) ?  $dwqa_general_settings['captcha-google-public-key'] : '';
	echo '<div class="google-recaptcha">';
	$is_ssl = is_ssl();
	echo recaptcha_get_html( $public_key, null, $is_ssl );
	echo '<br></div>';
}