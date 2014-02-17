<?php 
/**
 *  Template use for display a single question
 *
 *  @since  DW Question Answer 1.0
 */
    global $current_user, $post;
    get_header('dwqa'); 
?>

<?php do_action( 'dwqa_before_page' ) ?>
    <?php if( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <?php $post_id = get_the_ID();  ?>
            <div class="dwqa-single-question">
                <!-- dwqa-status-private -->
                <article id="question-<?php echo $post_id ?>" <?php post_class( 'dwqa-question' ); ?>>
                    <header class="dwqa-header">
                        <h1 class="dwqa-title"><?php the_title(); ?></h1>
                        <div class="dwqa-meta">
                            <span class="dwqa-vote" data-type="question" data-nonce="<?php echo wp_create_nonce( '_dwqa_question_vote_nonce' ) ?>" data-question="<?php echo $post_id; ?>" >
                                <a class="dwqa-vote-dwqa-btn dwqa-vote-up" data-vote="up" href="#"  title="<?php _e('Vote Up','dwqa') ?>"><?php _e('Vote Up','dwqa') ?></a>
                                <div class="dwqa-vote-count"><?php $point = dwqa_vote_count(); echo $point > 0 ? '+'.$point:$point; ?></div>
                                <a class="dwqa-vote-dwqa-btn dwqa-vote-down" data-vote="down" href="#"  title="<?php _e('Vote Down','dwqa') ?>"><?php _e('Vote Down','dwqa') ?></a>
                            </span>

                            <?php if( is_user_logged_in() ) : ?>
                            <span data-post="<?php echo $post_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_follow_question' ); ?>" class="dwqa-favourite <?php echo dwqa_is_followed($post_id) ? 'active' : ''; ?>" title="<?php echo dwqa_is_followed($post_id) ? __('Unfollow This Question','dwqa') : __('Follow This Question','dwqa'); ?>"><!-- add class 'active' -->
                                <i class="fa fa-star"></i>
                            </span>
                            <?php endif; ?>
                            <?php if( dwqa_current_user_can( 'edit_question' ) ) : ?>
                            <span  data-post="<?php echo $post_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_stick_question' ); ?>" class="dwqa-stick-question <?php echo dwqa_is_sticky($post_id) ? 'active' : ''; ?>" title="<?php echo dwqa_is_sticky($post_id) ? __('Stick this Question to the front page','dwqa') : __('Unstick this Question to the front page','dwqa'); ?>"><i class="fa fa-bookmark"></i></span>
                            <?php endif; ?>
                        </div>
                    </header>
                    <div class="dwqa-content">
                        <?php the_content(); ?>
                    </div>
                    <?php  
                        $tags = get_the_term_list( $post_id, 'dwqa-question_tag', '<span class="dwqa-tag">', '</span><span class="dwqa-tag">', '</span>' );
                        if( ! empty($tags) ) :
                    ?>
                    <div class="dwqa-tags"><?php echo $tags; ?></div>
                    <?php endif; ?>  <!-- Question Tags -->

                    <footer class="dwqa-footer">
                        <div class="dwqa-author">
                            <?php echo get_avatar( $post->post_author, 32, false ); ?>
                            <span class="author">
                                <?php  
                                    printf('<a href="%1$s" title="%2$s %3$s">%3$s</a>',
                                        get_author_posts_url( get_the_author_meta( 'ID' ) ),
                                        __('Posts by','dwqa'),
                                        get_the_author_meta(  'display_name')
                                    );
                                ?>
                            </span><!-- Author Info -->
                            <span class="dwqa-date">
                                <?php 
                                    printf('<a href="%s" title="%s #%d">%s %s</a>',
                                        get_permalink(),
                                        __('Link to','dwqa'),
                                        $post_id,
                                        __('asked','dwqa'),
                                        get_the_date()
                                    ); 
                                ?>
                            </span> <!-- Question Date -->
                            
                            
                            <div data-post="<?php echo $post_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_privacy_nonce' ); ?>" data-type="question" class="dwqa-privacy">
                                <input type="hidden" name="privacy" value="<?php get_post_status(); ?>">
                                <span class="dwqa-current-privacy"> <?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> ' . __('Private','dwqa') : '<i class="fa fa-globe"></i> ' . __('Public','dwqa'); ?></span>
                                <?php if( dwqa_current_user_can('edit_question') || dwqa_current_user_can('edit_answer') || $post->post_author == $current_user->ID ) { ?>
                                <span class="dwqa-change-privacy">
                                    <div class="dwqa-btn-group">
                                        <button type="button" class="dropdown-toggle" ><i class="fa fa-caret-down"></i></button>
                                        <div class="dwqa-dropdown-menu">
                                            <div class="dwqa-dropdown-caret">
                                                <span class="dwqa-caret-outer"></span>
                                                <span class="dwqa-caret-inner"></span>
                                            </div>
                                            <ul role="menu">
                                                <li title="<?php _e('Everyone can see','dwqa'); ?>" data-privacy="publish" <?php echo 'publish' == get_post_status() ? 'class="current"' : ''; ?>><a href="#"><i class="fa fa-globe"></i> <?php _e('Public','dwqa'); ?></a></li>
                                                <li title="<?php _e('Only Author and Administrator can see','dwqa'); ?>" data-privacy="private" <?php echo 'private' == get_post_status() ? 'class="current"' : ''; ?>><a href="#" ><i class="fa fa-lock"></i> <?php _e('Private','dwqa') ?></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </span>
                                <?php } ?>
                            </div><!-- post status -->
                        </div>
                        <?php  
                            $categories = wp_get_post_terms( $post_id, 'dwqa-question_category' );
                            if( ! empty($categories) ) :
                                $cat = $categories[0]
                        ?>
                        <div class="dwqa-category">
                            <span class="dwqa-category-title"><?php _e('Category','dwqa') ?></span>
                            <a class="dwqa-category-name" href="<?php echo get_term_link( $cat );  ?>" title="<?php _e('All questions from','dwqa') ?> <?php echo $cat->name ?>"><?php echo $cat->name ?></a>
                        </div>
                        <?php endif; ?> <!-- Question Categories -->

                        <?php
                            $meta = get_post_meta( $post_id, '_dwqa_status', true );
                            if( ! $meta ) {
                                $meta = 'open';
                            }
                        ?>
                        <div class="dwqa-current-status">
                            <span class="dwqa-status-title"><?php _e('Status','dwqa') ?></span>
                            <span class="dwqa-status-name"><?php echo $meta; ?></span>
                            <?php
                                if( dwqa_current_user_can('edit_question') 
                                    || dwqa_current_user_can('edit_answer') 
                                    || $current_user->ID == $post->post_author ) :
                            ?>
                            <span class="dwqa-change-status">
                                <div class="dwqa-btn-group">
                                    <button type="button" class="dropdown-toggle" ><i class="fa fa-caret-down"></i></button>
                                    <div class="dwqa-dropdown-menu" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_question_status_nonce' ) ?>" data-question="<?php the_ID(); ?>" >
                                        <div class="dwqa-dropdown-caret">
                                            <span class="dwqa-caret-outer"></span>
                                            <span class="dwqa-caret-inner"></span>
                                        </div>
                                        <ul role="menu" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_question_status_nonce' ) ?>" data-question="<?php the_ID(); ?>">
                                            <?php if( 'resolved' == $meta || 'pending' == $meta || 'closed' == $meta) : ?>
                                                <li class="dwqa-re-open" data-status="re-open">
                                                    <a href="#"><i class="fa fa-reply"></i> <?php _e('Re-Open','dwqa') ?></a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if( 'closed' != $meta  ) : ?>
                                                <li class="dwqa-closed" data-status="closed">
                                                    <a href="#"><i class="fa fa-lock"></i> <?php _e('Closed','dwqa') ?></a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if( 'pending' != $meta && 'closed' != $meta && current_user_can( 'edit_posts', $post_id ) ) : ?>
                                                <li class="dwqa-pending"  data-status="pending">
                                                    <a href="#"><i class="fa fa-question-circle"></i> <?php _e('Pending','dwqa') ?></a>
                                                </li>
                                            <?php endif; ?>
                                            <?php if( 'resolved' != $meta && 'closed' != $meta ) : ?>
                                                <li class="dwqa-resolved" data-status="resolved">
                                                    <a href="#"><i class="fa fa-check-circle-o"></i> <?php _e('Resolved','dwqa') ?></a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </span>
                            <?php endif; ?> <!-- Change Question Status -->
                        </div>
                    </footer>
                    <div class="dwqa-comments">
                        <?php comments_template(); ?>
                    </div>
                </article><!-- end question -->

                <div id="dwqa-answers">
                    <?php dwqa_load_template('answers'); ?>
                </div><!-- end dwqa-add-answers -->

            </div><!-- end dwqa-single-question -->
        <?php endwhile; // end of the loop. ?>  
    <?php endif; ?>
<?php do_action( 'dwqa_after_page' ) ?>
<?php get_footer('dwqa'); ?>