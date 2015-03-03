<?php  
global $dwqa_db_version;

function dwqa_get_question_field( $field, $question_id = false ) {
	global $dwqa_database_upgrade;
	if ( ! $question_id ) {
		$question_id = get_the_ID();
	}
	return $dwqa_database_upgrade->get_question_field( $field, $question_id );
}

class DWQA_Database_Upgrade {
	public $db_version = '1.3.3';
	public $table = 'dwqa_question_index';
	public $temp = false;

	public function __construct() {
		global $wpdb;
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		$this->table = $wpdb->prefix . $this->table;

		add_action( 'wp_ajax_dwqa_upgrade_database', array( $this, 'create_table' ) );
		if ( $this->db_version != get_option( 'dwqa_db_version' ) ) {
			update_option( 'dwqa_db_version', $this->db_version );
		}
		
		// Replace old data by new table
		if ( dwqa_table_exists( $this->table ) ) {
			remove_filter( 'dwqa-prepare-archive-posts', 'dwqa_prepare_archive_posts' );
			remove_filter( 'dwqa-after-archive-posts', 'dwqa-after-archive-posts' );
			add_action( 'dwqa-prepare-archive-posts', array( $this, 'prepare_archive_posts') );
			add_action( 'dwqa-after-archive-posts', array( $this, 'after_archive_posts' ) );

			//Filter update table
			add_action( 'save_post', array( $this, 'update_question' ) );
			add_action( 'before_delete_post', array( $this, 'delete_question') );
			add_action( 'before_delete_post', array( $this, 'delete_answer') );
			add_action( 'dwqa_add_answer', array( $this, 'answers_change' ) );
			add_action( 'dwqa_update_answer', array( $this, 'answers_change') );
			add_action( 'update_postmeta', array( $this, 'update_question_metadata' ), 10, 4  );
		}

	}

	/**
	 * Create Index Table
	 */
	public function create_table(){
		global $wpdb;
		$offset = isset( $_GET['offset'] ) ? intval( $_GET['offset'] ) : 0;
		$posts_per_round = 100;
		$dwqa_table = $this->table; 

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
										LEFT JOIN {$wpdb->postmeta} as meta
											ON `meta`.post_id = `question`.ID AND `meta`.meta_key = '_dwqa_status'
								) as status 
									ON `new_table`.ID = `status`.ID
							SET `new_table`.question_status = `status`.status";
			$wpdb->query( $query_status );

			// Update Question Category ( category id) : 
			$query_column_exists = "SHOW COLUMNS FROM {$dwqa_table} LIKE 'question_categories'";
			if( ! $wpdb->query( $query_column_exists ) ) {
				$wpdb->query( "ALTER TABLE {$dwqa_table} ADD COLUMN question_categories varchar(255) NOT NULL DEFAULT '' AFTER last_activity_id" );
			} 

			$query_cat = "UPDATE {$this->table} new_table 
						JOIN ( 
							SELECT 
								`TR`.object_id ID, 
								GROUP_CONCAT( `T`.term_id SEPARATOR ',' ) question_categories 
							FROM `{$wpdb->term_taxonomy}` T 
								JOIN `{$wpdb->term_relationships}` TR 
									ON `T`.term_taxonomy_id = `TR`.term_taxonomy_id 
							WHERE `T`.taxonomy = 'dwqa-question_category'
								AND `TR`.object_id IN ( {$posts__in} )
							GROUP BY `TR`.object_id 
						) as cat ON `new_table`.ID = `cat`.ID
						SET `new_table`.question_categories = `cat`.question_categories
						WHERE `new_table`.ID IN ( {$posts__in} )
						";
			$wpdb->query( $query_cat );


			// Update Question Tags ( tag id ) : 
			$query_column_exists = "SHOW COLUMNS FROM {$dwqa_table} LIKE 'question_tags'";
			if( ! $wpdb->query( $query_column_exists ) ) {
				$wpdb->query( "ALTER TABLE {$dwqa_table} ADD COLUMN question_tags varchar(255) NOT NULL DEFAULT '' AFTER question_categories" );
			} 

