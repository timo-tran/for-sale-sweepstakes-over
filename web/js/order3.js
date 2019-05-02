$(document).ready(function() {
    var giftofspeed = document.createElement('link');
    giftofspeed.rel = 'stylesheet';
    giftofspeed.href = 'https://fonts.googleapis.com/css?family=Alfa+Slab+One|Gentium+Book+Basic:400,400i,700|Poppins:400,700,900|Open+Sans:400,500,700|Roboto:300,400,700,900|Roboto+Slab:300,400|Oswald:400,500,700|Montserrat:300,400,700';
    giftofspeed.type = 'text/css';
    var godefer = document.getElementsByTagName('link')[0];
    godefer.parentNode.insertBefore(giftofspeed, godefer);
    var giftofspeed2 = document.createElement('link');
    giftofspeed2.rel = 'stylesheet';
    giftofspeed2.href = 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css';
    giftofspeed2.type = 'text/css';
    var godefer2 = document.getElementsByTagName('link')[0];
    godefer2.parentNode.insertBefore(giftofspeed2, godefer2);

    $(function() {
        $('.lazy').Lazy();
    });
    $(document).scroll(function() {
        var blog = $('.blog');
        var cta = $('.floatingCta');
        var blogOffset = blog.offset();
        var scroll = $(document).scrollTop();

        if ( scroll >= blogOffset.top ) {
            cta.fadeIn();
        } else {
            cta.fadeOut();
        }
    });
    $(".blog-watch-slider").owlCarousel({
        loop: true,
        items: 1,
        singleItem: true,
        autoHeight: true,
        center: true,
        autoplay: true,
        autoplaySpeed: 3000,
        autoplayTimeout: 8000,
        lazyLoad : true,
        nav: true
    }).trigger('refresh.owl.carousel');
    $(".testimonial-slider").owlCarousel({
        loop: true,
        items: 1,
        singleItem: true,
        autoHeight: true,
        center: true,
        autoplay: true,
        autoplaySpeed: 1000,
        autoplayTimeout: 8000
    }).trigger('refresh.owl.carousel');
    $('nav.nav').scrollToFixed();
    $.get('/html/sweepstakes/order3/herovideo.html', function(lazyVideo) {
        $(lazyVideo).appendTo($("#herovideo"));
    },'html');
    $.get('/html/sweepstakes/order3/copyvideo.html', function(lazyVideo) {
        $(lazyVideo).appendTo($("#copyvideo"));
    },'html');
    $.get('/html/sweepstakes/order3/bottomcta.html', function(lazyVideo) {
        $(lazyVideo).appendTo($("#bottomcta"));
    },'html');
    $.get('/html/sweepstakes/order3/sidebarcta.html', function(lazyVideo) {
        $(lazyVideo).appendTo($("#sidebarcta"));
    },'html');
    $.get('/html/sweepstakes/order3/sidebarvideos.html', function(lazyVideo) {
        $(lazyVideo).appendTo($("#sidebarvideos"));
    },'html');
});

$(document).on('click', 'a[href^="#"]', function (event) {
    event.preventDefault();

    $('html, body').animate({
        scrollTop: $($.attr(this, 'href')).offset().top
    }, 500);
});

// var rellax = new Rellax('.rellax-sidebar', {
//     speed: -2
// });
