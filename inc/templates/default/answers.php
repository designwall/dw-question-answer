<?php  
    global $current_user, $post;
    $question_id = get_the_ID();
    $question = get_post( $question_id );
    $best_answer_id = dwqa_get_the_best_answer( $question_id );


    $status = array( 'publish', 'private' );
    $args = array(
       'post_type' => 'dwqa-answer',
       'posts_per_page' =>  -1,
       'order'      => 'ASC',
       'meta_query' => array(
           array(
               'key' => '_question',
               'value' => array( $question_id ),
               'compare' => 'IN',
           )
       ),
       'post_status' => $status
     );
    $answers = new WP_Query($args);
    $count = 0;
    foreach ($answers->posts as $answer) {
        if( $answer->post_status == 'private' ) {
            if( is_user_logged_in() 
                && ( $current_user->ID == $question->post_author 
                    || $current_user->ID == $answer->post_author 
                    || dwqa_current_user_can('edit_question') 
                    || dwqa_current_user_can('edit_answer') 
            ) ) {
                $count++;
            }
        } else if( $answer->post_status == 'publish' ) {
            $count++;   
        }
    }
    $drafts = dwqa_get_drafts( $question_id );

    if( $count > 0 || ! empty($drafts) ) { ?>
        <h3 class="dwqa-headline">
            <?php 
                printf( '<span class="answer-count"><span class="digit">%d</span> %s</span>',
                    $count,
                    _n( 'answer', 'answers', $count, 'dwqa' )
                );
            ?>
        </h3>
        
        <div class="dwqa-list-answers">
            <?php
                if( $best_answer_id ) {
                    global $post;
                    $best_answer = get_post( $best_answer_id );
                    $post = $best_answer;
                    setup_postdata( $post );
                    dwqa_load_template( 'content', 'answer' );
                }
                while ( $answers->have_posts() ) { $answers->the_post();
                    $answer = get_post( get_the_ID() );
                    if( $best_answer_id && $best_answer_id == get_the_ID() ) {
                        continue;
                    }
                    if( get_post_status( get_the_ID() ) == 'private' ) {
                        if( is_user_logged_in() && ( dwqa_current_user_can('edit_answer') || $current_user->ID == $answer->post_author || $current_user->ID == $question->post_author) ) {
                            dwqa_load_template( 'content', 'answer' );
                        }
                    } else {
                        dwqa_load_template( 'content', 'answer' );
                    }
                } 
                //Drafts
                if( current_user_can( 'edit_posts' ) ) {
                    global $post;
                    if( ! empty($drafts) ) {
                        foreach ( $drafts as $post ) {
                            setup_postdata( $post );
                            dwqa_load_template( 'content', 'answer' );
                        }
                    }
                } 
            ?>
        </div>
    
    <?php } else {

        if( ! dwqa_current_user_can('read_answer') ) {
            echo '<div class="alert">'.__('You do not have permission to view answers','dwqa').'</div>';
        }
    }
    
    wp_reset_query();
    //Create answer form
    global $dwqa_options;
    if( dwqa_is_closed( $question_id ) ) {
        echo '<p class="alert">'.__('This question has been closed','dwqa').'</p>';
        return false;
    }

    if( dwqa_current_user_can('post_answer') ){
    ?>
    <div id="dwqa-add-answers" class="dwqa-answer-form">
        <h3 class="dwqa-headline"><?php _e('Answer this Question', 'dwqa' ); ?></h3>
        <form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-add-answer' ); ?>" name="dwqa-answer-question-form" id="dwqa-answer-question-form" method="post">
            <?php  
                function dwqa_paste_srtip_disable( $mceInit ){
                    $mceInit['paste_strip_class_attributes'] = 'none';
                    return $mceInit;
                }
                add_filter( 'tiny_mce_before_init', 'dwqa_paste_srtip_disable' );
                $editor = array( 
                    'wpautop'       => false,
                    'id'            => 'dwqa-answer-question-editor',
                    'textarea_name' => 'answer-content',
                    'rows'          => 2
                );
            ?>
            <?php dwqa_init_tinymce_editor( $editor ); ?>
            <?php do_action( 'dwqa_submit_answer_ui', $question_id ); ?>
            <div class="form-buttons">
                <input type="submit" name="submit-answer" id="submit-answer" value="<?php _e('Add answer','dwqa'); ?>" class="dwqa-btn dwqa-btn-primary" />

                <?php if( current_user_can( 'manage_options' ) ) { ?>
                <input type="submit" name="submit-answer" id="save-draft-answer" value="<?php _e('Save draft','dwqa'); ?>" class="dwqa-btn dwqa-btn-default" />
                <?php } ?>
            </div>
            <div class="dwqa-privacy">
                <input type="hidden" name="privacy" value="publish">
                <span class="dwqa-current-privacy"><i class="fa fa-globe"></i> <?php _e('Public','dwqa') ?></span>
                <span class="dwqa-change-privacy">
                    <div class="dwqa-btn-group">
                        <button class="dropdown-toggle" type="button"><i class="fa fa-caret-down"></i></button>
                        <div class="dwqa-dropdown-menu">
                            <div class="dwqa-dropdown-caret">
                                <span class="dwqa-caret-outer"></span>
                                <span class="dwqa-caret-inner"></span>
                            </div>
                            <ul role="menu">
                                <li data-privacy="publish" class="current" title="<?php _e('Everyone can see','dwqa'); ?>"><a href="#"><i class="fa fa-globe"></i> <?php _e('Public','dwqa'); ?></a></li>
                                <li data-privacy="private" title="<?php _e('Only Author and Administrator can see','dwqa'); ?>"><a href="#"><i class="fa fa-lock"></i> <?php _e('Private','dwqa') ?></a></li>
                            </ul>
                        </div>
                    </div>
                </span>
            </div>
        </form>
    </div>
    <?php
    } else { ?>
        <?php if( is_user_logged_in() ) { ?>
            <div class="alert"><?php _e('You do not have permission to submit answer.','dwqa') ?></div>
        <?php } else { ?>
        <h3 class="dwqa-title">
            <?php 
                printf('%1$s %2$s <a href="%3$s" title="%4$s">%4$s</a> %5$s %6$s',
                    __('Please login','dwqa'),
                    __('or','dwqa'),
                    site_url( 'wp-login.php?action=register' ),
                    __('Register','dwqa'),
                    __('to','dwqa'),
                    __('Submit Answer','dwqa')
                );
            ?>
        </h3>
        <div class="login-box">
            <?php wp_login_form( array(
                'redirect'  => get_post_permalink( $question_id )
            ) ); ?>
        </div>
        <?php
        }
    }
?>