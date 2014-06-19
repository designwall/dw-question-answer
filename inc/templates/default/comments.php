<?php
/**
 * The template for displaying Comments.
 *
 */
if ( post_password_required() )
    return;

if( ! comments_open( get_the_ID() ) ) 
    return;

?>
    <?php $count = get_comment_count( get_the_ID() ); ?>
    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' )  ) : ?>
    <div class="dwqa-comments-more">
        <span class="dwqa-comments-more-link" data-post="<?php echo get_the_ID(); ?>"><a href="javascript:void(0);"><?php _e('View previous comments','dwqa') ?></a></span>
        <span class="dwqa-comments-more-pages"><?php echo get_option( 'comments_per_page' ) . ' ' . __('of','dwqa') . ' ' . $count['approved'] ; ?></span>
    </div>
    <?php endif; ?>

    <?php if( have_comments() ) : ?>
    <ol class="dwqa-comment-list">
    <?php 
        wp_list_comments( array( 
            'style' => 'ol',
            'callback'  => 'dwqa_question_comment_callback'
        ) ); 
    ?>
    </ol>
    <?php endif; ?>

    <?php if( ! dwqa_is_closed( get_the_ID() ) && dwqa_current_user_can( 'post_comment' ) ) { ?>
        <?php
            global $current_user;
            $args = array(
                'comment_field' => ((is_user_logged_in()) ? get_avatar( $current_user->ID, 32 ) : '') .'<textarea id="comment" name="comment" aria-required="true" placeholder="'.__('Write a reply...','dwqa').'"></textarea>',
                'comment_notes_before' => '',
                'logged_in_as' => '',
                'comment_notes_after' => '',
                'id_form'     =>    'comment_form_'.get_the_ID()
            );
        ?>
        <?php dwqa_comment_form($args); ?>
    <?php  } ?>