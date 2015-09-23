<?php  

class DWQA_Rewrite {
	public function __construct() {
		add_action( 'after_switch_theme', 'flush_rewrite_rules' );
	}

}
?>