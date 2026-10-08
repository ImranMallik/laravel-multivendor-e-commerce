/*
 * Shared AJAX setup for the admin area: every jQuery request carries the CSRF token.
 * Loaded once from the admin scripts partial, after jQuery.
 */
(function ($) {
    'use strict';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
})(jQuery);
