<?php global $post; ?>
<style id="dwqa-question-stand-alone">
.dwqa-question-stand-alone {
  border: 1px solid #e5e5e5 !important;
  border-color: #eee #eee #cecece !important;
  padding: 15px 20px 0 !important;
  margin-bottom: 30px;
  position: relative;
  word-wrap: break-word;
}
.dwqa-question-stand-alone .dwqa-title {
  font-size: 24px;
  line-height: 1.3;
  margin-top: 5px;
}
.dwqa-question-stand-alone .dwqa-tags {
  margin-bottom: 20px;
}
.dwqa-question-stand-alone .dwqa-tags .dwqa-tag {
  background: #f3f3f3;
  padding: 2px 10px;
  margin-right: 5px;
  display: inline-block;
}
.dwqa-question-stand-alone .dwqa-tags .dwqa-tag:hover {
  background: #e5e5e5;
}
.dwqa-question-stand-alone .dwqa-tags .dwqa-tag a {
  color: #555;
}
.dwqa-question-stand-alone .dwqa-footer {
  margin: 15px -20px 0 -20px;
  padding: 20px 0;
  background: transparent;
  border-bottom: 0;
  border-top: 1px solid #eee;
  position: relative;
}
.dwqa-question-stand-alone .dwqa-footer-links {
  margin: 0 -20px 0 -20px;
  padding: 10px 20px;
  background: transparent;
  border-bottom: 0;
  border-top: 1px solid #eee;
  position: relative;
  overflow: hidden;
}
.dwqa-question-stand-alone .dwqa-footer-links a {
  color: #999;
  text-transform: uppercase;
  font-size: 14px;
}
.dwqa-question-stand-alone .dwqa-footer-links a:hover {
  color: #555;
}
.dwqa-question-stand-alone .dwqa-footer-links .dwqa-links {
  display: block;
  float: left;
  margin: 0;
}
.dwqa-question-stand-alone .dwqa-footer-links .dwqa-actions {
  display: block;
  float: right;
  margin: 0;
}
.dwqa-question-stand-alone .dwqa-footer .dwqa-category {
  border-left: 1px solid #e5e5e5;
  display: inline-block;
  vertical-align: top;
  padding: 0 10px;
}
.dwqa-question-stand-alone .dwqa-footer .dwqa-date,
.dwqa-question-stand-alone .dwqa-footer .dwqa-category-name {
  color: #999;
  display: block;
  text-transform: capitalize;
}
.dwqa-question-stand-alone .dwqa-footer .dwqa-date a {
  color: #999;
}
.dwqa-question-stand-alone .dwqa-footer .author a,
.dwqa-question-stand-alone .dwqa-footer .dwqa-category-title {
  color: #555;
  font-weight: bold;
}
.dwqa-question-stand-alone .dwqa-author {
  position: relative;
  padding: 0 15px 0 70px;
  display: inline-block;
}
.dwqa-question-stand-alone .dwqa-author .avatar {
  width: 32px;
  height: 32px;
  padding: 3px;
  margin: 0 10px 0 0;
  border: 1px solid #e5e5e5;
  position: absolute;
  top: 0;
  left: 20px;
}
</style>
<div class="dwqa-question-stand-alone">
	<article id="question-<?php echo $post->ID ?>" <?php post_class( 'dwqa-question' ); ?>>
	    <header class="dwqa-header">
	        <h1 class="dwqa-title"><?php the_title(); ?></h1>
	    </header>
	    <div class="dwqa-content">
	        <?php the_content(); ?>
	    </div>
	    <?php  
	        $tags = get_the_term_list( $post->ID, 'dwqa-question_tag', '<span class="dwqa-tag">', '</span><span class="dwqa-tag">', '</span>' );
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
	                        $post->ID,
	                        __('asked','dwqa'),
	                        get_the_date()
	                    ); 
	                ?>
	            </span> <!-- Question Date -->
	        </div>
	        <?php  
	            $categories = wp_get_post_terms( $post->ID, 'dwqa-question_category' );
	            if( ! empty($categories) ) :
	                $cat = $categories[0]
	        ?>
	        <div class="dwqa-category">
	            <span class="dwqa-category-title"><?php _e('Category','dwqa') ?></span>
	            <a class="dwqa-category-name" href="<?php echo get_term_link( $cat );  ?>" title="<?php _e('All questions from','dwqa') ?> <?php echo $cat->name ?>"><?php echo $cat->name ?></a>
	        </div>
	        <?php endif; ?> <!-- Question Categories -->
	    </footer>

	    <footer class="dwqa-footer-links">
	    	<div class="dwqa-links">
	    		<a href="#"><strong>9</strong> views</a>
				<a href="#"><strong>3</strong> replies</a>
	    	</div>
			<div class="dwqa-actions">
				<a href="#"><i class="fa fa-mail-reply"></i></a>
	            <a href="#"><i class="fa fa-star"></i></a>
			</div>
	    </footer>
	</article><!-- end question -->
</div>