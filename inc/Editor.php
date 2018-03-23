<?php  

function dwqa_init_tinymce_editor( $args = array() ) {
	global $dwqa;
	$dwqa->editor->display( $args );
}

function dwqa_paste_srtip_disable( $mceInit ){
	$mceInit['paste_strip_class_attributes'] = 'none';
	return $mceInit;
}

class DWQA_Editor {

	public function __construct() {

		add_action( 'init', array( $this, 'tinymce_addbuttons' ) );

		add_filter( 'dwqa_prepare_edit_answer_content', 'wpautop' );
		add_filter( 'dwqa_prepare_edit_question_content', 'wpautop' );
	}
	
	public function tinymce_addbuttons() {
		if ( get_user_option( 'rich_editing' ) == 'true' && ! is_admin() ) {
			add_filter( 'mce_external_plugins', array( $this, 'add_custom_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'register_custom_button' ) );
		}
	}

	public function register_custom_button( $buttons ) {
		array_push( $buttons, '|', 'dwqaCodeEmbed' );
		return $buttons;
	} 

	public function add_custom_tinymce_plugin( $plugin_array ) {
		global $dwqa_options;
		if ( is_singular( 'dwqa-question' ) || ( $dwqa_options['pages']['submit-question'] && is_page( $dwqa_options['pages']['submit-question'] ) ) ) {
			$plugin_array['dwqaCodeEmbed'] = DWQA_URI . 'assets/js/code-edit-button.js';
		}
		return $plugin_array;
	}
	public function display( $args ) {
		extract( wp_parse_args( $args, array(
				'content'       => '',
				'id'            => 'dwqa-custom-content-editor',
				'textarea_name' => 'custom-content',
				'rows'          => 5,
				'wpautop'       => false,
				'media_buttons' => false,
		) ) );

		$dwqa_tinymce_css = apply_filters( 'dwqa_editor_style', DWQA_URI . 'templates/assets/css/editor-style.css' );
		$toolbar1 = apply_filters( 'dwqa_tinymce_toolbar1', 'bold,italic,underline,|,' . 'bullist,numlist,blockquote,|,' . 'link,unlink,|,' . 'image,code,|,'. 'spellchecker,fullscreen,dwqaCodeEmbed,|,' );
		wp_editor( $content, $id, array(
			'wpautop'       => $wpautop,
			'media_buttons' => $media_buttons,
			'textarea_name' => $textarea_name,
			'textarea_rows' => $rows,
			'tinymce' => array(
					'toolbar1' => $toolbar1,
					'toolbar2'   => '',
					'content_css' => $dwqa_tinymce_css
			),
			'quicktags'     => true,
		) );
	}

	public function toolbar_buttons() {

	}
}

?>