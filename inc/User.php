<?php  

function dwqa_get_following_user( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$followers = get_post_meta( $question_id, '_dwqa_followers' );
	
	if ( empty( $followers ) ) {
		return false;
	}
	
	return $followers;
}
/** 
 * Did user flag this post ?
 */
function dwqa_is_user_flag( $post_id, $user_id = null ) {
	if ( ! $user_id ) {
		global $current_user;
		if ( $current_user->ID > 0 ) {
			$user_id = $current_user->ID;
		} else {
			return false;
		}
	}
	$flag = get_post_meta( $post_id, '_flag', true );
	if ( ! $flag ) {
		return false;
	}
	$flag = unserialize( $flag );
	if ( ! is_array( $flag ) ) {
		return false;
	}
	if ( ! array_key_exists( $user_id, $flag ) ) {
		return false;
	}
	if ( $flag[$user_id] == 1 ) {
		return true;
	}
	return false;
}


function dwqa_user_post_count( $user_id, $post_type = 'post' ) {
	$posts = new WP_Query( array(
		'author' => $user_id,
		'post_status'		=> array( 'publish', 'private' ),
		'post_type'			=> $post_type,
		'fields' => 'ids',
	) );
	return $posts->found_posts;
}

function dwqa_user_question_count( $user_id ) {
	return dwqa_user_post_count( $user_id, 'dwqa-question' );
}

function dwqa_user_answer_count( $user_id ) {
	return dwqa_user_post_count( $user_id, 'dwqa-answer' );
}

function dwqa_user_comment_count( $user_id ) {
	global $wpdb;

	$query = "SELECT `{$wpdb->prefix}comments`.user_id, count(*) as number_comment FROM `{$wpdb->prefix}comments` JOIN `{$wpdb->prefix}posts` ON `{$wpdb->prefix}comments`.comment_post_ID = `{$wpdb->prefix}posts`.ID WHERE  1 = 1 AND  ( `{$wpdb->prefix}posts`.post_type = 'dwqa-question' OR `{$wpdb->prefix}posts`.post_type = 'dwqa-answer' ) AND  `{$wpdb->prefix}comments`.comment_approved = 1 GROUP BY `{$wpdb->prefix}comments`.user_id";

	$results = wp_cache_get( 'dwqa-user-comment-count' );
	if ( false == $results ) {
		$results = $wpdb->get_results( $query, ARRAY_A );
		wp_cache_set( 'dwqa-user-comment-count', $results );
	}

	$users_comment_count = array_filter( $results, create_function( '$a', 'return $a["user_id"] == '.$user_id.';' ) ); 
	if ( ! empty( $users_comment_count ) ) {
		$user_comment_count = array_shift( $users_comment_count );
		return $user_comment_count['number_comment'];
	}
	return false;
}

function dwqa_user_most_answer( $number = 10, $from = false, $to = false ) {
	global $wpdb;
	
	$query = "SELECT post_author, count( * ) as `answer_count` 
				FROM `{$wpdb->prefix}posts` 
				WHERE post_type = 'dwqa-answer' 
					AND post_status = 'publish'
					AND post_author <> 0";
	if ( $from ) {
		$from = date( 'Y-m-d h:i:s', $from );
		$query .= " AND `{$wpdb->prefix}posts`.post_date > '{$from}'";
	}
	if ( $to ) {
		$to = date( 'Y-m-d h:i:s', $to );
		$query .= " AND `{$wpdb->prefix}posts`.post_date < '{$to}'";
	}

	$prefix = '-all';
	if ( $from && $to ) {
		$prefix = '-' . ( $form - $to );
	}

	$query .= " GROUP BY post_author 
				ORDER BY `answer_count` DESC LIMIT 0,{$number}";
	$users = wp_cache_get( 'dwqa-most-answered' . $prefix );
	if ( false == $users ) {
		$users = $wpdb->get_results( $query, ARRAY_A  );
		wp_cache_set( 'dwqa-most-answered', $users );
	}
	return $users;            
}

function dwqa_user_most_answer_this_month( $number = 10 ) {
	$from = strtotime( 'first day of this month' );
	$to = strtotime( 'last day of this month' );
	return dwqa_user_most_answer( $number, $from, $to );
}

function dwqa_user_most_answer_last_month( $number = 10 ) {
	$from = strtotime( 'first day of last month' );
	$to = strtotime( 'last day of last month' );
	return dwqa_user_most_answer( $number, $from, $to );
}

function dwqa_is_followed( $post_id = false, $user_id = false ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! $user_id ) {
		$user = wp_get_current_user();
		$user_id = $user->ID;
	}

	if ( in_array( $user_id, get_post_meta( $post_id, '_dwqa_followers', false ) ) ) {
		return true;
	}
	return false;
}

