<?php
if ( !defined( 'ABSPATH' ) ) exit;

class DWQA_Autoclosure {
	private $days = 1;
	public function __construct() {
		global $dwqa_general_settings;
		if(isset($dwqa_general_settings['use-auto-closure']) && $dwqa_general_settings['use-auto-closure']){
			if(isset($dwqa_general_settings['number-day-auto-closure']) && is_numeric($dwqa_general_settings['number-day-auto-closure']) && $dwqa_general_settings['number-day-auto-closure']>0){
				
				$this->days = $dwqa_general_settings['number-day-auto-closure'];
				
				add_filter( 'cron_schedules', array($this, 'dwqa_add_schedule') );
				
				if (! wp_next_scheduled ( 'auto_closure' )) {
					wp_schedule_event(time(), 'half_daily', 'auto_closure');
				}
				
				add_action('auto_closure', array($this, 'do_auto_closure'));
			}
		}else{
			wp_clear_scheduled_hook( 'auto_closure' );
		}
	}
	
	public function do_auto_closure(){
		$days = $this->days;
		$posts = get_posts(array(
			'post_type' => 'dwqa-question',
			'date_query' => array(
								array(
									'column' => 'post_modified_gmt',
									'before' => $days.' day ago',
								),
						),
			'meta_query' => array(
								array(
									'key'	=> '_dwqa_status',
									'value' => 'closed',
									'compare' =>'!='
								)
						)
		));
		foreach($posts as $value){
			update_post_meta( $value->ID, '_dwqa_status', 'closed' );
		}
	}
	
	public function dwqa_add_schedule( $schedules ) {
		// add a 'weekly' schedule to the existing set
		/* $schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Once Weekly', 'dwqa')
		);
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display' => __('Once a month', 'dwqa')
		);
		$schedules['minutely'] = array(
			'interval' => 60,
			'display' => __('Minutely', 'dwqa')
		); */
		$schedules['half_daily'] = array(
			'interval' => 43200,
			'display' => __('Half Daily', 'dwqa')
		);
		return $schedules;
	}
	
}
?>