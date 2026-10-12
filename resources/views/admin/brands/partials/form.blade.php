{{--
    Shared by create and edit. Expects: $action, optional $brand (edit), optional $method.
--}}
@php($brand ??= null)

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$brand ? 'Edit Brand' : 'New Brand'">
        <div class="row">
            <div class="col-md-6">
                <x-admin.form.input name="name" label="Name" :value="$brand?->name" maxlength="100" required />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$brand?->sort_order ?? 0"
                                    help="Lower numbers appear first." />
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="d-block">Featured</label>
                    <x-admin.switch name="is_featured" label="Show in the home page brand slider"
                                    :checked="(bool) old('is_featured', $brand?->is_featured ?? false)" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="d-block">Status</label>
                    <x-admin.switch name="is_active" label="Active"
                                    :checked="(bool) old('is_active', $brand?->is_active ?? true)" />
                </div>
            </div>
            <div class="col-md-12">
                <x-admin.form.file name="logo" :label="$brand ? 'Logo (leave empty to keep the current one)' : 'Logo'"
                                   accept=".jpg,.jpeg,.png,.webp,.svg" :help="\App\Models\Brand::LOGO_HINT"
                                   data-preview-target="brand-preview" :required="! $brand" />
                <img id="brand-preview" alt="Brand logo preview" width="200" height="100"
                     class="rounded admin-img-contain {{ $brand ? '' : 'd-none' }}"
                     src="{{ $brand ? ($brand->hasLogo() ? $brand->logo_url : asset('admin-assets/img/example-image.jpg')) : '' }}">
            </div>
        </div>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.brands.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $brand ? 'Save Changes' : 'Create Brand' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>

@push('scripts')
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
@endpush
