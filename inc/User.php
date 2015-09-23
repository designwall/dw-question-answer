<?php  

function dwqa_get_following_user( $question_id = false ) {
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	$followers = get_post_meta( $question_id, '_dwqa_followers' );
	
	if ( empty( $followers ) ) {
		return false;
	}
	
	return $followers;
}

class DWQA_User { 
	public function __construct() {
		// Do something about user roles, permission login, profile setting
	}

}
?>