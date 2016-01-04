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
				echo '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__( 'asked by', 'dwqa' ).' ';
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

function dwqa_submit_question() {
	global $dwqa;
	$dwqa->question->submit_question();
}

function dwqa_insert_question( $args ) {
	global $dwqa;
	$dwqa->insert( $args );
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
		if ( isset( $post->view_count) ) {
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
		parent::__construct( 'dwqa-question', array(
			'plural' => __( 'Questions', 'dwqa' ),
			'singular' => __( 'Question', 'dwqa' ),
			'menu'	 => __( 'DW Q&A', 'dwqa' )
		) );

		add_action( 'manage_dwqa-question_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

		add_action( 'init', array( $this, 'submit_question' ), 11 );
		// Ajax update question
		add_action( 'wp_ajax_dwqa-update-question', array( $this, 'update' ) );
		// Update view count of question, if we change single question template into shortcode, this function will need to be rewrite
		add_action( 'wp_head', array( $this, 'update_view' ) );
		//Ajax Get Questions Archive link
		
		add_action( 'wp_ajax_dwqa-get-questions-permalink', array( $this, 'get_questions_permalink') );
		add_action( 'wp_ajax_nopriv_dwqa-get-questions-permalink', array( $this, 'get_questions_permalink') );
		//Ajax stick question
		add_action( 'wp_ajax_dwqa-stick-question', array( $this, 'stick_question' ) );
		add_action( 'restrict_manage_posts', array( $this, 'admin_posts_filter_restrict_manage_posts' ) );

		add_action( 'wp_ajax_dwqa-delete-question', array( $this, 'delete_question' ) );
		// Ajax Update question status
		add_action( 'wp_ajax_dwqa-update-question-status', array( $this, 'update_status' ) ); 
		add_filter( 'parse_query', array( $this, 'posts_filter' ) );  
		
		add_action( 'wp', array( $this, 'schedule_events' ) );
		add_action( 'dwqa_hourly_event', array( $this, 'do_this_hourly' ) );
		add_action( 'before_delete_post', array( $this, 'hook_on_remove_question' ) );

		//Prepare question content
		add_filter( 'dwqa_prepare_question_content', array( $this, 'pre_content_kses' ), 10 );
		add_filter( 'dwqa_prepare_question_content', array( $this, 'pre_content_filter'), 20 );
		add_filter( 'dwqa_prepare_question_update_content', array( $this, 'pre_content_kses'), 10 );
		add_filter( 'dwqa_prepare_question_update_content', array( $this, 'pre_content_filter'), 20 );

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

	public function register_taxonomy() {
		$labels = array(
			'name'					=> _x( 'Categories', 'Taxonomy question categories', 'dwqa' ),
			'singular_name'			=> _x( 'Category', 'Taxonomy question category', 'dwqa' ),
			'search_items'			=> __( 'Search categories', 'dwqa' ),
			'popular_items'			=> __( 'Popular categories', 'dwqa' ),
			'all_items'				=> __( 'All categories', 'dwqa' ),
			'parent_item'			=> __( 'Parent category', 'dwqa' ),
			'parent_item_colon'		=> __( 'Parent category', 'dwqa' ),
			'edit_item'				=> __( 'Edit category', 'dwqa' ),
			'update_item'			=> __( 'Update category', 'dwqa' ),
			'add_new_item'			=> __( 'Add New category', 'dwqa' ),
			'new_item_name'			=> __( 'New category Name', 'dwqa' ),
			'add_or_remove_items'	=> __( 'Add or remove categories', 'dwqa' ),
			'choose_from_most_used'	=> __( 'Choose from most used dwqa', 'dwqa' ),
			'menu_name'				=> __( 'Category', 'dwqa' ),
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
			'rewrite'           => array(
				'slug' => 'question-category'
			),
			'query_var'         => true,
			'capabilities'      => array(),
		);
		register_taxonomy( $this->get_slug() . '_category', array( $this->get_slug() ), $args );

		// Question Tags
		$labels = array(
			'name'					=> _x( 'Tags', 'Taxonomy question tags', 'dwqa' ),
			'singular_name'			=> _x( 'Tag', 'Taxonomy question tag', 'dwqa' ),
			'search_items'			=> __( 'Search tags', 'dwqa' ),
			'popular_items'			=> __( 'Popular tags', 'dwqa' ),
			'all_items'				=> __( 'All tags', 'dwqa' ),
			'parent_item'			=> __( 'Parent tag', 'dwqa' ),
			'parent_item_colon'		=> __( 'Parent tag', 'dwqa' ),
			'edit_item'				=> __( 'Edit tag', 'dwqa' ),
			'update_item'			=> __( 'Update tag', 'dwqa' ),
			'add_new_item'			=> __( 'Add New tag', 'dwqa' ),
			'new_item_name'			=> __( 'New tag Name', 'dwqa' ),
			'add_or_remove_items'	=> __( 'Add or remove tags', 'dwqa' ),
			'choose_from_most_used'	=> __( 'Choose from most used dwqa', 'dwqa' ),
			'menu_name'				=> __( 'Tag', 'dwqa' ),
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
			'rewrite'           => array(
				'slug' => 'question-tag'
			),
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
			wp_insert_term( __( 'Questions', 'dwqa' ), $this->get_slug() . '_category' );
		}

		global $dwqa;
		$dwqa->rewrite->update_term_rewrite_rules();
	}

	// ADD NEW COLUMN  
	public function columns_head( $defaults ) {  
		if ( isset( $_GET['post_type'] ) && esc_html( $_GET['post_type'] ) == $this->get_slug() ) {
			$defaults['info'] = __( 'Info', 'dwqa' );
			$defaults = dwqa_array_insert( $defaults, array( 'question-category' => 'Category', 'question-tag' => 'Tags' ), 1 );
		}
		return $defaults;  
	}

	// SHOW THE FEATURED IMAGE  
	public function columns_content( $column_name, $post_ID ) {  
		switch ( $column_name ) {
			case 'info':
				echo ucfirst( get_post_meta( $post_ID, '_dwqa_status', true ) ) . '<br>';
				echo '<strong>'.dwqa_question_answers_count( $post_ID ) . '</strong> '.__( 'answered', 'dwqa' ) . '<br>';
				echo '<strong>'.dwqa_vote_count( $post_ID ).'</strong> '.__( 'voted', 'dwqa' ) . '<br>';
				echo '<strong>'.dwqa_question_views_count( $post_ID ).'</strong> '.__( 'views', 'dwqa' ) . '<br>';
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
	 * Save question submitted
	 * @return void
	 */
	public function submit_question() {
		global $dwqa_options;

		if ( isset( $_POST['dwqa-action'] ) && 'dwqa-submit-question' == esc_html( $_POST['dwqa-action'] ) ) {
			global $dwqa_current_error;
			$valid_captcha = dwqa_valid_captcha( 'question' );

			$dwqa_submit_question_errors = new WP_Error();

			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( esc_html( $_POST['_wpnonce'] ), 'dwqa-submit-question-nonce-#!' ) ) {
				if ( $valid_captcha ) {
					if ( empty( $_POST['question-title'] ) ) {

						$dwqa_submit_question_errors->add( 'submit_question', 'You must enter a valid question title' );
						return false;
					}

					$title = esc_html( $_POST['question-title'] );

					$category = isset( $_POST['question-category'] ) ? 
								intval( $_POST['question-category'] ) : 0;
					if ( ! term_exists( $category, 'dwqa-question_category' ) ) {
						$category = 0;
					}

					$tags = isset( $_POST['question-tag'] ) ? 
								esc_html( $_POST['question-tag'] ): '';

					$content = isset( $_POST['question-content'] ) ? $_POST['question-content'] : '';
					$content = apply_filters( 'dwqa_prepare_question_content', $content );
					
					$user_id = 0;
					$is_anonymous = false;
					if ( is_user_logged_in() ) {
						$user_id = get_current_user_id();
					} else {
						//$post_author_email = $_POST['user-email'];
						if ( isset( $_POST['login-type'] ) && $_POST['login-type'] == 'sign-in' ) {
							$user = wp_signon( array(
								'user_login'    => isset( $_POST['user-name'] ) ? esc_html( $_POST['user-name'] ) : '',
								'user_password' => isset( $_POST['user-password'] ) ? esc_html( $_POST['user-password'] ) : '',
							), false );

							if ( ! is_wp_error( $user ) ) {
								global $current_user;
								$current_user = $user;
								get_currentuserinfo();
								$user_id = $user->data->ID;
							} else {
								$dwqa_current_error = $user;
								return false;
							}
						} elseif ( isset( $_POST['login-type'] ) && $_POST['login-type'] == 'sign-up' ) {
							//Create new user 
							$users_can_register = get_option( 'users_can_register' );
							if ( isset( $_POST['user-email'] ) && isset( $_POST['user-name-signup'] ) 
									&& $users_can_register && ! email_exists( $_POST['user-email'] ) 
										&& ! username_exists( $_POST['user-name-signup'] ) ) {

								if ( isset( $_POST['password-signup'] ) ) {
									$password = esc_html( $_POST['password-signup'] );
								} else {
									$password = wp_generate_password( 12, false );
								}

								$user_id = wp_create_user( 
									esc_html( $_POST['user-name-signup'] ), 
									$password, 
									sanitize_email( $_POST['user-email'] )
								);
								if ( is_wp_error( $user_id ) ) {
									$dwqa_current_error = $user_id;
									return false;
								}
								wp_new_user_notification( $user_id, $password );
								$user = wp_signon( array(
									'user_login'    => esc_html( $_POST['user-name-signup'] ),
									'user_password' => $password,
								), false );
								if ( ! is_wp_error( $user ) ) {
									global $current_user;
									$current_user = $user;
									get_currentuserinfo();
									$user_id = $user->data->ID;
								} else {
									$dwqa_current_error = $user;
									return false;
								}
							} else {
								$message = '';
								if ( ! $users_can_register ) {
									$message .= __( 'User Registration was disabled.','dwqa' ).'<br>';
								}
								if ( isset( $_POST['user-name'] ) && email_exists( sanitize_email( $_POST['user-email'] ) ) ) {
									$message .= __( 'This email is already registered, please choose another one.','dwqa' ).'<br>';
								}
								if ( isset( $_POST['user-name'] ) && username_exists( esc_html( $_POST['user-name'] ) ) ) {
									$message .= __( 'This username is already registered. Please use another one.','dwqa' ).'<br>';
								}
								$dwqa_current_error = new WP_Error( 'submit_question', $message );
								return false;
							}
						} else {
							$is_anonymous = true;
							$question_author_email = isset( $_POST['_dwqa_anonymous_email'] ) && is_email( $_POST['_dwqa_anonymous_email'] ) ? sanitize_email( $_POST['_dwqa_anonymous_email'] ) : false; 
							$user_id = 0;
						}
					}

					$post_status = ( isset( $_POST['private-message'] ) && esc_html( $_POST['private-message'] ) ) ? 'private' : 'publish';

					//Enable review mode
					global $dwqa_general_settings;
					if ( isset( $dwqa_general_settings['enable-review-question'] ) 
						&& $dwqa_general_settings['enable-review-question'] 
						&& $post_status != 'private' && ! current_user_can( 'manage_options' ) ) {
						 $post_status = 'pending';
					}

					$postarr = array(
						'comment_status' => 'open',
						'post_author'    => $user_id,
						'post_content'   => $content,
						'post_status'    => $post_status,
						'post_title'     => $title,
						'post_type'      => 'dwqa-question',
						'tax_input'      => array(
							'dwqa-question_category'    => array( $category ),
							'dwqa-question_tag'         => explode( ',', $tags )
						)
					);  

					if ( apply_filters( 'dwqa-current-user-can-add-question', dwqa_current_user_can( 'post_question' ), $postarr ) ) {
						$new_question = $this->insert( $postarr );
					} else {
						$dwqa_submit_question_errors->add( 'submit_question',  __( 'You do not have permission to submit question.', 'dwqa' ) );
						$new_question = $dwqa_submit_question_errors;
					}

					if ( ! is_wp_error( $new_question ) ) {
						if ( $is_anonymous ) {
							update_post_meta( $new_question, '_dwqa_anonymous_email', $question_author_email );
							update_post_meta( $new_question, '_dwqa_is_anonymous', true );
						}
						exit( wp_safe_redirect( get_permalink( $new_question ) ) );
					}
				} else {
					$dwqa_submit_question_errors->add( 'submit_question', __( 'Captcha is not correct','dwqa' ) );
				}
			} else {
				$dwqa_submit_question_errors->add( 'submit_question', __( 'Are you cheating huh?','dwqa' ) );
			}
			$dwqa_current_error = $dwqa_submit_question_errors;
		}
	}

	public function insert( $args ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} elseif ( dwqa_current_user_can( 'post_question' ) ) {
			$user_id = 0;
		} else {
			return false;
		}

		$args = wp_parse_args( $args, array(
			'comment_status' => 'open',
			'post_author'    => $user_id,
			'post_content'   => '',
			'post_status'    => 'pending',
			'post_title'     => '',
			'post_type'      => 'dwqa-question',
		) );
			
		$new_question = wp_insert_post( $args, true );

		if ( ! is_wp_error( $new_question ) ) {

			if ( isset( $args['tax_input'] ) ) {
				foreach ( $args['tax_input'] as $taxonomy => $tags ) {
					wp_set_post_terms( $new_question, $tags, $taxonomy );
				}
			}
			update_post_meta( $new_question, '_dwqa_status', 'open' );
			update_post_meta( $new_question, '_dwqa_views', 0 );
			update_post_meta( $new_question, '_dwqa_votes', 0 );
			update_post_meta( $new_question, '_dwqa_answers_count', 0 );
			$date = get_post_field( 'post_date', $new_question );
			// dwqa_log_last_activity_on_question( $new_question, 'Create question', $date );
			//Call action when add question successfull
			do_action( 'dwqa_add_question', $new_question, $user_id );
		} 
		return $new_question;
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


	public function update() {
		global $dwqa_options;
		
		if ( ! isset( $_POST['_wpnonce'] ) 
			|| ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), '_dwqa_update_question' ) ) {
			wp_send_json_error( array( 'message' => __( 'Hello, Are you cheating huh?', 'dwqa' ) ) );
		}


		if ( isset( $_POST['dwqa-action'] ) && sanitize_text_field( $_POST['dwqa-action'] ) == 'update-question' ) {
			//Start update question
			if ( ! isset( $_POST['question'] ) ) {
				wp_send_json_error( array( 
					'message'	=> __( 'The question is missing', 'dwqa' )
				) );
			}

			$question_id = intval( $_POST['question'] );

			if ( ! dwqa_current_user_can( 'edit_question', $question_id ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to edit question', 'dwqa' ) ) );
			}

			$question_content = '';
			if ( isset( $_POST['dwqa-question-content'] ) ) {
				$question_content = apply_filters( 'dwqa_prepare_question_update_content', $_POST['dwqa-question-content'] );
				// $question_content = wp_kses( $_POST['dwqa-question-content'], $this->filter );
				// $question_content = $this->pre_content_filter( $question_content );
			} 
			$question_update = array(
				'ID'    => $question_id,
				'post_content'   => $question_content,
			);
			if ( isset( $_POST['dwqa-question-title'] ) && $_POST['dwqa-question-title'] ) {
				$question_update['post_title'] = sanitize_text_field( $_POST['dwqa-question-title'] );
			}
			$old_post = get_post( $question_id );
			$question_id = wp_update_post( $question_update );
			$new_post = get_post( $question_id );
			do_action( 'dwqa_update_question', $question_id, $old_post, $new_post );
			if ( $question_id ) {
				wp_safe_redirect( get_permalink( $question_id ) );
				return true;
			}
			break;
		}
	}
	/**
	 * AJAX: update post status
	 * @return void 
	 */
	public function update_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_dwqa_update_question_status_nonce' ) ) {
		} 
		if ( ! isset( $_POST['question'] ) ) {
			wp_die( 0 );
		}
		if ( ! isset( $_POST['status'] ) || ! in_array( sanitize_text_field( $_POST['status'] ), array( 'open', 're-open', 'resolved', 'closed', 'pending' ) ) ) {
			wp_die( 0 );
		}

		global $current_user;
		$question_id = intval( $_POST['question'] );
		$question = get_post( $question_id );

		if ( dwqa_current_user_can( 'edit_question' ) || $current_user->ID == $question->post_author ) {
			$status = sanitize_text_field( $_POST['status'] );
			update_post_meta( $question_id, '_dwqa_status', $status );
			if ( $status == 'resolved' ) {
				update_post_meta( $question_id, '_dwqa_resolved_time', time() );
			}
		} else {
			wp_send_json_error( array(
				'message'   => __( 'You do not have permission to edit question status', 'dwqa' )
			) );
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
			wp_send_json_error( array( 'message' => __( 'Invalid Post', 'dwqa' ) ) );
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
			<label for="dwqa-filter-sticky-questions" style="line-height: 32px"><input type="checkbox" name="dwqa-filter-sticky-questions" id="dwqa-filter-sticky-questions" value="1" <?php checked( true, ( isset( $_GET['dwqa-filter-sticky-questions'] ) && sanitize_text_field( $_GET['post_type'] ) ) ? true : false, true ); ?>> <span class="description"><?php _e( 'Sticky Questions','dwqa' ) ?></span></label>
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
				'message' => __( 'Hello, Are you cheating huh?', 'dwqa' )
			) );
		}

		if ( ! isset( $_POST['question'] ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Question is not valid','dwqa' )
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
					'message'   => __( 'Delete Action was failed','dwqa' )
				) );
			}
		} else {
			wp_send_json_error( array(
				'message'   => __( 'You do not have permission to delete this question','dwqa' )
			) );
		}
	}

	public function hook_on_remove_question( $post_id ) {
		if ( 'dwqa-question' == get_post_type( $post_id ) ) {
			$answers = wp_cache_get( 'dwqa-answers-for-' . $post_id, 'dwqa' );

			if ( false == $answers ) {
				global $wpdb;
				$query = "SELECT `{$wpdb->posts}`.ID FROM `{$wpdb->posts}` JOIN `{$wpdb->postmeta}` ON `{$wpdb->posts}`.ID = `{$wpdb->postmeta}`.post_id  WHERE 1=1 AND `{$wpdb->postmeta}`.meta_key = '_question' AND `{$wpdb->postmeta}`.meta_value = {$post_id} AND `{$wpdb->posts}`.post_status = 'publish' AND `{$wpdb->posts}`.post_type = 'dwqa-answer'";

				$answers = $wpdb->get_results( $query );

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