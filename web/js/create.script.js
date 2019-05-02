$(document).ready(function () {
    $("select").select2({theme: 'bootstrap'});
    if (!editing) {
        $('#restomods_listingbundle_listing_model').children('option:not(:first)').remove();
    }

    var select2Options = {
        theme: 'bootstrap', width: 'resolve', sorter: function (data) {
            return data.sort(function (a, b) {
                return a.text < b.text ? -1 : a.text > b.text ? 1 : 0;
            });
        }
    };

    var cascadeLoading = new Select2Cascade($('#restomods_listingbundle_listing_make'), $('#restomods_listingbundle_listing_model'), modelsUrl, select2Options);

    if (editing) {
        cascadeLoading.then(function (parent, child, items) {
            if (modelId != null) {
                child.val(modelId).trigger("change");
                modelId = null;
            }
        });

        $makeSelect2 = $('#restomods_listingbundle_listing_make').select2({theme: 'bootstrap'});
        $makeSelect2.val(makeId).trigger("change");
    }

    Dropzone.options.rmDropzoneFake   = false;
    Dropzone.options.acmeDropzoneForm = {
        addRemoveLinks: false,
        maxFiles: 50,
        acceptedFiles: 'image/*',
        resizeQuality: 0.8,
        resizeWidth: 1600,
        resizeMimeType: 'image/jpeg',
        previewsContainer: "#rm-dropzone-preview",
        previewTemplate: document.getElementById('rm-preview-template').innerHTML,
        init: function (e) {
            this.on("success", function (file, response) {
                file.mediaId = response.media_id;
                $hidden      = $('<input type="hidden" name="media_ids[]" data-mediaId="' + response.media_id + '" value="' + response.media_id + '">');
                $("#restomods_listingbundle_listing").append($hidden);
                $(file.previewElement).addClass('draggable');
                $('body').find(file.previewElement).attr('data-id',+response.media_id);
            });

            var that = this;
            this.on("complete", function (file) {
                if (file.status == 'error') {
                    file.previewElement.addEventListener("click", function() {
                      that.removeFile(file);
                    });
                }
            });

            this.on("removedfile", function (file) {
                if (file.mediaId == undefined) {
                    return;
                }
                $listingMedia = $("#restomods_listingbundle_listing");
                $hidden       = $('<input type="hidden" name="removed_media_ids[]" data-mediaId="' + file.mediaId + '" value="' + file.mediaId + '">');

                $listingMedia.find("[data-mediaId='" + file.mediaId + "']").remove();
                $listingMedia.append($hidden);
            });
        }
    }
});


function toggleSoldAndSubmit(button, elementId, formId) {
    button.disabled     = true;
    var formSelector    = "#" + formId;
    var elementSelector = "#" + elementId;

    $(elementSelector).prop("checked", !$(elementSelector).prop("checked"));
    $(formSelector).submit();
}
