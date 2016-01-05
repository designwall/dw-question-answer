<?php  
/**
 *  Plugin Name: DW Question Answer
 *  Description: A WordPress plugin was make by DesignWall.com to build an Question Answer system for support, asking and comunitcate with your customer 
 *  Author: DesignWall
 *  Author URI: http://www.designwall.com
 *  Version: 1.3.6
 *  Text Domain: dwqa
 *  @since 1.3.5
 */

// DWQA plugin dir path
if ( ! defined( 'DWQA_DIR' ) ) {
	define( 'DWQA_DIR', plugin_dir_path( __FILE__ ) );
}
// DWQA plguin dir URI
if ( ! defined( 'DWQA_URI' ) ) {
	define( 'DWQA_URI', plugin_dir_url( __FILE__ ) );
}

// These lines just is used to back to older version of this plugin
// require_once DWQA_DIR . 'dw-question-answer-bak.php';
// return;

// Add autoload class
require_once DWQA_DIR . 'inc/autoload.php';
require_once DWQA_DIR . 'inc/helper/functions.php';
require_once DWQA_DIR . 'upgrades/upgrades.php';
require_once DWQA_DIR . 'inc/deprecated.php';

class DW_Question_Answer {
	private $last_update = 151220151200; //last update time of the plugin

	public function __construct() {
		$this->dir = DWQA_DIR;
		$this->uri = DWQA_URI;
		
		// Add recaptcha library from google, 99 to sure that the library was not include if any other plugins use same library
		add_action( 'plugins_loaded', array( $this, 'include_recaptcha_library' ), 99 );
		// load posttype
		$this->question = new DWQA_Posts_Question();
		$this->answer = new DWQA_Posts_Answer();
		$this->comment = new DWQA_Posts_Comment();
		$this->permission = new DWQA_Permission();
		$this->status = new DWQA_Status();
		$this->shortcode = new DWQA_Shortcode();
		$this->template = new DWQA_Template();
		$this->settings = new DWQA_Settings();
		$this->editor = new DWQA_Editor();
		$this->rewrite = new DWQA_Rewrite();
		$this->user = new DWQA_User();
		$this->notifications = new DWQA_Notifications();
		$this->filter = new DWQA_Filter();

		$this->metaboxes = new DWQA_Metaboxes();

		$this->helptab = new DWQA_Helptab();
		$this->pointer_helper = new DWQA_PointerHelper();

		// All init action of plugin will be included in
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate_hook' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_hook' ) );

		//Widgets
		//add_action( 'widgets_init', create_function( '', "register_widget( 'DWQA_Widgets_Latest_Question' );" ) );
		//add_action( 'widgets_init', create_function( '', "register_widget( 'DWQA_Widgets_Closed_Question' );" ) );
		//add_action( 'widgets_init', create_function( '', "register_widget( 'DWQA_Widgets_Popular_Question' );" ) );
		//add_action( 'widgets_init', create_function( '', "register_widget( 'DWQA_Widgets_Related_Question' );" ) );
	}

	public function include_recaptcha_library() {
		if ( ! defined( 'RECAPTCHA_VERIFY_SERVER' ) ) {
			require_once DWQA_DIR  . 'lib/recaptcha-php/recaptchalib.php';
		}
	}

	public function init() {
		global $dwqa_sript_vars, $dwqa_template, $dwqa_general_settings;

		$active_template = $this->template->get_template();
		//Load translate text domain
		load_plugin_textdomain( 'dwqa', false,  plugin_basename( dirname( __FILE__ ) )  . '/languages' );
		//Scripts var
		
		$question_category_rewrite = $dwqa_general_settings['question-category-rewrite'];
		$question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
		$question_tag_rewrite = $dwqa_general_settings['question-tag-rewrite'];
		$question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
		$dwqa_sript_vars = array(
			'is_logged_in'  => is_user_logged_in(),
			'plugin_dir_url' => DWQA_URI,
			'code_icon'     => DWQA_URI . 'inc/templates/' . $active_template . '/assets/img/icon-code.png',
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
			'question_category_rewrite' => $question_category_rewrite,//$question_category_rewrite,
			'question_tag_rewrite'      => $question_tag_rewrite, //$question_tag_rewrite,
			'delete_question_confirm' => __( 'Do you want to delete this question?', 'dwqa' )
		);
	}

	// Update rewrite url when active plugin
	public function activate_hook() {
		$this->permission->prepare_permission_caps();
		
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

		// dwqa_posttype_init();
		flush_rewrite_rules();
	}

	public function deactivate_hook() {
		$this->permission->remove_permision_caps();

		wp_clear_scheduled_hook( 'dwqa_hourly_event' );

		flush_rewrite_rules();
	}

	public function get_last_update() {
		return $this->last_update;
	}

}
$GLOBALS['dwqa'] = new DW_Question_Answer();
