<?php  
/**
 *  A tool for import question answer just for designwall.com, will be removed when public as plugin
 *  @version  1.0
 */

function dwqa_tool_menu(){
    add_submenu_page( 
        'edit.php?post_type=dwqa-question', 
        'DW Convert Data', 
        'DW Convert', 
        'manage_options', 
        'dwqa-convert', 
        'dwqa_convert_page' 
    );
}   
add_action( 'admin_menu', 'dwqa_tool_menu' );

function dwqa_pre_content($content){
    global $post_submit_filter;
    $content = htmlspecialchars_decode($content);
    $content = wp_kses( dwqa_pre_content_filter( $content ), $post_submit_filter ) ;
    return $content;
}
function dwqa_convert_page(){
        global $post_submit_filter;

        if( isset($_POST['action']) && 'dwqa_convert_data' == $_POST['action']  ) {
            if( ! isset($_POST['_wpnonce']) && ! wp_verify_nonce( $_POST['_wp_nonce'], '_dwqa_convert_data_from_designwall' ) ){
                return;
            }
                $questions = get_posts(  array(
                'numberposts'        =>    -1,
                'offset'            =>    0,
                'post_type'            =>    'question',
                'post_status'        =>    'publish' 
                )
            );
            global $wpdb;

            foreach ($questions as $q ) {
                $wpdb->query( $wpdb->prepare(
                    'SELECT ID FROM ' . $wpdb->posts . '
                    WHERE post_title = %s
                    AND post_type = \'dwqa-question\' AND post_status = \'publish\' ',
                    $q->post_title
                ) );
                if( $wpdb->num_rows > 0 ) {
                    //continue;
                }

                // Get comment 
                $comments = get_comments( array( 
                    'post_id' => $q->ID
                 ) );
                $answer = array();
                $answer_comment = array();
                $question_comment = array();

                foreach ( $comments as $c ) {
                    $comment_type = get_comment_meta( $c->comment_ID,'new_comment_type',true );

                    if( 'question_comment' == $comment_type ) {
                        // Is question comment
                        $question_comment[] = $c;
                    } else {
                        if( $c->comment_parent == 0 ) {
                            // Is answer
                            $answer[] = $c;
                        } else {
                            // Is answer comment
                            $answer_comment[$c->comment_parent][] = $c;
                        }
                    }
                    
                }


                // Not customize for user info because this was developed for specify site like Designwall that have customer's infomation was not change
                $new_question = get_object_vars( $q );
                $status = get_post_meta( $new_question['ID'], '_question_status', true );
                switch ($status) {
                    case 'resolved':
                        $status = 'resolved';
                        break;
                    
                    case 'hold':
                        $status = 'pending';
                        break;
                    
                    default:
                        $status = 'open';
                        break;
                }
                //Tag
                $old_qus_tags = wp_get_post_terms( $new_question['ID'], 'question_tag' );
                $qus_tags = array();
                foreach ($old_qus_tags as $tag ) {
                    $qus_tags[] = $tag->name;
                }
                //category
                $old_qus_cats = wp_get_post_terms( $new_question['ID'], 'question_category' );
                $qus_cats = array();
                foreach ( $old_qus_cats as $cat ) {
                    $qus_cats[] = $cat->name;
                }
                $old_views = (int) get_post_meta( $new_question['ID'], 'views', true );
                unset($new_question['ID']);
                $new_question['post_type'] = 'dwqa-question';
                $new_question['comment_count'] = count( $question_comment );
                $new_question['tax_input'] = array(
                        'dwqa-question_tag'             => $qus_tags,
                        'dwqa-question_category'        => $qus_cats
                    );
                $new_question['post_content'] = dwqa_pre_content( $new_question['post_content'] );

                $question_id = wp_insert_post( $new_question, true );
                if( ! is_wp_error( $question_id ) ) {
                    update_post_meta( $question_id, '_dwqa_status', $status );
                    update_post_meta( $question_id, '_dwqa_views', $old_views );
                    //Insert question comment
                    foreach ( $question_comment as $qc ) {
                        $qus_cmt = get_object_vars($qc);
                        unset($qus_cmt['comment_ID']);
                        $qus_cmt['comment_post_ID'] = $question_id;
                        wp_insert_comment( $qus_cmt );
                    }
                    // Insert answer
                    foreach ($answer as $a) {

                        $new_answer = wp_insert_post( array(
                            'comment_status' => 'open',
                            'post_author'    => $a->user_id,
                            'post_content'   => dwqa_pre_content( $a->comment_content ),
                            'post_status'    => 'publish',
                            'post_title'     => __( 'Answer for ', 'dwqa' ) . $new_question['post_title'],
                            'post_type'      => 'dwqa-answer',
                            'post_date'      => $a->comment_date
                        ), true );

                        if( ! is_wp_error( $new_answer ) ) {
                            update_post_meta( $new_answer, '_question', $question_id );
                            //Insert comment for answer
                            if( ! empty($answer_comment[$a->comment_ID]) ) {

                                foreach ( $answer_comment[$a->comment_ID] as $a_cmt ) {
                                    $ans_cmt = get_object_vars($a_cmt);
                                    unset($ans_cmt['comment_ID']);
                                    $ans_cmt['comment_post_ID'] = $new_answer;
                                    wp_insert_comment( $ans_cmt );
                                }

                            }
                        }
                    }
                }//End for comment and answer
            }
        }
    ?>
    <div class="wrap">
        <h2>Convert DesignWall Data</h2>
        <p class="description">
            Convert all data from old question &amp; answer system of DesignWall.com
        </p>
        <form action="" method="post">
            <p>
                <label for="option-clear-old-data">
                    <input type="checkbox" name="option-clear-old-data" id="option-clear-old-data"> <span class="description">Clear old data of DW Question Answer</span>
                </label>
            </p>
            <p>
                <?php wp_nonce_field( '_dwqa_convert_data_from_designwall' ) ?>
                <input type="hidden" name="action" value="dwqa_convert_data">
                <?php submit_button( 'Start' ) ?>
            </p>
        </form>
    </div>
    <?php
}


?>