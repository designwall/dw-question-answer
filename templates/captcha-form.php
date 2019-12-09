<?php
/**
 * The template for displaying captcha form
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */
?>

<?php if ( ( 'dwqa-question' == get_post_type() && dwqa_is_captcha_enable_in_single_question() ) || ( dwqa_is_ask_form() && dwqa_is_captcha_enable_in_submit_question() ) ) : ?>
<p class="dwqa-captcha">
	<?php 
	$number_1 = mt_rand( 0, 20 );
	$number_2 = mt_rand( 0, 20 );
	?>
	<span class="dwqa-number-one"><?php echo esc_attr( $number_1 ) ?></span>
	<span class="dwqa-plus">&#43;</span>
	<span class="dwqa-number-one"><?php echo esc_attr( $number_2 ) ?></span>
	<span class="dwqa-plus">&#61;</span>
	<input type="text" name="dwqa-captcha-result" id="dwqa-captcha-result" value="" placeholder="<?php _e( 'Enter the result', 'dw-question-answer' ) ?>">
	<input type="hidden" name="dwqa-captcha-number-1" id="dwqa-captcha-number-1" value="<?php echo esc_attr( $number_1 ) ?>">
	<input type="hidden" name="dwqa-captcha-number-2" id="dwqa-captcha-number-2" value="<?php echo esc_attr( $number_2 ) ?>">
</p>
<?php endif; ?>