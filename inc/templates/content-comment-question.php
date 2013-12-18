<?php global $comment, $post; ?>
    <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
        <span class="comment-content">
            <?php echo get_comment_text(); ?>
        </span><!-- .comment-content -->
        <span class="comment-meta">
            <?php $author = get_userdata( $comment->user_id ); ?>
            <strong class="author">
                <?php printf(' - <a href="%s" alt="%s">%s</a>', get_author_posts_url( $comment->user_id ), $author->display_name, $author->display_name); 
                ?>
            </strong>
            <?php  
                if( user_can( $comment->user_id, 'edit_published_posts' ) ) {
                    echo ' / <span class="user-role">'.__('Staff','dwqa').'</span>';
                }
            ?>
            <span class="date"><a href="#li-comment-<?php comment_ID(); ?>" title="Link to comment #<?php comment_ID(); ?>"><?php echo get_comment_date(); ?></a></span>
        </span>
        <div class="comment-action">
            <?php 
                global $current_user;
                if( dwqa_current_user_can('edit_comment') || $current_user->ID == $comment->user_id ) { 
            ?>
                <span class="edit-link comment-edit-link" data-edit="0" data-comment-edit-nonce="<?php echo wp_create_nonce( '_dwqa_action_comment_edit_nonce' ) ?>" data-comment-id="<?php echo $comment->comment_ID; ?>"><i alt="f411" class="icon-pencil"></i><a title="<?php _e('Edit comment','dwqa') ?>" href="javascript:void()" ><?php _e('Edit','dwqa') ?></a></span>
            <?php } ?>
            
            <?php 
                if( dwqa_current_user_can('delete_comment') || $current_user->ID == $comment->user_id ) { 
            ?>
                <span class="comment-delete-link" data-comment-type="<?php echo $post->post_type == 'dwqa-question' ? 'question' : 'answer' ?>" data-comment-id="<?php echo $comment->comment_ID; ?>" data-comment-delete-nonce="<?php echo wp_create_nonce( '_dwqa_action_comment_delete_nonce' ); ?>">
                    <i alt="f407" class="icon-trash"></i>
                    <a title="Delete comment" href="javascript:void();"><?php _e('Delete','dwqa'); ?></a>
                </span>
            <?php } ?>
        </div>

        <?php if ( '0' == $comment->comment_approved ) : ?>
            <p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'dwqa' ); ?></p>
        <?php endif; ?>
    