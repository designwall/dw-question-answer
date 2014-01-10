<?php  
global $post_submit_filter;

$post_submit_filter     = array(
                            'a'             => array(
                                'href'  => array(),
                                'title' => array()
                            ),
                            'br'            => array(),
                            'em'            => array(),
                            'strong'        => array(),
                            'code'          => array(
                                    'class'     => array()
                                ),
                            'blockquote'    => array(),
                            'quote'         => array(),
                            'span'          =>  array(
                                'style' =>  array()
                            ),
                            'img'           => array(
                                    'src'   => array(),
                                    'alt'   => array(),
                                    'width' => array(),
                                    'height'=> array(),
                                    'style' => array()
                                ),
                            'ul'            => array(),
                            'li'            => array(),
                            'ol'            => array(),
                            'pre'            => array()
                            
                        );
/**
 * AJAX: vote for a question/answer
 * @return json
 */
function dwqa_action_vote(){
    $result = array(
        'error_code'    => 'authorization',  
        'error_message' => __('Are you cheating, huh?', 'dwqa' )
    );

    $vote_for = isset($_POST['vote_for']) && $_POST['vote_for'] == 'question'
                    ? 'question' : 'answer';

    if( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_dwqa_'.$vote_for.'_vote_nonce' ) )
    {
        wp_send_json_error( $result );
    }


    if( ! isset( $_POST[ $vote_for . '_id'] ) ) {
        $result['error_code']       = 'missing ' . $vote_for;
        $result['error_message']    = __('What '.$vote_for.' are you looking for? ', 'dwqa');
        wp_send_json_error( $result );
    }

    $post_id = $_POST[ $vote_for . '_id'];
    $point = isset( $_POST['type'] ) && $_POST['type'] == 'up' ? 1 : -1;


    //vote
    if( is_user_logged_in() ) {
        global $current_user;

        if( ! dwqa_is_user_voted( $post_id, $point ) ) {
            $votes = maybe_unserialize(  get_post_meta( $post_id, '_dwqa_votes_log', true ) );

            $votes[$current_user->ID] = $point;
            //update
            update_post_meta( $post_id, '_dwqa_votes_log', serialize($votes) );
            // Update vote point
            dwqa_update_vote_count( $post_id );

            $point = dwqa_vote_count( $post_id );
            if( $point > 0 ) {
                $point = '+' . $point;
            }
            wp_send_json_success( array(
                'vote'  => $point
            ) );

        } else {
            $result['error_code'] = 'voted';
            $result['error_message']    = __( 'You voted for this ' . $vote_for,'dwqa' );
            wp_send_json_error( $result );
        }
        
    } else if( 'question' == $vote_for ) {
        // useful of question with meta field is "_dwqa_question_useful", point of this question
        $useful = get_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful', true );
        $useful = $useful ? (int) $useful : 0;
        update_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful', $useful+$point );

        // Number of votes by guest
        $useful_rate = get_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful_rate', true );
        $useful_rate = $useful_rate ? (int) $useful_rate : 0;
        update_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful_rate', $useful_rate + 1 );
    }
}
add_action( 'wp_ajax_dwqa-action-vote', 'dwqa_action_vote' );
add_action( 'wp_ajax_nopriv_dwqa-action-vote', 'dwqa_action_vote' );

/**
 * Check for current user can vote for the question
 * @param  int  $post_id ID of object ( question /answer ) post
 * @param  int  $point       Point of vote
 * @param  boolean $user        Current user id
 * @return boolean              Voted or not
 */
function dwqa_is_user_voted( $post_id, $point, $user = false ){
    if( ! $user ) {
        global $current_user;
        $user = $current_user->ID;
    }
    $votes = maybe_unserialize(  get_post_meta( $post_id, '_dwqa_votes_log', true ) );

    if( empty($votes) ) { 
        return false; 
    }

    if( array_key_exists( $user, $votes) ) {
        if( (int) $votes[$user] == $point ) {
            return true;
        }
    }
    return false;   
}

function dwqa_get_user_vote( $post_id, $user = false ){
    if( ! $user ) {
        global $current_user;
        $user = $current_user->ID;
    }
    if( dwqa_is_user_voted( $post_id, 1, $user) ){
        return 'up';
    } else if( dwqa_is_user_voted( $post_id, -1, $user) ) {
        return 'down';
    }
    return false;
}
/**
 * Calculate number of votes for specify post
 * @param  int $post_id ID of post
 * @return void              
 */
function dwqa_update_vote_count( $post_id ) {
    if( ! $post_id ) {
        global $post;
        $post_id = $post->ID;
    }
    $votes = maybe_unserialize(  get_post_meta( $post_id, '_dwqa_votes_log', true ) );
    
    if( empty($votes) ) {
        return 0;
    }

    $total = 0;
    foreach ($votes as $user => $vote ) {
        $total += $vote;
    }

    update_post_meta( $post_id, '_dwqa_votes', $total );
}

/**
 * Return vote point of post
 * @param  int $post_id ID of post
 * @param  boolean $echo        Print or not
 * @return int  Vote point
 */
function dwqa_vote_count( $post_id = false, $echo = false ){
    if( ! $post_id ) {
        global $post;
        $post_id = $post->ID;
    }
    $votes =  get_post_meta( $post_id, '_dwqa_votes', true );
    if( empty($votes) ) {
        return 0;
    } 
    if( $echo ) {
        echo $votes;
    }
    return (int) $votes;
}


/**
 *  ANSWER
 */

/**
 * Add new answer for a specify question
 * @return [type] [description]
 */
function dwqa_add_answer(){
    global $post_submit_filter, $dwqa_current_error, $dwqa_options;
    if( ! isset($_POST['dwqa-action']) || ! isset( $_POST['submit-answer'] ) ) {
        return false;
    }
    $dwqa_add_answer_errors = new WP_Error();
    if( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce( $_POST['_wpnonce'], '_dwqa_add_new_answer' ) ) {
        $dwqa_add_answer_errors->add( 'answer_question', '"Helllo", Are you cheating huh?.' );
    }
    if( $_POST['submit-answer'] == __('Delete draft','dwqa') ) {
        $draft = $_POST['answer-id'];
        wp_delete_post( $draft );
        return false;
    }

    if( empty( $_POST['answer-content'] ) ||  empty( $_POST['question'] ) ) {
        if( empty($_POST['answer-content']) ) {
            $dwqa_add_answer_errors->add('answer_question','answer content is empty' );
        } 
        if( empty($_POST['question']) ) {
            $dwqa_add_answer_errors->add('answer_question','question is empty' );
        }
    }else{

        $user_id = 0;
        $is_anonymous = false;
        if( is_user_logged_in() ){
            $user_id = get_current_user_id();
        } else {
            $is_anonymous = true;
            if( isset($_POST['user-email']) && is_email( $_POST['user-email'] ) ) {
                $post_author_email = $_POST['user-email'];
            }
        }

        $question_id = (int) $_POST['question'];
        $question = get_post( $question_id );

        $answer_title = __( 'Answer for ', 'dwqa' ) . $question->post_title;

        $answ_content = dwqa_pre_content_filter( $_POST['answer-content'] );
        $answ_content = wp_kses(  $answ_content, $post_submit_filter );
        $post_status = ( isset($_POST['private-message']) && $_POST['private-message'] ) ? 'private' : 'publish';
        $answers = array(
            'comment_status' => 'open',
            'post_author'    => $user_id,
            'post_content'   => $answ_content,
            'post_status'    => $post_status,
            'post_title'     => $answer_title,
            'post_type'      => 'dwqa-answer'
        );
        if( $_POST['submit-answer'] == __( 'Save draft','dwqa' ) ) {
            $answers['post_status'] = 'draft';
        } else if( isset($_POST['privacy']) && 'private' == $_POST['privacy'] ) {
            $answers['post_status'] = 'private';
        }

        switch ( $_POST['dwqa-action'] ) {
            case 'add-answer':
                if( dwqa_current_user_can('post_answer') ) {
                    $answer_id = wp_insert_post( $answers, true );
                } else {
                    $answer_id =  new WP_Error('permission', __("You do not have permission to submit question.",'dwqa') );
                }
                
                if( ! is_wp_error( $answer_id ) ) {
                    //Send email alert for author of question about this answer
                    $question_author = $question->post_author;
                    if( isset($_POST['submit-answer-and-resolve']) ) {
                        if( $answers['post_status'] != 'draft' ) {
                            update_post_meta( $question_id, '_dwqa_status', 'resolved' );
                        }
                        update_post_meta( $question_id, '_dwqa_resolved_time', time() );
                    }
                    update_post_meta( $answer_id, '_question', $question_id  );
                    if( $is_anonymous ) {
                        update_post_meta( $answer_id, '_dwqa_is_anonymous', true );
                        if( isset($post_author_email) && is_email( $post_author_email ) ){
                            update_post_meta( $answer_id, '_dwqa_anonymous_email', $post_author_email );
                        }
                    }
                    do_action( 'dwqa_add_answer', $answer_id );
                    wp_redirect( get_permalink($question_id) );
                    return true;
                } else {
                    $dwqa_current_error = $answer_id;
                }
                break;
            case 'update-answer':
                $answer_update = array(
                    'ID'    => $_POST['answer-id'],
                    'post_content'   => $answ_content
                );
                if( isset($_POST['dwqa-action-draft']) && $_POST['dwqa-action-draft'] && strtolower( $_POST['submit-answer'] ) == 'publish' ) {
                    $answer_update['post_status'] = isset($_POST['privacy']) && 'private' == $_POST['privacy'] ? 'private' : 'publish';
                }
                $answer_id = wp_update_post( $answer_update );
                do_action( 'dwqa_update_answer', $answer_id );
                if( $answer_id ) {
                    wp_safe_redirect( get_permalink( $question_id ) );
                    return true;
                }
                break;

        }

    }
    $dwqa_current_error = $dwqa_add_answer_errors;
}
add_action( 'wp_ajax_dwqa-add-answer', 'dwqa_add_answer' );
add_action( 'wp_ajax_nopriv_dwqa-add-answer', 'dwqa_add_answer' );

/**
 * Change redirect link when comment for answer finished
 * @param  string $location Old redirect link
 * @param  object $comment  Comment Object
 * @return string           New redirect link
 */
function dwqa_hook_redirect_comment_for_answer( $location, $comment ){

    if( 'dwqa-answer' == get_post_type( $comment->comment_post_ID ) ) {
        $question = get_post_meta( $comment->comment_post_ID, '_question', true );
        if( $question ) {
            return get_post_permalink( $question ).'#'.'answer-' . $comment->comment_post_ID . '&comment='.$comment->comment_ID;
        }
    }
    return $location;
}
add_filter( 'comment_post_redirect', 'dwqa_hook_redirect_comment_for_answer',
            10, 2 );


/**
 * Displays form token for unfiltered comments. Override wp_comment_form_unfiltered_html_nonce custom for dwqa
 *
 * Backported to 2.0.10.
 *
 * @since 2.1.3
 * @uses $post Gets the ID of the current post for the token
 */
function dwqa_wp_comment_form_unfiltered_html_nonce() {
    $post = get_post();
    $post_id = $post ? $post->ID : 0;

    if ( current_user_can( 'unfiltered_html' ) 
            && 'dwqa-answer' != get_post_type( $post_id )  ) {
        wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_disabled', false );
        echo "<script>(function(){if(window===window.parent){document.getElementById('_wp_unfiltered_html_comment_disabled').name='_wp_unfiltered_html_comment';}})();</script>\n";
    } elseif( current_user_can( 'unfiltered_html' ) 
                    && 'dwqa-answer' == get_post_type( $post_id ) ) {
                        
        wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_answer_disabled', false );
        echo "<script>(function(){if(window===window.parent){document.getElementById('_wp_unfiltered_html_comment_answer_disabled').name='_wp_unfiltered_html_comment';}})();</script>\n";
    }
}
remove_action( 'comment_form', 'wp_comment_form_unfiltered_html_nonce' );
add_action( 'comment_form', 'dwqa_wp_comment_form_unfiltered_html_nonce' );

/**
 * Remove an answer with specify id
 */
function dwqa_remove_answer(){
    if( ! isset($_POST['wpnonce']) || ! wp_verify_nonce( $_POST['wpnonce'], '_dwqa_action_remove_answer_nonce' ) ) {
        wp_send_json_error( array( 'message' => __('Are you cheating huh?','dwqa' ) ) );
    }
    if( ! isset($_POST['answer_id']) ) {
        wp_send_json_error( array( 'message' => __('Missing answer ID','dwqa') ) );
    }
    wp_delete_post( $_POST['answer_id'] );
    wp_send_json_success();
}
add_action( 'wp_ajax_dwqa-action-remove-answer', 'dwqa_remove_answer' );
/** QUESTION */

/**
 * Save question submitted
 * @return void
 */
function dwqa_submit_question(){
    global $post_submit_filter, $dwqa_options;

    if( isset($_POST['dwqa-action']) && 'dwqa-submit-question' == $_POST['dwqa-action'] )
    {
        global $dwqa_current_error;

        $dwqa_submit_question_errors = new WP_Error();
        if( isset($_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'dwqa-submit-question-nonce-#!' ) ) {
            if( empty($_POST['question-title']) ) {

                $dwqa_submit_question_errors->add( 'submit_question', 'You must enter a valid question title' );
                return false;
            }

            $title = esc_html( $_POST['question-title'] );

            $category = isset($_POST['question-category']) ? 
                        (int) $_POST['question-category'] : 0;
            if( ! term_exists( $category, 'dwqa-question_category' ) ){
                $category = 0;
            }

            $tags = isset($_POST['question-tag']) ? 
                        esc_html( $_POST['question-tag'] ): '';

            $content = isset($_POST['question-content']) ? 
                        $_POST['question-content'] : '';
            $content = wp_kses( dwqa_pre_content_filter( $content ), $post_submit_filter );
            
            $user_id = 0;
            $is_anonymous = false;
            if( is_user_logged_in() ){
                $user_id = get_current_user_id();
            } else {
                //$post_author_email = $_POST['user-email'];
                if( isset($_POST['login-type']) && $_POST['login-type'] == 'sign-in' ) {
                    $user = wp_signon( array(
                        'user_login'    => $_POST['user-name'],
                        'user_password' => $_POST['user-password']
                    ), false );

                    if( ! is_wp_error( $user ) ) {
                        global $current_user;
                        $current_user = $user;
                        get_currentuserinfo();
                        $user_id = $user->data->ID;
                    } else {
                        $dwqa_current_error = $user;
                        return false;
                    }
                } else {
                    //Create new user 
                    $users_can_register = get_option( 'users_can_register' );
                    if( isset($_POST['user-email']) && isset($_POST['user-name-signup']) 
                            && $users_can_register && ! email_exists( $_POST['user-email'] ) 
                                && ! username_exists( $_POST['user-name-signup'] ) ) {

                        if( isset($_POST['password-signup']) ) {
                            $password = $_POST['password-signup'];
                        } else {
                            $password = wp_generate_password( 12, false );
                        }

                        $user_id = wp_create_user( $_POST['user-name-signup'], $password, $_POST['user-email'] );
                        if( is_wp_error( $user_id ) ) {
                            $dwqa_current_error = $user_id;
                            return false;
                        }
                        wp_new_user_notification( $user_id, $password );
                        $user = wp_signon( array(
                            'user_login'    => $_POST['user-name-signup'],
                            'user_password' => $password
                        ), false );
                        if( ! is_wp_error($user) ) {
                            global $current_user;
                            $current_user = $user;
                            get_currentuserinfo();
                            $user_id = $user->data->ID;
                        } else {
                            $dwqa_current_error = $user;
                            return false;
                        }
                    } else {
                        $message = '';
                        if( ! $users_can_register ) {
                            $message .= __('User Registration was disabled.','dwqa').'<br>';
                        }
                        if( email_exists( $_POST['user-email'] ) ) {
                            $message .= __('This email is already registered, please choose another one.','dwqa').'<br>';
                        }
                        if( username_exists( $_POST['user-name'] ) ) {
                            $message .= __('This username is already registered. Please choose another one.','dwqa').'<br>';
                        }
                        $dwqa_current_error = new WP_Error( 'submit_question', $message );
                        return false;
                    }
                }
            }

            $post_status = ( isset($_POST['private-message']) && $_POST['private-message'] ) ? 'private' : 'publish';
            $postarr = array(
                'comment_status' => 'open',
                'post_author'    => $user_id,
                'post_content'   => $content,
                'post_status'    => $post_status,
                'post_title'     => $title,
                'post_type'      => 'dwqa-question',
                'tax_input'      => array(
                    'dwqa-question_category'    => array( $category ),
                    'dwqa-question_tag'         => explode(',', $tags )
                )
            );  

            if( dwqa_current_user_can('post_question') ) {
                $new_question = dwqa_insert_question( $postarr );
            } else {
                $new_question = new WP_Error('permission', __("You do not have permission to submit question.",'dwqa') );
            }

            if( ! is_wp_error( $new_question ) ) {
                exit( wp_safe_redirect( get_permalink( $new_question ) ) );
            } else {
                $dwqa_current_error = $new_question;
            }
        }else{
            $dwqa_submit_question_errors->add( 'submit_question', '"Helllo", Are you cheating huh?.' );
        }
        $dwqa_current_error = $dwqa_submit_question_errors;

    }
}
add_action( 'init','dwqa_submit_question', 11 );


function dwqa_insert_question( $args ){

    $user_id = get_current_user_id();

    $args = wp_parse_args( $args, array(
        'comment_status' => 'open',
        'post_author'    => $user_id,
        'post_content'   => '',
        'post_status'    => 'draft',
        'post_title'     => '',
        'post_type'      => 'dwqa-question'
    ) );
            
    $new_question = wp_insert_post( $args, true );

    if( ! is_wp_error( $new_question ) ) {

        if( isset($args['tax_input']) ) {
            foreach ($args['tax_input'] as $taxonomy => $tags ) {
                wp_set_post_terms( $new_question, $tags, $taxonomy );
            }
        }
        update_post_meta( $new_question, '_dwqa_status', 'open' );
        update_post_meta( $new_question, '_dwqa_views', 0 );
        update_post_meta( $new_question, '_dwqa_votes', 0 );
        //Call action when add question successfull
        do_action( 'dwqa_add_question', $new_question, $user_id );
    } 

    return $new_question;
}

/**
 * Return number of answer for a question
 * @param  int $question_id Question ID ( if null get ID of current post )
 * @return int      Number of answer
 */
function dwqa_question_answers_count( $question_id = null){
    if( ! $question_id ) {
        global $post;
        $question_id = $post->ID;
    }

    $args = array(
       'post_type' => 'dwqa-answer',
       'post_status' => 'publish',
       'meta_query' => array(
           array(
               'key' => '_question',
               'value' => array( $question_id ),
               'compare' => 'IN',
           )
       )
    );
    $answers = new WP_Query($args);
    return $answers->post_count;
}
/**
 * Init or increase views count for single question 
 * @return void 
 */ 
function dwqa_question_view(){
    global $post;
    if( is_single() && get_post_type() === 'dwqa-question' ) {
        $refer = wp_get_referer();
        if( is_user_logged_in() ) {
            global $current_user;
            if( user_can( $current_user->ID, 'edit_posts' ) ) {
                update_post_meta( $post->ID, '_dwqa_admin_checked', date('H:i:s Y-m-d') );
                update_post_meta( $post->ID, '_dwqa_admin_checked_id', $current_user->ID );
            }
        }
        if( $refer && $refer != get_permalink( $post->ID ) ) {
            if( is_single() && 'dwqa-question' == get_post_type() ) {
                $views = get_post_meta( $post->ID, '_dwqa_views', true );

                if( ! $views ) {
                    $views = 1;
                } else {
                    $views = ( (int) $views ) + 1;
                }
                update_post_meta( $post->ID, '_dwqa_views', $views );
            }
        }
    }
}
add_action( 'dwqa_before_page', 'dwqa_question_view' );

/**
 * Count number of views for a questions
 * @param  int $question_id Question Post ID
 * @return int Number of views
 */ 
function dwqa_question_views_count( $question_id = null ){
    if( ! $question_id ) {
        global $post;
        $question_id = $post->ID;
    }
    $views = get_post_meta( $question_id, '_dwqa_views', true );

    if( ! $views ) {
        return 0; 
    } else {
        return (int) $views;
    }
}

/**
 * AJAX: update post status
 * @return void 
 */
function dwqa_question_update_status(){
    if( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], '_dwqa_update_question_status_nonce' ) ) {
        wp_die( 0 );
    } 
    if( ! isset($_POST['question']) ) {
        wp_die( 0 );
    }
    if( ! isset($_POST['status']) || ! in_array( $_POST['status'], array( 'open', 're-open', 'resolved', 'closed', 'pending') ) ) {
        wp_die( 0 );
    }

    global $current_user;
    $question = get_post( $_POST['question'] );
    if( current_user_can( 'edit_posts', $_POST['question']   ) || $current_user->ID == $question->post_author ) { 
        update_post_meta( $_POST['question'], '_dwqa_status', $_POST['status'] );
        if( $_POST['status'] == 'resolved' ) {
            update_post_meta( $_POST['question'], '_dwqa_resolved_time', time() );
        } 
    }
}
add_action( 'wp_ajax_dwqa-update-question-status', 
                'dwqa_question_update_status' );   



