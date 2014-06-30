<?php  

function dwqa_current_user_can( $perm, $post_id = false ){
    global $dwqa_permission, $current_user;
    if( is_user_logged_in() ) {

        if( $post_id && $current_user->ID == get_post_field( 'post_author', $post_id ) ) {
            return true;
        }

        if( current_user_can( 'dwqa_can_' . $perm ) ) {
            return true;
        }
        return false;
    } else {
        $anonymous = $dwqa_permission->perms['anonymous'];
        $type = explode('_', $perm);
        if( isset($anonymous[$type[1]][$type[0]]) && $anonymous[$type[1]][$type[0]] ) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function dwqa_read_permission_apply( $posts, $query ){
    if( isset($query->query['post_type']) && $query->query['post_type'] == 'dwqa-answer' && ! dwqa_current_user_can('read_answer') && is_single() ) {
        return false;
    }
    if( isset($query->query['post_type']) && $query->query['post_type'] == 'dwqa-question' && ! dwqa_current_user_can('read_question') ) {
        return false;
    }
    return $posts;
}
add_filter( 'the_posts', 'dwqa_read_permission_apply', 10, 2 );

function dwqa_read_comment_permission_apply( $comments, $post_id ){
    if( ( 'dwqa-question' == get_post_type($post_id) || 'dwqa-answer' == get_post_type($post_id) ) && ! dwqa_current_user_can('read_comment') ) {
        return array();
    }
    return $comments;
}
add_filter( 'comments_array', 'dwqa_read_comment_permission_apply', 10, 2 );

class DWQA_Permission {
    public $defaults;
    public $perms;
    public $default_cap;
    public $objects;
    
    function __construct() {
        $this->default_cap = array(
            'read'      => 1,
            'post'      => 0,
            'edit'      => 0,
            'delete'    => 0   
        );
        $this->objects = array( 'question', 'answer', 'comment' );
        $this->defaults = array(
            'administrator' => array(
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1 ,
                    'delete'    => 1 
                ),
                'answer'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1,
                    'delete'    => 1   
                ),
                'comment'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1,
                    'delete'    => 1   
                )
            ),
            'editor'        => array(
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1,
                    'delete'    => 1   
                ),
                'answer'        => array(
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1,
                    'delete'    => 1   
                ),
                'comment'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1,
                    'delete'    => 1   
                )
            ),
            'author'        => array(
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'answer'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'comment'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                )
            ),
            'contributor'   => array(
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'answer'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'comment'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                )
            ),
            'subscriber'    => array(
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'answer'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'comment'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                )
            ),
            'anonymous'    => array(
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'answer'        => array( 
                    'read'      => 1,
                    'post'      => 0,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'comment'        => array( 
                    'read'      => 1,
                    'post'      => 0,
                    'edit'      => 0,
                    'delete'    => 0   
                )
            )
        );
        add_action( 'init', array( $this, 'first_update_role_functions' ) );
        add_action( 'init', array( $this, 'prepare_permission' ) );
        add_action( 'update_option_dwqa_permission', array( $this, 'update_caps'), 10, 2 );

        add_filter( 'user_has_cap', array( $this, 'allow_user_view_their_draft_post' ), 10, 4 );
    }

    public function prepare_permission(){
        $this->perms = get_option( 'dwqa_permission' );
        $this->perms = $this->perms ? $this->perms : array();
        $this->perms = wp_parse_args( $this->perms, $this->defaults );
    }


    public function add_caps( $value ){
        // $roles = get_editable_roles();
        $this->prepare_permission();

        foreach ($value as $role_name  => $role_info) {
            if( $role_name == 'anonymous' )
                continue;
            $role = get_role( $role_name );
            if( !$role )
                continue;

            foreach ($this->objects as $post_type) {
                foreach ($this->default_cap as $cap => $default) {
                    if( isset($this->perms[$role_name][$post_type][$cap]) && $this->perms[$role_name][$post_type][$cap] ) {
                        $role->add_cap( 'dwqa_can_' . $cap . '_' . $post_type );
                    } else {
                        $role->remove_cap( 'dwqa_can_' . $cap . '_' . $post_type );
                    }
                }
            }
        }
    }
    public function update_caps( $old_value, $value ){
        //update_option( 'dwqa_permission', $this->perms );
        $this->add_caps( $value );
    }

    public function reset_caps( $old_value, $value ){
        update_option( 'dwqa_permission', $this->perms );
        $this->add_caps( $value );
    }

    public function prepare_permission_caps(){
        $this->add_caps( $this->defaults );
    }

    public function first_update_role_functions(){
        $dwqa_has_roles = get_option( 'dwqa_has_roles' );
        $dwqa_permission = get_option( 'dwqa_permission' );
        $this->perms = get_option( 'dwqa_permission' );
        if( ! $dwqa_has_roles || !is_array( $this->perms ) || empty($this->perms ) ) {
            $this->perms = $this->defaults;
            $this->prepare_permission_caps();
            update_option( 'dwqa_permission', $this->perms );
            update_option( 'dwqa_has_roles', 1 );
        }
    }  

    public function remove_permision_caps(){
        foreach ($this->defaults as $role_name => $perm) {
            if( $role_name == 'anonymous' ) {
                continue;
            }
            $role = get_role( $role_name );
            foreach ($perm['question'] as $key => $value) {
                $role->remove_cap( 'dwqa_can_'.$key.'_question' );
            }
            foreach ($perm['answer'] as $key => $value) {
                $role->remove_cap( 'dwqa_can_'.$key.'_answer' );
            }
            foreach ($perm['comment'] as $key => $value) {
                $role->remove_cap( 'dwqa_can_'.$key.'_comment' );
            }
        }
    }

    function allow_user_view_their_draft_post( $all_caps, $caps, $name, $user ) {
        if( is_user_logged_in() ) {
            global $wp_query, $current_user;
            if( $wp_query->is_single && $wp_query->query_vars['post_type'] == 'dwqa-question' && $name[0] == 'edit_post' ) {
                if( isset($name[2]) ) {
                    $post_id = $name[2];
                    $author = get_post_field( 'post_author', $post_id );
                    if( $author == $current_user->ID ) {
                        foreach ($caps as $cap) {
                            $all_caps[$cap] = true;
                        }
                    }
                }
            }
        }
        return $all_caps;
    }
}

?>