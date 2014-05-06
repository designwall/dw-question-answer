<?php  

function dwqa_submit_question_form(){
    _deprecated_function( __FUNCTION__, '1.2.6', 'dwqa_load_template( "submit-question", "form" )' );
    dwqa_load_template( 'submit-question', 'form' );
}

?>