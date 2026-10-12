{{--
    Admin product form, shared by create and edit. Expects: $action, optional $product (edit), optional $method.
    Options ($categoryOptions, $brandOptions, $vendorOptions) come from ProductFormComposer.
    Behaviour: shared-assets/js/dependent-select.js (category levels), shared-assets/js/product-form.js
    (gallery and specifications), admin-assets/js/product-editor.js (Summernote, vendor search).
--}}
@php
    $product ??= null;
    $maxGallery = (int) config('marketplace.max_gallery_images', 8);
    $specRows = old('specifications', $product ? $product->specifications->map(fn ($s) => ['key' => $s->key, 'value' => $s->value])->all() : []);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-product-form data-gallery-max="{{ $maxGallery }}">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="row">
        <div class="col-lg-8">
            <x-admin.card title="Basic information">
                <div class="row">
                    <div class="col-md-8">
                        <x-admin.form.input name="name" label="Product name" :value="$product?->name" maxlength="200" required />
                    </div>
                    <div class="col-md-4">
                        <x-admin.form.input name="sku" label="SKU" :value="$product?->sku" maxlength="100" required
                                            help="Unique code, shown as “model” in the store." />
                    </div>
                    <div class="col-md-12">
                        <x-admin.form.textarea name="short_description" label="Short description" :value="$product?->short_description"
                                               rows="2" maxlength="500" help="Shown on product cards, in list view and in hot deals." />
                    </div>
                    <div class="col-md-12">
                        <x-admin.form.select name="vendor_id" label="Vendor" placeholder="In-house (no vendor)"
                                             :options="$vendorOptions" :selected="$product?->vendor_id" data-searchable-select
                                             help="Leave empty for an in-house product. Only approved vendors are listed." />
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Category & brand">
                <div class="row">
                    <div class="col-md-6">
                        <x-admin.form.select name="category_id" label="Category" placeholder="Select a category"
                                             :options="$categoryOptions" :selected="$product?->category_id" required
                                             data-dependent-source="{{ route('admin.categories.sub-categories', ['category' => ':id']) }}"
                                             data-dependent-target="#sub_category_id" />
                    </div>
                    <div class="col-md-6">
                        <x-admin.form.select name="brand_id" label="Brand" placeholder="No brand"
                                             :options="$brandOptions" :selected="$product?->brand_id" />
                    </div>
                    <div class="col-md-6">
                        <x-admin.form.select name="sub_category_id" label="Sub category" placeholder="No sub category"
                                             :options="[]" data-placeholder="No sub category"
                                             data-selected="{{ old('sub_category_id', $product?->sub_category_id) }}"
                                             data-dependent-source="{{ route('admin.sub-categories.child-categories', ['subCategory' => ':id']) }}"
                                             data-dependent-target="#child_category_id" />
                    </div>
                    <div class="col-md-6">
                        <x-admin.form.select name="child_category_id" label="Child category" placeholder="No child category"
                                             :options="[]" data-placeholder="No child category"
                                             data-selected="{{ old('child_category_id', $product?->child_category_id) }}" />
                    </div>
                </div>
            </x-admin.card>

            @include('admin.products.partials.variants')

            <x-admin.card title="Description">
                <x-admin.form.textarea name="long_description" label="Long description" :value="$product?->long_description"
                                       class="summernote" rows="8"
                                       help="Shown in the Description tab. Scripts and unsafe HTML are removed when saving." />
            </x-admin.card>

            <x-admin.card title="Specifications">
                <p class="text-muted">Shown in the “Information” tab, e.g. Material — 100% cotton.</p>
                @error('specifications')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <div data-spec-rows>
                    @foreach ($specRows as $index => $row)
                        @include('admin.products.partials.spec-row', ['index' => $index, 'row' => $row])
                    @endforeach
                </div>
                <template data-spec-template>
                    @include('admin.products.partials.spec-row', ['index' => '__INDEX__', 'row' => ['key' => '', 'value' => '']])
                </template>
                <x-admin.button variant="light" icon="fas fa-plus" data-spec-add>Add specification</x-admin.button>
            </x-admin.card>

            <x-admin.card title="SEO">
                <x-admin.form.input name="seo_title" label="SEO title" :value="$product?->seo_title" maxlength="200"
                                    help="Defaults to the product name." />
                <x-admin.form.textarea name="seo_description" label="SEO description" :value="$product?->seo_description"
                                       rows="2" maxlength="500" help="Defaults to the short description." />
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Status & home sections">
                <x-admin.form.select name="status" label="Status" :options="\App\Enums\ProductStatus::options()"
                                     :selected="$product?->status->value ?? 'active'" required
                                     help="Draft and Inactive products are hidden from the store." />
                <label class="d-block">Home page sections</label>
                @foreach (\App\Enums\ProductFlag::cases() as $flag)
                    <div>
                        <x-admin.switch :name="$flag->value" :label="$flag->label().' — '.$flag->help()"
                                        :checked="(bool) old($flag->value, $product?->{$flag->value} ?? false)" />
                    </div>
                @endforeach
            </x-admin.card>

            <x-admin.card title="Pricing & offer">
                {{-- With variants the prices live on the variants (variants.js hides these two fields). --}}
                <div data-simple-pricing>
                    <x-admin.form.input name="price" type="number" step="0.01" min="0" label="Regular price" :value="$product?->has_variants ? null : $product?->price" />
                    <x-admin.form.input name="offer_price" type="number" step="0.01" min="0" label="Offer price" :value="$product?->has_variants ? null : $product?->offer_price"
                                        help="Optional. Must be lower than the regular price." />
                </div>
                <div class="row">
                    <div class="col-6">
                        <x-admin.form.input name="offer_start_date" type="date" label="Offer starts" :value="$product?->offer_start_date?->toDateString()" />
                    </div>
                    <div class="col-6">
                        <x-admin.form.input name="offer_end_date" type="date" label="Offer ends" :value="$product?->offer_end_date?->toDateString()" />
                    </div>
                </div>
                <small class="form-text text-muted mt-n2">Both dates or none. The offer runs through the whole end day; dated offers appear in Flash Sell.</small>
            </x-admin.card>

            <x-admin.card title="Stock" data-simple-pricing>
                <x-admin.form.input name="stock_qty" type="number" min="0" label="Quantity in stock" :value="$product?->stock_qty ?? 0" />
            </x-admin.card>

            <x-admin.card title="Images">
                <x-admin.form.file name="thumbnail" :label="$product ? 'Thumbnail (leave empty to keep it)' : 'Thumbnail'"
                                   accept=".jpg,.jpeg,.png,.webp" :help="\App\Models\Product::IMAGE_HINT"
                                   data-preview-target="thumbnail-preview" :required="! $product" />
                <img id="thumbnail-preview" alt="Thumbnail preview" width="120" height="120"
                     class="rounded admin-img-cover mb-3 {{ $product ? '' : 'd-none' }}"
                     src="{{ $product ? ($product->hasThumbnail() ? $product->thumbnail_url : asset('admin-assets/img/example-image.jpg')) : '' }}">

                <label class="d-block">Gallery</label>
                @if ($product && $product->images->isNotEmpty())
                    <div class="d-flex flex-wrap" data-gallery-existing>
                        @foreach ($product->images as $image)
                            <div class="mr-2 mb-2 text-center" data-gallery-item>
                                <input type="hidden" name="gallery_order[]" value="{{ $image->id }}">
                                <img src="{{ $image->url }}" alt="Gallery image {{ $loop->iteration }}" width="90" height="90" class="rounded admin-img-cover d-block mb-1">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light" data-gallery-left aria-label="Move left"><i class="fas fa-arrow-left"></i></button>
                                    <button type="button" class="btn btn-light" data-gallery-right aria-label="Move right"><i class="fas fa-arrow-right"></i></button>
                                    <button type="button" class="btn btn-danger" data-gallery-remove aria-label="Remove image"
                                            data-url="{{ route('admin.products.images.destroy', [$product, $image]) }}"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small class="form-text text-muted mb-2">Arrows change the order (saved with the form); the first image is the hover picture on product cards.</small>
                @endif
                <div class="form-group">
                    <input type="file" name="gallery[]" multiple accept=".jpg,.jpeg,.png,.webp" aria-label="Add gallery images"
                           data-gallery-input
                           class="form-control-file {{ $errors->has('gallery') || $errors->has('gallery.*') ? 'is-invalid' : '' }}">
                    <small class="form-text text-muted" data-gallery-count>Up to {{ $maxGallery }} images.</small>
                    @if ($errors->has('gallery') || $errors->has('gallery.*'))
                        <div class="invalid-feedback d-block">{{ $errors->first('gallery') ?: $errors->first('gallery.*') }}</div>
                    @endif
                </div>
                <div class="d-flex flex-wrap" data-gallery-new></div>
            </x-admin.card>

            <x-admin.card title="Video">
                <x-admin.form.input name="video_url" type="url" label="Video link" :value="$product?->video_url" maxlength="255"
                                    placeholder="https://youtu.be/..." help="YouTube or Vimeo; shown as a play button on the gallery." />
            </x-admin.card>
        </div>
    </div>

    <div class="text-right mb-4">
        <x-admin.button :href="$product ? route('admin.products.show', $product) : route('admin.products.index')" variant="light">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="fas fa-save">{{ $product ? 'Save Changes' : 'Create Product' }}</x-admin.button>
    </div>
</form>

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/modules/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="{{ asset('admin-assets/modules/select2/dist/css/select2.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('admin-assets/modules/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('admin-assets/modules/select2/dist/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
    <script src="{{ asset('shared-assets/js/dependent-select.js') }}"></script>
    <script src="{{ asset('shared-assets/js/product-form.js') }}"></script>
    <script src="{{ asset('shared-assets/js/variants.js') }}"></script>
    <script src="{{ asset('admin-assets/js/product-editor.js') }}"></script>
@endpush
