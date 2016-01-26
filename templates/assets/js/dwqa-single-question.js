(function($){

	// Follow and Unfollow Question
	$('span.dwqa_follow, span.dwqa_unfollow').click(function(e){
		e.preventDefault();
		var t = $(this);

		// prevent action if is processing
		if (t.parent().hasClass('processing')) {
			return false;
		}

		t.parent().addClass('processing');

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
			success: function(data){
				t.parent().addClass('processing');
				if (true == data.success){
					if ('followed' === data.data.code){
						t.parent().addClass('active');
					}

					if ('unfollowed' === data.data.code){
						t.parent().removeClass('active');
					}
				}
			}
		});
	});
})(jQuery);