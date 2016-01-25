(function($){

	// Follow and Unfollow Question
	$( 'span.dwqa_follow, span.dwqa_unfollow' ).click( function(e) {
		e.preventDefault();
		var t = $(this);
		data = {
			action: 'dwqa-follow-question',
			nonce: t.parent().data('nonce'),
			post: t.parent().data('post')
		}

		$.ajax({
			url: dwqa.ajax_url,
			data: data,
			type: 'POST',
			dataType: 'json',
			success: function(data) {
				if ( true == data.success ) {
					if ('followed' === data.data.code) {
						t.parent().addClass('active');
					}

					if ('unfollowed' === data.data.code) {
						t.parent().removeClass('active');
					}
				}
			}
		});
	});

	$( 'a.dwqa_edit_question' ).click(function($){
		''
	});
})(jQuery);