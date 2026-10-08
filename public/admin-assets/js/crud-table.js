/*
 * Generic admin CRUD list: server-side DataTable (yajra) with an AJAX status switch and AJAX delete.
 * Configured by data-* attributes on <table data-crud-table> (see admin/partials/crud-table.blade.php):
 *   data-source, data-table-columns (JSON), data-order-column, data-noun.
 * There are no page filters: only the table's own search, page length and pagination.
 * No custom "processing" markup: the admin template already draws its own spinner.
 */
(function ($) {
    'use strict';

    var $table = $('[data-crud-table]');

    if (!$table.length) {
        return;
    }

    var noun = $table.data('noun') || 'item';

    function errorMessage(xhr) {
        var json = xhr.responseJSON || {};

        if (json.errors) {
            return Object.keys(json.errors).map(function (key) { return json.errors[key][0]; }).join(' ');
        }

        return json.message || 'Something went wrong. Please try again.';
    }

    function success(message) {
        return Swal.fire({
            position: 'center',
            icon: 'success',
            title: 'Done',
            text: message,
            timer: 1500,
            timerProgressBar: true,
            showConfirmButton: false
        });
    }

    // data-table-columns (not data-columns): DataTables reads data-* attributes as init options.
    var columns = $table.data('tableColumns').map(function (column) {
        return $.extend({name: column.data}, column);
    });

    columns.push({data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right text-nowrap'});

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[parseInt($table.data('order-column'), 10), 'asc']],
        language: {
            search: '',
            searchPlaceholder: 'Search',
            emptyTable: 'No ' + noun + ' records yet.',
            zeroRecords: 'No ' + noun + ' records match your search.'
        },
        ajax: {url: $table.data('source')},
        columns: columns,
        preDrawCallback: function () {
            $('.tooltip').remove(); // tooltips of rows that are about to be replaced
        },
        drawCallback: function () {
            $table.find('[data-toggle="tooltip"]').tooltip();
        }
    });

    $.fn.dataTable.ext.errMode = 'none';
    $table.on('error.dt', function (event, settings, techNote, message) {
        Swal.fire({position: 'center', icon: 'error', title: 'Could not load the list', text: message});
    });

    // Status switch: PATCH, then follow the server's state. On failure the switch snaps back.
    $table.on('change', '[data-toggle-status]', function () {
        var $switch = $(this);
        var wanted = $switch.prop('checked');

        $switch.prop('disabled', true);

        $.ajax({url: $switch.data('url'), method: 'PATCH', dataType: 'json'})
            .done(function (response) {
                $switch.prop('checked', response.is_active);
                success(response.message);
            })
            .fail(function (xhr) {
                $switch.prop('checked', !wanted);
                Swal.fire({position: 'center', icon: 'error', title: 'Could not update', text: errorMessage(xhr)});
            })
            .always(function () {
                $switch.prop('disabled', false);
            });
    });

    // Delete: one centered confirm. A refusal (e.g. "still has sub categories") is shown inside the
    // same dialog. On success the table reloads in place, keeping page and search.
    $table.on('click', '[data-delete-row]', function (event) {
        event.preventDefault();

        var button = this;
        var what = button.dataset.noun || noun;

        Swal.fire({
            position: 'center',
            icon: 'warning',
            title: 'Delete this ' + what + '?',
            text: '“' + button.dataset.title + '” will be removed.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#fc544b',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function () {
                return $.ajax({url: button.dataset.url, method: 'DELETE', dataType: 'json'})
                    .then(null, function (xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));

                        return false;
                    });
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) {
                return;
            }

            table.ajax.reload(null, false);
            success(result.value.message);
        });
    });
})(jQuery);
