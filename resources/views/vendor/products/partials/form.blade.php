{{--
    Vendor product form (create and edit), in the vendor panel's dashboard styling.
    Same fields and rules as the admin form, without the home flags and the vendor selector.
    Expects: $action, $reviewRequired, optional $product (edit), optional $method.
    Options ($categoryOptions, $brandOptions) come from ProductFormComposer.
--}}
@php
    $product ??= null;
    $maxGallery = (int) config('marketplace.max_gallery_images', 8);
    $specRows = old('specifications', $product ? $product->specifications->map(fn ($s) => ['key' => $s->key, 'value' => $s->value])->all() : []);
    $value = fn (string $field, $default = null) => old($field, $product?->{$field} ?? $default);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-product-form data-gallery-max="{{ $maxGallery }}" novalidate>
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="wsus__dashboard_profile">
        <div class="wsus__dash_pro_area">
            <h4>basic information</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label" for="name">Product name *</label>
                    <input type="text" id="name" name="name" maxlength="200" required value="{{ $value('name') }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sku">SKU *</label>
                    <input type="text" id="sku" name="sku" maxlength="100" required value="{{ $value('sku') }}" class="form-control @error('sku') is-invalid @enderror">
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="short_description">Short description</label>
                    <textarea id="short_description" name="short_description" rows="2" maxlength="500" class="form-control @error('short_description') is-invalid @enderror">{{ $value('short_description') }}</textarea>
                    <div class="form-text">Shown on product cards and in list view.</div>
                    @error('short_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h4>category &amp; brand</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required class="form-select @error('category_id') is-invalid @enderror"
                            data-dependent-source="{{ route('vendor.categories.sub-categories', ['category' => ':id']) }}"
                            data-dependent-target="#sub_category_id">
                        <option value="">Select a category</option>
                        @foreach ($categoryOptions as $id => $label)
                            <option value="{{ $id }}" @selected((string) $id === (string) $value('category_id'))>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="brand_id">Brand</label>
                    <select id="brand_id" name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                        <option value="">No brand</option>
                        @foreach ($brandOptions as $id => $label)
                            <option value="{{ $id }}" @selected((string) $id === (string) $value('brand_id'))>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="sub_category_id">Sub category</label>
                    <select id="sub_category_id" name="sub_category_id" class="form-select @error('sub_category_id') is-invalid @enderror"
                            data-placeholder="No sub category" data-selected="{{ $value('sub_category_id') }}"
                            data-dependent-source="{{ route('vendor.sub-categories.child-categories', ['subCategory' => ':id']) }}"
                            data-dependent-target="#child_category_id">
                        <option value="">No sub category</option>
                    </select>
                    @error('sub_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="child_category_id">Child category</label>
                    <select id="child_category_id" name="child_category_id" class="form-select @error('child_category_id') is-invalid @enderror"
                            data-placeholder="No child category" data-selected="{{ $value('child_category_id') }}">
                        <option value="">No child category</option>
                    </select>
                    @error('child_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            @include('vendor.products.partials.variants')

            <h4>pricing, offer &amp; stock</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4" data-simple-pricing>
                    <label class="form-label" for="price">Regular price *</label>
                    <input type="number" step="0.01" min="0" id="price" name="price" value="{{ $product?->has_variants ? old('price') : $value('price') }}" class="form-control @error('price') is-invalid @enderror">
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4" data-simple-pricing>
                    <label class="form-label" for="offer_price">Offer price</label>
                    <input type="number" step="0.01" min="0" id="offer_price" name="offer_price" value="{{ $product?->has_variants ? old('offer_price') : $value('offer_price') }}" class="form-control @error('offer_price') is-invalid @enderror">
                    @error('offer_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4" data-simple-pricing>
                    <label class="form-label" for="stock_qty">Quantity in stock *</label>
                    <input type="number" min="0" id="stock_qty" name="stock_qty" value="{{ $value('stock_qty', 0) }}" class="form-control @error('stock_qty') is-invalid @enderror">
                    @error('stock_qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="offer_start_date">Offer starts</label>
                    <input type="date" id="offer_start_date" name="offer_start_date" value="{{ old('offer_start_date', $product?->offer_start_date?->toDateString()) }}" class="form-control @error('offer_start_date') is-invalid @enderror">
                    @error('offer_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="offer_end_date">Offer ends</label>
                    <input type="date" id="offer_end_date" name="offer_end_date" value="{{ old('offer_end_date', $product?->offer_end_date?->toDateString()) }}" class="form-control @error('offer_end_date') is-invalid @enderror">
                    @error('offer_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="status">Status *</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach (\App\Enums\ProductStatus::options() as $status => $label)
                            <option value="{{ $status }}" @selected($status === old('status', $product?->status->value ?? 'active'))>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12"><div class="form-text">The offer price must be lower than the regular price. Give both offer dates or none; the offer runs through the whole end day.</div></div>
            </div>

            <h4>images</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label" for="thumbnail">Thumbnail {{ $product ? '(leave empty to keep it)' : '*' }}</label>
                    <img id="thumbnail-preview" src="{{ $product?->thumbnail_url ?? asset('frontend-assets/images/product-placeholder.svg') }}"
                         alt="Thumbnail preview" width="160" height="160" class="rounded avatar-cover d-block mb-2">
                    <input type="file" id="thumbnail" name="thumbnail" accept=".jpg,.jpeg,.png,.webp" data-preview-target="thumbnail-preview"
                           class="form-control @error('thumbnail') is-invalid @enderror">
                    <div class="form-text">{{ \App\Models\Product::IMAGE_HINT }}</div>
                    @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Gallery</label>
                    @if ($product && $product->images->isNotEmpty())
                        <div class="d-flex flex-wrap" data-gallery-existing>
                            @foreach ($product->images as $image)
                                <div class="me-2 mb-2 text-center" data-gallery-item>
                                    <input type="hidden" name="gallery_order[]" value="{{ $image->id }}">
                                    <img src="{{ $image->url }}" alt="Gallery image {{ $loop->iteration }}" width="90" height="90" class="rounded avatar-cover d-block mb-1">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light" data-gallery-left aria-label="Move left"><i class="fas fa-arrow-left"></i></button>
                                        <button type="button" class="btn btn-light" data-gallery-right aria-label="Move right"><i class="fas fa-arrow-right"></i></button>
                                        <button type="button" class="btn btn-danger" data-gallery-remove aria-label="Remove image"
                                                data-url="{{ route('vendor.products.images.destroy', [$product, $image]) }}"
                                                data-notice="{{ $product->isApproved() && $reviewRequired ? 'The image is deleted right away and the product goes back to review.' : 'The image is deleted right away.' }}"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text mb-2">Arrows change the order (saved with the form); the first image is the hover picture on product cards.</div>
                    @endif
                    <input type="file" name="gallery[]" multiple accept=".jpg,.jpeg,.png,.webp" aria-label="Add gallery images" data-gallery-input
                           class="form-control {{ $errors->has('gallery') || $errors->has('gallery.*') ? 'is-invalid' : '' }}">
                    <div class="form-text" data-gallery-count>Up to {{ $maxGallery }} images.</div>
                    @if ($errors->has('gallery') || $errors->has('gallery.*'))
                        <div class="invalid-feedback d-block">{{ $errors->first('gallery') ?: $errors->first('gallery.*') }}</div>
                    @endif
                    <div class="d-flex flex-wrap mt-2" data-gallery-new></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="video_url">Video link</label>
                    <input type="url" id="video_url" name="video_url" maxlength="255" placeholder="https://youtu.be/..." value="{{ $value('video_url') }}" class="form-control @error('video_url') is-invalid @enderror">
                    @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h4>description</h4>
            <div class="mb-4">
                <textarea id="long_description" name="long_description" rows="8" class="form-control summernote @error('long_description') is-invalid @enderror">{{ $value('long_description') }}</textarea>
                <div class="form-text">Shown in the Description tab. Scripts and unsafe HTML are removed when saving.</div>
                @error('long_description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <h4>specifications</h4>
            <div class="mb-4">
                <p class="text-muted small mb-2">Shown in the “Information” tab, e.g. Material — 100% cotton.</p>
                <div data-spec-rows>
                    @foreach ($specRows as $index => $row)
                        @include('vendor.products.partials.spec-row', ['index' => $index, 'row' => $row])
                    @endforeach
                </div>
                <template data-spec-template>
                    @include('vendor.products.partials.spec-row', ['index' => '__INDEX__', 'row' => ['key' => '', 'value' => '']])
                </template>
                <button type="button" class="btn btn-light" data-spec-add><i class="far fa-plus"></i> Add specification</button>
            </div>

            <h4>seo</h4>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" for="seo_title">SEO title</label>
                    <input type="text" id="seo_title" name="seo_title" maxlength="200" value="{{ $value('seo_title') }}" class="form-control @error('seo_title') is-invalid @enderror">
                    @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="seo_description">SEO description</label>
                    <textarea id="seo_description" name="seo_description" rows="2" maxlength="500" class="form-control @error('seo_description') is-invalid @enderror">{{ $value('seo_description') }}</textarea>
                    @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <button class="common_btn mb-4" type="submit">{{ $product ? 'save changes' : 'create product' }}</button>
            <a class="ms-3" href="{{ route('vendor.products.index') }}">cancel</a>
        </div>
    </div>
</form>

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend-assets/vendor/summernote/summernote-lite.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('frontend-assets/vendor/summernote/summernote-lite.js') }}"></script>
    <script src="{{ asset('frontend-assets/js/image-preview.js') }}"></script>
    <script src="{{ asset('shared-assets/js/dependent-select.js') }}"></script>
    <script src="{{ asset('shared-assets/js/product-form.js') }}"></script>
    <script src="{{ asset('shared-assets/js/variants.js') }}"></script>
    <script src="{{ asset('frontend-assets/js/vendor-product-editor.js') }}"></script>
@endpush
