jQuery(function($){
    if( $.browser.msie == true && parseInt( $.browser.version ) < 10 ) {
        $('[placeholder]').focus(function() {
          var input = $(this);
          if (input.val() == input.attr('placeholder')) {
            input.val('');
            input.removeClass('placeholder');
          }
        }).blur(function() {
          var input = $(this);
          if (input.val() == '' || input.val() == input.attr('placeholder')) {
            input.addClass('placeholder');
            input.val(input.attr('placeholder'));
          }
        }).blur();
    }
    var submitAjax = false;

    $('.btn-submit-question').on('click',function(event){
        var $label = $(this).val();
        if( 'Ask Question' != $label ) {
            if( $('.list-open-question').length>0 ) {
                $('.list-open-question').fadeOut(200).remove();
            }
            $('.question-form-fields').fadeIn('slow');
            $(this).val('Ask Question');
            submitAjax = false;
            return false;
        } 
    });
    $('#dwqa-submit-question-form').on('submit',function(e){
        var t= $(this);
        var flag = true;
        console.log( submitAjax );
        if( submitAjax ) {
            return false;
        }

        if( $('.list-open-question').length>0 ) {
            $('.list-open-question').fadeOut(200).remove();
        }
        var returnDefault = function( el, placeholder ){
            el.on('focus',function(){
                $(this).removeClass('required');
                if( placeholder ) {
                    $(this).attr({
                        placeholder: placeholder
                    });
                }
                if( $(this).next('.required') ) {
                    $(this).next('.required').remove();
                }
            });
        }
        if( $('#question-tag').val() == $('#question-tag').attr('placeholder') ) {
            $('#question-tag').val('');
        }
        if( $('#question-title').val().length <= 3 || $('#question-title').val() == $('#question-title').attr('placeholder') ) {
            e.preventDefault();
            var placeholder = $('#question-title').attr('placeholder');
            if( $('#question-title').val().length == 0 ) {
                $('#question-title').addClass('required').attr('placeholder', dwqa.error_missing_question_content);
            } else {
                $('#question-title').addClass('required').after( '<span class="description required">* ' + dwqa.error_question_length + '</span>');
            }
            returnDefault( $('#question-title'), placeholder );
            flag = false;
        }
        var email_field = t.find('[name="user-email"]');
        var username_signup = t.find('#user-name-signup');
        var password = t.find('#user-password');
        var username = t.find('#user-name');
        if( $('#login-type').length > 0 ) {
            if( $('#login-type').val() == 'sign-up' ) {
                username.attr('disabled', 'disabled');
                password.attr('disabled', 'disabled');
                username_signup.removeAttr('disabled');
                username_signup.removeAttr('disabled');
                var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                
                if( ! regex.test( email_field.val() ) || email_field.val() == email_field.attr('placeholder') ) {
                    email_field.closest('p').fadeIn('slow');
                    email_field.addClass('required');
                    returnDefault( email_field );
                    flag = false;
                }

                if( (username_signup.length > 0 && username_signup.val().length < 3) || username_signup.val() == username_signup.attr('placeholder') ) {
                    username_signup.addClass('required');
                    returnDefault( username_signup );
                    flag = false;
                }
            } else {
                email_field.attr('disabled', 'disabled');
                username_signup.attr('disabled', 'disabled');
                username.removeAttr('disabled');
                password.removeAttr('disabled');
                if( (username.length > 0 && username.val().length < 3) || username.val() == username.attr('placeholder') ) {
                    username.addClass('required');
                    returnDefault( username );
                    flag = false;
                }
                if( password.val().length < 3 || password.val() == password.attr('placeholder') ) {
                    password.addClass('required');
                    returnDefault( password );
                    flag = false;
                }
            }
        }
        if( ! flag ) { 
            if( ! $('#question-tag').val() ) {
                $('#question-tag').val( $('#question-tag').attr('placeholder') );
            }
            console.log( 'test' );
            return false; 
        }

        $('#question-title, #question-tag, [name="password-signup"], [name="user-name"], [name="user-name-signup"], [name="user-email"], [name="private-message"],#question-category, [name="question-content"]').attr('disabled','disabled');
        $('.dwqa-submit-question').addClass('loading');
        $('.search-results-suggest').remove();
        //Submit Question by Ajax
        
        submitAjax = true;
        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-submit-question-ajax',
                '_wpnonce': $('#_wpnonce').val(),
                'question-title' : $('#question-title').val(),
                'question-category' : $('#question-category').val(),
                'question-tag' : $('#question-tag').val(),
                'question-content' : tinyMCE.activeEditor.getContent(),
                'recaptcha_challenge_field' : $('[name="recaptcha_challenge_field"]').val(),
                'recaptcha_response_field' : $('[name="recaptcha_response_field"]').val(),
                'user-email' : $('[name="user-email"]').val(),
                'user-name-signup' : $('[name="user-name-signup"]').val(),
                'user-name' : $('[name="user-name"]').val(),
                'user-password' : $('[name="user-password"]').val(),
                'password-signup' : $('[name="password-signup"]').val(),
                'private-message' : ($('[name="private-message"]').is(':checked') ? 1 : 0 ),
                'login-type' : $("#login-type").val(),
                '_wp_http_referer' : $('[name="_wp_http_referer"]').val()
            }
        })
        .done(function(resp) {
            if( resp.success ) {
                if( $('.list-open-question').length>0 ) {
                    $('.list-open-question').remove();
                }
                $('.question-form-fields').hide();
                $(resp.data.html).hide().insertBefore($('#submit-question')).fadeIn('slow');
                $('.btn-submit-question').val('Ask More Question');
                $('#_wpnonce').val(resp.data.nonce);
                $('.question-signin').hide().find('#login-type').remove();
            } else {
                console.log( resp.data.message );
            }
            submitAjax = false;
            $('.dwqa-submit-question').removeClass('loading');
        })
        .always(function() {
            //Reset post form
            $('#question-title, [name="password-signup"], [name="user-name"], [name="user-name-signup"], [name="user-email"]').val('');
            $('[name="private-message"]').attr('checked', false);
            $('#question-category').val(-1);
            $('#question-tag').val('Other');
            tinyMCE.activeEditor.setContent('');

            $('#question-title, #question-tag, [name="password-signup"], [name="user-name"], [name="user-name-signup"], [name="user-email"], [name="private-message"],#question-category, [name="question-content"]').removeAttr('disabled');
            submitAjax = false;

        });
        
        return false;
    });

    $('#dwqa-submit-question-form').on('input','#user-email',function(event){
        var t = $(this);
        $('#dwqa-submit-question-form .user-credential').fadeIn('slow');
    });
    

    var $search = null, $search_submit = false, timeout = false, canEnter = false;
    $('#question-title').on('input',function(event){

        if( timeout ) {
            clearTimeout( timeout );
            timeout = false;
        } 
        var t = $(this);
        timeout = setTimeout( function(){
            if( submitAjax ) {
                return false;
            }
            if( t.val().length > 2 ) {
                
                t.parent().find('.dwqa-search-loading').show();
                $search = $.ajax({
                    url: dwqa.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action : 'dwqa-auto-suggest-search-result',
                        nonce : t.data('nonce'),
                        title: t.val()
                    }
                })
                .always(function() {
                    t.parent().find('.dwqa-search-loading').hide();
                })
                .done(function( resp ) {
                    var $results = '';
                    if( resp.success ) {
                        $results = '<ul>'+resp.data.html+'</ul>';
                    } else {
                        if( t.parent().find('.search-results-suggest').length > 0 ) {
                            t.parent().find('.search-results-suggest').remove();
                        }
                    }

                    if( t.parent().find('.search-results-suggest').length > 0 ) {
                        t.parent().find('.search-results-suggest').hide().html( $results ).fadeIn('fast');
                    } else {
                        $('<div class="search-results-suggest">' + $results + '</div>').hide().insertAfter(t).slideDown('100');
                    }
                });
            } 
            clearTimeout( timeout );
            timeout = false;
        },700);
        if( t.val().length <= 0 ) {
            t.parent().find('.search-results-suggest').remove();
        }
    });
    
    $('#question-title').on('blur',function(event){
        var t = $(this);
        t.parent().find('.search-results-suggest').slideUp('slow');
    });

    $('#dwqa-submit-question-form').on('click', '.credential-form-toggle', function(event){
        event.preventDefault();
        var loginType = $('#dwqa-submit-question-form input[name="login-type"]');
        if( loginType.val() == 'sign-up' ) {
            loginType.val('sign-in');
            $('#dwqa-submit-question-form .question-register').fadeOut('slow');
            $('#dwqa-submit-question-form .question-login').fadeIn('slow').removeClass('dwqa-hide');
        } else {
            loginType.val('sign-up');
            $('#dwqa-submit-question-form .question-register').fadeIn('slow');
            $('#dwqa-submit-question-form .question-login').fadeOut('slow');

        }
    });

    jQuery(document).ready(function($) {
        $('.question-register').parent().height( $('.question-register').height() );
        var loginType = $('#dwqa-submit-question-form input[name="login-type"]');
        if( loginType.val() == 'sign-up' ) {
            loginType.val('sign-up');
            $('#dwqa-submit-question-form .question-register').fadeIn('slow');
            $('#dwqa-submit-question-form .question-login').fadeOut('slow');
        } else {
            loginType.val('sign-in');
            $('#dwqa-submit-question-form .question-register').fadeOut('slow');
            $('#dwqa-submit-question-form .question-login').fadeIn('slow');
        }
    });
});