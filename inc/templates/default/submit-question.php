<?php get_header('dwqa'); ?>

<?php do_action( 'dwqa_before_page' ) ?>

    <?php dwqa_submit_question_form(); ?>

<?php do_action( 'dwqa_after_page' ) ?>

<?php get_footer('dwqa'); ?>