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
	);

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

	public function __construct() {
		if ( ! defined( 'DWQA_DIR' ) ) {
			return false;
		}
		
		add_shortcode( 'dwqa-list-questions', array( $this, 'archive_question') );
		add_shortcode( 'dwqa-submit-question-form', array( $this, 'submit_question_form_shortcode') );
		add_shortcode( 'dwqa-popular-questions', array( $this, 'shortcode_popular_questions' ) );
		add_shortcode( 'dwqa-latest-answers', array( $this, 'shortcode_latest_answers' ) );
		add_shortcode( 'dwqa-question-followers', array( $this, 'question_followers' ) );
		add_filter( 'the_content', array( $this, 'post_content_remove_shortcodes' ), 0 );
	}

	public function archive_question() {
		global $script_version, $dwqa_sript_vars, $dwqa_template_compat;
		ob_start();

		$dwqa_template_compat->remove_all_filters( 'the_content' );

		echo '<div class="dwqa-container" >';
		dwqa_load_template( 'question', 'list' );
		echo '</div>';
		$html = ob_get_contents();

		$dwqa_template_compat->restore_all_filters( 'the_content' );

		ob_end_clean();

		wp_enqueue_script( 'dwqa-questions-list', DWQA_URI . 'inc/templates/default/assets/js/dwqa-questions-list.js', array( 'jquery' ), $script_version, true );
		wp_localize_script( 'dwqa-questions-list', 'dwqa', $dwqa_sript_vars );
		return $this->sanitize_output( $html );
	}

	public function submit_question_form_shortcode() {
		global $dwqa_sript_vars, $script_version, $dwqa_template_compat;
		ob_start();

		$dwqa_template_compat->remove_all_filters( 'the_content' );

		echo '<div class="dwqa-container" >';
		if ( dwqa_current_user_can( 'post_question' ) ) {
			dwqa_load_template( 'question', 'submit-form' );
		} else {
			echo '<p class="alert alert-error">'.__( 'You do not have permission to submit question.', 'dwqa' ).'</p>';
		}
		echo '</div>';
		$html = ob_get_contents();

		$dwqa_template_compat->restore_all_filters( 'the_content' );

		ob_end_clean();

		wp_enqueue_script( 'dwqa-submit-question', DWQA_URI . 'inc/templates/default/assets/js/dwqa-submit-question.js', array( 'jquery' ), $script_version, true );
		wp_localize_script( 'dwqa-submit-question', 'dwqa', $dwqa_sript_vars );
		return $this->sanitize_output( $html );
	}

	public function shortcode_popular_questions( $atts ){
		extract( shortcode_atts( array(
			'number' => 5,
			'title' => __( 'Popular Questions', 'dwqa' ),
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
				$html .= '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__( 'asked by', 'dwqa' ).' ' . get_the_author_link() . '</li>';
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
			'title' => __( 'Latest Answers', 'dwqa' )
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
				$question_id = get_post_meta( $answer_id, '_question', true );
				if ( 'publish' != get_post_status( $question_id ) ) {
					continue;
				}
				if ( $question_id ) {
					$html .= '<li>'.__( 'Answer at', 'dwqa' ).' <a href="'.get_permalink( $question_id ).'#answer-'.$answer_id.'" title="'.__( 'Link to', 'dwqa' ).' '.get_the_title( $question_id ).'">'.get_the_title( $question_id ).'</a></li>';
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
			printf( _n( '% person who is following this question', '% people who are following this question', $count,  'dwqa' ),  $count );
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
}

$GLOBALS['dwqa_shortcode'] = new DWQA_Shortcode();

?>