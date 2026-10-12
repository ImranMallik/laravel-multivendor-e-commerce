@extends('admin.layouts.app')

@section('title', 'Attributes')
@section('page-title', 'Attributes')

@section('page-actions')
    <x-admin.button :href="route('admin.attributes.create')" icon="fas fa-plus">Add Attribute</x-admin.button>
@endsection

@section('content')
    <x-admin.card title="Product attributes (Size, Color, ...)">
        @include('admin.partials.crud-table', [
            'id' => 'attributes-table',
            'source' => route('admin.attributes.data'),
            'noun' => 'attribute',
            'headers' => ['#', 'Name', 'Type', 'Values', 'Order', 'Status'],
            'columns' => [
                ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
                ['data' => 'name'],
                ['data' => 'type', 'orderable' => false],
                ['data' => 'values_list'],
                ['data' => 'sort_order'],
                ['data' => 'is_active'],
            ],
            'orderColumn' => 4,
        ])
    </x-admin.card>
@endsection
