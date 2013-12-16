<?php
/**
 * The template for displaying Comments.
 *
 */
if ( post_password_required() )
    return;
?>

<div id="comments" class="comments-area">
    <?php if ( have_comments() ) : ?>
        <ol class="commentlist">
            <?php 
                wp_list_comments( array( 
                    'style' => 'ol',
                    'callback'  => 'dwqa_question_comment_callback'
                ) ); 
            ?>
        </ol><!-- .commentlist -->

        <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
        <nav id="comment-nav-below" class="navigation" role="navigation">
            <h1 class="assistive-text section-heading"><?php _e( 'Comment navigation', 'dwqa' ); ?></h1>
            <div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', 'dwqa' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', 'dwqa' ) ); ?></div>
        </nav>
        <?php endif; // check for comment navigation ?>

        <?php
        /* If there are no comments and comments are closed, let's leave a note.
         * But we only want the note on posts and pages that had comments in the first place.
         */
        if ( ! comments_open() && get_comments_number() ) : ?>
        <p class="nocomments"><?php _e( 'Comments are closed.' , 'dwqa' ); ?></p>
        <?php endif; ?>

    <?php endif; // have_comments() ?>
    <?php if( ! dwqa_is_closed( get_the_ID() ) ) { ?>
        <?php if( dwqa_current_user_can( 'post_comment' ) ) { ?>
            <?php
                global $current_user;
                $args = array(
                    'comment_field' => ((is_user_logged_in()) ? get_avatar( $current_user->ID, 32 ) : '') .'<textarea id="comment" name="comment" aria-required="true" placeholder="Write a reply..."></textarea>',
                    'comment_notes_before' => '',
                    'logged_in_as' => '',
                    'comment_notes_after' => '',
                    'id_form'     =>    'comment_form_'.get_the_ID()
                );
            ?>
            <?php comment_form($args); ?>
        <?php  }  ?>
    <?php } ?>

</div><!-- #comments .comments-area -->