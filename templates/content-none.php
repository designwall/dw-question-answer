<?php
/**
 * The template for displaying a message that questions cannot be found
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */
?>
<?php if ( ! dwqa_current_user_can( 'read_question' ) ) : ?>
	<div class="dwqa-alert dwqa-alert-info"><?php _e( 'You do not have permission to view questions', 'dwqa' ) ?></div>
<?php else : ?>
	<div class="dwqa-alert dwqa-alert-info"><?php _e( 'Sorry, but nothing matched your filter', 'dwqa' ) ?></div>
<?php endif; ?>
