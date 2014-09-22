<?php

add_action( 'bp_setup_nav', 'dwqa_bp_nav_adder' );
function dwqa_bp_nav_adder() {
   bp_core_new_nav_item(
    array(
      'name' => __( 'Questions', 'dwqa' ),
      'slug' => 'questions',
      'position' => 21,
      'show_for_displayed_user' => true,
      'screen_function' => 'questions_list',
      'item_css_id' => 'questions',
      'default_subnav_slug' => 'public',
    ));
}



function questions_list() {
  add_action( 'bp_template_content', 'profile_questions_loop' );
  bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function profile_questions_loop() {
    global $dwqa_options;
    $submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
    $questions = get_posts(  array(
      'posts_per_page' => 1,
      'author'         => bp_displayed_user_id(),
      'post_type'      => 'dwqa-question',
    ));
    if ( ! empty( $questions ) ) { ?>
      <div class="dwqa-container">
        <div class="dwqa-list-question">
          <div class="dw-question" id="archive-question">
            <?php foreach ( $questions as $q ) { ?>
            <article id="question-18267" class="dwqa-question">
                <header class="dwqa-header">
                    <a class="dwqa-title" href="<?php echo get_post_permalink( $q->ID ); ?>" title="Permalink to <?php echo $q->post_title ?>" rel="bookmark"><?php echo $q->post_title ?></a>
                    <div class="dwqa-meta">
                        <?php dwqa_question_print_status( $q->ID ); ?>
                        <span><?php echo get_the_time( 'M d, Y, g:i a', $q->ID ); ?></span>
                        &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                        <?php echo get_the_term_list( $q->ID, 'dwqa-question_category', '<span>Category: ', ', ', '</span>' ); ?> 
                    </div>
                </header>
            </article>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } else { ?>
    <div class="info" id="message">
      <?php if ( get_current_user_id() == bp_displayed_user_id() ) { ?>
        Why don't you have question for us. <a href="<?php echo $submit_question_link ?>">Start asking</a>!
      <?php } else { ?>
        <p><strong><?php bp_displayed_user_fullname(); ?></strong> has not asked any question.</p>
      <?php } ?>
    </div>
    <?php }
  }

 


/*-----------------------------------------------------------------------------------*/
/*  Redirect author to profile page
/*-----------------------------------------------------------------------------------*/

add_action( 'template_redirect', 'authorblog_template_redirect' );

function authorblog_template_redirect(){
  if ( is_author() ) {
    $user_id = get_query_var( 'author' );
    wp_redirect( bp_core_get_user_domain( $user_id ) );
  }
}

/*-----------------------------------------------------------------------------------*/
/*  Record Activities
/*-----------------------------------------------------------------------------------*/
// Question
  /*-----------------------------------------------------------------------------------*/
  /*  Record Activities
  /*-----------------------------------------------------------------------------------*/
  // Question
  function dwqa_record_question_activity( $post_id ) {
    $post = get_post( $post_id );
    if( ( $post->post_status != 'publish' ) && ( $post->post_status != 'private' ) ) {
        return;
    }

    $user_id = get_current_user_id();
    $post_permalink = get_permalink( $post_id );
    $post_title = get_the_title( $post_id );
    $content = $post->post_content;
    $hide_sitewide = ( $post->post_status == 'private' ) ? true : false;
    bp_blogs_record_activity( array(
      'user_id' => $user_id,
      'content' => $content,
      'primary_link' => $post_permalink,
      'type' => 'new_question',
      'item_id' => 0,
      'secondary_item_id' => $post_id,
      'recorded_time' => $post->post_date_gmt,
      'hide_sitewide' => $hide_sitewide,
    ));
  }
  add_action( 'dwqa_add_question', 'dwqa_record_question_activity' );

  //Answer
  function dwqa_record_answer_activity( $post_id ) {
    $post = get_post( $post_id );

    if ( $post->post_status != 'publish' ) {
      return;
    }

    $user_id = $post->post_author;
    $post_permalink = get_permalink( $question_id );
    $content = $post->post_content;
    $hide_sitewide = ( $question->post_status == 'private' ) ? true : false;

    bp_blogs_record_activity( array(
      'user_id' => $user_id,
      'content' => $content,
      'primary_link' => $post_permalink,
      'type' => 'new_answer',
      'item_id' =>0,
      'secondary_item_id' => $post_id,
      'recorded_time' => $post->post_date_gmt,
      'hide_sitewide' => $hide_sitewide,
    ));
  }
  add_action( 'dwqa_add_answer', 'dwqa_record_answer_activity' );
  add_action( 'dwqa_update_answer', 'dwqa_record_answer_activity' );

  //Comment
  function dwqa_record_comment_activity( $comment_id ) {
  $comment = get_comment( $comment_id );
  $user_id = get_current_user_id();
  $post_id = $comment->comment_post_ID;
  $content = $comment->comment_content;
  $post = get_post( $post_id );
  $hide_sitewide = ( $post->post_status == 'private' ) ? true : false;

  if ( get_post_type($post_id) == 'dwqa-question' ) {
    $type = 'comment_question';
    $post_permalink = get_permalink( $post_id );
  } else {
    $type = 'comment_answer';
    $question_id = get_post_meta( $post_id, '_question', true );
    $question = get_post( $question_id );
    $post_permalink = get_permalink( $question_id );
  }


    bp_blogs_record_activity( array(
      'user_id' => $user_id,
      'action' => $activity_action,
      'content' => $content,
      'primary_link' => $post_permalink,
      'type' => $type,
      'item_id' => 0,
      'secondary_item_id' => $post_id,
      'recorded_time' => $comment->comment_date_gmt,
      'hide_sitewide' => $hide_sitewide,
    ));
  }
  add_action( 'dwqa_add_comment', 'dwqa_record_comment_activity');

  //User Counter
  function dwqa_user_counter() {
    $dwqa_user_question_count = dwqa_user_question_count( bp_displayed_user_id() );
    $dwqa_user_answer_count = dwqa_user_answer_count( bp_displayed_user_id() );
    $dwqa_user_comment_count = dwqa_user_comment_count( bp_displayed_user_id() );
?>
    <div>
      <strong><?php echo $dwqa_user_question_count; ?></strong> <span class="activity"><?php echo ( $dwqa_user_question_count == 0 ) ? __( 'Question', 'dwqa' ) : __( 'Questions', 'dwqa' ); ?></span><br>
      <strong><?php echo $dwqa_user_answer_count; ?></strong> <span class="activity"><?php echo ( $dwqa_user_answer_count == 0 ) ? __( 'Answer', 'dwqa' ) : __( 'Answers', 'dwqa' ); ?></span><br>
      <strong><?php echo $dwqa_user_comment_count; ?></strong> <span class="activity"><?php echo ( $dwqa_user_comment_count == 0 ) ? __( 'Comment', 'dwqa' ) : __( 'Comments', 'dwqa' ); ?></span>
   </div>
<?php
  }
add_action( 'bp_profile_header_meta', 'dwqa_user_counter' );


  function dwqa_replace_activity_meta() {
  global $activities_template;

  $blog_url  = bp_blogs_get_blogmeta( $activity->item_id, 'url' );
  $blog_name = bp_blogs_get_blogmeta( $activity->item_id, 'name' );

  if ( empty( $blog_url ) || empty( $blog_name ) ) {
    $blog_url  = get_home_url( $activity->item_id );
    $blog_name = get_blog_option( $activity->item_id, 'blogname' );

    bp_blogs_update_blogmeta( $activity->item_id, 'url', $blog_url );
    bp_blogs_update_blogmeta( $activity->item_id, 'name', $blog_name );
  }

  $post_url = add_query_arg( 'p', $activities_template->activity->secondary_item_id, trailingslashit( $blog_url ) );

  $post_title = bp_activity_get_meta( $activities_template->activity->id, 'post_title' );

  if ( empty( $post_title ) ) {

    $post = get_post( $activities_template->activity->secondary_item_id );
    if ( is_a( $post, 'WP_Post' ) ) {
      $post_title = $post->post_title;
      bp_activity_update_meta( $activities_template->activity->id, 'post_title', $post_title );
    }
  }
     $post_link  = '<a href="' . $post_url . '">' . $post_title . '</a>';

  $user_link = bp_core_get_userlink( $activities_template->activity->user_id );

  if ( $activities_template->activity->type == 'new_question' ){
    $action  = sprintf( __( '%1$s asked a new question: %2$s', 'dwqa' ), $user_link, $post_link );
  }else if ( $activities_template->activity->type == 'new_answer' ){
     $action  = sprintf( __( '%1$s answered the question: %2$s', 'dwqa' ), $user_link, $post_link );
  }else if ( $activities_template->activity->type == 'comment_question' ){
     $action  = sprintf( __( '%1$s commented on the question: %2$s', 'dwqa' ), $user_link, $post_link );
  }else if ( $activities_template->activity->type == 'comment_answer' ){
     $action  = sprintf( __( '%1$s commented on the answer at: %2$s', 'dwqa' ), $user_link, $post_link );
  }else {
     $action = $activities_template->activity->action;
  }
    
  

  // Strip any legacy time since placeholders from BP 1.0-1.1
  $content = str_replace( '<span class="time-since">%s</span>', '', $content );

  // Insert the time since.
  $time_since = apply_filters_ref_array( 'bp_activity_time_since', array( '<span class="time-since">' . bp_core_time_since( $activities_template->activity->date_recorded ) . '</span>', &$activities_template->activity ) );

  // Insert the permalink
  if ( ! bp_is_single_activity() )
  $content = apply_filters_ref_array( 'bp_activity_permalink', array( sprintf( '%1$s <a href="%2$s" class="view activity-time-since" title="%3$s">%4$s</a>', $content, bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity ), esc_attr__( 'View Discussion', 'buddypress' ), $time_since ), &$activities_template->activity ) );
  else
  $content .= str_pad( $time_since, strlen( $time_since ) + 2, ' ', STR_PAD_BOTH );

  echo $action.' '.$content;
  // echo 'abc';
  // echo $activities_template->activity->content;
  }

  function dwqa_remove_activity_meta(){
    return '';
  }


// add_filter( 'bp_activity_permalink', 'dwqa_remove_activity_meta' );

// add_filter( 'bp_activity_permalink', 'dwqa_remove_activity_meta' );

// add_filter( 'bp_activity_time_since', 'dwqa_replace_activity_meta' );
// add_filter( 'bp_activity_delete_link', 'dwqa_remove_activity_meta' );

// add_filter( 'bp_insert_activity_meta', 'dwqa_remove_activity_meta' );
add_filter( 'bp_insert_activity_meta', 'dwqa_replace_activity_meta' );





?>
