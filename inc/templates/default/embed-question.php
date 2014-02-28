<?php 
	global $post; 
?>
<div class="dwqa-question-stand-alone">
	<article id="question-<?php echo $post->ID ?>" <?php post_class( 'dwqa-question' ); ?>>
	    <header class="dwqa-header">
	        <h1 class="dwqa-title"><a href="<?php echo get_permalink(); ?>"><?php the_title(); ?></a></h1>
	    </header>
	    <div class="dwqa-content">
	    	<div class="dwqa-content-inner dim">
	        	<?php the_content(); ?>
	        	<div class="show-more-end"></div>
	    	</div>
			<div class="dwqa-read-more"><?php _e('-- More --','dwqa') ?></div>
	    </div>
		
	    <?php 
	        $tags = get_the_term_list( $post->ID, 'dwqa-question_tag', '<span class="dwqa-tag">', '</span><span class="dwqa-tag">', '</span>' );
	        if( ! empty($tags) ) :
	    ?>
	    <div class="dwqa-tags"><?php echo $tags; ?></div>
		<?php endif; ?>  <!-- Question Tags -->

	    <footer class="dwqa-footer">
	        <div class="dwqa-author"><?php echo get_avatar( $post->post_author, 32, false ); ?><span class="author"><?php  
	                    printf('<a href="%1$s" title="%2$s %3$s">%3$s</a>',
	                        get_author_posts_url( get_the_author_meta( 'ID' ) ),
	                        __('Posts by','dwqa'),
	                        get_the_author_meta(  'display_name')
	                    );
	                ?></span><!-- Author Info --><span class="dwqa-date"><?php 
	                    printf('<a href="%s" title="%s #%d">%s %s</a>',
	                        get_permalink(),
	                        __('Link to','dwqa'),
	                        $post->ID,
	                        __('asked','dwqa'),
	                        get_the_date()
	                    ); 
	                ?></span> <!-- Question Date --></div>
	        <?php  
	            $categories = wp_get_post_terms( $post->ID, 'dwqa-question_category' );
	            if( ! empty($categories) ) :
	                $cat = $categories[0]
	        ?>
	        <div class="dwqa-category"><span class="dwqa-category-title"><?php _e('Category','dwqa') ?></span><a class="dwqa-category-name" href="<?php echo get_term_link( $cat );  ?>" title="<?php _e('All questions from','dwqa') ?> <?php echo $cat->name ?>"><?php echo $cat->name ?></a></div><?php endif; ?> <!-- Question Categories -->
	        <div class="dwqa-answer-btn"><a target="_blank" href="<?php echo get_permalink(); ?>" class="dwqa-btn"><?php _e('Answer Now','dwqa') ?></a></div>
	    </footer>
	    <footer class="dwqa-footer-links">
	    	<div class="dwqa-links">
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
			<div class="dwqa-actions">
				<a target="_blank" href="<?php echo get_permalink(); ?>#dwqa-add-answers"><i class="fa fa-mail-reply"></i></a>&nbsp;&nbsp;<a target="_blank" href="<?php echo get_permalink(); ?>"><i class="fa fa-star"></i></a>
			</div>
	    </footer>
	</article><!-- end question --></div>