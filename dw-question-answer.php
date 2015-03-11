<?php  
/**
 *  Plugin Name: DW Question Answer
 *  Description: A WordPress plugin was make by DesignWall.com to build an Question Answer system for support, asking and comunitcate with your customer 
 *  Author: DesignWall
 *  Author URI: http://www.designwall.com
 *  Version: 1.3.2
 *  Text Domain: dwqa
 */

// Define constant for plugin info 
if ( ! defined( 'DWQA_DIR' ) ) {
	define( 'DWQA_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DWQA_URI' ) ) {
	define( 'DWQA_URI', plugin_dir_url( __FILE__ ) );
}

require_once DWQA_DIR  . 'inc/template-functions.php';
require_once DWQA_DIR  . 'inc/settings.php';
require_once DWQA_DIR  . 'inc/actions.php';
require_once DWQA_DIR  . 'inc/actions-question.php';
require_once DWQA_DIR  . 'inc/actions-vote.php';
require_once DWQA_DIR  . 'inc/filter.php';
require_once DWQA_DIR  . 'inc/metaboxes.php';
include_once DWQA_DIR  . 'inc/notification.php';
require_once DWQA_DIR  . 'inc/class-answers-list-table.php';
require_once DWQA_DIR  . 'inc/class-walker-category.php';
require_once DWQA_DIR  . 'inc/class-walker-tag-dropdown.php';
include_once DWQA_DIR  . 'inc/contextual-helper.php'; 
include_once DWQA_DIR  . 'inc/pointer-helper.php'; 
include_once DWQA_DIR  . 'inc/beta.php'; 
include_once DWQA_DIR  . 'inc/shortcodes.php';
include_once DWQA_DIR  . 'inc/status.php';
include_once DWQA_DIR  . 'inc/roles.php';

include_once DWQA_DIR  . 'inc/widgets/related-question.php';
include_once DWQA_DIR  . 'inc/widgets/popular-question.php';
include_once DWQA_DIR  . 'inc/widgets/latest-question.php';
include_once DWQA_DIR  . 'inc/widgets/list-closed-question.php';

// include_once DWQA_DIR  . 'inc/database_upgrade.php';

include_once DWQA_DIR  . 'inc/cache.php';

include_once DWQA_DIR  . 'inc/deprecated.php';



function dwqa_include_recaptcha_library() {
	if ( ! defined( 'RECAPTCHA_VERIFY_SERVER' ) ) {
		require_once DWQA_DIR  . 'inc/lib/recaptcha-php/recaptchalib.php';
	}
}
add_action( 'plugins_loaded', 'dwqa_include_recaptcha_library' );


global $dwqa_permission;
$dwqa_permission = new DWQA_Permission();

