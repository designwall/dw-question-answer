<?php

/**
 * Get related questions            [description]
 */
function dwqa_related_question( $question_id = false, $number = 5, $echo = true ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$tag_in = $cat_in = array();
	$tags = wp_get_post_terms( $question_id, 'dwqa-question_tag' );
	if ( ! empty($tags) ) {
		foreach ( $tags as $tag ) {
			$tag_in[] = $tag->term_id;
		}
	}

	$category = wp_get_post_terms( $question_id, 'dwqa-question_category' );
	if ( ! empty($category) ) {
		foreach ( $category as $cat ) {
			$cat_in[] = $cat->term_id;
		}
	}
	$args = array(
		'orderby'       => 'rand',
		'post__not_in'  => array($question_id),
		'showposts'     => $number,
		'ignore_sticky_posts' => 1,
		'post_type'     => 'dwqa-question',
	);

	$args['tax_query']['relation'] = 'OR';
	if ( ! empty( $cat_in ) ) {
		$args['tax_query'][] = array(
			'taxonomy'  => 'dwqa-question_category',
			'field'     => 'id',
			'terms'     => $cat_in,
			'operator'  => 'IN',
		);
	}
	if ( ! empty( $tag_in ) ) {
		$args['tax_query'][] = array(
			'taxonomy'  => 'dwqa-question_tag',
			'field'     => 'id',
			'terms'     => $tag_in,
			'operator'  => 'IN',
		);
	}

	$related_questions = new WP_Query( $args );

	if ( $related_questions->have_posts() ) {
		if ( $echo ) {
			echo '<ul>';
			while ( $related_questions->have_posts() ) { $related_questions->the_post();
				echo '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__( 'asked by', 'dw-question-answer' ).' ';
				the_author_posts_link();
				echo '</li>';
			}
			echo '</ul>';
		}
	}
	$posts = $related_questions->posts;
	wp_reset_postdata();
	return $posts;
}

/**
 * Count number of views for a questions
 * @param  int $question_id Question Post ID
 * @return int Number of views
 */
function dwqa_question_views_count( $question_id = null ) {
	if ( ! $question_id ) {
		global $post;
		$question_id = $post->ID;
		if ( isset( $post->view_count ) ) {
			return $post->view_count;
		}
	}
	$views = get_post_meta( $question_id, '_dwqa_views', true );

	if ( ! $views ) {
		return 0;
	} else {
		return ( int ) $views;
	}
}

class DWQA_Posts_Question extends DWQA_Posts_Base {

