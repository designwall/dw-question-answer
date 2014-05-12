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
            wp_safe_redirect( get_permalink( $question_id ) );
            query_posts( 'p='.$question_id.'&post_type=dwqa-question' );
            return dwqa_load_template( 'single', 'question', false );
        }
    } 

    if( is_singular( 'dwqa-question' ) ) {
        return dwqa_load_template( 'single', 'question', false );
    }
    return $template;
}
add_filter( "single_template", "dwqa_generate_template_for_plugin", 20 ) ;

/**
 * Filter new page for submit question form
 * @param  string $template Template path
 * @return string           Submit question template path
 */ 
function dwqa_generate_template_for_submit_question_page($template) {
    global $post, $dwqa_options;
    if( isset($dwqa_options['pages']['submit-question']) && is_page( $dwqa_options['pages']['submit-question'] ) ){
        $template = dwqa_load_template( 'submit', 'question', false );
    }
    if( isset($dwqa_options['pages']['archive-question']) && is_page( $dwqa_options['pages']['archive-question'])  ){
        return dwqa_load_template( 'archive', 'question', false );
    }
    return $template;
}
add_filter( 'page_template', 'dwqa_generate_template_for_submit_question_page', 20 );

/**
 * Override template path of comment form in singe page for question custom post type
 * @param  string $comment_template Template path 
 * @return string                   New template path
 */
function dwqa_generate_template_for_comment_form( $comment_template ) {
    if (  is_single() && ('dwqa-question' == get_post_type() || 'dwqa-answer' == get_post_type()) ) {
        return dwqa_load_template( 'comments', false, false );
    }
    return $comment_template;
}

add_filter( "comments_template", "dwqa_generate_template_for_comment_form", 20 );

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
add_filter( 'taxonomy_template', 
    'dwqa_generate_template_for_questions_list_page', 20 );
/**
 * Print class for question detail container
 */
function dwqa_class_for_question_details_container(){
    $class = array();
    $class[] = 'question-details';
    $class = apply_filters( 'dwqa-class-questions-details-container', $class );
    echo implode(' ', $class);
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
    dwqa_load_template( 'content', 'comment' );
}


function dwqa_single_postclass( $post_class ){
    global $post, $current_user;

    if( get_post_type( $post ) == 'dwqa-answer' ) {
        $post_class[] = 'dwqa-answer';
        $post_class[] = 'dwqa-status-' . get_post_status( $post->ID );

        if( dwqa_is_answer_flag( $post->ID ) ) {
            $post_class[] = 'answer-flagged-content';
        }
        if( user_can( $post->post_author, 'edit_published_posts' ) ) {
            $post_class[] = 'staff';
        }
        $question_id = get_post_meta( $post->ID, '_question', true );
        $best_answer_id = dwqa_get_the_best_answer( $question_id );
        if( $best_answer_id && $best_answer_id == $post->ID ) {
            $post_class[] = 'best-answer';
        }

        if( ! is_user_logged_in() ||  $current_user->ID != $post->ID || ! current_user_can( 'edit_posts' ) ) {
            $post_class[] = 'dwqa-no-click';
        }
    }

    if( get_post_type( $post ) == 'dwqa-answer' && get_post_type( $post ) == 'dwqa-question' ) { 
        if( in_array( 'hentry', $post_class ) ) {
            unset($post_class);
        }
    }

    return $post_class;
}
add_action( 'post_class', 'dwqa_single_postclass' );

