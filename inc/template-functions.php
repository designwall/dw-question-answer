<?php  
/**
 * Custom template for each page of plugin
 * @param  string $single_template template url 
 * @return string                  New url of custom template for each page of dwqa plugin
 */ 
function dwqa_generate_template_for_plugin($template) {
    global $post, $dwqa_options;

    if( is_singular( 'dwqa-answer' ) ) {
        $question_id = get_post_meta( $post->ID, '_question', true );
        if( $question_id ) {
            query_posts( 'p='.$question_id.'&post_type=dwqa-question' );
            return dwqa_load_template( 'single', 'question', false );
        }
    } 

    if( is_singular( 'dwqa-question' ) ) {
        return dwqa_load_template( 'single', 'question', false );
    }
    return $template;
}
add_filter( "single_template", "dwqa_generate_template_for_plugin" ) ;

/**
 * Filter new page for submit question form
 * @param  string $template Template path
 * @return string           Submit question template path
 */ 
function dwqa_generate_template_for_submit_question_page($template) {
    global $post, $dwqa_options;
    if( $dwqa_options['pages']['submit-question'] && is_page( $dwqa_options['pages']['submit-question'] ) ){
        $template = dwqa_load_template( 'submit', 'question', false );
    }
    if( $dwqa_options['pages']['archive-question'] && is_page( $dwqa_options['pages']['archive-question'])  ){
        return dwqa_load_template( 'archive', 'question', false );
    }
    return $template;
}
add_filter( 'page_template', 'dwqa_generate_template_for_submit_question_page' );

/**
 * Override template path of comment form in singe page for question custom post type
 * @param  string $comment_template Template path 
 * @return string                   New template path
 */
function dwqa_generate_template_for_comment_form( $comment_template ) {
    if (  is_single() && 'dwqa-question' == get_post_type() ) {
        return dwqa_load_template( 'comment', 'of-question', false );
    }

    if (  is_single() && 'dwqa-answer' == get_post_type() ) {
        return dwqa_load_template( 'comment', 'of-answer', false );
    }

    return $comment_template;
}

add_filter( "comments_template", "dwqa_generate_template_for_comment_form" );

/**
 * Override template path for archive of question post type template
 * @param  string $template template path
 * @return string           New template path
 */
function dwqa_generate_template_for_questions_list_page($template) {
    if( is_archive() 
        &&  ( 'dwqa-question' == get_post_type() 
                || 'dwqa-question' == get_query_var( 'post_type' ) 
                || 'dwqa-question_category' == get_query_var( 'taxonomy' ) 
                || 'dwqa-question_tag' == get_query_var( 'taxonomy' ) ) 
    ) {
        return dwqa_load_template( 'archive', 'question', false );
    }
    return $template;
}
add_filter( 'archive_template', 
    'dwqa_generate_template_for_questions_list_page' );
/**
 * Print class for question detail container
 */
function dwqa_class_for_question_details_container(){
    $class = array();
    $class[] = 'question-details';
    $class = apply_filters( 'dwqa-class-questions-details-container', $class );
    echo implode(' ', $class);
}

/**
 * Load template for content of single answer
 * @param  int $question_id ID of question 
 * @return HTML
 */
