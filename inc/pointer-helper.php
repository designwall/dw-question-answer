<?php  

add_action( 'admin_enqueue_scripts', 'dwqa_pointer_load', 1000 );
 
function dwqa_pointer_load( $hook_suffix ) {
 
    // Don't run on WP < 3.3
    if ( get_bloginfo( 'version' ) < '3.3' )
        return;
 
    $screen = get_current_screen();
    $screen_id = $screen->id;

    // Get pointers for this screen
    $pointers = apply_filters( 'dwqa_admin_pointers-' . $screen_id, array() );
 
    if ( ! $pointers || ! is_array( $pointers ) )
        return;
 
    // Get dismissed pointers
    $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
    $valid_pointers =array();
 
    // Check pointers and remove dismissed ones.
    foreach ( $pointers as $pointer_id => $pointer ) {
 
        // Sanity check
        if ( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) )
            continue;
        
        $pointer['pointer_id'] = $pointer_id;
 
        // Add the pointer to $valid_pointers array
        $valid_pointers['pointers'][] =  $pointer;
    }
 
    // No valid pointers? Stop here.
    if ( empty( $valid_pointers ) )
        return;
 
    // Add pointers style to queue.
    wp_enqueue_style( 'wp-pointer' );
 
    // Add pointers script to queue. Add custom script.
    wp_enqueue_script( 'dwqa-pointer', DWQA_URI . 'assets/js/admin-pointer-helper.js', array( 'jquery', 'wp-pointer' ) );
 
    // Add pointer options to script.
    wp_localize_script( 'dwqa-pointer', 'dwqaPointer', $valid_pointers );
}

add_filter( 'dwqa_admin_pointers-edit-dwqa-question', 'dwqa_register_pointer_testing' );
function dwqa_register_pointer_testing( $p ) {
    $p['xyz140'] = array(
        'target' => '#adminmenu [href="edit.php?post_type=dwqa-question&page=dwqa-settings"]',
        'options' => array(
            'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
                __( 'Config your support channel' ,'dwqa'),
                __( 'Change comment setting, and create submit question page.','dwqa')
            ),
            'position' => array( 'edge' => 'left', 'align' => 'middle' )
        )
    );
    $p['test'] = array(
        'target' => '#doaction',
        'options' => array(
            'content' => sprintf( '<h3> %s </h3> <p> %s </p> ',
                __( 'Config your support channel' ,'dwqa'),
                __( 'Change comment setting, and create submit question page.','dwqa')
            ),
            'position' => array( 'edge' => 'left', 'align' => 'middle' )
        )
    );
    return $p;
}

?>