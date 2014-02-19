<?php global $comment, $post, $current_user; ?>
    <li id="comment-<?php comment_ID(); ?>" <?php comment_class('dwqa-comment'); ?>> 
        <div class="dwqa-comment-author">
            <?php 
                if( $comment->user_id ) {
                    echo get_avatar( $comment->user_id, 32);
                    $author = get_userdata( $comment->user_id );  
                    printf('<span class="author"><a href="%1$s" title="%2$s %3$s">%3$s</a> %4$s</span>',
                        get_author_posts_url( $comment->user_id ),
                        __('Post by','dwqa'),
                        $author->display_name,
                        ( user_can( $comment->user_id, 'edit_posts' ) ? ' <strong>&sdot;</strong> <span class="dwqa-label dwqa-staff">'.__('Staff','dwqa').'</span>' : '')
                    );
                } else {
                    echo '<span class="author">'.( $comment->comment_author ? $comment->comment_author : __('Anonymous','dwqa') ).'</span>';
                }

            ?>
            
            <span class="dwqa-date">
                <?php  
                    echo get_avatar( $comment->user_id, 0);
                    printf('<a href="#comment-%1$d" title="%2$s #%1$d">%3$s</a>',
                        $comment->comment_ID,
                        __('Link to comment','dwqa'),
                        get_comment_date()
                    );
                ?>
            </span>
        </div>
        <div class="dwqa-comment-content">
            <div class="dwqa-comment-content-inner">
                <?php echo get_comment_text(); ?>
            </div>
        </div>
        <?php if( is_user_logged_in() && (dwqa_current_user_can('edit_comment') || $current_user->ID == $comment->user_id || dwqa_current_user_can('delete_comment') )) { ?>
        <div class="dwqa-comment-actions">
            <span class="loading"></span>
            <div class="dwqa-btn-group">
                <button type="button" class="dropdown-toggle"><i class="fa fa-chevron-down"></i> </button>
                <div class="dwqa-dropdown-menu">
                    <div class="dwqa-dropdown-caret">
                        <span class="dwqa-caret-outer"></span>
                        <span class="dwqa-caret-inner"></span>
                    </div>
                    <ul role="menu">
                        <?php if( dwqa_current_user_can('edit_comment') || $current_user->ID == $comment->user_id ) { ?>
                        <li class="dwqa-comment-edit-link" data-edit="0" data-comment-edit-nonce="<?php echo wp_create_nonce( '_dwqa_action_comment_edit_nonce' ); ?>" data-comment-id="<?php echo $comment->comment_ID ?>" ><a href="#"><i class="fa fa-pencil"></i> <?php _e('Edit','dwqa') ?></a></li>
                        <?php } ?>
                        <?php if( dwqa_current_user_can('delete_comment') || $current_user->ID == $comment->user_id ) { ?>
                        <li class="comment-delete-link" data-comment-type="<?php echo $post->post_type == 'dwqa-question' ? 'question' : 'answer' ?>" data-comment-id="<?php echo $comment->comment_ID; ?>" data-comment-delete-nonce="<?php echo wp_create_nonce( '_dwqa_action_comment_delete_nonce' ); ?>" ><a href="#"><i class="fa fa-trash-o"></i> <?php _e('Delete','dwqa') ?></a></li>
                        <?php } ?>
                    </ul>
                </ul>
            </div>
        </div>
        <?php } ?>
    </li>
    