function dwqa_answers($question_id){

    $best_answer_id = dwqa_get_the_best_answer( $question_id );
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
       'post_status' => 'publish'
     );
    $answers = new WP_Query($args);
    $drafts = dwqa_get_drafts( $question_id );

    if( $answers->have_posts() || ! empty($drafts) ) { ?>
        <h3 id="answers-title">
            <?php 
                printf( '<span class="answer-count"><span class="digit">%d</span> %s</span>',
                    $answers->post_count,
                    _n( 'answer', 'answers', $answers->post_count, 'dwqa' )
                );
            ?>
        </h3>
        
        <div class="answers-list">
            <?php
                if( $best_answer_id ) {
                    global $post;
                    $best_answer = get_post( $best_answer_id );
                    $post = $best_answer;
                    setup_postdata( $post );
                    dwqa_load_template( 'content', 'answer' );
                }
                while ( $answers->have_posts() ) { $answers->the_post();
                    if( $best_answer_id && $best_answer_id == get_the_ID() ) {
                        continue;
                    }
                    dwqa_load_template( 'content', 'answer' );
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
    
    <?php }
    
    wp_reset_query();
    //Create answer form
    global $dwqa_options;
    $status = get_post_meta( $question_id, '_dwqa_status', true );
    if( 'closed' == strtolower( $status ) ) {
        echo '<p class="alert">'.__('This question has been closed','dwqa').'</p>';
        return false;
    }

    if(  is_user_logged_in() 
        || ( ! is_user_logged_in() 
            && ( ! isset( $dwqa_options['answer-registration'] ) 
                            || ! (bool) $dwqa_options['answer-registration']) )  
    ) {
    ?>
    <div id="add-answer">
        <h3 class="dwqa-title"><?php _e('Answer this Question', 'dwqa' ); ?></h3>
        <form action="" name="dwqa-answer-question-form" id="dwqa-answer-question-form" method="post">

            <?php 
                $editor = array( 
                    'id'            => 'dwqa-answer-question-editor',
                    'textarea_name' => 'answer-content',
                    'rows'          => 2
                );
            ?>
            <?php dwqa_init_tinymce_editor( $editor ); ?>
            <?php do_action( 'dwqa_submit_answer_ui', $question_id ); ?>
            <div class="form-buttons">
                <input type="submit" name="submit-answer" id="submit-answer" value="<?php _e('Add answer','dwqa'); ?>" class="btn btn-primary" />

                <?php if( current_user_can( 'manage_options' ) ) { ?>
                <input type="submit" name="submit-answer" id="save-draft-answer" value="<?php _e('Save draft','dwqa'); ?>" class="btn" />
                <?php } ?>
            </div>
        </form>
    </div>
    <?php
    } else {
        $register_link = wp_register( '', '', false );
        ?>
        <h3 class="dwqa-title"><?php _e('Please <a href="'.wp_login_url( get_post_permalink( $question_id ) ).'">Login</a> to Submit Question', 'dwqa' ); ?></h3>
        <div class="login-box">
            <?php
            wp_login_form();
            ?>
        </div>
        <?php
    }
}

function dwqa_question_add_class($classes, $class, $post_id){
    if( get_post_type( $post_id ) == 'dwqa-question' ) {

        $have_new_reply = dwqa_have_new_reply();
        if( $have_new_reply == 'staff-answered' ) {
            $classes[] = 'staff-answered';
        }
    }
    return $classes;
}
add_action( 'post_class', 'dwqa_question_add_class', 10, 3 );

/**
 * callback for comment of question
 */
function dwqa_answer_comment_callback( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
    global $post;

    if( get_user_by( 'id', $comment->user_id ) ) {
        dwqa_load_template( 'content', 'comment' );
    }
}


function dwqa_question_comment_callback( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
    global $post;
    dwqa_load_template( 'content', 'comment-question' );
}

function dwqa_load_template( $name, $extend = false, $include = true ){
    if( $extend ) {
        $name .= '-' . $extend;
    }
    $template = get_stylesheet_directory() . '/dwqa-templates/'.$name.'.php';
    if( ! file_exists($template) ) {
        $template = DWQA_DIR . 'inc/templates/'.$name.'.php';
    }
    if( ! $include ) {
        return $template;
    }
    include $template;
}

function dwqa_single_postclass( $post_class ){
    global $post, $current_user;

    if( get_post_type( $post ) == 'dwqa-answer' ) {

        if( get_post_status( $post->ID ) == 'draft' ) {
            $post_class[] = 'draft-answer';
        }
        if( dwqa_is_answer_flag( $post->ID ) ) {
            $post_class[] = 'answer-flagged-content';
        }
        if( user_can( $post->post_author, 'edit_published_posts' ) ) {
            $post_class[] = 'staff';
        }
        $question_id = get_post_meta( $post->ID, '_question', true );
        $best_answer_id = dwqa_get_the_best_answer( $question_id );
        if( $best_answer_id && $best_answer_id == $post->ID ) {
            $post_class[] = 'best_answer';
        }

        if( ! is_user_logged_in() ||  $current_user->ID != $post->ID || ! current_user_can( 'edit_posts' ) ) {
            $post_class[] = 'no-click';
        }
    }

    return $post_class;
}
add_action( 'post_class', 'dwqa_single_postclass' );

