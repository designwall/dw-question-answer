jQuery(document).ready( function($) {
    dwqa_open_pointer(0);
    function dwqa_open_pointer(i) {
        pointer = dwqaPointer.pointers[i];
        options = $.extend( pointer.options, {
            close: function() {
                $.post( ajaxurl, {
                    pointer: pointer.pointer_id,
                    action: 'dismiss-wp-pointer'
                });
                if( dwqaPointer.pointers[i+1] ) {
                    dwqa_open_pointer(i+1);
                }
            }
        });
 
        $(pointer.target).pointer( options ).pointer('open');
    }
});