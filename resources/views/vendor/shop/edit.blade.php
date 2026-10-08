@extends('dashboard.layouts.app')

@section('title', 'Shop Settings')
@section('page-title', 'shop settings')

@section('content')
    <div class="wsus__dashboard_profile">
        <div class="wsus__dash_pro_area">
            <h4>shop information</h4>
            <form method="POST" action="{{ route('vendor.shop.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-xl-9">
                        <div class="row">
                            <div class="col-xl-6 col-md-6">
                                <div class="wsus__dash_pro_single">
                                    <i class="far fa-store"></i>
                                    <input type="text" name="shop_name" placeholder="Shop name" value="{{ old('shop_name', $vendor->shop_name) }}" required>
                                </div>
                            </div>
                            <div class="col-xl-6 col-md-6">
                                <div class="wsus__dash_pro_single">
                                    <i class="far fa-phone-alt"></i>
                                    <input type="text" name="phone" placeholder="Shop phone" value="{{ old('phone', $vendor->phone) }}">
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="wsus__dash_pro_single">
                                    <i class="fal fa-map-marker-alt"></i>
                                    <input type="text" name="address" placeholder="Address" value="{{ old('address', $vendor->address) }}" required>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="wsus__dash_pro_single">
                                    <textarea cols="3" rows="5" name="description" placeholder="About your shop">{{ old('description', $vendor->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-md-6">
                        <div class="wsus__dash_pro_img">
                            <img id="logo-preview" src="{{ $vendor->logo_url }}" alt="{{ $vendor->shop_name }}" class="img-fluid w-100">
                            <input type="file" name="logo" id="logo" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <small class="text-muted d-block mt-2">JPG, PNG or WEBP, up to 2 MB.</small>
                    </div>
                    <div class="col-xl-12">
                        <button class="common_btn mb-4 mt-2" type="submit">save shop settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('logo').addEventListener('change', function () {
            var file = this.files && this.files[0];

            if (file) {
                document.getElementById('logo-preview').src = URL.createObjectURL(file);
            }
        });
    </script>
@endpush
