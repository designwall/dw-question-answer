<?php  

function dwqa_question_print_status( $question_id, $echo = true ){
    $status_meta = get_post_meta( $question_id, '_dwqa_status', true );
    if( 'open' == $status_meta || 're-open' == $status_meta || ! $status_meta ) {
        if( dwqa_is_answered( $question_id ) ) {
            $status = 'answered';
        } elseif( dwqa_is_new( $question_id ) ) {
            $status = 'new';
        } elseif( dwqa_is_overdue( $question_id) ) {
            $status = 'open';
            if( current_user_can( 'edit_posts' ) ) {
                $status .= ' status-overdue';
            }
        } else {
            $status = 'open';
        }
    } elseif ( 'resolved' == $status_meta ) {
        $status = 'resolved';
    } elseif( 'pending' == $status_meta ) {
        $status = 'open';
    } else {
        $status = 'closed';
    }

    if( $echo ) {
        echo '<span class="entry-status status-'.$status.'">'.strtoupper($status).'</span>';    
    }
    return '<span class="entry-status status-'.$status.'">'.strtoupper($status).'</span>';
}

// Detect resolved question
function dwqa_is_resolved( $question_id = false ){
    if( !$question_id ) {
       $question_id = get_the_ID();
    }
    $status = get_post_meta($question_id, '_dwqa_status', true );
    if( $status == 'resolved' ) {
        return true;
    }
    return false;
}

// Detect closed question
function dwqa_is_closed($question_id = false ){
    if( !$question_id ) {
       $question_id = get_the_ID();
    }
    $status = get_post_meta($question_id, '_dwqa_status', true );
    if( $status == 'closed' ) {
        return true;
    }
    return false;
}

// Detect open question
function dwqa_is_open( $question_id = false ){
    if( !$question_id ) {
       $question_id = get_the_ID();
    }
    $status = get_post_meta($question_id, '_dwqa_status', true );
    if( $status == 'open' ) {
        return true;
    }
    return false;
}

// Detect open pending
function dwqa_is_pending( $question_id = false ){
    if( !$question_id ) {
       $question_id = get_the_ID();
    }
    $status = get_post_meta($question_id, '_dwqa_status', true );
    if( $status == 'pending' ) {
        return true;
    }
    return false;
}

// Detect answered question ( have an answer that was posted by supporter and still open )
function dwqa_is_answered( $question_id ){
    if( !$question_id ) {
        $question_id = get_the_ID();
    }
    if( dwqa_is_resolved( $question_id) ) {
        return true;
    }
    $latest_answer = dwqa_get_latest_answer( $question_id );
    if( $latest_answer && dwqa_is_staff_answer($latest_answer) ) {
        return true;
    }
    return false;
}



// Detect new question
function dwqa_is_new( $question_id = null ){
    global  $dwqa_general_settings;
    $hours = isset( $dwqa_general_settings['question-new-time-frame'] ) ? (int) $dwqa_general_settings['question-new-time-frame'] : 4;
    $created_date = get_post_time( 'U', false, $question_id );
    $hours = - $hours;
    if( $created_date > strtotime( $hours.' hours') && dwqa_is_open() ) {
        return true;
    }
    return false;
}


/**
 * The status that admin can see it
 */
// detect overdue question
function dwqa_is_overdue( $question_id ){
    global  $dwqa_general_settings;
    $created_date = get_post_time( 'U', false, $question_id );

    $days = isset( $dwqa_general_settings['question-overdue-time-frame'] ) ? (int) $dwqa_general_settings['question-new-time-frame'] : 2;
    $days = - $days;
    if( $created_date < strtotime( $days.' days') && ! dwqa_is_answered( $question_id )  ) {
        return true;
    }
    return false;
}



// Detect new answer from an other user
function dwqa_have_new_reply( $question_id = false ){
    //if latest answer is not administrator 
    if( ! $question_id ) {
        $question_id = get_the_ID();
    }

    $latest_answer = dwqa_get_latest_answer($question_id);
    if( $latest_answer ) {
        if( dwqa_is_staff_answer( $latest_answer ) ) {
            //answered
            return 'staff-answered';
        } else {
            //Is open
            return strtotime($latest_answer['post_date']);
        }
    }
    return false;
}

// Detect new comment
function dwqa_have_new_comment( $question_id = false ){
    //if latest answer is not administrator 
    if( ! $question_id ) {
        global $post;
        $question_id = $post->ID;
    }

    $lastest_comment = false;

    $comments = get_comments( array(
        'status'    => 'approve',
        'post_id'   => $question_id,
    ) );    
    if( count($comments) > 0 ) {
        $lastest_comment = $comments[0];
    }

    $answers = get_posts(  array(
        'numberposts'        =>    -1,
        'meta_key'            =>    '_question',
        'meta_value'        =>    $question_id,
        'post_type'            =>    'dwqa-answer',
        'post_status'        =>    'publish' 
    ) );
    if( count($answers) > 0 ) {
        //Comment of answers
        foreach ( $answers as $answer ) {
            $comments = get_comments( array( 
                'post_id'   => $answer->ID,
                'status'    => 'approve',
                'number'    => 1
            ) );
            if( empty($comments) ) {
                continue;
            }
            if( $lastest_comment ) {
                if( strtotime($comments[0]->comment_date_gmt) > strtotime($lastest_comment->comment_date_gmt)  ) {
                    $lastest_comment = $comments[0];
                }
            } else {
                $lastest_comment = $comments[0];
            }
        }
    }

    if( $lastest_comment ) {
        if( ! $lastest_comment->user_id ) {
            return strtotime($lastest_comment->comment_date_gmt);
        } else {
            if( user_can( $lastest_comment->user_id, 'edit_posts' ) ) {
                return false;
            } else {
                return strtotime($lastest_comment->comment_date_gmt);
            }
        }
    } else {
        return false;
    }
}

// End statuses of admin

// Get new reply
function dwqa_get_latest_answer( $question_id ){
    $args = array(
        'numberposts'   => 1,
        'post_type' => 'dwqa-answer',
        'meta_query' => array(
            array(
                'key' => '_question',
                'value' => array( $question_id ),
                'compare' => 'IN',
            )
        ),
        'post_status'    => 'publish'
    );
    $recent_answers = wp_get_recent_posts($args);
    if( count($recent_answers) > 0 ) {
        return $recent_answers[0];    
    }
    return false;
}



/**
 * All status of the Answer
 */

// Detect staff-answer
function dwqa_is_staff_answer( $answer ){
    if( ! $answer ) {
        $answer = get_post( get_the_ID() );
        if( 'dwqa-answer' != $answer['post_status'] ) {
            return false;
        }
    }
    if( user_can( $answer['post_author'], 'edit_posts' ) ) {
        return true;
    }
    return false;
}

/**
 * Return a message in context for question status code
 * @param  string $status status code
 * @return string         Status message
 */ 
function dwqa_question_get_status_name( $status, $context = 'dwqa' ){  
    $status = strtolower($status);  
    switch ($status) {
        case 'resolved':
            $message = __( 'Resolved', $context );
            break;
        case 'pending':
            $message = __( 'Pending', $context );
            break;
        case 're-open':
            $message = __( 'Re-Open', $context );
            break;
        case 'closed':
            $message = __( 'Closed', $context );
            break;
        case 'new':
            $message = __( 'New', $context );
            break;
        
        default:
            $message = __( 'Open', $context );
            break;
    }
    return $message;
}   

?>