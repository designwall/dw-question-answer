<?php 
	global $post;
	$temp_filter = new DWQA_Filter();
	$new_question = $post->ID;
	$tags = wp_get_post_terms( $new_question, 'dwqa-question_tag' );
	//add_filter( 'posts_where', array($temp_filter, 'posts_where')  );
?>
<div class="dw-question list-open-question">
	<div class="message-success alert alert-success">
		Welldone, your question “<a href="<?php echo get_permalink( $new_question ); ?>"><?php echo get_the_title( $new_question ); ?></a>” succesfully posted  <?php if( isset($tags[0]) ) { echo '<a href="'.get_term_link( $tags[0] ).'" >'.$tags[0]->name.'</a>'; } ?>
	</div>
	<div class="row-fluid">
		<div class="span6">
		<?php 
			$latest = array(); $i = 0;
			$latest[] = $post->ID;
			$args = array(
			   'post_type' => 'dwqa-question',
			   'posts_per_page' => -1,
			   'orderby' => 'date',
			   'order' => 'DESC',
			    'post__not_in' => array( $post->ID ),
			   'meta_query' => array(
			       array(
			           'key' => '_dwqa_status',
			           'value' => array( 'open', 're-open'),
			           'compare' => 'IN',
			       )
			   ),
			   'suppress_filters' => false
			);
			$latest_query = new WP_Query( $args );
			if( $latest_query->have_posts() ) :
		?>
		<h3><?php echo 'Latest '.($latest_query->found_posts > 5 ? 5 : $latest_query->found_posts).' open questions awaiting for your answers'; ?></h3>
		<ul class="dwqa-latest-questions">
			<?php
				while( $latest_query->have_posts() ) : $latest_query->the_post();
					$i++; $latest[] = get_the_ID();
			?>
				<li>
			     <a href="<?php the_permalink(); ?>" class="question-title"><?php the_title(); ?></a>
			     <div class="dwqa-meta">
        			<span class="dwqa-user-avatar"><?php echo get_avatar( get_the_author_meta('ID'), 12 ); ?></span>
			      	<span class="dwqa-author"><?php the_author_posts_link(); ?></span>
			      	<span class="dwqa-date"><?php echo get_the_date(); ?></span>
			      	<?php echo get_the_term_list( get_the_ID(), 'dwqa-question_category', '<span class="dwqa-category">in ', ', ', '</span>' ); ?>    
      				<?php echo get_the_term_list( get_the_ID(), 'dwqa-question_tag', '<span class="dwqa-tag">@ ', ', ', '</span>' ); ?>
			     </div>
			    </li>
			<?php
					if( $i == 5 ) break;
				endwhile;
			?>
		</ul>

		<?php endif; ?>
		</div>
		<div class="span6">
		<?php if( ! empty($tags) ) : ?>
			<?php  $suggest_tag = $tags[0]; ?>
			<?php 
					$args = array(
					    'post_type' => 'dwqa-question',
					    'orderby' => 'date',
					    'order' => 'DESC',
				   	    'posts_per_page' => 5,
					    'post__not_in' => $latest,
				   		'suppress_filters' => false,
					    'meta_query' => array(
					        array(
					           'key' => '_dwqa_status',
					           'value' => array( 'open', 're-open'),
					           'compare' => 'IN',
					        )
					    ),
						'tax_query' => array(
							array(
								'taxonomy' => 'dwqa-question_tag',
								'field' => 'slug',
								'terms' => $suggest_tag->slug
							)
						)
					);
					$same_tag_query = new WP_Query( $args );
					if( $same_tag_query->have_posts() ) :
			?>
			<h3><?php echo 'And ' . $same_tag_query->post_count . ' more from ' . $suggest_tag->name; ?></h3>
			<ul class="dwqa-related-questions">
				<?php while( $same_tag_query->have_posts() ) : $same_tag_query->the_post(); ?>
				<li>
			     	<a href="<?php the_permalink(); ?>" class="question-title"><?php the_title(); ?></a>
			     	<div class="dwqa-meta">
        				<span class="dwqa-user-avatar"><?php echo get_avatar( get_the_author_meta('ID'), 12 ); ?></span>
			      		<span class="dwqa-author"><?php the_author_posts_link(); ?></span>
			      		<span class="dwqa-date"><?php echo get_the_date(); ?></span>
			      		<?php echo get_the_term_list( get_the_ID(), 'dwqa-question_category', '<span class="dwqa-category">in ', ', ', '</span>' ); ?>    
      					<?php echo get_the_term_list( get_the_ID(), 'dwqa-question_tag', '<span class="dwqa-tag">@ ', ', ', '</span>' ); ?>
			     	</div>
			    </li>
				<?php endwhile; ?>
			</ul>
			<?php endif; ?>
		<?php endif; ?>
		</div>
		<?php  
			wp_reset_query(); 
			remove_filter( 'posts_where', array($temp_filter, 'posts_where')  );
		?>
	</div>
</div>

