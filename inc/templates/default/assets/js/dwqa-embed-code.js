jQuery('.dwqa-question-stand-alone .show-more-end,.dwqa-question-stand-alone .dwqa-read-more').on('click', function(event) {
    event.preventDefault();
    var parent = jQuery(this).closest('.dwqa-question-stand-alone');
    var content = parent.find('.dwqa-content .dwqa-content-inner');
    content.toggleClass('dim');
    if (content.hasClass('dim')) {
        parent.find('.dwqa-read-more').text('-- More --');
    } else {
        parent.find('.dwqa-read-more').text('-- Less --');
    }

});