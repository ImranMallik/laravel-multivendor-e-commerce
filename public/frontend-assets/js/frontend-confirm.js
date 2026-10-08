/*
 * Global confirmation dialogs for the storefront (SweetAlert2, centered).
 *
 *   <form data-confirm="Message">                       confirm before the form submits
 *   <button type="submit" data-confirm="Message">       confirm, then submit the button's form
 *   <a href="#" data-confirm="Message"
 *      data-confirm-form="formId">                      confirm, then submit that form
 *   <a href="/x" data-confirm="Message">                confirm, then follow the link
 *
 * Optional: data-confirm-title, data-confirm-button, data-confirm-icon (default: warning).
 *
 * Event delegation on document plus a guard so it can never be bound twice.
 */
(function () {
    'use strict';

    if (window.__frontendConfirmBound) {
        return;
    }
    window.__frontendConfirmBound = true;

    function ask(el) {
        return Swal.fire({
            position: 'center',
            icon: el.dataset.confirmIcon || 'warning',
            title: el.dataset.confirmTitle || 'Are you sure?',
            text: el.dataset.confirm,
            showCancelButton: true,
            confirmButtonText: el.dataset.confirmButton || 'Yes, continue',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0088cc',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            return result.isConfirmed;
        });
    }

    // <form data-confirm>: intercept the submit. form.submit() below does not re-fire this event.
    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
            return;
        }

        event.preventDefault();

        ask(form).then(function (confirmed) {
            if (confirmed) {
                form.submit();
            }
        });
    });

    // Buttons and links with data-confirm.
    document.addEventListener('click', function (event) {
        var el = event.target.closest('[data-confirm]');

        // Forms are handled by the submit listener above.
        if (!el || el instanceof HTMLFormElement) {
            return;
        }

        event.preventDefault();

        var form = el.dataset.confirmForm
            ? document.getElementById(el.dataset.confirmForm)
            : el.form;

        ask(el).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            if (form) {
                form.submit();
            } else if (el.href && el.getAttribute('href') !== '#') {
                window.location.href = el.href;
            }
        });
    });
})();
