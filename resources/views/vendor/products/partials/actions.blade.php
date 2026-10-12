<a class="btn btn-sm btn-primary" href="{{ route('vendor.products.edit', $product) }}" title="Edit" aria-label="Edit {{ $product->name }}"><i class="far fa-edit"></i></a>
<button type="button" class="btn btn-sm btn-danger" title="Delete" aria-label="Delete {{ $product->name }}"
        data-delete-product
        data-url="{{ route('vendor.products.destroy', $product) }}"
        data-title="{{ $product->name }}"><i class="far fa-trash-alt"></i></button>
