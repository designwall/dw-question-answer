<?php 
/**
 *  Template use for display a single question
 *
 *  @since  DW Question Answer 1.0
 */
    global $current_user;
    get_header('dwqa'); 
?>

<?php do_action( 'dwqa_before_page' ) ?>
    <?php if( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <?php $post_id = get_the_ID();  ?>
            <div id="single-question" class="dw-question">
                <article id="question-<?php the_ID(); ?>" <?php post_class(); ?>>

                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                    </header><!-- .entry-header -->

                    <div class="entry-content">
                        <?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'dwqa' ) ); ?>
                        <?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'dwqa' ), 'after' => '</div>' ) ); ?>
                    </div><!-- .entry-content -->

                    <footer class="entry-footer">
                        <div class="meta-bottom">

                        <span class="question-vote"><?php _e('Was this post useful to you?', 'dwqa') ?> 
                            <a href="" class="vote vote-up btn btn-small" data-vote="up" data-nonce="<?php echo wp_create_nonce( '_dwqa_question_vote_nonce' ) ?>" data-question="<?php echo $post_id; ?>">
                                <?php _e('Yes','dwqa'); ?>
                            </a>
                            <a href="" class="vote vote-down btn btn-small" data-vote="down" data-nonce="<?php echo wp_create_nonce( '_dwqa_question_vote_nonce' ) ?>" data-question="<?php echo $post_id; ?>">
                                <?php _e('No','dwqa'); ?>
                            </a>
                        </span>
                        <?php 
                            $question = get_post( get_the_ID() );
                            $meta = get_post_meta( $post_id, '_dwqa_status', true );
                            if( ! $meta ) {
                                $meta = 'open';
                            }

                            if( dwqa_current_user_can('edit_question') || $current_user->ID == $question->post_author ) { 
                        ?>
                        <span class="change-question-status select">
                            <span class="current-select"><?php _e('Change '.dwqa_question_get_status_name( $meta ).' to:') ?></span>

                            <ul data-nonce="<?php echo wp_create_nonce( '_dwqa_update_question_status_nonce' ) ?>" data-question="<?php the_ID(); ?>">
                            <?php 
                                if( 'resolved' == $meta || 'pending' == $meta || 'closed' == $meta) {
                                    echo '<li data-status="re-open">'.dwqa_question_get_status_name( 're-open' ).'</li>';
                                }

                                if( 'pending' != $meta && 'closed' != $meta && current_user_can( 'edit_posts', $post_id ) ) {
                                    echo '<li data-status="pending" >'.dwqa_question_get_status_name( 'pending' ).'</li>';
                                }
                                if( 'resolved' != $meta && 'closed' != $meta ) {
                                    echo '<li data-status="resolved" >'.dwqa_question_get_status_name( 'resolved' ).'</li>';
                                }

                                if( 'closed' != $meta  ) {
                                    echo '<li data-status="closed">'.dwqa_question_get_status_name( 'closed' ).'</li>';
                                }
                            ?>
                            </ul>
                        </span>

                        <?php } ?>
                        </div>

                        <?php comments_template(); ?>
                    </footer>

                </article>
                <div id="answers">
                    <?php dwqa_answers( $post_id ); ?>
                </div>
            </div>
        <?php endwhile; // end of the loop. ?>  
    <?php endif; ?>
<?php do_action( 'dwqa_after_page' ) ?>
<?php get_footer('dwqa'); ?>