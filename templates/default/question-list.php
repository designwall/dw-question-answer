<div class="dwqa-question-list">
	<div class="dwqa-breadcrumbs">
		<a href="#">Questions</a>
		<span class="dwqa-sep"> &rsaquo; </span>
		<span class="dwqa-current">Category: Sample Category Name</span>
	</div>
	<form class="dwqa-search">
		<input type="text" placeholder="<?php _e( 'What do you want to know?', 'dwqa' ); ?>">
	</form>
	<div class="dwqa-question-filter">
		<span><?php _e( 'Filter:', 'dwqa' ); ?></span>
		<a href="#" class="active"><?php _e( 'All', 'dwqa' ); ?></a>
		<a href="#"><?php _e( 'Popular', 'dwqa' ); ?></a>
		<a href="#"><?php _e( 'Recent', 'dwqa' ); ?></a>
		<a href="#"><?php _e( 'Unanswered', 'dwqa' ); ?></a>
		<div class="pull-right">
			<span>Sort by:</span>
			<select>
				<option>Views</option>
				<option>Answers</option>
				<option>Votes</option>
			</select>
		</div>
	</div>
	<div class="dwqa-questions">

		<?php
			global $wp_query;
			$custom_query_args = array( 'post_type' => 'dwqa-question', 'posts_per_page' => 20, 'ignore_sticky_posts' => true );
			$custom_query_args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
			$custom_query = new WP_Query( $custom_query_args );
			$temp_query = $wp_query;
			$wp_query = NULL;
			$wp_query = $custom_query;
		?>
		<?php if ( $custom_query->have_posts() ) : ?>
		<?php while ( $custom_query->have_posts() ) : $custom_query->the_post(); ?>
			<div class="dwqa-question">
				<a class="dwqa-question-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				<div class="dwqa-question-meta">
					<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
						<?php echo get_avatar( get_the_author_meta( 'ID' ), 48 ); ?>
						<?php the_author_meta( 'display_name' ); ?>
					</a>
					<?php printf( __( ' asked %1$s ago', 'dwqa' ), esc_attr( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ) ); ?>
				</div>
				<div class="dwqa-question-stats">
					<span class="dwqa-views-count">
						<?php printf( __( '<strong>%1$s</strong> views', 'dwqa' ), dwqa_question_views_count() ); ?>
					</span>
					<span class="dwqa-answers-count">
						<?php printf( __( '<strong>%1$s</strong> answers', 'dwqa' ), dwqa_question_answers_count() ); ?>
					</span>
					<span class="dwqa-votes-count">
						<?php printf( __( '<strong>%1$s</strong> votes', 'dwqa' ), dwqa_vote_count() ); ?>
					</span>
				</div>
			</div>
		<?php endwhile; ?>
			<?php the_posts_pagination( array( 'mid_size' => 4 ) ); ?>
		<?php endif; ?>
		<?php
			wp_reset_postdata();
			$wp_query = NULL;
			$wp_query = $temp_query;
		?>
	</div>
</div>

