<?php  

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$template = get_option('template');

switch( $template ) {
    case 'twentyeleven' :
        echo '<div id="primary"><div role="main">';
        break;
    case 'twentytwelve' :
        echo '<div id="primary"><div role="main">';
        break;
    case 'twentythirteen' :
        echo '<div id="primary" class="site-content"><div role="main" class="entry-content twentythirteen">';
        break;
}

?>