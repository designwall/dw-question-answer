<?php  

function dwqa_question_print_status( $question_id, $echo = true ) {
	$status_meta = get_post_meta( $question_id, '_dwqa_status', true );
	if ( 'open' == $status_meta || 're-open' == $status_meta || ! $status_meta ) {
		if ( dwqa_is_answered( $question_id ) ) {
			$status = 'answered';
		} elseif ( dwqa_is_new( $question_id ) ) {
			$status = 'new';
		} elseif ( dwqa_is_overdue( $question_id ) ) {
			$status = 'open';
			if ( current_user_can( 'edit_posts' ) ) {
				$status .= ' status-overdue';
			}
		} else {
			$status = 'open';
		}
	} elseif ( 'resolved' == $status_meta ) {
		$status = 'resolved';
	} elseif ( 'pending' == $status_meta ) {
		$status = 'open';
	} else {
		$status = 'closed';
	}

	if ( $echo ) {
		echo '<span  data-toggle="tooltip" data-placement="left" title="'.strtoupper( $status ).'" class="dwqa-status status-'.$status.'">'.strtoupper( $status ).'</span>';    
	}
	return '<span  data-toggle="tooltip" data-placement="left" class="entry-status title="'.strtoupper( $status ).'" status-'.$status.'">'.strtoupper( $status ).'</span>';
}

// Detect resolved question
function dwqa_is_resolved( $question_id = false ) {
	if ( ! $question_id ) {
	   $question_id = get_the_ID();
	}
	$status = get_post_meta( $question_id, '_dwqa_status', true );
	if ( $status == 'resolved' ) {
		return true;
	}
	return false;
}

// Detect closed question
function dwqa_is_closed( $question_id = false ) {
	if ( ! $question_id ) {
	   $question_id = get_the_ID();
	}
	$status = get_post_meta( $question_id, '_dwqa_status', true );
	if ( $status == 'closed' ) {
		return true;
	}
	return false;
}

// Detect open question
function dwqa_is_open( $question_id = false ) {
	if ( ! $question_id ) {
	   $question_id = get_the_ID();
	}
	$status = get_post_meta( $question_id, '_dwqa_status', true );
	if ( $status == 'open' ) {
		return true;
	}
	return false;
}

// Detect open pending
function dwqa_is_pending( $question_id = false ) {
	if ( ! $question_id ) {
	   $question_id = get_the_ID();
	}
	$status = get_post_meta( $question_id, '_dwqa_status', true );
	if ( $status == 'pending' ) {
		return true;
	}
	return false;
}

// Detect answered question ( have an answer that was posted by supporter and still open )
function dwqa_is_answered( $question_id ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	if ( dwqa_is_resolved( $question_id ) ) {
		return true;
	}
	$latest_answer = dwqa_get_latest_answer( $question_id );
	if ( $latest_answer && dwqa_is_staff_answer( $latest_answer ) ) {
		return true;
	}
	return false;
}



// Detect new question
function dwqa_is_new( $question_id = null ) {
	global  $dwqa_general_settings;
	$hours = isset( $dwqa_general_settings['question-new-time-frame'] ) ? (int) $dwqa_general_settings['question-new-time-frame'] : 4;
	$created_date = get_post_time( 'U', false, $question_id );
	$hours = - $hours;
	if ( $created_date > strtotime( $hours.' hours' ) && dwqa_is_open() ) {
		return true;
	}
	return false;
}


/**
 * The status that admin can see it
 */
// detect overdue question
function dwqa_is_overdue( $question_id ) {
	global  $dwqa_general_settings;
	$created_date = get_post_time( 'U', false, $question_id );

	$days = isset( $dwqa_general_settings['question-overdue-time-frame'] ) ? (int) $dwqa_general_settings['question-new-time-frame'] : 2;
	$days = - $days;
	if ( $created_date < strtotime( $days.' days' ) && ! dwqa_is_answered( $question_id )  ) {
		return true;
	}
	return false;
}



// Detect new answer from an other user
function dwqa_have_new_reply( $question_id = false ) {
	//if latest answer is not administrator 
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	$latest_answer = dwqa_get_latest_answer( $question_id );
	if ( $latest_answer ) {
		if ( dwqa_is_staff_answer( $latest_answer ) ) {
			//answered
			return 'staff-answered';
		} else {
			//Is open
			return strtotime( $latest_answer->post_date );
		}
	}
	return false;
}

