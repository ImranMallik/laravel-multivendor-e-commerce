{{--
    Shop details form of the "Become a Seller" page.
    Expects: $action, $button, optional $method (PUT for a re-application), optional $vendor (prefill).
--}}
@php($vendor ??= null)

<div class="wsus__dashboard_profile">
    <div class="wsus__dash_pro_area">
        <h4>shop details</h4>
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if (($method ?? 'POST') !== 'POST')
                @method($method)
            @endif
            <div class="row">
                <div class="col-xl-9">
                    <div class="row">
                        <div class="col-xl-6 col-md-6">
                            <div class="wsus__dash_pro_single">
                                <i class="far fa-store"></i>
                                <input type="text" name="shop_name" placeholder="Shop name" maxlength="150" required
                                       value="{{ old('shop_name', $vendor?->shop_name) }}">
                            </div>
                            @error('shop_name')<small class="text-danger d-block mb-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="wsus__dash_pro_single">
                                <i class="far fa-phone-alt"></i>
                                <input type="text" name="shop_phone" placeholder="Shop phone (optional)" maxlength="20"
                                       value="{{ old('shop_phone', $vendor?->phone) }}">
                            </div>
                            @error('shop_phone')<small class="text-danger d-block mb-2">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-xl-12">
                            <div class="wsus__dash_pro_single">
                                <i class="fal fa-map-marker-alt"></i>
                                <input type="text" name="address" placeholder="Shop address" maxlength="500" required
                                       value="{{ old('address', $vendor?->address) }}">
                            </div>
                            @error('address')<small class="text-danger d-block mb-2">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-md-6">
                    <div class="wsus__dash_pro_img">
                        <img id="seller-logo-preview" src="{{ $vendor?->logo_url ?? asset('frontend-assets/images/logo.png') }}"
                             alt="Shop logo" class="img-fluid w-100">
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" aria-label="Shop logo"
                               data-preview-target="seller-logo-preview">
                    </div>
                    <p class="text-muted small mt-2 mb-0">Logo (optional): JPG, PNG or WEBP, up to 1 MB.</p>
                    @error('logo')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="col-xl-12">
                    <button class="common_btn mb-4 mt-2" type="submit">{{ $button }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('frontend-assets/js/image-preview.js') }}"></script>
@endpush