function dwqa_pre_content_filter($content){
    return preg_replace_callback( '/<(code)([^>]*)>(.*)<\/(code)[^>]*>/isU' , 'dwqa_convert_pre_entities',  $content );
}

function dwqa_convert_pre_entities( $matches ) {
    $string = $matches[0];
    preg_match('/class=\\\"([^\\\"]*)\\\"/', $matches[2], $sub_match );
    if( empty($sub_match) ) {
        $string = str_replace( $matches[1], $matches[1] . ' class="prettyprint"', $string );
    } else {
        if( strpos($sub_match[1], 'prettyprint') === false ) {
            $new_class = str_replace( $sub_match[1], $sub_match[1] . ' prettyprint', $sub_match[0] );
            $string = str_replace( $matches[2], str_replace( $sub_match[0], $new_class, $matches[2] ), $string );
        }
    }

    $string = str_replace( $matches[3],  htmlentities( $matches[3], ENT_COMPAT , 'UTF-8', false  ), $string );
    
    return '<pre>' . $string . '</pre>';
}

function dwqa_content_html_decode($content){
    return preg_replace_callback( '/(<pre>)<code([^>]*)>(.*)<\/(code)[^>]*>(<\/pre[^>]*>)/isU' , 'dwqa_decode_pre_entities',  $content );
}

