/*
 * Product approve / reject over AJAX, used by the products table and the product page.
 * ONE centered SweetAlert per action: a confirm for approve, a required reason textarea for reject.
 * The table reloads in place; other pages reload once the success message has been shown.
 */
(function ($) {
    'use strict';

    if (window.__productActionsBound) {
        return;
    }
    window.__productActionsBound = true;

    function errorMessage(xhr) {
        var json = xhr.responseJSON || {};

        if (json.errors) {
            return Object.keys(json.errors).map(function (key) { return json.errors[key][0]; }).join(' ');
        }

        return json.message || 'Something went wrong. Please try again.';
    }

    $(document).on('click', '[data-product-approval]', function (event) {
        event.preventDefault();

        var button = this;
        var reject = button.dataset.productApproval === 'reject';

        var options = {
            position: 'center',
            icon: reject ? 'warning' : 'question',
            title: reject ? 'Reject this product?' : 'Approve this product?',
            text: reject
                ? 'The reason is emailed to the vendor, who can fix the product and resubmit it.'
                : '“' + button.dataset.title + '” can be shown in the store while it is Active.',
            showCancelButton: true,
            confirmButtonText: reject ? 'Reject product' : 'Yes, approve',
            confirmButtonColor: reject ? '#fc544b' : '#47c363',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: !reject,
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function (reason) {
                return $.ajax({url: button.dataset.url, method: 'POST', dataType: 'json', data: reject ? {reason: reason} : {}})
                    .then(null, function (xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));

                        return false;
                    });
            }
        };

        if (reject) {
            options.input = 'textarea';
            options.inputPlaceholder = 'Reason (min. 5 characters)';
            options.inputAttributes = {maxlength: 1000, 'aria-label': 'Reason'};
            options.inputValidator = function (value) {
                if (!value || value.trim().length < 5) {
                    return 'Please enter a reason (at least 5 characters).';
                }
            };
        }

        Swal.fire(options).then(function (result) {
            if (!result.isConfirmed || !result.value) {
                return;
            }

            var done = Swal.fire({position: 'center', icon: 'success', title: 'Done', text: result.value.message, timer: 1500, timerProgressBar: true, showConfirmButton: false});

            if (window.productsTable) {
                window.productsTable.ajax.reload(null, false);
            } else {
                done.then(function () { window.location.reload(); });
            }
        });
    });
})(jQuery);
