<?php  


// Callback for dwqa-general-settings Option
function dwqa_question_registration_setting_display(){
    global  $dwqa_general_settings;
    ?>
    <p><input type="checkbox" name="dwqa_options[answer-registration]" value="true" <?php checked( true, isset($dwqa_general_settings['answer-registration']) ? (bool) $dwqa_general_settings['answer-registration'] : false ) ; ?> id="dwqa_option_answer_registation">
    <label for="dwqa_option_answer_registation"><span class="description"><?php _e('Login required. No anonymous allowed','dwqa'); ?></span></label></p>
    <?php
}

function dwqa_pages_settings_display(){
    global  $dwqa_general_settings;
    $archive_question_page = isset($dwqa_general_settings['pages']['archive-question']) ? $dwqa_general_settings['pages']['archive-question'] : 0; 
    ?>
    <p>
        <?php
            wp_dropdown_pages( array(
                'name'              => 'dwqa_options[pages][archive-question]',
                'show_option_none'  => __('Select Archive Question Page','dwqa'),
                'option_none_value' => 0,
                'selected'          => $archive_question_page
            ) );
        ?><span class="description"><?php _e('A page where displays all questions','dwqa') ?></span>
    </p>
    <?php
}

function dwqa_question_new_time_frame_display(){ 
    global  $dwqa_general_settings;
    echo '<p><input type="text" name="dwqa_options[question-new-time-frame]" id="dwqa_options_question_new_time_frame" value="'.( isset( $dwqa_general_settings['question-new-time-frame'] ) ? $dwqa_general_settings['question-new-time-frame'] : 4 ).'" class="small-text" /><span class="description"> '.__('hours','dwqa').'<span title="'.__('A period of time in which new questions are highlighted and marked as New','dwqa').'">(?)</span></span></p>';
}

function dwqa_question_overdue_time_frame_display(){ 
    global  $dwqa_general_settings;
    echo '<p><input type="text" name="dwqa_options[question-overdue-time-frame]" id="dwqa_options_question_new_time_frame" value="'.( isset( $dwqa_general_settings['question-overdue-time-frame'] ) ? $dwqa_general_settings['question-overdue-time-frame'] : 2 ).'" class="small-text" /><span class="description"> '.__('days','dwqa').'<span title="'.__('A Question will be marked as overdue if passes this period of time, starting from the date the question was submitted','dwqa').'">(?)</span></span></p>';
}

function dwqa_submit_question_page_display(){
    global  $dwqa_general_settings;
    $submit_question_page = isset($dwqa_general_settings['pages']['submit-question']) ? $dwqa_general_settings['pages']['submit-question'] : 0; 
    ?>
    <p>
        <?php
            wp_dropdown_pages( array(
                'name'              => 'dwqa_options[pages][submit-question]',
                'show_option_none'  => __('Select Submit Question Page','dwqa'),
                'option_none_value' => 0,
                'selected'          => $submit_question_page
            ) );
        ?>
        <span class="description"><?php _e('A page where users can submit questions.','dwqa') ?></span>
    </p>
    <?php
}
function dwqa_email_template_settings_display(){
    global $dwqa_options;
    $editor_content = isset( $dwqa_options['subscribe']['email-template'] ) ? $dwqa_options['subscribe']['email-template'] : '';
    wp_editor( $editor_content, 'dwqa_email_template_editor', array(
        'textarea_name' => 'dwqa_options[subscribe][email-template]'
    ) );
}


function dwqa_subscrible_email_logo_display(){
    ?>
    <div class="uploader">
        <p><input type="text" name="dwqa_subscrible_email_logo" id="dwqa_subscrible_email_logo" class="regular-text" value="<?php echo  get_option( 'dwqa_subscrible_email_logo' ); ?>" />&nbsp;<input type="button" class="button" name="dwqa_subscrible_email_logo_button" id="dwqa_subscrible_email_logo_button" value="<?php _e('Upload','dwqa') ?>" /><span class="description">&nbsp;<?php _e('Upload or choose a logo to be displayed at the top of the email','dwqa') ?></span></p>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($){
      var _custom_media = true,
          _orig_send_attachment = wp.media.editor.send.attachment;

      $('#dwqa_subscrible_email_logo_button').click(function(e) {
        var send_attachment_bkp = wp.media.editor.send.attachment;
        var button = $(this);
        var id = button.attr('id').replace('_button', '');
        _custom_media = true;
        wp.media.editor.send.attachment = function(props, attachment){
          if ( _custom_media ) {
            $("#"+id).val(attachment.url);

            if( $( "#"+id ).closest('.uploader').find('.logo-preview').length > 0 ) {
                $( "#"+id ).closest('.uploader').find('.logo-preview img').attr('src', attachment.url);
            }else {
                $( "#"+id ).closest('.uploader').append('<p class="logo-preview"><img src="'+attachment.url+'"></p>')
            }
          } else {
            return _orig_send_attachment.apply( this, [props, attachment] );
          };
        }

        wp.media.editor.open(button);
        return false;
      });

      $('.add_media').on('click', function(){
        _custom_media = false;
      });
    });
    </script>
    <?php
}

function dwqa_subscrible_enable_new_question_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_question_notification"><input type="checkbox" value="1" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_question_notification', 1), false ).' name="dwqa_subscrible_enable_new_question_notification" id="dwqa_subscrible_enable_new_question_notification" >'.__('Enable notification for new question','dwqa').'</label></p>';
}
// New Question - Enable Notification

