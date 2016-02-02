<?php
/**
 * The template for displaying content comment
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.0
 */
?>

<?php global $comment; ?>
<div class="dwqa-comment">
	<div class="dwqa-comment-meta">
		<a href="<?php echo dwqa_get_author_link( $comment->user_id ); ?>"><?php the_author_meta( 'display_name', $comment->user_id ); ?></a>
		<?php dwqa_print_user_badge( $comment->user_id, true ); ?>
		<?php printf( _x( 'replied %s ago', '%s = human-readable time difference', 'dwqa' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
		<div class="dwqa-comment-actions">
			<?php if ( dwqa_current_user_can( 'edit_comment' ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'comment_edit' => $comment->comment_ID ) ) ) ?>"><?php _e( 'Edit', 'dwqa' ) ?></a>
			<?php endif; ?>
			<?php if ( dwqa_current_user_can( 'delete_comment' ) ) : ?>
				<a href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'dwqa-action-delete-comment', 'comment_id' => $comment->comment_ID ), admin_url( 'admin-ajax.php' ) ), '_dwqa_delete_comment' ) ?>"><?php _e( 'Delete', 'dwqa' ) ?></a>
			<?php endif; ?>
		</div>
	</div>
	<?php comment_text(); ?>
</div>