	public function __construct() {
		global $dwqa_general_settings;

		if ( !$dwqa_general_settings ) {
			$dwqa_general_settings = get_option( 'dwqa_options' );
		}
		$slug = isset( $dwqa_general_settings['question-rewrite'] ) ? $dwqa_general_settings['question-rewrite'] : 'question';
		parent::__construct( 'dwqa-question', array(
			'plural' => __( 'Questions', 'dw-question-answer' ),
			'singular' => __( 'Question', 'dw-question-answer' ),
			'menu'	 => __( 'Questions', 'dw-question-answer' ),
			'rewrite' => array( 'slug' => $slug, 'with_front' => false ),
		) );

		add_action( 'manage_dwqa-question_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

		// Update view count of question, if we change single question template into shortcode, this function will need to be rewrite
		add_action( 'wp_head', array( $this, 'update_view' ) );
		//Ajax Get Questions Archive link

		add_action( 'wp_ajax_dwqa-get-questions-permalink', array( $this, 'get_questions_permalink') );
		add_action( 'wp_ajax_nopriv_dwqa-get-questions-permalink', array( $this, 'get_questions_permalink') );
		//Ajax stick question
		add_action( 'wp_ajax_dwqa-stick-question', array( $this, 'stick_question' ) );
		add_action( 'restrict_manage_posts', array( $this, 'admin_posts_filter_restrict_manage_posts' ) );

		
		// Ajax Update question status
		add_filter( 'parse_query', array( $this, 'posts_filter' ) );

		add_action( 'wp', array( $this, 'schedule_events' ) );
		add_action( 'dwqa_hourly_event', array( $this, 'do_this_hourly' ) );
		add_action( 'before_delete_post', array( $this, 'hook_on_remove_question' ) );

		//Prepare question content
		add_filter( 'dwqa_prepare_question_content', array( $this, 'pre_content_kses' ), 10 );
		add_filter( 'dwqa_prepare_question_content', array( $this, 'pre_content_filter'), 20 );
		add_filter( 'dwqa_prepare_update_question', array( $this, 'pre_content_kses'), 10 );
		add_filter( 'dwqa_prepare_update_question', array( $this, 'pre_content_filter'), 20 );
	}

	public function init() {
		$this->register_taxonomy();
	}

	public function set_supports() {
		return array( 'title', 'editor', 'comments', 'author', 'page-attributes' );
	}

	public function set_rewrite() {
		global $dwqa_general_settings;
		if( isset( $dwqa_general_settings['question-rewrite'] ) ) {
			return array(
				'slug' => $dwqa_general_settings['question-rewrite'],
				'with_front' => false,
			);
		}
		return array(
			'slug' => 'question',
			'with_front' => false,
		);
	}

	public function get_question_rewrite() {
		global $dwqa_general_settings;

		if ( !$dwqa_general_settings ) {
			$dwqa_general_settings = get_option( 'dwqa_options' );
		}

		return isset( $dwqa_general_settings['question-rewrite'] ) && !empty( $dwqa_general_settings['question-rewrite'] ) ? $dwqa_general_settings['question-rewrite'] : 'question';
	}

	public function get_category_rewrite() {
		global $dwqa_general_settings;

		if ( !$dwqa_general_settings ) {
			$dwqa_general_settings = get_option( 'dwqa_options' );
		}

		return isset( $dwqa_general_settings['question-category-rewrite'] ) && !empty( $dwqa_general_settings['question-category-rewrite'] ) ? $dwqa_general_settings['question-category-rewrite'] : 'category';
	}

	public function get_tag_rewrite() {
		global $dwqa_general_settings;

		if ( !$dwqa_general_settings ) {
			$dwqa_general_settings = get_option( 'dwqa_options' );
		}

		return isset( $dwqa_general_settings['question-tag-rewrite'] ) && !empty( $dwqa_general_settings['question-tag-rewrite'] ) ? $dwqa_general_settings['question-tag-rewrite'] : 'tag';
	}

	public function register_taxonomy() {
		global $dwqa_general_settings;

		if ( !$dwqa_general_settings ) {
			$dwqa_general_settings = get_option( 'dwqa_options' );
		}

		$cat_slug = $this->get_question_rewrite() . '/' . $this->get_category_rewrite();
		$tag_slug = $this->get_question_rewrite() . '/' . $this->get_tag_rewrite();

		$labels = array(
			'name'              => _x( 'Question Categories', 'taxonomy general name', 'dw-question-answer' ),
			'singular_name'     => _x( 'Question Category', 'taxonomy singular name', 'dw-question-answer' ),
			'search_items'      => __( 'Search Question Categories', 'dw-question-answer' ),
			'all_items'         => __( 'All Question Categories', 'dw-question-answer' ),
			'parent_item'       => __( 'Parent Question Category', 'dw-question-answer' ),
			'parent_item_colon' => __( 'Parent Question Category:', 'dw-question-answer' ),
			'edit_item'         => __( 'Edit Question Category', 'dw-question-answer' ),
			'update_item'       => __( 'Update Question Category', 'dw-question-answer' ),
			'add_new_item'      => __( 'Add New Question Category', 'dw-question-answer' ),
			'new_item_name'     => __( 'New Question Category Name', 'dw-question-answer' ),
			'menu_name'         => __( 'Question Category', 'dw-question-answer' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => false,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $cat_slug, 'with_front' => false, 'hierarchical' => true ),
			'query_var'         => true,
			'capabilities'      => array(),
		);
		register_taxonomy( $this->get_slug() . '_category', array( $this->get_slug() ), $args );

		$labels = array(
			'name'                       => _x( 'Question Tags', 'taxonomy general name', 'dw-question-answer' ),
			'singular_name'              => _x( 'Question Tag', 'taxonomy singular name', 'dw-question-answer' ),
			'search_items'               => __( 'Search Question Tags', 'dw-question-answer' ),
			'popular_items'              => __( 'Popular Question Tags', 'dw-question-answer' ),
			'all_items'                  => __( 'All Question Tags', 'dw-question-answer' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Question Tag', 'dw-question-answer' ),
			'update_item'                => __( 'Update Question Tag', 'dw-question-answer' ),
			'add_new_item'               => __( 'Add New Question Tag', 'dw-question-answer' ),
			'new_item_name'              => __( 'New Question Tag Name', 'dw-question-answer' ),
			'separate_items_with_commas' => __( 'Separate question tags with commas', 'dw-question-answer' ),
			'add_or_remove_items'        => __( 'Add or remove question tags', 'dw-question-answer' ),
			'choose_from_most_used'      => __( 'Choose from the most used question tags', 'dw-question-answer' ),
			'not_found'                  => __( 'No question tags found.', 'dw-question-answer' ),
			'menu_name'                  => __( 'Question Tags', 'dw-question-answer' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => false,
			'hierarchical'      => false,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => true,
			'rewrite'               => array( 'slug' => $tag_slug, 'with_front' => false, 'hierarchical' => true ),
			'query_var'         => true,
			'capabilities'      => array(),
		);
		register_taxonomy( $this->get_slug() . '_tag', array( $this->get_slug() ), $args );

		// Create default category for dwqa question type when dwqa plugin is actived
		$cats = get_categories( array(
			'type'                     => $this->get_slug(),
			'hide_empty'               => 0,
			'taxonomy'                 => $this->get_slug() . '_category',
		) );

		if ( empty( $cats ) ) {
			wp_insert_term( __( 'Questions', 'dw-question-answer' ), $this->get_slug() . '_category' );
		}

		// global $dwqa;
		// $dwqa->rewrite->update_term_rewrite_rules();
	}

	// ADD NEW COLUMN
	public function columns_head( $defaults ) {
		if ( isset( $_GET['post_type'] ) && esc_html( $_GET['post_type'] ) == $this->get_slug() ) {
			$defaults['info'] = __( 'Info', 'dw-question-answer' );
			$defaults = dwqa_array_insert( $defaults, array( 'question-category' => 'Category', 'question-tag' => 'Tags' ), 1 );
		}
		return $defaults;
	}

	// SHOW THE FEATURED IMAGE
	public function columns_content( $column_name, $post_ID ) {
		switch ( $column_name ) {
			case 'info':
				echo ucfirst( get_post_meta( $post_ID, '_dwqa_status', true ) ) . '<br>';
				echo '<strong>'.dwqa_question_answers_count( $post_ID ) . '</strong> '.__( 'answered', 'dw-question-answer' ) . '<br>';
				echo '<strong>'.dwqa_vote_count( $post_ID ).'</strong> '.__( 'voted', 'dw-question-answer' ) . '<br>';
				echo '<strong>'.dwqa_question_views_count( $post_ID ).'</strong> '.__( 'views', 'dw-question-answer' ) . '<br>';
				break;
			case 'question-category':
				$terms = wp_get_post_terms( $post_ID, 'dwqa-question_category' );
				$i = 0;
				foreach ( $terms as $term ) {
					if ( $i > 0 ) {
						echo ', ';
					}
					echo '<a href="'.get_term_link( $term, 'dwqa-question_category' ).'">'.$term->name . '</a> ';
					$i++;
				}
				break;
			case 'question-tag':
				$terms = wp_get_post_terms( $post_ID, 'dwqa-question_tag' );
				$i = 0;
				foreach ( $terms as $term ) {
					if ( $i > 0 ) {
						echo ', ';
					}
					echo '<a href="'.get_term_link( $term, 'dwqa-question_tag' ).'">' . $term->name . '</a> ';
					$i++;
				}
				break;
		}
	}
	
	/**
	 * Init or increase views count for single question
	 * @return void
	 */
	public function update_view() {
		global $post;
		if ( is_singular( 'dwqa-question' ) ) {
			$refer = wp_get_referer();
			if ( is_user_logged_in() ) {
				global $current_user;
				//save who see this post
				$viewed = get_post_meta( $post->ID, '_dwqa_who_viewed', true );
				$viewed = ! is_array( $viewed ) ? array() : $viewed;
				$viewed[$current_user->ID] = current_time( 'timestamp' );
			}

			if ( ( $refer && $refer != get_permalink( $post->ID ) ) || ! $refer ) {
				if ( is_single() && 'dwqa-question' == get_post_type() ) {
					$views = get_post_meta( $post->ID, '_dwqa_views', true );

					if ( ! $views ) {
						$views = 1;
					} else {
						$views = ( ( int ) $views ) + 1;
					}
					update_post_meta( $post->ID, '_dwqa_views', $views );
				}
			}
		}
	}

	public function get_questions_permalink() {
		if ( isset( $_GET['params'] ) ) {
			global $dwqa_options;
			$params = explode( '&', sanitize_text_field( $_GET['params'] ) );
			$args = array();
			if ( ! empty( $params ) ) {
				foreach ( $params as $p ) {
					if ( $p ) {
						$arr = explode( '=', $p );
						$args[$arr[0]] = $arr[1];
					}
				}
			}

			if ( ! empty( $args ) ) {
				$url = get_permalink( $dwqa_options['pages']['archive-question'] );
				$url = $url ? $url : get_post_type_archive_link( 'dwqa-question' );

				$question_tag_rewrite = $dwqa_options['question-tag-rewrite'];
				$question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
				if ( isset( $args[$question_tag_rewrite] ) ) {
					if ( isset( $args['dwqa-question_tag'] ) ) {
						unset( $args['dwqa-question_tag'] );
					}
				}

				$question_category_rewrite = $dwqa_options['question-category-rewrite'];
				$question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';

				if ( isset( $args[$question_category_rewrite] ) ) {
					if ( isset( $args['dwqa-question_category'] ) ) {
						unset( $args['dwqa-question_category'] );
					}
					$term = get_term_by( 'slug', $args[$question_category_rewrite], 'dwqa-question_category' );
					unset( $args[$question_category_rewrite] );
					$url = get_term_link( $term, 'dwqa-question_category' );
				} else {
					if ( isset( $args[$question_tag_rewrite] ) ) {
						$term = get_term_by( 'slug', $args[$question_tag_rewrite], 'dwqa-question_tag' );
						unset( $args[$question_tag_rewrite] );
						$url = get_term_link( $term, 'dwqa-question_tag' );
					}
				}


				if ( $url && ! is_wp_error( $url ) ) {
					$url = esc_url( add_query_arg( $args, $url ) );
					wp_send_json_success( array( 'url' => $url ) );
				} else {
					wp_send_json_error( array( 'error' => 'missing_questions_archive_page' ) );
				}
			} else {
				$url = get_permalink( $dwqa_options['pages']['archive-question'] );
				$url = $url ? $url : get_post_type_archive_link( 'dwqa-question' );
				wp_send_json_success( array( 'url' => $url ) );
			}
		}
		wp_send_json_error();
	}

	public function stick_question() {
		check_ajax_referer( '_dwqa_stick_question', 'nonce' );
		if ( ! isset( $_POST['post'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Post', 'dw-question-answer' ) ) );
		}

		$question = get_post( intval( $_POST['post'] ) );
		if ( is_user_logged_in() ) {
			global $current_user;
			$sticky_questions = get_option( 'dwqa_sticky_questions', array() );

			if ( ! dwqa_is_sticky( $question->ID )  ) {
				$sticky_questions[] = $question->ID;
				update_option( 'dwqa_sticky_questions', $sticky_questions );
				wp_send_json_success( array( 'code' => 'stickied' ) );
			} else {
				foreach ( $sticky_questions as $key => $q ) {
					if ( $q == $question->ID ) {
						unset( $sticky_questions[$key] );
					}
				}
				update_option( 'dwqa_sticky_questions', $sticky_questions );
				wp_send_json_success( array( 'code' => 'Unstick' ) );
			}
		} else {
			wp_send_json_error( array( 'code' => 'not-logged-in' ) );
		}
	}

	public function admin_posts_filter_restrict_manage_posts() {
		$type = 'post';
		if ( isset( $_GET['post_type'] ) ) {
			$type = sanitize_text_field( $_GET['post_type'] );
		}

		//only add filter to post type you want
		if ( 'dwqa-question' == $type ) {
			?>
			<label for="dwqa-filter-sticky-questions" style="line-height: 32px"><input type="checkbox" name="dwqa-filter-sticky-questions" id="dwqa-filter-sticky-questions" value="1" <?php checked( true, ( isset( $_GET['dwqa-filter-sticky-questions'] ) && sanitize_text_field( $_GET['post_type'] ) ) ? true : false, true ); ?>> <span class="description"><?php _e( 'Sticky Questions','dw-question-answer' ) ?></span></label>
			<?php
		}
	}

	public function posts_filter( $query ) {
		global $pagenow;
		$type = 'post';
		if ( isset( $_GET['post_type'] ) ) {
			$type = sanitize_text_field( $_GET['post_type'] );
		}
		if ( 'dwqa-question' == $type && is_admin() && $pagenow == 'edit.php' && isset( $_GET['dwqa-filter-sticky-questions'] ) && $_GET['dwqa-filter-sticky-questions'] ) {

			$sticky_questions = get_option( 'dwqa_sticky_questions' );

			if ( $sticky_questions ) {
				$query->query_vars['post__in'] = $sticky_questions;
			}
		}
		return $query;
	}


	public function delete_question() {
		$valid_ajax = check_ajax_referer( '_dwqa_delete_question', 'nonce', false );
		$nonce = isset($_POST['nonce']) ? esc_html( $_POST['nonce'] ) : false;
		if ( ! $valid_ajax || ! wp_verify_nonce( $nonce, '_dwqa_delete_question' ) || ! is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'Hello, Are you cheating huh?', 'dw-question-answer' )
			) );
		}

		if ( ! isset( $_POST['question'] ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Question is not valid','dw-question-answer' )
			) );
		}

		$question = get_post( sanitize_text_field( $_POST['question'] ) );
		global $current_user;
		if ( dwqa_current_user_can( 'delete_question', $question->ID ) ) {
			//Get all answers that is tired with this question
			do_action( 'before_delete_post', $question->ID );

			$delete = wp_delete_post( $question->ID );

			if ( $delete ) {
				global $dwqa_options;
				do_action( 'dwqa_delete_question', $question->ID );
				wp_send_json_success( array(
					'question_archive_url' => get_permalink( $dwqa_options['pages']['archive-question'] )
				) );
			} else {
				wp_send_json_error( array(
					'question'  => $question->ID,
					'message'   => __( 'Delete Action was failed','dw-question-answer' )
				) );
			}
		} else {
			wp_send_json_error( array(
				'message'   => __( 'You do not have permission to delete this question','dw-question-answer' )
			) );
		}
	}

	public function hook_on_remove_question( $post_id ) {
		if ( 'dwqa-question' == get_post_type( $post_id ) ) {
			$answers = wp_cache_get( 'dwqa-answers-for-' . $post_id, 'dwqa' );

			if ( false == $answers ) {

				$args = array(
					'post_type' => 'dwqa-answer',
					'post_parent' => $post_id,
					'post_per_page' => '-1',
					'post_status' => array('publish', 'private', 'pending')
				);

				$answers = get_posts($args);

				wp_cache_set( 'dwqa-answers-for'.$post_id, $answers, 'dwqa', 21600 );
			}

			if ( ! empty( $answers ) ) {
				foreach ( $answers as $answer ) {
					wp_trash_post( $answer->ID );
				}
			}
		}
	}

	//Auto close question when question was resolved longtime
	public function schedule_events() {
		if ( ! wp_next_scheduled( 'dwqa_hourly_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'dwqa_hourly_event' );
		}
	}

	public function do_this_hourly() {
		$closed_questions = wp_cache_get( 'dwqa-closed-question' );
		if ( false == $closed_questions ) {
			global $wpdb;
			$query = "SELECT `{$wpdb->posts}`.ID FROM `{$wpdb->posts}` JOIN `{$wpdb->postmeta}` ON `{$wpdb->posts}`.ID = `{$wpdb->postmeta}`.post_id WHERE 1=1 AND `{$wpdb->postmeta}`.meta_key = '_dwqa_status' AND `{$wpdb->postmeta}`.meta_value = 'closed' AND `{$wpdb->posts}`.post_status = 'publish' AND `{$wpdb->posts}`.post_type = 'dwqa-question'";
			$closed_questions = $wpdb->get_results( $query );

			wp_cache_set( 'dwqa-closed-question', $closed_questions );
		}

		if ( ! empty( $closed_questions ) ) {
			foreach ( $closed_questions as $q ) {
				$resolved_time = get_post_meta( $q->ID, '_dwqa_resolved_time', true );
				if ( dwqa_is_resolved( $q->ID ) && ( time() - $resolved_time > (3 * 24 * 60 * 60 ) ) ) {
					update_post_meta( $q->ID, '_dwqa_status', 'resolved' );
				}
			}
		}
	}
}

?>