function dwqa_subscrible_new_question_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_question_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_question_email_subject" name="dwqa_subscrible_new_question_email_subject" value="'.get_option( 'dwqa_subscrible_new_question_email_subject' ).'" class="widefat" /></span></p>';
}
// New Question - Email subject

function dwqa_subscrible_new_question_email_display(){
    echo '<label for="dwqa_subscrible_new_question_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_question_email', 'new-question' );
    wp_editor( $content, 'dwqa_subscrible_new_question_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-template="new-question.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new question on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{user_avatar}</strong> - Question Author Avatar <br />
        <strong>{username}</strong> - Question Author Name <br />
        <strong>{user_link}</strong> - Question Author Posts Link<br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{question_content}</strong> - Question Content <br />
    </div>';
    echo '</label>';
}
// New Question - Email Content


function dwqa_subscrible_enable_new_answer_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_answer_notification"><input type="checkbox" value="1" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_answer_notification', 1 ), false ).' name="dwqa_subscrible_enable_new_answer_notification" id="dwqa_subscrible_enable_new_answer_notification" >'.__('Enable notification for new answer','dwqa').'</label></p>';
}
// New Answer - Enable Notification

function dwqa_subscrible_new_answer_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_answer_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_answer_email_subject" name="dwqa_subscrible_new_answer_email_subject" value="'.get_option( 'dwqa_subscrible_new_answer_email_subject' ).'" class="widefat" /></span></p>';
}
// New Answer - Email Subject

function dwqa_subscrible_new_answer_email_display(){
    echo '<label for="dwqa_subscrible_new_answer_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_email', 'new-answer' );
    wp_editor( $content, 'dwqa_subscrible_new_answer_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-template="new-answer.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new answer on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{site_description}</strong> - Your site description <br />
        <strong>{answer_avatar}</strong> - Answer Author Avatar <br />
        <strong>{answer_author}</strong> - Answer Author Name <br />
        <strong>{answer_author_link}</strong> - Answer Author Link <br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{answer_content}</strong> - Answer Content <br />

    </div>';
    echo '</label>';
}
// New Answer - Email Content

function dwqa_subscrible_enable_new_answer_followers_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_answer_followers_notification"><input type="checkbox" value="1" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_answer_followers_notification', 1 ), false ).' name="dwqa_subscrible_enable_new_answer_followers_notification" id="dwqa_subscrible_enable_new_answer_followers_notification" >'.__('Enable notification for new answer (to Followers)','dwqa').'</label></p>';
}
// New Answer - Follow - Enable Notification

function dwqa_subscrible_new_answer_followers_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_answer_followers_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_answer_followers_email_subject" name="dwqa_subscrible_new_answer_followers_email_subject" value="'.get_option( 'dwqa_subscrible_new_answer_followers_email_subject' ).'" class="widefat" /></span></p>';
}
// New Answer - Follow - Email Subject

function dwqa_subscrible_new_answer_followers_email_display(){
    echo '<label for="dwqa_subscrible_new_answer_followers_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_followers_email', 'new-answer-followers' );
    wp_editor( $content, 'dwqa_subscrible_new_answer_followers_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-template="new-answer-followers.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new answer on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{site_description}</strong> - Your site description <br />
        <strong>{answer_avatar}</strong> - Answer Author Avatar <br />
        <strong>{answer_author}</strong> - Answer Author Name <br />
        <strong>{answer_author_link}</strong> - Answer Author Link <br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{answer_content}</strong> - Answer Content <br />

    </div>';
    echo '</label>';
}
// New Answer - Follow - Email Content

function dwqa_subscrible_enable_new_comment_question_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_comment_question_notification"><input type="checkbox" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_comment_question_notification', 1 ), false ).' value="1" name="dwqa_subscrible_enable_new_comment_question_notification" id="dwqa_subscrible_enable_new_comment_question_notification" >'.__('Enable notification for new comment of question','dwqa').'</label></p>';
}
// New Comment - Question - Enable Notification

function dwqa_subscrible_new_comment_question_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_comment_question_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_comment_question_email_subject" name="dwqa_subscrible_new_comment_question_email_subject" value="'.get_option( 'dwqa_subscrible_new_comment_question_email_subject' ).'" class="widefat" /></label></p>';
}
// New Comment - Question - Email subject

function dwqa_subscrible_new_comment_question_email_display(){
    echo '<label for="dwqa_subscrible_new_comment_question_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_question_email', 'new-comment-question' );
    wp_editor( $content, 'dwqa_subscrible_new_comment_question_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-editor="dwqa_subscrible_new_comment_question_email" data-template="new-comment-question.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new answer on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{site_description}</strong> - Your site description <br />
        <strong>{question_author}</strong> - Question Author Name <br />
        <strong>{comment_author}</strong> - Comment Author Name <br />
        <strong>{comment_author_avatar}</strong> - Comment Author Avatar <br />
        <strong>{comment_author_link}</strong> - Comment Author Link <br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{comment_content}</strong> - Comment Content <br />
    </div>';
    echo '</label>';
}
// New Comment - Question - Email Content

