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

include_once DWQA_DIR  . 'inc/cache.php';

include_once DWQA_DIR  . 'inc/deprecated.php';







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

  

?>
