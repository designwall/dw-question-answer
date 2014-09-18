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

	// Methods
	
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
					echo '<li><a href="'.add_query_arg( 'paged',1,$link ).'">1</a></li><li class="dot"><span>...</span></li>';
				}
				for ( $i = $start_page; $i < $start_page + 5; $i++ ) { 
					if ( $pages_number < $i ) {
						break;
					}
					if ( $i == $this->filter['paged'] ) {
						echo '<li class="active"><a href="'.$link.'">'.$i.'</a></li>';
					} else {
						echo '<li><a href="'.add_query_arg( 'paged',$i,$link ).'">'.$i.'</a></li>';
					}
				}

				if ( $i - 1 < $pages_number ) {
					echo '<li class="dot"><span>...</span></li><li><a href="'.add_query_arg( 'paged',$pages_number,$link ).'"> '.$pages_number.'</a></li>';
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
			case 'views':
			case 'votes':
			default:
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
			case 'views':
			case 'votes':
			default:
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
			FROM `{$wpdb->prefix}posts`, 
				( SELECT `{$wpdb->prefix}postmeta`.meta_value as question, max( `{$wpdb->prefix}posts`.post_date) as post_date 
					FROM `{$wpdb->prefix}posts`, `{$wpdb->prefix}postmeta` 
					WHERE `{$wpdb->prefix}posts`.post_type = 'dwqa-answer' 
					AND ( `{$wpdb->prefix}posts`.post_status = 'publish'
						OR `{$wpdb->prefix}posts`.post_status = 'private' ) 
					AND `{$wpdb->prefix}postmeta`.post_id = `{$wpdb->prefix}posts`.ID 
					AND `{$wpdb->prefix}postmeta`.meta_key = '_question' 
					GROUP BY question ) AS dw_latest_answer_date,
				{$wpdb->prefix}users,
				{$wpdb->prefix}usermeta

			WHERE `{$wpdb->prefix}posts`.post_status = 'publish' 
			AND `{$wpdb->prefix}posts`.post_type = 'dwqa-answer' 
			AND `{$wpdb->prefix}posts`.post_date = `dw_latest_answer_date`.post_date
			AND `{$wpdb->prefix}users`.ID = `{$wpdb->prefix}posts`.post_author
			AND `{$wpdb->prefix}usermeta`.user_id = `{$wpdb->prefix}users`.ID
			AND `{$wpdb->prefix}usermeta`.meta_key = '{$wpdb->prefix}capabilities'
			AND ( 
				`{$wpdb->prefix}usermeta`.meta_value LIKE '%administrator%' 
				OR `{$wpdb->prefix}usermeta`.meta_value LIKE '%editor%' 
				OR `{$wpdb->prefix}usermeta`.meta_value LIKE '%author%' 
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
									SELECT `{$wpdb->prefix}postmeta`.meta_value FROM 
										`{$wpdb->prefix}comments` 
									JOIN 
										( SELECT `{$wpdb->prefix}comments`.comment_ID, `{$wpdb->prefix}comments`.comment_post_ID, max( `{$wpdb->prefix}comments`.comment_date ) as comment_time FROM `{$wpdb->prefix}comments` 
										 JOIN `{$wpdb->prefix}posts` ON `{$wpdb->prefix}comments`.comment_post_ID = `{$wpdb->prefix}posts`.ID 
										 WHERE `{$wpdb->prefix}comments`.comment_approved = 1 AND `{$wpdb->prefix}posts`.post_type = 'dwqa-answer'
										 GROUP BY `{$wpdb->prefix}comments`.comment_post_ID ) as t1 
									ON `{$wpdb->prefix}comments`.comment_post_ID = t1.comment_post_ID AND `{$wpdb->prefix}comments`.comment_date = t1.comment_time 
									JOIN `{$wpdb->prefix}usermeta` ON `{$wpdb->prefix}comments`.user_id = `{$wpdb->prefix}usermeta`.user_id
									JOIN `{$wpdb->prefix}postmeta` ON `{$wpdb->prefix}postmeta`.post_id = `{$wpdb->prefix}comments`.comment_post_ID
									WHERE 1=1 AND `{$wpdb->prefix}usermeta`.meta_key = '{$wpdb->prefix}capabilities' 
										AND `{$wpdb->prefix}usermeta`.meta_value NOT LIKE '%administrator%'
										AND `{$wpdb->prefix}usermeta`.meta_value NOT LIKE '%editor%' 
										AND `{$wpdb->prefix}usermeta`.meta_value NOT LIKE '%author%'
										AND `{$wpdb->prefix}postmeta`.meta_key = '_question'
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
				$where .= "post_title LIKE '%".preg_quote( $w )."%'";
				$first = false;
			}
			$where .= ' ) ';
		}
		return $where;
	}
	
	public function auto_suggest_for_seach(){
		if ( ! isset( $_POST['nonce'])  ) {
			wp_send_json_error( array( 
				'error' => 'sercurity',
				'message' => __( 'Are you cheating huh?', 'dwqa' ) 
			) );
		}
		check_ajax_referer( '_dwqa_filter_nonce', 'nonce' );

		if ( ! isset( $_POST['title'] ) ) {
			wp_send_json_error( array( 
				'error' => 'empty title',
				'message' => __( 'Search query is empty', 'dwqa' ), 
			) );
		}

		$status = 'publish';
		if ( is_user_logged_in() ) {
			$status = array( 'publish', 'private' );
		}

		$search = sanitize_text_field( $_POST['title'] );
		$args_query = array(
			'post_type'			=> 'dwqa-question',
			'posts_per_page'	=> 6,
			'post_status'		=> $status,
		);
		preg_match_all( '/#\S*\w/i', $search, $matches );
		if ( $matches && is_array( $matches ) && count( $matches ) > 0 && count( $matches[0] ) > 0 ) {
			$args_query['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_tag',
				'field' => 'slug',
				'terms' => $matches[0],
				'operator'  => 'IN',
			);
			$search = preg_replace( '/#\S*\w/i', '', $search );
		}
		$args_query['s'] = $search;
		$query = new WP_Query( $args_query );
		if ( ! $query->have_posts() ) {
			global $current_search;
			$current_search = $search;
			add_filter( 'posts_where' , array( $this, 'posts_where_suggest' ) );
			unset( $args_query['s'] );
			$query = new WP_Query( $args_query );
			remove_filter( 'posts_where' , array( $this, 'posts_where_suggest') );
		}
		
		if ( $query->have_posts() ) {
			$html = '';
			while ( $query->have_posts() ) { $query->the_post();
				$words = explode( ' ', sanitize_text_field( $_POST['title'] ) );
				$title = get_the_title();
				foreach ( $words as $w ) {
					if ( ! $w ) continue;
					$title = preg_replace( '/( '.preg_quote( $w ).' )/i', '<strong>${1}</strong>', $title );
				}
				$html .= '<li><a href="'.get_permalink( get_the_ID() ).'" >'.$title.'</a>';
			}
			wp_reset_query();
			wp_send_json_success( array(
				'number'    => $query->post_count,
				'html'      => $html,
			) );
		} else {
			wp_reset_query();
			wp_send_json_error( array( 'error' => 'not found' ) );
		}
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
		add_action( 'wp_ajax_dwqa-filter-question', array( $this, 'filter_question' ) );
		add_action( 'wp_ajax_nopriv_dwqa-filter-question', array( $this, 'filter_question' ) );

		add_action( 'wp_ajax_dwqa-auto-suggest-search-result', array( $this, 'auto_suggest_for_seach' ) );
		add_action( 'wp_ajax_nopriv_dwqa-auto-suggest-search-result', array( $this, 'auto_suggest_for_seach' ) );

	}
}
global $dwqa_filter;
$dwqa_filter = new DWQA_Filter();



?>
