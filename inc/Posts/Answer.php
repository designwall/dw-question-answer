<?php  

class DWQA_Posts_Answer extends DWQA_Posts_Base {

	public function __construct() {
		parent::__construct( 'dwqa-answer', array(
			'plural' => __( 'Answers', 'dwqa' ),
			'singular' => __( 'Answer', 'dwqa' ),
			'menu' => __( 'All answers', 'dwqa' ),
		) );
	}

	// Remove default menu and change it to submenu of questions
	public function set_show_in_menu() {
		global $dwqa;
		return 'edit.php?post_type=' . $dwqa->question->get_slug();
	}

	public function set_supports() {
		return array( 
			'title', 'editor', 'comments', 
			'custom-fields', 'author', 'page-attributes',
		);
	}

	public function set_has_archive() {
		return false;
	}
}

?>