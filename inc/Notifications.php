<?php  

class DWQA_Notifications {

	public function __construct() {
		// add_action( 'dwqa_add_question', array( $this, 'new_question_notify' ), 10, 2 );
		// add_action( 'wp_insert_comment', array( $this, 'new_comment_notify' ), 10, 2 );
		// add_action( 'dwqa_add_answer', array( $this, 'new_answer_notify' ), 10, 2 );
		
		add_action('dwqa_new_question_notify', array( $this, 'new_question_notify' ), 10, 2);
		add_action('dwqa_new_answer_notify', array( $this, 'new_answer_notify' ), 10, 2);
		add_action('dwqa_new_comment_notify', array( $this, 'new_comment_notify' ), 10, 2);

		add_action( 'dwqa_add_question', array( $this, 'dwqa_queue_add_question' ), 10, 2 );
		add_action( 'dwqa_add_answer', array( $this, 'dwqa_queue_add_answer' ), 10, 2 );
		add_action( 'wp_insert_comment', array( $this, 'dwqa_queue_insert_comment' ), 10, 2 );
		

		// add_action( 'dwqa_add_question', array( $this, 'new_activity' ) );
		// add_action( 'dwqa_add_answer', array( $this, 'new_activity' ) );
		// add_action( 'dwqa_add_comment', array( $this, 'new_activity' ) );
	}
	
	public function dwqa_queue_add_question($question_id, $user_id){
		wp_schedule_single_event( time() + 120, 'dwqa_new_question_notify', array($question_id, $user_id) );
	}
	public function dwqa_queue_add_answer($answer_id, $question_id){
		wp_schedule_single_event( time() + 120, 'dwqa_new_answer_notify', array($answer_id, $question_id) );
	}
	public function dwqa_queue_insert_comment($comment_id, $comment){
		wp_schedule_single_event( time() + 120, 'dwqa_new_comment_notify', array($comment_id, $comment) );
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

		$headers = array( 
			"From: {$this->get_from_name()} <{$this->get_from_address()}>",
			"Reply-To: {$this->get_from_address()}",
			"Content-Type: {$this->get_content_type()}; charset=utf-8"
		);
		
		// start send out email
		foreach( $admin_email as $to ) {
			if ( is_email( $to ) )
				$sended = $this->send( sanitize_email( $to ), $subject, $message, $headers );
		}
	}

