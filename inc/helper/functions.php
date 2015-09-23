<?php  
/** 
 * This file was used to include all functions which i can't classify, just use those for support my work
 */
/**
 * Custom Date time format for DW Question Anwer
 */
function dwqa_human_time_diff( $from, $to = false, $format = false ) {
	if ( ! $format ) {
		$format = get_option( 'date_format' );
	}
	if ( ! $to ) {
		$to = current_time( 'timestamp' );
	}

	$diff = (int) abs( $to - $from );
	if ( $diff <= 1 ) {
		$since = '1 second';
	} elseif ( $diff <= 60 ) {
		$since = sprintf( _n( '%s second', '%s seconds', $diff, 'dwqa' ), $diff );
	} elseif ( $diff <= 3600 ) {

		$mins = round( $diff / 60 );

		if ( $mins <= 1 ) {
			$mins = 1;
		}
		/* translators: min=minute */
		$since = sprintf( _n( 'about %s min', '%s mins', $mins, 'dwqa' ), $mins );
	} elseif ( ( $diff <= 86400 ) && ( $diff > 3600 ) ) {
		$hours = round( $diff / 3600 );
		if ( $hours <= 1 ) {
			$hours = 1;
		}
		$since = sprintf( _n( 'about %s hour', '%s hours', $hours, 'dwqa' ), $hours );
	} elseif ( $diff >= 86400 && $diff <= 86400 * 7 ) {
		$days = round( $diff / 86400 );
		if ( $days <= 1 ) {
			$days = 1;
		}
		$since = sprintf( _n( '%s day', '%s days', $days, 'dwqa' ), $days );
	} else {
		return date( $format, $from );
	}
	return sprintf( __( '%1$s ago', 'dwqa' ), $since );
}


add_filter( 'get_the_date', 'dwqa_human_time_diff_for_date', 10, 2 );
function dwqa_human_time_diff_for_date( $the_date, $d ) {
	global $post;
	if ( $post->post_type == 'dwqa-question' || $post->post_type == 'dwqa-answer' ) {
		return dwqa_human_time_diff( strtotime( get_the_time( 'c' ) ), false, $d );
	}
	return $the_date;
}

add_filter( 'get_comment_date', 'dwqa_comment_human_time_diff_for_date', 10, 2 );
function dwqa_comment_human_time_diff_for_date( $the_date, $d ) {
	global $comment;
	$parent_posttype = get_post_type( $comment->comment_post_ID );
	if ( $parent_posttype == 'dwqa-question' || $parent_posttype == 'dwqa-answer' ) {
		return dwqa_human_time_diff( strtotime( $comment->comment_date ), false, $d );
	}
	return $the_date;
}

?>