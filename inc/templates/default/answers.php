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
        dwqa_submit_answer_form();
    } else { ?>
        <?php if( is_user_logged_in() ) { ?>
            <div class="alert"><?php _e('You do not have permission to submit answer.','dwqa') ?></div>
        <?php } else { ?>
        <h3 class="dwqa-title">
            <?php 
                printf('%1$s <a href="%2$s" title="%3$s">%3$s</a> %4$s',
                    __('Please login or','dwqa'),
                    site_url( 'wp-login.php?action=register' ),
                    __('Register','dwqa'),
                    __('to Submit Answer','dwqa')
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