function dwqa_require_field_submit_question(){
    ?>
    <input type="hidden" name="dwqa-action" value="dwqa-submit-question" />
    <?php wp_nonce_field( 'dwqa-submit-question-nonce-#!' ); ?>
    <?php if( ! is_user_logged_in() ) { ?>
    
    <input type="hidden" name="login-type" id="login-type" value="sign-up" autocomplete="off">
    <div class="question-register clearfix">
        <label for="user-email"><?php _e('You need an account to submit question and get answers. Create one:','dwqa') ?></label>
        <div class="register-email register-input">
            <input type="text" size="20" value="" class="input" placeholder="<?php _e('Type your email','dwqa') ?>" name="user-email">
        </div>
        <div class="register-username register-input">
            <input type="text" size="20" value="" class="input" placeholder="Choose an username" name="user-name-signup" id="user-name-signup">
        </div>
        <div class="login-switch"><?php _e('Already a member?','dwqa') ?> <a class="credential-form-toggle" href="<?php echo wp_login_url(); ?>"><?php _e('Log In','dwqa') ?></a></div>
    </div>

    <div class="question-login clearfix hide">
        <label for="user-name"><?php _e('Login to submit your question','dwqa') ?></label>
        <div class="login-username login-input">
            <input type="text" size="20" value="" class="input" placeholder="Type your username" id="user-name" name="user-name">
        </div>
        <div class="login-password login-input">
            <input type="password" size="20" value="" class="input" placeholder="Type your password" id="user-password" name="user-password">
        </div>
        <div class="login-switch"><?php _e('Not yet a member?','dwqa') ?> <a class="credential-form-toggle" href="javascript:void(0);" title="<?php _e('Register','dwqa') ?>"><?php _e('Register','dwqa') ?></a></div>
    </div>
    <?php } ?>
    <?php
}
add_action( 'dwqa_submit_question_ui', 'dwqa_require_field_submit_question' );

function dwqa_require_field_submit_answer( $question_id ){
    ?>
    <?php wp_nonce_field( '_dwqa_add_answer' ); ?>
    <input type="hidden" name="question" value="<?php echo $question_id; ?>" />
    <input type="hidden" name="answer-id" value="0" >
    <input type="hidden" name="dwqa-action" value="add-answer" />
    <?php if( ! is_user_logged_in() ) { ?>
    <label for="answer_notify"><input type="checkbox" name="answer_notify" /> Notify me when have new comment to my answer</label>
    <div class="dwqa-answer-signin hide">
        <input type="text" name="user-email" id="user-email" placeholder="<?php _e('Type your email','dwqa') ?>">
    </div>
    <?php } ?>
    <?php
}
add_action( 'dwqa_submit_answer_ui', 'dwqa_require_field_submit_answer' );


function dwqa_title( $title ){
    if( defined('DWQA_FILTERING') && DWQA_FILTERING ) {
        global $post;
        if ( isset( $post->post_type ) && 'dwqa-question' == $post->post_type && isset( $post->post_status ) && 'private' == $post->post_status ) {
            $private_title_format = apply_filters( 'private_title_format', __( 'Private: %s' ) );
            $title = sprintf( $private_title_format, $title );
        }
    }
    return $title;
}
add_action( 'the_title', 'dwqa_title' );

function dwqa_body_class($classes) {
    global $post, $dwqa_options;
    if( ( $dwqa_options['pages']['archive-question'] && is_page( $dwqa_options['pages']['archive-question'])  )
        || ( is_archive() &&  ( 'dwqa-question' == get_post_type() 
                || 'dwqa-question' == get_query_var( 'post_type' ) 
                || 'dwqa-question_category' == get_query_var( 'taxonomy' ) 
                || 'dwqa-question_tag' == get_query_var( 'taxonomy' ) ) )
    ){
        $classes[] = 'list-dwqa-question';
    }

    if( $dwqa_options['pages']['submit-question'] && is_page( $dwqa_options['pages']['submit-question'] ) ){
        $classes[] = 'submit-dwqa-question';
    }
    return $classes;
}
add_filter('body_class', 'dwqa_body_class');

?>