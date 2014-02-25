<?php  

function dwqa_embed_question( $content ){
	global $dwqa_start_loop, $post;

	if( 'dwqa-question' ==  $post->post_type ) {
		return $content;
	}
	if( $dwqa_start_loop ) {
		return $content;
	}
	$dwqa_start_loop = true;

	$content = preg_replace_callback('#(?<=[\s>])(\()?([\w]+?://(?:[\w\\x80-\\xff\#$%&~/=?@\[\](+-]|[.,;:](?![\s<]|(\))?([\s]|$))|(?(1)\)(?![\s<.,;:]|$)|\)))+)#is', 'dwqa_make_embed_code', $content);

	$dwqa_start_loop = false;
	return $content;
}	
add_filter( 'the_content', 'dwqa_embed_question', 11 );

function dwqa_make_embed_code( $matches ){
    $link = $matches[0];
    $site_link = get_bloginfo('url');
    if (strpos($link, $site_link) === false) {
        return $matches[0];
    } else {
		global $post;
    	$post_id = url_to_postid( $link );
    	if( 'dwqa-question' == get_post_type( $post_id ) && $post_id != $post->ID ) {
    		$post = get_post( $post_id );
    		setup_postdata( $post );
    		$embed_code = '';
    		ob_start();
			dwqa_load_template( 'embed', 'question' );
    		$embed_code = ob_get_contents();
    		ob_end_clean();
    		wp_reset_postdata();
    		return $embed_code;
    	}
    }
    return $matches[0];
}

?>