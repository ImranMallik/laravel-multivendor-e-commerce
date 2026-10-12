{{-- Table thumbnail; the admin placeholder is used when the file is missing (admin pages never load frontend assets). --}}
<img src="{{ $product->hasThumbnail() ? $product->thumbnail_url : asset('admin-assets/img/example-image-50.jpg') }}"
     alt="{{ $product->name }}" width="50" height="50" class="rounded admin-img-cover">
