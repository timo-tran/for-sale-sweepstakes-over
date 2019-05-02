$(document).ready(function () {
    var slider = $("#lightSlider").lightSlider({
        item: 5,
        loop: true,
        pager: false,
        controls: false,
    });

    $(".left.carousel-control").click(function () {
        slider.goToPrevSlide();
    });

    $(".right.carousel-control").click(function () {
        slider.goToNextSlide();
    });

    $('#lightSlider').viewer({
        movable: false,
        shown: function () {
            $(".viewer-canvas").click(function () {
                $('.viewer-button.viewer-close').click();
            });

            $(".viewer-canvas img").click(function (e) {
                e.stopPropagation();
            });
        }
    });

    $(".rm-listing-call-seller").click(function () {
        $(this).find('div').toggle();
    });

    $(".rm-listing-call-seller").find("a").click(function (e) {
        e.stopPropagation();
    });

    var masonryOptions = {
        itemSelector: '.grid-item-similar',
        columnWidth: '.grid-sizer-similar',
        percentPosition: true
    };

    var $grid    = $('.grid-similar');
    var $masonry = $grid.masonry(masonryOptions);

    $masonry.imagesLoaded().progress(function () {
        $masonry.masonry('layout');
    });

    $grid.infinitescroll({
            infid: 0,
            navSelector: ".navigation",
            nextSelector: ".navigation a",
            itemSelector: ".grid-item-similar",
            loading: {
                msgText: '<em>Loading the next set of similar listings...</em>',
                finishedMsg: 'No more similar listings to load.',
                selector: "#rm-loading"
            }
        },

        function (newElements) {
            var $newElems = $(newElements).css({opacity: 0});
            $newElems.imagesLoaded(function () {
                $newElems.animate({opacity: 1});
                $grid.append($newElems).masonry('appended', $newElems).masonry('layout');
            });
        }
    );
});