function dwqa_posttype_init() {
	global $dwqa_options;
	extract( dwqa_get_rewrite_slugs() );
	/* Question Posttype Registration */
	$question_labels = array(
		'name' => __( 'Question', 'dwqa' ),
		'singular_name' => __( 'Question', 'dwqa' ),
		'add_new' => __( 'Add New', 'theme' ),
		'add_new_item' => __( 'Add New Question', 'dwqa' ),
		'edit_item' => __( 'Edit Question' ),
		'new_item' => __( 'New Question', 'dwqa' ),
		'all_items' => __( 'All Questions', 'dwqa' ),
		'view_item' => __( 'View Question', 'dwqa' ),
		'search_items' => __( 'Search Question', 'dwqa' ),
		'not_found' => __( 'No questions found', 'dwqa' ),
		'not_found_in_trash' => __( 'No questions found in Trash', 'dwqa' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'DW Q&A', 'dwqa' ),
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
			'with_front'    => false,
		),
		'has_archive' => true, 
		'hierarchical' => true,
		'menu_icon' => '',
		'supports' => array( 'title', 'editor', 'comments', 'author', 'page-attributes' )
	); 
	register_post_type( 'dwqa-question', $question_args );
	
	/* Question Posttype Registration */
	$answer_labels = array(
		'name' => __( 'Answer', 'dwqa' ),
		'singular_name' => __( 'Answer', 'dwqa' ),
		'add_new' => __( 'Add New', 'dwqa' ),
		'add_new_item' => __( 'Add new answer', 'dwqa' ),
		'edit_item' => __( 'Edit answer', 'dwqa' ),
		'new_item' => __( 'New Answer', 'dwqa' ),
		'all_items' => __( 'All Answers', 'dwqa' ),
		'view_item' => __( 'View Answer', 'dwqa' ),
		'search_items' => __( 'Search Answer', 'dwqa' ),
		'not_found' => __( 'No Answers found', 'dwqa' ),
		'not_found_in_trash' => __( 'No Answers found in Trash', 'dwqa' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'Answer', 'dwqa' ),
	);
	$answer = array(
		'labels' => $answer_labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => 'edit.php?post_type=dwqa-question', 
		'query_var' => true,
		'rewrite' => true,
		'has_archive' => false, 
		'hierarchical' => true,
		'menu_icon' => '',
		'supports' => array( 'title', 'editor', 'comments', 'custom-fields', 'author', 'page-attributes', )
	); 
	register_post_type( 'dwqa-answer', $answer );

	// Question Taxonomies ( Tag, Category register for Question Posttype)
	// Question Categories
	$question_category_labels = array(
		'name' => _x( 'Categories', 'taxonomy general name' ),
		'singular_name' => _x( 'Category', 'taxonomy singular name' ),
		'search_items' => __( 'Search Categories', 'dwqa' ),
		'all_items' => __( 'All Categories', 'dwqa' ),
		'parent_item' => __( 'Parent Category', 'dwqa' ),
		'parent_item_colon' => __( 'Parent Category:', 'dwqa' ),
		'edit_item' => __( 'Edit Category', 'dwqa' ), 
		'update_item' => __( 'Update Category', 'dwqa' ),
		'add_new_item' => __( 'Add New Category', 'dwqa' ),
		'new_item_name' => __( 'New Category Name', 'dwqa' ),
		'menu_name' => __( 'Question Categories', 'dwqa' ),
	);  


	register_taxonomy( 'dwqa-question_category', array( 'dwqa-question' ), array(
			'hierarchical'  => true,
			'labels'        => $question_category_labels,
			'show_ui'       => true,
			'query_var'     => true,
			'rewrite'       => array( 
				'slug'          => $question_category_rewrite,
				'with_front'    => false,
			),
	) );

	// Question Tags
	$question_tag_labels = array(
		'name' => _x( 'Tags', 'taxonomy general name' ),
		'singular_name' => _x( 'Tag', 'taxonomy singular name' ),
		'search_items' => __( 'Search Tags', 'dwqa' ),
		'popular_items' => __( 'Popular Tags', 'dwqa' ),
		'all_items' => __( 'All Tags', 'dwqa' ),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __( 'Edit Tag', 'dwqa' ), 
		'update_item' => __( 'Update Tag', 'dwqa' ),
		'add_new_item' => __( 'Add New Tag', 'dwqa' ),
		'new_item_name' => __( 'New Tag Name', 'dwqa' ),
		'separate_items_with_commas' => __( 'Separate tags with commas', 'dwqa' ),
		'add_or_remove_items' => __( 'Add or remove tags', 'dwqa' ),
		'choose_from_most_used' => __( 'Choose from the most used tags', 'dwqa' ),
		'menu_name' => __( 'Question Tags', 'dwqa' ),
	); 

	register_taxonomy('dwqa-question_tag', array('dwqa-question'), array(
		'hierarchical'  => false,
		'labels'        => $question_tag_labels,
		'show_ui'       => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'     => true,
		'rewrite'       => array( 
			'slug' => $question_tag_rewrite,
			'with_front'    => false,
		),
	) );

	// Create default category for dwqa question type when dwqa plugin is actived 
	$cats = get_categories( array(
		'type'                     => 'dwqa-question',
		'hide_empty'               => 0,
		'taxonomy'                 => 'dwqa-question_category',
	) );

	if ( empty( $cats ) ) {
		wp_insert_term( 'Questions', 'dwqa-question_category' );
	}

	// update term rewrite rule
	dwqa_update_term_rewrite_rules();
}
/*** PLUGIN INIT */
function dwqa_plugin_init() {
	global $script_version, $dwqa_template, $dwqa_sript_vars;
	extract( dwqa_get_rewrite_slugs() );

	//Load translate text domain
	load_plugin_textdomain( 'dwqa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	dwqa_posttype_init();

	$dwqa_template = 'default';
	$script_version = 06062014;
	$dwqa_sript_vars = array(
		'is_logged_in'  => is_user_logged_in(),
		'plugin_dir_url' => DWQA_URI,
		'code_icon'     => DWQA_URI . 'inc/templates/'.$dwqa_template.'/assets/img/icon-code.png',
		'ajax_url'      => admin_url( 'admin-ajax.php' ),
		'text_next'     => __( 'Next','dwqa' ),
		'text_prev'     => __( 'Prev','dwqa' ),
		'questions_archive_link'    => get_post_type_archive_link( 'dwqa-question' ),
		'error_missing_question_content'    => __( 'Please enter your question', 'dwqa' ),
		'error_question_length' => __( 'Your question must be at least 2 characters in length', 'dwqa' ),
		'error_valid_email'    => __( 'Enter a valid email address', 'dwqa' ),
		'error_valid_user'    => __( 'Enter a question title', 'dwqa' ),
		'error_valid_name'    => __( 'Please add your name', 'dwqa' ),
		'error_missing_answer_content'  => __( 'Please enter your answer', 'dwqa' ),
		'error_missing_comment_content' => __( 'Please enter your comment content', 'dwqa' ),
		'error_not_enought_length'      => __( 'Comment must have more than 2 characters', 'dwqa' ),
		'search_not_found_message'  => __( 'Not found! Try another keyword.', 'dwqa' ),
		'search_enter_get_more'  => __( 'Or press <strong>ENTER</strong> to get more questions', 'dwqa' ),
		'comment_edit_submit_button'    => __( 'Update', 'dwqa' ),
		'comment_edit_link'    => __( 'Edit', 'dwqa' ),
		'comment_edit_cancel_link'    => __( 'Cancel', 'dwqa' ),
		'comment_delete_confirm'        => __( 'Do you want to delete this comment?', 'dwqa' ),
		'answer_delete_confirm'     => __( 'Do you want to delete this answer?', 'dwqa' ),
		'answer_update_privacy_confirm' => __( 'Do you want to update this answer?', 'dwqa' ), 
		'report_answer_confirm' => __( 'Do you want to report this answer?', 'dwqa' ),
		'flag'      => array(
			'label'         => __( 'Report', 'dwqa' ),
			'label_revert'  => __( 'Undo', 'dwqa' ),
			'text'          => __( 'This answer will be marked as spam and hidden. Do you want to flag it?', 'dwqa' ),
			'revert'        => __( 'This answer was flagged as spam. Do you want to show it', 'dwqa' ),
			'flag_alert'         => __( 'This answer was flagged as spam', 'dwqa' ),
			'flagged_hide'  => __( 'hide', 'dwqa' ),
			'flagged_show'  => __( 'show', 'dwqa' ),
		),
		'follow_tooltip'    => __( 'Follow This Question', 'dwqa' ),
		'unfollow_tooltip'  => __( 'Unfollow This Question', 'dwqa' ),
		'stick_tooltip'    => __( 'Pin this question to top', 'dwqa' ),
		'unstick_tooltip'  => __( 'Unpin this question from top', 'dwqa' ),
		'question_category_rewrite' => $question_category_rewrite,
		'question_tag_rewrite'      => $question_tag_rewrite,
		'delete_question_confirm' => __( 'Do you want to delete this question?', 'dwqa' )
		  
	);
}
add_action( 'init', 'dwqa_plugin_init' );

// Update rewrite url when active plugin
function dwqa_activate() {
	global $dwqa_permission;
	$dwqa_permission->prepare_permission_caps();
	
	//Auto create question page
	$options = get_option( 'dwqa_options' );

	if ( ! isset( $options['pages']['archive-question'] ) || ( isset( $options['pages']['archive-question'] ) && ! get_page( $options['pages']['archive-question'] ) ) ) {
		$args = array(
			'post_title' => __( 'DWQA Questions', 'dwqa' ),
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_content'  => '[dwqa-list-questions]',
		);
		$question_page = get_page_by_path( sanitize_title( $args['post_title'] ) );
		if ( ! $question_page ) {
			$options['pages']['archive-question'] = wp_insert_post( $args );
		} else {
			// Page exists
			$options['pages']['archive-question'] = $question_page->ID;
		}
	}

	if ( ! isset( $options['pages']['submit-question'] ) || ( isset( $options['pages']['submit-question'] ) && ! get_page( $options['pages']['submit-question'] ) ) ) {

		$args = array(
			'post_title' => __( 'DWQA Ask Question', 'dwqa' ),
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_content'  => '[dwqa-submit-question-form]',
		);
		$ask_page = get_page_by_path( sanitize_title( $args['post_title'] ) );

		if ( ! $ask_page ) {
			$options['pages']['submit-question'] = wp_insert_post( $args );
		} else {
			// Page exists
			$options['pages']['submit-question'] = $ask_page->ID;
		}
	}

	// Valid page content to ensure shortcode was inserted
	$questions_page_content = get_post_field( 'post_content', $options['pages']['archive-question'] );
	if ( strpos( $questions_page_content, '[dwqa-list-questions]' ) === false ) {
		$questions_page_content = str_replace( '[dwqa-submit-question-form]', '', $questions_page_content );
		wp_update_post( array(
			'ID'			=> $options['pages']['archive-question'],
			'post_content'	=> $questions_page_content . '[dwqa-list-questions]',
		) );
	}

	$submit_question_content = get_post_field( 'post_content', $options['pages']['submit-question'] );
	if ( strpos( $submit_question_content, '[dwqa-submit-question-form]' ) === false ) {
		$submit_question_content = str_replace( '[dwqa-list-questions]', '', $submit_question_content );
		wp_update_post( array(
			'ID'			=> $options['pages']['submit-question'],
			'post_content'	=> $submit_question_content . '[dwqa-submit-question-form]',
		) );
	}

	update_option( 'dwqa_options', $options );

	dwqa_posttype_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dwqa_activate' );

function dwqa_deactivate_hook() {
	global $dwqa_permission;
	$dwqa_permission->remove_permision_caps();

	wp_clear_scheduled_hook( 'dwqa_hourly_event' );

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'dwqa_deactivate_hook' );

/* Flush rewrite rules for custom post types. */

add_action( 'after_switch_theme', 'dwqa_flush_rewrite_rules' );
function dwqa_flush_rewrite_rules() {
     flush_rewrite_rules();
}

function dwqa_update_term_rewrite_rules() {
	//add rewrite for question taxonomy
	global $wp_rewrite;
	$options = get_option( 'dwqa_options' );

	$page_id = $options['pages']['archive-question'];
	$question_list_page = get_page( $page_id );
	$rewrite_category = isset( $options['question-category-rewrite'] ) ? sanitize_title( $options['question-category-rewrite'] ) : 'question-category';
	$rewrite_tag = isset( $options['question-tag-rewrite'] ) ? sanitize_title( $options['question-tag-rewrite'] ) : 'question-tag';

	if ( $question_list_page ) {
		$dwqa_rewrite_rules = array(
			'^'.$question_list_page->post_name.'/'.$rewrite_category.'/([^/]*)' => 'index.php?page_id='.$page_id.'&taxonomy=dwqa-question_category&dwqa-question_category=$matches[1]',
			'^'.$question_list_page->post_name.'/'.$rewrite_tag.'/([^/]*)' => 'index.php?page_id='.$page_id.'&taxonomy=dwqa-question_tag&dwqa-question_tag=$matches[1]',
		);
		foreach ( $dwqa_rewrite_rules as $regex => $redirect ) {
			add_rewrite_rule( $regex, $redirect, 'bottom' );
		}
		// Add permastruct for pretty link
		add_permastruct( 'dwqa-question_category', "{$question_list_page->post_name}/{$rewrite_category}/%dwqa-question_category%" );
		add_permastruct( 'dwqa-question_tag', "{$question_list_page->post_name}/{$rewrite_tag}/%dwqa-question_tag%" );
	}
}


function dwqa_human_time_diff( $from, $to = false, $format = false ) {
	if ( ! $format ) {
		$format = get_option( 'date_format' );
	}
	if ( ! $to ) {
		$to = current_time( 'timestamp' );
	}

	$diff = (int) abs( $to - $from );
	if ( $diff <= 1 ) {
		$since = '1 second';
	} elseif ( $diff <= 60 ) {
		$since = sprintf( _n( '%s second', '%s seconds', $diff, 'dwqa' ), $diff );
	} elseif ( $diff <= 3600 ) {

		$mins = round( $diff / 60 );

		if ( $mins <= 1 ) {
			$mins = 1;
		}
		/* translators: min=minute */
		$since = sprintf( _n( 'about %s min', '%s mins', $mins, 'dwqa' ), $mins );
	} elseif ( ( $diff <= 86400 ) && ( $diff > 3600 ) ) {
		$hours = round( $diff / 3600 );
		if ( $hours <= 1 ) {
			$hours = 1;
		}
		$since = sprintf( _n( 'about %s hour', '%s hours', $hours, 'dwqa' ), $hours );
	} elseif ( $diff >= 86400 && $diff <= 86400 * 7 ) {
		$days = round( $diff / 86400 );
		if ( $days <= 1 ) {
			$days = 1;
		}
		$since = sprintf( _n( '%s day', '%s days', $days, 'dwqa' ), $days );
	} else {
		return date( $format, $from );
	}
	return sprintf( __( '%1$s ago', 'dwqa' ), $since );
}


function dwqa_human_time_diff_for_date( $the_date, $d ) {
	global $post;
	if ( $post->post_type == 'dwqa-question' || $post->post_type == 'dwqa-answer' ) {
		return dwqa_human_time_diff( strtotime( get_the_time( 'c' ) ), false, $d );
	}
	return $the_date;
}
add_filter( 'get_the_date', 'dwqa_human_time_diff_for_date', 10, 2 );

function dwqa_comment_human_time_diff_for_date( $the_date, $d ) {
	global $comment;
	$parent_posttype = get_post_type( $comment->comment_post_ID );
	if ( $parent_posttype == 'dwqa-question' || $parent_posttype == 'dwqa-answer' ) {
		return dwqa_human_time_diff( strtotime( $comment->comment_date ), false, $d );
	}
	return $the_date;
}
add_filter( 'get_comment_date', 'dwqa_comment_human_time_diff_for_date', 10, 2 );


function dwqa_tinymce_addbuttons() {
	if ( get_user_option( 'rich_editing' ) == 'true' && ! is_admin() ) {
		add_filter( 'mce_external_plugins', 'dwqa_add_custom_tinymce_plugin' );
		add_filter( 'mce_buttons', 'dwqa_register_custom_button' );
	}
}
add_action( 'init', 'dwqa_tinymce_addbuttons' );

function dwqa_register_custom_button( $buttons ) {
	array_push( $buttons, '|', 'dwqaCodeEmbed' );
	return $buttons;
} 

function dwqa_add_custom_tinymce_plugin( $plugin_array ) {
	global $dwqa_options;
	if ( is_singular( 'dwqa-question' ) || ( $dwqa_options['pages']['submit-question'] && is_page( $dwqa_options['pages']['submit-question'] ) ) ) {
		$plugin_array['dwqaCodeEmbed'] = DWQA_URI . 'assets/js/code-edit-button.js';
	}
	return $plugin_array;
}

function dwqa_add_js_variable_for_admin_page() {
	$html = '<script type="text/javascript">';
	$html .= 'if( typeof dwqa == "undefined" ){';
	$html .= 'var dwqa = {
					"plugin_dir"                : "' . addslashes( DWQA_DIR ) . '",
					"plugin_uri"                : "' . DWQA_URI . '",
					"ajax_url"                  : "' . admin_url( 'admin-ajax.php' ) . '",
					"text_next"                 : "' . __( 'Next', 'dwqa' ) . '",
					"text_prev"                 : "' . __( 'Prev', 'dwqa' ) . '" ,
					"questions_archive_link"    : "' . get_post_type_archive_link( 'dwqa-question' ) . '",
					"error_missing_question_content"     : "' . __( 'Please enter your question', 'dwqa' ) . '",
					"error_missing_answer_content"   : "' . __( 'Please enter your answer', 'dwqa' ) . '",
					"error_missing_comment_content"  : "' . __( 'Please enter your comment content', 'dwqa' ) . '",
					"error_not_enought_length"      : "' . __( 'Comment must have more than 2 characters', 'dwqa' ) . '",
					"comment_edit_submit_button"     : "' . __( 'Update', 'dwqa' ) . '",
					"comment_edit_link"     : "' . __( 'Edit', 'dwqa' ) . '",
					"comment_edit_cancel_link"     : "' . __( 'Cancel', 'dwqa' ) . '",
					"comment_delete_confirm"         : "' . __( 'Do you want to delete this comment?', 'dwqa' ) . '",
					"answer_delete_confirm"      : "' . __( 'Do you want to delete this answer?', 'dwqa' ) . '",
					"flag"       : {
						"label"          : "' . __( 'Flag', 'dwqa' ) . '",
						"label_revert"   : "' . __( 'Unflag', 'dwqa' ) . '",
						"text"           : "' . __( 'This answer will be hidden when flagged. Are you sure you want to flag it?', 'dwqa' ) . '",
						"revert"         : "' . __( 'This answer was flagged as spam. Do you want to show it?', 'dwqa' ) . '",
						"flagged_hide"   : "' . __( 'hide' , 'dwqa' ) . '",
						"flagged_show"   : "' . __( 'show', 'dwqa' ) . '"
					}
			}';
	$html .= '}'; //Endif
	$html .= '</script>';

	echo preg_replace(
	    array(
	        '/ {2,}/',
	        '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s',
	    ),
	    array(
	        ' ',
	        '',
	    ),
	    $html
	);
}
add_action( 'admin_print_scripts', 'dwqa_add_js_variable_for_admin_page' );

function dwqa_array_insert( &$array, $element, $position = null ) {
	if ( is_array( $element ) ) {
		$part = $element;
	} else {
		$part = array( $position => $element );
	}

	$len = count( $array );

	$firsthalf = array_slice( $array, 0, $len / 2 );
	$secondhalf = array_slice( $array, $len / 2 );

	$array = array_merge( $firsthalf, $part, $secondhalf );
	return $array;
}
// ADD NEW COLUMN  
function dwqa_columns_head( $defaults ) {  
	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'dwqa-answer' ) {
		$defaults = array(
			'cb'            => '<input type="checkbox">',
			'info'          => __( 'Answer', 'dwqa' ),
			'author'        => __( 'Author', 'dwqa' ),
			'comment'       => '<span><span class="vers"><div title="Comments" class="comment-grey-bubble"></div></span></span>',
			'dwqa-question' => __( 'In Response To', 'dwqa' ),
		);
	}
	if ( $_GET['post_type'] == 'dwqa-question' ) {
		$defaults['info'] = __( 'Info', 'dwqa' );
		$defaults = dwqa_array_insert( $defaults, array( 'question-category' => 'Category', 'question-tag' => 'Tags' ), 1 );
	}
	return $defaults;  
} 
add_filter( 'manage_posts_columns', 'dwqa_columns_head' );  

