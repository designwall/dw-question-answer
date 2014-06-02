jQuery(function($) {
    
    var BrowserDetect = {
        init: function () {
            this.browser = this.searchString(this.dataBrowser) || "Other";
            this.version = this.searchVersion(navigator.userAgent) ||       this.searchVersion(navigator.appVersion) || "Unknown";
        },

        searchString: function (data) {
            for (var i=0 ; i < data.length ; i++)   
            {
                var dataString = data[i].string;
                this.versionSearchString = data[i].subString;

                if (dataString.indexOf(data[i].subString) != -1)
                {
                    return data[i].identity;
                }
            }
        },

        searchVersion: function (dataString) {
            var index = dataString.indexOf(this.versionSearchString);
            if (index == -1) return;
            return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
        },

        dataBrowser: 
        [
            { string: navigator.userAgent, subString: "Chrome",  identity: "Chrome" },
            { string: navigator.userAgent, subString: "MSIE",    identity: "Explorer" },
            { string: navigator.userAgent, subString: "Firefox", identity: "Firefox" },
            { string: navigator.userAgent, subString: "Safari",  identity: "Safari" },
            { string: navigator.userAgent, subString: "Opera",   identity: "Opera" }
        ]

    };
    BrowserDetect.init();
    if ( BrowserDetect.browser == 'Explorer' && parseInt(BrowserDetect.version) < 10) {
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

    $('#dwqa-submit-question-form').on('submit', function(e) {
        var t = $(this);
        var flag = true;

        if ($('.list-open-question').length > 0) {
            $('.list-open-question').fadeOut(200).remove();
        }
        var returnDefault = function(el, placeholder) {
            el.on('focus', function() {
                $(this).removeClass('required');
                if (placeholder) {
                    $(this).attr({
                        placeholder: placeholder
                    });
                }
                if ($(this).next('.required')) {
                    $(this).next('.required').remove();
                }
            });
        }
        if ($('#question-tag').val() == $('#question-tag').attr('placeholder')) {
            $('#question-tag').val('');
        }
        if ($('#question-title').val().length <= 3 || $('#question-title').val() == $('#question-title').attr('placeholder')) {
            e.preventDefault();
            var placeholder = $('#question-title').attr('placeholder');
            if ($('#question-title').val().length == 0) {
                $('#question-title').addClass('required').attr('placeholder', dwqa.error_missing_question_content);
            } else {
                $('#question-title').addClass('required').after('<span class="description required">* ' + dwqa.error_question_length + '</span>');
            }
            returnDefault($('#question-title'), placeholder);
            flag = false;
        }
        var email_field = t.find('[name="user-email"]');
        var username_signup = t.find('#user-name-signup');
        var password = t.find('#user-password');
        var username = t.find('#user-name');
        var anonymous = t.find('#_dwqa_anonymous_email');
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

        if( anonymous.length > 0 ) {
            if (!regex.test(anonymous.val()) || anonymous.val() == anonymous.attr('placeholder')) {
                anonymous.closest('p').fadeIn('slow');
                anonymous.addClass('required');
                returnDefault(anonymous);
                flag = false;
            }

        }
        if ($('#login-type').length > 0) {
            if ($('#login-type').val() == 'sign-up') {
                username.attr('disabled', 'disabled');
                password.attr('disabled', 'disabled');
                username_signup.removeAttr('disabled');
                username_signup.removeAttr('disabled');

                if (!regex.test(email_field.val()) || email_field.val() == email_field.attr('placeholder')) {
                    email_field.closest('p').fadeIn('slow');
                    email_field.addClass('required');
                    returnDefault(email_field);
                    flag = false;
                }

                if ((username_signup.length > 0 && username_signup.val().length < 3) || username_signup.val() == username_signup.attr('placeholder')) {
                    username_signup.addClass('required');
                    returnDefault(username_signup);
                    flag = false;
                }
            } else {
                email_field.attr('disabled', 'disabled');
                username_signup.attr('disabled', 'disabled');
                username.removeAttr('disabled');
                password.removeAttr('disabled');
                if ((username.length > 0 && username.val().length < 3) || username.val() == username.attr('placeholder')) {
                    username.addClass('required');
                    returnDefault(username);
                    flag = false;
                }
                if (password.val().length < 3 || password.val() == password.attr('placeholder')) {
                    password.addClass('required');
                    returnDefault(password);
                    flag = false;
                }
            }
        }
        if (!flag) {
            if (!$('#question-tag').val()) {
                $('#question-tag').val($('#question-tag').attr('placeholder'));
            }
            console.log('test');
            return false;
        }
    });

    $('#dwqa-submit-question-form').on('input', '#user-email', function(event) {
        var t = $(this);
        $('#dwqa-submit-question-form .user-credential').fadeIn('slow');
    });


    var $search = null,
        $search_submit = false,
        timeout = false,
        canEnter = false;
    $('#question-title').on('input', function(event) {

        if (timeout) {
            clearTimeout(timeout);
            timeout = false;
        }
        var t = $(this);
        timeout = setTimeout(function() {
            if (t.val().length > 2) {
                t.parent().find('.dwqa-search-loading').show();
                $search = $.ajax({
                    url: dwqa.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'dwqa-auto-suggest-search-result',
                        nonce: t.data('nonce'),
                        title: t.val()
                    }
                })
                    .always(function() {
                        t.parent().find('.dwqa-search-loading').hide();
                    })
                    .done(function(resp) {
                        var $results = '';
                        if (resp.success) {
                            $results = '<ul>' + resp.data.html + '</ul>';
                        } else {
                            if (t.parent().find('.search-results-suggest').length > 0) {
                                t.parent().find('.search-results-suggest').remove();
                            }
                        }

                        if (t.parent().find('.search-results-suggest').length > 0) {
                            t.parent().find('.search-results-suggest').hide().html($results).fadeIn('fast');
                        } else {
                            $('<div class="search-results-suggest">' + $results + '</div>').hide().insertAfter(t).slideDown('100');
                        }
                    });
            }
            clearTimeout(timeout);
            timeout = false;
        }, 700);
        if (t.val().length <= 0) {
            t.parent().find('.search-results-suggest').remove();
        }
    });

    $('#question-title').on('blur', function(event) {
        var t = $(this);
        t.parent().find('.search-results-suggest').slideUp('slow');
    });

    $('#dwqa-submit-question-form').on('click', '.credential-form-toggle', function(event) {
        event.preventDefault();
        var loginType = $('#dwqa-submit-question-form input[name="login-type"]');
        if (loginType.val() == 'sign-up') {
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
        $('.question-register').parent().height($('.question-register').height());
        var loginType = $('#dwqa-submit-question-form input[name="login-type"]');
        if (loginType.val() == 'sign-up') {
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