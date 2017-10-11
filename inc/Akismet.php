<?php
if ( !defined( 'ABSPATH' ) ) exit;

class DWQA_Akismet {
	
	private $akismetAPIKey='';
	private $data = array();
	private $port = 443;
	private	$akismet_ua = "DWQA/1.4.7 | Akismet/3.1.7";
	
	public function __construct() {
		global $dwqa_general_settings;
		if(isset($dwqa_general_settings['use-akismet-antispam']) && $dwqa_general_settings['use-akismet-antispam']){
			$this->akismet_ua = "DWQA/".get_option( 'dwqa-db-version', '1.4.7' )." | Akismet/3.1.7";
			add_action('init', array( $this, 'dwqa_admin_show_spam_page'));
			add_action('init', array( $this, 'dwqa_akismet_mark_spam'));
			// add_action( 'admin_menu', array( $this, 'reported_list_admin' ) );
			
			// add_action( 'wp_enqueue_scripts', array($this,'dwqa_akismet_enqueue_script' ),10);
			
			add_action('manage_posts_extra_tablenav', array( $this, 'dwqa_admin_add_button_empty_spam'));
			// add_action( 'wp_ajax_dwqa_report_spam_to_admin', array( $this, 'dwqa_report_spam_to_admin') );
			// add_filter( 'dwqa_question_button_action', array( $this, 'dwqa_add_button_action_report_spam_to_admin'), 10);
			// add_filter( 'dwqa_answer_button_action', array( $this, 'dwqa_add_button_action_report_spam_to_admin'), 10);
			add_filter( 'post_row_actions', array( $this, 'dwqa_admin_add_post_row_button'), 11, 2 );
			
			//setkey
			$this->akismetAPIKey = (isset($dwqa_general_settings['akismet-api-key']) && $dwqa_general_settings['akismet-api-key']!='')?$dwqa_general_settings['akismet-api-key']:'';
			
			// Call to verify key function
			if($this->akismet_verify_key($this->akismetAPIKey)){
				//verified do something

				$this->data = array(
					'blog' => get_option( 'home' ),
					'blog_charset' => get_option( 'blog_charset' ),
					'blog_lang' => get_locale(),
					'user_ip' => $this->get_user_ip(),
					'user_agent' => (isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:''),
					'referrer' => (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:''),
					'permalink' => '',
					'comment_type' => '',
					'comment_author' => '',
					'comment_author_email' => '',
					'comment_author_url' => '',
					'comment_content' => '',
					'comment_approved' => '', //post_status
					'comment_date' => '', //post_date
					'comment_ID' => '', //postID
					'comment_post_ID' => '', //post_parent_ID
					'is_test' => false, 
					);
				
				// Keys to ignore
				$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );
				// Loop through _SERVER args and remove whitelisted keys
				foreach ( $_SERVER as $key => $value ) {

					// Key should not be ignored
					if ( !in_array( $key, $ignore ) && is_string( $value ) ) {
						$this->data[$key] = $value;

					// Key should be ignored
					} else {
						$this->data[$key] = '';
					}
				}

				add_filter( 'dwqa_insert_question_args', array( $this, 'dwqa_check_spam' ) , 10, 1 );
				add_filter( 'dwqa_insert_answer_args', array( $this, 'dwqa_check_spam' ) , 10, 1 );
				add_filter( 'dwqa_insert_comment_args', array( $this, 'dwqa_check_spam' ) , 10, 1 );
				// add_action( 'dwqa_after_mark_unspam', array( $this, 'dwqa_check_spam' ) , 10, 1 );
			}
		}
	}
	
	// Create reported list admin
	public function reported_list_admin(){
		$dwqa_reported_page = add_submenu_page( 'edit.php?post_type=dwqa-question', __( 'Report Spam List','dwqa' ), __( 'Report Spam','dwqa' ), 'manage_options', 'dwqa-report-spam-list', array( $this, 'reported_list_admin_display' )  );
	}
	public function reported_list_admin_display(){
		require_once DWQA_DIR . 'inc/class/class-display-reported-list-table.php';
		$reportedTable = new Reported_List_Table();
		$reportedTable->process_bulk_action();
		
		echo '<div class="wrap"><h1>Reported Spam List</h1>';
		$columns = array(
				'id'	=> 'id',
				'title' => __( 'Title', 'dwqa' ),
				'type'    => __( 'Type', 'dwqa' ),
				'author'    => __( 'Author', 'dwqa' ),
				'countreport'	=>__( 'Count Report', 'dwqa' )
			  );
			  
		$hiddens = array(
			'id'
		);
		$sortable = array(
			'id'	=> array('id',false),
			'title' => array('title',false),
			'type'    => array('type',false),
			'author'    => array('author',false),
			'countreport' => array('countreport',false)
		  );
		$reportedTable->edit_columns($columns);
		$reportedTable->edit_hiddens($hiddens);
		$reportedTable->edit_sortable($sortable);
		$reportedTable->edit_perpage(11);
		
		$query = get_posts( array(
			'post_type' => array('dwqa-answer','dwqa-question'),
			'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'),
			'meta_query' => array(
				array(
					'key'	=> '_dwqa_spam_reported',
					'value' => '',
					'compare' =>'!='
				),
			)
		) );
		// echo '<pre>';
		// print_r($query);
		// echo '</pre>';
		$_data = $this->setup_data_list($query);
		$reportedTable->prepare_items($_data);
	
		echo '<form method="post">';
		$reportedTable->display(); 
		echo '</form>'; 
		echo '</div>'; 
	}
	private function setup_data_list($report_list){
		
		$data_report = array();
		foreach ($report_list as $item) {
			$id = $item->ID;
			$id_link = admin_url().'post.php?post='.$id.'&action=edit';
			$title = '<a href="'.$id_link.'"><strong>'.$item->post_title.'</strong></a>';
			
			$author_id = $item->post_author;
			$author_info = get_user_by('id',$author_id);
			$author = '<a href="'.get_edit_user_link($author_id).'">'.$author_info->display_name.'</a>';
			$rp_list = get_post_meta($id, '_dwqa_spam_reported',true);
			if($rp_list!='' && $rp_list){
				$countreport = count(unserialize($rp_list));
			}else{
				$countreport = 0;
			}
			
			/*action*/
			$actions = array(
					'nospam'      => sprintf(
										'<span class="nospam"><a href="%s" rel="bookmark" aria-label="%s">%s</a></span>',
										wp_nonce_url( admin_url("edit.php?post_type=".$item->post_type."&action=unspam&post=".$item->ID), "nospam-post_{$item->ID}" ),
										esc_attr( sprintf( __( 'Unspam &#8220;%s&#8221;' ), $item->post_title ) ),
										__( 'publish' )
									),
					'view'      => sprintf(
										'<span class="view"><a href="%s" rel="bookmark" aria-label="%s">%s</a><span>',
										admin_url().'post.php?post='.$id.'&action=edit',
										esc_attr( sprintf( __( 'View %s' ), $item->post_title ) ),
										__( 'View','dwqa' )
									),
					'delete'    => sprintf( 
										'<span class="delete"><a href="%s">%s</a><span>',
										get_delete_post_link( $id , '', true), __( 'Delete permanently', 'dwqa' )
									)
				);
			$action='<div class="row-actions">';
			$action .= implode(' | ', $actions);
			$action .= '</div>';
			$temp = array(
				'id' => $id,
				'title' => $title.$action,
				'type'=> $type,
				'author'=> $author,
				'countreport' => $countreport
			);
			
			array_push($data_report,$temp);
		}
		return $data_report;
	}
	
	private function dwqa_prepare_data($data){
		$data_check_spam = $this->data;
		
		$post_permalink = '';
		if ( !empty( $data['post_parent'] ) ) {
			$post_permalink = get_permalink( $data['post_parent'] );
		}
		$data_check_spam['permalink'] = $post_permalink;
		
		if ( empty( $data['post_author'] ) ) {
			$data['post_author'] = 0;
		}
		$userdata = get_userdata( $data['post_author'] );
		
		if ( !empty( $userdata ) ) {
			$user_data['name'] = $userdata->display_name;
			$user_data['email'] = $userdata->user_email;
			$user_data['website'] = $userdata->user_url;
		} else if ( isset( $data['is_anonymous'] ) ) {
			$user_data['name'] = isset( $data['dwqa_anonymous_name'] ) ? $data['dwqa_anonymous_name'] : __( 'Anonymous', 'dwqa' );
			$user_data['email'] = isset( $data['dwqa_anonymous_email'] ) ? $data['dwqa_anonymous_email'] : '';
			$user_data['website'] = '';
		} else {
			$user_data['name'] = '';
			$user_data['email'] = '';
			$user_data['website'] = '';
		}
		
		$data_check_spam['comment_author'] = $user_data['name'];
		// $data_check_spam['comment_author'] = 'viagra-test-123'; // for test
		// $data_check_spam['is_test'] = true; // for test
		$data_check_spam['comment_author_email'] = $user_data['email'];
		$data_check_spam['comment_author_url'] = $user_data['website'];
		
		$data_check_spam['comment_content'] = isset($data['post_content'])?$data['post_content']:'';
		$data_check_spam['comment_type'] = isset($data['post_type'])?$data['post_type']:'';
		$data_check_spam['comment_approved'] = isset($data['post_status'])?$data['post_status']:'';
		$data_check_spam['comment_date'] = isset($data['post_date'])?$data['post_date']:'';
		$data_check_spam['comment_date_gmt'] = isset($data['post_date_gmt'])?$data['post_date_gmt']:'';
		$data_check_spam['comment_ID'] = isset($data['ID'])?$data['ID']:'';
		$data_check_spam['comment_post_ID'] = isset($data['post_parent'])?$data['post_parent']:'';
		
		
		return $data_check_spam;
	}
	
	public function dwqa_check_spam($data){
		if($this->akismet_comment_check($this->akismetAPIKey, $this->dwqa_prepare_data($data))){
			//is spam mark status spam
			$data['post_status'] = 'spam';
		}
		return $data;
	}
	
	
	// Display User IP in WordPress
	private function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return apply_filters( 'dwqa_get_ip', $ip );
	}
	
	// Authenticates your Akismet API key
	public static function akismet_verify_key( $key, $blog = '', $port = 443, $akismet_ua = "WordPress/4.4.1 | Akismet/3.1.7" ) {
		if($blog==''){
			$blog = urlencode(get_home_url());
		}else{
			$blog = urlencode($blog);
		}
		
		$request = 'key='. $key .'&blog='. $blog;
		$host = $http_host = 'rest.akismet.com';
		$path = '/1.1/verify-key';
		// $port = $this->port;
		// $akismet_ua = $this->akismet_ua;
		$content_length = strlen( $request );
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$http_request .= "Content-Length: {$content_length}\r\n";
		$http_request .= "User-Agent: {$akismet_ua}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';
		if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {
			 
			fwrite( $fs, $http_request );
		 
			while ( !feof( $fs ) )
				$response .= fgets( $fs, 1160 ); // One TCP-IP packet
			fclose( $fs );
			 
			$response = explode( "\r\n\r\n", $response, 2 );
		}
		 
		if ( 'valid' == $response[1] ){
			return true;
		}else{
			return false;
		}	
	}
	
	
	
	// Passes back true (it's spam) or false (it's ham)
	public function akismet_comment_check( $key, $data ) {

		$request = '';

		foreach($data as $kData => $vData){
			if($request == ''){
				$request .= $kData.'='.urlencode($vData);
			}else{
				$request .= '&'.$kData.'='.urlencode($vData);
			}
		}

		$host = $http_host = $key.'.rest.akismet.com';
		$path = '/1.1/comment-check';
		$port = $this->port;
		$akismet_ua = $this->akismet_ua;
		$content_length = strlen( $request );
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$http_request .= "Content-Length: {$content_length}\r\n";
		$http_request .= "User-Agent: {$akismet_ua}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';
		if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {
			 
			fwrite( $fs, $http_request );
		 
			while ( !feof( $fs ) )
				$response .= fgets( $fs, 1160 ); // One TCP-IP packet
			fclose( $fs );
			 
			$response = explode( "\r\n\r\n", $response, 2 );
		}
		 
		if ( 'true' == $response[1] )
			return true;
		else
			return false;
	}
	
	
	// Passes back true (it's spam) or false (it's ham)
	public function akismet_submit_spam( $key, $data ) {
		$new_data = $this->dwqa_prepare_data($data);
		$request = '';

		foreach($new_data as $kData => $vData){
			if($request == ''){
				$request .= $kData.'='.urlencode($vData);
			}else{
				$request .= '&'.$kData.'='.urlencode($vData);
			}
		}
		$host = $http_host = $key.'.rest.akismet.com';
		$path = '/1.1/submit-spam';
		$port = $this->port;
		$akismet_ua = $this->akismet_ua;
		$content_length = strlen( $request );
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$http_request .= "Content-Length: {$content_length}\r\n";
		$http_request .= "User-Agent: {$akismet_ua}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';
		if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {
			 
			fwrite( $fs, $http_request );
		 
			while ( !feof( $fs ) )
				$response .= fgets( $fs, 1160 ); // One TCP-IP packet
			fclose( $fs );
			 
			$response = explode( "\r\n\r\n", $response, 2 );
		}
		 
		if ( 'Thanks for making the web a better place.' == $response[1] )
			return true;
		else
			return false;
	}
	

	// Passes back true (it's spam) or false (it's ham)
	public function akismet_submit_ham( $key, $data ) {
		$new_data = $this->dwqa_prepare_data($data);
		$request = '';

			foreach($new_data as $kData => $vData){
				if($request == ''){
					$request .= $kData.'='.urlencode($vData);
				}else{
					$request .= '&'.$kData.'='.urlencode($vData);
				}
			}
		$host = $http_host = $key.'.rest.akismet.com';
		$path = '/1.1/submit-ham';
		$port = $this->port;
		$akismet_ua = $this->akismet_ua;
		$content_length = strlen( $request );
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$http_request .= "Content-Length: {$content_length}\r\n";
		$http_request .= "User-Agent: {$akismet_ua}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';
		if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {
			 
			fwrite( $fs, $http_request );
		 
			while ( !feof( $fs ) )
				$response .= fgets( $fs, 1160 ); // One TCP-IP packet
			fclose( $fs );
			 
			$response = explode( "\r\n\r\n", $response, 2 );
		}
		 
		if ( 'Thanks for making the web a better place.' == $response[1] )
			return true;
		else
			return false;
	}
	
	public function dwqa_admin_show_spam_page(){
		register_post_status( 'spam', array(
			'label'                     => _x( 'Spam', 'dwqa' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Spam <span class="count">(%s)</span>', 'Spam <span class="count">(%s)</span>' ),
		) );
	}
	public function dwqa_admin_add_button_empty_spam(){
		if ( isset( $_GET['post_status'] ) &&  sanitize_text_field( $_GET['post_status'] ) == 'spam') {
			echo '<div class="alignleft actions">';
			submit_button( __( 'Empty Spam' ), 'apply', 'delete_all', false );
			echo '</div>';
		}
	}
	public function dwqa_admin_add_post_row_button($actions, $post){

		if($post->post_type=="dwqa-question" || $post->post_type=="dwqa-answer"){
			if($post->post_status!="spam"){
				$actions['spam'] = sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					wp_nonce_url( admin_url("edit.php?post_type=".$post->post_type."&action=spam&post=".$post->ID), "spam-post_{$post->ID}" ),
					esc_attr( sprintf( __( 'Spam &#8220;%s&#8221;' ), $post->post_title ) ),
					__( 'Spam' )
				);
			}else{
				$actions['unspam'] = sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					wp_nonce_url( admin_url("edit.php?post_type=".$post->post_type."&action=unspam&post=".$post->ID), "unspam-post_{$post->ID}" ),
					esc_attr( sprintf( __( 'Unspam &#8220;%s&#8221;' ), $post->post_title ) ),
					__( 'publish' )
				);
			}
				
		}
		return $actions;
	}

	public function dwqa_akismet_mark_spam(){
		if(isset($_GET['post_type']) && ($_GET['post_type']=='dwqa-question' || $_GET['post_type']=='dwqa-answer')){
			if(isset($_GET['post']) && $_GET['post'] && is_numeric($_GET['post'])){
				if (isset($_GET['action']) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], "{$_GET['action']}-post_{$_GET['post']}" ) ) {
					 die( 'Security check' ); 
				}
				
				if($_GET['action']=='spam'){
					if ( !current_user_can( 'delete_post', $_GET['post'] ) )
						die( 'Security check' ); 
					
					$args = array(
						  'ID'           => $_GET['post'],
						  'post_status'   => 'spam'
					  );
					wp_update_post( $args );
					if(!$this->akismet_submit_spam($this->akismetAPIKey, get_post($_GET['post']))){
						//is spam
					}
					// do_action("dwqa_after_mark_spam");
					wp_redirect(admin_url( 'edit.php?post_type='.$_GET['post_type']));
					exit();
				}
				if($_GET['action']=='unspam'){
					if($_GET['post_type']=='dwqa-question'){
						$args = array(
						  'ID'           => $_GET['post'],
						  'post_status'   => 'publish'
						);
					}else{
						$args = array(
						  'ID'           => $_GET['post'],
						  'post_status'   => 'inherit'
						);
					}
					
					wp_update_post( $args );
					if(!$this->akismet_submit_ham($this->akismetAPIKey, get_post($_GET['post']))){
						//is spam
					}
					// do_action("dwqa_after_mark_unspam");
					wp_redirect(admin_url( 'edit.php?post_type='.$_GET['post_type']));
					exit();
				}
				
			}
		}
		return;
	}
	
	public function dwqa_report_spam_to_admin(){
		$user_id = get_current_user_id();
		if(!$user_id>0 || !is_numeric($user_id)){
			wp_send_json_error( array( 'message' => __( 'You need login to report spam!', 'dwqa' ) ) );
		}
		if ( ! isset( $_POST['post_id'] ) || !is_numeric($_POST['post_id']) ) {
			wp_send_json_error( array( 'message' => __( 'Post not found!', 'dwqa' ) ) );
		}
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), '_dwqa_action_report_spam_to_admin' ) ) {
			wp_send_json_error( array( 'message' => __( 'Are you cheating huh?', 'dwqa' ) ) );
		}
		$post_id = $_POST['post_id'];
		$key = '_dwqa_spam_reported';
		$args = get_post_meta($post_id , $key, true);
		if($args=='' || !$args){
			$args[] = $user_id;
		}else{
			$args = unserialize($args);
			if(!in_array($user_id,$args)){
				$args[] = $user_id;
			}else{
				wp_send_json_error( array( 'message' => __( 'You reported this post before!', 'dwqa' ) ) );
			}
		}
		update_post_meta($post_id, $key, serialize($args));
		// if(empty)
		wp_send_json_success( array( 'message' => __( 'Reported to admin', 'dwqa' ) ) );
	}
	
	public function dwqa_add_button_action_report_spam_to_admin($html){
		if ( is_user_logged_in() ) {
			$action_url = add_query_arg( array( 'action' => 'dwqa_delete_answer', 'answer_id' => get_the_ID() ), admin_url( 'admin-ajax.php' ) );
			$html .= '<a class="dwqa_report_spam" data-nonce="'.wp_create_nonce( '_dwqa_action_report_spam_to_admin' ).'" data-post="'. get_the_ID() .'">' . __( 'Report Spam', 'dwqa' ) . '</a> ';
		}
		return $html;
		
	}
	public function dwqa_akismet_enqueue_script() {
		wp_enqueue_script( 'dwqa-akismet-button-report-spam-script', DWQA_URI.'assets/js/dwqa-akismet-button-report-spam.js', false );
		wp_enqueue_style( 'dwqa-akismet-button-report-spam-style', DWQA_URI.'assets/css/dwqa-akismet-button-report-spam.css', false );
	}
}
?>