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
			if ( $question_id ) {
				$question = get_post( $question_id );
				echo '<a href="' . get_permalink( $question_id ) . '" >' . $question->post_title . '</a><br>';
			} 
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