function dwqa_decode_pre_entities( $matches ){
    $content = $matches[0];
    $content = str_replace( $matches[1], '', $content);
    $content = str_replace( $matches[5], '', $content);
    $content = str_replace( $matches[2], '', $content );
    //$content = str_replace( $matches[3], html_entity_decode($matches[3]), $content);
    return $content;
}

if( ! function_exists('dw_strip_email_to_display') ) { 
    /**
     * Strip email for display in front end
     * @param  string  $text name
     * @param  boolean $echo Display or just return
     * @return string        New text that was stripped
     */
    function dw_strip_email_to_display( $text, $echo = false ){
        preg_match('/([^\@]*)\@(.*)/i', $text, $matches );
        if( ! empty( $matches ) ) {
            $text = $matches[1] . '@...';
        }
        if( $echo ) {
            echo $text;
        }
        return $text;
    }
}  


function dwqa_action_update_comment(){
    global $post_submit_filter;
    $comment_content = esc_html( $_POST['comment'] );
    $comment_id = $_POST['comment_id'];

    if( ! isset($_POST['wpnonce']) || ! wp_verify_nonce( $_POST['wpnonce'], '_dwqa_action_comment_edit_nonce' ) ) {
        wp_send_json_error( array( 
            'message'   => __( 'Are you cheating huh?', 'dwqa' )  
        ) );
    }
    if( strlen( $comment_content ) <= 0 || ! isset($comment_id) || (int)$comment_id <= 0 ) {
        wp_send_json_error( array(
            'message'   => __( 'Comment content must not be empty.', 'dwqa' )  
        ) );
    }else{
        $commentarr = array(
            'comment_ID'        => $comment_id,
            'comment_content'   => $comment_content
        );
        
        wp_update_comment( $commentarr );
        wp_send_json_success();
    }
}
add_action( 'wp_ajax_dwqa-action-update-comment', 'dwqa_action_update_comment' );

