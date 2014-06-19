<?php 
	
	$taxonomy = get_query_var( 'taxonomy' );
	$term_name = get_query_var( $taxonomy );
	if( $taxonomy == 'dwqa-question_category' ) { 
		$args = array(
			'post_type' => 'dwqa-question',
			'posts_per_page'	=>	-1,
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $term_name
				)
			)
		);
		$query = new WP_Query( $args );
		$total = $query->post_count;
	} else if( 'dwqa-question_tag' == $taxonomy ) {

		$args = array(
			'post_type' => 'dwqa-question',
			'posts_per_page'	=>	-1,
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => $term_name
				)
			)
		);
		$query = new WP_Query( $args );
		$total = $query->post_count;
	} else {
		$total = wp_count_posts( 'dwqa-question' );
		$total = $total->publish;
	}

	$number_questions = $total;

	$number = get_query_var( 'posts_per_page' );

	$pages = ceil( $number_questions / $number );
	
	if( $pages > 1 ) {

	?>
		<div class="pagination">
			<ul data-pages="<?php echo $pages; ?>" >
				<?php  
					global $dwqa_options;

					$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
					$i = 0;
					echo '<li class="prev';
					if( $i == 0 ) {
						echo ' dwqa-hide';
					}
					echo '"><a href="javascript:void()">'.__('Prev', 'dwqa').'</a></li>';
					$link = get_permalink( $dwqa_options['pages']['archive-question'] );
					$start = $paged - 2;
					$end = $paged + 2;

		            if( $end > $pages ) {
		                $end = $pages;
		                $start = $pages -  5;
		            }

		            if( $start < 1 ) {
		                $start = 1;
		                $end = 5;
		                if( $end > $pages ) {
		                	$end = $pages;
		                }
		            }
		            if( $start > 1 ) {
                        echo '<li><a href="'.add_query_arg('paged',1,$link).'">1</a></li><li class="dot"><span>...</span></li>';
                    }
					for ($i=$start; $i <= $end; $i++) { 
						$current = $i == $paged ? 'class="active"' : '';
						if( $i == 1 ) {
							echo '<li '.$current.'><a href="'.$link.'">'.$i.'</a></li>';
						}else{
							echo '<li '.$current.'><a href="'.add_query_arg('paged', $i, $link).'">'.$i.'</a></li>';
						}
					}

		            if( $i - 1 < $pages ) {
                        echo '<li class="dot"><span>...</span></li><li><a href="'.add_query_arg('paged',$pages,$link).'">'.$pages.'</a></li>';
                    }
					echo '<li class="next';
					if( $paged == $pages ) {
						echo ' dwqa-hide';
					}
					echo '"><a href="javascript:void()">'.__('Next', 'dwqa') .'</a></li>';

				?>
			</ul>
		</div>
<?php } ?>