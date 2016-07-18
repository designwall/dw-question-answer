<?php

/** Advanced Ads **/
add_filter( 'advanced-ads-ad-select-args', 'dwqa_advanced_ads_select_args', 99 );
function dwqa_advanced_ads_select_args( $args ) {
	if ( 'dwqa-answer' == get_post_type() || 'dwqa-question' == get_post_type() ) {
		$args['post']['post_type'] = get_post_type();
	}

	return $args;
}

/** Facebook Comments **/
add_filter( 'get_post_metadata', 'dwqa_disabel_wpdevart_facebook_comment', 10, 3 );
function dwqa_disabel_wpdevart_facebook_comment( $value, $post_id, $meta_key ) {
	$dwqa_options = get_option( 'dwqa_options', array() );
	if ( 
			'_disabel_wpdevart_facebook_comment' == $meta_key
			&& 
			( 
				'dwqa-question' == get_post_type( $post_id ) // is single question
				|| 
				'dwqa-answer' == get_post_type( $post_id ) // is single answer
				||
				(int) $dwqa_options['pages']['submit-question'] == (int) $post_id // is submit question page
				||
				(int) $dwqa_options['pages']['archive-question'] == (int) $post_id // is archive page
			)
		) {
		$value = 'disable';
	}

	return $value;
}