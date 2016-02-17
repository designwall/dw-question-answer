<?php  

/**
 *  Inlucde all funtion for filter of dw question answer plugin
 */
class DWQA_Filter {

	//Properties 
	private $tb_post; // Name of table of posts
	private $tb_postmeta; // Name of table of postmeta
	private $filter = array(
			'type'              => 'all',
			'posts_per_page'    => 5,
			'filter_plus'       => 'open',
			'category'          => 'all',
			'paged'             => 1,
			'tags'              => 0,
			'order'             => 'DESC',
		);  
	/**
	 * AJAX: To make filter questions for plugins
	 * @return string JSON
	 */
	public function filter_question(){
		if ( ! isset( $_POST['nonce']) ) {
			wp_die( 0 );
		}
		if ( ! check_ajax_referer( '_dwqa_filter_nonce', 'nonce', false ) ) {
			wp_die( 0 );
		}
		if ( ! isset( $_POST['type']) ) {
			wp_die( 0 );
		}
		// Make an query for
		global $wpdb;
		if ( ! defined( 'DWQA_FILTERING' ) ) {
			define( 'DWQA_FILTERING', true );
		}
		$this->filter = wp_parse_args( $_POST,$this->filter );
		//query post filter 
		add_filter( 'posts_join', array( $this, 'join_postmeta_count_answers') );
		add_filter( 'posts_orderby', array( $this, 'edit_posts_orderby') );
		add_filter( 'posts_where', array( $this, 'posts_where') );

		$questions = $this->get_questions();

		//remove query post filter 
		remove_filter( 'posts_join', array( $this, 'join_postmeta_count_answers') );
		remove_filter( 'posts_orderby', array( $this, 'edit_posts_orderby') );
		remove_filter( 'posts_where', array( $this, 'posts_where') );
		// Print content of questions
		if ( $questions->have_posts() ) {
			global $post;
			$pages_total = $questions->found_posts;
			$pages_number = ceil( $pages_total / (int) $this->filter['posts_per_page'] );
			$start_page = isset( $this->filter['paged']) && $this->filter['paged'] > 2 ? $this->filter['paged'] - 2 : 1;
			if ( $pages_number > 1 ) {
				$link = get_post_type_archive_link( 'dwqa-question' );
				ob_start();
				echo '<ul data-pages="'.$pages_number.'" >';
				echo '<li class="prev' .( $this->filter['paged'] == 1 ? ' dwqa-hide' : '' ).'"><a href="javascript:void( 0 );">Prev</a></li>';
				
				if ( $start_page > 1 ) {
					echo '<li><a href="'.esc_url(add_query_arg( 'paged',1,$link ) ).'">1</a></li><li class="dot"><span>...</span></li>';
				}
				for ( $i = $start_page; $i < $start_page + 5; $i++ ) { 
					if ( $pages_number < $i ) {
						break;
					}
					if ( $i == $this->filter['paged'] ) {
						echo '<li class="active"><a href="'.$link.'">'.$i.'</a></li>';
					} else {
						echo '<li><a href="'.esc_url( add_query_arg( 'paged',$i,$link ) ).'">'.$i.'</a></li>';
					}
				}

				if ( $i - 1 < $pages_number ) {
					echo '<li class="dot"><span>...</span></li><li><a href="'.esc_url( add_query_arg( 'paged',$pages_number,$link ) ).'"> '.$pages_number.'</a></li>';
				}
				echo '<li class="next'.( $this->filter['paged'] == $pages_number ? ' dwqa-hide' : '' ).'"><a href="javascript:void( 0 );">'.__( 'Next','dwqa' ).'</a></li>';

				echo '</ul>';
				$pagenavigation = ob_get_contents();
				ob_end_clean();
			} 

			ob_start();
			while ( $questions->have_posts() ) { $questions->the_post();
				dwqa_load_template( 'content', 'question' );
			}
			$results = ob_get_contents();
			ob_end_clean();

		} else { // Notthing found
			ob_start();
			if ( ! dwqa_current_user_can( 'read_question' ) ) {
				echo '<div class="alert">'.__( 'You do not have permission to view questions','dwqa' ).'</div>';
			}
			echo '<p class="not-found">';
			 _e( 'Sorry, but nothing matched your filter. ', 'dwqa' );
			if ( is_user_logged_in() ) {
				global $dwqa_options;
				if ( isset( $dwqa_options['pages']['submit-question']) ) {
					$submit_link = get_permalink( $dwqa_options['pages']['submit-question'] );
					if ( $submit_link ) {
						printf( 
							'%s <a href="%s">%s</a>',
							__( 'You can ask question', 'dwqa' ),
							$submit_link,
							__( 'here', 'dwqa' )
						);
					}
				}
			} else {
				printf( '%s <a href="%s">%s</a>',
					__( 'Please','dwqa' ),
					wp_login_url( get_post_type_archive_link( 'dwqa-question' ) ),
					__( 'Login','dwqa' )
				);
				$register_link = wp_register( '', '',false );
				if ( ! empty( $register_link) && $register_link  ) {
					echo __( ' or','dwqa' ).' '.$register_link;
				}
				_e( ' to submit question.','dwqa' );
			 }

			echo  '</p>';
			$results = ob_get_contents();
			ob_end_clean();
		}

		wp_send_json_success( array(
			'results'   => $results,
			'pagenavi'  => isset( $pagenavigation) ? $pagenavigation : ''
		) );
		wp_die();
	}

