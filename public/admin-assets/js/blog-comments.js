/*
 * Blog comments table: Approve / Spam buttons ([data-comment-action]) send a PATCH and reload the table
 * in place (page and search kept). Delete is handled by crud-table.js.
 */
(function ($) {
    'use strict';

    var $table = $('#blog-comments-table');

    $table.on('click', '[data-comment-action]', function (event) {
        event.preventDefault();

        var $button = $(this);

        $button.prop('disabled', true);
        $('.tooltip').remove();

        $.ajax({url: $button.data('url'), method: 'PATCH', dataType: 'json'})
            .done(function (response) {
                $table.DataTable().ajax.reload(null, false);
                Swal.fire({position: 'center', icon: 'success', title: 'Done', text: response.message, timer: 1500, timerProgressBar: true, showConfirmButton: false});
            })
            .fail(function (xhr) {
                $button.prop('disabled', false);
                Swal.fire({position: 'center', icon: 'error', title: 'Could not update', text: (xhr.responseJSON || {}).message || 'Something went wrong. Please try again.'});
            });
    });
})(jQuery);
