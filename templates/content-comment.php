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
		<?php printf( _x( '%s ago', '%s = human-readable time difference', 'dwqa' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) ); ?>
		<?php edit_comment_link( __( 'Edit', 'dwqa' ), '  ', '' ); ?>
	</div>
	<?php comment_text(); ?>
</div>
