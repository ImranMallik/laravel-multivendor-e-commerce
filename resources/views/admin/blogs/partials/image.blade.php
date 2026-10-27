{{-- Table thumbnail; the admin placeholder is used when the file is missing (admin pages never load frontend assets). --}}
<img src="{{ $blog->hasImage() ? $blog->image_url : asset('admin-assets/img/example-image-50.jpg') }}"
     alt="{{ $blog->title }}" width="80" height="42" class="rounded admin-img-cover">
