
function importScript (sSrc, fOnload) {
    var oScript = document.createElement("script");
    oScript.type = "text\/javascript";
    oScript.defer = true;
    if (fOnload) { oScript.onload = fOnload; }
    document.body.appendChild(oScript);
    oScript.src = sSrc;
}

function showFirstOrderTab() {
    $('#sweepstakes_order_tabs a:first').tab('show');
}

function validateEmailAddress(email) {
}

function initializeOrderPage(sweepstakesEndTime, gtmEnabled) {

    jQuery(function ($) {

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

          // if (days == 0) {
          //     $('#countdown-timer > .days').hide();
          //     $('#countdown-timer-unit > .days').hide();
          // } else {
              $('#countdown-timer > .days').html(days);
          // }
          $('#countdown-timer > .hours').html(('0'+hours).substr(-2));
          $('#countdown-timer > .minutes').html(('0'+minutes).substr(-2));
          $('#countdown-timer > .seconds').html(('0'+seconds).substr(-2));

          // If the count down is finished, write some text
          if (distance < 0) {
              clearInterval(x);
              $('.count-down-container').hide();
              $('.count-down-container-closed').show();
          }
        }
        discountTimerFunc();

        $.ajax({
            type: "GET",
            url:'/sweepstakes/visit',
            success: function(response){
            },
        });

        var entrantFields = ['full_name', 'email', 'phone', 'address', 'city', 'state', 'zip'];

        var formError = $('body').find('#form-errors');

        $('#sweepstakes_order_entrant_action').click(function() {
            formError.addClass('hide');
            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            var valid = true;
            entrantFields.forEach(function(fieldName) {
                var field = $('#restomods_listingbundle_sweepstakesprofiletype_' + fieldName);
                field.removeClass('is-invalid');
                if (!field.val() || field.val().length === 0) {
                    field.addClass('is-invalid');
                    valid = false;
                }
            });

            var emailField = $('#restomods_listingbundle_sweepstakesprofiletype_email');
            if (!re.test(String(emailField.val()).toLowerCase().trim())) {
                emailField.removeClass('is-invalid');
                emailField.addClass('is-invalid');
                valid = false;
            } else {
                emailField.val(String(emailField.val()).toLowerCase().trim());
            }

            if (valid) {

                // google tag manager event
                if (gtmEnabled) {
                    dataLayer.push({'event': 'lead'});
                }

                // submit form
                entrantFields.forEach(function(fieldName) {
                    var field = $('#restomods_listingbundle_sweepstakesprofiletype_' + fieldName);
                    $.cookie('sw_order_' + fieldName, field.val());
                });

                $('#sweepstakes_order_entrant_action').addClass('loading');
                $('#form_profile').submit();
            }
        });
        entrantFields.forEach(function(fieldName) {
            var val = $.cookie('sw_order_' + fieldName);
            if (val) {
                var field = $('#restomods_listingbundle_sweepstakesprofiletype_' + fieldName);
                field.val(val);
            }
        });

        var $country = $('#restomods_listingbundle_sweepstakesprofiletype_country');
        $country.change(function() {
            // ... retrieve the corresponding form.
            var $form = $(this).closest('form');
            // Simulate form data, but only include the selected sport value.
            var data = {};
            data[$country.attr('name')] = $country.val();
            // Submit data via AJAX to the form's action path.
            $.ajax({
                url : $form.attr('action'),
                type: $form.attr('method'),
                data : data,
                success: function(html) {
                    // Replace current position field ...
                    $('#restomods_listingbundle_sweepstakesprofiletype_state').replaceWith(
                        // ... with the returned one from the AJAX response.
                        $(html).find('#restomods_listingbundle_sweepstakesprofiletype_state')
                    );
                    // Position field now displays the appropriate positions.
                }
            });
        });
    });
}
