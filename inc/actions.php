<?php  
global $post_submit_filter;

$post_submit_filter     = array(
	'a'             => array(
		'href'  => array(),
		'title' => array()
	),
	'br'            => array(),
	'em'            => array(),
	'strong'        => array(),
	'code'          => array(
			'class'     => array()
		),
	'blockquote'    => array(),
	'quote'         => array(),
	'span'          => array(
		'style' => array()
	),
	'img'            => array(
			'src'    => array(),
			'alt'    => array(),
			'width'  => array(),
			'height' => array(),
			'style'  => array()
		),
	'ul'            => array(),
	'li'            => array(),
	'ol'            => array(),
	'pre'            => array(),
);

/**
 *  ANSWER
 */

/**
 * Add new answer for a specify question
 */
function dwqa_add_answer() {
	global $post_submit_filter, $dwqa_options;
	if ( ! isset( $_POST['dwqa-action'] ) || ! isset( $_POST['submit-answer'] ) ) {
		return false;
	}
	$dwqa_add_answer_errors = new WP_Error();
	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( esc_html(  $_POST['_wpnonce'] ), '_dwqa_add_new_answer' ) ) {
		$dwqa_add_answer_errors->add( 'answer_question', '"Helllo", Are you cheating huh?.' );
	}

	if ( $_POST['submit-answer'] == __( 'Delete draft', 'dwqa' ) ) {
		$draft = isset( $_POST['answer-id'] ) ? intval( $_POST['answer-id'] ) : 0;
		if ( $draft ) 
			wp_delete_post( $draft );
		return false;
	}

	if ( empty( $_POST['answer-content'] ) ||  empty( $_POST['question'] ) ) {
		if ( empty( $_POST['answer-content'] ) ) {
			$dwqa_add_answer_errors->add( 'answer_question','answer content is empty' );
		} 
		if ( empty( $_POST['question'] ) ) {
			$dwqa_add_answer_errors->add( 'answer_question','question is empty' );
		}
	} else {

		$user_id = 0;
		$is_anonymous = false;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$is_anonymous = true;
			if ( isset( $_POST['user-email'] ) && is_email( $_POST['user-email'] ) ) {
				$post_author_email = sanitize_email( $_POST['user-email'] );
			}
		}

		$question_id = intval( $_POST['question'] );
		$question = get_post( $question_id );

		$answer_title = __( 'Answer for ', 'dwqa' ) . $question->post_title;

		$answ_content = wp_kses( $_POST['answer-content'], $post_submit_filter );
		$answ_content = dwqa_pre_content_filter( $answ_content );
		
		$post_status = ( isset( $_POST['private-message'] ) && esc_html( $_POST['private-message'] ) ) ? 'private' : 'publish';
		$answers = array(
			'comment_status' => 'open',
			'post_author'    => $user_id,
			'post_content'   => $answ_content,
			'post_status'    => $post_status,
			'post_title'     => $answer_title,
			'post_type'      => 'dwqa-answer',
		);
		if ( $_POST['submit-answer'] == __( 'Save draft','dwqa' ) ) {
			$answers['post_status'] = 'draft';
		} else if ( isset( $_POST['privacy'] ) && 'private' == $_POST['privacy'] ) {
			$answers['post_status'] = 'private';
		}

		switch ( $_POST['dwqa-action'] ) {
			case 'add-answer':
				$valid_captcha = dwqa_valid_captcha( 'single-question' );

				if ( $valid_captcha ) {
					if ( dwqa_current_user_can( 'post_answer' ) ) {
						$answer_id = wp_insert_post( $answers, true );
					} else {
						$answer_id = new WP_Error( 'permission', __( 'You do not have permission to submit question.', 'dwqa' ) );
					}
					
					if ( ! is_wp_error( $answer_id ) ) {
						//Send email alert for author of question about this answer
						$question_author = $question->post_author;

						if ( user_can( $answers['post_author'], 'edit_posts' ) && $answers['post_status'] != 'draft' ) {
							update_post_meta( $question_id, '_dwqa_status', 'answered' );
							update_post_meta( $question_id, '_dwqa_answered_time', time() );
						}
						update_post_meta( $answer_id, '_question', $question_id  );

						if ( $is_anonymous ) {
							update_post_meta( $answer_id, '_dwqa_is_anonymous', true );
							if ( isset( $post_author_email ) && is_email( $post_author_email ) ) {
								update_post_meta( $answer_id, '_dwqa_anonymous_email', $post_author_email );
							}
						}
						do_action( 'dwqa_add_answer', $answer_id, $question_id );
						wp_redirect( get_permalink( $question_id ) );
						return true;
					} else {
						$dwqa_add_answer_errors = $answer_id;
					}
				} else {
					$dwqa_add_answer_errors->add( 'in_valid_captcha', __( 'Captcha is not correct','dwqa' ) );
				}
				
				break;
			case 'update-answer':
				if ( ! isset( $_POST['answer-id'] ) ) {
					wp_send_json_error( array(
						'message'   => __( 'Answer is missing', 'dwqa' )
					) );
				}

				$answer_id = intval( $_POST['answer-id'] );
				$answer_author = get_post_field( 'post_author', $answer_id  );
				
				global $current_user;

				if ( ! ( dwqa_current_user_can( 'edit_answer' ) || ( is_user_logged_in() && $answer_author == $current_user->ID ) ) ) {
					wp_send_json_error( array(
						'message'   => __( 'You do not have permission to edit this post', 'dwqa' )
					) );
				}
				if ( get_post_type( $answer_id  ) != 'dwqa-answer' ) {
					wp_send_json_error( array(
						'message'   => __( 'This post is not an answer', 'dwqa' )
					) );
				}

				$answer_update = array(
					'ID'    => $answer_id,
					'post_content'   => $answ_content,
				);
				$post_status = get_post_status( $answer_id );

				if ( ( $post_status == 'draft' && strtolower( $_POST['submit-answer'] ) == 'publish' ) || ( $post_status != 'draft' && strtolower( $_POST['submit-answer'] ) == 'update' ) ) {
					$answer_update['post_status'] = isset( $_POST['privacy'] ) && 'private' == esc_html( $_POST['privacy'] ) ? 'private' : 'publish';
					update_post_meta( $question_id, '_dwqa_status', 're-open' );
				} 
				$old_post = get_post( $answer_id  );
				$answer_id = wp_update_post( $answer_update );
				$new_post = get_post( $answer_id );
				do_action( 'dwqa_update_answer', $answer_id, $old_post, $new_post );
				if ( $answer_id ) {
					wp_safe_redirect( get_permalink( $question_id ) );
					return true;
				}
				break;
		}
	}
	$url = get_permalink( $question_id );
	$error_messages = $dwqa_add_answer_errors->get_error_messages();
	foreach ( $error_messages as $value ) {
		$url = esc_url( add_query_arg( 'errors', urlencode( $value ), $url ) );
	}

	wp_safe_redirect( $url );
}
add_action( 'wp_ajax_dwqa-add-answer', 'dwqa_add_answer' );
add_action( 'wp_ajax_nopriv_dwqa-add-answer', 'dwqa_add_answer' );


