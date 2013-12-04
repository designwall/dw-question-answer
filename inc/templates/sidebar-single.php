
    <div class="dwqa-sidebar" >
        <div class="info well">
            <div class="author-info">     
                <?php 
                    $question = get_post( get_the_ID() );
                    echo get_avatar( $question->post_author, 32, false ); ?>
                <strong class="author"><a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>"><?php the_author_meta(  'display_name', $question->post_author ); ?></a></strong>
                <div class="time"><?php the_date();  ?></div>
            </div>
            <ul class="unstyled list-info">
                <li class="status">Status 
                    <span class="entry-status status-new pull-right"><?php echo get_post_meta( $question->ID, '_dwqa_status', true ) ?></span>
                </li>
                <?php  
                    $cats = wp_get_post_terms( $question->ID, 'dwqa-question_category' );
                    $cats_html = '';
                    if( ! empty($cats) ) {
                        $i = 0;
                        foreach ( $cats as $category ) {
                            if( $i > 0 )
                                $cats_html .= ', ';
                            $cats_html .= '<span><a href="'.get_term_link( $category ).'">' . $category->name . '</a></span>';
                            $i++;
                        }
                ?>
                <li class="category">Category
                    <span class="category pull-right"><?php echo $cats_html ?></span>
                </li>
                <?php
                    }
                ?>

                <?php  
                    $tags = wp_get_post_terms( $question->ID, 'dwqa-question_tag' );
                    $tags_html = '';
                    if( ! empty($tags) ) {
                        $i = 0;
                        foreach ( $tags as $tag ) {
                            if( $i > 0 )
                                $tags_html .= ', ';
                            $tags_html .= '<span><a href="'.get_term_link( $tag ).'">' . $tag->name . '</a></span>';
                            $i++;
                        }
                ?>
                <li class="category"><?php _e('Tags','dwqa') ?>
                    <span class="category pull-right"><?php echo $tags_html ?></span>
                </li>
                <?php
                    }
                ?>
                <li class="view"><?php _e('Views','dwqa') ?>
                    <span class="pull-right"><?php echo dwqa_question_views_count($question->ID) ?></span>
                </li>
                <?php do_action( 'dwqa_question_meta' ) ?>
            </ul>
        </div>
        <div class="related-question dwqa-widget">
            <?php dwqa_related_question(); ?>
        </div>
    </div>