// Detect new comment
function dwqa_have_new_comment( $question_id = false ) {
	//if latest answer is not administrator 
	if ( ! $question_id ) {
		global $post;
		$question_id = $post->ID;
	}

	$lastest_comment = false;

	$comments = get_comments( array(
		'status'    => 'approve',
		'post_id'   => $question_id,
	) );   

	if ( ! empty( $comments ) ) {
		$lastest_comment = $comments[0];
	}

	$answers = wp_cache_get( 'dwqa-answers-for-'.$question_id, 'dwqa' );
	if ( false == $answers ) {
		global $wpdb;
		$query = "SELECT `{$wpdb->posts}`.ID FROM `{$wpdb->posts}` JOIN `{$wpdb->postmeta}` ON `{$wpdb->posts}`.ID = `{$wpdb->postmeta}`.post_id  WHERE 1=1 AND `{$wpdb->postmeta}`.meta_key = '_question' AND `{$wpdb->postmeta}`.meta_value = {$question_id} AND `{$wpdb->posts}`.post_status = 'publish' AND `{$wpdb->posts}`.post_type = 'dwqa-answer'";

		$answers = $wpdb->get_results( $query );

		wp_cache_set( 'dwqa-answers-for'.$question_id, $answers, 'dwqa', 21600 );
	}

	if ( ! empty( $answers ) ) {
		//Comment of answers
		foreach ( $answers as $answer ) {
			$comments = get_comments( array( 
				'post_id'   => $answer->ID,
				'status'    => 'approve',
				'number'    => 1,
			) );
			if ( empty( $comments ) ) {
				continue;
			}
			if ( $lastest_comment ) {
				if ( strtotime( $comments[0]->comment_date_gmt ) > strtotime( $lastest_comment->comment_date_gmt )  ) {
					$lastest_comment = $comments[0];
				}
			} else {
				$lastest_comment = $comments[0];
			}
		}
	}

	if ( $lastest_comment ) {
		if ( ! $lastest_comment->user_id ) {
			return strtotime( $lastest_comment->comment_date_gmt );
		} else {
			if ( user_can( $lastest_comment->user_id, 'edit_posts' ) ) {
				return false;
			} else {
				return strtotime( $lastest_comment->comment_date_gmt );
			}
		}
	} else {
		return false;
	}
}

// End statuses of admin

// Get new reply
function dwqa_get_latest_answer( $question_id ) {
	$args = array(
		'post_type' => 'dwqa-answer',
		'meta_query' => array(
			array(
				'key' => '_question',
				'value' => array( $question_id ),
				'compare' => 'IN',
			),
		),
		'post_status'    => 'publish,private',
	);
	$recent_answers = wp_get_recent_posts( $args, 'OBJECT' );
	if ( count( $recent_answers ) > 0 ) {
		return $recent_answers[0];    
	}
	return false;
}

/**
 * All status of the Answer
 */

// Detect staff-answer
function dwqa_is_staff_answer( $answer ) {
	if ( ! $answer ) {
		$answer = get_post( get_the_ID() );
		if ( 'dwqa-answer' != $answer->post_status ) {
			return false;
		}
	}
	if ( user_can( $answer->post_author, 'edit_posts' ) ) {
		return true;
	}
	return false;
}

/**
 * Return a message in context for question status code
 * @param  string $status status code
 * @return string         Status message
 */ 
function dwqa_question_get_status_name( $status ) {  
	$status = strtolower( $status );  
	switch ( $status ) {
		case 'resolved':
			$message = __( 'Resolved', 'dwqa' );
			break;
		case 'pending':
			$message = __( 'Pending', 'dwqa' );
			break;
		case 're-open':
			$message = __( 'Re-Open', 'dwqa' );
			break;
		case 'closed':
			$message = __( 'Closed', 'dwqa' );
			break;
		case 'new':
			$message = __( 'New', 'dwqa' );
			break;
		
		default:
			$message = __( 'Open', 'dwqa' );
			break;
	}
	return $message;
}   


function dwqa_update_privacy() {
	if ( ! isset( $_POST['nonce'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
	}
	check_ajax_referer( '_dwqa_update_privacy_nonce', 'nonce' );

	if ( ! isset( $_POST['post'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing post ID', 'dwqa' ) ) );
	}

	global $current_user;
	$post_author = get_post_field( 'post_author', esc_html( $_POST['post'] ) );
	if ( dwqa_current_user_can( 'edit_question' ) || $current_user->ID == $post_author ) {
		$status = 'publish';
		if ( isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'draft', 'publish', 'pending', 'future', 'private' ) ) ) {
			$update = wp_update_post( array(
				'ID'    => intval( $_POST['post'] ),
				'post_status'   => esc_html( $_POST['status'] ),
			) );
			if ( $update ) {
				wp_send_json_success( array( 'ID' => $update ) );
			} else {
				wp_send_json_error(  array(
					'message'   => __( 'Post does not exist','dwqa' )
				) );
			}
		} else {
			wp_send_json_error( array(
				'message'   => __( 'Invalid post status','dwqa' )
			) );
		}
	} else {
		wp_send_json_error( array(
			'message'   => __( 'You do not have permission to edit question', 'dwqa' )
		) );
	}

	
}
add_action( 'wp_ajax_dwqa-update-privacy', 'dwqa_update_privacy' );

?>
