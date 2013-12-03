<div id="submit-question" class="dw-question">    
    <?php  
        global $dwqa_options, $dwqa_current_error;

        if( is_wp_error( $dwqa_current_error ) ) {
            $error_messages = $dwqa_current_error->get_error_messages();
            
            if( !empty($error_messages) ) {
                echo '<div class="alert alert-error">';
                foreach ($error_messages as $message) {
                    echo $message;
                }
                echo '</div>';
            }
        }
    ?>
    <form action="" name="dwqa-submit-question-form" id="dwqa-submit-question-form" method="post">

        <div class="question-meta">
            <div class="select-category">
                <label for="question-category"><?php _e('Question Category','dwqa') ?></label>
                <?php  
                    wp_dropdown_categories( array( 
                        'name'          => 'question-category',
                        'id'            => 'question-category',
                        'taxonomy'      => 'dwqa-question_category',
                        'show_option_none' => __('Select question category','dwqa'),
                        'hide_empty'    => 0,
                        'quicktags'     => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' )
                    ) );
                ?>
            </div>   
            <div class="input-tag">
                <label for="question-tag"><?php _e('Question Tags','dwqa') ?></label>
                <input type="text" name="question-tag" id="question-tag" placeholder="<?php _e('tag 1, tag 2,...','dwqa') ?>" />
            </div>
        </div>
        <div class="input-title">
            <label for="question-title"><?php _e('Your question','dwqa') ?> *</label>
            <input type="text" name="question-title" id="question-title" placeholder="<?php _e('How to...','dwqa') ?>" autocomplete="off" data-nonce="<?php echo wp_create_nonce( '_dwqa_filter_nonce' ) ?>" />
            <span class="dwqa-search-loading hide"></span>
            <span class="dwqa-search-clear icon-remove hide"></span>
        </div>  
            
        <div class="input-content">
            <label for="question-content"><?php _e('Question details','dwqa') ?></label>
            <?php dwqa_init_tinymce_editor( array( 'id' => 'dwqa-question-content-editor', 'textarea_name' => 'question-content' ) ); ?>
        </div>
        
        <div class="checkbox-private">
            <label for="private-message"><input type="checkbox" name="private-message" id="private-message" value="true"> <?php _e('Post this Question as Private.','dwqa') ?> <i class="icon-question-sign" title="<?php _e(' Only we and you can read the question. No one else can!', 'dwqa') ?>"></i></label>
        </div>

        <div class="question-signin">
            <?php do_action( 'dwqa_submit_question_ui' ); ?>
        </div>

        <div class="form-submit">
            <input type="submit" class="btn" value="<?php _e('Ask Question','dwqa') ?>" class="btn btn-submit-question" />
        </div>  
    </form>
</div>