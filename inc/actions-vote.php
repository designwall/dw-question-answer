<?php  

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
            do_action( 'dwqa_vote_'.$vote_for, $post_id, (int) $point );
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

        do_action( 'dwqa_vote_'.$vote_for, $post_id, (int) $point );
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
            return $votes[$user];
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


?>