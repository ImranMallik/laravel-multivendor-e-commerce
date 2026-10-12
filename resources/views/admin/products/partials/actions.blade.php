{{-- Row actions. Approve / Reject appear only when the approval workflow allows them. --}}
<x-admin.button :href="route('admin.products.show', $product)" variant="info" icon="fas fa-eye" size="sm" tooltip="View" />
<x-admin.button :href="route('admin.products.edit', $product)" variant="primary" icon="fas fa-edit" size="sm" tooltip="Edit" />
@include('admin.products.partials.approval-buttons', ['product' => $product, 'size' => 'sm'])
<x-admin.button variant="danger" icon="fas fa-trash" size="sm" tooltip="Delete"
                data-delete-row
                data-url="{{ route('admin.products.destroy', $product) }}"
                data-title="{{ $product->name }}" />
