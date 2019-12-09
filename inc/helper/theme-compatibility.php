<?php

/** Genesis Framework **/

// not show question in category page when set content archive is excerpt
add_filter( 'genesis_pre_get_option_content_archive', 'dwqa_genesis_intergrate_genesis', 9999, 2 );
function dwqa_genesis_intergrate_genesis( $value, $setting ) {
	if ( is_tax( 'dwqa-question_category' ) || is_tax( 'dwqa-question_tag' ) ) {
		return 'full';
	}

	return $value;
}

// not show question in category page when set Features on front is 0
add_filter( 'genesis_pre_get_option_features_on_front', 'dwqa_genesis_feature_on_first_page', 9999, 2 );
function dwqa_genesis_feature_on_first_page( $value, $setting ) {
	if ( is_tax( 'dwqa-question_category' ) || is_tax( 'dwqa-question_tag' ) ) {
		$dwqa_options = get_option( 'dwqa_options', array() );
		return isset( $dwqa_options['posts-per-page'] ) ? $dwqa_options['posts-per-page'] : 15;
	}

	return $value;
}

/** Except Post **/

/**
 * Show shortcode when page or page template when using the_excerpt()
 *
 * @param string $content
 * @return string
 */
function dwqa_the_excerpt( $content ) {
	global $post;

	$dwqa_options = get_option( 'dwqa_options' );

	if ( 
			isset( $post->ID )
			&& 
			( 
				(int) $post->ID == (int) $dwqa_options['pages']['archive-question'] 
				|| 
				(int) $post->ID == (int) $dwqa_options['pages']['submit-question'] 
			) 
		) {
		$content = apply_filters( 'the_content', $post->post_content );
	}

	if ( is_singular( 'dwqa-question' ) ) {
		$content = apply_filters( 'the_content', $post->post_content );
	}

	return $content;
}
add_filter( 'the_excerpt', 'dwqa_the_excerpt' );