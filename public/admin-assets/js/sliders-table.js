/*
 * Admin sliders list: server-side DataTable (yajra), AJAX status switch and AJAX delete.
 * Column keys must match App\DataTables\SliderDataTable. There are no page filters.
 */
(function ($) {
    'use strict';

    var $table = $('#sliders-table');

    if (!$table.length) {
        return;
    }

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

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'asc']], // sort_order ascending
        // No custom "processing" markup: the admin template already draws its own spinner on
        // .dataTables_processing, and adding another would show two.
        language: {
            search: '',
            searchPlaceholder: 'Search by title',
            emptyTable: 'No sliders yet. Use “Add Slider” to create one.',
            zeroRecords: 'No sliders match your search.'
        },
        ajax: {url: $table.data('source')},
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'image', name: 'image', orderable: false, searchable: false},
            {data: 'title', name: 'title'},
            {data: 'button_text', name: 'button_text'},
            {data: 'sort_order', name: 'sort_order'},
            {data: 'is_active', name: 'is_active'},
            {data: 'created_at', name: 'created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right text-nowrap'}
        ],
        preDrawCallback: function () {
            $('.tooltip').remove(); // tooltips of rows that are about to be replaced
        },
        drawCallback: function () {
            $table.find('[data-toggle="tooltip"]').tooltip();
        }
    });

    $.fn.dataTable.ext.errMode = 'none';
    $table.on('error.dt', function (event, settings, techNote, message) {
        Swal.fire({position: 'center', icon: 'error', title: 'Could not load sliders', text: message});
    });

    // Status switch: PATCH, then follow the server's state. On failure the switch snaps back.
    $table.on('change', '[data-slider-toggle]', function () {
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

    // Delete: one centered confirm; the table reloads in place (page and search are kept).
    $table.on('click', '[data-slider-delete]', function (event) {
        event.preventDefault();

        var button = this;

        Swal.fire({
            position: 'center',
            icon: 'warning',
            title: 'Delete this slider?',
            text: '“' + button.dataset.title + '” will be removed from the home page.',
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
