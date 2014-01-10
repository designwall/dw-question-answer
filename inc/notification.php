<?php  
function dwqa_new_question_notify( $question_id, $user_id ){
    $enabled = get_option( 'dwqa_subscrible_enable_new_question_notification', 1);
    if( !$enabled ) {
        return false;
    }
    $question = get_post( $question_id );
    if( ! $question ) {
        return false;
    }

    $subject = get_option( 'dwqa_subscrible_new_question_email_subject' );
    if( ! $subject ) {
        $subject = __('A new question was posted on {site_name}', 'dwqa');
    }
    $subject = str_replace('{site_name}', get_bloginfo( 'name' ), $subject);
    $subject = str_replace( '{question_title}', $question->post_title, $subject);
    $subject = str_replace( '{question_id}', $question->ID, $subject);
    $subject = str_replace( '{username}', get_the_author_meta( 'display_name', $user_id ), $subject);
    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

    $message = dwqa_get_mail_template( 'dwqa_subscrible_new_question_email', 'new-question' );
    if( ! $message ) {
        return false;
    }

    // Replacement
    // receiver
    $admin_email = get_bloginfo( 'admin_email' );
    $admin = get_user_by( 'email', $admin_email );
    $message = str_replace( '{admin}', get_the_author_meta( 'display_name', $admin->id ), $message);
    //sender
    $message = str_replace( '{user_avatar}', get_avatar( $user_id, '60'), $message);
    $message = str_replace( '{user_link}', get_author_posts_url( $user_id ), $message);
    $message = str_replace( '{username}', get_the_author_meta( 'display_name', $user_id ), $message);
    //question
    $message = str_replace( '{question_link}', get_permalink( $question_id ), $message);
    $message = str_replace( '{question_title}', $question->post_title, $message);
    $message = str_replace( '{question_content}', $question->post_content, $message);
    // Site info
    $logo = get_option( 'dwqa_subscrible_email_logo','' );
    $logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
    $message = str_replace( '{site_logo}', $logo, $message );
    $message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
    $message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
    $message = str_replace( '{site_url}', site_url(), $message );


    // start send out email
    wp_mail( $admin_email, $subject, $message, $headers );
}
add_action( 'dwqa_add_question', 'dwqa_new_question_notify', 10, 2 );


