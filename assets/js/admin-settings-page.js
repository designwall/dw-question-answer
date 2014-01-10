jQuery(document).ready(function($) {
    $('.dwqa-reset-email-template').on('click', function(event) {
        event.preventDefault();
        var template = $(this).data('template');
        var editor = $(this).closest('td').find('.wp-editor-area').attr('id');
        tinymce.execCommand('mceFocus', false, editor);
        $.ajax({
            url: dwqa.template_folder + template,
            type: 'GET',
            dataType: 'html'
        }).done(function(html) {
            tinyMCE.activeEditor.setContent(html);
        });

    });

    $('.reset-permission').on('click', function(event) {
        event.preventDefault();
        if (confirm(dwqa.reset_permission_confirm_text)) {
            var nonce = $('#reset-permission-nonce').val();
            var type = $(this).data('type');

            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-reset-permission-default',
                    nonce: nonce,
                    type: type
                }
            }).done(function() {
                window.location.href = window.location.href;
            });

        }
    });
});