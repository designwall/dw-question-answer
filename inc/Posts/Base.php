<?php 
function dwqa_action_vote( ) {
	$result = array(
		'error_code'    => 'authorization',  
		'error_message' => __( 'Are you cheating, huh?', 'dwqa' ),
	);

	$vote_for = isset( $_POST['vote_for'] ) && sanitize_text_field( $_POST['vote_for'] ) == 'question'
					? 'question' : 'answer';

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_dwqa_'.$vote_for.'_vote_nonce' ) ) {
		wp_send_json_error( $result );
	}


	if ( ! isset( $_POST[ $vote_for . '_id'] ) ) {
		$result['error_code']       = 'missing ' . $vote_for;
		$result['error_message']    = __( 'What '.$vote_for.' are you looking for?', 'dwqa' );
		wp_send_json_error( $result );
	}

	$post_id = sanitize_text_field( $_POST[ $vote_for . '_id'] );
	$point = isset( $_POST['type'] ) && sanitize_text_field( $_POST['type'] ) == 'up' ? 1 : -1;

	//vote
	if ( is_user_logged_in( ) ) {
		global $current_user;

		if ( ! dwqa_is_user_voted( $post_id, $point ) ) {
			$votes = maybe_unserialize(  get_post_meta( $post_id, '_dwqa_votes_log', true ) );

			$votes[$current_user->ID] = $point;
			//update
			do_action( 'dwqa_vote_'.$vote_for, $post_id, ( int ) $point );
			update_post_meta( $post_id, '_dwqa_votes_log', serialize( $votes ) );
			// Update vote point
			dwqa_update_vote_count( $post_id );

			$point = dwqa_vote_count( $post_id );
			if ( $point > 0 ) {
				$point = '+' . $point;
			}
			wp_send_json_success( array( 'vote' => $point ) );
		} else {
			$result['error_code'] = 'voted';
			$result['error_message'] = __( 'You voted for this ' . $vote_for, 'dwqa' );
			wp_send_json_error( $result );
		}		
	} elseif ( 'question' == $vote_for ) {
		// useful of question with meta field is "_dwqa_question_useful", point of this question
		$useful = get_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful', true );
		$useful = $useful ? ( int ) $useful : 0;

		do_action( 'dwqa_vote_'.$vote_for, $post_id, ( int ) $point );
		update_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful', $useful + $point );

		// Number of votes by guest
		$useful_rate = get_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful_rate', true );
		$useful_rate = $useful_rate ? ( int ) $useful_rate : 0;
		update_post_meta( $post_id, '_dwqa_'.$vote_for.'_useful_rate', $useful_rate + 1 );
	}
}
add_action( 'wp_ajax_dwqa-action-vote', 'dwqa_action_vote' );
add_action( 'wp_ajax_nopriv_dwqa-action-vote', 'dwqa_action_vote' );

/**
 * Check for current user can vote for the question
 * @param  int  $post_id ID of object ( question /answer ) post
 * @param  int  $point       Point of vote
 * @param  boolean $user        Current user id
 * @return boolean              Voted or not
 */
function dwqa_is_user_voted( $post_id, $point, $user = false ) {
	if ( ! $user ) {
		global $current_user;
		$user = $current_user->ID;
	}
	$votes = maybe_unserialize(  get_post_meta( $post_id, '_dwqa_votes_log', true ) );

	if ( empty( $votes ) ) { 
		return false; 
	}

	if ( array_key_exists( $user, $votes ) ) {
		if ( ( int ) $votes[$user] == $point ) {
			return $votes[$user];
		}
	}
	return false;   
}

function dwqa_get_user_vote( $post_id, $user = false ) {
	if ( ! $user ) {
		global $current_user;
		$user = $current_user->ID;
	}
	if ( dwqa_is_user_voted( $post_id, 1, $user ) ) {
		return 'up';
	} else if ( dwqa_is_user_voted( $post_id, -1, $user ) ) {
		return 'down';
	}
	return false;
}
/**
 * Calculate number of votes for specify post
 * @param  int $post_id ID of post
 * @return void              
 */
