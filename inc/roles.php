<?php  

class DWQA_Permission {
    public $defaults;
    public $perms;
    
    function __construct() {
        $this->defaults = array(
            'administrator' => array(
                'disabled'      => true,
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
                )
            ),
            'editor'        => array(
                'disabled'      => true,
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
                )
            ),
            'author'        => array(
                'disabled'      => true,
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
                )
            ),
            'contributor'   => array(
                'disabled'      => true,
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
                )
            ),
            'subscriber'    => array(
                'disabled'      => true,
                'question'      => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 0,
                    'delete'    => 0   
                ),
                'answer'        => array( 
                    'read'      => 1,
                    'post'      => 1,
                    'edit'      => 1,
                    'delete'    => 1   
                )
            ),
            'anonymous'    => array(
                'disabled'      => true,
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
                )
            )
        );
        add_action( 'init', array( $this, 'prepare_permission' ) );
        add_filter( 'pre_update_option_dwqa_permission', array( $this, 'parse_permission' ), 10, 2 );
        add_action( 'update_option_dwqa_permission', array( $this, 'update_caps'), 10, 2 );
    }
    public function prepare_permission(){
        $this->perms = get_option( 'dwqa_permission' );
        $this->perms = is_array( $this->perms ) ? $this->perms : array();
        $this->perms = $this->parse_args( $this->perms );
    }

    public function parse_permission( $value, $old_value ){
        return $this->parse_args( $value );
    }

    public function parse_args( $perms ){
        foreach ($this->defaults as $key => $perm) {
            if( ! isset($perms[$key]) ) {
                $perms[$key] = $perm;
            } else {
                $perms[$key] = wp_parse_args( $perms[$key], $perm );
                $perms[$key]['question'] = wp_parse_args( $perms[$key]['question'], $perm['question'] );
                $perms[$key]['answer'] = wp_parse_args( $perms[$key]['answer'], $perm['answer'] );
            }
        }
        return $perms;
    }

    public function add_caps( $value ){
        foreach ($value as $role_name => $perm) {
            if( $role_name == 'anonymous' ) {
                continue;
            }
            $role = get_role( $role_name );
            foreach ($perm['question'] as $key => $value) {
                if( isset($value) && $value ) {
                    $role->add_cap( 'dwqa_can_'.$key.'_question' );
                } else {
                    $role->remove_cap( 'dwqa_can_'.$key.'_question' );
                }
            }
            foreach ($perm['answer'] as $key => $value) {
                if( isset($value) && $value ) {
                    $role->add_cap( 'dwqa_can_'.$key.'_answer' );
                } else {
                    $role->remove_cap( 'dwqa_can_'.$key.'_answer' );
                }
            }
        }
    }
    public function update_caps( $old_value, $value ){
        $this->add_caps( $value );
    }

    public function prepare_permission_caps(){
        $this->add_caps( $this->defaults );
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
                $role->remove_cap( 'dwqa_can_'.$key.'answer' );
            }
        }
    }
}

?>