/**
 * Change redirect link when comment for answer finished
 * @param  string $location Old redirect link
 * @param  object $comment  Comment Object
 * @return string           New redirect link
 */
function dwqa_hook_redirect_comment_for_answer( $location, $comment ) {

	if ( 'dwqa-answer' == get_post_type( $comment->comment_post_ID ) ) {
		$question = get_post_meta( $comment->comment_post_ID, '_question', true );
		if ( $question ) {
			return get_post_permalink( $question ).'#'.'answer-' . $comment->comment_post_ID . '&comment='.$comment->comment_ID;
		}
	}
	return $location;
}
add_filter( 'comment_post_redirect', 'dwqa_hook_redirect_comment_for_answer',
			10, 2 );


/**
 * Displays form token for unfiltered comments. Override wp_comment_form_unfiltered_html_nonce custom for dwqa
 *
 * Backported to 2.0.10.
 *
 * @since 2.1.3
 * @uses $post Gets the ID of the current post for the token
 */
function dwqa_wp_comment_form_unfiltered_html_nonce() {
	$post = get_post();
	$post_id = $post ? $post->ID : 0;

	if ( current_user_can( 'unfiltered_html' ) 
			&& 'dwqa-answer' != get_post_type( $post_id )  ) {
		wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_disabled', false );
		echo "<script>( function() {if ( window===window.parent ) {document.getElementById( '_wp_unfiltered_html_comment_disabled' ).name='_wp_unfiltered_html_comment';}} )();</script>\n";
	} elseif ( current_user_can( 'unfiltered_html' ) 
					&& 'dwqa-answer' == get_post_type( $post_id ) ) {
						
		wp_nonce_field( 'unfiltered-html-comment_' . $post_id, '_wp_unfiltered_html_comment_answer_disabled', false );
		echo "<script>( function() {if ( window===window.parent ) {document.getElementById( '_wp_unfiltered_html_comment_answer_disabled' ).name='_wp_unfiltered_html_comment';}} )();</script>\n";
	}
}
remove_action( 'comment_form', 'wp_comment_form_unfiltered_html_nonce' );
add_action( 'comment_form', 'dwqa_wp_comment_form_unfiltered_html_nonce' );

/**
 * Remove an answer with specify id
 */
function dwqa_remove_answer() {
	if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( esc_html( $_POST['wpnonce'] ), '_dwqa_action_remove_answer_nonce' ) || ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
	}
	if ( isset( $_POST['answer_id'] ) ) {
		$answer_id = intval( $_POST['answer_id'] );
	} else {
		wp_send_json_error( array( 'message' => __( 'Missing answer ID', 'dwqa' ) ) );
	}

	global $current_user;
	$answer_author = get_post_field( 'post_author', $answer_id );

	if ( ! ( dwqa_current_user_can( 'delete_answer' ) || ( is_user_logged_in() && $answer_author == $current_user->ID ) ) ) {
		wp_send_json_error( array(
			'message'   => __( 'You do not have permission to edit this post', 'dwqa' )
		) );
	}
	if ( get_post_type( $answer_id ) != 'dwqa-answer' ) {
		wp_send_json_error( array(
			'message'   => __( 'This post is not an answer', 'dwqa' )
		) );
	}
	$question_id = get_post_meta( $answer_id, '_question', true );

	do_action( 'dwqa_delete_answer', $answer_id, $question_id );

	wp_delete_post( $answer_id );

	wp_send_json_success();
}
add_action( 'wp_ajax_dwqa-action-remove-answer', 'dwqa_remove_answer' );
/** QUESTION */

/**
 * Save question submitted
 * @return void
 */