/**
 * AJAX:Remove comment of quest/answer away from database
 */
function dwqa_action_delete_comment(){
    if( ! isset($_POST['wpnonce']) || ! wp_verify_nonce( $_POST['wpnonce'], '_dwqa_action_comment_delete_nonce' ) ) {
        wp_send_json_error( array(
            'message'   => __( 'Are you cheating huh?', 'dwqa' )  
        ) );
    }
    if( ! isset($_POST['comment_id']) ) {
        wp_send_json_error( array(
            'message'   => __( 'Comment ID must be show.', 'dwqa' )  
        ) );
    }

    wp_delete_comment( $_POST['comment_id'] );
    wp_send_json_success();
}
add_action( 'wp_ajax_dwqa-action-delete-comment', 'dwqa_action_delete_comment' );
/**
 * Flag as spam answer
 */
function dwqa_flag_answer(){
    if( !isset($_POST['wpnonce']) || !wp_verify_nonce( $_POST['wpnonce'], '_dwqa_action_flag_answer_nonce' ) ) {
        wp_send_json_error( array( 'message' => __('Are you cheating huh?', 'dwqa' ) ) );
    }
    if( !isset($_POST['answer_id']) ) {
        wp_send_json_error( array( 'message' => __('Missing id of answer', 'dwqa' ) ) );
    }
    global $current_user;
    $flag = get_post_meta( $_POST['answer_id'], '_flag', true );
    if( !$flag ) {
        $flag = array();
    } else {
        $flag = unserialize($flag);
    }
    // _flag[ user_id => flag_bool , ...]
    $flag_score = 0; 
    if( dwqa_is_user_flag( $_POST['answer_id'], $current_user->ID ) ){
        //unflag
        $flag[$current_user->ID] = $flag_score = 0;
    } else {
        $flag[$current_user->ID] = $flag_score = 1;

    }
    $flag = serialize($flag);
    update_post_meta( $_POST['answer_id'], '_flag', $flag );
    wp_send_json_success( array(
            'status' => $flag_score
        ) );
}
add_action( 'wp_ajax_dwqa-action-flag-answer', 'dwqa_flag_answer' );

