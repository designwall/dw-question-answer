<?php
function custom_filter_notifications_get_registered_components( $component_names = array() ) {

    // Force $component_names to be an array
    if ( ! is_array( $component_names ) ) {
        $component_names = array();
    }

    array_push( $component_names, 'dwqa' );

    return $component_names;
}
add_filter( 'bp_notifications_get_registered_components', 'custom_filter_notifications_get_registered_components' );

function bp_dwqa_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string',$component_action_name, $component_name ) {

	if ( 'dwqa_new_answer_reply' !== $component_action_name ) {
		return $action;
	}

    // New answer notifications
    if ( 'dwqa_new_answer_reply' === $component_action_name ) {
		$answer = get_post( $item_id );
		if(empty($answer)){
			return $action;
		}
		$author = get_user_by( 'id', $answer->post_author );
		
		$dwqa_notif_title = get_the_title( $answer->post_parent );
		$dwqa_notif_link = wp_nonce_url( add_query_arg( array( 'action' => 'bp_dwqa_mark_read', 'question_id' => $answer->post_parent, 'answer_id' => $answer->ID ), get_permalink( $answer->post_parent ) ), 'bp_dwqa_mark_answer_' . $answer->ID );
		$dwqa_notif_title_attr  = __( 'Question Replies', 'dwqa' );
		
		if ( (int) $total_items > 1 ) {
			$text   = sprintf( __('DWQA: ','dwqa') .__( 'You have %d new replies', 'dwqa' ), (int) $total_items );
			$filter = 'bp_dwqa_multiple_new_subscription_notification';
		} else {
			if ( !empty( $secondary_item_id ) ) {
				$text = sprintf( __('DWQA: ','dwqa') .__( 'You have %d new reply to %2$s from %3$s', 'dwqa' ), (int) $total_items, $dwqa_notif_title, bp_core_get_user_displayname( $secondary_item_id ) );
				
			} else {
				$text = sprintf( __('DWQA: ','dwqa') .__( 'You have %d new reply to %s', 'dwqa' ), (int) $total_items, $dwqa_notif_title );
				
			}
			$filter = 'bp_dwqa_single_new_subscription_notification';
		}

		// WordPress Toolbar
		if ( 'string' === $format ) {
			$return = apply_filters( $filter, '<a href="' . esc_url( $dwqa_notif_link ) . '" title="' . esc_attr( $dwqa_notif_title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $dwqa_notif_link );

		// Deprecated BuddyBar
		} else {
			$return = apply_filters( $filter, array(
				'text' => $text,
				'link' => $dwqa_notif_link
			), $dwqa_notif_link, (int) $total_items, $text, $dwqa_notif_title );
		}

		do_action( 'bp_dwqa_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );
        return $return;
    }
}
add_filter( 'bp_notifications_get_notifications_for_user', 'bp_dwqa_format_buddypress_notifications', 11, 7 );


function bp_dwqa_add_answer_notification( $answer_id, $question_id ) {
    $post = get_post( $question_id );
    $answer = get_post( $answer_id );
    
	if($answer->post_status=='publish' || $answer->post_status=='private'){
		$author_id = $post->post_author;
		bp_notifications_add_notification( array(
			'user_id'           => $author_id,
			'item_id'           => $answer_id,
			'component_name'    => 'dwqa',
			'component_action'  => 'dwqa_new_answer_reply',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}
}
add_action( 'dwqa_add_answer', 'bp_dwqa_add_answer_notification', 99, 2 );

function bp_dwqa_buddypress_mark_notifications() {

	if ( !isset( $_GET['answer_id'] ) || !is_numeric($_GET['answer_id']) ) {
		return;
	}

	if ( !isset( $_GET['action']) || 'bp_dwqa_mark_read' !== $_GET['action'] ) {
		return;
	}

	// Get required data
	$user_id  = bp_loggedin_user_id();
	$answer_id = intval( $_GET['answer_id'] );
	$question_id = intval( $_GET['question_id'] );

	// Check nonce
	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'bp_dwqa_mark_answer_' . $answer_id ) ) {
		dwqa_add_notice( __( "Hello, Are you cheating huh?", 'dwqa' ), 'error' );
	// Check current user's ability to edit the user
	} elseif ( !current_user_can( 'edit_user', $user_id ) ) {
		dwqa_add_notice( __( "You do not have permission to mark notifications for that user.", 'dwqa' ), 'error' );
	}

	if ( dwqa_count_notices( 'error' ) > 0 ) {
		return;
	}else{
		$success = bp_notifications_mark_notifications_by_item_id( $user_id, $answer_id, 'dwqa', 'dwqa_new_answer_reply' );
	}
	
	if($success){
		wp_redirect(get_permalink($question_id));
		exit();
	}
}
add_action( 'init', 'bp_dwqa_buddypress_mark_notifications', 10 );