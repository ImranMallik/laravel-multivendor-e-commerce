@extends('admin.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    <div class="row mt-sm-4">
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    <img alt="photo" src="{{ $admin->photo_url }}" class="rounded-circle profile-widget-picture admin-img-cover">
                </div>
                <div class="profile-widget-description text-center">
                    <div class="profile-widget-name">{{ $admin->name }}</div>
                    <div class="text-muted">{{ $admin->email }}</div>
                    @if ($admin->phone)
                        <div class="text-muted">{{ $admin->phone }}</div>
                    @endif
                </div>
                @if ($admin->photo)
                    <div class="card-footer text-center">
                        <form method="POST" action="{{ route('admin.profile.photo.destroy') }}"
                              data-confirm="Your profile photo will be removed and replaced with the default avatar."
                              data-confirm-title="Remove photo?"
                              data-confirm-button="Yes, remove it">
                            @csrf
                            @method('DELETE')
                            <x-admin.button type="submit" variant="danger" outline size="sm" icon="fas fa-trash">Remove photo</x-admin.button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-md-12 col-lg-7">
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <x-admin.card title="Edit Profile">
                    <x-admin.form.input name="name" label="Name" :value="$admin->name" required />
                    <x-admin.form.input name="email" type="email" label="Email" :value="$admin->email" required />
                    <x-admin.form.input name="phone" label="Phone" :value="$admin->phone" />
                    <x-admin.form.file name="photo" label="Profile photo" accept=".jpg,.jpeg,.png,.webp"
                                       help="JPG, PNG or WEBP, up to 2 MB." data-preview-target="photo-preview" />
                    <img id="photo-preview" alt="Preview" class="rounded-circle d-none admin-img-cover" width="80" height="80">

                    <x-slot:footer>
                        <div class="text-right"><x-admin.button type="submit">Save Changes</x-admin.button></div>
                    </x-slot:footer>
                </x-admin.card>
            </form>

            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf
                @method('PUT')
                <x-admin.card title="Change Password">
                    <x-admin.form.input name="current_password" type="password" label="Current password" error-bag="updatePassword" autocomplete="current-password" required />
                    <x-admin.form.input name="password" type="password" label="New password" error-bag="updatePassword"
                                        help="At least 8 characters, with letters and numbers." autocomplete="new-password" required />
                    <x-admin.form.input name="password_confirmation" type="password" label="Confirm new password" error-bag="updatePassword" autocomplete="new-password" required />

                    <x-slot:footer>
                        <div class="text-right"><x-admin.button type="submit">Change Password</x-admin.button></div>
                    </x-slot:footer>
                </x-admin.card>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/image-preview.js') }}"></script>
@endpush