function dwqa_require_field_submit_question(){
    ?>
    <input type="hidden" name="dwqa-action" value="dwqa-submit-question" />
    <?php wp_nonce_field( 'dwqa-submit-question-nonce-#!' ); ?>

    <?php if( ! is_user_logged_in() && ! dwqa_current_user_can('post_question') ) { ?>
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

    <div class="question-login clearfix dwqa-hide">
        <label for="user-name"><?php _e('Login to submit your question','dwqa') ?></label>
        <div class="login-username login-input">
            <input type="text" size="20" value="" class="input" placeholder="<?php _e('Type your username','dwqa') ?>" id="user-name" name="user-name">
        </div>
        <div class="login-password login-input">
            <input type="password" size="20" value="" class="input" placeholder="<?php _e('Type your password','dwqa') ?>" id="user-password" name="user-password">
        </div>
        <div class="login-switch"><?php _e('Not yet a member?','dwqa') ?> <a class="credential-form-toggle" href="javascript:void(0);" title="<?php _e('Register','dwqa') ?>"><?php _e('Register','dwqa') ?></a></div>
    </div>
    <?php } else if( ! is_user_logged_in() && dwqa_current_user_can('post_question' ) ) { ?>
    <div class="user-email">
        <label for="user-email" title="<?php _e('Enter your email to receive notification regarding your question. Your email is safe with us and will not be published.','dwqa') ?>"><?php _e('Your email *','dwqa') ?></label> 
        <input type="email" name="_dwqa_anonymous_email" id="_dwqa_anonymous_email" class="large-text" placeholder="<?php _e('Email address ...','dwqa') ?>" required> 
        <span><?php printf( __('or <strong><a href="%s">login</a></strong> to submit question', 'dwqa'), wp_login_url( apply_filters( 'the_permalink', get_permalink( get_the_ID() ) ) ) ) ?></span>
    </div>
    <?php  }
}
add_action( 'dwqa_submit_question_ui', 'dwqa_require_field_submit_question' );

