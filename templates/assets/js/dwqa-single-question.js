(function($){

	// Follow and Unfollow Question
	$('#dwqa-favorites').change(function(e){
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
				t.parent().addClass('processing');
				if (true == data.success){
					if ('followed' === data.data.code){
						t.next().text(data.data.code);
					}

					if ('unfollowed' === data.data.code){
						t.parent().removeClass('active');
					}
				}
			}
		});
	});
})(jQuery);