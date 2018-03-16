<?php
/**
 *  Plugin Name: DW Question Answer
 *  Description: A WordPress plugin was make by DesignWall.com to build an Question Answer system for support, asking and comunitcate with your customer
 *  Author: DesignWall
 *  Author URI: http://www.designwall.com
 *  Version: 1.5.1
 *  Text Domain: dwqa
 *  @since 1.4.0
 */

if ( !class_exists( 'DW_Question_Answer' ) ) :

class DW_Question_Answer {
	private $last_update = 180720161355; //last update time of the plugin

	public function __construct() {
		$this->define_constants();
		$this->includes();

		$this->dir = DWQA_DIR;
		$this->uri = DWQA_URI;
		$this->temp_dir = DWQA_TEMP_DIR;
		$this->temp_uri = DWQA_TEMP_URL;
		$this->stylesheet_dir = DWQA_STYLESHEET_DIR;
		$this->stylesheet_uri = DWQA_STYLESHEET_URL;

		$this->version = '1.5.1';

		// load posttype
		$this->question = new DWQA_Posts_Question();
		$this->answer = new DWQA_Posts_Answer();
		$this->comment = new DWQA_Posts_Comment();
		$this->ajax = new DWQA_Ajax();
		$this->handle = new DWQA_Handle();
		$this->permission = new DWQA_Permission();
		$this->status = new DWQA_Status();
		$this->shortcode = new DWQA_Shortcode();
		$this->template = new DWQA_Template();
		$this->settings = new DWQA_Settings();
		$this->editor = new DWQA_Editor();
		$this->user = new DWQA_User();
		$this->notifications = new DWQA_Notifications();
		
		$this->akismet = new DWQA_Akismet();
		$this->autoclosure = new DWQA_Autoclosure();
		
		$this->filter = new DWQA_Filter();
		$this->session = new DWQA_Session();

		$this->metaboxes = new DWQA_Metaboxes();

		$this->helptab = new DWQA_Helptab();
		$this->pointer_helper = new DWQA_PointerHelper();

		new DWQA_Admin_Extensions();
		new DWQA_Admin_Welcome();

		// All init action of plugin will be included in
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		add_filter( 'plugin_action_links', array( $this, 'go_pro' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_rows_meta' ), 10, 2 );
		register_activation_hook( __FILE__, array( $this, 'activate_hook' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_hook' ) );
		
		add_action( 'bp_include', array($this,'dwqa_setup_buddypress'), 10 );
	}
	
	public function dwqa_setup_buddypress(){
		// Include the BuddyPress Component
		require( DWQA_DIR . 'inc/extend/buddypress/loader.php' );
		
		// Instantiate BuddyPress for bbPress
		$this->DWQA_Buddypress = new DWQA_QA_Component();	
	}

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	public function includes() {
		require_once DWQA_DIR . 'inc/autoload.php';
		require_once DWQA_DIR . 'inc/helper/functions.php';
		//require_once DWQA_DIR . 'upgrades/upgrades.php';
		require_once DWQA_DIR . 'inc/deprecated.php';
		require_once DWQA_DIR . 'inc/helper/plugin-compatibility.php';
		require_once DWQA_DIR . 'inc/helper/theme-compatibility.php';

		require_once DWQA_DIR . 'inc/widgets/Closed_Question.php';
		require_once DWQA_DIR . 'inc/widgets/Latest_Question.php';
		require_once DWQA_DIR . 'inc/widgets/Popular_Question.php';
		require_once DWQA_DIR . 'inc/widgets/Related_Question.php';
	}

	public function define_constants() {
		$defines = array(
			'DWQA_DIR' => plugin_dir_path( __FILE__ ),
			'DWQA_URI' => plugin_dir_url( __FILE__ ),
			'DWQA_TEMP_DIR' => trailingslashit( get_template_directory() ),
			'DWQA_TEMP_URL' => trailingslashit( get_template_directory_uri() ),
			'DWQA_STYLESHEET_DIR' => trailingslashit( get_stylesheet_directory() ),
			'DWQA_STYLESHEET_URL' => trailingslashit( get_stylesheet_directory_uri() ),
		);

		foreach( $defines as $k => $v ) {
			if ( !defined( $k ) ) {
				define( $k, $v );
			}
		}
	}

	public function widgets_init() {
		$widgets = array(
			'DWQA_Widgets_Closed_Question',
			'DWQA_Widgets_Latest_Question',
			'DWQA_Widgets_Popular_Question',
			'DWQA_Widgets_Related_Question'
		);

		foreach( $widgets as $widget ) {
			register_widget( $widget );
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
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
		);

		$this->flush_rules();
	}

	// Update rewrite url when active plugin
	public function activate_hook() {
		$this->permission->prepare_permission_caps();

		flush_rewrite_rules();
		//Auto create question page
		$options = get_option( 'dwqa_options' );

		if ( ! isset( $options['pages']['archive-question'] ) || ( isset( $options['pages']['archive-question'] ) && ! get_post( $options['pages']['archive-question'] ) ) ) {
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

		if ( ! isset( $options['pages']['submit-question'] ) || ( isset( $options['pages']['submit-question'] ) && ! get_post( $options['pages']['submit-question'] ) ) ) {

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
		update_option( 'dwqa_plugin_activated', true );
		// dwqa_posttype_init();
	}

	public function deactivate_hook() {
		$this->permission->remove_permision_caps();

		wp_clear_scheduled_hook( 'dwqa_hourly_event' );

		flush_rewrite_rules();
	}

	public function flush_rules() {
		if ( get_option( 'dwqa_plugin_activated', false ) || get_option( 'dwqa_plugin_upgraded', false ) ) {
			delete_option( 'dwqa_plugin_upgraded' );
			flush_rewrite_rules();
		}
	}

	public function get_last_update() {
		return $this->last_update;
	}

	public function go_pro( $actions, $file ) {
		$file_name = plugin_basename( __FILE__ );
		if ( $file == $file_name ) {
			$actions['dwqa_go_pro'] = '<a href="http://bit.ly/dwqa-pro" style="color: red; font-weight: bold">Go Pro!</a>';
			$action = $actions['dwqa_go_pro'];
			unset( $actions['dwqa_go_pro'] );
			array_unshift( $actions, $action );
		}

		return $actions;
	}

	public function plugin_rows_meta( $meta, $file ) {
		$file_name = plugin_basename( __FILE__ );
		if ( $file == $file_name ) {
			$meta['extensions'] = '<a href="'.admin_url( 'edit.php?post_type=dwqa-question&page=dwqa-extensions' ).'">Extensions</a>';
			// $meta['facebook'] = '<a href="">Facebook</a>';
		}

		return $meta;
	}
}

function dwqa() {
	return DW_Question_Answer::instance();
}

$GLOBALS['dwqa'] = dwqa();

endif;