function dwqa_require_field_submit_answer( $question_id ){
    ?>
    <?php wp_nonce_field( '_dwqa_add_new_answer' ); ?>
    <input type="hidden" name="question" value="<?php echo $question_id; ?>" />
    <input type="hidden" name="answer-id" value="0" >
    <input type="hidden" name="dwqa-action" value="add-answer" />
    <?php if( ! is_user_logged_in() ) { ?>
    <label for="answer_notify"><input type="checkbox" name="answer_notify" /> Notify me when have new comment to my answer</label>
    <div class="dwqa-answer-signin dwqa-hide">
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


function dwqa_paste_srtip_disable( $mceInit ){
    $mceInit['paste_strip_class_attributes'] = 'none';
    return $mceInit;
}

function dwqa_submit_answer_form(){
    ?>
    <div id="dwqa-add-answers" class="dwqa-answer-form">
        <h3 class="dwqa-headline"><?php _e('Answer this Question', 'dwqa' ); ?></h3>
        <?php  
            if( isset($_GET['errors']) ) {
                echo '<p class="alert">';
                echo urldecode( $_GET['errors'] ) . '<br>';
                echo '</p>';
            }
        ?>
        <form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-add-answer' ); ?>" name="dwqa-answer-question-form" id="dwqa-answer-question-form" method="post">
            <?php  
                add_filter( 'tiny_mce_before_init', 'dwqa_paste_srtip_disable' );
                $editor = array( 
                    'wpautop'       => false,
                    'id'            => 'dwqa-answer-question-editor',
                    'textarea_name' => 'answer-content',
                    'rows'          => 2
                );
            ?>
            <?php dwqa_init_tinymce_editor( $editor ); ?>
            <?php do_action( 'dwqa_submit_answer_ui', get_the_ID() ); ?>
            
            <script type="text/javascript">
             var RecaptchaOptions = {
                theme : 'clean'
             };
             </script>
            <?php  
                global  $dwqa_general_settings;
                if( dwqa_is_captcha_enable_in_single_question() ) {
                    $public_key = isset($dwqa_general_settings['captcha-google-public-key']) ?  $dwqa_general_settings['captcha-google-public-key'] : '';
                    echo '<div class="google-recaptcha">';
                    echo recaptcha_get_html($public_key);
                    echo '<br></div>';
                }
            ?>
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
}

function dwqa_paged_query(){
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    echo '<input type="hidden" name="dwqa-paged" id="dwqa-paged" value="'.$paged.'" >';
}
add_action( 'dwqa-prepare-archive-posts', 'dwqa_paged_query' );


function dwqa_add_guide_menu_icons_styles(){
?>
    <style>
    #adminmenu .menu-icon-dwqa-question div.wp-menu-image:before {
      content: "\f468";
    }
    </style>
<?php
}
add_action( 'admin_head', 'dwqa_add_guide_menu_icons_styles' );




function dwqa_load_template( $name, $extend = false, $include = true ){
    global $dwqa_template;
    
    $check = true;
    if( $extend ) {
        $name .= '-' . $extend;
    }
    $template = get_stylesheet_directory() . '/dwqa-templates/'.$name.'.php';
    if( ! file_exists($template) ) {
        $template = DWQA_DIR . 'inc/templates/'.$dwqa_template.'/' .$name.'.php';
    }

    $template = apply_filters( 'dwqa-load-template', $template, $name );
    if( ! $template ) {
        return false;
    }
    if( ! $include ) {
        return $template;
    }
    include $template;
}


/**
 * Enqueue all scripts for plugins on front-end
 * @return void
 */     
function dwqa_enqueue_scripts(){
    global $dwqa_options, $script_version, $dwqa_template, $dwqa_sript_vars;

    $question_category_rewrite = get_option( 'dwqa-question-category-rewrite', 'question-category' );
    $question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
    $question_tag_rewrite = get_option( 'dwqa-question-tag-rewrite', 'question-tag' );
    $question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';

    $assets_folder = DWQA_URI . 'inc/templates/' . $dwqa_template . '/assets/';
    wp_enqueue_script( 'jquery' );   
    if( is_singular( 'dwqa-question' ) ) {
        wp_enqueue_script( 'jquery-effects-core' );
        wp_enqueue_script( 'jquery-effects-highlight' );
    }
    $version = $script_version;
    

    // Enqueue style
    wp_enqueue_style( 'dwqa-style', $assets_folder . 'css/style.css', array(), $version );
    // Enqueue for single question page
    if( is_single() && 'dwqa-question' == get_post_type() ) {
        // js
        wp_enqueue_script( 'dwqa-single-question', $assets_folder . 'js/dwqa-single-question.js', array('jquery'), $version, true );
        $single_script_vars = $dwqa_sript_vars;
        $single_script_vars['question_id'] = get_the_ID();
        wp_localize_script( 'dwqa-single-question', 'dwqa', $single_script_vars );
    }


    if( (is_archive() && 'dwqa-question' == get_post_type()) || ( isset( $dwqa_options['pages']['archive-question'] ) && is_page( $dwqa_options['pages']['archive-question'] ) ) ) {
        wp_enqueue_script( 'dwqa-questions-list', $assets_folder . 'js/dwqa-questions-list.js', array( 'jquery' ), $version, true );
        wp_localize_script( 'dwqa-questions-list', 'dwqa', $dwqa_sript_vars );
    }

    if( isset($dwqa_options['pages']['submit-question']) 
        && is_page( $dwqa_options['pages']['submit-question'] ) ) {
        wp_enqueue_script( 'dwqa-submit-question', $assets_folder . 'js/dwqa-submit-question.js', array( 'jquery' ), $version, true );
        wp_localize_script( 'dwqa-submit-question', 'dwqa', $dwqa_sript_vars );
    }
}
add_action( 'wp_enqueue_scripts', 'dwqa_enqueue_scripts' );

function dwqa_comment_form( $args = array(), $post_id = null ) {
    if ( null === $post_id )
        $post_id = get_the_ID();
    else
        $id = $post_id;

    $commenter = wp_get_current_commenter();
    $user = wp_get_current_user();
    $user_identity = $user->exists() ? $user->display_name : '';

    $args = wp_parse_args( $args );
    if ( ! isset( $args['format'] ) )
        $args['format'] = current_theme_supports( 'html5', 'comment-form' ) ? 'html5' : 'xhtml';

    $req      = get_option( 'require_name_email' );
    $aria_req = ( $req ? " aria-required='true'" : '' );
    $html5    = 'html5' === $args['format'];
    $fields   =  array(
        'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
                    '<input id="email-'.$post_id.'" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>'
    );

    $required_text = sprintf( ' ' . __('Required fields are marked %s'), '<span class="required">*</span>' );

    /**
     * Filter the default comment form fields.
     *
     * @since 3.0.0
     *
     * @param array $fields The default comment fields.
     */
    $fields = apply_filters( 'comment_form_default_fields', $fields );
    $defaults = array(
        'fields'               => $fields,
        'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun' ) . '</label> <textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
        'must_log_in'          => '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.','dwqa' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
        'logged_in_as'         => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>' ), get_edit_user_link(), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
        'comment_notes_before' => '<p class="comment-notes">' . __( 'Your email address will not be published.','dwqa' ) . ( $req ? $required_text : '' ) . '</p>',
        'comment_notes_after'  => '<p class="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s','dwqa' ), ' <code>' . allowed_tags() . '</code>' ) . '</p>',
        'id_form'              => 'commentform',
        'id_submit'            => 'submit',
        'title_reply'          => __( 'Leave a Reply','dwqa' ),
        'title_reply_to'       => __( 'Leave a Reply to %s','dwqa' ),
        'cancel_reply_link'    => __( 'Cancel reply', 'dwqa' ),
        'label_submit'         => __( 'Post Comment', 'dwqa' ),
        'format'               => 'xhtml',
    );

    /**
     * Filter the comment form default arguments.
     *
     * Use 'comment_form_default_fields' to filter the comment fields.
     *
     * @since 3.0.0
     *
     * @param array $defaults The default comment form arguments.
     */
    $args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );

    ?>
        <?php if ( comments_open( $post_id ) ) : ?>
            <?php
            /**
             * Fires before the comment form.
             *
             * @since 3.0.0
             */
            do_action( 'comment_form_before' );
            ?>
            <div id="dwqa-respond" class="dwqa-comment-form">
                <?php if ( get_option( 'comment_registration' ) && !is_user_logged_in() ) : ?>
                    <?php echo $args['must_log_in']; ?>
                    <?php
                    /**
                     * Fires after the HTML-formatted 'must log in after' message in the comment form.
                     *
                     * @since 3.0.0
                     */
                    do_action( 'comment_form_must_log_in_after' );
                    ?>
                <?php else : ?>
                    <form action="<?php echo site_url( '/wp-comments-post.php' ); ?>" method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>" class="comment-form"<?php echo $html5 ? ' novalidate' : ''; ?>>
                        <?php
                        /**
                         * Fires at the top of the comment form, inside the <form> tag.
                         *
                         * @since 3.0.0
                         */
                        do_action( 'comment_form_top' );
                        ?>
                        <?php if ( is_user_logged_in() ) : ?>
                            <?php
                            /**
                             * Filter the 'logged in' message for the comment form for display.
                             *
                             * @since 3.0.0
                             *
                             * @param string $args['logged_in_as'] The logged-in-as HTML-formatted message.
                             * @param array  $commenter            An array containing the comment author's username, email, and URL.
                             * @param string $user_identity        If the commenter is a registered user, the display name, blank otherwise.
                             */
                            echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity );
                            ?>
                            <?php
                            /**
                             * Fires after the is_user_logged_in() check in the comment form.
                             *
                             * @since 3.0.0
                             *
                             * @param array  $commenter     An array containing the comment author's username, email, and URL.
                             * @param string $user_identity If the commenter is a registered user, the display name, blank otherwise.
                             */
                            do_action( 'comment_form_logged_in_after', $commenter, $user_identity );
                            ?>
                        <?php else : ?>
                            <?php echo $args['comment_notes_before']; ?>
                            <?php
                            /**
                             * Fires before the comment fields in the comment form.
                             *
                             * @since 3.0.0
                             */
                            do_action( 'comment_form_before_fields' );
                            echo '<div class="dwqa-anonymous-fields">';
                            foreach ( (array) $args['fields'] as $name => $field ) {
                                /**
                                 * Filter a comment form field for display.
                                 *
                                 * The dynamic portion of the filter hook, $name, refers to the name
                                 * of the comment form field. Such as 'author', 'email', or 'url'.
                                 *
                                 * @since 3.0.0
                                 *
                                 * @param string $field The HTML-formatted output of the comment form field.
                                 */
                                echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";
                            }
                            echo '</div>';
                            /**
                             * Fires after the comment fields in the comment form.
                             *
                             * @since 3.0.0
                             */
                            do_action( 'comment_form_after_fields' );
                            ?>
                        <?php endif; ?>
                        <?php
                        /**
                         * Filter the content of the comment textarea field for display.
                         *
                         * @since 3.0.0
                         *
                         * @param string $args['comment_field'] The content of the comment textarea field.
                         */
                        echo apply_filters( 'comment_form_field_comment', $args['comment_field'] );
                        ?>
                        <?php echo $args['comment_notes_after']; ?>
                        <p class="dwqa-form-submit dwqa-hide">
                            <input name="submit" type="submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" value="<?php echo esc_attr( $args['label_submit'] ); ?>" class="dwqa-btn dwqa-btn-primary" />
                            <?php comment_id_fields( $post_id ); ?>
                        </p>
                        <?php
                        /**
                         * Fires at the bottom of the comment form, inside the closing </form> tag.
                         *
                         * @since 1.5.0
                         *
                         * @param int $post_id The post ID.
                         */
                        do_action( 'comment_form', $post_id );
                        ?>
                    </form>
                <?php endif; ?>
            </div><!-- #respond -->
            <?php
            /**
             * Fires after the comment form.
             *
             * @since 3.0.0
             */
            do_action( 'comment_form_after' );
        else :
            /**
             * Fires after the comment form if comments are closed.
             *
             * @since 3.0.0
             */
            do_action( 'comment_form_comments_closed' );
        endif;
}

