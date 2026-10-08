/*
 * Admin vendors list: server-side DataTable (yajra) with AJAX filters and status tabs.
 * Column keys must match App\DataTables\VendorDataTable.
 */
(function ($) {
    'use strict';

    var $table = $('#vendors-table');

    if (!$table.length) {
        return;
    }

    var $status = $('#filter-status');
    var $from = $('#filter-date-from');
    var $to = $('#filter-date-to');
    var $tabs = $('#vendor-status-tabs');

    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 400,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[6, 'desc']], // newest first
        // No custom "processing" markup: the admin template already draws its own spinner on
        // .dataTables_processing, and adding another would show two.
        language: {
            search: '',
            searchPlaceholder: 'Search shop, owner, email or phone',
            emptyTable: 'No vendors found.',
            zeroRecords: 'No vendors match your filters.'
        },
        ajax: {
            url: $table.data('source'),
            data: function (params) {
                // Filters are read on every request, so reloads keep them.
                params.status = $status.val();
                params.date_from = $from.val();
                params.date_to = $to.val();
            },
            dataSrc: function (json) {
                Object.keys(json.counts || {}).forEach(function (key) {
                    $tabs.find('[data-count="' + key + '"]').text(json.counts[key]);
                });

                return json.data;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'shop', name: 'shop'},
            {data: 'owner', name: 'owner'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'status', name: 'status'},
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

    // Used by vendor-actions.js to reload in place after an action.
    window.vendorTable = table;

    $.fn.dataTable.ext.errMode = 'none';
    $table.on('error.dt', function (event, settings, techNote, message) {
        Swal.fire({position: 'center', icon: 'error', title: 'Could not load vendors', text: message});
    });

    function syncTabs() {
        var current = $status.val() || '';

        $tabs.find('.nav-link').removeClass('active').filter(function () {
            return $(this).data('status') === current;
        }).addClass('active');
    }

    $('#vendor-filters').on('submit', function (event) {
        event.preventDefault();
    });

    $status.add($from).add($to).on('change', function () {
        syncTabs();
        table.ajax.reload();
    });

    $tabs.on('click', '.nav-link', function (event) {
        event.preventDefault();

        $status.val($(this).data('status'));
        syncTabs();
        table.ajax.reload();
    });

    $('#filter-reset').on('click', function () {
        $status.val('');
        $from.val('');
        $to.val('');
        syncTabs();
        table.search('').order([6, 'desc']).ajax.reload();
    });
})(jQuery);
