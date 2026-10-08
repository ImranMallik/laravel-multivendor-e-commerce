<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const PHOTO_FOLDER = 'admins/photos';

    public function __construct(private readonly ImageUploadService $images) {}

    public function edit(): View
    {
        return view('admin.profile.edit', ['admin' => auth('admin')->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $admin = auth('admin')->user();
        $data = $request->safe()->except('photo');
        $oldPhoto = $admin->photo;

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->images->upload($request->file('photo'), self::PHOTO_FOLDER);
        }

        $admin->update($data);

        // Delete the previous file only after the new one is stored and saved.
        if (isset($data['photo'])) {
            $this->images->delete($oldPhoto);
        }

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $admin = auth('admin')->user();

        $admin->update(['password' => $request->validated('password')]);

        Auth::guard('admin')->logoutOtherDevices($request->validated('password'));
        $request->session()->regenerate();

        return back()->with('success', 'Password changed successfully.');
    }

    public function destroyPhoto(): RedirectResponse
    {
        $admin = auth('admin')->user();
        $oldPhoto = $admin->photo;

        $admin->update(['photo' => null]);
        $this->images->delete($oldPhoto);

        return back()->with('success', 'Profile photo removed.');
    }
}