function dwqa_subscrible_enable_new_comment_question_followers_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_comment_question_followers_notify"><input type="checkbox" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_comment_question_followers_notify', 1 ), false ).' value="1" name="dwqa_subscrible_enable_new_comment_question_followers_notify" id="dwqa_subscrible_enable_new_comment_question_followers_notify" >'.__('Enable notification for new comment of question','dwqa').'</label></p>';
}
// New Comment - Question - Follow - Enable Notification

function dwqa_subscrible_new_comment_question_followers_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_comment_question_followers_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_comment_question_followers_email_subject" name="dwqa_subscrible_new_comment_question_followers_email_subject" value="'.get_option( 'dwqa_subscrible_new_comment_question_followers_email_subject' ).'" class="widefat" /></label></p>';
}
// New Comment - Question - Follow - Email subject

function dwqa_subscrible_new_comment_question_followers_email_display(){
    echo '<label for="dwqa_subscrible_new_comment_question_followers_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_question_followers_email', 'new-comment-question-followers' );
    wp_editor( $content, 'dwqa_subscrible_new_comment_question_followers_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-template="new-comment-question-followers.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new answer on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{site_description}</strong> - Your site description <br />
        <strong>{question_author}</strong> - Question Author Name <br />
        <strong>{comment_author}</strong> - Comment Author Name <br />
        <strong>{comment_author_avatar}</strong> - Comment Author Avatar <br />
        <strong>{comment_author_link}</strong> - Comment Author Link <br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{comment_content}</strong> - Comment Content <br />
    </div>';
    echo '</label>';
}
// New Comment - Question - Follow - Email Content

function dwqa_subscrible_enable_new_comment_answer_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_comment_answer_notification"><input type="checkbox" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_comment_answer_notification', 1 ), false ).' value="1" name="dwqa_subscrible_enable_new_comment_answer_notification" id="dwqa_subscrible_enable_new_comment_answer_notification" >'.__('Enable notification for new comment of answer','dwqa').'</label></p>';
}
// New Comment - Answer - Enable Notification

function dwqa_subscrible_new_comment_answer_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_comment_answer_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_comment_answer_email_subject" name="dwqa_subscrible_new_comment_answer_email_subject" value="'.get_option( 'dwqa_subscrible_new_comment_answer_email_subject' ).'" class="widefat" /></label></p>';
}
// New Comment - Answer - Email Subject

function dwqa_subscrible_new_comment_answer_email_display(){
    echo '<label for="dwqa_subscrible_new_comment_answer_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_answer_email', 'new-comment-answer' );
    wp_editor( $content, 'dwqa_subscrible_new_comment_answer_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-template="new-comment-answer.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new answer on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{site_description}</strong> - Your site description <br />
        <strong>{answer_author}</strong> - Answer Author Name <br />
        <strong>{comment_author}</strong> - Comment Author Name <br />
        <strong>{comment_author_avatar}</strong> - Comment Author Avatar <br />
        <strong>{comment_author_link}</strong> - Comment Author Link <br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{comment_content}</strong> - Comment Content <br />
    </div>';
    echo '</label>';
}
// New Comment - Answer - Email Content

function dwqa_subscrible_enable_new_comment_answer_followers_notification(){
    echo '<p><label for="dwqa_subscrible_enable_new_comment_answer_followers_notification"><input type="checkbox" '.checked( 1, get_option( 'dwqa_subscrible_enable_new_comment_answer_followers_notification', 1 ), false ).' value="1" name="dwqa_subscrible_enable_new_comment_answer_followers_notification" id="dwqa_subscrible_enable_new_comment_answer_followers_notification" >'.__('Enable notification for new comment of answer','dwqa').'</label></p>';
}
// New Comment - Answer - Follow - Enable Notification

function dwqa_subscrible_new_comment_answer_followers_email_subject_display(){ 
    echo '<p><label for="dwqa_subscrible_new_comment_answer_followers_email_subject">'.__('Email subject','dwqa').'<br><input type="text" id="dwqa_subscrible_new_comment_answer_followers_email_subject" name="dwqa_subscrible_new_comment_answer_followers_email_subject" value="'.get_option( 'dwqa_subscrible_new_comment_answer_followers_email_subject' ).'" class="widefat" /></label></p>';
}
// New Comment - Answer - Follow - Email Subject

function dwqa_subscrible_new_comment_answer_followers_email_display(){
    echo '<label for="dwqa_subscrible_new_comment_answer_followers_email">'.__('Email Content','dwqa').'<br>';
    $content = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_answer_followers_email', 'new-comment-answer-followers' );
    wp_editor( $content, 'dwqa_subscrible_new_comment_answer_followers_email', array(
        'wpautop'   => false,
        'tinymce' => array( 
            'content_css' => DWQA_URI . 'assets/css/email-template-editor.css'
        ) 
    ) );
    echo '<p><input data-template="new-comment-answer-followers.html" type="button" class="button dwqa-reset-email-template" value="Reset Template"></p>';
    echo '<div class="description">
        Enter the email that is sent to Administrator when have new answer on your site. HTML is accepted. Available template settings:<br>
        <strong>{site_logo}</strong> - Your site logo <br />
        <strong>{site_name}</strong> - Your site name <br />
        <strong>{site_description}</strong> - Your site description <br />
        <strong>{answer_author}</strong> - Answer Author Name <br />
        <strong>{comment_author}</strong> - Comment Author Name <br />
        <strong>{comment_author_avatar}</strong> - Comment Author Avatar <br />
        <strong>{comment_author_link}</strong> - Comment Author Link <br />
        <strong>{question_title}</strong> - Question Title <br />
        <strong>{question_link}</strong> - Question Link <br />
        <strong>{comment_content}</strong> - Comment Content <br />
    </div>';
    echo '</label>';
}
// New Comment - Answer - Follow - Email Content

