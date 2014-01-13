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
    default:
        echo '</div></div>';
        get_sidebar();
        break;
}


?>