function dwqa_display_sticky_questions(){
    $sticky_questions = get_option( 'dwqa_sticky_questions', array() );
    if( !empty($sticky_questions) ) {
            $query = array(
                'post_type' => 'dwqa-question',
                'post__in' => $sticky_questions,
                'posts_per_page' => -1
            );
            query_posts( $query );
    ?>
    <div class="sticky-questions">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php dwqa_load_template( 'content', 'question' ); ?>
        <?php endwhile; ?>
    </div>
    <?php   
        wp_reset_query();
    }
}
add_action( 'dwqa-before-question-list', 'dwqa_display_sticky_questions' );

function dwqa_is_sticky($question_id = false){
    if(  ! $question_id ) {
        $question_id = get_the_ID();
    }
    $sticky_questions = get_option( 'dwqa_sticky_questions', array() );
    if( in_array( $question_id, $sticky_questions) ) {
        return true;
    }
    return false;
}


function dwqa_question_states( $states, $post ){
    if( dwqa_is_sticky( $post->ID ) && 'dwqa-question' == get_post_type( $post->ID ) ) {
        $states[] = __('Sticky Question','dwqa');
    }
    return $states;
}
add_filter( 'display_post_states', 'dwqa_question_states', 10, 2 );


function dwqa_get_ask_question_link( $echo = true, $label = false, $class = false ){
    global $dwqa_options;
    $submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
    if( $dwqa_options['pages']['submit-question'] && $submit_question_link ) {
        $label = $label ? $label : __('Ask a question','dwqa');
        $class = $class ? $class  : 'dwqa-btn-success';
        $button = '<a href="'.$submit_question_link.'" class="dwqa-btn '.$class.'">'.$label.'</a>';
        $button = apply_filters( 'dwqa_ask_question_link', $button, $submit_question_link );
        if( ! $echo ) {
            return $button;
        }
        echo $button;
    }
}



?>
