{{-- Table thumbnail; the admin placeholder is used when the file is missing (admin pages never load frontend assets). --}}
<img src="{{ $slider->hasImage() ? $slider->image_url : asset('admin-assets/img/example-image-50.jpg') }}"
     alt="{{ $slider->title }}" width="96" height="37" class="rounded admin-img-cover">
