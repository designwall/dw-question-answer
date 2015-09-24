<?php  
/**
 *  Plugin Name: DW Question Answer
 *  Description: A WordPress plugin was make by DesignWall.com to build an Question Answer system for support, asking and comunitcate with your customer 
 *  Author: DesignWall
 *  Author URI: http://www.designwall.com
 *  Version: 1.3.2
 *  Text Domain: dwqa
 */

// Define constant for plugin info 
if ( ! defined( 'DWQA_DIR' ) ) {
	define( 'DWQA_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DWQA_URI' ) ) {
	define( 'DWQA_URI', plugin_dir_url( __FILE__ ) );
}

require_once DWQA_DIR  . 'inc/template-functions.php'; // Template
require_once DWQA_DIR  . 'inc/settings.php'; // Settings
require_once DWQA_DIR  . 'inc/actions.php'; //removed
require_once DWQA_DIR  . 'inc/actions-question.php'; //removed
require_once DWQA_DIR  . 'inc/actions-vote.php'; //removed
require_once DWQA_DIR  . 'inc/filter.php'; 
require_once DWQA_DIR  . 'inc/metaboxes.php';
include_once DWQA_DIR  . 'inc/notification.php';//Notification
require_once DWQA_DIR  . 'inc/class-answers-list-table.php';
require_once DWQA_DIR  . 'inc/class-walker-category.php';
require_once DWQA_DIR  . 'inc/class-walker-tag-dropdown.php';
include_once DWQA_DIR  . 'inc/contextual-helper.php'; 
include_once DWQA_DIR  . 'inc/pointer-helper.php'; // Pointer_Helper
include_once DWQA_DIR  . 'inc/beta.php'; //removed
include_once DWQA_DIR  . 'inc/shortcodes.php'; // Shortcode
include_once DWQA_DIR  . 'inc/status.php';
include_once DWQA_DIR  . 'inc/roles.php'; // Permission

include_once DWQA_DIR  . 'inc/widgets/related-question.php';
include_once DWQA_DIR  . 'inc/widgets/popular-question.php';
include_once DWQA_DIR  . 'inc/widgets/latest-question.php';
include_once DWQA_DIR  . 'inc/widgets/list-closed-question.php';

include_once DWQA_DIR  . 'inc/cache.php';

include_once DWQA_DIR  . 'inc/deprecated.php';

?>