			$query_cat = "UPDATE {$this->table} new_table 
						JOIN ( 
							SELECT 
								`TR`.object_id ID, 
								GROUP_CONCAT( `T`.term_id SEPARATOR ',' ) question_tags 
							FROM `{$wpdb->term_taxonomy}` T 
								JOIN `{$wpdb->term_relationships}` TR 
									ON `T`.term_taxonomy_id = `TR`.term_taxonomy_id 
							WHERE `T`.taxonomy = 'dwqa-question_tag'
								AND `TR`.object_id IN ( {$posts__in} )
							GROUP BY `TR`.object_id 
						) as cat ON `new_table`.ID = `cat`.ID
						SET `new_table`.question_tags = `cat`.question_tags
						WHERE `new_table`.ID IN ( {$posts__in} )
						";
			$wpdb->query( $query_cat );
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
		if ( is_user_logged_in() ) { 
			$post_status = "'publish', 'private', 'pending'";
		} else {
			$post_status = "'publish'";
		}

		$query = "SELECT * FROM {$this->table} WHERE 1=1 AND post_status IN ( {$post_status} )";

		//Permisson
		if ( is_user_logged_in() && ! dwqa_current_user_can( 'edit_question' ) ) {
			global $current_user;
			$query .= " AND IF( post_author = {$current_user->ID}, 1, IF( post_status = 'private', 0, 1 ) ) = 1";
		}
		$sticky_questions = get_option( 'dwqa_sticky_questions' );
		if ( is_array( $sticky_questions ) ) {
			$sticky_questions = implode(',', $sticky_questions );
		}
		if ( $sticky_questions ) {
			$query .= " AND ID NOT IN ( {$sticky_questions} )";
		}

		if ( is_tax( 'dwqa-question_category' ) ) {
			$category = get_query_var( 'dwqa-question_category' );
			$term = get_term_by( 'slug', $category, 'dwqa-question_category' );
			$query .= " AND question_categories REGEXP '^{$term->term_id},|,{$term->term_id},|,{$term->term_id}$|^{$term->term_id}$' ";
		}
		if ( is_tax( 'dwqa-question_tag' ) ) {
			$tag = get_query_var( 'dwqa-question_tag' );
			$term = get_term_by( 'slug', $tag, 'dwqa-question_tag' );
			if ( $term ) {
				$query .= " AND question_tags REGEXP '^{$term->term_id},|,{$term->term_id},|,{$term->term_id}$|^{$term->term_id}$' ";
			}
		} 

		$posts_per_page = isset( $dwqa_general_settings['posts-per-page'] ) ?  $dwqa_general_settings['posts-per-page'] : 5;
		$paged = get_query_var( 'paged' );
		$offset = $paged ? ( $paged - 1 ) * $posts_per_page : 0;
		$query .= " ORDER BY last_activity_date DESC LIMIT {$offset}, {$posts_per_page}";
		