	public function new_answer_notify( $answer_id, $question_id ) {
		// print_r( $answer_id ); die;
		if ( 'dwqa-answer' !== get_post_type( $answer_id ) ) {
			return false;
		}

		if ( 'dwqa-question' !== get_post_type( $question_id ) ) {
			return false;
		}

		// default value
		$site_name = get_bloginfo( 'name' );
		$question_title = get_the_title( $question_id );
		$answer_content = get_post_field( 'post_content', $answer_id );
		$question_link = get_permalink( $question_id );
		$answer_link = trailingslashit( $question_link ) . '#answer-' . $answer_id;
		$site_description = get_bloginfo( 'description' );
		$site_url = site_url();
		$enable_send_copy = get_option( 'dwqa_subscrible_send_copy_to_admin' );
		$admin_email = $this->get_admin_email();
		$site_logo = get_option( 'dwqa_subscrible_email_logo', '' );
		$site_logo = $site_logo ? '<img src="' . $site_logo . '" alt="' . get_bloginfo( 'name' ) . '" style="max-width: 100%; height: auto;" />' : '';

		// for answer
		$answer_is_anonymous = dwqa_is_anonymous( $answer_id );
		if ( $answer_is_anonymous ) {
			$user_answer_id = 0;
			$user_answer_display_name = get_post_meta( $answer_id, '_dwqa_anonymous_name', true );
			$user_answer_display_name = $user_answer_display_name ? sanitize_text_field( $user_answer_display_name ) : __( 'Anonymous', 'dwqa' );
			$user_answer_email = get_post_meta( $answer_id, '_dwqa_anonymous_email', true );
			$user_answer_email = $user_answer_email ? sanitize_email( $user_answer_email ) : false;
		} else {
			$user_answer_id = get_post_field( 'post_author', $answer_id );
			$user_answer_display_name = get_the_author_meta( 'display_name', $user_answer_id );
			$user_answer_email = get_the_author_meta( 'user_email', $user_answer_id );
		}

		if ( $user_answer_email ) {
			$user_answer_avatar = get_avatar( $user_answer_email, 60 );
		} else {
			$user_answer_avatar = get_avatar( $user_answer_id, 60 );
		}
		
		// for question
		$question_is_anonymous = dwqa_is_anonymous( $question_id );
		if ( $question_is_anonymous ) {
			$user_question_id = 0;
			$user_question_display_name = get_post_meta( $question_id, '_dwqa_anonymous_name', true );
			$user_question_display_name = $user_question_display_name ? sanitize_text_field( $user_question_display_name ) : __( 'Anonymous', 'dwqa' );
			$user_question_email = get_post_meta( $question_id, '_dwqa_anonymous_email', true );
			$user_question_email = $user_question_email ? sanitize_email( $user_question_email ) : false;
		} else {
			$user_question_id = get_post_field( 'post_author', $question_id );
			$user_question_display_name = get_the_author_meta( 'display_name', $user_question_id );
			$user_question_email = get_the_author_meta( 'user_email', $user_question_id );
		}

		if ( $user_question_email ) {
			$user_question_avatar = get_avatar( $user_question_email, 60 );
		} else {
			$user_question_avatar = get_avatar( $user_question_id, 60 );
		}

		// get all follower email lists
		$followers = get_post_meta( $question_id, '_dwqa_followers' );
		$followers_email = array();
		if ( !empty( $followers ) && is_array( $followers ) ) {
			foreach( $followers as $follower ) {
				if ( is_numeric( $follower ) ) {
					// prevent send to answer author and question author
					if ( absint( $follower ) == $user_answer_id || absint( $follower ) == $user_question_id ) continue;
					// get user email has registered
					$followers_email[] = get_the_author_meta( 'user_email', $follower );
				} else {
					// prevent send to question author and answer author
					if ( sanitize_email( $user_answer_email ) == sanitize_email( $follower ) || sanitize_email( $user_question_email ) == sanitize_email( $follower ) ) continue;
					// get anonymous email
					$followers_email[] = sanitize_email( $follower );
				}
			}
		}

		// start send to followers
		$answer_notify_enabled = get_option( 'dwqa_subscrible_enable_new_answer_followers_notification', 1 );
		if ( $answer_notify_enabled && !empty( $followers_email ) && is_array( $followers_email ) && 'private' !== get_post_status( $answer_id ) ) {
			$subject = get_option( 'dwqa_subscrible_new_answer_followers_email_subject', __( '[{site_name}] You have a new answer for your followed question', 'dwqa' ) );
			$subject = str_replace( '{site_name}', esc_html( $site_name ), $subject );
			$subject = str_replace( '{question_title}', sanitize_title( $question_title ), $subject );
			$subject = str_replace( '{answer_author}', esc_html( $user_answer_display_name ), $subject );

			$message = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_followers_email', 'new-answer-followers' );
			$message = apply_filters( 'dwqa_get_new_answer_email_to_followers_message', $message, $answer_id, $question_id );

			if ( !$message ) {
				return false;
			}

			$message = str_replace( 'Howdy {follower},', '', $message );
			$message = str_replace( '{answer_author}', esc_html( $user_answer_display_name ), $message );
			$message = str_replace( '{question_link}', esc_url( $question_link ), $message );
			$message = str_replace( '{answer_link}', esc_url( $answer_link ), $message );
			$message = str_replace( '{question_title}', sanitize_title( $question_title ), $message );
			$message = str_replace( '{answer_content}', wp_kses_post( $answer_content ), $message );
			$message = str_replace( '{answer_avatar}', $user_answer_avatar, $message );
			$message = str_replace( '{site_logo}', $site_logo, $message );
			$message = str_replace( '{site_name}', esc_html( $site_name ), $message );
			$message = str_replace( '{site_description}', esc_html( $site_description ), $message );
			$message = str_replace( '{site_url}', esc_url( $site_url ), $message );

			if ( $enable_send_copy ) {
				$followers_email = array_merge( $followers_email, $admin_email );
			}

			// make sure it is not duplicate email
			$followers_email = array_unique( $followers_email );

			$headers = array( 
				"From: {$this->get_from_name()} <{$this->get_from_address()}>",
				"Content-Type: {$this->get_content_type()}; charset=utf-8"
			);

			foreach( $followers_email as $f_email ) {
				$headers[] = "Bcc: " . $f_email;
			}

			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) === 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}
			$no_reply = 'noreply@' . $sitename;

