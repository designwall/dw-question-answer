<?php  
/**
 *  Template use for display list of question 
 *
 *  @since  DW Question Answer 1.0
 */

get_header('dwqa'); ?>

<?php do_action( 'dwqa_before_page' ) ?>

<?php dwqa_load_template('question', 'list'); ?>

<?php do_action( 'dwqa_after_page' ) ?>

<?php get_footer('dwqa'); ?>