		$questions = $wpdb->get_results( $query );
		$this->temp = array( 
			'posts' => $wp_query->posts,
			'post_count' => $wp_query->post_count
		);
		$wp_query->posts = $questions;
		$wp_query->post_count = count( $questions );
		rewind_posts();
	}

	public function after_archive_posts() {
		global $wp_query, $post;
		$wp_query->posts = $this->temp['posts'];
		$wp_query->post_count = $this->temp['post_count'];
		$this->temp = false;

		// wp_reset_postdata();
		if ( have_posts() ) the_post();
	}
	/**
	 * Update table index when have new question, question was update or have new answer
	 * @param int $question_id Updated question ID
	 */
	public function update_question( $id ) {
		global $wpdb;
		// Just update with question post type
		$post_type = get_post_type( $id );
		if ( $post_type == 'dwqa-question' ) {
			$question_exists = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM {$this->table} WHERE ID = %d", $id ) );
			if ( $question_exists ) { // Update
				$query = $wpdb->prepare( "UPDATE {$this->table} as new_table
							JOIN ( SELECT * FROM {$wpdb->posts} WHERE post_type = 'dwqa-question' AND ID = %d ) as question
							ON `new_table`.ID = `question`.ID
							SET `new_table`.post_author = `question`.post_author, `new_table`.post_date = `question`.post_date, `new_table`.post_date_gmt = `question`.post_date_gmt, `new_table`.post_content = `question`.post_content, `new_table`.post_title = `question`.post_title, `new_table`.post_excerpt = `question`.post_excerpt, `new_table`.post_status = `question`.post_status, `new_table`.comment_status = `question`.comment_status, `new_table`.ping_status = `question`.ping_status, `new_table`.post_password = `question`.post_password, `new_table`.post_name = `question`.post_name, `new_table`.to_ping = `question`.to_ping, `new_table`.pinged = `question`.pinged, `new_table`.post_modified = `question`.post_modified, `new_table`.post_modified_gmt = `question`.post_modified_gmt, `new_table`.post_content_filtered = `question`.post_content_filtered, `new_table`.post_parent = `question`.post_parent, `new_table`.guid = `question`.guid, `new_table`.menu_order = `question`.menu_order, `new_table`.post_type = `question`.post_type, `new_table`.post_mime_type = `question`.post_mime_type, `new_table`.comment_count = `question`.comment_count", $id );
				$wpdb->query( $query );
			} else { // Insert
				$query = $wpdb->prepare( "INSERT INTO {$this->table} ( ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count ) SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id );
				$wpdb->query( $query );

				//Update last activity
				$question = get_post( $id );
				$query = $wpdb->prepare( "UPDATE {$this->table} SET last_activity_date = '{$question->post_date}', last_activity_author = $question->post_author, last_activity_id = {$question->ID} WHERE ID = %d", $id );
				$wpdb->query( $query );
			}

			//Update question category
			$posts__in = $id;
			// Update Question Category ( category id) : 
			$query_column_exists = "SHOW COLUMNS FROM {$this->table} LIKE 'question_categories'";
			if( ! $wpdb->query( $query_column_exists ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN question_categories varchar(255) NOT NULL DEFAULT '' AFTER last_activity_id" );
			} 

			$query_cat = "UPDATE {$this->table} new_table 
						JOIN ( 
							SELECT 
								`TR`.object_id ID, 
								GROUP_CONCAT( `T`.term_id SEPARATOR ',' ) question_categories 
							FROM `{$wpdb->term_taxonomy}` T 
								JOIN `{$wpdb->term_relationships}` TR 
									ON `T`.term_taxonomy_id = `TR`.term_taxonomy_id 
							WHERE `T`.taxonomy = 'dwqa-question_category'
								AND `TR`.object_id IN ( {$posts__in} )
							GROUP BY `TR`.object_id 
						) as cat ON `new_table`.ID = `cat`.ID
						SET `new_table`.question_categories = `cat`.question_categories
						WHERE `new_table`.ID IN ( {$posts__in} )
						";
			$wpdb->query( $query_cat );


			// Update Question Tags ( tag id ) : 
			$query_column_exists = "SHOW COLUMNS FROM {$this->table} LIKE 'question_tags'";
			if( ! $wpdb->query( $query_column_exists ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN question_tags varchar(255) NOT NULL DEFAULT '' AFTER question_categories" );
			} 

			$query_cat = "UPDATE {$this->table} new_table 
						JOIN ( 
							SELECT 
								`TR`.object_id ID, 
								GROUP_CONCAT( `T`.term_id SEPARATOR ',' ) question_tags 
							FROM `{$wpdb->term_taxonomy}` T 
								JOIN `{$wpdb->term_relationships}` TR 
									ON `T`.term_taxonomy_id = `TR`.term_taxonomy_id 
							WHERE `T`.taxonomy = 'dwqa-question_tag'
								AND `TR`.object_id IN ( {$posts__in} )
							GROUP BY `TR`.object_id 
						) as cat ON `new_table`.ID = `cat`.ID
						SET `new_table`.question_tags = `cat`.question_tags
						WHERE `new_table`.ID IN ( {$posts__in} )
						";
			$wpdb->query( $query_cat );
		}
	}

	public function delete_question( $id ) {
		global $wpdb;

		$post_type = get_post_type( $id );
		if ( 'dwqa-question' == $post_type ) {
			$query = $wpdb->prepare( "DELETE FROM {$this->table} WHERE ID = %d", $id );
			$wpdb->query( $query );
		}
	}

	public function delete_answer( $id ) {
		global $wpdb;
		$post_type = get_post_type( $id );
		if ( 'dwqa-answer' == $post_type && get_post_status( $id ) != 'draft' ) {
			$question_id = get_post_meta( $id, '_question', true );

			$query = "UPDATE {$this->table} as new_table
						JOIN ( 
							SELECT `M`.meta_value as question,
								count(*) answer_count, 
								sum( if( `A`.post_status = 'private', 1, 0 ) ) private_answer_count, 
								sum( if( `A`.post_status = 'publish', 1, 0 ) ) publish_answer_count,
								max( `A`.post_date ) last_activity_date,
								if( count(*) > 0, 'answer', 'create' ) last_activity_type,
								GROUP_CONCAT( ID SEPARATOR ',') answers
							FROM `{$wpdb->posts}` A
							JOIN `{$wpdb->postmeta}` M ON `A`.ID = `M`.post_id
							WHERE `M`.meta_value = {$question_id} 
								AND `A`.ID <> {$id}
								AND `A`.post_type = 'dwqa-answer' 
								AND `M`.meta_key = '_question' 
								AND ( `A`.post_status = 'publish' OR `A`.post_status = 'private' )
							ORDER BY `A`.post_date DESC 
						) as calculated ON `new_table`.ID = `calculated`.question 
						SET `new_table`.answer_count = `calculated`.answer_count,
							`new_table`.private_answer_count = `calculated`.private_answer_count,
							`new_table`.publish_answer_count = `calculated`.publish_answer_count,
							`new_table`.last_activity_type = `calculated`.last_activity_type,
							`new_table`.last_activity_date = `calculated`.last_activity_date,
							`new_table`.answers = `calculated`.answers
						WHERE `new_table`.ID = {$question_id}
					";
			$wpdb->query( $query );

			$question = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE ID = %d", $question_id ) );

			//update question_status, last_activity_author, last_activity_id
			if ( $question->last_activity_type == 'answer' ) {
				$query = $wpdb->query( "UPDATE {$this->table} new_table
					JOIN (
						SELECT {$question_id} qID, 
							`answers`.post_author last_activity_author,
							`answers`.ID last_activity_id,
							IF( `usermeta`.meta_value LIKE '%administrator%' OR `usermeta`.meta_value LIKE '%editor%', 'answered', 're-open' ) question_status
						FROM {$wpdb->posts} answers 
							JOIN {$wpdb->usermeta} usermeta ON `answers`.post_author = `usermeta`.user_id
						WHERE `answers`.ID IN ( {$question->answers} )
							AND `answers`.post_date = '{$question->last_activity_date}'
							AND `usermeta`.meta_key = '{$wpdb->prefix}capabilities'
						LIMIT 0, 1 ) latest_answer ON `new_table`.ID = `latest_answer`.qID
					SET `new_table`.last_activity_author = `latest_answer`.last_activity_author,`new_table`.last_activity_id = `latest_answer`.last_activity_id,`new_table`.question_status = `latest_answer`.question_status
					WHERE `new_table`.ID = {$question_id}
				" );
			} elseif ( $question->last_activity_type == 'create' ) {
				$query = $wpdb->query( "UPDATE {$this->table} new_table 
					JOIN ( SELECT ID, post_author, post_date FROM {$this->table} WHERE ID = {$question_id} ) question ON `new_table`.ID = `question`.ID 
					SET `new_table`.last_activity_author = `question`.post_author,
						`new_table`.last_activity_id = `question`.ID,
						`new_table`.last_activity_date = `question`.post_date
					" );
			}
		}
	}	
	public function answers_change( $id ) {
		global $wpdb;
		$post_type = get_post_type( $id );
		if ( 'dwqa-answer' == $post_type && get_post_status( $id ) != 'draft' ) {
			$question_id = get_post_meta( $id, '_question', true );

			$query = "UPDATE {$this->table} as new_table
						JOIN ( 
							SELECT `M`.meta_value as question,
								count(*) answer_count, 
								sum( if( `A`.post_status = 'private', 1, 0 ) ) private_answer_count, 
								sum( if( `A`.post_status = 'publish', 1, 0 ) ) publish_answer_count,
								max( `A`.post_date ) last_activity_date,
								if( count(*) > 0, 'answer', 'create' ) last_activity_type,
								GROUP_CONCAT( ID SEPARATOR ',') answers
							FROM `{$wpdb->posts}` A
							JOIN `{$wpdb->postmeta}` M ON `A`.ID = `M`.post_id
							WHERE `M`.meta_value = {$question_id} 
								AND `A`.post_type = 'dwqa-answer' 
								AND `M`.meta_key = '_question' 
								AND ( `A`.post_status = 'publish' OR `A`.post_status = 'private' )
							ORDER BY `A`.post_date DESC 
						) as calculated ON `new_table`.ID = `calculated`.question 
						SET `new_table`.answer_count = `calculated`.answer_count,
							`new_table`.private_answer_count = `calculated`.private_answer_count,
							`new_table`.publish_answer_count = `calculated`.publish_answer_count,
							`new_table`.last_activity_type = `calculated`.last_activity_type,
							`new_table`.last_activity_date = `calculated`.last_activity_date,
							`new_table`.answers = `calculated`.answers
						WHERE `new_table`.ID = {$question_id}
					";
			$wpdb->query( $query );

			$question = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE ID = %d", $question_id ) );

			//update question_status, last_activity_author, last_activity_id
			if ( $question->last_activity_type == 'answer' ) {
				$query = $wpdb->query( "UPDATE {$this->table} new_table
					JOIN (
						SELECT {$question_id} qID, 
							`answers`.post_author last_activity_author,
							`answers`.ID last_activity_id,
							IF( `usermeta`.meta_value LIKE '%administrator%' OR `usermeta`.meta_value LIKE '%editor%', 'answered', 're-open' ) question_status
						FROM {$wpdb->posts} answers 
							JOIN {$wpdb->usermeta} usermeta ON `answers`.post_author = `usermeta`.user_id
						WHERE `answers`.ID IN ( {$question->answers} )
							AND `answers`.post_date = '{$question->last_activity_date}'
							AND `usermeta`.meta_key = '{$wpdb->prefix}capabilities'
						LIMIT 0, 1 ) latest_answer ON `new_table`.ID = `latest_answer`.qID
					SET `new_table`.last_activity_author = `latest_answer`.last_activity_author,`new_table`.last_activity_id = `latest_answer`.last_activity_id,`new_table`.question_status = `latest_answer`.question_status
					WHERE `new_table`.ID = {$question_id}
				" );
			} elseif ( $question->last_activity_type == 'create' ) {
				$query = $wpdb->query( "UPDATE {$this->table} new_table 
					JOIN ( SELECT ID, post_author, post_date FROM {$this->table} WHERE ID = {$question_id} ) question ON `new_table`.ID = `question`.ID 
					SET `new_table`.last_activity_author = `question`.post_author,
						`new_table`.last_activity_id = `question`.ID,
						`new_table`.last_activity_date = `question`.post_date
					" );
			}
		}
	}

	public function update_question_metadata( $meta_id, $object_id, $meta_key, $meta_value ){
		global $wpdb;
		if ( $meta_key == '_dwqa_views' ) {
			$query = $wpdb->prepare( "UPDATE {$this->table} SET view_count = {$meta_value} WHERE ID = %d", $object_id );
			$wpdb->query( $query );
		} elseif ( '_dwqa_votes' == $meta_key ) {
			$query = $wpdb->prepare( "UPDATE {$this->table} SET vote_count = {$meta_value} WHERE ID = %d", $object_id );
			$wpdb->query( $query );
		} elseif ( '_dwqa_status' == $meta_key ) {
			$query = $wpdb->prepare( "UPDATE {$this->table} SET question_status = '{$meta_value}' WHERE ID = %d", $object_id );
			$wpdb->query( $query );
		}
	}

	public function get_question_field( $field, $question_id ) {
		global $wpdb;
		if ( strpos( $field, ',') === false ) {
			$field = $wpdb->get_var( $wpdb->prepare( "SELECT {$field} FROM {$this->table} WHERE ID = %d LIMIT 0,1", $question_id ) );
		} else {
			$field = $wpdb->get_row( $wpdb->prepare( "SELECT {$field} FROM {$this->table} WHERE ID = %d LIMIT 0,1", $question_id ) );
		}

		if ( $field && ! is_wp_error( $field ) ) {
			return $field;
		}
		return false;
	}

}
$GLOBALS['dwqa_database_upgrade'] = new DWQA_Database_Upgrade();

?>
