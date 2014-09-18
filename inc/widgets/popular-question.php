<?php  

class DWQA_Popular_Question_Widget extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 **/
	function DWQA_Popular_Question_Widget() {
		$widget_ops = array( 'classname' => 'dwqa-widget dwqa-popular-question', 'description' => __( 'Show a list of questions that ordered by views.', 'dwqa' ) );
		$this->WP_Widget( 'dwqa-popular-question', __( 'DWQA Popular Questions', 'dwqa' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		$instance = wp_parse_args( $instance, array( 
			'title' => __( 'Popular Questions', 'dwqa' ),
			'number' => 5,
		) );
		
		echo $before_widget;
		echo $before_title;
		echo $instance['title'];
		echo $after_title;
		
		$args = array(
			'posts_per_page'       => $instance['number'],
			'order'             => 'DESC',
			'orderby'           => 'meta_value_num',
			'meta_key'           => '_dwqa_views',
			'post_type'         => 'dwqa-question',
			'suppress_filters'  => false,
		);
		$questions = new WP_Query( $args );
		if ( $questions->have_posts() ) {
			echo '<div class="dwqa-popular-questions">';
			echo '<ul>';
			while ( $questions->have_posts() ) { $questions->the_post();
				echo '<li><a href="'.get_permalink().'" class="question-title">'.get_the_title().'</a> '.__( 'asked by', 'dwqa' ).' ' . get_the_author_link() . '</li>';
			}   
			echo '</ul>';
			echo '</div>';
		}
		wp_reset_query();
		wp_reset_postdata();
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {

		// update logic goes here
		$updated_instance = $new_instance;
		return $updated_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( $instance, array( 
			'title' => '',
			'number' => 5,
		) );
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Widget title', 'dwqa' ) ?></label>
		<input type="text" name="<?php echo $this->get_field_name( 'title' ) ?>" id="<?php echo $this->get_field_id( 'title' ) ?>" value="<?php echo $instance['title'] ?>" class="widefat">
		</p>
		<p><label for="<?php echo $this->get_field_id( 'number' ) ?>"><?php _e( 'Number of posts', 'dwqa' ) ?></label>
		<input type="text" name="<?php echo $this->get_field_name( 'number' ) ?>" id="<?php echo $this->get_field_id( 'number' ) ?>" value="<?php echo $instance['number'] ?>" class="widefat">
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', "register_widget( 'DWQA_Popular_Question_Widget' );" ) );

?>