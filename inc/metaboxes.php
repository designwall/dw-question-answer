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

/**
 * Add metabox for question status meta data
 * @return void
 */
function dwqa_add_status_metabox(){
    add_meta_box( 'dwqa-post-status', __('Question Meta Data','dwqa'), 'dwqa_question_status_box_html', 'dwqa-question', 'side', 'high' );
}
add_action( 'admin_init', 'dwqa_add_status_metabox' );

/**
 * Generate html for metabox of question status meta data
 * @param  object $post Post Object
 * @return void       
 */
function dwqa_question_status_box_html($post){
        $meta = get_post_meta( $post->ID, '_dwqa_status', true );
        $meta = $meta ? $meta : 'open';
    ?>
    <p>
        <label for="dwqa-question-status">
            <?php _e('Status','dwqa') ?><br>&nbsp;
            <select name="dwqa-question-status" id="dwqa-question-status" class="widefat">
                <option <?php selected( $meta, 'open' ); ?> value="open"><?php _e('Open','dwqa') ?></option>
                <option <?php selected( $meta, 'pending' ); ?> value="pending"><?php _e('Pending','dwqa') ?></option>
                <option <?php selected( $meta, 'resolved' ); ?> value="resolved"><?php _e('Resolved','dwqa') ?></option>
                <option <?php selected( $meta, 're-open' ); ?> value="re-open"><?php _e('Re-Open','dwqa') ?></option>
                <option <?php selected( $meta, 'closed' ); ?> value="closed"><?php _e('Closed','dwqa') ?></option>
            </select>
        </label>
    </p>    
    <p>
    	<label for="dwqa-question-sticky">
    		<?php _e('Sticky','dwqa'); ?><br><br>&nbsp;
    		<?php
        		$sticky_questions = get_option( 'dwqa_sticky_questions', array() );
    		?>
    		<input <?php checked( true, in_array( $post->ID, $sticky_questions), true ); ?> type="checkbox" name="dwqa-question-sticky" id="dwqa-question-sticky" value="1" ><span class="description"><?php _e('Sticky question on top of archive page.','dwqa'); ?></span>
    	</label>
    </p>
    <?php
}



function dwqa_question_status_save($post_id){
    if( ! wp_is_post_revision( $post_id ) ) {
        if( isset($_POST['dwqa-question-status']) ) {
            update_post_meta( $post_id, '_dwqa_status', $_POST['dwqa-question-status'] );
        }
        if( ! defined('DOING_AJAX') || ! DOING_AJAX ) {

            $sticky_questions = get_option( 'dwqa_sticky_questions', array() );
            if( isset($_POST['dwqa-question-sticky']) && $_POST['dwqa-question-sticky'] ) {
                if( ! in_array( $post_id, $sticky_questions) ) {
                    $sticky_questions[] = $post_id;
                    update_option( 'dwqa_sticky_questions', $sticky_questions );
                }
            } else {
                if( in_array( $post_id, $sticky_questions) ) {
                    if( ($key = array_search($post_id, $sticky_questions) ) !== false) {
                        unset($sticky_questions[$key]);
                    }
                    update_option( 'dwqa_sticky_questions', $sticky_questions );
                }
            }
        }
    }
}
add_action( 'save_post', 'dwqa_question_status_save' );


?>