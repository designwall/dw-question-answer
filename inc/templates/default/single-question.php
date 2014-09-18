<?php 
/**
 *  Template use for display a single question
 *
 *  @since  DW Question Answer 1.0
 */
	global $current_user, $post;
?>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<?php $post_id = get_the_ID(); $post_status = get_post_status();  ?>
			<div class="dwqa-single-question">
				<!-- dwqa-status-private -->
				<div class="dwqa-question">
					<header class="dwqa-header">
						<?php if ( $post_status == 'draft' || $post_status == 'pending' ) : ?>
						<div class="dwqa-alert alert"><?php echo $current_user->ID == $post->post_author ? __( 'Your question has been submitted and is currently awaiting approval', 'dwqa' ) : __( 'This question is currently awaiting approval', 'dwqa' ); ?></div>
						<?php endif; ?>
						<?php dwqa_question_meta_button( $post_id ); ?>
						<div class="dwqa-author">
							<?php echo get_avatar( $post->post_author, 64, false ); ?>
							<span class="author">
							<?php if ( dwqa_is_anonymous( $post->ID ) ) : ?>
									<?php _e( 'Anonymous', 'dwqa' ); ?>
							<?php else : ?>
								<?php
									printf( 
										'<a href="%1$s" title="%2$s %3$s">%3$s</a>',
										get_author_posts_url( get_the_author_meta( 'ID' ) ),
										__( 'Posts by', 'dwqa' ),
										get_the_author_meta( 'display_name' ) 
									);
								?>
							<?php endif; ?>
							</span><!-- Author Info -->
							<span class="dwqa-date">
								<?php 
									printf( '<a href="%s" title="%s #%d">%s %s</a>', get_permalink(), __( 'Link to', 'dwqa' ), $post_id, __( 'asked', 'dwqa' ), get_the_date() ); 
								?>
							</span> <!-- Question Date -->
						</div>
					</header>

					<div class="dwqa-content">
						<?php the_content(); ?>
					</div>
					<?php $tags = get_the_term_list( $post_id, 'dwqa-question_tag', '<span class="dwqa-tag">', '</span><span class="dwqa-tag">', '</span>' ); ?>
					<?php if ( ! empty( $tags ) ) : ?>
					<div class="dwqa-tags"><?php echo $tags; ?></div>
					<?php endif; ?>  <!-- Question Tags -->

					<?php do_action( 'dwqa-question-content-footer' ); ?>
					
					<!-- Question footer -->
					<div class="dwqa-comments">
						<?php comments_template(); ?>
					</div>
				</div><!-- end question -->

				<div id="dwqa-answers">
					<?php dwqa_load_template( 'answers' ); ?>
				</div><!-- end dwqa-add-answers -->
			</div><!-- end dwqa-single-question -->
		<?php endwhile; // end of the loop. ?>  
	<?php endif; ?>
