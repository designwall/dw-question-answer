<?php  

/**
 *  Inlucde all funtion for filter of dw question answer plugin
 */
class DWQA_Filter {
	public function prepare_archive_posts() {
		global $wp_query,$dwqa_general_settings;

		$posts_per_page = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : 5;
		$user = isset( $_GET['user'] ) && !empty( $_GET['user'] ) ? urldecode( $_GET['user'] ) : false;
		$filter = isset( $_GET['filter'] ) && !empty( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
		$search_text = isset( $_GET['qs'] ) ? sanitize_text_field( $_GET['qs'] ) : false;
		$sort = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : '';
		$query = array(
			'post_type' => 'dwqa-question',
			'posts_per_page' => $posts_per_page,
			'orderby'	=> 'modified'
		);
		$page_text = dwqa_is_front_page() ? 'page' : 'paged';
		$paged = get_query_var( $page_text );
		$query['paged'] = $paged ? $paged : 1;
		
		// filter by category
		$cat = get_query_var( 'dwqa-question_category' ) ? get_query_var( 'dwqa-question_category' ) : false;
		if ( $cat ) {
			$query['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_category',
				'terms' => $cat,
				'field' => 'slug'
			);
		}

		// filter by tags
		$tag = get_query_var( 'dwqa-question_tag' ) ? get_query_var( 'dwqa-question_tag' ) : false;
		if ( $tag ) {
			$query['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_tag',
				'terms' => $tag,
				'field' => 'slug'
			);

		}

		// filter by user
		if ( $user ) {
			$user = get_user_by( 'login', $user );
			$query['author'] = $user->ID;
		}


		switch ( $sort ) {
			// sort by views count
			case 'views':
				$query['meta_key'] = '_dwqa_views';
				$query['orderby'] = 'meta_value_num';
				break;

			// sort by answers count
			case 'answers':
				$query['meta_key'] = '_dwqa_answers_count';
				$query['orderby'] = 'meta_value_num';
				break;

			// sort by votes count
			case 'votes':
				$query['meta_key'] = '_dwqa_votes';
				$query['orderby'] = 'meta_value_num';
				break;
		}

		// filter by status
		switch ( $filter ) {
			case 'open':
				$query['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'open', 're-open' ),
				   'compare' => 'IN',
				);
				break;
			case 'resolved':
				$query['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'resolved' ),
				   'compare' => 'IN',
				);
				break;
			case 'closed':
				$query['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'closed', 'close' ),
				   'compare' => 'IN',
				);
				break;
			case 'unanswered':
				$query['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'open', 'pending' ),
				   'compare' => 'IN',
				);
				break;
			case 'subscribes':
				if ( $user ) {
					$query['meta_query'][] = array(
						'key'					=> '_dwqa_followers',
						'value'					=> $user->ID,
						'compare'				=> '='
					);
				}
				break;
			case 'my-questions':
				if ( is_user_logged_in() ) {
					$query['author'] = get_current_user_id();
				}
				break;
			case 'my-subscribes':
				if ( is_user_logged_in() ) {
					$query['meta_query'][] = array(
						'key'					=> '_dwqa_followers',
						'value'					=> get_current_user_id(),
						'compare'				=> '='
					);
				}
				break;
		}

		// search
		if ( $search_text ) {
			$search = sanitize_text_field( $search_text );
			preg_match_all( '/#\S*\w/i', $search_text, $matches );
			if ( $matches && is_array( $matches ) && count( $matches ) > 0 && count( $matches[0] ) > 0 ) {
				$query['tax_query'][] = array(
					'taxonomy' => 'dwqa-question_tag',
					'field' => 'slug',
					'terms' => $matches[0],
					'operator'  => 'IN',
				);
				$search = preg_replace( '/#\S*\w/i', '', $search );
			}

			$query['s'] = $search;
		}

		$sticky_questions = get_option( 'dwqa_sticky_questions' );

		if ( is_user_logged_in() ) {
			$query['post_status'] = array( 'publish', 'private' );
		}

		$query = apply_filters( 'dwqa_prepare_archive_posts', $query );

		$wp_query->dwqa_questions = new WP_Query( $query );

		// sticky question
		$sticky_questions = get_option( 'dwqa_sticky_questions' );
		if ( !empty( $sticky_questions ) && 'all' == $filter && ! $sort && !$search_text && $query['paged'] == 1 ) {

			if ( $cat ) {
				foreach( $sticky_questions as $key => $id ) {
					$terms = wp_get_post_terms( $id, 'dwqa-question_category' );
					if ( empty( $terms ) || $cat !== $terms[0]->slug ) {
						unset( $sticky_questions[ $key ] );
					}
				}
			}

			if ( $tag ) {
				foreach( $sticky_questions as $key => $id ) {
					$terms = wp_get_post_terms( $id, 'dwqa-question_tag' );
					if ( empty( $terms ) || $tag !== $terms[0]->slug ) {
						unset( $sticky_questions[ $key ] );
					}
				}
			}

			if ( $user ) {
				foreach( $sticky_questions as $key => $id ) {
					if ( $user->ID !== get_post_field( 'post_author', $id ) ) {
						unset( $sticky_questions[ $key ] );
					}
				}
			}

			if ( !is_array( $sticky_questions ) ) {
				$sticky_questions = array( $sticky_questions );
			}
			$num_posts = count( $wp_query->dwqa_questions->posts );
			$stickies_offset = 0;

			for ( $i = 0; $i < $num_posts; $i++ ) {
				if ( in_array( $wp_query->dwqa_questions->posts[ $i ]->ID, $sticky_questions ) ) {
					$sticky_post = $wp_query->dwqa_questions->posts[$i];

					array_splice( $wp_query->dwqa_questions->posts, $i, 1 );
					array_splice( $wp_query->dwqa_questions->posts, $stickies_offset, 0, array( $sticky_post ) );

					$stickies_offset++;

					$offset = array_search( $sticky_post->ID, $sticky_questions );
					unset( $sticky_questions[$offset] );
				}
			}

			if ( !empty( $sticky_questions ) ) {
				$stickies = get_posts( array(
					'post__in' 		=> $sticky_questions,
					'post_type' 	=> 'dwqa-question',
					'post_status' 	=> 'publish',
					'nopaging'		=> true
				) );

				foreach( $stickies as $sticky_post ) {
					array_splice( $wp_query->dwqa_questions->posts, $stickies_offset, 0, array( $sticky_post ) );
					$stickies_offset++;
				}
			}
			$wp_query->dwqa_questions->post_count = count( $wp_query->dwqa_questions->posts );
		}
	}

	public function after_archive_posts() {
		wp_reset_query();
		wp_reset_postdata();
	}

	public function prepare_answers( $posts, $query ) {
		global $dwqa, $dwqa_general_settings;

		if ( $query->is_single() && $query->query_vars['post_type'] == $dwqa->question->get_slug() ) {
			$question = $posts[0];
			$ans_cur_page = isset( $_GET['ans-page'] ) ? intval( $_GET['ans-page'] ) : 1;
			$posts_per_page = isset( $dwqa_general_settings['answer-per-page'] ) ?  $dwqa_general_settings['answer-per-page'] : 5;
			// We will include the all answers of this question here;
			$args = array(
				'post_type' 		=> 'dwqa-answer',
				'order'      		=> 'ASC',
				'paged'				=> $ans_cur_page,
				'meta_query' 		=> array(
					array(
						'key' => '_question',
						'value' => $question->ID
					),
				),
				'post_status' => array( 'publish', 'private', 'draft' )
			);

			if ( isset( $dwqa_general_settings['show-all-answers-on-single-question-page'] ) && $dwqa_general_settings['show-all-answers-on-single-question-page'] ) {
				$args['nopaging'] = true;
			} else {
				$args['posts_per_page'] = $posts_per_page;
			}

			$best_answer = dwqa_get_the_best_answer( $question->ID );

			$args = apply_filters( 'dwqa_prepare_answers', $args );

			$query->dwqa_answers = new WP_Query( $args );

			// best answer
			if ( $best_answer && !empty( $best_answer ) ) {
				$sticky_posts = array( $best_answer );
				$num_posts = count( $query->dwqa_answers->posts );
				$best_offset = 0;

				for( $i = 0; $i < $num_posts; $i++ ) {
					if ( $query->dwqa_answers->posts[ $i ]->ID == $best_answer ) {
						$sticky_post = $query->dwqa_answers->posts[$i];

						array_splice($query->dwqa_answers->posts, $i, 1);

						array_splice($query->dwqa_answers->posts, $best_offset, 0, array($sticky_post));

						$best_offset++;

						$offset = array_search($sticky_post->ID, $sticky_posts);
						unset( $sticky_posts[$offset] );
					}
				}

				if ( !empty($sticky_posts) ) {
					$stickies = get_posts( array(
						'post__in' => $sticky_posts,
						'post_type' => 'dwqa-answer',
						'post_status' => 'publish',
						'nopaging' => true
					) );

					foreach ( $stickies as $sticky_post ) {
						array_splice( $query->dwqa_answers->posts, $best_offset, 0, array( $sticky_post ) );
						$best_offset++;
					}
				}
			}
			$query->dwqa_answers->post_count = count( $query->dwqa_answers->posts );
		}
		return $posts;
	}

	public function __construct(){
		add_action( 'dwqa_before_questions_list', array( $this, 'prepare_archive_posts' ) );
		add_action( 'dwqa_after_questions_list', array( $this, 'after_archive_posts' ) );
		add_action( 'dwqa_after_question_stickies', array( $this, 'after_archive_posts' ) );

		//Prepare answers for single questions
		add_action( 'the_posts', array( $this, 'prepare_answers' ), 10, 2 );
	}
}
?>