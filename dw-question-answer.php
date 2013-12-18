<?php  
/**
 *  Plugin Name: DW Question Answer
 *  Description: A WordPress plugin was make by DesignWall.com to build an Question Answer system for support, asking and comunitcate with your customer 
 *  Author: DesignWall
 *  Author URI: http://www.designwall.com
 *  Version: 1.0.3
 *  Text Domain: dwqa
 */
global $script_version;
$script_version = 1387162992;

// Define constant for plugin info 
if( !defined( 'DWQA_DIR' ) ) {
    define( 'DWQA_DIR', plugin_dir_path( __FILE__ ) );
}

if( !defined( 'DWQA_URI' ) ) {
    define( 'DWQA_URI', plugin_dir_url( __FILE__ ) );
}

require_once DWQA_DIR  . 'inc/template-functions.php';
require_once DWQA_DIR  . 'inc/settings.php';
require_once DWQA_DIR  . 'inc/actions.php';
require_once DWQA_DIR  . 'inc/filter.php';
require_once DWQA_DIR  . 'inc/metaboxes.php';
include_once DWQA_DIR  . 'inc/notification.php';
require_once DWQA_DIR . 'inc/class-answers-list-table.php';
require_once DWQA_DIR . 'inc/class-walker-category.php';
require_once DWQA_DIR . 'inc/class-walker-dwqa-tag.php';
require_once DWQA_DIR . 'inc/class-walker-tag-dropdown.php';
include_once DWQA_DIR  . 'inc/contextual-helper.php'; 
include_once DWQA_DIR  . 'inc/pointer-helper.php'; 
include_once DWQA_DIR  . 'inc/beta.php'; 
include_once DWQA_DIR  . 'inc/shortcodes.php';
include_once DWQA_DIR  . 'inc/status.php';
include_once DWQA_DIR  . 'inc/roles.php';
global $dwqa_permission;
$dwqa_permission = new DWQA_Permission();

