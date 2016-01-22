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
	<a href="<?php echo get_author_posts_url( $comment->user_id ); ?>"><?php the_author_meta( 'display_name', $comment->user_id ); ?></a>
	<?php echo get_comment_text(); ?>
</div>