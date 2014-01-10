String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
}

function _e(event, obj, fn) {
    jQuery(obj)[fn](event);
}

jQuery(function($) {
    var answers = $('#answers'),
        answer_editor = $('#add-answer');

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


    //Vote For Question
    $('.question-vote .vote').on('click', function(event) {
        event.preventDefault();
        var t = $(this);
        t.closest('span').hide().html('Thank you for your feedback').fadeIn();

        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'html',
            data: {
                action: 'dwqa-action-vote',
                vote_for: 'question',
                nonce: t.data('nonce'),
                question_id: t.data('question'),
                type: t.data('vote')
            }
        });

    });

    // Vote for Answer 
    $('.answer-vote .vote').click(function(event) {
        event.preventDefault();
        var t = $(this),
            count = t.closest('.answer-vote').find('.vote-count'),
            voted = count.data('voted');

        point = parseInt(count.text().replace(/[^0-9\-]/, ''));
        if (t.hasClass('vote-up')) {
            if (voted == 'up') {
                return false;
            } else if (voted == 'down') {
                point += 2;
            } else {
                point++;
            }
            voted = 'up';
        } else if (t.hasClass('vote-down')) {
            if (voted == 'down') {
                return false;
            } else if (voted == 'up') {
                point -= 2;
            } else {
                point--;
            }
            voted = 'down';
        }
        count.data('voted', voted);
        count.text(point > 0 ? '+' + point : point);

        var container = t.closest('.answer-vote');
        $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-action-vote',
                vote_for: 'answer',
                nonce: container.data('nonce'),
                answer_id: container.data('answer'),
                type: voted
            }
        });

    });


    // Change single status
    $('.change-question-status').click(function(event) {
        event.preventDefault();
        var t = $(this);
        t.find('ul').slideToggle();
        t.toggleClass('open');
    });

    $('.change-question-status ul li').click(function(event) {
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
    $(document).click(function(event) {
        if (!$(event.target).is('.change-question-status, .change-question-status *')) {
            $('.change-question-status').removeClass('open').find('ul').slideUp('slow');
        }
    });
    //Answer Editor Submit 
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

    var onSubmitComment = false;
    $('[id^=comment_form_]').on('submit', function(event) {
        event.preventDefault();

        var t = $(this),
            contentField = t.find('textarea[name="comment"]'),
            content = contentField.val().trim().replace(/\n/g, '<br>');

        if (content.length <= 2) {
            var message = dwqa.error_missing_comment_content;
            if (content.length > 0) {
                message = dwqa.error_not_enought_length;
            }
            if (t.parent().find('.alert-error').length > 0) {
                t.find('.alert-error').fadeIn();
            } else {
                t.before('<div class="alert alert-error">' + message + '</div>');
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
                    comment_parent: t.find('[name="comment_parent"]').val()
                },
                complete: function() {
                    onSubmitComment = false;
                },
                success: function(data, textStatus, xhr) {
                    var respond = t.closest('#respond'),
                        commentList = respond.prev('.commentlist');

                    if (commentList.length > 0) {
                        commentList.append(data.data.html);
                    } else {
                        var commentList = '<ol class="commentlist">' + data.data.html + '</ol>';
                        respond.before(commentList);
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
        current_form = t.closest('#respond');
        current_form.find('.form-submit').slideDown();
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
        t.bind('keyup change', function(event) {
            changeHeight();
        });

    });


    $(document).click(function(e) {
        if (!$(e.target).is('#respond, #respond *') && current_form && current_form.length > 0) {
            current_form.find('.form-submit').slideUp();
            current_form.find('textarea').height(current_form.find('textarea').css('line-height').replace('px', ''));
        }
    });
    //Comment update task
    $('#comments, .answers-list, .commentlist').delegate('.comment-edit-link', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            comment_container = t.closest('li'),
            comment_content = comment_container.find('.comment-content'),
            status = t.data('edit'),
            edit_content;

        if (typeof status == 'undefined' || !status) {
            t.data('edit', 1);
            edit_content = $('<div class="comment-edit-container"><textarea cols="50" rows="1" aria-required="true" class="comment-edit-field" data-current-content="' + escape(comment_content.html()) + '" data-comment-id="' + t.data('comment-id') + '" >' + comment_content.html().trim().replace(/\<br\>/g, "\n") + '</textarea><button class="comment-edit-submit-button btn btn-small">' + dwqa.comment_edit_submit_button + '</button>' + t[0].outerHTML + '</div>');
            t.hide();
            var cancel_link = edit_content.find('.comment-edit-link');
            cancel_link.text(dwqa.comment_edit_cancel_link).attr('class', 'cancel-link');
            cancel_link.bind('click', function(event) {
                event.preventDefault();
                edit_content = unescape(comment_container.find('.comment-edit-field').data('current-content'));
                t.data('edit', 0).css('display', 'inline-block');
                comment_content.html(edit_content);
            });
        }
        comment_content.html(edit_content);
    });

    $('#comments, .answers-list, .commentlist').delegate('.comment .comment-edit-submit-button', 'click', function(event) {
        event.preventDefault();
        var comment_container = $(this).closest('li'),
            edit_content,
            edit_link = comment_container.find('.comment-edit-link');
        edit_content = comment_container.find('.comment-edit-field').val();
        if (edit_content.length <= 0) {
            return false;
        }
        var comment_id = comment_container.find('.comment-edit-field').data('comment-id');
        comment_container.find('.comment-content').html(htmlForTextWithEmbeddedNewlines(edit_content));
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

    //Delete comment 
    $('.dwqa-container').delegate('.comment-delete-link', 'click', function(event) {
        event.preventDefault();

        var t = $(this),
            comment_type = t.data('comment-type'),
            comment_count = $('.dwqa-' + comment_type + ' .' + comment_type + '-comment .comment-count');

        if (confirm(dwqa.comment_delete_confirm)) {
            t.closest('li').fadeOut('slow', function() {
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
                },
            });

        }
    });
    //End delete comment
    //Answer edit
    // answers.delegate('.answer-edit-link', 'click', function(event){
    //     event.preventDefault();
    //     dwqa_answer_edit(this);
    // });

    var current_answer_editor = null;
    $('#comments, .answers-list, .commentlist').delegate('.answer-edit-cancel', 'click', function(event) {
        event.preventDefault();
        answer_editor.fadeIn();
        current_answer_editor.find('footer .answer-actions').show();
        remove_editor();
    });


    var remove_editor = function() {
        if (!current_answer_editor) {
            return false;
        }
        var content = $('#dwqa-custom-content-editor').data('current-content'),
            edit_link = current_answer_editor.find('.answer-edit-link');
        current_answer_editor.find('.entry-content').html(unescape(content));

        edit_link.find('a').text('Edit');
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
            answer_content = answer_container.find('.entry-content'),
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

                // //init quicktags
                // var qt = quicktags({
                //     id      : id,
                //     buttons : tinyMCEPreInit.qtInit['dwqa-answer-question-editor']['buttons']
                // });


                settings.elements = id;
                settings.body_class = id + ' post-type-dwqa-answer';
                settings.editor_selector = id;
                //init tinymce
                tinymce.init(settings);
                // dwqa_buttonsInit( qt );
                editor.slideDown();
                t.find('a').text('Cancel');
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

    //Flag answer
    answers.delegate('.answer-flag', 'click', function(event) {
        event.preventDefault();
        var confirm_text = $(this).find('a').text() == dwqa.flag.label ? dwqa.flag.text : dwqa.flag.revert;
        if (confirm(confirm_text)) {
            var t = $(this),
                wpnonce = $(this).data('nonce'),
                answer_id = $(this).data('answer-id'),
                answer_container = t.closest('.dwqa-answer');
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

                            answer_container.find('.entry-content').before('<p class="answer-flagged-alert alert"><i class="icon-flag"></i>' + dwqa.flag.flag_alert + ' <strong class="answer-flagged-show ">' + dwqa.flag.flagged_show + '</strong></p>')
                            answer_container.addClass('answer-flagged-content');
                            t.find('a').text(dwqa.flag.label_revert);
                        } else {
                            answer_container.find('.answer-flagged-alert').remove();
                            answer_container.removeClass('answer-flagged-content');
                            t.find('a').text(dwqa.flag.label);
                        }
                    }
                }
            });
        }
    });

    answers.delegate('.answer-flagged-show', 'click', function(event) {
        event.preventDefault();
        if ($(this).text() == dwqa.flag.flagged_show) {
            $(this).text(dwqa.flag.flagged_hide);
        } else {
            $(this).text(dwqa.flag.flagged_show);
        }
        var answer = $(this).closest('.answer-flagged-alert').next('.answer-flagged-content .entry-content');
        if (!answer.is(":visible")) {
            answer.show().css('background-color', 'rgb(255, 255, 220)').animate({
                'background-color': 'transparent'
            }, 1000);
        } else {
            answer.hide();
        }
    });
    $('.entry-vote-best').on('click', function(event) {
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
            $('.entry-vote-best').removeClass('active');
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
});