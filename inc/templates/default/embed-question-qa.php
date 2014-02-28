<div class="dwqa-question-embed">
	<div class="dwqa-embed-avatar">
        <?php  global $post; echo get_avatar( $post->post_author, 64 ); ?>
    </div>
	<div class="dwqa-embed-content">
		<div class="dwqa-embed-title"><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></div>
        <div class="dwqa-embed-links">
                <a target="_blank" href="<?php echo get_permalink(); ?>"><?php
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
                ?></a>&nbsp;&nbsp;<a target="_blank" href="<?php echo get_permalink(); ?>"><?php  
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
                ?></a>
        </div>
	</div>
    <div class="dwqa-embed-summary">
    <?php
        $content =  substr(get_the_content(), 0 , 153 );
        echo $content . '...';
    ?>
    </div>
</div>