<?php  
global $dwqa_db_version;


function dwqa_table_exists( $name ) {
	global $wpdb;
	$check = wp_cache_get( 'table_exists_' . $name );
	if ( ! $check ) {
		$check = $wpdb->get_var( 'SHOW TABLES LIKE "'. $name .'"' );
		wp_cache_set( 'table_exists_', $check );
	}

	if ( $check == $name ) {
		return true;
	}
	return false;
}


class DWQA_Database_Upgrade {
	public $db_version = '1.3.3';
	public $table = 'dwqa_question_index';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );

		// Replace old data by new table
		if ( dwqa_table_exists( $this->table ) ) {
			remove_filter( 'dwqa-prepare-archive-posts', 'dwqa_prepare_archive_posts' );
			add_action( 'dwqa-prepare-archive-posts', array( $this, 'prepare_archive_posts'), 99 );
		}

		add_action( 'wp_ajax_dwqa_upgrade_database', array( $this, 'create_table' ) );
		if ( $this->db_version != get_option( 'dwqa_db_version' ) ) {
			update_option( 'dwqa_db_version', $this->db_version );
		}
	}

	/**
	 * Create Index Table
	 */
	public function create_table(){
		global $wpdb;
		$offset = isset( $_GET['offset'] ) ? intval( $_GET['offset'] ) : 0;
		$posts_per_round = 100;

		$dwqa_table = 'dwqa_question_index';

		$questions_count = wp_count_posts( 'dwqa-question' );
		$total = $questions_count->publish + $questions_count->private;
		$start = microtime(true);

		$query_create_table = "CREATE TABLE IF NOT EXISTS {$dwqa_table} (
			`ID` bigint(20) unsigned NOT NULL,
			`post_author` bigint(20) NOT NULL DEFAULT 0,
			`post_date` datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
			`post_date_gmt` datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
			`post_content` longtext NOT NULL DEFAULT '',
			`post_title` text NOT NULL DEFAULT '',
			`post_excerpt` text NOT NULL DEFAULT '',
			`post_status` varchar(20) NOT NULL DEFAULT 'publish',
			`comment_status` varchar(20) NOT NULL DEFAULT 'open',
			`ping_status` varchar(20) NOT NULL DEFAULT 'open',
			`post_password` varchar(20) NOT NULL,
			`post_name` varchar(200) NOT NULL,
			`to_ping` text NOT NULL DEFAULT '',
			`pinged` text NOT NULL DEFAULT '',
			`post_modified` datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
			`post_modified_gmt` datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
			`post_content_filtered` longtext NOT NULL DEFAULT '',
			`post_parent` bigint(20) NOT NULL DEFAULT 0,
			`guid` varchar(255) NOT NULL,
			`menu_order` int(11)  NOT NULL DEFAULT 0,
			`post_type` varchar(20) NOT NULL DEFAULT 'dwqa-question',
			`post_mime_type` varchar(100) NOT NULL,
			`comment_count` bigint(20)  NOT NULL DEFAULT 0,
			`last_activity_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			`last_activity_author` bigint(20) unsigned NOT NULL DEFAULT '0',
			`last_activity_type` varchar(255) NOT NULL DEFAULT 'create',
			`last_activity_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			`answers` varchar(255),
			`answer_count` bigint(20) NOT NULL DEFAULT '0',
			`publish_answer_count` bigint(20) NOT NULL DEFAULT '0',
			`private_answer_count` bigint(20) NOT NULL DEFAULT '0',
			`view_count` bigint(20) NOT NULL DEFAULT '0',
			`vote_count` bigint(20) NOT NULL DEFAULT '0',
			PRIMARY KEY (`ID`)
		);";
		$table = $wpdb->query( $query_create_table );

		if ( $table ) {
			if ( $offset == 0 ) {
				$clear_table = "DELETE FROM {$dwqa_table}";
				$wpdb->query( $clear_table );
			}
			$query_questions_table = "FROM {$wpdb->posts} WHERE post_type = 'dwqa-question' AND post_status IN ( 'publish', 'private' ) ORDER BY post_date DESC LIMIT {$offset},{$posts_per_round}";
			$query_questions = "SELECT ID " . $query_questions_table;

			$import_ids = $wpdb->get_results( "SELECT ID {$query_questions_table}" );
			$posts__in = array();
			foreach ( $import_ids as $id ) {
				$posts__in[] = $id->ID;
			}
			$posts__in = implode(',', $posts__in );

			// Insert Question ID, title
			$query_insert_questions = "INSERT INTO {$dwqa_table} ( ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count ) SELECT * " . $query_questions_table;

			$wpdb->query( $query_insert_questions );

			// Update View Count
			$query_view_count = "UPDATE {$dwqa_table} as new_table 
									JOIN ( SELECT `insert_questions`.ID, `meta`.meta_value 
											FROM {$wpdb->postmeta} as meta 
											JOIN ( {$query_questions} ) as insert_questions 
											ON `meta`.post_id = `insert_questions`.ID 
											WHERE meta_key = '_dwqa_views' 
									) AS view 
										ON `new_table`.ID = `view`.ID 
								SET `new_table`.view_count = `view`.meta_value";
			$wpdb->query( $query_view_count );

			// Update Vote
			$query_vote_count = "UPDATE {$dwqa_table} as new_table 
								JOIN ( SELECT ID, `meta`.meta_value 
										FROM {$wpdb->postmeta} as meta 
										JOIN ( {$query_questions} ) as insert_questions 
											ON `meta`.post_id = `insert_questions`.ID  
										WHERE `meta`.meta_key = '_dwqa_votes' 
								) as vote 
									ON `new_table`.ID = `vote`.ID
							SET `new_table`.vote_count = `vote`.meta_value";
			$wpdb->query( $query_vote_count );

			// Publish Answer count
			$query_answer_count ="UPDATE {$dwqa_table} as new_table 
									JOIN ( SELECT 
											`Q`.ID as question, 
											IF( ISNULL( `A`.post_date ), `Q`.post_date, 
											max( `A`.post_date ) ) as post_modified, 
											IF( ISNULL( `A`.ID ), 'create', 'answer' ) as last_activity_type,
											GROUP_CONCAT(DISTINCT `A`.ID SEPARATOR ',') AS answers, 
											count(distinct `A`.ID) as total, 
											count(distinct (case when `A`.post_status = 'publish' then `A`.ID end)) as publish_count, 
											count(distinct (case when `A`.post_status = 'private' then `A`.ID end)) as private_count 
										FROM ( SELECT * FROM {$dwqa_table} WHERE ID IN ( {$posts__in} ) ) AS Q 
										LEFT JOIN {$wpdb->postmeta} AS N 
											ON `Q`.ID = `N`.meta_value AND `N`.meta_key = '_question' 
										LEFT JOIN {$wpdb->posts} AS A 
											ON `N`.post_id = `A`.ID AND `A`.post_type = 'dwqa-answer' AND `A`.post_status IN ( 'publish', 'private' ) 
										WHERE `Q`.post_status IN ( 'publish', 'private' ) AND `Q`.post_type = 'dwqa-question' GROUP BY `Q`.ID 
									) as count_table ON `count_table`.question = `new_table`.ID 
								SET `new_table`.private_answer_count = `count_table`.private_count, `new_table`.publish_answer_count = `count_table`.publish_count, `new_table`.answer_count = `count_table`.total,
									`new_table`.answers = `count_table`.answers, `new_table`.last_activity_date = `count_table`.post_modified, `new_table`.last_activity_type = `count_table`.last_activity_type";
			$wpdb->query( $query_answer_count );

			// get last activity author and id
			$query_update_last_activity = "UPDATE {$dwqa_table} as new_table 
											JOIN ( 
												SELECT `question`.ID, 
													max( if(`question`.last_activity_date = `answer`.post_date, `answer`.post_author, `question`.post_author)) as last_activity_author,
													max(if( `question`.last_activity_date = `answer`.post_date, `answer`.ID,`question`.ID)) last_activity_id  
												FROM ( SELECT * FROM {$dwqa_table} WHERE ID IN ( {$posts__in} ) ) question 
													JOIN {$wpdb->postmeta} meta on `meta`.meta_value = `question`.ID and `meta`.meta_key = '_question' 
													JOIN {$wpdb->posts} answer on `meta`.post_id = `answer`.ID AND `answer`.post_type = 'dwqa-answer' AND `answer`.post_date >= `question`.last_activity_date
												GROUP BY `question`.ID
											) as last_activity ON `new_table`.ID = `last_activity`.ID
											SET `new_table`.last_activity_author = `last_activity`.last_activity_author, `new_table`.last_activity_id = `last_activity`.last_activity_id
										";
			$wpdb->query( $query_update_last_activity );

			// Update Status : 
			// IF Have lastest action is admin answer -> answered, while open 
			// If have meta resolved or closed -> answered / closed
			$query_column_exists = "SHOW COLUMNS FROM {$dwqa_table} LIKE 'question_status'";
			if( ! $wpdb->query( $query_column_exists ) ) {
				$wpdb->query( "ALTER TABLE {$dwqa_table} ADD COLUMN question_status varchar(20) NOT NULL DEFAULT 'open' AFTER post_status" );
			} 

			$query_status = "UPDATE {$dwqa_table} as new_table 
								JOIN ( 
										SELECT `question`.ID, IF( `meta`.meta_value = 'resolved' OR `meta`.meta_value = 'closed', `meta`.meta_value, IF( `usermeta`.meta_value LIKE '%editor%' OR `usermeta`.meta_value LIKE '%administrator%', 'answered', 'open' )  ) as status
										FROM ( 
											SELECT ID, last_activity_author, last_activity_type 
											FROM {$dwqa_table} 
											WHERE ID IN ( {$posts__in} ) 
										) question
										LEFT JOIN {$wpdb->usermeta} usermeta ON `question`.last_activity_author = `usermeta`.user_id AND `usermeta`.meta_key = '{$wpdb->prefix}capabilities'
										JOIN {$wpdb->postmeta} as meta
											ON `meta`.post_id = `question`.ID AND `meta`.meta_key = '_dwqa_status'
								) as status 
									ON `new_table`.ID = `status`.ID
							SET `new_table`.question_status = `status`.status";
			$wpdb->query( $query_status );
		}

		//Executime for single loop
		$time_elapsed_us = microtime(true) - $start; 
		$offset += $posts_per_round;
		if ( $offset > $total ) {
			wp_send_json_error( array( 'message' => 'done' ) );
		}
		wp_send_json_success( array(
			'time' => $time_elapsed_us,
			'next_offset' => $offset
		) );
		
	}

	public function add_menu() {
		add_submenu_page( 'edit.php?post_type=dwqa-question', __( 'DWQA ReIndex', 'dwqa' ), __( 'DWQA ReIndex', 'dwqa' ), 'manage_options', 'dwqa-question', array( $this, 'display' ) );
	}

	public function display() {
		$posts_per_round = 20;
		$questions_count = wp_count_posts( 'dwqa-question' );
		$total = $questions_count->publish + $questions_count->private;
		$answers_count = wp_count_posts( 'dwqa-answer' );
		?>
		<div class="wrap">
			<h2><?php _e( 'Questions Index', 'dwqa' ); ?></h2>
			<form action="" method="post">
				<p><?php printf( __( 'Total questions: %d ( %d publish - %d private )', 'dwqa' ), $total, $questions_count->publish, $questions_count->private ) ?></p>
				<p><?php printf('Total answers: %d ( %d publish - %d private ) ', $answers_count->publish + $answers_count->private, $answers_count->publish, $answers_count->private ) ?></p>
				
				<p><progress id="dwqa-upgrade-database-progress" max="<?php echo $total; ?>" value="80">80/<?php echo $total; ?></progress></p>
				<div><input id="dwqa-upgrade-database" type="button" class="btn btn-primary" value="Index Questions"></div>
			</form>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($) {

			var run_upgrade = function( offset ) {
				$.ajax({
					url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
					type: 'GET',
					dataType: 'json',
					data: {
						action: 'dwqa_upgrade_database',
						offset: offset
					},
				})
				.done(function( resp ) {
					if ( resp.success ) {
						console.log( resp.data.next_offset );
						run_upgrade( resp.data.next_offset );
						$('#dwqa-upgrade-database-progress').attr('value', resp.data.next_offset );
					} else {
						console.log( resp.data.message );
						$('#dwqa-upgrade-database').removeAttr('disabled');
					}
					//resend query with new offset
				});
			}

			$('#dwqa-upgrade-database').on( 'click', function(e){
				e.preventDefault();
				$(this).attr('disabled', 'disabled');
				run_upgrade(0);
			});
		});
		</script>
		<?php
	}

	public function prepare_archive_posts() {
		global $wpdb, $wp_query,$dwqa_general_settings;
		
		$query['posts_per_page'] = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : 5;
		$paged = get_query_var( 'paged' );
		$query['offset'] = $paged ? ( $paged - 1 ) * $query['posts_per_page'] : 0;

		// if ( is_tax( 'dwqa-question_category' ) ) {
		// 	$query['dwqa-question_category'] = get_query_var( 'dwqa-question_category' );
		// } 
		// if ( is_tax( 'dwqa-question_tag' ) ) {
		// 	$query['dwqa-question_tag'] = get_query_var( 'dwqa-question_tag' );
		// } 
		 
		$sticky_questions = get_option( 'dwqa_sticky_questions' );
		if ( is_array( $sticky_questions ) ) {
			$sticky_questions = implode(',', $sticky_questions );
		}
		if ( is_user_logged_in() ) {
			$query['post_status'] = "'publish', 'private', 'pending'";
		} else {
			$query['post_status'] = "'publish'";
		}
		
		$questions = $wpdb->get_results( "SELECT * FROM dwqa_question_index WHERE 1=1 AND post_status IN ( ".$query['post_status']." ) AND ID NOT IN ( {$sticky_questions} ) ORDER BY last_activity_date DESC LIMIT ".$query['offset'].", ".$query['posts_per_page'] );
		
		$wp_query->posts = $questions;
		$wp_query->post_count = count( $questions );
	}
}
$GLOBALS['dwqa_database_upgrade'] = new DWQA_Database_Upgrade();

?>