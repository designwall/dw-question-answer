<?php

class DWQA_Handle {
	public function __construct() {
		// question
		add_action( 'wp_loaded', array( $this, 'submit_question' ), 11 );
		add_action( 'wp_loaded', array( $this, 'update_question' ) );

		// answer
		add_action( 'wp_loaded', array( $this, 'insert_answer') );
		add_action( 'wp_loaded', array( $this, 'update_answer' ) );

		// comment
		add_action( 'wp_loaded', array( $this, 'insert_comment' ) );
		add_action( 'wp_loaded', array( $this, 'update_comment' ) );
	}

	public function insert_answer() {
		global $dwqa_options;
		if ( ! isset( $_POST['dwqa-action'] ) || ! isset( $_POST['submit-answer'] ) ) {
			return false;
		}
		// do_action( 'dwqa_add_answer', $answer_id, $question_id );
		// die();
		if ( 'add-answer' !== sanitize_text_field( $_POST['dwqa-action'] ) ) {
			return false;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( esc_html(  $_POST['_wpnonce'] ), '_dwqa_add_new_answer' ) ) {
			dwqa_add_notice( __( '&quot;Helllo&quot;, Are you cheating huh?.', 'dwqa' ), 'error' );
		}

		if ( sanitize_text_field( $_POST['submit-answer'] ) == __( 'Delete draft', 'dwqa' ) ) {
			$draft = isset( $_POST['answer-id'] ) ? intval( $_POST['answer-id'] ) : 0;
			if ( $draft )
				wp_delete_post( $draft );
		}

		if ( empty( $_POST['answer-content'] ) ) {
			dwqa_add_notice( __( 'Answer content is empty', 'dwqa' ), 'error' );
		}
		if ( empty( $_POST['question_id'] ) ) {
			dwqa_add_notice( __( 'Question is empty', 'dwqa' ), 'error' );
		}

		if ( !dwqa_current_user_can( 'post_answer' ) ) {
			dwqa_add_notice( __( 'You do not have permission to submit question.', 'dwqa' ), 'error' );
		}

		if ( !is_user_logged_in() && ( empty( $_POST['user-email'] ) || !is_email( sanitize_email( $_POST['user-email'] ) ) ) ) {
			dwqa_add_notice( __( 'Missing email information', 'dwqa' ), 'error' );
		}

		if ( !is_user_logged_in() && ( empty( $_POST['user-name'] ) ) ) {
			dwqa_add_notice( __( 'Missing name information', 'dwqa' ), 'error' );
		}

		if ( !dwqa_valid_captcha( 'single-question' ) ) {
			dwqa_add_notice( __( 'Captcha is not correct', 'dwqa' ), 'error' );
		}

		$user_id = 0;
		$is_anonymous = false;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$is_anonymous = true;
			if ( isset( $_POST['user-email'] ) && is_email( $_POST['user-email'] ) ) {
				$post_author_email = sanitize_email( $_POST['user-email'] );
			}
			if ( isset( $_POST['user-name'] ) && !empty( $_POST['user-name'] ) ) {
				$post_author_name = sanitize_text_field( $_POST['user-name'] );
			}
		}

		$question_id = intval( $_POST['question_id'] );

		$answer_title = __( 'Answer for ', 'dwqa' ) . get_post_field( 'post_title', $question_id );
		$answ_content = apply_filters( 'dwqa_prepare_answer_content', $_POST['answer-content'] );

		$answers = array(
			'comment_status' => 'open',
			'post_author'    => $user_id,
			'post_content'   => $answ_content,
			'post_title'     => $answer_title,
			'post_type'      => 'dwqa-answer',
			'post_parent'	 => $question_id,
		);

		$answers['post_status'] = isset( $_POST['save-draft'] )
									? 'draft'
										: ( isset( $_POST['dwqa-status'] ) && $_POST['dwqa-status'] ? sanitize_text_field( $_POST['dwqa-status'] ) : 'publish' );

		do_action( 'dwqa_prepare_add_answer' );

		if ( dwqa_count_notices( 'error' ) > 0 ) {
			return false;
		}

		$answers = apply_filters( 'dwqa_insert_answer_args', $answers );
		
		$answer_id = wp_insert_post( $answers );

