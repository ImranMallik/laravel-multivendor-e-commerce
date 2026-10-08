{{--
    Icon / image cell of the categories table: the real icon (Font Awesome 5.15.1, loaded by the index page),
    not its class name. Admin pages never load frontend assets.
--}}
<div class="d-flex align-items-center">
    @if ($category->hasImage())
        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" width="35" height="35" class="rounded-circle mr-2 admin-img-cover">
    @endif

    @if (filled($category->icon))
        <i class="{{ $category->icon }} fa-lg" role="img" aria-label="Icon: {{ \Illuminate\Support\Str::afterLast($category->icon, 'fa-') }}"></i>
    @elseif (! $category->hasImage())
        <span class="text-muted">&mdash;</span>
    @endif
</div>