// End email setting html 

function dwqa_question_rewrite_display(){
    global  $dwqa_general_settings;
    echo '<p><input type="text" name="dwqa_options[question-rewrite]" id="dwqa_options_question_rewrite" value="'.( isset( $dwqa_general_settings['question-rewrite'] ) ? $dwqa_general_settings['question-rewrite'] : 'question' ).'" class="regular-text" /></p>';
}

function dwqa_question_category_rewrite_display(){
    global  $dwqa_general_settings;
    echo '<p><input type="text" name="dwqa_options[question-category-rewrite]" id="dwqa_options_question_category_rewrite" value="'.( isset( $dwqa_general_settings['question-category-rewrite'] ) ? $dwqa_general_settings['question-category-rewrite'] : 'question-category' ).'" class="regular-text" /></p>';
}

function dwqa_question_tag_rewrite_display(){
    global  $dwqa_general_settings;
    echo '<p><input type="text" name="dwqa_options[question-tag-rewrite]" id="dwqa_options_question_tag_rewrite" value="'.( isset( $dwqa_general_settings['question-tag-rewrite'] ) ? $dwqa_general_settings['question-tag-rewrite'] : 'question-tag' ).'" class="regular-text" /></p>';
}

function dwqa_permission_display(){
    global $dwqa_permission;
    $perms = $dwqa_permission->perms;
    ?>
    <input type="hidden" id="reset-permission-nonce" name="reset-permission-nonce" value="<?php echo wp_create_nonce( '_dwqa_reset_permission' ); ?>">
    <h3><?php _e('Question','dwqa') ?></h3>
    <table class="table widefat dwqa-permission-settings">
        <thead>
            <tr>
                <th width="20%"></th>
                <th><?php _e('Read','dwqa') ?></th>
                <th><?php _e('Post','dwqa') ?></th>
                <th><?php _e('Edit','dwqa') ?></th>
                <th><?php _e('Delete','dwqa') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php  
                $roles = get_editable_roles();
                foreach ($dwqa_permission->defaults as $key => $role) {
                    if( $key == 'anonymous' ) {
                        continue;
                    }
            ?>
            <tr class="group available">
                <td><?php echo $roles[$key]['name'] ?></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['question']['read'] ); ?> name="dwqa_permission[<?php echo $key ?>][question][read]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['question']['post'] ); ?> name="dwqa_permission[<?php echo $key ?>][question][post]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['question']['edit'] ); ?> name="dwqa_permission[<?php echo $key ?>][question][edit]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['question']['delete'] ); ?> name="dwqa_permission[<?php echo $key ?>][question][delete]" value="1"></td>
               
            </tr>
            <?php
                }
            ?>
            <tr class="group available">
                <td><?php _e('Anonymous','dwqa') ?></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['question']['read'] ); ?> name="dwqa_permission[<?php echo 'anonymous' ?>][question][read]" value="1"></td>
                <td><input type="checkbox" disabled="disabled" name="dwqa_permission[<?php echo 'anonymous' ?>][question][post]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['question']['edit'] ); ?> disabled="disabled" name="dwqa_permission[<?php echo 'anonymous' ?>][question][edit]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['question']['delete'] ); ?> disabled="disabled"  name="dwqa_permission[<?php echo 'anonymous' ?>][question][delete]" value="1"></td>
            </tr>
        </tbody>
    </table>
    <p class="reset-button-container align-right" style="text-align:right">
        <button data-type="question" class="button reset-permission"><?php _e( 'Reset Default', 'dwqa' ); ?></button>
    </p>
    <h3><?php _e( 'Answer', 'dwqa' ); ?></h3>
    <table class="table widefat dwqa-permission-settings">
        <thead>
            <tr>
                <th width="20%"></th>
                <th>Read</th>
                <th>Post</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php  
                $roles = get_editable_roles();
                foreach ($dwqa_permission->defaults as $key => $role) {
                    if( $key == 'anonymous' ) {
                        continue;
                    }
            ?>
            <tr class="group available">
                <td><?php echo $roles[$key]['name'] ?></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['answer']['read'] ); ?> name="dwqa_permission[<?php echo $key ?>][answer][read]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['answer']['post'] ); ?> name="dwqa_permission[<?php echo $key ?>][answer][post]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['answer']['edit'] ); ?> name="dwqa_permission[<?php echo $key ?>][answer][edit]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['answer']['delete'] ); ?> name="dwqa_permission[<?php echo $key ?>][answer][delete]" value="1"></td>
            </tr>
            <?php
                }
            ?>
            <tr class="group available">
                <td><?php _e('Anonymous','dwqa') ?></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['answer']['read'] ); ?> name="dwqa_permission[<?php echo 'anonymous' ?>][answer][read]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['answer']['post'] ); ?> name="dwqa_permission[<?php echo 'anonymous' ?>][answer][post]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['answer']['edit'] ); ?> disabled="disabled"  name="dwqa_permission[<?php echo 'anonymous' ?>][answer][edit]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['answer']['delete'] ); ?> disabled="disabled"  name="dwqa_permission[<?php echo $key ?>][answer][delete]" value="1"></td>
            </tr>
        </tbody>
    </table>
    <p class="reset-button-container align-right" style="text-align:right">
        <button data-type="answer" class="button reset-permission"><?php _e( 'Reset Default', 'dwqa' ); ?></button>
    </p>
    <h3><?php _e('Comment','dwqa') ?></h3>
    <table class="table widefat dwqa-permission-settings">
        <thead>
            <tr>
                <th width="20%"></th>
                <th>Read</th>
                <th>Post</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php  
                $roles = get_editable_roles();
                foreach ($dwqa_permission->defaults as $key => $role) {
                    if( $key == 'anonymous' ) {
                        continue;
                    }
            ?>
            <tr class="group available">
                <td><?php echo $roles[$key]['name'] ?></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['comment']['read'] ); ?> name="dwqa_permission[<?php echo $key ?>][comment][read]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['comment']['post'] ); ?> name="dwqa_permission[<?php echo $key ?>][comment][post]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['comment']['edit'] ); ?> name="dwqa_permission[<?php echo $key ?>][comment][edit]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms[$key]['comment']['delete'] ); ?> name="dwqa_permission[<?php echo $key ?>][comment][delete]" value="1"></td>
            </tr>
            <?php
                }
            ?>
            <tr class="group available">
                <td><?php _e('Anonymous','dwqa') ?></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['comment']['read'] ); ?> name="dwqa_permission[<?php echo 'anonymous' ?>][comment][read]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['comment']['post'] ); ?> name="dwqa_permission[<?php echo 'anonymous' ?>][comment][post]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['comment']['edit'] ); ?> disabled="disabled"  name="dwqa_permission[<?php echo 'anonymous' ?>][comment][edit]" value="1"></td>
                <td><input type="checkbox" <?php checked( true, $perms['anonymous']['comment']['delete'] ); ?> disabled="disabled"  name="dwqa_permission[<?php echo $key ?>][comment][delete]" value="1"></td>
            </tr>
        </tbody>
    </table>

    <p class="reset-button-container align-right" style="text-align:right">
        <button data-type="comment" class="button reset-permission"><?php _e( 'Reset Default', 'dwqa' ); ?></button>
    </p>
    <?php
}

