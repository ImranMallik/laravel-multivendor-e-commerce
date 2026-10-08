<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Actions\User\ChangePasswordAction;
use App\Actions\User\RemoveAvatarAction;
use App\Actions\User\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\UpdatePasswordRequest;
use App\Http\Requests\Frontend\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('frontend.account.profile', ['customer' => auth('web')->user()]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->safe()->except('avatar'), $request->file('avatar'));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function destroyAvatar(Request $request, RemoveAvatarAction $action): RedirectResponse
    {
        $action->execute($request->user());

        return back()->with('success', 'Profile photo removed.');
    }

    public function updatePassword(UpdatePasswordRequest $request, ChangePasswordAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated('password'), $request);

        return back()->with('success', 'Password changed successfully.');
    }
}
