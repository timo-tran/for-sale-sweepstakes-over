// var liveSearchUrl declared in parent file

var masonryOptions = {
    itemSelector: '.grid-item',
    columnWidth: '.grid-sizer',
    percentPosition: true
};

$(document).ready(function () {
    var $typeahead = $(".js-typeahead");

    var $grid            = $('.grid');
    var $grid_results    = $('.grid_results');
    var $masonry         = $grid.masonry(masonryOptions);
    var $masonry_results = $grid_results.masonry(masonryOptions);

    $masonry.imagesLoaded().progress(function () {
        $masonry.masonry('layout');
    });

    $masonry_results.imagesLoaded().progress(function () {
        $masonry_results.masonry('layout');
    });

    $typeahead.keydown(function () {
        if ($(this).val().length >= 2) {
            $grid.infinitescroll('pause');
            $grid.hide();

            $grid_results.show();
            $grid_results.infinitescroll('resume');
        } else {
            $grid.infinitescroll('resume');
            $grid.show();

            $grid_results.hide();
            $grid_results.infinitescroll('pause');
        }
    });

    $.typeahead({
        input: ".js-typeahead",
        order: "asc",
        minLength: 3,
        display: 'title',
        dynamic: true,
        source: {
            listings: {
                ajax: {
                    url: liveSearchUrl,
                    data: {
                        searchText: '{{query}}'
                    },
                    method: 'post',
                    dataType: 'html',
                    callback: {
                        done: function (data) {
                            putSearchResults($grid_results, $typeahead, data);
                        }
                    }
                }
            }
        }
    });
});

function putSearchResults($grid_results, $typeahead, data) {
    var inifiteScrollGridResultOptions = {
        infid: 1,
        navSelector: ".navigation-results",
        nextSelector: ".navigation-results a",
        itemSelector: ".grid-item",
        loading: {
            msgText: '<em>Loading the next set of search results...</em>',
            finishedMsg: 'No more listings to load.',
            selector: "#rm-loading"
        },
        state: {
            isPaused: false,
            isDone: false,
            isDestroyed: false,
            isDuringAjax: false
        },
        path: function (page) {
            return liveSearchUrl + '/' + $typeahead.val().replace(/ /g, '+') + "/" + page;
        }
    };

    var $elementsToRemove = $(".grid_results .grid-item");
    $grid_results.masonry('remove', $elementsToRemove).masonry('layout');

    var $data = $(data).css({opacity: 0});
    $data.imagesLoaded(function () {
        $data.animate({opacity: 1});
        $grid_results.append($data).masonry('appended', $data).masonry('layout');

        $grid_results.infinitescroll('destroy');
        $grid_results.removeData('infinitescroll');
        $grid_results.infinitescroll(inifiteScrollGridResultOptions,
            function (newElements) {
                var $newElems = $(newElements).css({opacity: 0});
                $newElems.imagesLoaded(function () {
                    $newElems.animate({opacity: 1});
                    $grid_results.masonry('appended', $newElems, true);
                });
            }
        );
    });
}
