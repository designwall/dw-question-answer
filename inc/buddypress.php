<?php

// function delete folder and file in that folder
if ( !function_exists('delete_directory') )
{

function delete_directory($dirPath) {
    if (is_dir($dirPath)) {
        $objects = scandir($dirPath);
        foreach ($objects as $object) {
            if ($object != "." && $object !="..") {
                if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                    deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
    reset($objects);
    rmdir($dirPath);
    }
}
}

//funtion create question tab on your bp profile page
if ( !function_exists('create_bbcustom') )
{
    function create_bbcustom()
    {
         $themeDir =  get_template_directory();
         if (!file_exists( get_template_directory().'/buddypress')) {
         mkdir(get_template_directory().'/buddypress', 0777, true);
            }

       
        $customThemeFile = 'bp-custom.php';
        $my_theme = wp_get_theme();
        $theme_folder_name = $my_theme->get( 'TextDomain' );
        $stringData = '<?php
if ( function_exists(\'dwqa_plugin_init\') && function_exists(\'bp_is_active\')) {
  /*-----------------------------------------------------------------------------------*/
  /*  Setup Questions in BuddyPress User Profile
  /*-----------------------------------------------------------------------------------*/
  add_action( \'bp_setup_nav\', \''.$theme_folder_name.'_bp_nav_adder\' );
  function '.$theme_folder_name.'_bp_nav_adder() {
     bp_core_new_nav_item(
      array(
        \'name\' => __(\'Questions\', \''.$theme_folder_name.'\'),
        \'slug\' => \'questions\',
        \'position\' => 21,
        \'show_for_displayed_user\' => true,
        \'screen_function\' => \'questions_list\',
        \'item_css_id\' => \'questions\',
        \'default_subnav_slug\' => \'public\'
      ));
  }

  function questions_list() {
    add_action( \'bp_template_content\', \'profile_questions_loop\' );
    bp_core_load_template( apply_filters( \'bp_core_template_plugin\', \'members/single/plugins\' ) );
  }

  function profile_questions_loop() {
    global $dwqa_options;
    $submit_question_link = get_permalink( $dwqa_options[\'pages\'][\'submit-question\'] );
    $questions = get_posts(  array(
      \'posts_per_page\' => -1,
      \'author\'         => bp_displayed_user_id(),
      \'post_type\'      => \'dwqa-question\'
    ));
    if( ! empty($questions) ) { ?>
      <div class="dwqa-container">
        <div class="dwqa-list-question">
          <div class="dw-question" id="archive-question">
            <?php foreach ($questions as $q) { ?>
            <article id="question-18267" class="dwqa-question">
                <header class="dwqa-header">
                    <a class="dwqa-title" href="<?php echo get_post_permalink($q->ID); ?>" title="Permalink to <?php echo $q->post_title ?>" rel="bookmark"><?php echo $q->post_title ?></a>
                    <div class="dwqa-meta">
                        <?php dwqa_question_print_status($q->ID); ?>
                        <span><?php echo get_the_time( \'M d, Y, g:i a\', $q->ID ); ?></span>
                        &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                        <?php echo get_the_term_list( $q->ID, \'dwqa-question_category\', \'<span>Category: \', \', \', \'</span>\' ); ?> 
                    </div>
                </header>
            </article>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } else { ?>
    <div class="info" id="message">
      <?php if( get_current_user_id() == bp_displayed_user_id() ) : ?>
        Why don\'t you have question for us. <a href="<?php echo $submit_question_link ?>">Start asking</a>!
      <?php else : ?>
        <p><strong><?php bp_displayed_user_fullname(); ?></strong> has not asked any question.</p>
      <?php endif; ?>
    </div>
    <?php }
  }

  /*-----------------------------------------------------------------------------------*/
  /*  Record Activities
  /*-----------------------------------------------------------------------------------*/
  // Question
  function '.$theme_folder_name.'_record_question_activity( $post_id ) {
    $post = get_post($post_id);
    if(($post->post_status != \'publish\') && ($post->post_status != \'private\'))
      return;

    $user_id = get_current_user_id();
    $post_permalink = get_permalink( $post_id );
    $post_title = get_the_title( $post_id );
    $activity_action = sprintf( __( \'%s asked a new question: %s\', \''.$theme_folder_name.'\' ), bp_core_get_userlink( $user_id ), \'<a href="\' . $post_permalink . \'">\' . $post->post_title . \'</a>\' );
    $content = $post->post_content;
    $hide_sitewide = ($post->post_status == \'private\') ? true : false;

    bp_blogs_record_activity( array(
      \'user_id\' => $user_id,
      \'action\' => $activity_action,
      \'content\' => $content,
      \'primary_link\' => $post_permalink,
      \'type\' => \'new_blog_post\',
      \'item_id\' => 0,
      \'secondary_item_id\' => $post_id,
      \'recorded_time\' => $post->post_date_gmt,
      \'hide_sitewide\' => $hide_sitewide,
    ));
  }
  add_action( \'dwqa_add_question\', \''.$theme_folder_name.'_record_question_activity\');

  //Answer
  function '.$theme_folder_name.'_record_answer_activity( $post_id ) {
    $post = get_post($post_id);

    if($post->post_status != \'publish\')
      return;

    $user_id = $post->post_author;

    $question_id = get_post_meta( $post_id, \'_question\', true );
    $question = get_post( $question_id );

    $post_permalink = get_permalink($question_id);
    $activity_action = sprintf( __( \'%s answered the question: %s\', \''.$theme_folder_name.'\' ), bp_core_get_userlink( $user_id ), \'<a href="\' . $post_permalink . \'">\' . $question->post_title . \'</a>\' );
    $content = $post->post_content;

    $hide_sitewide = ($question->post_status == \'private\') ? true : false;

    bp_blogs_record_activity( array(
      \'user_id\' => $user_id,
      \'action\' => $activity_action,
      \'content\' => $content,
      \'primary_link\' => $post_permalink,
      \'type\' => \'new_blog_post\',
      \'item_id\' => 0,
      \'secondary_item_id\' => $post_id,
      \'recorded_time\' => $post->post_date_gmt,
      \'hide_sitewide\' => $hide_sitewide,
    ));
  }
  add_action( \'dwqa_add_answer\', \''.$theme_folder_name.'_record_answer_activity\');
  add_action( \'dwqa_update_answer\', \''.$theme_folder_name.'_record_answer_activity\');

  //Comment
  function '.$theme_folder_name.'_record_comment_activity( $comment_id ) {
    $comment = get_comment($comment_id);
    $user_id = get_current_user_id();
    $post_id = $comment->comment_post_ID;
    $content = $comment->comment_content;

    if(get_post_type($post_id) == \'dwqa-question\') {
      $post = get_post( $post_id );
      $post_permalink = get_permalink( $post_id );
      $activity_action = sprintf( __( \'%s commented on the question: %s\', \''.$theme_folder_name.'\' ), bp_core_get_userlink( $user_id ), \'<a href="\' . $post_permalink . \'">\' . $post->post_title . \'</a>\' );
      $hide_sitewide = ($post->post_status == \'private\') ? true : false;
    } else {
      $post = get_post( $post_id );
      $question_id = get_post_meta( $post_id, \'_question\', true );
      $question = get_post( $question_id );
      $post_permalink = get_permalink( $question_id );
      $activity_action = sprintf( __( \'%s commented on the answer at: %s\', \''.$theme_folder_name.'\' ), bp_core_get_userlink( $user_id ), \'<a href="\' . $post_permalink . \'">\' . $question->post_title . \'</a>\' );
      $hide_sitewide = ($question->post_status == \'private\') ? true : false;
    }

    bp_blogs_record_activity( array(
      \'user_id\' => $user_id,
      \'action\' => $activity_action,
      \'content\' => $content,
      \'primary_link\' => $post_permalink,
      \'type\' => \'new_blog_comment\',
      \'item_id\' => 0,
      \'secondary_item_id\' => $comment_id,
      \'recorded_time\' => $comment->comment_date_gmt,
      \'hide_sitewide\' => $hide_sitewide,
    ));
  }
  add_action( \'dwqa_add_comment\', \''.$theme_folder_name.'_record_comment_activity\');
}';

        $fh = fopen(get_template_directory().'/buddypress'. '/' . $customThemeFile, 'w') or die('cant open file');
        fwrite($fh, $stringData);
        fclose($fh);
    }

}

// function use bp-custom.php and redirect to user’s profile
if ( !function_exists('append_string_2_functions') )
{
    function append_string_2_functions()
    {

        $my_theme = wp_get_theme();
        $theme_folder_name = $my_theme->get( 'TextDomain' );

        $stringData = '<?php include_once get_template_directory().\'/buddypress/bp-custom.php\';';
        $stringData .= 'add_action( \'template_redirect\', \''.$theme_folder_name.'_redirect_author_archive_to_profile\' );
function '.$theme_folder_name.'_redirect_author_archive_to_profile() {
if(is_author()){
$user_id = get_query_var( \'author\' );
wp_redirect( bp_core_get_user_domain( $user_id ) );
}
}?>';
        $old_string = file_get_contents(get_template_directory().'/functions.php');
        $old_string .= $stringData;
        unlink(get_template_directory().'/functions.php');
        $fh = fopen(get_template_directory().'/functions.php', 'w') or die('cant open file');
        fwrite($fh, $old_string);
        fclose($fh);
    }
}


if ( !function_exists('remove_bbcustom') )
{
    function remove_bbcustom()
    {
        delete_directory(get_template_directory().'/buddypress');

        
    }
}


if ( !function_exists('replace_string_functions') )
{
    function replace_string_functions()
    {
       $my_theme = wp_get_theme();
        $theme_folder_name = $my_theme->get( 'TextDomain' );

        $stringData = '<?php include_once get_template_directory().\'/buddypress/bp-custom.php\';';
        $stringData .= 'add_action( \'template_redirect\', \''.$theme_folder_name.'_redirect_author_archive_to_profile\' );
function '.$theme_folder_name.'_redirect_author_archive_to_profile() {
if(is_author()){
$user_id = get_query_var( \'author\' );
wp_redirect( bp_core_get_user_domain( $user_id ) );
}
}?>';

        $old_string = file_get_contents(get_template_directory().'/functions.php');
        $file_contents = str_replace($stringData,"",$old_string);
        file_put_contents(get_template_directory().'/functions.php',$file_contents);
    }
}

?>