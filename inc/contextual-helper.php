<?php 
/**
 * Plugin Name: Help Tab Test Case
 * Plugin URI:  http://unserkaiser.com
 * Description: Add Help Tab test case
 */
class dwqa_help_tab {
    public $tabs;

    static public function init()
    {   
        $class = __CLASS__ ;
        new $class;
    }

    public function __construct() {
        $post_type = $this->get_current_posttype();

        if( 'dwqa-question' == $post_type || 'dwqa-answer' == $post_type ) {
            add_action( "load-{$GLOBALS['pagenow']}", array( $this, 'add_tabs' ), 20 );
        }
    }

    public function get_current_posttype(){
        global $post, $typenow, $current_screen;
    
        //we have a post so we can just get the post type from that
        if ( $post && $post->post_type )
            return $post->post_type;

        //check the global $typenow - set in admin.php
        elseif( $typenow )
            return $typenow;

        //check the global $current_screen object - set in sceen.php
        elseif( $current_screen && $current_screen->post_type )
            return $current_screen->post_type;

        //lastly check the post_type querystring
        elseif( isset( $_REQUEST['post_type'] ) )
            return sanitize_key( $_REQUEST['post_type'] );
    }

    private function create_tabs(){
        $this->tabs = array(
            // The assoc key represents the ID
            // It is NOT allowed to contain spaces
            'dwqa-overview' => array(
                'title'         => __('Overview','dwqa'),
                'content'       => '<h3>'.__('Why use DW Question & Answer', 'dwqa').'</h3>'.
                '<p>'.__('You need a support channel inside wordpress page?').'</p>'
            ),
            'dwqa-guide-make-ask-form' => array(
                'title'         => __('Create Submit Question Page','dwqa'),
                'content'       => '<h3>'.__('How to create submit question page?', 'dwqa').'</h3>'.
                '<p>'.__('Go to <strong>Dashboard -> Question -> Settings</strong>').'</p>'
            ),
            'dwqa-guide-settings' => array(
                'title'         => __('Available Settings','dwqa'),
                'content'       => '<h3>'.__('Go to config your support channel', 'dwqa').'</h3>'.
                '<p>'.__('Go to ...?').'</p>'
            ),
            'dwqa-designwall' => array(
                'title'         => __('DesignWall Team','dwqa'),
                'content'       => $this->help_tab_designwall()
            ) 
        );
    }

    private function help_tab_designwall(){
        ob_start();
        ?>
        <h3>What is DesignWall?</h3>
        <p>It is the place where you will get Responsive WordPress Themes and Best WordPress Plugins. Our products focus on User Experience, not just beautiful.</p>
        <table>
            <tbody>
                <tr>
                    <td width="33.33333%">
                        <div class="thumbnail">
                            <a href="http://demo.designwall.com/#dw-focus" alt="Wordpress Theme Preview"><img width="100%" src="http://www.designwall.com/wp-content/uploads/dw-focus-screenshot13.jpg" alt="DesignWall Wordpress Theme"></a><br>
                            <a href="http://www.designwall.com/product/dw-focus/" alt="Wordpress Theme Preview"><strong>DW Focus</strong></a>
                        </div>
                    </td>
                    <td width="33.33333%">
                        <div class="thumbnail">
                            <a href="http://demo.designwall.com/#DW-Page-Modern" alt="Wordpress Theme Preview"><img width="100%" src="http://www.designwall.com/wp-content/uploads/dw-page-slide-62.jpg" alt="DesignWall Wordpress Theme"></a><br>
                            <a href="http://www.designwall.com/product/dw-page/" alt="Wordpress Theme Preview"><strong>DW Page</strong></a>
                        </div>
                    </td>
                    <td width="33.33333%">
                        <div class="thumbnail">
                            <a href="http://demo.designwall.com/#wallpress" alt="Wordpress Theme Preview"><img width="100%" src="http://www.designwall.com/wp-content/uploads/dw-wallpress-slide-12.jpg" alt="DesignWall Wordpress Theme"></a><br>
                            <a href="http://www.designwall.com/product/wallpress/" alt="Wordpress Theme Preview"><strong>WallPress</strong> <span class="pull-right">FREE</span></a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function add_tabs()
    {
        $this->create_tabs();

        foreach ( $this->tabs as $id => $data )
        {
            get_current_screen()->add_help_tab( array(
                 'id'       => $id
                ,'title'    => __( $data['title'], 'dwqa' )
                // Use the content only if you want to add something
                // static on every help tab. Example: Another title inside the tab
                ,'content'  => $data['content']
            ) );
        }
        get_current_screen()->set_help_sidebar(
            '<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
            '<p><a href="#" target="_blank">' . __( 'Documentations' ) . '</a></p>' .
            '<p><a href="http://www.designwall.com/question/" target="_blank">' . __( 'Support' ) . '</a></p>'.
            '<p><a href="http://www.designwall.com/product/category/wordpress-theme/" target="_blank">' . __( 'DesignWall Wordpress Themes' ) . '</a></p>'.
            '<p><a href="http://designwall.com" target="_blank">' . __( 'DesignWall Homepage' ) . '</a></p>'
        );
    }

}
// Always add help tabs during "load-{$GLOBALS['pagenow'}".
add_action( 'admin_init', array( 'dwqa_help_tab', 'init' ) );


?>