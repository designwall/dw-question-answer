<?php
/**
 *  DW Question Answer Shortcode
 */
class DWQA_Shortcode {
	private $shortcodes = array(
		'dwqa-list-questions',
		'dwqa-submit-question-form', 
		'dwqa-popular-questions',
		'dwqa-latest-answers',
		'dwqa-question-followers',
		'dwqa-question-list'
	);

	public function __construct() {
		if ( ! defined( 'DWQA_DIR' ) ) {
			return false;
		}
		
		add_shortcode( 'dwqa-list-questions', array( $this, 'archive_question') );
		add_shortcode( 'dwqa-submit-question-form', array( $this, 'submit_question_form_shortcode') );
		add_shortcode( 'dwqa-popular-questions', array( $this, 'shortcode_popular_questions' ) );
		add_shortcode( 'dwqa-latest-answers', array( $this, 'shortcode_latest_answers' ) );
		add_shortcode( 'dwqa-question-followers', array( $this, 'question_followers' ) );
		//add_shortcode( 'dwqa-question-list', array( $this, 'question_list' ) );
		add_filter( 'the_content', array( $this, 'post_content_remove_shortcodes' ), 0 );
	}

	public function sanitize_output( $buffer ) {
		$search = array(
			'/\>[^\S ]+/s',  // strip whitespaces after tags, except space
			'/[^\S ]+\</s',  // strip whitespaces before tags, except space
			'/(\s)+/s',       // shorten multiple whitespace sequences
			"/\r/",
			"/\n/",
			"/\t/",
			'/<!--[^>]*>/s',
		);

		$replace = array(
			'>',
			'<',
			'\\1',
			'',
			'',
			'',
			'',
		);

		$buffer = preg_replace( $search, $replace, $buffer );
		return $buffer;
	}

