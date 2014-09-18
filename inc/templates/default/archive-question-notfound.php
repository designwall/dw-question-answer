<?php 

if ( ! dwqa_current_user_can( 'read_question' ) ) : 
	?>
	<div class="alert"><?php _e( 'You do not have permission to view questions', 'dwqa' ) ?></div>
	<?php 
endif; 

?>
<p class="not-found">
	
<?php 

_e( 'Sorry, but nothing matched your filter.', 'dwqa' ); 

if ( is_user_logged_in() ) : 
	dwqa_get_ask_question_link();
else : 

	$register_link = wp_register( '', '', false );
	printf('%s <a href="%s">%s</a> %s %s',
		__( 'Please', 'dwqa' ),
		wp_login_url( get_post_type_archive_link( 'dwqa-question' ) ),
		__( 'Login', 'dwqa' ),
		( ! empty( $register_link ) && $register_link  ) ? __( ' or', 'dwqa' ).' '.$register_link : '',
		__( ' to submit question.', 'dwqa' )
	);
	wp_login_form();
endif; ?>

</p>