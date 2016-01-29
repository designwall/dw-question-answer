<?php
/**
 * The template for displaying all single questions
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
// global $wp_query; print_r( $wp_query );
?>
<div class="dwqa-single-question">
<?php if ( have_posts() ) : ?>
	<?php do_action( 'dwqa_before_single_question' ) ?>

	<?php if ( dwqa_current_user_can( 'edit_question' ) || get_current_user_id() == get_post_field( 'post_author', get_the_ID() ) ) : ?>
	<?php _e( 'This question is:', 'dwqa' ) ?>
	<select id="dwqa-question-status" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_privacy_nonce' ) ?>" data-post="<?php the_ID(); ?>">
		<optgroup label="Status">
			<option <?php selected( dwqa_question_status(), 'open' ) ?> value="open"><?php _e( 'Open', 'dwqa' ) ?></option>
			<option <?php selected( dwqa_question_status(), 'close' ) ?> value="close"><?php _e( 'Close', 'dwqa' ) ?></option>
			<option <?php selected( dwqa_question_status(), 'resolved' ) ?> value="resolved"><?php _e( 'Resolve', 'dwqa' ) ?></option>
		</optgroup>
	</select>
	<?php endif; ?>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( !dwqa_is_edit() ) : ?>
			<?php dwqa_load_template( 'content', 'single-question' ) ?>
		<?php else : ?>
			<?php dwqa_load_template( 'content', 'edit' ) ?>
		<?php endif; ?>
	<?php endwhile; ?>
	<?php do_action( 'dwqa_after_single_question' ) ?>
<?php endif; ?>
</div>
