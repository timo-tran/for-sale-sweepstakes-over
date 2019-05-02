function setupCountDown(sweepstakesEndTime) {
    var countDownDate = sweepstakesEndTime;

    // Update the count down every 1 second
    var x = setInterval(discountTimerFunc, 1000);
    function discountTimerFunc() {
      // Get todays date and time
      var now = new Date().getTime();

      // Find the distance between now an the count down date
      var distance = countDownDate - now;

      // Time calculations for days, hours, minutes and seconds
      var days = Math.floor(distance / (1000 * 60 * 60 * 24));
      var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((distance % (1000 * 60)) / 1000);

      if (days == 0) {
          $('#countdown-timer > .days').hide();
          $('#countdown-timer-unit > .days').hide();
      } else {
          $('#countdown-timer > .days').html(days);
      }
      $('#countdown-timer > .hours').html(('0'+hours).substr(-2));
      $('#countdown-timer > .minutes').html(('0'+minutes).substr(-2));
      $('#countdown-timer > .seconds').html(('0'+seconds).substr(-2));

      // If the count down is finished, write some text
      if (distance < 0) {
          clearInterval(x);
          $('#countdown-timer > .days').hide();
          $('#countdown-timer > .hours').hide();
          $('#countdown-timer > .minutes').hide();
          $('#countdown-timer-unit').hide();
          $('#countdown-timer > .seconds').html("PICKING THE WINNER");
      }
    }
    discountTimerFunc();
}

function loadProductsSlider() {
    $.ajax({
        type: "GET",
        url:'/store/products',
        success: function(response){
            if(response){
                var html = '';
                response.forEach(function(product) {
                    var productHtml = '<li><div class="product text-center"><div class="product-inner">' +
                        '<a href="' + product.link + '"><div class="image"><img src="' + product.image + '" class="elIMG ximg" alt=""><div class="image-label">SELECT OPTIONS</div></div></a>' +
                        '<div class="name">' + product.name + '</div>' +
                        '<div class="price">' + product.price_html + '</div>' +
                        '<div class="entries"><span class="number">' + product.entries + '</span><span>ENTRIES</span></div>' +
                        '</div></li>';
                    html = html + productHtml;
                });
                $('#productsSlider').html(html);
                var slider = $("#productsSlider").lightSlider({
                    item: 5,
                    loop: false,
                    pager: false,
                    controls: false,
                    enableDrag: false,
                    responsive: [
                        {
                            breakpoint: '1600',
                            settings: {
                                item: 4
                            }
                        },
                        {
                            breakpoint: '1240',
                            settings: {
                                item: 3
                            }
                        },
                        {
                            breakpoint: '976',
                            settings: {
                                item: 2
                            }
                        },
                        {
                            breakpoint: '690',
                            settings: {
                                item: 2
                            }
                        },
                        {
                            breakpoint: '310',
                            settings: {
                                item: 1
                            }
                        }
                    ]
                });
                $(".left.carousel-control[data-id='productsSlider']").click(function () {
                    slider.goToPrevSlide();
                });

                $(".right.carousel-control[data-id='productsSlider']").click(function () {
                    slider.goToNextSlide();
                });
                $('.products').fadeIn();
                $('.products-placeholder').fadeOut();
            }
        }
    });
}

function loadRecentArticleSlider() {
    $.ajax({
        type: "GET",
        url:'/wp-json/wp/v2/posts?context=embed&per_page=10',
        success: function(response){
            if(response){
                var html = '';
                response.forEach(function(article) {
                    var dateStr = article.date + '+00:00';
                    var date = new Date(dateStr);
                    var articleHtml =
                    '<li>' +
                    '   <div class="recent-aritcle">' +
                    '       <a href="' + article.link + '">' +
                    '       <img src="' + article.featured_slider_url + '" class="elIMG ximg" alt="">' +
                    '       </a>' +
                    '       <div class="caption">' +
                    '           <time class="the-date" datetime="' + dateStr +  '">' + date.toDateString() +'</time>' +
                    '           <div class="title">' + article.title.rendered + '</div>' +
                    '       </div>' +
                    '   </div>' +
                    '</li>';
                    html = html + articleHtml;
                });
                $('#recent-articles-slider').html(html);
                var slider = $("#recent-articles-slider").lightSlider({
                    item: 5,
                    loop: false,
                    pager: false,
                    controls: false,
                    enableDrag: false,
                    responsive: [
                        {
                            breakpoint: '1600',
                            settings: {
                                item: 4
                            }
                        },
                        {
                            breakpoint: '1240',
                            settings: {
                                item: 3
                            }
                        },
                        {
                            breakpoint: '976',
                            settings: {
                                item: 2
                            }
                        },
                        {
                            breakpoint: '690',
                            settings: {
                                item: 1
                            }
                        }
                    ]
                });
                $(".left.carousel-control[data-id='recent-articles-slider']").click(function () {
                    slider.goToPrevSlide();
                });

                $(".right.carousel-control[data-id='recent-articles-slider']").click(function () {
                    slider.goToNextSlide();
                });
                $('.recent-articles-slider-wrapper').fadeIn();
                $('.recent-articles-placeholder').fadeOut();
            }
        }
    });
}


function loadListingSlider(sliderId, count) {
    var listingSlider = $("#" + sliderId).lightSlider({
        item: Math.min(4, count),
        loop: false,
        pager: false,
        controls: false,
        enableDrag: false,
        responsive: [
            {
                breakpoint: '1600',
                settings: {
                    item: Math.min(4, count)
                }
            },
            {
                breakpoint: '1240',
                settings: {
                    item: Math.min(3, count)
                }
            },
            {
                breakpoint: '976',
                settings: {
                    item: Math.min(2, count)
                }
            },
            {
                breakpoint: '690',
                settings: {
                    item: Math.min(1, count)
                }
            }
        ]
    });
    $(".left.carousel-control[data-id='" + sliderId + "']").click(function () {
        listingSlider.goToPrevSlide();
    });

    $(".right.carousel-control[data-id='"+ sliderId + "']").click(function () {
        listingSlider.goToNextSlide();
    });
    $(".listings-slider-wrapper[data-id='" + sliderId + "']").fadeIn();
}

function setupContactForm() {
    $('#contact_form').on('submit', function(event) {
        event.preventDefault();
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        var valid = true;
        var entrantFields = ['name', 'email', 'message'];
        entrantFields.forEach(function(fieldName) {
            var field = $('#contact_form_' + fieldName);
            field.removeClass('is-invalid');
            if (!field.val() || field.val().length === 0) {
                field.addClass('is-invalid');
                valid = false;
            }
        });

        if (valid) {

            $('#contact_form_submit').addClass('loading');
            $.ajax({
                type: "POST",
                url:'/profile/contact',
                data:$(this).serialize(),
                success: function(response){
                    if(response.success){
                        var alertHtml = '<div class="alert alert-success alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            'Your message has been sent successfully.' +
                        '</div>';
                        $('.contact-form-alert-container').html(alertHtml);
                        $('#contact_form_submit').removeClass('loading');
                    }else{
                        var alertHtml = '<div class="alert alert-success alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<strong>Error!</strong> Your message wasn\'t sent. Please reach us via email.' +
                        '</div>';
                        $('.contact-form-alert-container').html(alertHtml);
                        $('#contact_form_submit').removeClass('loading');
                    }
                }
            });
        } else {
            console.log('invalid form');
        }
    });
}
