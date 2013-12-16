<?php 
    $post_id = get_the_ID();
?>
    <article id="question-<?php echo $post_id; ?>" <?php post_class(); ?>>

        <header class="entry-header">
            <?php if( current_user_can( 'edit_posts' ) ) { ?>
                <?php if( dwqa_is_pending( $post_id ) ) { ?>
                <span class="tag-label pending">Pending</span>
                <?php } ?>
            <?php } ?>
            <a class="entry-title" href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'dwqa' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark">
                <?php the_title(); ?>
            </a>
            <div class="entry-meta">
                <?php dwqa_question_print_status($post_id); ?>
                <?php
                    global $authordata;
                    if( ! dwqa_is_anonymous($post_id) ) {
                        $author_link = sprintf(
                            '<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
                            get_author_posts_url( $authordata->ID, $authordata->user_nicename ),
                            esc_attr( sprintf( __( 'Posts by %s' ), get_the_author() ) ),
                            dw_strip_email_to_display( get_the_author( ) )
                        );
                    } else {
                        $author_link = __('Anonymous','dwqa');
                    }
                    printf( 
                        '%1$s %1$s  <span>%2$s</span>  %3$s', 
                        __('by','dwqa'),
                        $author_link,
                        get_the_date(), 
                        get_the_term_list( $post_id, 'dwqa-question_category', '<span>Theme: ', ', ', '</span>' )
                    );
                ?>
            </div>
        </header><!-- .entry-header -->

        <footer class="entry-meta">
            <div class="entry-view">
                <?php  
                    $views = dwqa_question_views_count();
                    if( $views > 0 ) {
                        printf(
                            '<strong>%d</strong> %s',
                            $views,
                            _n( 'view', 'views', $views, 'dwqa' )
                        );
                    }else{
                        echo '<strong>0</strong> '.__('view','dwqa');
                    }
                ?>
            </div>
            <div class="entry-comment">
            <?php
                $answer_count = dwqa_question_answers_count();
                if( $answer_count > 0 ) {
                    printf(
                        '<strong>%d</strong> %s',
                        $answer_count,
                        _n( 'answer', 'answers', $answer_count, 'dwqa' )
                    );
                } else {
                    echo '<strong>0</strong> '.__('answer','dwqa');
                }
            ?>
            </div>
            <div class="entry-vote">
                <?php  
                    $answer_vote = dwqa_vote_count();
                    if( $answer_vote > 0 ) {
                        printf(
                            '<strong>%d</strong> %s',
                            $answer_vote,
                            _n( 'vote', 'votes', $answer_vote, 'dwqa' )
                        );
                    } else {
                        echo '<strong>0</strong> '.__('vote','dwqa');
                    }
                ?>
            </div>
        </footer>
    </article>