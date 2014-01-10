<?php  
class dwqa_question_tag_walker extends Walker_CategoryDropdown{

    function start_el(&$output, $object, $depth = 0, $args = array(), $current_object_id = 0) {
        $pad = str_repeat('&nbsp;', $depth * 2);
        $cat_name = apply_filters('list_cats', $object->name, $object);

        if( !isset($args['value']) ){
            $args['value'] = 'id';
        }

        $value = ($args['value']=='name' ? $object->name : $object->term_id );

        $output .= "\t<option class=\"level-$depth\" value=\"".$value."\"";
        if ( $value === (string) $args['selected'] ){ 
            $output .= ' selected="selected"';
        }
        $output .= '>';
        $output .= $pad.$cat_name;
        if ( $args['show_count'] )
            $output .= '&nbsp;&nbsp;('. $object->count .')';

        $output .= "</option>\n";
    }
}

?>