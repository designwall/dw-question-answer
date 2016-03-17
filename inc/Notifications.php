<?php  

class DWQA_Notifications {

	public function __construct() {
		add_action( 'dwqa_add_question', array( $this, 'new_question_notify' ), 10, 2 );
		add_action( 'dwqa_add_answer', array( $this, 'new_answer_nofity' ) );
		add_action( 'dwqa_update_answer', array( $this, 'new_answer_nofity' ) );
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
		$message = str_replace( '{user_link}', get_author_posts_url( $user_id ), $message );
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
		wp_mail( $admin_email, $subject, $message, $headers );
	}

	public function new_answer_nofity( $answer_id ) {
		$enabled = get_option( 'dwqa_subscrible_enable_new_answer_notification', 1 );
		if ( ! $enabled ) {
			return false;
		}

		//Admin email
		$admin_email = $admin_email = $this->get_admin_email( 'answer' );
		$enable_send_copy = get_option( 'dwqa_subscrible_send_copy_to_admin' );

		$question_id = get_post_meta( $answer_id, '_question', true );
		$question = get_post( $question_id );
		$answer = get_post( $answer_id );
		if ( $answer->post_status != 'publish' && $answer->post_status != 'private' ) {
			return false;
		}
		//Send email alert for author of question about this answer
		if ( dwqa_is_anonymous( $question_id ) ) {
			$email = get_post_meta( $question_id, '_dwqa_anonymous_email', true );
			if ( ! is_email( $email ) ) {
				return false;
			}
		} else {
			// if user is not the author of question/answer, add user to followers list
			if ( $question->post_author != $answer->post_author ) {
				if ( ! dwqa_is_followed( $question_id, $answer->post_author ) ) {
					add_post_meta( $question_id, '_dwqa_followers', $answer->post_author );
				}
			}

			$email = get_the_author_meta( 'user_email', $question->post_author );
		}

		$subject = get_option( 'dwqa_subscrible_new_answer_email_subject' );
		if ( ! $subject ) {
			$subject = __( 'A new answer for "{question_title}" was posted on {site_name}', 'dwqa' );
		}
		$subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $subject );
		$subject = str_replace( '{question_title}', $question->post_title, $subject );
		$subject = str_replace( '{question_id}', $question->ID, $subject );

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

		//From email 
		$from_email = get_option( 'dwqa_subscrible_from_address' );
		if ( $from_email ) {
			$headers .= 'From: ' . $from_email . "\r\n";
		}

		$message = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_email', 'new-answer' );
		if ( ! $message ) {
			return false;
		}

		//Receiver
		$message = str_replace( '{question_author}', get_the_author_meta( 'display_name', $question->post_author ), $message );
		//Answer
		$answer  = get_post( $answer_id ); 

		if ( dwqa_is_anonymous( $answer_id ) ) {
			$user_id = 0;
			$display_name = __( 'Anonymous', 'dwqa' );
			$avatar = get_avatar( $user_id, '60' );
			$answer_author = __( 'Anonymous', 'dwqa' );
		} else {
			$user_id = $answer->post_author;
			$display_name = get_the_author_meta( 'display_name', $user_id );
			$avatar = get_avatar( $user_id, '60' );
			$answer_author = '<a href="'.get_author_posts_url( $user_id ).'" >'.get_the_author_meta( 'display_name', $user_id ).'</a>';
		}


		$subject = str_replace( '{username}', $display_name, $subject );
		$subject = str_replace( '{answer_author}', $answer_author, $subject );

		$message = str_replace( '{answer_avatar}', $avatar, $message );
		$message = str_replace( '{answer_author}', $answer_author, $message );
		$message = str_replace( '{question_link}', get_permalink( $question->ID ), $message );
		$message = str_replace( '{answer_link}', get_permalink( $question->ID ) . '#answer-' . $answer_id, $message );
		$message = str_replace( '{question_title}', $question->post_title, $message );
		$message = str_replace( '{answer_content}', $answer->post_content, $message );
		// logo replace
		$logo = get_option( 'dwqa_subscrible_email_logo', '' );
		$logo = $logo ? '<img src="'.$logo.'" alt="'.get_bloginfo( 'name' ).'" style="max-width: 100%; height: auto;" />' : '';
		$message = str_replace( '{site_logo}', $logo, $message );
		$message = str_replace( '{site_name}', get_bloginfo( 'name' ), $message );
		$message = str_replace( '{site_description}', get_bloginfo( 'description' ), $message );
		$message = str_replace( '{site_url}', site_url(), $message );

		$enable_notify = get_option( 'dwqa_subscrible_enable_new_answer_followers_notification', true );
		
