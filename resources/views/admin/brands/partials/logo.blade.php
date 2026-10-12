{{-- Table thumbnail; the admin placeholder is used when the file is missing (admin pages never load frontend assets). --}}
<img src="{{ $brand->hasLogo() ? $brand->logo_url : asset('admin-assets/img/example-image-50.jpg') }}"
     alt="{{ $brand->name }}" width="80" height="40" class="rounded admin-img-contain">
