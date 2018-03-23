jQuery(document).ready(function($) {

    function hide_item(element,element2) {
        if ($(element).is(':checked')) {
            $(element2).attr('disabled','disabled');
            if ($(element2).is(':checked')) {
                $(element2).removeAttr('checked');
            }
        } else {
            $(element2).removeAttr('disabled');
        }
    }

    hide_item('#dwqa_options_dwqa_show_all_answers','#dwqa_setting_answers_per_page');
    hide_item('#dwqa_options_dwqa_disable_question_status','#dwqa_options_enable_show_status_icon');

    $('#dwqa_options_dwqa_show_all_answers').on('change',function() {
        hide_item(this,'#dwqa_setting_answers_per_page');
    });

    $('#dwqa_options_dwqa_disable_question_status').on('change',function(){
        hide_item(this,'#dwqa_options_enable_show_status_icon');
    });

    $('#dwqa-message').on('click', function(e){
        document.cookie = "qa-pro-notice=off";
    });
});