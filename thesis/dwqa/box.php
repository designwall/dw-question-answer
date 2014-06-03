<?php  
/**
 * Name: DW Q&A Box
 * Author: DesignWall
 * Author URI: designwall.com
 * Version: 1.0
 * Description: DW Q&A box for Thesis 2.
 * Class: thesis_dwqa
 */
if( function_exists('dwqa_load_template') ) {
    
class thesis_dwqa extends thesis_box {
    protected function translate(){
        $this->title = __('DW Q&A Box','dwqa');
    }
    protected function html_options() {
        global $thesis;
        return array(
            'gutter'  => array(
                'type'  => 'text',
                'label' => __('Container Padding', 'dwqa'),
                'options'   => array(
                    'padding'   => __('Container padding size','dwqa')
                ),
                'tooltip' => __('By default, the container does not have a gutter. You can set padding width to make a gutter. You make it by updating the setting here.', 'dwqa')
            )
        );
    }
    public function html(){
        global $current_user, $dwqa_options;
        if( isset($this->options['gutter']) ) {
            $padding = $this->options['gutter'];
        } else {
            $padding = 0;
        }
        if( strpos($padding, 'px') === false ) {
            $padding .= 'px';
        }
        echo '<div class="dwqa-container" >';
        if( is_single() && get_post_type( get_the_ID() ) ) {
            $post_id = get_the_ID();
            echo '<div class="single-dwqa-question" style="padding:'.$padding.'">';
            $this->single_question();
            echo '</div>';
        } else if( is_page( $dwqa_options['pages']['archive-question']) || is_tax( 'dwqa-question_category' ) || is_tax( 'dwqa-question_tag' ) ) {
            echo '<div class="list-dwqa-question"  style="padding:'.$padding.'">';
            $this->archive_question();
            echo '</div>';
        } else if( is_page( $dwqa_options['pages']['submit-question']) ) {
            echo '<div class="submit-dwqa-question" style="padding:'.$padding.'">';
            dwqa_load_template( 'submit-question', 'form' );
            echo '</div>';
        }
        echo '</div>';
    }

    public function archive_question(){
    ?>
        <div id="archive-question" class="dw-question">
            <div class="dwqa-list-question">
                
                <?php dwqa_load_template('search', 'question'); ?>
                <div class="filter-bar">
                    <?php wp_nonce_field( '_dwqa_filter_nonce', '_filter_wpnonce', false ); ?>
                    <?php  
                        global $dwqa_options;
                        $submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
                    ?>
                    <?php if( $dwqa_options['pages']['submit-question'] && $submit_question_link ) { ?>
                    <a href="<?php echo $submit_question_link ?>" class="dwqa-btn dwqa-btn-success"><?php _e('Ask a question','dwqa') ?></a>
                    <?php } ?>
                    <div class="filter">
                        <li class="status">
                            <?php  
                                $selected = isset($_GET['status']) ? $_GET['status'] : 'all';
                            ?>
                            <ul>
                                <li><?php _e('Status:') ?></li>
                                <li class="<?php echo $selected == 'all' ? 'active' : ''; ?> status-all" data-type="all">
                                    <a href="#"><?php _e( 'All','dwqa' ); ?></a>
                                </li>

                                <li class="<?php echo $selected == 'open' ? 'active' : ''; ?> status-open" data-type="open">
                                    <a href="#"><?php echo current_user_can( 'edit_posts' ) ? __( 'Need Answer','dwqa' ) : __( 'Open','dwqa' ); ?></a>
                                </li>
                                <li class="<?php echo $selected == 'replied' ? 'active' : ''; ?> status-replied" data-type="replied">
                                    <a href="#"><?php _e( 'Answered','dwqa' ); ?></a>
                                </li>
                                <li class="<?php echo $selected == 'resolved' ? 'active' : ''; ?> status-resolved" data-type="resolved">
                                    <a href="#"><?php _e( 'Resolved','dwqa' ); ?></a>
                                </li>
                                <li class="<?php echo $selected == 'closed' ? 'active' : ''; ?> status-closed" data-type="closed">
                                    <a href="#"><?php _e( 'Closed','dwqa' ); ?></a>
                                </li>
                                <?php if( dwqa_current_user_can( 'edit_question' ) ) : ?>
                                <li class="<?php echo $selected == 'overdue' ? 'active' : ''; ?> status-overdue" data-type="overdue"><a href="#"><?php _e('Overdue','dwqa') ?></a></li>
                                <li class="<?php echo $selected == 'pending-review' ? 'active' : ''; ?> status-pending-review" data-type="pending-review"><a href="#"><?php _e('Queue','dwqa') ?></a></li>

                                <?php endif; ?>
                            </ul>
                        </li>
                    </div>
                    <div class="filter sort-by">
                            <div class="filter-by-category select">
                                <?php 
                                    $selected = false;
                                    $taxonomy = get_query_var( 'taxonomy' );
                                    if( $taxonomy && 'dwqa-question_category' == $taxonomy ) {
                                        $term_name = get_query_var( $taxonomy );
                                        $term = get_term_by( 'slug', $term, $taxonomy );
                                        $selected = $term->term_id;
                                    } else {

                                        $question_category_rewrite = get_option( 'dwqa-question-category-rewrite', 'question-category' );
                                        $question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';
                                        $selected =  isset($_GET[$question_category_rewrite]) ? $_GET[$question_category_rewrite] : 'all'; 
                                    }
                                    $selected_label = __('Select a category','dwqa');
                                    if( $selected  && $selected != 'all' ) {
                                        $selected_term = get_term_by( 'id', $selected, 'dwqa-question_category' );
                                        $selected_label = $selected_term->name;
                                    }
                                ?>
                                <span class="current-select"><?php echo $selected_label; ?></span>
                                <ul id="dwqa-filter-by-category" class="category-list" data-selected="<?php echo $selected; ?>">
                                <?php  
                                    wp_list_categories( array(
                                        'show_option_all'   =>  __('All','dwqa'),
                                        'show_option_none'  => __('Empty','dwqa'),
                                        'taxonomy'          => 'dwqa-question_category',
                                        'hide_empty'        => 0,
                                        'show_count'        => 0,
                                        'title_li'          => '',
                                        'walker'            => new Walker_Category_DWQA
                                    ) );
                                ?>  
                                </ul>
                            </div>
                        <?php 
                            $tag_field = '';
                            if( $taxonomy == 'dwqa-question_tag' ) {
                                $selected = false;
                                if( $taxonomy ) {
                                    $term_name = get_query_var( $taxonomy );
                                    $term = get_term_by( 'slug', $term_name, $taxonomy );
                                    $selected = $term->term_id;
                                } elseif( 'dwqa-question_category' == $taxonomy ) {

                                    $question_tag_rewrite = get_option( 'dwqa-question-tag-rewrite', 'question-tag' );
                                    $question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
                                    $selected =  isset($_GET[$question_tag_rewrite]) ? $_GET[$question_tag_rewrite] : 'all'; 
                                }
                                if( isset( $selected )  &&  $selected != 'all' ) {
                                    $tag_field = '<input type="hidden" name="dwqa-filter-by-tags" id="dwqa-filter-by-tags" value="'.$selected.'" >';
                                }
                            } 
                            $tag_field = apply_filters( 'dwqa_filter_bar', $tag_field ); 
                            echo $tag_field;
                        ?>
                        <ul class="order">
                            <li class="most-reads <?php echo isset($_GET['orderby']) && $_GET['orderby'] == 'views' ? 'active' : ''; ?>"  data-type="views" >
                                <span><?php _e('View', 'dwqa') ?></span> <i class="fa fa-sort <?php echo isset($_GET['orderby']) && $_GET['orderby'] == 'views' ? 'icon-sort-up' : ''; ?>"></i>
                            </li>
                            <li class="most-answers <?php echo isset($_GET['orderby']) && $_GET['orderby'] == 'answers' ? 'active' : ''; ?>" data-type="answers" >
                                <span href="#"><?php _e('Answer', 'dwqa') ?></span> <i class="fa fa-sort <?php echo isset($_GET['orderby']) && $_GET['orderby'] == 'answers' ? 'fa-sort-up' : ''; ?>"></i>
                            </li>
                            <li class="most-votes <?php echo isset($_GET['orderby']) && $_GET['orderby'] == 'votes' ? 'active' : ''; ?>" data-type="votes" >
                                <span><?php _e('Vote', 'dwqa') ?></span> <i class="fa fa-sort <?php echo isset($_GET['orderby']) && $_GET['orderby'] == 'votes' ? 'fa-sort-up' : ''; ?>"></i>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <?php do_action( 'dwqa-before-question-list' ); ?>

                <?php  do_action('dwqa-prepare-archive-posts');?>
                <?php if ( have_posts() ) : ?>
                <div class="questions-list">
                <input type="hidden" id="dwqa_filter_posts_per_page" name="posts_per_page" value="<?php echo get_query_var( 'posts_per_page' ); ?>">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php dwqa_load_template( 'content', 'question' ); ?>
                <?php endwhile; ?>
                </div>
                <div class="archive-question-footer">
                    <?php dwqa_load_template( 'navigation', 'archive' ); ?>

                    <?php dwqa_get_ask_question_link(); ?>
                </div>
                <?php else: ?>
                    <?php
                        if( ! dwqa_current_user_can('read_question') ) {
                            echo '<div class="alert">'.__('You do not have permission to view questions','dwqa').'</div>';
                        }
                        echo '<p class="not-found">';
                         _e('Sorry, but nothing matched your filter.', 'dwqa' );
                         if( is_user_logged_in() ) {
                            global $dwqa_options;
                            if( isset($dwqa_options['pages']['submit-question']) ) {
                                
                                $submit_link = get_permalink( $dwqa_options['pages']['submit-question'] );
                                if( $submit_link ) {
                                    _e('You can ask question <a href="'.$submit_link.'">here</a>', 'dwqa' );
                                }
                            }
                         } else {
                            _e('Please <a href="'.wp_login_url( get_post_type_archive_link( 'dwqa-question' ) ).'">Login</a>', 'dwqa' );

                            $register_link = wp_register('', '',false);
                            if( ! empty($register_link) && $register_link  ) {
                                echo __(' or','dwqa').' '.$register_link;
                            }
                            _e(' to submit question.','dwqa');
                            wp_login_form();
                         }

                        echo  '</p>';
                    ?>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    public function single_question(){
        global $post, $current_user;
        $post_id = get_the_ID();

    ?>

        <?php do_action( 'dwqa_before_page' ) ?>
        <div class="dwqa-single-question">
            <!-- dwqa-status-private -->
            <article id="question-<?php echo $post_id ?>" <?php post_class( 'dwqa-question' ); ?>>
                <header class="dwqa-header">
                    <h1 class="dwqa-title"><?php the_title(); ?></h1>
                    <div class="dwqa-meta">
                        <span class="dwqa-vote" data-type="question" data-nonce="<?php echo wp_create_nonce( '_dwqa_question_vote_nonce' ) ?>" data-question="<?php echo $post_id; ?>" >
                            <a class="dwqa-vote-dwqa-btn dwqa-vote-up" data-vote="up" href="#"  title="<?php _e('Vote Up','dwqa') ?>"><?php _e('Vote Up','dwqa') ?></a>
                            <div class="dwqa-vote-count"><?php $point = dwqa_vote_count(); echo $point > 0 ? '+'.$point:$point; ?></div>
                            <a class="dwqa-vote-dwqa-btn dwqa-vote-down" data-vote="down" href="#"  title="<?php _e('Vote Down','dwqa') ?>"><?php _e('Vote Down','dwqa') ?></a>
                        </span>

                        <?php if( is_user_logged_in() ) : ?>
                        <span data-post="<?php echo $post_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_follow_question' ); ?>" class="dwqa-favourite <?php echo dwqa_is_followed($post_id) ? 'active' : ''; ?>" title="<?php echo dwqa_is_followed($post_id) ? __('Unfollow This Question','dwqa') : __('Follow This Question','dwqa'); ?>"><!-- add class 'active' -->
                            <i class="fa fa-star"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </header>
                <div class="dwqa-content">
                    <?php the_content(); ?>
                </div>
                <?php  
                    $tags = get_the_term_list( $post_id, 'dwqa-question_tag', '<span class="dwqa-tag">', '</span><span class="dwqa-tag">', '</span>' );
                    if( ! empty($tags) ) :
                ?>
                <div class="dwqa-tags"><?php echo $tags; ?></div>
                <?php endif; ?>  <!-- Question Tags -->

                <footer class="dwqa-footer">
                    <div class="dwqa-author">
                        <?php echo get_avatar( $post->post_author, 32, false ); ?>
                        <span class="author">
                            <?php  
                                printf('<a href="%1$s" title="%2$s %3$s">%3$s</a>',
                                    get_author_posts_url( get_the_author_meta( 'ID' ) ),
                                    __('Posts by','dwqa'),
                                    get_the_author_meta(  'display_name')
                                );
                            ?>
                        </span><!-- Author Info -->
                        <span class="dwqa-date">
                            <?php 
                                printf('<a href="%s" title="%s #%d">%s %s</a>',
                                    get_permalink(),
                                    __('Link to','dwqa'),
                                    $post_id,
                                    __('asked','dwqa'),
                                    get_the_date()
                                ); 
                            ?>
                        </span> <!-- Question Date -->
                        
                        
                        <div data-post="<?php echo $post_id; ?>" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_privacy_nonce' ); ?>" data-type="question" class="dwqa-privacy">
                            <input type="hidden" name="privacy" value="<?php get_post_status(); ?>">
                            <span class="dwqa-current-privacy"> <?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> ' . __('Private','dwqa') : '<i class="fa fa-globe"></i> ' . __('Public','dwqa'); ?></span>
                            <?php if( dwqa_current_user_can('edit_question') || dwqa_current_user_can('edit_answer') || $post->post_author == $current_user->ID ) { ?>
                            <span class="dwqa-change-privacy">
                                <div class="dwqa-btn-group">
                                    <button type="button" class="dropdown-toggle" ><i class="fa fa-caret-down"></i></button>
                                    <div class="dwqa-dropdown-menu">
                                        <div class="dwqa-dropdown-caret">
                                            <span class="dwqa-caret-outer"></span>
                                            <span class="dwqa-caret-inner"></span>
                                        </div>
                                        <ul role="menu">
                                            <li title="<?php _e('Everyone can see','dwqa'); ?>" data-privacy="publish" <?php echo 'publish' == get_post_status() ? 'class="current"' : ''; ?>><a href="#"><i class="fa fa-globe"></i> <?php _e('Public','dwqa'); ?></a></li>
                                            <li title="<?php _e('Only Author and Administrator can see','dwqa'); ?>" data-privacy="private" <?php echo 'private' == get_post_status() ? 'class="current"' : ''; ?>><a href="#" ><i class="fa fa-lock"></i> <?php _e('Private','dwqa') ?></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </span>
                            <?php } ?>
                        </div><!-- post status -->
                    </div>
                    <?php  
                        $categories = wp_get_post_terms( $post_id, 'dwqa-question_category' );
                        if( ! empty($categories) ) :
                            $cat = $categories[0]
                    ?>
                    <div class="dwqa-category">
                        <span class="dwqa-category-title"><?php _e('Category','dwqa') ?></span>
                        <a class="dwqa-category-name" href="<?php echo get_term_link( $cat );  ?>" title="<?php _e('All questions from','dwqa') ?> <?php echo $cat->name ?>"><?php echo $cat->name ?></a>
                    </div>
                    <?php endif; ?> <!-- Question Categories -->

                    <?php
                        $meta = get_post_meta( $post_id, '_dwqa_status', true );
                        if( ! $meta ) {
                            $meta = 'open';
                        }
                    ?>
                    <div class="dwqa-current-status">
                        <span class="dwqa-status-title"><?php _e('Status','dwqa') ?></span>
                        <span class="dwqa-status-name"><?php echo $meta; ?></span>
                        <?php
                            if( dwqa_current_user_can('edit_question') 
                                || dwqa_current_user_can('edit_answer') 
                                || $current_user->ID == $post->post_author ) :
                        ?>
                        <span class="dwqa-change-status">
                            <div class="dwqa-btn-group">
                                <button type="button" class="dropdown-toggle" ><i class="fa fa-caret-down"></i></button>
                                <div class="dwqa-dropdown-menu" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_question_status_nonce' ) ?>" data-question="<?php the_ID(); ?>" >
                                    <div class="dwqa-dropdown-caret">
                                        <span class="dwqa-caret-outer"></span>
                                        <span class="dwqa-caret-inner"></span>
                                    </div>
                                    <ul role="menu" data-nonce="<?php echo wp_create_nonce( '_dwqa_update_question_status_nonce' ) ?>" data-question="<?php the_ID(); ?>">
                                        <?php if( 'resolved' == $meta || 'pending' == $meta || 'closed' == $meta) : ?>
                                            <li class="dwqa-re-open" data-status="re-open">
                                                <a href="#"><i class="fa fa-reply"></i> <?php _e('Re-Open','dwqa') ?></a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if( 'closed' != $meta  ) : ?>
                                            <li class="dwqa-closed" data-status="closed">
                                                <a href="#"><i class="fa fa-lock"></i> <?php _e('Closed','dwqa') ?></a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if( 'pending' != $meta && 'closed' != $meta && current_user_can( 'edit_posts', $post_id ) ) : ?>
                                            <li class="dwqa-pending"  data-status="pending">
                                                <a href="#"><i class="fa fa-question-circle"></i> <?php _e('Pending','dwqa') ?></a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if( 'resolved' != $meta && 'closed' != $meta ) : ?>
                                            <li class="dwqa-resolved" data-status="resolved">
                                                <a href="#"><i class="fa fa-check-circle-o"></i> <?php _e('Resolved','dwqa') ?></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </span>
                        <?php endif; ?> <!-- Change Question Status -->
                    </div>
                </footer>
                <div class="dwqa-comments">
                    <?php comments_template(); ?>
                </div>
            </article><!-- end question -->

            <div id="dwqa-answers">
                <?php dwqa_load_template('answers'); ?>
            </div><!-- end dwqa-add-answers -->
        </div><!-- end dwqa-single-question -->
        <?php do_action( 'dwqa_after_page' ) ?>
    <?php
    }
}
}

?>