<?php /* global $dwqa_general_settings; ?>
<div id="archive-question" class="dw-question">
	<div class="dwqa-list-question">
		<?php dwqa_load_template( 'search', 'question' ); ?>
		<div class="filter-bar">
			<?php wp_nonce_field( '_dwqa_filter_nonce', '_filter_wpnonce', false ); ?>
			<?php dwqa_get_ask_question_link( ); ?>
			<div class="filter">
				<li class="status">
					<?php $selected = isset( $_GET['status'] ) ? esc_html( $_GET['status'] ) : 'all'; ?>
					<ul>
						<li><?php _e( 'Status:', 'dwqa' ) ?></li>
						<li class="<?php echo $selected == 'all' ? 'active' : ''; ?> status-all" data-type="all">
							<a href="#"><?php _e( 'All', 'dwqa' ); ?></a>
						</li>

						<li class="<?php echo $selected == 'open' ? 'active' : ''; ?> status-open" data-type="open">
							<a href="#"><?php echo current_user_can( 'edit_posts' ) ? __( 'Need Answer', 'dwqa' ) : __( 'Open', 'dwqa' ); ?></a>
						</li>
						<li class="<?php echo $selected == 'replied' ? 'active' : ''; ?> status-replied" data-type="replied">
							<a href="#"><?php _e( 'Answered', 'dwqa' ); ?></a>
						</li>
						<li class="<?php echo $selected == 'resolved' ? 'active' : ''; ?> status-resolved" data-type="resolved">
							<a href="#"><?php _e( 'Resolved', 'dwqa' ); ?></a>
						</li>
						<li class="<?php echo $selected == 'closed' ? 'active' : ''; ?> status-closed" data-type="closed">
							<a href="#"><?php _e( 'Closed', 'dwqa' ); ?></a>
						</li>
						<?php if ( dwqa_current_user_can( 'edit_question' ) ) : ?>
						<li class="<?php echo $selected == 'overdue' ? 'active' : ''; ?> status-overdue" data-type="overdue"><a href="#"><?php _e( 'Overdue', 'dwqa' ) ?></a></li>
						<li class="<?php echo $selected == 'pending-review' ? 'active' : ''; ?> status-pending-review" data-type="pending-review"><a href="#"><?php _e( 'Queue', 'dwqa' ) ?></a></li>

						<?php endif; ?>
					</ul>
				</li>
			</div>
			<div class="filter sort-by">
				<div class="filter-by-category select">
				<?php
					$selected = false;
					$taxonomy = get_query_var( 'taxonomy' );
				?>

				<?php if ( $taxonomy && 'dwqa-question_category' == $taxonomy ) :
						$term_name = get_query_var( $taxonomy );
						$term = get_term_by( 'slug', $term_name, $taxonomy );
						if ( $term )
							$selected = $term->term_id;
				?>
				<?php else :
						$question_category_rewrite = $dwqa_general_settings['question-category-rewrite'];
						$question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
						$selected = isset( $_GET[$question_category_rewrite] ) ? esc_html( $_GET[$question_category_rewrite] ) : 'all';
					endif;
					$selected_label = __( 'Select a category', 'dwqa' );
				?>

				<?php if ( $selected && $selected != 'all' ) :
						$selected_term = get_term_by( 'id', $selected, 'dwqa-question_category' );
						$selected_label = $selected_term->name;
					endif;
				?>
					<span class="current-select"><?php echo $selected_label; ?></span>
					<ul id="dwqa-filter-by-category" class="category-list" data-selected="<?php echo $selected; ?>">
					<?php
						wp_list_categories( array(
							'show_option_all'	=> __( 'All', 'dwqa' ),
							'show_option_none'  => __( 'Empty', 'dwqa' ),
							'taxonomy'			=> 'dwqa-question_category',
							'hide_empty'        => 0,
							'show_count'		=> 0,
							'title_li'			=> '',
							'walker'			=> new DWQA_Walker_Category,
						) );
					?>
					</ul>
				</div>
				<?php $tag_field = ''; ?>
				<?php if ( $taxonomy == 'dwqa-question_tag' ) :
						$selected = false;
				?>

					<?php if ( $taxonomy ) :
							$term_name = get_query_var( $taxonomy );
							$term = get_term_by( 'slug', $term_name, $taxonomy );
							$selected = $term->term_id; ?>

					<?php elseif ( 'dwqa-question_category' == $taxonomy ) :
							$question_tag_rewrite = $dwqa_general_settings['question-tag-rewrite'];
							$question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
							$selected = isset( $_GET[$question_tag_rewrite] ) ? esc_html( $_GET[$question_tag_rewrite] ) : 'all';
						endif;
					?>

					<?php if ( isset( $selected )  &&  $selected != 'all' ) :
							$tag_field = '<input type="hidden" name="dwqa-filter-by-tags" id="dwqa-filter-by-tags" value="'.$selected.'" >';
						endif;
					?>
				<?php endif; ?>

				<?php
					$tag_field = apply_filters( 'dwqa_filter_bar', $tag_field );
					echo $tag_field;
				?>

				<?php $orderby = isset( $_GET['orderby'] ) ? esc_html( $_GET['orderby'] ) : ''; ?>
				<ul class="order">
					<li class="most-reads <?php echo $orderby == 'views' ? 'active' : ''; ?>"  data-type="views" >
						<span><?php _e( 'View', 'dwqa' ) ?></span> <i class="fa fa-sort <?php echo $orderby == 'views' ? 'icon-sort-up' : ''; ?>"></i>
					</li>
					<li class="most-answers <?php echo $orderby == 'answers' ? 'active' : ''; ?>" data-type="answers" >
						<span href="#"><?php _e( 'Answer', 'dwqa' ) ?></span> <i class="fa fa-sort <?php echo $orderby == 'answers' ? 'fa-sort-up' : ''; ?>"></i>
					</li>
					<li class="most-votes <?php echo $orderby == 'votes' ? 'active' : ''; ?>" data-type="votes" >
						<span><?php _e( 'Vote', 'dwqa' ) ?></span> <i class="fa fa-sort <?php echo $orderby == 'votes' ? 'fa-sort-up' : ''; ?>"></i>
					</li>
				</ul>

				<?php
					global $dwqa_general_settings;
					$posts_per_page = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : get_query_var( 'posts_per_page' );
				?>
				<input type="hidden" id="dwqa_filter_posts_per_page" name="posts_per_page" value="<?php echo $posts_per_page; ?>">
			</div>
		</div>

		<?php do_action( 'dwqa-before-question-list' ); ?>

		<?php do_action( 'dwqa-prepare-archive-posts' ); ?>
		<?php if ( have_posts() ) : ?>
		<div class="loading"></div>
		<div class="questions-list">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php dwqa_load_template( 'content', 'question' ); ?>
		<?php endwhile; ?>
		</div>
		<div class="archive-question-footer">
			<?php dwqa_load_template( 'navigation', 'archive' ); ?>

			<?php dwqa_get_ask_question_link(); ?>
		</div>
		<?php else : ?>
			<?php dwqa_load_template( 'archive', 'question-notfound' ); ?>
		<?php endif; ?>

		<?php do_action( 'dwqa-after-archive-posts' ); ?>
	</div>
</div> */ ?>