function dwqa_deactivate_hook(){
    global $dwqa_permission;
    wp_clear_scheduled_hook( 'dwqa_hourly_event' );
    $dwqa_permission->remove_permision_caps();
    delete_option( 'dwqa_options' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'dwqa_deactivate_hook' );
// Update rewrite url when active plugin
function dwqa_activate() {
    global $dwqa_permission;
    $dwqa_permission->prepare_permission_caps();
    flush_rewrite_rules();

    //Auto create question page
    $options = get_option( 'dwqa_options' );

    $args = array(
        'post_title' => __('DWQA Questions','dwqa'),
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    );
    $question_page = get_page_by_title( $args['post_title'] );
    if( ! $question_page && ( !isset($options['pages']['archive-question']) || ! $options['pages']['archive-question'] ) ) {
        $new_page = wp_insert_post( $args );
        $options['pages']['archive-question'] = $new_page;
    } else {
        $options['pages']['archive-question'] = $question_page->ID;
    }
    $args = array(
        'post_title' => __('DWQA Ask Question','dwqa'),
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 1
    );
    $ask_page = get_page_by_title( $args['post_title'] );
    if( ! $ask_page && ( !isset($options['pages']['submit-question']) || ! $options['pages']['archive-question'] ) ) {
        $new_page = wp_insert_post( $args );
        $options['pages']['submit-question'] = $new_page;
    } else {
        $options['pages']['submit-question'] = $ask_page->ID;
    }
    update_option( 'dwqa_options', $options );
}
register_activation_hook( __FILE__, 'dwqa_activate' );

function dwqa_flush_rewrite(){
    flush_rewrite_rules();
}
add_action('switch_theme', 'dwqa_flush_rewrite');

/*** PLUGIN INIT */
function dwqa_plugin_init(){
    global  $dwqa_general_settings;
    load_plugin_textdomain( 'dwqa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    $dwqa_general_settings = get_option( 'dwqa_options' );
    $flag = false;

    $question_rewrite = get_option( 'dwqa-question-rewrite', 'question' );
    $question_rewrite = $question_rewrite ? $question_rewrite : 'question';
    if( isset($dwqa_general_settings['question-rewrite']) && $dwqa_general_settings['question-rewrite'] && $dwqa_general_settings['question-rewrite'] != $question_rewrite ) {
        $question_rewrite = $dwqa_general_settings['question-rewrite'];
        update_option( 'dwqa-question-rewrite', $question_rewrite );
        $flag = true;
    }

    $question_category_rewrite = get_option( 'dwqa-question-category-rewrite', 'question-category' );
    $question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
    if( isset($dwqa_general_settings['question-category-rewrite']) && $dwqa_general_settings['question-category-rewrite'] && $dwqa_general_settings['question-category-rewrite'] != $question_category_rewrite ) {
        $question_category_rewrite = $dwqa_general_settings['question-category-rewrite'];
        update_option( 'dwqa-question-category-rewrite', $question_category_rewrite );
        $flag = true;
    }

    $question_tag_rewrite = get_option( 'dwqa-question-tag-rewrite', 'question-tag' );
    $question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
    if( isset($dwqa_general_settings['question-tag-rewrite']) && $dwqa_general_settings['question-tag-rewrite'] && $dwqa_general_settings['question-tag-rewrite'] != $question_tag_rewrite ) {
        $question_tag_rewrite = $dwqa_general_settings['question-tag-rewrite'];
        update_option( 'dwqa-question-tag-rewrite', $question_tag_rewrite );
        $flag = true;
    }

    /* Question Posttype Registration */
    $question_labels = array(
        'name' => _x('Question', 'post type general name'),
        'singular_name' => _x('Question', 'post type singular name'),
        'add_new' => _x('Add New', 'theme'),
        'add_new_item' => __('Add New Question'),
        'edit_item' => __('Edit Question'),
        'new_item' => __('New Question'),
        'all_items' => __('All Questions'),
        'view_item' => __('View Question'),
        'search_items' => __('Search Question'),
        'not_found' =>  __('No questions found'),
        'not_found_in_trash' => __('No questions found in Trash'), 
        'parent_item_colon' => '',
        'menu_name' => __('DW Q&A')
    );
    $question_args = array(
        'labels' => $question_labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true, 
        'show_in_menu' => true, 
        'query_var' => true,
        'rewrite' => array( 
            'slug' => $question_rewrite,
            'with_front'    => false
        ),
        'capability_type' => 'post',
        'has_archive' => true, 
        'hierarchical' => true,
        'menu_icon' =>  '',
        'supports' => array( 'title', 'editor', 'comments', 'author', 'page-attributes' )
    ); 
    register_post_type( 'dwqa-question', $question_args );
    /* Question Posttype Registration */
    $answer_labels = array(
        'name' => _x('Answer', 'post type general name'),
        'singular_name' => _x('Answer', 'post type singular name'),
        'add_new' => _x('Add New', 'theme'),
        'add_new_item' => __('Add new answer'),
        'edit_item' => __('Edit answer'),
        'new_item' => __('New Answer'),
        'all_items' => __('All Answers'),
        'view_item' => __('View Answer'),
        'search_items' => __('Search Answer'),
        'not_found' =>  __('No Answers found'),
        'not_found_in_trash' => __('No Answers found in Trash'), 
        'parent_item_colon' => '',
        'menu_name' => __('Answer')
    );
    $answer = array(
        'labels' => $answer_labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true, 
        'show_in_menu' => 'edit.php?post_type=dwqa-question', 
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => false, 
        'hierarchical' => true,
        'menu_icon' =>  '',
        'supports' => array( 'title', 'editor', 'comments', 'custom-fields', 'author', 'page-attributes' )
    ); 
    register_post_type( 'dwqa-answer', $answer );


    // Question Taxonomies ( Tag, Category register for Question Posttype)
    // Question Categories
    $question_category_labels = array(
        'name' => _x( 'Categories', 'taxonomy general name' ),
        'singular_name' => _x( 'Category', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Categories' ),
        'all_items' => __( 'All Categories' ),
        'parent_item' => __( 'Parent Category' ),
        'parent_item_colon' => __( 'Parent Category:' ),
        'edit_item' => __( 'Edit Category' ), 
        'update_item' => __( 'Update Category' ),
        'add_new_item' => __( 'Add New Category' ),
        'new_item_name' => __( 'New Category Name' ),
        'menu_name' => __( 'Question Categories' ),
    );  


    register_taxonomy( 'dwqa-question_category', array( 'dwqa-question' ),
        array(
            'hierarchical'  => true,
            'labels'        => $question_category_labels,
            'show_ui'       => true,
            'query_var'     => true,
            'rewrite'       => array( 
                'slug'          => $question_category_rewrite,
                'with_front'    => false
            )
        ) );

    // Question Tags
    $question_tag_labels = array(
        'name' => _x( 'Tags', 'taxonomy general name' ),
        'singular_name' => _x( 'Tag', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Tags' ),
        'popular_items' => __( 'Popular Tags' ),
        'all_items' => __( 'All Tags' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Tag' ), 
        'update_item' => __( 'Update Tag' ),
        'add_new_item' => __( 'Add New Tag' ),
        'new_item_name' => __( 'New Tag Name' ),
        'separate_items_with_commas' => __( 'Separate tags with commas' ),
        'add_or_remove_items' => __( 'Add or remove tags' ),
        'choose_from_most_used' => __( 'Choose from the most used tags' ),
        'menu_name' => __( 'Question Tags' ),
    ); 

    register_taxonomy('dwqa-question_tag', array('dwqa-question'),
        array(
            'hierarchical'  => false,
            'labels'        => $question_tag_labels,
            'show_ui'       => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'     => true,
            'rewrite'       => array( 
                'slug' => $question_tag_rewrite,
                'with_front'    => false
            )
        ) );

    // Create default category for dwqa question type when dwqa plugin is actived 
    $cats = get_categories ( array(
        'type'                     => 'dwqa-question',
        'hide_empty'               => 0,
        'taxonomy'                 => 'dwqa-question_category'
    ) );

    if( empty($cats) ) {
        wp_insert_term( 'Questions', 'dwqa-question_category' );
    }
    if( $flag == true ){
        flush_rewrite_rules();
    }
}
add_action( 'init', 'dwqa_plugin_init' );

/**
 * Enqueue all scripts for plugins on front-end
 * @return void
 */     
function dwqa_enqueue_scripts(){
    global $dwqa_options, $script_version;
    wp_enqueue_script( 'jquery' );
    $version = $script_version;
    $dwqa = array(
        'code_icon'    => DWQA_URI . 'assets/img/icon-code.png',
        'ajax_url'      => admin_url( 'admin-ajax.php' ),
        'text_next'     => __('Next','dwqa'),
        'text_prev'     => __('Prev','dwqa'),
        'questions_archive_link'    => get_post_type_archive_link( 'dwqa-question' ),
        'error_missing_question_content'    =>  __( 'Please enter your question', 'dwqa' ),
        'error_question_length' => __('Your question must be at least 2 characters in length', 'dwqa' ),
        'error_valid_email'    =>  __( 'Enter a valid email address', 'dwqa' ),
        'error_valid_user'    =>  __( 'Enter a question title', 'dwqa' ),
        'error_missing_answer_content'  => __('Please enter your answer','dwqa'),
        'error_missing_comment_content' =>  __('Please enter your comment content','dwqa'),
        'error_not_enought_length'      => __('Comment must have more than 2 characters','dwqa'),
        'comment_edit_submit_button'    =>  __( 'Update', 'dwqa' ),
        'comment_edit_link'    =>  __( 'Edit', 'dwqa' ),
        'comment_edit_cancel_link'    =>  __( 'Cancel', 'dwqa' ),
        'comment_delete_confirm'        => __('Do you want to delete this comment?', 'dwqa' ),
        'answer_delete_confirm'     =>  __('Do you want to delete this answer?', 'dwqa' ),
        'flag'      => array(
            'label'         =>  __('Flag','dwqa'),
            'label_revert'  =>  __('Unflag','dwqa'),
            'text'          =>  __('This answer will be marked as spam and hidden. Do you want to flag it?', 'dwqa' ),
            'revert'        =>  __('This answer was flagged as spam. Do you want to show it','dwqa'),
            'flag_alert'         => __('This answer was flagged as spam','dwqa'),
            'flagged_hide'  =>  __('hide','dwqa'),
            'flagged_show'  =>  __('show','dwqa')
        )
          
    );

    // Enqueue style
    wp_enqueue_style( 'dwqa-style', DWQA_URI . 'assets/css/style.css', array(), $version );
    // Enqueue for single question page
    if( is_single() && 'dwqa-question' == get_post_type() ) {
        // js
        wp_enqueue_script( 'jquery-color' );
        wp_enqueue_script( 'dwqa-single-question', DWQA_URI . 'assets/js/dwqa-single-question.js', array('jquery'), $version, true );
        wp_localize_script( 'dwqa-single-question', 'dwqa', $dwqa );


    }


    if( (is_archive() && 'dwqa-question' == get_post_type()) || ( isset( $dwqa_options['pages']['archive-question'] ) && is_page( $dwqa_options['pages']['archive-question'] ) ) ) {
        wp_enqueue_script( 'dwqa-questions-list', DWQA_URI . 'assets/js/dwqa-questions-list.js', array( 'jquery' ), $version, true );
        wp_localize_script( 'dwqa-questions-list', 'dwqa', $dwqa );
    }

    if( isset($dwqa_options['pages']['submit-question']) 
        && is_page( $dwqa_options['pages']['submit-question'] ) ) {
        wp_enqueue_script( 'dwqa-submit-question', DWQA_URI . 'assets/js/dwqa-submit-question.js', array( 'jquery' ), $version, true );
        wp_localize_script( 'dwqa-submit-question', 'dwqa', $dwqa );
    }
}
add_action( 'wp_enqueue_scripts', 'dwqa_enqueue_scripts' );

/**
 * Add metabox for question status meta data
 * @return void
 */
function dwqa_add_status_metabox(){
    add_meta_box( 'dwqa-post-status', 'Status', 'dwqa_question_status_box_html', 'dwqa-question', 'side', 'high' );
}
add_action( 'admin_init', 'dwqa_add_status_metabox' );

/**
 * Generate html for metabox of question status meta data
 * @param  object $post Post Object
 * @return void       
 */
function dwqa_question_status_box_html($post){
        $meta = get_post_meta( $post->ID, '_dwqa_status', true );
        $meta = $meta ? $meta : 'open';
    ?>
    <p>
        <label for="dwqa-question-status">
            <?php _e('Status','dwqa') ?><br>&nbsp;
            <select name="dwqa-question-status" id="dwqa-question-status" class="widefat">
                <option <?php selected( $meta, 'open' ); ?> value="open"><?php _e('Open','dwqa') ?></option>
                <option <?php selected( $meta, 'pending' ); ?> value="pending"><?php _e('Pending','dwqa') ?></option>
                <option <?php selected( $meta, 'resolved' ); ?> value="resolved"><?php _e('Resolved','dwqa') ?></option>
                <option <?php selected( $meta, 're-open' ); ?> value="re-open"><?php _e('Re-Open','dwqa') ?></option>
                <option <?php selected( $meta, 'closed' ); ?> value="closed"><?php _e('Closed','dwqa') ?></option>
            </select>
        </label>
    </p>    
    <?php
}

function dwqa_question_status_save($post_id){
    if( ! wp_is_post_revision( $post_id ) ) {
        if( isset($_POST['dwqa-question-status']) ) {
            update_post_meta( $post_id, '_dwqa_status', $_POST['dwqa-question-status'] );
        }
    }
}
add_action( 'save_post', 'dwqa_question_status_save' );

function dwqa_human_time_diff( $from, $to = false, $format = false ){
    if( ! $format ) {
        $format = get_option('date_format');
    }
    if ( !$to ){
        $to = current_time('timestamp');
    }

    $diff = (int) abs($to - $from);
    if($diff <= 1){
        $since = '1 second';
    } else if($diff <= 60 ){
        $since = sprintf(_n('%s second', '%s seconds', $diff), $diff);
    } else if ($diff <= 3600) {

        $mins = round($diff / 60);

        if ($mins <= 1) {
            $mins = 1;
        }
        /* translators: min=minute */
        $since = sprintf(_n('about %s min', '%s mins', $mins), $mins);
    } else if ( ($diff <= 86400) && ($diff > 3600)) {
        $hours = round($diff / 3600);
        if ($hours <= 1) {
            $hours = 1;
        }
        $since = sprintf(_n('about %s hour', '%s hours', $hours), $hours);
    } elseif ($diff >= 86400 && $diff <= 86400*7 ) {
        $days = round($diff / 86400);
        if ($days <= 1) {
            $days = 1;
        }
        $since = sprintf(_n('%s day', '%s days', $days), $days);
    } else {
        return date( $format, $from );
    }
    return $since . ' ' . __('ago','dwqa');
}


function dwqa_human_time_diff_for_date( $the_date, $d ){
    global $post;
    if( $post->post_type == 'dwqa-question' || $post->post_type == 'dwqa-answer' ) {
        return dwqa_human_time_diff( strtotime( get_the_time('c') ), false, $d );
    }
    return $the_date;
}
add_filter( 'get_the_date', 'dwqa_human_time_diff_for_date', 10, 2);

function dwqa_comment_human_time_diff_for_date( $the_date, $d ){
    global $comment;
    $parent_posttype = get_post_type( $comment->comment_post_ID );
    if( $parent_posttype == 'dwqa-question' || $parent_posttype == 'dwqa-answer' ) {
        return dwqa_human_time_diff( strtotime($comment->comment_date), false, $d );
    }
    return $the_date;
}
add_filter( 'get_comment_date', 'dwqa_comment_human_time_diff_for_date', 10, 2);


function dwqa_tinymce_addbuttons() {
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
        return;

    if ( get_user_option('rich_editing') == 'true') {
        add_filter("mce_external_plugins", "dwqa_add_custom_tinymce_plugin");
        add_filter('mce_buttons', 'dwqa_register_custom_button');
    }
}
add_action('init', 'dwqa_tinymce_addbuttons');

function dwqa_register_custom_button($buttons) {
    array_push($buttons, "|", "dwqaCodeEmbed");
    return $buttons;
} 

function dwqa_add_custom_tinymce_plugin($plugin_array) {
    $plugin_array['dwqaCodeEmbed'] = DWQA_URI . 'assets/js/code-edit-button.js';
    return $plugin_array;
}

function dwqa_add_js_variable_for_admin_page(){
    ?>
    <script type="text/javascript">
    if( ! dwqa ) {
        var dwqa = {
                    'plugin_dir'                : '<?php echo addslashes( DWQA_DIR ) ?>',
                    'plugin_uri'                : '<?php echo DWQA_URI ?>',
                    'ajax_url'                  : '<?php echo admin_url( 'admin-ajax.php' ) ?>',
                    'text_next'                 : '<?php echo __('Next','dwqa') ?>',
                    'text_prev'                 : '<?php echo __('Prev','dwqa') ?>' ,
                    'questions_archive_link'    : '<?php echo get_post_type_archive_link( 'dwqa-question' ) ?>',
                    'error_missing_question_content'     : '<?php echo   __( 'Please enter your question', 'dwqa' ) ?>',
                    'error_missing_answer_content'   : '<?php echo  __('Please enter your answer','dwqa') ?>',
                    'error_missing_comment_content'  : '<?php echo   __('Please enter your comment content','dwqa') ?>',
                    'error_not_enought_length'      : '<?php echo __('Comment must have more than 2 characters','dwqa') ?>',
                    'comment_edit_submit_button'     : '<?php echo   __( 'Update', 'dwqa' ) ?>',
                    'comment_edit_link'     : '<?php echo   __( 'Edit', 'dwqa' ) ?>',
                    'comment_edit_cancel_link'     : '<?php echo   __( 'Cancel', 'dwqa' ) ?>',
                    'comment_delete_confirm'         : '<?php echo  __('Do you want to delete this comment?', 'dwqa' ) ?>',
                    'answer_delete_confirm'      : '<?php echo   __('Do you want to delete this answer?', 'dwqa' ) ?>',
                    'flag'       : {
                        'label'          : '<?php echo   __('Flag','dwqa') ?>',
                        'label_revert'   : '<?php echo   __('Unflag','dwqa') ?>',
                        'text'           : '<?php echo   __('This answer will be hide when flag it. Are you sure?', 'dwqa' ) ?>',
                        'revert'         : '<?php echo   __('This answer was flagged as spam. Do you want to show it','dwqa') ?>',
                        'flagged_hide'   : '<?php echo   __('hide','dwqa') ?>',
                        'flagged_show'   : '<?php echo  __('show','dwqa') ?>'
                    }
            }
    }
    </script>
    <?php
}
//add_action( 'wp_head', 'dwqa_add_js_variable_for_admin_page' );
add_action( 'admin_print_scripts', 'dwqa_add_js_variable_for_admin_page' );

function array_insert(&$array,$element,$position=null) {
    if( is_array($element) ) {
        $part = $element;
    } else {
        $part = array($position=>$element);
    }

    $len = count($array);

    $firsthalf = array_slice($array, 0, $len / 2);
    $secondhalf = array_slice($array, $len / 2);

    $array = array_merge($firsthalf, $part, $secondhalf);
    return $array;
}
// ADD NEW COLUMN  
function dwqa_columns_head($defaults) {  
    if( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'dwqa-answer' ) {
        $defaults = array(
            'cb'            => '<input type="checkbox">',
            'info'          => __( 'Answer', 'dwqa' ),
            'author'        => __( 'Author', 'dwqa' ),
            'comment'       => '<span><span class="vers"><div title="Comments" class="comment-grey-bubble"></div></span></span>',
            'dwqa-question' => __( 'In Response To', 'dwqa' )
        );
    }
    if( $_GET['post_type'] == 'dwqa-question' ) {
        $defaults['info'] = __('Info','dwqa') ;
        $defaults = array_insert( $defaults, array( 'question-category' => 'Category', 'question-tag' => 'Tags' ), 1 );
    }
    return $defaults;  
} 
add_filter('manage_posts_columns', 'dwqa_columns_head');  

function dwqa_answer_columns_content($column_name, $post_ID) {  
    $answer = get_post( $post_ID );
    switch ($column_name) {
        case 'comment' :
            $comment_count = get_comment_count($post_ID);
            echo '<a href="'.admin_url('edit-comments.php?p='.$post_ID ).'"  class="post-com-count"><span class="comment-count">'.$comment_count['approved'].'</span></a>';
            break;
        case 'info':
            printf(
                '%s %s <a href="%s">%s %s</a> <br /> %s',
                __('Submitted','dwqa'),
                __('on','dwqa'),
                get_permalink(),
                date( 'M d Y', get_post_time( 'U', false, $answer ) ),
                ( time() - get_post_time( 'U', false, $answer ) ) > 60 * 60 * 24 * 2 ? '' : ' at ' . human_time_diff( get_post_time( 'U', false, $answer ) ) . ' ago',
                get_the_excerpt()

            );
            break;
        case 'dwqa-question':
            $question_id = get_post_meta( $post_ID, '_question', true );
            $question = get_post( $question_id );
            echo '<a href="'.get_permalink( $question_id ). '" >'. $question->post_title. '</a><br>'; 
            break;
    }
} 
add_action('manage_dwqa-answer_posts_custom_column', 'dwqa_answer_columns_content', 10, 2);


// Init script for back-end
function dwqa_admin_enqueue_scripts(){
    $screen = get_current_screen();
    if( 'dwqa-question_page_dwqa-settings' == $screen->id ) {
        wp_enqueue_media();
        wp_enqueue_script( 'dwqa-settings', DWQA_URI . 'assets/js/admin-settings-page.js', array( 'jquery' ) );
        wp_localize_script( 'dwqa-settings', 'dwqa', array(
            'template_folder'   => DWQA_URI . 'inc/templates/email/'
        ) );
    }
    if( 'dwqa-question' == get_post_type() || 'dwqa-answer' == get_post_type() || 'dwqa-question_page_dwqa-settings' == $screen->id ) {
        wp_enqueue_style( 'dwqa-admin-style' , DWQA_URI . 'assets/css/admin-style.css' );
    }
}
add_action( 'admin_enqueue_scripts', 'dwqa_admin_enqueue_scripts' ); 

// SHOW THE FEATURED IMAGE  
function dwqa_question_columns_content($column_name, $post_ID) {  
    switch ($column_name) {
        case 'info':
            echo ucfirst( get_post_meta( $post_ID, '_dwqa_status', true ) ) . '<br>';
            echo '<strong>'.dwqa_answer_count($post_ID) . '</strong> '.__('answered','dwqa') . '<br>';
            echo '<strong>'.dwqa_vote_count($post_ID) . '</strong> '.__('voted','dwqa') . '<br>';
            echo '<strong>'.dwqa_question_views_count($post_ID) . '</strong> '.__('views','dwqa') . '<br>';
            break;
        case 'question-category':
            $terms = wp_get_post_terms( $post_ID, 'dwqa-question_category' );
            $i = 0;
            foreach ($terms as $term) {
                if( $i > 0 ) {
                    echo ', ';
                }
                echo '<a href="'.get_term_link( $term, 'dwqa-question_category' ).'">'.$term->name . '</a> ';
                $i++;
            }
            break;
        case 'question-tag':
            $terms = wp_get_post_terms( $post_ID, 'dwqa-question_tag' );
            $i = 0;
            foreach ($terms as $term) {
                if( $i > 0 ) {
                    echo ', ';
                }
                echo '<a href="'.get_term_link( $term, 'dwqa-question_tag' ).'">'.$term->name . '</a> ';
                $i++;
            }
            break;
    }
} 
add_action('manage_dwqa-question_posts_custom_column', 'dwqa_question_columns_content', 10, 2);  


function dwqa_answer_count( $question_id ){
    $args = array(
       'posts_per_page' => -1,
       'post_type'      => 'dwqa-answer',
       'meta_key'       => '_question',
       'meta_value'     => $question_id
    );
    $rs = new WP_Query($args);

    return $rs->found_posts;
}

function dwqa_content_start_wrapper(){
    dwqa_load_template( 'content', 'start-wrapper' );
    echo '<div class="dwqa-container">';
}
add_action( 'dwqa_before_page', 'dwqa_content_start_wrapper' );

function dwqa_content_end_wrapper(){
    dwqa_has_sidebar_template();
    dwqa_load_template( 'content', 'end-wrapper' );
    echo '</div>';
}
add_action( 'dwqa_after_page', 'dwqa_content_end_wrapper' );

function dwqa_has_sidebar_template(){
    global $dwqa_options;
    $template = get_stylesheet_directory() . '/dwqa-templates/';
    if( is_single() && file_exists($template . '/sidebar-single.php') ) {
        include $template . '/sidebar-single.php';
        return;
    } else if( is_single() && file_exists( DWQA_DIR . 'inc/templates/sidebar-single.php') ) {
        include DWQA_DIR . 'inc/templates/sidebar-single.php';
        return;
    }

    return;
}

function dwqa_related_question( $question_id = false ) {
    if( ! $question_id ) {
        $question_id = get_the_ID();
    }
    $tag_in = $cat_in = array();
    $tags = wp_get_post_terms( $question_id, 'dwqa-question_tag' );
    if( ! empty($tags) ) {
        foreach ($tags as $tag ) {
            $tag_in[] = $tag->term_id;
        }   
    }
    
    $category = wp_get_post_terms( $question_id, 'dwqa-question_category' );
    if( ! empty($category) ) {
        foreach ($category as $cat) {
            $cat_in[] = $cat->term_id;
        }    
    }
    $args = array(
        'orderby'       => 'rand',
        'post__not_in'  => array($question_id),
        'showposts'     => 5,
        'ignore_sticky_posts' => 1,
        'post_type'     => 'dwqa-question'
    );

    $args['tax_query']['relation'] = 'OR';
    if( !empty($cat_in) ) {
        $args['tax_query'][] = array(
            'taxonomy'  => 'dwqa-question_category',
            'field'     => 'id',
            'terms'     => $cat_in,
            'operator'  => 'IN'
        );
    }
    if( !empty($tag_in) ) {
        $args['tax_query'][] = array(
            'taxonomy'  => 'dwqa-question_tag',
            'field'     => 'id',
            'terms'     => $tag_in,
            'operator'  => 'IN'
        );
    }

    $related_questions = new WP_Query( $args );
    
    if( $related_questions->have_posts() ) {
        echo '<h3>'.__('Related Questions','dwqa').'</h3>';
        echo '<ul>';
        while ( $related_questions->have_posts() ) { $related_questions->the_post();
            echo '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__('asked by','dwqa').' ';
            the_author_posts_link();
            echo '</li>';
        }
        echo '</ul>';
    }
}

?>