function dwqa_is_user_flag( $post_id, $user_id = null ){
    if( !$user_id ) {
        global $current_user;
        if( $current_user->ID > 0 ){
            $user_id = $current_user->ID;
        } else {
            return false;
        }
    }
    $flag = get_post_meta( $post_id, '_flag', true );
    if( !$flag ) {
        return false;
    }
    $flag = unserialize($flag);
    if( !is_array($flag) ) {
        return false;
    }
    if( ! array_key_exists( $user_id, $flag) ) {
        return false;
    }
    if( $flag[$user_id] == 1 ){
        return true;
    }
    return false;
}

function dwqa_is_answer_flag( $post_id ){
    if( dwqa_is_user_flag( $post_id ) ) {
        return true;
    } else {
        $flag =  get_post_meta( $post_id, '_flag', true );
        if( empty($flag) || !is_array($flag) ) {
            return false;
        } 
        $flag = unserialize( $flag );
        $flag_point = array_sum( $flag );
        if( $flag_point > 5 ) {
            return true;
        }
    }
    return false; //showing
}


function dwqa_is_anonymous( $post_id ) {
    $anonymous = get_post_meta( $post_id, '_dwqa_is_anonymous', true );
    if( $anonymous ) {
        return true;
    }
    return false;
}

function dwqa_init_tinymce_editor( $args = array() ){
    extract( wp_parse_args( $args, array(
            'content'       => '',
            'id'            =>  'dwqa-custom-content-editor',
            'textarea_name' => 'custom-content',
            'rows'          => 5,
            'wpautop'       => false
        ) ) );
    
    wp_editor( $content, $id, array(
        'wpautop'       => $wpautop,
        'media_buttons' => false,
        'textarea_name' => $textarea_name,
        'textarea_rows' => $rows,
        'tinymce' => array(
                'theme_advanced_buttons1' => 'bold,italic,underline,|,' .
                        'bullist,numlist,blockquote,|,' .
                        'link,unlink,|,' .
                        'image,code,|,'.
                        'spellchecker,wp_fullscreen,dwqaCodeEmbed,|,',
                'theme_advanced_buttons2'   =>  '',
                'content_css'   => apply_filters( 'dwqa_editor_style', DWQA_URI . 'assets/css/tinymce.css' ),
        ),
        'quicktags'     => false
    ) );
}

function dwqa_editor_stylesheet( $plugin_style ){
    $stylesheet_directory = get_stylesheet_directory();
    if( file_exists( $stylesheet_directory . '/editor-style.css') ) {
        return get_stylesheet_directory_uri() . '/editor-style.css';
    }
    return $plugin_style;
}
add_filter( 'dwqa_editor_style', 'dwqa_editor_stylesheet' );

function dwqa_ajax_create_editor(){

    if( ! isset($_POST['answer_id']) || ! isset($_POST['question']) ){
        return false;
    }
    extract($_POST);

    ob_start();
    ?>
    <form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-add-answer' ); ?>" method="post">
        <?php wp_nonce_field( '_dwqa_add_new_answer' ); ?>

        <?php if( 'draft' == get_post_status( $answer_id ) && current_user_can( 'manage_options' ) ) { 
        ?>
        <input type="hidden" name="dwqa-action-draft" value="true" >
        <?php } ?> 
        <input type="hidden" name="dwqa-action" value="update-answer" >
        <input type="hidden" name="answer-id" value="<?php echo $answer_id; ?>">
        <input type="hidden" name="question" value="<?php echo $question; ?>">
        <?php 
            $answer = get_post( $answer_id );
            dwqa_init_tinymce_editor( array(
                'content'       => htmlentities($answer->post_content, ENT_COMPAT | ENT_HTML5, get_option( 'blog_charset' ) ), 
                'textarea_name' => 'answer-content',
                'wpautop'       => false
            ) ); 
        ?>
        <p class="dwqa-answer-form-btn">
            <input type="submit" name="submit-answer" class="dwqa-btn dwqa-btn-default" value="<?php _e('Update','dwqa') ?>">
            <a type="button" class="answer-edit-cancel dwqa-btn dwqa-btn-link" ><?php _e('Cancel','dwqa') ?></a>
            <?php if( 'draft' == get_post_status( $answer_id ) && current_user_can( 'manage_options' ) ) { 
            ?>
            <input type="submit" name="submit-answer" class="btn btn-primary btn-small" value="<?php _e('Publish','dwqa') ?>">
            <?php } ?>
        </p>
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
                            <li data-privacy="private" <?php _e('Only Author and Administrator can see','dwqa'); ?>><a href="#"><i class="fa fa-lock"></i> <?php _e('Private','dwqa') ?></a></li>
                        </ul>
                    </div>
                </div>
            </span>
        </div>
    </form>
    <?php
    $editor = ob_get_contents();
    ob_end_clean();
    wp_send_json_success( array(
        'editor'    => $editor
    ) );
}
add_action( 'wp_ajax_dwqa-editor-init', 'dwqa_ajax_create_editor' ); 


