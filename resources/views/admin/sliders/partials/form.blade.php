{{--
    Shared by create and edit. Expects: $action, optional $slider (edit), optional $method.
--}}
@php($slider ??= null)

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <x-admin.card :title="$slider ? 'Edit Slider' : 'New Slider'">
        <div class="row">
            <div class="col-md-6">
                <x-admin.form.input name="top_text" label="Top text" :value="$slider?->top_text" maxlength="100"
                                    help="Small line above the title, e.g. “new arrivals”." />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="title" label="Title" :value="$slider?->title" maxlength="150" required />
            </div>
            <div class="col-md-12">
                <x-admin.form.input name="offer_text" label="Offer text" :value="$slider?->offer_text" maxlength="150"
                                    help="Line under the title, e.g. “start at $99.00”." />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="button_text" label="Button text" :value="$slider?->button_text" maxlength="50"
                                    help="The button shows only when both the text and the link are filled in." />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="button_url" type="url" label="Button link" :value="$slider?->button_url" maxlength="255"
                                    placeholder="https://example.com/shop" />
            </div>
            <div class="col-md-6">
                <x-admin.form.input name="sort_order" type="number" min="0" label="Sort order" :value="$slider?->sort_order ?? 0"
                                    help="Lower numbers appear first." />
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="d-block">Status</label>
                    <x-admin.switch name="is_active" label="Active (shown on the home page)"
                                    :checked="(bool) old('is_active', $slider?->is_active ?? true)" />
                </div>
            </div>
            <div class="col-md-12">
                <x-admin.form.file name="image" :label="$slider ? 'Image (leave empty to keep the current one)' : 'Image'"
                                   accept=".jpg,.jpeg,.png,.webp" :help="\App\Models\Slider::IMAGE_HINT"
                                   data-preview-target="slider-preview" :required="! $slider" />
                <img id="slider-preview" alt="Slider image preview" width="390" height="150"
                     class="rounded admin-img-cover {{ $slider ? '' : 'd-none' }}"
                     src="{{ $slider ? ($slider->hasImage() ? $slider->image_url : asset('admin-assets/img/example-image.jpg')) : '' }}">
            </div>
        </div>

        <x-slot:footer>
            <div class="text-right">
                <x-admin.button :href="route('admin.sliders.index')" variant="light">Cancel</x-admin.button>
                <x-admin.button type="submit" icon="fas fa-save">{{ $slider ? 'Save Changes' : 'Create Slider' }}</x-admin.button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</form>

@push('scripts')
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
@endpush
