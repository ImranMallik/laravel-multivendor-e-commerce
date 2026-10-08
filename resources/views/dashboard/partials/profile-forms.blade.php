{{--
    Shared by the customer account and the vendor panel.
    Expects: $user, $updateRoute, $passwordRoute, $avatarRemoveRoute
--}}
@push('scripts')
    <script src="{{ asset('frontend-assets/js/image-preview.js') }}"></script>
@endpush

<div class="wsus__dashboard_profile">
    <div class="wsus__dash_pro_area">
        <h4>basic information</h4>
        <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-xl-9">
                    <div class="row">
                        <div class="col-xl-6 col-md-6">
                            <div class="wsus__dash_pro_single">
                                <i class="fas fa-user-tie"></i>
                                <input type="text" name="name" placeholder="Name" value="{{ old('name', $user->name) }}" required>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="wsus__dash_pro_single">
                                <i class="far fa-phone-alt"></i>
                                <input type="text" name="phone" placeholder="Phone" value="{{ old('phone', $user->phone) }}">
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="wsus__dash_pro_single">
                                <i class="fal fa-envelope-open"></i>
                                <input type="email" name="email" placeholder="Email" value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-md-6">
                    <div class="wsus__dash_pro_img">
                        <img id="avatar-preview" src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="img-fluid w-100 avatar-cover">
                        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" aria-label="Profile photo"
                               data-preview-target="avatar-preview">
                    </div>
                    <p class="text-muted small mt-2 mb-0">JPG, PNG or WEBP, up to 2 MB. Use the file bar under the photo to choose one.</p>
                    @if ($user->hasAvatar())
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                data-confirm="Your profile photo will be removed and replaced with the default picture."
                                data-confirm-title="Remove photo?"
                                data-confirm-button="Yes, remove it"
                                data-confirm-form="remove-avatar-form">Remove photo</button>
                    @endif
                </div>
                <div class="col-xl-12">
                    <button class="common_btn mb-4 mt-2" type="submit">save changes</button>
                </div>
            </div>
        </form>

        @if ($user->hasAvatar())
            <form id="remove-avatar-form" method="POST" action="{{ $avatarRemoveRoute }}" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        @endif

        <h4>change password</h4>
        <form method="POST" action="{{ $passwordRoute }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-xl-4 col-md-6">
                    <div class="wsus__dash_pro_single">
                        <i class="fas fa-unlock-alt"></i>
                        <input type="password" name="current_password" placeholder="Current Password" autocomplete="current-password" required>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="wsus__dash_pro_single">
                        <i class="fas fa-lock-alt"></i>
                        <input type="password" name="password" placeholder="New Password" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="wsus__dash_pro_single">
                        <i class="fas fa-lock-alt"></i>
                        <input type="password" name="password_confirmation" placeholder="Confirm Password" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="col-xl-12">
                    <button class="common_btn mt-2" type="submit">change password</button>
                </div>
            </div>
        </form>
    </div>
</div>
