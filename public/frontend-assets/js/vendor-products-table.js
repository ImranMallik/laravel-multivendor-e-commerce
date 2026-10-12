/*
 * Vendor panel: the seller's own products. Server-side DataTable (yajra), Active switch,
 * rejection reason dialog and delete, each with ONE centered SweetAlert.
 * Column keys must match App\DataTables\VendorProductDataTable.
 */
(function ($) {
    'use strict';

    var $table = $('#vendor-products-table');

    if (!$table.length) {
        return;
    }

    var csrf = $('meta[name="csrf-token"]').attr('content');

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
        order: [],
        language: {
            search: '',
            searchPlaceholder: 'Search name or SKU',
            emptyTable: 'You have no products yet. Use “add product” to create one.',
            zeroRecords: 'No products match your search.'
        },
        ajax: {url: $table.data('source')},
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'thumbnail', name: 'thumbnail', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'category', name: 'category', orderable: false},
            {data: 'price', name: 'price'},
            {data: 'stock_qty', name: 'stock_qty'},
            {data: 'status', name: 'status', orderable: false},
            {data: 'approval_status', name: 'approval_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end text-nowrap'}
        ]
    });

    $.fn.dataTable.ext.errMode = 'none';
    $table.on('error.dt', function (event, settings, techNote, message) {
        Swal.fire({position: 'center', icon: 'error', title: 'Could not load products', text: message});
    });

    $table.on('change', '[data-product-status]', function () {
        var $switch = $(this);
        var wanted = $switch.prop('checked');

        $switch.prop('disabled', true);

        $.ajax({url: $switch.data('url'), method: 'PATCH', dataType: 'json', headers: {'X-CSRF-TOKEN': csrf}})
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

    $table.on('click', '[data-rejection-reason]', function () {
        Swal.fire({
            position: 'center',
            icon: 'info',
            title: 'Why it was rejected',
            text: this.dataset.rejectionReason,
            footer: 'Edit the product to fix it; saving sends it for review again.'
        });
    });

    $table.on('click', '[data-delete-product]', function (event) {
        event.preventDefault();

        var button = this;

        Swal.fire({
            position: 'center',
            icon: 'warning',
            title: 'Delete this product?',
            text: '“' + button.dataset.title + '” will be removed from your shop.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function () {
                return $.ajax({url: button.dataset.url, method: 'DELETE', dataType: 'json', headers: {'X-CSRF-TOKEN': csrf}})
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