function dwqa_comment_form_id_fields_filter($result, $id, $replytoid){
    if( 'dwqa-answer' == get_post_type( $id ) ) {
        $result = str_replace("id='comment_post_ID'", "id='comment_post_".$id."_ID'", $result);
        $result = str_replace("id='comment_parent'", "id='comment_".$id."_parent'", $result);
    }
    return $result;
}
add_filter( 'comment_id_fields', 'dwqa_comment_form_id_fields_filter', 10, 3 );


function dwqa_comment_action_add(){
    global $current_user;
    if( ! dwqa_current_user_can('post_comment') ) {
        wp_send_json_error( array(
            'message'   => __( 'You can\'t post comment','dwqa' )
        ) );
    }
    $args = array(
        'comment_post_ID'   => $_POST['comment_post_ID'],
        'comment_content'   => $_POST['content'],
        'comment_parent'    => $_POST['comment_parent']
    );
    if( is_user_logged_in() ) {
        $args['user_id'] =  $current_user->ID;
    } else {
        if( !isset($_POST['email']) || !$_POST['email'] ) {
            wp_send_json_error( array(
                'message'   => __('Missing email infomation','dwqa')
            ) );
        }
        if( !isset($_POST['name']) || !$_POST['name'] ) {
            wp_send_json_error( array(
                'message'   => __('Missing name infomation','dwqa')
            ) );
        }
        $args['comment_author'] = isset($_POST['name']) ? $_POST['name'] : 'anonymous';
        $args['comment_author_email'] = $_POST['email'];
        $args['comment_author_url'] = isset($_POST['url']) ? $_POST['url'] : '';
        $args['user_id']    = -1;
    }

    $comment_id = wp_insert_comment( $args );   
    do_action( 'dwqa_add_comment', $comment_id );
    global $comment;
    $comment = get_comment( $comment_id );
    ob_start();
    $args = array('walker' => null, 'max_depth' => '', 'style' => 'ol', 'callback' => null, 'end-callback' => null, 'type' => 'all',
        'page' => '', 'per_page' => '', 'avatar_size' => 32, 'reverse_top_level' => null, 'reverse_children' => '');
    dwqa_question_comment_callback( $comment, $args, 0 );
    echo '</li>';
    $comment_html = ob_get_contents();
    ob_end_clean();
    wp_send_json_success( array(
            'html'   => $comment_html
        ) );
}
add_action( 'wp_ajax_dwqa-comment-action-add', 'dwqa_comment_action_add' );
add_action( 'wp_ajax_nopriv_dwqa-comment-action-add', 'dwqa_comment_action_add' );


function dwqa_is_the_best_answer( $answer_id, $question_id = false ) {
    if( ! $question_id ) {
        $question_id = get_the_ID();
    }

    $best_answer = dwqa_get_the_best_answer( $question_id );
    if( $best_answer && $best_answer == $answer_id ) {
        return true;
    }
    return false;
}

function dwqa_get_the_best_answer( $question_id = false ){
    if( ! $question_id ) {
        $question_id = get_the_ID();
    }
    if( 'dwqa-question' != get_post_type( $question_id ) ) {
        return false;
    }

    $user_vote = get_post_meta( $question_id, '_dwqa_best_answer', true );

    if( $user_vote && get_post($user_vote) ) {
        return $user_vote;
    }

    global $wpdb;

    $query = "SELECT `post_id` FROM `{$wpdb->prefix}postmeta` LEFT JOIN `{$wpdb->prefix}posts` 
                ON `{$wpdb->prefix}postmeta`.post_id = `{$wpdb->prefix}posts`.ID   
                WHERE `post_id` 
                    IN ( SELECT  `post_id` FROM `{$wpdb->prefix}postmeta` 
                            WHERE `meta_key` = '_question' AND `meta_value` = {$question_id} ) 
                    AND `meta_key` = '_dwqa_votes'
                    ORDER BY CAST( `meta_value` as DECIMAL ) DESC LIMIT 0,1";

    $answer_id = $wpdb->get_var( $query );

    if( $answer_id && (int) dwqa_vote_count($answer_id) > 2 ) {
        return $answer_id;
    }
    return false;
}   

/**
 * Draft answer
 */

function dwqa_user_get_draft( $question_id = false ) {
    if( ! $question_id ) {
        $question_id = get_the_ID();
    }

    if( ! $question_id || 'dwqa-question' != get_post_type( $question_id ) ) {
        return false;
    }

    if( ! is_user_logged_in() ) {
        return false;
    }
    global $current_user;

    $answers = get_posts(  array(
       'post_type' => 'dwqa-answer',
       'posts_per_page' =>  1,
       'meta_query' => array(
           array(
               'key' => '_question',
               'value' => array( $question_id ),
               'compare' => 'IN',
           )
       ),
       'post_status' => 'draft',
       'author' => $current_user->ID
    ) );

    if( !empty( $answers ) ){
        return $answers[0];
    }
    return false;
}

function dwqa_get_drafts( $question_id = false ) {
    if( ! $question_id ) {
        $question_id = get_the_ID();
    }

    if( ! $question_id || 'dwqa-question' != get_post_type( $question_id ) ) {
        return false;
    }

    if( ! is_user_logged_in() ) {
        return false;
    }
    global $current_user;

    $answers = get_posts(  array(
       'post_type' => 'dwqa-answer',
       'posts_per_page' =>  -1,
       'meta_query' => array(
           array(
               'key' => '_question',
               'value' => array( $question_id ),
               'compare' => 'IN',
           )
       ),
       'post_status' => 'draft'
    ) );

    if( !empty( $answers ) ){
        return $answers;
    }
    return false;
}

