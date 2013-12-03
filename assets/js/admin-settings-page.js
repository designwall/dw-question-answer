jQuery(document).ready(function($) {
    $('.dwqa-reset-email-template').on('click',function(event){
        event.preventDefault();
        var template = $(this).data('template');
        var editor = $(this).closest('td').find('.wp-editor-area').attr('id');
        tinymce.execCommand('mceFocus',false,editor);
        $.ajax({
            url: dwqa.template_folder+template,
            type: 'GET',
            dataType: 'html'
        })
        .done(function(html) {
            tinyMCE.activeEditor.setContent(html);
        });
        
    });
});