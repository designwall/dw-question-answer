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
            }
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
            	if (data.success) {
                    parent.find('.dwqa-vote-count').text(data.data.vote);
                }
            }
		});
	});


	

})(jQuery);