<?php  

function dwqa_current_user_can( $perm, $post_id = false ) {
	global $dwqa, $current_user;
	if ( is_user_logged_in() ) {
		if ( $post_id && $current_user->ID == get_post_field( 'post_author', $post_id ) ) {
			return true;
		}

		if ( current_user_can( 'dwqa_can_' . $perm ) ) {
			return true;
		}
		return false;
	} else {
		$anonymous = $dwqa->permission->perms['anonymous'];
		$type = explode( '_', $perm );
		if ( isset( $anonymous[$type[1]][$type[0]] ) && $anonymous[$type[1]][$type[0]] ) {
			return true;
		} else {
			return false;
		}
	}
	return false;
}

function dwqa_get_warning_page() {
	global $dwqa_options, $wpdb;
	$warning_page_id = isset( $dwqa_options['pages']['404'] ) ? $dwqa_options['pages']['404'] : false;
	if (  $warning_page_id ) {
	
		$warning_page = wp_cache_get( 'dwqa-warning-page' );
		if ( $warning_page == false ) {
			$query = $wpdb->prepare( 'SELECT * FROM '.$wpdb->prefix.'posts WHERE ID = %d ',
				$warning_page_id
			);
			$warning_page = $wpdb->get_results( $query );
			wp_cache_set( 'dwqa-warning-page', $warning_page );
		}
		return $warning_page;
	}
}

class DWQA_Permission {
	public $defaults;
	public $perms;
	public $default_cap;
	public $objects;
	
