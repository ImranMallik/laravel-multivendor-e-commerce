/*
 * Admin products list (and Pending Products): server-side DataTable (yajra) with AJAX filters,
 * status / home-flag switches and AJAX delete. Approve / Reject live in product-actions.js.
 * Column keys must match App\DataTables\ProductDataTable.
 */
(function ($) {
    'use strict';

    var $table = $('#products-table');

    if (!$table.length) {
        return;
    }

    var $vendor = $('#filter-vendor');
    var $category = $('#filter-category');
    var $approval = $('#filter-approval');

    function errorMessage(xhr) {
        var json = xhr.responseJSON || {};

        if (json.errors) {
            return Object.keys(json.errors).map(function (key) { return json.errors[key][0]; }).join(' ');
        }

        return json.message || 'Something went wrong. Please try again.';
    }

    function success(message) {
        return Swal.fire({position: 'center', icon: 'success', title: 'Done', text: message, timer: 1500, timerProgressBar: true, showConfirmButton: false});
    }

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [], // server default: newest first
        language: {
            search: '',
            searchPlaceholder: 'Search name, SKU or shop',
            emptyTable: 'No products found.',
            zeroRecords: 'No products match your filters.'
        },
        ajax: {
            url: $table.data('source'),
            data: function (params) {
                params.vendor = $vendor.val();
                params.category_id = $category.val();
                params.approval_status = $approval.val();
            },
            dataSrc: function (json) {
                var $badge = $('[data-pending-products-count]');

                if ($badge.length && typeof json.pending !== 'undefined') {
                    $badge.text(json.pending).toggleClass('d-none', json.pending === 0);
                }

                return json.data;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'thumbnail', name: 'thumbnail', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'vendor', name: 'vendor'},
            {data: 'category', name: 'category'},
            {data: 'price', name: 'price'},
            {data: 'stock_qty', name: 'stock_qty'},
            {data: 'status', name: 'status', orderable: false},
            {data: 'approval_status', name: 'approval_status'},
            {data: 'flags', name: 'flags', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right text-nowrap'}
        ],
        preDrawCallback: function () {
            $('.tooltip').remove();
        },
        drawCallback: function () {
            $table.find('[data-toggle="tooltip"]').tooltip();
        }
    });

    // Used by product-actions.js to reload in place after approve / reject.
    window.productsTable = table;

    $.fn.dataTable.ext.errMode = 'none';
    $table.on('error.dt', function (event, settings, techNote, message) {
        Swal.fire({position: 'center', icon: 'error', title: 'Could not load products', text: message});
    });

    $('#product-filters').on('change', 'select', function () {
        table.ajax.reload();
    });

    $('#filter-reset').on('click', function () {
        $vendor.val('');
        $category.val('');
        $approval.filter('select').val('');
        table.ajax.reload();
    });

    // Status and home-flag switches: PATCH, then follow the server's state; on failure snap back.
    $table.on('change', '[data-toggle-status]', function () {
        var $switch = $(this);
        var wanted = $switch.prop('checked');

        $switch.prop('disabled', true);

        $.ajax({url: $switch.data('url'), method: 'PATCH', dataType: 'json'})
            .done(function (response) {
                $switch.prop('checked', response[$switch.data('field') || 'is_active']);
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

    // Delete: one centered confirm; the table reloads in place.
    $table.on('click', '[data-delete-row]', function (event) {
        event.preventDefault();

        var button = this;

        Swal.fire({
            position: 'center',
            icon: 'warning',
            title: 'Delete this product?',
            text: '“' + button.dataset.title + '” will be removed from the store.',
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
