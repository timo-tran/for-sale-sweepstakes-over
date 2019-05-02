$(document).ready(function () {
    var $grid    = $('.grid');
    var $masonry = $grid.masonry({
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer',
        percentPosition: true,
    });

    $masonry.imagesLoaded().progress(function () {
        $masonry.masonry('layout');
    });

    $('#deleteListingModal').on('show.bs.modal', function (event) {
        var button       = $(event.relatedTarget);
        var listingTitle = button.data('title');
        var listingId    = button.data('listingid');
        var _token       = button.data('_token');
        var modal        = $(this);
        var form         = modal.find('.modal-footer form');

        modal.find('.modal-title .listing-title').text(listingTitle);
        form.attr('action', form.data('action').replace(0,listingId));
        modal.find('.modal-footer #form__token').attr('value', _token);
    });

    $('.copy-referral-url').on('click',function (e) {
        e.preventDefault();
       copyToClipboard('referral-url');
        $(this).text('Copied');
        setTimeout(function () {
            $('.copy-referral-url').text('Copy')
        },500)
    });

    function copyToClipboard(elementId){
        var aux = document.createElement("input");
        aux.setAttribute("value", document.getElementById(elementId).value);
        document.body.appendChild(aux);
        aux.select();
        document.execCommand("copy");
        document.body.removeChild(aux);
        document.getElementById("referral-url").select();
    }
});
