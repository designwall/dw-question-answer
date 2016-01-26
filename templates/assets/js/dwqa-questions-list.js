jQuery(function($){

	// Search form
	$('form#dwqa-search input').autocomplete({
		source: function( request, resp ) {
			$.ajax({
				url: dwqa.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'dwqa-auto-suggest-search-result',
					title: request.term,
					nonce: $('form#dwqa-search #_dwqa_filter_nonce').val()
				},
				success: function( data ) {
					resp( $.map( data.data, function( item ) {
						return {
							label: item.title,
							value: item.title,
							url: item.url,
						}
					}))
				}
			});
		},
		select: function( e, ui ) {
			if ( ui.item.url !== '#' ) {
				window.location.href = ui.item.url;
			} else {
				return true;
			}
		},
		open: function( e, ui ) {
			var acData = $(this).data( 'uiAutocomplete' );
			acData.menu.element.find('li').each(function(){
				var $self = $(this),
					keyword = $.trim( acData.term ).split(' ').join('|');
					$self.html( $self.text().replace( new RegExp( "(" + keyword + ")", "gi" ), '<span class="dwqa-text-highlight">$1</span>' ) );
			});
		}
	});
	
});