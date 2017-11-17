<?php
/**
 * The template for displaying question content
 *
 * @package DW Question & Answer
 * @since DW Question & Answer 1.4.3
 */

?>
<div class="<?php echo dwqa_post_class(); ?>" itemscope itemtype="https://schema.org/Question">
    <div>
        <h1 class="dwqa-question-title"><a href="<?php the_permalink(); ?>"><span
                        itemprop="name headline"><?php the_title(); ?></span></a></h1>
    </div>
    <div class="dwqa-question-meta">
		<?php dwqa_question_print_status() ?>
		<?php
		global $post;
		$user_id       = get_post_field( 'post_author', get_the_ID() ) ? get_post_field( 'post_author', get_the_ID() ) : false;
		$time          = human_time_diff( get_post_time( 'U', true ) );
		$text          = __( 'asked', 'dwqa' );
		$latest_answer = dwqa_get_latest_answer();
		if ( $latest_answer ) {
			$time = human_time_diff( strtotime( $latest_answer->post_date_gmt ) );
			$text = __( 'answered', 'dwqa' );
		}
		?>
		<?php printf( __( '<span><a href="%s"><span itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name">%s%s</span></span></a> %s %s ago</span>', 'dwqa' ), dwqa_get_author_link( $user_id ), get_avatar( $user_id, 48 ), get_the_author(), $text, $time ) ?>
		<?php echo get_the_term_list( get_the_ID(), 'dwqa-question_category', '<span class="dwqa-question-category">' . __( '&nbsp;&bull;&nbsp;', 'dwqa' ), ', ', '</span>' ); ?>
    </div>
    <aside class="dwqa-question-stats">
		<span class="dwqa-views-count" itemprop="interactionStatistic" itemscope
              itemtype="https://schema.org/InteractionCounter">
			<?php $views_count = dwqa_question_views_count() ?>
            <link itemprop="interactionType" href="https://schema.org/ViewAction"/>
			<?php printf( __( '<span style="border: none;" itemprop="userInteractionCount"><strong>%1$s</strong></span> views', 'dwqa' ), $views_count ); ?>
		</span>
        <span class="dwqa-answers-count">
			<?php $answers_count = dwqa_question_answers_count(); ?>
			<?php printf( __( '<span style="border: none;" itemprop="answerCount"><strong>%1$s</strong></span> answers', 'dwqa' ), $answers_count ); ?>
		</span>
        <span class="dwqa-votes-count">
			<?php $vote_count = dwqa_vote_count() ?>
			<?php printf( __( '<span style="border: none;" itemprop="upvoteCount"><strong>%1$s</strong></span> votes', 'dwqa' ), $vote_count ); ?>
		</span>
    </aside>
    <meta itemprop="url" content="<?php the_permalink(); ?>"/>
</div>