	private function get_questions( $on_paged = true ) {
		$posts_per_page = $this->filter['posts_per_page'] ;
		$paged = $this->filter['paged'];
		$offset = ($paged - 1) * $posts_per_page;

		// pagenavigation
		$number = -1;
		if ( $on_paged )
			$number = $posts_per_page;
		// arguments array for get questions
		$status = array( 'publish' );
		if ( is_user_logged_in() ) {
			$status[] = 'private';
			$status[] = 'pending';
		}
		$sticky_questions = get_option( 'dwqa_sticky_questions', array() );
		$args = array(
			'posts_per_page'    => $number,
			'offset'            => $offset,
			'post_type'         => 'dwqa-question',
			'suppress_filters'  => false,
			'post__not_in'      => $sticky_questions,
			'post_status'       => $status,
		);

		if ( is_user_logged_in() ) {
			$args['perm']   = 'readable';
		}

		$args['order'] = ( $this->filter['order'] && $this->filter['order'] != 'ASC' ? 'DESC' : 'ASC' );

		switch ( $this->filter['type'] ) {
			case 'views':
				$args['meta_key'] = '_dwqa_views';
				$args['orderby'] = 'meta_value_num';
				break;
			case 'votes':
				$args['meta_key'] = '_dwqa_votes';
				$args['orderby'] = 'meta_value_num';
				break;
			default : 
				$args['orderby'] = 'modified';
				break;
		}

		switch ( $this->filter['filter_plus'] ) {
			case 'resolved' :
				$args['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'resolved' ),
				   'compare' => 'IN',
				); 
				break;
			case 'replied':

				$args['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'open', 're-open', 'pending', 'answered' ),
				   'compare' => 'IN',
				);
				//not have answered by admin
				break;
			case 'overdue':
			case 'new-comment':
			case 'open' :
				$args['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'open', 're-open', 'pending' ),
				   'compare' => 'IN',
				);
				//not have answered by admin
				break;
			case 'pending-review':
				$args['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'pending' ),
				   'compare' => 'IN',
				);
				break;
			case 'closed' :
				$args['meta_query'][] = array(
				   'key' => '_dwqa_status',
				   'value' => array( 'closed' ),
				   'compare' => 'IN',
				);
				break;
		}
		if ( $this->filter['category'] != 'all' ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_category',
				'field' => 'id',
				'terms' => array( $this->filter['category'] ),
				'operator'  => 'IN',
			);
		}

		if ( (boolean) $this->filter['tags'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_tag',
				'field' => 'id',
				'terms' => array( $this->filter['tags'] ),
				'operator'  => 'IN',
			);
		}

		if ( isset( $this->filter['title'] ) && $this->filter['title'] && $this->filter['title'] !== 'Search'  ) {
			$args['s'] = $this->filter['title'];
		}

