<?php 
	global $post;
	$temp_filter = new DWQA_Filter();
	$new_question = $post->ID;
	$tags = wp_get_post_terms( $new_question, 'dwqa-question_tag' );
	//add_filter( 'posts_where', array($temp_filter, 'posts_where')  );
?>
<div class="dw-question list-open-question">
	<div class="alert alert-success">
		Awesome, your question “<a href="<?php echo get_permalink( $new_question ); ?>"><?php echo get_the_title( $new_question ); ?></a>” has been succesfully posted to <?php if( isset($tags[0]) ) { echo '<a href="'.get_term_link( $tags[0] ).'" >'.$tags[0]->name.'</a>'; } ?>
	</div>
	<div class="alert alert-well alignleft">
		<p>At DesignWall, we value Knowledge Sharing, Learn by Doing and Respect not only about DW products in specific, but also <a href="http://www.google.com/url?q=http%3A%2F%2Fwww.designwall.com%2Fquestion%2Fanything-wordpress-and-the-free-lifetime-membership-at-designwall%2F&sa=D&sntz=1&usg=AFQjCNHfCpoQ2tbooEhPEX8aaEuXpozNAw">Anything Wordpress</a>. Your question will soon be answered by either us - DW team or by other members in the community.</p>
		<p>At the moment, we only have 2 team members who will be dedicated to get all your questions answered, and we are currently overwhelmed with the number of questions submitting daily.</p>
		<p>We believe you CAN also contribute by sharing your knowledge and helping others. Below are a few people that you can really help out.</p>
	</div>
	<div class="alert alert-classic">
		Thank you for lending us an extra hand, we would like to give you <span>5</span> <strong>$tones</strong> for every answer you’ve provided.
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
		<h3><?php echo 'Latest '.($latest_query->found_posts > 10 ? 10 : $latest_query->found_posts).' open questions awaiting for your answers'; ?></h3>
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
					if( $i == 10 ) break;
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
				   	    'posts_per_page' => 10,
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