function dwqa_answer_row_actions( $actions, $always_visible = false ) {
	$action_count = count( $actions );
	$i = 0;

	if ( ! $action_count )
		return '';

	$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
	foreach ( $actions as $action => $link ) {
		++$i;
		( $i == $action_count ) ? $sep = '' : $sep = ' | ';
		$out .= "<span class='$action'>$link$sep</span>";
	}
	$out .= '</div>';

	return $out;
}

function dwqa_answer_columns_content( $column_name, $post_ID ) {  
	$answer = get_post( $post_ID );
	switch ( $column_name ) {
		case 'comment' :
			$comment_count = get_comment_count( $post_ID );
			echo '<a href="'.admin_url( 'edit-comments.php?p='.$post_ID ).'"  class="post-com-count"><span class="comment-count">'.$comment_count['approved'].'</span></a>';
			break;
		case 'info':
			//Build row actions
			$actions = array(
				'edit'      => sprintf( '<a href="%s">%s</a>', get_edit_post_link( $post_ID ), __( 'Edit', 'edd-dw-membersip' ) ),
				'delete'    => sprintf( '<a href="%s">%s</a>', get_delete_post_link( $post_ID ), __( 'Delete', 'edd-dw-membersip' ) ),
				'view'      => sprintf( '<a href="%s">%s</a>', get_permalink( $post_ID ), __( 'View', 'edd-dw-membersip' ) )
			);
			printf(
				'%s %s <a href="%s">%s %s</a> <br /> %s %s',
				__( 'Submitted', 'dwqa' ),
				__( 'on', 'dwqa' ),
				get_permalink(),
				date( 'M d Y', get_post_time( 'U', false, $answer ) ),
				( time() - get_post_time( 'U', false, $answer ) ) > 60 * 60 * 24 * 2 ? '' : ' at ' . human_time_diff( get_post_time( 'U', false, $answer ) ) . ' ago',
				substr( get_the_content(), 0 , 140 ) . ' ...',
				dwqa_answer_row_actions( $actions )
			);
			break;
		case 'dwqa-question':
			$question_id = get_post_meta( $post_ID, '_question', true );
			$question = get_post( $question_id );
			echo '<a href="' . get_permalink( $question_id ) . '" >' . $question->post_title . '</a><br>'; 
			break;
	}
} 
add_action( 'manage_dwqa-answer_posts_custom_column', 'dwqa_answer_columns_content', 10, 2 );