function dwqa_user_question_number( $user_id ){
    global $wpdb;
 
    $where = get_posts_by_author_sql('dwqa-question', true, $user_id, true);
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

    return apply_filters('get_usernumposts', $count, $user_id);
}

function dwqa_user_answer_number( $user_id ){
    global $wpdb;
 
    $where = get_posts_by_author_sql('dwqa-answer', true, $user_id, true);
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

    return apply_filters('get_usernumposts', $count, $user_id);
}

function dwqa_get_mail_template( $option, $name = '' ){
    if( ! $name ) {
        return '';
    }
    $template = get_option( $option );
    if( $template ) {
        return $template;
    } else {
        if( file_exists( DWQA_DIR . 'inc/templates/email/'.$name.'.html' ) ) {
            ob_start();
            load_template( DWQA_DIR . 'inc/templates/email/'.$name.'.html', false );
            $template = ob_get_contents();
            ob_end_clean();
            return $template;
        } else {
            return '';
        }
    }
}


function dwqa_auto_convert_urls( $content ){
    global $post;
    if( is_single() && ( 'dwqa-question' == $post->post_type || 'dwqa-answer' == $post->post_type) ) {
        $content = make_clickable( $content );

        $content = preg_replace('/(<a[^>]*)(>)/', '$1 target="_blank" $2', $content);
    }
    return $content;
}
add_filter( 'the_content', 'dwqa_auto_convert_urls' );

function dwqa_sanitizie_comment( $content ){
    $content = str_replace( esc_html('<br>'), '<br>', esc_html( $content ) );
    $content = make_clickable( $content );
    $content = preg_replace('/(<a[^>]*)(>)/', '$1 target="_blank" $2', $content);
    return $content;
}
add_filter( 'get_comment_text', 'dwqa_sanitizie_comment' );

function dwqa_vote_best_answer(){
    global $current_user;
    check_ajax_referer( '_dwqa_vote_best_answer', 'nonce' );
    if( ! isset($_POST['answer']) ) {
        exit(0);
    }
    $q = get_post_meta( $_POST['answer'], '_question', true );
    $question = get_post( $q );
    if( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
        update_post_meta( $q, '_dwqa_best_answer', $_POST['answer'] );
    }
    
}
add_action( 'wp_ajax_dwqa-vote-best-answer', 'dwqa_vote_best_answer' );

function dwqa_unvote_best_answer(){
    global $current_user;
    check_ajax_referer( '_dwqa_vote_best_answer', 'nonce' );
    if( ! isset($_POST['answer']) ) {
        exit(0);
    }
    $q = get_post_meta( $_POST['answer'], '_question', true );
    $question = get_post( $q );
    if( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
        delete_post_meta( $q, '_dwqa_best_answer' );
    }
    
}
add_action( 'wp_ajax_dwqa-unvote-best-answer', 'dwqa_unvote_best_answer' );

function dwqa_vote_best_answer_button(){
    global $current_user;
    $question_id = get_post_meta( get_the_ID(), '_question', true );
    $question = get_post( $question_id );
        $best_answer = dwqa_get_the_best_answer( $question_id );
        $data =  is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ? 'data-answer="'.get_the_ID().'" data-nonce="'.wp_create_nonce( '_dwqa_vote_best_answer' ).'" data-ajax="true"' : 'data-ajax="false"';
    if( get_post_status( get_the_ID() ) != 'publish' ) {
        return false;
    }
    if( $best_answer == get_the_ID() || ( is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ) ) {
    ?>
    <div class="entry-vote-best <?php echo $best_answer == get_the_ID() ? 'active' : ''; ?>" <?php echo $data ?> >
        <a href="javascript:void(0);" title="<?php _e('Choose as best answer','dwqa') ?>">
            <div class="entry-vote-best-bg"></div>
            <i class="icon-thumbs-up"></i>
        </a>
    </div>
    <?php
    }
}


function dwqa_prepare_archive_posts(){
    global $wp_query;
    //Change main query to get dwqa-question posts for what was not a page, single post or archive page of dwqa-question post stype
    
    if( $wp_query->query_vars['post_type'] != 'dwqa-question' ) {
        $query = array(
            'post_type' => 'dwqa-question',
            'posts_per_page' => $wp_query->query_vars['posts_per_page']
        );
        if( is_tax('dwqa-question_category') ) {
            $query['dwqa-question_category'] = get_query_var('dwqa-question_category');
        } 
        if( is_tax('dwqa-question_tag') ) {
            $query['dwqa-question_tag'] = get_query_var('dwqa-question_tag');
        } 
        $paged = get_query_var( 'paged' );
        $query['paged'] = $paged ? $paged : 1; 
        query_posts( $query );
    }
}
add_action( 'dwqa-prepare-archive-posts', 'dwqa_prepare_archive_posts' );

function dwqa_user_post_count( $user_id, $post_type = 'post' ) {
    $posts = get_posts( array(
        'author' => $user_id,
        'post_status'  => 'any',
        'post_type'   => $post_type,
        'posts_per_page' => -1
    ) );
    if( $posts ) {
        return count($posts);
    }
    return 0;
}
function dwqa_user_question_count( $user_id ){
    return dwqa_user_post_count( $user_id, 'dwqa-question' );
}

function dwqa_user_answer_count( $user_id ){
    return dwqa_user_post_count( $user_id, 'dwqa-answer' );
}

function dwqa_user_comment_count( $user_id ) {
    global $wpdb;

    $query = "SELECT count(*) FROM `{$wpdb->prefix}comments` JOIN `{$wpdb->prefix}posts` ON `{$wpdb->prefix}comments`.comment_post_ID = `{$wpdb->prefix}posts`.ID WHERE `{$wpdb->prefix}comments`.user_id = {$user_id} AND  ( `{$wpdb->prefix}posts`.post_type = 'dwqa-question' OR `{$wpdb->prefix}posts`.post_type = 'dwqa-answer' ) AND  `{$wpdb->prefix}comments`.comment_approved = 1";
    $comment_count = $wpdb->get_var( $query );
    return $comment_count;
}