//Captcha
function dwqa_captcha_in_question_display() {
    global $dwqa_general_settings;

    echo '<p><input type="checkbox" name="dwqa_options[captcha-in-question]"  id="dwqa_options_captcha_in_question" value="1" '.checked( 1, (isset($dwqa_general_settings['captcha-in-question']) ? $dwqa_general_settings['captcha-in-question'] : false) , false ) .'><span class="description">'.__('Enable/Disable captcha on submit question page','dwqa').'</span></p>';
}

function dwqa_captcha_in_single_question_display() {
    global $dwqa_general_settings;
    
    echo '<p><input type="checkbox" name="dwqa_options[captcha-in-single-question]"  id="dwqa_options_captcha_in_question" value="1" '.checked( 1, (isset($dwqa_general_settings['captcha-in-single-question']) ? $dwqa_general_settings['captcha-in-single-question'] : false) , false ) .'><span class="description">'.__('Enable/Disable captcha on single question page','dwqa').'</span></p>';
}

function dwqa_captcha_google_pubic_key_display() {
    global $dwqa_general_settings;
    $public_key = isset($dwqa_general_settings['captcha-google-public-key']) ?  $dwqa_general_settings['captcha-google-public-key'] : '';
    echo '<p><input type="text" name="dwqa_options[captcha-google-public-key]" value="'.$public_key.'" class="regular-text"></p>';
}

function dwqa_captcha_google_private_key_display() {
    global $dwqa_general_settings;
    $private_key = isset($dwqa_general_settings['captcha-google-private-key']) ?  $dwqa_general_settings['captcha-google-private-key'] : '';
    echo '<p><input type="text" name="dwqa_options[captcha-google-private-key]" value="'.$private_key.'" class="regular-text"></p>';
}

function dwqa_posts_per_page_display(){
    global $dwqa_general_settings;
    $posts_per_page = isset($dwqa_general_settings['posts-per-page']) ?  $dwqa_general_settings['posts-per-page'] : 5;
    echo '<p><input type="text" name="dwqa_options[posts-per-page]" class="small-text" value="'.$posts_per_page.'" > <span class="description">'.__('questions','dwqa').'</span></p>';
}

function dwqa_enable_private_question_display() {
    global $dwqa_general_settings;
    
    echo '<p><label for="dwqa_options_enable_private_question"><input type="checkbox" name="dwqa_options[enable-private-question]"  id="dwqa_options_enable_private_question" value="1" '.checked( 1, (isset($dwqa_general_settings['enable-private-question']) ? $dwqa_general_settings['enable-private-question'] : false) , false ) .'><span class="description">'.__('Allow members to post private question','dwqa').'</span></label></p>';
}

function dwqa_subscrible_send_to_display(){

}