			$sender = $this->send( $no_reply, $subject, $message, $headers );
		}

		// start send to question author
		$answer_notify_for_question_enabled = get_option( 'dwqa_subscrible_enable_new_answer_notification', 1 );
		if ( $user_question_email && $answer_notify_for_question_enabled && absint( $user_answer_id ) !== absint( $user_question_id ) ) {
			$subject = get_option( 'dwqa_subscrible_new_answer_email_subject', __( '[{site_name}] A new answer for "{question_title}" was posted on {site_name}', 'dwqa' ) );
			$subject = str_replace( '{site_name}', esc_html( $site_name ), $subject );
			$subject = str_replace( '{question_title}', sanitize_title( $question_title ), $subject );
			$subject = str_replace( '{question_id}', absint( $question_id ), $subject );
			$subject = str_replace( '{username}', esc_html( $user_question_display_name ), $subject );
			$subject = str_replace( '{answer_author}', esc_html( $user_answer_display_name ), $subject );

			$message = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_email', 'new-answer' );
			$message = apply_filters( 'dwqa_get_new_answer_email_to_author_message', $message, $question_id, $answer_id );
			if ( !$message ) {
				return false;
			}

			$message = str_replace( '{answer_avatar}', $user_answer_avatar, $message );
			$message = str_replace( '{answer_author}', esc_html( $user_answer_display_name ), $message );
			$message = str_replace( '{question_link}', esc_url( $question_link ), $message );
			$message = str_replace( '{question_author}', esc_html( $user_question_display_name ), $message );
			$message = str_replace( '{answer_link}', esc_url( $answer_link ), $message );
			$message = str_replace( '{question_title}', sanitize_title( $question_title ), $message );
			$message = str_replace( '{answer_content}', wp_kses_post( $answer_content ), $message );
			$message = str_replace( '{site_logo}', $site_logo, $message );
			$message = str_replace( '{site_name}', esc_html( $site_name ), $message );
			$message = str_replace( '{site_description}', esc_html( $site_description ), $message );
			$message = str_replace( '{site_url}', esc_url( $site_url ), $message );

			$headers = array( 
				"From: {$this->get_from_name()} <{$this->get_from_address()}>",
				"Content-Type: {$this->get_content_type()}; charset=utf-8"
			);

			if ( $enable_send_copy ) {
				foreach( $admin_email as $a_email ) {
					$headers[] = "Bcc: " . $a_email;
				}
			}

			$sender = $this->send( $user_question_email, $subject, $message, $headers );
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
			$headers = array( 
				"From: {$this->get_from_name()} <{$this->get_from_address()}>",
				"Content-Type: {$this->get_content_type()}; charset=utf-8"
			);
			
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

	// Pushover
	public function push( $args ) {
		if ( !function_exists( 'ckpn_send_notification' ) ) {
			if ( class_exists( 'CKPushoverNotifications' ) ) {
				$ckpn_core = CKPushoverNotifications::getInstance();
				$ckpn_core->ckpn_send_notification( $args );
			}
		} else {
			ckpn_send_notification( $args );
		}
	}

	function new_activity( $post_id = 0 ) {
		if ( empty( $post_id ) ) {
			return false;
		}
		$title = false;

		if ( 'dwqa_add_comment' == current_action() ) {
			$comment = get_comment( $post_id );
			$title = __( 'New Comment in: ', 'dwqa-notification' );
			$post_id = $comment->comment_post_ID;
		}

		if ( 'dwqa-answer' == get_post_type( $post_id ) ) {
			$question_id = get_post_type( $post_id, '_question', true );

			if ( !$title ) {
				$title = __( 'New Answer in: ', 'dwqa-notification' );
			}

		} else {
			$question_id = $post_id;
			if ( !$title ) {
				$title = __( 'New Question: ', 'dwqa-notification' );
			}
		}

		$question_title = get_post_field( 'post_title', $question_id );
		$question_link = get_permalink( $question_id );
		$content = get_post_field( 'post_content', $post_id );
		$title = $title . $question_title;

		$args = array( 'title' => $title, 'message' => strip_tags( $content ) );
		$this->push( $args );
	}

	public function get_from_address() {
		$from_email = get_option( 'dwqa_subscrible_from_address', get_bloginfo( 'admin_email' ) );

		if ( empty( $from_email ) ) {
			$from_email = get_bloginfo( 'admin_email' );
		}

		return sanitize_email( $from_email );
	}

	public function get_from_name() {
		$name = get_option( 'dwqa_subscrible_from_name', get_bloginfo( 'name' ) );

		if ( empty( $name ) ) {
			$name = get_bloginfo( 'name' );
		}

		return $name;
	}

	public function get_content_type() {
		return apply_filters( 'dwqa_notifications_get_content_type', 'text/html' );
	}

	public function send( $to, $subject, $message, $headers = '', $attachments = array() ) {
		// return ;
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ), 9999 );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ), 9999 );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 9999 );

		$sended = wp_mail( $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
		return $sended;
	}
}


?>