function dwqa_new_answer_nofity( $answer_id ){
    $enabled = get_option( 'dwqa_subscrible_enable_new_answer_notification', 1);
    if( !$enabled ) {
        return false;
    }
    $question_id = get_post_meta( $answer_id, '_question', true );
    $question = get_post( $question_id );
    $answer = get_post( $answer_id );
    if( $answer->post_status != 'publish' ) {
        return false;
    }
    //Send email alert for author of question about this answer
    if( dwqa_is_anonymous($question_id) ) {
        $email = get_post_meta( $question_id, '_dwqa_anonymous_email', true );
        if( ! is_email( $email ) ) {
            return false;
        }
    } else {
        // if user is not the author of question/answer, add user to followers list
        if( $question->post_author != $answer->post_author ) {

            if(  ! in_array( $answer->post_author, get_post_meta( $question_id, '_dwqa_followers', false ) ) ) {
                add_post_meta( $question_id, '_dwqa_followers', $answer->post_author );
            }
        }

        $email = get_the_author_meta( 'user_email', $question->post_author );
    }

    $subject = get_option( 'dwqa_subscrible_new_answer_email_subject' );
    if( ! $subject ) {
        $subject = __('A new answer for "{question_title}" was posted on {site_name}', 'dwqa');
    }
    $subject = str_replace('{site_name}', get_bloginfo( 'name' ), $subject);
    $subject = str_replace( '{question_title}', $question->post_title, $subject);
    $subject = str_replace( '{question_id}', $question->ID, $subject);

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    
    $message = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_email', 'new-answer' );
    if( ! $message ) {
        return false;
    }

    //Receiver
    $message = str_replace( '{question_author}', get_the_author_meta( 'display_name', $question->post_author ), $message );
    //Answer
    $answer  = get_post( $answer_id ); 

    if( dwqa_is_anonymous($answer_id) ) {
        $user_id = 0;
        $display_name = __('Anonymous','dwqa');
        $avatar = get_avatar( $user_id, '60');
        $answer_author = __('Anonymous','dwqa');
    } else {
        $user_id = $answer->post_author;
        $display_name = get_the_author_meta( 'display_name', $user_id );
        $avatar = get_avatar( $user_id, '60');
        $answer_author = '<a href="'.get_author_posts_url( $user_id ).'" >'.get_the_author_meta( 'display_name', $user_id ).'</a>';
    }


    $subject = str_replace( '{username}', $display_name, $subject);
    $subject = str_replace( '{answer_author}', $answer_author, $subject);

    $message = str_replace( '{answer_avatar}', $avatar, $message);
    $message = str_replace( '{answer_author}', $answer_author, $message);
    $message = str_replace( '{question_link}', get_permalink( $question->ID ), $message);
    $message = str_replace( '{question_title}', $question->post_title, $message);
    $message = str_replace( '{answer_content}', $answer->post_content, $message);
    // logo replace
    $logo = get_option( 'dwqa_subscrible_email_logo','' );
    $logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
    $message = str_replace( '{site_logo}', $logo, $message);
    $message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message);
    $message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
    $message = str_replace( '{site_url}', site_url(), $message);

    $followers = get_post_meta( $question_id, '_dwqa_followers' );
    $answer_email = get_the_author_meta( 'user_email', $answer->post_author );
    if( ! empty($followers) ) {
        $question_link = get_permalink( $question->ID );
        foreach ( $followers as $follower ) {
            $follow_email = get_the_author_meta( 'user_email', $follower );
            if( $follow_email && $follow_email != $email && $follow_email != $answer_email ) {
                $follow_subject = __('You got new answer for your followed question','dwqa');

                $message_to_follower = ' Hi {follower_name}, A new answer has been posted on your followed question at: {followed_question_link}';
                $message_to_follower = str_replace( '{follower_name}', get_the_author_meta( 'display_name', $follower ), $message_to_follower);
                $message_to_follower = str_replace( '{followed_question_link}', $question_link, $message_to_follower);
                //Send email to follower
                wp_mail( $follow_email, $follow_subject, $message_to_follower, $headers );
            }
        }
    }
    if( $question->post_author != $answer->post_author ) {
        wp_mail( $email, $subject, $message, $headers );
    }
}
add_action( 'dwqa_add_answer', 'dwqa_new_answer_nofity' );
add_action( 'dwqa_update_answer', 'dwqa_new_answer_nofity' );



