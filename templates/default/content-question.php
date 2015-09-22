	<?php $post_id = get_the_ID(); ?>
	<article id="question-<?php echo $post_id; ?>" class="dwqa-question <?php echo dwqa_is_sticky( $post_id ) ? 'dwqa-sticky-question' : ''; ?>">
		<header class="dwqa-header">
			<?php if ( current_user_can( 'edit_posts' ) ) { ?>
				<?php if ( dwqa_is_pending( $post_id ) ) { ?>
				<span class="dwqa-label"><?php _e( 'Pending', 'dwqa' ); ?></span>
				<?php } ?>
			<?php } ?>
			<?php if ( dwqa_is_sticky( $post_id ) ) { echo '<i class="fa fa-bookmark"></i>'; } ?>
			&nbsp;<a class="dwqa-title" href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'dwqa' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			<div class="dwqa-meta">
				<?php 
					dwqa_question_print_status( $post_id );
					echo dwqa_get_latest_action_date(); 
					echo get_the_term_list( $post_id, 'dwqa-question_category', '&nbsp;&nbsp;<strong>&sdot;</strong>&nbsp;&nbsp;<span>'.__( 'Category', 'dwqa' ).': ', ', ', '</span>' );
					do_action( 'dwqa_question_meta' ); 
				?>  
			</div>
		</header>
		<footer class="dwqa-footer-meta">
			<div class="dwqa-view"><?php $views = dwqa_question_views_count(); ?>
				<?php if ( $views > 0 ) {
						printf(
							'<strong>%d</strong> %s',
							$views,
							_n( 'view', 'views', $views, 'dwqa' )
						); ?>
				<?php } else {
						echo '<strong>0</strong> '.__( 'view','dwqa' );
					}
				?>
			</div>
			<div class="dwqa-comment">
				<?php $answer_count = dwqa_question_answers_count(); ?>
				<?php if ( $answer_count > 0 ) {
						printf(
							'<strong>%d</strong> %s',
							$answer_count,
							_n( 'answer', 'answers', $answer_count, 'dwqa' )
						); ?>
				<?php } else {
						echo '<strong>0</strong> '.__( 'answer','dwqa' );
					}
				?>
			</div>
			<div class="dwqa-vote">
				<?php $answer_vote = dwqa_vote_count(); ?>
				<?php if ( $answer_vote > 0 ) {
						printf(
							'<strong>%d</strong> %s',
							$answer_vote,
							_n( 'vote', 'votes', $answer_vote, 'dwqa' )
						); ?>
				<?php } else {
						echo '<strong>0</strong> '.__( 'vote','dwqa' );
					}
				?>
			</div>
		</footer>
	</article>