function dwqa_submit_question() {
	global $post_submit_filter, $dwqa_options;

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

				$content = isset( $_POST['question-content'] ) ? 
							 dwqa_pre_content_filter( wp_kses( $_POST['question-content'] , $post_submit_filter ) ) : '';
				
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

				if ( dwqa_current_user_can( 'post_question' ) ) {
					$new_question = dwqa_insert_question( $postarr );
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
add_action( 'init','dwqa_submit_question', 11 );


function dwqa_insert_question( $args ) {
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
		dwqa_log_last_activity_on_question( $new_question, 'Create question', $date );
		//Call action when add question successfull
		do_action( 'dwqa_add_question', $new_question, $user_id );
	} 
	return $new_question;
}

/**
 * Return number of answer for a question
 * @param  int $question_id Question ID ( if null get ID of current post )
 * @return int      Number of answer
 */
function dwqa_question_answers_count( $question_id = null ) {
	global $wpdb;

	if ( ! $question_id ) {
		global $post;
		$question_id = $post->ID;
	}
	
	$answer_count = get_transient( 'dwqa_answer_count_for_' . $question_id );

	if ( false === $answer_count ) {
		$sql = "SELECT COUNT( DISTINCT `P`.ID ) FROM {$wpdb->postmeta} PM JOIN {$wpdb->posts} P ON `PM`.post_id = `P`.ID WHERE `PM`.meta_key = '_question' AND meta_value = {$question_id} AND `P`.post_type = 'dwqa-answer' AND `P`.post_status = 'publish'";
		$sql .= " AND ( `P`.post_status = 'publish' ";
		if ( dwqa_current_user_can( 'edit_question', $question_id ) ) {
			$sql .= " OR `P`.post_status = 'private'";
		}
		$sql .= " )";
		$answer_count = $wpdb->get_var( $sql );

		set_transient( 'dwqa_answer_count_for_' . $question_id, $answer_count, 60*60*6 );
	}

	return $answer_count;
}

/**
 * Init or increase views count for single question 
 * @return void 
 */ 
function dwqa_question_view() {
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
add_action( 'wp_head', 'dwqa_question_view' );

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

/**
 * AJAX: update post status
 * @return void 
 */
function dwqa_question_update_status() {
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
add_action( 'wp_ajax_dwqa-update-question-status', 'dwqa_question_update_status' );   



function dwqa_pre_content_filter( $content ) {
	return preg_replace_callback( '/<( code )( [^>]* )>( .* )<\/( code )[^>]*>/isU' , 'dwqa_convert_pre_entities',  $content );
}

function dwqa_convert_pre_entities( $matches ) {
	$string = $matches[0];
	preg_match( '/class=\\\"( [^\\\"]* )\\\"/', $matches[2], $sub_match );
	if ( empty( $sub_match ) ) {
		$string = str_replace( $matches[1], $matches[1] . ' class="prettyprint"', $string );
	} else {
		if ( strpos( $sub_match[1], 'prettyprint' ) === false ) {
			$new_class = str_replace( $sub_match[1], $sub_match[1] . ' prettyprint', $sub_match[0] );
			$string = str_replace( $matches[2], str_replace( $sub_match[0], $new_class, $matches[2] ), $string );
		}
	}

	$string = str_replace( $matches[3],  htmlentities( $matches[3], ENT_COMPAT , 'UTF-8', false  ), $string );
	
	return '<pre>' . $string . '</pre>';
}

function dwqa_content_html_decode( $content ) {
	return preg_replace_callback( '/( <pre> )<code( [^>]* )>( .* )<\/( code )[^>]*>( <\/pre[^>]*> )/isU' , 'dwqa_decode_pre_entities',  $content );
}

function dwqa_decode_pre_entities( $matches ) {
	$content = $matches[0];
	$content = str_replace( $matches[1], '', $content );
	$content = str_replace( $matches[5], '', $content );
	$content = str_replace( $matches[2], '', $content );
	//$content = str_replace( $matches[3], html_entity_decode( $matches[3] ), $content );
	return $content;
}

if ( ! function_exists( 'dw_strip_email_to_display' ) ) { 
	/**
	 * Strip email for display in front end
	 * @param  string  $text name
	 * @param  boolean $echo Display or just return
	 * @return string        New text that was stripped
	 */
	function dw_strip_email_to_display( $text, $echo = false ) {
		preg_match( '/( [^\@]* )\@( .* )/i', $text, $matches );
		if ( ! empty( $matches ) ) {
			$text = $matches[1] . '@...';
		}
		if ( $echo ) {
			echo $text;
		}
		return $text;
	}
}  


function dwqa_action_update_comment() {
	global $post_submit_filter;

	if ( ! isset( $_POST['comment_id']) ) {
		wp_send_json_error( array(
			'message'	=> __( 'Comment is missing', 'dwqa' )
		) );
	}
	$comment_id = intval( $_POST['comment_id'] );
	$comment_content = isset( $_POST['comment'] ) ? esc_html( $_POST['comment'] ) : '';

	if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_comment_edit_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
	}
	if ( strlen( $comment_content ) <= 0 || ! isset( $comment_id ) || ( int )$comment_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Comment content must not be empty.', 'dwqa' ) ) );
	} else {
		$commentarr = array(
			'comment_ID'        => $comment_id,
			'comment_content'   => $comment_content,
		);
		
		wp_update_comment( $commentarr );
		wp_send_json_success();
	}
}
add_action( 'wp_ajax_dwqa-action-update-comment', 'dwqa_action_update_comment' );

/**
 * AJAX:Remove comment of quest/answer away from database
 */
function dwqa_action_delete_comment() {
	if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_comment_delete_nonce' ) ) {
		wp_send_json_error( array(
			'message'   => __( 'Are you cheating huh?', 'dwqa' )  
		) );
	}
	if ( ! isset( $_POST['comment_id'] ) ) {
		wp_send_json_error( array(
			'message'   => __( 'Comment ID must be showed.', 'dwqa' )  
		) );
	}

	wp_delete_comment( intval( $_POST['comment_id'] ) );
	wp_send_json_success();
}
add_action( 'wp_ajax_dwqa-action-delete-comment', 'dwqa_action_delete_comment' );
/**
 * Flag as spam answer
 */
function dwqa_flag_answer() {
	if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wpnonce'] ), '_dwqa_action_flag_answer_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
	}
	if ( ! isset( $_POST['answer_id'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing id of answer', 'dwqa' ) ) );
	}
	global $current_user;
	$answer_id = intval( $_POST['answer_id'] );
	$flag = get_post_meta( $answer_id, '_flag', true );
	if ( ! $flag ) {
		$flag = array();
	} else {
		$flag = unserialize( $flag );
	}
	// _flag[ user_id => flag_bool , ...]
	$flag_score = 0; 
	if ( dwqa_is_user_flag( $answer_id, $current_user->ID ) ) {
		//unflag
		$flag[$current_user->ID] = $flag_score = 0;
	} else {
		$flag[$current_user->ID] = $flag_score = 1;

	}
	$flag = serialize( $flag );
	update_post_meta( $answer_id, '_flag', $flag );
	wp_send_json_success( array( 'status' => $flag_score ) );
}
add_action( 'wp_ajax_dwqa-action-flag-answer', 'dwqa_flag_answer' );

function dwqa_is_user_flag( $post_id, $user_id = null ) {
	if ( ! $user_id ) {
		global $current_user;
		if ( $current_user->ID > 0 ) {
			$user_id = $current_user->ID;
		} else {
			return false;
		}
	}
	$flag = get_post_meta( $post_id, '_flag', true );
	if ( ! $flag ) {
		return false;
	}
	$flag = unserialize( $flag );
	if ( ! is_array( $flag ) ) {
		return false;
	}
	if ( ! array_key_exists( $user_id, $flag ) ) {
		return false;
	}
	if ( $flag[$user_id] == 1 ) {
		return true;
	}
	return false;
}

function dwqa_is_answer_flag( $post_id ) {
	if ( dwqa_is_user_flag( $post_id ) ) {
		return true;
	} else {
		$flag = get_post_meta( $post_id, '_flag', true );
		if ( empty( $flag ) || ! is_array( $flag ) ) {
			return false;
		} 
		$flag = unserialize( $flag );
		$flag_point = array_sum( $flag );
		if ( $flag_point > 5 ) {
			return true;
		}
	}
	return false; //showing
}


function dwqa_is_anonymous( $post_id ) {
	$anonymous = get_post_meta( $post_id, '_dwqa_is_anonymous', true );
	if ( $anonymous ) {
		return true;
	}
	return false;
}

function dwqa_init_tinymce_editor( $args = array() ) {
	global $editor_styles;
	extract( wp_parse_args( $args, array(
			'content'       => '',
			'id'            => 'dwqa-custom-content-editor',
			'textarea_name' => 'custom-content',
			'rows'          => 5,
			'wpautop'       => false,
			'media_buttons' => false,
	) ) );
	$editor_styles = (array) $editor_styles;
	$dwqa_tinymce_css = apply_filters( 'dwqa_editor_style', array( DWQA_URI . 'assets/css/tinymce.css' ) );
	$dwqa_tinymce_css = array_merge( $editor_styles, $dwqa_tinymce_css );
	if ( ! empty( $dwqa_tinymce_css ) ) {
		$dwqa_tinymce_css = implode( ',', $dwqa_tinymce_css );
	} else {
		$dwqa_tinymce_css = implode( ',', $editor_styles );
	}
	
	wp_editor( $content, $id, array(
		'wpautop'       => $wpautop,
		'media_buttons' => $media_buttons,
		'textarea_name' => $textarea_name,
		'textarea_rows' => $rows,
		'tinymce' => array(
				'theme_advanced_buttons1' => 'bold,italic,underline,|,' . 'bullist,numlist,blockquote,|,' . 'link,unlink,|,' . 'image,code,|,'. 'spellchecker,wp_fullscreen,dwqaCodeEmbed,|,',
				'theme_advanced_buttons2'   => '',
				'content_css' => $dwqa_tinymce_css
		),
		'quicktags'     => false,
	) );
}


function dwqa_ajax_create_update_answer_editor() {

	if ( ! isset( $_POST['answer_id'] ) || ! isset( $_POST['question'] ) ) {
		return false;
	}
	extract( $_POST );

	ob_start();
	?>
	<form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-add-answer' ); ?>" method="post">
		<?php wp_nonce_field( '_dwqa_add_new_answer' ); ?>

		<?php if ( 'draft' == get_post_status( $answer_id ) && current_user_can( 'manage_options' ) ) { 
		?>
		<input type="hidden" name="dwqa-action-draft" value="true" >
		<?php } ?> 
		<input type="hidden" name="dwqa-action" value="update-answer" >
		<input type="hidden" name="answer-id" value="<?php echo $answer_id; ?>">
		<input type="hidden" name="question" value="<?php echo $question; ?>">
		<?php 
			$answer = get_post( $answer_id );
			$answer_content = get_post_field( 'post_content', $answer_id );
			dwqa_init_tinymce_editor( array(
				'content'       => wpautop( $answer_content ), 
				'textarea_name' => 'answer-content',
				'wpautop'       => false,
			) ); 
		?>
		<p class="dwqa-answer-form-btn">
			<input type="submit" name="submit-answer" class="dwqa-btn dwqa-btn-default" value="<?php _e( 'Update','dwqa' ) ?>">
			<a type="button" class="answer-edit-cancel dwqa-btn dwqa-btn-link" ><?php _e( 'Cancel','dwqa' ) ?></a>
			<?php if ( 'draft' == get_post_status( $answer_id ) && current_user_can( 'manage_options' ) ) { 
			?>
			<input type="submit" name="submit-answer" class="btn btn-primary btn-small" value="<?php _e( 'Publish','dwqa' ) ?>">
			<?php } ?>
		</p>
		<div class="dwqa-privacy">
			<input type="hidden" name="privacy" value="<?php echo $answer->post_status ?>">
			<span class="dwqa-change-privacy">
				<div class="dwqa-btn-group">
					<button type="button" class="dropdown-toggle" ><span><?php echo 'private' == get_post_status() ? '<i class="fa fa-lock"></i> '.__( 'Private','dwqa' ) : '<i class="fa fa-globe"></i> '.__( 'Public','dwqa' ); ?></span> <i class="fa fa-caret-down"></i></button>
					<div class="dwqa-dropdown-menu">
						<div class="dwqa-dropdown-caret">
							<span class="dwqa-caret-outer"></span>
							<span class="dwqa-caret-inner"></span>
						</div>
						<ul role="menu">
							<li data-privacy="publish" <?php if ( $answer->post_status == 'publish' ) { echo 'class="current"'; } ?> title="<?php _e( 'Everyone can see','dwqa' ); ?>"><a href="#"><i class="fa fa-globe"></i> <?php _e( 'Public','dwqa' ); ?></a></li>
							<li data-privacy="private"  <?php if ( $answer->post_status == 'private' ) { echo 'class="current"'; } ?>  title="<?php _e( 'Only Author and Administrator can see','dwqa' ); ?>" ><a href="#"><i class="fa fa-lock"></i> <?php _e( 'Private','dwqa' ) ?></a></li>
						</ul>
					</div>
				</div>
			</span>
		</div>
	</form>
	<?php
	$editor = ob_get_contents();
	ob_end_clean();
	wp_send_json_success( array( 'editor' => $editor ) );
}
add_action( 'wp_ajax_dwqa-editor-update-answer-init', 'dwqa_ajax_create_update_answer_editor' ); 

function dwqa_update_question() {
	global $post_submit_filter, $dwqa_options;
	
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
			$question_content = wp_kses( $_POST['dwqa-question-content'], $post_submit_filter );
			$question_content = dwqa_pre_content_filter( $question_content );
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
add_action( 'wp_ajax_dwqa-update-question', 'dwqa_update_question' );


function dwqa_ajax_create_update_question_editor() {

	if ( ! isset( $_POST['question'] ) ) {
		return false;
	}
	extract( $_POST );

	ob_start();
	?>
	<form action="<?php echo admin_url( 'admin-ajax.php?action=dwqa-update-question' ); ?>" method="post">
		<?php wp_nonce_field( '_dwqa_update_question' ); ?>

		<?php if ( 'draft' == get_post_status( $question ) && dwqa_current_user_can( 'edit_question' ) ) {  ?>
		<input type="hidden" name="dwqa-action-draft" value="true" >
		<?php } ?> 
		<input type="hidden" name="dwqa-action" value="update-question" >
		<input type="hidden" name="question" value="<?php echo $question; ?>">
		<?php $question = get_post( $question ); ?>
		<input type="text" style="width:100%" name="dwqa-question-title" id="dwqa-question-title" value="<?php echo $question->post_title; ?>">
		<?php 
			dwqa_init_tinymce_editor( array(
				'content'       => wpautop( $question->post_content ), 
				'textarea_name' => 'dwqa-question-content',
				'wpautop'       => false,
			) ); 
		?>
		<p class="dwqa-question-form-btn">
			<input type="submit" name="submit-question" class="dwqa-btn dwqa-btn-default" value="<?php _e( 'Update','dwqa' ) ?>">
			<a type="button" class="question-edit-cancel dwqa-btn dwqa-btn-link" ><?php _e( 'Cancel','dwqa' ) ?></a>
			<?php if ( 'draft' == get_post_status( $question ) && current_user_can( 'manage_options' ) ) { 
			?>
			<input type="submit" name="submit-question" class="btn btn-primary btn-small" value="<?php _e( 'Publish','dwqa' ) ?>">
			<?php } ?>
		</p>
	</form>
	<?php
	$editor = ob_get_contents();
	ob_end_clean();
	wp_send_json_success( array( 'editor' => $editor ) );
}
add_action( 'wp_ajax_dwqa-editor-update-question-init', 'dwqa_ajax_create_update_question_editor' ); 

function dwqa_comment_form_id_fields_filter( $result, $id, $replytoid ) {
	if ( 'dwqa-answer' == get_post_type( $id ) ) {
		$result = str_replace( "id='comment_post_ID'", "id='comment_post_".$id."_ID'", $result );
		$result = str_replace( "id='comment_parent'", "id='comment_".$id."_parent'", $result );
	}
	return $result;
}
add_filter( 'comment_id_fields', 'dwqa_comment_form_id_fields_filter', 10, 3 );


function dwqa_comment_action_add() {
	global $current_user;
	if ( ! dwqa_current_user_can( 'post_comment' ) ) {
		wp_send_json_error( array(
			'message'   => __( 'You can\'t post comment', 'dwqa' )
		) );
	}
	if ( ! isset( $_POST['comment_post_ID'] ) ) {
		wp_send_json_error( array(
			'message'   => __( 'Please enter your comment content', 'dwqa' )
		) );
	}
	$args = array(
		'comment_post_ID'   => intval( $_POST['comment_post_ID'] ),
		'comment_content'   => isset( $_POST['content'] ) ? wp_kses_data( $_POST['content'] ) : '',
		'comment_parent'    => isset( $_POST['comment_parent']) ? intval( $_POST['comment_parent'] ) : 0,
	);
	if ( is_user_logged_in() ) {
		$args['user_id'] = $current_user->ID;
		$args['comment_author'] = $current_user->display_name;
	} else {
		if ( ! isset( $_POST['email'] ) || ! sanitize_email( $_POST['email'] ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Missing email information','dwqa' )
			) );
		}
		if ( ! isset( $_POST['name'] ) || ! sanitize_text_field( $_POST['name'] ) ) {
			wp_send_json_error( array(
				'message'   => __( 'Missing name information','dwqa' )
			) );
		}
		$args['comment_author'] = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : 'anonymous';
		$args['comment_author_email'] = sanitize_email(  $_POST['email'] );
		$args['comment_author_url'] = isset( $_POST['url'] ) ? esc_url( $_POST['url'] ) : '';
		$args['user_id']    = -1;
	}

	$comment_id = wp_insert_comment( $args );  

	global $comment;
	$comment = get_comment( $comment_id );
	ob_start();
	$args = array( 
		'walker' => null, 
		'max_depth' => '', 
		'style' => 'ol', 
		'callback' => null, 
		'end-callback' => null, 
		'type' => 'all',
		'page' => '', 
		'per_page' => '', 
		'avatar_size' => 32, 
		'reverse_top_level' => null, 
		'reverse_children' => '', 
	);
	dwqa_question_comment_callback( $comment, $args, 0 );
	echo '</li>';
	$comment_html = ob_get_contents();
	ob_end_clean();

	$client_id = isset( $_POST['clientId'] ) ? sanitize_text_field( $_POST['clientId'] ) : false;
	do_action( 'dwqa_add_comment', $comment_id, $client_id );
	
	wp_send_json_success( array( 'html' => $comment_html ) );
}
add_action( 'wp_ajax_dwqa-comment-action-add', 'dwqa_comment_action_add' );
add_action( 'wp_ajax_nopriv_dwqa-comment-action-add', 'dwqa_comment_action_add' );


function dwqa_is_the_best_answer( $answer_id, $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	$best_answer = dwqa_get_the_best_answer( $question_id );
	if ( $best_answer && $best_answer == $answer_id ) {
		return true;
	}
	return false;
}

function dwqa_get_the_best_answer( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	if ( 'dwqa-question' != get_post_type( $question_id ) ) {
		return false;
	}

	$user_vote = get_post_meta( $question_id, '_dwqa_best_answer', true );

	if ( $user_vote && get_post( $user_vote ) ) {
		return $user_vote;
	}

	$answer_id = wp_cache_get( 'dwqa-best-answer-for-' . $question_id, 'dwqa' );

	if ( ! $answer_id ) {
		global $wpdb;
		$query = "SELECT `post_id` FROM `{$wpdb->postmeta}`
					WHERE `post_id` IN ( 
							SELECT  `post_id` FROM `{$wpdb->postmeta}` 
							WHERE `meta_key` = '_question' AND `meta_value` = {$question_id} 
					) 
					AND `meta_key` = '_dwqa_votes'
					ORDER BY CAST( `meta_value` as DECIMAL ) DESC LIMIT 0,1";

		$answer_id = $wpdb->get_var( $query );
		if ( ! $answer_id ) {
			$answer_id = -1;
		}
		wp_cache_set( 'dwqa-best-answer-for-'.$question_id, $answer_id, 'dwqa', 21600 );
	}

	if ( $answer_id && ( int ) dwqa_vote_count( $answer_id ) > 2 ) {
		return $answer_id;
	}
	return false;
}   

/**
 * Draft answer
 */

function dwqa_user_get_draft( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	if ( ! $question_id || 'dwqa-question' != get_post_type( $question_id ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}
	global $current_user;
	$args = array(
	   'post_type' => 'dwqa-answer',
	   'meta_query' => array(
			array(
				'key' => '_question',
				'value' => array( $question_id ),
				'compare' => 'IN',
			),
		),
		'post_status' => 'draft',
	);

	if ( ! current_user_can( 'edit_posts' ) ) {
		$args['author'] = $current_user->ID;
	}

	$answers = get_posts( $args );

	if ( ! empty( $answers ) ) {
		return $answers;
	}
	return false;
}

function dwqa_get_drafts( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}

	if ( ! $question_id || 'dwqa-question' != get_post_type( $question_id ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}
	global $current_user;

	$answers = get_posts(  array(
		'post_type' => 'dwqa-answer',
		'posts_per_page' => 40,
		'meta_query' => array(
			array(
				'key' => '_question',
				'value' => array( $question_id ),
				'compare' => 'IN',
			),
		),
		'post_status' => 'draft',
	) );

	if ( ! empty( $answers ) ) {
		return $answers;
	}
	return false;
}

function dwqa_get_mail_template( $option, $name = '' ) {
	if ( ! $name ) {
		return '';
	}
	$template = get_option( $option );
	if ( $template ) {
		return $template;
	} else {
		if ( file_exists( DWQA_DIR . 'inc/templates/email/'.$name.'.html' ) ) {
			ob_start();
			load_template( DWQA_DIR . 'inc/templates/email/'.$name.'.html', false );
			$template = ob_get_contents();
			ob_end_clean();
			return $template;
		} else {
			return '';
		}
	}
}


function dwqa_auto_convert_urls( $content ) {
	global $post;
	if ( is_single() && ( 'dwqa-question' == $post->post_type || 'dwqa-answer' == $post->post_type ) ) {
		$content = make_clickable( $content );
		//$content = preg_replace( '/( <a[^>]* )( > )/', '$1 target="_blank" rel="nofollow" $2', $content );
		$content = preg_replace_callback( '/<a[^>]*>]+/', 'dwqa_auto_nofollow_callback', $content );
	}
	return $content;
}
add_filter( 'the_content', 'dwqa_auto_convert_urls' );

function dwqa_auto_nofollow_callback( $matches ) {
	$link = $matches[0];
	$site_link = get_bloginfo( 'url' );
 
	if ( strpos( $link, 'rel' ) === false ) {
		$link = preg_replace( "%( href=S( ?! $site_link ))%i", 'rel="nofollow" $1', $link );
	} elseif ( preg_match( "%href=S( ?! $site_link )%i", $link ) ) {
		$link = preg_replace( '/rel=S( ?! nofollow )S*/i', 'rel="nofollow"', $link );
	}
	return $link;
}

function dwqa_sanitizie_comment( $content, $comment ) {
	$post_type = get_post_type( $comment->comment_post_ID );
	if ( $post_type == 'dwqa-question' || $post_type == 'dwqa-answer' ) {
		$content = str_replace( esc_html( '<br>' ), '<br>', esc_html( $content ) );
		$content = make_clickable( $content );
		$content = preg_replace( '/( <a[^>]* )( > )/', '$1 target="_blank" $2', $content );
	}
	return $content;
}
add_filter( 'get_comment_text', 'dwqa_sanitizie_comment', 10, 2 );

function dwqa_vote_best_answer() {
	global $current_user;
	check_ajax_referer( '_dwqa_vote_best_answer', 'nonce' );
	if ( ! isset( $_POST['answer'] ) ) {
		exit( 0 );
	}
	$answer_id = intval( $_POST['answer'] );
	$q = get_post_meta( $answer_id, '_question', true );
	$question = get_post( $q );
	if ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
		do_action( 'dwqa_vote_best_answer', $answer_id );
		update_post_meta( $q, '_dwqa_best_answer', $answer_id );
	}
}
add_action( 'wp_ajax_dwqa-vote-best-answer', 'dwqa_vote_best_answer' );


function dwqa_unvote_best_answer() {
	global $current_user;
	check_ajax_referer( '_dwqa_vote_best_answer', 'nonce' );
	if ( ! isset( $_POST['answer'] ) ) {
		exit( 0 );
	}
	$answer_id = intval( $_POST['answer'] );
	$q = get_post_meta( $answer_id, '_question', true );
	$question = get_post( $q );
	if ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) {
		do_action( 'dwqa_unvote_best_answer', $answer_id );
		delete_post_meta( $q, '_dwqa_best_answer' );
	}
	
}
add_action( 'wp_ajax_dwqa-unvote-best-answer', 'dwqa_unvote_best_answer' );

function dwqa_vote_best_answer_button() {
	global $current_user;
	$question_id = get_post_meta( get_the_ID(), '_question', true );
	$question = get_post( $question_id );
		$best_answer = dwqa_get_the_best_answer( $question_id );
		$data = is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ? 'data-answer="'.get_the_ID().'" data-nonce="'.wp_create_nonce( '_dwqa_vote_best_answer' ).'" data-ajax="true"' : 'data-ajax="false"';
	if ( get_post_status( get_the_ID() ) != 'publish' ) {
		return false;
	}
	if ( $best_answer == get_the_ID() || ( is_user_logged_in() && ( $current_user->ID == $question->post_author || current_user_can( 'edit_posts' ) ) ) ) {
		?>
		<div class="entry-vote-best <?php echo $best_answer == get_the_ID() ? 'active' : ''; ?>" <?php echo $data ?> >
			<a href="javascript:void( 0 );" title="<?php _e( 'Choose as the best answer','dwqa' ) ?>">
				<div class="entry-vote-best-bg"></div>
				<i class="icon-thumbs-up"></i>
			</a>
		</div>
		<?php
	}
}

function dwqa_user_post_count( $user_id, $post_type = 'post' ) {
	$posts = new WP_Query( array(
		'author' => $user_id,
		'post_status'		=> array( 'publish', 'private' ),
		'post_type'			=> $post_type,
		'fields' => 'ids',
	) );
	return $posts->found_posts;
}

function dwqa_user_question_count( $user_id ) {
	return dwqa_user_post_count( $user_id, 'dwqa-question' );
}

function dwqa_user_answer_count( $user_id ) {
	return dwqa_user_post_count( $user_id, 'dwqa-answer' );
}

function dwqa_user_comment_count( $user_id ) {
	global $wpdb;

	$query = "SELECT `{$wpdb->prefix}comments`.user_id, count(*) as number_comment FROM `{$wpdb->prefix}comments` JOIN `{$wpdb->prefix}posts` ON `{$wpdb->prefix}comments`.comment_post_ID = `{$wpdb->prefix}posts`.ID WHERE  1 = 1 AND  ( `{$wpdb->prefix}posts`.post_type = 'dwqa-question' OR `{$wpdb->prefix}posts`.post_type = 'dwqa-answer' ) AND  `{$wpdb->prefix}comments`.comment_approved = 1 GROUP BY `{$wpdb->prefix}comments`.user_id";

	$results = wp_cache_get( 'dwqa-user-comment-count' );
	if ( false == $results ) {
		$results = $wpdb->get_results( $query, ARRAY_A );
		wp_cache_set( 'dwqa-user-comment-count', $results );
	}

	$users_comment_count = array_filter( $results, create_function( '$a', 'return $a["user_id"] == '.$user_id.';' ) ); 
	if ( ! empty( $users_comment_count ) ) {
		$user_comment_count = array_shift( $users_comment_count );
		return $user_comment_count['number_comment'];
	}
	return false;
}

function dwqa_user_most_answer( $number = 10, $from = false, $to = false ) {
	global $wpdb;
	
	$query = "SELECT post_author, count( * ) as `answer_count` 
				FROM `{$wpdb->prefix}posts` 
				WHERE post_type = 'dwqa-answer' 
					AND post_status = 'publish'
					AND post_author <> 0";
	if ( $from ) {
		$from = date( 'Y-m-d h:i:s', $from );
		$query .= " AND `{$wpdb->prefix}posts`.post_date > '{$from}'";
	}
	if ( $to ) {
		$to = date( 'Y-m-d h:i:s', $to );
		$query .= " AND `{$wpdb->prefix}posts`.post_date < '{$to}'";
	}

	$prefix = '-all';
	if ( $from && $to ) {
		$prefix = '-' . ( $form - $to );
	}

	$query .= " GROUP BY post_author 
				ORDER BY `answer_count` DESC LIMIT 0,{$number}";
	$users = wp_cache_get( 'dwqa-most-answered' . $prefix );
	if ( false == $users ) {
		$users = $wpdb->get_results( $query, ARRAY_A  );
		wp_cache_set( 'dwqa-most-answered', $users );
	}
	return $users;            
}

function dwqa_user_most_answer_this_month( $number = 10 ) {
	$from = strtotime( 'first day of this month' );
	$to = strtotime( 'last day of this month' );
	return dwqa_user_most_answer( $number, $from, $to );
}

function dwqa_user_most_answer_last_month( $number = 10 ) {
	$from = strtotime( 'first day of last month' );
	$to = strtotime( 'last day of last month' );
	return dwqa_user_most_answer( $number, $from, $to );
}

function dwqa_get_questions_permalink() {
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
			

			$question_tag_rewrite = get_option( 'dwqa-question-tag-rewrite', 'question-tag' );
			$question_tag_rewrite = $question_tag_rewrite ? $question_tag_rewrite : 'question-tag';
			if ( isset( $args[$question_tag_rewrite] ) ) {
				if ( isset( $args['dwqa-question_tag'] ) ) {
					unset( $args['dwqa-question_tag'] );
				}
			}

			$question_category_rewrite = get_option( 'dwqa-question-category-rewrite', 'question-category' );
			$question_category_rewrite = $question_category_rewrite ? $question_category_rewrite : 'question-category';

			if ( isset( $args[$question_category_rewrite] ) ) {
				if ( isset( $args['dwqa-question_category'] ) ) {
					unset( $args['dwqa-question_category'] );
				}
				$term = get_term( $args[$question_category_rewrite], 'dwqa-question_category' );
				unset( $args[$question_category_rewrite] );
				$url = get_term_link( $term, 'dwqa-question_category' );
			} else {
				if ( isset( $args[$question_tag_rewrite] ) ) {
					$term = get_term( $args[$question_tag_rewrite], 'dwqa-question_tag' );
					unset( $args[$question_tag_rewrite] );
					$url = get_term_link( $term, 'dwqa-question_tag' );
				}
			}


			if ( $url ) {
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
add_action( 'wp_ajax_dwqa-get-questions-permalink', 'dwqa_get_questions_permalink' );
add_action( 'wp_ajax_nopriv_dwqa-get-questions-permalink', 'dwqa_get_questions_permalink' );

function dwqa_reset_permission_default() {
	global $dwqa_permission;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_dwqa_reset_permission' ) ) {
		wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
	}
	if ( isset( $_POST['type'] ) ) {
		$old = $dwqa_permission->perms;
		$type = sanitize_text_field( $_POST['type'] );
		foreach ( $dwqa_permission->defaults as $role => $perms ) {
			$dwqa_permission->perms[$role][$type] = $perms[$type];
		}
		$dwqa_permission->reset_caps( $old, $dwqa_permission->perms );
		wp_send_json_success();
	}
	wp_send_json_error();
}
add_action( 'wp_ajax_dwqa-reset-permission-default', 'dwqa_reset_permission_default' );

function dwqa_get_comments() {
	if ( isset( $_GET['post'] ) ) {
		$comments = get_comments( array(
			'post_id' => intval( $_GET['post'] ),
			'status' => 'approve',
		) );
		
		wp_list_comments( array( 
			'style' => 'ol',
			'callback'  => 'dwqa_question_comment_callback',
		), $comments ); 
	}
	exit( 0 );
}
add_action( 'wp_ajax_dwqa-get-comments', 'dwqa_get_comments' );
add_action( 'wp_ajax_nopriv_dwqa-get-comments', 'dwqa_get_comments' );

function dwqa_is_followed( $post_id, $user_id = false ) {
	if ( ! $user_id ) {
		$user = wp_get_current_user();
		$user_id = $user->ID;
	}

	if ( in_array( $user_id, get_post_meta( $post_id, '_dwqa_followers', false ) ) ) {
		return true;
	}
	return false;
}
function dwqa_follow_question() {
	check_ajax_referer( '_dwqa_follow_question', 'nonce' );
	if ( ! isset( $_POST['post'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid Post', 'dwqa' ) ) );
	}
	$question = get_post( intval( $_POST['post'] ) );
	if ( is_user_logged_in() ) {
		global $current_user;
		if ( ! dwqa_is_followed( $question->ID )  ) {
			do_action( 'dwqa_follow_question', $question->ID, $current_user->ID );
			add_post_meta( $question->ID, '_dwqa_followers', $current_user->ID );
			wp_send_json_success( array( 'code' => 'followed' ) );
		} else {
			do_action( 'dwqa_unfollow_question', $question->ID, $current_user->ID );
			delete_post_meta( $question->ID, '_dwqa_followers', $current_user->ID );
			wp_send_json_success( array( 'code' => 'unfollowed' ) );
		}
	} else {
		wp_send_json_error( array( 'code' => 'not-logged-in' ) );
	}

}
add_action( 'wp_ajax_dwqa-follow-question', 'dwqa_follow_question' );

function dwqa_stick_question() {
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
add_action( 'wp_ajax_dwqa-stick-question', 'dwqa_stick_question' );

// CAPTCHA
function dwqa_valid_captcha( $type ) {
	if ( 'question' == $type && ! dwqa_is_captcha_enable_in_submit_question() ) {
		return true;
	}

	if ( 'single-question' == $type && ! dwqa_is_captcha_enable_in_single_question() ) {
		return true;
	}
	
	global  $dwqa_general_settings;
	$private_key = isset( $dwqa_general_settings['captcha-google-private-key'] ) ?  $dwqa_general_settings['captcha-google-private-key'] : '';
	if ( ! isset( $_POST['recaptcha_challenge_field'] ) || ! isset( $_POST['recaptcha_response_field'] ) ) {
		return false;
	}
	$resp = recaptcha_check_answer(
		$private_key,
		( isset( $_SERVER['REMOTE_ADDR'] ) ? esc_url( $_SERVER['REMOTE_ADDR'] ) : '' ),
		sanitize_text_field( $_POST['recaptcha_challenge_field'] ),
		sanitize_text_field( $_POST['recaptcha_response_field'] )
	);
	if ( $resp->is_valid ) {
		return true;
	}
	return false;
}

function dwqa_is_captcha_enable() {
	global $dwqa_general_settings;
	$public_key = isset( $dwqa_general_settings['captcha-google-public-key'] ) ?  $dwqa_general_settings['captcha-google-public-key'] : '';
	$private_key = isset( $dwqa_general_settings['captcha-google-private-key'] ) ?  $dwqa_general_settings['captcha-google-private-key'] : '';

	if ( ! $public_key || ! $private_key ) {
		return false;
	}
	return true;
}

function dwqa_is_captcha_enable_in_submit_question() {
	global $dwqa_general_settings;
	$captcha_in_question = isset( $dwqa_general_settings['captcha-in-question'] ) ? $dwqa_general_settings['captcha-in-question'] : false;
	
	if ( $captcha_in_question && dwqa_is_captcha_enable() ) {
		return true;
	}
	return false;
}

function dwqa_is_captcha_enable_in_single_question() {
	global $dwqa_general_settings;
	$captcha_in_single_question = isset( $dwqa_general_settings['captcha-in-single-question'] ) ? $dwqa_general_settings['captcha-in-single-question'] : false;
	if ( $captcha_in_single_question && dwqa_is_captcha_enable() ) {
		return true;
	} 
	return false;
}


function dwqa_admin_posts_filter_restrict_manage_posts() {
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
add_action( 'restrict_manage_posts', 'dwqa_admin_posts_filter_restrict_manage_posts' );


function dwqa_posts_filter( $query ) {
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
add_filter( 'parse_query', 'dwqa_posts_filter' );

function dwqa_delete_question() {
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
add_action( 'wp_ajax_dwqa-delete-question', 'dwqa_delete_question' );

function dwqa_hook_on_remove_question( $post_id ) {
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
add_action( 'before_delete_post', 'dwqa_hook_on_remove_question' );


function dwqa_hook_on_update_anonymous_post( $data, $postarr ) {
	if ( isset( $postarr['ID'] ) && get_post_meta( $postarr['ID'], '_dwqa_is_anonymous', true ) ) {
		$data['post_author'] = 0;
	} 
	return $data;
}
add_filter( 'wp_insert_post_data', 'dwqa_hook_on_update_anonymous_post', 10, 2 );

function dwqa_comment_author_link_anonymous( $comment ) {
	// global $current_comment;
	if ( $comment->user_id <= 0 && ( get_post_type( $comment->comment_post_ID ) == 'dwqa-question' || get_post_type( $comment->comment_post_ID ) == 'dwqa-answer' ) ) {
		$comment->comment_author = __( 'Anonymous','dwqa' );
	}
	return $comment;
}
add_filter( 'get_comment', 'dwqa_comment_author_link_anonymous' );


/**
 * Hook when have new answer
 */
//add_action( 'dwqa_add_answer', 'dwqa_add_answer_logs', 10, 2 );
function dwqa_add_answer_logs( $answer_id, $question_id ) {
	// dwqa_question_answer_count( $answer_id, $question_id );
	$date = get_post_field( 'post_date', $answer_id );
	dwqa_log_last_activity_on_question( $question_id, 'Add new answer', $date );
}

/**
 * Hook when delete answer
 */
//add_action( 'dwqa_delete_answer', 'dwqa_delete_answer_logs', 10, 2 );
function dwqa_delete_answer_logs( $answer_id, $question_id ) {
	// dwqa_question_answer_count( $answer_id, $question_id );
	$date = get_post_field( 'post_date', $answer_id );
	dwqa_log_last_activity_on_question( $question_id, 'Delete answer', $date );
}

/**
 * Update answers count for question when new answer was added
 * @param  int $answer_id   new answer id
 * @param  int $question_id question id
 */
function dwqa_question_answer_count( $question_id ) {
	return dwqa_question_answer_count_by_status( $question_id, array( 'publish', 'private') );
}

function dwqa_question_answer_count_by_status( $question_id, $status = 'publish' ) {
	$query = new WP_Query( array(
		'post_type' => 'dwqa-answer',
		'post_status' => $status,
		'meta_query' => array(
			array(
				'key'	=> '_question',
				'value' => $question_id,
			),
		),
		'fields' => 'ids'
	) );
	return $query->found_posts;
}


function dwqa_log_last_activity_on_question( $question_id, $message, $date ) {
	//log activity date
	// update_post_meta( $question_id, '_dwqa_log_last_activity', $message );
	// update_post_meta( $question_id, '_dwqa_log_last_activity_date', $date );
	
}


function dwqa_table_exists( $name ) {
	global $wpdb;
	$check = wp_cache_get( 'table_exists_' . $name );
	if ( ! $check ) {
		$check = $wpdb->get_var( 'SHOW TABLES LIKE "'. $name .'"' );
		wp_cache_set( 'table_exists_', $check );
	}

	if ( $check == $name ) {
		return true;
	}
	return false;
}
?>