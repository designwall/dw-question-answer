String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
}

function _e(event, obj, fn) {
    jQuery(obj)[fn](event);
}


jQuery(function($) {

    var answers = $('#dwqa-answers'),
        answer_editor = $('#dwqa-add-answers');

    function replaceURLWithHTMLLinks(text) {
        var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
        return text.replace(exp, "<a href='$1'>$1</a>");
    }

    function htmlForTextWithEmbeddedNewlines(text) {
        var htmls = [];
        var lines = text.split(/\n/);
        var tmpDiv = jQuery(document.createElement('div'));
        for (var i = 0; i < lines.length; i++) {
            htmls.push(tmpDiv.text(lines[i]).html());
        }
        return htmls.join("<br>");
    }

    //Comment edit
    $('.question-comment a, .answer-comment a').on('click', function(event) {
        event.preventDefault();
        $(this).closest('article').find('.comments-area').slideToggle();
    });

    // DWQA Vote Function=========================================================================
    $('.dwqa-vote .dwqa-vote-dwqa-btn').on('click', function(event) {
        event.preventDefault();
        var t = $(this),
            parent = t.parent(),
            type = parent.data('type'),
            vote = t.data('vote'),
            nonce = parent.data('nonce');

        if (type == 'question') {
            question_id = parent.data('question');
            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-action-vote',
                    vote_for: 'question',
                    nonce: nonce,
                    question_id: question_id,
                    type: vote
                }
            })
                .done(function(resp) {
                    if (resp.success) {
                        parent.find('.dwqa-vote-count').text(resp.data.vote);
                    }
                });
        } else if (type == 'answer') {
            answer_id = parent.data('answer');

            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-action-vote',
                    vote_for: 'answer',
                    nonce: nonce,
                    answer_id: answer_id,
                    type: vote
                }
            })
                .done(function(resp) {
                    if (resp.success) {
                        parent.find('.dwqa-vote-count').text(resp.data.vote);
                    }
                });

        }
    });


    // Change Question Status =====================================================================
    $('.dwqa-change-status ul li').click(function(event) {
        event.preventDefault();
        var t = $(this),
            parent = t.parent();
        if (window.confirm('Are you sure about that change?')) {
            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-update-question-status',
                    status: t.data('status'),
                    nonce: parent.data('nonce'),
                    question: parent.data('question')

                },
                complete: function(xhr, textStatus) {
                    window.location.reload();
                },
            });
        }
    });


    //Answer Editor SUBMIT ======================================================================= 
    answers.delegate('#dwqa-answer-question-form', 'submit', function(event) {
        var t = $(this);
        var value = $('#dwqa-answer-question-editor').val().length,
            editor_style = $('#dwqa-answer-question-editor_parent').css('display');
        if (editor_style == 'block' || editor_style == 'inline') {
            value = tinyMCE.get('dwqa-answer-question-editor').getContent().length;
        }

        if (value <= 2) {
            event.preventDefault();

            if (t.parent().find('.alert-error').length > 0) {
                t.find('.alert-error').fadeIn();
            } else {
                t.before('<div class="alert alert-error">' + dwqa.error_missing_answer_content + '</div>');
            }
            return false;
        }

        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        var email_field = t.find('#user-email');
        if (email_field.length > 0) {
            if (email_field.val().length > 0 && !regex.test(email_field.val())) {
                email_field.closest('p').fadeIn('slow');
                email_field.focus();
                return false;
            }
        }
    }); //End Answer editor submit

    // Comment ====================================================================================
    var onSubmitComment = false;
    $('[id^=comment_form_]').on('submit', function(event) {
        event.preventDefault();

        var t = $(this),
            contentField = t.find('textarea[name="comment"]'),
            content = contentField.val().trim().replace(/\n/g, '<br>');

        var name = '',
            email = '',
            url = '';

        if (!dwqa.is_logged_in) {
            if (t.find('[name="author"]').length > 0) {
                name = t.find('[name="author"]').val();
                if (name.length <= 0) {
                    if (t.parent().find('.name-error').length > 0) {
                        t.parent().find('.name-error').text('Please add your name').fadeIn();
                    } else {
                        t.before('<div class="alert alert-error name-error">' + 'Please add your name' + '</div>');
                    }
                } else {
                    t.parent().find('.name-error').remove();
                }
            }

            if ($(this).find('[name="email"]').length > 0) {
                email = $(this).find('[name="email"]').val();
                var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

                if (email.length <= 0 || !regex.test(email)) {
                    if (t.parent().find('.email-error').length > 0) {
                        t.parent().find('.email-error').text('Please enter a valid email').fadeIn();
                    } else {
                        t.before('<div class="alert alert-error email-error">' + 'Please enter a valid email' + '</div>');
                    }
                    email = '';
                } else {
                    t.parent().find('.email-error').remove();
                }
            }

            if (!name || !email) {
                return false;
            }

        }
        if ($(this).find('[name="url"]').length > 0) {
            url = $(this).find('[name="url"]').val();
        }

        if (content.length <= 2) {
            var message = dwqa.error_missing_comment_content;
            if (content.length > 0) {
                message = dwqa.error_not_enought_length;
            }
            if (t.parent().find('.content-error').length > 0) {
                t.parent().find('.content-error').fadeIn();
            } else {
                t.before('<div class="alert alert-error content-error">' + message + '</div>');
            }
            return false;
        } else {
            if (onSubmitComment) {
                return false;
            }
            onSubmitComment = true;
            var loading = $('<span class="loading"></span>');
            $(this).find('[name="submit"]').after(loading);
            loading.css('display', 'inline-block').parent().css('position', 'relative');
            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-comment-action-add',
                    content: content,
                    wpnonce: 'test',
                    comment_post_ID: t.find('[name="comment_post_ID"]').val(),
                    comment_parent: t.find('[name="comment_parent"]').val(),
                    name: name,
                    email: email,
                    url: url
                },
                complete: function() {
                    onSubmitComment = false;
                },
                success: function(data, textStatus, xhr) {
                    var submitForm = t.closest('.dwqa-comment-form'),
                        commentList = submitForm.parent().find('.dwqa-comment-list');

                    if (commentList.length > 0) {
                        commentList.append(data.data.html);
                    } else {
                        var commentList = '<ol class="dwqa-comment-list">' + data.data.html + '</ol>';
                        submitForm.before(commentList);
                    }
                    contentField.val('');
                    loading.remove();
                },
            });

        }

    });

    $('[id^=comment_form_]').delegate('textarea', 'keydown', function(event) {
        var key = event.keycode || event.which,
            t = $(this);
        if (t.val().length > 2) {
            if (t.closest('#respond').find('.alert-error').length > 0) {
                t.closest('#respond').find('.alert-error').hide(300).remove();
            }
        }
    });

    var originHeight, current_form;
    $('[id^=comment_form_]').delegate('textarea', 'focus', function(event) {
        var t = $(this);
        current_form = t.closest('.dwqa-comment-form');
        var lineHeight = parseInt(t.css('line-height').replace('px', '')),
            thisPadding = parseInt(t.css('padding-top').replace('px', '')),
            defaultHeight = (lineHeight + thisPadding) * 3;

        originHeight = t.height();
        var changeHeight = function() {
            var matches = t.val().match(/\n/g);
            var breaks = matches ? matches.length : 0;
            if (breaks <= 1 || t.val().length < 0) {
                t.height(defaultHeight);
            }
            if (breaks > 1) {
                var newHeight = lineHeight * (breaks + 1) + thisPadding * 3;
                if (t.height() < newHeight) {
                    t.height(newHeight);
                }
            }

        }

        changeHeight();
        current_form.find('.dwqa-form-submit').show();
        t.bind('keyup change', function(event) {
            changeHeight();
        });

    });

    //Comment Update ==============================================================================
    $('.dwqa-comments').delegate('.dwqa-comment-edit-link', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            comment_container = t.closest('.dwqa-comment'),
            comment_content = comment_container.find('.dwqa-comment-content .dwqa-comment-content-inner'),
            status = t.data('edit'),
            edit_content;

        if (typeof status == 'undefined' || !status) {
            t.data('edit', 1);
            edit_content = $('<div class="comment-edit-container"><textarea cols="50" rows="1" aria-required="true" class="comment-edit-field" data-current-content="' + escape(comment_content.html()) + '" data-comment-id="' + t.data('comment-id') + '" >' + comment_content.html().trim().replace(/\<br\>/g, "\n").replace(/(<([^>]+)>)/ig, "") + '</textarea><button class="dwqa-btn dwqa-btn-default dwqa-btn-update-comment-submit">' + dwqa.comment_edit_submit_button + '</button>' + t[0].outerHTML + '</div>');
            t.hide();
            var cancel_link = edit_content.find('.dwqa-comment-edit-link');
            cancel_link.text(dwqa.comment_edit_cancel_link).attr('class', 'answer-edit-cancel dwqa-btn dwqa-btn-link');
            cancel_link.bind('click', function(event) {
                event.preventDefault();
                edit_content = unescape(comment_container.find('.comment-edit-field').data('current-content'));
                t.data('edit', 0).css('display', 'inline-block');
                comment_content.html(edit_content);
            });
        }
        comment_content.html(edit_content);
    }); // SHOW FORM to UPDATE  comment

    $('.dwqa-comments').delegate('.comment .dwqa-btn-update-comment-submit', 'click', function(event) {
        event.preventDefault();
        var comment_container = $(this).closest('.dwqa-comment'),
            edit_content,
            edit_link = comment_container.find('.dwqa-comment-edit-link');

        edit_content = comment_container.find('.comment-edit-field').val();
        if (edit_content.length <= 0) {
            return false;
        }
        var comment_id = comment_container.find('.comment-edit-field').data('comment-id');
        comment_container.find('.dwqa-comment-content').html(replaceURLWithHTMLLinks(htmlForTextWithEmbeddedNewlines(edit_content)));
        edit_link.data('edit', 0);
        //edit_link.text( dwqa.comment_edit_link );
        edit_link.css('display', 'inline-block');
        // Update database
        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-action-update-comment',
                comment: edit_content.replace(/\n/g, '<br>'),
                comment_id: comment_id,
                wpnonce: edit_link.data('comment-edit-nonce')
            }
        });
    });
    //End comment edit

    //Delete Comment ============================================================================= 
    $('.dwqa-container').delegate('.comment-delete-link', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            comment_type = t.data('comment-type'),
            comment_count = $('.dwqa-' + comment_type + ' .' + comment_type + '-comment .comment-count');

        if (confirm(dwqa.comment_delete_confirm)) {
            t.closest('.dwqa-comment').fadeOut('slow', function() {
                if (comment_count.length > 0) {
                    var count = parseInt(comment_count.text());
                    if (count > 0 && count - 1 > 0) {
                        comment_count.text(count - 1);
                    } else {
                        comment_count.parent().text('Comment');
                    }
                }
                $(this).remove();
            });

            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-action-delete-comment',
                    comment_id: t.data('comment-id'),
                    wpnonce: t.data('comment-delete-nonce')
                }
            });

        }
    });
    // DELETE comment

    var current_answer_editor = null;
    answers.delegate('.answer-edit-cancel', 'click', function(event) {
        event.preventDefault();
        answer_editor.fadeIn();
        remove_editor();
    });


    var remove_editor = function() {
        if (!current_answer_editor) {
            return false;
        }
        var content = $('#dwqa-custom-content-editor').data('current-content'),
            edit_link = current_answer_editor.find('.answer-edit-link');

        current_answer_editor.find('.dwqa-content').html(unescape(content));
        edit_link.data('on-editor', '');
        current_answer_editor = null;
    }
    //Init quick tags for new 
    var dwqa_buttonsInit = function(inst) {
        var canvas, name, settings, theButtons, html, inst, ed, id, i, use,
            defaults = ',strong,em,link,block,del,ins,img,ul,ol,li,code,more,spell,close,';

        if (!inst)
            return false;
        ed = inst;
        canvas = ed.canvas;
        name = ed.name;
        settings = ed.settings;
        html = '';
        theButtons = {};
        use = '';

        // set buttons
        if (settings.buttons)
            use = ',' + settings.buttons + ',';

        for (i in edButtons) {
            if (!edButtons[i])
                continue;

            id = edButtons[i].id;
            if (use && defaults.indexOf(',' + id + ',') != -1 && use.indexOf(',' + id + ',') == -1)
                continue;

            if (!edButtons[i].instance || edButtons[i].instance == inst) {
                theButtons[id] = edButtons[i];

                if (edButtons[i].html)
                    html += edButtons[i].html(name + '_');
            }
        }

        if (use && use.indexOf(',fullscreen,') != -1) {
            theButtons['fullscreen'] = new qt.FullscreenButton();
            html += theButtons['fullscreen'].html(name + '_');
        }


        if ('rtl' == document.getElementsByTagName('html')[0].dir) {
            theButtons['textdirection'] = new qt.TextDirectionButton();
            html += theButtons['textdirection'].html(name + '_');
        }

        ed.toolbar.innerHTML = html;
        ed.theButtons = theButtons;
    };
    $.fn.dwqa_answer_edit = function(e) {
        var evt = e || window.event;
        evt.preventDefault();
        var t = $(this),
            answer_container = t.closest('.dwqa-answer'),
            answer_content = answer_container.find('.dwqa-content'),
            current_content = answer_content.html().trim();


        if (t.data('on-editor')) {
            answer_editor.slideDown();
            remove_editor();
            return false;
        }

        if (current_answer_editor != answer_container && current_answer_editor != null) {
            remove_editor();
        }
        current_answer_editor = answer_container;

        if (getUserSetting('editor', 'tmce') == 'html') {
            setUserSetting('editor', 'tmce');
        }
        answer_container.find('.answer-edit-link .loading').css('display', 'inline-block');

        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-editor-init',
                answer_id: t.data('answer-id'),
                question: t.data('question-id')
            },
            complete: function() {
                answer_container.find('.answer-edit-link .loading').hide();
                answer_container.find('footer .answer-actions').hide();
                answer_editor.hide();
            },
            success: function(response, textStatus, xhr) {
                if (!response.success) {
                    return false;
                }
                var editor = $(unescape(response.data.editor)),
                    id = 'dwqa-custom-content-editor';
                editor.hide();
                answer_content.html(editor);
                $('#' + id).data('current-content', escape(current_content));


                var settings = tinyMCEPreInit.mceInit['dwqa-answer-question-editor'];

                settings.elements = id;
                settings.body_class = id + ' post-type-dwqa-answer';
                settings.editor_selector = id;
                //init tinymce
                tinymce.init(settings);
                editor.slideDown();
                t.data('on-editor', true);
            }
        });

    }
    /**
     * DWQA add content for mce
     * @param  string id      Identity of editor element
     * @param  string content New content add to tinymce
     */
    function dwqa_tinymce_add_content(content, where) {
        if (typeof(tinyMCE) == "object" && typeof(tinyMCE.execCommand) == "function") {
            var id = 'dwqa-answer-question-editor',
                ed = tinyMCE.get(id);
            content = content.trim();

            if (ed && !ed.isHidden()) {
                ed.setContent(content);
            } else {
                $('#' + id).val(content);
            }

            if (where) {
                where.html(answer_editor);
            } else {
                answers.append(answer_editor);
            };
            setTimeout(function() {
                $('#' + id + '-tmce').click();
                ed.remove();
                tinyMCE.execCommand('mceAddControl', false, id);
            }, 50);

        }
    }

    // Answer delete
    answers.delegate('.answer-delete', 'click', function(event) {
        event.preventDefault();
        if (!confirm(dwqa.answer_delete_confirm)) {
            return false;
        }
        var t = $(this),
            answer_id = t.data('answer-id'),
            wpnonce = t.data('nonce');
        t.closest('.dwqa-answer').fadeOut(300, function() {
            var answer_count = answers.find('.answer-count'),
                answer_old_count = answer_count.find('.digit'),
                answer_new_count = parseInt(answer_old_count.text()) - 1;
            if (answer_new_count > 0) {
                answer_old_count.text(answer_new_count);
            } else {
                answer_count.text('');
            }
            $(this).remove();
        });
        //Delete answer indatabase
        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-action-remove-answer',
                answer_id: answer_id,
                wpnonce: wpnonce
            }
        });

    });

    //Flag Answer
    answers.delegate('.dwqa-answer-report', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            answer_container = t.closest('.dwqa-answer')
            wpnonce = $(this).data('nonce'),
            answer_id = $(this).data('answer-id');

        if (confirm((answer_container.hasClass('answer-flagged-content') ? dwqa.flag.revert : dwqa.flag.text))) {

            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-action-flag-answer',
                    wpnonce: wpnonce,
                    answer_id: answer_id
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.status == 1) {

                            answer_container.find('.dwqa-content').prepend('<p class="answer-flagged-alert alert"><i class="fa fa-flag"></i>' + dwqa.flag.flag_alert + ' <strong class="answer-flagged-show ">' + dwqa.flag.flagged_show + '</strong></p>')
                            answer_container.addClass('answer-flagged-content');
                            answer_container.find('.dwqa-content .dwqa-content-inner').addClass('hide');
                            t.find('a').html('<i class="fa fa-flag"></i>' + dwqa.flag.label_revert);
                        } else {
                            answer_container.removeClass('answer-flagged-content');
                            answer_container.find('.answer-flagged-alert').remove();
                            answer_container.removeClass('answer-flagged-content');
                            answer_container.find('.dwqa-content .dwqa-content-inner').removeClass('hide');
                            t.find('a').html('<i class="fa fa-flag"></i>' + dwqa.flag.label);
                        }
                    }
                }
            });
        }
    });

    answers.delegate('.answer-flagged-alert', 'click', function(event) {
        event.preventDefault();
        var answerContent = $(this).closest('.dwqa-content').find('.dwqa-content-inner');
        answerContent.toggleClass('hide');
        if (answerContent.is(':visible')) {
            $(this).find('.answer-flagged-show').text(dwqa.flag.flagged_hide);
        } else {
            $(this).find('.answer-flagged-show').text(dwqa.flag.flagged_show);
        }
    });
    // Vote Best Answer
    $('.dwqa-best-answer').on('click', function(event) {
        event.preventDefault();
        var t = $(this);

        if (t.hasClass('active')) {
            if (t.data('ajax') != false) {
                $.ajax({
                    url: dwqa.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'dwqa-unvote-best-answer',
                        answer: t.data('answer'),
                        nonce: t.data('nonce')
                    },
                })
                    .always(function() {
                        t.removeClass('active');
                        document.location.href = document.location.href;
                    });
            }
        } else {
            $('.dwqa-best-answer').removeClass('active');
            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-vote-best-answer',
                    answer: t.data('answer'),
                    nonce: t.data('nonce')
                },
            })
                .always(function() {
                    t.addClass('active');
                    document.location.href = document.location.href;
                });
        }

    });

    $('[name="answer_notify"]').click(function() {
        if ($(this).is(':checked')) {
            $(".dwqa-answer-signin").removeClass('hide');
        } else {
            $('.dwqa-answer-signin').addClass('hide');
        }

    });

    $('.dwqa-container').delegate('.dwqa-change-privacy ul li', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            privacy = t.closest('.dwqa-privacy'),
            status = t.data('privacy'),
            post = privacy.data('post'),
            nonce = privacy.data('nonce');
        privacy.find('.dwqa-change-privacy ul li').removeClass('current');
        privacy.find('[name="privacy"]').val(status);
        privacy.find('.dwqa-current-privacy').html(t.find('a').html());
        t.addClass('current');

        if (privacy.data('type') == 'question' || privacy.data('type') == 'answer') {
            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-update-privacy',
                    nonce: nonce,
                    post: post,
                    status: status
                }
            })
                .done(function(resp) {
                    if (!resp.success) {
                        console.log('error');
                    } else {
                        window.location.href = window.location.href;
                    }
                });

        }
    });

    //View more Comment ==========================================================================
    $('.dwqa-container').delegate('.dwqa-comments-more-link', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            post = t.data('post');
        $.ajax({
            url: dwqa.ajax_url,
            type: 'GET',
            dataType: 'HTML',
            data: {
                action: 'dwqa-get-comments',
                post: post
            }
        })
            .done(function(resp) {
                if (resp && resp.length > 0) {
                    t.closest('.dwqa-comments-more').fadeOut('slow', function() {
                        $(this).remove();
                    });
                    var commentList = t.closest('.dwqa-comments').find('.dwqa-comment-list');
                    commentList.fadeOut('slow', function() {
                        $(this).html(resp).slideDown();
                    });
                }
            });

    });

    $('.dwqa-container').delegate('.dwqa-favourite', 'click', function(event) {
        event.preventDefault();
        var t = $(this);
        t.toggleClass('active');
        if (t.hasClass('active')) {
            t.attr('title', dwqa.unfollow_tooltip);
        } else {
            t.attr('title', dwqa.follow_tooltip);
        }

        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-follow-question',
                nonce: t.data('nonce'),
                post: t.data('post')
            }
        });

    });

    // Dropdown Toggle
    $('.dwqa-container').delegate('.dropdown-toggle', 'click', function(event) {
        event.preventDefault();
        var t = $(this);
        var parent = $(this).parent();
        $('.dwqa-container .dwqa-btn-group').each(function() {

            if ($(this).get(0) == parent.get(0)) {
                return false;
            }
            $(this).removeClass('open');
        });
        parent.toggleClass('open');
    });

    //Document On Click ===========================================================================
    $(document).click(function(e) {
        if (!$(e.target).is('.dwqa-comment-form, .dwqa-comment-form *') && current_form && current_form.length > 0) {
            current_form.find('.dwqa-form-submit').hide();
            current_form.find('textarea').height(current_form.find('textarea').css('line-height').replace('px', ''));
        }

        if (!$(e.target).is('.dwqa-container .dropdown-toggle,.dwqa-container .dropdown-toggle *')) {
            $('.dwqa-container .dropdown-toggle').each(function() {
                $(this).parent().removeClass('open');
            });
        }
    });
});