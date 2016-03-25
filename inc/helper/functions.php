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
	$type_selected = isset( $dwqa_general_settings['captcha-type'] ) ? $dwqa_general_settings['captcha-type'] : 'default';

	$is_old_version = $type_selected == 'google-recaptcha' ? true : false;
	if ( $type_selected == 'default' || $is_old_version ) {
		$number_1 = isset( $_POST['dwqa-captcha-number-1'] ) ? intval( $_POST['dwqa-captcha-number-1'] ) : 0;
		$number_2 = isset( $_POST['dwqa-captcha-number-2'] ) ? intval( $_POST['dwqa-captcha-number-2'] ) : 0;
		$result = isset( $_POST['dwqa-captcha-result'] ) ? intval( $_POST['dwqa-captcha-result'] ) : 0;

		if ( ( $number_1 + $number_2 ) === $result ) {
			return true;
		}

		return false;
	}

	return $res;
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
	} else {
		$lists = implode( ',', $lists );
	}

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

	if ( (int) $page_on_front === (int) $dwqa_general_settings['pages']['archive-question'] ) {
		return true;
	}

	return false;
}

function dwqa_has_question( $args = array() ) {
	global $wp_query;

	return $wp_query->dwqa_questions->have_posts();
}

function dwqa_the_question() {
	global $wp_query;

	return $wp_query->dwqa_questions->the_post();
}

function dwqa_has_question_stickies() {
	global $wp_query;

	return isset( $wp_query->dwqa_question_stickies ) ? $wp_query->dwqa_question_stickies->have_posts() : false;
}

function dwqa_the_sticky() {
	global $wp_query;

	return $wp_query->dwqa_question_stickies->the_post();
}

function dwqa_has_answers() {
	global $wp_query;

	return isset( $wp_query->dwqa_answers ) ? $wp_query->dwqa_answers->have_posts() : false;
}

function dwqa_the_answers() {
	global $wp_query;

	return $wp_query->dwqa_answers->the_post();
}

function dwqa_get_answer_count( $question_id = false ) {

	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	$answer_count = get_post_meta( $question_id, '_dwqa_answers_count', true );

	if ( current_user_can( 'edit_posts' ) ) {
		return $answer_count;
	} else {
		$answer_private = get_post_meta( $question_id, 'dwqa_answers_private_count', true );

		if ( empty( $answer_private ) ) {
			global $wp_query;
			$args = array(
				'post_type' => 'dwqa-answer',
				'post_status' => 'private',
				'meta_query' => array(
					'key' => '_question',
					'value' => array( $question_id ),
					'compare' => 'IN'
				),
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'fields' => 'ids'
			);

			$private_answer = new WP_Query( $args );

			update_post_meta( $question_id, 'dwqa_answers_private_count', count( $private_answer ) );
			$answer_private = count( $private_answer );
		}

		return (int) $answer_count - (int) $answer_private;
	}
}

function dwqa_is_ask_form() {
	global $dwqa_general_settings;
	if ( !isset( $dwqa_general_settings['pages']['submit-question'] ) ) {
		return false;
	}

	return is_page( $dwqa_general_settings['pages']['submit-question'] );
}

function dwqa_is_archive_question() {
	global $dwqa_general_settings;
	if ( !isset( $dwqa_general_settings['pages']['archive-question'] ) ) {
		return false;
	}
	
	return is_page( $dwqa_general_settings['pages']['archive-question'] );
}

function dwqa_question_status( $question = false ) {
	if ( !$question ) {
		$question = get_the_ID();
	}

	return get_post_meta( $question, '_dwqa_status', true );
}

function dwqa_current_filter() {
	return isset( $_GET['filter'] ) && !empty( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
}

function dwqa_get_ask_link() {
	global $dwqa_general_settings;

	return get_permalink( $dwqa_general_settings['pages']['submit-question'] );
}

function dwqa_get_question_link( $post_id ) {
	if ( 'dwqa-answer' == get_post_type( $post_id ) ) {
		$post_id = dwqa_get_question_from_answer_id( $post_id );
	}

	return get_permalink( $post_id );
}
?>