class DWQA_Settings {
    public function __construct(){

        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_action( 'init', array( $this, 'init_options' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    // Create admin menus for backend
    public function admin_menu(){
        global $dwqa_setting_page;
        $dwqa_setting_page = add_submenu_page( 'edit.php?post_type=dwqa-question', __('Plugin Settings','dwqa'), __('Settings','dwqa'), 'manage_options', 'dwqa-settings', array( $this, 'settings_display' )  );
    }   

    public function init_options(){
        global $dwqa_options;
        $dwqa_options = wp_parse_args( get_option( 'dwqa_options' ), array( 
            'pages'     => array(
                    'submit-question'   => 0,
                    'archive-question'  => 0
                )
        ) );
    }

    public function register_settings(){
        global  $dwqa_general_settings;

        //Register Setting Sections
        add_settings_section( 
            'dwqa-general-settings', 
            false, 
            null, 
            'dwqa-settings' 
        );

        add_settings_field( 
            'dwqa_options[pages][archive-question]', 
            __('Question List Page', 'dwqa'), 
            'dwqa_pages_settings_display', 
            'dwqa-settings', 
            'dwqa-general-settings'
        );

        add_settings_field( 
            'dwqa_options[posts-per-page]', 
            __('Archive page show at most','dwqa'), 
            'dwqa_posts_per_page_display', 
            'dwqa-settings', 
            'dwqa-general-settings' 
        );

        add_settings_field( 
            'dwqa_options[pages][submit-question]', 
            __('Ask Question Page', 'dwqa'), 
            'dwqa_submit_question_page_display', 
            'dwqa-settings', 
            'dwqa-general-settings'
        );
        add_settings_field( 
            'dwqa_options[enable-private-question]', 
            __('Private Question', 'dwqa'), 
            'dwqa_enable_private_question_display', 
            'dwqa-settings', 
            'dwqa-general-settings'
        );
        //Time setting
        add_settings_section( 
            'dwqa-time-settings', 
            __('Time settings','dwqa'), 
            null, 
            'dwqa-settings' 
        );

        add_settings_field( 
            'dwqa_options[question-new-time-frame]', 
            __('New Question Time Frame', 'dwqa'), 
            'dwqa_question_new_time_frame_display', 
            'dwqa-settings', 
            'dwqa-time-settings'
        );

        add_settings_field( 
            'dwqa_options[question-overdue-time-frame]', 
            __('Question Overdue - Time Frame', 'dwqa'), 
            'dwqa_question_overdue_time_frame_display', 
            'dwqa-settings', 
            'dwqa-time-settings'
        );

        //Captcha Setting

        add_settings_section( 
            'dwqa-captcha-settings', 
            __('Captcha settings','dwqa'), 
            null, 
            'dwqa-settings' 
        );

        add_settings_field( 
            'dwqa_options[captcha-in-question]', 
            __('Captcha in Submit Question Page', 'dwqa'), 
            'dwqa_captcha_in_question_display', 
            'dwqa-settings', 
            'dwqa-captcha-settings'
        );

        add_settings_field( 
            'dwqa_options[captcha-in-single-question]', 
            __('Captcha in Single Question Page', 'dwqa'), 
            'dwqa_captcha_in_single_question_display', 
            'dwqa-settings', 
            'dwqa-captcha-settings'
        );

        add_settings_field( 
            'dwqa_options[captcha-google-public-key]', 
            __('Google Captcha Public Key', 'dwqa'), 
            'dwqa_captcha_google_pubic_key_display', 
            'dwqa-settings', 
            'dwqa-captcha-settings'
        );

        add_settings_field( 
            'dwqa_options[captcha-google-private-key]', 
            __('Google Captcha Private Key', 'dwqa'), 
            'dwqa_captcha_google_private_key_display', 
            'dwqa-settings', 
            'dwqa-captcha-settings'
        );


        //Permalink
        add_settings_section( 
            'dwqa-permalink-settings', 
            __('Permalink Settings','dwqa'), 
            create_function('', '_e(\'Custom permalinks for single questions, categories, tags.\',\'dwqa\'); '), 
            'dwqa-settings' 
        );

        add_settings_field( 
            'dwqa_options[question-rewrite]', 
            __('Single Question', 'dwqa'), 
            'dwqa_question_rewrite_display', 
            'dwqa-settings', 
            'dwqa-permalink-settings'
        );

        add_settings_field( 
            'dwqa_options[question-category-rewrite]', 
            __('Single Category', 'dwqa'), 
            'dwqa_question_category_rewrite_display', 
            'dwqa-settings', 
            'dwqa-permalink-settings'
        );

        add_settings_field( 
            'dwqa_options[question-tag-rewrite]', 
            __('Single Tag', 'dwqa'), 
            'dwqa_question_tag_rewrite_display', 
            'dwqa-settings', 
            'dwqa-permalink-settings'
        );

        register_setting( 'dwqa-settings', 'dwqa_options');


        add_settings_section( 
            'dwqa-subscribe-settings', 
            false,
            false,
            'dwqa-email' 
        );

        // Send to address setting
        // add_settings_field( 
        //     'dwqa_subscrible_sendto_address', 
        //     __('Admin Email', 'dwqa'), 
        //     array( $this, 'email_sendto_address_display' ), 
        //     'dwqa-email', 
        //     'dwqa-subscribe-settings'
        // );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_sendto_address');

        // Cc address setting
        // add_settings_field( 
        //     'dwqa_subscrible_cc_address', 
        //     __('Cc', 'dwqa'), 
        //     array( $this, 'email_cc_address_display' ), 
        //     'dwqa-email', 
        //     'dwqa-subscribe-settings'
        // );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_cc_address');

        // Bcc address setting
        // add_settings_field( 
        //     'dwqa_subscrible_bcc_address', 
        //     __('Bcc', 'dwqa'), 
        //     array( $this, 'email_bcc_address_display' ), 
        //     'dwqa-email', 
        //     'dwqa-subscribe-settings'
        // );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_bcc_address');

        // Bcc address setting
        add_settings_field( 
            'dwqa_subscrible_from_address', 
            __('From Email', 'dwqa'), 
            array( $this, 'email_from_address_display' ), 
            'dwqa-email', 
            'dwqa-subscribe-settings'
        );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_from_address');

        // Send copy
        add_settings_field( 
            'dwqa_subscrible_send_copy_to_admin', 
            false, 
            array( $this, 'email_send_copy_to_admin' ), 
            'dwqa-email', 
            'dwqa-subscribe-settings'
        );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_send_copy_to_admin');

        // Logo setting in for email template
        // add_settings_field( 
        //     'dwqa_subscrible_email_logo', 
        //     __('Email Logo', 'dwqa'), 
        //     'dwqa_subscrible_email_logo_display', 
        //     'dwqa-email', 
        //     'dwqa-subscribe-settings'
        // );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_email_logo');

        //New Question Email Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_question_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_question_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_question_notification' );

        // New Answer Email Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_answer_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_answer_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_answer_notification' );
        // New Answer to Followers Email Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_answer_followers_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_answer_followers_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_answer_followers_notification' );

        // New Comment for Question Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_question_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_question_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_comment_question_notification' );

        // New Comment for Question to Followers Email Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_question_followers_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_question_followers_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_comment_question_followers_notify' );

        // New Comment for Answer Email Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_answer_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_answer_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_comment_answer_notification' );

        // New Comment for Answer to Followers Email Notify
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_answer_followers_email_subject' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_new_comment_answer_followers_email' );
        register_setting( 'dwqa-subscribe-settings', 'dwqa_subscrible_enable_new_comment_answer_followers_notification' );


        add_settings_section( 
            'dwqa-permission-settings', 
            __('Group Permission','dwqa'),
            false,
            'dwqa-permission' 
        );

        add_settings_field( 
            'dwqa_permission', 
            __('Group Permission','dwqa'), 
            'dwqa_permission_display', 
            'dwqa-permission', 
            'dwqa-permission-settings' 
        );

        register_setting( 'dwqa-permission-settings', 'dwqa_permission' );    
    }

    public function settings_display(){
        global $dwqa_options;
    ?>
        <div class="wrap">
            <h2><?php _e('DWQA Settings', 'dwqa') ?></h2>
            <?php settings_errors(); ?>  

            <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET['tab'] : 'general'; ?>  
            <h2 class="nav-tab-wrapper">  
                <a href="?post_type=dwqa-question&amp;page=dwqa-settings&amp;tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General','dwqa'); ?></a> 
                <a href="?post_type=dwqa-question&amp;page=dwqa-settings&amp;tab=email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>"><?php _e('Notification','dwqa'); ?></a> 
                <a href="?post_type=dwqa-question&amp;page=dwqa-settings&amp;tab=permission" class="nav-tab <?php echo $active_tab == 'permission' ? 'nav-tab-active' : ''; ?>"><?php _e('Permission','dwqa'); ?></a> 
            </h2>  
              
            <form method="post" action="options.php">  
            <?php  
                if( 'email' == $active_tab ) {
                    echo '<div class="dwqa-notification-settings">';

                    echo '<h3>'.__('Email setup','dwqa').'</h3>';
                    settings_fields( 'dwqa-subscribe-settings' );

                    echo '<table class="form-table"><tr>';
                    echo '<th scope="row">'.__('Email Logo','dwqa').'</th><td>';
                    dwqa_subscrible_email_logo_display();
                    echo '</td></tr></table>';

                    do_settings_sections( 'dwqa-email' );

                    echo '<div class="dwqa-mail-templates">';
                    echo '<div class="progress-bar"><div class="progress-bar-inner"></div></div>';
                    echo '<ul class="nav-tabs">';

                    echo '<li class="active"><a href="#new-question">'.__('New Question', 'dwqa').'</a></li>';
                    echo '<li><a href="#new-answer">'.__('New Answer', 'dwqa').'</a></li>';
                    echo '<li><a href="#new-comment-question">'.__('New Comment to Question', 'dwqa').'</a></li>';
                    echo '<li><a href="#new-comment-answer">'.__('New Comment to Answer', 'dwqa').'</a></li>';
                    echo '<li><a href="#new-answer-followers">'.__('New Answer (to Followers)', 'dwqa').'</a></li>';
                    echo '<li><a href="#new-comment-question-followers">'.__('New Comment to Question (to Followers)', 'dwqa').'</a></li>';
                    echo '<li><a href="#new-comment-answer-followers">'.__('New Comment to Answer (to Followers)', 'dwqa').'</a></li>';

                    echo '</ul>'; // Create default email template

                    echo '<div class="tab-content">'; 

                    echo '<div id="new-question" class="tab-pane active">';
                    echo '<h3>'.__('New Question Notification','dwqa') . '</h3>';
                    $this->email_sendto_address_display();
                    $this->email_cc_address_display();
                    $this->email_bcc_address_display();
                    dwqa_subscrible_enable_new_question_notification();
                    dwqa_subscrible_new_question_email_subject_display();
                    dwqa_subscrible_new_question_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '<hr>';
                    echo '</div>'; //End tab for New Question Notification

                    echo '<div id="new-answer" class="tab-pane">';
                    echo '<h3>'.__('New Answer Notification','dwqa'). '</h3>';
                    dwqa_subscrible_enable_new_answer_notification();
                    dwqa_subscrible_new_answer_email_subject_display();
                    dwqa_subscrible_new_answer_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '<hr>';
                    echo '</div>';//End tab for New Answer Notification

                    echo '<div id="new-answer-followers" class="tab-pane">';
                    echo '<h3>'.__('New Answer Notification (to Followers)','dwqa'). '</h3>';
                    dwqa_subscrible_enable_new_answer_followers_notification();
                    dwqa_subscrible_new_answer_followers_email_subject_display();
                    dwqa_subscrible_new_answer_followers_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '<hr>';
                    echo '</div>';//End tab for New Answer Notification To Followers

                    echo '<div id="new-comment-question" class="tab-pane">';
                    echo '<h3>'.__('New Comment to Question Notification','dwqa'). '</h3>';
                    dwqa_subscrible_enable_new_comment_question_notification();
                    dwqa_subscrible_new_comment_question_email_subject_display();
                    dwqa_subscrible_new_comment_question_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '<hr>';
                    echo '</div>'; //End tab for New Comment to Question Notification


                    echo '<div id="new-comment-answer" class="tab-pane">';
                    echo '<h3>'.__('New Comment to Answer Notification','dwqa'). '</h3>';
                    dwqa_subscrible_enable_new_comment_answer_notification();
                    dwqa_subscrible_new_comment_answer_email_subject_display();
                    dwqa_subscrible_new_comment_answer_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '</div>'; //End tab for New Comment to Answer Notification

                    echo '<div id="new-comment-question-followers" class="tab-pane">';
                    echo '<h3>'.__('New Comment to Question Notification (to Followers)','dwqa'). '</h3>';
                    dwqa_subscrible_enable_new_comment_question_followers_notification();
                    dwqa_subscrible_new_comment_question_followers_email_subject_display();
                    dwqa_subscrible_new_comment_question_followers_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '<hr>';
                    echo '</div>'; //End tab for New Comment to Question Notification


                    echo '<div id="new-comment-answer-followers" class="tab-pane">';
                    echo '<h3>'.__('New Comment to Answer Notification (to Followers)','dwqa'). '</h3>';
                    dwqa_subscrible_enable_new_comment_answer_followers_notification();
                    dwqa_subscrible_new_comment_answer_followers_email_subject_display();
                    dwqa_subscrible_new_comment_answer_followers_email_display();
                    submit_button( __('Save all changes','dwqa') );
                    echo '</div>'; //End tab for New Comment to Answer Notification

                    echo '</div>'; //End wrap mail template settings

                    echo '</div>'; //End wrap tab content

                    echo '</div>'; //The End
                } elseif ( 'permission' == $active_tab ) {
                    settings_fields( 'dwqa-permission-settings' );
                    dwqa_permission_display();
                    submit_button();
                } else {
                    settings_fields( 'dwqa-settings' );
                    do_settings_sections( 'dwqa-settings' );
                    submit_button();
                }
            ?>
            </form>  
        </div>
    <?php
    }

    public function email_sendto_address_display(){
        echo '<p>'.__('Send to', 'dwqa').'</p>';
        $this->input_text_field( 'dwqa_subscrible_sendto_address' );
    }

    public function email_cc_address_display(){
        echo '<p>'.__('Cc', 'dwqa').'</p>';
        $this->input_text_field( 'dwqa_subscrible_cc_address' );
    }

    public function email_bcc_address_display(){
        echo '<p>'.__('Bcc', 'dwqa').'</p>';
        $this->input_text_field( 'dwqa_subscrible_bcc_address' );
    }

    public function email_from_address_display(){
        $this->input_text_field( 'dwqa_subscrible_from_address', false, __('This address will be used as the sender of the outgoing emails.','dwqa') );
    }

    public function email_send_copy_to_admin(){
        $this->input_checkbox_field( 
            'dwqa_subscrible_send_copy_to_admin',
            __('Send A Copy Of Every Email To Admin','dwqa')
        );
    }

    public function input_text_field( $option, $label = false, $description = false, $class = false ){
        echo '<p><label for="'.$option.'"><input type="text" id="'.$option.'" name="'.$option.'" value="'.get_option( $option ).'" class="widefat" / ';
        if( $description ) {
            echo '<span class="description">'.$description.'</span>';
        }
        echo '</label></p>';
    }

    public function input_checkbox_field( $option, $description = false ){
        echo '</p><label for="'.$option.'"><input id="'.$option.'" name="'.$option.'" type="checkbox" '.checked( true, (bool) get_option( $option ), false ).' value="true">';
        if( $description ) {
            echo '<span class="description">'.$description.'</span>';
        }
        echo '</label></p>';
    }
}
$GLOBAL['dwqa-settings'] = new DWQA_Settings();


?>
