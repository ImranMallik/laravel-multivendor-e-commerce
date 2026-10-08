<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\Request;

class ChangePasswordAction
{
    public function execute(User $user, string $password, Request $request): void
    {
        $user->update(['password' => $password]);

        // Rotate the session id after a credential change.
        $request->session()->regenerate();
    }
}
