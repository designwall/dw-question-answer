(function($){
	$('#question-title').autocomplete({
		appendTo: '.dwqa-search',
		source: function( request, resp ) {
			$.ajax({
				url: dwqa.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'dwqa-auto-suggest-search-result',
					title: request.term,
					nonce: $('#question-title').data('nonce')
				},
				success: function( data ) {
					console.log( data );
					resp( $.map( data.data, function( item ) {
						if ( true == data.success ) {
							return {
								label: item.title,
								value: item.title,
								url: item.url,
							}
						}
					}))
				}
			});
		},
		select: function( e, ui ) {
			keycode = e.which || e.keyCode;

			if ( keycode == 13 ) {
				return true;
			} else {
				if ( ui.item.url ) {
					window.open( ui.item.url );
				}
			}
		},
		open: function( e, ui ) {
			var acData = $(this).data( 'uiAutocomplete' );
			acData.menu.element.addClass('dwqa-autocomplete').find('li').each(function(){
				var $self = $(this),
					keyword = $.trim( acData.term ).split(' ').join('|');
					$self.html( $self.text().replace( new RegExp( "(" + keyword + ")", "gi" ), '<span class="dwqa-text-highlight">$1</span>' ) );
			});
		}
	})
})(jQuery);