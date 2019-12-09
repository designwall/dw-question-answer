(function($){

	// Follow and Unfollow Question
	$('#dwqa-favorites').on('change',function(e){
		e.preventDefault();
		var t = $(this);

		// prevent action if is processing
		if (t.parent().hasClass('processing')) {
			return false;
		}

		t.parent().addClass('processing');

		data = {
			action: 'dwqa-follow-question',
			nonce: t.data('nonce'),
			post: t.data('post')
		}

		$.ajax({
			url: dwqa.ajax_url,
			data: data,
			type: 'POST',
			dataType: 'json',
			success: function(data){
				t.parent().removeClass('processing');
			}
		});
	});


	// Vote
	var update_vote = false;
	$( '.dwqa-vote-up' ).on('click', function(e){
		e.preventDefault();
		var t = $(this),
			parent = t.parent(),
			id = parent.data('post'),
			nonce = parent.data('nonce'),
			vote_for = 'question';

		if ( parent.hasClass( 'dwqa-answer-vote' ) ) {
			vote_for = 'answer';
		}

		var data = {
			action: 'dwqa-action-vote',
			vote_for: vote_for,
			nonce: nonce,
			post: id,
			type: 'up'
		}

		$.ajax({
			url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function( data ) {
            	console.log(data);
            	if (data.success) {
                    parent.find('.dwqa-vote-count').text(data.data.vote);
                }
            },
			error:function( data ) {
				console.log("error",data);
            	
            },
		});
	});

	$( '.dwqa-vote-down' ).on('click', function(e){
		e.preventDefault();
		var t = $(this),
			parent = t.parent(),
			id = parent.data('post'),
			nonce = parent.data('nonce'),
			vote_for = 'question';

		if ( parent.hasClass( 'dwqa-answer-vote' ) ) {
			vote_for = 'answer';
		}

		var data = {
			action: 'dwqa-action-vote',
			vote_for: vote_for,
			nonce: nonce,
			post: id,
			type: 'down'
		}

		$.ajax({
			url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function( data ) {
				console.log(data);
            	if (data.success) {
                    parent.find('.dwqa-vote-count').text(data.data.vote);
                }
            },
			error:function( data ) {
				console.log("error",data);
            	
            },
		});
	});

	// delete question
	$( '.dwqa_delete_question, .dwqa_delete_answer, .dwqa-delete-comment' ).on('click', function(e) {
		var message = confirm( 'Are you sure to delete this question.' );

		if ( !message ) {
			e.preventDefault();
		}
	});

	// change question status
	$('#dwqa-question-status').on('change', function(e){
		var t = $(this),
			nonce = t.data('nonce'),
			post = t.data('post'),
			status = t.val(),
			data = {
				action: 'dwqa-update-privacy',
				post: post,
				nonce: nonce,
				status: status
			};

		$.ajax({
			url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(data) {
            	if ( data.success == false ) {
            		alert( data.data.message );
            	} else {
            		window.location.reload();
            	}
            }
		})
	});

	var originHeight, current_form;
	$('.dwqa-anonymous-fields').hide();
	$('.dwqa-comment-form #comment').on('focus',function(e){
		var t = $(this);

        //Collapse all comment form
        if (current_form && t.get(0) != current_form.get(0)) {
            $('[id^=comment_form_]').each(function(index, el) {
                var comment_form = $(this);
                comment_form.find('.dwqa-form-submit').hide();
                comment_form.find('textarea').height(comment_form.find('textarea').css('line-height').replace('px', ''));
            });
        }
        current_form = t.closest('.dwqa-comment-form');
        var lineHeight = parseInt(t.css('line-height').replace('px', '')),
            thisPadding = parseInt(t.css('padding-top').replace('px', '')),
            defaultHeight = (lineHeight + thisPadding) * 3;

        originHeight = t.height();
        var changeHeight = function() {
            var matches = t.val().match(/\n/g);
            var breaks = matches ? matches.length : 0;
            t.height(defaultHeight);
        }

        changeHeight();
        $(this).closest('form').addClass( 'dwqa-comment-show-button' ).find('.dwqa-anonymous-fields').slideDown();
        current_form.find('.dwqa-form-submit').show();
	});

})(jQuery);