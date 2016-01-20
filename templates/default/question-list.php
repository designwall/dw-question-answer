<div class="dwqa-question-list">
	<?php dwqa_load_template( 'search', 'question' ); ?>
	<div class="dwqa-question-filter">
		<span><?php _e( 'Filter:', 'dwqa' ); ?></span>
		<a href="<?php echo esc_url( add_query_arg( array( 'filter' => 'all' ) ) ) ?>" class="active"><?php _e( 'All', 'dwqa' ); ?></a>
		<a href="#"><?php _e( 'Popular', 'dwqa' ); ?></a>
		<a href="#"><?php _e( 'Recent', 'dwqa' ); ?></a>
		<a href="#"><?php _e( 'Unanswered', 'dwqa' ); ?></a>
	</div>
	<?php do_action( 'dwqa_before_question_lists' ) ?>
	<div class="dwqa-questions">
		<?php do_action( 'dwqa-prepare-archive-posts' ) ?>
		<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
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
		<?php do_action( 'dwqa-after-archive-posts' ) ?>
	</div>
	<?php do_action( 'dwqa_before_question_lists' ) ?>
</div>