function dwqa_user_most_answer( $number = 10, $from = false, $to = false ){
    global $wpdb;
    
    $query = "SELECT post_author, count(*) as `answer_count` 
                FROM `{$wpdb->prefix}posts` 
                WHERE post_type = 'dwqa-answer' 
                    AND post_status = 'publish'
                    AND post_author <> 0";
    if( $from ) {
        $from = date('Y-m-d h:i:s', $from );
        $query .= " AND `{$wpdb->prefix}posts`.post_date > '{$from}'";
    }
    if( $to ) {
        $to = date('Y-m-d h:i:s', $to );
        $query .= " AND `{$wpdb->prefix}posts`.post_date < '{$to}'";
    }

    $query .=    " GROUP BY post_author 
                ORDER BY `answer_count` DESC LIMIT 0,{$number}";
    $users = $wpdb->get_results( $query, ARRAY_A  );
    return $users;            
}

function dwqa_user_most_answer_this_month( $number = 10 ){
    $from = strtotime('first day of this month' );
    $to = strtotime('last day of this month' );
    return dwqa_user_most_answer( $number, $from, $to);
}

function dwqa_user_most_answer_last_month( $number = 10 ){
    $from = strtotime('first day of last month' );
    $to = strtotime('last day of last month' );
    return dwqa_user_most_answer( $number, $from, $to);
}

function dwqa_get_questions_permalink(){
    if( isset($_GET['params']) ) {
        global $dwqa_options;
        $params = explode( '&', $_GET['params'] );
        $args = array();
        foreach ($params as $p ) {
            $arr = explode('=', $p);
            $args[$arr[0]] = $arr[1];
        }
        if( !empty( $args ) ) {
            $url = get_permalink( $dwqa_options['pages']['archive-question'] );
            $url = $url ? $url : get_post_type_archive_link( 'dwqa-question' );
            

            $question_tag_rewrite = get_option( 'dwqa-question-tag-rewrite', 'question-tag' );
            $question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
            if( isset($args[$question_tag_rewrite]) ) {
                if( isset($args['dwqa-question_tag']) ) {
                    unset($args['dwqa-question_tag']);
                }
            }

            $question_category_rewrite = get_option( 'dwqa-question-category-rewrite', 'question-category' );
            $question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';

            if( isset($args[$question_category_rewrite]) ) {
                if( isset($args['dwqa-question_category']) ) {
                    unset($args['dwqa-question_category']);
                }
                $term = get_term( $args[$question_category_rewrite], 'dwqa-question_category' );
                unset($args[$question_category_rewrite]);
                $url = get_term_link( $term, 'dwqa-question_category' );
            } else {
                if( isset($args[$question_tag_rewrite]) ) {
                    $term = get_term( $args[$question_tag_rewrite], 'dwqa-question_tag' );
                    unset($args[$question_tag_rewrite]);
                    $url = get_term_link( $term, 'dwqa-question_tag' );
                }
            }


            if( $url ) {
                $url = add_query_arg( $args, $url);
                wp_send_json_success( array( 'url' => $url ) );
            } else {
                wp_send_json_error( array(
                    'error' => 'missing_questions_archive_page'
                ) );
            }
        } else {
            wp_send_json_error( array(
                'error' => 'empty'
            ) );
        }
    }
    wp_send_json_error();
}
add_action( 'wp_ajax_dwqa-get-questions-permalink', 'dwqa_get_questions_permalink' );
add_action( 'wp_ajax_nopriv_dwqa-get-questions-permalink', 'dwqa_get_questions_permalink' );

function dwqa_reset_permission_default(){
    global $dwqa_permission;
    if( !isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], '_dwqa_reset_permission' ) ) {
        wp_send_json_error( array(
            'message'   => __('Are you cheating huh?')
        ) );
    }
    if( isset($_POST['type']) ) {
        $old = $dwqa_permission->perms;
        foreach ($dwqa_permission->defaults as $role => $perms) {
            $dwqa_permission->perms[$role][$_POST['type']] = $perms[$_POST['type']];
        }
        $dwqa_permission->reset_caps( $old, $dwqa_permission->perms );
        wp_send_json_success();
    }
    wp_send_json_error();
}
add_action( 'wp_ajax_dwqa-reset-permission-default', 'dwqa_reset_permission_default' );

function dwqa_get_comments(){
    if( isset($_GET['post']) ) {
        $comments = get_comments( array(
            'post_id' => $_GET['post'],
            'status' => 'approve'
        ) );
        
        wp_list_comments( array( 
            'style' => 'ol',
            'callback'  => 'dwqa_question_comment_callback'
        ), $comments ); 
    }
    exit(0);
}
add_action( 'wp_ajax_dwqa-get-comments', 'dwqa_get_comments' );
add_action( 'wp_ajax_nopriv_dwqa-get-comments', 'dwqa_get_comments' );

function dwqa_is_followed( $post_id, $user = false ){
    if( !$user ) {
        $user = wp_get_current_user();
    }

    if( in_array( $user->ID, get_post_meta( $post_id, '_dwqa_followers', false ) ) ) {
        return true;
    }
    return false;
}
function dwqa_follow_question(){
    check_ajax_referer( '_dwqa_follow_question', 'nonce' );
    if( ! isset($_POST['post']) ) {
        wp_send_json_error( array(
            'message'   => __('Invalid Post','dwqa')
        ) );
    }
    $question = get_post( $_POST['post'] );
    if( is_user_logged_in() ) {
        global $current_user;
        if( ! dwqa_is_followed( $question->ID )  ) {
            add_post_meta( $question->ID, '_dwqa_followers', $current_user->ID );
            wp_send_json_success( array(
                'code' => 'followed'
            ) );
        } else {
            delete_post_meta( $question->ID, '_dwqa_followers', $current_user->ID );
            wp_send_json_success( array(
                'code' => 'unfollowed'
            ) );
        }
    } else {
        wp_send_json_error( array(
            'code' => 'not-logged-in'
        ) );
    }

}
add_action( 'wp_ajax_dwqa-follow-question', 'dwqa_follow_question' );

?>