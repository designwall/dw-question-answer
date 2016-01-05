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

/** 
 * Array
 */
function dwqa_array_insert( &$array, $element, $position = null ) {
	if ( is_array( $element ) ) {
		$part = $element;
	} else {
		$part = array( $position => $element );
	}

	$len = count( $array );

	$firsthalf = array_slice( $array, 0, $len / 2 );
	$secondhalf = array_slice( $array, $len / 2 );

	$array = array_merge( $firsthalf, $part, $secondhalf );
	return $array;
}

if ( ! function_exists( 'dw_strip_email_to_display' ) ) { 
	/**
	 * Strip email for display in front end
	 * @param  string  $text name
	 * @param  boolean $echo Display or just return
	 * @return string        New text that was stripped
	 */
	function dw_strip_email_to_display( $text, $echo = false ) {
		preg_match( '/( [^\@]* )\@( .* )/i', $text, $matches );
		if ( ! empty( $matches ) ) {
			$text = $matches[1] . '@...';
		}
		if ( $echo ) {
			echo $text;
		}
		return $text;
	}
}  

// CAPTCHA
function dwqa_valid_captcha( $type ) {
	global $dwqa_general_settings;

	if ( 'question' == $type && ! dwqa_is_captcha_enable_in_submit_question() ) {
		return true;
	}

	if ( 'single-question' == $type && ! dwqa_is_captcha_enable_in_single_question() ) {
		return true;
	}
	
	return apply_filters( 'dwqa_valid_captcha', false );
}

add_filter( 'dwqa_valid_captcha', 'dwqa_recaptcha_check' );
function dwqa_recaptcha_check( $res ) {
	global $dwqa_general_settings;
	$type_selected = isset( $dwqa_general_settings['captcha-type'] ) ? $dwqa_general_settings['captcha-type'] : 'google-recaptcha';

	if ( 'google-recaptcha' !== $type_selected ) {
		return true;
	}

	$private_key = isset( $dwqa_general_settings['captcha-google-private-key'] ) ?  $dwqa_general_settings['captcha-google-private-key'] : '';
	if ( ! isset( $_POST['recaptcha_challenge_field'] ) || ! isset( $_POST['recaptcha_response_field'] ) ) {
		return false;
	}
	$resp = recaptcha_check_answer(
		$private_key,
		( isset( $_SERVER['REMOTE_ADDR'] ) ? esc_url( $_SERVER['REMOTE_ADDR'] ) : '' ),
		sanitize_text_field( $_POST['recaptcha_challenge_field'] ),
		sanitize_text_field( $_POST['recaptcha_response_field'] )
	);
	if ( $resp->is_valid ) {
		return true;
	}
	return false;
}

?>