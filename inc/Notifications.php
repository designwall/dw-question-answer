<?php  

class DWQA_Notifications {

	public function __construct() {
		add_action( 'dwqa_add_question', array( $this, 'new_question_notify' ), 10, 2 );
		add_action( 'dwqa_add_answer', array( $this, 'new_answer_nofity_to_follower' ) );
		add_action( 'dwqa_add_answer', array( $this, 'new_answer_nofity_to_question_author' ) );
		add_action( 'dwqa_update_answer', array( $this, 'new_answer_nofity_to_follower' ) );
		add_action( 'dwqa_update_answer', array( $this, 'new_answer_nofity_to_question_author' ) );
		add_action( 'wp_insert_comment', array( $this, 'new_comment_notify' ), 10, 2 );
	}

	public function new_question_notify( $question_id, $user_id ) {
		// receivers
		$admin_email = $this->get_admin_email();

		$enabled = get_option( 'dwqa_subscrible_enable_new_question_notification', 1 );
		if ( ! $enabled ) {
			return false;
		}
		$question = get_post( $question_id );
		if ( ! $question ) {
			return false;
		}

		$subject = get_option( 'dwqa_subscrible_new_question_email_subject' );
		if ( ! $subject ) {
			$subject = __( 'A new question was posted on {site_name}', 'dwqa' );
		}
		$subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject );
		$subject = str_replace( '{question_title}', $question->post_title, $subject );
		$subject = str_replace( '{question_id}', $question->ID, $subject );
		$subject = str_replace( '{username}', get_the_author_meta( 'display_name', $user_id ), $subject );
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		//From email 
		$from_email = get_option( 'dwqa_subscrible_from_address' );
		if ( $from_email ) {
			$headers .= 'From: ' . $from_email . "\r\n";
		}

		//Cc email
		$cc_address = get_option( 'dwqa_subscrible_cc_address' );
		if ( $cc_address ) {
			$headers .= 'Cc: ' . $cc_address . "\r\n";
		}
		//Bcc email
		$bcc_address = get_option( 'dwqa_subscrible_bcc_address' );
		if ( $bcc_address ) {
			$headers .= 'Bcc: ' . $bcc_address . "\r\n";
		}
		
		$message = dwqa_get_mail_template( 'dwqa_subscrible_new_question_email', 'new-question' );
		if ( ! $message ) {
			return false;
		}
		// Replacement
		
		$admin = get_user_by( 'email', $admin_email[0] );
		if ( $admin ) {
			$message = str_replace( '{admin}', get_the_author_meta( 'display_name', $admin->ID ), $message );
		}
		//sender
		$message = str_replace( '{user_avatar}', get_avatar( $user_id, '60' ), $message );
		$message = str_replace( '{user_link}', dwqa_get_author_link( $user_id ), $message );
		$message = str_replace( '{username}', get_the_author_meta( 'display_name', $user_id ), $message );
		//question
		$message = str_replace( '{question_link}', get_permalink( $question_id ), $message );
		$message = str_replace( '{question_title}', $question->post_title, $message );
		$message = str_replace( '{question_content}', $question->post_content, $message );
		// Site info
		$logo = get_option( 'dwqa_subscrible_email_logo', '' );
		$logo = $logo ? '<img src="' . $logo . '" alt="' . get_bloginfo( 'name' ) . '" style="max-width: 100%; height: auto;" />' : '';
		$message = str_replace( '{site_logo}', $logo, $message );
		$message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
		$message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
		$message = str_replace( '{site_url}', site_url(), $message );

