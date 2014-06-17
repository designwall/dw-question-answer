<?php  
/**
 *  Template for display content of single answer 
 *  @since  DW Question Answer 1.0
 */
    global $current_user, $post, $position;
    $answer_id = get_the_ID(); 
    $question_id = get_post_meta( $answer_id, '_question', true );
    $question = get_post( $question_id );
    $answer = get_post( $answer_id );
    setup_postdata( $answer );

    $post_class = 'dwqa-answer';
?>

    <div id="answer-<?php echo $answer_id; ?>" <?php post_class(); ?>>
        <header class="dwqa-header">
            <div class="dwqa-meta">
                <div class="dwqa-vote" data-type="answer" data-nonce="<?php echo wp_create_nonce( '_dwqa_answer_vote_nonce' ) ?>" data-answer="<?php the_ID(); ?>" >
                    <a data-vote="up" class="dwqa-vote-dwqa-btn dwqa-vote-up" href="#" title="<?php _e('Vote Up','dwqa') ?>"><?php _e('Vote Up','dwqa') ?> </a>
                    <div class="dwqa-vote-count">
                    <?php  
                        $vote = dwqa_vote_count(); 
                        if( $vote > 0 ) {
                            $vote = '+'.$vote;
                        }
                        echo $vote;
                    ?>
                    </div>
                    <a data-vote="down" class="dwqa-vote-dwqa-btn dwqa-vote-down" href="#"  title="<?php _e('Vote Down','dwqa') ?>"><?php _e('Vote Down','dwqa') ?> </a>
                </div>
                
                <?php  if( is_user_logged_in() ) { ?>
                <div class="dwqa-actions">
                    <span class="loading"></span>
                    <div class="dwqa-btn-group">
                        <button type="button" class="dropdown-toggle circle"><i class="fa fa-chevron-down"></i> </button>
                        <div class="dwqa-dropdown-menu">
                            <div class="dwqa-dropdown-caret">
                                <span class="dwqa-caret-outer"></span>
                                <span class="dwqa-caret-inner"></span>
                            </div>
                            <ul role="menu">
                                <?php if( dwqa_current_user_can('edit_answer') || $answer->post_author == $current_user->ID ) { ?>
                                <li class="answer-edit-link" onclick="_e(event,this,'dwqa_answer_edit')" data-answer-id="<?php echo $answer_id ?>" data-question-id="<?php echo $question_id ?>"><a href="#"><i class="fa fa-pencil"></i> <?php _e('Edit','dwqa') ?></a></li>
                                <?php } ?>
                                <?php if( dwqa_current_user_can('delete_answer') || $answer->post_author == $current_user->ID ) { ?>
                                <li  class="answer-delete" data-answer-id="<?php echo $answer_id ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_action_remove_answer_nonce' ); ?>" ><a href="#"><i class="fa fa-trash-o"></i> <?php _e('Delete','dwqa') ?></a></li>
                                <?php } ?>
                                <li class="dwqa-answer-report" data-answer-id="<?php echo $answer_id ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_action_flag_answer_nonce' ); ?>" ><a href="#"><i class="fa fa-flag"></i> <?php _e('Report','dwqa') ?></a>
                                </li>
                            </ul>
                        </ul>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <div data-post="<?php echo $answer_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_privacy_nonce' ); ?>" data-type="answer" class="dwqa-privacy">
                <input type="hidden" name="privacy" value="<?php get_post_status(); ?>">
                <?php if( get_post_status() != 'draft' && is_user_logged_in() && (dwqa_current_user_can('edit_answer') || $answer->post_author == $current_user->ID || $current_user->ID == $question->post_author ) ) : ?>
                    <span class="dwqa-change-privacy">
                        <div class="dwqa-btn-group">
                            <button type="button" class="dropdown-toggle" ><span><?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> '.__('Private','dwqa') : '<i class="fa fa-globe"></i> '.__('Public','dwqa'); ?></span> <i class="fa fa-caret-down"></i></button>
                            <div class="dwqa-dropdown-menu">
                                <div class="dwqa-dropdown-caret">
                                    <span class="dwqa-caret-outer"></span>
                                    <span class="dwqa-caret-inner"></span>
                                </div>
                                <ul role="menu">
                                    <li title="<?php _e('Everyone can see','dwqa'); ?>" <?php echo 'private' != get_post_status() ? 'class="current"' : ''; ?> data-privacy="publish"><a href="javascript:void(0);"><i class="fa fa-globe"></i> <?php _e('Public','dwqa'); ?></a></li>
                                    <li title="<?php _e('Only Author and Administrator can see','dwqa'); ?>" data-privacy="private" <?php echo 'private' == get_post_status() ? 'class="current"' : ''; ?>><a href="javascript:void(0);"  ><i class="fa fa-lock"></i> <?php _e('Private','dwqa') ?></a></li>
                                </ul>
                            </div>
                        </div>
                    </span>
                <?php elseif( get_post_status() != 'draft' ) : ?>
                    <span class="dwqa-current-privacy"><?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> '.__('Private','dwqa') : '<i class="fa fa-globe"></i> '.__('Public','dwqa'); ?></span>
                <?php endif; ?>
                </div>

            </div>
            <div class="dwqa-author">
                <?php echo get_avatar( get_the_author_meta( 'ID' ), 64 ); ?>
                <span class="author">
                    <?php 
                        if( ! dwqa_is_anonymous($answer_id) )  {
                            the_author_posts_link();
                            if( user_can( $answer->post_author, 'edit_posts' ) ) {
                                echo ' <strong>&sdot;</strong> <span class="dwqa-label dwqa-staff">'.__('Staff','dwqa').'</span>';
                            }
                        } else {
                            _e( 'Anonymous','dwqa' ); 
                        }
                    ?>
                </span>
                <span class="dwqa-date">
                    <strong>&sdot; </strong><a href="#answer-<?php echo $answer_id ?>" title="<?php _e('Link to answer','dwqa') ?> #<?php echo $answer_id ?>"><?php echo get_the_date(); ?></a>
                </span>

                <?php if( get_post_status() == 'draft' ) { ?>
                    <strong>&sdot; </strong> <?php _e('Draft','dwqa'); ?>
                <?php } ?>
            </div><!-- Answer Author -->
            
        </header>
        <div class="dwqa-content">
            <?php if( dwqa_is_answer_flag($answer_id) ) { ?>
            <p class="answer-flagged-alert alert">
                <i class="fa fa-flag"></i> 
                <?php 
                    _e('This answer was flagged as spam.','dwqa'); 
                    echo ' <strong class="answer-flagged-show">show</strong>';
                ?>
            </p>
            <?php } ?>
            <div class="dwqa-content-inner <?php echo dwqa_is_answer_flag($answer_id) ? 'dwqa-hide' : ''; ?>">
                <?php the_content(); ?>
            </div>

            <span class="dwqa-anchor">
                <?php 
                    if( get_post_status( get_the_ID() ) == 'publish' ) {
                        $is_the_best = dwqa_is_the_best_answer($answer_id,$question_id);
                        $data =  is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ? 'data-answer="'.get_the_ID().'" data-nonce="'.wp_create_nonce( '_dwqa_vote_best_answer' ).'" data-ajax="true"' : 'data-ajax="false"';
                        if( $is_the_best || $data != 'data-ajax="false"' ) {
                ?>
                <span class="dwqa-best-answer <?php echo $is_the_best ? 'active' : ''; ?>" title="<?php _e('This is the best answer','dwqa') ?>" <?php echo $data ?>><i class="fa fa-check-circle"></i></span>
                    <?php } ?>
                <?php } ?>
                
                <a title="<?php _e('The answer link','dwqa') ?>" href="<?php echo  get_permalink( $question_id ) . '#answer-' . $answer_id; ?>">#<?php echo $position; ?></a>
            </span>
        </div>
        <?php if( ! dwqa_is_closed( $question_id ) ) { ?>
        <div class="dwqa-comments">
            <?php comments_template(); ?>
        </div>
        <?php } ?>
    </div>