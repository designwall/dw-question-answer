<?php  
/**
 * DW Question Answer Embed code for question
 * @since  1.2.1
 */
class DWQA_Embed {
    private $parent_post;
    private $depth;

    public function __construct(){
        $this->depth = 0;
        add_filter( 'the_content', array($this, 'filter_content'), 11 );

        if( isset($_REQUEST['dwqa-embed']) && $_REQUEST['dwqa-embed'] ) {
            add_filter( 'show_admin_bar', '__return_false' );
        }
    }


    public function filter_content( $content ){
        global $dwqa_start_loop, $post;
        $this->parent_post = $post;
        if( $this->depth > 0 ) {
            return $content;
        }
        $this->depth++;

        $content = preg_replace_callback('#(?<=[\s>])(\()?([\w]+?://(?:[\w\\x80-\\xff\#$%&~/=?@\[\](+-]|[.,;:](?![\s<]|(\))?([\s]|$))|(?(1)\)(?![\s<.,;:]|$)|\)))+)#is', array($this,'make_embed_code'), $content);

        $this->parent_post = false;
        $this->depth = 0;
        return $content;
    } 

    public function make_embed_code( $matches ){
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
                $template = 'question';
                $parent_post_type = get_post_type( $this->parent_post->ID );
                if( 'dwqa-question' == $parent_post_type || 'dwqa-answer' == $parent_post_type ) {
                    $template = 'question-qa';
                }
                ob_start();
                dwqa_load_template( 'embed', $template );
                $embed_code = ob_get_contents();
                ob_end_clean();
                wp_reset_postdata();
                return $embed_code;
            }
        }
        return $matches[0];
    }
}
$GLOBALS['dwqa_embed'] = new DWQA_Embed();

?>