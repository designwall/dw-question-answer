<?php  
/**
 * 
 */
if( ! function_exists('dwqa_archive_question') ) {
    function dwqa_archive_question(){
        query_posts( 'post_type=dwqa-question' );
        require dwqa_load_template( 'archive', 'question', false );
        wp_reset_query();
    }
    add_shortcode( 'dwqa_list_questions', 'dwqa_archive_question' );
}

if( ! function_exists('dwqa_submit_question_form') ) {
    function dwqa_submit_question_form(){
        
        $dwqa = array(
            'code_icon'    => DWQA_URI . 'assets/img/icon-code.png',
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'text_next'     => __('Next','dwqa'),
            'text_prev'     => __('Prev','dwqa'),
            'questions_archive_link'    => get_post_type_archive_link( 'dwqa-question' ),
            'error_missing_question_content'    =>  __( 'Please enter your question', 'dwqa' ),
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
        wp_enqueue_script( 'dwqa-submit-question', DWQA_URI . 'assets/js/dwqa-submit-question.js', array( 'jquery' ) );
        wp_localize_script( 'dwqa-submit-question', 'dwqa', $dwqa );
        require dwqa_load_template( 'submit-question', 'form', false );
    }
    add_shortcode( 'dwqa_submit_question_form', 'dwqa_submit_question_form' );
}



?>