function dwqa_update_vote_count( $post_id ) {
	if ( ! $post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	$votes = maybe_unserialize(  get_post_meta( $post_id, '_dwqa_votes_log', true ) );
	
	if ( empty( $votes ) ) {
		return 0;
	}

	$total = array_sum( $votes );
	update_post_meta( $post_id, '_dwqa_votes', $total );
	return $total;
}

/**
 * Return vote point of post
 * @param  int $post_id ID of post
 * @param  boolean $echo        Print or not
 * @return int  Vote point
 */
function dwqa_vote_count( $post_id = false, $echo = false ) {
	if ( ! $post_id ) {
		global $post;
		$post_id = $post->ID;
		if( isset( $post->vote_count ) ) {
			return $post->vote_count;
		}
	}
	$votes = get_post_meta( $post_id, '_dwqa_votes', true );
	if ( empty( $votes ) ) {
		return 0;
	} 
	if ( $echo ) {
		echo $votes;
	}
	return ( int ) $votes;
}

function dwqa_decode_pre_entities( $matches ) {
	$content = $matches[0];
	$content = str_replace( $matches[1], '', $content );
	$content = str_replace( $matches[5], '', $content );
	$content = str_replace( $matches[2], '', $content );
	//$content = str_replace( $matches[3], html_entity_decode( $matches[3] ), $content );
	return $content;
}

function dwqa_content_html_decode( $content ) {
	return preg_replace_callback( '/( <pre> )<code( [^>]* )>( .* )<\/( code )[^>]*>( <\/pre[^>]*> )/isU' , 'dwqa_decode_pre_entities',  $content );
}
/**
 * Is post was submitted by a guest
 * @param  int $post_id question/answer id
 * @return boolean
 */
function dwqa_is_anonymous( $post_id ) {
	$anonymous = get_post_meta( $post_id, '_dwqa_is_anonymous', true );
	if ( $anonymous ) {
		return true;
	}
	return false;
}



function dwqa_get_latest_action_date( $question = false, $before = '<span>', $after = '</span>' ){
	if ( ! $question ) {
		$question = get_the_ID();
	}
	global $post;

	$message = '';

	$latest_answer = dwqa_get_latest_answer( $question );
	$last_activity_date = $latest_answer ? $latest_answer->post_date : get_post_field( 'post_date', $question );
	$post_id = $latest_answer ? $latest_answer->ID : $question;
	$author_id = $post->post_author;
	if ( $author_id == 0 || dwqa_is_anonymous( $post_id ) ) {
		$anonymous_name = get_post_meta( $post_id, '_dwqa_anonymous_name', true );
		if ( $anonymous_name ) {
			$author_link = $anonymous_name . ' ';
		} else {
			$author_link = __( 'Anonymous', 'dwqa' )  . ' ';
		}
	} else {
		$display_name = get_the_author_meta( 'display_name', $author_id );
		$author_url = get_author_posts_url( $author_id );
		$author_avatar = wp_cache_get( 'avatar_of_' . $author_id, 'dwqa' );
		if ( false === $author_avatar ) {
			$author_avatar = get_avatar( $author_id, 12 );
			wp_cache_set( 'avatar_of_'. $author_id, $author_avatar, 'dwqa', 60*60*24*7 );
		}
		$author_link = sprintf(
			'<span class="dwqa-author"><span class="dwqa-user-avatar">%4$s</span> <a href="%1$s" title="%2$s" rel="author">%3$s</a></span>',
			$author_url,
			esc_attr( sprintf( __( 'Posts by %s' ), $display_name ) ),
			$display_name,
			$author_avatar
		);
	}
	
	if ( $last_activity_date && $post->last_activity_type == 'answer' ) {
		$date = dwqa_human_time_diff( strtotime( $last_activity_date ), false, get_option( 'date_format' ) );
		return sprintf( __( '%s answered <span class="dwqa-date">%s</span>', 'dwqa' ), $author_link, $date );
	}
	return sprintf( __( '%s asked <span class="dwqa-date">%s</span>', 'dwqa' ), $author_link, get_the_date() );
}

class DWQA_Posts_Base {

	private $slug;
	private $labels;

	// tags allowed for post content
	protected $filter = array(
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

	public function __construct( $slug, $labels ) {
		$this->slug = $slug;
		$this->labels = is_array( $labels ) ? $labels : array();

		// add posttype
		add_action( 'init', array( $this, 'register' ) );
		// Do any init by it self
		add_action( 'init', array( $this, 'init' ) );
		// Custom Admin List Table
		add_filter( 'manage_posts_columns', array( $this, 'columns_head' ) );

		// Auto convert url in the conthen to clickable and no-follow
		add_filter( 'the_content', array( $this, 'auto_convert_urls' ) );

		add_filter( 'wp_insert_post_data', array( $this, 'hook_on_update_anonymous_post' ), 10, 2 );

		add_action( 'dwqa-prepare-archive-posts', array( $this, 'prepare_archive_posts' ) );
		add_action( 'dwqa-after-archive-posts', array( $this, 'after_archive_posts' ) );
	}

	// Abstract, do all init actions for itself
	public function init(){}

	public function columns_head( $default ){ return $default; }

	public function get_slug() {
		return $this->slug;
	}

	public function get_name_labels() {
		return wp_parse_args( $this->labels, array(
			'plural' => __( 'DWQA Posts', 'dwqa' ),
			'singular' => __( 'DWQA Post', 'dwqa' ),
		) );
	}

	public function set_labels() {
		$names = $this->get_name_labels();

		return $labels = array(
			'name'                => $names['plural'],
			'singular_name'       => $names['singular'],
			'add_new'             => _x( 'Add New', 'dwqa', 'dwqa' ) . ' ' . $names['singular'],
			'add_new_item'        => __( 'Add New', 'dwqa' ) . ' ' . $names['singular'],
			'edit_item'           => __( 'Edit', 'dwqa' ) . ' ' . $names['singular'],
			'new_item'            => __( 'New', 'dwqa' ) . ' ' . $names['singular'],
			'view_item'           => __( 'View', 'dwqa' ) . ' ' . $names['singular'],
			'search_items'        => __( 'Search ', 'dwqa' ) . $names['plural'],
			'not_found'           => $names['plural'] . ' ' . __( 'not found', 'dwqa' ),
			'not_found_in_trash'  => $names['plural'] . ' ' . __( 'not found in Trash', 'dwqa' ),
			'parent_item_colon'   => __( 'Parent:', 'dwqa' ) . ' ' . $names['singular'],
			'menu_name'           => isset( $names['menu'] ) ? $names['menu'] : $names['plural'],
		);
	}

	public function register() {
		
		$args = array(
			'labels'              => array(),
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => array(),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => null,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'supports'            => array(
				'title', 'editor', 'author', 'thumbnail',
				'excerpt','custom-fields', 'trackbacks', 'comments',
				'revisions', 'page-attributes', 'post-formats'
			)
		);

		foreach ( $args as $key => $value ) {
			$method = 'set_' . $key;
			if ( method_exists( $this, $method ) ) {
				$args[$key] = call_user_func( array( $this, $method ) );
			}
		}

		register_post_type( $this->get_slug(), $args );
	}

	public function pre_content_filter( $content ) {
		return preg_replace_callback( '/<( code )( [^>]* )>( .* )<\/( code )[^>]*>/isU' , array( $this, 'convert_pre_entities' ),  $content );
	}

	public function pre_content_kses( $content ) {
		return wp_kses( $content, $this->filter );
	}

	public function convert_pre_entities( $matches ) {
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

	public function auto_convert_urls( $content ) {
		global $post;
		if ( is_single() && ( 'dwqa-question' == $post->post_type || 'dwqa-answer' == $post->post_type ) ) {
			$content = make_clickable( $content );
			$content = preg_replace_callback( '/<a[^>]*>]+/', array( $this, 'auto_nofollow_callback' ), $content );
		}
		return $content;
	}

	public function auto_nofollow_callback( $matches ) {
		$link = $matches[0];
		$site_link = get_bloginfo( 'url' );
	 
		if ( strpos( $link, 'rel' ) === false ) {
			$link = preg_replace( "%( href=S( ?! $site_link ))%i", 'rel="nofollow" $1', $link );
		} elseif ( preg_match( "%href=S( ?! $site_link )%i", $link ) ) {
			$link = preg_replace( '/rel=S( ?! nofollow )S*/i', 'rel="nofollow"', $link );
		}
		return $link;
	}

	public function hook_on_update_anonymous_post( $data, $postarr ) {
		if ( isset( $postarr['ID'] ) && get_post_meta( $postarr['ID'], '_dwqa_is_anonymous', true ) ) {
			$data['post_author'] = 0;
		} 
		return $data;
	}

	public function prepare_archive_posts() {
		global $wp_query,$dwqa_general_settings;
		
		$posts_per_page = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : 5;
		$query = array(
			'post_type' => 'dwqa-question',
			'posts_per_page' => $posts_per_page,
			'orderby'	=> 'modified',
		);
		if ( is_tax( 'dwqa-question_category' ) ) {
			$query['dwqa-question_category'] = get_query_var( 'dwqa-question_category' );
		} 
		if ( is_tax( 'dwqa-question_tag' ) ) {
			$query['dwqa-question_tag'] = get_query_var( 'dwqa-question_tag' );
		} 
		$paged = get_query_var( 'paged' );
		$query['paged'] = $paged ? $paged : 1; 
		$sticky_questions = get_option( 'dwqa_sticky_questions' );

		if ( $sticky_questions ) {
			$query['post__not_in'] = $sticky_questions;
		}
		if ( is_user_logged_in() ) {
			$query['post_status'] = array( 'publish', 'private', 'pending' );
		}
		query_posts( $query );
	}

	public function after_archive_posts() {
		wp_reset_query();
		wp_reset_postdata();
	}

	public function rewrite() {
		return true;
	}
}

?>