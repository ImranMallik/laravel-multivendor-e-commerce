/*
 * Auth page behaviour: show/hide password, double-submit protection, and the
 * "I want to shop / sell" switch that keeps typed values.
 *
 * The role is never read from a field: the switch only changes which route the single
 * signup form posts to (register or register/vendor), and the server decides from the route.
 */
(function () {
    'use strict';

    if (window.__authBound) {
        return;
    }
    window.__authBound = true;

    // Show / hide password.
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.auth-eye');

        if (!button) {
            return;
        }

        var input = document.getElementById(button.getAttribute('aria-controls'));
        var icon = button.querySelector('i');
        var show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    });

    // Loading state: runs after native validation passes, so invalid forms are not locked.
    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!form.matches || !form.matches('[data-auth-form]')) {
            return;
        }

        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = '1';

        var button = form.querySelector('[type="submit"]');

        if (button) {
            button.dataset.label = button.textContent;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Please wait...';
        }
    });

    // Restore the button when the page comes back from the browser cache (Back button).
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }

        document.querySelectorAll('[data-auth-form]').forEach(function (form) {
            form.dataset.submitting = '0';

            var button = form.querySelector('[type="submit"]');

            if (button && button.dataset.label) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.textContent = button.dataset.label;
            }
        });
    });

    // Customer / seller switch on the signup form.
    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-auth-mode]');

        if (!link) {
            return;
        }

        var form = document.querySelector('[data-auth-form][data-customer-action]');

        if (!form) {
            return;
        }

        event.preventDefault();

        var mode = link.dataset.authMode;
        var vendor = mode === 'vendor';

        document.querySelectorAll('[data-auth-mode]').forEach(function (item) {
            var active = item === link;

            item.classList.toggle('active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        form.action = vendor ? form.dataset.vendorAction : form.dataset.customerAction;

        var fields = form.querySelector('[data-vendor-fields]');

        fields.classList.toggle('d-none', !vendor);
        // Disabled inputs are neither validated nor submitted, so customer sign-ups never send shop fields.
        fields.querySelectorAll('input').forEach(function (input) {
            input.disabled = !vendor;
        });

        var submit = form.querySelector('[type="submit"]');

        submit.textContent = vendor ? submit.dataset.textVendor : submit.dataset.textCustomer;
        submit.dataset.label = submit.textContent;

        // Keep the URL in step so a validation redirect (back) reopens the same sub-tab.
        window.history.replaceState(null, '', link.href);
    });
})();