		// start send out email
		$sended = $this->send( $admin_email, $subject, $message, $headers );
	}

	public function new_answer_nofity_to_follower( $answer_id ) {
		$enabled = get_option( 'dwqa_subscrible_enable_new_answer_followers_notification', 1 );
		if ( ! $enabled ) {
			return false;
		}

		// make sure this is new answer
		if ( 'dwqa-answer' !== get_post_type( $answer_id ) ) {
			return false;
		}

		$question_id = dwqa_get_question_from_answer_id( $answer_id );

		// make sure is reply for a question
		if ( 'dwqa-question' !== get_post_type( $question_id ) ) {
			return false;
		}

		if ( 'private' == get_post_status( $answer_id ) ) {
			return false;
		}

		$logo = get_option( 'dwqa_subscrible_email_logo', '' );
		$logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		// this is answer of anonymous ?
		$is_anonymous = dwqa_is_anonymous( $answer_id );
		if ( $is_anonymous ) {
			$user_id = 0;
			$user_display_name = get_post_meta( $answer_id, '_dwqa_anonymous_name', true );
			$user_display_name = $user_display_name ? esc_html( $user_display_name ) : __( 'Anonymous', 'dwqa' );
			$user_email = get_post_meta( $answer_id, '_dwqa_anonymous_email', true );
			$user_email = $user_email ? sanitize_email( $user_email ) : false;
			if ( $user_email ) {
				$avatar = get_avatar( $user_email, '60' );
			} else {
				$avatar = get_avatar( $user_id, '60' );
			}
		} else {
			$user_id = absint( get_post_field( 'post_author', $answer_id ) );
			$user_display_name = get_the_author_meta( 'display_name', $user_id );
			$user_email = get_the_author_meta( 'display_name', $user_id );
			$avatar = get_avatar( $user_id, '60' );
		}

		$followers = get_post_meta( $question_id, '_dwqa_followers' );
		$subject = get_option( 'dwqa_subscrible_new_answer_followers_email_subject', __( 'You have a new answer for your followed question', 'dwqa' ) );
		$subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject );
		$subject = str_replace( '{question_title}', get_the_title( $question_id ), $subject );
		$subject = str_replace( '{question_id}', $question_id, $subject );
		$subject = str_replace( '{answer_author}', $user_display_name, $subject );

		$message = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_followers_email', 'new-answer-followers' );
		$message = apply_filters( 'dwqa_get_new_answer_email_to_followers_message', $message, $answer_id, $question_id );
		if ( !$message ) {
			return false;
		}

		$message = str_replace( '{answer_author}', $user_display_name, $message );
		$message = str_replace( '{question_link}', get_permalink( $question_id ), $message );
		$message = str_replace( '{answer_link}', get_permalink( $question_id ) . '#answer-' . $answer_id, $message );
		$message = str_replace( '{question_title}', get_the_title( $question_id ), $message );
		$message = str_replace( '{answer_content}', get_post_field( 'post_content', $answer_id ), $message );
		$message = str_replace( '{answer_avatar}', $avatar, $message );
		$message = str_replace( '{site_logo}', $logo, $message );
		$message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
		$message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
		$message = str_replace( '{site_url}', site_url(), $message );

		// make sure the question have subscriber
		if ( !empty( $followers ) && is_array( $followers ) ) {
			foreach ( $followers as $follower_id ) {
				$user = get_user_by( 'id', $follower_id );
				// prevent send to question author and answer author and user exists
				if (
						absint( $follower_id ) !== $user_id
						&&
						absint( $follower_id ) !== get_post_field( 'post_author', $question_id )
						&&
						isset( $user->ID ) 
					) {
					$subject = str_replace( '{username}', $user->display_name, $subject );
					$message = str_replace( '{follower}', $user->display_name, $message );
					$message = str_replace( '{follower_avatar}', get_avatar( $user->user_email, '60' ), $message );

					$subject = apply_filters( 'dwqa_get_new_answer_email_to_followers_subject', $subject, $answer_id, $question_id );

					// send
					$this->send( get_the_author_meta( 'user_email', absint( $follower_id ) ), $subject, $message, $headers );
					// send copy to admin
					$enable_send_copy = get_option( 'dwqa_subscrible_send_copy_to_admin' );
					if ( $enable_send_copy ) {
						$admin_email = $this->get_admin_email();
						$this->send( $admin_email, $subject, $message, $headers );
					}
				}
			}
		}
	}

	public function new_answer_nofity_to_question_author( $answer_id ) {
		$enable = get_option( 'dwqa_subscrible_enable_new_answer_notification', 1 );
		if ( !$enable ) {
			return false;
		}

		// make sure this is new answer
		if ( 'dwqa-answer' !== get_post_type( $answer_id ) ) {
			return false;
		}

		$question_id = dwqa_get_question_from_answer_id( $answer_id );

		// make sure is reply for a question
		if ( 'dwqa-question' !== get_post_type( $question_id ) ) {
			return false;
		}

		$logo = get_option( 'dwqa_subscrible_email_logo', '' );
		$logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

		$enable_send_copy = get_option( 'dwqa_subscrible_send_copy_to_admin' );
		$is_anonymous = dwqa_is_anonymous( $question_id );
		if ( $is_anonymous ) {
			$user_id = 0;
			$user_display_name = get_post_meta( $question_id, '_dwqa_anonymous_name', true );
			$user_display_name = $user_display_name ? esc_html( $user_display_name ) : __( 'Anonymous', 'dwqa' );
			$user_email = get_post_meta( $question_id, '_dwqa_anonymous_email', true );
			$user_email = $user_email ? sanitize_email( $user_email ) : false;
			if ( $user_email ) {
				$avatar = get_avatar( $user_email, '60' );
			} else {
				$avatar = get_avatar( $user_id, '60' );
			}
		} else {
			$user_id = absint( get_post_field( 'post_author', $question_id ) );
			$user_display_name = get_the_author_meta( 'display_name', $user_id );
			$user_email = get_the_author_meta( 'display_name', $user_id );
			$avatar = get_avatar( $user_id, '60' );
		}

		$is_answer_anonymous = dwqa_is_anonymous( $answer_id );
		if ( $is_answer_anonymous ) {
			$answer_user_id = 0;
			$answer_user_display_name = get_post_meta( $answer_id, '_dwqa_anonymous_name', true );
			$answer_user_display_name = $user_display_name ? esc_html( $user_display_name ) : __( 'Anonymous', 'dwqa' );
			$answer_user_email = get_post_meta( $answer_id, '_dwqa_anonymous_email', true );
			$answer_user_email = $user_email ? sanitize_email( $user_email ) : false;
			if ( $user_email ) {
				$answer_avatar = get_avatar( $user_email, '60' );
			} else {
				$answer_avatar = get_avatar( $answer_user_id, '60' );
			}
		} else {
			$answer_user_id = absint( get_post_field( 'post_author', $answer_id ) );
			$answer_user_display_name = get_the_author_meta( 'display_name', $answer_user_id );
			$answer_user_email = get_the_author_meta( 'display_name', $answer_user_id );
			$answer_avatar = get_avatar( $answer_user_id, '60' );
		}

		// make sure anonymous entered email
		if ( $user_email ) {
			$subject = get_option( 'dwqa_subscrible_new_answer_email_subject', __( 'A new answer for "{question_title}" was posted on {site_name}', 'dwqa' ) );
			$subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject );
			$subject = str_replace( '{question_title}', get_the_title( $question_id ), $subject );
			$subject = str_replace( '{question_id}', $question_id, $subject );
			$subject = str_replace( '{username}', $user_display_name, $subject );
			$subject = str_replace( '{answer_author}', $answer_user_display_name, $subject );

			$message = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_email', 'new-answer' );
			$message = apply_filters( 'dwqa_get_new_answer_email_to_author_message', $message, $question_id, $answer_id );
			if ( !$message ) {
				return false;
			}

			$message = str_replace( '{answer_avatar}', $answer_avatar, $message );
			$message = str_replace( '{answer_author}', $answer_user_display_name, $message );
			$message = str_replace( '{question_link}', get_permalink( $question_id ), $message );
			$message = str_replace( '{question_author}', $user_display_name, $message );
			$message = str_replace( '{answer_link}', get_permalink( $question_id ) . '#answer-' . $answer_id, $message );
			$message = str_replace( '{question_title}', get_the_title( $question_id ), $message );
			$message = str_replace( '{answer_content}', get_post_field( 'post_content', $answer_id ), $message );
			$message = str_replace( '{site_logo}', $logo, $message );
			$message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
			$message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
			$message = str_replace( '{site_url}', site_url(), $message );

			$this->send( $user_email, $subject, $message, $headers );

			if ( $enable_send_copy ) {
				$emails = $this->get_admin_email();
				$this->send( $emails, $subject, $message, $headers );
			}
		}
	}

	public function new_comment_notify( $comment_id, $comment ) {
		$parent = get_post_type( $comment->comment_post_ID );

		//Admin email
		$admin_email = get_bloginfo( 'admin_email' );
		$enable_send_copy = get_option( 'dwqa_subscrible_send_copy_to_admin' );

		if ( 1 == $comment->comment_approved && ( 'dwqa-question' == $parent || 'dwqa-answer' == $parent ) ) { 
			if ( $parent == 'dwqa-question' ) {
				$enabled = get_option( 'dwqa_subscrible_enable_new_comment_question_notification', 1 );
				$admin_email = $this->get_admin_email( 'comment-question' );      
			} elseif ( $parent == 'dwqa-answer' ) {
				$enabled = get_option( 'dwqa_subscrible_enable_new_comment_answer_notification', 1 );
				$admin_email = $this->get_admin_email( 'comment-answer' );
			}
		
			if ( ! $enabled ) {
				return false;
			}

			$post_parent = get_post( $comment->comment_post_ID );

			
			if ( dwqa_is_anonymous( $comment->comment_post_ID ) ) {
				$post_parent_email = get_post_meta( $comment->comment_post_ID, '_dwqa_anonymous_email', true );
				if ( ! is_email( $post_parent_email ) ) {
					return false;
				}
			} else {
				// if user is not the author of question/answer, add user to followers list
				if ( $post_parent->post_author != $comment->user_id ) {

					if ( ! dwqa_is_followed( $post_parent->ID, $comment->user_id ) ) {
						add_post_meta( $post_parent->ID, '_dwqa_followers', $comment->user_id );
					}
				}
				$post_parent_email = get_the_author_meta( 'user_email', $post_parent->post_author );
			}

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
			//From email 
			$from_email = get_option( 'dwqa_subscrible_from_address' );
			if ( $from_email ) {
				$headers .= 'From: ' . $from_email . "\r\n";
			}
			
			if ( $parent == 'dwqa-question' ) {
				$message = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_question_email', 'new-comment-question' );    
				$subject = get_option( 'dwqa_subscrible_new_comment_question_email_subject',__( '[{site_name}] You have a new comment for question {question_title}', 'dwqa' ) );
				$message = str_replace( '{question_author}', get_the_author_meta( 'display_name', $post_parent->post_author ), $message );
				$question = $post_parent;
			} else {
				$message = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_answer_email', 'new-comment-answer' );
				$subject = get_option( 'dwqa_subscrible_new_comment_answer_email_subject',__( '[{site_name}] You have a new comment for answer', 'dwqa' ) );
				$message = str_replace( '{answer_author}', get_the_author_meta( 'display_name', $post_parent->post_author ), $message );
				$question_id = get_post_meta( $post_parent->ID, '_question', true );
				$question = get_post( $question_id );
			}
			$subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject );
			$subject = str_replace( '{question_title}', $question->post_title, $subject );
			$subject = str_replace( '{question_id}', $question->ID, $subject );
			$subject = str_replace( '{username}',get_the_author_meta( 'display_name', $comment->user_id ), $subject );

			if ( ! $message ) {
				return false;
			}
			// logo replace
			$logo = get_option( 'dwqa_subscrible_email_logo','' );
			$logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
			$subject = str_replace( '{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $subject );
			$message = str_replace( '{site_logo}', $logo, $message );
			$message = str_replace( '{question_link}', get_permalink( $question->ID ), $message );
			$message = str_replace( '{comment_link}', get_permalink( $question->ID ) . '#comment-' . $comment_id, $message );
			$message = str_replace( '{question_title}', $question->post_title, $message );
			$message = str_replace( '{comment_author_avatar}', get_avatar( $comment->user_id, '60' ), $message );
			$message = str_replace( '{comment_author_link}', dwqa_get_author_link( $comment->user_id ), $message );
			$message = str_replace( '{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $message );
			$message = str_replace( '{comment_content}', $comment->comment_content, $message );
			$message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
			$message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
			$message = str_replace( '{site_url}', site_url(), $message );
			if ( $parent == 'dwqa-question' ) {
				$enable_notify = get_option( 'dwqa_subscrible_enable_new_comment_question_followers_notify', true );
			} else {
				$enable_notify = get_option( 'dwqa_subscrible_enable_new_comment_answer_followers_notification', true );
			}
			
			if ( $enable_notify ) {
				//Follower email task
				$followers = get_post_meta( $post_parent->ID, '_dwqa_followers' );
				$comment_email = get_the_author_meta( 'user_email', $comment->user_id );

				if ( $parent == 'dwqa-question' ) {
					$message_to_follower = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_question_followers_email', 'new-comment-question' );    
					$follow_subject = get_option( 'dwqa_subscrible_new_comment_question_followers_email_subject',__( '[{site_name}] You have a new comment for question {question_title}', 'dwqa' )  );
					$message_to_follower = str_replace( '{question_author}', get_the_author_meta( 'display_name', $post_parent->post_author ), $message_to_follower );
					$question = $post_parent;
				} else {
					$message_to_follower = dwqa_get_mail_template( 'dwqa_subscrible_new_comment_answer_followers_email', 'new-comment-answer' );
					$follow_subject = get_option( 'dwqa_subscrible_new_comment_answer_followers_email_subject',__( '[{site_name}] You have a new comment for answer', 'dwqa' )  );
					$message_to_follower = str_replace( '{answer_author}', get_the_author_meta( 'display_name', $post_parent->post_author ), $message_to_follower );
				}
				$follow_subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $follow_subject );
				$follow_subject = str_replace( '{question_title}', $question->post_title, $follow_subject );
				$follow_subject = str_replace( '{question_id}', $question->ID, $follow_subject );
				$follow_subject = str_replace( '{username}',get_the_author_meta( 'display_name', $comment->user_id ), $follow_subject );

				$follow_subject = str_replace( '{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $follow_subject );
				$message_to_follower = str_replace( '{site_logo}', $logo, $message_to_follower );
				$message_to_follower = str_replace( '{question_link}', get_permalink( $question->ID ), $message_to_follower );
				$comment_link = get_permalink( $question->ID ) . '#comment-' . $comment_id;
				$message_to_follower = str_replace( '{comment_link}', $comment_link, $message_to_follower );
				$message_to_follower = str_replace( '{question_title}', $question->post_title, $message_to_follower );
				$message_to_follower = str_replace( '{comment_author_avatar}', get_avatar( $comment->user_id, '60' ), $message_to_follower );
				$message_to_follower = str_replace( '{comment_author_link}', dwqa_get_author_link( $comment->user_id ), $message_to_follower );
				$message_to_follower = str_replace( '{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $message_to_follower );
				$message_to_follower = str_replace( '{comment_content}', $comment->comment_content, $message_to_follower );
				$message_to_follower = str_replace( '{site_name}', get_bloginfo( 'name' ), $message_to_follower );
				$message_to_follower = str_replace( '{site_description}', get_bloginfo( 'description' ), $message_to_follower );
				$message_to_follower = str_replace( '{site_url}', site_url(), $message_to_follower );
				if ( ! empty( $followers ) && is_array( $followers ) ) {
					foreach ( $followers as $follower ) {
						$follower = (int) $follower;
						$user_data = get_user_by( 'id', $follower );
						if ( $user_data ) {
							$follow_email = $user_data->user_email;
							$follower_name = $user_data->display_name;
							if ( $follow_email && absint( $follower ) !== absint( $post_parent->post_author ) && absint( $follower ) !== absint( $comment->user_id ) ) {

								$message_to_each_follower = str_replace( '{follower}', $follower_name, $message_to_follower );
								$test = $this->send( $follow_email, $follow_subject, $message_to_each_follower, $headers );
								if ( $enable_send_copy && $follow_email != $admin_email ) {
									$this->send( $admin_email, $follow_subject, $message_to_each_follower, $headers );
								}
							}
						}
					}
				}
			}

			if ( $post_parent->post_author != $comment->user_id ) {
				$this->send( $post_parent_email, $subject, $message, $headers );
				if ( $enable_send_copy && $admin_email != $post_parent_email ) {
					$this->send( $admin_email, $subject, $message, $headers );
				}
			}
		}
	}
	
	public function get_admin_email( $type = 'question' ){
		$admin_email = get_option( 'dwqa_subscrible_sendto_address', '' );
		$emails = explode( PHP_EOL, $admin_email );
		$emails = array_merge( $emails, array( get_bloginfo( 'admin_email' ) ) );
		return $emails;
	}

	public function get_from_address() {
		$from_email = get_option( 'dwqa_subscrible_from_address' );
		if ( !$from_email ) {
			$from_email = get_bloginfo( 'admin_email' );
		}

		return sanitize_email( $from_email );
	}

	public function get_content_type() {
		return apply_filters( 'dwqa_notifications_get_content_type', 'text/html' );
	}

	public function send( $to, $subject, $message, $headers = '', $attachments = array() ) {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		$sended = wp_mail( $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
		return $sended;
	}

	public function email_header() {
		ob_start();
		?>
		<!DOCTYPE html>
		<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
		</head>
		<body>
		<?php
		return ob_get_clean();
	}

	public function email_footer() {
		ob_start();
		?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}


?>
