<?php  
/**
 *  Template for display content of single answer 
 *  @since  DW Question Answer 1.0
 */
    $answer_id = get_the_ID(); 
    $question_id = get_post_meta( $answer_id, '_question', true );
    $answer = get_post( $answer_id );

    $post_class = '';
?>
    <article id="answer-<?php the_ID(); ?>" <?php post_class($post_class); ?>>
        <header>
            <div class="entry-meta">
                <?php echo get_avatar( get_the_author_meta( 'ID' ), 32 ); ?>
                <strong class="author">
                    <?php 
                        if( ! dwqa_is_anonymous($answer_id) ) 
                            the_author_posts_link();
                        else 
                            _e( 'Anonymous','dwqa' ); 
                    ?>
                </strong>
        
                <?php if( is_user_logged_in() ) { ?>
                <div class="answer-actions">
                    <span class="answer-comment">
                        <i alt="f300" class="icon-comment"></i>
                        <a href="#">
                        <?php  
                            $comments =  get_comment_count($answer_id);
                            if( $comments['approved'] > 0 ) {
                                printf(
                                    '<span class="comment-count">%d</span> %s',
                                    $comments['approved'],
                                    _n('comment','comments',$comments['approved'])
                                );
                            } else {
                                echo __('Comment','dwqa');
                            }
                        ?>
                        </a>
                    </span>
                    <?php global $current_user; ?>
                    <?php if( dwqa_current_user_can('edit_answer') || $answer->post_author == $current_user->ID ) { ?>
                    <span class="answer-edit-link" onclick="_e(event,this,'dwqa_answer_edit')" data-answer-id="<?php echo $answer_id ?>" data-question-id="<?php echo $question_id ?>">
                        <i alt="f411" class="icon-pencil"></i>
                        <a class="post-edit-link" href="javascript:void(0)"><?php _e('Edit','dwqa') ?></a>
                        <span class="loading"></span>
                    </span>    
                    <?php } ?>

                    <?php if( dwqa_current_user_can('delete_answer') || $current_user->ID == $answer->post_author ) { ?>
                    <span class="answer-delete" data-answer-id="<?php echo $answer_id ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_action_remove_answer_nonce' ); ?>">
                        <i alt="f407" class="icon-trash"></i>
                        <a href="javascript:void(0)"><?php _e('Delete','dwqa') ?></a></span>
                    <?php } ?>
                    
                    <?php if( is_user_logged_in() ){ ?>
                    <span class="answer-flag" data-answer-id="<?php echo $answer_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_action_flag_answer_nonce' ) ?>" >
                        <i class="icon-flag"></i>
                        <a href="javascript:void(0)"><?php 
                            global $current_user;
                            echo dwqa_is_user_flag( $answer_id, $current_user->ID ) ? __('Unflag', 'dwqa' ) : __('Flag','dwqa'); ?></a>
                    </span>
                    <?php } ?>
                </div><!-- Entry Action -->
                <?php } ?>

                <?php  
                    if( user_can( $answer->post_author, 'edit_published_posts' ) ) {
                        echo '<span class="user-role">'.__('Staff').'</span>';
                    }
                    if( get_post_status( get_the_ID() ) == 'draft' ) {
                        echo '<span class="tag-label">Draft</span>';
                    }
                ?>

                <span class="date"> <a href="#answer-<?php the_ID(); ?>" title="<?php echo __( 'Link to answer', 'dwqa' ) ?> #<?php echo $answer_id; ?>"><?php echo get_the_date(); ?></a></span>
            </div> <!-- Entry meta -->
        </header>
        <div class="answer-inner">
            <?php if( dwqa_is_answer_flag($answer_id) ) { ?>
            <p class="answer-flagged-alert alert">
                <i alt="f414" class="icon-flag"></i> 
                <?php 
                    _e('This answer was flagged as spam.','dwqa'); 
                    echo ' <strong class="answer-flagged-show">show</strong>';
                ?>
            </p>
            <?php } ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div><!-- .entry-content -->

            <footer >

                <div class="entry-vote answer-vote" data-nonce="<?php echo wp_create_nonce( '_dwqa_answer_vote_nonce' ) ?>" data-answer="<?php the_ID(); ?>">
                    <a href="#" class="vote vote-up">
                            <?php _e('Vote Up','dwqa'); ?>
                    </a>
                    <div class="vote-count" data-voted="<?php echo dwqa_get_user_vote($answer_id); ?>" >
                        <?php 
                            $vote = dwqa_vote_count(); 
                            if( $vote > 0 ) {
                                $vote = '+'.$vote;
                            }
                            echo $vote;
                        ?>
                    </div>
                    <a href="#" class="vote vote-down">
                        <?php _e('Vote Down','dwqa'); ?>
                    </a>
                </div>

                <?php dwqa_vote_best_answer_button(); ?>
            </footer>
        </div>

        <?php comments_template(); ?>
    </article>