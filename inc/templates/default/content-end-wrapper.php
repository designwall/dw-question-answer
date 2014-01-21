<?php  

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$template = get_option('template');

switch( $template ) {
    case 'twentyeleven' :
    case 'twentytwelve' :
    case 'twentythirteen' :
        echo '</div></div>';
        get_sidebar();
        break;
    case 'twentyfourteen':
        echo '</div></div></div>';
        get_sidebar();
        break;
    case 'Circles':
        echo '</div></div>';
        ts_get_single_post_sidebar('right2');
        ts_get_single_post_sidebar('right');
        echo '</div></div></div>';
        break;
    default:
        echo '</div></div>';
        get_sidebar();
        break;
}


?>