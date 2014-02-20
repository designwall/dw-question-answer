<?php 
	global $post;
	$temp_filter = new DWQA_Filter();
	$new_question = $post->ID;
	$tags = wp_get_post_terms( $new_question, 'dwqa-question_tag' );
	add_filter( 'posts_where', array($temp_filter, 'posts_where')  );
?>
<div id="archive-question" class="dw-question">
	<div class="message-success">
		<p>Welldone, you question “<a href="<?php echo get_permalink( $new_question ); ?>"><?php echo get_the_title( $new_question ); ?></a>” succesfully posted  <?php if( isset($tags[0]) ) { echo '<a href="'.get_term_link( $tags[0] ).'" >'.$tags[0]->name.'</a>'; } ?></p>  
	</div>
	<div class="dwqa-list-question suggest-question">
		<?php 
			$args = array(
			   'post_type' => 'dwqa-question',
			   'posts_per_page' => -1,
			   'orderby' => 'date',
			   'order' => 'DESC',
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
			$latest = array(); $i = 0;

			if( $latest_query->have_posts() ) :
		?>
		<h3><?php _e('Latest 10 open questions awaiting for your answers','dwqa') ?></h3>
		<div class="dwqa-latest-questions">
			<?php
				while( $latest_query->have_posts() ) : $latest_query->the_post();
					$i++;
					if( $i == 10 ) break;
					$latest[] = get_the_ID();
					dwqa_load_template('content', 'question');
				endwhile;
			?>
		</div>

		<?php
			endif;
		?>
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
			<h3><?php echo 'And ' . $same_tag_query->found_posts . ' more from ' . $suggest_tag->name; ?></h3>
			<div class="dwqa-latest-questions">
				<?php
					while( $same_tag_query->have_posts() ) : $same_tag_query->the_post();
						dwqa_load_template('content', 'question');
					endwhile;
				?>
			</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php  
			wp_reset_query(); 
			remove_filter( 'posts_where', array($temp_filter, 'posts_where')  );
		?>
	</div>
</div>