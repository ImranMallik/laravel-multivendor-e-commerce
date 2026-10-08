/*
 * Vendor status actions (approve / reject / suspend / reactivate) over AJAX.
 *
 * Buttons come from resources/views/admin/vendors/partials/actions.blade.php and carry
 * everything as data-* (url, confirm copy, whether a reason is needed), which is generated
 * from the VendorAction enum. Nothing about the actions is hard-coded here.
 *
 * ONE centered SweetAlert per action: confirm (with a required textarea for reject), a loader
 * while saving, server errors shown inside the same dialog, then a success message.
 */
(function ($) {
    'use strict';

    if (window.__vendorActionsBound) {
        return;
    }
    window.__vendorActionsBound = true;

    function errorMessage(xhr) {
        var json = xhr.responseJSON || {};

        if (json.errors) {
            return Object.keys(json.errors).map(function (key) { return json.errors[key][0]; }).join(' ');
        }

        return json.message || 'Something went wrong. Please try again.';
    }

    function refresh() {
        // The vendors list reloads in place (keeps page and filters); other pages reload.
        if (window.vendorTable) {
            window.vendorTable.ajax.reload(null, false);
        } else {
            window.location.reload();
        }
    }

    $(document).on('click', '[data-vendor-action]', function (event) {
        event.preventDefault();

        var button = this;
        var needsReason = button.dataset.requiresReason === '1';

        var options = {
            position: 'center',
            icon: button.dataset.confirmIcon,
            title: button.dataset.confirmTitle,
            text: button.dataset.confirmText,
            showCancelButton: true,
            confirmButtonText: button.dataset.confirmButton,
            confirmButtonColor: button.dataset.confirmColor,
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: !needsReason,
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function (reason) {
                return $.ajax({
                    url: button.dataset.url,
                    method: 'POST',
                    dataType: 'json',
                    data: needsReason ? {reason: reason} : {}
                }).then(null, function (xhr) {
                    // Keep the dialog open and show the server's message (e.g. the reason rule).
                    Swal.showValidationMessage(errorMessage(xhr));

                    return false;
                });
            }
        };

        if (needsReason) {
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

            refresh();

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Done',
                text: result.value.message,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        });
    });
})(jQuery);
