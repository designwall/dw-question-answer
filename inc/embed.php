<?php  

function dwqa_embed_question( $content ){
	$content = preg_replace_callback('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', 'dwqa_make_embed_code', $content);
	return $content;
}	
add_filter( 'the_content', 'dwqa_embed_question' );

function dwqa_make_embed_code( $matches ){
    $link = $matches[0];
    $site_link = get_bloginfo('url');
    if (strpos($link, $site_link) === false) {
        return $matches[0];
    } else {
    	$post_id = url_to_postid( $link );
    	if( 'dwqa-question' == get_post_type( $post_id ) ) {
    		ob_start();
    		global $post;
    		$post = get_post( $post_id );
    		setup_postdata( $post );
			dwqa_load_template( 'embed', 'question' );
			$html = ob_get_contents();
    		ob_end_clean();
    		wp_reset_query();
    		return $html;
    	}
    }
    return $matches[0];
}

?>