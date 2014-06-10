<div id="submit-question" class="dwqa-submit-question">    
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
        <div class="question-advance">
            <div class="question-meta">
                <?php if(!is_user_logged_in() && $dwqa_options['enable-anon-name']) : ?>
                <div class="anon_name">
                        <label for="question-anon-user"><?php _e('Please add your name','dwqa') ?></label>
                        <input type="text" name="question-anon-user" id="question-anon-user" placeholder="<?php _e('Anonymous','dwqa') ?>" autocomplete="off" />
                </div>
                <?php endif; ?>
                <div class="select-category">
                    <label for="question-category"><?php _e('Question Category','dwqa') ?></label>
                    <?php  
                        wp_dropdown_categories( array( 
                            'name'          => 'question-category',
                            'id'            => 'question-category',
                            'taxonomy'      => 'dwqa-question_category',
                            'show_option_none' => __('Select question category','dwqa'),
                            'hide_empty'    => 0,
                            'quicktags'     => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,spell,close' ),
                            'selected'      => (isset( $_POST['question-category'] ) ? stripslashes(htmlentities($_POST['question-category'])) : false)
                        ) );
                    ?>
                </div>   
                <div class="input-tag">
                    <label for="question-tag"><?php _e('Question Tags','dwqa') ?></label>
                    <input type="text" name="question-tag" id="question-tag" placeholder="<?php _e('tag 1, tag 2,...','dwqa') ?>" value="<?php echo isset( $_POST['question-tag'] ) ? stripslashes(htmlentities($_POST['question-tag'])) : ''; ?>" />
                </div>
            </div>
        </div>
        <div class="input-title">
            <label for="question-title"><?php _e('Your question','dwqa') ?> *</label>
            <input type="text" name="question-title" id="question-title" placeholder="<?php _e('How to...','dwqa') ?>" autocomplete="off" data-nonce="<?php echo wp_create_nonce( '_dwqa_filter_nonce' ) ?>" value="<?php echo isset( $_POST['question-title'] ) ? stripslashes(htmlentities($_POST['question-title'])) : ''; ?>" />
            <span class="dwqa-search-loading dwqa-hide"></span>
            <span class="dwqa-search-clear fa fa-times dwqa-hide"></span>
        </div>  
            
        <div class="question-advance">
            <div class="input-content">
                <label for="question-content"><?php _e('Question details','dwqa') ?></label>
                <?php 
                    dwqa_init_tinymce_editor( array( 
                            'content' => ( isset( $_POST['question-content'] ) ? stripslashes(htmlentities($_POST['question-content'])) : '' ),
                            'id' => 'dwqa-question-content-editor', 
                            'textarea_name' => 'question-content',
                            'media_buttons' => true
                    ) ); 
                ?>
            </div>
            
            <?php if( isset($dwqa_options['enable-private-question']) && $dwqa_options['enable-private-question'] ) : ?>
            <div class="checkbox-private">
                <label for="private-message"><input type="checkbox" name="private-message" id="private-message" value="true"> <?php _e('Post this Question as Private.','dwqa') ?> <i class="fa fa-question-circle" title="<?php _e('Only you as Author and Admin can see the question', 'dwqa') ?>"></i></label>
            </div>
            <?php endif; ?>
            <div class="question-signin">
                <?php do_action( 'dwqa_submit_question_ui' ); ?>
            </div>
            <script type="text/javascript">
             var RecaptchaOptions = {
                theme : 'clean'
             };
             </script>
            <?php  
                global  $dwqa_general_settings;
                if( dwqa_is_captcha_enable_in_submit_question() ) {
                    $public_key = isset($dwqa_general_settings['captcha-google-public-key']) ?  $dwqa_general_settings['captcha-google-public-key'] : '';
                    echo '<div class="google-recaptcha">';
                    echo recaptcha_get_html($public_key);
                    echo '<br></div>';
                }
            ?>
            
        </div>
        <div class="form-submit">
            <input type="submit" value="<?php _e('Ask Question','dwqa') ?>" class="dwqa-btn dwqa-btn-success btn-submit-question" />
        </div>  
    </form>
</div>
