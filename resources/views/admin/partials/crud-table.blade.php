{{--
    Server-side DataTable shell driven by public/admin-assets/js/crud-table.js.
    Expects: $id, $source (JSON route), $headers (labels, without the Actions column),
             $columns (DataTables column definitions, without the action column),
             $orderColumn (index of the default sort column), $noun ("category", ...).
    The Actions column is appended automatically. There are no filters: only the table's own search.
    NOTE: DataTables treats data-* attributes on the table as init options (data-columns would override
    the column list), so the attribute is called data-table-columns.
--}}
<div class="table-responsive">
    <table class="table table-striped w-100" id="{{ $id }}"
           data-crud-table
           data-source="{{ $source }}"
           data-table-columns="{{ json_encode($columns) }}"
           data-order-column="{{ $orderColumn }}"
           data-noun="{{ $noun }}">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
                <th class="text-right">Actions</th>
            </tr>
        </thead>
    </table>
</div>

@push('scripts')
    <script src="{{ asset('admin-assets/js/crud-table.js') }}"></script>
@endpush