function dwqa_new_comment_notify( $comment_id, $comment ){
    $parent = get_post_type( $comment->comment_post_ID );
    if ( 1 == $comment->comment_approved && ( 'dwqa-question' == $parent || 'dwqa-answer' == $parent )  ) { 

        if( $parent == 'dwqa-question' ) {
            $enabled = get_option( 'dwqa_subscrible_enable_new_comment_question_notification', 1);        
        } elseif( $parent == 'dwqa-answer' ) {
            $enabled = get_option( 'dwqa_subscrible_enable_new_comment_answer_notification', 1);
        }
    
        if( !$enabled ) {
            return false;
        }

        $post_parent = get_post( $comment->comment_post_ID );

        
        if( dwqa_is_anonymous( $comment->comment_post_ID ) ) {
            $post_parent_email = get_post_meta( $comment->comment_post_ID, '_dwqa_anonymous_email', true );
            if( ! is_email( $post_parent_email ) ) {
                return false;
            }
        } else {
            // if user is not the author of question/answer, add user to followers list
            if( $post_parent->post_author != $comment->user_id ) {

                if(  ! in_array( $comment->user_id, get_post_meta( $post_parent->ID, '_dwqa_followers', false ) ) ) {
                    add_post_meta( $post_parent->ID, '_dwqa_followers', $comment->user_id );
                }
            }
            $post_parent_email = get_the_author_meta( 'user_email', $post_parent->post_author );
        }

        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

        if( $parent == 'dwqa-question' ) {
            $message = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_question_email', 'new-comment-question' );    
            $subject = get_option( 'dwqa_subscrible_new_comment_question_email_subject',__('[{site_name}] You have a new comment for question {question_title} ', 'dwqa')  );
            $message = str_replace( '{question_author}', get_the_author_meta( 'display_name', $post_parent->post_author ), $message);
            $question = $post_parent;
        } else {
            $message = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_answer_email', 'new-comment-answer' );
            $subject = get_option( 'dwqa_subscrible_new_comment_answer_email_subject',__('[{site_name}] You have a new comment for answer', 'dwqa')  );
            $message = str_replace( '{answer_author}', get_the_author_meta( 'display_name', $post_parent->post_author ), $message);
            $question_id = get_post_meta( $post_parent->ID, '_question', true );
            $question = get_post( $question_id );
        }
        $subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject);
        $subject = str_replace( '{question_title}', $question->post_title, $subject);
        $subject = str_replace( '{question_id}', $question->ID, $subject);
        $subject = str_replace( '{username}',get_the_author_meta( 'display_name', $comment->user_id ), $subject);

        if( ! $message ) {
            return false;
        }
        // logo replace
        $logo = get_option( 'dwqa_subscrible_email_logo','' );
        $logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
        $subject = str_replace('{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $subject );
        $message = str_replace( '{site_logo}', $logo, $message);
        $message = str_replace( '{question_link}', get_permalink( $question->ID ), $message);
        $message = str_replace( '{comment_link}', get_permalink( $question->ID ) . '#li-comment-' . $comment_id, $message);
        $message = str_replace( '{question_title}', $question->post_title, $message);
        $message = str_replace( '{comment_author_avatar}', get_avatar( $comment->user_id, '60'), $message);
        $message = str_replace( '{comment_author_link}', get_author_posts_url( $comment->user_id ), $message);
        $message = str_replace('{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $message );
        $message = str_replace( '{comment_content}', $comment->comment_content, $message);
        $message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message);
        $message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
        $message = str_replace( '{site_url}', site_url(), $message);
        
        $followers = get_post_meta( $post_parent->ID, '_dwqa_followers' );
        $comment_email = get_the_author_meta( 'user_email', $comment->user_id );
        if( ! empty($followers) ) {
            $comment_link = get_permalink( $question->ID ) . '#li-comment-' . $comment_id;
            foreach ( $followers as $follower ) {
                $follow_email = get_the_author_meta( 'user_email', $follower );
                if( $follow_email && $follow_email != $post_parent_email && $follow_email != $comment_email ) {
                    $follow_subject = sprintf('%s %s',
                        __('You got new comment for your followed','dwqa'),
                        ($parent == 'dwqa-question' ? __('question','dwqa') :  __('answer','dwqa'))
                    );

                    $message_to_follower = ' Hi {follower_name}, A new comment has been posted on your followed question at: {followed_question_link}';
                    $message_to_follower = str_replace( '{follower_name}', get_the_author_meta( 'display_name', $follower ), $message_to_follower);
                    $message_to_follower = str_replace( '{followed_question_link}', $comment_link, $message_to_follower);

                    wp_mail( $follow_email, $follow_subject, $message_to_follower, $headers );
                }
            }
        }
        if( $post_parent->post_author != $comment->user_id ) {
            wp_mail( $post_parent_email, $subject, $message, $headers );
        }

    }
}
add_action( 'wp_insert_comment', 'dwqa_new_comment_notify',10,2 );



?>