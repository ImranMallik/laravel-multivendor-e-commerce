{{--
    Server-side DataTable shell driven by public/admin-assets/js/crud-table.js.
    Expects: $id, $source (JSON route), $headers (labels, without the Actions column),
             $columns (DataTables column definitions, without the action column),
             $orderColumn (index of the default sort column), $noun ("category", ...).
    Optional: $orderDir ('asc' default, or 'desc'), $actions (false for read-only lists such as reports).
    The Actions column is appended automatically unless $actions is false. There are no filters: only the
    table's own search.
    NOTE: DataTables treats data-* attributes on the table as init options (data-columns would override
    the column list), so the attribute is called data-table-columns.
--}}
<div class="table-responsive">
    <table class="table table-striped w-100" id="{{ $id }}"
           data-crud-table
           data-source="{{ $source }}"
           data-table-columns="{{ json_encode($columns) }}"
           data-order-column="{{ $orderColumn }}"
           data-order-dir="{{ $orderDir ?? 'asc' }}"
           data-actions="{{ ($actions ?? true) ? 'true' : 'false' }}"
           data-noun="{{ $noun }}">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
                @if ($actions ?? true)
                    <th class="text-right">Actions</th>
                @endif
            </tr>
        </thead>
    </table>
</div>

@push('scripts')
    <script src="{{ asset('admin-assets/js/crud-table.js') }}"></script>
@endpush