	public function __construct() {
		$this->default_cap = array(
			'read'      => 1,
			'post'      => 0,
			'edit'      => 0,
			'delete'    => 0,
		);
		$this->objects = array( 'question', 'answer', 'comment' );
		$this->defaults = array(
			'administrator' => array(
				'question'      => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 1,
					'delete'    => 1,
				),
				'answer'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 1,
					'delete'    => 1,  
				),
				'comment'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 1,
					'delete'    => 1,
				),
			),
			'editor'        => array(
				'question'      => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 1,
					'delete'    => 1,
				),
				'answer'        => array(
					'read'      => 1,
					'post'      => 1,
					'edit'      => 1,
					'delete'    => 1,
				),
				'comment'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 1,
					'delete'    => 1, 
				),
			),
			'author'        => array(
				'question'      => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'answer'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'comment'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
			),
			'contributor'   => array(
				'question'      => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'answer'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'comment'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
			),
			'subscriber'    => array(
				'question'      => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'answer'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'comment'        => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				)
			),
			'anonymous'    => array(
				'question'      => array( 
					'read'      => 1,
					'post'      => 1,
					'edit'      => 0,
					'delete'    => 0,
				),
				'answer'        => array( 
					'read'      => 1,
					'post'      => 0,
					'edit'      => 0,
					'delete'    => 0,
				),
				'comment'        => array( 
					'read'      => 1,
					'post'      => 0,
					'edit'      => 0,
					'delete'    => 0,
				),
			),
		);
		add_action( 'init', array( $this, 'first_update_role_functions' ) );
		add_action( 'init', array( $this, 'prepare_permission' ) );
		add_action( 'update_option_dwqa_permission', array( $this, 'update_caps' ), 10, 2 );

		add_filter( 'user_has_cap', array( $this, 'allow_user_view_their_draft_post' ), 10, 4 );

		add_action( 'wp_ajax_dwqa-reset-permission-default', array( $this, 'reset_permission_default' ) );
		add_filter( 'the_posts', array( $this, 'read_permission_apply' ), 10, 2 );
		add_filter( 'comments_array', array( $this, 'read_comment_permission_apply' ), 10, 2 );

		add_filter( 'the_posts', array( $this, 'restrict_single_question' ), 11 );
	}

	public function prepare_permission() {
		$this->perms = get_option( 'dwqa_permission' );
		$this->perms = $this->perms ? $this->perms : array();
		$this->perms = wp_parse_args( $this->perms, $this->defaults );
	}


	public function add_caps( $value ) {
		// $roles = get_editable_roles();
		$this->prepare_permission();

		foreach ( $value as $role_name  => $role_info ) {
			if ( $role_name == 'anonymous' )
				continue;
			$role = get_role( $role_name );
			if ( ! $role )
				continue;

			foreach ( $this->objects as $post_type ) {
				foreach ( $this->default_cap as $cap => $default ) {
					if ( isset( $this->perms[$role_name][$post_type][$cap] ) && $this->perms[$role_name][$post_type][$cap] ) {
						$role->add_cap( 'dwqa_can_' . $cap . '_' . $post_type );
					} else {
						$role->remove_cap( 'dwqa_can_' . $cap . '_' . $post_type );
					}
				}
			}
		}
	}
	public function update_caps( $old_value, $value ) {
		//update_option( 'dwqa_permission', $this->perms );
		$this->add_caps( $value );
	}

	public function reset_caps( $old_value, $value ) {
		update_option( 'dwqa_permission', $this->perms );
		$this->add_caps( $value );
	}

	public function prepare_permission_caps() {
		$this->add_caps( $this->defaults );
	}

	public function first_update_role_functions() {
		$dwqa_has_roles = get_option( 'dwqa_has_roles' );
		$dwqa_permission = get_option( 'dwqa_permission' );
		$this->perms = get_option( 'dwqa_permission' );
		if ( ! $dwqa_has_roles || ! is_array( $this->perms ) || empty( $this->perms ) ) {
			$this->perms = $this->defaults;
			$this->prepare_permission_caps();
			update_option( 'dwqa_permission', $this->perms );
			update_option( 'dwqa_has_roles', 1 );
		}
	}  

	public function remove_permision_caps() {
		foreach ( $this->defaults as $role_name => $perm ) {
			if ( $role_name == 'anonymous' ) {
				continue;
			}
			$role = get_role( $role_name );
			foreach ( $perm['question'] as $key => $value ) {
				$role->remove_cap( 'dwqa_can_'.$key.'_question' );
			}
			foreach ( $perm['answer'] as $key => $value ) {
				$role->remove_cap( 'dwqa_can_'.$key.'_answer' );
			}
			foreach ( $perm['comment'] as $key => $value ) {
				$role->remove_cap( 'dwqa_can_'.$key.'_comment' );
			}
		}
	}

	public function allow_user_view_their_draft_post( $all_caps, $caps, $name, $user ) {
		if ( is_user_logged_in() ) {
			global $wp_query, $current_user;
			if ( isset( $wp_query->is_single ) && $wp_query->is_single && $wp_query->query_vars['post_type'] == 'dwqa-question' && $name[0] == 'edit_post' ) {
				if ( isset( $name[2] ) ) {
					$post_id = $name[2];
					$author = get_post_field( 'post_author', $post_id );
					if ( $author == $current_user->ID ) {
						foreach ( $caps as $cap ) {
							$all_caps[$cap] = true;
						}
					}
				}
			}
		}
		return $all_caps;
	}

	public function reset_permission_default() {
		global $dwqa;
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_dwqa_reset_permission' ) ) {
			wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
		}
		if ( isset( $_POST['type'] ) ) {
			$old = $dwqa->permission->perms;
			$type = sanitize_text_field( $_POST['type'] );
			foreach ( $dwqa->permission->defaults as $role => $perms ) {
				$dwqa->permission->perms[$role][$type] = $perms[$type];
			}
			$dwqa->permission->reset_caps( $old, $dwqa->permission->perms );
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public function read_permission_apply( $posts, $query ) {

		if ( isset( $query->query['post_type'] ) && $query->query['post_type'] == 'dwqa-question' && ! dwqa_current_user_can( 'read_question' ) ) {
			return false;
		}

		if ( isset( $query->query['post_type'] ) && $query->query['post_type'] == 'dwqa-answer' && ! dwqa_current_user_can( 'read_answer' ) ) {
			return false;
		}

		return $posts;
	}

	public function read_comment_permission_apply( $comments, $post_id ) {
		if ( ( 'dwqa-question' == get_post_type( $post_id ) || 'dwqa-answer' == get_post_type( $post_id ) ) && ! dwqa_current_user_can( 'read_comment' ) ) {
			return array();
		}
		return $comments;
	}

	public function restrict_single_question( $posts ) {
		global $wp_query, $wpdb, $dwqa_options;
		if ( is_user_logged_in() ) 
			return $posts;
		//user is not logged
		if ( ! is_single() ) 
			return $posts;
		//this is a single post

		if ( ! $wp_query->is_main_query() )
			return $posts;
		//this is the main query

		if ( $wp_query->post_count ) 
			return $posts;

		if ( ! isset( $wp_query->query['post_type'] ) || $wp_query->query['post_type'] != 'dwqa-question' ) {
			return $posts;
		}
		if ( isset( $wp_query->query['name'] ) && ! $posts ) {
			$question = get_page_by_path( $wp_query->query['name'], OBJECT, 'dwqa-question' );
		} elseif ( isset( $wp_query->query['p'] ) && ! $posts ) {
			$question = get_post( $wp_query->query['p'] );
		} elseif ( ! empty( $posts ) ) {
			$question = $posts[0];	
		} else {
			return dwqa_get_warning_page();
		}
		//this is a question which was submitted by anonymous user
		if ( ! dwqa_is_anonymous( $question->ID ) ) {
			if ( ! $posts ) {
				return dwqa_get_warning_page();
			}
			return $posts;
		} else {
			//This is a pending question
			if ( 'pending' == get_post_status( $question->ID ) || 'private' == get_post_status( $question->ID ) ) {
				$anonymous_author_view = get_post_meta( $question->ID, '_anonymous_author_view', true );
				$anonymous_author_view = $anonymous_author_view  ? $anonymous_author_view  : 0;
				
				
				if ( $anonymous_author_view < 3 ) {
					// Allow to read question right after this was added
					$questions[] = $question;
					$anonymous_author_view++;
					update_post_meta( $question->ID, '_anonymous_author_view', $anonymous_author_view );
					return $questions;
				} else {
					return dwqa_get_warning_page();
				}
			}
		}

		return $posts;
	}
}

?>