{{-- Shared by create and edit. Expects: $action, optional $category (edit), optional $method. --}}
@php($category ??= null)

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$category ? 'Edit Category' : 'New Category'">
        <div class="row">
            <div class="col-md-6">
                <x-admin.form.input name="name" label="Name" :value="$category?->name" maxlength="100" required />
            </div>
            <div class="col-md-6">
                <x-admin.icon-picker name="icon" label="Icon" :value="old('icon', $category?->icon)"
                                     help="Shown beside the category in the storefront menu. Click “Choose icon” to browse." />
            </div>
            <div class="col-md-6">
                <x-admin.form.file name="image" label="Image (optional)" accept=".jpg,.jpeg,.png,.webp"
                                   help="JPG, PNG or WEBP, up to 1 MB." data-preview-target="category-preview" />
                <img id="category-preview" alt="Category image preview" width="120" height="120"
                     class="rounded admin-img-cover {{ $category?->hasImage() ? '' : 'd-none' }}"
                     src="{{ $category?->hasImage() ? $category->image_url : '' }}">
                @if ($category?->hasImage())
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" name="remove_image" value="1" id="remove_image" class="custom-control-input" @checked(old('remove_image'))>
                        <label class="custom-control-label" for="remove_image">Remove the current image</label>
                    </div>
                @endif
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$category?->sort_order ?? 0"
                                    help="Lower numbers appear first." />
                <div class="form-group">
                    <label class="d-block">Visibility</label>
                    <x-admin.switch name="is_active" label="Active"
                                    :checked="(bool) old('is_active', $category?->is_active ?? true)" />
                    <x-admin.switch name="show_in_menu" label="Show in the storefront menu"
                                    :checked="(bool) old('show_in_menu', $category?->show_in_menu ?? true)" />
                </div>
            </div>
        </div>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.categories.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $category ? 'Save Changes' : 'Create Category' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>

@push('scripts')
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
@endpush
