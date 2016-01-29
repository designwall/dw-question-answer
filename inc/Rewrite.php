<?php

class DWQA_Rewrite {
	public function __construct() {
		add_action( 'after_switch_theme', 'flush_rewrite_rules' );
	}

	function update_term_rewrite_rules() {
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
				add_rewrite_rule( $regex, $redirect, 'top' );
			}
			// Add permastruct for pretty link
			add_permastruct( 'dwqa-question_category', "{$question_list_page->post_name}/{$rewrite_category}/%dwqa-question_category%", array( 'with_front' => false ) );
			add_permastruct( 'dwqa-question_tag', "{$question_list_page->post_name}/{$rewrite_tag}/%dwqa-question_tag%", array( 'with_front' => false ) );
		}
	}
}
?>