// Init script for back-end
function dwqa_admin_enqueue_scripts() {
	$screen = get_current_screen();
	if ( 'dwqa-question_page_dwqa-settings' == $screen->id ) {
		wp_enqueue_media();
		wp_enqueue_script( 'dwqa-settings', DWQA_URI . 'assets/js/admin-settings-page.js', array( 'jquery' ) );
		wp_localize_script( 'dwqa-settings', 'dwqa', array(
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'template_folder'   => DWQA_URI . 'inc/templates/email/',
			'reset_permission_confirm_text'  => __( 'Reset all changes to default', 'dwqa' )
		) );
	}
	if ( 'dwqa-question' == get_post_type() || 'dwqa-answer' == get_post_type() || 'dwqa-question_page_dwqa-settings' == $screen->id ) {
		wp_enqueue_style( 'dwqa-admin-style' , DWQA_URI . 'assets/css/admin-style.css' );
	}
}
add_action( 'admin_enqueue_scripts', 'dwqa_admin_enqueue_scripts' ); 

// SHOW THE FEATURED IMAGE  
function dwqa_question_columns_content( $column_name, $post_ID ) {  
	switch ( $column_name ) {
		case 'info':
			echo ucfirst( get_post_meta( $post_ID, '_dwqa_status', true ) ) . '<br>';
			echo '<strong>'.dwqa_question_answers_count( $post_ID ) . '</strong> '.__( 'answered', 'dwqa' ) . '<br>';
			echo '<strong>'.dwqa_vote_count( $post_ID ).'</strong> '.__( 'voted', 'dwqa' ) . '<br>';
			echo '<strong>'.dwqa_question_views_count( $post_ID ).'</strong> '.__( 'views', 'dwqa' ) . '<br>';
			break;
		case 'question-category':
			$terms = wp_get_post_terms( $post_ID, 'dwqa-question_category' );
			$i = 0;
			foreach ( $terms as $term ) {
				if ( $i > 0 ) {
					echo ', ';
				}
				echo '<a href="'.get_term_link( $term, 'dwqa-question_category' ).'">'.$term->name . '</a> ';
				$i++;
			}
			break;
		case 'question-tag':
			$terms = wp_get_post_terms( $post_ID, 'dwqa-question_tag' );
			$i = 0;
			foreach ( $terms as $term ) {
				if ( $i > 0 ) {
					echo ', ';
				}
				echo '<a href="'.get_term_link( $term, 'dwqa-question_tag' ).'">' . $term->name . '</a> ';
				$i++;
			}
			break;
	}
} 
add_action( 'manage_dwqa-question_posts_custom_column', 'dwqa_question_columns_content', 10, 2 );  