		if ( !is_wp_error( $answer_id ) ) {
			if ( $answers['post_status'] != 'draft' ) {
				update_post_meta( $question_id, '_dwqa_status', 'answered' );
				update_post_meta( $question_id, '_dwqa_answered_time', time() );
				update_post_meta( $answer_id, '_dwqa_votes', 0 );
				$answer_count = get_post_meta( $question_id, '_dwqa_answers_count', true );
				update_post_meta( $question_id, '_dwqa_answers_count', (int) $answer_count + 1 );
			}
			update_post_meta( $answer_id, '_question', $question_id  );

			if ( $is_anonymous ) {
				update_post_meta( $answer_id, '_dwqa_is_anonymous', true );

				if ( isset( $post_author_email ) && is_email( $post_author_email ) ) {
					update_post_meta( $answer_id, '_dwqa_anonymous_email', $post_author_email );
				}

				if ( isset( $post_author_name ) && !empty( $post_author_name ) ) {
					update_post_meta( $answer_id, '_dwqa_anonymous_name', $post_author_name );
				}
			} else {
				if ( !dwqa_is_followed( $question_id, get_current_user_id() ) ) {
					add_post_meta( $question_id, '_dwqa_followers', get_current_user_id() );
				}
			}

			do_action( 'dwqa_add_answer', $answer_id, $question_id );
			$this->update_modified_date( $question_id , current_time( 'timestamp', 0 ), current_time( 'timestamp', 1 ) );

			exit( wp_redirect( get_permalink( $question_id ) ) );
		} else {
			dwqa_add_wp_error_message( $answer_id );
		}
	}

	public function update_answer() {
		if ( isset( $_POST['dwqa-edit-answer-submit'] ) ) {
			if ( !dwqa_current_user_can( 'edit_answer' ) ) {
				dwqa_add_notice( __( "You do not have permission to edit answer.", 'dwqa' ), 'error' );
			}

			if ( !isset( $_POST['_wpnonce'] ) && !wp_verify_nonce( esc_html( $_POST['_wpnonce'] ), '_dwqa_edit_answer' ) ) {
				dwqa_add_notice( __( 'Hello, Are you cheating huh?', 'dwqa' ), 'error' );
			}

			$answer_content = apply_filters( 'dwqa_prepare_edit_answer_content', $_POST['answer_content'] );
			if ( empty( $answer_content ) ) {
				dwqa_add_notice( __( 'You must enter a valid answer content.', 'dwqa' ), 'error' );
			}

			$answer_id = isset( $_POST['answer_id'] ) ? intval( $_POST['answer_id'] ) : false;

			if ( !$answer_id ) {
				dwqa_add_notice( __( 'Answer is missing.', 'dwqa' ), 'error' );
			}

			if ( 'dwqa-answer' !== get_post_type( $answer_id ) ) {
				dwqa_add_notice( __( 'This post is not answer.', 'dwqa' ), 'error' );
			}

			do_action( 'dwqa_prepare_insert_question', $answer_id );

			if ( dwqa_count_notices( 'error' ) > 0 ) {
				return false;
			}

			$args = array(
				'ID' => $answer_id,
				'post_content' => $answer_content
			);

			$new_answer_id = wp_update_post( $args );

			if ( !is_wp_error( $new_answer_id ) ) {
				$old_post = get_post( $answer_id  );
				$new_post = get_post( $new_answer_id );
				do_action( 'dwqa_update_answer', $new_answer_id, $old_post, $new_post );
				$question_id = get_post_meta( $new_answer_id, '_question', true );
				$this->update_modified_date( $question_id , current_time( 'sql', 0 ), current_time( 'sql', 1 ) );

				wp_safe_redirect( get_permalink( $question_id ) . '#answer-' . $new_answer_id );
			} else {
				dwqa_add_wp_error_message( $new_answer_id );
				return false;
			}
			exit();
		}
	}

	public function insert_comment() {
		global $current_user;
		if ( isset( $_POST['comment-submit'] ) ) {
			if ( ! dwqa_current_user_can( 'post_comment' ) ) {
				dwqa_add_notice( __( 'You can\'t post comment', 'dwqa' ), 'error', true );
			}
			if ( ! isset( $_POST['comment_post_ID'] ) ) {
				dwqa_add_notice( __( 'Missing post id.', 'dwqa' ), 'error', true );
			}
			$comment_content = isset( $_POST['comment'] ) ? $_POST['comment'] : '';
			$comment_content = apply_filters( 'dwqa_pre_comment_content', $comment_content );

			if ( empty( $comment_content ) ) {
				dwqa_add_notice( __( 'Please enter your comment content', 'dwqa' ), 'error', true );
			}

			$args = array(
				'comment_post_ID'   => intval( $_POST['comment_post_ID'] ),
				'comment_content'   => $comment_content,
				'comment_parent'    => isset( $_POST['comment_parent']) ? intval( $_POST['comment_parent'] ) : 0,
				'comment_type'		=> 'dwqa-comment'
			);

			if ( is_user_logged_in() ) {
				$args['user_id'] = $current_user->ID;
				$args['comment_author'] = $current_user->display_name;
			} else {
				if ( ! isset( $_POST['email'] ) || ! sanitize_email( $_POST['email'] ) ) {
					dwqa_add_notice( __( 'Missing email information', 'dwqa' ), 'error', true );
				}

				if ( ! isset( $_POST['name'] ) || empty( $_POST['name'] ) ) {
					dwqa_add_notice( __( 'Missing name information', 'dwqa' ), 'error', true );
				}

				$args['comment_author'] = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : 'Anonymous';
				$args['comment_author_email'] = sanitize_email(  $_POST['email'] );
				$args['comment_author_url'] = isset( $_POST['url'] ) ? esc_url( $_POST['url'] ) : '';
				$args['user_id']    = -1;
			}
			
			$question_id = absint( $_POST['comment_post_ID'] );
			if ( 'dwqa-answer' == get_post_type( $question_id ) ) {
				$question_id = dwqa_get_question_from_answer_id( $question_id );
			}
			$redirect_to = get_permalink( $question_id );

			if ( isset( $_GET['ans-page'] ) ) {
				$redirect_to = add_query_arg( 'ans-page', absint( $_GET['ans-page'] ), $redirect_to );
			}

			

			

			if ( dwqa_count_notices( 'error', true ) > 0 ) {
				$redirect_to = apply_filters( 'dwqa_submit_comment_error_redirect', $redirect_to, $question_id);
				exit(wp_safe_redirect( $redirect_to ));
				return false;
			}
			
			$args = apply_filters( 'dwqa_insert_comment_args', $args );

			$comment_id = wp_insert_comment( $args );

			global $comment;
			$comment = get_comment( $comment_id );
			$client_id = isset( $_POST['clientId'] ) ? sanitize_text_field( $_POST['clientId'] ) : false;
			do_action( 'dwqa_add_comment', $comment_id, $client_id );
			
			$redirect_to = apply_filters( 'dwqa_submit_comment_success_redirect', $redirect_to, $question_id);
			exit(wp_safe_redirect( $redirect_to ));
		}
	}

	public function update_comment() {
		global $post_submit_filter;
		if ( isset( $_POST['dwqa-edit-comment-submit'] ) ) {
			if ( ! isset( $_POST['comment_id']) ) {
				dwqa_add_notice( __( 'Comment is missing', 'dwqa' ), 'error' );
			}
			$comment_id = intval( $_POST['comment_id'] );
			$comment_content = isset( $_POST['comment_content'] ) ? $_POST['comment_content'] : '';
			$comment_content = apply_filters( 'dwqa_pre_update_comment_content', $comment_content );

			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), '_dwqa_edit_comment' ) ) {
				dwqa_add_notice( __( 'Are you cheating huh?', 'dwqa' ), 'error' );
			}

			if ( !dwqa_current_user_can( 'edit_comment', $comment_id ) ) {
				dwqa_add_notice( __( 'You do not have permission to edit comment.', 'dwqa' ), 'error' );
			}

			if ( strlen( $comment_content ) <= 0 || ! isset( $comment_id ) || ( int )$comment_id <= 0 ) {
				dwqa_add_notice( __( 'Comment content must not be empty.', 'dwqa' ), 'error' );
			} else {
				$commentarr = array(
					'comment_ID'        => $comment_id,
					'comment_content'   => $comment_content
				);

				$intval = wp_update_comment( $commentarr );
				if ( !is_wp_error( $intval ) ) {
					$comment = get_comment( $comment_id );
					exit( wp_safe_redirect( dwqa_get_question_link( $comment->comment_post_ID ) ) );
				}else {
					dwqa_add_wp_error_message( $intval );
				}
			}
		}
	}

	public function submit_question() {
		global $dwqa_options;

		if ( isset( $_POST['dwqa-question-submit'] ) ) {
			global $dwqa_current_error;
			$valid_captcha = dwqa_valid_captcha( 'question' );

			$dwqa_submit_question_errors = new WP_Error();

			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( esc_html( $_POST['_wpnonce'] ), '_dwqa_submit_question' ) ) {
				if ( $valid_captcha ) {
					if ( empty( $_POST['question-title'] ) ) {
						dwqa_add_notice( __( 'You must enter a valid question title.', 'dwqa' ), 'error' );
						return false;
					}

					if ( !is_user_logged_in() ) {
						if ( empty( $_POST['_dwqa_anonymous_email'] ) || !is_email( sanitize_email( $_POST['_dwqa_anonymous_email'] ) ) ) {
							dwqa_add_notice( __( 'Missing email information', 'dwqa' ), 'error' );
							return false;
						}

						if ( empty( $_POST['_dwqa_anonymous_name'] ) ) {
							dwqa_add_notice( __( 'Missing name information', 'dwqa' ), 'error' );
							return false;
						}
					}

					$title = esc_html( $_POST['question-title'] );

					$category = isset( $_POST['question-category'] ) ?
								intval( $_POST['question-category'] ) : 0;
					if ( ! term_exists( $category, 'dwqa-question_category' ) ) {
						$category = 0;
					}

					$tags = isset( $_POST['question-tag'] ) ?
								esc_html( $_POST['question-tag'] ): '';

					$content = isset( $_POST['question-content'] ) ?  $_POST['question-content']  : '';
					$content = apply_filters( 'dwqa_prepare_question_content', $content );

					$user_id = 0;
					$is_anonymous = false;
					if ( is_user_logged_in() ) {
						$user_id = get_current_user_id();
					} else {
						//$post_author_email = $_POST['user-email'];
						if ( isset( $_POST['login-type'] ) && sanitize_text_field( $_POST['login-type'] ) == 'sign-in' ) {
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
						} elseif ( isset( $_POST['login-type'] ) && sanitize_text_field( $_POST['login-type'] ) == 'sign-up' ) {
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
								// $dwqa_current_error = new WP_Error( 'submit_question', $message );
								dwqa_add_notice( $message, 'error' );
								return false;
							}
						} else {
							$is_anonymous = true;
							$question_author_email = isset( $_POST['_dwqa_anonymous_email'] ) && is_email( $_POST['_dwqa_anonymous_email'] ) ? sanitize_email( $_POST['_dwqa_anonymous_email'] ) : false;
							$question_author_name = isset( $_POST['_dwqa_anonymous_name'] ) && !empty( $_POST['_dwqa_anonymous_name'] ) ? sanitize_text_field( $_POST['_dwqa_anonymous_name'] ) : false;
							$user_id = 0;
						}
					}

					$post_status = ( isset( $_POST['question-status'] ) && esc_html( $_POST['question-status'] ) ) ? $_POST['question-status'] : 'publish';

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
						$new_question = $this->insert_question( $postarr );
						do_action('dwqa_after_insert_question',$new_question);
					} else {
						//$dwqa_submit_question_errors->add( 'submit_question',  __( 'You do not have permission to submit question.', 'dwqa' ) );
						dwqa_add_notice( __( 'You do not have permission to submit question.', 'dwqa' ), 'error' );
						$new_question = $dwqa_submit_question_errors;
					}

					if ( dwqa_count_notices( 'error' ) == 0 ) {
						if ( $is_anonymous ) {
							update_post_meta( $new_question, '_dwqa_anonymous_email', $question_author_email );
							update_post_meta( $new_question, '_dwqa_anonymous_name', $question_author_name );
							update_post_meta( $new_question, '_dwqa_is_anonymous', true );
						}

						if ( isset( $dwqa_options['enable-review-question'] ) && $dwqa_options['enable-review-question'] && !current_user_can( 'manage_options' ) && $post_status != 'private' ) {
							dwqa_add_notice( __( 'Your question is waiting moderator.', 'dwqa' ), 'success' );
						} else {
							exit( wp_safe_redirect( get_permalink( $new_question ) ) );
						}
					}
				} else {
					// $dwqa_submit_question_errors->add( 'submit_question', __( 'Captcha is not correct','dwqa' ) );
					dwqa_add_notice( __( 'Captcha is not correct', 'dwqa' ), 'error' );
				}
			} else {
				// $dwqa_submit_question_errors->add( 'submit_question', __( 'Are you cheating huh?','dwqa' ) );
				dwqa_add_notice( __( 'Are you cheating huh?', 'dwqa' ), 'error' );
			}
			//$dwqa_current_error = $dwqa_submit_question_errors;
		}
	}

	public function update_question() {
		if ( isset( $_POST['dwqa-edit-question-submit'] ) ) {
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( esc_html( $_POST['_wpnonce'] ), '_dwqa_edit_question' ) ) {

				if ( !dwqa_current_user_can( 'edit_question' ) ) {
					dwqa_add_notice( __( "You do not have permission to edit question", 'dwqa' ), 'error' );
				}

				$question_title = apply_filters( 'dwqa_prepare_edit_question_title', sanitize_text_field( $_POST['question_title'] ) );
				if ( empty( $question_title ) ) {
					dwqa_add_notice( __( 'You must enter a valid question title.', 'dwqa' ), 'error' );
				}

				$question_id = isset( $_POST['question_id'] ) ? sanitize_text_field( $_POST['question_id'] ) : false;

				if ( !$question_id ) {
					dwqa_add_notice( __( 'Question is missing.', 'dwqa' ), 'error' );
				}

				if ( 'dwqa-question' !== get_post_type( $question_id ) ) {
					dwqa_add_notice( __( 'This post is not question.', 'dwqa' ), 'error' );
				}

				$question_content = apply_filters( 'dwqa_prepare_edit_question_content', $_POST['question_content'] );

				$tags = isset( $_POST['question-tag'] ) ? esc_html( $_POST['question-tag'] ): '';
				$category = isset( $_POST['question-category'] ) ? intval( $_POST['question-category'] ) : 0;
				if ( ! term_exists( $category, 'dwqa-question_category' ) ) {
					$category = 0;
				}

				do_action( 'dwqa_prepare_update_question', $question_id );

				if ( dwqa_count_notices( 'error' ) > 0 ) {
					return false;
				}

				$args = array(
					'ID' => $question_id,
					'post_content' => $question_content,
					'post_title' => $question_title,
					'tax_input' => array(
						'dwqa-question_category' => array( $category ),
						'dwqa-question_tag'		=> explode( ',', $tags )
					),
				);

				$new_question_id = wp_update_post( $args );

				if ( !is_wp_error( $new_question_id ) ) {
					$old_post = get_post( $question_id );
					$new_post = get_post( $new_question_id );
					do_action( 'dwqa_update_question', $new_question_id, $old_post, $new_post );
					wp_safe_redirect( get_permalink( $new_question_id ) );
				} else {
					dwqa_add_wp_error_message( $new_question_id );
					return false;
				}
			} else {
				dwqa_add_notice( __( 'Hello, Are you cheating huh?', 'dwqa' ), 'error' );
				return false;
			}
			exit(0);
		}
	}

	public function insert_question( $args ) {
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
		
		$args = apply_filters( 'dwqa_insert_question_args', $args );

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
			add_post_meta( $new_question, '_dwqa_followers', $user_id );
			$date = get_post_field( 'post_date', $new_question );
			// dwqa_log_last_activity_on_question( $new_question, 'Create question', $date );
			//Call action when add question successfull
			do_action( 'dwqa_add_question', $new_question, $user_id );
		}
		return $new_question;
	}

	function update_modified_date( $question_id, $modified_date, $modified_date_gmt ) {
		$data = array(
			'ID' => $question_id,
			'post_modified' => $this->timeformat_convert( $modified_date ),
			'post_modified_gmt' => $this->timeformat_convert( $modified_date_gmt ),
		);
		wp_update_post( $data );
	}

	function timeformat_convert( $timestamp ) {
		return date("Y-m-d H:i:s", $timestamp );
	}
}
