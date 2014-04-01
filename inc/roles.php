<?php  

function dwqa_current_user_can( $perm ){
    global $dwqa_permission, $current_user;
    if( is_user_logged_in() ) {
        if( current_user_can( 'dwqa_can_' . $perm ) ) {
            return true;
        }
        return false;
    } else {
        $anonymous = $dwqa_permission->perms['anonymous'];
        $type = explode('_', $perm);
        if( $anonymous[$type[1]][$type[0]] ) {
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
    
    function __construct() {
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
                    'post'      => 0,
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
    }
    public function prepare_permission(){
        $this->perms = get_option( 'dwqa_permission' );
        $this->perms = $this->perms ? $this->perms : array();
        foreach ($this->defaults as $role => $role_val ) {
            foreach ($role_val as $type => $perms ) {
                foreach ($perms as $perm => $val) {
                    $this->perms[$role][$type][$perm] = isset( $this->perms[$role][$type][$perm] ) ? $this->perms[$role][$type][$perm] : 0;
                }
            }
        }
    }


    public function add_caps( $value ){
        foreach ($this->defaults as $role_name => $perms) {
            if( $role_name == 'anonymous' ) { continue; }
            
            $role = get_role( $role_name );
            foreach ($perms['question'] as $key => $val) {
                if( isset($value[$role_name]['question'][$key]) && $value[$role_name]['question'][$key]  ) {
                    $role->add_cap( 'dwqa_can_' . $key . '_question' );
                } else {
                    $role->remove_cap( 'dwqa_can_' . $key . '_question' );
                }
            }
            foreach ($perms['answer'] as $key => $val) {
                if( isset($value[$role_name]['answer'][$key]) && $value[$role_name]['answer'][$key]  ) {
                    $role->add_cap( 'dwqa_can_' . $key . '_answer' );
                } else {
                    $role->remove_cap( 'dwqa_can_' . $key . '_answer' );
                }
            }
            foreach ($perms['comment'] as $key => $val) {
                if( isset($value[$role_name]['comment'][$key]) && $value[$role_name]['comment'][$key]  ) {
                    $role->add_cap( 'dwqa_can_' . $key . '_comment' );
                } else {
                    $role->remove_cap( 'dwqa_can_' . $key . '_comment' );
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
}

?>