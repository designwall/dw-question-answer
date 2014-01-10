jQuery(function($) {
    function URLToArray() {
        var url = $(location).attr('href');
        var request = {};
        var pairs = url.substring(url.indexOf('?') + 1).split('&');

        if (url.indexOf('?') >= 0) {
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split('=');
                request[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
            }
            return request;
        } else {
            return (new Array())
        }
    }

    function getURLParameter(name) {
        var param = decodeURI(
            (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1]
        );
        return param == 'null' ? null : param;
    }

    var filter_bar = $('.filter-bar'),
        container = $('.dwqa-list-question'),
        old_type = 'all',
        type = getURLParameter('orderby') ? getURLParameter('orderby') : 'all',
        order = 'DESC',
        pagenavi_box = filter_bar.find('#dwqa_filter_posts_per_page'),
        posts_per_page = 10,
        filter_plus = getURLParameter('status');
    filter_plus = filter_plus ? filter_plus : 'all',
    nonce = filter_bar.find('#_filter_wpnonce').val(),
    category_select = $('.filter-bar #dwqa-filter-by-category'),
    category = getURLParameter(dwqa.question_category_rewrite),
    tag_select = $('.filter-bar #dwqa-filter-by-tags'),
    tags = getURLParameter(dwqa.question_tag_rewrite),
    paged = getURLParameter('paged'),
    paged = paged ? paged : $('#dwqa-paged').val(),
    search_form = $('.dwqa-search-form'),
    title = null, tags = 'null';

    var get_filter_args = function() {
        posts_per_page = pagenavi_box.val();
        if (category_select.is('ul')) {
            category = parseInt(category_select.data('selected')) > 0 ? category_select.data('selected') : 'all';
        } else {
            category = category_select.val();
        }

        if (tag_select.is('ul')) {
            tags = parseInt(tag_select.data('selected')) > 0 ? tag_select.data('selected') : 0;
        } else {
            tags = tag_select.val();
        }

        title = search_form.find('.dwqa-search-input').val();
        if (($.browser.version == "9.0" || $.browser.version == "8.0") && title == search_form.find('.dwqa-search-input').attr('placeholder')) {
            title = '';
        }
    }
    get_filter_args();
    var $filter = null;
    var start_filter = function() {

        get_filter_args();
        container.find('.questions-list').css('opacity', 0.3);
        container.find('.loading').show();
        if ($filter != null) {
            $filter.abort();
        }
        $url_args = URLToArray();

        if (filter_plus != 'all') {
            $url_args['status'] = filter_plus;
        } else {
            delete($url_args['status']);
        }

        if (category != 'all' && typeof category != 'undefined') {
            $url_args[dwqa.question_category_rewrite] = category;
        } else {
            delete($url_args[dwqa.question_category_rewrite]);
        }
        if (tags) {
            $url_args[dwqa.question_tag_rewrite] = tags;
        } else {
            delete($url_args[dwqa.question_tag_rewrite]);
        }
        if (type != 'all') {
            $url_args['orderby'] = type;
        } else {
            delete($url_args['orderby']);
        }

        if (paged > 1) {
            $url_args['paged'] = paged;
        } else {
            delete($url_args['paged']);
        }

        var $paramString = '';
        for (var i in $url_args) {
            if (!i) {
                continue;
            }
            $paramString += '&' + i + '=' + $url_args[i];
        }
        if ($paramString.substring(0, 1) == '&') {
            $paramString = $paramString.substring(1, $paramString.length);
        }
        $.ajax({
            url: dwqa.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'dwqa-get-questions-permalink',
                params: $paramString
            }
        })
            .done(function(resp) {
                if (resp.success) {
                    if (history.pushState)
                        window.history.pushState(null, document.title, resp.data.url);
                }
            });


        $filter = $.ajax({
            url: dwqa.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'dwqa-filter-question',
                nonce: nonce,
                type: type,
                order: order,
                old_type: old_type,
                posts_per_page: posts_per_page,
                filter_plus: filter_plus,
                category: category,
                paged: paged,
                tags: tags,
                title: title
            }
        })
            .done(function(resp) {
                container.find('.questions-list').css('opacity', 1);
                if (resp.success) {
                    container.find('.questions-list').hide().html(resp.data.results).fadeIn();
                    $('.dwqa-list-question .pagination').html(resp.data.pagenavi);
                }
                $filter = null;
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                container.find('.questions-list').css('opacity', 1);
                $filter = null;
                if (textStatus === "timeout") {
                    //do something on timeout
                }
            })
            .always(function() {
                container.find('.loading').hide();
            });

    }

    var offset = 1;

    if (filter_plus != 'all' || category != 'all' || tags != 0) {
        start_filter();
    }

    filter_bar.find('.order li').click(function(event) {
        event.preventDefault();
        var t = $(this);

        if ($filter != null) {
            return false;
        }
        if (!t.hasClass('active')) {
            type = t.data('type');
            old_type = 'all';
            $('.filter-bar .order li').each(function(i) {
                var $this = $(this);
                if ($this.hasClass('active')) {
                    old_type = $this.data('type');
                    $this.find('.fa-sort').removeClass('fa-sort-up fa-sort-down');
                    $this.removeClass('active');
                }
            });
            t.addClass('active');
        }



        var icon_sort = t.find('.fa-sort');

        if (icon_sort.hasClass('fa-sort-up')) {
            icon_sort.removeClass('fa-sort-up').addClass('fa-sort-down');
            order = 'DESC';
        } else {
            icon_sort.removeClass('fa-sort-down').addClass('fa-sort-up');
            order = 'ASC';
        }

        start_filter();

    });



    filter_bar.find('.filter-by-category .current-select').click(function(event) {
        event.preventDefault();
        var t = $(this);
        filter_bar.find('.filter-by-category .category-list').slideToggle(100);
        filter_bar.find('.filter-by-category').toggleClass('open');
    });

    filter_bar.find('.filter-by-category .category-list li').click(function(event) {
        event.preventDefault();
        filter_bar.find('.filter-by-category .current-select').text($(this).text());
        filter_bar.find('.filter-by-category .category-list').slideUp(50);
        filter_bar.find('.filter-by-category').removeClass('open');

        category = 'all';
        if ($(this).data('cat')) {
            category = $(this).data('cat');
        }
        category_select.data('selected', category);

        start_filter();

    });

    //Filter by tags
    filter_bar.find('.filter-by-tags .current-select').click(function(event) {
        event.preventDefault();
        var t = $(this);
        filter_bar.find('.filter-by-tags .category-list').slideToggle(100);
        filter_bar.find('.filter-by-tags').toggleClass('open');
    });

    filter_bar.find('.filter-by-tags .category-list li').click(function(event) {
        event.preventDefault();
        filter_bar.find('.filter-by-tags .current-select').text($(this).text());
        filter_bar.find('.filter-by-tags .category-list').slideUp(50);
        filter_bar.find('.filter-by-tags').removeClass('open');

        tags = 0;
        if ($(this).data('cat')) {
            tags = $(this).data('cat');
        }
        tag_select.data('selected', tags);

        start_filter();

    });

    $(document).click(function(e) {
        if (!$(e.target).is('.filter-by-category, .filter-by-category *')) {
            filter_bar.find('.filter-by-category').removeClass('open');
            filter_bar.find('.filter-by-category .category-list').slideUp('slow');
        }
    });

    filter_bar.find('.filter .status ul li').click(function(event) {
        event.preventDefault();
        var t = $(this);
        if (t.hasClass('active')) {
            return false;
        }
        filter_bar.find('.filter .status ul li').each(function(index) {
            $(this).removeClass('active');
        });
        t.addClass('active');

        filter_plus = t.data('type');
        paged = 1;
        start_filter();
    });



    $('.dwqa-list-question').delegate('.pagination li', 'click', function(event) {
        event.preventDefault();
        var t = $(this),
            pages = $('.dwqa-list-question .pagination ul').data('pages');

        if (t.hasClass('hide') || t.hasClass('active') || t.hasClass('dot')) {
            return false;
        }
        $(window).scrollTop($('.questions-list').scrollTop());

        var current = $('.dwqa-list-question .pagination ul li.active'),
            current_page = parseInt(current.text());

        // change paged
        if (t.hasClass('next')) {
            if (current_page + 1 > pages) {
                return false;
            }
            paged = current_page + 1;
        } else if (t.hasClass('prev')) {
            if (current_page - 1 < 1) {
                return false;
            }
            paged = current_page - 1;
        } else {
            paged = parseInt(t.text());
        }

        // Move active page navi to index
        current.removeClass('active');
        start_filter();
    });

    //Search box
    var $search = null,
        $search_submit = false,
        timeout = false,
        canEnter = false;
    $('.dwqa-search-form').on('input', '.dwqa-search-input', function(event) {
        if (timeout) {
            clearTimeout(timeout);
            timeout = false;
        }
        var t = $(this);
        timeout = setTimeout(function() {
            if (t.val().length > 2) {
                filter_bar.animate({
                    'opacity': 0.3
                }, 300);
                $('.questions-list').animate({
                    'opacity': 0.3
                }, 300);
                $('.archive-question-footer').animate({
                    'opacity': 0.3
                }, 300);
                $('.dwqa-search-form').find('.fa-search').hide();

                get_filter_args();
                if ($search) {
                    if ('object' == typeof $search) {
                        $search.abort();
                        $('.dwqa-search-form').find('.fa-remove').hide();
                    }
                }
                if ($search_submit) {
                    return false;
                }
                $('.dwqa-search-form .dwqa-search-loading').show();
                $search = $.ajax({
                    url: dwqa.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'dwqa-auto-suggest-search-result',
                        nonce: nonce,
                        title: title
                    }
                })
                    .done(function(resp) {
                        var $results = '';
                        if (resp.success) {
                            $results = '<ul>' + resp.data.html + '</ul>';
                            if (resp.data.number > 5) {
                                $results += '<span>' + dwqa.search_enter_get_more + '</span>';
                                canEnter = true;
                            } else {
                                canEnter = false;
                            }
                        } else {
                            $results += '<span>' + dwqa.search_not_found_message + '</span>';
                        }
                        if (t.parent().find('.search-results-suggest').length > 0) {
                            t.parent().find('.search-results-suggest').html($results);
                        } else {
                            t.after('<div class="search-results-suggest">' + $results + '</div>');
                        }
                    })
                    .always(function() {
                        $('.dwqa-search-form .dwqa-search-loading').hide();
                        $('.dwqa-search-form').find('.fa-remove').show();
                    });


            } else {
                filter_bar.animate({
                    'opacity': 1
                }, 300);
                if ($search) {
                    if ('object' == typeof $search) {
                        $search.abort();
                        $('.dwqa-search-form').find('.fa-remove').hide();
                    }
                }
                $('.questions-list').animate({
                    'opacity': 1
                }, 300);
                $('.archive-question-footer').animate({
                    'opacity': 1
                }, 300);
                $('.dwqa-search-form').find('.fa-search').show();
                $('.dwqa-search-form').find('.fa-remove').hide();
                $('.dwqa-search-form').find('.dwqa-search-loading').hide();
                $('.dwqa-search-form').find('.search-results-suggest').remove();
                if ($search_submit) {
                    start_filter();
                    $search_submit = false;
                }
            }
            clearTimeout(timeout);
            timeout = false;
        }, 700);
    });
    $('.dwqa-search-form').on('click', '.fa-remove', function(event) {
        event.preventDefault();
        $('.dwqa-search-form .dwqa-search-input').val('');
        filter_bar.animate({
            'opacity': 1
        }, 300);
        $('.questions-list').animate({
            'opacity': 1
        }, 300);
        $('.archive-question-footer').animate({
            'opacity': 1
        }, 300);
        $('.dwqa-search-form').find('.fa-search').show();
        $('.dwqa-search-form').find('.fa-remove').hide();
        $('.dwqa-search-form').find('.search-results-suggest').remove();
    });
    $('.dwqa-search-form').on('submit', function(event) {
        event.preventDefault();
        var t = $(this);
        if (t.find('.dwqa-search-input').length > 0 && canEnter) {
            $search_submit = true;
            if ($search) {
                if ('object' == typeof $search) {
                    $search.abort();
                    $('.dwqa-search-form').find('.fa-remove').hide();
                    $('.dwqa-search-form .dwqa-search-loading').hide();
                    $('.dwqa-search-form').find('.fa-search').show();
                }
            }
            $('.dwqa-search-form').find('.search-results-suggest').remove();
            filter_bar.animate({
                'opacity': 1
            }, 300);
            $('.questions-list').animate({
                'opacity': 1
            }, 300);
            $('.archive-question-footer').animate({
                'opacity': 1
            }, 300);

            start_filter();
            canEnter = false;
        }
    });
});