/**
* Get username
*
* @param string $display_name
* @return string
* @since 1.4.0
*/
function dwqa_the_author( $display_name ) {
	global $post;

	if ( 'dwqa-answer' == $post->post_type || 'dwqa-question' == $post->post_type) {
		if ( dwqa_is_anonymous( $post->ID ) ) {
			$anonymous_name = get_post_meta( $post->ID, '_dwqa_anonymous_name', true );
			$display_name = $anonymous_name ? $anonymous_name : __( 'Anonymous', 'dwqa' );
		}
	}

	return $display_name;
}
add_filter( 'the_author', 'dwqa_the_author' );

/**
* Get user's profile link
*
* @param int $user_id
* @return string
* @since 1.4.0
*/
function dwqa_get_author_link( $user_id = false ) {
	if ( ! $user_id ) {
		return false;
	}

	$user = get_user_by( 'id', $user_id );
	if(!$user){
		return false;
	}

	global $dwqa_general_settings;
	
	$question_link = isset( $dwqa_general_settings['pages']['archive-question'] ) ? get_permalink( $dwqa_general_settings['pages']['archive-question'] ) : false;
	$url = get_the_author_link( $user_id );
	if ( $question_link ) {
		$url = add_query_arg( array( 'user' => urlencode( $user->user_nicename ) ), $question_link );
	}

	return apply_filters( 'dwqa_get_author_link', $url, $user_id, $user );
}


/**
* Get question ids user is subscribing
*
* @param int $user_id
* @return array
* @since 1.4.0
*/
function dwqa_get_user_question_subscribes( $user_id = false, $posts_per_page = 5, $page = 1 ) {
	if ( !$user_id ) {
		return array();
	}

	$args = array(
		'post_type' 				=> 'dwqa-question',
		'posts_per_page'			=> $posts_per_page,
		'paged'						=> $page,
		'fields' 					=> 'ids',
		'update_post_term_cache' 	=> false,
		'update_post_meta_cache' 	=> false,
		'no_found_rows' 			=> true,
		'meta_query'				=> array(
			'key'					=> '_dwqa_followers',
			'value'					=> $user_id,
			'compare'				=> '='
		)
	);

	$question_id = wp_cache_get( '_dwqa_user_'. $user_id .'_question_subscribes' );

	if ( ! $question_id ) {
		$question_id = get_posts( $args );
		wp_cache_set( '_dwqa_user_'. $user_id .'_question_subscribes', $question_id, false, 450 );
	}

	return $question_id;
}

function dwqa_get_user_badge( $user_id = false ) {
	if ( !$user_id ) {
		return;
	}

	$badges = array();
	if ( user_can( $user_id, 'edit_posts' ) ) {
		$badges['staff'] = __( 'Staff', 'dwqa' );
	}

	return apply_filters( 'dwqa_get_user_badge', $badges, $user_id );
}

function dwqa_print_user_badge( $user_id = false, $echo = false ) {
	if ( !$user_id ) {
		return;
	}

	$badges = dwqa_get_user_badge( $user_id );
	$result = '';
	if ( $badges && !empty( $badges ) ) {
		foreach( $badges as $k => $badge ) {
			$k = str_replace( ' ', '-', $k );
			$result .= '<span class="dwqa-label dwqa-'. strtolower( $k ) .'">'.$badge.'</span>';
		}
	}

	if ( $echo ) {
		echo $result;
	}

	return $result;
}

class DWQA_User { 
	public function __construct() {
		// Do something about user roles, permission login, profile setting
		add_action( 'wp_ajax_dwqa-follow-question', array( $this, 'follow_question' ) );
	}

	function follow_question() {
		check_ajax_referer( '_dwqa_follow_question', 'nonce' );
		if ( ! isset( $_POST['post'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Post', 'dwqa' ) ) );
		}
		$question = get_post( intval( $_POST['post'] ) );
		if ( is_user_logged_in() ) {
			global $current_user;
			if ( ! dwqa_is_followed( $question->ID )  ) {
				do_action( 'dwqa_follow_question', $question->ID, $current_user->ID );
				add_post_meta( $question->ID, '_dwqa_followers', $current_user->ID );
				wp_send_json_success( array( 'code' => 'followed', 'text' => 'Unsubscribe' ) );
			} else {
				do_action( 'dwqa_unfollow_question', $question->ID, $current_user->ID );
				delete_post_meta( $question->ID, '_dwqa_followers', $current_user->ID );
				wp_send_json_success( array( 'code' => 'unfollowed', 'text' => 'Subscribe' ) );
			}
		} else {
			wp_send_json_error( array( 'code' => 'not-logged-in' ) );
		}

	}
}
?>