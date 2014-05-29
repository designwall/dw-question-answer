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
            <?php $post_id = get_the_ID(); $post_status = get_post_status();  ?>
            <div class="dwqa-single-question">
                <!-- dwqa-status-private -->
                <article id="question-<?php echo $post_id ?>" <?php post_class( 'dwqa-question' ); ?>>
                    <header class="dwqa-header">
                        <h1 class="dwqa-title"><?php the_title(); ?></h1>
                        <?php if( $post_status == 'draft' || $post_status == 'pending' ) : ?>
                        <div class="dwqa-alert alert"><?php echo $current_user->ID == $post->post_author ? __('Your question has been submitted and is currently awaiting approval','dwqa') : __('This question is currently awaiting approval','dwqa'); ?></div>
                        <?php endif; ?>
                        <?php dwqa_question_meta_button( $post_id ); ?>
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
                    
                    <?php dwqa_question_action_buttons($post_id); ?>

                    <?php do_action( 'dwqa-question-content-footer' ); ?>
                    
                    <!-- Question footer -->
                    <footer class="dwqa-footer">
                        <div class="dwqa-author">
                            <?php echo get_avatar( $post->post_author, 32, false ); ?>
                            <span class="author">
                                <?php  
                                    if( dwqa_is_anonymous( $post->ID ) ) {
                                        _e('Anonymous','dwqa');
                                    } else {
                                        printf('<a href="%1$s" title="%2$s %3$s">%3$s</a>',
                                            get_author_posts_url( get_the_author_meta( 'ID' ) ),
                                            __('Posts by','dwqa'),
                                            get_the_author_meta(  'display_name')
                                        );
                                    }
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
                            
                            
                            <?php dwqa_question_privacy_button( $post_id ); ?>
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

                        <?php dwqa_question_status_button( $post_id ); ?>
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