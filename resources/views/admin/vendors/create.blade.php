@extends('admin.layouts.app')

@section('title', 'Add Vendor')
@section('page-title', 'Add Vendor')

@section('page-actions')
    <x-admin.button :href="route('admin.vendors.index')" variant="light" icon="fas fa-arrow-left">Back to list</x-admin.button>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.vendors.store') }}" enctype="multipart/form-data">
        @csrf

        <x-admin.card title="Owner">
            <div class="row">
                <div class="col-md-6">
                    <x-admin.form.input name="name" label="Name" maxlength="100" required />
                </div>
                <div class="col-md-6">
                    <x-admin.form.input name="email" type="email" label="Email" maxlength="255" required
                                        help="An existing customer keeps their account and gets this shop. A new email gets an account and a set-password email." />
                    @if (session('existing_vendor_id'))
                        <p class="mt-n2 mb-3"><a href="{{ route('admin.vendors.show', session('existing_vendor_id')) }}">View this user's shop</a></p>
                    @endif
                </div>
                <div class="col-md-6">
                    <x-admin.form.input name="phone" label="Phone" maxlength="20" />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Shop">
            <div class="row">
                <div class="col-md-6">
                    <x-admin.form.input name="shop_name" label="Shop name" maxlength="150" required />
                </div>
                <div class="col-md-6">
                    <x-admin.form.input name="shop_phone" label="Shop phone" maxlength="20" />
                </div>
                <div class="col-md-12">
                    <x-admin.form.textarea name="address" label="Address" rows="3" maxlength="500" required />
                </div>
                <div class="col-md-12">
                    <x-admin.form.file name="logo" label="Logo (optional)" accept=".jpg,.jpeg,.png,.webp"
                                       help="JPG, PNG or WEBP, up to 1 MB." data-preview-target="vendor-logo-preview" />
                    <img id="vendor-logo-preview" alt="Shop logo preview" width="120" height="120" class="rounded admin-img-cover d-none">
                </div>
            </div>

            <x-slot:footer>
                <div class="text-right">
                    <x-admin.button :href="route('admin.vendors.index')" variant="light">Cancel</x-admin.button>
                    <x-admin.button type="submit" icon="fas fa-save">Create Vendor</x-admin.button>
                </div>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
@endpush
