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

class DWQA_Posts_Question extends DWQA_Posts_Base {

	public function __construct() {
		parent::__construct( 'dwqa-question', array(
			'plural' => __( 'Questions', 'dwqa' ),
			'singular' => __( 'Question', 'dwqa' ),
			'menu'	 => __( 'DWQA', 'dwqa' )
		) );

		add_action( 'manage_dwqa-question_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
	}

	public function init() {
		$this->register_taxonomy();
	}

	public function set_supports() {
		return array( 'title', 'editor', 'comments', 'author', 'page-attributes' );
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
			'rewrite'           => true,
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
			'rewrite'           => true,
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
	function columns_content( $column_name, $post_ID ) {  
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
}

?>