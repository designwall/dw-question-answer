<?php  
// Exit if accessed directly
// Upgrade functions
if ( !defined( 'ABSPATH' ) ) exit;


class DWQA_Upgrades {
	public static $db_version;
	private static $version = '1.3.4';

	public static function init() {
		self::$db_version = get_option( 'dwqa_version', false );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'admin_menu', array( __CLASS__, 'upgrade_screen' ) );
		add_action( 'wp_ajax_dwqa-upgrades', array( __CLASS__, 'ajax_upgrades' ) );
	}

	public static function admin_notices() {
		if ( isset( $_GET['page']) && 'dwqa-upgrades' == esc_html( $_GET['page'] ) ) {
			return;
		}

		if ( ! self::$db_version || version_compare( self::$db_version, self::$version, '<') ) {
			printf(
				'<div class="error"><p>' . esc_html__( 'DW Question Answer needs to upgrade the database, click %shere%s to start the upgrade.', 'dw-question-answer' ) . '</p></div>',
				'<a href="' . esc_url( admin_url( 'options.php?page=dwqa-upgrades' ) ) . '">',
				'</a>'
			);
		}
	}

	public static function upgrade_screen() {
		add_submenu_page( null, __( 'DWQA Upgrade', 'dw-question-answer' ),  __( 'DWQA Upgrade', 'dw-question-answer' ), 'manage_options', 'dwqa-upgrades', array( __CLASS__, 'proccess_upgrades' ) );
	}

	public static function proccess_upgrades() {
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title(); ?></h2>
			<p><?php _e('The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished...','dw-question-answer') ?></p>
			<script type="text/javascript">
			jQuery(document).ready(function($) {
				function dwqaUpgradeSendRequest( restart ) {

					$.ajax({
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'dwqa-upgrades',
							restart: restart,
						},
					})
					.done(function( resp ) {
						if ( resp.success ) {
							if ( resp.data.finish ) {
								document.location.href = '<?php echo admin_url(); ?>';
							} else {
								dwqaUpgradeSendRequest( 0 );
							}
						} else {
							console.log( resp.message );
						}
					});
				}

				dwqaUpgradeSendRequest( 1 );
				
			});
			</script>
		</div>
		<?php
	}

	public static function upgrade_question_answer_relationship() {
		global $wpdb;
		$cursor = get_option( 'dwqa_upgrades_step', 0 );
		$step = 100;
		$length = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE 1=1 AND post_type = 'dwqa-answer'" );
		if( $cursor + $step <= $length ) {
			$answers = $wpdb->get_results( $wpdb->prepare( "SELECT ID, meta_value as parent FROM $wpdb->posts p JOIN $wpdb->postmeta pm ON p.ID = pm.post_id WHERE 1=1 AND post_type = 'dwqa-answer' AND pm.meta_key = '_question' LIMIT %d, %d ", $cursor, $step ) );

			if ( ! empty( $answers ) ) {
				foreach ( $answers as $answer ) {
					$update = wp_update_post( array( 'ID' => $answer->ID, 'post_parent' => $answer->parent, ), true );
				}
				$cursor += $step;
				update_option( 'dwqa_upgrades_step', $cursor );
			} else {
				delete_option( 'dwqa_upgrades_step' );
			}
		} else {
			delete_option( 'dwqa_upgrades_step' );
		}
	}

	/**
	 * Will run it on next week. time pause here
	 * @return [type] [description]
	 */
	public static function upgrade_question_status() {
		global $wpdb, $dwqa_general_settings;
		$cursor = get_option( 'dwqa_upgrades_step', 0 );
		$step = 100;
		$length = $wpdb->get_var( "SELECT count(*) FROM $wpdb->posts WHERE 1=1 AND post_type = 'dwqa-question'" );
		if( $cursor <= $length ) {
			$questions = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_date FROM $wpdb->posts p JOIN $wpdb->posts WHERE 1=1 AND post_type = 'dwqa-question' LIMIT %d, %d ", $cursor, $step ) );
			if ( ! empty($questions) ) {
				foreach ( $questions as $question ) {
					$answers = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_date, post_author FROM $wpdb->posts WHERE post_type = 'dwqa-answer' AND ( post_status = 'publish' OR post_status = 'private' ) AND post_parent = %d ORDER BY post_date DESC", $question->ID ) );
					$overdue = isset($dwqa_general_settings['question-overdue-time-frame']) ? intval( $dwqa_general_settings['question-overdue-time-frame'] ) : 2;
				}
				$cursor += $step;
				update_option( 'dwqa_upgrades_step', $cursor );
				return $cursor;
			} else {
				// Go Next
				delete_option( 'dwqa_upgrades_step' );
				return 0;
			}
		} else {
			// Go Next
			delete_option( 'dwqa_upgrades_step' );
			return 0;
		}
	}

	public static function ajax_upgrades() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this task', 'dw-question-answer' ) ) );
		}

		if ( isset( $_POST['restart'] ) && intval( $_POST['restart'] ) ) {
			delete_option( 'dwqa_upgrades_start' );
			$start = 0;
		} else {
			$start = get_option( 'dwqa_upgrades_start', 0 );
		}

		switch ( $start ) {
			case 0:
				$start += 1;
				update_option( 'dwqa_upgrades_start', $start );
				wp_send_json_success( array(
					'start' => $start,
					'finish' => 0,
					'message' => __( 'Just do it..', 'dw-question-answer' )
				) );
				break;
			case 1:
				$do_next = self::upgrade_question_answer_relationship();
				if ( ! $do_next ) {
					$start += 1;
					update_option( 'dwqa_upgrades_start', $start );
					$message = sprintf( __( 'Move to next step %d', 'dw-question-answer' ), $start );
				} else {
					$message = $do_next;
				}
				wp_send_json_success( array(
					'start' => $start,
					'finish' => 0,
					'message' => $message
				) );
				break;
			
			default:
				delete_option( 'dwqa_upgrades_start' );
				update_option( 'dwqa_version', self::$version );
				wp_send_json_success( array(
					'start' => $start,
					'finish' => 1,
					'message' => __('Upgrade process is done','dw-question-answer')
				) );
				break;
		}
	}
}
DWQA_Upgrades::init();
?>