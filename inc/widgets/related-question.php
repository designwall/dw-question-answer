<?php  

class dwqa_Related_Question_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    function dwqa_Related_Question_Widget() {
        $widget_ops = array( 'classname' => 'dwqa-widget dwqa-related-questions', 'description' => __('Show a list of questions that related to a question. Just show in single question page','dwqa') );
        $this->WP_Widget( 'dwqa-related-question', __('DWQA Related Questions','dwqa'), $widget_ops );
    }

    function widget( $args, $instance ) {
        extract( $args, EXTR_SKIP );
        $instance = wp_parse_args( $instance, array( 
        	'title'	=> '',
        	'number' => 5
        ) );
        $post_type = get_post_type();
        if( is_single() && ( $post_type == 'dwqa-question' || $post_type == 'dwqa-answer' ) ) {

	        echo $before_widget;
	        echo $before_title;
	        echo $instance['title'];
	        echo $after_title;
	        echo '<div class="related-questions">';
	   		dwqa_related_question( false, $instance['number'] );
	   		echo '</div>';
    		echo $after_widget;
        }
    }

    function update( $new_instance, $old_instance ) {

        // update logic goes here
        $updated_instance = $new_instance;
        return $updated_instance;
    }

    function form( $instance ) {
        $instance = wp_parse_args( $instance, array( 
        	'title'	=> '',
        	'number' => 5
        ) );
        ?>
        <p><label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Widget title') ?></label>
        <input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?>" value="<?php echo $instance['title'] ?>" class="widefat">
        </p>
        <p><label for="<?php echo $this->get_field_id('number') ?>"><?php _e('Number of posts') ?></label>
        <input type="text" name="<?php echo $this->get_field_name('number') ?>" id="<?php echo $this->get_field_id('number') ?>" value="<?php echo $instance['number'] ?>" class="widefat">
        </p>
        <?php
    }
}
add_action( 'widgets_init', create_function( '', "register_widget( 'dwqa_Related_Question_Widget' );" ) );

?>