		$questions = new WP_Query( $args );
		return $questions;
	}

	function edit_posts_orderby( $orderby_statement ) {
		switch ( $this->filter['type'] ) {
			case 'answers':
				$order = ( $this->filter['order'] && $this->filter['order'] != 'ASC' ? 'DESC' : 'ASC' );
				$orderby_statement = 'count_answer.dwqa_answers '. $order;
				break;
			case 'views';
			case 'votes';
				break;
			
			default:
				// $order = ( $this->filter['order'] && $this->filter['order'] != 'ASC' ? 'DESC' : 'ASC' );
				// $orderby_statement = $this->order_filter_default( $orderby_statement, $order );
				break;
		}
		return $orderby_statement;
	}

	// Join for searching metadata
	function join_postmeta_count_answers( $join ) {
		global $wp_query, $wpdb;
		switch ( $this->filter['type'] ) {
			case 'answers':
					$join .= "LEFT JOIN 
							( SELECT meta_value AS question, COUNT( * ) AS dwqa_answers
							FROM $wpdb->postmeta
							WHERE meta_key =  '_question'
							GROUP BY meta_value ) AS count_answer
						ON $wpdb->posts.ID = count_answer.question
					";
				break;
			case 'views';
			case 'votes';
				break;
			default:
				// $join = $this->join_filter_default( $join );
				break;
		}
		return $join;
	}


	public function join_filter_default( $join ) {
		global $wpdb;
		
		$join .= "LEFT JOIN 
				( SELECT $wpdb->postmeta.meta_value as question, max( $wpdb->posts.post_modified) as post_modified 
					FROM $wpdb->posts, $wpdb->postmeta
					WHERE 
						$wpdb->posts.post_type = 'dwqa-answer'
						AND ( 
							$wpdb->posts.post_status = 'publish'";
		if ( is_user_logged_in() ) {
			$join .= " OR $wpdb->posts.post_status = 'private' ";
			$join .= " OR $wpdb->posts.post_status = 'pending' ";
		}
		$join .= ")
				AND $wpdb->postmeta.post_id = $wpdb->posts.ID 
				AND $wpdb->postmeta.meta_key = '_question'
			GROUP BY question ) as dw_table_latest_answers 
		ON $wpdb->posts.ID = dw_table_latest_answers.question ";

		return $join;
	}

	public function order_filter_default( $orderby_statement, $order = 'DESC' ) {
		global $wpdb;
		return " ifnull( dw_table_latest_answers.post_modified, $wpdb->posts.post_modified) ".$order;
	}

	public function posts_where_filter_default( $where ) {
		global $current_user;
		$manager = 0;
		if ( dwqa_current_user_can( 'edit_question' ) ) {
			$manager = 1;
		}
		$where .= " AND if ( post_status = 'private', if ( $manager = 1, 1, if ( post_author = $current_user->ID, 1, 0 ) ), 1 ) = 1";
		$where .= " AND if ( post_status = 'pending', if ( $manager = 1, 1, if ( post_author = $current_user->ID, 1, 0 ) ), 1 ) = 1";
		return $where;
	}

	// Filter post where
	public function posts_where( $where ) {
		global $wpdb, $dwqa_general_settings;
		$get_question_answered_query = "SELECT `dw_latest_answer_date`.question
			FROM `{$wpdb->posts}`, 
				( SELECT `{$wpdb->postmeta}`.meta_value as question, max( `{$wpdb->posts}`.post_date) as post_date 
					FROM `{$wpdb->posts}`, `{$wpdb->postmeta}` 
					WHERE `{$wpdb->posts}`.post_type = 'dwqa-answer' 
					AND ( `{$wpdb->posts}`.post_status = 'publish'
						OR `{$wpdb->posts}`.post_status = 'private' ) 
					AND `{$wpdb->postmeta}`.post_id = `{$wpdb->posts}`.ID 
					AND `{$wpdb->postmeta}`.meta_key = '_question' 
					GROUP BY question ) AS dw_latest_answer_date,
				{$wpdb->users},
				{$wpdb->usermeta}

			WHERE `{$wpdb->posts}`.post_status = 'publish' 
			AND `{$wpdb->posts}`.post_type = 'dwqa-answer' 
			AND `{$wpdb->posts}`.post_date = `dw_latest_answer_date`.post_date
			AND `{$wpdb->users}`.ID = `{$wpdb->posts}`.post_author
			AND `{$wpdb->usermeta}`.user_id = `{$wpdb->users}`.ID
			AND `{$wpdb->usermeta}`.meta_key = '{$wpdb->prefix}capabilities'
			AND ( 
				`{$wpdb->usermeta}`.meta_value LIKE '%administrator%' 
				OR `{$wpdb->usermeta}`.meta_value LIKE '%editor%' 
				OR `{$wpdb->usermeta}`.meta_value LIKE '%author%' 
			)";

		switch ( $this->filter['filter_plus'] ) {
			case 'overdue' :
				$overdue_time_frame = isset( $dwqa_general_settings['question-overdue-time-frame']) ? $dwqa_general_settings['question-overdue-time-frame'] : 2;
				$where .= " AND post_date < '" . date( 'Y-m-d H:i:s', strtotime( '-'.$overdue_time_frame.' days' ) ) . "'";
			case 'open':
				// answered
				$where .= ' AND ID NOT IN (' . $get_question_answered_query . ' )';
				break;
			case 'replied':
				// answered
				$where .= ' AND ID IN (' . $get_question_answered_query. ' )';
				break;
			case 'new-comment':
				if ( current_user_can( 'edit_posts' ) ) {
					$where .= " AND ID IN (
									SELECT `{$wpdb->postmeta}`.meta_value FROM 
										`{$wpdb->comments}` 
									JOIN 
										( SELECT `{$wpdb->comments}`.comment_ID, `{$wpdb->comments}`.comment_post_ID, max( `{$wpdb->comments}`.comment_date ) as comment_time FROM `{$wpdb->comments}` 
										 JOIN `{$wpdb->posts}` ON `{$wpdb->comments}`.comment_post_ID = `{$wpdb->posts}`.ID 
										 WHERE `{$wpdb->comments}`.comment_approved = 1 AND `{$wpdb->posts}`.post_type = 'dwqa-answer'
										 GROUP BY `{$wpdb->comments}`.comment_post_ID ) as t1 
									ON `{$wpdb->comments}`.comment_post_ID = t1.comment_post_ID AND `{$wpdb->comments}`.comment_date = t1.comment_time 
									JOIN `{$wpdb->usermeta}` ON `{$wpdb->comments}`.user_id = `{$wpdb->usermeta}`.user_id
									JOIN `{$wpdb->postmeta}` ON `{$wpdb->postmeta}`.post_id = `{$wpdb->comments}`.comment_post_ID
									WHERE 1=1 AND `{$wpdb->usermeta}`.meta_key = '{$wpdb->prefix}capabilities' 
										AND `{$wpdb->usermeta}`.meta_value NOT LIKE '%administrator%'
										AND `{$wpdb->usermeta}`.meta_value NOT LIKE '%editor%' 
										AND `{$wpdb->usermeta}`.meta_value NOT LIKE '%author%'
										AND `{$wpdb->postmeta}`.meta_key = '_question'
								) ";
				}
				# code...
				break;
		}

		return $where;
	}

	public function where_title( $where ){
		if ( isset( $_POST['title'] ) ) {
			$where .= " AND post_title LIKE '%".preg_quote( sanitize_text_field( $_POST['title'] ) )."%'";
		}
		return $where;
	}
	
	public function posts_where_suggest( $where ) {
		global $current_search;
		$first = true;
		$s = explode( ' ', $current_search );
		if ( count( $s ) > 0 ) {
			$where .= ' AND (';
			foreach ( $s as $w ) {
				if ( ! $first ) {
					$where .= ' OR ';
				}
				$where .= "post_title REGEXP '".preg_quote( $w )."'";
				$first = false;
			}
			$where .= ' ) ';
		}
		return $where;
	}

	public function prepare_archive_posts() {
		global $wp_query,$dwqa_general_settings;

		$posts_per_page = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : 5;
		$user = isset( $_GET['user'] ) && !empty( $_GET['user'] ) ? urldecode( $_GET['user'] ) : false;
		$filter = isset( $_GET['filter'] ) && !empty( $_GET['filter'] ) ? $_GET['filter'] : 'all';
		$search_text = isset( $_GET['qs'] ) ? $_GET['qs'] : false;
		$sort = isset( $_GET['sort'] ) ? $_GET['sort'] : '';
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

		// exclude sticky question
		if ( $sticky_questions && 'all' == $filter && !$sort && !$search_text ) {
			$query['post__not_in'] = $sticky_questions;
		}

		if ( is_user_logged_in() ) {
			$query['post_status'] = array( 'publish', 'private' );
		}

		$query = apply_filters( 'dwqa_prepare_archive_posts', $query );

		$wp_query->dwqa_questions = new WP_Query( $query );
	}

	public function sticky_question() {
		global $wp_query;
		$sticky_questions = get_option( 'dwqa_sticky_questions' );

		$user = isset( $_GET['user'] ) && !empty( $_GET['user'] ) ? urldecode( $_GET['user'] ) : false;
		$filter = isset( $_GET['filter'] ) && !empty( $_GET['filter'] ) ? $_GET['filter'] : 'all';
		$search_text = isset( $_GET['qs'] ) ? $_GET['qs'] : false;
		$sort = isset( $_GET['sort'] ) ? $_GET['sort'] : '';

		$cat = get_query_var( 'dwqa-question_category' ) ? get_query_var( 'dwqa-question_category' ) : false;
		$tag = get_query_var( 'dwqa-question_tag' ) ? get_query_var( 'dwqa-question_tag' ) : false;

		if ( $sticky_questions && 'all' == $filter && !$search_text && !$sort ) {
			if ( $cat ) {
				foreach( $sticky_questions as $key => $id ) {
					$terms = wp_get_post_terms( $id, 'dwqa-question_category' );

					if ( empty( $terms ) || $cat !== $terms[0]->slug ) {
						unset( $sticky_questions[ $key ] );
					}
				}

				$args['tax_query'][] = array(
					'taxonomy' => 'dwqa-question_category',
					'terms' => $cat,
					'field' => 'slug'
				);
			}

			if ( $tag ) {
				foreach( $sticky_questions as $key => $id ) {
					$terms = wp_get_post_terms( $id, 'dwqa-question_tag' );

					if ( empty( $terms ) || $tag !== $terms[0]->slug ) {
						unset( $sticky_questions[ $key ] );
					}
				}
				
				$args['tax_query'][] = array(
					'taxonomy' => 'dwqa-question_category',
					'terms' => $cat,
					'field' => 'slug'
				);
			}

			if ( $user ) {
				$user = get_user_by( 'login', $user );
				foreach( $sticky_questions as $key => $id ) {
					if ( $user->data->ID !== get_post_field( 'post_author', $id ) ) {
						unset( $sticky_questions[ $key ] );
					}
				}
			}
			
			$args = array(
				'posts_per_page' => 40,
				'post_type' => 'dwqa-question',
				'post__in' => $sticky_questions,
			);

			if ( !empty( $sticky_questions ) ) {
				$wp_query->dwqa_question_stickies = new WP_Query( $args );
			}
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
		global $wpdb;
		//Init
		$prefix = wp_cache_get( 'dwqa-database-prefix' );
		if ( false == $prefix ) {
			$prefix = $wpdb->prefix;
			wp_cache_set( 'dwqa-database-prefix', $prefix );
		}

		$this->tb_posts = $prefix . 'posts';
		$this->tb_postmeta = $prefix . 'postmeta';
		$table = $prefix . 'dwqa_question_index';
		$filter = array( $this, 'filter_question' );
		
		add_action( 'wp_ajax_dwqa-filter-question', $filter );
		add_action( 'wp_ajax_nopriv_dwqa-filter-question', $filter );

		add_action( 'dwqa_before_questions_list', array( $this, 'prepare_archive_posts' ) );
		add_action( 'dwqa_after_questions_list', array( $this, 'after_archive_posts' ) );
		add_action( 'dwqa_before_question_stickies', array( $this, 'sticky_question' ) );
		add_action( 'dwqa_after_question_stickies', array( $this, 'after_archive_posts' ) );

		//Prepare answers for single questions
		add_action( 'the_posts', array( $this, 'prepare_answers' ), 10, 2 );
	}
}
?>