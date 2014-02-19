<?php  

function dwqa_submit_question_ajax(){
    global $dwqa_current_error, $post_submit_filter;
    $valid_captcha = dwqa_valid_captcha('question');
    if( ! check_ajax_referer( 'dwqa-submit-question-nonce-#!', '_wpnonce', false ) ) {
    	wp_send_json_error( array(
            'message'   => __('"Helllo", Are you cheating huh?.','dwqa')
    	) );
    }
    if( isset($_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'dwqa-submit-question-nonce-#!' ) ) {
        if( $valid_captcha ) {
            if( empty($_POST['question-title']) ) {
                wp_send_json_error( array(
                    'message'   => __('You must enter a valid question title','dwqa')
                ) );
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
                        wp_send_json_error( array(
                        	'message'	=> $user->get_error_message()
                        ) );
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
	                        wp_send_json_error( array(
	                        	'message'	=> $user_id->get_error_message()
	                        ) );
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
	                        wp_send_json_error( array(
	                        	'message'	=> $user->get_error_message()
	                        ) );
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
                        wp_send_json_error( array(
                            'message'   => $message
                        ) );
                    }
                }
            }

            $post_status = ( isset($_POST['private-message']) && $_POST['private-message']  ) ? 'private' : 'publish';
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
                wp_send_json_error( array(
                    'message'   => __("You do not have permission to submit question.",'dwqa')
                ) );
            }

            if( ! is_wp_error( $new_question ) ) {
                //exit( wp_safe_redirect( get_permalink( $new_question ) ) );
                $url = get_permalink($new_question);
                wp_send_json_success( array(
                    'message'   => __('Welldone, you question "<a href="'.$url.'">'.get_the_title($new_question).'</a>" succesfully posted to "'.get_bloginfo('title' ).'"','dwqa'),
                    'url'		=> $url
                ) );
            } else {
                wp_send_json_error( array(
                    'message'   => $new_question->get_error_message()
                ) );
            }   
        } else {
            wp_send_json_error( array(
                'message'   => __('YCaptcha is not correct','dwqa')
            ) );
        }
    }else{
        wp_send_json_error( array(
            'message'   => __('"Helllo", Are you cheating huh?.','dwqa')
        ) );
    }
}
add_action( 'wp_ajax_nopriv_dwqa-submit-question-ajax', 'dwqa_submit_question_ajax' );
add_action( 'wp_ajax_dwqa-submit-question-ajax', 'dwqa_submit_question_ajax' );

?>