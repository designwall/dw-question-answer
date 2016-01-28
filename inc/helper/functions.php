<?php  
/** 
 * This file was used to include all functions which i can't classify, just use those for support my work
 */

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

/**
* Get tags list of question
*
* @param int $quetion id of question
* @param bool $echo
* @return string
* @since 1.4.0
*/
function dwqa_get_tag_list( $question = false, $echo = false ) {
	if ( !$question ) {
		$question = get_the_ID();
	}

	$terms = wp_get_post_terms( $question, 'dwqa-question_tag' );
	$lists = array();
	if ( $terms ) {
		foreach( $terms as $term ) {
			$lists[] = $term->name;
		}
	}

	if ( empty( $lists ) ) {
		$lists = '';
	}

	$lists = implode( ',', $lists );

	if ( $echo ) {
		echo $lists;
	}

	return $lists;
}


function dwqa_is_front_page() {
	global $dwqa_general_settings;

	if ( !$dwqa_general_settings ) {
		$dwqa_general_settings = get_option( 'dwqa_options' );
	}

	if ( !isset( $dwqa_general_settings['pages']['archive-question'] ) ) {
		return false;
	}

	$page_on_front = get_option( 'page_on_front' );

	if ( $page_on_front === $dwqa_general_settings['pages']['archive-question'] ) {
		return true;
	}

	return false;
}

function dwqa_has_question() {
	global $wp_query;

	return $wp_query->dwqa_questions->have_posts();
}

function dwqa_the_question() {
	global $wp_query;

	return $wp_query->dwqa_questions->the_post();
}

function dwqa_has_answers() {
	global $wp_query;

	return $wp_query->dwqa_answers->have_posts();
}

function dwqa_the_answers() {
	global $wp_query;

	return $wp_query->dwqa_answers->the_post();
}
?>