function dwqa_content_start_wrapper() {
	dwqa_load_template( 'content', 'start-wrapper' );
	echo '<div class="dwqa-container" >';
}
add_action( 'dwqa_before_page', 'dwqa_content_start_wrapper' );

function dwqa_content_end_wrapper() {
	echo '</div>';
	dwqa_load_template( 'content', 'end-wrapper' );
	wp_reset_query();
}
add_action( 'dwqa_after_page', 'dwqa_content_end_wrapper' );

function dwqa_has_sidebar_template() {
	global $dwqa_options, $dwqa_template;
	$template = get_stylesheet_directory() . '/dwqa-templates/';
	if ( is_single() && file_exists( $template . '/sidebar-single.php' ) ) {
		include $template . '/sidebar-single.php';
		return;
	} elseif ( is_single() ) { 
		if ( file_exists( DWQA_DIR . 'inc/templates/'.$dwqa_template.'/sidebar-single.php' ) ) {
			include DWQA_DIR . 'inc/templates/'.$dwqa_template.'/sidebar-single.php';
		} else {
			get_sidebar();
		}
		return;
	}

	return;
}

function dwqa_related_question( $question_id = false, $number = 5 ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$tag_in = $cat_in = array();
	$tags = wp_get_post_terms( $question_id, 'dwqa-question_tag' );
	if ( ! empty($tags) ) {
		foreach ( $tags as $tag ) {
			$tag_in[] = $tag->term_id;
		}   
	}
	
	$category = wp_get_post_terms( $question_id, 'dwqa-question_category' );
	if ( ! empty($category) ) {
		foreach ( $category as $cat ) {
			$cat_in[] = $cat->term_id;
		}    
	}
	$args = array(
		'orderby'       => 'rand',
		'post__not_in'  => array($question_id),
		'showposts'     => $number,
		'ignore_sticky_posts' => 1,
		'post_type'     => 'dwqa-question',
	);

	$args['tax_query']['relation'] = 'OR';
	if ( ! empty( $cat_in ) ) {
		$args['tax_query'][] = array(
			'taxonomy'  => 'dwqa-question_category',
			'field'     => 'id',
			'terms'     => $cat_in,
			'operator'  => 'IN',
		);
	}
	if ( ! empty( $tag_in ) ) {
		$args['tax_query'][] = array(
			'taxonomy'  => 'dwqa-question_tag',
			'field'     => 'id',
			'terms'     => $tag_in,
			'operator'  => 'IN',
		);
	}

	$related_questions = new WP_Query( $args );
	
	if ( $related_questions->have_posts() ) {
		echo '<ul>';
		while ( $related_questions->have_posts() ) { $related_questions->the_post();
			echo '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__( 'asked by', 'dwqa' ).' ';
			the_author_posts_link();
			echo '</li>';
		}
		echo '</ul>';
	}
	wp_reset_postdata();
}

function dwqa_get_following_user( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$followers = get_post_meta( $question_id, '_dwqa_followers' );
	
	if ( empty( $followers ) ) {
		return false;
	}
	
	return $followers;
}

?>