	public function archive_question( $atts = array() ) {
		global $wp_query, $dwqa, $script_version, $dwqa_sript_vars, $dwqa_atts;
		$dwqa_atts = (array)$atts;
		$dwqa_atts['page_id'] = isset($wp_query->post) && isset($wp_query->post->ID) && $wp_query->post->ID ? $wp_query->post->ID : 0;
		ob_start();

		if ( isset( $atts['category'] ) ) {
			$atts['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_category',
				'terms' => esc_html( $atts['category'] ),
				'field' => 'slug'
			);
			unset( $atts['category'] );
		}

		if ( isset( $atts['tag'] ) ) {
			$atts['tax_query'][] = array(
				'taxonomy' => 'dwqa-question_tag',
				'terms' => esc_html( $atts['tag'] ),
				'field' => 'slug'
			);
			unset( $atts['tag'] );
		}

		$dwqa->template->remove_all_filters( 'the_content' );
		dwqa()->filter->prepare_archive_posts( $atts );
		echo '<div class="dwqa-container" >';
		dwqa_load_template( 'archive', 'question' );
		echo '</div>';
		$html = ob_get_contents();

		$dwqa->template->restore_all_filters( 'the_content' );

		ob_end_clean();
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'dwqa-questions-list', DWQA_URI . 'templates/assets/js/dwqa-questions-list.js', array( 'jquery', 'jquery-ui-autocomplete' ), $script_version, true );
		wp_localize_script( 'dwqa-questions-list', 'dwqa', $dwqa_sript_vars );
		return apply_filters( 'dwqa-shortcode-question-list-content', $this->sanitize_output( $html ) );
	}

	public function submit_question_form_shortcode() {
		global $dwqa, $dwqa_sript_vars, $script_version;
		ob_start();

		$dwqa->template->remove_all_filters( 'the_content' );

		echo '<div class="dwqa-container" >';
		dwqa_load_template( 'question', 'submit-form' );
		echo '</div>';
		$html = ob_get_contents();

		$dwqa->template->restore_all_filters( 'the_content' );

		ob_end_clean();
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'dwqa-submit-question', DWQA_URI . 'templates/assets/js/dwqa-submit-question.js', array( 'jquery', 'jquery-ui-autocomplete' ), $script_version, true );
		wp_localize_script( 'dwqa-submit-question', 'dwqa', $dwqa_sript_vars );
		return $this->sanitize_output( $html );
	}

	public function shortcode_popular_questions( $atts ){
		extract( shortcode_atts( array(
			'number' => 5,
			'title' => __( 'Popular Questions', 'dw-question-answer' ),
		), $atts ) );

		$args = array(
			'posts_per_page'       => $number,
			'order'             => 'DESC',
			'orderby'           => 'meta_value_num',
			'meta_key'           => '_dwqa_views',
			'post_type'         => 'dwqa-question',
			'suppress_filters'  => false,
		);
		$questions = new WP_Query( $args );
		$html = '';

		if ( $title ) {
			$html .= '<h3>';
			$html .= $title;
			$html .= '</h3>';
		}
		if ( $questions->have_posts() ) {
			$html .= '<div class="dwqa-popular-questions">';
			$html .= '<ul>';
			while ( $questions->have_posts() ) { $questions->the_post();
				$html .= '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__( 'asked by', 'dw-question-answer' ).' ' . get_the_author_link() . '</li>';
			}   
			$html .= '</ul>';
			$html .= '</div>';
		}
		wp_reset_query();
		wp_reset_postdata();
		return $html;
	}

	public function shortcode_latest_answers( $atts ){

		extract( shortcode_atts( array(
			'number' => 5,
			'title' => __( 'Latest Answers', 'dw-question-answer' )
		), $atts ) );

		$args = array(
			'posts_per_page'    => $number,
			'post_type'         => 'dwqa-answer',
			'suppress_filters'  => false,
		);
		$questions = new WP_Query( $args );
		$html = '';

		if ( $title ) {
			$html .= '<h3>';
			$html .= $title;
			$html .= '</h3>';
		}
		if ( $questions->have_posts() ) {
			$html .= '<div class="dwqa-latest-answers">';
			$html .= '<ul>';
			while ( $questions->have_posts() ) { $questions->the_post();
				$answer_id = get_the_ID();
				$question_id = dwqa_get_post_parent_id( $answer_id );
				if ( 'publish' != get_post_status( $question_id ) ) {
					continue;
				}
				if ( $question_id ) {
					$html .= '<li>'.__( 'Answer at', 'dw-question-answer' ).' <a href="'.get_permalink( $question_id ).'#answer-'.$answer_id.'" title="'.__( 'Link to', 'dw-question-answer' ).' '.get_the_title( $question_id ).'">'.get_the_title( $question_id ).'</a></li>';
				}
			}   
			$html .= '</ul>';
			$html .= '</div>';
		}
		wp_reset_query();
		wp_reset_postdata();
		return $html;
	}

	function question_followers( $atts ) {
		extract( shortcode_atts( array(
			'id'    => false,
			'before_title'  => '<h3 class="small-title">',
			'after_title'   => '</h3>',
		), $atts ) );
		if ( ! $id ) {
			global $post;
			$id = $post->ID;
		}
		$followers = dwqa_get_following_user( $id );
		$question = get_post( $id );
		$followers[] = $question->post_author;
		if ( ! empty( $followers ) ) :
			echo '<div class="question-followers">';
			echo $before_title;
			$count = count( $followers );
			printf( _n( '%d person who is following this question', '%d people who are following this question', $count,  'dw-question-answer' ),  $count );
			echo $after_title;

			foreach ( $followers as $follower ) :
				$user_info = get_userdata( $follower );
				if ( $user_info ) :
				 echo '<a href="'.home_url().'/profile/'.$user_info->user_nicename . '" title="'.$user_info->display_name.'">'.get_avatar( $follower, 32 ).'</a>&nbsp;';
				endif;
			endforeach;
			echo '</div>';
		endif;
	}
   
	function post_content_remove_shortcodes( $content ) {
		$shortcodes = array(
			'dwqa-list-questions',
			'dwqa-submit-question-form',
		);
		if ( is_singular( 'dwqa-question' ) || is_singular( 'dwqa-answer' ) ) {
			foreach ( $shortcodes as $shortcode_tag ) 
				remove_shortcode( $shortcode_tag );
		}
		/* Return the post content. */
		return $content;
	}

	function question_list( $atts ) {
		extract( shortcode_atts( array(
			'categories' 	=> '',
			'number' 		=> '',
			'title' 		=> __( 'Question List', 'dw-question-answer' ),
			'orderby' 		=> 'modified',
			'order' 		=> 'DESC'
		), $atts ) );

		$args = array(
			'post_type' 		=> 'dwqa-question',
			'posts_per_page' 	=> $number,
			'orderby' 			=> $orderby,
			'order' 			=> $order,
		);

		if ( $term ) {
			$args['tax_query'][] = array(
				'taxonomy' 	=> 'dwqa-question_category',
				'terms' 	=> explode( ',', $categories ),
				'field' 	=> 'slug'
			);
		}

		if ( $title ) {
			echo '<h3>';
			echo $title;
			echo '</h3>';
		}

	}
}

?>