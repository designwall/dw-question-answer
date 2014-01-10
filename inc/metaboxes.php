<?php  

//Add a metabox that was used for display list of answers of a questions
function dwqa_answers_metabox(){
	add_meta_box( 'dwqa-answers', __('Answers','dwqa'),
					'dwqa_metabox_answers_list', 'dwqa-question' );
}
add_action( 'add_meta_boxes', 'dwqa_answers_metabox' );

/**
 * generate html for metabox that was used for display list of answers of a questions
 */
function dwqa_metabox_answers_list(){
	$answer_list_table = new DWQA_answer_list_table();
	$answer_list_table->display();
}

function dwqa_add_css_class_metabox( $classes ){
    $classes[] = 'dwqa-answer-list';
    return $classes;
}
add_filter( 'postbox_classes_dwqa-question_dwqa-answers', 'dwqa_add_css_class_metabox' );

?>