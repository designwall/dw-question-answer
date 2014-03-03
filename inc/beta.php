<?php  
function dwqa_auto_change_question_status( $answer_id ){
	if( !is_wp_error( $answer_id ) ) {
    	$question_id = get_post_meta( $answer_id, '_question', true );
    	$answer = get_post( $answer_id );
    	if( $question_id && $answer->post_author ) {
    		$question_status = get_post_meta( $question_id, '_dwqa_status', true );
    		if( ! user_can( $answer->post_author, 'edit_posts' ) ) {
    			if( $question_status == 'resolved' ) {
                    update_post_meta( $question_id, '_dwqa_status', 're-open' );
                }
    		} else {
                if( $question_status == 're-open' ) {
                    update_post_meta( $question_id, '_dwqa_status', 'open' );
                }
            }
    	}
	}
}
add_action( 'dwqa_add_answer', 'dwqa_auto_change_question_status' );

//Update question status when have new comment
function dwqa_reopen_question_have_new_comment($comment_ID){
    $comment = get_comment( $comment_ID );
    $comment_post_type = get_post_type( $comment->comment_post_ID );
    $question = false;
    if( 'dwqa-answer' ==  $comment_post_type ) {
        $question = get_post_meta( $comment->comment_post_ID, '_question', true );
    } elseif ( 'dwqa-question' == $comment_post_type) {
        $question = $comment->comment_post_ID;
    }

    if( $question ) {
        $question_status = get_post_meta( $question, '_dwqa_status', true );
        if( ! user_can( $comment->user_id, 'edit_posts' ) ) {
            if( 'resolved' == $question_status ) {
                update_post_meta( $question, '_dwqa_status', 're-open' );
            }
        }
    }
}
add_action( 'wp_insert_comment', 'dwqa_reopen_question_have_new_comment' );

//Auto close question when question was resolved longtime
function dwqa_schedule_events() {
    if ( !wp_next_scheduled( 'dwqa_hourly_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'dwqa_hourly_event');
    }
}
add_action('wp', 'dwqa_schedule_events');

function dwqa_do_this_hourly() {
    $questions = get_posts(  array(
        'numberposts'        =>    -1,
        'meta_query' => array(
               array(
                   'key'        => '_dwqa_status',
                   'value'      => 'closed',
                   'compare'    => '=',
               )
           ),
        'post_type'             => 'dwqa-question',
        'post_status'           => 'publish'
    ) );
    if( count($questions) > 0 ) {
        foreach ( $questions as $q ) {
            $resolved_time = get_post_meta( $q->ID, '_dwqa_resolved_time', true );
            if ( dwqa_is_resolved($q->ID) && ( time() - $resolved_time > (3 * 24 * 60 * 60) ) ) {
                update_post_meta( $q->ID, '_dwqa_status', 'resolved' );
            }
        }
    } 
}
add_action('dwqa_hourly_event', 'dwqa_do_this_hourly');

//Chat 
function dwqa_post_comment_action(){
    if( ! isset($_POST['question_id']) ) {
        wp_send_json_error();
    }
    $channel_id = $_POST['question_id'];
    $respond = wp_remote_post( 'http://ec2-54-224-117-255.compute-1.amazonaws.com:8000/', array(
        'body' => array( 
            'channel_id' => $channel_id, 
            'message' => '{"hello": "world"}' 
        )
    ) );
    print_r($respond);
    exit(0);
}
add_action( 'wp_ajax_nopriv_dwqa-post-comment-action', 'dwqa_post_comment_action' );
add_action( 'wp_ajax_dwqa-post-comment-action', 'dwqa_post_comment_action' );

function dwqa_post_comment_realtime( $comment_id, $comment_html, $clientId ){
    $comment = get_comment( $comment_id );
    $post_parent = get_post( $comment->comment_post_ID );

    if( 'dwqa-question' == $post_parent->post_type ) {
        $question_id = $post_parent->ID;
    } elseif( 'dwqa-answer' == $post_parent->post_type ) {
        $question_id = get_post_meta( $post_parent->ID, '_question', true );
    } else {
        wp_send_json_error( array(
            'message'   => __('This post is not a question','dwqa')
        ) );
    }
    $channel_id = $question_id;
    $respond = wp_remote_post( 'http://ec2-54-224-117-255.compute-1.amazonaws.com:8000/', array(
        'body' => array( 
            'channel_id' => $channel_id, 
            'message' => '{"type":"add_new_comment","new_comment_id": "'.$comment_id.'","clientId":"'.$clientId.'"}' 
        )
    ) );
}
add_action( 'dwqa_add_comment', 'dwqa_post_comment_realtime', 10, 3 );

function dwqa_get_comment_template(){
    if( ! isset($_POST['comment_id']) ) {
        wp_send_json_error( array(
            'message' => __('Have no data of comment id','dwqa')
        ) );
    }
    global $comment;
    $comment = get_comment( $_POST['comment_id'] );
    ob_start();
    $args = array('walker' => null, 'max_depth' => '', 'style' => 'ol', 'callback' => null, 'end-callback' => null, 'type' => 'all',
        'page' => '', 'per_page' => '', 'avatar_size' => 32, 'reverse_top_level' => null, 'reverse_children' => '');
    dwqa_question_comment_callback( $comment, $args, 0 );
    echo '</li>';
    $comment_html = ob_get_contents();
    ob_end_clean();
    wp_send_json_success( array(
        'html' => $comment_html,
        'form_id' => '#comment_form_' . $comment->comment_post_ID
    ) );
}
add_action( 'wp_ajax_dwqa-get-comment-template','dwqa_get_comment_template' );
add_action( 'wp_ajax_nopriv_dwqa-get-comment-template','dwqa_get_comment_template' );

?>