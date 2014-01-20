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


    var refererField = $('[name="_wp_http_referer"]');
    var updateHashInUrl = function(){
        var url = refererField.val();
        if( location.hash.length > 0 ) {

            if( url.match(/#(\S*)/) ) {
                refererField.val( url.replace(/#(\S*)/g, location.hash) );
            } else {
                refererField.val( url + location.hash );
            }
        }
    }

    $('.dwqa-notification-settings .nav-tabs li').click(function(event){
        event.preventDefault();
        var tab = $(this).find('a:first').attr('href');
        window.location.hash = tab;
        $('.dwqa-notification-settings .nav-tabs li').removeClass('active');
        $('.dwqa-notification-settings .tab-content .tab-pane').removeClass('active');
        $(tab).addClass('active');
        $(this).addClass('active');
        updateHashInUrl();
        $('.dwqa-mail-templates .progress-bar .progress-bar-inner').stop().css('width',0).animate(
            { width: '70%' },
            600,
            function(){
                $(this).css('width', '100%').fadeOut(200, function() {
                    $(this).css({ width: '0%' }).show();
                });;
            }
        );
    });

    $(document).ready(function($) {
        updateHashInUrl();
        if( $('.dwqa-notification-settings').length > 0 && location.hash.length > 0 ) {
            var tab = window.location.hash;
            $('.dwqa-notification-settings .nav-tabs li').removeClass('active');
            $('.dwqa-notification-settings .tab-content .tab-pane').removeClass('active');
            $('.dwqa-notification-settings .nav-tabs li a[href="'+tab+'"]').parent().addClass('active');
            $('.dwqa-notification-settings .tab-content '+tab+'.tab-pane').addClass('active');
        }
    });
});