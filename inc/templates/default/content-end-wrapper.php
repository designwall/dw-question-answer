<?php  

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$template = get_option('template');

switch( $template ) {
    case 'twentyeleven' :
    case 'twentytwelve' :
    case 'twentythirteen' :
        echo '</div></div>';
        break;
    case 'twentyfourteen':
        echo '</div></div></div>';
        break;
    default:
        echo '</div></div>';
        break;
}


?>