		if ( $enable_notify ) {
			$followers = get_post_meta( $question_id, '_dwqa_followers' );


			$answer_email = get_the_author_meta( 'user_email', $answer->post_author );
			if ( ! empty( $followers ) ) {
				$question_link = get_permalink( $question->ID );

				$follow_subject = get_option( 'dwqa_subscrible_new_answer_followers_email_subject' );
				if ( ! $follow_subject ) {
					$follow_subject = __( 'You have a new answer for your followed question','dwqa' );
				}
				$follow_subject = str_replace( '{site_name}', get_bloginfo( 'name' ), $follow_subject );
				$follow_subject = str_replace( '{question_title}', $question->post_title, $follow_subject );
				$follow_subject = str_replace( '{question_id}', $question->ID, $follow_subject );
				$follow_subject = str_replace( '{username}', $display_name, $follow_subject );
				$follow_subject = str_replace( '{answer_author}', $answer_author, $follow_subject );

				$message_to_follower = dwqa_get_mail_template( 'dwqa_subscrible_new_answer_followers_email', 'new-answer-followers' );

				
				$message_to_follower = str_replace( '{answer_avatar}', $avatar, $message_to_follower );
				$message_to_follower = str_replace( '{answer_author}', $answer_author, $message_to_follower );
				$message_to_follower = str_replace( '{question_link}', get_permalink( $question->ID ), $message_to_follower );
				$message_to_follower = str_replace( '{question_title}', $question->post_title, $message_to_follower );
				$message_to_follower = str_replace( '{answer_content}', $answer->post_content, $message_to_follower );
				$message_to_follower = str_replace( '{site_logo}', $logo, $message_to_follower );
				$message_to_follower = str_replace( '{site_name}', get_bloginfo( 'name' ), $message_to_follower );
				$message_to_follower = str_replace( '{site_description}', get_bloginfo( 'description' ), $message_to_follower );
				$message_to_follower = str_replace( '{site_url}', site_url(), $message_to_follower );

				foreach ( $followers as $follower ) {
					$follower = intval( $follower );
					$user_data = get_userdata( $follower );
					if ( $user_data ) {
						$follow_email = $user_data->user_email;
						$follower_name = $user_data->display_name;
						if ( $follow_email ) {
							//Send email to follower
							$message_to_each_follower = str_replace( '{follower}', $follower_name, $message_to_follower );
							$test = wp_mail( $follow_email, $follow_subject, $message_to_each_follower, $headers );
							if ( $enable_send_copy && $follow_email != $admin_email ) {
								wp_mail( $admin_email, $follow_subject, $message_to_each_follower, $headers );
							}
						}
					}
				}
			}
		} // Send email to followers

		// if ( $question->post_author != $answer->post_author ) {
		// 	wp_mail( $email, $subject, $message, $headers );
		// 	if ( $enable_send_copy && $email != $admin_email ) {
		// 		wp_mail( $admin_email, $subject, $message, $headers );
		// 	}
		// }
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
			$message = str_replace( '{comment_author_link}', get_author_posts_url( $comment->user_id ), $message );
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
				$message_to_follower = str_replace( '{comment_author_link}', get_author_posts_url( $comment->user_id ), $message_to_follower );
				$message_to_follower = str_replace( '{comment_author}', get_the_author_meta( 'display_name', $comment->user_id ), $message_to_follower );
				$message_to_follower = str_replace( '{comment_content}', $comment->comment_content, $message_to_follower );
				$message_to_follower = str_replace( '{site_name}', get_bloginfo( 'name' ), $message_to_follower );
				$message_to_follower = str_replace( '{site_description}', get_bloginfo( 'description' ), $message_to_follower );
				$message_to_follower = str_replace( '{site_url}', site_url(), $message_to_follower );

				if ( ! empty( $followers ) ) {
					foreach ( $followers as $follower ) {
						$follower = (int ) $follower;
						$user_data = get_userdata( $follower );
						if ( $user_data ) {
							$follow_email = $user_data->user_email;
							$follower_name = $user_data->display_name;

							if ( $follow_email && $follow_email != $post_parent_email && $follow_email != $comment_email ) {

								$message_to_each_follower = str_replace( '{follower}', $follower_name, $message_to_follower );
								wp_mail( $follow_email, $follow_subject, $message_to_each_follower, $headers );
								if ( $enable_send_copy && $follow_email != $admin_email ) {
									wp_mail( $admin_email, $follow_subject, $message_to_each_follower, $headers );
								}
							}
						}
					}
				}
			}

			if ( $post_parent->post_author != $comment->user_id ) {
				wp_mail( $post_parent_email, $subject, $message, $headers );
				if ( $enable_send_copy && $admin_email != $post_parent_email ) {
					wp_mail( $admin_email, $subject, $message, $headers );
				}
			}
		}
	}
	
	public function get_admin_email( $type = 'question' ){

		$admin_email = false;
		if ( 'question' == $type ) {
			$admin_email = get_option( 'dwqa_subscrible_sendto_address' );
		}

		if ( 'answer' == $type ) {
			$admin_email = get_option( 'dwqa_subscrible_new_answer_forward' );
		}

		if ( 'comment-question' == $type ) {
			$admin_email = get_option( 'dwqa_subscrible_new_comment_question_forward' );
		}

		if ( 'comment-answer' == $type ) {
			$admin_email = get_option( 'dwqa_subscrible_new_comment_answer_forward' );
		}

		if ( ! $admin_email ) {
			$admin_email = get_bloginfo( 'admin_email' );
		}
		return explode( PHP_EOL, $admin_email );
	}
}


?>
