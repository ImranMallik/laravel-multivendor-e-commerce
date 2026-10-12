<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\LoginRequest;
use App\Services\GuardSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('frontend.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // One login for everyone; an approved shop lands in the vendor panel, others in the account.
        return redirect()->intended($request->user()->homeRoute());
    }

    public function destroy(Request $request, GuardSessionService $sessions): RedirectResponse
    {
        $sessions->logout($request, 'web');

        return redirect()->route('home');
    }
}
